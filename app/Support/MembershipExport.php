<?php

namespace App\Support;

use App\Models\Membership;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;
use XMLWriter;
use ZipArchive;

class MembershipExport
{
    private const COLUMNS = ['id', 'cbm_id', 'name', 'phone', 'email', 'age_range', 'gender', 'state', 'lga', 'ward', 'pu', 'delimitation_code', 'cbm_delimitation_code', 'want_to_be_contacted', 'same_address', 'support_us', 'created_at', 'updated_at'];

    /** @param Builder<Membership> $query */
    public function download(Builder $query, string $type): StreamedResponse
    {
        $mime = match ($type) {
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv; charset=UTF-8',
            'json' => 'application/json',
            'sql' => 'application/sql',
        };

        return response()->streamDownload(function () use ($query, $type): void {
            $members = $query->with(['lgaInfo', 'wardInfo', 'puInfo'])->lazy(500);
            if ($type === 'xlsx') {
                $this->xlsx($members);

                return;
            }
            $output = fopen('php://output', 'wb');
            if ($type === 'csv') {
                fwrite($output, "\xEF\xBB\xBF");
                fputcsv($output, [...self::COLUMNS, 'lga_name', 'ward_name', 'polling_unit_name', 'has_voters_card'], escape: '');
            } elseif ($type === 'json') {
                fwrite($output, '[');
            } else {
                fwrite($output, "-- CBM membership export: MySQL INSERT statements for an existing memberships table.\n-- Location IDs refer to the original location tables. Existing IDs are preserved.\nSET NAMES utf8mb4;\nSTART TRANSACTION;\n");
            }
            $first = true;
            foreach ($members as $member) {
                $row = $this->row($member);
                if ($type === 'csv') {
                    fputcsv($output, array_map(fn ($value) => is_string($value) && preg_match('/^[\s]*[=+@\-]/u', $value) ? "'".$value : $value, array_values($row)), escape: '');
                } elseif ($type === 'json') {
                    fwrite($output, ($first ? '' : ',').json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
                } else {
                    $values = array_map(fn (string $column): string => $row[$column] === null ? 'NULL' : "CONVERT(X'".bin2hex((string) $row[$column])."' USING utf8mb4)", self::COLUMNS);
                    fwrite($output, 'INSERT INTO `memberships` (`'.implode('`, `', self::COLUMNS).'`) VALUES ('.implode(', ', $values).");\n");
                }
                $first = false;
            }
            fwrite($output, match ($type) {
                'json' => "]\n", 'sql' => "COMMIT;\n", default => ''
            });
            fclose($output);
        }, 'cbm-members-'.now()->format('Y-m-d-His').'.'.$type, ['Content-Type' => $mime, 'Cache-Control' => 'private, no-store']);
    }

    /** @return array<string, mixed> */
    private function row(Membership $member): array
    {
        $row = [];
        foreach (self::COLUMNS as $column) {
            $row[$column] = $member->getRawOriginal($column);
        }

        return [...$row, 'lga_name' => $member->lgaInfo?->name, 'ward_name' => $member->wardInfo?->name, 'polling_unit_name' => $member->puInfo?->name, 'has_voters_card' => $member->ward === null ? 'no' : 'yes'];
    }

    /** @param iterable<Membership> $members */
    private function xlsx(iterable $members): void
    {
        $sheetPath = tempnam(sys_get_temp_dir(), 'cbm-sheet-');
        $zipPath = tempnam(sys_get_temp_dir(), 'cbm-xlsx-');
        try {
            $xml = new XMLWriter;
            $xml->openUri($sheetPath);
            $xml->startDocument('1.0', 'UTF-8');
            $xml->startElement('worksheet');
            $xml->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $xml->startElement('sheetData');
            $this->xlsxRow($xml, [...self::COLUMNS, 'lga_name', 'ward_name', 'polling_unit_name', 'has_voters_card']);
            foreach ($members as $member) {
                $this->xlsxRow($xml, array_values($this->row($member)));
            }
            $xml->endElement();
            $xml->endElement();
            $xml->endDocument();
            $xml->flush();
            unset($xml);
            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Unable to create Excel export.');
            }
            $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
            $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
            $zip->addFromString('xl/workbook.xml', '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Members" sheetId="1" r:id="rId1"/></sheets></workbook>');
            $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
            $zip->addFile($sheetPath, 'xl/worksheets/sheet1.xml');
            if (! $zip->close()) {
                throw new \RuntimeException('Unable to finish Excel export.');
            }
            readfile($zipPath);
        } finally {
            unlink($sheetPath);
            unlink($zipPath);
        }
    }

    /** @param array<int, mixed> $values */
    private function xlsxRow(XMLWriter $xml, array $values): void
    {
        $xml->startElement('row');
        foreach ($values as $value) {
            $xml->startElement('c');
            $xml->writeAttribute('t', 'inlineStr');
            $xml->startElement('is');
            $xml->startElement('t');
            $xml->writeAttribute('xml:space', 'preserve');
            $xml->text(preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', (string) $value));
            $xml->endElement();
            $xml->endElement();
            $xml->endElement();
        }
        $xml->endElement();
    }
}

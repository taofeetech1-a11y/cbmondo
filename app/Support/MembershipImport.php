<?php

namespace App\Support;

use App\Models\Membership;
use App\Models\PollingUnit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Throwable;
use ZipArchive;

class MembershipImport
{
    public const COLUMNS = ['name', 'phone', 'email', 'gender', 'age_range', 'has_voters_card', 'lga', 'ward', 'pu', 'support_us', 'want_to_be_contacted', 'same_address'];

    /** @return array<int, array<string, string|null>> */
    public function read(UploadedFile $file): array
    {
        try {
            $rows = strtolower($file->getClientOriginalExtension()) === 'xlsx'
                ? $this->excel($file->getPathname()) : $this->csv($file->getPathname());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw ValidationException::withMessages(['file' => 'The file could not be read. Use the template and save as UTF-8 CSV or Excel (.xlsx).']);
        }
        $headers = array_shift($rows) ?? [];
        $headers = array_map(fn ($value) => strtolower(trim((string) $value, "\xEF\xBB\xBF \t\n\r")), $headers);
        if (count($headers) !== count(self::COLUMNS) || array_diff(self::COLUMNS, $headers)) {
            throw ValidationException::withMessages(['file' => 'The header must contain each template column exactly once, with no extra columns.']);
        }
        $records = [];
        foreach ($rows as $index => $values) {
            if (count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }
            if (count($values) !== count($headers)) {
                throw ValidationException::withMessages(['file' => 'Row '.($index + 2).' has the wrong number of columns.']);
            }
            foreach ($values as $value) {
                if (! mb_check_encoding((string) $value, 'UTF-8') || strlen((string) $value) > 1000) {
                    throw ValidationException::withMessages(['file' => 'Row '.($index + 2).' contains invalid encoding or an oversized cell.']);
                }
            }
            $records[$index + 2] = MembershipValidation::normalize(array_combine($headers, array_map(fn ($value) => trim((string) $value), $values)));
        }
        if ($records === []) {
            throw ValidationException::withMessages(['file' => 'Add at least one member below the header.']);
        }

        return $records;
    }

    /** @return list<array<mixed>> */
    private function csv(string $path): array
    {
        $stream = fopen($path, 'r');
        try {
            $rows = [];
            while (($row = fgetcsv($stream, null, ',', '"', '')) !== false) {
                $rows[] = $row;
                if (count($rows) > 501) {
                    throw ValidationException::withMessages(['file' => 'Use no more than 500 data rows per import.']);
                }
            }

            return $rows;
        } finally {
            fclose($stream);
        }
    }

    /** @return list<array<mixed>> */
    private function excel(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages(['file' => 'This is not a valid Excel workbook.']);
        }
        try {
            $bytes = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $bytes += $zip->statIndex($i)['size'];
            }
            if ($bytes > 20 * 1024 * 1024 || $zip->numFiles > 200) {
                throw ValidationException::withMessages(['file' => 'The workbook is too complex. Copy the data into a fresh template.']);
            }
        } finally {
            $zip->close();
        }
        $reader = new Xlsx;
        $reader->setReadDataOnly(true);
        $info = $reader->listWorksheetInfo($path);
        if (count($info) !== 1 || $info[0]['totalRows'] > 501 || $info[0]['totalColumns'] > count(self::COLUMNS)) {
            throw ValidationException::withMessages(['file' => 'Use one worksheet, the 12 template columns, and no more than 500 data rows.']);
        }
        $book = $reader->load($path);
        try {
            return $book->getSheet(0)->toArray(null, false, false, false);
        } finally {
            $book->disconnectWorksheets();
        }
    }

    /** @return array<int, list<string>> */
    public function errors(array $records): array
    {
        $errors = [];
        $seen = [];
        foreach ($records as $row => $data) {
            $validator = Validator::make($data, MembershipValidation::rules($data));
            $messages = $validator->errors()->all();
            foreach ($data as $value) {
                if (is_string($value) && str_starts_with($value, '=')) {
                    $messages[] = 'Formulas are not supported. Paste values only.';
                }
            }
            if ($data['has_voters_card'] === 'no' && ($data['ward'] !== '' || $data['pu'] !== '' || $data['same_address'] !== '')) {
                $messages[] = 'Leave ward, pu and same_address blank for a member without a voter card.';
            }
            foreach (['phone', 'email'] as $field) {
                $value = $data[$field];
                if ($value !== null && $value !== '') {
                    if (isset($seen[$field][$value])) {
                        $first = $seen[$field][$value];
                        $messages[] = "Duplicate {$field} in this file (row {$first}).";
                        $errors[$first][] = "Duplicate {$field} in this file (row {$row}).";
                    } else {
                        $seen[$field][$value] = $row;
                    }
                }
            }
            if ($messages !== []) {
                $errors[$row] = array_merge($errors[$row] ?? [], $messages);
            }
        }

        return $errors;
    }

    public function save(array $records): int
    {
        return DB::transaction(function () use ($records) {
            $units = PollingUnit::whereIn('id', collect($records)->where('has_voters_card', 'yes')->pluck('pu'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $errors = $this->errors($records);
            if ($errors !== []) {
                $messages = [];
                foreach ($errors as $row => $issues) {
                    foreach ($issues as $issue) {
                        $messages[] = "Row {$row}: {$issue}";
                    }
                }
                throw ValidationException::withMessages(['import' => $messages]);
            }
            foreach ($records as $data) {
                $hasCard = $data['has_voters_card'] === 'yes';
                unset($data['has_voters_card']);
                $data['ward'] = $hasCard ? $data['ward'] : null;
                $data['pu'] = $hasCard ? $data['pu'] : null;
                $data['same_address'] = $hasCard ? $data['same_address'] : null;
                $data['delimitation_code'] = null;
                $data['cbm_delimitation_code'] = null;
                if ($hasCard) {
                    $unit = $units[$data['pu']];
                    $data['delimitation_code'] = $unit->delimitation_code;
                    $data['cbm_delimitation_code'] = $unit->delimitation_code.'/'.str_pad((string) $unit->next_member_number, 3, '0', STR_PAD_LEFT);
                    $unit->increment('next_member_number');
                }
                Membership::create($data);
            }

            return count($records);
        });
    }
}

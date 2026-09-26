<?php

namespace App\Support;

use App\Models\Membership;
use App\View\MembershipCard;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class MembershipCardBatch
{
    public function __construct(private MembershipCard $card) {}

    /** @param Builder<Membership> $query */
    public function download(Builder $query, string $format): StreamedResponse
    {
        return response()->streamDownload(function () use ($query, $format): void {
            set_time_limit(0);
            $files = [];
            $archivePath = tempnam(sys_get_temp_dir(), 'cbm-cards-');
            $zip = null;
            try {
                if ($format === 'zip') {
                    $zip = new ZipArchive;
                    if ($zip->open($archivePath, ZipArchive::OVERWRITE) !== true) {
                        throw new RuntimeException('Unable to create card archive.');
                    }
                } else {
                    $pdf = new \FPDF('P', 'mm', 'A4');
                    $pdf->SetAutoPageBreak(false);
                    $pdf->SetTitle('CBM Ondo membership cards');
                    $pdf->SetCreator('CBM Ondo');
                }
                $index = 0;
                foreach ($query->lazy(100) as $member) {
                    $path = tempnam(sys_get_temp_dir(), 'cbm-card-');
                    $files[] = $path;
                    if (file_put_contents($path, $this->card->render($member)) === false) {
                        throw new RuntimeException('Unable to prepare membership card.');
                    }
                    if ($zip !== null) {
                        if (! $zip->addFile($path, 'CBM-ID-'.$member->id.'.png')) {
                            throw new RuntimeException('Unable to add membership card.');
                        }
                    } else {
                        if ($index % 8 === 0) {
                            $pdf->AddPage();
                            $pdf->SetFont('Helvetica', '', 9);
                            $pdf->SetXY(15, 8);
                            $pdf->Cell(180, 6, 'CBM Ondo - print at Actual size / 100% - cut along card edges', 0, 0, 'C');
                        }
                        $slot = $index % 8;
                        $pdf->Image($path, 15 + ($slot % 2) * 95, 25 + intdiv($slot, 2) * 63, 85.6, 85.6 * MembershipCard::HEIGHT / MembershipCard::WIDTH, 'PNG');
                    }
                    $index++;
                }
                if ($zip !== null) {
                    if (! $zip->close()) {
                        throw new RuntimeException('Unable to finish card archive.');
                    }
                    $zip = null;
                    readfile($archivePath);
                } else {
                    echo $pdf->Output('S');
                }
            } finally {
                if ($zip !== null) {
                    $zip->close();
                }
                foreach ($files as $path) {
                    if (is_file($path)) {
                        unlink($path);
                    }
                }
                if (is_file($archivePath)) {
                    unlink($archivePath);
                }
            }
        }, 'cbm-id-cards-'.now()->format('Y-m-d-His').'.'.$format, ['Content-Type' => $format === 'zip' ? 'application/zip' : 'application/pdf', 'Cache-Control' => 'private, no-store']);
    }
}

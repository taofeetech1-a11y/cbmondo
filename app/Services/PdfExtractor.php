<?php

namespace App\Services;

use Illuminate\Http\File;
use Illuminate\Support\Facades\File as FS;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\PdfToText\Pdf;
use Symfony\Component\Process\Process;

class PdfExtractor
{
    public function extractText(string $pdfPath): string
    {
        return Pdf::getText(
            $pdfPath,
            config('services.poppler.pdftotext'),
            ['layout']
        );
    }

    /** Parse fields from the text. Adjust the patterns to match your PDF's labels. */
    public function parseFields(string $text): array
    {
        $grab = fn (string $pattern) => preg_match($pattern, $text, $m) ? trim($m[1]) : null;

        return [
            'name' => $grab('/Name\s*[:\-]\s*(.+)/i'),
            'phone' => $grab('/(?:Phone|Tel|Mobile)[^\d+]*([+\d][\d\s\-()]{7,})/i'),
            'email' => $grab('/([A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,})/'),
            'address' => $grab('/Address\s*[:\-]\s*(.+)/i'),
        ];
    }

    /** Extract the largest embedded image (assumed to be the photo) and store it. */
    public function extractPhoto(string $pdfPath): ?string
    {
        $tmp = storage_path('app/tmp/'.Str::uuid());
        FS::makeDirectory($tmp, 0755, true);

        try {
            $process = new Process([
                config('services.poppler.pdfimages'),
                '-all',
                '-f',
                '1',
                '-l',
                '1', // native formats, first page only
                $pdfPath,
                $tmp.'/img',
            ]);
            $process->setTimeout(60)->run();

            $best = null;
            $bestArea = 0;

            foreach (glob($tmp.'/img-*') as $file) {
                $info = @getimagesize($file);
                if (! $info) {
                    continue;
                }                 // skip unreadable files
                [$w, $h] = $info;
                if ($w < 80 || $h < 80) {
                    continue;
                }     // skip logos/icons/masks
                if ($w * $h > $bestArea) {
                    $bestArea = $w * $h;
                    $best = $file;
                }
            }

            if (! $best) {
                return null;
            }

            $ext = strtolower(pathinfo($best, PATHINFO_EXTENSION));
            $name = Str::uuid().'.'.$ext;

            return Storage::disk('public')->putFileAs('photos', new File($best), $name);
        } finally {
            FS::deleteDirectory($tmp);
        }
    }
}

<?php

namespace App\View;

use App\Models\Membership;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;
use GdImage;

class MembershipCard
{
    public const WIDTH = 1200;

    public const HEIGHT = 704;

    public function render(Membership $member): string
    {
        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagesavealpha($image, true);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);

        for ($x = 0; $x < self::WIDTH; $x++) {
            $ratio = max(0, ($x - 700) / 500);
            $color = imagecolorallocate($image, (int) (18 - 8 * $ratio), (int) (32 + 48 * $ratio), (int) (69 - 20 * $ratio));
            imageline($image, $x, 0, $x, 193, $color);
        }

        imagefilledellipse($image, 105, 98, 96, 96, $white);
        $logo = imagecreatefrompng(public_path('assets/logo-cityboy.png'));
        $scale = min(104 / imagesx($logo), 104 / imagesy($logo));
        $logoWidth = (int) round(imagesx($logo) * $scale);
        $logoHeight = (int) round(imagesy($logo) * $scale);
        imagecopyresampled($image, $logo, 54, 47, 0, 0, $logoWidth, $logoHeight, imagesx($logo), imagesy($logo));
        imagedestroy($logo);

        $bold = public_path('assets/card-poppins-bold.ttf');
        $heavy = public_path('assets/card-poppins-extrabold.ttf');
        $script = public_path('assets/card-handlee.ttf');
        $navy = imagecolorallocate($image, 19, 35, 78);
        $green = imagecolorallocate($image, 9, 94, 45);
        $muted = imagecolorallocate($image, 52, 68, 112);
        $this->text($image, 'CITY BOY MOVEMENT · ONDO STATE', $bold, 27, 178, 128, 966, $white);
        $this->text($image, 'Name: '.mb_strtoupper($member->name), $heavy, 35, 62, 338, 830, $navy);
        $this->text($image, 'Member', $bold, 27, 62, 394, 500, $green);
        $this->text($image, 'Member ID: '.($member->cbm_delimitation_code ?: 'Not assigned'), $heavy, 35, 62, 496, 830, $navy);
        $this->text($image, 'CBM ID: '.$member->cbm_id, $heavy, 25, 62, 604, 490, $muted);
        $this->text($image, 'Inform . Engage . Empower', $script, 32, 572, 610, 570, $green);

        $writer = new Writer(new GDLibRenderer(232, 4));
        $qr = imagecreatefromstring($writer->writeString($member->cbm_id, 'UTF-8', ErrorCorrectionLevel::M()));
        imagecopy($image, $qr, 906, 222, 0, 0, 232, 232);
        imagedestroy($qr);

        foreach ([[0, 303, 56, 166, 209], [600, 900, 12, 124, 62], [900, 1199, 193, 20, 44]] as [$left, $right, $red, $greenValue, $blue]) {
            imagefilledrectangle($image, $left, 660, $right, 703, imagecolorallocate($image, $red, $greenValue, $blue));
        }

        $border = imagecolorallocate($image, 223, 234, 229);
        imagerectangle($image, 1, 1, 1198, 702, $border);
        imagealphablending($image, false);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        for ($x = 0; $x < 42; $x++) {
            for ($y = 0; $y < 42; $y++) {
                $distance = sqrt((42 - $x) ** 2 + (42 - $y) ** 2);
                if ($distance > 42) {
                    $color = $transparent;
                } elseif ($distance > 39.5) {
                    $color = $border;
                } else {
                    continue;
                }
                imagesetpixel($image, $x, $y, $color);
                imagesetpixel($image, 1199 - $x, $y, $color);
                imagesetpixel($image, $x, 703 - $y, $color);
                imagesetpixel($image, 1199 - $x, 703 - $y, $color);
            }
        }

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        return $png;
    }

    private function text(GdImage $image, string $text, string $font, float $size, int $x, int $baseline, int $maxWidth, int $color): void
    {
        do {
            $bounds = imagettfbbox($size, 0, $font, $text);
            $width = max($bounds[0], $bounds[2], $bounds[4], $bounds[6]) - min($bounds[0], $bounds[2], $bounds[4], $bounds[6]);
            if ($width <= $maxWidth) {
                break;
            }
            $size -= 0.5;
        } while ($size > 1);

        imagettftext($image, $size, 0, $x, $baseline, $color, $font, $text);
    }
}

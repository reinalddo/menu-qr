<?php

declare(strict_types=1);

namespace MenuQr;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

final class Qr
{
    /** Address encoded in the QR code; ?src=qr lets the visit counter tell scans from links. */
    public static function target(): string
    {
        return rtrim(Settings::get('menu_url'), '/') . '/?src=qr';
    }

    public static function svg(): string
    {
        return self::render([
            'outputInterface' => QRMarkupSVG::class,
            'svgAddXmlHeader' => true,
            'drawLightModules' => false,
            'connectPaths' => true,
        ]);
    }

    /** Large PNG, sharp enough to print on a table card. */
    public static function png(): string
    {
        return self::render([
            'outputInterface' => QRGdImagePNG::class,
            'scale' => 24,
        ]);
    }

    private static function render(array $options): string
    {
        // Level M survives a scratched or slightly dirty printed code.
        $options += ['eccLevel' => EccLevel::M, 'outputBase64' => false, 'quietzoneSize' => 4];
        return (string) (new QRCode(new QROptions($options)))->render(self::target());
    }
}

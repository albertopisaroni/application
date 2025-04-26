<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

use BaconQrCode\Writer;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;

class QrController extends Controller
{
    public function show($uuid)
    {
        $url = route('registration.upload.mobile', ['uuid' => $uuid]);

        $renderer = new ImageRenderer(
            new RendererStyle(300),
            new ImagickImageBackEnd()
        );
        
        $writer = new Writer($renderer);
        $qrImage = $writer->writeString($url);

        return response($qrImage)->header('Content-Type', 'image/png');
    }
}
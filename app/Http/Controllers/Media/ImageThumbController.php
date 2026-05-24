<?php

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Services\Media\ImageResizeService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImageThumbController extends Controller
{
    public function __invoke(Request $request, int $width, string $path, ImageResizeService $resizer): BinaryFileResponse
    {
        $width = max(40, min(1600, $width));
        $quality = max(60, min(95, (int) $request->integer('q', 82)));

        $source = $resizer->resolveSource($path);
        if (! $source) {
            abort(404);
        }

        $thumb = $resizer->ensureThumbnail($source, $width, $quality) ?? $source;

        return response()->file($thumb, [
            'Cache-Control' => 'public, max-age=604800, immutable',
            'Content-Type' => 'image/jpeg',
        ]);
    }
}

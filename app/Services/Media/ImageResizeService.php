<?php

namespace App\Services\Media;

use Illuminate\Support\Facades\File;

class ImageResizeService
{
    public function resolveSource(string $relativePath): ?string
    {
        $relativePath = $this->normalizeRelativePath($relativePath);

        if ($relativePath === null) {
            return null;
        }

        $publicPath = public_path($relativePath);
        if (is_file($publicPath)) {
            return $publicPath;
        }

        $storagePath = storage_path('app/public/'.$relativePath);
        if (is_file($storagePath)) {
            return $storagePath;
        }

        return null;
    }

    public function cachedPath(string $sourcePath, int $width, int $quality): string
    {
        $signature = md5($sourcePath.'|'.$width.'|'.$quality.'|'.filemtime($sourcePath));

        return storage_path("app/cache/image-thumbs/{$width}/{$signature}.jpg");
    }

    public function ensureThumbnail(string $sourcePath, int $width, int $quality = 82): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $width = max(40, min(1600, $width));
        $cachePath = $this->cachedPath($sourcePath, $width, $quality);
        $cacheDir = dirname($cachePath);

        if (is_file($cachePath) && filemtime($cachePath) >= filemtime($sourcePath)) {
            return $cachePath;
        }

        if (! File::isDirectory($cacheDir)) {
            File::makeDirectory($cacheDir, 0755, true);
        }

        $image = $this->loadImage($sourcePath);
        if (! $image) {
            return null;
        }

        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);

        if ($originalWidth <= 0 || $originalHeight <= 0) {
            imagedestroy($image);

            return null;
        }

        if ($originalWidth <= $width) {
            imagedestroy($image);

            return $sourcePath;
        }

        $height = (int) round($originalHeight * ($width / $originalWidth));
        $canvas = imagecreatetruecolor($width, $height);
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);
        imagedestroy($image);

        imagejpeg($canvas, $cachePath, $quality);
        imagedestroy($canvas);

        return $cachePath;
    }

    protected function loadImage(string $sourcePath)
    {
        $info = @getimagesize($sourcePath);
        if (! $info) {
            return null;
        }

        return match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => @imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : null,
            IMAGETYPE_GIF => @imagecreatefromgif($sourcePath),
            default => null,
        };
    }

    protected function normalizeRelativePath(string $path): ?string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        if ($path === '' || str_contains($path, '..')) {
            return null;
        }

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return $path;
    }
}

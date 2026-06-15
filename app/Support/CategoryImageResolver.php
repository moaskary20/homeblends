<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class CategoryImageResolver
{
    public function resolve(string $slug, ?string $publicRelative = null, ?string $sourceUrl = null): ?string
    {
        $sourceUrl = $sourceUrl ?: CategoryCatalog::imageSource($slug);
        $publicRelative = $publicRelative ?: 'images/categories/'.$slug.'.jpg';
        $absolutePath = public_path($publicRelative);

        if ($this->shouldUseExistingPublicImage($publicRelative, $sourceUrl)) {
            return $publicRelative;
        }

        if (blank($sourceUrl)) {
            return is_file($absolutePath) ? $publicRelative : null;
        }

        File::ensureDirectoryExists(dirname($absolutePath));

        try {
            $response = Http::timeout(45)->get($sourceUrl);

            if ($response->successful() && strlen($response->body()) > 1024) {
                File::put($absolutePath, $response->body());

                return $publicRelative;
            }
        } catch (\Throwable) {
            // Fall back to configured public path below.
        }

        return is_file($absolutePath) ? $publicRelative : $publicRelative;
    }

    protected function shouldUseExistingPublicImage(string $publicRelative, ?string $sourceUrl): bool
    {
        if (! is_file(public_path($publicRelative))) {
            return false;
        }

        if (blank($sourceUrl)) {
            return true;
        }

        return str_ends_with(strtolower($publicRelative), '.jpg')
            || str_ends_with(strtolower($publicRelative), '.jpeg')
            || str_ends_with(strtolower($publicRelative), '.png')
            || str_ends_with(strtolower($publicRelative), '.webp');
    }
}

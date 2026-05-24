<?php

namespace App\Data;

class SeoMeta
{
    /**
     * @param  array<int, array<string, mixed>>  $schema
     */
    public function __construct(
        public string $title,
        public ?string $description = null,
        public ?string $canonical = null,
        public ?string $robots = null,
        public string $ogType = 'website',
        public ?string $ogTitle = null,
        public ?string $ogDescription = null,
        public ?string $ogImage = null,
        public ?string $ogUrl = null,
        public ?string $twitterCard = null,
        public ?string $twitterSite = null,
        public ?string $googleVerification = null,
        public array $schema = [],
    ) {}

    public function ogTitle(): string
    {
        return $this->ogTitle ?? $this->title;
    }

    public function ogDescription(): ?string
    {
        return $this->ogDescription ?? $this->description;
    }
}

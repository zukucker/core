<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Test\Snippet\_fixtures\testGetLanguageFilesByIso;

use Shopware\Core\Framework\Snippet\Files\LanguageFileInterface;

class en_US_e2 implements LanguageFileInterface
{
    public function getName(): string
    {
        return 'en_us_e2';
    }

    public function getPath(): string
    {
        return __DIR__ . '/en_us_e2.json';
    }

    public function getIso(): string
    {
        return 'en_US';
    }

    public function isBase(): bool
    {
        return $this::BASE_LANGUAGE_FILE;
    }
}

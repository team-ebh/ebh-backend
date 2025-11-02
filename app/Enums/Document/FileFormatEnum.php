<?php

declare(strict_types=1);

namespace App\Enums\Document;

use Filament\Support\Contracts\HasLabel;

enum FileFormatEnum: string implements HasLabel
{
    case PDF = 'PDF';
    //    case JPG = 'JPG';
    case JPEG = 'JPEG';
    case PNG = 'PNG';

    case WEBP = 'WEBP';
    case DOC = 'DOC';
    case DOCX = 'DOCX';

    public function getLabel(): ?string
    {
        return $this->value;
    }

    public function getMimeType(): string
    {
        return match ($this) {
            self::PDF => 'application/pdf',
            self::WEBP => 'application/webp',
            /* self::JPG, */ self::JPEG => 'image/jpeg',
            self::PNG => 'image/png',
            self::DOC => 'application/msword',
            self::DOCX => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        };
    }

    /**
     * Get all available format options as array
     *
     * @return array<string, string>
     */
    public static function getOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->getLabel();
        }

        return $options;
    }

    /**
     * Convert array of format strings to array of MIME types
     *
     * @param  array<string>  $formats
     * @return array<string>
     */
    public static function toMimeTypes(array $formats): array
    {
        $mimeTypes = [];

        foreach ($formats as $format) {
            $formatUpper = strtoupper(trim($format));

            // Try to find matching enum case
            foreach (self::cases() as $case) {
                if ($case->value === $formatUpper || $case->name === $formatUpper) {
                    $mimeTypes[] = $case->getMimeType();

                    break;
                }
            }
        }

        return array_unique($mimeTypes);
    }

    /**
     * Try to get enum case from format string
     */
    public static function tryFromFormat(string $format): ?self
    {
        $formatUpper = strtoupper(trim($format));

        foreach (self::cases() as $case) {
            if ($case->value === $formatUpper || $case->name === $formatUpper) {
                return $case;
            }
        }

        return null;
    }
}

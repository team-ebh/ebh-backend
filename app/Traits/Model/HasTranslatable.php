<?php

declare(strict_types=1);

namespace App\Traits\Model;

use App\Enums\LanguageEnum;
use App\Interfaces\Model\TranslatableInterface;

trait HasTranslatable
{
    //             You can translate it in four different ways:
    //            'name' => $company->translated(Company::COLUMN_NAME),
    //            'name' => $company->getTranslatedAttribute(Company::COLUMN_NAME),
    //            'name' => translated($company, Company::COLUMN_NAME),
    //            'name' => $company->getNameTranslated(),
    //            'name' => $company->{Company::COLUMN_NAME. '_translated'},

    public function translated(string $field): mixed
    {
        if (! $this instanceof TranslatableInterface) {
            return $this->$field;
        }

        if (! in_array($field, $this->getTranslatableColumns(), true)) {
            return $this->$field;
        }

        $locale = app()->getLocale();

        if ($locale === LanguageEnum::ENGLISH->value) {
            return $this->{$field};
        }

        $localizedField = "{$field}_{$locale}";

        return $this->{$localizedField} ?? $this->{$field}; // fallback
    }

    public function getTranslatedAttribute(string $field): mixed
    {
        return $this->translated($field);
    }

    public function __get($key)
    {
        if (str_ends_with($key, '_translated')) {
            $originalField = str_replace('_translated', '', $key);

            return $this->translated($originalField);
        }

        return parent::__get($key);
    }

    public function __call($method, $parameters)
    {
        if (str_starts_with($method, 'get') && str_ends_with($method, 'Translated')) {
            $field = lcfirst(str_replace(['get', 'Translated'], '', $method));

            return $this->translated($field);
        }

        return parent::__call($method, $parameters);
    }
}

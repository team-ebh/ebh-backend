<?php

declare(strict_types=1);

namespace App\Filament\Resources\Documents\Schemas;

use App\Enums\Document\FileFormatEnum;
use App\Models\Document;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make(Document::COLUMN_NAME)
                            ->label(trans('documents.admin.fields.name'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make(Document::COLUMN_DESCRIPTION)
                            ->label(trans('documents.admin.fields.description'))
                            ->rows(3)
                            ->columnSpanFull(),

                        TextInput::make(Document::COLUMN_VALIDITY_PERIOD)
                            ->label(trans('documents.admin.fields.validity_period'))
                            ->placeholder('e.g., 6 months')
                            ->maxLength(255),

                        CheckboxList::make(Document::COLUMN_ACCEPTED_FORMATS)
                            ->label(trans('documents.admin.fields.accepted_formats'))
                            ->options(FileFormatEnum::getOptions())
                            ->columns(3)
                            ->required()
                            ->columnSpanFull(),

                        Toggle::make(Document::COLUMN_IS_REQUIRED)
                            ->label(trans('documents.admin.fields.is_required'))
                            ->helperText(trans('documents.admin.fields.is_required_helper'))
                            ->default(true)
                            ->onColor('warning')
                            ->offColor('gray'),

                        Toggle::make(Document::COLUMN_ENABLED)
                            ->label(trans('documents.admin.fields.enabled'))
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger')
                            ->onIcon('heroicon-m-check-circle')
                            ->offIcon('heroicon-m-x-circle'),
                    ]),
            ]);
    }
}

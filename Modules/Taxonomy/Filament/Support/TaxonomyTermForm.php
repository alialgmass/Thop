<?php

namespace Modules\Taxonomy\Filament\Support;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * The one form shape all four `TaxonomyTerm` types share (US-ADM-03) — bilingual
 * name + active toggle, with an optional color-only field. Used by all four
 * resources' Create/Edit pages so the four don't each redefine an identical
 * schema.
 */
class TaxonomyTermForm
{
    public static function configure(Schema $schema, bool $withHex = false): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    TextInput::make('name_ar')
                        ->label(__('taxonomy::panel.fields.name_ar'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('name_en')
                        ->label(__('taxonomy::panel.fields.name_en'))
                        ->required()
                        ->maxLength(255),
                    ...($withHex ? [
                        ColorPicker::make('hex')
                            ->label(__('taxonomy::panel.fields.hex')),
                    ] : []),
                    Toggle::make('is_active')
                        ->label(__('taxonomy::panel.fields.is_active'))
                        ->default(true),
                ]),
        ]);
    }
}

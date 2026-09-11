<?php

namespace Modules\Admin\Filament\Resources\Banners\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('image')
                ->label(__('admin::panel.banner.fields.image'))
                ->image()
                ->disk((string) config('admin.banners.disk'))
                ->directory('banners')
                ->required(fn (string $operation): bool => $operation === 'create')
                ->maxSize((int) config('admin.banners.max_file_size_kb')),
            TextInput::make('link_url')
                ->label(__('admin::panel.banner.fields.link_url'))
                ->url()
                ->maxLength(2048),
            TextInput::make('position')
                ->label(__('admin::panel.banner.fields.position'))
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->required(),
            DateTimePicker::make('starts_at')
                ->label(__('admin::panel.banner.fields.starts_at'))
                ->helperText(__('admin::panel.banner.fields.starts_at_hint')),
            DateTimePicker::make('ends_at')
                ->label(__('admin::panel.banner.fields.ends_at'))
                ->helperText(__('admin::panel.banner.fields.ends_at_hint'))
                ->after('starts_at'),
            Toggle::make('is_active')
                ->label(__('admin::panel.banner.fields.is_active'))
                ->default(true),
        ]);
    }
}

<?php

namespace Modules\Admin\Filament\Resources\Banners\Pages;

use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Modules\Admin\Actions\ManageBanner;
use Modules\Admin\Filament\Resources\Banners\BannerResource;

class CreateBanner extends CreateRecord
{
    protected static string $resource = BannerResource::class;

    /**
     * Routed through ManageBanner so the panel writes the same
     * `banner.created` audit row as the REST endpoint. `image` is already a
     * stored path string here — Filament's FileUpload stores it itself.
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $admin */
        $admin = auth()->user();

        return app(ManageBanner::class)->create([
            'image' => $data['image'],
            'link_url' => $data['link_url'] ?? null,
            'position' => $data['position'] ?? 0,
            'starts_at' => isset($data['starts_at']) ? Carbon::parse($data['starts_at']) : null,
            'ends_at' => isset($data['ends_at']) ? Carbon::parse($data['ends_at']) : null,
            'is_active' => $data['is_active'] ?? true,
        ], $admin);
    }
}

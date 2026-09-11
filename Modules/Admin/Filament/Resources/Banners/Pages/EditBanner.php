<?php

namespace Modules\Admin\Filament\Resources\Banners\Pages;

use App\Models\User;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Modules\Admin\Actions\ManageBanner;
use Modules\Admin\Filament\Resources\Banners\BannerResource;
use Modules\Admin\Models\Banner;

class EditBanner extends EditRecord
{
    protected static string $resource = BannerResource::class;

    /**
     * Routed through ManageBanner — same as {@see CreateBanner}, and the
     * only place the old image file is deleted when replaced.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $admin */
        $admin = auth()->user();

        /** @var Banner $record */
        return app(ManageBanner::class)->update($record, [
            'image' => $data['image'] ?? null,
            'link_url' => $data['link_url'] ?? null,
            'position' => $data['position'] ?? 0,
            'starts_at' => isset($data['starts_at']) ? Carbon::parse($data['starts_at']) : null,
            'ends_at' => isset($data['ends_at']) ? Carbon::parse($data['ends_at']) : null,
            'is_active' => $data['is_active'] ?? false,
        ], $admin);
    }
}

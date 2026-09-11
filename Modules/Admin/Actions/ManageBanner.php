<?php

namespace Modules\Admin\Actions;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Enums\AuditAction;
use Modules\Admin\Models\AuditLog;
use Modules\Admin\Models\Banner;

/**
 * The single place an admin create/edit/remove on a homepage banner is
 * applied (Phase 9 · T6, issue #35). Management is Filament-only (the ticket
 * asks for no admin REST surface here, unlike the other Phase 9 tickets) —
 * this Action still exists on its own so the Create/Edit pages don't do the
 * audit-log write and disk cleanup inline.
 *
 * `image` is always an already-stored path string — `FileUpload` stores the
 * file itself, onto `admin.banners.disk`, before either method here runs.
 */
class ManageBanner
{
    /**
     * @param  array{image: string, link_url: ?string, position: int, starts_at: ?Carbon, ends_at: ?Carbon, is_active: bool}  $data
     */
    public function create(array $data, User $admin): Banner
    {
        $banner = DB::transaction(function () use ($data, $admin): Banner {
            $banner = Banner::create([
                'image_disk' => (string) config('admin.banners.disk'),
                'image_path' => $data['image'],
                'link_url' => $data['link_url'] ?? null,
                'position' => $data['position'] ?? 0,
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'created_by' => $admin->getKey(),
            ]);

            AuditLog::record($admin, AuditAction::BannerCreated, $banner, [
                'position' => $banner->position,
            ]);

            return $banner;
        });

        return $banner;
    }

    /**
     * @param  array{image?: string|null, link_url?: ?string, position?: int, starts_at?: ?Carbon, ends_at?: ?Carbon, is_active?: bool}  $data
     */
    public function update(Banner $banner, array $data, User $admin): Banner
    {
        $newPath = (! empty($data['image']) && $data['image'] !== $banner->image_path) ? $data['image'] : null;

        DB::transaction(function () use ($banner, $data, $newPath, $admin): void {
            $oldDisk = $banner->image_disk;
            $oldPath = $banner->image_path;

            $banner->forceFill([
                ...array_intersect_key($data, array_flip(['link_url', 'position', 'starts_at', 'ends_at', 'is_active'])),
                ...($newPath !== null ? ['image_path' => $newPath] : []),
            ])->save();

            if ($newPath !== null) {
                Storage::disk($oldDisk)->delete($oldPath);
            }

            AuditLog::record($admin, AuditAction::BannerUpdated, $banner, [
                'position' => $banner->position,
            ]);
        });

        return $banner;
    }

    public function remove(Banner $banner, User $admin): void
    {
        DB::transaction(function () use ($banner, $admin): void {
            // Recorded before the delete, same reasoning as
            // ManageFeaturedPlacement::remove() — a deleted row can't be
            // read back afterward.
            AuditLog::record($admin, AuditAction::BannerRemoved, $banner, [
                'position' => $banner->position,
            ]);

            $banner->delete();
        });

        Storage::disk($banner->image_disk)->delete($banner->image_path);
    }
}

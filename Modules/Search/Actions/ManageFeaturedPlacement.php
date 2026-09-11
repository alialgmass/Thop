<?php

namespace Modules\Search\Actions;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Enums\AuditAction;
use Modules\Admin\Models\AuditLog;
use Modules\Favorites\Enums\FavoritableType;
use Modules\Search\Exceptions\FeaturedPlacementAlreadyExistsException;
use Modules\Search\Models\FeaturedPlacement;
use Modules\Search\Services\FeaturedRanker;

/**
 * The single place an admin creates/removes a featured placement
 * (US-SRC-10, BR-SRC-01, Phase 9 · T5). Independent of the featurable's own
 * subscription plan — {@see FeaturedRanker} unions
 * this with the plan-entitlement check.
 */
class ManageFeaturedPlacement
{
    /**
     * @param  array{type: FavoritableType, featurable_id: int, slot: string, starts_at: ?Carbon, ends_at: ?Carbon}  $data
     */
    public function create(array $data, User $admin): FeaturedPlacement
    {
        $this->assertNotAlreadyPlaced($data['type'], $data['featurable_id'], $data['slot']);

        $placement = DB::transaction(function () use ($data, $admin): FeaturedPlacement {
            $placement = FeaturedPlacement::create([
                'featurable_type' => $data['type']->value,
                'featurable_id' => $data['featurable_id'],
                'slot' => $data['slot'],
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
                'created_by' => $admin->getKey(),
            ]);

            AuditLog::record($admin, AuditAction::FeaturedPlaced, $placement, [
                'featurable_type' => $data['type']->value,
                'featurable_id' => $data['featurable_id'],
                'slot' => $data['slot'],
            ]);

            return $placement;
        });

        return $placement;
    }

    public function remove(FeaturedPlacement $placement, User $admin): void
    {
        DB::transaction(function () use ($placement, $admin): void {
            // Recorded before the delete — AuditLog::record() reads the
            // placement's own key/attributes, and a deleted row can't be
            // read back afterward.
            AuditLog::record($admin, AuditAction::FeaturedRemoved, $placement, [
                'featurable_type' => $placement->featurable_type,
                'featurable_id' => $placement->featurable_id,
                'slot' => $placement->slot,
            ]);

            $placement->delete();
        });
    }

    private function assertNotAlreadyPlaced(FavoritableType $type, int $featurableId, string $slot): void
    {
        $exists = FeaturedPlacement::query()
            ->where('featurable_type', $type->value)
            ->where('featurable_id', $featurableId)
            ->where('slot', $slot)
            ->exists();

        if ($exists) {
            throw new FeaturedPlacementAlreadyExistsException;
        }
    }
}

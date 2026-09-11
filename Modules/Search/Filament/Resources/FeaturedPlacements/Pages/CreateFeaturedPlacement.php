<?php

namespace Modules\Search\Filament\Resources\FeaturedPlacements\Pages;

use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Modules\Favorites\Enums\FavoritableType;
use Modules\Search\Actions\ManageFeaturedPlacement;
use Modules\Search\Exceptions\FeaturedPlacementAlreadyExistsException;
use Modules\Search\Filament\Resources\FeaturedPlacements\FeaturedPlacementResource;

class CreateFeaturedPlacement extends CreateRecord
{
    protected static string $resource = FeaturedPlacementResource::class;

    /**
     * Routed through ManageFeaturedPlacement — same existence check,
     * duplicate-slot guard, and `featured.placed` audit row as the REST
     * endpoint.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $type = FavoritableType::from($data['featurable_type']);

        if ($type->find((int) $data['featurable_id']) === null) {
            Notification::make()->danger()->title(__('search::panel.messages.featurable_not_found'))->send();

            throw new Halt;
        }

        /** @var User $admin */
        $admin = auth()->user();

        try {
            return app(ManageFeaturedPlacement::class)->create([
                'type' => $type,
                'featurable_id' => (int) $data['featurable_id'],
                'slot' => $data['slot'],
                'starts_at' => isset($data['starts_at']) ? Carbon::parse($data['starts_at']) : null,
                'ends_at' => isset($data['ends_at']) ? Carbon::parse($data['ends_at']) : null,
            ], $admin);
        } catch (FeaturedPlacementAlreadyExistsException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();

            throw new Halt;
        }
    }
}

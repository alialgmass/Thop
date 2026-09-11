<?php

namespace Modules\Admin\Filament\Resources\SupplierOnboardings\Pages;

use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Modules\Admin\Actions\OnboardSupplier;
use Modules\Admin\Filament\Resources\SupplierOnboardings\SupplierOnboardingResource;
use Modules\Admin\Http\Requests\OnboardSupplierRequest;
use Modules\Auth\Exceptions\PhoneAlreadyRegisteredException;
use Modules\Auth\Rules\EgyptianMobile;
use Modules\Auth\Support\PhoneNumber;

class CreateOnboardedSupplier extends CreateRecord
{
    protected static string $resource = SupplierOnboardingResource::class;

    /**
     * Routed through OnboardSupplier — same duplicate-phone rule, same
     * audit row, as the REST endpoint. `phone` reaches here already valid
     * (the form field carries the same {@see EgyptianMobile}
     * rule as {@see OnboardSupplierRequest}),
     * so PhoneNumber::normalize() here can only succeed.
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $admin */
        $admin = auth()->user();

        try {
            return app(OnboardSupplier::class)->handle([
                'phone' => PhoneNumber::normalize($data['phone']),
                'account_type' => $data['account_type'],
                'password' => $data['password'],
                'email' => $data['email'] ?? null,
                'language' => $data['language'] ?? null,
                'company_name' => $data['company_name'],
                'activity' => $data['activity'],
                'governorate_id' => $data['governorate_id'],
                'address' => $data['address'],
                'contact_person' => $data['contact_person'],
            ], $admin);
        } catch (PhoneAlreadyRegisteredException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();

            throw new Halt;
        }
    }
}

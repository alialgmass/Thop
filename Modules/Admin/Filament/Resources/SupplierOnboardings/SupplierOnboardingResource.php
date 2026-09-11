<?php

namespace Modules\Admin\Filament\Resources\SupplierOnboardings;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Admin\Filament\Resources\SupplierOnboardings\Pages\CreateOnboardedSupplier;
use Modules\Admin\Filament\Resources\SupplierOnboardings\Pages\ListOnboardedSuppliers;
use Modules\Admin\Filament\Resources\SupplierOnboardings\Schemas\SupplierOnboardingForm;
use Modules\Admin\Filament\Resources\SupplierOnboardings\Tables\OnboardedSuppliersTable;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Core\Filament\Concerns\HasLocalizedLabels;
use Modules\Core\Http\Middleware\EnsureUserIsAdmin;

/**
 * An admin onboards a supplier on their behalf (US-ADM-10, Phase 9 · T10).
 * The list is a read-only reference of who's been onboarded this way, not a
 * general business-account CRUD surface — edit/delete stay with the
 * self-service business-profile endpoints.
 */
class SupplierOnboardingResource extends Resource
{
    use HasLocalizedLabels;

    protected static ?string $model = BusinessAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static ?int $navigationSort = 15;

    protected static string $labelTranslationKey = 'admin::panel.supplier_onboarding';

    protected static string $navigationGroupTranslationKey = 'panel.nav.moderation';

    public static function form(Schema $schema): Schema
    {
        return SupplierOnboardingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OnboardedSuppliersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOnboardedSuppliers::route('/'),
            'create' => CreateOnboardedSupplier::route('/create'),
        ];
    }

    /**
     * @return Builder<BusinessAccount>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('owner')->where('onboarded_by_admin', true);
    }

    /**
     * `BusinessAccount`'s own policy (`BusinessPolicy::create()`) only
     * allows a user creating *their own* profile — it was never meant to
     * gate an admin creating one on someone else's behalf, and its
     * `before()` doesn't bypass `create` for admins either. Every page in
     * this panel is already admin-only ({@see EnsureUserIsAdmin}),
     * so this override doesn't skip a real check — it stops Filament from
     * incorrectly applying an unrelated one.
     */
    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit(mixed $record): bool
    {
        return false;
    }

    public static function canDelete(mixed $record): bool
    {
        return false;
    }
}

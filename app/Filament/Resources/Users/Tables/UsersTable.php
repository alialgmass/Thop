<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Admin\Actions\ReactivateAccount;
use Modules\Admin\Actions\SuspendAccount;
use Modules\Auth\Enums\UserStatus;
use Modules\Core\Exceptions\ApiException\ExceptionResponse;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('phone')
                    ->label(__('panel.user.fields.phone'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('panel.user.fields.email'))
                    ->searchable(),
                TextColumn::make('account_type')
                    ->label(__('panel.user.fields.account_type'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('panel.user.fields.status'))
                    ->badge()
                    ->color(fn (UserStatus $state): string => match ($state) {
                        UserStatus::Active => 'success',
                        UserStatus::Suspended => 'danger',
                        UserStatus::PendingTypeSelection => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('panel.common.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('panel.common.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('suspend')
                    ->label(__('panel.user.actions.suspend'))
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (User $record): bool => $record->status !== UserStatus::Suspended && ! $record->hasRole('admin'))
                    ->schema([
                        Checkbox::make('confirm')
                            ->label(__('panel.user.actions.suspend_confirm'))
                            ->required()
                            ->accepted(),
                    ])
                    ->action(fn (User $record) => self::moderate(
                        fn (User $admin) => app(SuspendAccount::class)->handle($record, $admin),
                        __('panel.user.actions.suspended'),
                    )),
                Action::make('reactivate')
                    ->label(__('panel.user.actions.reactivate'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => $record->status === UserStatus::Suspended)
                    ->action(fn (User $record) => self::moderate(
                        fn (User $admin) => app(ReactivateAccount::class)->handle($record, $admin),
                        __('panel.user.actions.reactivated'),
                    )),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Shared shape for both moderation actions: run the action as the
     * current admin, turn a domain-guard exception into a danger
     * notification instead of a raw error page, otherwise notify success —
     * matches ViewVerificationRequest::decide()'s pattern.
     *
     * @param  callable(User): void  $run
     */
    private static function moderate(callable $run, string $successTitle): void
    {
        /** @var User $admin */
        $admin = auth()->user();

        try {
            $run($admin);
        } catch (ExceptionResponse $e) {
            Notification::make()->danger()->title($e->getMessage())->send();

            return;
        }

        Notification::make()->success()->title($successTitle)->send();
    }
}

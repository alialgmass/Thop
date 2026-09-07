<?php

namespace Modules\Verification\Filament\Resources\VerificationRequests\Pages;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Number;
use Modules\Verification\Actions\DecideVerificationRequest;
use Modules\Verification\Enums\VerificationRequestStatus;
use Modules\Verification\Exceptions\VerificationNotPendingException;
use Modules\Verification\Filament\Resources\VerificationRequests\VerificationRequestResource;
use Modules\Verification\Models\VerificationRequest;

class ViewVerificationRequest extends ViewRecord
{
    protected static string $resource = VerificationRequestResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('verification::panel.sections.business'))
                ->columns(2)
                ->schema([
                    TextEntry::make('businessAccount.company_name')->label(__('verification::panel.fields.company')),
                    TextEntry::make('businessAccount.activity')->label(__('verification::panel.fields.activity')),
                    TextEntry::make('businessAccount.governorate.name_en')->label(__('verification::panel.columns.governorate')),
                    TextEntry::make('businessAccount.owner.phone')->label(__('verification::panel.fields.owner_phone')),
                    TextEntry::make('businessAccount.owner.email')->label(__('verification::panel.fields.owner_email'))->placeholder('—'),
                    TextEntry::make('businessAccount.contact_person')->label(__('verification::panel.fields.contact_person')),
                    TextEntry::make('businessAccount.address')->label(__('verification::panel.fields.address'))->columnSpanFull(),
                ]),

            Section::make(__('verification::panel.sections.request'))
                ->columns(2)
                ->schema([
                    TextEntry::make('status')
                        ->label(__('verification::panel.columns.status'))
                        ->badge()
                        ->color(fn (VerificationRequestStatus $state): string => match ($state) {
                            VerificationRequestStatus::Pending => 'warning',
                            VerificationRequestStatus::Approved => 'success',
                            VerificationRequestStatus::Rejected => 'danger',
                        }),
                    TextEntry::make('submitted_at')->label(__('verification::panel.columns.submitted_at'))->dateTime()->placeholder(__('verification::panel.columns.not_submitted')),
                    TextEntry::make('reviewer.phone')->label(__('verification::panel.columns.reviewed_by'))->placeholder('—'),
                    TextEntry::make('reviewed_at')->label(__('verification::panel.fields.reviewed_at'))->dateTime()->placeholder('—'),
                    TextEntry::make('rejection_reason')->label(__('verification::panel.fields.rejection_reason'))->placeholder('—')->columnSpanFull(),
                ]),

            Section::make(__('verification::panel.sections.documents'))
                ->schema([
                    RepeatableEntry::make('documents')
                        ->hiddenLabel()
                        ->columns(3)
                        ->schema([
                            TextEntry::make('documentType.name_en')->label(__('verification::panel.fields.type')),
                            TextEntry::make('original_name')->label(__('verification::panel.fields.file')),
                            TextEntry::make('size')
                                ->label(__('verification::panel.fields.size'))
                                ->formatStateUsing(fn (int $state): string => Number::fileSize($state)),
                            TextEntry::make('id')
                                ->label('')
                                ->badge()
                                ->color('primary')
                                ->formatStateUsing(fn (): string => __('verification::panel.fields.download'))
                                ->url(fn ($record): string => route('admin.verification.documents.download', $record))
                                ->openUrlInNewTab()
                                ->columnSpanFull(),
                        ]),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label(__('verification::panel.actions.approve'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => $this->isPending())
                ->requiresConfirmation()
                ->action(fn () => $this->decide(fn (DecideVerificationRequest $decide, User $admin) => $decide->approve($this->record, $admin), 'approved')),

            Action::make('reject')
                ->label(__('verification::panel.actions.reject'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->isPending())
                ->schema([
                    Textarea::make('reason')
                        ->label(__('verification::panel.fields.reason_for_rejection'))
                        ->required()
                        ->minLength(3)
                        ->maxLength(1000),
                ])
                ->action(fn (array $data) => $this->decide(
                    fn (DecideVerificationRequest $decide, User $admin) => $decide->reject($this->record, $admin, $data['reason']),
                    'rejected',
                )),
        ];
    }

    private function isPending(): bool
    {
        /** @var VerificationRequest $record */
        $record = $this->record;

        return $record->isAwaitingReview() && $record->submitted_at !== null;
    }

    private function decide(callable $run, string $verb): void
    {
        /** @var User $admin */
        $admin = auth()->user();

        try {
            $run(app(DecideVerificationRequest::class), $admin);
        } catch (VerificationNotPendingException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();

            return;
        }

        Notification::make()->success()->title(__("verification::panel.actions.{$verb}"))->send();

        $this->refreshFormData(['status']);
    }
}

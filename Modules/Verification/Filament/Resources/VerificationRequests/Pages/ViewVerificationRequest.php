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
            Section::make('Business')
                ->columns(2)
                ->schema([
                    TextEntry::make('businessAccount.company_name')->label('Company'),
                    TextEntry::make('businessAccount.activity')->label('Activity'),
                    TextEntry::make('businessAccount.governorate.name_en')->label('Governorate'),
                    TextEntry::make('businessAccount.owner.phone')->label('Owner phone'),
                    TextEntry::make('businessAccount.owner.email')->label('Owner email')->placeholder('—'),
                    TextEntry::make('businessAccount.contact_person')->label('Contact person'),
                    TextEntry::make('businessAccount.address')->label('Address')->columnSpanFull(),
                ]),

            Section::make('Request')
                ->columns(2)
                ->schema([
                    TextEntry::make('status')
                        ->badge()
                        ->color(fn (VerificationRequestStatus $state): string => match ($state) {
                            VerificationRequestStatus::Pending => 'warning',
                            VerificationRequestStatus::Approved => 'success',
                            VerificationRequestStatus::Rejected => 'danger',
                        }),
                    TextEntry::make('submitted_at')->dateTime()->placeholder('Not submitted'),
                    TextEntry::make('reviewer.phone')->label('Reviewed by')->placeholder('—'),
                    TextEntry::make('reviewed_at')->dateTime()->placeholder('—'),
                    TextEntry::make('rejection_reason')->placeholder('—')->columnSpanFull(),
                ]),

            Section::make('Documents')
                ->schema([
                    RepeatableEntry::make('documents')
                        ->hiddenLabel()
                        ->columns(3)
                        ->schema([
                            TextEntry::make('documentType.name_en')->label('Type'),
                            TextEntry::make('original_name')->label('File'),
                            TextEntry::make('size')
                                ->label('Size')
                                ->formatStateUsing(fn (int $state): string => Number::fileSize($state)),
                            TextEntry::make('id')
                                ->label('')
                                ->badge()
                                ->color('primary')
                                ->formatStateUsing(fn (): string => 'Download')
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
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => $this->isPending())
                ->requiresConfirmation()
                ->action(fn () => $this->decide(fn (DecideVerificationRequest $decide, User $admin) => $decide->approve($this->record, $admin), 'approved')),

            Action::make('reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->isPending())
                ->schema([
                    Textarea::make('reason')
                        ->label('Reason for rejection')
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

        Notification::make()->success()->title("Verification {$verb}.")->send();

        $this->refreshFormData(['status']);
    }
}

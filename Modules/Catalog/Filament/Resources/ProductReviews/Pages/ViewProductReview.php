<?php

namespace Modules\Catalog\Filament\Resources\ProductReviews\Pages;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Actions\DecideProductReview;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Exceptions\ProductNotInReviewException;
use Modules\Catalog\Filament\Resources\ProductReviews\ProductReviewResource;
use Modules\Catalog\Models\Product;

class ViewProductReview extends ViewRecord
{
    protected static string $resource = ProductReviewResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('catalog::panel.sections.product'))
                ->columns(2)
                ->schema([
                    TextEntry::make('name_ar')->label(__('catalog::panel.fields.name_ar')),
                    TextEntry::make('name_en')->label(__('catalog::panel.fields.name_en'))->placeholder('—'),
                    TextEntry::make('businessAccount.company_name')->label(__('catalog::panel.columns.seller')),
                    TextEntry::make('businessAccount.owner.phone')->label(__('catalog::panel.fields.seller_phone')),
                    TextEntry::make('status')
                        ->label(__('catalog::panel.columns.status'))
                        ->badge()
                        ->color(fn (ProductStatus $state): string => match ($state) {
                            ProductStatus::Draft => 'gray',
                            ProductStatus::PendingReview => 'warning',
                            ProductStatus::Published => 'success',
                            ProductStatus::Hidden, ProductStatus::Unavailable => 'gray',
                            ProductStatus::Rejected => 'danger',
                        }),
                    TextEntry::make('rejection_reason')->label(__('catalog::panel.fields.rejection_reason'))->placeholder('—')->columnSpanFull(),
                    TextEntry::make('description')->label(__('catalog::panel.fields.description'))->placeholder('—')->columnSpanFull(),
                ]),

            Section::make(__('catalog::panel.sections.images'))
                ->schema([
                    RepeatableEntry::make('media')
                        ->hiddenLabel()
                        ->columns(4)
                        ->schema([
                            ImageEntry::make('path')
                                ->hiddenLabel()
                                ->height(120)
                                ->getStateUsing(fn ($record): string => Storage::disk($record->disk)->url($record->path)),
                        ]),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label(__('catalog::panel.actions.approve'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => $this->isPendingReview())
                ->requiresConfirmation()
                ->action(fn () => $this->decide(fn (DecideProductReview $decide, User $admin) => $decide->approve($this->record, $admin), 'approved')),

            Action::make('reject')
                ->label(__('catalog::panel.actions.reject'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->isPendingReview())
                ->schema([
                    Textarea::make('reason')
                        ->label(__('catalog::panel.fields.reason'))
                        ->required()
                        ->minLength(3)
                        ->maxLength(1000),
                ])
                ->action(fn (array $data) => $this->decide(
                    fn (DecideProductReview $decide, User $admin) => $decide->reject($this->record, $admin, $data['reason']),
                    'rejected',
                )),

            Action::make('requestEdits')
                ->label(__('catalog::panel.actions.request_edits'))
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->visible(fn (): bool => $this->isPendingReview())
                ->schema([
                    Textarea::make('reason')
                        ->label(__('catalog::panel.fields.reason'))
                        ->required()
                        ->minLength(3)
                        ->maxLength(1000),
                ])
                ->action(fn (array $data) => $this->decide(
                    fn (DecideProductReview $decide, User $admin) => $decide->requestEdits($this->record, $admin, $data['reason']),
                    'edits_requested',
                )),

            Action::make('hide')
                ->label(__('catalog::panel.actions.hide'))
                ->icon('heroicon-o-eye-slash')
                ->color('gray')
                ->visible(fn (): bool => $this->isPublished())
                ->requiresConfirmation()
                ->action(fn () => $this->decide(fn (DecideProductReview $decide, User $admin) => $decide->hide($this->record, $admin), 'hidden')),
        ];
    }

    private function isPendingReview(): bool
    {
        /** @var Product $record */
        $record = $this->record;

        return $record->status === ProductStatus::PendingReview;
    }

    private function isPublished(): bool
    {
        /** @var Product $record */
        $record = $this->record;

        return $record->status === ProductStatus::Published;
    }

    private function decide(callable $run, string $verb): void
    {
        /** @var User $admin */
        $admin = auth()->user();

        try {
            $run(app(DecideProductReview::class), $admin);
        } catch (ProductNotInReviewException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();

            return;
        }

        Notification::make()->success()->title(__("catalog::panel.actions.{$verb}"))->send();

        $this->refreshFormData(['status']);
    }
}

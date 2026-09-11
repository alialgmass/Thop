<?php

namespace Modules\Inquiries\Filament\Resources\Reports\Pages;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Inquiries\Actions\ResolveReport;
use Modules\Inquiries\Enums\ReportStatus;
use Modules\Inquiries\Exceptions\ReportAlreadyResolvedException;
use Modules\Inquiries\Filament\Resources\Reports\ReportResource;
use Modules\Inquiries\Models\Report;

class ViewReport extends ViewRecord
{
    protected static string $resource = ReportResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('inquiries::panel.report.sections.report'))
                ->columns(2)
                ->schema([
                    TextEntry::make('reportable_type')->label(__('inquiries::panel.report.columns.type'))->badge(),
                    TextEntry::make('status')->label(__('inquiries::panel.report.columns.status'))->badge(),
                    TextEntry::make('reason')->label(__('inquiries::panel.report.columns.reason'))->columnSpanFull(),
                    TextEntry::make('reporter.phone')->label(__('inquiries::panel.report.columns.reporter')),
                    TextEntry::make('created_at')->label(__('inquiries::panel.report.columns.reported_at'))->dateTime(),
                ]),

            Section::make(__('inquiries::panel.report.sections.parties'))
                ->columns(2)
                ->schema([
                    TextEntry::make('buyer')
                        ->label(__('inquiries::panel.report.fields.buyer'))
                        ->state(fn (Report $record): string => $record->parties()['buyer']->phone),
                    TextEntry::make('seller')
                        ->label(__('inquiries::panel.report.fields.seller'))
                        ->state(fn (Report $record): string => $record->parties()['sellerBusiness']->company_name),
                ]),

            Section::make(__('inquiries::panel.report.sections.resolution'))
                ->columns(2)
                ->schema([
                    TextEntry::make('resolver.phone')->label(__('inquiries::panel.report.fields.resolved_by'))->placeholder('—'),
                    TextEntry::make('resolved_at')->label(__('inquiries::panel.report.fields.resolved_at'))->dateTime()->placeholder('—'),
                    TextEntry::make('resolution_note')->label(__('inquiries::panel.report.fields.resolution_note'))->placeholder('—')->columnSpanFull(),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resolve')
                ->label(__('inquiries::panel.report.actions.resolve'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => ! $this->isResolved())
                ->schema([
                    Select::make('status')
                        ->label(__('inquiries::panel.report.columns.status'))
                        ->options([
                            ReportStatus::Resolved->value => __('inquiries::panel.report.status.resolved'),
                            ReportStatus::Dismissed->value => __('inquiries::panel.report.status.dismissed'),
                        ])
                        ->default(ReportStatus::Resolved->value)
                        ->required(),
                    Textarea::make('note')
                        ->label(__('inquiries::panel.report.fields.resolution_note'))
                        ->required()
                        ->minLength(3)
                        ->maxLength(2000),
                ])
                ->action(function (array $data): void {
                    /** @var User $admin */
                    $admin = auth()->user();

                    try {
                        app(ResolveReport::class)->handle(
                            $this->record,
                            $admin,
                            $data['note'],
                            ReportStatus::from($data['status']),
                        );
                    } catch (ReportAlreadyResolvedException $e) {
                        Notification::make()->danger()->title($e->getMessage())->send();

                        return;
                    }

                    Notification::make()->success()->title(__('inquiries::panel.report.actions.resolved'))->send();

                    $this->refreshFormData(['status']);
                }),
        ];
    }

    private function isResolved(): bool
    {
        /** @var Report $record */
        $record = $this->record;

        return $record->status->isResolved();
    }
}

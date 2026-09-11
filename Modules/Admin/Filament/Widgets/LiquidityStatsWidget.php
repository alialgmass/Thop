<?php

namespace Modules\Admin\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Admin\Services\LiquidityMetrics;

/**
 * Marketplace-health stats on the admin dashboard (US-ADM-06, Phase 9 · T7,
 * issue #36) — the panel-side consumer of {@see LiquidityMetrics}, same
 * numbers `GET /api/v1/admin/dashboard/liquidity` returns.
 */
class LiquidityStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $metrics = app(LiquidityMetrics::class)->summarize();

        $topTerm = $metrics['zero_result_terms'][0]['term'] ?? null;

        return [
            Stat::make(__('admin::panel.dashboard.active_sellers'), $metrics['active_sellers']),
            Stat::make(__('admin::panel.dashboard.active_products'), $metrics['active_products']),
            Stat::make(__('admin::panel.dashboard.active_buyers'), $metrics['active_buyers'])
                ->description(__('admin::panel.dashboard.last_days', ['days' => $metrics['range_days']])),
            Stat::make(__('admin::panel.dashboard.inquiries'), $metrics['inquiries_last_period'])
                ->description(__('admin::panel.dashboard.last_days', ['days' => $metrics['range_days']])),
            Stat::make(
                __('admin::panel.dashboard.top_zero_result_term'),
                $topTerm ?? __('admin::panel.dashboard.none'),
            )->description(__('admin::panel.dashboard.zero_result_hint')),
        ];
    }
}

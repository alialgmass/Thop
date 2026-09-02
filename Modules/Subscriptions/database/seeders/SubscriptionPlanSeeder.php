<?php

namespace Modules\Subscriptions\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Subscriptions\Models\SubscriptionEntitlement;
use Modules\Subscriptions\Models\SubscriptionPlan;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Seed subscription plans per SRS Appendix A (Section 6).
     *
     * Prices are nullable per spec ("not specified in source document").
     * Entitlement values are admin-editable key/value pairs (MNT-NFR-02).
     */
    public function run(): void
    {
        $this->seedImporterPlans();
        $this->seedWholesalerPlan();
        $this->seedRetailerPlan();
    }

    private function seedImporterPlans(): void
    {
        //
        // NOTE: numeric `product_limit` / `inquiry_limit` values below are
        // placeholders pending Open Decision #2 (exact caps not yet decided in
        // the spec). Non-numeric values would be treated as unlimited by
        // EntitlementService, so temporary numeric caps ensure server-side
        // enforcement actually engages. Revisit when the spec resolves the caps.
        //

        // ── Basic ──
        $basic = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Basic',
            'price' => null,
            'billing_cycle' => null,
        ]);

        collect([
            'product_limit' => '50',
            'inquiry_limit' => '30',
            'search_priority' => 'false',
            'featured_products' => 'false',
            'featured_supplier' => 'false',
            'featured_placement' => 'false',
            'promotions' => 'false',
            'analytics_depth' => 'basic',
            'leads_management' => 'false',
            'support_level' => 'standard',
            'market_insights' => 'false',
        ])->each(fn ($value, $key) => SubscriptionEntitlement::create([
            'plan_id' => $basic->getKey(),
            'key' => $key,
            'value' => $value,
        ]));

        // ── Pro ──
        $pro = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Pro',
            'price' => null,
            'billing_cycle' => null,
        ]);

        collect([
            'product_limit' => '500',
            'inquiry_limit' => '200',
            'search_priority' => 'true',
            'featured_products' => 'true',
            'featured_supplier' => 'true',
            'featured_placement' => 'false',
            'promotions' => 'offers',
            'analytics_depth' => 'advanced',
            'leads_management' => 'true',
            'support_level' => 'standard',
            'market_insights' => 'false',
        ])->each(fn ($value, $key) => SubscriptionEntitlement::create([
            'plan_id' => $pro->getKey(),
            'key' => $key,
            'value' => $value,
        ]));

        // ── Premium ──
        $premium = SubscriptionPlan::create([
            'account_type' => 'importer',
            'name' => 'Premium',
            'price' => null,
            'billing_cycle' => null,
        ]);

        collect([
            'product_limit' => '1000',
            'inquiry_limit' => '500',
            'search_priority' => 'true',
            'featured_products' => 'true',
            'featured_supplier' => 'true',
            'featured_placement' => 'true',
            'promotions' => 'full',
            'analytics_depth' => 'advanced',
            'leads_management' => 'true',
            'support_level' => 'dedicated',
            'market_insights' => 'roadmap',
        ])->each(fn ($value, $key) => SubscriptionEntitlement::create([
            'plan_id' => $premium->getKey(),
            'key' => $key,
            'value' => $value,
        ]));
    }

    private function seedWholesalerPlan(): void
    {
        // Single tier — all capabilities enabled
        $wholesaler = SubscriptionPlan::create([
            'account_type' => 'wholesaler',
            'name' => 'Wholesaler',
            'price' => null,
            'billing_cycle' => null,
        ]);

        collect([
            'advanced_search' => 'true',
            'advanced_filters' => 'true',
            'supplier_discovery' => 'true',
            'compare_suppliers' => 'true',
            'price_comparison' => 'true',
            'save_suppliers' => 'true',
            'request_quotations' => 'true',
            'purchase_history' => 'true',
            'market_alerts' => 'true',
            'new_arrivals' => 'true',
        ])->each(fn ($value, $key) => SubscriptionEntitlement::create([
            'plan_id' => $wholesaler->getKey(),
            'key' => $key,
            'value' => $value,
        ]));
    }

    private function seedRetailerPlan(): void
    {
        // Single tier — highest value per SRS §2.3.3
        $retailer = SubscriptionPlan::create([
            'account_type' => 'retailer',
            'name' => 'Retailer',
            'price' => null,
            'billing_cycle' => null,
        ]);

        collect([
            'store_profile' => 'true',
            'add_products' => 'true',
            'supplier_discovery' => 'true',
            'receive_customer_inquiries' => 'true',
            'featured_store_products' => 'true',
            'better_visibility' => 'true',
            'analytics' => 'true',
            'promotions' => 'true',
            'supplier_tools' => 'true',
        ])->each(fn ($value, $key) => SubscriptionEntitlement::create([
            'plan_id' => $retailer->getKey(),
            'key' => $key,
            'value' => $value,
        ]));
    }
}

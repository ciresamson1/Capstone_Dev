<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','pm','dm','client','special_pm') NOT NULL DEFAULT 'client'");

        Schema::table('users', function (Blueprint $table) {
            $table->string('stripe_customer_id')->nullable()->after('remember_token')->index();
            $table->string('stripe_subscription_id')->nullable()->after('stripe_customer_id')->index();
            $table->string('stripe_price_id')->nullable()->after('stripe_subscription_id');
            $table->string('subscription_status')->default('inactive')->after('stripe_price_id');
            $table->timestamp('subscription_current_period_end')->nullable()->after('subscription_status');
            $table->string('white_label_brand_name')->nullable()->after('subscription_current_period_end');
            $table->string('white_label_logo_url')->nullable()->after('white_label_brand_name');
            $table->string('white_label_primary_color')->default('#0f172a')->after('white_label_logo_url');
            $table->string('white_label_accent_color')->default('#38bdf8')->after('white_label_primary_color');
        });
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'special_pm')->update(['role' => 'pm']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_customer_id',
                'stripe_subscription_id',
                'stripe_price_id',
                'subscription_status',
                'subscription_current_period_end',
                'white_label_brand_name',
                'white_label_logo_url',
                'white_label_primary_color',
                'white_label_accent_color',
            ]);
        });

        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','pm','dm','client') NOT NULL DEFAULT 'client'");
    }
};

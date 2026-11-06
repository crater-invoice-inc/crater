<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddServiceFieldsToItemsAndLineItemsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('items') && ! Schema::hasColumn('items', 'deliverables')) {
            Schema::table('items', function (Blueprint $table) {
                $table->text('deliverables')->nullable()->after('description');
                $table->string('delivery_delay')->nullable()->after('deliverables');
                $table->text('terms')->nullable()->after('delivery_delay');
            });
        }

        if (Schema::hasTable('estimate_items') && ! Schema::hasColumn('estimate_items', 'deliverables')) {
            Schema::table('estimate_items', function (Blueprint $table) {
                $table->text('deliverables')->nullable()->after('description');
                $table->string('delivery_delay')->nullable()->after('deliverables');
                $table->text('terms')->nullable()->after('delivery_delay');
            });
        }

        if (Schema::hasTable('invoice_items') && ! Schema::hasColumn('invoice_items', 'deliverables')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->text('deliverables')->nullable()->after('description');
                $table->string('delivery_delay')->nullable()->after('deliverables');
                $table->text('terms')->nullable()->after('delivery_delay');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('items') && Schema::hasColumn('items', 'deliverables')) {
            Schema::table('items', function (Blueprint $table) {
                $table->dropColumn(['deliverables', 'delivery_delay', 'terms']);
            });
        }

        if (Schema::hasTable('estimate_items') && Schema::hasColumn('estimate_items', 'deliverables')) {
            Schema::table('estimate_items', function (Blueprint $table) {
                $table->dropColumn(['deliverables', 'delivery_delay', 'terms']);
            });
        }

        if (Schema::hasTable('invoice_items') && Schema::hasColumn('invoice_items', 'deliverables')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->dropColumn(['deliverables', 'delivery_delay', 'terms']);
            });
        }
    }
}

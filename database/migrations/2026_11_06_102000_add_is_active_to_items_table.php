<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsActiveToItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Services default to inactive: only explicitly activated services
     * are offered in the AI assistant selector.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('items') && ! Schema::hasColumn('items', 'is_active')) {
            Schema::table('items', function (Blueprint $table) {
                $table->boolean('is_active')->default(false)->after('price');
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
        if (Schema::hasTable('items') && Schema::hasColumn('items', 'is_active')) {
            Schema::table('items', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
}

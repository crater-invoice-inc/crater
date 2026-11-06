<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDefaultQuantityToItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('items') && ! Schema::hasColumn('items', 'default_quantity')) {
            Schema::table('items', function (Blueprint $table) {
                $table->decimal('default_quantity', 15, 2)->nullable()->after('price');
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
        if (Schema::hasTable('items') && Schema::hasColumn('items', 'default_quantity')) {
            Schema::table('items', function (Blueprint $table) {
                $table->dropColumn('default_quantity');
            });
        }
    }
}

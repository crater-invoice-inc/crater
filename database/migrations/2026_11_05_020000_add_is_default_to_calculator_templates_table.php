<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsDefaultToCalculatorTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('calculator_templates') && ! Schema::hasColumn('calculator_templates', 'is_default')) {
            Schema::table('calculator_templates', function (Blueprint $table) {
                $table->boolean('is_default')->default(false)->after('unit_price');
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
        if (Schema::hasColumn('calculator_templates', 'is_default')) {
            Schema::table('calculator_templates', function (Blueprint $table) {
                $table->dropColumn('is_default');
            });
        }
    }
}

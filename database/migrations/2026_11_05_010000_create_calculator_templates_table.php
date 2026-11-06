<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCalculatorTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('calculator_templates')) {
            Schema::create('calculator_templates', function (Blueprint $table) {
                $table->id();
                $table->integer('company_id')->unsigned();
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
                $table->string('name');
                $table->string('category');
                $table->unsignedBigInteger('unit_price')->default(0);
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }

        // No foreign key on purpose: customers.id is INT on some installs and
        // BIGINT on others, which makes an FK constraint environment-dependent.
        if (! Schema::hasColumn('calculators', 'customer_id')) {
            Schema::table('calculators', function (Blueprint $table) {
                $table->unsignedBigInteger('customer_id')->nullable()->index();
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
        if (Schema::hasColumn('calculators', 'customer_id')) {
            Schema::table('calculators', function (Blueprint $table) {
                $table->dropColumn('customer_id');
            });
        }

        Schema::dropIfExists('calculator_templates');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCalculatorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('calculators', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->unsignedInteger('creator_id');
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('name');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('calculator_items', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->unsignedBigInteger('calculator_id');
            $table->foreign('calculator_id')->references('id')->on('calculators')->onDelete('cascade');
            $table->string('category');
            $table->string('type')->default('cost');
            $table->string('item_name');
            $table->string('description')->nullable();
            $table->unsignedBigInteger('unit_price');
            $table->decimal('quantity', 15, 2)->default(1);
            $table->timestamps();
        });

        // Grant the new calculator abilities to the super admin role of existing companies
        \Crater\Models\Company::all()->each(function ($company) {
            $company->setupRoles();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('calculator_items');
        Schema::dropIfExists('calculators');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAiConversationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('ai_conversations')) {
            return;
        }

        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 20);
            $table->unsignedBigInteger('document_id')->nullable();
            $table->unsignedInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            // users.id is INT UNSIGNED on every Crater install (same
            // environment-dependent FK pitfall documented in the calculator
            // migrations), so the column type must match exactly.
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('draft_key', 64)->nullable();
            $table->timestamps();

            // A saved document owns exactly one conversation per company.
            // document_id is nullable (draft conversations are identified by
            // draft_key instead), and MySQL/MariaDB allow multiple NULLs in a
            // unique index, so several drafts can coexist.
            $table->unique(
                ['company_id', 'document_type', 'document_id'],
                'ai_conversations_company_document_unique'
            );

            // A draft conversation is identified by company + document
            // type + draft key, mirroring the saved document identity:
            // the same draft key must never resolve a conversation of
            // another document type, but may exist on both types at once
            // (client bug recovery) or in two companies.
            $table->unique(
                ['company_id', 'document_type', 'draft_key'],
                'ai_conversations_company_type_draft_unique'
            );
            $table->index(['document_type', 'document_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('ai_conversations')) {
            Schema::dropIfExists('ai_conversations');
        }
    }
}

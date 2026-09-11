<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->string('type')->index();
            $table->timestamps();
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->foreign('conversation_id', 'messages_conversation_fk')
                ->references('id')->on('conversations')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign('messages_conversation_fk');
        });
        Schema::dropIfExists('conversations');
    }
};

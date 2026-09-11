<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('type_users', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('title_fa')->nullable();
            $table->boolean('status')->default(false)->index();
            $table->timestamps();
        });

        DB::table('type_users')->insert([
            'title' => 'superadmin',
            'title_fa' => 'کاربر اصلی',
            'status' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('type_users');
    }
};

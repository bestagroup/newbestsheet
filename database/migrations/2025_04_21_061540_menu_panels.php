<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_panels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('priority');
            $table->string('label');
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->boolean('submenu')->default(false);
            $table->string('class')->nullable();
            $table->string('level')->nullable();
            $table->string('controller')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('status')->default(false)->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['priority', 'status']);
        });

        DB::table('menu_panels')->insert([
            ['id' => 1, 'priority' => 1, 'label' => 'داشبورد', 'title' => 'dashboard', 'slug' => 'dashboard', 'icon' => null, 'submenu' => false, 'class' => 'index', 'level' => null, 'controller' => 'IndexController', 'status' => 4, 'user_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'priority' => 2, 'label' => 'مدیریت داشبورد', 'title' => 'dashboard manage', 'slug' => 'dashboard-manage', 'icon' => null, 'submenu' => true, 'class' => 'panel', 'level' => null, 'controller' => 'IndexController', 'status' => 4, 'user_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'priority' => 3, 'label' => 'مدیریت کاربران', 'title' => 'user manage', 'slug' => 'user-manage', 'icon' => null, 'submenu' => true, 'class' => 'index', 'level' => null, 'controller' => 'UserController', 'status' => 4, 'user_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'priority' => 4, 'label' => 'مدیریت سایت', 'title' => 'site manage', 'slug' => 'site-manage', 'icon' => null, 'submenu' => true, 'class' => 'index', 'level' => null, 'controller' => 'SiteController', 'status' => 4, 'user_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'priority' => 5, 'label' => 'مدیریت فایل ها', 'title' => 'file manage', 'slug' => 'file-manage', 'icon' => null, 'submenu' => true, 'class' => 'index', 'level' => null, 'controller' => 'FileController', 'status' => 4, 'user_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'priority' => 6, 'label' => 'مدیریت تنظیمات', 'title' => 'config manage', 'slug' => 'config-manage', 'icon' => null, 'submenu' => false, 'class' => 'index', 'level' => null, 'controller' => 'SettingController', 'status' => 4, 'user_id' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_panels');
    }
};

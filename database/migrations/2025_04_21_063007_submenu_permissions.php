<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submenu_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submenu_id')->constrained('submenu_panels')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('can_create')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->timestamps();

            $table->unique(['submenu_id', 'user_id']);
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->foreign('menu_panel_id', 'permissions_menu_panel_fk')
                ->references('id')->on('menu_panels')->nullOnDelete();
            $table->foreign('submenu_panel_id', 'permissions_submenu_panel_fk')
                ->references('id')->on('submenu_panels')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropForeign('permissions_menu_panel_fk');
            $table->dropForeign('permissions_submenu_panel_fk');
        });

        Schema::dropIfExists('submenu_permissions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('admins', function (Blueprint $table) {
            $table->id('admin_id');
            $table->string('family_tree_id')->unique();
            $table->string('full_name');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('admins');
    }
};


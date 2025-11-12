<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('family_tree_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('full_name');
            $table->string('address')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('avatar')->nullable();
            $table->timestamps();

            // Foreign key ke tabel User (self relation)
            $table->foreign('parent_id')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void {
        Schema::dropIfExists('users');
    }
};

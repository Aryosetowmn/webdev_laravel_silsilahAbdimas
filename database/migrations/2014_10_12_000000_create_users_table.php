<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('id_silsilah')->unique();
            $table->unsignedBigInteger('id_parent')->nullable();
            $table->unsignedBigInteger('id_pasangan')->nullable();
            $table->string('name');
            $table->string('tempat_tinggal')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('avatar')->nullable();
            $table->timestamps();

            // Foreign key ke tabel User (self relation)
            $table->foreign('id_parent')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('id_pasangan')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void {
        Schema::dropIfExists('users');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preferences', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('group', 50)->default('general')->index();
            $table->string('label', 150);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preferences');
    }
};

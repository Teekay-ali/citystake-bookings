<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('changelogs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('version')->nullable();
            $table->enum('type', ['feature', 'fix', 'improvement', 'security'])->default('improvement');
            $table->boolean('send_email')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('changelog_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changelog_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at')->useCurrent();
            $table->unique(['user_id', 'changelog_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('changelog_reads');
        Schema::dropIfExists('changelogs');
    }
};

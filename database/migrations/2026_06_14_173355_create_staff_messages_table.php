<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject')->nullable();
            $table->text('body');
            $table->foreignId('parent_id')->nullable()->constrained('staff_messages')->cascadeOnDelete();
            // null = direct message, set = broadcast to role
            $table->string('broadcast_role')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_message_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->unique(['staff_message_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_message_recipients');
        Schema::dropIfExists('staff_messages');
    }
};

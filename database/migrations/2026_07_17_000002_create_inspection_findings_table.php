<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_inspection_id')->constrained()->cascadeOnDelete();

            $table->enum('category', [
                'cleanliness', 'damage', 'electrical', 'plumbing',
                'appliance', 'furniture', 'safety', 'other',
            ]);
            $table->enum('severity', ['low', 'medium', 'high'])->default('low');
            $table->text('description');
            $table->json('photos')->nullable();

            $table->boolean('resolved')->default(false);
            // Escalation hook: a concern can spawn a maintenance report later.
            $table->foreignId('maintenance_report_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();

            $table->index('unit_inspection_id');
            $table->index(['resolved', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_findings');
    }
};

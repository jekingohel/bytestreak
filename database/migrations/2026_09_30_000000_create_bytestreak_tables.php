<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('slug', 60)->unique();
            $table->string('emoji', 16)->default('🧠');
            $table->string('color', 9)->default('#FF6B2C');
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 30);
            $table->string('difficulty', 10)->default('easy');
            $table->string('title', 160);
            $table->text('question');
            $table->text('code_snippet')->nullable();
            $table->string('code_language', 20)->nullable();
            $table->text('explanation');
            $table->unsignedSmallInteger('xp')->default(10);
            $table->string('status', 12)->default('draft')->index();
            // One daily challenge per date. NULL + scheduled = waiting in the queue,
            // NULL + published = bonus challenge that only lives in the archive.
            $table->date('publish_date')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('challenge_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_id')->constrained()->cascadeOnDelete();
            $table->text('label');
            $table->boolean('is_correct')->default(false);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('challenge_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('challenge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('challenge_option_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_correct');
            // Answered on the challenge's own publish date — only these feed the streak.
            $table->boolean('is_daily')->default(false);
            $table->unsignedSmallInteger('xp_awarded')->default(0);
            $table->timestamp('submitted_at')->index();
            $table->timestamps();

            $table->unique(['user_id', 'challenge_id']);
        });

        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('name', 60);
            $table->string('description');
            $table->string('emoji', 16)->default('🏅');
            $table->string('criteria_type', 30);
            $table->unsignedInteger('criteria_value')->default(1);
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('xp_bonus')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('badge_id')->constrained()->cascadeOnDelete();
            $table->timestamp('earned_at');

            $table->unique(['user_id', 'badge_id']);
        });

        Schema::create('xp_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('amount');
            $table->string('reason', 30);
            $table->string('description');
            $table->foreignId('challenge_attempt_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('badge_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->index();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xp_ledger');
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('badges');
        Schema::dropIfExists('challenge_attempts');
        Schema::dropIfExists('challenge_options');
        Schema::dropIfExists('challenges');
        Schema::dropIfExists('categories');
    }
};

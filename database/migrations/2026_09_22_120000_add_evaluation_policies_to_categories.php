<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->string('evaluation_mode', 20)->nullable()->after('evaluations_count');
            $table->unsignedSmallInteger('human_evaluations_required')->nullable()->after('evaluation_mode');
            $table->unsignedSmallInteger('ai_evaluations_required')->nullable()->after('human_evaluations_required');
            $table->string('indication_mode', 20)->nullable()->after('nominations_count');
            $table->unsignedSmallInteger('human_indications_required')->nullable()->after('indication_mode');
            $table->unsignedSmallInteger('ai_indications_required')->nullable()->after('human_indications_required');
        });

        Schema::table('opinions', function (Blueprint $table): void {
            $table->string('source', 10)->default('human')->after('registration_id')->index();
        });
        Schema::table('indications', function (Blueprint $table): void {
            $table->string('source', 10)->default('human')->after('registration_id')->index();
        });

        DB::table('ai_executions')->whereNotNull('opinion_id')->pluck('opinion_id')
            ->each(fn ($id) => DB::table('opinions')->where('id', $id)->update(['source' => 'ai']));
        DB::table('ai_executions')->whereNotNull('indication_id')->pluck('indication_id')
            ->each(fn ($id) => DB::table('indications')->where('id', $id)->update(['source' => 'ai']));
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->dropColumn([
                'evaluation_mode', 'human_evaluations_required', 'ai_evaluations_required',
                'indication_mode', 'human_indications_required', 'ai_indications_required',
            ]);
        });
        Schema::table('opinions', function (Blueprint $table): void {
            $table->dropIndex(['source']);
            $table->dropColumn('source');
        });
        Schema::table('indications', function (Blueprint $table): void {
            $table->dropIndex(['source']);
            $table->dropColumn('source');
        });
    }
};

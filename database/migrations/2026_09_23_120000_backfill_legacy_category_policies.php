<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Categorias criadas antes das políticas de IA eram avaliadas apenas por humanos:
     * o total legado passa a ser a quantidade de humanos exigida.
     */
    public function up(): void
    {
        DB::table('categories')
            ->where('is_honorific', false)
            ->whereNull('evaluation_mode')
            ->update([
                'evaluation_mode' => 'human_only',
                'human_evaluations_required' => DB::raw('evaluations_count'),
                'ai_evaluations_required' => 0,
            ]);

        DB::table('categories')
            ->where('is_honorific', false)
            ->whereNull('indication_mode')
            ->update([
                'indication_mode' => 'human_only',
                'human_indications_required' => DB::raw('nominations_count'),
                'ai_indications_required' => 0,
            ]);
    }

    public function down(): void
    {
        //
    }
};

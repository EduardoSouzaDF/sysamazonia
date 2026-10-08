<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Categorias criadas antes das políticas de IA eram avaliadas apenas por humanos:
     * o total legado passa a ser a quantidade de humanos exigida.
     */
    public function up(): void
    {
        $restored = [];
        foreach (['evaluations_count', 'nominations_count', 'ai_evaluations_required', 'ai_indications_required'] as $column) {
            if (! Schema::hasColumn('categories', $column)) {
                Schema::table('categories', function (Blueprint $table) use ($column): void {
                    if (str_starts_with($column, 'ai_')) {
                        $table->unsignedSmallInteger($column)->nullable();
                    } else {
                        $table->unsignedInteger($column)->default(0);
                    }
                });
                $restored[] = $column;
            }
        }

        foreach (DB::table('categories')->get() as $category) {
            $evaluationAi = in_array($category->evaluation_mode, ['ai_only', 'hybrid'], true) ? 1 : 0;
            $indicationAi = in_array($category->indication_mode, ['ai_only', 'hybrid'], true) ? 1 : 0;
            $values = array_intersect_key([
                'evaluations_count' => $category->is_honorific ? 0 : (int) $category->human_evaluations_required + $evaluationAi,
                'nominations_count' => $category->is_honorific ? 0 : (int) $category->human_indications_required + $indicationAi,
                'ai_evaluations_required' => $category->is_honorific ? null : $evaluationAi,
                'ai_indications_required' => $category->is_honorific ? null : $indicationAi,
            ], array_flip($restored));
            if ($values !== []) {
                DB::table('categories')->where('id', $category->id)->update($values);
            }
        }

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

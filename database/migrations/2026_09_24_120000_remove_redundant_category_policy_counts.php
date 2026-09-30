<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $categories = DB::table('categories')->orderBy('id')->get();

        // Um total legado não permite inferir a distribuição entre humanos e IA.
        foreach ($categories->where('is_honorific', false) as $category) {
            foreach ([
                ['evaluation_mode', 'human_evaluations_required', 'ai_evaluations_required', 'evaluations_count'],
                ['indication_mode', 'human_indications_required', 'ai_indications_required', 'nominations_count'],
            ] as [$mode, $human, $ai, $total]) {
                $validMode = in_array($category->$mode, ['human_only', 'ai_only', 'hybrid'], true);
                $expectedAi = $category->$mode === 'human_only' ? 0 : 1;
                $validHuman = $category->$human !== null && ($category->$mode === 'ai_only'
                    ? (int) $category->$human === 0
                    : (int) $category->$human >= 1);
                if (! $validMode || ! $validHuman || $category->$ai === null
                    || (int) $category->$ai !== $expectedAi
                    || (int) $category->$total !== (int) $category->$human + $expectedAi) {
                    throw new RuntimeException("Categoria {$category->id}: política {$mode} ambígua ou inconsistente. Defina a política explicitamente antes de migrar.");
                }
            }
        }

        if ($categories->isNotEmpty()) {
            $directory = storage_path('app/private/backups');
            File::ensureDirectoryExists($directory, 0700);
            $path = $directory.'/category-policies-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json';
            $contents = json_encode($categories, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            if (File::put($path, $contents, true) !== strlen($contents) || ! chmod($path, 0600)) {
                throw new RuntimeException('Não foi possível guardar a cópia das categorias. Migração cancelada.');
            }
        }

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropColumn(['evaluations_count', 'nominations_count', 'ai_evaluations_required', 'ai_indications_required']);
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->unsignedInteger('evaluations_count')->default(0);
            $table->unsignedInteger('nominations_count')->default(0);
            $table->unsignedSmallInteger('ai_evaluations_required')->nullable();
            $table->unsignedSmallInteger('ai_indications_required')->nullable();
        });

        // Reconstitui os totais a partir das políticas vigentes no momento do rollback.
        foreach (DB::table('categories')->get() as $category) {
            $evaluationAi = in_array($category->evaluation_mode, ['ai_only', 'hybrid'], true) ? 1 : 0;
            $indicationAi = in_array($category->indication_mode, ['ai_only', 'hybrid'], true) ? 1 : 0;
            DB::table('categories')->where('id', $category->id)->update([
                'evaluations_count' => $category->is_honorific ? 0 : (int) $category->human_evaluations_required + $evaluationAi,
                'nominations_count' => $category->is_honorific ? 0 : (int) $category->human_indications_required + $indicationAi,
                'ai_evaluations_required' => $category->is_honorific ? null : $evaluationAi,
                'ai_indications_required' => $category->is_honorific ? null : $indicationAi,
            ]);
        }
    }
};

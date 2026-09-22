<?php

namespace App\Services;

use App\Enum\RegistrationStatusEnum;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RegistrationReportService
{
    /** @return array{markdown:string,filename:string,title:string,total:int,by_modality:array<string,int>,by_category:array<string,int>,missing:int} */
    public function generate(int $status): array
    {
        $statusEnum = RegistrationStatusEnum::tryFrom($status);

        if (! in_array($statusEnum, [RegistrationStatusEnum::Habilitado, RegistrationStatusEnum::Agraciado], true)) {
            throw new InvalidArgumentException('Status indisponível para este relatório.');
        }

        $regular = $this->regularRecords($status);
        $honorific = $this->honorificRecords($status);
        $records = $regular->concat($honorific)->sortBy([
            ['modality', 'asc'], ['category', 'asc'], ['name', 'asc'],
        ], SORT_NATURAL | SORT_FLAG_CASE)->values();

        $slug = $statusEnum === RegistrationStatusEnum::Habilitado ? 'habilitados' : 'agraciados';
        $title = 'Relatório de '.($slug === 'habilitados' ? 'Habilitados' : 'Agraciados');
        $byModality = $records->countBy('modality')->sortKeys(SORT_NATURAL | SORT_FLAG_CASE)->all();
        $byCategory = $records->countBy(fn (object $record): string => $record->modality.' — '.$record->category)
            ->sortKeys(SORT_NATURAL | SORT_FLAG_CASE)->all();
        $missing = $records->sum(fn (object $record): int => $this->missingFields($record));

        return [
            'markdown' => $this->renderMarkdown($title, $statusEnum, $records, $byModality, $byCategory, $missing),
            'filename' => "relatorio-{$slug}.md",
            'title' => $title,
            'total' => $records->count(),
            'by_modality' => $byModality,
            'by_category' => $byCategory,
            'missing' => $missing,
        ];
    }

    private function regularRecords(int $status): Collection
    {
        return DB::table('registrations as r')
            ->join('candidates as author', 'author.id', '=', 'r.candidate_id')
            ->join('categories as c', 'c.id', '=', 'r.category_id')
            ->join('modalities as m', 'm.id', '=', 'c.modality_id')
            ->where('r.status', $status)
            ->where('c.is_honorific', false)
            ->where('m.is_active', true)
            ->select([
                'r.id', 'r.title as name', 'r.coautores as coauthors', 'r.resumo as summary',
                'author.nome as author', 'author.ufendereco as state', 'm.title as modality',
                'c.title as category', DB::raw("'regular' as record_type"),
            ])->get();
    }

    private function honorificRecords(int $status): Collection
    {
        return DB::table('nominees as n')
            ->join('candidates as indicator', 'indicator.id', '=', 'n.candidate_id')
            ->join('categories as c', 'c.id', '=', 'n.category_id')
            ->join('modalities as m', 'm.id', '=', 'c.modality_id')
            ->where('n.status', $status)
            ->where('c.is_honorific', true)
            ->where('m.is_active', true)
            ->select([
                'n.id', 'n.name', 'n.state', 'n.justification as summary',
                'indicator.nome as indicator', 'm.title as modality', 'c.title as category',
                DB::raw("'honorific' as record_type"),
            ])->get();
    }

    private function renderMarkdown(string $title, RegistrationStatusEnum $status, Collection $records, array $byModality, array $byCategory, int $missing): string
    {
        $lines = ['# '.$title, ''];

        foreach ($records->groupBy('modality') as $modality => $modalityRecords) {
            $lines[] = '## Modalidade: '.$this->markdown($modality);
            $lines[] = '';

            foreach ($modalityRecords->groupBy('category') as $category => $categoryRecords) {
                $lines[] = '### Categoria: '.$this->markdown($category);
                $lines[] = '';

                foreach ($categoryRecords as $record) {
                    $lines[] = '#### '.$this->value($record->name);
                    $lines[] = '';
                    if ($record->record_type === 'honorific') {
                        $lines[] = '- **Estado do indicado:** '.$this->value($record->state);
                        $lines[] = '- **Indicador:** '.$this->value($record->indicator);
                        $this->appendLongField($lines, 'Justificativa da indicação', $record->summary);
                    } else {
                        $lines[] = '- **Autor:** '.$this->value($record->author);
                        $lines[] = '- **Coautores:** '.$this->coauthors($record->coauthors);
                        $lines[] = '- **Estado do autor:** '.$this->value($record->state);
                        $this->appendLongField($lines, 'Resumo da inscrição', $record->summary);
                    }
                    $lines[] = '';
                }
            }
        }

        if ($records->isEmpty()) {
            $lines[] = 'Nenhum registro ativo e válido foi encontrado para este status.';
            $lines[] = '';
        }

        $lines[] = '## Totais';
        $lines[] = '';
        $lines[] = '- **Total geral de registros:** '.$records->count();
        $lines[] = '- **Campos obrigatórios não informados:** '.$missing;
        $lines[] = '';
        $lines[] = '### Total por modalidade';
        $lines[] = '';
        $this->appendTotals($lines, $byModality);
        $lines[] = '';
        $lines[] = '### Total por categoria';
        $lines[] = '';
        $this->appendTotals($lines, $byCategory);
        $lines[] = '';
        $lines[] = '## Validação';
        $lines[] = '';
        $lines[] = '- Status validado pelo código interno do sistema: `'.$status->value.'` (`'.$status->label().'`).';
        $lines[] = '- Inscrições comuns consultadas em `registrations`, limitadas a categorias não honoríficas e modalidades ativas.';
        $lines[] = '- Indicações consultadas em `nominees`, limitadas a categorias honoríficas e modalidades ativas.';
        $lines[] = '- As consultas selecionam uma linha por ID; coautores são lidos do campo textual da inscrição, sem `join` multiplicador.';

        return implode("\n", $lines)."\n";
    }

    private function appendLongField(array &$lines, string $label, mixed $value): void
    {
        $text = $this->plainText($value);
        if ($text === '') {
            $lines[] = '- **'.$label.':** Não informado';

            return;
        }
        $lines[] = '- **'.$label.':**';
        $lines[] = '';
        foreach (explode("\n", $this->markdown($text)) as $paragraph) {
            $lines[] = '  '.$paragraph;
        }
    }

    private function appendTotals(array &$lines, array $totals): void
    {
        if ($totals === []) {
            $lines[] = '- Nenhum registro';

            return;
        }
        foreach ($totals as $label => $total) {
            $lines[] = '- **'.$this->markdown($label).':** '.$total;
        }
    }

    private function missingFields(object $record): int
    {
        $fields = $record->record_type === 'honorific'
            ? [$record->name, $record->state, $record->indicator, $record->summary]
            : [$record->name, $record->author, $record->state, $record->summary];

        return collect($fields)->filter(fn (mixed $value): bool => $this->plainText($value) === '')->count();
    }

    private function coauthors(mixed $value): string
    {
        $text = $this->plainText($value);
        if ($text === '') {
            return 'Nenhum';
        }

        return collect(preg_split('/\s*;\s*/u', $text) ?: [])->filter()->map(fn (string $name): string => $this->markdown($name))->implode(', ');
    }

    private function value(mixed $value): string
    {
        $text = $this->plainText($value);

        return $text === '' ? 'Não informado' : $this->markdown($text);
    }

    private function plainText(mixed $value): string
    {
        $text = (string) ($value ?? '');
        $text = preg_replace('/<\s*br\s*\/?>/iu', "\n", $text) ?? $text;
        $text = preg_replace('/<\/(p|div|li|h[1-6])\s*>/iu', "\n\n", $text) ?? $text;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\r\n", "\r", "\u{00A0}"], ["\n", "\n", ' '], $text);

        return trim(preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text);
    }

    private function markdown(string $value): string
    {
        return preg_replace('/([\\\\`*_{}\[\]()#+!|>])/u', '\\\\$1', $value) ?? $value;
    }
}

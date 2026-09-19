@php
    $maximum = max(array_column($statistics['current']['map'], 'total'));
    $levels = [0 => '0'];
    for ($level = 1; $level <= 4; $level++) {
        $lower = (int) floor(($level - 1) * $maximum / 4) + 1;
        $upper = (int) floor($level * $maximum / 4);
        if ($lower <= $upper) {
            $levels[$level] = $lower === $upper ? number_format($upper, 0, ',', '.') : number_format($lower, 0, ',', '.').'–'.number_format($upper, 0, ',', '.');
        }
    }
@endphp
<section aria-labelledby="brazil-map-title" class="dashboard-map-section">
    <div class="dashboard-heading"><div><h3 id="brazil-map-title" class="text-lg font-semibold">Distribuição pelo Brasil</h3><p class="dashboard-footnote">UF de residência do responsável pela inscrição. Selecione um estado para ver seus dados.</p></div></div>
    <div class="dashboard-map-grid">
        <div>
            <svg id="dashboard-brazil-map" viewBox="0 0 640 640" aria-labelledby="map-title map-description" role="group">
                <title id="map-title">Inscrições da edição por unidade da federação</title><desc id="map-description">Mapa com 26 estados e Distrito Federal. Use Tab e Enter para selecionar uma UF ou consulte a tabela abaixo.</desc>
                @foreach($statistics['current']['map'] as $state)
                    @php
                        $level = $state['total'] === 0 ? 0 : max(1, (int) ceil(4 * $state['total'] / max(1, $maximum)));
                        $description = $state['name'].' ('.$state['uf'].') · '.$state['region'].' · '.number_format($state['total'], 0, ',', '.').' inscrições · '.number_format($state['percentage'], 2, ',', '.').'% da edição';
                    @endphp
                    <path d="{{ $mapPaths[$state['uf']] }}" class="map-state map-level-{{ $level }}" data-uf="{{ $state['uf'] }}" tabindex="0" role="button" aria-pressed="false" aria-label="{{ $description }}" fill-rule="evenodd"><title>{{ $description }}</title></path>
                @endforeach
            </svg>
            <div class="dashboard-map-legend" aria-label="Legenda de intensidade">
                @foreach($levels as $level => $label)<span><i class="map-level-{{ $level }}" aria-hidden="true"></i>{{ $label }}</span>@endforeach
            </div>
            <p class="dashboard-footnote">Fonte cartográfica: IBGE, malha simplificada. Mapa armazenado localmente.</p>
        </div>
        <aside class="space-y-5">
            <div class="dashboard-selected-state" aria-live="polite" aria-atomic="true"><h4 class="font-semibold" id="selected-state-title">Nenhuma UF selecionada</h4><p id="selected-state-details">Clique no mapa ou escolha uma UF na tabela.</p></div>
            <h4 class="font-semibold">Totais por região</h4>
            <table class="kt-table"><caption class="sr-only">Regiões e percentual sobre o total da edição</caption><thead><tr><th scope="col">Região</th><th scope="col">Total</th><th scope="col">%</th></tr></thead><tbody>
                @foreach($statistics['current']['regions'] as $row)<tr><th scope="row">{{ $row['label'] }}</th><td>{{ number_format($row['total'], 0, ',', '.') }}</td><td>{{ number_format($row['percentage'], 2, ',', '.') }}%</td></tr>@endforeach
            </tbody></table>
            @if($statistics['current']['unmapped'])<p class="dashboard-footnote">{{ number_format($statistics['current']['unmapped'], 0, ',', '.') }} inscrição(ões) sem UF válida. Integram o total e os percentuais, mas não são coloridas no mapa.</p>@endif
        </aside>
    </div>
    <details class="dashboard-table-details"><summary>Consultar as 27 UFs, incluindo o Distrito Federal</summary><div class="overflow-x-auto"><table class="kt-table"><thead><tr><th scope="col">Estado</th><th scope="col">Região</th><th scope="col">Inscrições</th><th scope="col">% da edição</th></tr></thead><tbody>
        @foreach($statistics['current']['map'] as $state)<tr data-state-row="{{ $state['uf'] }}"><th scope="row"><button type="button" class="kt-link" data-select-uf="{{ $state['uf'] }}">{{ $state['name'] }} ({{ $state['uf'] }})</button></th><td>{{ $state['region'] }}</td><td>{{ number_format($state['total'], 0, ',', '.') }}</td><td>{{ number_format($state['percentage'], 2, ',', '.') }}%</td></tr>@endforeach
    </tbody></table></div></details>
</section>

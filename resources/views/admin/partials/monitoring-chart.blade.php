                    <div class="monitoring-distribution">
                        @if (array_sum($distribution))
                            <div class="monitoring-pie" data-monitoring-pie data-series="{{ json_encode(array_values($distribution)) }}" role="img" aria-label="{{ implode('; ', array_map(fn ($label, $total) => $label.': '.$total, array_keys($distribution), array_values($distribution))) }}"></div>
                        @else
                            <p class="monitoring-empty text-secondary-foreground">O gráfico estará disponível após as avaliações.</p>
                        @endif
                        <ul class="monitoring-legend" aria-label="Quantidade por classificação">
                            @foreach ($distribution as $label => $total)
                                <li><span class="monitoring-swatch monitoring-swatch-{{ $loop->index }}" aria-hidden="true"></span><span>{{ $label }}</span><strong>{{ $total }}</strong></li>
                            @endforeach
                        </ul>
                    </div>

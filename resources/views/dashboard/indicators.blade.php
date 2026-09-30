<div class="dashboard-indicators">
    @foreach(['total' => 'Total de inscrições', 'regular' => 'Inscrições regulares', 'honorary' => 'Indicações honoríficas'] as $key => $label)
        <div class="kt-card dashboard-indicator" data-indicator="{{ $key }}"><span class="text-secondary-foreground">{{ $label }}</span><strong>{{ number_format($data[$key], 0, ',', '.') }}</strong></div>
    @endforeach
</div>

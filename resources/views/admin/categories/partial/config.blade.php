@props([
    'errors' => null,
    'modalities' => [],
    'object' => null,
])

@php
    $honorificSelected = (bool) old('is_honorific', $object?->is_honorific ?? false);
@endphp

<div class="w-full mt-4">
    <div class="kt-form-group">
        <div class="flex items-center gap-2">
            <sl-switch value="1" @checked($honorificSelected) name="is_honorific" data-honorific-toggle> É honorífica ?</sl-switch>
        </div>
        <div class="kt-form-description mt-2">
            Categoria realiza nomeação de pessoa
        </div>
    </div>
</div>

<div class="kt-card p-5 mt-5 space-y-4 {{ $honorificSelected ? 'hidden' : '' }}" data-evaluation-policy data-regular-policy>
    <div><h3 class="font-semibold">Política de avaliação técnica</h3><p class="text-sm text-secondary-foreground">Defina quem deve emitir os pareceres necessários para concluir a avaliação.</p></div>
    <div class="grid md:grid-cols-3 gap-4">
        <label>Modo<select class="kt-select" name="evaluation_mode">
            @foreach(\App\Enum\EvaluationMode::cases() as $mode)<option value="{{ $mode->value }}" @selected(old('evaluation_mode', $object?->evaluation_mode?->value ?? 'hybrid') === $mode->value)>{{ $mode->label() }}</option>@endforeach
        </select></label>
        <label>Avaliações humanas necessárias<input class="kt-input" type="number" min="0" max="50" name="human_evaluations_required" value="{{ old('human_evaluations_required', $object?->human_evaluations_required ?? max(0, (int) ($object?->evaluations_count ?? 2) - 1)) }}"></label>
        <label>Avaliações IA necessárias<input class="kt-input" type="number" min="0" max="1" name="ai_evaluations_required" value="{{ old('ai_evaluations_required', $object?->ai_evaluations_required ?? 1) }}"><small>O sistema possui uma identidade técnica de IA por categoria.</small></label>
    </div>
</div>

<div class="kt-card p-5 mt-5 space-y-4 {{ $honorificSelected ? 'hidden' : '' }}" data-indication-policy data-regular-policy>
    <div><h3 class="font-semibold">Política de indicação estratégica</h3><p class="text-sm text-secondary-foreground">A avaliação técnica e a indicação podem usar modos diferentes.</p></div>
    <div class="grid md:grid-cols-3 gap-4">
        <label>Modo<select class="kt-select" name="indication_mode">
            @foreach(\App\Enum\EvaluationMode::cases() as $mode)<option value="{{ $mode->value }}" @selected(old('indication_mode', $object?->indication_mode?->value ?? 'hybrid') === $mode->value)>{{ $mode->label() }}</option>@endforeach
        </select></label>
        <label>Indicações humanas necessárias<input class="kt-input" type="number" min="0" max="50" name="human_indications_required" value="{{ old('human_indications_required', $object?->human_indications_required ?? max(0, (int) ($object?->nominations_count ?? 2) - 1)) }}"></label>
        <label>Indicações IA necessárias<input class="kt-input" type="number" min="0" max="1" name="ai_indications_required" value="{{ old('ai_indications_required', $object?->ai_indications_required ?? 1) }}"></label>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('[data-evaluation-policy], [data-indication-policy]').forEach((section) => {
    const mode = section.querySelector('select');
    const numbers = section.querySelectorAll('input[type="number"]');
    const apply = () => {
        if (mode.value === 'human_only') { numbers[0].min = 1; numbers[0].readOnly = false; numbers[1].value = 0; numbers[1].readOnly = true; }
        if (mode.value === 'ai_only') { numbers[0].value = 0; numbers[0].readOnly = true; numbers[1].value = 1; numbers[1].readOnly = true; }
        if (mode.value === 'hybrid') { numbers[0].readOnly = false; numbers[0].min = 1; if (Number(numbers[0].value) < 1) numbers[0].value = 1; numbers[1].value = 1; numbers[1].readOnly = true; }
    };
    mode.addEventListener('change', apply);
    apply();
});
const honorificToggle = document.querySelector('[data-honorific-toggle]');
const updateHonorificPolicies = () => document.querySelectorAll('[data-regular-policy]').forEach((section) => {
    const isHonorific = honorificToggle?.checked ?? honorificToggle?.hasAttribute('checked') ?? false;
    section.classList.toggle('hidden', Boolean(isHonorific));
});
honorificToggle?.addEventListener('sl-change', updateHonorificPolicies);
honorificToggle?.addEventListener('change', updateHonorificPolicies);
updateHonorificPolicies();
customElements.whenDefined('sl-switch').then(updateHonorificPolicies);
</script>
@endpush

<div class="flex w-full gap-4 mt-5 ">

    <div class="w-4/12">
        <x-form.input value="{{ isset($object) ? $object->nominations_count : old('nominations_count') }}" type="number"
            name="nominations_count" label="Quantidade de nomeações" placeholder="Ex: 3" :required="true" />
    </div>

    <div class="w-4/12 {{ $honorificSelected ? 'hidden' : '' }}" data-regular-policy>
        <x-form.input value="{{ isset($object) ? $object->evaluations_count : old('evaluations_count') }}"
            type="number" name="evaluations_count" label="Quantidade de avaliações Técnicas" placeholder="Ex: 3"
            :required="true" />
    </div>

    <div class="w-4/12">
        <x-form.input value="{{ isset($object) ? $object->recipients_count : old('recipients_count') }}" type="number"
            name="recipients_count" label="Quantidade de Agracidados" placeholder="Ex: 3" :required="true" />
    </div>


    <div class="w-4/12">
        <x-form.input

            value="{{ isset($object) ? $object->submissions_per_candidate : old('submissions_per_candidate') }}"
            type="number" name="submissions_per_candidate" label="Inscrições por candidato" placeholder="Ex: 3"
            :required="true" />
    </div>
</div>

<div class="flex w-full gap-4 mt-5 ">
    <div class="w-1/2">
        <x-form.input type="date" :value="isset($object) && $object->judging_start
            ? \Carbon\Carbon::parse($object->judging_start)->format('Y-m-d')
            : old('judging_start')" name="judging_start" label="Início do Julgamento"
            :required="true" />
    </div>

    <div class="w-1/2">
        <x-form.input type="date" :value="isset($object) && $object->judging_end
            ? \Carbon\Carbon::parse($object->judging_end)->format('Y-m-d')
            : old('judging_end')" name="judging_end" label="Fim do Julgamento" :required="true" />
    </div>
</div>

<div class="flex w-full gap-4 mt-5 ">
    <div class="w-1/2">
        <div class="kt-form-group">
            <div class="flex items-center gap-2">
                <sl-switch value="1" @checked((isset($object) && $object->is_open_for_submissions) || old('is_open_for_submissions')) name="is_open_for_submissions">
                    Ativa para inscrições ?
                </sl-switch>
            </div>

        </div>
    </div>
</div>

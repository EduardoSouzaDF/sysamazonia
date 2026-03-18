@props([
    'errors' => null,
    'modalities' => [],
    'object' => null,
])

<div class="w-full mt-4">
    <div class="kt-form-group">
        <div class="flex items-center gap-2">
            <sl-switch value="1" @checked((isset($object) && $object->is_honorific) || old('is_honorific')) name="is_honorific"> É honorífica ?</sl-switch>
        </div>
        <div class="kt-form-description mt-2">
            Categoria realiza nomeação de pessoa
        </div>
    </div>
</div>

<div class="flex w-full gap-4 mt-5 ">

    <div class="w-4/12">
        <x-form.input value="{{ isset($object) ? $object->nominations_count : old('nominations_count') }}" type="number"
            name="nominations_count" label="Quantidade de nomeações" placeholder="Ex: 3" :required="true" />
    </div>

    <div class="w-4/12">
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

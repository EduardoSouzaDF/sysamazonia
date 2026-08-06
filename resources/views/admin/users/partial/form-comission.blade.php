@props([
    'errors' => [],
    'object' => null,
])

<?php
$categories = $categories
    ->mapWithKeys(function ($categorie) {
        return [$categorie->id => $categorie->title];
    })
    ->toArray();


$categoriesEvaluators = $categoriesEvaluators->mapWithKeys(function ($categorie) {
        return [$categorie->id => $categorie->title];
    })
    ->toArray();

$indicatorCategories = $object ? $object->indicatorCategories->pluck('id')->implode(' ') : '';



$evaluatorCategories = $object ? $object->categoriesEvaluators->pluck('id')->implode(' ') : '';

?>

<div class="flex w-full gap-4">
    <div class="w-1/2">
        <div class="flex items-center gap-2">
            <input type="checkbox" @checked($object !== null && $object->is_judge) class="kt-checkbox" id="julgador" name="julgador"
                value="1" />
            <label class="kt-label" for="julgador">Julgador</label>
        </div>
    </div>

    <div class="w-1/2">
        <div class="flex items-center gap-2">
            <input type="checkbox" @checked($object !== null && $object->is_organizer) class="kt-checkbox" id="organizador" name="organizador"
                value="1" /><label class="kt-label" for="organizador">Organizador</label>
        </div>
    </div>
</div>

<div class="flex w-full gap-4 mt-4">

    <div class="w-1/2 ">
        <div class="flex items-center gap-2">
            <input @checked($object !== null && $object->isIndicator()) type="checkbox" class="kt-checkbox" id="seindicador" name="seindicador"
                value="1" /><label class="kt-label" for="seindicador">Indicador</label>
        </div>
    </div>
    <div class="w-1/2 ">

        <div id="indicador_" class="hidden mt-2">
            <x-form-select label="Categorias para Indicação" nameOld="indicador" name="indicador[]" id="indicador"
                value="{{ $indicatorCategories }}" multiple="{{ true }}" required="true" maxSelections="99"
                :options="$categories" />
        </div>
    </div>
</div>


<div class="flex w-full gap-4 mt-4">

    <div class="w-1/2 ">
        <div class="flex items-center gap-2">
            <input type="checkbox" @checked($object !== null && $object->isEvaluator()) class="kt-checkbox" id="seavaliador" name="seavaliador"
                value="1" /><label class="kt-label" for="seavaliador">Avaliador</label>
        </div>
    </div>
    <div class=" w-1/2 ">

        <div id="avaliador_" class="hidden  mt-2">
            <x-form-select label="Categorias para Avaliação" nameOld="avaliador" name="avaliador[]" id="avaliador"
                value="{{ $evaluatorCategories }}" multiple="{{ true }}" required="true" maxSelections="99"
                :options="$categoriesEvaluators" />
        </div>
    </div>
</div>
@push('scripts')
    <script>
        $(document).ready(function() {
            const seIndicador = $('#seindicador');
            seIndicador.on('change', () => {
                let checked = $('#seindicador').is(':checked');
                if (checked) {
                    $('#indicador_').removeClass('hidden');
                } else {
                    $('#indicador_').addClass('hidden');
                }
            });


            const seavaliador = $('#seavaliador');
            seavaliador.on('change', () => {
                console.log('ok');
                let checked = $('#seavaliador').is(':checked');
                if (checked) {
                    $('#avaliador_').removeClass('hidden');
                } else {
                    $('#avaliador_').addClass('hidden');
                }
            });
        });
    </script>

    @if ($object)
        <script>
            $(document).ready(function() {
                $('#seindicador').change();
                $('#seavaliador').change();

            });
        </script>
    @endif
@endpush

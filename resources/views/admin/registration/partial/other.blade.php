<?php
use Carbon\Carbon;
?>
@props([
    'object' => null,
])

<div class="flex w-full gap-4">
    <div class="w-1/2  ">
    <b>Escolaridade :</b>  {{ $object->candidate->escolaridade }}  <br>
    <b>Resumo Curricular :</b> <br>
    <div >
        {!! $object->candidate->resumo_curricular !!}
    </div>
     <br>
    <b>Instituição:</b>  {{ $object->candidate->instituicao }}  <br>
    <b>Instagram:</b>  {{ $object->candidate->instagram }} &nbsp;&nbsp;  <br>
    <b>Facebook:</b>  {{ $object->candidate->facebook }} &nbsp;&nbsp;  <br>
    <b>Outra rede social:</b>  {{ $object->candidate->outra_rede_social }}  <br>

    </div>
</div>


<?php
use Carbon\Carbon;
?>
@props([
    'object' => null,
])

<div class="flex w-full gap-4">
    <div class="w-1/2  ">
    <b>CPF:</b>  {{ $object->candidate->cpf }}  <br>
    <b>Nome :</b>  {{ $object->candidate->nome }}  <br>
    <b>Email:</b>  {{ $object->candidate->email }}  <br>
    <b>RG:</b>  {{ $object->candidate->rg }} &nbsp;&nbsp;
    <b>Órgão expeditor do RG:</b>  {{ $object->candidate->rg_expeditor }} &nbsp;&nbsp;
    <b>UF do RG:</b>  {{ $object->candidate->rg_uf }}  <br>
    <b>Data de Nascimento:</b>  {{   Carbon::parse($object->candidate->dt_nascimento)->format('d/m/Y') }}  <br>
    <b>Sexo:</b>  {{ $object->candidate->sexo }}  <br>
    <b>Celular:</b>  {{ $object->candidate->celular }}  <br>
    <b>Contato por whatsapp ? </b>  {{ $object->candidate->whatsapp == '1' ? 'Sim' : 'Não' }}  <br>
    </div>
</div>


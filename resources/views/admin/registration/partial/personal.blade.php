<?php
use Carbon\Carbon;
?>

<div class="flex w-full gap-4">
    <div class="w-1/2  ">
    <b>CPF:</b>  {{ $candidate->cpf }}  <br>
    <b>Nome :</b>  {{ $candidate->nome }}  <br>
    <b>Email:</b>  {{ $candidate->email }}  <br>
    <b>RG:</b>  {{ $candidate->rg }} &nbsp;&nbsp;
    <b>Órgão expeditor do RG:</b>  {{ $candidate->rg_expeditor }} &nbsp;&nbsp;
    <b>UF do RG:</b>  {{ $candidate->rg_uf }}  <br>
    <b>Data de Nascimento:</b>  {{   Carbon::parse($candidate->dt_nascimento)->format('d/m/Y') }}  <br>
    <b>Sexo:</b>  {{ $candidate->sexo }}  <br>
    <b>Celular:</b>  {{ $candidate->celular }}  <br>
    <b>Contato por whatsapp ? </b>  {{ $candidate->whatsapp == '1' ? 'Sim' : 'Não' }}  <br>
    </div>
</div>


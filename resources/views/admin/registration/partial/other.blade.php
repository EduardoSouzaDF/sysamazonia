<?php
use Carbon\Carbon;
?>
<div class="flex w-full gap-4">
    <div class="w-full  ">
    <b>Escolaridade :</b>  {{ $candidate->escolaridade }}  <br>
    <b>Resumo Curricular :</b> <br>
    <div style="min-width: 100%;" >
        {!! $candidate->resumo_curricular !!}
    </div>
     <br>
    <b>Instituição:</b>  {{ $candidate->instituicao }}  <br>
    <b>Instagram:</b>  {{ $candidate->instagram }} &nbsp;&nbsp;  <br>
    <b>Facebook:</b>  {{ $candidate->facebook }} &nbsp;&nbsp;  <br>
    <b>Outra rede social:</b>  {{ $candidate->outra_rede_social }}  <br>

    </div>
</div>


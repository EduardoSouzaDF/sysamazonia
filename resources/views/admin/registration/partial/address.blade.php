@props([
    'object' => null,
])

<div class="flex w-full gap-4">
    <div class="w-1/2  ">
    <b>CEP:</b>  {{ $object->candidate->cep }}  <br>
    <b>Estado:</b>  {{ $object->candidate->ufendereco }}  <br>
    <b>Cidade:</b>  {{ $object->candidate->cidade }}  <br>
    <b>Endereço:</b>  {{ $object->candidate->endereco }}  <br>
    <b>Número:</b>  {{ $object->candidate->numero }}  <br>
    <b>Complemento:</b>  {{ $object->candidate->complemento }}  <br>
    </div>
</div>

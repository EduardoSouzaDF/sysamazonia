@props([
    'object' => null,
])

<div class="flex w-full gap-4">
    <div class="w-full  ">
    <b>Edição :</b>  {{ $object->category->modality->edition->title }}  <br>
    <b>Modalidade :</b>  {{  $object->category->modality->title }}  <br>
    <b>Categoria :</b>  {{ $object->category->title }}  <br>

    @if (!$object->category->is_honorific)
         <b>Título:</b>  {{ $object->title }}  <br>
         <b>Coautores:</b>  {{ $object->coautores }}  <br>

        <sl-details summary="Resumo">
            <div >{!! $object->resumo !!}</div>
        </sl-details>

        <sl-details summary="Desenvolvimento">
            <div >{!! $object->desenvolvimento !!}</div>
        </sl-details>

        <sl-details summary="Objetivo">
            <div >{!! $object->objetivo !!}</div>
        </sl-details>
        <sl-details summary="Conclusão">
            <div >{!! $object->conclusao !!}</div>
        </sl-details>

    @else
         <b>Nome do(a) Indicado(a):</b>  {{ $object->name }}  <br>
         <b>Estado de Residência do(a) Indicado(a):</b>  {{  $object->contact_data }}  <br>
         <b>Dados de contato do(a) Indicado(a):</b>  {{  Str::upper($object->state )}}  <br>

        <sl-details summary="Apresentação do(a) Indicado(a)">
            <div >{!! $object->presentation !!}</div>
        </sl-details>

        <sl-details summary="Atividades desempenhadas: ">
            <div >{!! $object->activities !!}</div>
        </sl-details>

        <sl-details summary="Justifique a indicação">
            <div >{!! $object->justification !!}</div>
        </sl-details>

    @endif


    </div>
</div>

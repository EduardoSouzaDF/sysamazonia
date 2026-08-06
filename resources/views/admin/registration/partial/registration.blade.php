@props([
    'object' => null,
])



    <div class="w-full  ">
        <div class="  flex flex-row justify-end">
            @php
                $bgColor = match($object->getTextEvaluationAvg()) {
                    'Não Recomendado' => 'bg-red-300',
                    'Meritório' => 'bg-yellow-300',
                    'Recomendado' => 'bg-green-300',
                    default => 'bg-gray-300',
                };

            @endphp
            <div class="flex flex-col w-1/6  p-4 min-h-16 rounded-xl text-center {{ $bgColor }}  bg-red-400">
                <div>Nota Avaliação</div>
                <div class="place-self-center"> {{ $object->getEvaluationAvgPercentage() }}</div>
                <div class="place-self-center"> {{ $object->getTextEvaluationAvg() }}</div>
            </div>
        </div>
    <b>Edição :</b>  {{ $object->category->modality->edition->title }}  <br>
    <b>Modalidade :</b>  {{  $object->category->modality->title }}  <br>
    <b>Categoria :</b>  {{ $object->category->title }}  <br>

    @if (!$object->category->is_honorific)
         <b>Título:</b>  {{ $object->title }}  <br>
         <b>Coautores:</b>  {{ $object->coautores }}  <br><br>

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
         <b>Dados de contato do(a) Indicado(a):</b>  {{  Str::upper($object->state )}}  <br><br>

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

    <sl-details summary="Arquivos">
        @if ($object->files)
            @foreach ($object->files as $file)
                @php
                    $fileName = $file->file_path;
                @endphp
                <a target="_blank" href="{{ route('admin.registration.file', ['file' => $file->id]) }}"

                class="kt-btn kt-btn-outline">
                Ver anexo : {{ $file->file_name }}
            </a>
            @endforeach

        @endif
    </sl-details>

    @if(auth()->user()->hasRole('admin'))
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <div class="flex flex-row justify-end gap-4 mt-4">
         @if( $object->statusb <= 3 )
         <button type="button" class="kt-btn habilitaInscricao"
          onclick="habilitaInscricao('{{ $object->id }}', '{{get_class($object)}}')"
          >Habilitar</button>


          <button type="button"
            onclick="rejeitaInscricao('{{ $object->id }}', '{{get_class($object)}}')"
          class="kt-btn kt-btn-destructive">Rejeitar</button>
         @endif
        </div>
    @endif

    @if(auth()->user()->hasRole('admin'))

    @endif

    </div>
</div>




@if(auth()->user()->hasRole('admin'))
    @push('scripts')
    <script type="text/javascript">
        function habilitaInscricao(id,type){
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    let url = "{{ route('admin.registration.habilitar', ['id' => ':id', 'type' => ':type']) }}";
                    url = url.replace(':id', id).replace(':type', type);
                    $.ajax({
                        url,
                        type: 'POST',
                        success: function(result) {
                            $('.alter-success').removeClass('hidden');
                        setTimeout(function() {
                            window.location =window.location;
                        }, 2000); // 2000ms = 2 segundos
                        },
                        error: function(xhr) {
                        $('.alter-danger').removeClass('hidden');

                        }
                    });

        }

        function rejeitaInscricao(id,type){
             $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    let url = "{{ route('admin.registration.rejeitar', ['id' => ':id', 'type' => ':type']) }}";
                    url = url.replace(':id', id).replace(':type', type);
                    $.ajax({
                        url,
                        type: 'POST',
                        success: function(result) {
                            $('.alter-success').removeClass('hidden');
                        setTimeout(function() {
                            window.location =window.location;
                        }, 2000); // 2000ms = 2 segundos
                        },
                        error: function(xhr) {
                        $('.alter-danger').removeClass('hidden');

                        }
                    });
        }
    </script>
    @endpush

@endif

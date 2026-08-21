@php
use Carbon\Carbon;
@endphp

@props([
    'object' => null,
])



    <div class="w-full  ">

        @if(get_class($object) == "App\Models\Registration")
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
        @endif
       
    <b>Edição :</b>  {{ $object->category->modality->edition->title }}  <br>
    <b>Modalidade :</b>  {{  $object->category->modality->title }}  <br>
    <b>Categoria :</b>  {{ $object->category->title }}  <br>

    @if (!$object->category->is_honorific)
         <b>Título:</b>  {{ $object->title }}  <br>

         @if($user->isAdmin())
            <b>Coautores:</b>  {{ $object->coautores }}  <br><br>
         @endif
         

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

    @if(sizeof($object->files))
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
    @endif
    
   
    @if (get_class($object) == "App\Models\Registration" && $object->opinionsWithScores() && $user->isAdmin() && sizeof($object->opinionsWithScores()))
        <sl-details summary="Avaliações">
            @foreach ($object->opinionsWithScores() as $opinion )
                <sl-details summary="Avaliado em: {{Carbon::parse( $opinion->created_at)->format('d/m/Y') }} por: {{ $opinion->user->name }}">
                
                    @foreach ($opinion->scores as $score )
                    <div class="w-full  space-y-3   " >
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="text-muted-foreground">
                                <b>{{$score->evaluationCriterion->name}}: {{ (int) $score->valor }}</b>
                            </span>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{$score->descricao}}
                        </p>
                    </div>
                    <br>
                    <br>
                    @endforeach
                
                </sl-details>
                
                <br><br>
            @endforeach
        </sl-details>

        @if(sizeof($object->indications))
        <sl-details summary="Indicações">
            @foreach ($object->indications as $indication )
                <sl-details summary="Indicado em: {{Carbon::parse( $indication->created_at)->format('d/m/Y') }} por: {{ $indication->user->name }}">
                     <p class="text-xs text-muted-foreground">
                            {{$indication->descricao}}
                    </p>
                </sl-details>
                
                <br><br>
            @endforeach
        </sl-details>
        @endif
        
    @endif

     

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

    @if(auth()->user()->isIndicator())
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <div class="flex flex-col justify-end gap-4 mt-4 w-full">
         <sl-textarea class="w-full" label="Justificativa da Indicação" name="justificativa"></sl-textarea>
         <button type="button" class="kt-btn habilitaInscricao"
          onclick="IndicarInscricao('{{ $object->id }}')"
          >Indicar Inscrição</button>

          <sl-alert class=" hidden w-full alter-danger-form" variant="danger" open>
            <sl-icon slot="icon" name="shield-fill-exclamation"></sl-icon>
            <strong>Erro !.</strong><br />
            Preencha o Formulário
        </sl-alert>
    @endif

 

    </div>
 
 
@if(auth()->user()->isIndicator())
    @push('scripts')
         <script type="text/javascript">
            function IndicarInscricao(id){
                var justificativa = $("[name='justificativa']").val();
                    if(justificativa !== undefined && justificativa !== ""){
                        $.ajaxSetup({
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            }
                        });
                        let url = "{{ route('admin.registration.indicar', ['id' => ':id']) }}";
                        url = url.replace(':id', id);
                        $.ajax({
                            url,
                            data:{
                             justificativa: justificativa
                            },
                            type: 'POST',
                            success: function(result) {
                                $('.alter-success').removeClass('hidden');
                            setTimeout(function() {
                                window.location = "{{ route('admin.registration.index') }}";
                            }, 2000); // 2000ms = 2 segundos
                            },
                            error: function(xhr) {
                            $('.alter-danger').removeClass('hidden');

                            }
                        });
                    }else{
                      
                            $('.alter-danger-form').removeClass('hidden');
                    }

        }
        </script>
    @endpush
@endif



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

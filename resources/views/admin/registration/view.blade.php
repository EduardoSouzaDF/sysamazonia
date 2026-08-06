@php
use App\Enum\RolesEnum;
use Illuminate\Support\Facades\Auth;

    $user = Auth::user();
    $titulo = 'Visualização de Inscrição';

    if($user->isAdmin()){
        $qualificadas = $registrations->filter(function($reg){
            return $reg->status >= 3;
        });

        $rejeitadas = $registrations->filter(function($reg){
            return $reg->status == 2;
        });
    }else{
        $titulo = 'Avaliar Inscrição';
        $qualificadas = [];
        $rejeitadas = [];
    }

    $useForm =  $user->isEvaluator() ? false : true;
@endphp

@extends('admin.content')
@section('maincontent')
    <style>
        .details-group-example sl-details:not(:last-of-type) {
            margin-bottom: var(--sl-spacing-2x-small);
        }
    </style>

    <x-pages.crud.create btnSubmit='{{ false }}'
    useForm='{{$useForm}}'
    titulo='{{ $titulo }}' btnCancelTitle='Voltar' btnCancelRoute="{{ route('admin.registration.index') }}"  use-tabs=false>
         @if($user->isAdmin())
                <div class="w-full flex flex-row justify-between gap-2 min-h-10">
                        <div>  <b>Autor:</b> {{ $candidate->nome }} </div>
                        <div class="w-1/4  bg-green-300 min-h-16 rounded-xl flex flex-row justify-between p-4">
                            <div class="flex flex-col">
                                <div>Inscrições</div>
                                <div class="place-self-center">{{ sizeof($registrations) }}</div>
                            </div>

                            <div class="flex flex-col text-blue-500">
                                <div>Qualificadas</div>
                                <div class="place-self-center">{{ sizeof($qualificadas) }}</div>
                            </div>
                            <div class="flex flex-col text-red-500">
                                <div>Rejeitadas</div>
                                <div class="place-self-center">{{ sizeof($rejeitadas) }}</div>
                            </div>


                    </div>
                </div>
            <sl-details summary="Dados do Candidato">
                <div class="flex w-full gap-4">
                    <div class="w-1/2  ">
                        <h3><b>Dados Pessoais</b></h3> <br>
                        @include('admin.registration.partial.personal', $candidate)
                    </div>
                    <div class="w-1/2  ">
                        <h3><b>Endereço</b></h3> <br>
                        @include('admin.registration.partial.address', $candidate)
                    </div>
                </div>
                <div class="flex w-full gap-4 mt-4">
                    <div class="w-full  ">
                        <h3><b>Outros dados</b></h3> <br>
                        @include('admin.registration.partial.other', $candidate)
                    </div>
                </div>
            </sl-details>
        @endif


        @foreach ($registrations as $object)
            @if ($user->isAdmin())
                <sl-details summary="{{ $object->category->modality->edition->title }} - {{ $object->category->modality->title }} - {{ $object->category->title }} :  {{ $object->title ?: $object->name }}">
                     @include('admin.registration.partial.registration', $object)
                </sl-details>
            @else
                @include('admin.registration.partial.registration', $object)

                @if($user->isEvaluator())
                    <div class="kt-card w-[400px]">
                        <div class="kt-card-content  ">
                            <form
                            method="POST"
                            action="{{ route('admin.registration.send.opinion', $object->id) }}"
                            >
                                @csrf
                                <div>
                                    <h3 class="text-sm font-semibold text-foreground">Fazer Avaliação</h3>
                                    <br>
                                    <div class="flex flex-col w-full  justify-between text-sm ">
                                        @foreach ($object->evaluationCriteriaList() as $criteria )
                                            <div class="w-full  space-y-3 " >
                                                <div class="flex items-center justify-between gap-3 text-sm">
                                                    <span class="text-muted-foreground">
                                                        <b>{{ $criteria->name }}</b>
                                                        &nbsp;&nbsp; Mínimo: {{(int) $criteria->min_score}}
                                                        &nbsp;&nbsp; Máximo: {{(int) $criteria->max_score}}
                                                         &nbsp;&nbsp; Peso: {{(int) $criteria->weight}}
                                                    </span>
                                                </div>
                                                <sl-range tooltip="top" name="criteria[{{ $criteria->id }}]"  min="{{(int) $criteria->min_score}}"  max="{{(int) $criteria->max_score}}"  step="1"></sl-range>

                                                <p class="text-xs text-muted-foreground">
                                                    {{ $criteria->description }}
                                                </p>
                                            </div>
                                            <br><br>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="kt-card-footer justify-end ">
                                   <button type="submit" class="kt-btn " >Avaliar</button>
                                </div>
                            </form>
                        </div>
                @endif
            @endif

        @endforeach

    <sl-alert class="hidden  w-full alter-success" variant="success" open>
        <sl-icon slot="icon" name="check2-circle"></sl-icon>
        <strong>Operação realizada.</strong><br />
        Registro Alterado
    </sl-alert>

    <sl-alert class=" hidden w-full alter-danger" variant="danger" open>
        <sl-icon slot="icon" name="shield-fill-exclamation"></sl-icon>
        <strong>Erro !.</strong><br />
        Erro ao alterar registro. Verifique as permissões necessárias.
    </sl-alert>
    </x-pages.crud.create>
@endsection

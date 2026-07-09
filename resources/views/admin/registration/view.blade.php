@php
    use App\Enum\RolesEnum;
    $object = $registration ?? null;
    $titulo = 'Visualização de Inscrição';

    $tabs = [
        [
            'buttonId' => 'personal_btn',
            'tabId' => 'tab_personal',
            'titulo' => 'Dados Pessoais',
            'active' => true,
            'include' => 'admin.registration.partial.form-personal',
            'includeData' => [
                'object' => $object,
            ],
        ],
    ];

@endphp

@extends('admin.content')
@section('maincontent')
    <style>
        .details-group-example sl-details:not(:last-of-type) {
            margin-bottom: var(--sl-spacing-2x-small);
        }
    </style>

    <x-pages.crud.create btnSubmit='{{ false }}' titulo='{{ $titulo }}' btnCancelTitle='Voltar' btnCancelRoute="{{ route('admin.registration.index') }}"  use-tabs=false>




        <div class="details-group-example">
            <sl-details summary="Visão Única">
                <div class="flex w-full gap-4">
                    <div class="w-1/2  ">
                        <h3><b>Dados Pessoais</b></h3> <br>
                        @include('admin.registration.partial.personal', $object)
                    </div>

                    <div class="w-1/2  ">
                        <h3><b>Endereço</b></h3> <br>
                        @include('admin.registration.partial.address', $object)
                    </div>


                </div>

                <div class="flex w-full gap-4 mt-4">
                    <div class="w-1/2  ">
                        <h3><b>Outros dados</b></h3> <br>
                        @include('admin.registration.partial.other', $object)
                    </div>

                     <div class="w-1/2  ">
                        <h3><b>Outros dados</b></h3> <br>
                        @include('admin.registration.partial.registration', $object)
                    </div>
                </div>
            </sl-details>
            <sl-details summary="Dados Pessoais" open>
                @include('admin.registration.partial.personal', $object)
            </sl-details>

            <sl-details summary="Endereço">
                @include('admin.registration.partial.address', $object)
            </sl-details>

            <sl-details summary="Outros Dados">
                @include('admin.registration.partial.other', $object)
            </sl-details>

            <sl-details summary="Inscrição">
                @include('admin.registration.partial.registration', $object)
            </sl-details>


            <sl-details summary="Arquivos">
                @if ($object->files)
                    @foreach ($object->files as $file)
                        @php
                            $fileName = $file->file_path;
                        @endphp
                       <a target="_blank" href="{{ route('admin.registration.file', ['file' => $file->id]) }}"

                        class="kt-btn kt-btn-outline">
                        Ver/Fazer Download do Arquivo PDF
                    </a>
                    @endforeach

                @endif
            </sl-details>





        </div>
    </x-pages.crud.create>
@endsection

@push('scripts')
    <script>
        // $(document).ready(function() {
        //     const container = document.querySelector('.details-group-example');

        //     // Close all other details when one is shown
        //     container.addEventListener('sl-show', event => {
        //         if (event.target.localName === 'sl-details') {
        //             [...container.querySelectorAll('sl-details')].map(details => (details.open = event
        //                 .target === details));
        //         }
        //     });
        // });
    </script>
@endpush

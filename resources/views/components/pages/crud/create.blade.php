@props([
    'titulo' => 'titulo',
    'formAction' => '',
    'formId' => '',
    'useTabs' => false,
    'tabs' => [],
    'btnCancelTitle' => 'Cancelar',
    'btnCancelRoute' => '',
    'btnSubmitTitle' => '',
    'put' => false,
    'space' => true,
    'btnDelete' => false,
    'btnSubmit' => true,
    'useForm' => true,
    'btnDeleteRoute' => '',
    'btnDeleteId' => 'btnDeleteId',
])

<div class="kt-container-fixed">
    <div class="kt-card min-w-full">
        <div class="kt-card-header">
            <h3 class="kt-card-title">
                {{ $titulo }}
            </h3>


        </div>

        @if($useForm)
            <form method="POST" id="{{ $formId }}" action="{{ $formAction }}" enctype="multipart/form-data" novalidate
            class="kt-form">
            @csrf
            @if ($put)
                @method('PUT')
            @endif
        @endif



            @if ($useTabs)
                <div class="kt-card-content" data-kt-tabs-hidden-class="hidden" data-kt-tabs-active-class="active">
                @else
                    <div class="kt-card-content">
            @endif

            <div class="space-y-3">
                @if ($useTabs)
                    <x-elements.tabs :tabs="$tabs" />
                    @if ($errors->any())
                        <sl-alert variant="danger" class="mt-4" open>
                            <sl-icon slot="icon" name="exclamation-octagon"></sl-icon>
                            <strong>Formulário Contém erros</strong><br />
                            Favor verificar
                        </sl-alert>
                    @endif
                @endif
                {{ $slot }}
            </div>

    </div>
    <div class="kt-card-footer justify-end gap-4">
        @if ($btnDelete)
            <a id='{{ $btnDeleteId }}' class="kt-btn kt-btn-destructive kt-btn-outline">Excluir</a>
        @endif
        <a href="{{ $btnCancelRoute }}" class="kt-btn kt-btn-outline">{{ $btnCancelTitle }}</a>

        @if ($btnSubmit)
            <button type="submit" class="kt-btn">{{ $btnSubmitTitle }}</button>
        @endif

    </div>

    @if($useForm)
        </form>
    @endif

</div>
</div>

@props([
    'roles' => [],
    'object' => null,
    'errors' => [],
])
<?php
$roles = $roles
    ->mapWithKeys(function ($role) {
        return [$role->id => $role->name];
    })
    ->toArray();

$rolesValue = $object ? $object->roles->pluck('id')->implode(' ') : '';
?>

<div class="flex w-full gap-4">
    <div class="w-1/2">
        <x-form.input type="text" class="mb-4" label="Nome Completo" placeholder="Nome completo" name="name"
            :value="isset($object) ? $object->name : null" required="false" description="Informe o nome completo" />
    </div>

    <div class="w-1/2">
        <x-form.input type="text" class="mb-4" label="Telefone" placeholder="Telefone" name="telefone"
            :value="isset($object) ? $object->telefone : null" required="false" description="Informe o telefone" />
    </div>

</div>

<div class="flex w-full gap-4">
    <div class="w-1/2">
        <x-form.input required="false" class="mb-4" label="Email" name="email" type="email" placeholder="Email"
            :value="isset($object) ? $object->email : null" description="Por favor, informe um email válido." />

    </div>

    <div class="w-1/2">
        <x-form-select label="Tipo de Usuário" nameOld="roles" name="roles[]" id="roles" value="{{ $rolesValue }}"
            multiple="{{ true }}" required="true" maxSelections="99" :options="$roles" />
    </div>
</div>


@push('scripts')
    <script src="{{ asset('js/jquery.maskedinput.min.js') }}"></script>
    <script>
        $(document).ready(function() {


            $("input[name='telefone']").mask("(99) 99999-9999");
            // Se for um campo input do tipo date, mostrar máscara somente no Safari e Firefox pra evitar problemas com validação e mobile
            var isFirefox = typeof InstallTrigger !== 'undefined';
            var isSafari = Object.prototype.toString.call(window.HTMLElement).indexOf('Constructor') >
                0 || (function(p) {
                    return p.toString() === "[object SafariRemoteNotification]";
                })(!window['safari'] || safari.pushNotification);

            //Máscara de telefone fixo ou celular automático
            $.mask.definitions['~'] = ['+-'];
            $("input[name='telefone']").focusout(function() {
                var phone, element;
                element = $(this);
                element.unmask();
                phone = element.val().replace(/\D/g, '');
                if (phone.length > 10) {
                    element.mask('(99) 99999-999?9');
                } else {
                    element.mask('(99) 9999-9999?9');
                }
            }).trigger('focusout');

        });
    </script>

    @if ($object)
        <script>
            $(document).ready(function() {
                $("input[name='telefone']").val('{{ $object->telefone }}').trigger('input');
                let selectRoles = document.querySelector("sl-select[name='roles[]']");
                const evento = new CustomEvent('sl-change', {
                    bubbles: true,
                    cancelable: true
                });

                setTimeout(() => {
                    selectRoles.dispatchEvent(evento);
                }, 1500);

            });
        </script>
    @endif
@endpush

@props([
    'errors' => [],
    'object' => null,
])
@php
    $escolaridade = [
        'Ensino_Fundamental' => 'Ensino Fundamental',
        'Ensino_Médio' => 'Ensino Médio',
        'Ensino_Superior' => 'Ensino Superior',
        'Ensino_Fundamental_Incompleto' => 'Ensino Fundamental Incompleto',
        'Ensino_Médio_Incompleto' => 'Ensino  Médio Incompleto',
        'Ensino_Superior_Incompleto' => 'Ensino Superior Incompleto',
    ];

@endphp


<div class="flex w-full gap-4">
    <div class="w-1/2">
        <x-form.input type="text" class="mb-4 telefone-celular" label="WhatsApp" placeholder="WhatsApp" name="whatsapp"
            value="{{ $object->extraData->whatsapp ?? '' }}" required="{{ false }}"
            description="Informe o númedo de contato do WhatsApp" />
    </div>

    <div class="w-1/2">
        <x-form-select label="Escolaridade" nameOld="escolaridade" name="escolaridade" id="escolaridade"
            value="{{ $object->extraData->escolaridade ?? '' }}" required="{{ false }}"
            multiple="{{ false }}" required="false" :options="$escolaridade" />
    </div>

</div>



<div class="flex w-full gap-4">
    <div class="w-1/2">
        <x-form.input type="text" class="mb-4" label="Área de Atuação" placeholder="Área de Atuação"
            value="{{ $object->extraData->area_atuacao ?? '' }}" name="area_atuacao" required="{{ false }}" />
    </div>

    <div class="w-1/2">
        <x-form.input type="text" class="mb-4" label="Indicado por" placeholder="Indicado por" name="indicado"
            value="{{ $object->extraData->indicado ?? '' }}" required="{{ false }}"
            description="Quem indicou a pessoa" />
    </div>

</div>

<div class="flex w-full gap-4">
    <div class="w-1/2">
        <x-form.input type="text" class="mb-4" label="Empresa / Instituição" placeholder="Empresa / Instituição"
            value="{{ $object->extraData->empresa ?? '' }}" name="empresa" required="{{ false }}" />
    </div>

    <div class="w-1/2">
        <x-form.input type="text" class="mb-4" label="Cargo" placeholder="Cargo" name="cargo"
            value="{{ $object->extraData->cargo ?? '' }}" required="{{ false }}" description="Cargo" />
    </div>

</div>

<div class="flex w-full gap-4">
    <div class="w-1/2">
        <x-form.input type="text" class="mb-4" label="Cep" placeholder="xxxxx-xx" name="cep"
            value="{{ $object->extraData->cep ?? '' }}" required="{{ false }}" />
    </div>



</div>



<div class="flex w-full gap-4">
    <div class="w-1/2">
        <x-form-select label="Estado" nameOld="estado" name="estado" id="estado" multiple="{{ false }}"
            value="{{ $object->extraData->estado ?? '' }}" required="{{ false }}" />
    </div>

    <div class="w-1/2">
        <x-form-select label="Cidade" nameOld="cidade" name="cidade" id="cidade" multiple="{{ false }}"
            value="{{ $object->extraData->cidade ?? '' }}" required="{{ false }}" />
    </div>

</div>



<div class="flex w-full gap-4 mt-4">
    <div class="w-1/2">
        <x-form.input type="text" class="mb-4" label="Logradouro" placeholder="Logradouro" name="logradouro"
            id="logradouro" value="{{ $object->extraData->logradouro ?? '' }}" required="{{ false }}" />
    </div>

    <div class="w-1/2">
        <x-form.input type="text" class="mb-4" label="Complemento" placeholder="Complemento" name="complemento"
            id="complemento" value="{{ $object->extraData->complemento ?? '' }}" required="{{ false }}" />
    </div>

</div>


<div class="flex w-full gap-4 mt-4">
    <div class="w-1/2">
        <x-form.input type="text" class="mb-4" label="Unidade" placeholder="Unidade" name="unidade"
            id="unidade" value="{{ $object->extraData->unidade ?? '' }}" required="{{ false }}" />
    </div>

    <div class="w-1/2">
        <x-form.input type="text" class="mb-4" value="{{ $object->extraData->bairro ?? '' }}" label="Bairro"
            placeholder="Bairro" name="bairro" id="bairro" required="{{ false }}" />
    </div>

</div>

<div class="flex w-full gap-4 mt-4">
    <div class="w-1/2">
        <x-form.input type="text" class="mb-4" label="Instagram" placeholder="Instagram" name="instagram"
            value="{{ $object->extraData->instagram ?? '' }}" required="{{ false }}" />
    </div>

    <div class="w-1/2">
        <x-form.input type="text" class="mb-4" label="Facebook" placeholder="Facebook" name="facebook"
            value="{{ $object->extraData->facebook ?? '' }}" required="{{ false }}" />
    </div>

</div>
<div class="flex w-full gap-4">
    <div class="w-1/2">
        <x-form.input type="text" class="mb-4" label="LinkedIn" placeholder="LinkedIn" name="linkedin"
            value="{{ $object->extraData->linkedin ?? '' }}" required="{{ false }}" />
    </div>

</div>

@push('scripts')
    <script src="{{ asset('js/jquery.maskedinput.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            var jsonData = window.location.origin + '/json/estados_cidades.json';
            $.getJSON(jsonData, function(data) {
                const selectEstado = document.querySelector("sl-select[name='estado']");
                const selectCidade = document.querySelector("sl-select[name='cidade']");
                const option = document.createElement("sl-option");
                option.setAttribute("value", "");
                option.innerText = "Escolha um Estado";
                selectEstado.append(option);

                var estados = [];
                var options = '<sl-option value="">escolha um estado</sl-option>';

                $.each(data, function(key, val) {

                    const option = document.createElement("sl-option");
                    option.setAttribute("value", val.sigla);
                    option.innerText = val.nome;
                    selectEstado.append(option);
                });

                selectEstado.addEventListener('sl-change', event => {
                    while (selectCidade.firstChild) {
                        selectCidade.removeChild(selectCidade.firstChild);
                    }
                    let value = event.target.value;
                    $.each(data, function(key, val) {
                        if (val.sigla == value) {
                            $.each(val.cidades, function(key_city, val_city) {
                                const option = document.createElement("sl-option");
                                option.setAttribute("value", val_city);
                                option.innerText = val_city;
                                selectCidade.append(option);
                            });
                        }
                    });

                });





                $("input[name='whatsApp']").mask("(99) 99999-9999");

                // Se for um campo input do tipo date, mostrar máscara somente no Safari e Firefox pra evitar problemas com validação e mobile
                var isFirefox = typeof InstallTrigger !== 'undefined';
                var isSafari = Object.prototype.toString.call(window.HTMLElement).indexOf('Constructor') >
                    0 || (function(p) {
                        return p.toString() === "[object SafariRemoteNotification]";
                    })(!window['safari'] || safari.pushNotification);

                //Máscara de telefone fixo ou celular automático
                $.mask.definitions['~'] = ['+-'];
                $("input[name='whatsApp']").focusout(function() {
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



            $("input[name='cep']").mask("99999999");
            $("input[name='cep']").on('change', (event) => {
                let val = event.target.value;
                let url = 'https://viacep.com.br/ws/' + val + '/json/';
                if (val.length == 8) {
                    console.log(url);
                    $.getJSON(url, function(data) {

                        if (data.erro !== 'true') {
                            const selectEstado = document.querySelector(
                                "sl-select[name='estado']");
                            const selectCidade = document.querySelector(
                                "sl-select[name='cidade']");
                            selectEstado.value = data.uf.toUpperCase();
                            const evento = new CustomEvent('sl-change', {
                                bubbles: true,
                                cancelable: true
                            });
                            selectEstado.dispatchEvent(evento);
                            let localidade = formatarLocalidade(data.localidade);
                            selectCidade.value = localidade;
                            $('#logradouro').val(data.logradouro);
                            $('#complemento').val(data.complemento);
                            $('#unidade').val(data.unidade);
                            $('#bairro').val(data.bairro);
                        }

                    });
                }
            })
        });

        function formatarLocalidade(str) {
            return str
                .toLowerCase()
                .split(' ')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join('_');
        }
    </script>



    @if ($object)
        <script>
            $(document).ready(function() {
                $("input[name='cep']").val('{{ $object->extraData->cep ?? '' }}').trigger('input');
                const selectEstado = document.querySelector(
                    "sl-select[name='estado']");

                const evento = new CustomEvent('sl-change', {
                    bubbles: true,
                    cancelable: true
                });

                setTimeout(() => {
                    selectEstado.dispatchEvent(evento);
                }, 1200);


            });
        </script>
    @endif
@endpush

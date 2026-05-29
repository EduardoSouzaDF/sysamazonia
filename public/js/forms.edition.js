let urlAmazonia = 'https://hmsisamazonia.ibict.br';
// let urlAmazonia = 'https://sysamazonia.test';
let hasRegistration = null;

function addCss(url) {
    return new Promise((resolve, reject) => {
        const link = document.createElement("link");
        link.rel = "stylesheet";
        link.type = "text/css";
        link.href = url;
        link.onload = () => resolve();
        link.onerror = () => reject(new Error(`Failed to load CSS: ${url}`));
        document.head.insertBefore(link, document.head.firstChild);
        //document.head.appendChild(link);
    });
}

function addJs(src, type = "text/javascript") {
    return new Promise((resolve, reject) => {
        const script = document.createElement("script");
        script.type = "text/javascript";
        script.src = src;
        script.async = true;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error(`Failed to load JS: ${src}`));
        document.head.appendChild(script);
    });
}
$(document).ready(async function() {
    const jsFiles = [
        "https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-lite.min.js",
        urlAmazonia+"/build/assets/ktui.min-55m0iKcC.js",
         urlAmazonia+"/js/jquery.maskedinput.min.js",
         urlAmazonia+"/js/longbow.slidercaptcha.min.js",
        "https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js",
        "https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4",
        "https://www.jqueryscript.net/demo/image-puzzle-slider-captcha/disk/longbow.slidercaptcha.js",
        // "https://www.google.com/recaptcha/enterprise.js?render=6LcMs_MsAAAAAI5zxkpRDeWOi61gG9QP8z4msiOV"
        // 'https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.20.1/cdn/shoelace.js'
    ];
    const cssFiles = [
        "https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css",
        "https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.20.1/cdn/themes/light.css",
        "https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-lite.min.css",
        "https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-lite.min.css",
         urlAmazonia+"/css/slidercaptcha.min.css",
    ];
    try {
        await Promise.all(jsFiles.map(addJs));
        await Promise.all(cssFiles.map(addCss));
        console.log("Todos os scripts e estilos foram carregados!");
	$('#bootstrap-css').remove();
	$('#bootstrap-min-css').remove();
        InitForm() ;
	$('#intro > div > div > h1').remove();
//	$('#intro > div > div > div > p').remove();

    } catch (error) {
        console.error("Erro ao carregar algum recurso:", error);
    }
});

function getUfCidadeData() {
    var jsonData = urlAmazonia + "/json/estados-cidades";
    $.getJSON(jsonData, function(data) {
        let selectEstadoRG = document.querySelector("select[name='input-rg_uf']");
        let selectEstado = document.querySelector("select[name='estado']");
        let selectStateNominee = document.querySelector("select[name='state']");
        let selectCidade = document.querySelector("select[name='cidade']");
        let option = document.createElement("option");
        option.setAttribute("value", "");
        option.innerText = "Escolha um Estado";
        var estados = [];

        $.each(data, function(key, val) {
            const option = document.createElement("option");
            option.setAttribute("value", val.sigla);
            option.innerText = val.nome;
            selectEstado.append(option);
        });
        $.each(data, function(key, val) {
            const option = document.createElement("option");
            option.setAttribute("value", val.sigla);
            option.innerText = val.nome;
            selectEstadoRG.append(option);
        });
        $.each(data, function(key, val) {
            const option = document.createElement("option");
            option.setAttribute("value", val.sigla);
            option.innerText = val.nome;
            selectStateNominee.append(option);
        });
        selectEstado.addEventListener('change', event => {
            $(selectCidade).empty();
            let value = event.target.value;
            $.each(data, function(key, val) {
                if (val.sigla == value) {
                    $.each(val.cidades, function(key_city, val_city) {
                        const option = document.createElement("option");
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
        var isSafari = Object.prototype.toString.call(window.HTMLElement).indexOf('Constructor') > 0 || (function(p) {
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
    $("#input-cep").on('change', (event) => {
        let val = event.target.value;
        let url = 'https://viacep.com.br/ws/' + val + '/json/';
        if (val.length == 8) {
            $.getJSON(url, function(data) {
                if (data.erro !== 'true') {
                    estado = data.uf.toUpperCase();
                    selectEstado = document.querySelector("select[name='estado']");
                    selectCidade = document.querySelector("select[name='cidade']");
                    selectEstado.value = estado;
                    const evento = new Event('change');
                    selectEstado.dispatchEvent(evento);

                    setTimeout(() => {
                        selectCidade.value = data.localidade;
                        console.log(data.localidade);
                    }, 1500);
                    // selectCidade.value = localidade;
                    $('#input-endereco').val(data.regiao + ' ' + data.bairro + ' ' + data.logradouro);
                }
            });
        }
    });
}

function formatarLocalidade(str) {
    return str.toLowerCase().split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join('_');
}

function prepareForm() {
    const inputData = document.querySelector('#input-dt_nascimento');
    const hoje = new Date();
    const dataLimite = new Date(hoje.getFullYear() - 18, hoje.getMonth(), hoje.getDate());
    const dataFormatada = dataLimite.toISOString().split('T')[0];
    inputData.setAttribute('max', dataFormatada);
    getUfCidadeData()
    $("#input-cpf").mask("999.999.999-99");
    $("#input-celular").mask("(99) 99999-999?9");
    $("#input-cpf").keyup(function(e) {
        var valorSemMascara = $('#input-cpf').val().replace(/\D/g, '');
        if(valorSemMascara.length == 11){
            if (!validarCPF(e.target.value)) {
                $('#input-cpf').val('');
                notify('CPF Inválido','danger');
            }else{
        let value = e.target.value;
        if (value.length == 14) {
            $.ajax({
                url: urlAmazonia + "/api/candidato/" + value, // Substitua pela URL correta
                type: "GET",
                dataType: "json",
                success: function(data) {
                    if (data.status === 'success') {
                        $('#candidate_id').val(data.candidate.id);
                        $('#input-email').val(data.candidate.email);
                        $('#nome').val(data.candidate.nome);
                        $('#input-rg').val(data.candidate.rg);
                        $('#input-rg_expeditor').val(data.candidate.rg_expeditor);

                        $('#input-celular').val('(' + data.candidate.ddd + ') ' + data.candidate.celular);
                        $('#input-cep').val(data.candidate.cep);
                        const select = document.querySelector('#input-estado'); // ou a sua variável selectEstado
                        select.value = data.candidate.ufendereco;
                        // Cria e dispara o evento de forma que o addEventListener consiga ouvir
                        const evento = new Event('change', {
                            bubbles: true
                        });
                        select.dispatchEvent(evento);
                        setTimeout(() => {
                            $('#input-cidade').val(data.candidate.cidade).trigger('change');
                        }, 1000)
                        $('#input-endereco').val(data.candidate.endereco).trigger('change');
                        $('#input-numero').val(data.candidate.numero).trigger('change');
                        $('#input-complemento').val(data.candidate.complemento).trigger('change');

                        $('#editor-resumo-curricular').summernote('code', data.candidate.resumo_curricular);
                        $('#input-instituicao').val(data.candidate.instituicao).trigger('change');
                        $('#input-instagram').val(data.candidate.instagram).trigger('change');
                        $('#input-facebook').val(data.candidate.facebook).trigger('change');
                        $('#input-outra_rede_social').val(data.candidate.outra_rede_social).trigger('change');
                        setDateField('#input-dt_nascimento', data.candidate.dt_nascimento);
                        const switchEl = document.querySelector('sl-switch');
                        switchEl.checked = data.candidate.whatsapp;

                        setTimeout(()=>{
                            $('#input-rg_uf').val(data.candidate.rg_uf);
                            $('#input-escolaridade').val(data.candidate.escolaridade);
                            $('#input-sexo').val(data.candidate.sexo);
                            $('#escolaridade').val(data.candidate.escolaridade).trigger('change');
                        },1000);


                    } else {
                        $('#input-email').val('');
                        $('#input-nome').val('');
                        $('#input-rg').val('');
                        $('#input-rg_expeditor').val('');
                        $('#input-rg_uf').val('');
                        $('#input-escolaridade').val('');
                        $('#input-sexo').val('');
                        $('#input-celular').val('');
                        setDateField('#input-dt_nascimento', '');
                    }
                },
                error: function(data) {
                    $('#tab-addres').attr('disabled', false);
                    $('#tab-social').attr('disabled', false);
                }
            });
        }
            }
        }
    });


    const tabGroup = document.querySelector('#tabsform');
    btnAnt = $('.btnAnt');
    btnPro = $('.btnPro');
    btnAnt.prop('disabled', true);
    console.log(tabGroup);
    tabGroup.addEventListener('sl-tab-show', (event) => {
            const tabName = event.detail.name;
            const tabs = Array.from(tabGroup.querySelectorAll('sl-tab'));
            const tabIndex = tabs.findIndex(tab => tab.getAttribute('panel') === tabName);
            switch (tabIndex) {
                case 0:
                    btnAnt.prop('disabled', true);
                    btnPro.prop('disabled', false);
                    break;

                case 1:
                case 2:
                case 3:
                    btnAnt.prop('disabled', false);
                    btnPro.prop('disabled', false);
                break;

                case 4:
                    btnAnt.prop('disabled', false);
                    btnPro.prop('disabled', true);
                break;

                default:
                    break;
            }
    });

    const btnVoltar = document.querySelector('#btn-voltar');
    const btnProximo = document.querySelector('#btn-proximo');
    btnVoltar.addEventListener('click', () => {
        const tabs = Array.from(tabGroup.querySelectorAll('sl-tab'));
        const indiceAtual = tabs.findIndex(tab => tab.hasAttribute('active'));
        const indiceAnterior = indiceAtual - 1;
        if (indiceAnterior >= 0) {
            const abaAnterior = tabs[indiceAnterior];
            const nomeDoPainel = abaAnterior.getAttribute('panel');
            tabGroup.show(nomeDoPainel);
        } else {
            console.log("Você já está na primeira aba!");
        }
    });

    btnProximo.addEventListener('click', () => {
        const tabs = Array.from(tabGroup.querySelectorAll('sl-tab'));
        const indiceAtual = tabs.findIndex(tab => tab.hasAttribute('active'));
        const indiceAnterior = indiceAtual + 1;
        if (indiceAnterior <= 4) {
            const abaAnterior = tabs[indiceAnterior];
            const nomeDoPainel = abaAnterior.getAttribute('panel');
            tabGroup.show(nomeDoPainel);
        }
    });

}

function showForm() {
    $.ajax({
        url: urlAmazonia + "/forms/edition", // Substitua pela URL correta
        type: "GET",
        dataType: "html",
        success: function(data) {
            $(".sistema").html(data);

            const urlParams = new URLSearchParams(window.location.search);
            const token = urlParams.get('token');
            if (token !== null) {
               $('#recaptchaInit').remove();
               $('.formRegistration ').removeClass('hidden');
               InitRegistrationsRequests();

            }else{
            checkCPFInicial();
            sliderCaptcha({
            id: 'captcha',
            repeatIcon: 'fa fa-redo',
            loadingText: 'Carregando...',
            failedText: 'Tente Novamente',
            barText: 'Mova a peça corretamente',
            onSuccess: function () {

                if($("#input-cpf").val().length <= 13){
                    notify('Informe o CPF','danger');
                    $('.refreshIcon').click();
                    return false;
                }


                var handler = setTimeout(function () {
                     value =  $("#input-cpf").val();
                     $.ajax({

                            url: urlAmazonia + "/api/candidato/" + value, // Substitua pela URL correta
                            type: "GET",
                            dataType: "json",
                            success: function(data) {
                                console.log(data);
                                if (data.status === 'success') {
                                    $('#recaptchaInit').remove();
                                    if(data.candidatures.length){
                                        $(".chooseAction").removeClass('hidden');
                                        $("#btnNovaInscrição").bind('click',()=>{
                                            $(".chooseAction").remove();
                                            $(".formRegistration").removeClass('hidden');
                                            gotoForm(value);
                                        });

                                        $("#btnNovaInscrição").bind('click',()=>{
                                            $(".chooseAction").remove();
                                            $(".formRegistration").removeClass('hidden');
                                            gotoForm(value);
                                        });

                                        $("#btnEditarInscrição").bind('click',()=>{
                                            console.log(data);
                                            $(".chooseAction").remove();
                                            $(".formRegistration").removeClass('hidden');
                                        initDrawerRegistrations(data.candidatures);
                                        });
                                    }else{
                                          $(".chooseAction").removeClass('hidden');
                                            $(".chooseAction").remove();
                                            $(".formRegistration").removeClass('hidden');
                                            gotoForm(value);
                                    }







                                }
                            },
                            error: function(data) {
                                $('#recaptchaInit').remove();
                                $(".formRegistration").removeClass('hidden');
                                gotoForm(value);
                            }
                        });


                }, 500);
            }
        });
            }


        },
        error: function(xhr, status, error) {
            console.error("Erro ao carregar conteúdo:", error);
            $(".sistema").html("<p>Erro ao carregar o formulário.</p>", );
        },
    });



}

function checkCPFInicial(){
     $("#input-cpf").mask("999.999.999-99");
    $("#input-cpf").keyup(function(e) {
        var valorSemMascara = $('#input-cpf').val().replace(/\D/g, '');
        if(valorSemMascara.length == 11){
            if (!validarCPF(e.target.value)) {
                $('#input-cpf').val('');
                notify('CPF Inválido','danger');
            }
        }
    });
}

function checkhasRegistrationActive() {
    $.ajax({
        url: urlAmazonia + "/api/has-registrations", // Substitua pela URL correta
        type: "GET",
        dataType: "json",
        success: function(res) {
            hasRegistration = res;
            showForm();
        },
        error: function(xhr, status, error) {
            // window.location = "https://amazonia.ibict.br/insc-enceradas/";
        },
    });
}

function setFormValidation() {


    $('form input,select').on('change', () => {
        const forms = document.querySelector('.needs-validation');
        let regulamento = $('input[name="regulamento"]').is(':checked');
        if (!forms.checkValidity() || !regulamento) {
            $('.btn-success').prop('disabled', true);
            console.log(verificarErros());
        } else {
            $('.btn-success').prop('disabled', false);
        }
    });
    const forms = document.querySelectorAll('.needs-validation')
    // Loop over them and prevent submission
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            event.preventDefault();
            event.stopPropagation();
            sendPost();
        }, false)
    })
}

function sendPost() {
    const form = document.querySelector('form');
    json = JSON.parse(formParaJSON(form));
    prepareJson = {};

    if($('.notHonorific').hasClass('hidden')){
        if($('#nome').val() == $("#input-name").val()){
            notify('Nome do(a) Indicado(a) tem que ser diferente do nome Pessoal ( seu nome )','danger',90000000000000);
            $("#input-name").val('');
            $('#nome').val('');
            return false;
        }
    }

      const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');
    if (token == null) {
        prepareJson.candidate_id = json.candidate_id;
        prepareJson.edition = json.edition;
        prepareJson.nome = json.nome;
        prepareJson.cpf = json['input-cpf'];
        prepareJson.dt_nascimento = json.dt_nascimento;
        prepareJson.rg = json.rg;
        prepareJson.rg_expeditor = json.rg_expeditor;
        prepareJson.rg_uf = json['input-rg_uf'];
        prepareJson.sexo = json.sexo;
        prepareJson.cep = json.inputcep;
        prepareJson.ufendereco = json.estado;
        prepareJson.cidade = json.cidade;
        prepareJson.endereco = json.endereco;
        prepareJson.numero = json.numero;
        prepareJson.complemento = json.complemento;
        prepareJson.ddd = json.celular.slice(1, 3);
        prepareJson.celular = json.celular.slice(5, 15);
        const el = document.querySelector('sl-switch');
        prepareJson.whatsapp = el.checked;
        prepareJson.email = json['input-email'];
        prepareJson.instituicao = json.instituicao;
        prepareJson.escolaridade = json.escolaridade;
        prepareJson.instagram = json.instagram;
        prepareJson.facebook = json.facebook;
        prepareJson.outra_rede_social = json.outra_rede_social;
        prepareJson.resumo_curricular = json['resumo_curricular'];
    }

    prepareJson.category_id = json.category;
    prepareJson.title = json.titulo;
    prepareJson.coautores = json.coautores;
    prepareJson.resumo = json['input-resumo'];
    prepareJson.desenvolvimento = json['input-desenvolvimento'];
    prepareJson.objetivo = json['input-objetivo'];
    prepareJson.conclusao = json['input-conclusao'];
    prepareJson.name = json['name_nominee'];
    prepareJson.state = json['state'];
    prepareJson.contact_data = json['contact-data'];
    prepareJson.presentation = json['input-presentation'];
    prepareJson.activities = json['input-activities'];
    prepareJson.justification = json['input-justification'];
    let formData = new FormData(form);
    console.log(formData);
    if (json.file1) {
        formData.append('files1', json.file1);
    }
    if (json.file2) {
        formData.append('files2', json.file2);
    }
    if (json.file3) {
        formData.append('files3', json.file3);
    }
    formData.append('data', JSON.stringify(prepareJson));
    $('.btn-success').prop('disabled', true);


    if(token  === null){
        sendPonstAjax(formData);
    }else{
        sendUpdateAjax(formData);
    }



}

function sendUpdateAjax(formData){
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');
    $.ajax({
        url: urlAmazonia + '/api/verifyToken/' + token,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false, // ✅ CORRETO - Deixe o jQuery definir automaticamente
        cache: false, // ✅ Adicione esta linha
        success: function(response) {

            if(response.status == 'edit'){
                editProtocol(response.candidature);
            }else{
                notify(response.message, 'success');
            }

        },
        error: function(resp) {
            notify(response.message, '<danger></danger>');
        }
    });
}

function sendPonstAjax(formData){
  $.ajax({
        url: urlAmazonia + "/api/registration",
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false, // ✅ CORRETO - Deixe o jQuery definir automaticamente
        cache: false, // ✅ Adicione esta linha
        success: function(response) {
            protocolo = response.data.registration.protocol;
            $('form').trigger("reset");
            resetEditores();
            $("sl-tab[panel='personal']").click();

            $('#formRegistration').remove();
            $('#confirmationRegistration').removeClass('hidden');
            console.log(response);

            $('#show_protocol').html(response.data.registration.protocol);
            $('#show_name').html(response.data.candidate.nome);
            $('#show_category').html(response.data.registration.category.title);

            if(response.data.registration.category.is_honorific){
                $('.show_title').remove();
                $('#show_indicate').html(response.data.registration.name);
            }else{
                $('.show_indicate').remove();
                $('#show_title').html(response.data.registration.title);
            }


            $('#show_title').html(response.data.registration.title);
            $('#show_title').html(response.data.registration.title);
            let a = new Date(response.data.registration.created_at);
            dataFormatada = a.toLocaleDateString('pt-BR');
            $('#show_date').html(dataFormatada);

             a = new Date(response.data.registration.category.modality.edition.judgment_date);
             dataFormatada = a.toLocaleDateString('pt-BR');
            $('#show_date_judge').html(dataFormatada);

            a = new Date(response.data.registration.category.modality.edition.grant_date);
            dataFormatada = a.toLocaleDateString('pt-BR');
            $('#show_date_out').html(dataFormatada);


            // notify('Inscrição Realizada! Verifique seu Email!', 'success', 'info-circle', 90000000);
            // notify('Protocolo:' + protocolo, 'info', 'info-circle', 90000000);
            $('.btn-success').prop('disabled', false);
        },
        error: function(resp) {
            $('.btn-success').prop('disabled', false);
            console.log(resp);
            Object.entries(resp.responseJSON.erros).forEach(([campo, mensajes]) => {
                // Como 'mensajes' es un array, recorremos cada mensaje
                mensajes.forEach(mensaje => {
                    notify(`Erro em ${campo}: ${mensaje}`, 'danger', 'info-circle', 90000000);
                });
            });
        }
    });
}

function resetEditores() {
    $('.input-conclusao').summernote('reset');
    $('.input-desenvolvimento').summernote('reset');
    $('.editor-resumo').summernote('reset');
    $('.input-resumo').summernote('reset');
    $('.input-objetivo').summernote('reset');
    $('.input-presentation').summernote('reset');
    $('.input-activities').summernote('reset');
    $('.input-justification').summernote('reset');
}

function formParaJSON(formSelector) {
    const obj = {};
    const form = $(formSelector);
    // Captura inputs nativos e do Shoelace
    form.find('input, select, sl-checkbox').each(function() {
        const name = $(this).attr('name');
        const val = $(this).val();
        if (!name) return;
        // Se o nome já existe (ex: múltiplos checkboxes com mesmo nome), cria um array
        if (obj[name]) {
            if (!Array.isArray(obj[name])) {
                obj[name] = [obj[name]];
            }
            obj[name].push(val);
        } else {
            obj[name] = val;
        }
    });
    return JSON.stringify(obj);
}

function verificarErros() {
    const erros = [];
    const form = document.querySelector('.needs-validation');
    // Transformamos os elementos em array para usar o forEach
    Array.from(form.elements).forEach(campo => {
        if (!campo.checkValidity()) {
            // Pegamos o nome do campo (ou id) e a mensagem de erro que o browser gerou
            erros.push({
                campo: campo.name || campo.id,
                mensagem: campo.validationMessage
            });
        }
    });
    return erros;
}

function loadSelects(resp = []) {
    $('.notHonorific input, .notHonorific select, .notHonorific textarea').prop('disabled', true);
    $('.Honorific input, .Honorific select, .Honorific textarea').prop('disabled', true);
    const selectCategory = document.querySelector("select[name='category']");
    const option = document.createElement("option");
    option.setAttribute("value", "");
    option.innerText = "Escolha uma Categoria";
    selectCategory.append(option);
    $.each(resp.data.modalities, function(key, modality) {
        $.each(modality.categories, function(kkey, category) {
            const option = document.createElement("option");
            option.setAttribute("value", category.id);
            option.innerText = category.title;
            selectCategory.append(option);
        });
    });
    selectCategory.addEventListener('change', event => {
        let value = event.target.value;
        let categorySel = {};
        $.each(resp.data.modalities, function(key, modality) {
            $.each(modality.categories, function(kkey, category) {
                if (category.id === value) {
                    categorySel = category;
                }
            });
        });
        if (categorySel.is_honorific) {
            $('.notHonorific').addClass('hidden');
            $('.notHonorific input, .notHonorific select, .notHonorific textarea').prop('disabled', true);
            $('.Honorific').removeClass('hidden');
            $('.Honorific input, .Honorific select, .Honorific textarea').prop('disabled', false);
        } else {
            $('.notHonorific').removeClass('hidden');
            $('.notHonorific input, .notHonorific select, .notHonorific textarea').prop('disabled', false);
            $('.Honorific').addClass('hidden');
            $('.Honorific input, .Honorific select, .Honorific textarea').prop('disabled', true);
        }
        $('.kt-input-text').summernote({
            placeholder: '',
            tabsize: 2,
            height: 120,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                // ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen']]
            ]
        });
    });
    $('.notHonorific').addClass('hidden');
    $('.Honorific').addClass('hidden');
}
// Exemplo de função que só deve rodar depois que tudo estiver pronto
async function InitForm() {
    checkhasRegistrationActive();
}

function setDateField(selector, dateString) {
    // Verifica se a string parece válida
    if (!dateString) {
        console.warn("Data vazia ou inválida fornecida.");
        return;
    }
    // Tenta criar um objeto Date a partir da string ISO
    const date = new Date(dateString);
    // Valida se a data é válida
    if (isNaN(date.getTime())) {
        console.error("Falha ao interpretar a data:", dateString);
        return;
    }
    // Formata como YYYY-MM-DD
    const formattedDate = date.toISOString().split('T')[0];
    // Define o valor no campo
    $(selector).val(formattedDate);
}

function escapeHtml(html) {
    const div = document.createElement('div');
    div.textContent = html;
    return div.innerHTML;
}
// Custom function to emit toast notifications
function notify(message, variant = 'danger', icon = 'info-circle', duration = 9999999999999) {
    const alert = Object.assign(document.createElement('sl-alert'), {
        variant,
        closable: true,
        duration: duration,
        innerHTML: `
        <sl-icon name="${icon}" slot="icon"></sl-icon>
        ${escapeHtml(message)}
      `
    });
    document.body.append(alert);
    return alert.toast();
}

function validarCPF(cpf) {
    cpf = cpf.replace(/[^\d]+/g, ''); // Remove máscara (pontos e traço)
    if (cpf == '' || cpf.length != 11 || !!cpf.match(/(\d)\1{10}/)) return false;
    let add = 0;
    for (let i = 0; i < 9; i++) add += parseInt(cpf.charAt(i)) * (10 - i);
    let rev = 11 - (add % 11);
    if (rev == 10 || rev == 11) rev = 0;
    if (rev != parseInt(cpf.charAt(9))) return false;
    add = 0;
    for (let i = 0; i < 10; i++) add += parseInt(cpf.charAt(i)) * (11 - i);
    rev = 11 - (add % 11);
    if (rev == 10 || rev == 11) rev = 0;
    if (rev != parseInt(cpf.charAt(10))) return false;
    return true;
}

function InitRegistrationsRequests() {
    console.log('InitRegistrationsRequests');
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');
    if (token !== null) {
        $.ajax({
            url: urlAmazonia + '/api/verifyToken/' + token,
            type: 'GET',
            success: function(response) {
                if(response !== undefined){
                    if(response.status == 'edit'){
                        editProtocol(response.candidature);
                        setFormValidation();
                    }else{
                        $('.formRegistration').addClass('hidden');
                        notify(response.message, 'success');
                    }
                }else{
                    const urlSemParametros = window.location.origin + window.location.pathname;
                    window.location = urlSemParametros;
                }


            },
            error: function(resp) {
                notify(response.message, '<danger></danger>');
            }
        });
    }
}

function initDrawerRegistrations(candidaturas) {
    if (candidaturas.length) {
        $(".alert-toast  ").remove();
        $(".confirmRegulamento").remove();
        $(".footerPage").remove();
        $('#registrations_tab').removeClass('hidden');
        $('#registrations_tab').click();
        $('#tab-personal').addClass('hidden');
        $('#tab-addres').addClass('hidden');
        $('#tab-social').addClass('hidden');
        $('#tab-register').addClass('hidden');
        $('#tab-documents').addClass('hidden');


        popularLista(candidaturas);
    }
}

function popularLista(listaDeItens = []) {
    const container = document.querySelector('#inscricoesDiv');
    // Mapeia o array para HTML e junta tudo em uma string
    container.innerHTML = listaDeItens.map(item => `
    <div class="btn-group mb-4 w-full" role="group" >
    <button type="button" class="btn btn-primary">Titulo: ${item['title'] || item['name']}  &nbsp;&nbsp;&nbsp; Protocolo: ${item['protocol']}</button>
    <button type="button" class="btn btn-primary">Titulo: ${item['title'] || item['name']}  &nbsp;&nbsp;&nbsp; Protocolo: ${item['protocol']}</button>
    <button type="button" onClick="sendRequestprotocol('${item['protocol']}','edit');"  class="btn btn-info">Editar</button>
    <button type="button" onClick="sendRequestprotocol('${item['protocol']}','delete');" class="btn btn-danger">Excluir</button>

    </div>
  `).join('');
}

function sendRequestprotocol(protocol,action) {
    $.ajax({
            url: urlAmazonia + '/api/requestToken/'+protocol+'/'+action,
            type: 'GET',
            success: function(response) {
                notify(response.message, 'success');
            },
            error: function(resp) {
               notify(response.message, 'danger');
            }
        });
}

function editProtocol(item){
    $('.btn-success').text('Alterar Inscrição');
    $("sl-tab[panel='personal']").remove();
    $("sl-tab-panel[name='personal']").remove();

    $("sl-tab[panel='address']").remove();
    $("sl-tab-panel[name='address']").remove();

    $("sl-tab[panel='social']").remove();
    $("sl-tab-panel[name='social']").remove();

    $("sl-tab[panel='registrations']").remove();
    $("sl-tab-panel[name='registrations']").remove();

    $(".categoryDiv").remove();
    $("sl-tab[panel='register']").click();
    $(' input, select, textarea').prop('disabled', false);
    if(item?.conclusao){
        $('.notHonorific').removeClass('hidden');
         $('.Honorific').remove();
        $('#input-title').val(item.title);
        $('#input-coautores').val(item.coautores);

        $('#editor-resumo').summernote('code', item.resumo);
        $('#editor-resumo').trigger('change');

        $('#editor-desenvolvimento').summernote('code', item.desenvolvimento);
        $('#editor-desenvolvimento').trigger('change');

        $('#editor-objetivo').summernote('code', item.objetivo);
        $('#editor-objetivo').trigger('change');

        $('#editor-conclusao').summernote('code', item.conclusao);
        $('#editor-conclusao').trigger('change');

    }else{
        $('.Honorific').removeClass('hidden');
        $('.notHonorific').remove();


        $('#input-name').val(item.name);
        $('#input-state').val(item.state);
        $('#input-contact_data').val(item.contact_data  );

        $('#editor-resumo').summernote('code', item.resumo);
        $('#editor-resumo').trigger('change');

        $('#editor-presentation').summernote('code', item.presentation);
        $('#editor-presentation').trigger('change');

        $('#editor-activities').summernote('code', item.activities);
        $('#editor-activities').trigger('change');

         $('#editor-justification').summernote('code', item.justification);
        $('#editor-justification').trigger('change');
    }



}


function gotoForm(cpf){
    setTimeout(() => {
        loadSelects(hasRegistration);
        setFormValidation();
        $('#edition').attr('value', hasRegistration.data.id);
        $('#linkRegulamento').attr('href', hasRegistration.data.regulation_file_path);



    }, 2000);
    prepareForm();
    $("#input-cpf").val(value).trigger('keyup');
}

 $('#captcha').sliderCaptcha({
    repeatIcon: 'fa fa-redo',
    onSuccess: function () {

         notify('Informe o CPF','danger');
        //   notify('Informe o CPF','danger');
        // if($("#input-cpf").val().length >= 14){
        //     gotoForm();
        // }else{

        // }

    }
});


function mascaraLetrasComAcentos(input) {
    // Permite a-z, A-Z, espaços (\s) e todos os caracteres acentuados da língua portuguesa
    input.value = input.value.replace(/[^a-zA-ZáàâãéèêíïóôõöúçñÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇÑ\s]/g, "");
}

function mascaraApenasNumeros(input) {
    // Remove absolutamente tudo o que NÃO for número
    input.value = input.value.replace(/\D/g, "");
}

function mascaraLetrasSemAcento(input) {
    // Remove tudo o que NÃO for letras de A a Z (maiúsculas/minúsculas) ou espaços
    input.value = input.value.replace(/[^a-zA-Z\s]/g, "");
}


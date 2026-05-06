let urlAmazonia = 'https://sysamazonia.test';

function addCss(url) {
    return new Promise((resolve, reject) => {
        const link = document.createElement("link");
        link.rel = "stylesheet";
        link.type = "text/css";
        link.href = url;
        link.onload = () => resolve();
        link.onerror = () => reject(new Error(`Failed to load CSS: ${url}`));
        document.head.appendChild(link);
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
    const jsFiles = ["https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-lite.min.js", "https://sysamazonia.test:5173/resources/comp_themes/ktui/ktui.min.js", "https://sysamazonia.test:5173/public/js/jquery.maskedinput.min.js", "https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js", "https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4",
        // 'https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.20.1/cdn/shoelace.js'
    ];
    const cssFiles = ["https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css", "https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.20.1/cdn/themes/light.css", "https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-lite.min.css", ];
    try {
        await Promise.all(jsFiles.map(addJs));
        await Promise.all(cssFiles.map(addCss));
        console.log("Todos os scripts e estilos foram carregados!");
        InitForm() ;



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
                    const selectEstado = document.querySelector("sl-select[name='estado']");
                    const selectCidade = document.querySelector("sl-select[name='cidade']");
                    selectEstado.value = data.uf.toUpperCase();
                    const evento = new CustomEvent('sl-change', {
                        bubbles: true,
                        cancelable: true
                    });
                    selectEstado.dispatchEvent(evento);
                    let localidade = formatarLocalidade(data.localidade);
                    setTimeout(() => {
                        selectCidade.value = localidade;
                    }, 1000);
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
    $("#input-cpf").change(function(e) {
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
                        $('#input-rg_uf').val(data.candidate.rg_uf);
                        $('#input-escolaridade').val(data.candidate.escolaridade);
                        $('#input-sexo').val(data.candidate.sexo);
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
                        $('#escolaridade').val(data.candidate.escolaridade).trigger('change');
                        $('#editor-resumo-curricular').summernote('code', data.candidate.resumo_curricular);
                        $('#input-instituicao').val(data.candidate.instituicao).trigger('change');
                        $('#input-instagram').val(data.candidate.instagram).trigger('change');
                        $('#input-facebook').val(data.candidate.facebook).trigger('change');
                        $('#input-outra_rede_social').val(data.candidate.outra_rede_social).trigger('change');
                        setDateField('#input-dt_nascimento', data.candidate.dt_nascimento);
                        const switchEl = document.querySelector('sl-switch');
                        switchEl.checked = data.candidate.whatsapp;
                        initDrawerRegistrations(data.candidatures);
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
    });
}

function showForm() {
    $.ajax({
        url: urlAmazonia + "/forms/edition", // Substitua pela URL correta
        type: "GET",
        dataType: "html",
        success: function(data) {
            $("#formularioRegistroContainer").html(data);
            prepareForm();
        },
        error: function(xhr, status, error) {
            console.error("Erro ao carregar conteúdo:", error);
            $("#formularioRegistroContainer").html("<p>Erro ao carregar o formulário.</p>", );
        },
    });
}

function checkhasRegistrationActive() {
    $.ajax({
        url: urlAmazonia + "/api/has-registrations", // Substitua pela URL correta
        type: "GET",
        dataType: "json",
        success: function(res) {
            showForm();
            setTimeout(() => {
                loadSelects(res);
                setFormValidation();
                $('#edition').attr('value', res.data.id);
                $('#linkRegulamento').attr('href', res.data.regulation_file_path);
                InitRegistrationsRequests();
            }, 2000);;
        },
        error: function(xhr, status, error) {
            window.location = "https://amazonia.ibict.br/insc-enceradas/";
        },
    });
}

function setFormValidation() {
    $('#input-cpf').change((e) => {
        if (!validarCPF(e.target.value)) {
            $('#input-cpf').val('');
        }
    })
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
            notify('Inscrição Realizada! Verifique seu Email!', 'success', 'info-circle', 90000000);
            notify('Protocolo:' + protocolo, 'info', 'info-circle', 90000000);
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
function notify(message, variant = 'danger', icon = 'info-circle', duration = 3000) {
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
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');
    if (token !== null) {
        $.ajax({
            url: urlAmazonia + '/api/verifyToken/' + token,
            type: 'GET',
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
}

function initDrawerRegistrations(candidaturas) {
    if (candidaturas.length) {
        $('#registrations_tab').removeClass('hidden');
        popularLista(candidaturas);
    }
}

function popularLista(listaDeItens = []) {
    const container = document.querySelector('#inscricoesDiv');
    // Mapeia o array para HTML e junta tudo em uma string
    container.innerHTML = listaDeItens.map(item => `
    <div class="btn-group mb-4" role="group" aria-label="Button group with nested dropdown">
    <button type="button" class="btn btn-primary">Titulo: ${item['title'] || item['name']}  &nbsp;&nbsp;&nbsp; Protocolo: ${item['protocol']}</button>
    <div class="btn-group dropend" role="group">
        <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
        Ações
        </button>
        <ul class="dropdown-menu">
        <li><a class="dropdown-item" onClick="sendRequestprotocol('${item['protocol']}','edit');" >Editar</a></li>
        <li><a class="dropdown-item" onClick="sendRequestprotocol('${item['protocol']}','delete');"  >Excluir</a></li>
        </ul>
    </div>
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

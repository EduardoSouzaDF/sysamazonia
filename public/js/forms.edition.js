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

$(document).ready(async function () {
    const jsFiles = [
        "https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-lite.min.js",
        "https://sysamazonia.test:5173/resources/comp_themes/ktui/ktui.min.js",
        "https://sysamazonia.test:5173/public/js/jquery.maskedinput.min.js",
        "https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js",
        "https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4",
        // 'https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.20.1/cdn/shoelace.js'
    ];

    const cssFiles = [
        "https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css",
        "https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.20.1/cdn/themes/light.css",
        "https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-lite.min.css",
    ];

    try {
        await Promise.all(jsFiles.map(addJs));
        await Promise.all(cssFiles.map(addCss));
        console.log("Todos os scripts e estilos foram carregados!");
        InitForm(); // exemplo fictício
    } catch (error) {
        console.error("Erro ao carregar algum recurso:", error);
    }
});

function getUfCidadeData(){
     var jsonData = "https://sysamazonia.test/json/estados-cidades";
    $.getJSON(jsonData, function(data) {
        let selectEstado = document.querySelector("sl-select[name='estado']");
        let selectStateNominee = document.querySelector("sl-select[name='state']");
        let selectCidade = document.querySelector("sl-select[name='cidade']");
        let option = document.createElement("sl-option");
        option.setAttribute("value", "");
        option.innerText = "Escolha um Estado";

        var estados = [];
        var options = '<sl-option value="">escolha um estado</sl-option>';

        selectEstado.append(option);
        selectStateNominee.append(option);

        $.each(data, function(key, val) {

            const option = document.createElement("sl-option");
            option.setAttribute("value", val.sigla);
            option.innerText = val.nome;
            selectEstado.append(option);
            // selectStateNominee.append(option);
        });

         $.each(data, function(key, val) {

            const option = document.createElement("sl-option");
            option.setAttribute("value", val.sigla);
            option.innerText = val.nome;
            // selectEstado.append(option);
            selectStateNominee.append(option);
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

     $("#input-cep").on('change', (event) => {
                let val = event.target.value;
                let url = 'https://viacep.com.br/ws/' + val + '/json/';
                if (val.length == 8) {
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
                            setTimeout(()=>{
                                 selectCidade.value = localidade;
                            },1000);
                            // selectCidade.value = localidade;
                            $('#input-endereco').val(data.regiao +' '+data.bairro +' '+data.logradouro);

                        }

                    });
                }
    });
}
function formatarLocalidade(str) {
            return str
                .toLowerCase()
                .split(' ')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join('_');
        }

function prepareForm() {
    getUfCidadeData()
    $("#input-cpf").mask("999.999.999-99");
    $("#input-celular").mask("(99) 99999-999?9");

    $("#input-cpf").change(function (e) {
        let value = e.target.value;
        if (value.length == 14) {
            $.ajax({
                url: "https://sysamazonia.test/api/candidato/"+value, // Substitua pela URL correta
                type: "GET",
                dataType: "json",
                success: function (data) {
                  if(data.status === 'success'){
                    $('#input-email').val(data.candidate.email);
                    $('#input-nome').val(data.candidate.nome);
                    $('#input-rg').val(data.candidate.rg);
                    $('#input-rg_expeditor').val(data.candidate.rg_expeditor);
                    $('#input-rg_uf').val(data.candidate.rg_uf);
                    $('#input-escolaridade').val(data.candidate.escolaridade);
                    $('#input-sexo').val(data.candidate.sexo);
                    $('#input-celular').val('('+data.candidate.ddd+') '+data.candidate.celular);
                    setDateField('#input-dt_nascimento', data.candidate.dt_nascimento);
                    const switchEl = document.querySelector('sl-switch');
                    switchEl.checked = data.candidate.whatsapp;
                    $('#tab-addres').attr('disabled', true);
                    $('#tab-social').attr('disabled', true);
                  }else{

                     $('#input-email').val('');
                    $('#input-nome').val('');
                    $('#input-rg').val('');
                    $('#input-rg_expeditor').val('');
                    $('#input-rg_uf').val('');
                    $('#input-escolaridade').val('');
                    $('#input-sexo').val('');
                    $('#input-celular').val('');
                    setDateField('#input-dt_nascimento', '');

                     $('#tab-addres').attr('disabled', false);
                    $('#tab-social').attr('disabled', false);
                  }
                },
                error:function(data){
                    $('#tab-addres').attr('disabled', false);
                    $('#tab-social').attr('disabled', false);
                }

            });
        }
    });
}


function showForm() {
    $.ajax({
        url: "https://sysamazonia.test/forms/edition", // Substitua pela URL correta
        type: "GET",
        dataType: "html",
        success: function (data) {
            $("#formularioRegistroContainer").html(data);
            console.log("Conteúdo carregado com sucesso!");
            prepareForm();
        },
        error: function (xhr, status, error) {
            console.error("Erro ao carregar conteúdo:", error);
            $("#formularioRegistroContainer").html(
                "<p>Erro ao carregar o formulário.</p>",
            );
        },
    });
}

function checkhasRegistrationActive() {
    $.ajax({
        url: "https://sysamazonia.test/api/has-registrations", // Substitua pela URL correta
        type: "GET",
        dataType: "json",
        success: function (data) {
            console.log("Há Edição ativa");
            showForm();
            setTimeout(()=>{
                loadSelects(data);
                setFormValidation();
            },2000);


        },
        error: function (xhr, status, error) {
            window.location = "https://amazonia.ibict.br/insc-enceradas/";
        },
    });
}



function setFormValidation(){
$('form input,select').on('change',()=>{
    const forms = document.querySelector('.needs-validation');

    if(!forms.checkValidity()){
        $('.btn-success').prop('disabled', true);
        console.log(verificarErros());
    }else{
        $('.btn-success').prop('disabled', false);
    }
});

const forms = document.querySelectorAll('.needs-validation')

  // Loop over them and prevent submission
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', event => {
        event.preventDefault();
        event.stopPropagation();
        // form.checkValidity();
         console.log(form.checkValidity());


    }, false)
  })

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

function loadSelects(resp= []){
    $('.notHonorific input, .notHonorific select, .notHonorific textarea').prop('disabled', true);
    $('.Honorific input, .Honorific select, .Honorific textarea').prop('disabled', true);
    const selectCategory = document.querySelector("sl-select[name='category']");
    const option = document.createElement("sl-option");
    option.setAttribute("value", "");
    option.innerText = "Escolha uma Categoria";
    selectCategory.append(option);
    $.each(resp.data.modalities, function(key, modality) {
        $.each(modality.categories,function(kkey,category){
            const option = document.createElement("sl-option");
            option.setAttribute("value", category.id);
            option.innerText = category.title;
            selectCategory.append(option);
        });
    });


    selectCategory.addEventListener('sl-change', event => {
                let value = event.target.value;
                let categorySel= {};

               $.each(resp.data.modalities, function(key, modality) {
                $.each(modality.categories,function(kkey,category){
                   if( category.id === value){
                    categorySel =  category;
                   }
                });
            });

            if(categorySel.is_honorific){
                 $('.notHonorific').addClass('hidden');
                 $('.notHonorific input, .notHonorific select, .notHonorific textarea').prop('disabled', true);

                 $('.Honorific').removeClass('hidden');
                $('.Honorific input, .Honorific select, .Honorific textarea').prop('disabled', false);

            }else{
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
function InitForm() {
    this.checkhasRegistrationActive();
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

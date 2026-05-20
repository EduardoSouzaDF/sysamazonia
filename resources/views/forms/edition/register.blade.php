    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.1/css/all.css" integrity="sha384-50oBUHEmvpQ+1lW4y57PTFmhCaXp0ML5d60M1M7uH2+nqUivzIebhndOJK28anvf" crossorigin="anonymous">
<style>
sl-tab[active]::part(base) {
    background-color: #0284c7; /* Altere para a sua cor desejada */
    color: white;            /* Altere a cor do texto para dar contraste, se necessário */
}

sl-alert::part(base) {
  width: 80vw;       /* Altera a largura (padrão costuma ser menor) */
  font-size: 1.2rem;   /* Aumenta o tamanho do texto interno */
  padding: 1rem;       /* Aumenta o espaçamento interno */
}

/* Força o container de notificações a ficar no topo e à frente da navbar */
.sl-toast-stack {
     width: 80vw;       /* Alt
  top: 10px !important;       /* Distância do topo da tela */
  right: 20px !important;     /* Distância do canto direito */
  bottom: auto !important;    /* Garante que não puxe para baixo */

  /* Um valor maior que o 1030 da .navbar-fixed-top do Bootstrap */
  z-index: 99999 !important;
}

.page-content {
    background-color: unset !important;
    padding: 0px !important;
}


.slidercaptcha {
            margin: 0 auto;
            width: 314px;
            height: 286px;
            border-radius: 4px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.125);
            margin-top: 40px;
        }

            .slidercaptcha .card-body {
                padding: 1rem;
            }

            .slidercaptcha canvas:first-child {
                border-radius: 4px;
                border: 1px solid #e6e8eb;
            }

            .slidercaptcha.card .card-header {
                background-image: none;
                background-color: rgba(0, 0, 0, 0.03);
            }

            .refreshIcon {
                top: -54px;
            }
</style>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<form enctype="multipart/form-data" id="formRegistration" enctype="multipart/form-data" class="needs-validation" >
<div class="" id="recaptchaInit">

    <div class=" ">

    <div class="grid grid-cols-1    ">
            <div>
                <input type="hidden" name="edition" id="edition" value=""  >
                <input type="hidden" name="candidate_id" id="candidate_id" value=""  >
                <label for="input-cpf" class="form-label">CPF (*)</label>
                <input type="text" class="form-control" name="input-cpf" value="" id="input-cpf" required  >
                <div class="form-text mb-4">
                    Somente será aceito CPF Válido !.
                </div>

            </div>

            <div class="form-row mt-[-50px]">
                    <div class="col-12">
                        <div class="slidercaptcha card">
                            <div class="card-header">
                                <span>Mova para completar</span>
                            </div>
                            <div class="card-body"><div id="captcha"></div></div>
                        </div>
                    </div>
            </div>
    </div>
    </div>
</div>

<div class=" w-full p-4 flex justify-between gap-x-2 chooseAction hidden">
    <button type="button" id="btnEditarInscrição"  class="btn w-full  btn-primary">Editar Inscrições Anteriores</button>
    <button type="button" id="btnNovaInscrição"  class="btn w-full  btn-success">Fazer nova inscrição</button>
</div>
<sl-card class="card-footer w-full formRegistration min-w-full hidden" id="">

    <sl-tab-group noScrollControls="true" >
      <!-- Dados Pessoais -->
      <sl-tab slot="nav" id="tab-personal" panel="personal">Dados Pessoais</sl-tab>

      <!-- Categoria -->
      <sl-tab slot="nav" id="tab-addres"  panel="address">Endereço</sl-tab>

      <sl-tab slot="nav" id="tab-social" panel="social">Outros Dados</sl-tab>

      <!-- Inscrição -->
      <sl-tab slot="nav"  id="tab-register" panel="register">Inscrição</sl-tab>
      <sl-tab slot="nav" id="tab-documents" panel="documents">Documentos</sl-tab>

      <sl-tab slot="nav" panel="registrations" id="registrations_tab" class="hidden">Inscrições Realizadas</sl-tab>
      <div class="alert-toast  ">
        <sl-alert  variant="primary" open   closable class="mt-2"  >
            <sl-icon slot="icon" name="exclamation-octagon"></sl-icon>
            Os campos com asterisco (*) são obrigatórios !
        </sl-alert>
      </div>



      <sl-tab-panel name="personal" class="min-h-max">
          @include('forms.edition.personal-tab')
      </sl-tab-panel>
      <sl-tab-panel name="address">
         @include('forms.edition.address-tab')
      </sl-tab-panel>

      <sl-tab-panel name="social">
        @include('forms.edition.social-tab')
      </sl-tab-panel>

      <!-- Painel Inscrição -->
      <sl-tab-panel name="register">
         @include('forms.edition.registration-tab')
      </sl-tab-panel>

      <!-- Painel Documentos -->
      <sl-tab-panel name="documents">
         @include('forms.edition.documents-tab')

      </sl-tab-panel>

      <sl-tab-panel name="registrations">
         @include('forms.edition.registrations-tab')

      </sl-tab-panel>


      <div class="confirmRegulamento">
<input type="checkbox" name="regulamento" id="confirmRegulamento" > Confirmo que li o <a href="" style="text-decoration-color: red; color:red" target="__blank" id="linkRegulamento">Regulamento</a> e aceito seus termos.
      </div>



    </sl-tab-group>

    <div slot="footer" class="flex justify-between gap-x-2 footerPage">
        <button type="submit" disabled class="btn w-full  btn-success">Enviar inscrição</button>
    </div>
  </sl-card>


</form>



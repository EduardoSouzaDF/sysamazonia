
<form enctype="multipart/form-data" id="formRegistration" enctype="multipart/form-data" class="needs-validation" >
<sl-card class="card-footer w-full min-w-full">

    <sl-tab-group >
      <!-- Dados Pessoais -->
      <sl-tab slot="nav" panel="personal">Dados Pessoais</sl-tab>

      <!-- Categoria -->
      <sl-tab slot="nav" id="tab-addres"  panel="address">Endereço</sl-tab>

      <sl-tab slot="nav" id="tab-social" panel="social">Outros Dados</sl-tab>

      <!-- Inscrição -->
      <sl-tab slot="nav" panel="register">Inscrição</sl-tab>
      <sl-tab slot="nav" panel="documents">Documentos</sl-tab>

      <sl-tab slot="nav" panel="registrations" id="registrations_tab" class="hidden">Inscrições Realizadas</sl-tab>
      <div class="alert-toast">
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


      <input type="checkbox" name="regulamento" > Confirmo que li o <a href="" target="__blank" id="linkRegulamento">Regulamento</a> e aceito seus termos.


    </sl-tab-group>

    <div slot="footer" class="flex justify-between gap-x-2">
        <button type="submit" disabled class="btn w-full  btn-success">Enviar inscrição</button>
    </div>
  </sl-card>


</form>



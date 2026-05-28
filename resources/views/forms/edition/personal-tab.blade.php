
<div class="  gap-4">

   <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <input type="hidden" name="edition" id="edition" value=""  >
            <input type="hidden" name="candidate_id" id="candidate_id" value=""  >
            <label for="input-cpf" class="form-label">CPF <span class="text-red-600">(*)</span></label>
            <input type="text" class="form-control" name="input-cpf" value="" id="input-cpf" required  >
             <div class="form-text mb-4">
                Somente será aceito CPF Válido !.
            </div>

        </div>

        <div>
            <label for="input-nome" class="form-label">Nome <span class="text-red-600">(*)</span></label>
            <input type="text" class="form-control" id="nome" name="nome"  required  >
        </div>

        <div>
            <label for="input-email" class="form-label">Email <span class="text-red-600">(*)</span></label>
            <input type="text" class="form-control" id="input-email" name="input-email"  required placeholder="seu@email.com">
        </div>

    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3   gap-4 mt-4">
        <div>
            <label class="form-label" for="input-rg">RG: <span class="text-red-600">(*)</span></label>
            <input class="form-control" type="text" id="input-rg" required name="rg" >
        </div>
        <div>
             <label class="form-label" for="input-rg_expeditor">Órgão expeditor do RG:  <span class="text-red-600">(*)</span></label>
            <input class="form-control" type="text" id="input-rg_expeditor" required name="rg_expeditor"  >
        </div>

         <div>
            <label class="form-label" for="input-rg_uf">UF do RG: <span class="text-red-600">(*)</span></label>
            <select  class="form-control"    required name="input-rg_uf" id="input-rg_uf"  >
                <option selected value="">Escolha um Estado</option>
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 mt-4 gap-4">
        <div>
            <label class="form-label" for="input-dt_nascimento">Data de Nascimento: <span class="text-red-600">(*)</span></label>
            <input  class="form-control" type="date" id="input-dt_nascimento" required name="dt_nascimento" >
        </div>

        <div>
            <label class="form-label" for="input-sexo">Sexo: <span class="text-red-600">(*)</span></label>
            <select  class="form-control"  required="true" required id="input-sexo" name="sexo">
                <option value="" >Selecione</option>
                <option value="Mulher">Mulher</option>
                <option value="Mulher (transgênero)">Mulher (transgênero)</option>
                <option value="Homem">Homem</option>
                <option value="Homem (transgênero)">Homem (transgênero)</option>
                <option value="Não binário (identidade que não é estritamente homem ou mulher)">Não binário (identidade que não é estritamente homem ou mulher)</option>
                <option value="Prefiro não informar">Prefiro não informar</option>
            </select>
        </div>



    </div>

    <div class="grid grid-cols-2 mt-4">
        <div>
            <label class="form-label" for="input-celular">Celular: <span class="text-red-600">(*)</span></label>
            <input class="form-control"  type="text" id="input-celular" required name="celular" ><br><br>
        </div>

        <div class="mt-4 ml-4">
            <sl-switch  id="input-celular" name="celular"   >WhatsApp</sl-switch>
        </div>
    </div>

</div>



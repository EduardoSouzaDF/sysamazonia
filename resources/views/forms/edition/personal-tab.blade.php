<div class="  gap-4">

   <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="input-cpf" class="form-label">CPF (*)</label>
            <input type="text" class="form-control" name="input-cpf" value="" id="input-cpf" required  >

        </div>

        <div>
            <label for="input-email" class="form-label">Email (*)</label>
            <input type="text" class="form-control" id="input-email" name="input-email"  required placeholder="seu@email.com">
        </div>

    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3   gap-4 mt-4">
        <div>
            <label class="form-label" for="input-rg">RG: (*)</label>
            <input class="form-control" type="text" id="input-rg" required name="rg" >
        </div>
        <div>
             <label class="form-label" for="input-rg_expeditor">Órgão expeditor do RG:  (*)</label>
            <input class="form-control" type="text" id="input-rg_expeditor" required name="rg_expeditor"  >
        </div>
        <div>
            <label  class="form-label"for="input-rg_uf">UF do RG: (*)</label>
            <input class="form-control" type="text" id="input-rg_uf" required name="rg_uf" >
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 mt-4 gap-4">
        <div>
            <label class="form-label" for="input-dt_nascimento">Data de Nascimento: (*)</label>
            <input  class="form-control" type="date" id="input-dt_nascimento" required name="dt_nascimento" >
        </div>

        <div>
            <label class="form-label" for="input-sexo">Sexo: (*)</label>
            <select  class="form-control"  required="true" required id="input-sexo" name="sexo">
                <option value="" >Selecione</option>
                <option value="M" >Masculino</option>
                <option value="F">Feminino</option>
                <option value="O">Outro</option>
            </select>
        </div>



    </div>

    <div class="grid grid-cols-2 mt-4">
        <div>
            <label class="form-label" for="input-celular">Celular: (*)</label>
            <input class="form-control"  type="text" id="input-celular" required name="celular" ><br><br>
        </div>

        <div class="mt-4 ml-4">
            <sl-switch  id="input-celular" name="celular"   >WhatsApp</sl-switch>
        </div>
    </div>

</div>



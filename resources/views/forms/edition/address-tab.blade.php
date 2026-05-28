<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div>

        <label for="input-cep" class="form-label">CEP <span class="text-red-600">(*)</span></label>
        <input type="text" class="form-control " style="margin-top: -6px;" required id="input-cep"  name="inputcep" placeholder="99999-999">
    </div>



     <div>
            <label class="form-label" for="input-Estado">Estado: <span class="text-red-600">(*)</span></label>
            <select  class="form-control"    required name="estado" id="input-estado"  >
                  <option selected value="">Escolha um Estado</option>
            </select>
        </div>

    <div>
         <label class="form-label" for="incidadeput-EstaCidadedo">Cidade: <span class="text-red-600">(*)</span></label>
            <select  class="form-control"    required name="cidade" id="input-cidade"  >
            </select>

    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
    <span>
        <label for="input-endereco" class="form-label">Endereço: <span class="text-red-600">(*)</span></label>
        <input type="text" class="form-control " name="endereco"   required id="input-endereco"  >
    </span>


     <span>
        <label for="input-numero" class="form-label">Número: <span class="text-red-600">(*)</span></label>
        <input type="text" class="form-control "   required name="numero" id="input-numero"  >
    </span>

    <span>
        <label for="input-complemento" class="form-label">Complemento: </label>
        <input type="text" class="form-control " name="complemento"   id="input-complemento"  >
    </span>
</div>

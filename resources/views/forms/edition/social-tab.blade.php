@php
    $escolaridade = [
        'Ensino Fundamental' => 'Ensino Fundamental',
        'Ensino Médio' => 'Ensino Médio',
        'Ensino Superior' => 'Ensino Superior',
        'Ensino Fundamental Incompleto' => 'Ensino Fundamental Incompleto',
        'Ensino  Médio Incompleto' => 'Ensino  Médio Incompleto',
        'Ensino Superior Incompleto' => 'Ensino Superior Incompleto',
    ];

@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

    <div>
        <x-form-select label="Escolaridade"   name="escolaridade" id="input-escolaridade" multiple="{{ false }}"
              required="{{ false }}" value=""  :options="$escolaridade" />
    </div>

    <span>
            <label for="input-presentation" class="form-label">Resumo Curricular (*)</label>
            <input type="hidden" required name="resumo_curricular" />
            <div id="editor-resumo-curricular" class="kt-input-text input-resumo" style="height: 300px;"></div>

    </span>


</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">

    <div>

        <label for="input-instituicao" class="form-label">Instituição</label>
        <input type="text" class="form-control " name="instituicao" style="margin-top: -6px;"  id="input-instituicao"  >
    </div>
    <span>
        <label for="input-instagram" class="form-label">Instagram</label>
        <input type="text" class="form-control " name="instagram"  id="input-instagram" value="https://" >
    </span>


     <span>
        <label for="input-facebook" class="form-label">Facebook</label>
        <input type="text" class="form-control " name="facebook"  id="input-facebook"  value="https://"  >
    </span>

    <span>
        <label for="input-outra_rede_social" class="form-label">Outra rede social</label>
        <input type="text" class="form-control " name="outra_rede_social  id="input-outra_rede_social" value="https://"  >
    </span>
</div>
<script type="text/javascript">
$('document').ready(()=>{

    $('#editor-resumo-curricular').on('summernote.change', function(we, contents, $editable) {
        console.log('Changed', contents);
        $("input [name='resumo_curricular']").val(contents);
    });


});
</script>

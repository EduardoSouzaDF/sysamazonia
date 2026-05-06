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

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4  categoryDiv">
    <div>
        <label class="form-label" for="input-category_id">Categoria: (*)</label>
        <select  class="form-control"    required  name="category" id="input-category_id" >
        </select>
    </div>
</div>

<div class="w-full min-h-100">
    <div class="  grid grid-cols-1 sm:grid-cols-2 gap-4  mt-4  notHonorific">
        <span>
                <label for="input-title" class="form-label">Título (*)</label>
                <input type="text" class="form-control " name="titulo" required   id="input-title"   >
        </span>


        <span>
            <label for="input-coautores" class="form-label">Coautores</label>
            <input type="text" class="form-control" name="coautores"   id="input-coautores"   >
            <div   class="form-text">
            Coautores separados por (;) ponto e vírgula.
            </div>
        </span>

        <span>
            <label for="input-resumo" class="form-label">Resumo: (*)</label>
            <input  type="text" class="hidden" name="input-resumo" id="input-resumo" val="" required />
            <div id="editor-resumo" class="kt-input-text input-resumo editor-resumo" style="height: 300px;"></div>
            <div   class="form-text">
            O resumo deverá conter no mínimo 500 palavras e no máximo 1000.
            </div>
        </span>

        <span>
            <label for="input-desenvolvimento" class="form-label">Desenvolvimento: (*)</label>
            <input  type="text" class="hidden" name="input-desenvolvimento" id="input-desenvolvimento" val="" required />
            <div id="editor-desenvolvimento" class="kt-input-text input-desenvolvimento" style="height: 300px;"></div>
            <div   class="form-text">
            O desenvolvimento deverá conter no mínimo 2000 palavras e no máximo 3000.
            </div>
        </span>

        <span>
            <label for="input-objetivo" class="form-label">Objetivo: (*)</label>
            <input  type="text" class="hidden" name="input-objetivo" id="input-objetivo" val="" required />
            <div id="editor-objetivo" class="kt-input-text input-objetivo" style="height: 300px;"></div>
            <div   class="form-text">
            O objetivo deverá conter no mínimo 1000 palavras e no máximo 2000.
            </div>
        </span>



        <span>
            <label for="input-conclusao" class="form-label">Conclusão: (*)</label>
            <input  type="text" class="hidden" name="input-conclusao" id="input-conclusao" val="" required />
            <div id="editor-conclusao" class="kt-input-text input-conclusao" style="height: 300px;"></div>
            <div   class="form-text">
            O objetivo deverá conter no mínimo 500 palavras e no máximo 1000.
            </div>
        </span>

    </div>

    <div class="  grid grid-cols-1 sm:grid-cols-2 gap-4  mt-4  Honorific">
        <span>
            <label for="input-title" class="form-label">Nome do(a) Indicado(a): (*)</label>
            <input type="text" class="form-control " name="name_nominee"   id="input-name" required   >
        </span>

        <div class="mt-2">

         <label class="form-label" for="input-Estado">Estado de Residência do(a) Indicado(a): (*)</label>
            <select  class="form-control"    required name="state" id="input-state"  >
                <option selected value="">Escolha um Estado</option>
            </select>

        </div>

         <span>
            <label for="input-contact_data" class="form-label">Dados de contato do(a) Indicado(a): (*)</label>
            <input type="text" class="form-control " name="contact-data"  required id="input-contact_data"   >
        </span>

        <span>
            <label for="input-presentation" class="form-label">Apresentação do(a) Indicado(a): (*)</label>
            <input  type="text" class="hidden" name="input-presentation" id="input-presentation" val="" required />
            <div id="editor-presentation" class="kt-input-text input-presentation" style="height: 300px;"></div>

        </span>

         <span>
            <label for="input-activities" class="form-label">Atividades desempenhadas: (*)</label>
            <input  type="text" class="hidden" name="input-activities" id="input-activities" val="" required />
            <div id="editor-activities" class="kt-input-text input-activities" style="height: 300px;"></div>

        </span>

        <span>
            <label for="input-justification" class="form-label">Justifique a indicação: (*)</label>
            <input type="text" name="input-justification" id="input-justification" class="hidden" required />
            <div id="editor-justification" class="kt-input-text input-justification" style="height: 300px;"></div>

        </span>

    </div>


</div>




<script type="text/javascript">
    $('document').ready(()=>{
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



        $('#editor-resumo').on('summernote.change', function(we, contents, $editable) {
            $("#input-resumo").val(contents).trigger('change');
        });

        $('#editor-desenvolvimento').on('summernote.change', function(we, contents, $editable) {
             $("#input-desenvolvimento").val(contents).trigger('change');
        });

        $('#editor-objetivo').on('summernote.change', function(we, contents, $editable) {
             $("#input-objetivo").val(contents).trigger('change');
        });

        $('#editor-conclusao').on('summernote.change', function(we, contents, $editable) {
           $("#input-conclusao").val(contents).trigger('change');
        });

        $('#editor-presentation').on('summernote.change', function(we, contents, $editable) {
             $("#input-presentation").val(contents).trigger('change');
        });

        $('#editor-justification').on('summernote.change', function(we, contents, $editable) {
            $("#input-justification").val(contents).trigger('change');
        });

         $('#editor-activities').on('summernote.change', function(we, contents, $editable) {
            $("#input-activities").val(contents).trigger('change');
        });


    });
</script>

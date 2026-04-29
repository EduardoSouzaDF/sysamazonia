<div class="gap-4">

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="mb-3">
            <label for="file1" class="form-label">Escolher arquivo #1</label>
            <input class="form-control" type="file" id="file1" name="files[]"
                   accept=".pdf,image/*" data-max-size="5242880">

             <div class="form-text mb-4">
                Somente serão aceitos arquivos PDF ou Imagens de até 5mb cada.
            </div>
        </div>

        <div class="mb-3">
            <label for="file2" class="form-label">Escolher arquivo #2</label>
            <input class="form-control" type="file" id="file2" name="files[]"
                   accept=".pdf,image/*" data-max-size="5242880">

                    <div class="form-text mb-4">
                Somente serão aceitos arquivos PDF ou Imagens de até 5mb cada.
            </div>
        </div>

        <div class="mb-3">
            <label for="file3" class="form-label">Escolher arquivo #3</label>
            <input class="form-control" type="file" id="file3" name="files[]"
                   accept=".pdf,image/*" data-max-size="5242880">

                    <div class="form-text mb-4">
                Somente serão aceitos arquivos PDF ou Imagens de até 5mb cada.
            </div>
        </div>
    </div>
</div>


<script  >
$('document').ready(()=>{
  // Seleciona todos os inputs de arquivo
  const fileInputs = document.querySelectorAll('input[type="file"]');
  const MAX_SIZE = 5 * 1024 * 1024; // 5MB em bytes

  fileInputs.forEach(input => {
    input.addEventListener('change', function() {
      const file = this.files[0];

      if (file) {
        // 1. Validar Extensão (Segunda camada de proteção)
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        if (!allowedTypes.includes(file.type)) {
          alert("Formato inválido! Apenas PDF ou Imagens são permitidos.");
          this.value = ""; // Limpa o campo
          return;
        }

        // 2. Validar Tamanho
        if (file.size > MAX_SIZE) {
          alert("O arquivo é muito grande! O limite é de 5MB.");
          this.value = ""; // Limpa o campo
        }
      }
    });
  });
})
</script>

# Regras SDD — fluxo obrigatório

Este arquivo define o comportamento do Cline ao trabalhar com o paradigma **SDD**
(Specification-Driven Development) neste repositório. Ele **complementa** o `CLAUDE.md`
(Laravel Boost), que permanece como camada superior.

## Antes de implementar

1. **Leia a spec.** Ao receber um pedido de recurso, procure a spec em `specs/<dominio>/NNNN-*.md`.
   - Se não existir, **crie uma** usando `docs/sdd/templates/template-spec.md`.
2. **Leia as tasks.** Abra os cards em `specs/<dominio>/tasks/` e identifique o que está
   `pending`/`em-progresso`. Trabalhe **um card por vez**.
3. **Leia os irmãos.** Antes de criar/editar um arquivo, confira os sibling files e o padrão
   existente (ex.: `CategoryController`, `categories/create.blade.php`).

## Durante a implementação

- Siga o `CLAUDE.md` (FormRequest, testes PHPUnit, convenções, não criar pastas base sem aprovação).
- Respeite as regras por extensão: `.clinerules/php.md` e `.clinerules/blade.md`.
- Não edite arquivos sem referência à spec/task correspondente.

## Após a implementação

- Atualize o **status** da task (`done`) e da spec.
- Registre **débito técnico** na task quando detectar inconsistência (ex.: validação inline
  que deveria ser FormRequest).

## Regras de qualidade

- **Sempre**: priorize reuso de componentes e helpers existentes.
- **Nunca**: apague testes existentes; crie código sem spec/task; ignore o `CLAUDE.md`.
- **Sempre**: rode o teste relacionado com filtro antes de dar por concluído.
# Nomenclaturas Oficiais — sysamazonia

> **Escopo**: rótulos de **UI**, **mensagens**, **rotas/identificadores** e **termos exibidos ao
> usuário**. Fonte única de verdade para specs, tasks, código e testes — o Cline **sempre** a leva
> em conta ao escrever ou revisar qualquer artefato.
>
> Diferença para `regras-dominio.md`: lá ficam as regras de **significado** (semântica, `RDD-xx`);
> aqui ficam **como escrever/denominar** (rótulos oficiais, `NM-xx`).

## NM-01 — Menu do julgador
- Rótulo exibido: **"Julgar"**.
- Nunca: "Jogar", "Jul", "Julgamentos", "juliar", "Jurado".
- Método no `MenuBuilder`: `getJulgadorMenu()` · rota: `/julgar` · nome da rota: `panel.julgar.index`.

## NM-02 — Perfil julgador
- Termo oficial: **"julgador"** (usuário com `is_judge = true`).
- Nunca: "jurado", "avaliador" (exceto se uma regra de domínio definir o contrário).

## NM-03 — Mensagem de acesso negado (área do julgador)
- Texto exato do flash na tela de login: **"Perfil sem Acesso!"** (com acento em "Acesso").

## NM-04 — Rótulos de status de Inscrições (exibição)
- `Registration`: **"Avaliado"** (`status = 4`).
- `Nominee`: **"Habilitado"** (`status = 3`).
- (Ver RDD-02 para o significado do termo "Inscrições".)

## NM-05 — Tela de Julgamento (seleção do julgador para a premiação)
- Card de Inscrição: **"{acronym} {id} - {ano do julgamento}"** (ex.: "PSD 3232 - 2026"; ano =
  ano de `Edition->judgment_date`).
- Listagem de pré-seleção: **"Iniciativas selecionadas para esta categoria"** (categoria regular) /
  **"Indicação para esta categoria"** (honorífica).
- Botões: **"Confirmar"** (adicionar no drawer · submeter a categoria), **"Limpar"** (zera as
  pré-seleções), **"Fechar"** (fecha o drawer).
- Conclusão: **"Julgamento concluído!"** · Estado vazio: **"Nenhuma categoria disponível para
  julgamento."**
- Seção do drawer (Registration): **"Avaliações e indicações"** (somente leitura).
- Modelo da escolha: **`JudgeSelection`** (tabela `judge_selections`).
- Nunca: "votar", "votação", "shortlist", "vencedores", "julgamentos".

## Como adicionar

1. Acrescente uma nova seção aqui, numerada `NM-xx`, com o rótulo oficial e o que **nunca** usar.
2. Referencie a `NM-xx` nas specs/tasks em que se aplica.
3. Corrigir aqui vale para **artefatos futuros**; artefatos já escritos precisam de correção
   pontual (reporte ao Cline para propagar).

> ⚠️ Complementa o `CLAUDE.md` (Laravel Boost), `.clinerules/regras-dominio.md` e
> `.clinerules/sdd.md`. Novas nomenclaturas devem ser acrescentadas aqui (numeradas `NM-xx`) e
> referenciadas nas specs em que se aplicam.
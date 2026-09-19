# Regras de Domínio — sysamazonia

> **Escopo**: estas regras são **globais** e devem ser **sempre levadas em conta** pelo Cline ao
> interpretar specs, tasks ou pedidos de implementação neste repositório.
> Quando um requisito/spec usar estes termos, entenda conforme definido abaixo, **sem** precisar
> repetir o detalhamento.

## RDD-01 — "Edições Ativas"

Quando um requisito disser **"somente para edições ativas"**, o filtro aplicado é:

- `Edition->is_registration_active = true`.

## RDD-02 — Termo "Inscrições"

O termo **"Inscrições"** refere-se à **união dos modelos `Registration` e `Nominee`**:

- `Registration`: inscrição **regular** (concorrente);
- `Nominee`: inscrição **honorífica**.

Caso o requisito mencione especificamente **"Inscrições honoríficas"**, refere-se **somente** ao
modelo **`Nominee`**.

## RDD-03 — "Edições em Julgamento"

Quando um requisito disser **"edição (ões) em julgamento"**, o filtro
aplicado é:

- a data atual do sistema seja **superior** a `Edition->judgment_date`.

---

> ⚠️ Complementa o `CLAUDE.md` (Laravel Boost) e o `.clinerules/sdd.md`. Novas Regras de Domínio
> devem ser acrescentadas aqui (numeradas `RDD-xx`) e referenciadas nas specs em que se aplicam.
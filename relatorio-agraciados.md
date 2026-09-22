# Relatório de inscrições agraciadas

Nenhum registro ativo e válido com status **Agraciado** foi encontrado.

## Mapeamento utilizado

- **Tecnologia:** Laravel com Eloquent/Query Builder e banco MySQL configurado por `config/database.php` e variáveis de ambiente. Nenhuma credencial foi incluída neste relatório.
- **Status:** código interno `5`, correspondente a `RegistrationStatusEnum::Agraciado`.
- **Inscrições não honoríficas:** `registrations`, relacionadas a `categories`, `modalities` e `candidates`.
- **Indicações honoríficas:** `nominees`, relacionadas a `categories`, `modalities` e `candidates`.
- **Categoria honorífica:** `categories.is_honorific = 1`; categoria comum: `categories.is_honorific = 0`.
- **Título da inscrição:** `registrations.title`.
- **Autor:** `registrations.candidate_id → candidates.nome`.
- **Coautores:** `registrations.coautores`, armazenados em texto, separados por ponto e vírgula.
- **Estado do autor:** `candidates.ufendereco`.
- **Resumo da inscrição:** `registrations.resumo`.
- **Nome do indicado:** `nominees.name`.
- **Estado do indicado:** `nominees.state`.
- **Indicador:** `nominees.candidate_id → candidates.nome`, correspondente ao responsável pelo envio da nomeação.
- **Resumo da indicação:** `nominees.justification`, conforme definido para este relatório.
- **Registros ativos:** modalidades com `modalities.is_active = 1`. As tabelas analisadas não possuem exclusão lógica, arquivamento ou cancelamento.

## Totais

- **Total geral de registros:** 0
- **Total por modalidade:** Nenhum
- **Total por categoria:** Nenhum
- **Campos obrigatórios não informados:** 0

## Validação

Foram executadas consultas somente de leitura equivalentes a:

```sql
SELECT COUNT(*)
FROM registrations r
JOIN categories c ON c.id = r.category_id
JOIN modalities m ON m.id = c.modality_id
WHERE r.status = 5
  AND c.is_honorific = 0
  AND m.is_active = 1;

SELECT COUNT(*)
FROM nominees n
JOIN categories c ON c.id = n.category_id
JOIN modalities m ON m.id = c.modality_id
WHERE n.status = 5
  AND c.is_honorific = 1
  AND m.is_active = 1;
```

Resultados da validação:

- Inscrições não honoríficas: 0
- Indicações honoríficas: 0
- Registros com modalidade inativa: 0
- Registros associados ao tipo incorreto de categoria: 0
- Registros com categoria ou modalidade ausente: 0
- Duplicidades produzidas por relacionamentos: 0
- Registros com status diferente do solicitado no relatório: 0

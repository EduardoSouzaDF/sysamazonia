# Feature Specification: [NOME DA FEATURE]

**Feature Branch**: `[###-nome-da-feature]`

**Created**: [DATA]

**Status**: Draft

**Input**: User description: "[breve descrição do pedido]"

---

## User Scenarios & Testing *(obrigatório)*

> User stories priorizadas como jornadas, em ordem de importância. Cada uma deve ser
> INDEPENDENTEMENTE TESTÁVEL — implementar apenas uma já entrega um MVP viável.
> Prioridades: P1 (crítico) → P2 → P3.

### User Story 1 — [Título breve] (Priority: P1)

[Descreva essa jornada em linguagem simples]

**Por que essa prioridade**: [Valor entregue e motivo da prioridade]

**Teste independente**: [Como testar isoladamente — "Pode ser totalmente testado ao [ação] e entrega [valor]"]

**Cenários de aceite (Given/When/Then):**
1. **Dado** [estado inicial], **Quando** [ação], **Então** [resultado esperado]
2. **Dado** [estado inicial], **Quando** [ação], **Então** [resultado esperado]

---

### User Story 2 — [Título breve] (Priority: P2)

[Descreva essa jornada]

**Por que essa prioridade**: [...]

**Teste independente**: [...]

**Cenários de aceite (Given/When/Then):**
1. **Dado** [estado inicial], **Quando** [ação], **Então** [resultado esperado]

---

### User Story 3 — [Título breve] (Priority: P3)

[Descreva essa jornada]

**Por que essa prioridade**: [...]

**Teste independente**: [...]

**Cenários de aceite (Given/When/Then):**
1. **Dado** [estado inicial], **Quando** [ação], **Então** [resultado esperado]

---

[Adicione mais histórias conforme necessário]

### Edge Cases (casos de borda)

- O que acontece quando [condição de contorno]?
- Como o sistema lida com [cenário de erro]?

---

## Requirements *(obrigatório)*

### Functional Requirements

- **FR-001**: O sistema DEVE [capacidade específica]
- **FR-002**: O sistema DEVE [validar ...]
- **FR-003**: Usuários DEVEM ser capazes de [interação chave]
- **FR-004**: O sistema DEVE [requisito de dados]
- **FR-005**: O sistema DEVE [comportamento]

*Exemplos de requisitos incertos:*

- **FR-006**: O sistema DEVE autenticar usuários via [NEEDS CLARIFICATION: método não especificado - email/senha, SSO, OAuth?]
- **FR-007**: O sistema DEVE reter dados do usuário por [NEEDS CLARIFICATION: período de retenção não especificado]

### Key Entities *(incluir se o recurso envolve dados)*

- **[Entidade 1]**: [O que representa, atributos-chave sem detalhes de implementação]
- **[Entidade 2]**: [O que representa, relacionamentos com outras entidades]

---

## Success Criteria *(obrigatório)*

> Critérios de sucesso devem ser AGNÓSTICOS de tecnologia e MENSURÁVEIS.

### Resultados Mensuráveis

- **SC-001**: [Métrica mensurável, ex.: "Usuários concluem criação de conta em menos de 2 minutos"]
- **SC-002**: [Métrica mensurável, ex.: "Sistema lida com 1000 usuários simultâneos sem degradação"]
- **SC-003**: [Métrica de satisfação, ex.: "90% dos usuários concluem a tarefa primária na primeira tentativa"]
- **SC-004**: [Métrica de negócio, ex.: "Reduzir tickets de suporte relacionados a [X] em 50%"]

---

## Assumptions (Premissas)

- [Premissa sobre usuários, ex.: "Usuários têm conexão estável com a internet"]
- [Premissa sobre escopo, ex.: "Suporte mobile está fora do escopo da v1"]
- [Premissa sobre dados/ambiente, ex.: "Sistema de autenticação existente será reutilizado"]
- [Dependência de sistema/serviço existente, ex.: "Requer acesso à API de perfil do usuário"]

---

## Histórico

| Data | Status | Autor |
|------|--------|-------|
| [DATA] | Draft | [AUTOR] |
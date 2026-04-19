# Fluxo de estágios das tarefas

Este documento descreve o modelo de estágios atualmente implementado para `Task`, tanto no painel quanto na API.

## Visão geral

Cada tarefa possui um estágio operacional em `current_stage`. O estágio indica em que parte do fluxo a demanda está naquele momento.

Valores suportados:

* `analysis`
* `implementation:backend`
* `implementation:frontend`
* `implementation:infra`

O estágio é obrigatório na criação e na atualização de tarefas.

## Blocos de dados relacionados

Além de `current_stage`, a tarefa expõe três blocos complementares:

### `analysis`

Representa o resultado da triagem técnica da tarefa.

Campos disponíveis:

* `domain`: `backend`, `frontend` ou `infra`
* `confidence`: número entre `0` e `1`
* `next_stage`: próximo estágio sugerido
* `summary`: resumo da análise
* `evidence`: evidências em JSON
* `risks`: riscos em JSON
* `artifacts`: artefatos em JSON
* `notes`: observações livres

Uso esperado:

* classificar a demanda
* justificar a escolha do próximo estágio
* registrar contexto para quem vai executar a implementação

### `stage_execution`

Representa a execução técnica associada a um estágio específico.

Campos disponíveis:

* `reference`: identificador externo ou interno da execução
* `stage`: estágio executado
* `status`: estado livre da execução do estágio
* `agent`: agente, worker ou executor responsável
* `summary`: resumo técnico
* `output`: saída estruturada em JSON
* `raw_output`: saída textual bruta
* `exit_code`: código de saída
* `started_at`: início da execução
* `finished_at`: fim da execução
* `context`: contexto complementar em JSON

Uso esperado:

* registrar a execução da etapa técnica
* armazenar saída de automação
* manter rastreabilidade por estágio

### `handoff`

Representa a transferência de contexto entre estágios.

Campos disponíveis:

* `from_stage`: estágio de origem
* `to_stage`: estágio de destino
* `reason`: motivo da transição
* `confidence`: confiança da transição, entre `0` e `1`
* `summary`: resumo do handoff
* `payload`: contexto estruturado em JSON

Uso esperado:

* documentar por que a tarefa mudou de estágio
* transferir contexto de análise para implementação
* guardar payload útil para o próximo executor

## Regras práticas observadas na implementação

* `current_stage` é validado contra o enum `TaskStage`.
* `analysis.domain` é validado contra o enum `TaskAnalysisDomain`.
* `analysis.next_stage`, `stage_execution.stage`, `handoff.from_stage` e `handoff.to_stage` aceitam apenas valores válidos de `TaskStage`.
* campos JSON aceitam tanto string JSON válida quanto array/objeto no payload; a aplicação normaliza arrays antes de validar.
* `stage_execution.finished_at` deve ser maior ou igual a `stage_execution.started_at`.

## Exemplo de payload

```json
{
  "project_id": 1,
  "environment_profile_id": 2,
  "title": "Corrigir callback de autenticação",
  "description": "Ajustar o fluxo após o login social.",
  "priority": "high",
  "implementation_type": "fix",
  "current_stage": "analysis",
  "analysis_domain": "backend",
  "analysis_confidence": 0.92,
  "analysis_next_stage": "implementation:backend",
  "analysis_summary": "Fluxo localizado no controller de autenticação.",
  "analysis_evidence": {
    "entrypoint": "AuthController"
  },
  "handoff_to_stage": "implementation:backend"
}
```

## Referências no código

Os contratos acima refletem a implementação atual nestes pontos:

* `app/Support/Enums/TaskStage.php`
* `app/Support/Enums/TaskAnalysisDomain.php`
* `app/Http/Requests/Task/StoreTaskRequest.php`
* `app/Http/Requests/Task/UpdateTaskRequest.php`
* `app/Http/Resources/TaskResource.php`

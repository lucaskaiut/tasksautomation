# Fluxo de estágios das tarefas

Este documento descreve o modelo simplificado de estágios para `Task`, no painel e na API.

## Visão geral

Cada tarefa tem um **`current_stage`** (enum `TaskStage`) e um **histórico ordenado** de transições na tabela `task_stage_histories`.

Valores de estágio:

* `analysis`
* `implementation:backend`
* `implementation:frontend`
* `implementation:infra`

Na **criação** da tarefa, define-se o estágio inicial; é criada automaticamente a primeira linha de histórico com o resumo fixo `Tarefa criada`.

## Histórico (`task_stage_histories`)

Cada registo contém:

* `stage`: estágio associado a essa linha (valor de `TaskStage`)
* `summary`: texto livre que descreve o contexto da transição ou nota
* `created_at` / `updated_at`: carimbos temporais

O histórico é **apenas uma lista cronológica**; não há blocos separados de análise, execução ou handoff na base de dados.

## API: alterar estágio (`POST /api/tasks/{task}/change-stage`)

Corpo JSON (ver `TaskChangeStageRequest` no `openapi.yml`):

* **`stage`** (obrigatório): um dos valores de `TaskStage`
* **`summary`** (obrigatório): texto livre (até ~64 KiB)

Efeito:

1. Insere uma linha em `task_stage_histories` com `stage` e `summary`
2. Atualiza `tasks.current_stage` para o mesmo `stage`

Pode enviar o **mesmo** `stage` que o atual para acrescentar apenas uma nota ao histórico (o `current_stage` mantém-se coerente).

## Atualização geral da tarefa (`PUT` / `PATCH /api/tasks/{task}`)

O **`current_stage` não é alterado** por estes endpoints. Para mudar estágio, use sempre `change-stage`.

## Painel web

* **Criar tarefa:** escolhe-se o estágio inicial no formulário.
* **Ficha da tarefa:** tabela de evolução + formulário que faz `POST` para `tasks.change-stage` com `stage` e `summary`.

## Referências no código

* `app/Support/Enums/TaskStage.php`
* `app/Models/TaskStageHistory.php`
* `app/Http/Requests/Task/ChangeTaskStageRequest.php`
* `app/Services/Task/ChangeTaskStageService.php`
* `app/Http/Requests/Task/StoreTaskRequest.php` / `UpdateTaskRequest.php`
* `database/migrations/2026_04_24_131129_create_task_stage_histories_table.php`

# Contexto técnico — Gestor de tarefas

Este documento descreve o **estado atual** da aplicação: um gestor de tarefas em **Laravel 13** com painel web (Blade) e **API REST** autenticada por **Laravel Sanctum**. O núcleo de negócio e a validação são **partilhados** entre web e API (Form Requests, serviços e modelos); controllers web devolvem HTML e controllers API devolvem JSON (API Resources).

---

## Stack

| Camada | Tecnologia |
|--------|------------|
| Backend | PHP 8.4, Laravel 13 |
| Autenticação web | Laravel Breeze (sessão, CSRF) |
| Autenticação API | Sanctum (tokens Bearer) |
| Frontend | Blade, Tailwind CSS v3, Vite, Alpine.js |
| Testes | PHPUnit 12 (feature tests como base) |

Referências oficiais: [Laravel 13](https://laravel.com/docs/13.x), [Sanctum](https://laravel.com/docs/13.x/sanctum).

---

## O que a aplicação faz

- **Projetos**: CRUD no painel e na API; cada projeto agrupa tarefas e pode ter regras globais e repositório configurados.
- **Perfis de ambiente** (`ProjectEnvironmentProfile`): gestão no painel (listagem, criação, edição por projeto); servem de contexto estrutural para tarefas (ex.: perfil associado à tarefa).
- **Tarefas**: criação, edição, listagem, ficha detalhada no painel; na API inclui listagem, detalhe e atualização de campos **sem** alterar o estágio atual por `PUT`/`PATCH` (estágio só via fluxo dedicado).
- **Estágios e histórico**: cada tarefa tem `current_stage` (enum `TaskStage`) e histórico cronológico em `task_stage_histories` (transições com resumo em texto livre). Detalhe do fluxo: `docs/task-stage-flow.md`.
- **Ciclo operacional para workers (API)**: `claim` de tarefa elegível, `heartbeat` durante execução, `finish` para fecho técnico; campos na tabela `tasks` refletem bloqueios, tentativas, tempos e estado de revisão.
- **Execuções** (`TaskExecution`): histórico técnico por execução (estado, tempos, metadados, ligação a worker).
- **Revisões** (`TaskReview`): revisão funcional associada a uma execução, exposta na API e utilizável no painel na ficha da tarefa.
- **Tempo real**: endpoint autenticado para emissão de token WebSocket usado em atualizações de estado de tarefa (ver `TaskWebsocketTokenController` e testes em `tests/Feature/Api`).
- **Saúde**: `GET /api/health` (público) para verificações de disponibilidade.

---

## Princípios de arquitetura (como está implementado)

- **Controllers finos**: delegam a serviços (`app/Services/...`) e devolvem view ou `JsonResource`.
- **Form Requests** partilhados entre rotas web e API quando o caso de uso é o mesmo.
- **Serviços** concentram regras de criação/atualização, claim, heartbeat, finalização, mudança de estágio, revisões, etc.
- **Modelos** Eloquent: relacionamentos, casts para enums e tipos, scopes (ex.: tarefas elegíveis para claim).
- **Políticas** `ProjectPolicy` e `TaskPolicy` para autorização em recursos alinhados com os casos de uso.
- **Enums** em `app/Support/Enums/`: `TaskStatus`, `TaskPriority`, `TaskStage`, `TaskImplementationType`, `TaskExecutionStatus`, `TaskReviewStatus`, `TaskReviewDecision`.
- **DTOs** em `app/Support/DTOs/` onde faz sentido padronizar payloads (ex.: `TaskData`).

---

## Entidades e relações (resumo)

- **User**: autenticação; cria tarefas; pode rever execuções.
- **Project**: `hasMany` tarefas e perfis de ambiente.
- **ProjectEnvironmentProfile**: `belongsTo` projeto; opcionalmente referenciado por tarefas.
- **Task**: `belongsTo` projeto, criador e opcionalmente perfil de ambiente; `hasMany` execuções, revisões e linhas de histórico de estágio; campos operacionais (claim, heartbeats, revisão, etc.) conforme migrações.
- **TaskStageHistory**: `belongsTo` tarefa; regista `stage` + `summary` por transição.
- **TaskExecution**: `belongsTo` tarefa; `hasOne` revisão.
- **TaskReview**: associada a execução/tarefa conforme modelo.

O detalhe de colunas está nas **migrations** em `database/migrations/`.

---

## Rotas principais

- **Web** (`routes/web.php`): redirecionamento da raiz; Breeze (login, registo, perfil); resource de projetos; rotas de perfis de ambiente por projeto; resource de tarefas **com** `show`; `POST tasks/{task}/change-stage`; revisões por execução na tarefa.
- **API** (`routes/api.php`): prefixo de nomes `api.*`; `POST /api/tokens/create`; com `auth:sanctum`: projetos, tarefas, `POST /api/tasks/claim`, `POST /api/tasks/{task}/heartbeat`, `POST /api/tasks/{task}/finish`, `change-stage`, listagem/detalhe de execuções, criação de revisões; grupo com throttle para token WebSocket de tarefas.

Contrato HTTP detalhado: **`openapi.yml`** na raiz do repositório.

---

## Serviços (amostra representativa)

Em `app/Services/`:

- **Project**: `CreateProjectService`, `UpdateProjectService`
- **ProjectEnvironmentProfile**: `CreateProjectEnvironmentProfileService`, `UpdateProjectEnvironmentProfileService`
- **Task**: `CreateTaskService`, `UpdateTaskService`, `ChangeTaskStageService`, `ClaimTaskService`, `TaskHeartbeatService`, `FinishTaskService`, `SubmitTaskReviewService`
- **Auth**: `ApiTokenService`
- **Realtime**: `TaskStreamPublisher` (integração com fluxo de eventos)
- **Health**: `ApplicationHealthChecker`

---

## Testes

Os feature tests em `tests/Feature/` cobrem fluxos web e API (autenticação, projetos, tarefas, claim, heartbeat, finish, estágios, execuções, revisões, health, token em tempo real). Para validar alterações, prefira `php artisan test --compact` com o ficheiro ou filtro relevante.

---

## Documentação relacionada

| Ficheiro | Conteúdo |
|----------|-----------|
| `docs/task-stage-flow.md` | Estágios da tarefa, histórico e endpoint `change-stage` |
| `public/openapi.yml` | Endpoints, esquemas e códigos de resposta da API |
| `docs/WEBSOCKET_FRONTEND.md` | Websocket para atualização em tempo real das tarefas |

---

## Evolução possível

O código já inclui execuções, revisão, workers via API e sinais de tempo real. Linhas futuras típicas: integrações externas mais profundas, políticas por equipa, observabilidade operacional adicional ou automatismos de criação de tarefas — sempre alinhados com os testes e com o contrato em `openapi.yml`.

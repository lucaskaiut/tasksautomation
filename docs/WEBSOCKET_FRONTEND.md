# WebSocket de tarefas — integração no frontend

Este guia descreve como um cliente (SPA, app móvel com bridge, etc.) se autentica de forma segura, obtém o token de sessão WebSocket e interpreta as mensagens JSON enviadas pelo servidor.

## Visão geral da segurança

1. **API HTTPS** — O token WebSocket só deve ser pedido à API Laravel com **TLS** (`https://`) em produção, usando autenticação **Sanctum** (`Authorization: Bearer <token>`).
2. **WebSocket seguro** — Em páginas servidas por HTTPS, o browser exige **`wss://`** (não `ws://`). Configure um proxy reverso (Nginx, Caddy, Traefik, etc.) que termine TLS e faça *upgrade* para o processo `php artisan tasks:websocket`.
3. **Token de curta duração relativa** — O token na *query string* do WebSocket é assinado com a `APP_KEY` e expira após `TASKS_REALTIME_TOKEN_TTL_SECONDS` (valor em `config/tasks-realtime.php`). **Renove** o token chamando de novo o endpoint HTTP antes de expirar ou após reconexão falhada por `401` no *handshake*.
4. **Não exponha o token** em URLs partilháveis, logs públicos ou *analytics*. Trate-o como credencial de sessão do canal em tempo real.

## Pré-requisitos no servidor

- O comando **`php artisan tasks:websocket`** deve estar em execução (ou supervisionado por systemd, Supervisor, Laravel Cloud, etc.).
- Defina **`TASKS_REALTIME_PUBLIC_WS_ORIGIN`** no `.env` com a origem pública do WebSocket **sem path**, por exemplo:
  - `wss://api.seudominio.com`
  - `wss://api.seudominio.com:443` (se necessário explicitar porta)

  Com isso, a API pode devolver `websocket_url` já completa. Sem essa variável, a API devolve apenas `token` e `websocket_path`; o frontend deve montar a URL (por exemplo com variável de ambiente `VITE_TASKS_REALTIME_PUBLIC_WS_ORIGIN`).

## Obter o token (HTTP)

**Pedido**

```http
GET /api/realtime/tasks/ws-token
Authorization: Bearer <sanctum_token>
Accept: application/json
```

**Resposta 200 (exemplo)**

```json
{
  "data": {
    "token": "<jwt_like_signed_payload>",
    "expires_in_seconds": 28800,
    "websocket_path": "/ws/tasks",
    "websocket_url": "wss://api.seudominio.com/ws/tasks?token=..."
  },
  "message": "Token WebSocket emitido com sucesso."
}
```

- **`websocket_url`** — Pode ser `null` se `TASKS_REALTIME_PUBLIC_WS_ORIGIN` não estiver definido. Nesse caso monte: `new URL(websocket_path, SUA_ORIGEM_WSS).href + '?token=' + encodeURIComponent(token)` (ou equivalente).
- **`401`** — Token Sanctum em falta ou inválido.
- **`429`** — Limite de pedidos (por utilizador autenticado) para este endpoint.

## Abrir a conexão WebSocket

1. Obtenha `token` (e opcionalmente `websocket_url`) via `GET /api/realtime/tasks/ws-token`.
2. Conecte com `new WebSocket(url)` onde `url` inclui `?token=...` (a API já devolve o parâmetro corretamente codificado em `websocket_url` quando configurada).

Fluxo mínimo em JavaScript:

```javascript
const apiBase = 'https://api.seudominio.com';
const sanctumToken = '<seu_bearer_token>';

const res = await fetch(`${apiBase}/api/realtime/tasks/ws-token`, {
  headers: {
    Authorization: `Bearer ${sanctumToken}`,
    Accept: 'application/json',
  },
});

const body = await res.json();
const wsUrl = body.data.websocket_url ?? buildWsUrl(body.data);
const socket = new WebSocket(wsUrl);

function buildWsUrl(data) {
  const origin = import.meta.env.VITE_TASKS_REALTIME_PUBLIC_WS_ORIGIN;
  const url = new URL(data.websocket_path, origin);
  url.searchParams.set('token', data.token);
  return url.toString();
}
```

## Mensagens enviadas pelo servidor (eventos)

Todas as mensagens são **JSON** em texto (frame texto WebSocket).

### `connection.ready`

Enviada logo após o *handshake* bem-sucedido.

```json
{
  "type": "connection.ready",
  "connected_at": "2026-04-19T12:00:00+00:00"
}
```

### `subscription.synced`

Resposta a um `subscribe` válido. Inclui *snapshots* das tarefas cobertas pelas subscrições pedidas.

```json
{
  "type": "subscription.synced",
  "tasks": [],
  "synced_at": "2026-04-19T12:00:01+00:00"
}
```

Cada entrada em `tasks` segue o mesmo formato base dos eventos `task.*` (inclui `type`, `task_id`, `task`, `presentation`, etc.; em sincronização inicial o `type` é tipicamente `task.snapshot`).

### `task.snapshot`

Estado completo de uma tarefa no contexto de sincronização ou listagem.

### `task.created`

Uma tarefa foi criada.

### `task.updated`

Uma tarefa foi alterada. O campo `changes` indica atributos com `from` / `to`.

### `task.deleted`

Uma tarefa foi removida. Quem só tinha subscrição por `task_id` deve deixar de a mostrar; o servidor aplica regras de política para quem pode receber este evento.

### Campos comuns nos eventos `task.*`

| Campo | Descrição |
|--------|-----------|
| `type` | `task.snapshot` \| `task.created` \| `task.updated` \| `task.deleted` |
| `task_id` | Identificador da tarefa |
| `project_id` | Projeto |
| `occurred_at` | ISO 8601 |
| `task` | Objeto da tarefa (estrutura alinhada à API) |
| `presentation` | Etiquetas, classes de *badge*, prioridade, worker, revisões, etc. |
| `changes` | Apenas em atualizações: mapa `atributo` → `{ from, to }` |

## Mensagens enviadas pelo cliente

### Subscrever canais

Após `open`, envie um frame texto JSON:

```json
{
  "type": "subscribe",
  "subscriptions": [
    { "scope": "task", "task_id": 42 },
    { "scope": "index", "page": 1, "per_page": 20 },
    { "scope": "list", "task_ids": [1, 2, 3] },
    { "scope": "project", "project_id": 5 }
  ]
}
```

**Escopos**

| Escopo | Uso |
|--------|-----|
| `task` | Uma tarefa (`task_id`). Exige permissão de visualização da tarefa. |
| `index` | Lista paginada (`page`, `per_page` ≤ 100). Exige `viewAny` em tarefas. |
| `list` | Vários `task_id` permitidos ao utilizador. |
| `project` | Todas as tarefas do projeto (`project_id`). |

Subscrições inválidas ou não autorizadas são ignoradas sem erro explícito na lista.

### Cancelar subscrições

```json
{
  "type": "unsubscribe",
  "subscriptions": [{ "scope": "task", "task_id": 42 }]
}
```

## Reconexão

O cliente Blade existente reconecta após `close` com *backoff* simples. Para o teu frontend:

1. Ao fechar ou falhar, obtém de novo o token por HTTP (ou só se o anterior expirou).
2. Abre novo `WebSocket`.

## CORS e origem cruzada

Se o frontend corre noutro domínio que a API, garante que o browser pode chamar `GET /api/realtime/tasks/ws-token` (cabeçalhos CORS à tua configuração Laravel). O *handshake* WebSocket não usa CORS como REST, mas o pedido inicial do token sim.

## Referência de configuração

| Variável | Função |
|----------|--------|
| `TASKS_REALTIME_WS_HOST` / `TASKS_REALTIME_WS_PORT` | *Bind* do servidor WebSocket |
| `TASKS_REALTIME_WS_PATH` | Caminho do path HTTP do *upgrade* (ex.: `/ws/tasks`) |
| `TASKS_REALTIME_TOKEN_TTL_SECONDS` | Validade do token na query |
| `TASKS_REALTIME_PUBLIC_WS_ORIGIN` | Origem `wss://...` para a API montar `websocket_url` |

---

*Endpoint nomeado na aplicação: `api.realtime.tasks.ws-token` (útil para `route()` apenas no backend).*

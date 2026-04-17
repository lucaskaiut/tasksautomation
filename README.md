# Gestor de Tarefas (Fase 1)

Projeto em **Laravel 13** com painel web em **Blade + Tailwind CSS 4** e API autenticada via **Sanctum**, seguindo a arquitetura descrita em `docs/context.md`.

## Requisitos

- Docker + Docker Compose

## Subir com Docker (desenvolvimento)

1) Suba os serviços:

```bash
docker compose up -d --build
```

2) Instale dependências PHP e gere a chave (o entrypoint já tenta fazer isso, mas você pode rodar explicitamente):

```bash
docker compose run --rm app composer install
```

3) Configure `.env` (se ainda não existir):

```bash
docker compose run --rm app cp .env.example .env
docker compose run --rm app php artisan key:generate
```

4) Rode migrations e seed (cria um usuário admin padrão):

```bash
docker compose run --rm app php artisan migrate
docker compose run --rm app php artisan db:seed
```

5) Para assets (Vite/Tailwind):

```bash
docker compose up -d node
```

## Acessar o painel

- URL: `http://localhost:8080`
- Login (seed):
  - e-mail: `admin@example.com`
  - senha: `password`

## Usar a API autenticada (Sanctum)

### Criar token

```bash
curl -X POST "http://localhost:8080/api/tokens/create" \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password","token_name":"worker"}'
```

Resposta inclui `data.token`. Use como Bearer:

```bash
curl "http://localhost:8080/api/projects" \
  -H "Authorization: Bearer <TOKEN>"
```

## Rodar testes

```bash
docker compose run --rm app php artisan test
```
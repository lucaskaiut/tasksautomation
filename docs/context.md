# Arquitetura técnica — Gestor de tarefas (Fase 1)

## Objetivo da fase

Esta primeira fase cobre **apenas a gestão das tarefas pelo painel web**, incluindo criação, edição, autenticação, relacionamentos necessários, API autenticada e testes automatizados de todas as rotas. O sistema deve ser construído em **Laravel 13**, com frontend em **Blade + Tailwind CSS 4**, autenticação web tradicional para o painel e autenticação da API via **Laravel Sanctum**. Laravel 13 mantém a estrutura convencional de rotas em `routes/web.php` e `routes/api.php`, e o Sanctum continua sendo a solução oficial leve para autenticação por token em APIs. Tailwind CSS 4 é a linha atual e tem integração orientada a Vite, com mudanças importantes em relação ao v3 e foco em navegadores modernos. ([Laravel][1])

---

# 1. Escopo funcional da fase 1

## Incluído

* autenticação do painel web
* autenticação da API com Sanctum
* cadastro de projetos
* edição de projetos
* cadastro de tarefas
* edição de tarefas
* listagem de projetos
* listagem de tarefas
* relacionamento entre tarefas e projetos
* relacionamento entre tarefas e usuário criador
* estrutura inicial para regras globais do projeto
* estrutura inicial para perfis de ambiente do projeto
* testes automatizados de todas as rotas web e API com seus casos de uso

---

# 2. Visão arquitetural

A arquitetura deve seguir uma separação clara entre:

* **camada de apresentação web**: controllers Blade
* **camada de apresentação API**: controllers JSON
* **camada de entrada/validação**: Form Requests compartilhados
* **camada de aplicação**: Services
* **camada de domínio/regra de negócio**: regras encapsuladas nos Services e Value Objects simples quando necessário
* **camada de persistência**: Models Eloquent, scopes e relacionamentos
* **camada de testes**: Feature tests cobrindo web e API

A regra principal é:

> **web e API compartilham as mesmas regras de entrada e a mesma lógica de negócio**.
> A única diferença entre ambos deve estar nos controllers, que apenas orquestram se a resposta será uma view HTML ou um JSON.

Isso aproveita bem os padrões do Laravel, especialmente a organização por Form Requests, controllers e service container, que continuam como pilares da estrutura do framework. ([Laravel][2])

---

# 3. Princípios de projeto

## 3.1. Um único núcleo de negócio

Nada de duplicar regra entre:

* controller web
* controller API
* request web
* request API

A validação e a transformação da entrada devem ser únicas.

## 3.2. Controllers finos

Controllers não devem:

* aplicar regra de negócio
* montar arrays complexos de persistência
* decidir regra de autorização de domínio
* manipular diretamente detalhes de storage fora do fluxo simples

Controllers devem apenas:

* receber a request
* delegar ao service
* retornar view ou JSON

## 3.3. Services como camada de aplicação

Os Services serão responsáveis por:

* criação e atualização de projetos
* criação e atualização de tarefas
* centralização das regras de negócio
* preparação dos dados persistidos

## 3.4. Modelos com responsabilidade limitada

Models devem conter:

* relacionamentos
* casts
* scopes simples
* helpers leves de leitura

Evitar colocar regra de fluxo de aplicação dentro dos Models.

## 3.5. Testes por caso de uso

Cada rota relevante deve ter teste cobrindo:

* acesso autenticado
* acesso não autenticado
* sucesso
* validação inválida
* recurso inexistente, quando aplicável
* autorização, quando aplicável

---

# 4. Módulos da fase 1

## 4.1. Módulo de autenticação

Responsável por:

* login web no painel
* logout web
* emissão de token Sanctum para API
* revogação de token, se exposto nesta fase

### Estratégia recomendada

**Painel web**

* autenticação via sessão padrão do Laravel
* middleware `auth`

**API**

* autenticação via Sanctum
* middleware `auth:sanctum`

Laravel continua diferenciando claramente rotas web, com sessão/CSRF, e rotas API autenticadas por token ou cookie conforme o caso. Sanctum permanece apropriado para tokens simples por usuário. ([Laravel][3])

---

## 4.2. Módulo de projetos

O projeto é a entidade que contextualiza as tarefas.

### Responsabilidades

* cadastrar projeto
* editar projeto
* listar projetos
* armazenar regras globais
* armazenar configuração inicial de perfis de ambiente

### Motivação

A tarefa sozinha não é suficiente. O projeto concentra:

* nome
* repositório
* regras globais
* contexto base do executor futuro
* perfis operacionais de ambiente

---

## 4.3. Módulo de tarefas

A tarefa é a unidade de trabalho gerenciada pelo painel.

### Responsabilidades

* cadastrar tarefa
* editar tarefa
* listar tarefa
* vincular a um projeto
* armazenar título, descrição, entregáveis e restrições

### Observação

Mesmo na fase 1, já vale estruturar a tarefa pensando nas fases seguintes, para evitar refactor desnecessário.

---

# 5. Modelagem de domínio

## 5.1. Entidades principais

### User

Usuário autenticado do sistema.

### Project

Contexto do projeto onde as tarefas existem.

### ProjectEnvironmentProfile

Perfis de ambiente do projeto, como `light`, `moderate`, `full`.

### Task

Tarefa cadastrada no painel.

---

## 5.2. Relacionamentos

### User

* `hasMany(Task::class, 'created_by')`

### Project

* `hasMany(Task::class)`
* `hasMany(ProjectEnvironmentProfile::class)`

### ProjectEnvironmentProfile

* `belongsTo(Project::class)`

### Task

* `belongsTo(Project::class)`
* `belongsTo(User::class, 'created_by')`
* opcionalmente `belongsTo(ProjectEnvironmentProfile::class, 'environment_profile_id')`

---

# 6. Estrutura de banco de dados

## 6.1. Tabela `users`

A tabela padrão do Laravel pode ser usada como base.

Campos relevantes:

* `id`
* `name`
* `email`
* `password`
* timestamps

---

## 6.2. Tabela `projects`

Campos recomendados:

* `id`
* `name`
* `slug`
* `description` nullable
* `repository_url`
* `default_branch` default `main`
* `global_rules` nullable longText/json
* `is_active` boolean default true
* timestamps

### Observações

**`slug`**

* útil para URLs amigáveis
* útil para integrações futuras

**`global_rules`**

* pode começar como `longText`
* preferencialmente armazenado como JSON
* servirá depois para injeção de contexto no executor

---

## 6.3. Tabela `project_environment_profiles`

Campos recomendados:

* `id`
* `project_id`
* `name`
* `slug`
* `description` nullable
* `validation_profile` nullable json
* `environment_definition` nullable json
* `is_default` boolean default false
* timestamps

### Observação

Nesta fase, esses perfis podem ser apenas gerenciáveis como dado estrutural, mesmo sem uso operacional real ainda.

---

## 6.4. Tabela `tasks`

Campos recomendados:

* `id`
* `project_id`
* `environment_profile_id` nullable
* `created_by`
* `title`
* `description` longText
* `deliverables` longText nullable
* `constraints` longText nullable
* `status`
* `priority`
* timestamps

### Sugestão de enums lógicos

**status**

* `draft`
* `pending`

Na fase 1, isso já basta.

**priority**

* `low`
* `medium`
* `high`

### Observações

**`deliverables`**

* descreve o que precisa ser entregue

**`constraints`**

* descreve o que não deve ser feito ou limites da implementação

**`created_by`**

* importante para auditoria mínima desde já

---

# 7. Casts e tipagem de dados

## Em `Project`

* `global_rules` => `array`
* `is_active` => `boolean`

## Em `ProjectEnvironmentProfile`

* `validation_profile` => `array`
* `environment_definition` => `array`
* `is_default` => `boolean`

## Em `Task`

* usar enums PHP para `status` e `priority`, ou casts customizados se preferir maior robustez

---

# 8. Organização de diretórios

Estrutura sugerida:

```text
app/
  Actions/
  Http/
    Controllers/
      Web/
        Auth/
        ProjectController.php
        TaskController.php
      Api/
        Auth/
        ProjectController.php
        TaskController.php
    Requests/
      Auth/
      Project/
        StoreProjectRequest.php
        UpdateProjectRequest.php
      Task/
        StoreTaskRequest.php
        UpdateTaskRequest.php
    Resources/
  Models/
    Project.php
    ProjectEnvironmentProfile.php
    Task.php
    User.php
  Services/
    Auth/
      ApiTokenService.php
    Project/
      CreateProjectService.php
      UpdateProjectService.php
    Task/
      CreateTaskService.php
      UpdateTaskService.php
  Support/
    Enums/
      TaskPriority.php
      TaskStatus.php
    DTOs/
      ProjectData.php
      TaskData.php
```

## Motivo dessa organização

* separa claramente Web e API só na camada de controller
* Requests ficam únicos e compartilháveis
* Services agrupados por módulo
* Enums e DTOs ajudam a tipar melhor a aplicação

---

# 9. Fluxo das requisições

## 9.1. Fluxo web

1. usuário autenticado acessa rota web
2. controller web recebe `FormRequest`
3. controller chama service
4. service executa regra
5. controller retorna redirect/view

## 9.2. Fluxo API

1. cliente autenticado com Sanctum acessa rota API
2. controller API recebe o mesmo `FormRequest`
3. controller chama o mesmo service
4. service executa regra
5. controller retorna JSON

---

# 10. Camada de requests

Os Form Requests devem ser compartilhados entre web e API.

## Exemplo de responsabilidade de um Request

### `StoreTaskRequest`

* autorizar ação
* validar entrada
* expor método para obter payload validado padronizado

### Regras esperadas para `Task`

* `project_id` obrigatório e existente
* `environment_profile_id` opcional e existente
* `title` obrigatório, string, limite de tamanho
* `description` obrigatória
* `deliverables` opcional
* `constraints` opcional
* `priority` obrigatória
* `status` obrigatória ou default tratada pela aplicação

## Observação importante

O Request não deve salvar nada.
Ele apenas valida e normaliza a entrada.

---

# 11. Camada de services

## 11.1. `CreateProjectService`

Responsável por:

* receber dados validados
* gerar slug se necessário
* persistir projeto
* retornar projeto criado

## 11.2. `UpdateProjectService`

Responsável por:

* aplicar atualização
* preservar consistência do slug se essa for a política
* retornar projeto atualizado

## 11.3. `CreateTaskService`

Responsável por:

* validar coerência entre projeto e perfil de ambiente, se houver
* preencher `created_by`
* persistir tarefa
* retornar tarefa criada

## 11.4. `UpdateTaskService`

Responsável por:

* atualizar dados permitidos
* manter integridade relacional
* retornar tarefa atualizada

---

# 12. Autorização

Nesta fase, você pode começar com uma política simples:

## Regra mínima

* qualquer usuário autenticado pode gerir projetos e tarefas

## Estrutura recomendada

Mesmo começando simples, já vale criar:

* `ProjectPolicy`
* `TaskPolicy`

Assim você evita acoplamento futuro.

### Exemplo de ações

* `viewAny`
* `view`
* `create`
* `update`

---

# 13. Camada web (Blade)

O frontend será Blade com Tailwind CSS 4.

Tailwind v4 introduz mudanças importantes no fluxo de configuração e instalação, com integração forte ao pipeline moderno e foco em utilitários gerados a partir do conteúdo do projeto; ele também traz considerações de compatibilidade para navegadores modernos. ([tailwindcss.com][4])

## Páginas mínimas

### Autenticação

* login

### Projetos

* listagem
* criação
* edição

### Tarefas

* listagem
* criação
* edição

## Layout base

Criar um layout principal com:

* header
* navegação lateral ou superior
* área de mensagens flash
* container principal

## Padrões de UI

* formulários padronizados por partials/components Blade
* mensagens de erro por campo
* feedback de sucesso via session flash
* tabelas simples para listagens
* filtros podem ser deixados para uma etapa seguinte

---

# 14. Camada API

## Objetivo

Expor os mesmos casos de uso do painel para consumo pelo worker futuro.

## Endpoints mínimos

### Auth

* `POST /api/tokens/create`
* opcionalmente `DELETE /api/tokens/current`

### Projects

* `GET /api/projects`
* `POST /api/projects`
* `GET /api/projects/{project}`
* `PUT/PATCH /api/projects/{project}`

### Tasks

* `GET /api/tasks`
* `POST /api/tasks`
* `GET /api/tasks/{task}`
* `PUT/PATCH /api/tasks/{task}`

## Padrão de resposta

Definir envelope consistente, por exemplo:

```json
{
  "data": { ... },
  "message": "Task created successfully."
}
```

ou

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "title": ["The title field is required."]
  }
}
```

## Observação

Usar API Resources é recomendável para padronização, especialmente porque Laravel 13 reforça recursos para APIs como parte importante do ecossistema atual. ([Laravel][1])

---

# 15. Estratégia de autenticação

## 15.1. Painel web

* sessão padrão
* CSRF
* middleware `web`
* middleware `auth`

## 15.2. API

* Sanctum token-based
* middleware `auth:sanctum`

### Fluxo sugerido para token

1. usuário faz login web normalmente no painel
2. usuário pode gerar token de API em uma tela futura, ou via endpoint autenticado por credenciais conforme a política adotada
3. worker usa esse token para consumir a API

Sanctum continua indicado para tokens simples por usuário, com abilities/scopes opcionais. ([Laravel][5])

---

# 16. Estratégia de validação compartilhada

A exigência central deste projeto é:

> web e API devem compartilhar requests e regras de negócio.

## Como atender isso corretamente

### Compartilhado

* Form Requests
* Services
* Policies
* DTOs
* Enums
* Models

### Separado

* Controllers web
* Controllers API
* Views Blade
* API Resources / Responses JSON

Essa é a fronteira correta.

---

# 17. Estratégia de testes

Todas as rotas devem possuir testes automatizados com os casos de uso.

## 17.1. Tipos de teste

### Feature tests web

Cobrem:

* autenticação obrigatória
* renderização da página
* submissão válida
* submissão inválida
* atualização válida
* atualização inválida

### Feature tests API

Cobrem:

* autenticação Sanctum
* resposta JSON esperada
* validação
* persistência correta
* atualização correta

### Unit tests opcionais

Podem cobrir:

* Services
* Enums
* regras auxiliares

Mas, para esta fase, o principal valor está nos **Feature tests**.

---

## 17.2. Casos mínimos por rota web

### Login

* usuário acessa login
* usuário autentica com credenciais válidas
* usuário falha com credenciais inválidas

### Projects index/create/store/edit/update

* guest não acessa
* usuário autenticado acessa
* store válido cria projeto
* store inválido retorna erros
* update válido atualiza
* update inválido retorna erros

### Tasks index/create/store/edit/update

* guest não acessa
* usuário autenticado acessa
* store válido cria tarefa
* store inválido retorna erros
* update válido atualiza
* update inválido retorna erros

---

## 17.3. Casos mínimos por rota API

### Token / autenticação

* criação de token válida
* criação de token inválida, se aplicável

### API Projects

* sem token retorna 401
* com token acessa index
* store válido cria
* store inválido retorna 422
* show retorna projeto
* update válido atualiza
* update inválido retorna 422

### API Tasks

* sem token retorna 401
* com token acessa index
* store válido cria
* store inválido retorna 422
* show retorna tarefa
* update válido atualiza
* update inválido retorna 422

---

## 17.4. Banco de testes

* usar database refresh
* factories para User, Project, Task
* factories também para ProjectEnvironmentProfile, se o relacionamento for obrigatório em parte dos testes

---

# 18. Estrutura de rotas

## Web

```php
Route::middleware('guest')->group(function () {
    // login
});

Route::middleware('auth')->group(function () {
    Route::resource('projects', Web\ProjectController::class)->only([
        'index', 'create', 'store', 'edit', 'update'
    ]);

    Route::resource('tasks', Web\TaskController::class)->only([
        'index', 'create', 'store', 'edit', 'update'
    ]);
});
```

## API

```php
Route::post('/tokens/create', [Api\Auth\TokenController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('projects', Api\ProjectController::class)->only([
        'index', 'store', 'show', 'update'
    ]);

    Route::apiResource('tasks', Api\TaskController::class)->only([
        'index', 'store', 'show', 'update'
    ]);
});
```

Laravel 13 continua usando os arquivos de rotas padrão e middleware groups definidos no bootstrap da aplicação. ([Laravel][3])

---

# 19. Decisões de implementação

## 19.1. Não duplicar Request entre Web e API

A mesma classe deve servir para os dois.

## 19.2. Não duplicar Service entre Web e API

O mesmo service deve servir para os dois.

## 19.3. Controllers separados

Isso preserva clareza da resposta:

* view no web
* JSON na API

## 19.4. Resource classes para API

Mesmo em fase inicial, vale a pena usar Resources.

## 19.5. Blade sem framework JS pesado

Como o requisito é Blade + Tailwind, manter renderização server-side como padrão.

---

# 20. Regras de consistência

## Para projetos

* `slug` único
* `repository_url` obrigatória
* `name` obrigatório
* `default_branch` obrigatória

## Para perfis de ambiente

* `project_id` obrigatório
* `name` obrigatório
* `slug` único por projeto
* apenas um `is_default` por projeto

## Para tarefas

* `project_id` obrigatório
* `title` obrigatória
* `description` obrigatória
* `priority` obrigatória
* `status` obrigatória ou assumida automaticamente
* `environment_profile_id`, se informado, deve pertencer ao mesmo projeto

Essa última regra é importante e deve ficar no Service ou em uma validação contextual adicional.

---

# 21. Seed inicial recomendado

Criar seeders para:

* usuário administrador inicial
* prioridades padrão, se forem enum em banco
* status padrão, se forem enum em banco
* opcionalmente um projeto exemplo

---

# 22. Estrutura de testes por arquivo

Sugestão:

```text
tests/
  Feature/
    Web/
      Auth/
        LoginTest.php
      Project/
        ListProjectsTest.php
        CreateProjectTest.php
        UpdateProjectTest.php
      Task/
        ListTasksTest.php
        CreateTaskTest.php
        UpdateTaskTest.php
    Api/
      Auth/
        CreateTokenTest.php
      Project/
        ListProjectsTest.php
        CreateProjectTest.php
        ShowProjectTest.php
        UpdateProjectTest.php
      Task/
        ListTasksTest.php
        CreateTaskTest.php
        ShowTaskTest.php
        UpdateTaskTest.php
```

---

# 23. Evolução futura prevista

Esta modelagem já deixa o sistema preparado para as próximas fases:

## Fase 2

* workers cadastrados
* claim de tarefas
* status de execução
* histórico de execuções

## Fase 3

* integração com executor
* perfil de ambiente operacional real
* tokens de worker
* logs

## Fase 4

* integrações automáticas
* criação automática de tasks
* políticas mais refinadas
* multiusuário com permissões

---

# 24. Resumo das decisões técnicas

## Stack

* Laravel 13 MVC com Blade ([Laravel][1])
* Tailwind CSS 4 no frontend ([tailwindcss.com][4])
* Sanctum para autenticação da API ([Laravel][5])

## Camadas

* Controllers Web
* Controllers API
* Form Requests compartilhados
* Services compartilhados
* Policies
* Models Eloquent
* API Resources
* Views Blade

## Entidades

* User
* Project
* ProjectEnvironmentProfile
* Task

## Critério central

* web e API compartilham regra e validação
* a diferença fica apenas na forma de resposta

## Qualidade

* testes automatizados para todas as rotas
* foco em Feature tests
* controllers finos
* services com regra de negócio

---

# 25. Recomendação final de implementação

A melhor forma de executar esta fase é nesta ordem:

1. autenticação web
2. autenticação API com Sanctum
3. modelagem das tabelas
4. models e relacionamentos
5. enums e casts
6. Form Requests
7. Services
8. controllers web e API
9. views Blade
10. API Resources
11. testes de todas as rotas
12. refinamentos de UX

[1]: https://laravel.com/docs/13.x/releases?utm_source=chatgpt.com "Release Notes | Laravel 13.x - The clean stack for Artisans ..."
[2]: https://laravel.com/docs/13.x/documentation?utm_source=chatgpt.com "Laravel 13.x - The clean stack for Artisans and agents"
[3]: https://laravel.com/docs/13.x/routing?utm_source=chatgpt.com "Routing | Laravel 13.x - The clean stack for Artisans and ..."
[4]: https://tailwindcss.com/blog/tailwindcss-v4?utm_source=chatgpt.com "Tailwind CSS v4.0"
[5]: https://laravel.com/docs/13.x/sanctum?utm_source=chatgpt.com "Laravel Sanctum - The clean stack for Artisans and agents - Laravel"

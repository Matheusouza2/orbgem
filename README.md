# Orbgem

Orbgem é uma aplicação de organização financeira pessoal para centralizar contas, transações, cartões, investimentos e planejamento em um único lugar.

<p>
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white" alt="Laravel 13">
  <img src="https://img.shields.io/badge/React-Inertia-61DAFB?logo=react&logoColor=111827" alt="React com Inertia">
  <img src="https://img.shields.io/badge/Docker-produção-2496ED?logo=docker&logoColor=white" alt="Docker">
</p>

## O que o projeto oferece

- Carteiras e contas bancárias, com instituição, conta padrão e visibilidade nos totais.
- Cartões de crédito, faturas, compras parceladas e pagamentos.
- Lançamentos financeiros, transferências, reversões e categorização.
- Transações recorrentes e compromissos financeiros.
- Metas financeiras, orçamentos e consolidações.
- Investimentos em ações, FIIs, renda fixa e investimentos vinculados a um percentual do CDI.
- Contabilização diária de rendimentos CDI usando dados da Brapi.
- Integração Open Finance com Pluggy.
- Notificações em tempo real preparadas para Laravel Reverb.
- Interface em português com ícones e identidade visual das instituições bancárias.

## Stack

### Backend

- PHP 8.4+ e Laravel 13
- MariaDB
- Redis para cache, sessão, filas e broadcasting
- Laravel Reverb para WebSockets
- Pluggy para Open Finance
- Brapi para cotações e CDI

### Frontend

- React 19
- Inertia.js
- Vite
- Tailwind CSS
- Flowbite React
- React Select e Flatpickr

## Arquitetura

O backend segue o fluxo:

```text
Route → FormRequest → Controller → DTO → UseCase → Service → Repository → Model
```

As respostas públicas são serializadas por Resources. No frontend, as telas seguem:

```text
Page → Hook → Service → Laravel API
```

Os módulos financeiros estão organizados em `resources/js/Pages/Financial`, enquanto os casos de uso, serviços e repositórios ficam separados em `app/UseCases`, `app/Services` e `app/Repositories`.

## Requisitos

- Docker e Docker Compose
- Git
- MariaDB 10.6+ acessível pela aplicação

Para executar sem Docker, também são necessários PHP, Composer, Node.js e npm compatíveis com as versões do projeto.

## Desenvolvimento local

```bash
cp .env.example .env
```

Configure no `.env` a conexão do MariaDB e as credenciais das integrações opcionais. Depois, suba o ambiente:

```bash
docker compose up -d --build
```

O Compose de desenvolvimento executa a aplicação HTTP, PHP-FPM, Nginx e Vite. A aplicação fica disponível na porta definida por `APP_PORT` — `8002` por padrão.

Para executar as migrations:

```bash
docker compose exec app php artisan migrate
```

Para acompanhar os logs:

```bash
docker compose logs -f app
```

## Variáveis de ambiente

As integrações externas são configuradas somente no backend:

```dotenv
BRAPI_BASE_URL=https://brapi.dev
BRAPI_TOKEN=

PLUGGY_BASE_URL=https://api.pluggy.ai
PLUGGY_CLIENT_ID=
PLUGGY_CLIENT_SECRET=
PLUGGY_API_KEY=
```

Nunca publique valores reais dessas variáveis no repositório.

> [!WARNING]
> O arquivo `.env` é ignorado pelo Git. Em produção, crie o arquivo diretamente no servidor e preencha também `APP_KEY`, banco externo, Redis e credenciais do Reverb.

## Produção

O ambiente de produção usa uma imagem imutável publicada no Docker Hub. O MariaDB permanece no servidor; Redis, worker, scheduler e Reverb são executados pelo Compose.

O modelo de variáveis está em [`docker/.env.production.example`](docker/.env.production.example). A configuração do ambiente está em [`docker-compose.production.yml`](docker-compose.production.yml).

No servidor, o arquivo deve existir em:

```text
/var/www/webapps/orbgem.com.br/.env
```

O workflow [`deploy.yml`](.github/workflows/deploy.yml) é executado em pushes na branch `main` e:

1. constrói o `Dockerfile.production`;
2. publica `matheusouza2/orbgem:latest` e uma tag baseada no commit;
3. conecta ao servidor por SSH;
4. atualiza o Compose;
5. faz pull da imagem e reinicia os serviços.

No primeiro uso, o servidor precisa ter Docker, Docker Compose e permissões para o usuário configurado em `SERVER_USER`. O Nginx do host deve encaminhar:

```text
seu-dominio.example    → 127.0.0.1:8000
ws.seu-dominio.example → 127.0.0.1:8080
```

Existe um exemplo de proxy em [`docker/nginx/orbgem-proxy.conf.example`](docker/nginx/orbgem-proxy.conf.example).

## Filas, scheduler e CDI

O Compose de produção mantém três processos Laravel separados:

- `queue`: processa jobs usando Redis;
- `scheduler`: executa as tarefas agendadas;
- `reverb`: mantém o servidor WebSocket disponível.

O rendimento dos investimentos vinculados ao CDI é contabilizado pelo comando:

```bash
php artisan investments:accrue-cdi
```

O scheduler executa esse processo diariamente. Se houver indisponibilidade temporária da fonte, o comando pode ser executado novamente para realizar o processamento pendente sem duplicar a mesma data.

## Qualidade e comandos úteis

Executar a suíte de testes:

```bash
php artisan test --compact
```

Formatar o código PHP:

```bash
vendor/bin/pint --dirty --format agent
```

Construir os assets:

```bash
npm run build
```

Verificar as rotas:

```bash
php artisan route:list
```

## Principais páginas

| Página | Rota |
| --- | --- |
| Dashboard financeiro | `/dashboard-financeiro` |
| Carteiras | `/carteiras` |
| Contas | `/contas` |
| Cartões de crédito | `/cartoes-de-credito` |
| Transações | `/transacoes` |
| Recorrências | `/recorrencias` |
| Categorias | `/categorias` |
| Metas | `/metas` |
| Investimentos | `/investimentos` |
| Open Finance | `/open-finance` |

## Documentação adicional

- [Manual funcional](docs/MANUAL.md)
- [Exemplo de ambiente Docker de produção](docker/.env.production.example)
- [Proxy Nginx do host](docker/nginx/orbgem-proxy.conf.example)

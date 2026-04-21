# WealthOS 💰

Plataforma de inteligencia financiera personal — Laravel 11 + Vue 3 + Groq AI

## Stack técnico
- **Backend**: Laravel 11, PHP 8.3, MySQL 8, Redis
- **Frontend**: Vue 3, Vite, Pinia, Tailwind CSS, Chart.js
- **IA**: Groq API (llama-3.3-70b-versatile)
- **APIs externas**: CoinGecko, Yahoo Finance, CMF Chile
- **Infra**: Docker, Nginx, PHP-FPM

## Requisitos previos
- Docker Desktop instalado y en ejecución
- Git

## Instalación en 5 pasos

### 1. Clonar y configurar variables de entorno

```bash
git clone <repo-url> wealthos
cd wealthos

# Copiar el .env de ejemplo
cp backend/.env.example backend/.env
```

Edita `backend/.env` y completa:
```
GROQ_API_KEY=tu_clave_groq       # Obtén en: https://console.groq.com
CMF_API_KEY=tu_clave_cmf         # Obtén gratis en: https://api.cmfchile.cl
```

### 2. Levantar los contenedores Docker

```bash
docker-compose up -d
```

Esto levanta: `app` (PHP-FPM), `nginx` (puerto 8000), `mysql`, `redis`, `frontend` (puerto 5173).

### 3. Instalar dependencias y configurar Laravel

```bash
# Instalar dependencias PHP
docker exec wealthos_app composer install

# Generar clave de aplicación
docker exec wealthos_app php artisan key:generate

# Ejecutar migraciones
docker exec wealthos_app php artisan migrate

# Sembrar datos de demo (usuario alex@wealthos.cl / password)
docker exec wealthos_app php artisan db:seed
```

### 4. Acceder a la aplicación

| Servicio | URL |
|----------|-----|
| **Frontend (Vue)** | http://localhost:5173 |
| **API (Laravel)** | http://localhost:8000/api |

### 5. Credenciales de demo

```
Email:    alex@wealthos.cl
Password: password
```

---

## Estructura del proyecto

```
wealthos/
├── backend/                    # Laravel 11 API
│   ├── app/
│   │   ├── Http/Controllers/Api/
│   │   │   ├── AuthController.php      # Registro, login, logout
│   │   │   ├── PortfolioController.php # CRUD activos + precios en tiempo real
│   │   │   ├── TransactionController.php # CRUD transacciones + estadísticas
│   │   │   ├── GoalController.php      # CRUD metas financieras
│   │   │   ├── SimulatorController.php # Proyecciones financieras
│   │   │   └── AdvisorController.php   # Chat con IA Groq
│   │   ├── Models/             # User, Asset, Transaction, Goal, AdvisorSession
│   │   └── Services/
│   │       ├── PriceService.php    # Yahoo Finance + CoinGecko + CMF Chile
│   │       ├── GroqService.php     # Cliente Groq API + contexto financiero
│   │       └── PortfolioService.php # Cálculos, simulaciones, resúmenes
│   ├── database/migrations/    # 6 migraciones
│   └── routes/api.php          # Todas las rutas API protegidas con Sanctum
│
├── frontend/                   # Vue 3 + Vite
│   └── src/
│       ├── views/
│       │   ├── Dashboard.vue       # Patrimonio + balance + metas + acceso IA
│       │   ├── Portfolio.vue       # Tabla de activos con precios en tiempo real
│       │   ├── Transactions.vue    # Historial + estadísticas por categoría
│       │   ├── Goals.vue           # Metas con barra de progreso y aportes
│       │   ├── Simulator.vue       # Sliders + gráfico Chart.js comparativo
│       │   └── Advisor.vue         # Chat IA con historial de sesiones
│       ├── stores/             # Pinia (auth, portfolio, transactions, goals)
│       └── services/api.js     # Cliente Axios configurado con Sanctum
│
└── docker/                     # Configuración de contenedores
    ├── nginx/default.conf
    ├── php/Dockerfile
    └── mysql/my.cnf
```

## API Reference

### Autenticación
```
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout       (auth)
GET    /api/auth/me           (auth)
```

### Portafolio
```
GET    /api/portfolio/summary   (auth) — Patrimonio total con precios actuales
GET    /api/portfolio           (auth) — Lista de activos
POST   /api/portfolio           (auth) — Agregar activo
PUT    /api/portfolio/{id}      (auth) — Actualizar activo
DELETE /api/portfolio/{id}      (auth) — Eliminar activo
POST   /api/portfolio/{id}/refresh (auth) — Refrescar precio
```

### Transacciones
```
GET    /api/transactions           (auth) — Lista con filtros
POST   /api/transactions           (auth) — Crear transacción
GET    /api/transactions/summary   (auth) — Resumen mensual
GET    /api/transactions/breakdown (auth) — Gastos por categoría
GET    /api/transactions/history   (auth) — Historial mensual
```

### Metas
```
GET    /api/goals                      (auth)
POST   /api/goals                      (auth)
PUT    /api/goals/{id}                 (auth)
POST   /api/goals/{id}/contribution    (auth) — Registrar aporte
```

### Simulador
```
GET    /api/simulator/instruments      (auth) — Instrumentos disponibles
POST   /api/simulator/simulate         (auth) — Simular un instrumento
POST   /api/simulator/compare          (auth) — Comparar hasta 3 instrumentos
```

### Asesor IA
```
GET    /api/advisor/sessions                  (auth)
POST   /api/advisor/sessions/quick-start      (auth) — Nueva sesión con bienvenida
POST   /api/advisor/sessions/{id}/message     (auth) — Enviar mensaje
```

## Comandos útiles

```bash
# Ver logs en tiempo real
docker-compose logs -f app

# Acceder al contenedor PHP
docker exec -it wealthos_app bash

# Ejecutar artisan
docker exec wealthos_app php artisan <comando>

# Limpiar caché de Redis
docker exec wealthos_redis redis-cli -a redis_password FLUSHDB

# Reconstruir contenedores
docker-compose down && docker-compose up -d --build
```

## Variables de entorno importantes

| Variable | Descripción | Obligatoria |
|----------|-------------|-------------|
| `GROQ_API_KEY` | Clave API de Groq (consola.groq.com) | ✅ Sí |
| `CMF_API_KEY` | Clave CMF Chile para valor UF | Recomendada |
| `YAHOO_FINANCE_SERVICE_URL` | URL microservicio Python Yahoo Finance | No |

## Módulos implementados

| Módulo | Estado |
|--------|--------|
| 🔐 Autenticación (Sanctum) | ✅ Completo |
| 💼 Portafolio unificado | ✅ Completo |
| 💸 Control de gastos/ingresos | ✅ Completo |
| 🎯 Metas financieras | ✅ Completo |
| 📈 Simulador de escenarios | ✅ Completo |
| 🤖 Asesor IA (Groq) | ✅ Completo |

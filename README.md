# WealthOS 💰

Plataforma de inteligencia financiera personal — Laravel 11 + Vue 3 + Groq AI

---

## ¿Qué es WealthOS?

WealthOS es una aplicación web de finanzas personales diseñada para darte una visión completa e inteligente de tu situación financiera. No es solo un registro de gastos — es una plataforma integrada que conecta tus inversiones, tus ingresos, tus metas y un asesor con inteligencia artificial que conoce tus datos reales.

### Lo que puedes hacer

**💼 Portafolio de inversiones**
Registra acciones (chilenas e internacionales), criptomonedas, fondos mutuos, ETFs, depósitos a plazo y más. WealthOS obtiene los precios actuales automáticamente desde CoinGecko, Yahoo Finance y la CMF de Chile, y calcula tu rentabilidad, ganancia/pérdida y patrimonio total en pesos chilenos y dólares en tiempo real.

**💸 Control de ingresos y gastos**
Registra cada transacción por categoría (alimentación, transporte, vivienda, salud, etc.). Visualiza cuánto gastas por categoría con gráficos de dona, compara ingresos vs gastos de los últimos 6 meses con gráficos de barras y mide tu tasa de ahorro mensual automáticamente.

**🎯 Metas financieras**
Crea metas con nombre, monto objetivo y fecha límite. WealthOS calcula automáticamente cuánto necesitas ahorrar por mes para llegar a tiempo, muestra el porcentaje de progreso y te dice si vas al día o si estás en riesgo de no cumplirla.

**📈 Simulador de inversiones**
Compara hasta 3 instrumentos financieros distintos lado a lado. Ajusta el monto inicial, el aporte mensual y el plazo con sliders interactivos y visualiza en un gráfico la proyección de crecimiento de cada opción para tomar decisiones más informadas.

**🤖 Asesor financiero con IA**
Un chat con inteligencia artificial (modelo llama-3.3-70b de Groq) que tiene acceso a tu información financiera real: tu patrimonio, tus gastos del mes, tus metas y tu portafolio. Puedes consultarle si estás ahorrando bien, pedirle que analice tu situación o preguntarle sobre estrategias de inversión. Guarda el historial de conversaciones por sesión.

**👫 Espacio compartido para parejas o familia**
Crea un espacio compartido donde dos o más personas pueden registrar ingresos y gastos en común, ver el resumen del mes (ingresos, gastos, ahorro compartido) y trabajar juntos hacia metas financieras en equipo. Cada miembro mantiene sus finanzas personales completamente separadas. Se invita por email y si la persona ya tiene cuenta se une automáticamente.

### ¿Para quién es?

- Personas que quieren tener todo su dinero ordenado en un solo lugar
- Parejas que manejan gastos compartidos pero también finanzas individuales
- Quienes invierten en acciones, cripto o fondos y quieren ver su rentabilidad real
- Cualquiera que quiera entender su situación financiera con datos reales y no solo planillas de Excel

---

## Stack técnico
- **Backend**: Laravel 11, PHP 8.3, MySQL 8, Redis
- **Frontend**: Vue 3, Vite, Pinia, Tailwind CSS, Chart.js
- **IA**: Groq API (llama-3.3-70b-versatile)
- **APIs externas**: CoinGecko, Yahoo Finance, CMF Chile
- **Infra**: Docker, Nginx, PHP-FPM

## Módulos implementados

| Módulo | Estado |
|--------|--------|
| 🔐 Autenticación (Sanctum) | ✅ Completo |
| 💼 Portafolio unificado | ✅ Completo |
| 💸 Control de gastos/ingresos | ✅ Completo |
| 🎯 Metas financieras | ✅ Completo |
| 📈 Simulador de escenarios | ✅ Completo |
| 🤖 Asesor IA (Groq) | ✅ Completo |
| 👫 Espacio compartido (pareja/familia) | ✅ Completo |

---

## Requisitos previos
- Docker Desktop instalado y en ejecución
- Git

## Instalación en 5 pasos

### 1. Clonar y configurar variables de entorno

```bash
git clone https://github.com/AlexST360/wealthos.git wealthos
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
docker compose up -d
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
```

### 4. Acceder a la aplicación

| Servicio | URL |
|----------|-----|
| **Frontend (Vue)** | http://localhost:5173 |
| **API (Laravel)** | http://localhost:8000/api |

---

## Estructura del proyecto

```
wealthos/
├── backend/                    # Laravel 11 API
│   ├── app/
│   │   ├── Http/Controllers/Api/
│   │   │   ├── AuthController.php        # Registro, login, logout
│   │   │   ├── PortfolioController.php   # CRUD activos + precios en tiempo real
│   │   │   ├── TransactionController.php # CRUD transacciones + estadísticas
│   │   │   ├── GoalController.php        # CRUD metas financieras
│   │   │   ├── SimulatorController.php   # Proyecciones financieras
│   │   │   ├── AdvisorController.php     # Chat con IA Groq
│   │   │   └── SharedSpaceController.php # Espacio compartido pareja/familia
│   │   ├── Models/
│   │   └── Services/
│   │       ├── PriceService.php     # Yahoo Finance + CoinGecko + CMF Chile
│   │       ├── GroqService.php      # Cliente Groq API + contexto financiero
│   │       └── PortfolioService.php # Cálculos, simulaciones, resúmenes
│   ├── database/migrations/
│   └── routes/api.php
│
├── frontend/                   # Vue 3 + Vite
│   └── src/
│       ├── views/
│       │   ├── Dashboard.vue       # Patrimonio + balance + metas + acceso IA
│       │   ├── Portfolio.vue       # Tabla de activos con precios en tiempo real
│       │   ├── Transactions.vue    # Historial + estadísticas por categoría
│       │   ├── Goals.vue           # Metas con barra de progreso y aportes
│       │   ├── Simulator.vue       # Sliders + gráfico Chart.js comparativo
│       │   ├── Advisor.vue         # Chat IA con historial de sesiones
│       │   └── Shared.vue          # Espacio compartido pareja/familia
│       ├── stores/             # Pinia (auth, portfolio, transactions, goals)
│       └── services/api.js     # Cliente Axios configurado con Sanctum
│
└── docker/                     # Configuración de contenedores
    ├── nginx/default.conf
    ├── php/Dockerfile
    └── mysql/my.cnf
```

## Variables de entorno importantes

| Variable | Descripción | Obligatoria |
|----------|-------------|-------------|
| `GROQ_API_KEY` | Clave API de Groq (console.groq.com) | ✅ Sí |
| `CMF_API_KEY` | Clave CMF Chile para valor UF | Recomendada |

## Comandos útiles

```bash
# Ver logs en tiempo real
docker compose logs -f app

# Acceder al contenedor PHP
docker exec -it wealthos_app bash

# Ejecutar artisan
docker exec wealthos_app php artisan <comando>

# Limpiar caché de Redis
docker exec wealthos_redis redis-cli -a redis_password FLUSHDB

# Reconstruir contenedores
docker compose down && docker compose up -d --build
```

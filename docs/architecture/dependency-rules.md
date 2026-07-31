# MVBotsHub

# Правила зависимостей

**Документ:** `docs/architecture/dependency-rules.md`

**Версия:** 1.0

**Статус:** Draft

---

# Назначение

Настоящий документ определяет допустимые зависимости между архитектурными слоями MVBotsHub.

Все зависимости внутри проекта ДОЛЖНЫ соответствовать данным правилам.

---

# Общая схема зависимостей

```text
                Presentation
                       │
                       ▼
                Application
                       │
                       ▼
                   Domain
                       ▲
                       │
               Infrastructure
```

Стрелка означает допустимую зависимость.

Обратные зависимости НЕ ДОПУСКАЮТСЯ.

---

# Presentation

Presentation МОЖЕТ зависеть от:

- Application;
- DTO;
- Laravel;
- MoonShine;
- HTTP;
- Validation;
- Policies.

Presentation НЕ ДОЛЖЕН зависеть от:

- Infrastructure;
- Eloquent Models (за исключением административной панели);
- Telegraph.

---

# Application

Application МОЖЕТ зависеть от:

- Domain;
- Repository Interfaces;
- DTO;
- Laravel Transactions;
- Queue;
- Events.

Application НЕ ДОЛЖЕН зависеть от:

- MoonShine;
- HTTP;
- Controllers;
- Telegraph;
- SQL;
- Redis.

---

# Domain

Domain МОЖЕТ зависеть только от:

- PHP;
- собственных модулей Domain.

Domain НЕ ДОЛЖЕН зависеть от:

- Laravel;
- Eloquent;
- MoonShine;
- Redis;
- PostgreSQL;
- HTTP;
- Telegraph;
- Vue;
- Console.

---

# Infrastructure

Infrastructure МОЖЕТ зависеть от:

- Domain;
- Laravel;
- Eloquent;
- Redis;
- PostgreSQL;
- Telegraph;
- файловой системы;
- внешних API.

Infrastructure НЕ ДОЛЖЕН зависеть от:

- Presentation.

---

# Models

Каталог `app/Models` содержит Eloquent-модели.

Использование моделей допускается:

- в Infrastructure;
- в MoonShine;
- в Policies;
- в некоторых компонентах Presentation, если этого требует Laravel.

Использование моделей внутри Domain НЕ ДОПУСКАЕТСЯ.

---

# Repository

Repository Interface определяется в Domain.

Реализация Repository располагается в Infrastructure.

Application работает только с интерфейсом Repository.

```text
Application

↓

BotRepository

↓

Infrastructure

↓

Eloquent Model
```

---

# MoonShine

MoonShine является частью Presentation.

MoonShine МОЖЕТ:

- использовать Models;
- использовать Application;
- использовать Policies.

MoonShine НЕ ДОЛЖЕН:

- реализовывать бизнес-логику;
- выполнять сложные SQL-запросы;
- обращаться напрямую к Telegram API.

---

# Jobs

Jobs относятся к Application.

Job МОЖЕТ:

- вызывать Application Services;
- использовать Repository;
- публиковать события.

Job НЕ ДОЛЖЕН:

- содержать бизнес-правила;
- обращаться к MoonShine.

---

# Events

Domain Events создаются внутри Domain.

Laravel Events создаются в Application.

Infrastructure может подписываться на оба типа событий.

---

# Запрещённые зависимости

Следующие зависимости запрещены.

❌ Domain → Laravel

❌ Domain → Eloquent

❌ Domain → Redis

❌ Domain → Telegraph

❌ Domain → PostgreSQL

❌ Domain → MoonShine

❌ Application → MoonShine

❌ Application → HTTP Controllers

❌ Infrastructure → Presentation

---

# Допустимые зависимости

```text
Presentation
        │
        ▼
Application
        │
        ▼
Domain
        ▲
        │
Infrastructure
```

---

# Проверка архитектуры

При добавлении новой функциональности рекомендуется последовательно ответить на следующие вопросы.

1. Где должна располагаться новая логика?

2. От какого слоя она зависит?

3. Нарушает ли новая зависимость настоящие правила?

Если ответ на третий вопрос положительный, архитектурное решение должно быть пересмотрено.

---

# Связанные документы

- principles.md
- architecture/overview.md
- architecture/layers.md
- domain/README.md

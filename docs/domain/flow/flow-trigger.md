# FlowTrigger

**Документ:** `docs/domain/flow/flow-trigger.md`

**Версия:** 1.0

**Статус:** Актуален

---

# Назначение

`FlowTrigger` определяет условие, при котором должен быть запущен конкретный `Flow`.

Каждый `FlowTrigger` принадлежит одному `Flow`.

Один `Flow` может иметь любое количество Trigger.

`FlowTrigger` описывает точку входа в сценарий, но не отвечает за его выполнение.

---

# Ответственность

FlowTrigger отвечает за:

- описание точки входа в Flow;
- определение типа входящего события;
- хранение параметров сопоставления;
- определение приоритета;
- включение или отключение конкретного Trigger.

FlowTrigger не отвечает за:

- выполнение Flow;
- создание FlowSession;
- выполнение узлов;
- выбор следующего узла;
- обработку пользовательских данных;
- отправку сообщений.

---

# Основные свойства

| Поле | Описание |
|------|----------|
| id | Внутренний идентификатор |
| flow_id | Flow, который запускается |
| type | Публичный идентификатор типа Trigger |
| config | Конфигурация Trigger |
| priority | Приоритет обработки |
| enabled | Признак участия в маршрутизации |
| created_at | Дата создания |
| updated_at | Дата изменения |

---

# Один Flow — множество Trigger

Один сценарий может иметь несколько точек входа.

Например:

```text
Registration

├── telegram.command /start
├── telegram.deep_link registration
├── telegram.qr registration
└── system.manual
```

Все Trigger указывают на один и тот же Flow.

---

# Тип Trigger

Тип Trigger хранится в поле `type`.

`type` является строковым публичным идентификатором и должен иметь пространство имён.

Например:

```text
telegram.command
telegram.deep_link
telegram.callback
telegram.keyword
telegram.contact

system.manual
system.internal
system.scheduler

api.webhook
```

Использование пространства имён позволяет добавлять новые типы без изменения общей модели Trigger.

---

# TriggerRegistry

Для разрешения типа Trigger используется `TriggerRegistry`.

Registry сопоставляет значение `type` с обработчиком Trigger.

```text
FlowTrigger.type

        │

        ▼

TriggerRegistry

        │

        ▼

TriggerHandler
```

Например:

```text
telegram.command
        ↓
TelegramCommandTriggerHandler
```

```text
telegram.deep_link
        ↓
TelegramDeepLinkTriggerHandler
```

```text
system.manual
        ↓
ManualTriggerHandler
```

---

# TriggerHandler

Каждый тип Trigger имеет собственный обработчик.

TriggerHandler отвечает за проверку, соответствует ли входящее событие конкретному Trigger.

Обработчик не запускает Flow.

Он только отвечает на вопрос:

> Подходит ли это событие для данного Trigger?

---

# Маршрутизация

Общая последовательность выглядит следующим образом:

```text
Incoming Event
      │
      ▼
FlowRouter
      │
      ▼
TriggerRegistry
      │
      ▼
TriggerHandler
      │
      ▼
Matched FlowTrigger
      │
      ▼
Flow
      │
      ▼
FlowSession
```

`FlowRouter` отвечает за маршрутизацию.

`TriggerRegistry` отвечает за поиск обработчика.

`TriggerHandler` отвечает за проверку соответствия.

`Flow` и `FlowSession` отвечают за последующую работу сценария.

---

# Примеры типов Trigger

## `telegram.command`

Запуск по команде Telegram.

```json
{
    "command": "start"
}
```

Примеры:

```text
/start
/help
/settings
```

---

## `telegram.deep_link`

Запуск по параметру Deep Link.

```json
{
    "payload": "registration"
}
```

---

## `telegram.callback`

Запуск по Callback Query.

```json
{
    "value": "open_help"
}
```

---

## `telegram.keyword`

Запуск по текстовому совпадению.

```json
{
    "value": "помощь",
    "match": "exact"
}
```

Тип сопоставления определяется конфигурацией.

---

## `telegram.contact`

Запуск при получении контакта.

Может использоваться для первичной регистрации Person и BotMember.

---

## `system.manual`

Запуск вручную из административного интерфейса или другой системной операции.

---

## `system.internal`

Внутренний запуск системой.

Такой Trigger не предназначен для прямого взаимодействия пользователя с ботом.

---

## `api.webhook`

Запуск через внешний API или webhook.

---

# Конфигурация

Поле `config` содержит параметры конкретного Trigger.

Пример:

```json
{
    "command": "start"
}
```

или:

```json
{
    "payload": "sale"
}
```

или:

```json
{
    "value": "help",
    "match": "exact"
}
```

Структура `config` зависит от `type`.

---

# Приоритет

Если несколько Trigger одного Bot подходят под одно событие, используется `priority`.

Чем меньше значение, тем выше приоритет.

Например:

| Trigger | Priority |
|---------|---------:|
| telegram.deep_link | 10 |
| telegram.command | 20 |
| telegram.keyword | 30 |

При равном приоритете порядок выбора должен быть детерминированным.

---

# Включение и отключение

Trigger может быть временно отключён.

Если `enabled = false`, Trigger:

- не участвует в маршрутизации;
- не считается подходящим;
- не запускает новые FlowSession.

Сам Flow при этом продолжает существовать.

---

# Несколько подходящих Trigger

Один входящий Event может соответствовать нескольким Trigger.

`FlowRouter` должен:

1. найти все подходящие активные Trigger;
2. определить их приоритет;
3. применить детерминированное правило выбора;
4. выбрать один конечный Flow для запуска.

Порядок выбора не должен зависеть от случайного порядка записей в базе данных.

---

# Вызов Flow из другого Flow

При использовании `FlowCall` внешний Trigger не требуется.

Переход между Flow выполняется непосредственно механизмом Flow Engine.

```text
Flow A

↓

FlowCall

↓

Flow B
```

`FlowTrigger` используется для входа в Flow из внешнего события или системного запуска, а не для обычного перехода между сценариями.

---

# Инварианты

Всегда должны соблюдаться следующие правила.

- Trigger принадлежит одному Flow.
- Один Flow может иметь любое количество Trigger.
- Trigger не выполняет Flow.
- Trigger не изменяет состояние FlowSession.
- `type` является публичным строковым идентификатором.
- Каждый поддерживаемый тип Trigger должен иметь зарегистрированный обработчик.
- Отключённый Trigger не участвует в маршрутизации.
- Выбор Trigger при конфликте должен быть детерминированным.
- Trigger не содержит внутренних идентификаторов БД в конфигурации, если для этого нет специальной необходимости.

---

# Расширяемость

Добавление нового типа Trigger должно выполняться без изменения существующей модели `FlowTrigger`.

Для добавления нового Trigger необходимо:

1. определить новый `type`;
2. создать соответствующий `TriggerHandler`;
3. зарегистрировать Handler в `TriggerRegistry`;
4. определить формат `config`;
5. добавить поддержку типа в интерфейсе редактора.

Например:

```text
telegram.location
        ↓
TelegramLocationTriggerHandler
```

или:

```text
system.schedule
        ↓
ScheduleTriggerHandler
```

---

# Будущие возможности

Архитектура допускает добавление:

- расписаний;
- webhook;
- внешних событий;
- CRM-событий;
- платежных событий;
- событий других каналов связи;
- WhatsApp;
- Web Chat;
- email;
- SMS.

Новый тип Trigger не должен требовать изменения агрегата `Flow`.

---

# Связанные документы

- README.md
- flow.md
- flow-router.md
- flow-session.md
- flow-engine.md

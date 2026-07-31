# NodeHandler

**Документ:** `docs/domain/flow/node-handler.md`

**Версия:** 1.0

**Статус:** Актуален

---

# Назначение

`NodeHandler` — обработчик конкретного типа узла (`Node`) во время выполнения `Flow`.

Структура Node хранится внутри JSON-графа `FlowDraft` или `FlowVersion`.

Сам Node является данными сценария.

`NodeHandler` содержит логику выполнения этого Node.

Основной принцип:

```text
Node
    ↓
данные

NodeHandler
    ↓
логика выполнения
```

Такое разделение позволяет изменять и расширять набор типов Node без изменения структуры `Flow`, `FlowVersion` и `FlowEngine`.

---

# Место в архитектуре

NodeHandler является частью Runtime-части подсистемы Flow.

```text
FlowVersion
      │
      ▼
FlowSession
      │
      ▼
FlowEngine
      │
      ▼
Node
      │
      ▼
NodeHandlerRegistry
      │
      ▼
NodeHandler
      │
      ▼
NodeResult
      │
      ▼
Application
```

---

# Ответственность

NodeHandler отвечает за:

- выполнение конкретного типа Node;
- чтение параметров Node;
- использование `RuntimeContext`;
- обработку входного события;
- проверку runtime-параметров, необходимых для выполнения;
- формирование результата выполнения;
- формирование Commands;
- определение результата перехода внутри текущего Node.

NodeHandler НЕ отвечает за:

- выбор текущего Node;
- поиск Node в графе;
- выбор `FlowVersion`;
- маршрутизацию внешних событий;
- создание FlowSession;
- сохранение FlowSession;
- публикацию Flow;
- компиляцию Draft;
- прямое взаимодействие с Telegram;
- прямое взаимодействие с Redis;
- прямое взаимодействие с PostgreSQL;
- прямое управление очередями.

---

# Node

Node является элементом JSON-графа.

Минимальная структура Node:

```json
{
    "id": "welcome",
    "type": "message",
    "settings": {
        "text": "Здравствуйте!"
    }
}
```

Основные свойства:

| Свойство | Назначение |
|----------|------------|
| `id` | Стабильный идентификатор Node внутри графа |
| `type` | Тип Node |
| `settings` | Параметры конкретного типа Node |

Дополнительные поля могут использоваться редактором и Flow Engine, если это предусмотрено форматом `flow-json`.

---

# Node.type

`type` является публичным идентификатором типа Node.

Примеры:

```text
message
condition
input.text
input.contact
delay
flow.call
variable.set
variable.increment
http.request
```

Типы Node должны иметь единый формат именования и не должны зависеть от конкретной реализации PHP-класса.

Например:

```text
message
```

не должен быть связан с именем:

```text
MessageNodeHandler
```

через жёстко заданную конвенцию именования.

Связь выполняется через `NodeHandlerRegistry`.

---

# NodeHandlerRegistry

`NodeHandlerRegistry` сопоставляет `Node.type` с соответствующим NodeHandler.

```text
Node.type

↓

NodeHandlerRegistry

↓

NodeHandler
```

Пример:

```text
message
    ↓
MessageNodeHandler
```

```text
condition
    ↓
ConditionNodeHandler
```

```text
flow.call
    ↓
FlowCallNodeHandler
```

---

# Требования к Registry

`NodeHandlerRegistry` ДОЛЖЕН:

- возвращать один зарегистрированный Handler для поддерживаемого `type`;
- позволять регистрировать новые типы Node без изменения `FlowEngine`;
- однозначно определять Handler;
- сообщать об отсутствии обработчика;
- не создавать обработчик на основании произвольного пользовательского ввода.

Если для опубликованного Node отсутствует Handler, выполнение сценария должно завершиться контролируемой runtime-ошибкой.

---

# Контракт NodeHandler

Все NodeHandler используют единый концептуальный контракт.

```php
interface NodeHandler
{
    public function type(): string;

    public function handle(
        Node $node,
        RuntimeContext $context,
    ): NodeResult;
}
```

Конкретная реализация PHP-контракта может уточняться во время разработки, но ответственность должна оставаться неизменной:

```text
Node
+
RuntimeContext
+
Incoming Event

↓

NodeHandler

↓

NodeResult
```

---

# RuntimeContext

NodeHandler получает `RuntimeContext`.

Контекст предоставляет:

- Bot;
- Person;
- BotMember;
- Conversation;
- Flow;
- FlowVersion;
- FlowSession;
- FlowStack;
- переменные;
- эффективный язык;
- входное событие;
- технический контекст текущего выполнения.

NodeHandler НЕ ДОЛЖЕН самостоятельно искать эти объекты через Eloquent или Repository.

---

# Доступ к данным

NodeHandler работает с данными через RuntimeContext и специализированные контракты Application/Domain.

Например:

```text
RuntimeContext
    ↓
current language
```

или:

```text
RuntimeContext
    ↓
session variables
```

Прямой доступ к инфраструктуре запрещён.

---

# NodeResult

NodeHandler возвращает структурированный `NodeResult`.

Результат определяет, что FlowEngine должен сделать после обработки Node.

Основные типы результата:

```text
Continue
Wait
Pause
Complete
Cancel
Fail
```

---

# Continue

Node успешно выполнен.

FlowEngine может продолжить выполнение.

Например:

```text
MessageNode

↓

Continue

↓

Next Node
```

Результат может содержать:

- Commands;
- переход к следующему Node;
- изменения состояния;
- переход в другой Flow через `FlowCall`.

---

# Wait

Node требует ожидания внешнего события.

Например:

```text
input.text
```

или:

```text
input.contact
```

Результат содержит описание ожидаемого события.

После этого:

```text
FlowSession

↓

Waiting
```

Выполнение продолжится после получения подходящего события.

---

# Pause

Выполнение временно приостанавливается.

Используется, когда продолжение Flow должно произойти позже, но текущая операция не является завершением сценария.

---

# Complete

Текущий Flow завершён.

Если выполнялся дочерний Flow через `FlowCall`, FlowEngine выполняет возврат к предыдущему Frame.

Если текущий Frame является корневым, FlowSession переходит в:

```text
Completed
```

---

# Cancel

Текущее выполнение отменяется.

FlowSession переходит в:

```text
Cancelled
```

Причина отмены должна быть сохранена в рамках состояния выполнения и доступна для аудита.

---

# Fail

Произошла необрабатываемая ошибка.

FlowSession переходит в:

```text
Failed
```

Результат должен содержать безопасную информацию, необходимую для диагностики.

Чувствительные данные не должны попадать в диагностические сообщения.

---

# Commands

NodeHandler не выполняет внешние операции непосредственно.

Вместо этого он формирует Commands.

Примеры:

```text
SendMessageCommand
SaveSessionVariableCommand
SaveMemberVariableCommand
CallFlowCommand
ScheduleResumeCommand
HttpRequestCommand
```

Общая схема:

```text
NodeHandler

↓

Commands

↓

Application

↓

Infrastructure
```

---

# SendMessageCommand

Например, `MessageNodeHandler` не вызывает Telegram.

Он создаёт:

```text
SendMessageCommand
```

После этого:

```text
SendMessageCommand
    ↓
Application
    ↓
Transport Gateway
    ↓
Telegram
```

NodeHandler не знает, каким транспортом будет доставлено сообщение.

---

# SaveSessionVariableCommand

Используется для сохранения переменной текущей FlowSession.

Например:

```text
selected_product
verification_code
attempt_count
```

Такие переменные относятся только к текущему выполнению.

---

# SaveMemberVariableCommand

Используется для сохранения постоянных значений BotMember.

Например:

```text
customer_name
email
city
bonus
```

Такие данные могут сохраняться после завершения FlowSession.

---

# CallFlowCommand

Используется для перехода к другому Flow.

Например:

```text
Main Menu

↓

FlowCall

↓

Checkout
```

FlowEngine управляет `FlowStack`, а фактическое создание и изменение состояния выполнения выполняется через Application.

---

# ScheduleResumeCommand

Используется для операций, которые должны продолжиться позже.

Например:

```text
Delay
```

или:

```text
Wait Until
```

NodeHandler не удерживает PHP-процесс до момента продолжения.

---

# Работа с Telegram

NodeHandler НЕ ДОЛЖЕН обращаться напрямую к Telegraph или Telegram API.

Запрещено:

```php
Telegraph::sendMessage(...);
```

Вместо этого:

```text
NodeHandler

↓

SendMessageCommand

↓

Application

↓

TelegramGateway

↓

Telegraph
```

Таким образом Flow Engine остаётся независимым от Telegram.

---

# Работа с другими транспортами

Та же модель используется для будущих каналов:

```text
Telegram
WhatsApp
Web Chat
Email
SMS
```

NodeHandler формирует доменное действие, а Application/Infrastructure выбирают способ доставки.

---

# Работа с HTTP и внешними API

NodeHandler не выполняет HTTP-запрос непосредственно.

Например:

```text
HttpRequestNodeHandler

↓

HttpRequestCommand

↓

Application

↓

Infrastructure

↓

External API
```

Это сохраняет транспортную независимость Domain.

---

# Локализация

NodeHandler не определяет язык пользователя самостоятельно.

Он получает эффективный язык из:

```text
RuntimeContext
```

Например:

```text
RuntimeContext.language
```

Выбор эффективного языка уже выполнен механизмом настроек:

```text
BotMemberPreferences
        ↓
BotSettings
        ↓
Effective Language
        ↓
RuntimeContext
```

---

# Работа с переводами

Если Node содержит локализуемый текст, Handler использует уже разрешённое значение языка и передаёт необходимый текст или ключ в соответствующую Command.

NodeHandler не должен самостоятельно обходить все доступные языки.

---

# Валидация Node

Необходимо разделять две проверки.

## FlowCompiler

Проверяет сценарий **до публикации**.

Например:

- обязательные параметры Node;
- корректность ссылок;
- существование целевых Flow;
- корректность структуры графа;
- совместимость типов данных.

---

## NodeHandler

Проверяет **runtime-предусловия**, которые могут зависеть от конкретного исполнения.

Например:

- входные данные текущего события;
- наличие runtime-переменной;
- доступность необходимого значения;
- допустимость текущего состояния.

Таким образом:

```text
FlowCompiler
    ↓
готовность к публикации

NodeHandler
    ↓
корректность конкретного выполнения
```

---

# Синхронные Handler

Синхронный Handler выполняет операцию непосредственно в текущем цикле исполнения.

Примеры:

```text
condition
variable.set
variable.increment
goto
```

После выполнения возвращается `Continue`.

---

# Асинхронные Handler

Асинхронный Handler не должен удерживать процесс выполнения.

Примеры:

```text
input.text
delay
http.request
payment.wait
ai.request
```

Такие Handler обычно возвращают:

```text
Wait
```

или формируют Command, который запускает дальнейшую работу асинхронно.

---

# Идемпотентность

NodeHandler ДОЛЖЕН учитывать возможность повторного выполнения одного и того же входящего события.

Это особенно важно для:

```text
Callback
Contact
Payment
Webhook
Message
```

Повторная доставка события не должна приводить к:

- повторной отправке сообщения;
- повторному списанию средств;
- повторному изменению состояния;
- повторному вызову внешней операции,

если исходная операция уже была успешно зафиксирована.

Механизм идемпотентности реализуется на уровне Application/Infrastructure, а Handler должен предоставлять необходимые идентификаторы операции.

---

# Ошибки

Ошибки выполнения делятся на две категории.

## Ожидаемые

Являются частью нормальной работы Flow.

Например:

```text
неверный ввод пользователя
```

или:

```text
условие не выполнено
```

Такие ситуации не обязательно приводят к `Fail`.

---

## Системные

Например:

```text
Handler не найден
Node повреждён
невалидная runtime-конфигурация
ошибка внутренней интеграции
```

Такие ошибки должны обрабатываться контролируемо.

При невозможности восстановления:

```text
NodeHandler

↓

Fail

↓

FlowSession = Failed
```

---

# Handler не должен изменять FlowVersion

`FlowVersion` является неизменяемой опубликованной структурой.

NodeHandler может только читать Node из неё.

```text
FlowVersion
    │
    │ read-only
    ▼
NodeHandler
```

Изменение сценария выполняется исключительно через:

```text
FlowDraft
    ↓
FlowCompiler
    ↓
FlowVersion
```

---

# Производительность

NodeHandler должен быть небольшим и предсказуемым.

Он не должен:

- загружать всю историю Conversation;
- выполнять одинаковые запросы несколько раз;
- хранить состояние между вызовами;
- удерживать инфраструктурные подключения;
- выполнять длительные блокирующие операции;
- загружать данные, не относящиеся к текущему Node.

Тяжёлые операции должны передаваться Application/Queue.

---

# Расширение новыми Node

Добавление нового типа Node должно выполняться без изменения `FlowEngine`.

Последовательность:

```text
Определить type

↓

Создать NodeHandler

↓

Зарегистрировать в NodeHandlerRegistry

↓

Определить settings

↓

Добавить Compiler validation

↓

Добавить Node в Vue Editor

↓

Добавить runtime tests
```

Например:

```text
ai.classify

↓

AiClassifyNodeHandler
```

`FlowEngine` при этом остаётся неизменным.

---

# Принцип Open/Closed

Добавление нового Node типа должно расширять систему, а не требовать изменения существующей логики FlowEngine.

Недопустима конструкция:

```php
switch ($node->type) {
    case 'message':
        ...
    case 'condition':
        ...
    case 'delay':
        ...
}
```

внутри `FlowEngine`.

Вместо этого используется:

```text
Node.type
    ↓
NodeHandlerRegistry
    ↓
NodeHandler
```

---

# Пример полного выполнения

```text
FlowEngine
    │
    ▼
Current Node: message
    │
    ▼
NodeHandlerRegistry
    │
    ▼
MessageNodeHandler
    │
    ├── RuntimeContext
    │
    └── Node.settings
    │
    ▼
NodeResult
    │
    ├── SendMessageCommand
    │
    └── Continue
    │
    ▼
Application
    │
    ▼
TelegramGateway
```

После фиксации результата:

```text
FlowSession
    │
    ▼
Next Node
```

и цикл повторяется.

---

# Инварианты

Всегда должны соблюдаться следующие правила:

- каждый поддерживаемый `Node.type` имеет зарегистрированный NodeHandler;
- NodeHandler не зависит от Telegram;
- NodeHandler не зависит от MoonShine;
- NodeHandler не работает напрямую с Eloquent;
- NodeHandler не обращается напрямую к Redis;
- NodeHandler не изменяет FlowVersion;
- NodeHandler получает контекст через RuntimeContext;
- Handler возвращает структурированный NodeResult;
- внешние действия представлены Commands;
- длительные операции не выполняются блокирующим образом;
- повторная доставка одного события не должна приводить к повторному побочному эффекту;
- добавление нового Node не требует изменения FlowEngine.

---

# Будущие возможности

Архитектура NodeHandler допускает расширение:

```text
AI Nodes
HTTP Nodes
Payment Nodes
CRM Nodes
Database Nodes
Human Handover Nodes
Webhook Nodes
Parallel Nodes
Retry Nodes
```

Также в будущем может появиться отдельный механизм пользовательских Node Plugins.

При этом базовый контракт `NodeHandler` должен оставаться стабильным.

---

# Связанные документы

- README.md
- flow.md
- flow-draft.md
- flow-version.md
- flow-session.md
- flow-router.md
- flow-engine.md
- runtime-context.md
- flow-json.md

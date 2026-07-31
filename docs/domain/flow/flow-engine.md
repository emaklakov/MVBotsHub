# FlowEngine

**Документ:** `docs/domain/flow/flow-engine.md`

**Версия:** 1.0

**Статус:** Актуален

---

# Назначение

`FlowEngine` отвечает за выполнение опубликованного сценария для конкретной `FlowSession`.

FlowEngine получает:

- опубликованную `FlowVersion`;
- текущее состояние `FlowSession`;
- `RuntimeContext`;
- входное событие, если оно необходимо для продолжения выполнения.

На основании этих данных FlowEngine определяет, какой узел должен быть выполнен, вызывает соответствующий `NodeHandler` и обрабатывает результат выполнения.

FlowEngine является ядром выполнения подсистемы Flow.

---

# Основной принцип

FlowEngine работает только с опубликованными версиями сценариев.

```text
FlowDraft
    X
    │
    │ запрещено
    ▼

FlowVersion
    │
    ▼
FlowSession
    │
    ▼
FlowEngine
```

Draft никогда не исполняется в Production.

---

# Ответственность

FlowEngine отвечает за:

- получение текущего состояния FlowSession;
- получение текущего Node;
- выбор NodeHandler;
- выполнение NodeHandler;
- обработку результата Node;
- переход к следующему Node;
- обновление состояния FlowSession;
- управление FlowStack;
- формирование команд для Application Layer;
- перевод FlowSession в состояние ожидания;
- завершение FlowSession.

FlowEngine не отвечает за:

- редактирование Flow;
- публикацию FlowVersion;
- маршрутизацию внешних событий;
- отправку сообщений в Telegram;
- работу с PostgreSQL напрямую;
- работу с Redis напрямую;
- работу с MoonShine;
- выполнение HTTP-запросов напрямую от имени конкретного Node.

---

# Входные данные

FlowEngine получает `ExecutionRequest`.

Концептуально он содержит:

```text
FlowSession

FlowVersion

RuntimeContext

Incoming Event (опционально)
```

FlowEngine не должен самостоятельно искать эти данные в базе.

Необходимые зависимости передаются ему Application Layer.

---

# Общая схема выполнения

```text
FlowSession
    │
    ▼
FlowEngine
    │
    ▼
Current Node
    │
    ▼
NodeHandlerRegistry
    │
    ▼
NodeHandler
    │
    ▼
Node Result
    │
    ├───────────────┐
    │               │
    ▼               ▼
Continue         Wait / Pause
    │               │
    ▼               ▼
Next Node       Save State
    │
    ▼
FlowEngine
```

---

# Получение текущего Node

FlowSession содержит текущую позицию выполнения.

FlowEngine:

1. получает текущий Node;
2. проверяет его существование;
3. определяет его `type`;
4. получает соответствующий NodeHandler через `NodeHandlerRegistry`.

Если Node отсутствует или его тип не поддерживается, выполнение считается ошибочным.

---

# NodeHandlerRegistry

FlowEngine не должен содержать `switch`, `match` или цепочку условных операторов для определения обработчика Node.

Вместо этого используется `NodeHandlerRegistry`.

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

# NodeHandler

Каждый тип Node имеет собственный `NodeHandler`.

Handler получает:

```text
Node

RuntimeContext

Incoming Event

Execution State
```

и возвращает результат выполнения.

Handler не должен:

- напрямую изменять FlowVersion;
- напрямую работать с Telegram;
- самостоятельно сохранять Eloquent Model;
- напрямую управлять очередями.

---

# Результат Node

Результат выполнения Node должен быть типизированным результатом, а не произвольным набором данных.

На концептуальном уровне поддерживаются следующие варианты.

## Continue

Узел успешно выполнен, выполнение может продолжаться.

```text
Continue
    ↓
Next Node
```

---

## Wait

Узел требует внешнего события.

```text
Wait
    ↓
FlowSession = Waiting
```

Например:

- ожидание текста;
- ожидание контакта;
- ожидание Callback;
- ожидание оплаты.

---

## Pause

Выполнение временно приостановлено.

```text
Pause
    ↓
FlowSession = Paused
```

---

## Complete

Сценарий завершён.

```text
Complete
    ↓
FlowSession = Completed
```

---

## Cancel

Сценарий остановлен.

```text
Cancel
    ↓
FlowSession = Cancelled
```

---

## Fail

Произошла необрабатываемая ошибка.

```text
Fail
    ↓
FlowSession = Failed
```

---

# Команды

NodeHandler не должен непосредственно выполнять внешние действия.

Вместо этого Handler возвращает команды.

Например:

```text
SendMessageCommand
SaveVariableCommand
CallFlowCommand
ScheduleResumeCommand
RequestInputCommand
```

Общая схема:

```text
NodeHandler

↓

Commands

↓

Application Layer

↓

Infrastructure
```

---

# Почему используются Commands

Это позволяет отделить:

**что нужно сделать**

от

**как это технически сделать**.

Например:

```text
SendMessageCommand
```

не знает ничего о Telegram.

Application и Infrastructure уже определяют, каким транспортом выполнить команду.

---

# Выполнение нескольких Node

Если Node завершился синхронно и не требует ожидания, FlowEngine может продолжить выполнение.

Например:

```text
Node A
  ↓
Node B
  ↓
Node C
  ↓
Node D
```

FlowEngine может пройти несколько синхронных Node за одно выполнение.

Но количество последовательных переходов должно иметь ограничение.

---

# Защита от бесконечных циклов

Flow может содержать циклы.

Например:

```text
A
↓
B
↓
C
↓
A
```

Это допустимо, если цикл является частью бизнес-логики.

Однако FlowEngine должен защищать систему от бесконечного выполнения в рамках одного шага обработки.

Для этого используется лимит последовательных переходов.

Например:

```text
max transitions = 100
```

Конкретное значение является конфигурационным параметром.

Если лимит достигнут, FlowSession должна быть переведена в `Failed`, а причина зафиксирована.

---

# FlowStack

FlowEngine управляет стеком вызовов Flow.

Например:

```text
Main Menu

↓

Catalog

↓

Checkout
```

При выполнении `FlowCall` новый Frame помещается в стек.

```text
FlowStack

1. Main Menu
2. Catalog
3. Checkout
```

Текущим является верхний Frame.

После завершения дочернего Flow:

```text
Checkout
```

удаляется из стека, и выполнение продолжается в:

```text
Catalog
```

---

# FlowCall

`FlowCall` рассматривается как вызов другого Flow.

FlowEngine должен:

1. разрешить целевой Flow по публичному `code`;
2. определить активную версию целевого Flow;
3. создать новый Frame;
4. переключить выполнение на целевой Flow;
5. продолжить выполнение.

При этом текущая FlowVersion родительского Flow не изменяется.

---

# Возврат из Flow

Когда дочерний Flow завершён:

```text
Child Flow
    │
    ▼
Return
    │
    ▼
Previous Frame
```

FlowEngine восстанавливает предыдущий Frame и продолжает выполнение.

Если стек пуст и Flow завершён, FlowSession переходит в `Completed`.

---

# RuntimeContext

Каждый NodeHandler получает `RuntimeContext`.

Контекст содержит данные, необходимые для выполнения текущего шага.

Концептуально:

```text
RuntimeContext

├── Bot
├── BotMember
├── Person
├── Conversation
├── FlowSession
├── Current Flow
├── Current FlowVersion
├── FlowStack
├── Variables
├── Language
└── Current Event
```

RuntimeContext не является долговременным хранилищем состояния.

Источником истины для состояния FlowSession остаётся сама FlowSession.

---

# Работа с переменными

FlowEngine предоставляет NodeHandler доступ к переменным выполнения через RuntimeContext.

Например:

```text
customer_name
selected_product
order_id
attempt_count
```

Изменение переменных должно возвращаться как отдельная команда или структурированный результат выполнения.

FlowEngine не должен напрямую изменять хранилище переменных.

---

# Ожидание события

Если Node требует ответа пользователя:

```text
Input Text
```

NodeHandler возвращает:

```text
Wait
```

и описание ожидаемого события.

FlowSession сохраняется в состоянии:

```text
Waiting
```

При получении нового события Application определяет соответствующую FlowSession и передаёт событие для продолжения выполнения.

---

# Возобновление

После получения ожидаемого события:

```text
Waiting FlowSession

↓

Incoming Event

↓

FlowEngine

↓

Resume Current Node

↓

Continue
```

Повторная доставка одного и того же события не должна приводить к повторному выполнению уже обработанного шага.

---

# Идемпотентность

FlowEngine должен учитывать уникальный идентификатор входящего события.

Один Event не должен приводить к повторному выполнению одного и того же перехода после того, как результат уже был зафиксирован.

Механизм хранения идемпотентности относится к Application/Infrastructure.

---

# Транзакционные границы

FlowEngine сам не определяет инфраструктурную транзакцию базы данных.

Транзакционные границы задаются Application Layer.

Однако состояние FlowSession и связанные изменения должны сохраняться атомарно относительно соответствующей бизнес-операции.

---

# Внешние действия

FlowEngine не вызывает напрямую:

```text
Telegram API
Redis
HTTP
Filesystem
PostgreSQL
```

Любое внешнее действие представляется командой.

Например:

```text
SendMessageCommand
```

или:

```text
HttpRequestCommand
```

После формирования команда передаётся Application Layer.

---

# Ошибки

Ошибки подразделяются на две категории.

## Ожидаемые ошибки выполнения

Например:

- неверный пользовательский ввод;
- отсутствие значения переменной;
- условие не выполнено.

Такие ситуации могут быть частью нормального сценария Flow.

---

## Необрабатываемые ошибки

Например:

- повреждена FlowVersion;
- отсутствует NodeHandler;
- структура Node некорректна;
- превышен лимит переходов.

Такие ошибки приводят к:

```text
FlowSession = Failed
```

Причина ошибки должна быть зафиксирована.

---

# Отсутствующий Handler

Если `Node.type` существует в JSON, но для него нет зарегистрированного Handler:

```text
NodeHandlerRegistry
        ↓
Handler not found
```

FlowEngine не должен пропускать такой Node.

FlowSession завершается с ошибкой конфигурации сценария.

---

# Неизменяемость FlowVersion

FlowEngine никогда не изменяет `FlowVersion`.

```text
FlowVersion
    │
    │ read-only
    ▼
FlowEngine
```

Любое изменение сценария выполняется через Draft и последующую публикацию новой версии.

---

# Производительность

FlowEngine должен быть рассчитан на большое количество параллельных выполнений.

Для этого:

- выполнение не должно зависеть от памяти PHP-процесса;
- состояние FlowSession должно сохраняться независимо от worker;
- FlowVersion может кэшироваться;
- NodeHandlerRegistry должен быть резолвимым без повторной регистрации на каждый Node;
- синхронные переходы должны выполняться без лишних инфраструктурных операций.

---

# Ограничение времени выполнения

FlowEngine не должен выполнять потенциально длительные операции непосредственно в рамках одного PHP-запроса или worker execution.

Если Node требует:

- ожидания;
- задержки;
- внешнего запроса;
- AI;
- оплаты;
- другого длительного процесса,

он должен перевести FlowSession в соответствующее состояние и вернуть выполнение системе.

---

# Порядок выполнения

Типовой цикл:

```text
Load FlowSession
        │
        ▼
Load FlowVersion
        │
        ▼
Build RuntimeContext
        │
        ▼
Resolve Current Node
        │
        ▼
Resolve NodeHandler
        │
        ▼
Execute Handler
        │
        ▼
Process Result
        │
        ├─────────────┬──────────────┐
        ▼             ▼              ▼
     Continue        Wait          Complete
        │             │              │
        ▼             ▼              ▼
   Next Node       Persist        Finish
        │
        ▼
Continue Execution
```

---

# Инварианты

Всегда должны соблюдаться следующие правила.

- FlowEngine работает только с опубликованной FlowVersion.
- FlowEngine не изменяет FlowVersion.
- FlowEngine не зависит от Telegram.
- FlowEngine не обращается напрямую к Infrastructure.
- Каждый поддерживаемый Node type имеет NodeHandler.
- FlowSession является источником текущего состояния выполнения.
- Повторное событие не должно повторно выполнять уже завершённый шаг.
- Бесконечные циклы должны быть ограничены.
- Длительные операции не выполняются синхронно внутри FlowEngine.
- Все внешние действия представляются командами.

---

# Будущие возможности

Архитектура позволяет добавить:

- параллельные ветки выполнения;
- отмену отдельных веток;
- таймауты Node;
- retry;
- компенсационные действия;
- AI Node;
- человеческое вмешательство;
- отладку FlowSession;
- пошаговое выполнение;
- replay выполнения.

---

# Связанные документы

- README.md
- flow.md
- flow-session.md
- flow-version.md
- flow-router.md
- flow-compiler.md
- runtime-context.md
- node-handler.md
- flow-json.md

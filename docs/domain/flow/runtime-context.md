# RuntimeContext

**Документ:** `docs/domain/flow/runtime-context.md`

**Версия:** 1.0

**Статус:** Актуален

---

# Назначение

`RuntimeContext` — объект контекста текущего выполнения `Flow`.

Он объединяет данные, которые необходимы `FlowEngine` и `NodeHandler` для выполнения текущего шага сценария.

RuntimeContext не является отдельным агрегатом и не имеет собственного жизненного цикла.

Он существует только во время выполнения конкретной операции FlowEngine.

---

# Ответственность

RuntimeContext отвечает за предоставление единого контекста выполнения.

В него входят:

- текущий Bot;
- текущий Person;
- текущий BotMember;
- текущий Conversation;
- текущая FlowSession;
- текущий Flow;
- текущий FlowVersion;
- текущий Frame FlowStack;
- переменные выполнения;
- язык текущего взаимодействия;
- входное событие;
- технические данные текущего выполнения.

RuntimeContext не отвечает за:

- постоянное хранение состояния;
- сохранение данных в PostgreSQL;
- отправку сообщений;
- выполнение HTTP-запросов;
- работу с Redis;
- маршрутизацию;
- публикацию Flow.

---

# Основной принцип

RuntimeContext является **временным представлением текущего состояния выполнения**.

Он не является источником истины.

Источником истины остаются соответствующие доменные сущности и их постоянное состояние.

Например:

```text
BotMember
    │
    └── источник постоянных данных участника
```

```text
FlowSession
    │
    └── источник состояния выполнения
```

```text
FlowVersion
    │
    └── источник структуры опубликованного Flow
```

RuntimeContext лишь предоставляет эти данные FlowEngine в удобной форме.

---

# Основные данные контекста

Концептуально RuntimeContext содержит:

```text
RuntimeContext

├── Bot
├── Person
├── BotMember
├── Conversation
├── Flow
├── FlowVersion
├── FlowSession
├── FlowStack
├── Variables
├── Language
└── IncomingEvent
```

---

# Bot

RuntimeContext содержит текущий `Bot`, в рамках которого выполняется сценарий.

Bot используется для получения:

- настроек;
- доступных языков;
- конфигурации сценариев;
- других данных, относящихся к текущему боту.

RuntimeContext не изменяет Bot напрямую.

---

# Person

RuntimeContext содержит текущий `Person`, связанный с `BotMember`.

Person предоставляет глобальные данные человека.

Например:

```text
phone
name
```

RuntimeContext не должен использовать Person для хранения данных, специфичных для конкретного Bot.

---

# BotMember

`BotMember` является основным контекстом взаимодействия человека с конкретным ботом.

Из BotMember доступны данные:

- текущий Telegram-контекст;
- выбранные пользовательские настройки;
- статус участника;
- данные активности;
- другие параметры, относящиеся к конкретному Bot.

---

# Conversation

RuntimeContext может содержать текущий активный `Conversation`.

Он используется для:

- определения текущего сеанса общения;
- работы с историей;
- формирования контекста диалога;
- связывания исходящих действий с текущим Conversation.

Conversation не заменяет FlowSession.

---

# Flow

RuntimeContext содержит текущий Flow, выполняемый в активном Frame FlowStack.

При переходе через `FlowCall` текущий Flow RuntimeContext изменяется вместе с активным Frame.

---

# FlowVersion

RuntimeContext содержит конкретную `FlowVersion`, которая выполняется в текущем Frame.

FlowVersion является неизменяемым источником структуры сценария.

RuntimeContext не изменяет FlowVersion.

---

# FlowSession

RuntimeContext всегда относится к конкретной FlowSession.

FlowSession содержит постоянное состояние выполнения:

- текущую позицию;
- состояние ожидания;
- стек выполнения;
- переменные;
- статус.

RuntimeContext предоставляет эти данные для выполнения текущего шага.

---

# FlowStack

RuntimeContext предоставляет доступ к текущему стеку вызовов Flow.

Например:

```text
Main Menu
    ↓
Catalog
    ↓
Checkout
```

Текущим является верхний Frame:

```text
Checkout
```

Каждый Frame содержит данные, необходимые для возврата к предыдущему Flow.

---

# Variables

RuntimeContext предоставляет переменные, доступные текущему выполнению.

Например:

```text
customer_name
selected_product
order_id
attempt_count
```

Необходимо различать:

## Session Variables

Переменные текущего FlowSession.

Они относятся только к текущему выполнению.

---

## Member Variables

Постоянные пользовательские значения в рамках конкретного BotMember.

Они могут сохраняться после завершения FlowSession.

RuntimeContext может предоставлять оба типа переменных, но они должны оставаться логически различными.

---

# Язык

RuntimeContext содержит итоговый язык текущего взаимодействия.

Язык определяется с учётом:

```text
BotMemberPreferences
        ↓
BotSettings
        ↓
Effective Language
```

То есть RuntimeContext получает уже разрешённое итоговое значение.

NodeHandler не должен самостоятельно решать, какой язык использовать.

---

# IncomingEvent

RuntimeContext может содержать нормализованное входящее событие, которое вызвало или продолжает выполнение.

Например:

```text
MessageReceived
CallbackReceived
ContactReceived
LocationReceived
PaymentReceived
```

RuntimeContext работает с нормализованным событием и не должен зависеть от структуры Telegram Update.

---

# Технический контекст

При необходимости RuntimeContext может содержать технические метаданные текущего выполнения.

Например:

```text
execution_id
event_id
started_at
attempt
```

Такие данные используются для:

- трассировки;
- идемпотентности;
- диагностики;
- логирования.

Они не являются частью бизнес-модели.

---

# Effective Values

RuntimeContext должен предоставлять уже разрешённые значения там, где итоговое значение зависит от нескольких источников.

Например:

```text
language()
timezone()
```

Вместо того чтобы каждый NodeHandler самостоятельно проверял:

```text
BotMemberPreferences
или
BotSettings
```

он получает готовое значение через RuntimeContext.

---

# Read Access

По умолчанию RuntimeContext предоставляет NodeHandler доступ к данным в режиме чтения.

NodeHandler не должен произвольно изменять:

- Bot;
- Person;
- BotMember;
- Conversation;
- Flow;
- FlowVersion.

Изменения должны быть представлены как команды или структурированные результаты выполнения и обработаны Application Layer.

---

# Изменение переменных

Если Node изменяет переменную:

```text
Set Variable
```

Handler формирует соответствующий результат или команду.

Например:

```text
SaveSessionVariableCommand
```

или:

```text
SaveMemberVariableCommand
```

RuntimeContext обновляется только как временное представление после принятия соответствующего изменения системой.

---

# RuntimeContext и персистентность

RuntimeContext не должен использоваться как скрытое хранилище.

Недопустим следующий подход:

```text
RuntimeContext
    ↓
держим состояние только в памяти PHP
```

При завершении текущего выполнения состояние, необходимое для продолжения Flow, должно быть сохранено в `FlowSession`.

Это особенно важно для:

- очередей;
- задержек;
- ожидания сообщений;
- повторных попыток;
- перезапуска worker.

---

# RuntimeContext и FlowSession

Разделение ответственности:

```text
FlowSession
    ↓
Постоянное состояние выполнения
```

```text
RuntimeContext
    ↓
Временное представление состояния для текущей операции
```

Пример:

```text
FlowSession.current_node = "ask-phone"
```

Во время исполнения создаётся:

```text
RuntimeContext.currentNode = "ask-phone"
```

После завершения операции RuntimeContext уничтожается, а FlowSession продолжает хранить актуальное состояние.

---

# RuntimeContext и FlowStack

FlowStack является частью состояния FlowSession.

RuntimeContext получает текущий стек из состояния FlowSession и предоставляет его FlowEngine и NodeHandler.

После изменения стека соответствующее состояние должно быть сохранено в FlowSession.

---

# Создание RuntimeContext

RuntimeContext создаётся Application/Flow Runtime перед запуском или возобновлением выполнения.

Высокоуровневая последовательность:

```text
FlowSession

+

FlowVersion

+

Bot

+

BotMember

+

Conversation

+

IncomingEvent

↓

RuntimeContext

↓

FlowEngine
```

---

# Жизненный цикл

RuntimeContext существует только в пределах одного выполнения FlowEngine.

```text
Create

↓

Populate

↓

FlowEngine

↓

NodeHandler

↓

Commands / Result

↓

Destroy
```

При необходимости следующего шага создаётся новый или обновлённый RuntimeContext на основе актуального состояния.

---

# Инварианты

Всегда должны соблюдаться следующие правила.

- RuntimeContext не является Aggregate Root.
- RuntimeContext не хранится как самостоятельная бизнес-сущность.
- RuntimeContext не является источником истины.
- RuntimeContext не изменяет FlowVersion.
- RuntimeContext не зависит от Telegram API.
- RuntimeContext не должен содержать состояние, которое не сохранено в FlowSession и должно пережить завершение текущего процесса.
- NodeHandler получает все необходимые данные через RuntimeContext, а не выполняет самостоятельный поиск по инфраструктуре.
- Итоговые пользовательские настройки предоставляются RuntimeContext уже разрешёнными.
- Session Variables и Member Variables остаются разными уровнями состояния.

---

# Масштабирование

RuntimeContext должен быть дешёвым объектом текущего выполнения.

Он не должен:

- содержать большие коллекции без необходимости;
- загружать всю историю Conversation;
- загружать весь набор данных BotMember;
- удерживать инфраструктурные соединения;
- зависеть от локального процесса PHP после завершения операции.

При необходимости дополнительные данные должны загружаться лениво или предоставляться специализированными сервисами.

---

# Безопасность

RuntimeContext может содержать персональные данные.

Поэтому:

- RuntimeContext не должен целиком записываться в обычные логи;
- чувствительные данные должны быть исключены из трассировки;
- телефон, токены и другие чувствительные значения не должны попадать в диагностические сообщения без явного разрешения;
- сериализация RuntimeContext допускается только для специально определённых безопасных частей.

---

# Будущие возможности

Архитектура допускает расширение RuntimeContext дополнительными областями контекста, например:

```text
Locale
Timezone
Currency
Channel
Device
Referral
Campaign
Experiment
```

Добавление нового контекста не должно приводить к зависимости Domain от конкретного транспорта.

---

# Связанные документы

- README.md
- flow.md
- flow-session.md
- flow-engine.md
- flow-router.md
- node-handler.md
- flow-json.md
- bot-member.md
- member-preferences.md

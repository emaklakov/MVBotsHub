# Notifications

**Документ:** `docs/admin/notifications.md`

**Версия:** 1.0

**Статус:** Актуален

---

# Назначение

`Notification` содержит административные уведомления, предназначенные для конкретного `User` MVBotsHub.

Notification информирует User о результате или состоянии операций, которые выполняются внутри административной подсистемы.

Notification не является Telegram-сообщением и не является частью истории `Conversation`.

---

# Ответственность

Notification отвечает за:

* фиксацию административного уведомления для User;
* хранение состояния прочтения;
* хранение типа уведомления;
* связь уведомления с исходной операцией;
* отображение краткого результата фоновой или административной операции.

Notification не отвечает за:

* выполнение исходной операции;
* авторизацию User;
* аудит действия;
* историю Conversation;
* отправку сообщений через Telegram;
* хранение полного журнала технических событий.

---

# Принадлежность

Каждое Notification принадлежит одному `User`.

```text
User
 │
 └── Notifications
       ├── Notification
       ├── Notification
       └── ...
```

Уведомление всегда существует в контексте административного пользователя.

---

# Основные свойства

На концептуальном уровне Notification может содержать:

| Свойство      | Назначение                                  |
| ------------- | ------------------------------------------- |
| id            | Внутренний идентификатор                    |
| user_id       | Получатель уведомления                      |
| type          | Тип уведомления                             |
| title         | Заголовок                                   |
| message       | Содержимое                                  |
| status        | Состояние уведомления                       |
| resource_type | Связанный ресурс, если есть                 |
| resource_id   | Идентификатор связанного ресурса, если есть |
| action_url    | Ссылка на соответствующий раздел, если есть |
| read_at       | Время прочтения                             |
| created_at    | Дата создания                               |
|               |                                             |

Конкретный состав полей может расширяться.

---

# Тип уведомления

`type` является стабильным техническим идентификатором.

Примеры:

```text
flow.publish.completed
flow.publish.failed
broadcast.completed
broadcast.failed
import.completed
import.failed
bot-access.granted
bot-access.revoked
system.warning
system.error
```

Тип не должен зависеть от текста интерфейса.

---

# Состояние

Минимально Notification может иметь состояния:

```text
Unread
Read
```

В будущем могут появиться дополнительные состояния, если это потребуется бизнес-модели.

---

# Создание Notification

Notification создаётся как результат административного или системного события.

Например:

```text
PublishFlow
    ↓
Flow published
    ↓
Create Notification
    ↓
User
```

Notification не должна быть условием успешности исходной операции.

---

# Notification и Audit

Notification и Audit решают разные задачи.

```text
Audit
→ фиксирует исторический факт действия

Notification
→ сообщает User результат или состояние
```

Например, при публикации Flow:

```text
User publishes Flow
       │
       ├── Audit Record
       │
       └── Notification
```

Audit сохраняется как исторический факт.

Notification может быть прочитано, скрыто или удалено в рамках обычной политики интерфейса.

---

# Notification и UserLog

`UserLog` предназначен прежде всего для технической или административной истории User.

Notification предназначено непосредственно для отображения User.

Одна операция может создавать одновременно:

```text
UserLog
Audit
Notification
```

но эти записи не являются взаимозаменяемыми.

---

# Связь с ресурсом

Notification может ссылаться на ресурс, к которому относится событие.

Например:

```text
resource_type = Flow
resource_id = 125
```

или:

```text
resource_type = Broadcast
resource_id = 54
```

Если ресурс поддерживает публичный `code`, он может дополнительно использоваться в metadata для диагностики.

---

# Action URL

Notification может содержать ссылку на административный экран, где User может продолжить работу.

Например:

```text
Flow published
→ Открыть Flow
```

URL не должен использоваться как механизм авторизации.

При открытии ресурса сервер повторно проверяет:

```text
User
+ Global Permission
+ BotAccess
+ Resource access
```

---

# Фоновые операции

Notification особенно полезен для операций, выполняющихся асинхронно.

Например:

```text
Import Bot
    ↓
Queue
    ↓
Processing
    ↓
Completed
    ↓
Notification
```

или:

```text
Broadcast
    ↓
Queue
    ↓
Failed
    ↓
Notification
```

---

# Flow

Для Flow Notification может использоваться для:

```text
flow.publish.completed
flow.publish.failed
flow.sandbox.failed
flow.import.completed
flow.import.failed
```

Уведомление должно содержать краткий результат, а детали диагностики должны находиться в соответствующем журнале или UI.

---

# Broadcast

Для Broadcast Notification может сообщать:

```text
broadcast.scheduled
broadcast.started
broadcast.completed
broadcast.failed
broadcast.cancelled
```

При необходимости Notification содержит ссылку на соответствующий Broadcast.

---

# BotAccess

Изменения доступа могут создавать:

```text
bot-access.granted
bot-access.revoked
bot-access.role-updated
```

Например:

```text
Вам предоставлен доступ к Bot «Support».
Роль: Operator.
```

Пользователь не должен получать через Notification права, которых у него нет.

---

# Системные уведомления

Notification может использоваться для системных сообщений административной подсистемы.

Например:

```text
system.warning
system.error
system.maintenance
```

Системные Notification не должны содержать секреты или внутренние технические детали, которые не предназначены для User.

---

# Прочтение

При открытии или подтверждении уведомления User оно может быть переведено в состояние `Read`.

Время прочтения фиксируется через `read_at`.

Историческое содержимое Notification после прочтения не изменяется, кроме служебного состояния прочтения.

---

# Счётчик непрочитанных

Административный интерфейс может отображать количество непрочитанных Notification.

Например:

```text
Notifications: 4
```

Счётчик вычисляется на основании состояния Notification.

Кэширование допускается, но кэш не является источником истины.

---

# Удаление

Notification может иметь более короткий срок хранения, чем Audit.

Удаление старых или прочитанных Notification допускается отдельной политикой хранения.

Удаление Notification не должно приводить к потере Audit или бизнес-истории исходной операции.

---

# Безопасность

Notification доступен только своему User.

Даже если Notification содержит ссылку на Bot или другой ресурс, открытие ресурса должно повторно проходить обычную проверку доступа.

Notification не должен использоваться для обхода BotAccess или permissions.

---

# Инварианты

Всегда должны соблюдаться следующие правила:

* Notification принадлежит одному User;
* Notification не заменяет Audit;
* Notification не заменяет UserLog;
* Notification не выполняет бизнес-операцию;
* ссылка из Notification не предоставляет дополнительных прав;
* доступ к Notification имеет только его User;
* секретные данные не хранятся в Notification;
* прочтение Notification не изменяет исторический факт исходной операции.

---

# Будущие возможности

Архитектура допускает:

* группировку уведомлений;
* приоритеты;
* массовое прочтение;
* категории;
* сроки действия;
* push-уведомления;
* email-уведомления;
* интеграцию с другими каналами административных уведомлений.

При этом Notification остаётся моделью административного уведомления User, а конкретные каналы доставки относятся к Application/Infrastructure.

---

# Связанные документы

* `docs/admin/README.md`
* `docs/admin/users.md`
* `docs/admin/audit.md`
* `docs/admin/permissions.md`
* `docs/domain/bot-access.md`

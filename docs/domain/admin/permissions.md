# Permissions

**Документ:** `docs/admin/permissions.md`

**Версия:** 1.0

**Статус:** Актуален

---

# Назначение

`Permission` определяет отдельное разрешение на выполнение конкретного административного действия в MVBotsHub.

Permissions являются частью системы авторизации административной подсистемы и используются совместно с ролями `User` и доступом `BotAccess`.

Permission отвечает на вопрос:

> Что User имеет право делать?

`BotAccess` отвечает на другой вопрос:

> С каким Bot User имеет право работать?

---

# Ответственность

Система permissions отвечает за:

* определение доступных административных операций;
* объединение permissions в роли;
* проверку глобальных permissions;
* применение permissions в контексте конкретного Bot;
* ограничение опасных административных операций.

Permissions не отвечают за:

* аутентификацию User;
* выбор доступных Bot;
* состояние Bot;
* бизнес-логику Flow;
* выполнение Broadcast;
* взаимодействие с Telegram.

---

# Два уровня авторизации

Авторизация административного пользователя разделена на два независимых уровня.

```text
Global Permission
       +
BotAccess
       +
Bot Permission
       ↓
Разрешённая операция
```

Наличие permission само по себе не должно предоставлять доступ к чужому Bot, если соответствующая политика не предусматривает специальное глобальное право.

---

# Глобальные permissions

Глобальный permission не привязан к конкретному Bot.

Он определяет системную возможность User.

Примеры:

```text
user.view
user.manage
bot.create
system.settings.view
system.settings.manage
```

Такие permissions могут использоваться для системных разделов и операций, не относящихся к одному конкретному Bot.

---

# Bot-level permissions

Bot-level permission действует только в контексте конкретного Bot.

Примеры:

```text
bot.view
bot.update
flow.view
flow.update
flow.publish
broadcast.view
broadcast.create
broadcast.send
conversation.view
member.view
statistics.view
```

Само наличие Bot-level permission не заменяет `BotAccess`.

---

# Проверка операции

Для операции над ресурсом конкретного Bot используется последовательность:

```text
User authenticated
        ↓
Global Permission
        ↓
BotAccess
        ↓
Bot-level Permission
        ↓
Resource relation
        ↓
Operation allowed
```

Конкретная операция может требовать не все уровни, если это явно определено политикой.

---

# Роли

Role объединяет набор permissions.

Например:

```text
Operator
├── bot.view
├── conversation.view
├── member.view
└── statistics.view
```

```text
Content Manager
├── bot.view
├── flow.view
├── flow.update
└── flow.publish
```

Роль не является заменой BotAccess.

User должен иметь действующий доступ к Bot, а роль определяет доступные операции внутри этого Bot.

---

# Принцип наименьших привилегий

User должен получать только те permissions, которые необходимы для его работы.

Например, пользователь с правом просмотра Conversation не должен автоматически получать право:

```text
flow.publish
broadcast.send
user.manage
```

---

# Публикация Flow

Право:

```text
flow.publish
```

должно проверяться отдельно от права редактирования:

```text
flow.update
```

User может иметь возможность изменять Draft, но не иметь права публиковать новую FlowVersion.

Это позволяет разделять роли редактора и ответственного за публикацию.

---

# Broadcast

Операции Broadcast также рекомендуется разделять.

Например:

```text
broadcast.view
broadcast.create
broadcast.update
broadcast.send
broadcast.cancel
```

Это позволяет запретить сотруднику самостоятельно запускать массовую рассылку, сохранив возможность готовить её содержимое.

---

# Conversation

Для Conversation могут использоваться отдельные permissions:

```text
conversation.view
conversation.export
conversation.manage
```

Доступ к Conversation всегда должен проверяться относительно соответствующего Bot.

---

# BotMember

Для участников Bot могут использоваться:

```text
member.view
member.update
member.export
```

Доступ к персональным данным должен учитывать как право на ресурс, так и дополнительные ограничения безопасности, если они предусмотрены политикой системы.

---

# Users

Для управления User используются отдельные глобальные permissions:

```text
user.view
user.create
user.update
user.block
user.manage
```

Особо опасные permissions должны выдаваться только соответствующим административным ролям.

---

# BotAccess

Управление доступом к Bot также должно быть выделено в отдельные permissions.

Например:

```text
bot-access.view
bot-access.grant
bot-access.update
bot-access.revoke
```

User без `bot-access.grant` не должен иметь возможности выдавать доступ другим User только на основании наличия собственного BotAccess.

---

# Системные permissions

Системные операции могут иметь отдельное пространство имён.

Например:

```text
system.settings.view
system.settings.manage
system.audit.view
system.jobs.manage
```

Такие права не должны автоматически давать доступ к обычным бизнес-ресурсам Bot.

---

# Именование permissions

Используется иерархическая схема:

```text
resource.action
```

или для системных операций:

```text
system.resource.action
```

Например:

```text
flow.view
flow.update
flow.publish
system.audit.view
```

Имена должны быть стабильными и не зависеть от текста интерфейса.

---

# Permission не зависит от UI

Permission описывает бизнес-разрешение, а не элемент интерфейса.

Например, permission:

```text
flow.publish
```

не означает:

```text
показывать кнопку "Опубликовать"
```

Кнопка лишь является одним из способов выполнения операции.

Проверка permission обязательна на серверной стороне.

---

# MoonShine

MoonShine использует permissions для:

* ограничения меню;
* скрытия недоступных действий;
* ограничения Resources и Pages;
* отображения доступных операций.

Однако скрытие элемента интерфейса не является механизмом безопасности.

Та же проверка должна выполняться при фактическом выполнении операции.

---

# Application Layer

Проверка критических permissions должна быть доступна Application Use Case.

Например:

```text
PublishFlow
    ↓
Authorization
    ↓
flow.publish
    ↓
BotAccess
    ↓
Publish
```

Это позволяет использовать те же правила через:

* MoonShine;
* API;
* Console;
* внутренние системные операции.

---

# Администратор

Термин `Admin` не является отдельной сущностью.

Административный статус определяется комбинацией:

```text
User
+
Role
+
Permissions
```

Поэтому User с ограниченными permissions остаётся User, даже если работает в административной панели.

---

# Выдача повышенных permissions

User не должен иметь возможность назначить самому себе permission или роль с более высокими полномочиями.

Изменение ролей и permissions должно выполняться только User, имеющим соответствующее административное право.

Особенно критичны:

```text
user.manage
system.settings.manage
bot-access.grant
```

---

# Аудит

Выдача, изменение и отзыв критических permissions или ролей должны фиксироваться в административном аудите.

Минимально должны быть доступны данные:

```text
кто
что изменил
кому
какое право / роль
когда
```

История аудита не должна зависеть от MoonShine.

---

# Инварианты

Всегда должны соблюдаться следующие правила:

* Permission определяет разрешённую операцию;
* Permission не является аутентификацией;
* Permission не заменяет BotAccess;
* BotAccess не заменяет необходимые permissions;
* глобальные и Bot-level permissions разделены;
* скрытие UI не является проверкой безопасности;
* User не может повысить собственные полномочия без соответствующего permission;
* критические изменения permissions и ролей должны аудироваться;
* имена permissions стабильны и не зависят от UI.

---

# Будущие возможности

Архитектура допускает:

* временные permissions;
* permissions с условиями;
* permission groups;
* ограничения по времени суток;
* ограничения по разделам Bot;
* делегирование отдельных полномочий;
* approval workflow для критических операций.

---

# Связанные документы

* `docs/admin/README.md`
* `docs/admin/users.md`
* `docs/domain/bot-access.md`
* `docs/domain/bot.md`

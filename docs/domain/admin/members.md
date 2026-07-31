# Members

**Документ:** `docs/admin/members.md`

**Версия:** 1.0

**Статус:** Актуален

---

# Назначение

Настоящий документ описывает административное управление участниками (`BotMember`) выбранного `Bot`.

Бизнес-модель `BotMember` описана в:

`docs/domain/bot-member.md`

Связь с реальным человеком описана в:

`docs/domain/person.md`

Настоящий документ определяет административные сценарии просмотра, поиска и разрешённых изменений данных участника.

---

# Контекст Bot

Все операции выполняются в контексте конкретного Bot.

```text
MVBotsHub
    ↓
Bot
    ↓
Members
```

Перед выполнением операции проверяется:

```text
Authenticated User
        ↓
Global Permission
        ↓
BotAccess
        ↓
Member Permission
        ↓
BotMember belongs to Bot
        ↓
Privacy / Data Access
        ↓
Allow
```

Прямой URL или внутренний `id` BotMember не должен позволять обойти проверку доступа к Bot.

---

# Список участников

В списке отображаются только `BotMember` выбранного Bot.

Для каждой записи могут отображаться:

* имя Person;
* телефон, если разрешён доступ к персональным данным;
* Telegram-идентификатор, если разрешён доступ;
* статус участника;
* язык;
* дата регистрации;
* дата последней активности;
* количество Conversation;
* наличие активной FlowSession.

---

# Поиск

Поиск участника может выполняться по разрешённым полям Person и BotMember.

Например:

```text
phone
first_name
last_name
telegram_id
```

Поиск по персональным данным должен учитывать права текущего User.

Не следует раскрывать поле, к которому User не имеет доступа, только для того, чтобы использовать его в поиске.

---

# Фильтрация

Минимально рекомендуется поддерживать фильтры:

```text
status
language
registered_at
last_activity_at
```

В будущем могут добавляться:

```text
segment
tag
custom variable
```

Фильтрация должна выполняться на серверной стороне.

---

# Открытие BotMember

При открытии участника административный интерфейс может показывать:

```text
Person
BotMember
MemberPreferences
Conversation
FlowSession
```

Каждая связанная область должна отображаться только при наличии соответствующих permissions.

---

# Person и BotMember

Административный интерфейс должен ясно разделять:

```text
Person
↓
общие данные человека
```

и:

```text
BotMember
↓
данные участия этого человека в конкретном Bot
```

Например:

```text
Person
  phone = +7...
  first_name = Ivan

BotMember
  language = ru
  status = active
  last_activity_at = ...
```

Изменение BotMember не должно менять глобальные данные Person без отдельного разрешения и соответствующего Application-сценария.

---

# MemberPreferences

Персональные настройки участника редактируются отдельно от BotSettings.

Например:

```text
Bot
  ↓
BotSettings

BotMember
  ↓
MemberPreferences
```

Административный интерфейс может позволять изменить:

* preferred_language;
* разрешённые пользовательские предпочтения;
* другие настройки, которые допускает политика Bot.

Подробная модель:

`docs/domain/member-preferences.md`

---

# Изменение языка

Администратор или оператор с соответствующим permission может изменить `preferred_language` участника.

Изменение должно выполняться через Application Use Case.

```text
Members
  ↓
UpdateMemberPreferences
  ↓
BotMember
  ↓
MemberPreferences
```

Язык должен быть одним из поддерживаемых языков Bot.

---

# Изменение статуса

Если бизнес-модель допускает административное управление статусом BotMember, соответствующая операция выполняется через отдельный Use Case.

Например:

```text
Block Member
Unblock Member
Archive Member
```

Изменение статуса не должно физически удалять историю Conversation и Message.

---

# Блокировка

При блокировке BotMember новые взаимодействия пользователя с Bot должны обрабатываться в соответствии с правилами Domain/Application.

Блокировка не удаляет:

* Person;
* BotMember history;
* Conversation;
* Message;
* FlowVersion.

---

# Conversation

Из карточки BotMember пользователь с соответствующим permission может перейти к связанным Conversation.

```text
BotMember
   ↓
Conversations
```

Conversation должны автоматически фильтроваться по текущему BotMember и Bot.

---

# FlowSession

При наличии прав административный интерфейс может показывать текущую FlowSession участника.

Например:

```text
Flow: registration
Version: registration@7
Node: ask-phone
Status: Waiting
```

Просмотр состояния не означает автоматического права изменять выполнение.

Для управления FlowSession нужны отдельные permissions и Use Cases.

---

# Telegram-идентификаторы

`telegram_id` и `chat_id` являются техническими данными BotMember.

Они могут отображаться только пользователю с соответствующим правом.

Изменение таких значений не должно выполняться произвольным редактированием поля в административной форме.

Изменение должно происходить через согласованный Application/Domain-сценарий, чтобы не нарушить идентификацию и связь с Telegram.

---

# Person

Если User имеет permission просмотра персональных данных, карточка BotMember может отображать связанные данные Person.

Например:

```text
First Name
Last Name
Phone
Birth Date
```

Если такого permission нет, интерфейс должен использовать обезличенное представление там, где это предусмотрено политикой безопасности.

---

# Массовые операции

Архитектура допускает массовые операции над BotMember.

Например:

```text
изменить язык
добавить тег
заблокировать
экспортировать
```

Массовая операция должна быть отдельным Application Use Case и не должна выполняться обычным циклом в Presentation Layer.

Перед выполнением должен проверяться доступ User ко всему выбранному набору участников.

---

# Экспорт

Если система поддерживает экспорт BotMember, для него требуется отдельное permission, например:

```text
member.export
```

Экспорт должен учитывать:

* BotAccess;
* доступ к Person;
* доступ к чувствительным полям;
* правила хранения данных;
* аудит операции.

---

# Удаление

Административная панель не должна использовать физическое удаление BotMember как обычную операцию.

При необходимости применяются:

* блокировка;
* архивирование;
* Soft Delete;
* анонимизация персональных данных;

в соответствии с политикой жизненного цикла и хранения данных.

История Conversation и Message не должна теряться только из-за административного изменения BotMember.

---

# Безопасность

Каждая операция должна проходить серверную проверку:

```text
Authenticated User
        ↓
Global Permission
        ↓
BotAccess
        ↓
Member Permission
        ↓
Privacy Check
        ↓
Execute
```

Скрытие поля или кнопки в MoonShine не является достаточной защитой.

---

# Аудит

Критические операции с BotMember должны фиксироваться в административном аудите.

Например:

```text
Member viewed
Member updated
Preferences changed
Member blocked
Member unblocked
Member archived
Data exported
```

Минимально должны фиксироваться:

```text
User
Bot
BotMember
Operation
Date / Time
Result
```

---

# Права доступа

Основные permissions могут включать:

```text
member.view
member.update
member.block
member.unblock
member.archive
member.export
member.preferences.update
member.personal-data.view
```

Конкретный набор permissions может расширяться.

---

# Инварианты

Всегда должны соблюдаться следующие правила:

* User видит только BotMember доступного ему Bot;
* BotMember всегда относится к конкретному Bot;
* Person и BotMember не смешиваются;
* изменение MemberPreferences не изменяет BotSettings;
* блокировка участника не удаляет историю;
* доступ к персональным данным может быть ограничен отдельно;
* экспорт требует отдельного разрешения;
* изменение Telegram-идентификаторов не выполняется простым редактированием поля;
* UI не является единственным уровнем безопасности.

---

# Будущие возможности

Административный интерфейс допускает расширение:

* теги участников;
* сегменты;
* пользовательские поля;
* история активности;
* массовые операции;
* импорт участников;
* расширенная аналитика;
* оценка взаимодействия;
* ручное назначение оператора.

---

# Связанные документы

* `docs/admin/README.md`
* `docs/admin/users.md`
* `docs/admin/permissions.md`
* `docs/admin/bot-access.md`
* `docs/admin/bots.md`
* `docs/admin/conversations.md`
* `docs/domain/bot-member.md`
* `docs/domain/person.md`
* `docs/domain/member-preferences.md`
* `docs/domain/conversation.md`

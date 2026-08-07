<?php

declare(strict_types=1);

use App\Application\Bots\Services\SystemMessageResolver;
use App\Application\Flows\Services\VariableResolver;
use App\Domain\Bots\Enums\SystemMessageKey;
use App\Domain\Bots\Models\Bot;
use App\Domain\Bots\Models\BotMessageTemplate;
use App\Domain\Conversations\Models\BotSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeBot(array $settings = []): Bot
{
    return Bot::query()->create([
        'username' => 'test_bot_'.uniqid(),
        'token' => 'dummy-token',
        'settings' => $settings,
        'status' => 'active',
        'channel_type' => 'telegram',
    ]);
}

function makeSubscriber(Bot $bot, ?string $language): BotSubscriber
{
    return BotSubscriber::query()->create([
        'bot_id' => $bot->id,
        'telegram_id' => random_int(100000, 999999),
        'language' => $language,
        'settings' => [],
        'status' => 'active',
    ]);
}

function resolver(): SystemMessageResolver
{
    return new SystemMessageResolver(new VariableResolver());
}

it('использует перевод из BotMessageTemplate на языке подписчика, если он есть', function () {
    $bot = makeBot();
    $subscriber = makeSubscriber($bot, language: 'en');

    BotMessageTemplate::query()->create([
        'bot_id' => $bot->id,
        'key' => SystemMessageKey::WELCOME->value,
        'translations' => ['ru' => 'Привет из шаблона', 'en' => 'Hello from template'],
    ]);

    $text = resolver()->resolve($bot, SystemMessageKey::WELCOME, $subscriber);

    expect($text)->toBe('Hello from template');
});

it('падает на язык бота по умолчанию, если перевода на языке подписчика нет в шаблоне', function () {
    $bot = makeBot(settings: ['language' => 'ru']);
    $subscriber = makeSubscriber($bot, language: 'kz'); // язык подписчика не покрыт шаблоном

    BotMessageTemplate::query()->create([
        'bot_id' => $bot->id,
        'key' => SystemMessageKey::WELCOME->value,
        'translations' => ['ru' => 'Привет из шаблона'], // только ru
    ]);

    $text = resolver()->resolve($bot, SystemMessageKey::WELCOME, $subscriber);

    expect($text)->toBe('Привет из шаблона');
});

it('падает на встроенный fallback enum, если для бота вообще нет шаблона', function () {
    $bot = makeBot();
    $subscriber = makeSubscriber($bot, language: 'en');

    // BotMessageTemplate намеренно не создаём

    $text = resolver()->resolve($bot, SystemMessageKey::WELCOME, $subscriber);

    expect($text)->toBe(SystemMessageKey::WELCOME->fallback()['en']);
});

it('падает на русский встроенный fallback как последний рубеж', function () {
    $bot = makeBot();
    $subscriber = makeSubscriber($bot, language: 'fr'); // язык, которого нет ни в шаблоне, ни в fallback()

    $text = resolver()->resolve($bot, SystemMessageKey::NOT_YOUR_CONTACT, $subscriber);

    expect($text)->toBe(SystemMessageKey::NOT_YOUR_CONTACT->fallback()['ru']);
});

it('подставляет переменные подписчика в разрешённый текст', function () {
    $bot = makeBot();
    $subscriber = makeSubscriber($bot, language: 'ru');
    $subscriber->telegram_username = 'ivan_petrov';
    $subscriber->save();

    BotMessageTemplate::query()->create([
        'bot_id' => $bot->id,
        'key' => SystemMessageKey::WELCOME->value,
        'translations' => ['ru' => 'Привет, {{subscriber.username}}!'],
    ]);

    $text = resolver()->resolve($bot, SystemMessageKey::WELCOME, $subscriber);

    expect($text)->toBe('Привет, ivan_petrov!');
});

it('не путает шаблоны разных ботов и разных ключей', function () {
    $botA = makeBot();
    $botB = makeBot();
    $subscriberA = makeSubscriber($botA, language: 'ru');

    BotMessageTemplate::query()->create([
        'bot_id' => $botA->id,
        'key' => SystemMessageKey::WELCOME->value,
        'translations' => ['ru' => 'Текст бота A'],
    ]);
    BotMessageTemplate::query()->create([
        'bot_id' => $botB->id,
        'key' => SystemMessageKey::WELCOME->value,
        'translations' => ['ru' => 'Текст бота B'],
    ]);
    BotMessageTemplate::query()->create([
        'bot_id' => $botA->id,
        'key' => SystemMessageKey::NOT_YOUR_CONTACT->value,
        'translations' => ['ru' => 'Другой ключ бота A'],
    ]);

    $text = resolver()->resolve($botA, SystemMessageKey::WELCOME, $subscriberA);

    expect($text)->toBe('Текст бота A');
});

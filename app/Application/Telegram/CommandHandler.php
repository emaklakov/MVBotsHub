<?php

declare(strict_types=1);

namespace App\Application\Telegram;

use App\Application\Bots\Services\SystemMessageResolver;
use App\Application\Services\LogService;
use App\Application\Telegram\DTO\SendMessage;
use App\Domain\Bots\Enums\SystemMessageKey;
use App\Domain\Bots\Models\Bot;
use App\Domain\Conversations\Models\BotSubscriber;
use App\Domain\Flows\Contracts\MessageSenderInterface;
use Illuminate\Support\Str;
use Stringable;

/**
 * Явный маппинг команд вместо опасного динамического вызова через Reflection.
 */
final class CommandHandler
{
    private const PREFIXES = ['/'];

    /** @var array<string, callable(Bot, BotSubscriber, string): void> */
    private array $handlers;

    public function __construct(
        private readonly MessageSenderInterface $messageSender,
        private readonly SystemMessageResolver $systemMessages,
    ) {
        $this->handlers = [
            //'start' => $this->handleStart(...),
        ];
    }

    public function isCommand(Stringable $text): bool
    {
        return Str::startsWith((string) $text, self::PREFIXES);
    }

    public function handle(Bot $bot, BotSubscriber $subscriber, Stringable $text): void
    {
        [$command, $parameter] = $this->parse((string) $text);

        $handler = $this->handlers[$command] ?? null;

        if ($handler === null) {
            $this->handleUnknown($bot, (string) $text);
            return;
        }

        $handler($bot, $subscriber, $parameter);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parse(string $text): array
    {
        $parts = explode(' ', $text, 2);
        $raw   = ltrim($parts[0], '/');
        $cmd   = explode('@', $raw)[0]; // Убираем @botname
        $param = $parts[1] ?? '';

        return [$cmd, $param];
    }

    private function handleStart(Bot $bot, BotSubscriber $subscriber, string $parameter): void
    {
        $this->messageSender->send(new SendMessage(
            $bot,
            $subscriber->telegram_id,
            $this->systemMessages->resolve($bot, SystemMessageKey::WELCOME_BACK, $subscriber),
        ));
        $this->messageSender->flush();
    }

    private function handleUnknown(Bot $bot, string $text): void
    {
        LogService::logWarning('Неизвестная Telegram-команда', [
            'bot_id'  => $bot->id,
            'command' => $text,
        ]);
    }
}

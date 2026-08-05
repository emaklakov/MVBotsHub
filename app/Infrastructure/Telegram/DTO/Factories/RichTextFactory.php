<?php

namespace App\Infrastructure\Telegram\DTO\Factories;

use DefStudio\Telegraph\Contracts\RichTextItem;
use App\Infrastructure\Telegram\DTO\RichText\RichTextAnchor;
use App\Infrastructure\Telegram\DTO\RichText\RichTextAnchorLink;
use App\Infrastructure\Telegram\DTO\RichText\RichTextBankCardNumber;
use App\Infrastructure\Telegram\DTO\RichText\RichTextBold;
use App\Infrastructure\Telegram\DTO\RichText\RichTextBotCommand;
use App\Infrastructure\Telegram\DTO\RichText\RichTextCashtag;
use App\Infrastructure\Telegram\DTO\RichText\RichTextCode;
use App\Infrastructure\Telegram\DTO\RichText\RichTextCustomEmoji;
use App\Infrastructure\Telegram\DTO\RichText\RichTextDateTime;
use App\Infrastructure\Telegram\DTO\RichText\RichTextEmailAddress;
use App\Infrastructure\Telegram\DTO\RichText\RichTextHashtag;
use App\Infrastructure\Telegram\DTO\RichText\RichTextItalic;
use App\Infrastructure\Telegram\DTO\RichText\RichTextMarked;
use App\Infrastructure\Telegram\DTO\RichText\RichTextMathematicalExpression;
use App\Infrastructure\Telegram\DTO\RichText\RichTextMention;
use App\Infrastructure\Telegram\DTO\RichText\RichTextPhoneNumber;
use App\Infrastructure\Telegram\DTO\RichText\RichTextReference;
use App\Infrastructure\Telegram\DTO\RichText\RichTextReferenceLink;
use App\Infrastructure\Telegram\DTO\RichText\RichTextSpoiler;
use App\Infrastructure\Telegram\DTO\RichText\RichTextStrikethrough;
use App\Infrastructure\Telegram\DTO\RichText\RichTextString;
use App\Infrastructure\Telegram\DTO\RichText\RichTextSubscript;
use App\Infrastructure\Telegram\DTO\RichText\RichTextSuperscript;
use App\Infrastructure\Telegram\DTO\RichText\RichTextTextMention;
use App\Infrastructure\Telegram\DTO\RichText\RichTextUnderline;
use App\Infrastructure\Telegram\DTO\RichText\RichTextUrl;
use DefStudio\Telegraph\Exceptions\RichTextException;
use DefStudio\Telegraph\Exceptions\RichTextFactoryException;
use Illuminate\Support\Collection;

class RichTextFactory
{
    /**
     *
     * @param  string|array<string, mixed>  $data
     *
     * @return RichTextItem|Collection<int|string,RichTextItem>
     * @throws RichTextException
     * @throws RichTextFactoryException
     */
    public function fromData(string|array $data): RichTextItem|Collection
    {
        if (is_string($data)) {
            return RichTextString::fromData($data);
        }

        if (!isset($data['type'])) {
            /** @phpstan-ignore-next-line  */
            return collect($data)->map(fn (string|array $item) => (is_array($item) && !isset($item['type'])) ? throw RichTextFactoryException::structureMismatch() : $this->fromData($item));
        }

        return match ($data['type']) {
            app(RichTextBold::class)->type() => RichTextBold::fromData($data), //@phpstan-ignore-line
            app(RichTextItalic::class)->type() => RichTextItalic::fromData($data), //@phpstan-ignore-line
            app(RichTextUnderline::class)->type() => RichTextUnderline::fromData($data), //@phpstan-ignore-line
            app(RichTextStrikethrough::class)->type() => RichTextStrikethrough::fromData($data), //@phpstan-ignore-line
            app(RichTextSpoiler::class)->type() => RichTextSpoiler::fromData($data), //@phpstan-ignore-line
            app(RichTextDateTime::class)->type() => RichTextDateTime::fromData($data), //@phpstan-ignore-line
            app(RichTextTextMention::class)->type() => RichTextTextMention::fromData($data), //@phpstan-ignore-line
            app(RichTextSubscript::class)->type() => RichTextSubscript::fromData($data), //@phpstan-ignore-line
            app(RichTextSuperscript::class)->type() => RichTextSuperscript::fromData($data), //@phpstan-ignore-line
            app(RichTextMarked::class)->type() => RichTextMarked::fromData($data), //@phpstan-ignore-line
            app(RichTextCode::class)->type() => RichTextCode::fromData($data), //@phpstan-ignore-line
            app(RichTextCustomEmoji::class)->type() => RichTextCustomEmoji::fromData($data), //@phpstan-ignore-line
            app(RichTextMathematicalExpression::class)->type() => RichTextMathematicalExpression::fromData($data), //@phpstan-ignore-line
            app(RichTextUrl::class)->type() => RichTextUrl::fromData($data), //@phpstan-ignore-line
            app(RichTextEmailAddress::class)->type() => RichTextEmailAddress::fromData($data), //@phpstan-ignore-line
            app(RichTextPhoneNumber::class)->type() => RichTextPhoneNumber::fromData($data), //@phpstan-ignore-line
            app(RichTextBankCardNumber::class)->type() => RichTextBankCardNumber::fromData($data), //@phpstan-ignore-line
            app(RichTextMention::class)->type() => RichTextMention::fromData($data), //@phpstan-ignore-line
            app(RichTextHashtag::class)->type() => RichTextHashtag::fromData($data), //@phpstan-ignore-line
            app(RichTextCashtag::class)->type() => RichTextCashtag::fromData($data), //@phpstan-ignore-line
            app(RichTextBotCommand::class)->type() => RichTextBotCommand::fromData($data), //@phpstan-ignore-line
            app(RichTextAnchor::class)->type() => RichTextAnchor::fromData($data), //@phpstan-ignore-line
            app(RichTextAnchorLink::class)->type() => RichTextAnchorLink::fromData($data), //@phpstan-ignore-line
            app(RichTextReference::class)->type() => RichTextReference::fromData($data), //@phpstan-ignore-line
            app(RichTextReferenceLink::class)->type() => RichTextReferenceLink::fromData($data), //@phpstan-ignore-line

            default => throw RichTextFactoryException::invalidType($data['type'])
        };
    }
}

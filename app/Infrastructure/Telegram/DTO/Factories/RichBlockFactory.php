<?php

namespace App\Infrastructure\Telegram\DTO\Factories;

use DefStudio\Telegraph\Contracts\RichBlockItem;
use App\Infrastructure\Telegram\DTO\RichBlock\RichBlockAnchor;
use App\Infrastructure\Telegram\DTO\RichBlock\RichBlockAnimation;
use App\Infrastructure\Telegram\DTO\RichBlock\RichBlockAudio;
use App\Infrastructure\Telegram\DTO\RichBlock\RichBlockBlockQuotation;
use App\Infrastructure\Telegram\DTO\RichBlock\RichBlockCollage;
use App\Infrastructure\Telegram\DTO\RichBlock\RichBlockDetails;
use App\Infrastructure\Telegram\DTO\RichBlock\RichBlockDivider;
use App\Infrastructure\Telegram\DTO\RichBlock\RichBlockFooter;
use App\Infrastructure\Telegram\DTO\RichBlock\RichBlockList;
use App\Infrastructure\Telegram\DTO\RichBlock\RichBlockMap;
use App\Infrastructure\Telegram\DTO\RichBlock\RichBlockMathematicalExpression;
use App\Infrastructure\Telegram\DTO\RichBlock\RichBlockParagraph;
use App\Infrastructure\Telegram\DTO\RichBlock\RichBlockPhoto;
use App\Infrastructure\Telegram\DTO\RichBlock\RichBlockPreformatted;
use App\Infrastructure\Telegram\DTO\RichBlock\RichBlockPullQuotation;
use App\Infrastructure\Telegram\DTO\RichBlock\RichBlockSectionHeading;
use App\Infrastructure\Telegram\DTO\RichBlock\RichBlockSlideshow;
use App\Infrastructure\Telegram\DTO\RichBlock\RichBlockTable;
use App\Infrastructure\Telegram\DTO\RichBlock\RichBlockThinking;
use App\Infrastructure\Telegram\DTO\RichBlock\RichBlockVideo;
use App\Infrastructure\Telegram\DTO\RichBlock\RichBlockVoiceNote;
use DefStudio\Telegraph\Exceptions\RichBlockFactoryException;

class RichBlockFactory
{
    /**
     * @param  array{
     *     type?:string
     * }  $blockData
     *
     * @return RichBlockItem
     * @throws RichBlockFactoryException
     */
    public function fromArray(array $blockData): RichBlockItem
    {
        if (!isset($blockData['type'])) {
            throw RichBlockFactoryException::missingType();
        }

        return match ($blockData['type']) {
            app(RichBlockAnchor::class)->type() => RichBlockAnchor::fromArray($blockData),
            app(RichBlockAnimation::class)->type() => RichBlockAnimation::fromArray($blockData),
            app(RichBlockAudio::class)->type() => RichBlockAudio::fromArray($blockData),
            app(RichBlockBlockQuotation::class)->type() => RichBlockBlockQuotation::fromArray($blockData),
            app(RichBlockCollage::class)->type() => RichBlockCollage::fromArray($blockData),
            app(RichBlockDetails::class)->type() => RichBlockDetails::fromArray($blockData),
            app(RichBlockDivider::class)->type() => RichBlockDivider::fromArray($blockData),
            app(RichBlockFooter::class)->type() => RichBlockFooter::fromArray($blockData),
            app(RichBlockList::class)->type() => RichBlockList::fromArray($blockData),
            app(RichBlockMap::class)->type() => RichBlockMap::fromArray($blockData),
            app(RichBlockMathematicalExpression::class)->type() => RichBlockMathematicalExpression::fromArray($blockData),
            app(RichBlockParagraph::class)->type() => RichBlockParagraph::fromArray($blockData),
            app(RichBlockPhoto::class)->type() => RichBlockPhoto::fromArray($blockData),
            app(RichBlockPreformatted::class)->type() => RichBlockPreformatted::fromArray($blockData),
            app(RichBlockPullQuotation::class)->type() => RichBlockPullQuotation::fromArray($blockData),
            app(RichBlockSectionHeading::class)->type() => RichBlockSectionHeading::fromArray($blockData),
            app(RichBlockSlideshow::class)->type() => RichBlockSlideshow::fromArray($blockData),
            app(RichBlockTable::class)->type() => RichBlockTable::fromArray($blockData),
            app(RichBlockThinking::class)->type() => RichBlockThinking::fromArray($blockData),
            app(RichBlockVideo::class)->type() => RichBlockVideo::fromArray($blockData),
            app(RichBlockVoiceNote::class)->type() => RichBlockVoiceNote::fromArray($blockData),
            default => throw RichBlockFactoryException::invalidType($blockData['type'])
        };
    }
}

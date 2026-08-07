<?php

declare(strict_types=1);

namespace App\Console\Commands\Audiences;

use App\Application\Audiences\Services\AudienceResolver;
use App\Domain\Audiences\Models\Audience;
use Illuminate\Console\Command;

/**
 * Держит Audience::cached_count в актуальном состоянии, чтобы в списке
 * MoonShine (AudienceIndexPage) было видно реальный размер сегмента без
 * пересчёта на каждый рендер страницы (AudienceResolver::count() бьёт в БД).
 *
 * Особенно важно для type=dynamic — такие сегменты меняются сами по себе
 * со временем (подписчики становятся активными/неактивными, стареют
 * по subscribed_days_ago и т.д.), в отличие от static-списков, которые
 * меняются только вручную через форму (там кэш освежается сразу при
 * сохранении — см. AudienceDetailPage::refreshCount()).
 *
 * Регистрируется в routes/console.php на ->hourly().
 */
final class RefreshAudienceCachedCount extends Command
{
    protected $signature = 'audiences:refresh-cached-count';

    protected $description = 'Пересчитывает Audience::cached_count для всех аудиторий';

    public function handle(AudienceResolver $resolver): int
    {
        $updated = 0;

        Audience::query()->chunkById(200, function ($audiences) use ($resolver, &$updated) {
            foreach ($audiences as $audience) {
                $resolver->refreshCachedCount($audience);
                $updated++;
            }
        });

        $this->info("Пересчитано аудиторий: {$updated}");

        return self::SUCCESS;
    }
}

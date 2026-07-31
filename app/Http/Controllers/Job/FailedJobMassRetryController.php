<?php

namespace App\Http\Controllers\Job;

use App\Models\Job\FailedJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class FailedJobMassRetryController
{
    public function __invoke(Request $request): RedirectResponse
    {
        if (! $request->user()?->can('update', FailedJob::class)) {
            abort(403);
        }

        $ids = (array) $request->input('ids', []);

        if ($ids === []) {
            session()->flash('toast', [
                'type' => 'error',
                'message' => 'Не выбрано ни одной задачи',
            ]);

            return back();
        }

        $jobs = FailedJob::query()->whereIn('id', $ids)->get();

        if ($jobs->isEmpty()) {
            session()->flash('toast', [
                'type' => 'error',
                'message' => 'Задачи не найдены',
            ]);

            return back();
        }

        try {
            Artisan::call('queue:retry', [
                'id' => $jobs->pluck('uuid')->all(),
            ]);

            session()->flash('toast', [
                'type' => 'success',
                'message' => "Перезапущено задач: {$jobs->count()}",
            ]);
        } catch (\Throwable $e) {
            Log::error('Ошибка массового retry failed jobs', [
                'ids' => $ids,
                'error' => $e->getMessage(),
            ]);

            session()->flash('toast', [
                'type' => 'error',
                'message' => 'Ошибка при перезапуске задач: ' . $e->getMessage(),
            ]);
        }

        return back();

        return back();
    }
}

<?php

namespace App\Http\Controllers\Api\Flows;

use App\Domain\Bots\Models\Bot;
use App\Domain\Flows\Enums\FlowVersionStatus;
use App\Domain\Flows\Models\Flow;
use App\Domain\Flows\Models\FlowVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FlowEditorController
{
    public function draft(Bot $bot, Flow $flow): JsonResponse
    {
        Gate::authorize('view', $flow);

        $draft = $flow->versions()->where('status', FlowVersionStatus::DRAFT)->first();

        if (!$draft) {
            $draft = FlowVersion::create([
                'flow_id' => $flow->id,
                'schema' => [],
                'status' => FlowVersionStatus::DRAFT,
                'version_number' => 0,
            ]);
        }

        return response()->json([
            'flow' => [
                'id' => $flow->id,
                'name' => $flow->name,
            ],
            'draft' => $draft,
        ]);
    }

    public function saveDraft(Request $request, Bot $bot, Flow $flow): JsonResponse
    {
        Gate::authorize('update', $flow);

        $validated = $request->validate([
            'schema' => 'required|array',
        ]);

        $draft = $flow->versions()->where('status', FlowVersionStatus::DRAFT)->first();

        if ($draft) {
            $draft->update(['schema' => $validated['schema']]);
        } else {
            $draft = FlowVersion::create([
                'flow_id' => $flow->id,
                'schema' => $validated['schema'],
                'status' => FlowVersionStatus::DRAFT,
                'version_number' => 0,
            ]);
        }

        return response()->json([
            'success' => true,
            'draft' => $draft,
        ]);
    }

    public function publish(Request $request, Bot $bot, Flow $flow): JsonResponse
    {
        Gate::authorize('update', $flow);

        $draft = $flow->versions()->where('status', FlowVersionStatus::DRAFT)->first();

        if (!$draft) {
            return response()->json(['error' => 'No draft found'], 422);
        }

        $nextVersion = ($flow->versions()->where('status', FlowVersionStatus::PUBLISHED)->max('version_number') ?? 0) + 1;

        $published = FlowVersion::create([
            'flow_id' => $flow->id,
            'schema' => $draft->schema,
            'status' => FlowVersionStatus::PUBLISHED,
            'version_number' => $nextVersion,
            'published_at' => now(),
            'published_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'version' => $published,
        ]);
    }
}

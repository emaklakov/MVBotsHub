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
        if($bot->id != $flow->bot_id) {
            return abort(404);
        }

        Gate::authorize('view', $flow);

        // Ищем черновик
        $draft = $flow->versions()->where('status', FlowVersionStatus::DRAFT)->latest()->first();

        // Если нет черновика и последней опубликованной версии, то создать новую
        if (!$draft) {
            // Если нет черновика, то ищем последнею опубликованную версию и берем у нее схему
            $published = $flow->versions()->where('status', FlowVersionStatus::PUBLISHED)->latest()->first();

            $schema = $published ? $published->schema : [
                'start_group_id' => null,
                'groups' => [],
                'blocks' => [],
                'edges' => [],
            ];

            $version_number = $published ? $published->version_number + 1 : 1;

            $draft = FlowVersion::create([
                'flow_id' => $flow->id,
                'schema' => $schema,
                'status' => FlowVersionStatus::DRAFT,
                'version_number' => $version_number,
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
        if($bot->id != $flow->bot_id) {
            return abort(404);
        }

        Gate::authorize('update', $flow);

//        $validated = $request->validate([
//            'schema' => 'required|array',
//        ]);

        $validated = $request->validate([
            'schema' => 'required|array',
            'schema.start_group_id' => 'nullable|string',

            'schema.groups' => 'present|array',
            'schema.groups.*.id' => 'required|string',
            'schema.groups.*.title' => 'required|string',
            'schema.groups.*.position' => 'required|array',
            'schema.groups.*.position.x' => 'required|numeric',
            'schema.groups.*.position.y' => 'required|numeric',
            'schema.groups.*.block_ids' => 'required|array',
            'schema.groups.*.block_ids.*' => 'string',

            'schema.blocks' => 'present|array',
            'schema.blocks.*.id' => 'required|string',
            'schema.blocks.*.group_id' => 'required|string',
            'schema.blocks.*.type' => 'required|string|in:text,image,video,audio,input,file,poll,input,button,number,email,phone,date,geolocation,contact,condition',
            'schema.blocks.*.content' => 'nullable',
            'schema.blocks.*.config' => 'nullable',
            'schema.blocks.*.outgoing_edge_id' => 'nullable|string',

            'schema.edges' => 'present|array',
            'schema.edges.*.id' => 'required|string',
            'schema.edges.*.source_block_id' => 'required|string',
            'schema.edges.*.target_group_id' => 'required|string',
            'schema.edges.*.source_handle' => 'nullable|string|in:true,false',
        ]);

        $draft = $flow->versions()->where('status', FlowVersionStatus::DRAFT)->first();

        if ($draft) {
            $draft->update(['schema' => $validated['schema']]);
        } else {
            $draft = FlowVersion::create([
                'flow_id' => $flow->id,
                'schema' => $validated['schema'],
                'status' => FlowVersionStatus::DRAFT,
                'version_number' => 1,
            ]);
        }

        return response()->json([
            'success' => true,
            'draft' => $draft,
        ]);
    }

    public function publish(Request $request, Bot $bot, Flow $flow): JsonResponse
    {
        if($bot->id != $flow->bot_id) {
            return abort(404);
        }

        Gate::authorize('update', $flow);

        $draft = $flow->versions()->where('status', FlowVersionStatus::DRAFT)->first();

        if (!$draft) {
            return response()->json(['error' => 'Черновик не найден'], 422);
        }

        $nextVersion = ($flow->versions()->where('status', FlowVersionStatus::PUBLISHED)->max('version_number') ?? 1) + 1;

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

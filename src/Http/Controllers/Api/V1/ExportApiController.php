<?php

namespace Hashcrypttech\HashGuardian\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Hashcrypttech\HashGuardian\Contracts\EntriesRepository;
use Hashcrypttech\HashGuardian\Export\EntryExporter;
use Hashcrypttech\HashGuardian\Jobs\ProcessExportJob;

class ExportApiController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $type = $request->input('type', 'request');
        $format = $request->input('format', 'csv');
        $async = $request->boolean('async', false);

        $filters = array_filter([
            'since' => $request->input('since'),
            'until' => $request->input('until'),
            'status' => $request->input('status'),
            'limit' => min((int) $request->input('limit', 1000), 10000),
        ]);

        if ($async) {
            $exportId = Str::uuid()->toString();
            ProcessExportJob::dispatch($exportId, $type, $filters, $format);

            return response()->json([
                'id' => $exportId,
                'status' => 'processing',
                'message' => 'Export queued. Check status at /export/' . $exportId,
            ], 202);
        }

        $exporter = new EntryExporter(app(EntriesRepository::class));
        $content = $exporter->export($type, $filters, $format);

        $contentType = $format === 'json' ? 'application/json' : 'text/csv';
        $extension = $format === 'json' ? 'json' : 'csv';

        return response($content, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => "attachment; filename=hashguardian-{$type}.{$extension}",
        ]);
    }

    public function show(string $id)
    {
        $disk = config('hashguardian.export.storage_disk', 'local');
        $csvPath = "hashguardian-exports/{$id}.csv";
        $jsonPath = "hashguardian-exports/{$id}.json";

        $storage = Storage::disk($disk);
        $path = $storage->exists($csvPath) ? $csvPath : ($storage->exists($jsonPath) ? $jsonPath : null);

        if (! $path) {
            return response()->json([
                'id' => $id,
                'status' => 'processing',
                'message' => 'Export is still being processed.',
            ]);
        }

        $content = $storage->get($path);
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $contentType = $ext === 'json' ? 'application/json' : 'text/csv';

        return response($content, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => "attachment; filename=hashguardian-export-{$id}.{$ext}",
        ]);
    }
}

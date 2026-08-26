<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class StatusController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $database = false;

        try {
            DB::select('select 1');
            $database = true;
        } catch (\Throwable) {
            $database = false;
        }

        $storageWritable = Storage::disk('local')->put(
            'status/'.now()->timestamp.'.txt',
            'ok',
        );

        if ($storageWritable) {
            foreach (Storage::disk('local')->files('status') as $file) {
                Storage::disk('local')->delete($file);
            }
        }

        return response()->json([
            'ok' => $database && $storageWritable !== false,
            'database' => $database,
            'storage' => $storageWritable !== false,
            'time' => now()->toIso8601String(),
        ]);
    }
}

<?php

namespace App\Application\Services;

use Illuminate\Support\Facades\Log;

class LogService
{
    public static function logError($message, $trace = null): void
    {
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];

        $location = isset($caller['class'])
            ? "{$caller['class']}::{$caller['function']}"
            : $caller['function'];

        Log::error("Error in {$location} {$caller['file']}:{$caller['line']}", [
            'message' => is_string($message) ? $message : json_encode($message),
            'trace' => json_encode($trace),
        ]);
    }

    public static function logWarning($message, $trace = null): void
    {
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];

        $location = isset($caller['class'])
            ? "{$caller['class']}::{$caller['function']}"
            : $caller['function'];

        Log::warning("Warning in {$location} {$caller['file']}:{$caller['line']}", [
            'message' => is_string($message) ? $message : json_encode($message),
            'trace' => json_encode($trace),
        ]);
    }

    public static function logInfo($message, $trace = null): void
    {
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];

        $location = isset($caller['class'])
            ? "{$caller['class']}::{$caller['function']}"
            : $caller['function'];

        Log::info("Info in {$location} {$caller['file']}:{$caller['line']}", [
            'message' => is_string($message) ? $message : json_encode($message),
            'trace' => json_encode($trace),
        ]);
    }
}

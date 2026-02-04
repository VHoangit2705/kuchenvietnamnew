<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ReportCommandController extends Controller
{
    /**
     * Trigger the report:send-email command via HTTP.
     */
    public function runSendReportEmail(Request $request, ?string $type = null)
    {
        $type = $this->normalizeType($type ?? $request->input('type', 'weekly'));

        $user = $request->get('user');

        try {
            Log::channel('email_report')->info('[API] Trigger report:send-email', [
                'type' => $type,
                'requested_by' => $user->id ?? null,
            ]);

            Artisan::call('report:send-email', ['type' => $type]);

            return response()->json([
                'message' => 'Command report:send-email executed successfully.',
                'type' => $type,
                'output' => trim(Artisan::output()),
            ]);
        } catch (\Throwable $e) {
            Log::channel('email_report')->error('[API] report:send-email failed', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to execute report:send-email.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Trigger the report:save-snapshot command via HTTP.
     */
    public function runSaveSnapshot(Request $request, ?string $type = null)
    {
        $type = $this->normalizeType($type ?? $request->input('type', 'weekly'));

        $user = $request->get('user');

        try {
            Log::channel('email_report')->info('[API] Trigger report:save-snapshot', [
                'type' => $type,
                'requested_by' => $user->id ?? null,
            ]);

            Artisan::call('report:save-snapshot', ['type' => $type]);

            return response()->json([
                'message' => 'Command report:save-snapshot executed successfully.',
                'type' => $type,
                'output' => trim(Artisan::output()),
            ]);
        } catch (\Throwable $e) {
            Log::channel('email_report')->error('[API] report:save-snapshot failed', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to execute report:save-snapshot.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Trigger the report:save-overdue-history command via HTTP.
     */
    public function runSaveOverdueHistory(Request $request, ?string $type = null)
    {
        $type = $this->normalizeType($type ?? $request->input('type', 'weekly'));

        $user = $request->get('user');

        try {
            Log::channel('email_report')->info('[API] Trigger report:save-overdue-history', [
                'type' => $type,
                'requested_by' => $user->id ?? null,
            ]);

            Artisan::call('report:save-overdue-history', ['type' => $type]);

            return response()->json([
                'message' => 'Command report:save-overdue-history executed successfully.',
                'type' => $type,
                'output' => trim(Artisan::output()),
            ]);
        } catch (\Throwable $e) {
            Log::channel('email_report')->error('[API] report:save-overdue-history failed', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to execute report:save-overdue-history.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Only allow supported command types.
     */
    protected function normalizeType(?string $type): string
    {
        $type = strtolower(trim((string) $type));

        return in_array($type, ['weekly', 'monthly'], true) ? $type : 'weekly';
    }
}

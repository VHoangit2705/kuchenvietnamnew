<?php

namespace App\Http\Controllers;

use App\Models\KyThuat\EditCtvHistory;
use Illuminate\Support\Facades\Log;

class SaveLogController extends Controller
{
    public function auditLog(
        int $installationOrdersId,
        string $orderCode,
        string $event,
        array $old,
        array $new,
        ?string $comments = null
    ): ?EditCtvHistory {
        // Chỉ giữ lại key thực sự thay đổi
        $changesOld = [];
        $changesNew = [];
        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
        foreach ($keys as $k) {
            $ov = $old[$k] ?? null;
            $nv = $new[$k] ?? null;
            if ((string)($ov ?? '') !== (string)($nv ?? '')) {
                $changesOld[$k] = $ov;
                $changesNew[$k] = $nv;
            }
        }

        if (empty($changesOld) && empty($changesNew)) {
            return null;
        }

        try {
            $actor = session('user', 'system');
            $now = now();

            $oldCollaboratorId = $old['collaborator_id'] ?? null;
            $newCollaboratorId = $new['collaborator_id'] ?? null;
            $oldCollaboratorId = is_numeric($oldCollaboratorId) ? (int) $oldCollaboratorId : null;
            $newCollaboratorId = is_numeric($newCollaboratorId) ? (int) $newCollaboratorId : null;

            $changesOldJson = json_encode($changesOld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $changesNewJson = json_encode($changesNew, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($changesOldJson === false) $changesOldJson = json_encode($changesOld);
            if ($changesNewJson === false) $changesNewJson = json_encode($changesNew);

            $historyRecord = EditCtvHistory::create([
                'installation_orders_id' => $installationOrdersId,
                'order_code' => $orderCode,
                'old_collaborator_id' => $oldCollaboratorId,
                'new_collaborator_id' => $newCollaboratorId,
                'event' => $event,
                'changes_old' => $changesOldJson,
                'changes_new' => $changesNewJson,
                'created_at' => $now,
                'action_type' => $event,
                'edited_by' => $actor,
                'edited_at' => $now,
                'comments' => $comments,
            ]);

            return $historyRecord;
        } catch (\Throwable $e) {
            Log::error('AuditLog failed: ' . $e->getMessage(), [
                'order_code' => $orderCode,
                'installation_orders_id' => $installationOrdersId,
                'event' => $event,
            ]);
            return null;
        }
    }
}

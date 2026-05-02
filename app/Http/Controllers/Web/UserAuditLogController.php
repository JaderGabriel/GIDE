<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserAuditLog;
use App\Support\AdminListPerPage;
use App\Support\UserAuditActionLabels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserAuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = AdminListPerPage::resolve($request);
        $catalog = UserAuditActionLabels::all();
        $actionFilters = $this->parseActionFilters($request, $catalog);
        $auditUserId = $this->parseAuditUserId($request);

        $base = $this->baseAuditQuery($actionFilters, $auditUserId);

        $items = (clone $base)
            ->with('actor')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $todayStart = now()->startOfDay();
        $statsBase = clone $base;
        $stats = [
            'total' => (int) (clone $statsBase)->count(),
            'today' => (int) (clone $statsBase)->where('occurred_at', '>=', $todayStart)->count(),
            'logins_today' => (int) (clone $statsBase)->where('action', 'auth.login')->where('occurred_at', '>=', $todayStart)->count(),
            'filtered' => $actionFilters !== [] || $auditUserId !== null,
        ];

        $auditUser = $auditUserId !== null ? User::query()->find($auditUserId) : null;

        return view('admin.user_audit_logs', [
            'items' => $items,
            'perPage' => $perPage,
            'stats' => $stats,
            'actionFilters' => $actionFilters,
            'actionThemes' => UserAuditActionLabels::filterThemes(),
            'actionLabels' => $catalog,
            'auditUserId' => $auditUserId,
            'auditUser' => $auditUser,
        ]);
    }

    /**
     * @param  list<string>  $actionFilters
     */
    private function baseAuditQuery(array $actionFilters, ?int $auditUserId): Builder
    {
        $query = UserAuditLog::query();
        if ($auditUserId !== null) {
            $query->relatedToUser($auditUserId);
        }
        if ($actionFilters !== []) {
            $query->whereIn('action', $actionFilters);
        }

        return $query;
    }

    /**
     * @param  array<string, string>  $catalog
     * @return list<string>
     */
    private function parseActionFilters(Request $request, array $catalog): array
    {
        $keys = [];
        $raw = $request->query('actions');
        if (is_array($raw)) {
            foreach ($raw as $v) {
                if (is_string($v) && array_key_exists($v, $catalog)) {
                    $keys[] = $v;
                }
            }
        } elseif (is_string($raw) && $raw !== '') {
            foreach (explode(',', $raw) as $part) {
                $v = trim($part);
                if ($v !== '' && array_key_exists($v, $catalog)) {
                    $keys[] = $v;
                }
            }
        }

        $legacy = (string) $request->query('action', '');
        if ($legacy !== '' && array_key_exists($legacy, $catalog)) {
            $keys[] = $legacy;
        }

        $keys = array_values(array_unique($keys));
        sort($keys);

        return $keys;
    }

    private function parseAuditUserId(Request $request): ?int
    {
        $raw = $request->query('audit_user_id');
        if ($raw === null || $raw === '') {
            return null;
        }
        if (! is_numeric($raw)) {
            return null;
        }
        $id = (int) $raw;

        return $id > 0 ? $id : null;
    }
}

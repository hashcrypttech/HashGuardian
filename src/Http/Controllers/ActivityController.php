<?php

namespace Hashcrypttech\HashGuardian\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Hashcrypttech\HashGuardian\Contracts\EntriesRepository;
use Hashcrypttech\HashGuardian\EntryType;

class ActivityController extends Controller
{
    public function index(Request $request, EntriesRepository $repository)
    {
        $filters = [
            'before' => $request->input('before'),
            'tag' => $request->input('tag'),
            'since' => $request->input('since'),
            'until' => $request->input('until'),
            'limit' => 50,
        ];

        if ($request->filled('user_id')) {
            $filters['tag'] = 'user:' . $request->input('user_id');
        }

        if ($request->filled('ip')) {
            $filters['tag'] = ($filters['tag'] ?? '') ? $filters['tag'] . ',ip:' . $request->input('ip') : 'ip:' . $request->input('ip');
        }

        $entries = $repository->get(EntryType::ACTIVITY, $filters);

        return view('hashguardian::activity.index', [
            'entries' => $entries,
        ]);
    }

    public function users(Request $request, EntriesRepository $repository)
    {
        $since = now()->subMinutes(5)->toDateTimeString();
        
        $entries = $repository->get(EntryType::ACTIVITY, [
            'since' => $since,
            'limit' => 1000,
        ]);

        // Group by user ID
        $activeUsers = [];
        foreach ($entries as $entry) {
            $content = $entry->content ?? [];
            $user = $content['user'] ?? null;
            if ($user && isset($user['id'])) {
                $userId = $user['id'];
                if (!isset($activeUsers[$userId])) {
                    $activeUsers[$userId] = [
                        'user' => $user,
                        'last_activity' => $entry->created_at,
                        'activity_count' => 0,
                        'sessions' => [],
                    ];
                }
                $activeUsers[$userId]['activity_count']++;
                if ($entry->created_at > $activeUsers[$userId]['last_activity']) {
                    $activeUsers[$userId]['last_activity'] = $entry->created_at;
                }
                $sessionId = $content['session_id'] ?? null;
                if ($sessionId && !in_array($sessionId, $activeUsers[$userId]['sessions'])) {
                    $activeUsers[$userId]['sessions'][] = $sessionId;
                }
            }
        }

        // Sort by last activity
        usort($activeUsers, function ($a, $b) {
            return strtotime($b['last_activity']) - strtotime($a['last_activity']);
        });

        return view('hashguardian::activity.active-users', [
            'activeUsers' => $activeUsers,
        ]);
    }

    public function user(string $id, Request $request, EntriesRepository $repository)
    {
        $entries = $repository->get(EntryType::ACTIVITY, [
            'tag' => 'user:' . $id,
            'before' => $request->input('before'),
            'since' => $request->input('since'),
            'until' => $request->input('until'),
            'limit' => 50,
        ]);

        $user = null;
        if ($entries->isNotEmpty()) {
            $firstEntry = $entries->first();
            $content = $firstEntry->content ?? [];
            $user = $content['user'] ?? null;
        }

        return view('hashguardian::activity.user', [
            'entries' => $entries,
            'userId' => $id,
            'user' => $user,
        ]);
    }

    public function session(string $id, Request $request, EntriesRepository $repository)
    {
        $entries = $repository->get(EntryType::ACTIVITY, [
            'tag' => 'session:' . $id,
            'limit' => 500,
        ]);

        if ($entries->isEmpty()) {
            abort(404);
        }

        // Sort by created_at ascending for timeline
        $entries = $entries->sortBy('created_at');

        $sessionInfo = [
            'session_id' => $id,
            'first_activity' => $entries->first()->created_at,
            'last_activity' => $entries->last()->created_at,
            'user' => null,
            'ip' => null,
        ];

        $firstEntry = $entries->first();
        $content = $firstEntry->content ?? [];
        if (isset($content['user'])) {
            $sessionInfo['user'] = $content['user'];
        }
        if (isset($content['ip'])) {
            $sessionInfo['ip'] = $content['ip'];
        }

        return view('hashguardian::activity.session', [
            'entries' => $entries,
            'sessionInfo' => $sessionInfo,
        ]);
    }
}

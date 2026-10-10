<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CommissionGuides
{
    public const VERSION = 1;

    public const GUIDES = [
        'welcome' => null,
        'evaluator-list' => 'evaluator',
        'evaluator-detail' => 'evaluator',
        'indicator-list' => 'indicator',
        'indicator-detail' => 'indicator',
        'judge-panel' => 'judge',
        'judge-detail' => 'judge',
        'judge-votes' => 'judge',
        'judge-done' => 'judge',
        'judge-empty' => 'judge',
    ];

    public function profiles(User $user): array
    {
        $profiles = [];
        if ($user->hasRole(Role::COMISSAO)) {
            if ($user->isEvaluator()) {
                $profiles[] = 'evaluator';
            }
            if ($user->isIndicator()) {
                $profiles[] = 'indicator';
            }
        }
        if ($user->isJudge()) {
            $profiles[] = 'judge';
        }

        return $profiles;
    }

    public function available(User $user): array
    {
        $profiles = $this->profiles($user);
        if ($profiles === []) {
            return [];
        }

        return array_keys(array_filter(self::GUIDES, fn ($profile) => $profile === null || in_array($profile, $profiles, true)));
    }

    public function completeTask(string $profile): void
    {
        $completed = session()->get('commission_guides.'.auth()->id().'.completed', []);
        session()->put('commission_guides.'.auth()->id().'.completed', array_values(array_unique([...$completed, $profile])));
    }

    public function state(User $user): array
    {
        $available = $this->available($user);
        if ($available === []) {
            return [];
        }

        $profiles = $this->profiles($user);
        $preferenceKey = 'commission_guides.'.$user->id.'.profile';
        if (in_array(request('guide_profile'), $profiles, true)) {
            session()->put($preferenceKey, request('guide_profile'));
        }
        $preferred = session()->get($preferenceKey);
        if (in_array($preferred, $profiles, true)) {
            $profiles = [$preferred, ...array_values(array_diff($profiles, [$preferred]))];
        }

        return [
            'profiles' => $this->profiles($user),
            'automaticProfiles' => array_values(array_diff($profiles, session()->get('commission_guides.'.$user->id.'.completed', []))),
            'entryUrls' => ['judge' => route('panel.julgar.index'), 'evaluator' => route('admin.registration.index', ['guide_profile' => 'evaluator']), 'indicator' => route('admin.registration.index', ['guide_profile' => 'indicator'])],
            'available' => $available,
            'version' => self::VERSION,
            'progress' => DB::table('user_guide_progress')->where('user_id', $user->id)
                ->where('version', self::VERSION)->whereIn('guide', $available)
                ->get(['guide', 'step', 'status'])->keyBy('guide')->all(),
        ];
    }
}

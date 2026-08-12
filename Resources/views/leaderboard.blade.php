@extends('layouts.app')

@section('title', __('Leaderboard'))

@section('content')
<div class="container oa-page">
    <div class="row">
        <div class="col-xs-12">
            <div class="oa-hero">
                <div>
                    <div class="oa-hero-kicker">{{ __('Leaderboard') }}</div>
                    <h2 class="oa-hero-title">{{ __('Hall of Fame') }}</h2>
                    <div class="oa-hero-sub">{{ __('XP, levels, and streaks — friendly competition, zero customer impact.') }}</div>
                </div>
                <div class="oa-hero-actions">
                    <a class="btn btn-default" href="{{ route('overflowachievement.my') }}">{{ __('My Progress') }}</a>
                    <a class="btn btn-primary" href="{{ route('overflowachievement.achievements') }}">{{ __('Trophies') }}</a>
                </div>
            </div>

            <div class="row">
                <div class="col-md-7">
                    <div class="oa-panel">
                        <div class="oa-panel-title">{{ __('Top Agents') }}</div>
                        <div class="table-responsive">
                            <table class="table table-striped oa-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('User') }}</th>
                                        <th>{{ __('Level') }}</th>
                                        <th>{{ __('XP') }}</th>
                                        <th>{{ __('Streak') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($top as $i => $row)
                                        @php
                                            $u = $users[$row->user_id] ?? null;
                                            $name = '';
                                            if ($u) {
                                                $name = trim(($u->first_name ?? '').' '.($u->last_name ?? ''));
                                            }
                                        @endphp
                                        <tr>
                                            <td>{{ $i+1 }}</td>
                                            <td>
                                                {{ $name !== '' ? $name : ('#'.$row->user_id) }}
                                            </td>
                                            <td><span class="oa-pill">{{ __('Level :n', ['n' => (int)$row->level]) }}</span></td>
                                            <td>{{ (int)$row->xp_total }}</td>
                                            <td>{{ (int)$row->streak_current }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="oa-panel">
                        <div class="oa-panel-title">{{ __('Recent Trophies') }}</div>
                        <div class="oa-feed">
                            @foreach ($recent_unlocks as $row)
                                @php
                                    $key = (string)$row->achievement_key;
                                    $is_level = substr($key, 0, 9) === 'level_up_';
                                    $def = $is_level ? null : ($defs[$key] ?? null);
                                    $title = $is_level ? __('Level Up') : ($def ? $def->display_title : \Modules\OverflowAchievement\Entities\Achievement::translateText('', $key, 'title'));
                                    if ($title === '') { $title = $key; }
                                    $rarity = $is_level ? 'epic' : ($def ? $def->rarity : 'common');
                                    $resolved_icon = $is_level ? null : \Modules\OverflowAchievement\Entities\Achievement::resolveIcon(
                                        $def ? $def->icon_type : 'img',
                                        $def ? $def->icon_value : 'icon_001.png',
                                        $key
                                    );
                                    $resolved_icon_url = $resolved_icon ? \Modules\OverflowAchievement\Entities\Achievement::iconUrl($resolved_icon['value']) : '';
                                    $u = $users[$row->user_id] ?? null;
                                    $name = '';
                                    if ($u) {
                                        $name = trim(($u->first_name ?? '').' '.($u->last_name ?? ''));
                                    }
                                @endphp
                                <div class="oa-feed-item oa-r-{{ $rarity }}">
                                    <div class="oa-feed-icon">
                                        @if ($is_level)
                                            <i class="glyphicon glyphicon-arrow-up"></i>
                                        @else
                                            <img class="oa-icon-img" alt="" src="{{ $resolved_icon_url }}" />
                                        @endif
                                    </div>
                                    <div class="oa-feed-body">
                                        <div class="oa-feed-title">{{ $title }}</div>
                                        <div class="oa-feed-meta">
                                            {{ $name !== '' ? $name : ('#'.$row->user_id) }} • {{ (!empty($row->unlocked_at) && method_exists($row->unlocked_at, 'diffForHumans')) ? $row->unlocked_at->diffForHumans() : '' }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

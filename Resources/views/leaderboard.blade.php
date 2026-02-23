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
                                                if ($name === '') {
                                                    $name = (string)($u->email ?? '');
                                                }
                                            }
                                        @endphp
                                        <tr>
                                            <td>{{ $i+1 }}</td>
                                            <td>
                                                {{ $name !== '' ? $name : ('#'.$row->user_id) }}
                                            </td>
                                            <td><span class="oa-pill">Lv {{ (int)$row->level }}</span></td>
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
                                    $is_level = str_starts_with($key, 'level_up_');
                                    $def = $is_level ? null : ($defs[$key] ?? null);
                                    $title = $is_level ? __('Level Up') : ($def ? $def->title : $key);
                                    $rarity = $is_level ? 'epic' : ($def ? $def->rarity : 'common');
                                    $iconType = $is_level ? 'fa' : ($def ? $def->icon_type : 'fa');
                                    $iconVal = $is_level ? 'fa-arrow-up' : ($def ? $def->icon_value : 'fa-trophy');
                                    $u = $users[$row->user_id] ?? null;
                                    $name = '';
                                    if ($u) {
                                        $name = trim(($u->first_name ?? '').' '.($u->last_name ?? ''));
                                        if ($name === '') {
                                            $name = (string)($u->email ?? '');
                                        }
                                    }
                                @endphp
                                <div class="oa-feed-item oa-r-{{ $rarity }}">
                                    <div class="oa-feed-icon">
                                        @if ($iconType === 'img' && !empty($iconVal))
                                            @php
                                                // Normalize stored icon values to an absolute URL/path that works
                                                // in subdirectory installs and for icon-pack filenames.
                                                $v = trim((string)$iconVal);
                                                $base = \Helper::getSubdirectory();
                                                if (preg_match('#^https?://#i', $v)) {
                                                    $src = $v;
                                                } elseif (strpos($v, '/modules/') === 0) {
                                                    $src = $base.$v;
                                                } elseif (strpos($v, 'modules/') === 0) {
                                                    $src = $base.'/'.$v;
                                                } elseif (strpos($v, '/') === false) {
                                                    $src = $base.'/modules/overflowachievement/icons/pack/'.$v;
                                                } elseif ($v !== '' && $v[0] !== '/') {
                                                    $src = $base.'/'.$v;
                                                } else {
                                                    $src = $base.$v;
                                                }
                                            @endphp
                                            <img class="oa-icon-img" data-oa-fallback-fa="fa-trophy" alt="" src="{{ $src }}" />
                                        @else
                                            @php
                                                $fa_raw = $iconVal ?: 'fa-trophy';
                                                $fa = $fa_raw;
                                                if (preg_match('/\bfa-[a-z0-9-]+\b/i', (string)$fa_raw, $m)) {
                                                    $fa = $m[0];
                                                }
                                            @endphp
                                            <i class="fa {{ $fa }}"></i>
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

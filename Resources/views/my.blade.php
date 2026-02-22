@extends('layouts.app')

@section('title', __('Achievement'))

@section('content')
<div class="container oa-page">
    <div class="row">
        <div class="col-xs-12">
            <div class="oa-hero">
                <div>
                    <div class="oa-hero-kicker">{{ __('My Progress') }}</div>
                    <h2 class="oa-hero-title">{{ __('Level') }} {{ (int)$stat->level }}</h2>
                    <div class="oa-hero-sub">{{ (int)$stat->xp_total }} XP • {{ __('Streak') }}: {{ (int)$stat->streak_current }} ({{ __('Best') }} {{ (int)$stat->streak_best }})</div>
                </div>
                <div class="oa-hero-actions">
                    <a class="btn btn-primary" href="{{ route('overflowachievement.achievements') }}">{{ __('Trophies') }}</a>
                    @if (\Option::get('overflowachievement.show_leaderboard', 1))
                        <a class="btn btn-default" href="{{ route('overflowachievement.leaderboard') }}">{{ __('Leaderboard') }}</a>
                    @endif
                </div>
            </div>

            @php
                $den = max(1, (int)$next_min - (int)$cur_min);
                $inLevel = max(0, (int)$stat->xp_total - (int)$cur_min);
                $progress = (int)round(($inLevel / $den) * 100);
                $progress = max(0, min(100, $progress));
            @endphp

            <div class="oa-progress-panel">
                <div class="oa-progress-top">
                    <div>
                        <div class="oa-progress-label">{{ __('XP to next level') }}</div>
                        <div class="oa-progress-value">{{ $inLevel }} / {{ $den }}</div>
                    </div>
                    <div class="oa-progress-badges">
                        <span class="oa-pill">{{ __('Actions') }}: {{ (int)($stat->actions_count ?? 0) }}</span>
                        <span class="oa-pill">{{ __('Closes') }}: {{ (int)($stat->closes_count ?? 0) }}</span>
                        <span class="oa-pill">{{ __('Replies') }}: {{ (int)($stat->first_replies_count ?? 0) }}</span>
                        <span class="oa-pill">{{ __('Notes') }}: {{ (int)($stat->notes_count ?? 0) }}</span>
                    </div>
                </div>
                <div class="oa-bar"><span style="width: {{ $progress }}%"></span></div>
            </div>

            <div class="oa-stat-grid">
                <div class="oa-stat">
                    <div class="oa-stat-icon"><i class="fa fa-check"></i></div>
                    <div class="oa-stat-label">{{ __('Closes') }}</div>
                    <div class="oa-stat-value">{{ (int)($stat->closes_count ?? 0) }}</div>
                </div>
                <div class="oa-stat">
                    <div class="oa-stat-icon"><i class="fa fa-reply"></i></div>
                    <div class="oa-stat-label">{{ __('Replies') }}</div>
                    <div class="oa-stat-value">{{ (int)($stat->first_replies_count ?? 0) }}</div>
                </div>
                <div class="oa-stat">
                    <div class="oa-stat-icon"><i class="fa fa-sticky-note"></i></div>
                    <div class="oa-stat-label">{{ __('Notes') }}</div>
                    <div class="oa-stat-value">{{ (int)($stat->notes_count ?? 0) }}</div>
                </div>
                <div class="oa-stat">
                    <div class="oa-stat-icon"><i class="fa fa-user"></i></div>
                    <div class="oa-stat-label">{{ __('Assigned') }}</div>
                    <div class="oa-stat-value">{{ (int)($stat->assigned_count ?? 0) }}</div>
                </div>
                <div class="oa-stat">
                    <div class="oa-stat-icon"><i class="fa fa-paperclip"></i></div>
                    <div class="oa-stat-label">{{ __('Attachments') }}</div>
                    <div class="oa-stat-value">{{ (int)($stat->attachments_count ?? 0) }}</div>
                </div>
                <div class="oa-stat">
                    <div class="oa-stat-icon"><i class="fa fa-address-card"></i></div>
                    <div class="oa-stat-label">{{ __('Customers') }}</div>
                    <div class="oa-stat-value">{{ (int)($stat->customers_created_count ?? 0) }}</div>
                </div>
            </div>

            <div class="oa-section">
                <div class="oa-section-title">{{ __('Recent Unlocks') }}</div>
                <div class="oa-recent">
                    @forelse ($recent as $row)
                        @php
                            $key = (string)$row->achievement_key;
                            $is_level = str_starts_with($key, 'level_up_');
                            $def = $is_level ? null : ($defs[$key] ?? null);
                            $title = $is_level ? __('Level Up') : ($def ? $def->title : $key);
                            $rarity = $is_level ? 'epic' : ($def ? $def->rarity : 'common');
                            $iconType = $is_level ? 'fa' : ($def ? $def->icon_type : 'fa');
                            $iconVal = $is_level ? 'fa-arrow-up' : ($def ? $def->icon_value : 'fa-trophy');
                        @endphp
                        <div class="oa-recent-item oa-r-{{ $rarity }}">
                            <div class="oa-recent-icon">
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
                            <div class="oa-recent-body">
                                <div class="oa-recent-title">{{ $title }}</div>
                                @if (!empty($row->quote_text))
                                    <div class="oa-recent-quote">“{{ $row->quote_text }}”</div>
                                @endif
                            </div>
                            <div class="oa-recent-date">{{ (!empty($row->unlocked_at) && method_exists($row->unlocked_at, 'format')) ? $row->unlocked_at->format('M j') : '' }}</div>
                        </div>
                    @empty
                        <div class="alert alert-info">{{ __('No achievements yet — go close something heroic.') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

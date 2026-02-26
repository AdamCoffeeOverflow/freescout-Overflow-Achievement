@extends('layouts.app')

@section('title', __('Achievement'))

@section('content')
<div class="container oa-page">
    <div class="row">
        <div class="col-xs-12">
            <div class="oa-hero">
                <div>
                    <div class="oa-hero-kicker">{{ __('Trophies') }}</div>
                    <h2 class="oa-hero-title">{{ __('Your Trophy Cabinet') }}</h2>
                    <div class="oa-hero-sub">{{ __('Unlock achievements as you close conversations, reply first, build streaks, and rack up XP.') }}</div>
                </div>
                <div class="oa-hero-actions">
                    <a class="btn btn-default" href="{{ route('overflowachievement.my') }}">{{ __('My Progress') }}</a>
                    @if (\Option::get('overflowachievement.show_leaderboard', 1))
                        <a class="btn btn-primary" href="{{ route('overflowachievement.leaderboard') }}">{{ __('Leaderboard') }}</a>
                    @endif
                </div>
            </div>

            <div class="oa-filters" role="toolbar" aria-label="{{ __('Filter trophies') }}">
                <button type="button" class="oa-filter-btn active" data-oa-filter="all">{{ __('All') }}</button>
                <button type="button" class="oa-filter-btn" data-oa-filter="unlocked">{{ __('Unlocked') }}</button>
                <button type="button" class="oa-filter-btn" data-oa-filter="locked">{{ __('Locked') }}</button>
            </div>

            <div class="oa-grid" id="oa-trophy-grid">
                @foreach ($defs as $def)
                    @php
                        $is_unlocked = isset($unlocked[$def->key]);
                        $u = $is_unlocked ? $unlocked[$def->key] : null;
                        $rar = $def->rarity ?? 'common';
                        $cardClass = $is_unlocked ? 'oa-card-unlocked' : 'oa-card-locked';

                        // Quote is shown only for unlocked trophies; missing quote keys safely fall back to empty strings.
                        $q = $is_unlocked ? ($quotes_by_key[$def->key] ?? null) : null;
                        $quote_text = (!empty($q) && !empty($q['text'])) ? (string)$q['text'] : '';
                        $quote_author = (!empty($q) && !empty($q['author'])) ? (string)$q['author'] : '';
	                        // Progress for this trophy (always defined, even if counts are missing)
	                        $current = isset($counts) && is_array($counts) ? (int)($counts[$def->trigger] ?? 0) : 0;
	                        $thr = (int)($def->threshold ?? 0);
	                        $pct = ($thr > 0) ? (int)min(100, floor(($current * 100) / $thr)) : 100;
	                        $unlocked_at = ($is_unlocked && !empty($u) && !empty($u->unlocked_at) && method_exists($u->unlocked_at, 'format'))
	                            ? $u->unlocked_at->format('Y-m-d H:i')
	                            : '';
                    @endphp
                    <div class="oa-card {{ $cardClass }} oa-r-{{ $rar }}"
                        role="button" tabindex="0"
                        data-oa-state="{{ $is_unlocked ? 'unlocked' : 'locked' }}"
                        data-oa-key="{{ e($def->key) }}"
                        data-oa-title="{{ e($def->title) }}"
                        data-oa-desc="{{ e($def->description) }}"
                        data-oa-rarity="{{ e($rar) }}"
                        data-oa-trigger="{{ e($def->trigger) }}"
                        data-oa-trigger-label="{{ e($trigger_labels[$def->trigger] ?? $def->trigger) }}"
                        data-oa-trigger-hint="{{ e($trigger_hints[$def->trigger] ?? '') }}"
                        data-oa-threshold="{{ (int)($def->threshold ?? 0) }}"
                        data-oa-current="{{ $current }}"
                        data-oa-progress="{{ $pct }}"
                        data-oa-xp="{{ (int)($def->xp_reward ?? 0) }}"
                        data-oa-icon-type="{{ e($def->icon_type ?? 'fa') }}"
                        data-oa-icon-value="{{ e($def->icon_value ?? 'fa-trophy') }}"
                        data-oa-unlocked-at="{{ e($unlocked_at) }}"
                        data-oa-quote="{{ e($quote_text) }}"
                        data-oa-quote-author="{{ e($quote_author) }}"
                    >
                        <div class="oa-card-top">
                            <div class="oa-card-icon">
                                @if (($def->icon_type ?? 'fa') === 'img' && !empty($def->icon_value))
                                    <img class="oa-icon-img" data-oa-fallback-fa="fa-trophy" alt="" src="{{ (\Illuminate\Support\Str::startsWith($def->icon_value, ['http://','https://','/']))
                                        ? $def->icon_value
                                        : (\Helper::getSubdirectory().'/modules/overflowachievement/icons/pack/'.$def->icon_value) }}" />
                                @else
                                    @php
                                        $fa_raw = $def->icon_value ?: 'fa-trophy';
                                        $fa = $fa_raw;
                                        if (preg_match('/\bfa-[a-z0-9-]+\b/i', (string)$fa_raw, $m)) {
                                            $fa = $m[0];
                                        }
                                    @endphp
                                    <i class="fa {{ $fa }}"></i>
                                @endif
                            </div>
                            <div class="oa-card-meta">
                                <div class="oa-card-title">{{ $def->title }}</div>
                                <div class="oa-card-desc">{{ $def->description }}</div>
                            </div>
                        </div>

                        <div class="oa-card-bottom">
                            <div class="oa-chip oa-chip-{{ $rar }}">{{ strtoupper($rar) }}</div>
                            <div class="oa-card-req">
                                <span class="oa-req">{{ $trigger_labels[$def->trigger] ?? $def->trigger }} ≥ {{ $def->threshold }}</span>
                            </div>
                        </div>

                        @if ($is_unlocked)
                            <div class="oa-card-unlock">
                                <span class="oa-check"><i class="fa fa-check"></i></span>
                                <span>{{ __('Unlocked') }}</span>
                                <span class="oa-date">{{ (!empty($u) && !empty($u->unlocked_at) && method_exists($u->unlocked_at, 'format')) ? $u->unlocked_at->format('Y-m-d') : '' }}</span>
                            </div>

                            @if (!empty($quote_text))
                                <div class="oa-card-quote">
                                    <span class="oa-card-quote-text">“{{ $quote_text }}”</span>
                                    @if (!empty($quote_author))
                                        <span class="oa-card-quote-author">— {{ $quote_author }}</span>
                                    @endif
                                </div>
                            @endif
                        @else
                            <div class="oa-card-overlay">
                                <i class="fa fa-lock"></i>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
<!-- Achievement details modal -->
<div class="oa-modal-backdrop" id="oa-ach-modal" style="display:none" aria-hidden="true">
  <div class="oa-modal" role="dialog" aria-modal="true" aria-labelledby="oa-ach-modal-title">
    <button type="button" class="oa-modal-close" data-oa-modal-close aria-label="Close">×</button>
    <div class="oa-modal-head">
      <div class="oa-modal-icon" data-oa-m="icon"></div>
      <div>
        <div class="oa-modal-kicker" data-oa-m="rarity"></div>
        <div class="oa-modal-title" id="oa-ach-modal-title" data-oa-m="title"></div>
      </div>
    </div>
    <div class="oa-modal-desc" data-oa-m="desc"></div>
    <div class="oa-modal-hint" data-oa-m="hint"></div>

    <div class="oa-modal-quote" data-oa-m="quote" style="display:none"></div>

    <div class="oa-modal-row">
      <div class="oa-modal-pill" data-oa-m="xp"></div>
      <div class="oa-modal-pill" data-oa-m="unlocked"></div>
      <div class="oa-modal-pill oa-modal-pill-scope" data-oa-m="scope" style="display:none"></div>
    </div>

    <div class="oa-modal-progress">
      <div class="oa-modal-progress-top">
        <div class="oa-modal-progress-label" data-oa-m="progressLabel"></div>
        <div class="oa-modal-progress-val" data-oa-m="progressVal"></div>
      </div>
      <div class="oa-bar oa-modal-bar"><span data-oa-m="bar"></span></div>
    </div>

    <div class="oa-modal-foot">
      <a class="btn btn-primary" href="{{ route("overflowachievement.my") }}">{{ __("My Progress") }}</a>
      <button type="button" class="btn btn-default" data-oa-modal-close>{{ __("Close") }}</button>
    </div>
  </div>
</div>

@endsection

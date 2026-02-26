    <div class="text-muted">{{ __('Create, edit, and deactivate trophies. Icons can be FontAwesome classes (fa-trophy) or module icon pack images.') }}</div>

    <div class="alert alert-info" style="margin-top:12px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <div>
                <strong>{{ __('Maintenance') }}</strong> — {{ __('If trophy icons are missing after caching/build issues, you can re-assign all trophies to use the bundled icon pack again.') }}
            </div>
            <form method="POST" action="{{ url(\Helper::getSubdirectory().'/modules/overflowachievement/admin/achievements/reassign-icons') }}" style="margin:0;">
                {{ csrf_field() }}
                <label class="checkbox" style="display:inline-block; margin:0 10px 0 0; vertical-align:middle;">
                    <input type="checkbox" name="reassign[confirm]" value="1" required>
                    {{ __('I understand this overwrites current trophy icons') }}
                </label>
                <button type="submit" class="btn btn-sm btn-primary" style="vertical-align:middle;">
                    <i class="fa fa-refresh"></i> {{ __('Re-assign trophy icons') }}
                </button>
            </form>
        </div>
    </div>

    @php
        // Map quote_id -> tone so we can group the dropdown (admin UX).
        $quoteToneById = [];
        foreach (($quote_buckets ?? []) as $tone => $ids) {
            foreach ((array)$ids as $qid) {
                $quoteToneById[$qid] = $tone;
            }
        }
    @endphp

    <div class="panel panel-default" style="margin-top:12px;">
        <div class="panel-heading"><strong>{{ __('Create New') }}</strong></div>
        <div class="panel-body">
            <form class="form-horizontal" method="POST" action="{{ url(\Helper::getSubdirectory().'/modules/overflowachievement/admin/achievements') }}" enctype="multipart/form-data">
                {{ csrf_field() }}

                <div class="form-group">
                    <label class="col-sm-2 control-label">{{ __('Title') }}</label>
                    <div class="col-sm-6">
                        <input type="text" class="form-control" name="achievement[title]" required>
                    </div>
                    <div class="col-sm-4">
                        <input type="text" class="form-control" name="achievement[key]" placeholder="key_optional">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-2 control-label">{{ __('Description') }}</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" name="achievement[description]" maxlength="255">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-2 control-label">{{ __('Mailbox') }}</label>
                    <div class="col-sm-4">
                        <select class="form-control" name="achievement[mailbox_id]">
                            <option value="">{{ __('All mailboxes (global)') }}</option>
                            @foreach (($mailboxes ?? []) as $mb)
                                @php $mbid = $mb['id'] ?? null; $mbname = $mb['name'] ?? ''; @endphp
                                @if($mbid)
                                    <option value="{{ $mbid }}">#{{ $mbid }} — {{ $mbname }}</option>
                                @endif
                            @endforeach
                        </select>
                        <div class="help-block">{{ __('Optional. Used to bias auto-selected motivation quotes for this trophy. Does not restrict who can earn it.') }}</div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-2 control-label">{{ __('Motivation quote') }}</label>
                    <div class="col-sm-3">
                        <select class="form-control" name="achievement[quote_tone]">
                            <option value="">{{ __('Auto (by rarity)') }}</option>
                            <option value="funny">{{ __('Funny') }}</option>
                            <option value="epic">{{ __('Epic') }}</option>
                            <option value="philosophical">{{ __('Philosophical') }}</option>
                        </select>
                        <div class="help-block">{{ __('Tone is optional. If set, we pick a unique quote from that vibe.') }}</div>
                    </div>
                    <div class="col-sm-7">
                        <div style="display:flex; gap:8px; align-items:center;">
                            <input type="text" class="form-control oa-quote-search" data-oa-target="oa-quote-select-create" placeholder="{{ __('Search quotes...') }}">
                            <select id="oa-quote-select-create" class="form-control oa-quote-select" name="achievement[quote_id]" data-oa-preview="#oa-quote-preview-create">
                                <option value="">{{ __('Auto (unique)') }}</option>
                                @php
                                    $groups = ['funny' => [], 'epic' => [], 'philosophical' => [], 'other' => []];
                                    foreach (($quote_library ?? []) as $q) {
                                        $id = $q['id'] ?? '';
                                        if (!$id) continue;
                                        $tone = $quoteToneById[$id] ?? 'other';
                                        $groups[$tone][] = $q;
                                    }
                                @endphp
                                @foreach (['funny' => __('Funny'), 'epic' => __('Epic'), 'philosophical' => __('Philosophical'), 'other' => __('Other')] as $k => $label)
                                    @if (!empty($groups[$k]))
                                        <optgroup label="{{ $label }}">
                                            @foreach ($groups[$k] as $q)
                                                @php $id = $q['id']; $txt = (string)($q['text'] ?? ''); @endphp
                                                <option value="{{ $id }}" data-oa-text="{{ e($txt) }}">{{ $id }} — {{ mb_substr($txt, 0, 80) }}@if(mb_strlen($txt)>80)…@endif</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div id="oa-quote-preview-create" class="help-block" style="margin-top:6px; font-style:italic;">{{ __('Auto: a unique quote will be assigned.') }}</div>
                        <div class="help-block" style="margin-top:6px;">
                            <details>
                                <summary>{{ __('Custom quote override') }}</summary>
                                <div style="margin-top:8px;">
                                    <textarea class="form-control" name="achievement[quote_text]" rows="2" placeholder="{{ __('Custom quote (optional)') }}"></textarea>
                                    <input type="text" class="form-control" name="achievement[quote_author]" style="margin-top:8px;" placeholder="{{ __('Author (optional)') }}">
                                </div>
                            </details>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-2 control-label">{{ __('Trigger') }}</label>
                    <div class="col-sm-3">
                        <select class="form-control" name="achievement[trigger]">
                            <option value="close_conversation">close_conversation</option>
                            <option value="first_reply">first_reply</option>
                            <option value="streak_days">streak_days</option>
                            <option value="xp_total">xp_total</option>
                        </select>
                    </div>
                    <label class="col-sm-2 control-label">{{ __('Threshold') }}</label>
                    <div class="col-sm-2">
                        <input type="number" class="form-control" name="achievement[threshold]" value="1" min="1">
                    </div>
                    <label class="col-sm-1 control-label">{{ __('XP') }}</label>
                    <div class="col-sm-2">
                        <input type="number" class="form-control" name="achievement[xp_reward]" value="0" min="0">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-2 control-label">{{ __('Rarity') }}</label>
                    <div class="col-sm-3">
                        <select class="form-control" name="achievement[rarity]">
                            <option value="common">common</option>
                            <option value="rare">rare</option>
                            <option value="epic">epic</option>
                            <option value="legendary">legendary</option>
                        </select>
                    </div>
                    <label class="col-sm-2 control-label">{{ __('Icon') }}</label>
                    <div class="col-sm-3">
                        <input type="text" class="form-control" name="achievement[icon_value]" value="fa-trophy" placeholder="fa-trophy">
                        <input type="hidden" name="achievement[icon_type]" value="fa">
                        <div class="oa-icon-preview" style="margin-top:8px; display:flex; align-items:center; gap:8px;">
                            <span class="text-muted">{{ __('Preview') }}:</span>
                            <span class="oa-icon-preview-slot" aria-hidden="true"><i class="fa fa-trophy"></i></span>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <input type="file" name="icon_file" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-sm-10 col-sm-offset-2">
                        <button type="button" class="btn btn-xs btn-default oa-icon-pack-toggle" data-oa-target="oa-icon-pack-create">
                            <i class="fa fa-th"></i> {{ __('Choose from icon pack') }}
                        </button>
                        <div id="oa-icon-pack-create" class="oa-icon-pack" style="display:none; margin-top:10px;">
                            @php $base = \Helper::getSubdirectory()."/modules/overflowachievement/icons/pack/"; @endphp
                            @for ($i=1; $i<=100; $i++)
                                @php $fn = sprintf("icon_%03d.png", $i); $url = $base.$fn; @endphp
                                <button type="button" class="oa-icon-choice" data-oa-url="{{ $url }}" title="{{ $fn }}">
                                    <img src="{{ $url }}" alt="" />
                                </button>
                            @endfor
                        </div>
                        <div class="help-block">{{ __('Pick an icon to set icon_type=img automatically.') }}</div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-sm-10 col-sm-offset-2">
                        <label class="checkbox" style="display:inline-block; margin-right:10px;">
                            <input type="checkbox" name="achievement[is_active]" value="1" checked> {{ __('Active') }}
                        </label>
                        <button type="submit" class="btn btn-success">{{ __('Create') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading"><strong>{{ __('Existing') }}</strong></div>
        <div class="panel-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>{{ __('Key') }}</th>
                            <th>{{ __('Title') }}</th>
                            <th>{{ __('Trigger') }}</th>
                            <th>{{ __('Threshold') }}</th>
                            <th>{{ __('Rarity') }}</th>
                            <th>{{ __('Active') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($achievements as $a)
                        <tr>
                            <td><code>{{ $a->key }}</code></td>
                            <td>{{ $a->title }}</td>
                            <td>{{ $a->trigger }}</td>
                            <td>{{ $a->threshold }}</td>
                            <td>{{ $a->rarity }}</td>
                            <td>{!! $a->is_active ? '<span class="label label-success">Yes</span>' : '<span class="label label-default">No</span>' !!}</td>
                            <td style="white-space:nowrap;">
                                <button class="btn btn-xs btn-default" type="button" data-toggle="collapse" data-target="#oa-edit-{{ $a->id }}">{{ __('Edit') }}</button>
                            </td>
                        </tr>
                        <tr class="collapse" id="oa-edit-{{ $a->id }}">
                            <td colspan="7">
                                <form class="form-horizontal" method="POST" action="{{ url(\Helper::getSubdirectory().'/modules/overflowachievement/admin/achievements/'.$a->id) }}" enctype="multipart/form-data">
                                    {{ csrf_field() }}
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">{{ __('Title') }}</label>
                                        <div class="col-sm-4">
                                            <input type="text" class="form-control" name="achievement[title]" value="{{ $a->title }}" required>
                                        </div>
                                        <label class="col-sm-2 control-label">{{ __('Trigger') }}</label>
                                        <div class="col-sm-2">
                                            <select class="form-control" name="achievement[trigger]">
                                                @foreach (['close_conversation','first_reply','streak_days','xp_total'] as $t)
                                                    <option value="{{ $t }}" @if($a->trigger===$t) selected @endif>{{ $t }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-sm-2">
                                            <input type="number" class="form-control" name="achievement[threshold]" value="{{ $a->threshold }}" min="1">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">{{ __('Description') }}</label>
                                        <div class="col-sm-10">
                                            <input type="text" class="form-control" name="achievement[description]" value="{{ $a->description }}">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">{{ __('Mailbox') }}</label>
                                        <div class="col-sm-4">
                                            @php $curMbid = (int)($a->mailbox_id ?? 0); @endphp
                                            <select class="form-control" name="achievement[mailbox_id]">
                                                <option value="" @if($curMbid===0) selected @endif>{{ __('All mailboxes (global)') }}</option>
                                                @foreach (($mailboxes ?? []) as $mb)
                                                    @php $mbid = (int)($mb['id'] ?? 0); $mbname = (string)($mb['name'] ?? ''); @endphp
                                                    @if($mbid>0)
                                                        <option value="{{ $mbid }}" @if($curMbid===$mbid) selected @endif>#{{ $mbid }} — {{ $mbname }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            <div class="help-block">{{ __('Optional. Used to bias auto-selected motivation quotes for this trophy.') }}</div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">{{ __('Motivation quote') }}</label>
                                        <div class="col-sm-3">
                                            <select class="form-control" name="achievement[quote_tone]">
                                                @php $toneVal = (string)($a->quote_tone ?? ''); @endphp
                                                <option value="" @if($toneVal==='') selected @endif>{{ __('Auto (by rarity)') }}</option>
                                                <option value="funny" @if($toneVal==='funny') selected @endif>{{ __('Funny') }}</option>
                                                <option value="epic" @if($toneVal==='epic') selected @endif>{{ __('Epic') }}</option>
                                                <option value="philosophical" @if($toneVal==='philosophical') selected @endif>{{ __('Philosophical') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-sm-7">
                                            <div style="display:flex; gap:8px; align-items:center;">
                                                <input type="text" class="form-control oa-quote-search" data-oa-target="oa-quote-select-{{ $a->id }}" placeholder="{{ __('Search quotes...') }}">
                                                <select id="oa-quote-select-{{ $a->id }}" class="form-control oa-quote-select" name="achievement[quote_id]" data-oa-preview="#oa-quote-preview-{{ $a->id }}">
                                                    <option value="" @if(empty($a->quote_id)) selected @endif>{{ __('Auto (unique)') }}</option>
                                                    @foreach (['funny' => __('Funny'), 'epic' => __('Epic'), 'philosophical' => __('Philosophical'), 'other' => __('Other')] as $k => $label)
                                                        @if (!empty($groups[$k]))
                                                            <optgroup label="{{ $label }}">
                                                                @foreach ($groups[$k] as $q)
                                                                    @php $id = $q['id']; $txt = (string)($q['text'] ?? ''); @endphp
                                                                    <option value="{{ $id }}" data-oa-text="{{ e($txt) }}" @if($a->quote_id===$id) selected @endif>{{ $id }} — {{ mb_substr($txt, 0, 80) }}@if(mb_strlen($txt)>80)…@endif</option>
                                                                @endforeach
                                                            </optgroup>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                            @php
                                                $previewTxt = '';
                                                if (!empty($a->quote_text)) {
                                                    $previewTxt = $a->quote_text;
                                                } elseif (!empty($a->quote_id)) {
                                                    foreach (($quote_library ?? []) as $q) {
                                                        if (($q['id'] ?? '') === $a->quote_id) { $previewTxt = (string)($q['text'] ?? ''); break; }
                                                    }
                                                }
                                            @endphp
                                            <div id="oa-quote-preview-{{ $a->id }}" class="help-block" style="margin-top:6px; font-style:italic;">@if($previewTxt)“{{ $previewTxt }}”@else{{ __('Auto: a unique quote will be assigned.') }}@endif</div>
                                            <div class="help-block" style="margin-top:6px;">
                                                <details>
                                                    <summary>{{ __('Custom quote override') }}</summary>
                                                    <div style="margin-top:8px;">
                                                        <textarea class="form-control" name="achievement[quote_text]" rows="2" placeholder="{{ __('Custom quote (optional)') }}">{{ $a->quote_text }}</textarea>
                                                        <input type="text" class="form-control" name="achievement[quote_author]" style="margin-top:8px;" placeholder="{{ __('Author (optional)') }}" value="{{ $a->quote_author }}">
                                                    </div>
                                                </details>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-2 control-label">{{ __('Rarity') }}</label>
                                        <div class="col-sm-2">
                                            <select class="form-control" name="achievement[rarity]">
                                                @foreach (['common','rare','epic','legendary'] as $r)
                                                    <option value="{{ $r }}" @if($a->rarity===$r) selected @endif>{{ $r }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <label class="col-sm-2 control-label">{{ __('Icon') }}</label>
                                        <div class="col-sm-3">
                                            <input type="text" class="form-control" name="achievement[icon_value]" value="{{ $a->icon_value }}">
                                            <input type="hidden" name="achievement[icon_type]" value="{{ $a->icon_type }}">
                                            <div class="oa-icon-preview" style="margin-top:8px; display:flex; align-items:center; gap:8px;">
                                                <span class="text-muted">{{ __('Preview') }}:</span>
                                                <span class="oa-icon-preview-slot" aria-hidden="true">
                                                    @if ($a->icon_type==='img')
                                                        <img class="oa-icon-img" data-oa-fallback-fa="fa-trophy" alt="" src="{{ $a->icon_value }}" />
                                                    @else
                                                        @php
                                                            $fa_raw = $a->icon_value ?: 'fa-trophy';
                                                            $fa = $fa_raw;
                                                            if (preg_match('/\bfa-[a-z0-9-]+\b/i', (string)$fa_raw, $m)) {
                                                                $fa = $m[0];
                                                            }
                                                        @endphp
                                                        <i class="fa {{ $fa }}"></i>
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-sm-3">
                                            <input type="file" name="icon_file" class="form-control">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <div class="col-sm-10 col-sm-offset-2">
                                            <button type="button" class="btn btn-xs btn-default oa-icon-pack-toggle" data-oa-target="oa-icon-pack-{{ $a->id }}">
                                                <i class="fa fa-th"></i> {{ __('Choose from icon pack') }}
                                            </button>
                                            <div id="oa-icon-pack-{{ $a->id }}" class="oa-icon-pack" style="display:none; margin-top:10px;">
                                                @php $base = \Helper::getSubdirectory()."/modules/overflowachievement/icons/pack/"; @endphp
                                                @for ($i=1; $i<=100; $i++)
                                                    @php $fn = sprintf("icon_%03d.png", $i); $url = $base.$fn; @endphp
                                                    <button type="button" class="oa-icon-choice" data-oa-url="{{ $url }}" title="{{ $fn }}">
                                                        <img src="{{ $url }}" alt="" />
                                                    </button>
                                                @endfor
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <div class="col-sm-10 col-sm-offset-2">
                                            <label class="checkbox" style="display:inline-block; margin-right:10px;">
                                                <input type="checkbox" name="achievement[is_active]" value="1" @if($a->is_active) checked @endif> {{ __('Active') }}
                                            </label>
                                            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                                        </div>
                                    </div>
                                </form>

                                <form method="POST" action="{{ url(\Helper::getSubdirectory().'/modules/overflowachievement/admin/achievements/'.$a->id.'/delete') }}" style="display:inline; margin-left:6px;">
                                    {{ csrf_field() }}
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Delete achievement?');">{{ __('Delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>


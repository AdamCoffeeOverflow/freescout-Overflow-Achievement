<div role="tabpanel" class="tab-pane" id="oa-tab-quotes">
                <div class="row">
                    <div class="col-sm-10">
                        <div class="panel panel-default">
                            <div class="panel-heading"><strong>{{ __('Mailbox quote generator') }}</strong></div>
                            <div class="panel-body">
                                <div class="text-muted" style="margin-bottom:10px;">
                                    {{ __('Pick which quote tones each mailbox should draw from. The module auto-builds a curated subset for that mailbox, keeping quotes unique per trophy when possible.') }}
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-striped" style="margin-bottom:0;">
                                        <thead>
                                            <tr>
                                                <th style="width:28%;">{{ __('Mailbox') }}</th>
                                                <th>{{ __('Tones') }}</th>
                                                <th style="width:20%;">{{ __('Max quotes') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="oa-mailbox-quotes">
                                        @foreach ($mailboxes as $mb)
                                            @php
                                                $r = $rules_arr[(string)$mb->id] ?? [];
                                                $sel_tones = (array)($r['tones'] ?? []);
                                                $limit = (int)($r['limit'] ?? 0);
                                            @endphp
                                            <tr class="oa-mailbox-quote-row" data-mailbox-id="{{ $mb->id }}">
                                                <td>
                                                    <strong>{{ $mb->name }}</strong>
                                                    <div class="text-muted" style="font-size:12px;">#{{ $mb->id }}</div>
                                                </td>
                                                <td>
                                                    @foreach ($tones as $k => $label)
                                                        <label class="checkbox-inline" style="margin-right:10px;">
                                                            <input type="checkbox" class="oa-mailbox-tone" value="{{ $k }}" @if(in_array($k, $sel_tones, true)) checked @endif />
                                                            {{ $label }}
                                                        </label>
                                                    @endforeach
                                                </td>
                                                <td>
                                                    <input type="number" min="0" step="1" class="form-control input-sm oa-mailbox-limit" value="{{ $limit > 0 ? $limit : '' }}" placeholder="{{ __('0 = all') }}" />
                                                    <div class="help-block" style="margin-bottom:0;">{{ __('Limits the generated pool size. 0 means no limit.') }}</div>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="alert alert-info" style="margin-top:12px; margin-bottom:0;">
                                    <strong>{{ __('How it works') }}</strong><br>
                                    {{ __('These settings do not restrict who can earn a trophy. They only influence which quote pool is used when auto-assigning a quote to a trophy with a mailbox set.') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-2">
                        <div class="alert alert-warning">
                            <strong>{{ __('Tip') }}</strong><br>
                            {{ __('Start with 2 tones and a limit of 40–60 for a mailbox. You can always expand later.') }}
                        </div>
                    </div>
                </div>
            </div>
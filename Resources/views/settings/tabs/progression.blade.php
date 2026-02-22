<div role="tabpanel" class="tab-pane" id="oa-tab-progression">
                <div class="row">
                    <div class="col-sm-8">
                        <div class="panel panel-default">
                            <div class="panel-heading"><strong>{{ __('Progression') }}</strong></div>
                            <div class="panel-body">

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">{{ __('Daily XP cap') }}</label>
                                    <div class="col-sm-9">
                                        <input type="number" class="form-control input-sized" name="settings[overflowachievement.caps.daily_xp]" value="{{ old('settings[overflowachievement.caps.daily_xp]', $settings_values['overflowachievement.caps.daily_xp'] ?? 0) }}" min="0" />
                                        <div class="help-block">{{ __('Prevents grind-farming. Set 0 to disable.') }}</div>
                                    </div>
                                </div>

                                <hr style="margin:18px 0;">
                                <div class="text-muted" style="margin-bottom:10px;">{{ __('XP rewards') }}</div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">{{ __('Close') }}</label>
                                    <div class="col-sm-9">
                                        <input type="number" class="form-control input-sized" name="settings[overflowachievement.xp.close_conversation]" value="{{ old('settings[overflowachievement.xp.close_conversation]', $settings_values['overflowachievement.xp.close_conversation'] ?? 0) }}" min="0" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">{{ __('First Reply') }}</label>
                                    <div class="col-sm-9">
                                        <input type="number" class="form-control input-sized" name="settings[overflowachievement.xp.first_reply]" value="{{ old('settings[overflowachievement.xp.first_reply]', $settings_values['overflowachievement.xp.first_reply'] ?? 0) }}" min="0" />
                                    </div>
                                </div>

                                <div class="oa-advanced-toggle text-muted" data-oa-advanced="xp" style="margin-top:6px;">
                                    <i class="glyphicon glyphicon-chevron-right"></i>{{ __('More rewarding actions') }}
                                </div>
                                <div class="oa-advanced" data-oa-advanced-panel="xp" style="margin-top:10px;">
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Note') }}</label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control input-sized" name="settings[overflowachievement.xp.note_added]" value="{{ old('settings[overflowachievement.xp.note_added]', $settings_values['overflowachievement.xp.note_added'] ?? 0) }}" min="0" />
                                            <div class="help-block">{{ __('Internal notes, capped per conversation per day.') }}</div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Assign') }}</label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control input-sized" name="settings[overflowachievement.xp.assigned]" value="{{ old('settings[overflowachievement.xp.assigned]', $settings_values['overflowachievement.xp.assigned'] ?? 0) }}" min="0" />
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Merge') }}</label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control input-sized" name="settings[overflowachievement.xp.merged]" value="{{ old('settings[overflowachievement.xp.merged]', $settings_values['overflowachievement.xp.merged'] ?? 0) }}" min="0" />
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Move') }}</label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control input-sized" name="settings[overflowachievement.xp.moved]" value="{{ old('settings[overflowachievement.xp.moved]', $settings_values['overflowachievement.xp.moved'] ?? 0) }}" min="0" />
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Forward') }}</label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control input-sized" name="settings[overflowachievement.xp.forwarded]" value="{{ old('settings[overflowachievement.xp.forwarded]', $settings_values['overflowachievement.xp.forwarded'] ?? 0) }}" min="0" />
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Attachment') }}</label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control input-sized" name="settings[overflowachievement.xp.attachment_added]" value="{{ old('settings[overflowachievement.xp.attachment_added]', $settings_values['overflowachievement.xp.attachment_added'] ?? 0) }}" min="0" />
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Customer created') }}</label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control input-sized" name="settings[overflowachievement.xp.customer_created]" value="{{ old('settings[overflowachievement.xp.customer_created]', $settings_values['overflowachievement.xp.customer_created'] ?? 0) }}" min="0" />
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Customer updated') }}</label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control input-sized" name="settings[overflowachievement.xp.customer_updated]" value="{{ old('settings[overflowachievement.xp.customer_updated]', $settings_values['overflowachievement.xp.customer_updated'] ?? 0) }}" min="0" />
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Create conversation') }}</label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control input-sized" name="settings[overflowachievement.xp.conversation_created]" value="{{ old('settings[overflowachievement.xp.conversation_created]', $settings_values['overflowachievement.xp.conversation_created'] ?? 0) }}" min="0" />
                                            <div class="help-block">{{ __('Awarded when an agent creates a new conversation (outbound/proactive).') }}</div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Edit subject') }}</label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control input-sized" name="settings[overflowachievement.xp.subject_changed]" value="{{ old('settings[overflowachievement.xp.subject_changed]', $settings_values['overflowachievement.xp.subject_changed'] ?? 0) }}" min="0" />
                                            <div class="help-block">{{ __('Capped once per ticket per day.') }}</div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Reply sent') }}</label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control input-sized" name="settings[overflowachievement.xp.reply_sent]" value="{{ old('settings[overflowachievement.xp.reply_sent]', $settings_values['overflowachievement.xp.reply_sent'] ?? 0) }}" min="0" />
                                            <div class="help-block">{{ __('Any agent reply (capped per ticket per day).') }}</div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Customer reply') }}</label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control input-sized" name="settings[overflowachievement.xp.customer_replied]" value="{{ old('settings[overflowachievement.xp.customer_replied]', $settings_values['overflowachievement.xp.customer_replied'] ?? 0) }}" min="0" />
                                            <div class="help-block">{{ __('Incoming customer reply on a conversation assigned to the agent (capped).') }}</div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Set pending') }}</label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control input-sized" name="settings[overflowachievement.xp.set_pending]" value="{{ old('settings[overflowachievement.xp.set_pending]', $settings_values['overflowachievement.xp.set_pending'] ?? 0) }}" min="0" />
                                            <div class="help-block">{{ __('Awarded when status is set to pending (once per ticket per day).') }}</div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Mark spam') }}</label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control input-sized" name="settings[overflowachievement.xp.marked_spam]" value="{{ old('settings[overflowachievement.xp.marked_spam]', $settings_values['overflowachievement.xp.marked_spam'] ?? 0) }}" min="0" />
                                            <div class="help-block">{{ __('Awarded when marking a ticket as spam (once per ticket).') }}</div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Delete ticket') }}</label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control input-sized" name="settings[overflowachievement.xp.deleted_conversation]" value="{{ old('settings[overflowachievement.xp.deleted_conversation]', $settings_values['overflowachievement.xp.deleted_conversation'] ?? 0) }}" min="0" />
                                            <div class="help-block">{{ __('Awarded when deleting a ticket (once per ticket).') }}</div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Merge customers') }}</label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control input-sized" name="settings[overflowachievement.xp.customer_merged]" value="{{ old('settings[overflowachievement.xp.customer_merged]', $settings_values['overflowachievement.xp.customer_merged'] ?? 0) }}" min="0" />
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Focus minute') }}</label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control input-sized" name="settings[overflowachievement.xp.focus_time]" value="{{ old('settings[overflowachievement.xp.focus_time]', $settings_values['overflowachievement.xp.focus_time'] ?? 0) }}" min="0" />
                                            <div class="help-block">{{ __('XP per focused minute viewing tickets (capped).') }}</div>
                                        </div>
                                    </div>

                                    

                                    <hr style="margin:18px 0;">
                                    <div class="text-muted" style="margin-bottom:10px;">{{ __('SLA bonuses') }}</div>

                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('First response') }}</label>
                                        <div class="col-sm-9">
                                            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                                                <div>
                                                    <div class="text-muted" style="font-size:12px;">{{ __('Ultra (minutes)') }}</div>
                                                    <input type="number" class="form-control input-sized" name="settings[overflowachievement.sla.first_response_ultra_minutes]" value="{{ old('settings[overflowachievement.sla.first_response_ultra_minutes]', $settings_values['overflowachievement.sla.first_response_ultra_minutes'] ?? 5) }}" min="0" />
                                                </div>
                                                <div>
                                                    <div class="text-muted" style="font-size:12px;">{{ __('Ultra XP') }}</div>
                                                    <input type="number" class="form-control input-sized" name="settings[overflowachievement.xp.sla_first_response_ultra]" value="{{ old('settings[overflowachievement.xp.sla_first_response_ultra]', $settings_values['overflowachievement.xp.sla_first_response_ultra'] ?? 12) }}" min="0" />
                                                </div>
                                                <div>
                                                    <div class="text-muted" style="font-size:12px;">{{ __('Fast (minutes)') }}</div>
                                                    <input type="number" class="form-control input-sized" name="settings[overflowachievement.sla.first_response_fast_minutes]" value="{{ old('settings[overflowachievement.sla.first_response_fast_minutes]', $settings_values['overflowachievement.sla.first_response_fast_minutes'] ?? 30) }}" min="0" />
                                                </div>
                                                <div>
                                                    <div class="text-muted" style="font-size:12px;">{{ __('Fast XP') }}</div>
                                                    <input type="number" class="form-control input-sized" name="settings[overflowachievement.xp.sla_first_response_fast]" value="{{ old('settings[overflowachievement.xp.sla_first_response_fast]', $settings_values['overflowachievement.xp.sla_first_response_fast'] ?? 8) }}" min="0" />
                                                </div>
                                            </div>
                                            <div class="help-block">{{ __('Awarded on the first agent reply relative to ticket creation.') }}</div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Follow-up after customer reply') }}</label>
                                        <div class="col-sm-9">
                                            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                                                <div>
                                                    <div class="text-muted" style="font-size:12px;">{{ __('Ultra (minutes)') }}</div>
                                                    <input type="number" class="form-control input-sized" name="settings[overflowachievement.sla.fast_reply_ultra_minutes]" value="{{ old('settings[overflowachievement.sla.fast_reply_ultra_minutes]', $settings_values['overflowachievement.sla.fast_reply_ultra_minutes'] ?? 5) }}" min="0" />
                                                </div>
                                                <div>
                                                    <div class="text-muted" style="font-size:12px;">{{ __('Ultra XP') }}</div>
                                                    <input type="number" class="form-control input-sized" name="settings[overflowachievement.xp.sla_fast_reply_ultra]" value="{{ old('settings[overflowachievement.xp.sla_fast_reply_ultra]', $settings_values['overflowachievement.xp.sla_fast_reply_ultra'] ?? 6) }}" min="0" />
                                                </div>
                                                <div>
                                                    <div class="text-muted" style="font-size:12px;">{{ __('Fast (minutes)') }}</div>
                                                    <input type="number" class="form-control input-sized" name="settings[overflowachievement.sla.fast_reply_minutes]" value="{{ old('settings[overflowachievement.sla.fast_reply_minutes]', $settings_values['overflowachievement.sla.fast_reply_minutes'] ?? 30) }}" min="0" />
                                                </div>
                                                <div>
                                                    <div class="text-muted" style="font-size:12px;">{{ __('Fast XP') }}</div>
                                                    <input type="number" class="form-control input-sized" name="settings[overflowachievement.xp.sla_fast_reply]" value="{{ old('settings[overflowachievement.xp.sla_fast_reply]', $settings_values['overflowachievement.xp.sla_fast_reply'] ?? 4) }}" min="0" />
                                                </div>
                                            </div>
                                            <div class="help-block">{{ __('Awarded when you reply soon after a customer message on an assigned ticket.') }}</div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Resolution speed') }}</label>
                                        <div class="col-sm-9">
                                            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                                                <div>
                                                    <div class="text-muted" style="font-size:12px;">{{ __('Rapid (hours)') }}</div>
                                                    <input type="number" class="form-control input-sized" name="settings[overflowachievement.sla.resolve_4h_hours]" value="{{ old('settings[overflowachievement.sla.resolve_4h_hours]', $settings_values['overflowachievement.sla.resolve_4h_hours'] ?? 4) }}" min="0" />
                                                </div>
                                                <div>
                                                    <div class="text-muted" style="font-size:12px;">{{ __('Rapid XP') }}</div>
                                                    <input type="number" class="form-control input-sized" name="settings[overflowachievement.xp.sla_resolve_4h]" value="{{ old('settings[overflowachievement.xp.sla_resolve_4h]', $settings_values['overflowachievement.xp.sla_resolve_4h'] ?? 12) }}" min="0" />
                                                </div>
                                                <div>
                                                    <div class="text-muted" style="font-size:12px;">{{ __('Same-day (hours)') }}</div>
                                                    <input type="number" class="form-control input-sized" name="settings[overflowachievement.sla.resolve_24h_hours]" value="{{ old('settings[overflowachievement.sla.resolve_24h_hours]', $settings_values['overflowachievement.sla.resolve_24h_hours'] ?? 24) }}" min="0" />
                                                </div>
                                                <div>
                                                    <div class="text-muted" style="font-size:12px;">{{ __('Same-day XP') }}</div>
                                                    <input type="number" class="form-control input-sized" name="settings[overflowachievement.xp.sla_resolve_24h]" value="{{ old('settings[overflowachievement.xp.sla_resolve_24h]', $settings_values['overflowachievement.xp.sla_resolve_24h'] ?? 8) }}" min="0" />
                                                </div>
                                            </div>
                                            <div class="help-block">{{ __('Awarded when closing a ticket soon after creation.') }}</div>
                                        </div>
                                    </div>

                                    
                                    <div class="text-muted" style="margin-bottom:10px;">{{ __('Anti-grind caps') }}</div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Notes/day per ticket') }}</label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control input-sized" name="settings[overflowachievement.limits.note_max_per_conversation_per_day]" value="{{ old('settings[overflowachievement.limits.note_max_per_conversation_per_day]', $settings_values['overflowachievement.limits.note_max_per_conversation_per_day'] ?? 3) }}" min="0" />
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Attachments/day per ticket') }}</label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control input-sized" name="settings[overflowachievement.limits.attachment_max_per_conversation_per_day]" value="{{ old('settings[overflowachievement.limits.attachment_max_per_conversation_per_day]', $settings_values['overflowachievement.limits.attachment_max_per_conversation_per_day'] ?? 3) }}" min="0" />
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Customer updates/day') }}</label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control input-sized" name="settings[overflowachievement.limits.customer_updates_max_per_day]" value="{{ old('settings[overflowachievement.limits.customer_updates_max_per_day]', $settings_values['overflowachievement.limits.customer_updates_max_per_day'] ?? 25) }}" min="0" />
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Replies/day per ticket') }}</label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control input-sized" name="settings[overflowachievement.limits.reply_max_per_conversation_per_day]" value="{{ old('settings[overflowachievement.limits.reply_max_per_conversation_per_day]', $settings_values['overflowachievement.limits.reply_max_per_conversation_per_day'] ?? 6) }}" min="0" />
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Customer replies/day per ticket') }}</label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control input-sized" name="settings[overflowachievement.limits.customer_reply_max_per_conversation_per_day]" value="{{ old('settings[overflowachievement.limits.customer_reply_max_per_conversation_per_day]', $settings_values['overflowachievement.limits.customer_reply_max_per_conversation_per_day'] ?? 6) }}" min="0" />
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Focus minutes/event') }}</label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control input-sized" name="settings[overflowachievement.limits.focus_max_minutes_per_event]" value="{{ old('settings[overflowachievement.limits.focus_max_minutes_per_event]', $settings_values['overflowachievement.limits.focus_max_minutes_per_event'] ?? 10) }}" min="1" />
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">{{ __('Focus minutes/day per ticket') }}</label>
                                        <div class="col-sm-9">
                                            <input type="number" class="form-control input-sized" name="settings[overflowachievement.limits.focus_max_minutes_per_conversation_per_day]" value="{{ old('settings[overflowachievement.limits.focus_max_minutes_per_conversation_per_day]', $settings_values['overflowachievement.limits.focus_max_minutes_per_conversation_per_day'] ?? 30) }}" min="1" />
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="alert alert-info">
                            <strong>{{ __('Leveling curve') }}</strong><br>
                            {{ __('Progression is gradual (quadratic curve). Early levels feel good, later levels are earned.') }}
                        </div>
                    </div>
                </div>
            </div>
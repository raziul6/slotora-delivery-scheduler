/* Slotora Delivery Scheduler — Admin JS */
(function($) {
    'use strict';

    var SloAdmin = {
        settings: {},
        init: function() {
            this.settings = slotoraAdmin.settings || {};
            this.bindTabs();
            this.bindDayChips();
            this.renderSlots();
            this.renderBlackouts();
            this.bindSlotActions();
            this.bindBlackoutActions();
            this.bindSave();
        },

        // ── Tabs ──────────────────────────────────────────────
        bindTabs: function() {
            $('.dsd-tab').on('click', function() {
                var tab = $(this).data('tab');
                $('.dsd-tab').removeClass('dsd-tab--active');
                $(this).addClass('dsd-tab--active');
                $('.dsd-tab-panel').removeClass('dsd-tab-panel--active');
                $('#dsd-tab-' + tab).addClass('dsd-tab-panel--active');
            });
        },

        // ── Day chips toggle ──────────────────────────────────
        bindDayChips: function() {
            $(document).on('change', '.dsd-day-chip input', function() {
                $(this).closest('.dsd-day-chip').toggleClass('dsd-day-chip--active', this.checked);
            });
        },

        // ── Slot rendering ────────────────────────────────────
        renderSlots: function() {
            var slots = this.settings.time_slots || [];
            var $list = $('#slotora-slots-list').empty();
            if (slots.length === 0) {
                slots = [
                    { label: '9:00 AM – 12:00 PM', value: '09:00-12:00', limit: 0 },
                    { label: '12:00 PM – 3:00 PM',  value: '12:00-15:00', limit: 0 },
                    { label: '3:00 PM – 6:00 PM',   value: '15:00-18:00', limit: 0 },
                ];
            }
            $.each(slots, function(i, slot) {
                $list.append(SloAdmin.buildSlotRow(slot));
            });
        },

        buildSlotRow: function(slot) {
            return $('<div class="dsd-slot-row">' +
                '<div class="dsd-slot-row__label">' +
                    '<span class="dsd-slot-label-small">Label</span>' +
                    '<input type="text" class="dsd-input" name="slotora_slot_label[]" value="' + SloAdmin.esc(slot.label || '') + '" placeholder="e.g. Morning 9am – 12pm" />' +
                '</div>' +
                '<div class="dsd-slot-row__value">' +
                    '<span class="dsd-slot-label-small">Value (internal)</span>' +
                    '<input type="text" class="dsd-input" name="slotora_slot_value[]" value="' + SloAdmin.esc(slot.value || '') + '" placeholder="09:00-12:00" />' +
                '</div>' +
                '<div class="dsd-slot-row__limit">' +
                    '<span class="dsd-slot-label-small">Max orders (0=∞)</span>' +
                    '<input type="number" class="dsd-input" name="slotora_slot_limit[]" value="' + parseInt(slot.limit || 0) + '" min="0" />' +
                '</div>' +
                '<div class="dsd-slot-row__actions">' +
                    '<button type="button" class="dsd-btn dsd-btn--danger dsd-remove-slot" aria-label="Remove slot">✕</button>' +
                '</div>' +
            '</div>');
        },

        bindSlotActions: function() {
            $('#slotora-add-slot').on('click', function() {
                $('#slotora-slots-list').append(SloAdmin.buildSlotRow({ label:'', value:'', limit:0 }));
            });
            $(document).on('click', '.dsd-remove-slot', function() {
                $(this).closest('.dsd-slot-row').remove();
            });
        },

        // ── Blackout dates ────────────────────────────────────
        renderBlackouts: function() {
            var dates = this.settings.blackout_dates || [];
            this.refreshBlackoutUI(dates);
        },

        refreshBlackoutUI: function(dates) {
            var $list = $('#slotora-blackout-list').empty();
            if (!dates || dates.length === 0) {
                $list.append('<span class="dsd-blackout-empty">No blackout dates set.</span>');
                return;
            }
            $.each(dates, function(i, date) {
                var display = SloAdmin.formatDate(date);
                $list.append(
                    '<span class="dsd-blackout-tag" data-date="' + SloAdmin.esc(date) + '">' +
                        display +
                        '<button type="button" class="dsd-blackout-tag__remove dsd-remove-blackout" aria-label="Remove">×</button>' +
                    '</span>'
                );
            });
        },

        bindBlackoutActions: function() {
            $('#slotora-add-blackout-btn').on('click', function() {
                var date = $('#slotora-new-blackout').val();
                if (!date) return;
                $.post(slotoraAdmin.ajaxUrl, {
                    action: 'slotora_add_blackout',
                    nonce: slotoraAdmin.nonce,
                    date: date
                }, function(res) {
                    if (res.success) {
                        SloAdmin.settings.blackout_dates = res.data.dates;
                        SloAdmin.refreshBlackoutUI(res.data.dates);
                        $('#slotora-new-blackout').val('');
                    }
                });
            });

            $(document).on('click', '.dsd-remove-blackout', function() {
                if (!confirm(slotoraAdmin.i18n.confirm)) return;
                var date = $(this).closest('.dsd-blackout-tag').data('date');
                $.post(slotoraAdmin.ajaxUrl, {
                    action: 'slotora_remove_blackout',
                    nonce: slotoraAdmin.nonce,
                    date: date
                }, function(res) {
                    if (res.success) {
                        SloAdmin.settings.blackout_dates = res.data.dates;
                        SloAdmin.refreshBlackoutUI(res.data.dates);
                    }
                });
            });
        },

        // ── Save ──────────────────────────────────────────────
        bindSave: function() {
            $('#slotora-save-btn').on('click', function() {
                var $btn = $(this).prop('disabled', true).text('Saving…');
                $.post(slotoraAdmin.ajaxUrl, {
                    action: 'slotora_save_settings',
                    nonce: slotoraAdmin.nonce,
                    data: $('#slotora-settings-form').serialize()
                }, function(res) {
                    $btn.prop('disabled', false).html(
                        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Save Settings'
                    );
                    if (res.success) {
                        SloAdmin.showToast(res.data.message || slotoraAdmin.i18n.saved, false);
                    } else {
                        SloAdmin.showToast(slotoraAdmin.i18n.error, true);
                    }
                }).fail(function() {
                    $btn.prop('disabled', false);
                    SloAdmin.showToast(slotoraAdmin.i18n.error, true);
                });
            });
        },

        // ── Helpers ───────────────────────────────────────────
        showToast: function(msg, isError) {
            var $t = $('#slotora-toast').removeClass('dsd-admin-toast--error');
            if (isError) $t.addClass('dsd-admin-toast--error');
            $t.text(msg).show();
            clearTimeout(SloAdmin._toastTimer);
            SloAdmin._toastTimer = setTimeout(function() { $('#slotora-toast').fadeOut(); }, 3000);
        },

        formatDate: function(ymd) {
            var d = new Date(ymd + 'T00:00:00');
            return d.toLocaleDateString(undefined, { year:'numeric', month:'short', day:'numeric' });
        },

        esc: function(s) {
            return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        }
    };

    // Expose so the Pro add-on block (separate IIFE below) can reuse helpers.
    window.SloAdmin = SloAdmin;

    $(function() { SloAdmin.init(); });

})(jQuery);

/* ── Pro features (loaded when Pro is active) ──────────────── */
(function($){
    if( typeof sloPro === 'undefined' ) return; // Pro not active

    // Run after the base SloAdmin.init() ready-callback so our .off('click')
    // cleanly replaces the free-only save handler with the combined one.
    $(function(){

    // Save Pro settings via the same Save button
    $('#slotora-save-btn').off('click').on('click', function(){
        var $btn = $(this).prop('disabled',true).text('Saving…');

        // Save free settings first
        var freeData = $('#slotora-settings-form').serialize();

        // Save Pro settings (zones_enabled, sms, whatsapp, deposit, reminder)
        $.post( sloPro.ajaxUrl, {
            action: 'slotora_pro_save_settings',
            nonce:  sloPro.nonce,
            data:   freeData
        }, function(proRes){
            // Also save free settings
            $.post( slotoraAdmin.ajaxUrl, {
                action: 'slotora_save_settings',
                nonce:  slotoraAdmin.nonce,
                data:   freeData
            }, function(freeRes){
                $btn.prop('disabled',false).html(
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Save Settings'
                );
                var ok = (proRes && proRes.success) || (freeRes && freeRes.success);
                SloAdmin.showToast( ok ? (slotoraAdmin.i18n.saved || 'Settings saved!') : (slotoraAdmin.i18n.error || 'Error'), !ok );
            });
        });
    });

    // SMS provider toggle
    $(document).on('change','#slotora_pro_sms_provider', function(){
        $('#dsd-twilio-fields').toggle( $(this).val()==='twilio' );
        $('#dsd-http-fields').toggle(   $(this).val()==='http' );
    });

    // Test SMS
    $(document).on('click','#dsd-test-sms-btn', function(){
        var $btn=$(this).prop('disabled',true).text('Sending…');
        $.post(sloPro.ajaxUrl,{action:'slotora_pro_test_sms',nonce:sloPro.nonce,to:$('#dsd-sms-test-to').val()},function(res){
            $btn.prop('disabled',false).text('Send Test SMS');
            SloAdmin.showToast( res.success ? 'Test SMS sent!' : 'Failed: '+(res.data&&res.data.message||''), !res.success );
        });
    });

    // Test WhatsApp
    $(document).on('click','#dsd-test-wa-btn', function(){
        var $btn=$(this).prop('disabled',true).text('Sending…');
        $.post(sloPro.ajaxUrl,{action:'slotora_pro_test_whatsapp',nonce:sloPro.nonce,to:$('#dsd-wa-test-to').val()},function(res){
            $btn.prop('disabled',false).text('Send Test WhatsApp');
            SloAdmin.showToast( res.success ? 'Test WhatsApp sent!' : 'Failed: '+(res.data&&res.data.message||''), !res.success );
        });
    });

    // Export CSV
    $(document).on('click','#dsd-export-btn', function(e){
        e.preventDefault();
        var from=$('#dsd-export-from').val(), to=$('#dsd-export-to').val();
        if(!from||!to){ SloAdmin.showToast('Select both dates',true); return; }
        window.location.href = sloPro.exportUrl+'&from='+encodeURIComponent(from)+'&to='+encodeURIComponent(to);
    });

    // Day summary
    $(document).on('click','#dsd-summary-btn', function(){
        var date=$('#dsd-summary-date').val(); if(!date) return;
        $.post(sloPro.ajaxUrl,{action:'slotora_pro_get_day_summary',nonce:sloPro.nonce,date:date},function(res){
            if(!res.success){$('#dsd-summary-output').html('<p style="color:#dc2626;font-size:13px;">Could not load.</p>');return;}
            var d=res.data;
            if(!d.total){$('#dsd-summary-output').html('<p style="color:#94a3b8;font-size:13px;">No deliveries on this date.</p>');return;}
            var rows=Object.entries(d.slots).map(function(e){return '<tr><td style="padding:7px 12px;border:1px solid #e2e8f0;">'+e[0]+'</td><td style="padding:7px 12px;border:1px solid #e2e8f0;font-weight:600;">'+e[1]+'</td></tr>';}).join('');
            $('#dsd-summary-output').html('<table style="width:100%;border-collapse:collapse;font-size:13px;"><thead><tr><th style="padding:7px 12px;border:1px solid #e2e8f0;background:#f8fafc;text-align:left;">Slot</th><th style="padding:7px 12px;border:1px solid #e2e8f0;background:#f8fafc;text-align:left;">Bookings</th></tr></thead><tbody>'+rows+'</tbody></table><p style="font-size:13px;font-weight:600;margin:8px 0 0;">Total: '+d.total+' orders</p>');
        });
    });

    // Zone management (reuse logic from Pro JS if available)
    if( typeof DSDPro !== 'undefined' ) {
        DSDPro.renderZones( sloPro.zones||[] );
        DSDPro.bindZoneActions();
    }

    }); // end ready

})(jQuery);

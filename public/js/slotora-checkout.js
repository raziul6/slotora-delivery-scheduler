/* Slotora Delivery Scheduler — Checkout JS */
(function($) {
    'use strict';

    var SloPicker = {
        selectedDate: null,
        selectedSlot: null,
        currentMonth: null,
        currentYear:  null,
        availableDates: [],

        init: function() {
            if (!$('#slotora-delivery-picker').length) return;

            this.availableDates = slotoraData.availableDates || [];

            // Start on first available date's month
            var now = new Date();
            this.currentMonth = now.getMonth();
            this.currentYear  = now.getFullYear();

            if (this.availableDates.length) {
                var first = new Date(this.availableDates[0] + 'T00:00:00');
                this.currentMonth = first.getMonth();
                this.currentYear  = first.getFullYear();
            }

            this.renderCalendar();
            this.bindNav();
            this.bindCheckout();
        },

        // ── Calendar rendering ────────────────────────────────
        renderCalendar: function() {
            var year  = this.currentYear;
            var month = this.currentMonth;

            var months = ['January','February','March','April','May','June',
                          'July','August','September','October','November','December'];
            $('.dsd-cal-month-label').text(months[month] + ' ' + year);

            var firstDay = new Date(year, month, 1).getDay();
            var daysInMonth = new Date(year, month + 1, 0).getDate();
            var today = new Date();
            today.setHours(0,0,0,0);

            var $grid = $('#slotora-cal-grid').empty();

            // Padding cells for start of month
            for (var p = 0; p < firstDay; p++) {
                $grid.append('<div class="dsd-cal-cell dsd-other-month"></div>');
            }

            for (var d = 1; d <= daysInMonth; d++) {
                var date = new Date(year, month, d);
                var ymd  = SloPicker.formatYMD(date);
                var cell = $('<div class="dsd-cal-cell"></div>').text(d).attr('data-date', ymd);

                if (date < today) {
                    cell.addClass('dsd-past');
                } else if (this.isAvailable(ymd)) {
                    cell.addClass('dsd-available');
                    if (ymd === this.selectedDate) {
                        cell.addClass('dsd-selected');
                    }
                } else {
                    cell.addClass('dsd-past');
                }

                if (date.toDateString() === today.toDateString()) {
                    cell.addClass('dsd-today');
                }

                $grid.append(cell);
            }

            // Disable prev button if at min available month
            var minDate = this.availableDates.length ? new Date(this.availableDates[0] + 'T00:00:00') : today;
            var minMonth = minDate.getMonth();
            var minYear  = minDate.getFullYear();
            $('.dsd-cal-prev').prop('disabled', (year < minYear || (year === minYear && month <= minMonth)));
        },

        isAvailable: function(ymd) {
            return this.availableDates.indexOf(ymd) !== -1;
        },

        bindNav: function() {
            var self = this;

            $(document).on('click', '.dsd-cal-prev', function() {
                self.currentMonth--;
                if (self.currentMonth < 0) { self.currentMonth = 11; self.currentYear--; }
                self.renderCalendar();
            });

            $(document).on('click', '.dsd-cal-next', function() {
                self.currentMonth++;
                if (self.currentMonth > 11) { self.currentMonth = 0; self.currentYear++; }
                self.renderCalendar();
            });

            // Date cell click
            $(document).on('click', '.dsd-cal-cell.dsd-available', function() {
                var date = $(this).data('date');
                self.selectDate(date);
            });

            // Slot button click
            $(document).on('click', '.dsd-slot-btn:not(.dsd-slot--full)', function() {
                var val = $(this).data('slot');
                self.selectSlot(val);
            });
        },

        selectDate: function(date) {
            this.selectedDate = date;
            this.selectedSlot = null;
            $('#slotora_delivery_date').val(date);
            $('#slotora_delivery_slot').val('');

            this.renderCalendar();
            this.showSelectedDate(date);
            this.clearValidation();

            if (slotoraData.hasTimeSlots) {
                this.loadSlots(date);
            }
        },

        showSelectedDate: function(date) {
            var d = new Date(date + 'T00:00:00');
            var label = d.toLocaleDateString(undefined, { weekday:'long', year:'numeric', month:'long', day:'numeric' });
            var $disp = $('#slotora-selected-date-display');
            $disp.text(label).show();
        },

        loadSlots: function(date) {
            var $group   = $('#slotora-slots-group');
            var $loading = $('#slotora-slots-loading');
            var $grid    = $('#slotora-slots-grid');
            var self     = this;

            $group.show();
            $loading.show();
            $grid.empty();

            $.post(slotoraData.ajaxUrl, {
                action: 'slotora_get_slots',
                nonce:  slotoraData.nonce,
                date:   date
            }, function(res) {
                $loading.hide();
                if (!res.success || !res.data.slots.length) {
                    $grid.html('<p style="color:#64748b;font-size:13px;">' + slotoraData.i18n.noSlots + '</p>');
                    return;
                }
                $.each(res.data.slots, function(i, slot) {
                    var availability = slot.full
                        ? slotoraData.i18n.slotFull
                        : (slot.limit > 0 ? (slot.limit - slot.booked) + ' spots left' : 'Available');
                    var fullClass  = slot.full ? 'dsd-slot--full' : '';
                    var selClass   = (slot.value === self.selectedSlot) ? 'dsd-slot--selected' : '';

                    $grid.append(
                        '<button type="button" class="dsd-slot-btn ' + fullClass + ' ' + selClass + '" data-slot="' + SloPicker.esc(slot.value) + '">' +
                            '<span class="dsd-slot-label">' + SloPicker.esc(slot.label) + '</span>' +
                            '<span class="dsd-slot-status">' + SloPicker.esc(availability) + '</span>' +
                            '<span class="dsd-slot-check">✓</span>' +
                        '</button>'
                    );
                });
            }).fail(function() {
                $loading.hide();
                $grid.html('<p style="color:#dc2626;font-size:13px;">Could not load slots. Please try again.</p>');
            });
        },

        selectSlot: function(val) {
            this.selectedSlot = val;
            $('#slotora_delivery_slot').val(val);
            this.clearValidation();

            $('.dsd-slot-btn').removeClass('dsd-slot--selected');
            $('.dsd-slot-btn[data-slot="' + val + '"]').addClass('dsd-slot--selected');
        },

        // ── Checkout validation ───────────────────────────────
        bindCheckout: function() {
            var self = this;

            $('body').on('checkout_place_order', function() {
                return self.validateBeforeSubmit();
            });

            // Also catch the native checkout button
            $(document.body).on('click', '#place_order', function() {
                return self.validateBeforeSubmit();
            });
        },

        validateBeforeSubmit: function() {
            if (!slotoraData.required) return true;

            if (!this.selectedDate) {
                this.showValidation(slotoraData.i18n.selectDate);
                $('html,body').animate({ scrollTop: $('#slotora-delivery-picker').offset().top - 80 }, 400);
                return false;
            }

            if (slotoraData.hasTimeSlots && !this.selectedSlot) {
                this.showValidation(slotoraData.i18n.selectSlot);
                $('html,body').animate({ scrollTop: $('#slotora-delivery-picker').offset().top - 80 }, 400);
                return false;
            }

            return true;
        },

        showValidation: function(msg) {
            $('#slotora-validation-msg').text(msg).show();
            $('#slotora-delivery-picker').css('border-color', '#fca5a5');
        },

        clearValidation: function() {
            $('#slotora-validation-msg').hide();
            $('#slotora-delivery-picker').css('border-color', '#c7dcf8');
        },

        // ── Utilities ─────────────────────────────────────────
        formatYMD: function(date) {
            var y = date.getFullYear();
            var m = String(date.getMonth() + 1).padStart(2, '0');
            var d = String(date.getDate()).padStart(2, '0');
            return y + '-' + m + '-' + d;
        },

        esc: function(s) {
            return String(s)
                .replace(/&/g,'&amp;').replace(/"/g,'&quot;')
                .replace(/</g,'&lt;').replace(/>/g,'&gt;');
        }
    };

    $(function() { SloPicker.init(); });

})(jQuery);

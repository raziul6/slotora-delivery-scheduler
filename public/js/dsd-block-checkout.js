/* Slotora Delivery Scheduler — Block (Gutenberg) Checkout date picker
 *
 * WooCommerce Block checkout renders our "Delivery Date" as a native
 * additional-checkout-field text input (controlled by React, with a floating
 * label). This script turns that bare text box into a click-to-open calendar
 * popover that only allows the dates the store has made available, then writes
 * the chosen YYYY-MM-DD value back into the React-controlled input so
 * WooCommerce saves it on order placement.
 *
 * We intentionally do NOT restructure WooCommerce's DOM (moving the input
 * breaks its floating label). The popover + icon are appended into the field's
 * existing container, which is already position:relative.
 *
 * The time-slot field is a native <select> and needs no enhancement.
 */
(function () {
    'use strict';

    var settings = {};
    try {
        settings = (window.wc && wc.wcSettings && wc.wcSettings.getSetting)
            ? (wc.wcSettings.getSetting('slotora-delivery-scheduler_data') || {})
            : {};
    } catch (e) {
        settings = {};
    }

    var AVAILABLE = Array.isArray(settings.availableDates) ? settings.availableDates : [];
    var I18N = settings.i18n || {};
    var MONTHS = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];

    var CAL_ICON = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';

    var stylesInjected = false;
    var openCalendar = null; // only one calendar open at a time

    /* ---- utilities ------------------------------------------------------ */

    function pad(n) { return String(n).length < 2 ? '0' + n : String(n); }

    function formatYMD(date) {
        return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
    }

    function parseYMD(ymd) {
        return new Date(ymd + 'T00:00:00');
    }

    // Push a value into a React-controlled input so its onChange fires and the
    // WooCommerce checkout store records it. Setting input.value alone is not
    // enough — React tracks the value internally.
    function setReactInputValue(input, value) {
        var setter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value');
        if (setter && setter.set) {
            setter.set.call(input, value);
        } else {
            input.value = value;
        }
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        input.dispatchEvent(new Event('blur', { bubbles: true }));
    }

    function injectStyles() {
        if (stylesInjected) return;
        stylesInjected = true;
        var css =
            '.dsd-bc-host input{cursor:pointer;padding-right:44px!important}' +
            '.dsd-bc-icon{position:absolute;right:14px;top:0;bottom:0;display:flex;align-items:center;color:#2563eb;pointer-events:none;z-index:2}' +
            '.dsd-bc-pop{position:absolute;z-index:9999;top:calc(100% + 6px);left:0;width:300px;max-width:calc(100vw - 32px);background:#fff;border:1px solid #e2e8f0;border-radius:14px;box-shadow:0 12px 32px rgba(15,23,42,.18);padding:14px;display:none;animation:dsdBcIn .12s ease-out}' +
            '.dsd-bc-pop.is-open{display:block}' +
            '@keyframes dsdBcIn{from{opacity:0;transform:translateY(-4px)}to{opacity:1;transform:none}}' +
            '.dsd-bc-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}' +
            '.dsd-bc-nav{border:none;background:#f1f5f9;color:#334155;border-radius:8px;width:32px;height:32px;cursor:pointer;font-size:18px;line-height:1;display:flex;align-items:center;justify-content:center;transition:background .12s}' +
            '.dsd-bc-nav:hover:not(:disabled){background:#e2e8f0}' +
            '.dsd-bc-nav:disabled{opacity:.3;cursor:default}' +
            '.dsd-bc-month{font-weight:600;font-size:14px;color:#0f172a}' +
            '.dsd-bc-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:2px}' +
            '.dsd-bc-dow{font-size:11px;font-weight:600;color:#94a3b8;text-align:center;padding:4px 0}' +
            '.dsd-bc-cell{position:relative;text-align:center;height:36px;line-height:36px;border-radius:9px;font-size:13px;color:#cbd5e1;user-select:none}' +
            '.dsd-bc-cell.is-available{color:#1e293b;cursor:pointer;font-weight:500}' +
            '.dsd-bc-cell.is-available:hover{background:#eff6ff;color:#1d4ed8}' +
            '.dsd-bc-cell.is-selected,.dsd-bc-cell.is-selected:hover{background:#2563eb;color:#fff;font-weight:600}' +
            '.dsd-bc-cell.is-today:not(.is-selected)::after{content:"";position:absolute;left:50%;bottom:5px;transform:translateX(-50%);width:4px;height:4px;border-radius:50%;background:#2563eb}' +
            '.dsd-bc-empty{color:#64748b;font-size:13px;padding:8px 4px;text-align:center}';
        var tag = document.createElement('style');
        tag.id = 'dsd-bc-styles';
        tag.textContent = css;
        document.head.appendChild(tag);
    }

    /* ---- calendar widget ------------------------------------------------ */

    function Calendar(input) {
        this.input = input;
        this.selected = AVAILABLE.indexOf(input.value) !== -1 ? input.value : null;

        var start = this.selected
            ? parseYMD(this.selected)
            : (AVAILABLE.length ? parseYMD(AVAILABLE[0]) : new Date());
        this.month = start.getMonth();
        this.year = start.getFullYear();

        this.build();
    }

    Calendar.prototype.build = function () {
        // Anchor to WooCommerce's existing field container (already relative).
        // Do NOT move the input — that breaks the floating label.
        var host = this.input.parentNode;
        host.classList.add('dsd-bc-host');
        if (getComputedStyle(host).position === 'static') {
            host.style.position = 'relative';
        }
        this.host = host;

        // Calendar icon overlay.
        var icon = document.createElement('span');
        icon.className = 'dsd-bc-icon';
        icon.innerHTML = CAL_ICON;
        host.appendChild(icon);

        var pop = document.createElement('div');
        pop.className = 'dsd-bc-pop';
        this.pop = pop;

        if (!AVAILABLE.length) {
            pop.innerHTML = '<div class="dsd-bc-empty">' +
                (I18N.noDates || 'No delivery dates are currently available.') + '</div>';
            host.appendChild(pop);
            return;
        }

        var head = document.createElement('div');
        head.className = 'dsd-bc-head';
        this.prev = document.createElement('button');
        this.prev.type = 'button';
        this.prev.className = 'dsd-bc-nav';
        this.prev.setAttribute('aria-label', 'Previous month');
        this.prev.textContent = '‹';
        this.label = document.createElement('span');
        this.label.className = 'dsd-bc-month';
        this.next = document.createElement('button');
        this.next.type = 'button';
        this.next.className = 'dsd-bc-nav';
        this.next.setAttribute('aria-label', 'Next month');
        this.next.textContent = '›';
        head.appendChild(this.prev);
        head.appendChild(this.label);
        head.appendChild(this.next);

        this.grid = document.createElement('div');
        this.grid.className = 'dsd-bc-grid';

        pop.appendChild(head);
        pop.appendChild(this.grid);
        host.appendChild(pop);

        var self = this;
        this.prev.addEventListener('click', function (e) { e.stopPropagation(); self.shift(-1); });
        this.next.addEventListener('click', function (e) { e.stopPropagation(); self.shift(1); });
        this.grid.addEventListener('click', function (e) {
            var cell = e.target.closest('.dsd-bc-cell.is-available');
            if (cell) self.pick(cell.getAttribute('data-date'));
        });

        // Open on click/focus of the input.
        this.input.addEventListener('click', function (e) { e.stopPropagation(); self.toggle(); });
        this.input.addEventListener('focus', function () { self.open(); });
        pop.addEventListener('click', function (e) { e.stopPropagation(); });

        this.render();
    };

    Calendar.prototype.open = function () {
        if (openCalendar && openCalendar !== this) openCalendar.close();
        this.pop.classList.add('is-open');
        openCalendar = this;
    };

    Calendar.prototype.close = function () {
        this.pop.classList.remove('is-open');
        if (openCalendar === this) openCalendar = null;
    };

    Calendar.prototype.toggle = function () {
        this.pop.classList.contains('is-open') ? this.close() : this.open();
    };

    Calendar.prototype.shift = function (dir) {
        this.month += dir;
        if (this.month < 0) { this.month = 11; this.year--; }
        else if (this.month > 11) { this.month = 0; this.year++; }
        this.render();
    };

    Calendar.prototype.pick = function (ymd) {
        this.selected = ymd;
        setReactInputValue(this.input, ymd);
        this.render();
        this.close();
    };

    Calendar.prototype.render = function () {
        if (!this.grid) return;

        this.label.textContent = MONTHS[this.month] + ' ' + this.year;

        var html = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']
            .map(function (d) { return '<div class="dsd-bc-dow">' + d + '</div>'; }).join('');

        var firstDow = new Date(this.year, this.month, 1).getDay();
        var daysIn = new Date(this.year, this.month + 1, 0).getDate();
        var todayYMD = formatYMD(new Date());

        for (var p = 0; p < firstDow; p++) {
            html += '<div class="dsd-bc-cell"></div>';
        }
        for (var d = 1; d <= daysIn; d++) {
            var ymd = this.year + '-' + pad(this.month + 1) + '-' + pad(d);
            var cls = 'dsd-bc-cell';
            if (AVAILABLE.indexOf(ymd) !== -1) cls += ' is-available';
            if (ymd === this.selected) cls += ' is-selected';
            if (ymd === todayYMD) cls += ' is-today';
            html += '<div class="' + cls + '" data-date="' + ymd + '">' + d + '</div>';
        }
        this.grid.innerHTML = html;

        // Disable "prev" past the first available month.
        var min = parseYMD(AVAILABLE[0]);
        var atMin = (this.year < min.getFullYear()) ||
            (this.year === min.getFullYear() && this.month <= min.getMonth());
        this.prev.disabled = atMin;
    };

    /* ---- bootstrap ------------------------------------------------------ */

    function findDateInput() {
        return document.querySelector(
            'input[data-dsd="date"], ' +
            'input[id*="delivery-date"], input[name*="delivery-date"]'
        );
    }

    function enhance() {
        var input = findDateInput();
        if (!input || input.dataset.dsdEnhanced) return;
        input.dataset.dsdEnhanced = '1';

        injectStyles();
        // The calendar drives the value; stop manual typing. We do NOT set a
        // placeholder — WooCommerce's floating label already serves that role,
        // and a placeholder would print on top of it.
        input.readOnly = true;
        input.setAttribute('aria-readonly', 'true');

        new Calendar(input);
    }

    function start() {
        enhance();
        // Block checkout renders asynchronously and can re-mount; keep watching.
        var observer = new MutationObserver(function () { enhance(); });
        observer.observe(document.body, { childList: true, subtree: true });

        // Close the open calendar when clicking elsewhere or pressing Escape.
        document.addEventListener('click', function () {
            if (openCalendar) openCalendar.close();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && openCalendar) openCalendar.close();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();

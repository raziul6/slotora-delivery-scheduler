<?php
defined( 'ABSPATH' ) || exit;
$slotora_pro_active = apply_filters( 'slotora_pro_is_active', false );
?>
<div class="dsd-admin-wrap" id="dsd-admin-wrap">

    <!-- ── Topbar ──────────────────────────────────────────────── -->
    <header class="dsd-topbar">
        <div class="dsd-topbar__brand">
            <span class="dsd-topbar__logo">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </span>
            <div class="dsd-topbar__titles">
                <h1 class="dsd-topbar__title">
                    <?php esc_html_e( 'Slotora Delivery Slots', 'slotora-delivery-scheduler' ); ?>
                    <?php if ( $slotora_pro_active ) : ?>
                        <span class="dsd-pro-pill">PRO</span>
                    <?php else : ?>
                        <span class="dsd-free-pill">FREE</span>
                    <?php endif; ?>
                </h1>
                <p class="dsd-topbar__sub">
                    <?php if ( $slotora_pro_active ) : ?>
                        <?php esc_html_e( 'Pro active — all features unlocked', 'slotora-delivery-scheduler' ); ?>
                    <?php else : ?>
                        <?php esc_html_e( 'Schedule deliveries at WooCommerce checkout', 'slotora-delivery-scheduler' ); ?>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <div class="dsd-topbar__actions">
            <span class="dsd-version-badge">v<?php echo esc_html( SLOTORA_VERSION ); ?></span>
            <button type="button" id="slotora-save-btn" class="dsd-btn dsd-btn--primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                <?php esc_html_e( 'Save Changes', 'slotora-delivery-scheduler' ); ?>
            </button>
        </div>
    </header>

    <div class="dsd-admin-toast" id="slotora-toast" style="display:none;"></div>

    <!-- ── App shell: sidebar + content ────────────────────────── -->
    <div class="dsd-shell">

        <!-- Sidebar nav -->
        <aside class="dsd-sidebar">
            <nav class="dsd-sidebar__nav" role="tablist">
                <button type="button" class="dsd-tab dsd-tab--active" data-tab="general" role="tab">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
                    <span><?php esc_html_e( 'General', 'slotora-delivery-scheduler' ); ?></span>
                </button>
                <button type="button" class="dsd-tab" data-tab="schedule" role="tab">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <span><?php esc_html_e( 'Schedule', 'slotora-delivery-scheduler' ); ?></span>
                </button>
                <button type="button" class="dsd-tab" data-tab="slots" role="tab">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span><?php esc_html_e( 'Time Slots', 'slotora-delivery-scheduler' ); ?></span>
                </button>
                <button type="button" class="dsd-tab" data-tab="blackout" role="tab">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                    <span><?php esc_html_e( 'Blackout Dates', 'slotora-delivery-scheduler' ); ?></span>
                </button>
                <button type="button" class="dsd-tab" data-tab="notifications" role="tab">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <span><?php esc_html_e( 'Notifications', 'slotora-delivery-scheduler' ); ?></span>
                </button>

                <?php if ( $slotora_pro_active ) : ?>
                <span class="dsd-sidebar__divider"><?php esc_html_e( 'Pro', 'slotora-delivery-scheduler' ); ?></span>
                <button type="button" class="dsd-tab dsd-tab--pro" data-tab="zones" role="tab">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>
                    <span><?php esc_html_e( 'Zones', 'slotora-delivery-scheduler' ); ?></span>
                </button>
                <button type="button" class="dsd-tab dsd-tab--pro" data-tab="sms" role="tab">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <span><?php esc_html_e( 'SMS', 'slotora-delivery-scheduler' ); ?></span>
                </button>
                <button type="button" class="dsd-tab dsd-tab--pro" data-tab="whatsapp" role="tab">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8z"/></svg>
                    <span><?php esc_html_e( 'WhatsApp', 'slotora-delivery-scheduler' ); ?></span>
                </button>
                <button type="button" class="dsd-tab dsd-tab--pro" data-tab="deposit" role="tab">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    <span><?php esc_html_e( 'Deposit', 'slotora-delivery-scheduler' ); ?></span>
                </button>
                <button type="button" class="dsd-tab dsd-tab--pro" data-tab="export" role="tab">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    <span><?php esc_html_e( 'Export', 'slotora-delivery-scheduler' ); ?></span>
                </button>
                <?php else : ?>
                <span class="dsd-sidebar__divider"><?php esc_html_e( 'Upgrade', 'slotora-delivery-scheduler' ); ?></span>
                <button type="button" class="dsd-tab dsd-tab--locked" data-tab="upgrade" role="tab">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <span><?php esc_html_e( 'Pro Features', 'slotora-delivery-scheduler' ); ?></span>
                </button>
                <?php endif; ?>
            </nav>

            <?php if ( ! $slotora_pro_active ) : ?>
            <div class="dsd-sidebar__promo">
                <span class="dsd-sidebar__promo-icon">⚡</span>
                <strong><?php esc_html_e( 'Go Pro', 'slotora-delivery-scheduler' ); ?></strong>
                <p><?php esc_html_e( 'SMS, WhatsApp, zones, deposits & more.', 'slotora-delivery-scheduler' ); ?></p>
                <a href="https://yoursite.com/slotora-delivery-scheduler-pro" target="_blank" rel="noopener" class="dsd-btn dsd-btn--pro"><?php esc_html_e( 'Upgrade →', 'slotora-delivery-scheduler' ); ?></a>
            </div>
            <?php endif; ?>
        </aside>

        <!-- Content -->
        <form id="slotora-settings-form" class="dsd-main">

        <!-- ── GENERAL ─────────────────────────────────────────── -->
        <div class="dsd-tab-panel dsd-tab-panel--active" id="dsd-tab-general">
            <div class="dsd-panel">
                <h2 class="dsd-panel__title"><?php esc_html_e( 'General Settings', 'slotora-delivery-scheduler' ); ?></h2>
                <p class="dsd-panel__desc"><?php esc_html_e( 'Control how the delivery picker appears at checkout.', 'slotora-delivery-scheduler' ); ?></p>

                <div class="dsd-field-row">
                    <div class="dsd-field-row__label">
                        <label><?php esc_html_e( 'Enable Plugin', 'slotora-delivery-scheduler' ); ?></label>
                        <span class="dsd-field-row__hint"><?php esc_html_e( 'Show delivery picker at checkout', 'slotora-delivery-scheduler' ); ?></span>
                    </div>
                    <div class="dsd-field-row__control">
                        <label class="dsd-toggle">
                            <input type="checkbox" name="slotora_enabled" value="yes" <?php checked( get_option( 'slotora_enabled', 'yes' ), 'yes' ); ?> />
                            <span class="dsd-toggle__track"><span class="dsd-toggle__thumb"></span></span>
                        </label>
                    </div>
                </div>

                <div class="dsd-field-row">
                    <div class="dsd-field-row__label">
                        <label for="dsd_date_label"><?php esc_html_e( 'Date Field Label', 'slotora-delivery-scheduler' ); ?></label>
                    </div>
                    <div class="dsd-field-row__control">
                        <input type="text" id="dsd_date_label" name="slotora_date_label" class="dsd-input" value="<?php echo esc_attr( get_option( 'slotora_date_label', __( 'Choose Delivery Date', 'slotora-delivery-scheduler' ) ) ); ?>" />
                    </div>
                </div>

                <div class="dsd-field-row">
                    <div class="dsd-field-row__label">
                        <label for="dsd_time_label"><?php esc_html_e( 'Time Slot Label', 'slotora-delivery-scheduler' ); ?></label>
                    </div>
                    <div class="dsd-field-row__control">
                        <input type="text" id="dsd_time_label" name="slotora_time_label" class="dsd-input" value="<?php echo esc_attr( get_option( 'slotora_time_label', __( 'Choose Time Slot', 'slotora-delivery-scheduler' ) ) ); ?>" />
                    </div>
                </div>

                <div class="dsd-field-row">
                    <div class="dsd-field-row__label">
                        <label><?php esc_html_e( 'Required Field', 'slotora-delivery-scheduler' ); ?></label>
                        <span class="dsd-field-row__hint"><?php esc_html_e( 'Block checkout if no date chosen', 'slotora-delivery-scheduler' ); ?></span>
                    </div>
                    <div class="dsd-field-row__control">
                        <label class="dsd-toggle">
                            <input type="checkbox" name="slotora_required" value="yes" <?php checked( get_option( 'slotora_required', 'yes' ), 'yes' ); ?> />
                            <span class="dsd-toggle__track"><span class="dsd-toggle__thumb"></span></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── SCHEDULE ────────────────────────────────────────── -->
        <div class="dsd-tab-panel" id="dsd-tab-schedule">
            <div class="dsd-panel">
                <h2 class="dsd-panel__title"><?php esc_html_e( 'Delivery Schedule', 'slotora-delivery-scheduler' ); ?></h2>
                <p class="dsd-panel__desc"><?php esc_html_e( 'Define how far ahead customers can book and which days you deliver.', 'slotora-delivery-scheduler' ); ?></p>

                <div class="dsd-field-row">
                    <div class="dsd-field-row__label">
                        <label for="dsd_min_days"><?php esc_html_e( 'Min Lead Days', 'slotora-delivery-scheduler' ); ?></label>
                        <span class="dsd-field-row__hint"><?php esc_html_e( '1 = tomorrow is earliest', 'slotora-delivery-scheduler' ); ?></span>
                    </div>
                    <div class="dsd-field-row__control">
                        <input type="number" id="dsd_min_days" name="slotora_min_days" class="dsd-input dsd-input--short" min="0" max="30" value="<?php echo esc_attr( get_option( 'slotora_min_days', 1 ) ); ?>" />
                        <span class="dsd-unit"><?php esc_html_e( 'days', 'slotora-delivery-scheduler' ); ?></span>
                    </div>
                </div>

                <div class="dsd-field-row">
                    <div class="dsd-field-row__label">
                        <label for="dsd_max_days"><?php esc_html_e( 'Max Booking Window', 'slotora-delivery-scheduler' ); ?></label>
                        <span class="dsd-field-row__hint"><?php esc_html_e( 'How many days ahead customers can book', 'slotora-delivery-scheduler' ); ?></span>
                    </div>
                    <div class="dsd-field-row__control">
                        <input type="number" id="dsd_max_days" name="slotora_max_days" class="dsd-input dsd-input--short" min="1" max="120" value="<?php echo esc_attr( get_option( 'slotora_max_days', 14 ) ); ?>" />
                        <span class="dsd-unit"><?php esc_html_e( 'days', 'slotora-delivery-scheduler' ); ?></span>
                    </div>
                </div>

                <div class="dsd-field-row">
                    <div class="dsd-field-row__label">
                        <label for="dsd_cutoff_hour"><?php esc_html_e( 'Same-Day Cutoff', 'slotora-delivery-scheduler' ); ?></label>
                        <span class="dsd-field-row__hint"><?php esc_html_e( 'After this hour today is "gone" (24h)', 'slotora-delivery-scheduler' ); ?></span>
                    </div>
                    <div class="dsd-field-row__control">
                        <input type="number" id="dsd_cutoff_hour" name="slotora_cutoff_hour" class="dsd-input dsd-input--short" min="0" max="23" value="<?php echo esc_attr( get_option( 'slotora_cutoff_hour', 14 ) ); ?>" />
                        <span class="dsd-unit">:00</span>
                    </div>
                </div>

                <div class="dsd-field-row dsd-field-row--top">
                    <div class="dsd-field-row__label">
                        <label><?php esc_html_e( 'Working Days', 'slotora-delivery-scheduler' ); ?></label>
                        <span class="dsd-field-row__hint"><?php esc_html_e( 'Days you accept deliveries', 'slotora-delivery-scheduler' ); ?></span>
                    </div>
                    <div class="dsd-field-row__control">
                        <div class="dsd-day-chips">
                            <?php
                            $slotora_working = (array) get_option( 'slotora_working_days', array( 'mon', 'tue', 'wed', 'thu', 'fri', 'sat' ) );
                            foreach ( array( 'mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat', 'sun' => 'Sun' ) as $slotora_day_key => $slotora_day_label ) :
                                ?>
                                <label class="dsd-day-chip <?php echo in_array( $slotora_day_key, $slotora_working, true ) ? 'dsd-day-chip--active' : ''; ?>">
                                    <input type="checkbox" name="slotora_working_days[]" value="<?php echo esc_attr( $slotora_day_key ); ?>" <?php checked( in_array( $slotora_day_key, $slotora_working, true ) ); ?> />
                                    <?php echo esc_html( $slotora_day_label ); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── TIME SLOTS ──────────────────────────────────────── -->
        <div class="dsd-tab-panel" id="dsd-tab-slots">
            <div class="dsd-panel">
                <h2 class="dsd-panel__title"><?php esc_html_e( 'Time Slots', 'slotora-delivery-scheduler' ); ?></h2>
                <p class="dsd-panel__desc"><?php esc_html_e( 'Define the time slots customers can choose. Set Limit to 0 for unlimited orders per slot.', 'slotora-delivery-scheduler' ); ?></p>
                <div id="slotora-slots-list"></div>
                <button type="button" id="slotora-add-slot" class="dsd-btn dsd-btn--outline">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <?php esc_html_e( 'Add Time Slot', 'slotora-delivery-scheduler' ); ?>
                </button>
            </div>
        </div>

        <!-- ── BLACKOUT ────────────────────────────────────────── -->
        <div class="dsd-tab-panel" id="dsd-tab-blackout">
            <div class="dsd-panel">
                <h2 class="dsd-panel__title"><?php esc_html_e( 'Blackout Dates', 'slotora-delivery-scheduler' ); ?></h2>
                <p class="dsd-panel__desc"><?php esc_html_e( 'Block specific dates — holidays, closures, etc.', 'slotora-delivery-scheduler' ); ?></p>
                <div class="dsd-blackout-add">
                    <input type="date" id="slotora-new-blackout" class="dsd-input" />
                    <button type="button" id="slotora-add-blackout-btn" class="dsd-btn dsd-btn--outline"><?php esc_html_e( '+ Block Date', 'slotora-delivery-scheduler' ); ?></button>
                </div>
                <div class="dsd-blackout-list" id="slotora-blackout-list"></div>
            </div>
        </div>

        <!-- ── NOTIFICATIONS ───────────────────────────────────── -->
        <div class="dsd-tab-panel" id="dsd-tab-notifications">
            <div class="dsd-panel">
                <h2 class="dsd-panel__title"><?php esc_html_e( 'Notifications', 'slotora-delivery-scheduler' ); ?></h2>
                <p class="dsd-panel__desc"><?php esc_html_e( 'Keep customers informed about their scheduled delivery.', 'slotora-delivery-scheduler' ); ?></p>

                <div class="dsd-field-row">
                    <div class="dsd-field-row__label">
                        <label><?php esc_html_e( 'Show in Order Emails', 'slotora-delivery-scheduler' ); ?></label>
                        <span class="dsd-field-row__hint"><?php esc_html_e( 'Include delivery date/slot in WooCommerce emails', 'slotora-delivery-scheduler' ); ?></span>
                    </div>
                    <div class="dsd-field-row__control">
                        <label class="dsd-toggle">
                            <input type="checkbox" name="slotora_show_in_email" value="yes" <?php checked( get_option( 'slotora_show_in_email', 'yes' ), 'yes' ); ?> />
                            <span class="dsd-toggle__track"><span class="dsd-toggle__thumb"></span></span>
                        </label>
                    </div>
                </div>

                <div class="dsd-field-row">
                    <div class="dsd-field-row__label">
                        <label><?php esc_html_e( 'Delivery Reminder Email', 'slotora-delivery-scheduler' ); ?></label>
                        <span class="dsd-field-row__hint"><?php esc_html_e( 'Send customers an email the day before delivery', 'slotora-delivery-scheduler' ); ?></span>
                    </div>
                    <div class="dsd-field-row__control">
                        <label class="dsd-toggle">
                            <input type="checkbox" name="slotora_reminder_email" value="yes" <?php checked( get_option( 'slotora_reminder_email', 'no' ), 'yes' ); ?> />
                            <span class="dsd-toggle__track"><span class="dsd-toggle__thumb"></span></span>
                        </label>
                    </div>
                </div>

                <?php if ( ! $slotora_pro_active ) : ?>
                <div class="dsd-pro-notice">
                    <div class="dsd-pro-notice__icon">⚡</div>
                    <div>
                        <strong><?php esc_html_e( 'Pro: SMS & WhatsApp + multi-day reminders', 'slotora-delivery-scheduler' ); ?></strong>
                        <p><?php esc_html_e( 'Upgrade to Pro to send reminders via SMS and WhatsApp, configure reminders 1–7 days before, and add delivery zone schedules.', 'slotora-delivery-scheduler' ); ?></p>
                        <a href="https://yoursite.com/slotora-delivery-scheduler-pro" target="_blank" rel="noopener" class="dsd-btn dsd-btn--pro"><?php esc_html_e( 'Upgrade to Pro →', 'slotora-delivery-scheduler' ); ?></a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ( $slotora_pro_active ) : ?>

        <!-- ── PRO: ZONES ──────────────────────────────────────── -->
        <div class="dsd-tab-panel" id="dsd-tab-zones">
            <?php do_action( 'slotora_pro_render_zones_tab' ); ?>
        </div>

        <!-- ── PRO: SMS ────────────────────────────────────────── -->
        <div class="dsd-tab-panel" id="dsd-tab-sms">
            <?php do_action( 'slotora_pro_render_sms_tab' ); ?>
        </div>

        <!-- ── PRO: WHATSAPP ───────────────────────────────────── -->
        <div class="dsd-tab-panel" id="dsd-tab-whatsapp">
            <?php do_action( 'slotora_pro_render_whatsapp_tab' ); ?>
        </div>

        <!-- ── PRO: DEPOSIT ────────────────────────────────────── -->
        <div class="dsd-tab-panel" id="dsd-tab-deposit">
            <?php do_action( 'slotora_pro_render_deposit_tab' ); ?>
        </div>

        <!-- ── PRO: EXPORT ─────────────────────────────────────── -->
        <div class="dsd-tab-panel" id="dsd-tab-export">
            <?php do_action( 'slotora_pro_render_export_tab' ); ?>
        </div>

        <?php else : ?>

        <!-- ── UPGRADE PAGE (free users) ───────────────────────── -->
        <div class="dsd-tab-panel" id="dsd-tab-upgrade">
            <div class="dsd-upgrade-panel">
                <div class="dsd-upgrade-hero">
                    <div class="dsd-upgrade-hero__icon">⚡</div>
                    <h2><?php esc_html_e( 'Unlock Pro Features', 'slotora-delivery-scheduler' ); ?></h2>
                    <p><?php esc_html_e( 'You\'re on the free plan. Upgrade once, use forever on your site.', 'slotora-delivery-scheduler' ); ?></p>
                    <a href="https://yoursite.com/slotora-delivery-scheduler-pro" target="_blank" rel="noopener" class="dsd-btn dsd-btn--pro dsd-btn--lg"><?php esc_html_e( 'Get Pro — $69/yr →', 'slotora-delivery-scheduler' ); ?></a>
                </div>
                <div class="dsd-upgrade-grid">
                    <?php
                    $slotora_features = array(
                        array( 'icon' => '📱', 'title' => __( 'SMS Reminders', 'slotora-delivery-scheduler' ), 'desc' => __( 'Send booking confirmations and reminders via Twilio or any HTTP SMS gateway.', 'slotora-delivery-scheduler' ) ),
                        array( 'icon' => '💬', 'title' => __( 'WhatsApp Notifications', 'slotora-delivery-scheduler' ), 'desc' => __( 'Send order confirmations and reminders via WhatsApp Business API.', 'slotora-delivery-scheduler' ) ),
                        array( 'icon' => '🗺️', 'title' => __( 'Delivery Zones', 'slotora-delivery-scheduler' ), 'desc' => __( 'Different schedules, time slots, and lead times per postcode area.', 'slotora-delivery-scheduler' ) ),
                        array( 'icon' => '📦', 'title' => __( 'Per-Product Rules', 'slotora-delivery-scheduler' ), 'desc' => __( 'Set different lead times or slot restrictions per product.', 'slotora-delivery-scheduler' ) ),
                        array( 'icon' => '💰', 'title' => __( 'Slot Deposit', 'slotora-delivery-scheduler' ), 'desc' => __( 'Charge a booking deposit to reduce no-shows — fixed amount or % of cart.', 'slotora-delivery-scheduler' ) ),
                        array( 'icon' => '📊', 'title' => __( 'CSV Export', 'slotora-delivery-scheduler' ), 'desc' => __( 'Download your delivery schedule as a CSV for drivers and fulfilment teams.', 'slotora-delivery-scheduler' ) ),
                        array( 'icon' => '⏰', 'title' => __( 'Multi-Day Reminders', 'slotora-delivery-scheduler' ), 'desc' => __( 'Send reminders 1, 2, 3 or 7 days before delivery — not just the day before.', 'slotora-delivery-scheduler' ) ),
                        array( 'icon' => '🔄', 'title' => __( 'Auto-Updates', 'slotora-delivery-scheduler' ), 'desc' => __( 'Get plugin updates directly in your WP dashboard via license key.', 'slotora-delivery-scheduler' ) ),
                    );
                    foreach ( $slotora_features as $slotora_feature ) :
                        ?>
                        <div class="dsd-upgrade-feature">
                            <span class="dsd-upgrade-feature__icon"><?php echo esc_html( $slotora_feature['icon'] ); ?></span>
                            <div>
                                <strong><?php echo esc_html( $slotora_feature['title'] ); ?></strong>
                                <p><?php echo esc_html( $slotora_feature['desc'] ); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php endif; ?>

        </form>
    </div><!-- .dsd-shell -->
</div>

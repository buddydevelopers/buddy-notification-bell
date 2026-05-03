# Buddy Notification Bell — Reform Plan

## Context

The plugin (v1.0.4, 100+ active installs on wp.org) is functional but aging. The code has security issues (raw `$_POST`, no nonces, unsafe SQL), a single basic setting, and a minimal UI. The goal is to reform it into a modern, LinkedIn/Facebook/X-style notification bell while keeping 100% backward compatibility for existing installs. No features removed. Same shortcode, same option keys, same filters. Just better code, better UI, new options.

---

## Backward Compatibility Guarantees (touch nothing)

| Item | Value | Reason |
|------|-------|--------|
| Shortcode | `[buddy_notification_bell]` | Users have it in posts/widgets |
| Option key | `make_default_visible` | Already saved in 100+ DBs |
| Filters | `buddy_bell_icon`, `buddy_theme_location`, `buddy_notification_output`, `bnb_get_js_settings` | Theme authors use these |
| Sound file | `assets/sounds/Pling-bell.mp3` | JS references it directly |
| Plugin slug | `buddy-notification-bell` | wp.org registered |

---

## New File Structure

```
buddy-notification-bell.php              ← rewritten: BNB_ constants, load core
uninstall.php                            ← new: clean option removal
index.php                                ← unchanged
assets/
  css/
    bnb-bell.css                         ← new: replaces style.css (modern UI)
    bnb-admin.css                        ← new: admin settings page styles
  js/
    bnb-bell.js                          ← new: replaces script.js (modern JS)
    bnb-admin.js                         ← new: admin page JS
  sounds/
    Pling-bell.mp3                       ← unchanged
includes/
  class-bd-bnb-core.php                  ← new: central loader
  class-bd-bnb-install.php               ← new: activate/deactivate, default options
  class-bd-bnb-bell.php                  ← new: replaces class-notification-bell-public.php
  class-bd-bnb-ajax.php                  ← new: heartbeat + mark-as-read AJAX
  class-bd-bnb-manager.php               ← new: notification fetching + formatting
  class-bd-bnb-settings.php              ← new: replaces class-notification-bell-settings.php
  class-notification-bell-public.php     ← KEEP (delete after new code confirmed working)
  class-notification-bell-settings.php   ← KEEP (delete after new code confirmed working)
languages/
  buddy-notification-bell.pot
```

---

## Step-by-Step Implementation Plan

### Step 1 — Main Plugin File (`buddy-notification-bell.php`)

**What changes:**
- Add `BNB_VERSION`, `BNB_PATH`, `BNB_URL` constants (replace the old `BUDDY_NOTIFICATION_BELL_PLUGINS_*` constants — keep old ones defined too for any theme that might use them)
- Replace direct class instantiation with `BD_BNB_Core::init()`
- Fix `bnb_admin_notice()`: add `include_once ABSPATH . 'wp-admin/includes/plugin.php';` before `is_plugin_active()`
- Fix plugin settings link: escape URLs with `esc_url()`
- Move `load_plugin_textdomain` to `init` hook (fixes LocoTranslate compatibility)

**Key guard:** Still hook on `bp_include` — no behavior change for existing users.

---

### Step 2 — `includes/class-bd-bnb-core.php` (new)

Central loader. No logic here — just includes and init calls.

```php
class BD_BNB_Core {
    public static function init() {
        // include all class files
        // instantiate BD_BNB_Bell, BD_BNB_Settings, BD_BNB_Ajax
    }
}
```

---

### Step 3 — `includes/class-bd-bnb-install.php` (new)

Activation hook: set default option values if not already set (so existing installs don't get overwritten).

```php
register_activation_hook( BNB_FILE, array( 'BD_BNB_Install', 'activate' ) );
```

Default options to set on first activation:
- `bnb_sound_enabled` → `'yes'`
- `bnb_bell_position` → `'right'`
- `bnb_show_count` → `'yes'`
- Leave `make_default_visible` alone (existing installs already have it)

---

### Step 4 — `includes/class-bd-bnb-settings.php` (rewrite)

**What changes (fixes P1 security issues):**
- Use WordPress Settings API: `register_setting()`, `add_settings_section()`, `add_settings_field()`
- Nonce handled automatically via `settings_fields()`
- Sanitize callbacks on every field
- Remove raw `$_POST` save block entirely

**New settings UI:**
- Tabbed layout using `.nav-tab-wrapper` (WP native admin pattern)
- Tab 1: General (bell position, count badge, sound)
- Tab 2: Display (shortcode usage info, menu auto-inject toggle)
- Modern admin card layout

**Settings registered:**

| Option | Sanitize | Default |
|--------|----------|---------|
| `make_default_visible` | `sanitize_text_field` | `''` |
| `bnb_sound_enabled` | `sanitize_text_field` | `'yes'` |
| `bnb_bell_position` | `sanitize_text_field` | `'right'` |
| `bnb_show_count` | `sanitize_text_field` | `'yes'` |

---

### Step 5 — `includes/class-bd-bnb-manager.php` (new)

Notification fetching logic extracted from the public class. Fixes SQL issues.

**`get_latest_notification_id( $user_id )`**
- Fix: use `$wpdb->prepare()` with placeholders correctly — no manual `implode` of component names into SQL string
- Safe approach: use `$wpdb->get_col( $wpdb->prepare(...) )` with IN via array of integers (component names are registered by BP, not user input, but still handle cleanly)

**`get_new_notifications( $user_id, $last_notified )`**
- Same SQL fix

**`format_notifications( $notifications )`**
- Returns array of formatted items: `[ 'text', 'href', 'time_diff', 'id' ]`
- `time_diff`: uses `human_time_diff()` + `__( '%s ago', 'buddy-notification-bell' )`

**`get_notification_description( $notification )`**
- Copy of existing `bnb_get_the_notification_description()` — no behavior change

---

### Step 6 — `includes/class-bd-bnb-ajax.php` (new)

Two responsibilities:

**A. Heartbeat handler** (moved from public class):
- Hook: `heartbeat_received`
- Fix: use `BD_BNB_Manager` methods
- Returns: `messages` array now includes `id`, `text`, `href`, `time_diff` per item (not just text strings)
- Keeps `last_notified` tracking

**B. Mark all as read** (new AJAX endpoint):
- `wp_ajax_bnb_mark_all_read`
- Checks nonce: `check_ajax_referer( 'bnb_mark_read', 'nonce' )`
- Checks: `is_user_logged_in()`
- Calls: `bp_notifications_mark_all_for_user( bp_loggedin_user_id() )`
- Returns: `wp_send_json_success()`

---

### Step 7 — `includes/class-bd-bnb-bell.php` (rewrite of class-notification-bell-public.php)

**What stays the same (backward compat):**
- Shortcode `[buddy_notification_bell]` → same callback
- Filter `buddy_bell_icon` → still applied to bell SVG
- Filter `buddy_theme_location` → still used for menu position
- Filter `buddy_notification_output` → still checked, returned early if set
- Menu injection via `wp_nav_menu_items`

**What changes:**
- `wp_head` inline script → `wp_localize_script()` on the enqueued `bnb-bell` script
- Asset handles: `bnb-bell` (JS), `bnb-bell-style` (CSS)
- Asset versioning: use `BNB_VERSION` constant (fixes "icon missing after update" bug)
- HTML output: modern semantic markup (see below)
- Nonce passed to JS for AJAX calls: `wp_create_nonce( 'bnb_mark_read' )`

**New HTML output** (LinkedIn/Facebook/X style):
```html
<div class="bnb-wrap" role="navigation" aria-label="Notifications">

  <button class="bnb-bell-btn" aria-expanded="false" aria-haspopup="true" aria-label="Notifications">
    [bell SVG icon — via buddy_bell_icon filter]
    <span class="bnb-count" aria-label="5 unread notifications">5</span>
    <!-- hidden if count is 0 -->
  </button>

  <div class="bnb-panel" role="dialog" aria-modal="false" aria-label="Notifications panel" hidden>
    <div class="bnb-panel-header">
      <h3 class="bnb-panel-title">Notifications</h3>
      <button class="bnb-mark-all-read" type="button">Mark all as read</button>
    </div>

    <ul class="bnb-list" role="list">
      <!-- Per notification: -->
      <li class="bnb-item bnb-unread" role="listitem">
        <span class="bnb-item-dot" aria-hidden="true"></span>
        <a href="{href}" class="bnb-item-link">
          <span class="bnb-item-text">{notification text}</span>
          <span class="bnb-item-time">{2 minutes ago}</span>
        </a>
      </li>
      <!-- Empty state: -->
      <li class="bnb-item bnb-empty">
        <span class="bnb-item-text">No new notifications</span>
      </li>
    </ul>

    <div class="bnb-panel-footer">
      <a href="{notifications page url}" class="bnb-see-all">See all notifications</a>
    </div>
  </div>

</div>
```

---

### Step 8 — `assets/css/bnb-bell.css` (new, replaces style.css)

Modern design inspired by LinkedIn/Facebook/X. Uses CSS custom properties so themes can override.

**CSS custom properties (on `.bnb-wrap`):**
```css
--bnb-accent: #0a66c2;        /* LinkedIn blue — unread dot + count badge */
--bnb-panel-bg: #ffffff;
--bnb-panel-shadow: 0 8px 24px rgba(0,0,0,0.15);
--bnb-panel-width: 380px;
--bnb-panel-radius: 8px;
--bnb-item-unread-bg: #f0f7ff;
--bnb-item-hover-bg: #f5f5f5;
--bnb-text-primary: #1d2129;
--bnb-text-secondary: #65676b;
--bnb-time-color: #0a66c2;
--bnb-font-size: 14px;
```

**Key design features:**
- Bell button: clean, no border, transparent background — fits any theme
- Count badge: red pill (top-right of bell), hidden when 0
- Bell ring animation: `@keyframes bnb-ring` (rotation wobble) triggered via `.bnb-bell-ringing` class added by JS
- Panel: white card, 380px wide, rounded corners, strong shadow — drops below the bell
- Notification items:
  - Full-width `<a>` tags for click area
  - Blue dot on the left (`.bnb-item.bnb-unread .bnb-item-dot`)
  - Bold text for unread items
  - Muted time stamp below the text (Facebook/LinkedIn style)
  - Light blue background on unread items (`--bnb-item-unread-bg`)
  - Hover state for all items
- Header: "Notifications" h3 + "Mark all as read" link on the right
- Footer: "See all notifications" centered link
- Scrollable list (max-height 400px, thin custom scrollbar)
- `display:none` toggle via JS (not slideToggle — CSS transition instead)

---

### Step 9 — `assets/js/bnb-bell.js` (new, replaces script.js)

Rewrites the jQuery script with cleaner patterns. Still uses jQuery (WP dependency) and Heartbeat API.

**Key improvements over old script.js:**
- Data from PHP via `wp_localize_script()` — object `bnbData` (not `bnb`) with: `lastNotified`, `nonce`, `ajaxUrl`, `soundUrl`, `showCount`, `soundEnabled`
- Panel toggle: uses `aria-expanded` + `hidden` attribute (not `slideToggle`)
- Close on outside click: use `document.addEventListener('click')` not `mouseup` — more reliable
- Bell animation: add `bnb-bell-ringing` class for 1s then remove
- New notifications injected into panel: build proper `<li>` elements from structured data (not `join` of raw strings — fixes XSS risk in old code where `data.messages.join()` was inserted as raw HTML)
- Mark all as read: AJAX call to `bnb_mark_all_read`, on success remove all `.bnb-unread` classes, hide count badge
- Prevent duplicate display: track shown `id`s in a JS Set, skip if already in list
- Sound: respects `bnbData.soundEnabled` setting
- Count: respects `bnbData.showCount` setting
- Keep `bnb:new_notifications` custom event (existing code may listen to it)
- Keep heartbeat-send/tick pattern — no breaking changes to the API

---

### Step 10 — `uninstall.php` (new)

Clean option removal on plugin delete:
```php
delete_option( 'make_default_visible' );
delete_option( 'bnb_sound_enabled' );
delete_option( 'bnb_bell_position' );
delete_option( 'bnb_show_count' );
```

---

### Step 11 — `assets/css/bnb-admin.css` + `assets/js/bnb-admin.js` (new)

Admin page styles and JS for the tabbed settings UI. Light enhancements — no heavy framework.

---

## Implementation Order

1. `buddy-notification-bell.php` (main file reform + constants)
2. `class-bd-bnb-core.php` (loader)
3. `class-bd-bnb-install.php` (activation defaults)
4. `class-bd-bnb-manager.php` (notification fetching, SQL fixes)
5. `class-bd-bnb-ajax.php` (heartbeat + mark-all-read)
6. `class-bd-bnb-bell.php` (frontend bell, new HTML output)
7. `class-bd-bnb-settings.php` (Settings API, tabbed UI)
8. `assets/css/bnb-bell.css` (modern bell UI)
9. `assets/js/bnb-bell.js` (modern JS)
10. `uninstall.php`
11. `assets/css/bnb-admin.css` + `assets/js/bnb-admin.js`
12. Remove old files: `class-notification-bell-public.php`, `class-notification-bell-settings.php`, `assets/css/style.css`, `assets/js/script.js`

---

## Security Fixes Applied (all P1 from support analysis)

| Issue | Fix |
|-------|-----|
| Raw `$_POST` in settings | Settings API with sanitize callbacks |
| No nonce on settings form | `settings_fields()` handles it |
| `json_encode()` → `wp_json_encode()` | In all PHP |
| Inline `<script>` in `wp_head` | `wp_localize_script()` |
| SQL `IN (...)` string concat | `$wpdb->prepare()` with correct placeholders |
| `is_plugin_active()` without plugin.php include | Add include |
| AJAX mark-read: no nonce | `check_ajax_referer()` |
| `data.messages.join()` XSS in JS | Build DOM elements programmatically |
| No output escaping in bell HTML | All output uses `esc_html()`, `esc_url()`, `esc_attr()` |

---

## Verification / Manual Test Checklist

1. Bell appears in primary nav (BuddyPress active, user logged in)
2. Shortcode `[buddy_notification_bell]` renders bell on a page
3. Existing `make_default_visible = 'yes'` still hides the auto bell
4. Bell shows unread count badge
5. Click bell → panel slides open, shows notifications with time ago
6. New notification arrives → bell rings animation, sound plays, count updates
7. "Mark all as read" clears unread state (blue dots gone, count hidden)
8. "See all notifications" link goes to BP notifications page
9. Panel closes on outside click
10. Settings page: all 4 options save and load correctly
11. Sound disabled setting: new notification arrives silently
12. Bell position: `bnb_bell_position = 'left'` floats bell to left in menu
13. No PHP warnings or notices in debug mode
14. No JS console errors
15. Plugin activates cleanly on fresh install (default options set)
16. Plugin deactivates without errors

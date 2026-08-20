# GS Conversion Tools

Quiz, price calculator, quote-form prefill and social-proof counters for
Guerilla Steel Stables.

---

## What this plugin stores

**Nothing.** There is no database footprint at all:

| | |
|---|---|
| `wp_options` entries | none |
| Post meta / user meta | none |
| Transients | none |
| Scheduled cron events | none |
| Custom tables | none |
| Custom post types / taxonomies | none |
| Files written to uploads | none |

Every value is calculated on the fly from the date or from the pricing config
in `includes/class-gs-pricing.php`.

**To remove the plugin completely, delete the folder.** Nothing is orphaned, and
no `uninstall.php` is required because there is nothing to clean up.

The one thing deletion *does* leave behind is shortcodes sitting in page
content — WordPress prints an unrecognised shortcode as literal text. Search
your content for the tags below before removing.

## Shortcodes

| Shortcode | Renders |
|---|---|
| `[gs_quiz]` | 6-question stable finder with a priced recommendation |
| `[gs_stable_calc]` | Size + bays + installation price calculator |
| `[gs_monthly_progress]` | Projects in progress this month |
| `[gs_year_progress]` | Stables delivered this year |
| `[gs_usage_count]` | People using the tool this month |
| `[gs_monthly_downloads]` | Downloads counter with "last X hours ago" |

### Legacy unprefixed names

These older names still work so content migrated from the previous site keeps
rendering:

`[simple_stable_calc]` · `[monthly_progress]` · `[year_progress]` ·
`[usage_count]` · `[monthly_downloads]`

They are registered on `init` at priority 20 and **only if nothing else has
claimed the name**, because tags this generic could legitimately belong to
another plugin and `add_shortcode()` overwrites silently.

**To retire them:** search page content for the five tags, swap each to its
`gs_`-prefixed equivalent, then delete `register_legacy_shortcode()` in
`class-gs-calculator.php` and `register_legacy_shortcodes()` plus the
`LEGACY_SHORTCODES` constant in `class-gs-social-proof.php`.

### Shortcode attributes

```
[gs_stable_calc default_size="4x4" default_bays="1" show_title="no" quote_url="/stable-quote/"]
[gs_quiz quote_url="/stables/quote/" learn_more_url="/stables-base-model-compare"]
[gs_monthly_downloads seed_start="2" seed_end="10"]
```

`quote_url` and `learn_more_url` must be a site-relative path or an `http(s)`
URL. Anything else falls back to the default — this is enforced in both PHP and
JavaScript.

## Everything else it registers

Useful if you ever need to grep for this plugin's footprint in a theme or
another plugin.

- **Constants** — `GS_CT_VERSION`, `GS_CT_DIR`, `GS_CT_URL`
- **Classes** — `GS_Pricing`, `GS_Calculator`, `GS_Quiz`, `GS_Prefill`, `GS_Social_Proof`
- **Script / style handles** — `gs-pricing-config`, `gs-calculator`, `gs-quiz`, `gs-prefill`, `gs-tools`
- **CSS classes** — all prefixed `gs-` (`gs-quiz`, `gs-calc`, `gs-prefill`)
- **Global JS** — `window.GS_PRICING_CONFIG`, `window.GS_QUIZ_CONFIG`
- **URL parameters read** — the 16 listed in `ALLOWED_PARAMS` in `assets/js/gs-prefill.js` (`source`, `size`, `bays`, `install`, `estimated_price`, `base_price`, `install_price`, `starting`, `horses`, `horse_size`, `surface`, `climate`, `additions`, `anchors`, `roof`, `addon`). Anything else in the URL is ignored.

### Filters

| Filter | Default | Purpose |
|---|---|---|
| `gs_prefill_enabled` | `false` | Force the prefill script to load on a page without `source=quiz` / `source=calculator` in the URL |
| `gs_pricing_config_always` | `false` | Publish `window.GS_PRICING_CONFIG` on every page, not only pages using the quiz or calculator |

## Pricing

All prices live in **one place**: `GS_Pricing::get_config()` in
`includes/class-gs-pricing.php`.

The config is published to the browser as `window.GS_PRICING_CONFIG`, so
**anything added to it is public**. It deliberately contains only the two keys
the front end actually reads:

- `base` — the four stable sizes, each with `first`, `extra`, `install`, `label`
- `upgrades` — `pitchRoof`, `yokeGates`, `anchors`

The `retrofit`, `panels`, `roofExtension`, `tackRooms`, `gstRate`, `currency`,
`business` and `notes` blocks were removed because nothing read them — they are
in git history if any are ever needed again.

It is loaded only on pages that use the quiz or calculator, via a script
dependency, not on every page.

### Moving to WooCommerce product pricing

`get_config()` is the single seam. Return prices read from the WooCommerce
products instead of the literals, keeping the same array shape, and the
calculator and quiz JavaScript need **no changes at all** — they only ever read
`window.GS_PRICING_CONFIG.base` and `.upgrades`.

## Security notes

The plugin registers **no REST routes, no AJAX actions, no admin pages and no
admin POST handlers**, and it never touches the database. Its whole surface is
the shortcodes above plus two `wp_enqueue_scripts` hooks.

Values arriving from the URL are allow-listed, length-capped and inserted as
text nodes rather than HTML. URLs from shortcode attributes are scheme-checked
in both PHP and JavaScript. See PR #3 for the full review.

## Requirements

WordPress 5.0+, PHP 7.2+. No build step, no dependencies, no lockfile.

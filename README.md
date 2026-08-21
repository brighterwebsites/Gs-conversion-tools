# GS Conversion Tools

Quiz, price calculator, quote-form prefill and social-proof counters for
Guerilla Steel Stables, plus the WooCommerce storefront extras: count
shortcodes, A-Z catalog sorting, and category-aware add-to-cart button text.

---

## What this plugin stores

**Three transients, and nothing else:**

| | |
|---|---|
| `wp_options` entries | none |
| Post meta / user meta | none |
| Transients | `gs_stats_products`, `gs_stats_orders`, `gs_stats_customers` |
| Scheduled cron events | none |
| Custom tables | none |
| Custom post types / taxonomies | none |
| Files written to uploads | none |

Every other value is calculated on the fly from the date or from the pricing
config in `includes/class-gs-pricing.php`.

The three transients are the cached store counts behind `[gs_prod_count]` and
friends, held for six hours so a page render never pays for the aggregate
query. They are invalidated on stock movement, product saves, new or changed
orders, and user registration. They existed as of v2.1.0; before that the
plugin wrote nothing at all.

**To remove the plugin completely, delete the folder.** `uninstall.php` deletes
the three transients on the way out — they would expire within six hours
anyway — and nothing else is left behind.

The one thing deletion *does* leave behind is shortcodes sitting in page
content — WordPress prints an unrecognised shortcode as literal text. Search
your content for the tags below before removing.

## Shortcodes

| Shortcode | Renders |
|---|---|
| `[gs_quiz]` | 6-question stable finder with a priced recommendation |
| `[gs_stable_calc]` | Size + bays + installation price calculator |
| `[gs_monthly_progress]` | Projects in progress this month |
| `[gs_year_progress]` | Stables delivered this calendar year, resets 1 Jan |
| `[gs_usage_count]` | People using the tool this month |
| `[gs_monthly_downloads]` | Downloads counter with "last X hours ago" |
| `[gs_prod_count]` | Published in-stock product count |
| `[gs_order_count]` | Completed + processing orders |
| `[gs_customer_count]` | Registered customer accounts |
| `[gs_product_categories]` | Linked `<div>`s per category, zero-count categories skipped |
| `[gs_first_product_cat]` | The first product category of the current product |

The last five need WooCommerce. Without it they render as an empty string
rather than leaving the raw tag printed in the page — see
[Store shortcodes](#store-shortcodes).

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
[gs_year_progress min="5"]
```

`quote_url` and `learn_more_url` must be a site-relative path or an `http(s)`
URL. Anything else falls back to the default — this is enforced in both PHP and
JavaScript.

### Social proof counters

Every figure is derived from the date, so the same day always renders the same
number on every page and every refresh, and nothing is stored.

`[gs_year_progress]` counts the **calendar year** and resets on 1 January. Each
completed month contributes a figure drawn once from a year+month seed and
fixed for that month for all time, so the total only ever climbs; the current
month contributes pro rata by day. The per-month rate is the
`YEAR_MONTH_MIN` / `YEAR_MONTH_MAX` constants in `class-gs-social-proof.php`,
currently **24–28**. That is deliberately just under the ~28 the monthly
counter settles on, so the year figure reads conservatively: late August lands
near 200 rather than 224, and a full year near 310.

On 1 January the honest figure is `0`. Use `min="5"` if a bare zero reads badly
on the day.

Values are derived by hashing the seed, not by `srand()` / `rand()`. The old
helper seeded the global PRNG and then called bare `srand()` to "reset" it —
which reseeds it randomly — disturbing any other code in the same request that
depended on a seeded sequence.

## Store shortcodes

WooCommerce only. Counts are approximate trust signals, cached for six hours
and invalidated on stock movement, product changes, new/changed orders and user
registration. Scope: published, **in stock only** — out-of-stock products are
excluded.

Without WooCommerce active the counts return an empty string, and the two
category shortcodes return nothing / their fallback. The tags are always
registered, so a page never prints `[gs_prod_count]` as literal text.

`[gs_order_count]` is the better trust signal than `[gs_customer_count]`: guest
checkouts never create a user account, so the customer count undercounts real
purchasers.

### Rounding

Add `round="10|50|100|1000"` to any count to floor it to a friendly figure and
append `+`, so the number reads as a claim rather than a live meter:

```
[gs_prod_count round="10"]     ->  40+
[gs_order_count round="100"]   ->  3,200+
```

A value that lands exactly on the step still gets the `+` — exactly 40 products
with `round="10"` reads "40+". That matches CNS Site Functions, which this was
ported from. Values at or below the step are printed as-is, so a store with 7
products shows "7", not "0+".

### Category list

```
[gs_product_categories]
[gs_product_categories class="pill" wrap_class="pill-row" show_count="yes"]
[gs_product_categories parent="15" orderby="count" order="DESC" limit="6"]
```

`orderby` accepts `name`, `count`, `slug`, `menu_order`; anything else falls
back to `name`. Defaults are `class="gs-category"` and
`wrap_class="gs-category-list"`; the plugin ships no CSS for them, style them
in the theme. Every attribute reaching markup is escaped at output.

### First product category

For single product pages — returns the product's first category.

```
[gs_first_product_cat]
[gs_first_product_cat link="yes" class="pill"]
[gs_first_product_cat exclude="uncategorised,shop"]
[gs_first_product_cat fallback="Stables"]
[gs_first_product_cat field="slug"]
[gs_first_product_cat id="123"]
```

| Attribute | Default | Notes |
|---|---|---|
| `field` | `name` | `name`, `slug`, `id`, or `url` |
| `link` | `no` | `yes` wraps the label in a link to the category |
| `class` | — | Class for the link; only used with `link="yes"` |
| `exclude` | — | Comma separated slugs or IDs to skip, falls through to the next category |
| `fallback` | — | Text returned when there is no category to show |
| `orderby` | `default` | `name` sorts alphabetically instead |
| `id` | current post | Target a specific product |

**"First" means WooCommerce's own order** — the manual term ordering set on the
category screen, the same order shown in the product editor. It is not
alphabetical unless you pass `orderby="name"`. If a product sits in both a
parent and its child, whichever WooCommerce orders first wins; use `exclude=`
to steer that rather than relying on it.

Anywhere that is not a product — archives, pages, the blog — it returns the
fallback, which is empty by default. Safe to leave in a shared template.

## Catalog sorting

Adds **Sort by name: A to Z** and **Z to A** to the shop sort dropdown, and
makes both selectable as the store default in the Customizer (Appearance →
Customize → WooCommerce → Product Catalog → Default product sorting).

## Add to cart button text

The button names the product's category instead of saying "Add to cart":

| Where | Text |
|---|---|
| Shop and archive loops | **Order this** *Stable* |
| Single product page | **Get this** *Stable* |

The category is `GS_Product_Cat::first_term()` — the same call
`[gs_first_product_cat]` makes, so the button and any category badge on the
page can never name different categories. A product in several categories uses
the first; a product in none falls back to **item** ("Order this item").

Only purchasable, in-stock products are relabelled. WooCommerce says "Read
more" for a product that cannot be bought and hides the form when it is out of
stock, and "Order this Stable" over a button that does neither would be a lie.
External and grouped products report themselves as not purchasable, so their
own button text is left alone too.

Variable products **are** relabelled, which replaces WooCommerce's "Select
options" — the button still goes to the product page. Use the
`gs_add_to_cart_relabel` filter if that hint is worth keeping:

```php
add_filter( 'gs_add_to_cart_relabel', function ( $relabel, $product ) {
    return $product->is_type( 'variable' ) ? false : $relabel;
}, 10, 2 );
```

Category names are used exactly as entered, so a category called "Stables"
gives "Order this Stables". Rename the category, or override the wording with
`gs_add_to_cart_loop_template` / `gs_add_to_cart_single_template`.

## Everything else it registers

Useful if you ever need to grep for this plugin's footprint in a theme or
another plugin.

- **Constants** — `GS_CT_VERSION`, `GS_CT_DIR`, `GS_CT_URL`
- **Classes** — `GS_Pricing`, `GS_Calculator`, `GS_Quiz`, `GS_Prefill`, `GS_Social_Proof`,
  `GS_Product_Cat`, `GS_Stats`, `GS_Store_Shortcodes`, `GS_Catalog_Sort`, `GS_Cart_Button`
- **Functions** — `gs_ct_init_store()` (hooked to `plugins_loaded`)
- **Script / style handles** — `gs-pricing-config`, `gs-calculator`, `gs-quiz`, `gs-prefill`, `gs-tools`
- **CSS classes** — all prefixed `gs-` (`gs-quiz`, `gs-calc`, `gs-prefill`, `gs-category`, `gs-category-list`)
- **Global JS** — `window.GS_PRICING_CONFIG`, `window.GS_QUIZ_CONFIG`
- **URL parameters read** — the 16 listed in `ALLOWED_PARAMS` in `assets/js/gs-prefill.js` (`source`, `size`, `bays`, `install`, `estimated_price`, `base_price`, `install_price`, `starting`, `horses`, `horse_size`, `surface`, `climate`, `additions`, `anchors`, `roof`, `addon`). Anything else in the URL is ignored.

### Filters

| Filter | Default | Purpose |
|---|---|---|
| `gs_prefill_enabled` | `false` | Force the prefill script to load on a page without `source=quiz` / `source=calculator` in the URL |
| `gs_pricing_config_always` | `false` | Publish `window.GS_PRICING_CONFIG` on every page, not only pages using the quiz or calculator |
| `gs_ct_year_progress` | — | The rendered year-to-date delivery count. Args: `$total`, `$year` |
| `gs_ct_year_progress_salt` | `gs-stables-delivered` | Reshuffles which rate each month draws, if a year's pattern looks wrong |
| `gs_add_to_cart_relabel` | purchasable && in stock | Whether a product's button gets category-aware text. Args: `$relabel`, `$product`, `$context` (`loop` or `single`) |
| `gs_add_to_cart_loop_template` | `Order this %s` | Shop and archive button wording |
| `gs_add_to_cart_single_template` | `Get this %s` | Single product button wording |
| `gs_add_to_cart_fallback_term` | `item` | Word used when the product has no category. Args: `$term`, `$product` |

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
admin POST handlers**. Its whole surface is the shortcodes above, two
`wp_enqueue_scripts` hooks, and the WooCommerce filters listed under catalog
sorting and button text.

Database access is read-only and limited to `GS_Stats`: two aggregate `SELECT`s
against `wc_product_meta_lookup`, a `WP_User_Query` count, and WooCommerce's own
`wc_orders_count()`. No shortcode attribute or request value reaches any query —
the only interpolated values are `$wpdb`'s table names, which cannot be
prepared. Writes are the three cached counts, via `set_transient()`.

Values arriving from the URL are allow-listed, length-capped and inserted as
text nodes rather than HTML. URLs from shortcode attributes are scheme-checked
in both PHP and JavaScript. See PR #3 for the full review.

## Requirements

WordPress 5.0+, PHP 7.2+. No build step, no dependencies, no lockfile.

WooCommerce is **optional**. The quiz, calculator, prefill and social-proof
counters work without it; the store shortcodes, catalog sorting and button text
are skipped when it is not active, checked on `plugins_loaded` so load order
cannot get it wrong. The product count needs WooCommerce 3.6+ for the
`wc_product_meta_lookup` table. Order counts are HPOS-compatible — they use
`wc_orders_count()`, not a posts query.

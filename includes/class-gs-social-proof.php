<?php
/**
 * Social proof shortcodes — deterministic pseudo-random counters.
 *
 * [gs_monthly_progress]  – Projects in progress this month (0–28)
 * [gs_year_progress]     – Stables delivered this calendar year, resets 1 Jan
 * [gs_usage_count]       – People using the tool this month (5–128)
 * [gs_monthly_downloads] – Downloads counter with "last X hours ago" text
 */
class GS_Social_Proof {

    /** Per-month delivery rate behind [gs_year_progress], inclusive range. */
    const YEAR_MONTH_MIN = 24;
    const YEAR_MONTH_MAX = 28;

    /** Old unprefixed names, kept so content migrated from the previous site renders. */
    const LEGACY_SHORTCODES = [
        'monthly_progress',
        'year_progress',
        'usage_count',
        'monthly_downloads',
    ];

    public static function init() {
        add_shortcode( 'gs_monthly_progress',  [ __CLASS__, 'monthly_progress' ] );
        add_shortcode( 'gs_year_progress',     [ __CLASS__, 'year_progress' ] );
        add_shortcode( 'gs_usage_count',       [ __CLASS__, 'usage_count' ] );
        add_shortcode( 'gs_monthly_downloads', [ __CLASS__, 'monthly_downloads' ] );

        // Late, so other plugins have already claimed anything they want.
        add_action( 'init', [ __CLASS__, 'register_legacy_shortcodes' ], 20 );
    }

    /**
     * Claim the old unprefixed names only if nothing else already has.
     *
     * [usage_count] and friends are generic enough that another plugin could
     * legitimately own them, and add_shortcode() overwrites silently with no
     * warning. Skipping a name that is already taken means this plugin can
     * never break someone else's shortcode.
     *
     * Once page content is migrated to the gs_ prefixed names, this method and
     * the LEGACY_SHORTCODES list can be deleted outright.
     */
    public static function register_legacy_shortcodes() {
        foreach ( self::LEGACY_SHORTCODES as $tag ) {
            if ( ! shortcode_exists( $tag ) ) {
                add_shortcode( $tag, [ __CLASS__, $tag ] );
            }
        }
    }

    /**
     * Deterministic float in [0, 1) from a seed string and a label.
     *
     * Hash-based rather than srand()/rand(): the old helper seeded the global
     * PRNG, drew a value, then called bare srand() to "reset" it — which
     * actually reseeds it randomly, so any other code in the same request
     * relying on a seeded sequence was silently disturbed. Nothing here needs
     * the global generator, so it no longer touches it.
     */
    private static function seeded_float( $seed, $label ) {
        $hash = hash( 'sha256', $seed . '|' . $label );
        $int  = hexdec( substr( $hash, 0, 12 ) ); // 48 bits, exact as a PHP float.

        return $int / 0x1000000000000;
    }

    /** Day-of-month seeded value (same all day, changes daily, site timezone). */
    private static function daily_rand( $seed, $min, $max ) {
        // current_time() honours the site's timezone; date() used the server's,
        // so the counters rolled over at the wrong hour for an AU site.
        $today = current_time( 'Ymd' );
        $f     = self::seeded_float( 'gs-daily|' . $seed, $today );

        return $min + (int) floor( $f * ( $max - $min + 1 ) );
    }

    public static function monthly_progress( $atts ) {
        $day    = (int) current_time( 'j' );
        $base   = max( 0, $day - 2 );
        $jitter = self::daily_rand( 1001, 0, 3 );
        return (string) min( 28, $base + $jitter );
    }

    /**
     * Stables delivered this calendar year.
     *
     * Completed months each contribute a figure fixed for that month for all
     * time, drawn once from a year+month seed. The current month contributes
     * pro rata by day. Two consequences that the previous version got wrong:
     *
     * 1. It resets on 1 January, which is what "this year" means. The old
     *    version counted months since a hardcoded 2024-01-01 and never reset,
     *    so by August 2026 it was 31 months deep and rendering 742.
     * 2. It only ever climbs. The old version re-rolled the per-month rate
     *    daily and multiplied it across every month since 2024, so the figure
     *    swung by ~300 overnight — a visitor could watch it fall.
     *
     * Rates are deliberately a little under the ~28/month the monthly counter
     * settles on, so the year figure reads conservatively rather than as the
     * best case: late August lands near 200 rather than 224.
     *
     * Attribute: min — display floor, for the first days of January when the
     * honest figure is 0 or 1. Defaults to 0.
     */
    public static function year_progress( $atts ) {
        $atts = shortcode_atts( [ 'min' => 0 ], $atts, 'gs_year_progress' );

        $year  = (int) current_time( 'Y' );
        $month = (int) current_time( 'n' );
        $day   = (int) current_time( 'j' );
        $days  = (int) current_time( 't' ); // Days in the current month.

        $total = 0;

        for ( $m = 1; $m < $month; $m++ ) {
            $total += self::month_rate( $year, $m );
        }

        // Part-way through the month, only part of its rate has been earned.
        $total += (int) floor( self::month_rate( $year, $month ) * $day / max( 1, $days ) );

        $total = max( (int) $atts['min'], $total );

        /**
         * Filter the rendered year-to-date delivery count.
         *
         * @param int $total Running total for today.
         * @param int $year  Calendar year, site timezone.
         */
        return (string) (int) apply_filters( 'gs_ct_year_progress', $total, $year );
    }

    /**
     * Stables delivered in one month, stable for that month for all time.
     *
     * Seeded on year+month rather than on the date, so a month's contribution
     * never changes once it is in the past — that is what keeps the year total
     * monotone.
     *
     * @return int
     */
    private static function month_rate( $year, $month ) {
        $span = self::YEAR_MONTH_MAX - self::YEAR_MONTH_MIN + 1;
        $salt = apply_filters( 'gs_ct_year_progress_salt', 'gs-stables-delivered' );
        $f    = self::seeded_float( $salt, $year . '-' . $month );

        return self::YEAR_MONTH_MIN + (int) floor( $f * $span );
    }

    public static function usage_count( $atts ) {
        $day    = (int) current_time( 'j' );
        $base   = max( 5, $day * 4 );
        $jitter = self::daily_rand( 3001, 0, 6 );
        return (string) min( 128, $base + $jitter );
    }

    public static function monthly_downloads( $atts ) {
        $atts = shortcode_atts( [
            'seed_start' => 2,
            'seed_end'   => 10,
            'id'         => 'dl',
        ], $atts );

        $day     = (int) current_time( 'j' );
        $base    = 630;
        $daily   = self::daily_rand( 4001 + (int) $atts['seed_start'], (int) $atts['seed_start'], (int) $atts['seed_end'] );
        $count   = min( 700, $base + ( $day * $daily ) );
        $hours   = self::daily_rand( 5001, 1, 8 );

        return esc_html( $count ) . ' <small>(last download ' . esc_html( $hours ) . ' hrs ago)</small>';
    }
}

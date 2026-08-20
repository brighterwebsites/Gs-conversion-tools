<?php
/**
 * Social proof shortcodes — deterministic pseudo-random counters.
 *
 * [gs_monthly_progress]  – Projects in progress this month (0–28)
 * [gs_year_progress]     – Stables delivered this year (starts at 60)
 * [gs_usage_count]       – People using the tool this month (5–128)
 * [gs_monthly_downloads] – Downloads counter with "last X hours ago" text
 */
class GS_Social_Proof {

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

    /** Day-of-month seeded RNG (same value all day, changes daily). */
    private static function daily_rand( $seed, $min, $max ) {
        // current_time() honours the site's timezone; date() used the server's,
        // so the counters rolled over at the wrong hour for an AU site.
        $today = (int) current_time( 'Ymd' );
        srand( $today + $seed );
        $val = rand( $min, $max );
        srand(); // reset
        return $val;
    }

    public static function monthly_progress( $atts ) {
        $day    = (int) current_time( 'j' );
        $base   = max( 0, $day - 2 );
        $jitter = self::daily_rand( 1001, 0, 3 );
        return (string) min( 28, $base + $jitter );
    }

    public static function year_progress( $atts ) {
        $start_date  = new DateTime( '2024-01-01', wp_timezone() );
        $now         = new DateTime( 'now', wp_timezone() );
        $months      = (int) $start_date->diff( $now )->m + ( (int) $start_date->diff( $now )->y * 12 );
        $per_month   = self::daily_rand( 2001, 15, 25 );
        return (string) ( 60 + ( $months * $per_month ) );
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

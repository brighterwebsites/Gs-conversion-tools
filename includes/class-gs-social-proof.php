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

    public static function init() {
        add_shortcode( 'gs_monthly_progress',  [ __CLASS__, 'monthly_progress' ] );
        add_shortcode( 'gs_year_progress',     [ __CLASS__, 'year_progress' ] );
        add_shortcode( 'gs_usage_count',       [ __CLASS__, 'usage_count' ] );
        add_shortcode( 'gs_monthly_downloads', [ __CLASS__, 'monthly_downloads' ] );

        // Legacy shortcode names
        add_shortcode( 'monthly_progress',  [ __CLASS__, 'monthly_progress' ] );
        add_shortcode( 'year_progress',     [ __CLASS__, 'year_progress' ] );
        add_shortcode( 'usage_count',       [ __CLASS__, 'usage_count' ] );
        add_shortcode( 'monthly_downloads', [ __CLASS__, 'monthly_downloads' ] );
    }

    /** Day-of-month seeded RNG (same value all day, changes daily). */
    private static function daily_rand( $seed, $min, $max ) {
        $today = (int) date( 'Ymd' );
        srand( $today + $seed );
        $val = rand( $min, $max );
        srand(); // reset
        return $val;
    }

    public static function monthly_progress( $atts ) {
        $day    = (int) date( 'j' );
        $base   = max( 0, $day - 2 );
        $jitter = self::daily_rand( 1001, 0, 3 );
        return (string) min( 28, $base + $jitter );
    }

    public static function year_progress( $atts ) {
        $start_date  = new DateTime( '2024-01-01' );
        $now         = new DateTime();
        $months      = (int) $start_date->diff( $now )->m + ( (int) $start_date->diff( $now )->y * 12 );
        $per_month   = self::daily_rand( 2001, 15, 25 );
        return (string) ( 60 + ( $months * $per_month ) );
    }

    public static function usage_count( $atts ) {
        $day    = (int) date( 'j' );
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

        $day     = (int) date( 'j' );
        $base    = 630;
        $daily   = self::daily_rand( 4001 + (int) $atts['seed_start'], (int) $atts['seed_start'], (int) $atts['seed_end'] );
        $count   = min( 700, $base + ( $day * $daily ) );
        $hours   = self::daily_rand( 5001, 1, 8 );

        return esc_html( $count ) . ' <small>(last download ' . esc_html( $hours ) . ' hrs ago)</small>';
    }
}

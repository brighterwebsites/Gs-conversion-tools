<?php
/**
 * Social proof shortcodes — deterministic pseudo-random counters.
 *
 * [gs_monthly_progress]  – Stables built this month (0 → 25-28 across days 1-28)
 * [gs_year_progress]     – Stables delivered this year (starts at 60)
 * [gs_usage_count]       – People using the tool this month (5–128)
 * [gs_monthly_downloads] – Downloads counter with "last X hours ago" text
 *
 * All values are computed on the fly from the current date — nothing is stored,
 * and the same date always produces the same number, on every page and every
 * refresh.
 */
class GS_Social_Proof {

    /** Days over which the monthly counter ramps up. */
    const BUILD_DAYS = 28;

    /** Month-end total lands somewhere in this inclusive range. */
    const MONTH_MIN = 25;
    const MONTH_MAX = 28;

    /** Per-request memo of generated month sequences, keyed "Y-n". */
    private static $sequences = [];

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

    /**
     * Deterministic float in [0, 1) derived from a seed string and a label.
     *
     * Hash-based rather than srand()/rand() so it never touches — or resets —
     * the global PRNG state that other code in the request may depend on.
     */
    private static function seeded_float( $seed, $label ) {
        $hash = hash( 'sha256', $seed . '|' . $label );
        $int  = hexdec( substr( $hash, 0, 12 ) ); // 48 bits, exact as a PHP float
        return $int / 0x1000000000000;
    }

    /** Day-of-month seeded value (same all day, changes daily, site timezone). */
    private static function daily_rand( $seed, $min, $max ) {
        $today = current_time( 'Ymd' );
        $f     = self::seeded_float( 'gs-daily|' . $seed, $today );
        return $min + (int) floor( $f * ( $max - $min + 1 ) );
    }

    /**
     * Build the whole month's running total, day 1 through BUILD_DAYS.
     *
     * Weights are drawn per day, then normalised against the month target, so
     * the cumulative total is guaranteed to be non-decreasing and to land
     * exactly on the target on day BUILD_DAYS. The weight curve is skewed low,
     * which gives quiet days and occasional two-or-three-at-once days rather
     * than a flat one-per-day march.
     *
     * @return int[] Map of day number => running total.
     */
    private static function month_sequence( $year, $month ) {
        $key = $year . '-' . $month;

        if ( isset( self::$sequences[ $key ] ) ) {
            return self::$sequences[ $key ];
        }

        $salt = apply_filters( 'gs_ct_monthly_progress_salt', 'gs-stables-built' );
        $seed = $salt . '|' . $key;

        // Month target, e.g. 25–28.
        $span  = self::MONTH_MAX - self::MONTH_MIN + 1;
        $total = self::MONTH_MIN + (int) floor( self::seeded_float( $seed, 'total' ) * $span );

        // Per-day weights: 0.2–3.0, skewed toward the low end.
        $weights = [];
        $sum     = 0.0;

        for ( $day = 1; $day <= self::BUILD_DAYS; $day++ ) {
            $f              = self::seeded_float( $seed, 'day' . $day );
            $weights[ $day ] = 0.2 + ( $f * $f * 2.8 );
            $sum            += $weights[ $day ];
        }

        // Normalised cumulative → monotone running total.
        $sequence = [];
        $running  = 0.0;

        for ( $day = 1; $day <= self::BUILD_DAYS; $day++ ) {
            $running            = $running + $weights[ $day ];
            $sequence[ $day ] = (int) floor( ( $running / $sum ) * $total );
        }

        // Pin the final day exactly, rather than trusting float rounding.
        $sequence[ self::BUILD_DAYS ] = $total;

        self::$sequences[ $key ] = $sequence;

        return $sequence;
    }

    /**
     * Stables built this month.
     *
     * Starts at or near zero on the 1st, climbs unevenly, and settles between
     * MONTH_MIN and MONTH_MAX by the 28th. Months with 29–31 days hold at the
     * month total for the remaining days.
     *
     * Attribute: min — display floor, in case a bare "0" reads poorly early in
     * the month. Defaults to 0.
     */
    public static function monthly_progress( $atts ) {
        $atts = shortcode_atts( [ 'min' => 0 ], $atts, 'gs_monthly_progress' );

        $now   = current_datetime();
        $day   = (int) $now->format( 'j' );
        $year  = (int) $now->format( 'Y' );
        $month = (int) $now->format( 'n' );

        $sequence = self::month_sequence( $year, $month );
        $count    = $sequence[ min( $day, self::BUILD_DAYS ) ];
        $count    = max( (int) $atts['min'], $count );

        /**
         * Filter the rendered monthly build count.
         *
         * @param int $count Running total for today.
         * @param int $day   Day of month, site timezone.
         */
        $count = (int) apply_filters( 'gs_ct_monthly_progress', $count, $day );

        return (string) $count;
    }

    public static function year_progress( $atts ) {
        $start_date  = new DateTime( '2024-01-01' );
        $now         = new DateTime();
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

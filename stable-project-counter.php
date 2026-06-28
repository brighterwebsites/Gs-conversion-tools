<?php
/**
 * Generate a pseudo-random daily counter from 1st–28th.
 * - Starts near 0 at the start of the month.
 * - Increases by roughly 0–3 per day.
 * - Caps between 25–28 by the 28th.
 * - No storage required; calculated on the fly.
 */

function get_monthly_progress_number() {
    // Get current day of month (1–31)
    $day = (int) date('j');

    // Cap calculations to 28 days for consistency
    $day = min($day, 28);

    // Seed randomness deterministically based on day + month + year
    // ensures same value all day, changes each new day
    srand((int) date('Ym') * 100 + $day);

    // Generate random increments for each day
    $total = 0;
    for ($i = 1; $i <= $day; $i++) {
        $increment = rand(0, 3); // increase by 0–3
        $total += $increment;
    }

    // Keep results within 0–28 range
    $total = max(0, min($total, 28));

    // Optionally smooth the curve slightly near month end
    if ($day >= 25 && $total < 25) {
        $total = 25 + rand(0, 3);
    }

    return $total;
}



add_shortcode('monthly_progress', function() {
    return get_monthly_progress_number();
});


function gs_get_stables_delivered_count() {
    $start_count = 60;
    $min_increase = 15;
    $max_increase = 25;
    $start_date = new DateTime('2024-01-01');
    $now = new DateTime();

    $months_elapsed = ($now->format('Y') - $start_date->format('Y')) * 12 + ($now->format('n') - $start_date->format('n'));

    // Deterministic pseudo-random growth
    $seed = intval($now->format('Ym'));
    mt_srand($seed);
    $avg_increase = mt_rand($min_increase, $max_increase);

    $totalstables = $start_count + ($months_elapsed * $avg_increase);

    return $totalstables;
}


add_shortcode('year_progress', function() {
    return gs_get_stables_delivered_count();
});

function get_monthly_usage_count() {
    // Get current day of the month (1–31)
    $day = (int) date('j');

    // Cap to 28 for consistency (avoids end-month skew)
    $day = min($day, 28);

    // Seed random deterministically (so it stays constant each day)
    srand((int) date('Ym') * 100 + $day);

    // Start base count
    $total = 5;

    // Add daily increments
    for ($i = 1; $i <= $day; $i++) {
        $total += rand(2, 6); // each day adds 2–6
    }

    // Soft clamp to realistic final range
    if ($total > 128) $total = rand(123, 128);

    return $total;
}
add_shortcode('usage_count', function() {
    return get_monthly_usage_count();
});



/**
 * Generate a realistic "downloads this month" proof line.
 * Usage: [monthly_downloads seed_start=2 seed_end=10]
 */

add_shortcode('monthly_downloads', function($atts) {
    $atts = shortcode_atts([
        'seed_start' => 2,  // random start floor
        'seed_end'   => 10, // random start ceiling
        'id'         => '', // optional identifier to differentiate instances
    ], $atts);

    $today = new DateTime('now', wp_timezone());
    $day   = (int) $today->format('j');
    $hour  = (int) $today->format('G');
    $month_days = (int) $today->format('t');

    // -------------------------------------------------
    // UNIQUE SEED PER MONTH + INSTANCE + DAY
    // -------------------------------------------------
    // Ensures consistent results daily, unique per shortcode
    $unique_hash = crc32($atts['id'] . $today->format('Ym'));
    srand($unique_hash);

    // Starting seed for the month (2–10 default)
    $start_seed = rand($atts['seed_start'], $atts['seed_end']);

    // Target range for end of month (630–700)
    srand($unique_hash + 999); // different stream
    $target_end = rand(630, 700);

    // -------------------------------------------------
    // CALCULATE DAILY PROGRESSION
    // -------------------------------------------------
    $daily_inc_min = 4;
    $daily_inc_range = ($target_end - $start_seed) / $month_days;
    $daily_inc_avg = max($daily_inc_min, $daily_inc_range);

    // Re-seed with day for stable random daily increments
    srand($unique_hash + $day);
    $downloads = $start_seed;
    for ($i = 1; $i <= $day; $i++) {
        $downloads += rand($daily_inc_min, ceil($daily_inc_avg));
    }
    $downloads = min($downloads, $target_end);

    // -------------------------------------------------
    // "LAST DOWNLOAD X HOURS AGO" LOGIC
    // -------------------------------------------------
    srand($unique_hash + $hour); // unique per hour
    if ($hour >= 22) { $hours_ago = rand(2, 6); }
    elseif ($hour >= 18) { $hours_ago = rand(2, 6); }
    elseif ($hour >= 15) { $hours_ago = rand(4, 6); }
    elseif ($hour >= 12) { $hours_ago = rand(1, 4); }
    elseif ($hour >= 9)  { $hours_ago = rand(1, 3); }
    else { $hours_ago = rand(3, 6); }

    // -------------------------------------------------
    // OUTPUT HTML
    // -------------------------------------------------
    ob_start(); ?>
    <p class="download-proof">
        <strong><?php echo number_format($downloads); ?> downloads</strong> this month
        ⭐ 5 avg rating from buyers | Last download <?php echo $hours_ago; ?> hour<?php echo $hours_ago > 1 ? 's' : ''; ?> ago
    </p>
    <?php
    return ob_get_clean();
});

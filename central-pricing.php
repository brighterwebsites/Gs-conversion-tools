<?php
/**
 * ========================================
 * GUERILLA STEEL - CENTRAL PRICING CONFIG
 * ========================================
 * 
 * UPDATE PRICES HERE - ALL TOOLS USE THIS
 * 
 * Used by:
 * - Quiz shortcode
 * - Simple calculator
 * - Advanced calculator
 * 
 * Add to Breakdance Advanced Scripts FIRST
 * Then add other shortcodes
 * 
 * ========================================
 */
/**
 * ========================================
 * USAGE EXAMPLES
 * ========================================
 * 
 * In PHP:
 * $config = gs_get_pricing_config();
 * $price = gs_calculate_base_price('4x4', 3);
 * echo gs_format_price($price);
 * 
 * In JavaScript (after wp_enqueue_scripts):
 * const config = window.GS_PRICING_CONFIG;
 * const basePrice = config.base['4x4'].first;
 * 
 *  IMPORTANT READ BEFORE UPDATING - worth flagging that injecting config data as base64 data: scripts is not ideal practice. When you next revisit that pricing config code, a cleaner approach is using wp_localize_script() or a <script> tag with wp_add_inline_script() which outputs proper inline JS rather than data URIs. That's not urgent, just a note for the backlog.
 * ========================================
 */
// Register global pricing function
function gs_get_pricing_config() {
    return [
        // Base stable prices (first bay + additional bays)
        'base' => [
            '4x4' => [
                'label'       => '4m × 4m Standard',
                'first'       => 4500,
                'extra'       => 4000,
                'install'     => 550,
                'front'       => 4,
                'depth'       => 4,
            ],
            '5x4' => [
                'label'       => '5m × 4m Large',
                'first'       => 6100,
                'extra'       => 5900,
                'install'     => 700,
                'front'       => 5,
                'depth'       => 4,
            ],
            '4x5' => [
                'label'       => '4m × 5m Large',
                'first'       => 6100,
                'extra'       => 5900,
                'install'     => 700,
                'front'       => 4,
                'depth'       => 5,
            ],
            '5x5' => [
                'label'       => '5m × 5m XLarge',
                'first'       => 7800,
                'extra'       => 6200,
                'install'     => 850,
                'front'       => 5,
                'depth'       => 5,
            ],
        ],
        
        // Upgrades (per bay)
        'upgrades' => [
            'pitchRoof'  => 450,  // Slanted/pitch roof upgrade
            'yokeGates'  => 750,  // yoke gates
            'anchors'    => 100,  // Ground anchors (4 per bay)
        ],
        // Panel upgrade to Full Ply
        'panels' => [
            'ends' => [
                'four' => 200,  // 4m side end panels (×2)
                'five' => 200,  // 5m side end panels (×2)
            ],
            'rear' => [
                'four' => 200,  // 4m front rear panels (×bays)
                'five' => 200,  // 5m front rear panels (×bays)
            ],
            'interior' => [
                'four' => 200,  // 4m side interior panels (×(bays-1))
                'five' => 200,  // 5m side interior panels (×(bays-1))
            ],
        ],
        
        // Side roof extensions
        'roofExtension' => [
            'four' => 1200,  // 4m side extension
            'five' => 1800,  // 5m side extension
        ],
        
        // Tack rooms (attaches to side/ends)
        'tackRooms' => [
            // 4m depth options
            '2_5x4' => [
                'label' => '2.5m × 4m',
                'price' => 3600,
                'depth' => 4,
            ],
        ],
        
        // Retrofit calculator – per-panel pricing (4m / 5m × type)
        'retrofit' => [
            // Panel type keys: pm = Ply & Mesh, pp = Full Ply, open = Open Front, cattle = Cattle rail
            'panelTypes' => [
                'pm'     => [ 'label' => 'Ply & Mesh' ],
                'pp'     => [ 'label' => 'Full Ply' ],
                'open'   => [ 'label' => 'Open Front' ],
                'cattle' => [ 'label' => 'Cattle rail' ],
            ],
            // Front panels (door panels) – price by dimension (4|5) and type
            'panelFront' => [
                '4' => [ 'pm' => 1400, 'pp' => 1250, 'open' => 950, 'cattle' => 700 ],
                '5' => [ 'pm' => 1800, 'pp' => 2000, 'open' => 1150, 'cattle' => 950 ],
            ],
            // Side / Rear / Dividing panels – same structure
            'panelSideRear' => [
                '4' => [ 'pm' => 1100, 'pp' => 1300, 'open' => 950, 'cattle' => 700 ],
                '5' => [ 'pm' => 1500, 'pp' => 1700, 'open' => 1150, 'cattle' => 950 ],
            ],
        ],
        
        // Settings
        'gstRate'  => 0.10,
        'currency' => 'AUD',
        
        // Business info (for quotes/invoices)
        'business' => [
            'name'  => 'Guerilla Steel Stables',
            'phone' => '0405 639 413',
            'email' => 'guerillasteel@gmail.com',
        ],
        
        // Notes
        'notes' => [
            'pricing'  => 'All prices include GST',
            'delivery' => 'Delivery charges may apply based on location',
            'custom'   => 'Custom modifications available - contact for quote',
            'anchors'  => 'Anchors include 4 per bay, even if only 3 needed',
        ],
    ];
}

// Make config available to JavaScript
add_action('wp_enqueue_scripts', function() {
    $config = gs_get_pricing_config();
    ?>
    <script>
        window.GS_PRICING_CONFIG = <?php echo wp_json_encode($config); ?>;
    </script>
    <?php
}, 5); // Priority 5 to load early

// Helper function: Get tack rooms for specific depth
function gs_get_tack_rooms_for_depth($depth) {
    $config = gs_get_pricing_config();
    $rooms = [];
    
    foreach ($config['tackRooms'] as $key => $room) {
        if ($room['depth'] == $depth) {
            $rooms[$key] = $room;
        }
    }
    
    return $rooms;
}

// Helper function: Calculate base price for X bays
function gs_calculate_base_price($size, $bays) {
    $config = gs_get_pricing_config();
    
    if (!isset($config['base'][$size])) {
        return 0;
    }
    
    $s = $config['base'][$size];
    return $s['first'] + ($s['extra'] * max(0, $bays - 1));
}

// Helper function: Format price
function gs_format_price($amount, $show_gst_note = false) {
    $formatted = '$' . number_format($amount, 0, '.', ',');
    
    if ($show_gst_note) {
        $formatted .= ' (inc GST)';
    }
    
    return $formatted;
}


<?php
/**
 * Central pricing configuration.
 * Injects window.GS_PRICING_CONFIG for use by all JS tools.
 */
class GS_Pricing {

    public static function init() {
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'inject_config' ], 5 );
    }

    public static function get_config() {
        return [
            'base' => [
                '4x4' => [
                    'label'   => '4m × 4m Standard',
                    'first'   => 4500,
                    'extra'   => 4000,
                    'install' => 550,
                    'front'   => 4,
                    'depth'   => 4,
                ],
                '5x4' => [
                    'label'   => '5m × 4m Large',
                    'first'   => 6100,
                    'extra'   => 5900,
                    'install' => 700,
                    'front'   => 5,
                    'depth'   => 4,
                ],
                '4x5' => [
                    'label'   => '4m × 5m Large',
                    'first'   => 6100,
                    'extra'   => 5900,
                    'install' => 700,
                    'front'   => 4,
                    'depth'   => 5,
                ],
                '5x5' => [
                    'label'   => '5m × 5m XLarge',
                    'first'   => 7800,
                    'extra'   => 6200,
                    'install' => 850,
                    'front'   => 5,
                    'depth'   => 5,
                ],
            ],
            'upgrades' => [
                'pitchRoof' => 450,
                'yokeGates' => 750,
                'anchors'   => 100,
            ],
            'panels' => [
                'ends'     => [ 'four' => 200, 'five' => 200 ],
                'rear'     => [ 'four' => 200, 'five' => 200 ],
                'interior' => [ 'four' => 200, 'five' => 200 ],
            ],
            'roofExtension' => [
                'four' => 1200,
                'five' => 1800,
            ],
            'tackRooms' => [
                '2_5x4' => [ 'label' => '2.5m × 4m', 'price' => 3600, 'depth' => 4 ],
            ],
            'retrofit' => [
                'panelTypes' => [
                    'pm'     => [ 'label' => 'Ply & Mesh' ],
                    'pp'     => [ 'label' => 'Full Ply' ],
                    'open'   => [ 'label' => 'Open Front' ],
                    'cattle' => [ 'label' => 'Cattle rail' ],
                ],
                'panelFront' => [
                    '4' => [ 'pm' => 1400, 'pp' => 1250, 'open' => 950, 'cattle' => 700 ],
                    '5' => [ 'pm' => 1800, 'pp' => 2000, 'open' => 1150, 'cattle' => 950 ],
                ],
                'panelSideRear' => [
                    '4' => [ 'pm' => 1100, 'pp' => 1300, 'open' => 950, 'cattle' => 700 ],
                    '5' => [ 'pm' => 1500, 'pp' => 1700, 'open' => 1150, 'cattle' => 950 ],
                ],
            ],
            'gstRate'  => 0.10,
            'currency' => 'AUD',
            'business' => [
                'name'  => 'Guerilla Steel Stables',
                'phone' => '0405 639 413',
                'email' => 'guerillasteel@gmail.com',
            ],
            'notes' => [
                'pricing'  => 'All prices include GST',
                'delivery' => 'Delivery charges may apply based on location',
                'custom'   => 'Custom modifications available - contact for quote',
                'anchors'  => 'Anchors include 4 per bay, even if only 3 needed',
            ],
        ];
    }

    public static function inject_config() {
        $config = self::get_config();
        wp_register_script( 'gs-pricing-config', false, [], GS_CT_VERSION, false );
        wp_enqueue_script( 'gs-pricing-config' );
        wp_add_inline_script(
            'gs-pricing-config',
            'window.GS_PRICING_CONFIG = ' . wp_json_encode( $config ) . ';'
        );
    }

    // PHP helpers (for use in templates/other PHP code)

    public static function calculate_base_price( $size, $bays ) {
        $config = self::get_config();
        if ( ! isset( $config['base'][ $size ] ) ) {
            return 0;
        }
        $s = $config['base'][ $size ];
        return $s['first'] + ( $s['extra'] * max( 0, $bays - 1 ) );
    }

    public static function format_price( $amount, $show_gst_note = false ) {
        $formatted = '$' . number_format( $amount, 0, '.', ',' );
        if ( $show_gst_note ) {
            $formatted .= ' (inc GST)';
        }
        return $formatted;
    }
}

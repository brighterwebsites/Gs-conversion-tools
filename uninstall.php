<?php
/**
 * The plugin's only stored data is the three GS_Stats transients — every other
 * value it produces is calculated on the fly. They expire by themselves within
 * six hours, but deleting them here keeps the "delete the folder and nothing is
 * orphaned" promise in the README literally true.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

foreach ( [ 'products', 'orders', 'customers' ] as $key ) {
    delete_transient( 'gs_stats_' . $key );
}

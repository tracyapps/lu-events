<?php
/**
 * Plugin Name: Loyalty Untapped Event Network
 * Description: Network-wide event-site provisioning, shared content, ACF options, and domain mapping.
 * Version: 0.2.0
 * Author: Loyalty Untapped
 * Network: true
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LU_EVENT_NETWORK_VERSION', '0.2.0' );
define( 'LU_EVENT_NETWORK_PATH', __DIR__ . '/lu-event-network' );

require_once LU_EVENT_NETWORK_PATH . '/class-lu-event-network.php';

LU_Event_Network::boot();

<?php

/*
Plugin Name: Wordpress Pretix Sync
Plugin URI: https://github.com/unikino-gegenlicht/wordpress-pretix-sync
Description: Allow linking to pretix for ticket sales
Version: 1.0
Author: Jan Eike Suchard
Author URI: https://suchard.cloud
License: GPL3
*/

function extend_metaboxes( $meta_boxes ): mixed {
	$meta_boxes[] = [
		'title'      => esc_html__( 'Reservations', 'wordpress-pretix-sync' ),
		'id'         => 'reservations',
		'context'    => 'side',
		'post_types' => [ 'movie', 'event' ],
		'autosave'   => true,
		'fields'     => [
			[
				'type'      => 'switch',
				'name'      => esc_html__( 'Allow Reservations', 'wordpress-pretix-sync' ),
				'id'        => 'allow_reservations',
				'on_label'  => esc_html__( 'Yes', 'wordpress-pretix-sync' ),
				'off_label' => esc_html__( 'No', 'wordpress-pretix-sync' ),
			],
			[
				'type' => 'url',
				'name' => esc_html__( 'URL to Pretix Page', "wordpress-pretix-sync" ),
				'id'   => "reservation_url",
				'visible' => ["allow_reservations"],
			]
		]

	];

	return $meta_boxes;
}

add_filter( 'rwmb_meta_boxes', 'extend_metaboxes' );

function wordpress_pretix_sync_load_textdomain() {
	load_plugin_textdomain( 'wordpress-pretix-sync', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

}
add_action('plugins_loaded', 'wordpress_pretix_sync_load_textdomain');

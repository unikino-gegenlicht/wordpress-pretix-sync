<?php

/*
Plugin Name: Wordpress Pretix Sync
Plugin URI: https://github.com/unikino-gegenlicht/wordpress-pretix-sync
Description: Allow linking to pretix for ticket sales
Version: 1.0
Author: Jan Eike Suchard
Author URI: https://suchard.cloud
License: GPL3
Domain Path: /src/languages
*/


require "vendor/autoload.php";
require_once "src/PretixAPI.php";

add_action('init', 'wordpress_pretix_sync_load_textdomain');
function wordpress_pretix_sync_load_textdomain(): void
{
    load_plugin_textdomain('wordpress-pretix-sync', true, dirname(plugin_basename(__FILE__)) . '/src/languages/');
}

add_action('rwmb_meta_boxes', 'wordpress_pretix_sync_add_movie_metaboxes', priority: 30);
function wordpress_pretix_sync_add_movie_metaboxes( $metaboxes ) {
    for ( $i = 0; $i < count( $metaboxes ); $i ++ ) {
        if (!key_exists("id", $metaboxes[$i])) {
            continue;
        }
        if ( ! in_array( $metaboxes[ $i ]["id"], [ "movie_meta", "event_meta" ] ) ) {
            continue;
        }

        $metaboxes[ $i ]["tabs"]["reservations"] = [
                "label" => __("Reservations", "wordpress-pretix-sync"),
                "icon"  => plugin_dir_url( __FILE__ ) . "/assets/pretix.svg",
        ];

        $metaboxes[ $i ]["fields"][] = [
                'type'     => 'url',
                'name'     => __( "Link to the Reservation Page", "wordpress-pretix-sync" ),
                'id'       => 'pretix_event_url',
                'required' => false,
                'revision' => true,
                'tab'      => 'reservations',
                'desc'     => __( 'Please enter the URL to the pretix detail page. You can either use the button below to let WordPress guess the URL or manually go to <a target="_blank" href="https://tickets.gegenlicht.net">tickets.gegenlicht.net</a> and copy the appropriate link manually. <b>If no link is set the reservation button will not appear!</b>', "wordpress-pretix-sync" ),
        ];
        $metaboxes[ $i ]["fields"][] = [
                'type'     => 'button',
                'name'     => ' ',
                'std'      => __( "Guess Pretix Event", "wordpress-pretix-sync" ),
                'id'       => 'pretix_guess_event_btn',
                'required' => false,
                'tab'      => 'reservations',
        ];

    }

    return $metaboxes;
}

add_action("init", function () {
    add_menu_page(__("Pretix Integration", "wordpress-pretix-sync"), __("Pretix Integration", "wordpress-pretix-sync"), "manage_options", "pretix", "wordpress_pretix_sync__display_settings", position: 100, icon_url: "data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyBpZD0iRWJlbmVfMSIgZGF0YS1uYW1lPSJFYmVuZSAxIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMjggMTI4Ij4KICA8ZGVmcz4KICAgIDxzdHlsZT4KICAgICAgLmNscy0xIHsKICAgICAgICBmaWxsOiAjNDkyMjY3OwogICAgICB9CiAgICA8L3N0eWxlPgogIDwvZGVmcz4KICA8cGF0aCBjbGFzcz0iY2xzLTEiIGQ9Ik01Ni43OSw2NS4wMWMuODEsNS43NC0uMjgsOC44OS0zLjgxLDkuMzktLjY1LjA5LTEuMTkuMDMtMS42LS4wNGwtMi4zOC0xNi45MWMuNDQtLjE5Ljk0LS40LDEuNjYtLjUsMy41OS0uNSw1LjMsMi4xOCw2LjEyLDguMDZaTTk4Ljk5LDU5LjA4YzEuNiwxMS4zNiwxMS43NywxOS4zMSwyMy4wMiwxOC40LjczLS4wNCwxLjMyLjQxLDEuNDIsMS4xM2w0LjU2LDMyLjQ0Yy4xLjcyLS40LDEuMzktMS4xMiwxLjQ5bC0zNy41LDUuMjctLjczLTUuMjJjLS4xNi0xLjEyLTEuMTktMS45LTIuMzEtMS43NHMtMS45LDEuMTUtMS43NCwyLjMxbC43Myw1LjIyLTY4LjM3LDkuNjFjLS43Mi4xLTEuMzktLjQtMS40OS0xLjEybC00LjU2LTMyLjQ0Yy0uMS0uNzIuMzUtMS4zMSwxLjA2LTEuNDgsMTEuMDgtMi4xNiwxOC42Ni0xMi42MSwxNy4wNy0yMy45Ni0xLjYtMTEuMzYtMTEuNzgtMTkuMzgtMjMuMDMtMTguNDYtLjczLjA0LTEuMzItLjQxLTEuNDItMS4xM0wuMDEsMTYuOTVjLS4xLS43Mi40LTEuMzksMS4xMi0xLjQ5TDY5LjUsNS44NWwuNzMsNS4yMmMuMTYsMS4xMiwxLjE5LDEuOSwyLjMxLDEuNzRzMS45LTEuMTksMS43NC0yLjMxbC0uNzMtNS4yMkwxMTEuMDUuMDFjLjcyLS4xLDEuMzkuNCwxLjQ5LDEuMTJsNC41NiwzMi40NGMuMS43Mi0uMzUsMS4zMS0xLjA2LDEuNDgtMTEuMDgsMi4xNi0xOC42NSwxMi42Ny0xNy4wNiwyNC4wM1pNNjYuNTcsNjMuNTdjLTEuMzktOS44Ni03Ljk4LTEzLjY2LTE2LjY2LTEyLjQ0LTUuNDIuNzYtOC44OCwyLjE4LTExLjM4LDMuOGw1LjMsMzcuNzMsOS45Mi0xLjM5LTEuNTktMTEuMjljLjc1LjA5LDIuMjcuMDgsNC4wNC0uMTcsNy4yNS0xLjAyLDExLjYxLTcuMzYsMTAuMzYtMTYuMjNaTTg1LjQ3LDkwLjA3Yy0uMTUtMS4wOC0xLjIzLTEuODktMi4zMS0xLjc0cy0xLjksMS4xOS0xLjc0LDIuMzFsMS40NywxMC40NGMuMTYsMS4xMiwxLjE5LDEuOSwyLjMxLDEuNzQsMS4xMi0uMTYsMS45LTEuMTksMS43NC0yLjMxbC0xLjQ3LTEwLjQ0Wk04Mi4yOSw2Ny40OWMtLjE2LTEuMTItMS4xOS0xLjktMi4zMS0xLjc0LTEuMTUuMTYtMS45LDEuMTUtMS43NCwyLjMxbDEuNDcsMTAuNDRjLjE1LDEuMDgsMS4yMywxLjg5LDIuMzEsMS43NHMxLjg5LTEuMjMsMS43NC0yLjMxbC0xLjQ3LTEwLjQ0Wk03OS4xNCw0NS4wM2MtLjE1LTEuMDgtMS4yMy0xLjg5LTIuMzEtMS43NHMtMS45LDEuMTktMS43NCwyLjMxbDEuNDcsMTAuNDRjLjE2LDEuMTIsMS4xOSwxLjksMi4zMSwxLjc0LDEuMTItLjE2LDEuOS0xLjE5LDEuNzQtMi4zMWwtMS40Ny0xMC40NFpNNzUuOTYsMjIuNDVjLS4xNi0xLjEyLTEuMTktMS45LTIuMzEtMS43NHMtMS45LDEuMTUtMS43NCwyLjMxbDEuNDcsMTAuNDRjLjE1LDEuMDgsMS4yMywxLjg5LDIuMzEsMS43NHMxLjg5LTEuMjMsMS43NC0yLjMxbC0xLjQ3LTEwLjQ0WiIvPgo8L3N2Zz4=");

});

add_action("admin_init", function () {
    register_setting("ggl_wp_pretix_sync", "pretix_base_url");
    register_setting("ggl_wp_pretix_sync", "pretix_token");
    register_setting("ggl_wp_pretix_sync", "pretix_organizer_slug");
    register_setting("ggl_wp_pretix_sync", "pretix_organizer_public_url");

    add_settings_section("default", "Configuration", function () {
    }, "pretix");

    add_settings_field("pretix_base_url", __("Pretix Host Base URL", "wordpress-pretix-sync"), "wordpress_pretix_sync__field_callback", "pretix", args: ["id" => "pretix_base_url"]);
    add_settings_field("pretix_token", __("Pretix Access Token", "wordpress-pretix-sync"), "wordpress_pretix_sync__field_callback", "pretix", args: ["id" => "pretix_token"]);
    add_settings_field("pretix_organizer_slug", __("Pretix Organizer Slug", "wordpress-pretix-sync"), "wordpress_pretix_sync__field_callback", "pretix", args: ["id" => "pretix_organizer_slug"]);
});

function wordpress_pretix_sync__field_callback($args)
{
    // get the value of the setting we've registered with register_setting()
    $setting = get_option($args["id"]);
    // output the field
    ?>
    <input style="width: 100%" type="text" name="<?= $args["id"] ?>"
           value="<?php echo isset($setting) ? esc_attr($setting) : ''; ?>">
    <?php
}

function wordpress_pretix_sync__display_settings()
{
    ?>
    <div class="wrap">
        <h1><?= esc_html__(get_admin_page_title()) ?></h1>
        <p><?= esc_html__("This page allows you to configure the settings for the pretix integration. To enable the functionality of the plugin, please set the values for all fields present on this page!") ?></p>
        <form action="options.php" method="post">
            <?php settings_fields("ggl_wp_pretix_sync"); ?>
            <?php do_settings_sections("pretix"); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

add_filter("cron_schedules", function ($schedules) {
    $schedules['4_hours'] = ["interval" => 60 * 60 * 4, "display" => __("Every Four Hours", "ggl_wp_pretix_sync")];

    return $schedules;
});

add_action('rwmb_enqueue_scripts', 'wordpress_pretix_sync_enqueue_custom_script');
function wordpress_pretix_sync_enqueue_custom_script()
{
    wp_enqueue_script('pretix-event-guesser', plugin_dir_url(__FILE__) . '/src/js/guess-pretix-event.js', ['jquery'], '', true);
    wp_localize_script('pretix-event-guesser', 'pretixEventAjax', ["ajax_url" => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('guess-pretix-event-url'),]);
}

add_action("wp_ajax_guess-pretix-event-url", "wordpress_pretix_sync__guess_pretix_event_url");
function wordpress_pretix_sync__guess_pretix_event_url()
{
    check_ajax_referer('guess-pretix-event-url');

    $expected_start_time_in = wp_unslash($_POST['screeningStart']) . " Europe/Berlin";

    $expected_start_time = date_create_immutable_from_format("d.m.Y H:i T", $expected_start_time_in);

    $events_cached = false;
    $events = wp_cache_get("wordpress-pretix-sync_events", found: $events_cached);

    if (!$events_cached || empty($events)) {
        $pretix_host = get_option("pretix_base_url");
        $pretix_secret = get_option("pretix_token");
        $pretix_organizer = get_option("pretix_organizer_slug");

        $pretix = new PretixAPI($pretix_host, $pretix_secret);
        $events = $pretix->getEvents($pretix_organizer);

        wp_cache_add("wordpress-pretix-sync_events", $events, expire: 60 * 60 * 6);
    }

    $data = [
        "usedCache" => $events_cached,
    ];

    foreach ($events as $event) {
        $event_start_time = date_create_immutable_from_format("Y-m-d\TH:i:sT", $event["date_from"]);
        if ($event_start_time->getTimestamp() != $expected_start_time->getTimestamp()) {
            continue;
        }

        $data["eventUrl"] = $event["public_url"];

    }

    if (!isset($data["eventUrl"])) {
        $pretix_host = get_option("pretix_base_url");
        $pretix_secret = get_option("pretix_token");
        $pretix_organizer = get_option("pretix_organizer_slug");

        $pretix = new PretixAPI($pretix_host, $pretix_secret);
        $events = $pretix->getEvents($pretix_organizer);

        foreach ($events as $event) {
            $event_start_time = date_create_immutable_from_format("Y-m-d\TH:i:sT", $event["date_from"]);
            if ($event_start_time->getTimestamp() != $expected_start_time->getTimestamp()) {
                continue;
            }

            $data["eventUrl"] = $event["public_url"];

        }
    }

    wp_send_json($data);
}

function ggl_get_event_booking_url(int|WP_Post $post = 0 ): string {
// Resolve the provided post or fall back to the global post
    $post = get_post( $post, filter: 'display' );

    // Return early if the post type is not supported by the function
    if ( ! in_array( $post->post_type, [ "movie", "event" ] ) ) {
        return "";
    }

    return get_post_meta($post->ID, "pretix_event_url", true);
}
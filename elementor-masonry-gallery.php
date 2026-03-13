<?php
/**
 * Plugin Name: Elementor Masonry Gallery Extension
 * Description: Adds a “Grid / Masonry” switch to the Basic Gallery widget, with a configurable column count.
 * Version:     1.2
 * Update URI: https://github.com/stronganchor/elementor-masonry-gallery
 * Author:      Strong Anchor Tech
 * License:     GPL-2.0+
 * Text Domain: emg
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function emg_get_update_branch() {
    $branch = 'main';

    if ( defined( 'EMG_UPDATE_BRANCH' ) && is_string( EMG_UPDATE_BRANCH ) ) {
        $override = trim( EMG_UPDATE_BRANCH );
        if ( '' !== $override ) {
            $branch = $override;
        }
    }

    return (string) apply_filters( 'emg_update_branch', $branch );
}

function emg_bootstrap_update_checker() {
    $checker_file = plugin_dir_path( __FILE__ ) . 'plugin-update-checker/plugin-update-checker.php';
    if ( ! file_exists( $checker_file ) ) {
        return;
    }

    require_once $checker_file;

    if ( ! class_exists( '\YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
        return;
    }

    $repo_url = (string) apply_filters( 'emg_update_repository', 'https://github.com/stronganchor/elementor-masonry-gallery' );
    $slug     = dirname( plugin_basename( __FILE__ ) );

    $update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        $repo_url,
        __FILE__,
        $slug
    );

    $update_checker->setBranch( emg_get_update_branch() );

    foreach ( array( 'EMG_GITHUB_TOKEN', 'STRONGANCHOR_GITHUB_TOKEN', 'ANCHOR_GITHUB_TOKEN' ) as $constant_name ) {
        if ( ! defined( $constant_name ) || ! is_string( constant( $constant_name ) ) ) {
            continue;
        }

        $token = trim( (string) constant( $constant_name ) );
        if ( '' !== $token ) {
            $update_checker->setAuthentication( $token );
            break;
        }
    }
}

emg_bootstrap_update_checker();

final class EMG_Plugin {

    const GUTTER = 10;

    public function __construct() {
        add_action( 'plugins_loaded', [ $this, 'init' ], 20 );
    }

    public function init() {
        if ( ! did_action( 'elementor/loaded' ) ) {
            add_action( 'admin_notices', [ $this, 'elementor_missing_notice' ] );
            return;
        }

        add_action(
            'elementor/element/image-gallery/section_gallery/before_section_end',
            [ $this, 'override_columns_default' ],
            5, 2
        );
        add_action(
            'elementor/element/image-gallery/section_gallery/before_section_end',
            [ $this, 'add_layout_control' ],
            10, 2
        );
        add_action(
            'elementor/frontend/widget/before_render',
            [ $this, 'add_wrapper_attributes' ]
        );
        add_action(
            'wp_enqueue_scripts',
            [ $this, 'enqueue_assets' ]
        );
    }

    public function override_columns_default( $element ) {
        $element->update_control( 'columns', [ 'default' => 4 ] );
    }

    public function add_layout_control( $element ) {
        $element->add_control(
            'layout_style',
            [
                'label'   => __( 'Layout Style', 'emg' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'grid',
                'options' => [
                    'grid'    => __( 'Grid (default)', 'emg' ),
                    'masonry' => __( 'Masonry', 'emg' ),
                ],
            ]
        );
    }

    public function add_wrapper_attributes( $widget ) {
        if ( 'image-gallery' !== $widget->get_name() ) {
            return;
        }

        $settings = $widget->get_settings_for_display();
        if ( ! empty( $settings['layout_style'] ) && 'masonry' === $settings['layout_style'] ) {
            $widget->add_render_attribute( '_wrapper', 'class', 'emg-masonry' );
            $cols = ! empty( $settings['columns'] ) ? intval( $settings['columns'] ) : 4;
            $widget->add_render_attribute( '_wrapper', 'data-columns', $cols );
        }
    }

    public function enqueue_assets() {
        wp_enqueue_script(
            'emg-frontend',
            plugins_url( 'assets/frontend.js', __FILE__ ),
            [ 'jquery', 'masonry', 'imagesloaded' ],
            '1.2',
            true
        );

        wp_add_inline_style(
            'elementor-frontend',
            '
            .emg-masonry .gallery {
                display: block !important;
                position: relative;
            }
            '
        );
    }

    public function elementor_missing_notice() {
        echo '<div class="notice notice-warning"><p>';
        esc_html_e( 'Elementor Masonry Gallery Extension requires Elementor to be installed and active.', 'emg' );
        echo '</p></div>';
    }
}

new EMG_Plugin();

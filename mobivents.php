<?php
/**
 * Plugin Name: Mobivents - Custom Event Manager
 * Description: A lightweight, secure, and multilingual custom event management plugin developed for professional agency standards.
 * Version:     1.0.0
 * Author:      Oguzhan Aydin
 * Text Domain: mobivents
 * Domain Path: /languages
 * License:     GPL2
 */

// Eğer dosyaya doğrudan erişilmeye çalışılırsa güvenliği sağla
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class MobiVents_Manager
 * Handles the initialization of Custom Post Types, Meta Boxes, Shortcodes, and REST API.
 */
class MobiVents_Manager {

    public function __construct() {
        // Hooking into WordPress initialization
        add_action( 'init', array( $this, 'register_event_post_type' ) );
        add_action( 'init', array( $this, 'register_event_taxonomy' ) );
        add_action( 'init', array( $this, 'load_textdomain' ) );
        
        // Scripts and Styles
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_styles' ) );

        // Meta Box Hooks
        add_action( 'add_meta_boxes', array( $this, 'add_event_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_event_meta_data' ) );

        // Shortcode Hook
        add_shortcode( 'mobivents_list', array( $this, 'render_events_shortcode' ) );

        // REST API Hook
        add_action( 'rest_api_init', array( $this, 'register_events_api_route' ) );
    }

    /**
     * Load translation files for multilingual support.
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'mobivents', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    /**
     * Registers the Custom Post Type for Events.
     */
    public function register_event_post_type() {
        $labels = array(
            'name'               => _x( 'Events', 'post type general name', 'mobivents' ),
            'singular_name'      => _x( 'Event', 'post type singular name', 'mobivents' ),
            'menu_name'          => _x( 'Mobivents', 'admin menu', 'mobivents' ),
            'add_new'            => _x( 'Add New', 'event', 'mobivents' ),
            'add_new_item'       => __( 'Add New Event', 'mobivents' ),
            'edit_item'          => __( 'Edit Event', 'mobivents' ),
            'all_items'          => __( 'All Events', 'mobivents' ),
            'view_item'          => __( 'View Event', 'mobivents' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'has_archive'        => true,
            'supports'           => array( 'title', 'editor', 'thumbnail' ),
            'show_in_rest'       => true, // Gutenberg ve REST API desteği için kritik
            'menu_icon'          => 'dashicons-calendar-alt',
        );

        register_post_type( 'mobivents', $args );
    }

    /**
     * Registers Custom Taxonomy (Event Types like Webinar, Conference).
     */
    public function register_event_taxonomy() {
        $labels = array(
            'name'              => _x( 'Event Types', 'taxonomy general name', 'mobivents' ),
            'singular_name'     => _x( 'Event Type', 'taxonomy singular name', 'mobivents' ),
            'all_items'         => __( 'All Event Types', 'mobivents' ),
            'edit_item'         => __( 'Edit Event Type', 'mobivents' ),
            'update_item'       => __( 'Update Event Type', 'mobivents' ),
            'add_new_item'      => __( 'Add New Event Type', 'mobivents' ),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
        );

        register_taxonomy( 'event_type', array( 'mobivents' ), $args );
    }

    /**
     * Enqueue CSS styles for frontend.
     */
    public function enqueue_frontend_styles() {
        wp_enqueue_style( 'mobivents-style', plugin_dir_url( __FILE__ ) . 'css/style.css', array(), '1.0.0' );
    }

    /**
     * Adds custom metabox for Event specifications.
     */
    public function add_event_meta_boxes() {
        add_meta_box(
            'mobivents_details',
            __( 'Event Details', 'mobivents' ),
            array( $this, 'render_meta_box_html' ),
            'mobivents',
            'normal',
            'high'
        );
    }

    /**
     * Renders the Meta Box fields in admin dashboard.
     */
    public function render_meta_box_html( $post ) {
        // CSRF Güvenliği için Nonce alanı (Ajanslar buna çok dikkat eder)
        wp_nonce_field( 'mobivents_save_meta', 'mobivents_meta_nonce' );

        $date     = get_post_meta( $post->ID, '_event_date', true );
        $location = get_post_meta( $post->ID, '_event_location', true );
        $price    = get_post_meta( $post->ID, '_event_price', true );

        echo '<p><label for="event_date">' . esc_html__( 'Event Date & Time:', 'mobivents' ) . '</label>';
        echo '<input type="datetime-local" id="event_date" name="event_date" value="' . esc_attr( $date ) . '" class="widefat" /></p>';

        echo '<p><label for="event_location">' . esc_html__( 'Location / Link:', 'mobivents' ) . '</label>';
        echo '<input type="text" id="event_location" name="event_location" value="' . esc_attr( $location ) . '" class="widefat" placeholder="e.g. Zoom or Berlin Office" /></p>';

        echo '<p><label for="event_price">' . esc_html__( 'Ticket Price (€):', 'mobivents' ) . '</label>';
        echo '<input type="number" id="event_price" name="event_price" step="0.01" value="' . esc_attr( $price ) . '" class="widefat" /></p>';
    }

    /**
     * Saves and sanitizes the meta box data securely.
     */
    public function save_event_meta_data( $post_id ) {
        // Nonce doğrulama
        if ( ! isset( $_POST['mobivents_meta_nonce'] ) || ! wp_verify_nonce( $_POST['mobivents_meta_nonce'], 'mobivents_save_meta' ) ) {
            return;
        }

        // Autosave durumunu kontrol et
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Kullanıcı yetki kontrolü
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Verileri temizle (Sanitisation) ve kaydet
        if ( isset( $_POST['event_date'] ) ) {
            update_post_meta( $post_id, '_event_date', sanitize_text_field( $_POST['event_date'] ) );
        }
        if ( isset( $_POST['event_location'] ) ) {
            update_post_meta( $post_id, '_event_location', sanitize_text_field( $_POST['event_location'] ) );
        }
        if ( isset( $_POST['event_price'] ) ) {
            update_post_meta( $post_id, '_event_price', sanitize_text_field( $_POST['event_price'] ) );
        }
    }

    /**
     * Frontend shortcode to list events in a grid layout.
     * Usage: [mobivents_list]
     */
    public function render_events_shortcode() {
        $args = array(
            'post_type'      => 'mobivents',
            'posts_per_page' => 6,
            'status'         => 'publish',
            'orderby'        => 'meta_value',
            'meta_key'       => '_event_date',
            'order'          => 'ASC'
        );

        $query = new WP_Query( $args );
        
        if ( ! $query->have_posts() ) {
            return '<p class="mobivents-no-events">' . esc_html__( 'No upcoming events found.', 'mobivents' ) . '</p>';
        }

        ob_start();
        echo '<div class="mobivents-grid">';
        
        while ( $query->have_posts() ) {
            $query->the_post();
            
            $date     = get_post_meta( get_the_ID(), '_event_date', true );
            $location = get_post_meta( get_the_ID(), '_event_location', true );
            $price    = get_post_meta( get_the_ID(), '_event_price', true );
            $formatted_date = $date ? date_i18n( get_option( 'date_format' ) . ' H:i', strtotime( $date ) ) : __('TBD', 'mobivents');
            ?>
            <div class="mobivents-card">
                <?php if ( has_post_thumbnail() ) : ?>
                    <div class="mobivents-thumb">
                        <?php the_post_thumbnail( 'medium' ); ?>
                    </div>
                <?php endif; ?>
                <div class="mobivents-content">
                    <h3 class="mobivents-title"><?php the_title(); ?></h3>
                    <div class="mobivents-meta">
                        <p><strong><?php esc_html_e( 'Date:', 'mobivents' ); ?></strong> <?php echo esc_html( $formatted_date ); ?></p>
                        <p><strong><?php esc_html_e( 'Location:', 'mobivents' ); ?></strong> <?php echo esc_html( $location ); ?></p>
                        <p><strong><?php esc_html_e( 'Price:', 'mobivents' ); ?></strong> <?php echo $price ? esc_html( $price ) . '€' : esc_html__( 'Free', 'mobivents' ); ?></p>
                    </div>
                    <div class="mobivents-excerpt">
                        <?php echo wp_trim_words( get_the_excerpt(), 15 ); ?>
                    </div>
                </div>
            </div>
            <?php
        }
        
        echo '</div>';
        wp_reset_postdata();

        return ob_get_clean();
    }

    /**
     * Registers custom endpoint for Headless/REST API integration.
     * Route: /wp-json/mobivents/v1/upcoming
     */
    public function register_events_api_route() {
        register_rest_route( 'mobivents/v1', '/upcoming', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'get_upcoming_events_api' ),
            'permission_callback' => '__return_true'
        ) );
    }

    /**
     * API Callback logic to return JSON.
     */
    public function get_upcoming_events_api() {
        $args = array(
            'post_type'      => 'mobivents',
            'posts_per_page' => 5,
            'orderby'        => 'meta_value',
            'meta_key'       => '_event_date',
            'order'          => 'ASC'
        );

        $query = new WP_Query( $args );
        $events = array();

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $events[] = array(
                    'id'       => get_the_ID(),
                    'title'    => get_the_title(),
                    'date'     => get_post_meta( get_the_ID(), '_event_date', true ),
                    'location' => get_post_meta( get_the_ID(), '_event_location', true ),
                    'price'    => get_post_meta( get_the_ID(), '_event_price', true ),
                    'link'     => get_permalink()
                );
            }
            wp_reset_postdata();
        }

        return rest_ensure_response( $events );
    }
}

// Instantiate the class to fire up the plugin
new MobiVents_Manager();
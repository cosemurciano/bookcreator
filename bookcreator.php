<?php
/**
 * Plugin Name: BookCreator
 * Description: Custom post type and management interface for creating books.
 * Version: 1.1
 * Author: Cosè Murciano
 * Text Domain: bookcreator
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Keep track of the most recent PHPePub loading error.
 *
 * @var string
 */
$GLOBALS['bookcreator_epub_library_error'] = '';
$GLOBALS['bookcreator_pdf_library_error']  = '';

function bookcreator_get_pdf_library_instruction_text() {
    return __( 'Per generare i PDF è necessario che la libreria mPDF 8.1.0 sia presente nella cartella "mpdf-8-1-0" del plugin.', 'bookcreator' );
}

function bookcreator_get_pdf_library_notice_markup() {
    return '<strong>' . esc_html__( 'Generazione PDF non disponibile', 'bookcreator' ) . '</strong><br />' . esc_html( bookcreator_get_pdf_library_instruction_text() );
}

function bookcreator_register_pdf_autoloader() {
    static $registered = false;

    if ( $registered ) {
        return;
    }

    $mpdf_dir = __DIR__ . '/mpdf-8-1-0/src/';

    spl_autoload_register(
        static function ( $class ) use ( $mpdf_dir ) {
            if ( 0 !== strpos( $class, 'Mpdf\\' ) ) {
                return;
            }

            $relative = substr( $class, 5 );
            $path     = $mpdf_dir . str_replace( '\\', '/', $relative ) . '.php';

            if ( file_exists( $path ) ) {
                require_once $path;
            }
        },
        true,
        true
    );

    $registered = true;
}

function bookcreator_load_mpdf_library() {
    static $loaded = null;

    if ( null !== $loaded ) {
        return $loaded;
    }

    global $bookcreator_pdf_library_error;
    $bookcreator_pdf_library_error = '';

    $mpdf_src = __DIR__ . '/mpdf-8-1-0/src';
    if ( ! is_dir( $mpdf_src ) ) {
        $bookcreator_pdf_library_error = bookcreator_get_pdf_library_instruction_text();
        $loaded                        = false;

        return $loaded;
    }

    $psr_stubs = __DIR__ . '/psr-log-stubs.php';
    if ( file_exists( $psr_stubs ) ) {
        if ( ! interface_exists( 'Psr\\Log\\LoggerInterface' ) || ! interface_exists( 'Psr\\Log\\LoggerAwareInterface' ) || ! class_exists( 'Psr\\Log\\NullLogger' ) ) {
            require_once $psr_stubs;
        }
    }

    bookcreator_register_pdf_autoloader();

    if ( ! class_exists( 'Mpdf\\Mpdf' ) ) {
        $bookcreator_pdf_library_error = bookcreator_get_pdf_library_instruction_text();
        $loaded                        = false;

        return $loaded;
    }

    $loaded = true;

    return $loaded;
}

function bookcreator_get_pdf_library_error_message() {
    global $bookcreator_pdf_library_error;

    if ( $bookcreator_pdf_library_error ) {
        return $bookcreator_pdf_library_error;
    }

    return bookcreator_get_pdf_library_instruction_text();
}

function bookcreator_is_pdf_library_available() {
    return bookcreator_load_mpdf_library();
}

/**
 * Load plugin textdomain.
 */
function bookcreator_load_textdomain() {
    load_plugin_textdomain( 'bookcreator', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'bookcreator_load_textdomain' );

function bookcreator_get_default_claude_settings() {
    return array(
        'enabled'         => false,
        'api_key'         => '',
        'default_model'   => 'claude-3-opus-20240229',
        'request_timeout' => 30,
    );
}

function bookcreator_get_claude_settings() {
    $defaults = bookcreator_get_default_claude_settings();
    $saved    = get_option( 'bookcreator_claude_settings', array() );

    if ( ! is_array( $saved ) ) {
        $saved = array();
    }

    $settings = wp_parse_args( $saved, $defaults );

    $settings['enabled']         = ! empty( $settings['enabled'] );
    $settings['api_key']         = isset( $settings['api_key'] ) ? (string) $settings['api_key'] : '';
    $settings['default_model']   = isset( $settings['default_model'] ) ? (string) $settings['default_model'] : $defaults['default_model'];
    $settings['request_timeout'] = isset( $settings['request_timeout'] ) ? (int) $settings['request_timeout'] : $defaults['request_timeout'];

    return $settings;
}

function bookcreator_get_allowed_claude_models() {
    $models = array(
        'claude-3-opus-20240229'   => __( 'Claude 3 Opus', 'bookcreator' ),
        'claude-3-sonnet-20240229' => __( 'Claude 3 Sonnet', 'bookcreator' ),
        'claude-3-haiku-20240307'  => __( 'Claude 3 Haiku', 'bookcreator' ),
    );

    return apply_filters( 'bookcreator_claude_allowed_models', $models );
}

function bookcreator_sanitize_claude_settings( $input ) {
    $existing = bookcreator_get_claude_settings();
    $defaults = bookcreator_get_default_claude_settings();

    if ( ! is_array( $input ) ) {
        return $existing;
    }

    $input  = wp_unslash( $input );
    $output = $existing;

    $output['enabled'] = ! empty( $input['enabled'] );

    if ( isset( $input['api_key'] ) ) {
        $api_key = trim( (string) $input['api_key'] );

        if ( '' === $api_key && ! empty( $existing['api_key'] ) && ! empty( $input['keep_existing_api_key'] ) ) {
            $output['api_key'] = $existing['api_key'];
        } elseif ( '' !== $api_key ) {
            $output['api_key'] = sanitize_text_field( $api_key );
        } else {
            $output['api_key'] = '';
        }
    }

    $allowed_models = bookcreator_get_allowed_claude_models();

    if ( isset( $input['default_model'] ) ) {
        $model = (string) $input['default_model'];

        if ( isset( $allowed_models[ $model ] ) ) {
            $output['default_model'] = $model;
        } elseif ( empty( $existing['default_model'] ) ) {
            $output['default_model'] = $defaults['default_model'];
        }
    }

    if ( isset( $input['request_timeout'] ) ) {
        $timeout = (int) $input['request_timeout'];
        $timeout = max( 5, min( 120, $timeout ) );

        $output['request_timeout'] = $timeout;
    }

    return $output;
}

function bookcreator_register_claude_settings() {
    register_setting(
        'bookcreator_settings',
        'bookcreator_claude_settings',
        array(
            'type'              => 'array',
            'sanitize_callback' => 'bookcreator_sanitize_claude_settings',
            'default'           => bookcreator_get_default_claude_settings(),
        )
    );

    add_settings_section(
        'bookcreator_claude_section',
        __( 'Integrazione Claude AI', 'bookcreator' ),
        'bookcreator_claude_settings_section_description',
        'bookcreator-settings'
    );

    add_settings_field(
        'bookcreator_claude_enabled',
        __( 'Abilita integrazione', 'bookcreator' ),
        'bookcreator_claude_settings_field_enabled',
        'bookcreator-settings',
        'bookcreator_claude_section'
    );

    add_settings_field(
        'bookcreator_claude_api_key',
        __( 'Claude API Key', 'bookcreator' ),
        'bookcreator_claude_settings_field_api_key',
        'bookcreator-settings',
        'bookcreator_claude_section'
    );

    add_settings_field(
        'bookcreator_claude_test_connection',
        __( 'Test connessione', 'bookcreator' ),
        'bookcreator_claude_settings_field_test_connection',
        'bookcreator-settings',
        'bookcreator_claude_section'
    );

    add_settings_field(
        'bookcreator_claude_default_model',
        __( 'Modello predefinito', 'bookcreator' ),
        'bookcreator_claude_settings_field_default_model',
        'bookcreator-settings',
        'bookcreator_claude_section'
    );

    add_settings_field(
        'bookcreator_claude_request_timeout',
        __( 'Timeout richieste (secondi)', 'bookcreator' ),
        'bookcreator_claude_settings_field_request_timeout',
        'bookcreator-settings',
        'bookcreator_claude_section'
    );
}
add_action( 'admin_init', 'bookcreator_register_claude_settings' );

function bookcreator_register_settings_page() {
    add_submenu_page(
        'edit.php?post_type=book_creator',
        __( 'Impostazioni', 'bookcreator' ),
        __( 'Impostazioni', 'bookcreator' ),
        'manage_options',
        'bookcreator-settings',
        'bookcreator_render_settings_page'
    );
}
add_action( 'admin_menu', 'bookcreator_register_settings_page' );

function bookcreator_settings_admin_enqueue( $hook ) {
    if ( 'book_creator_page_bookcreator-settings' !== $hook ) {
        return;
    }

    wp_enqueue_script(
        'bookcreator-settings',
        plugin_dir_url( __FILE__ ) . 'js/settings.js',
        array( 'jquery' ),
        '1.0',
        true
    );

    wp_localize_script(
        'bookcreator-settings',
        'bookcreatorClaudeSettings',
        array(
            'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'bookcreator_claude_test_connection' ),
            'messages' => array(
                'testing'      => __( 'Verifica in corso…', 'bookcreator' ),
                'genericError' => __( 'Impossibile completare il test di connessione. Riprova.', 'bookcreator' ),
            ),
        )
    );
}
add_action( 'admin_enqueue_scripts', 'bookcreator_settings_admin_enqueue' );

function bookcreator_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Non hai i permessi per accedere a questa pagina.', 'bookcreator' ) );
    }

    $settings = bookcreator_get_claude_settings();
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__( 'Impostazioni BookCreator', 'bookcreator' ); ?></h1>
        <?php settings_errors( 'bookcreator_settings' ); ?>
        <form method="post" action="options.php" novalidate="novalidate">
            <?php
            settings_fields( 'bookcreator_settings' );
            do_settings_sections( 'bookcreator-settings' );
            submit_button();
            ?>
        </form>
        <?php if ( empty( $settings['api_key'] ) ) : ?>
            <p><em><?php echo esc_html__( 'Suggerimento: per maggiore sicurezza puoi definire la costante BOOKCREATOR_CLAUDE_API_KEY nel file wp-config.php.', 'bookcreator' ); ?></em></p>
        <?php endif; ?>
    </div>
    <?php
}

function bookcreator_claude_settings_section_description() {
    echo '<p>' . esc_html__( 'Configura le credenziali necessarie per collegare il plugin alle API di Claude AI in modo sicuro.', 'bookcreator' ) . '</p>';
}

function bookcreator_claude_settings_field_enabled() {
    $settings = bookcreator_get_claude_settings();
    ?>
    <label for="bookcreator_claude_enabled">
        <input type="checkbox" name="bookcreator_claude_settings[enabled]" id="bookcreator_claude_enabled" value="1" <?php checked( $settings['enabled'] ); ?> />
        <?php esc_html_e( 'Attiva la connessione verso le API di Claude utilizzando le impostazioni sottostanti.', 'bookcreator' ); ?>
    </label>
    <?php
}

function bookcreator_claude_settings_field_api_key() {
    $settings = bookcreator_get_claude_settings();
    $placeholder_length = $settings['api_key'] ? min( 32, max( 8, strlen( $settings['api_key'] ) ) ) : 0;
    $placeholder        = $placeholder_length ? str_repeat( '•', $placeholder_length ) : '';
    ?>
    <input type="hidden" name="bookcreator_claude_settings[keep_existing_api_key]" value="1" />
    <input type="password" name="bookcreator_claude_settings[api_key]" id="bookcreator_claude_api_key" value="" autocomplete="new-password" class="regular-text" aria-describedby="bookcreator_claude_api_key_help" />
    <?php if ( $placeholder ) : ?>
        <p id="bookcreator_claude_api_key_help" class="description">
            <?php
            printf(
                esc_html__( 'Una chiave è già stata salvata (%s). Inserisci una nuova chiave per sostituirla oppure lascia vuoto il campo per mantenerla.', 'bookcreator' ),
                esc_html( $placeholder )
            );
            ?>
        </p>
    <?php else : ?>
        <p id="bookcreator_claude_api_key_help" class="description"><?php esc_html_e( 'Inserisci la Claude API Key fornita da Anthropic. Il valore non verrà visualizzato nuovamente dopo il salvataggio.', 'bookcreator' ); ?></p>
    <?php endif; ?>
    <?php
    $console_link = sprintf(
        '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
        esc_url( 'https://console.anthropic.com/' ),
        esc_html__( 'Anthropic Console', 'bookcreator' )
    );
    $console_message = sprintf(
        __( 'Ottieni la tua API key da %s.', 'bookcreator' ),
        $console_link
    );
    echo wp_kses(
        sprintf( '<p class="description">%s</p>', $console_message ),
        array(
            'p' => array( 'class' => array() ),
            'a' => array(
                'href'   => array(),
                'target' => array(),
                'rel'    => array(),
            ),
        )
    );
    ?>
    <?php
}

function bookcreator_claude_settings_field_test_connection() {
    ?>
    <button type="button" class="button" id="bookcreator_claude_test_connection"><?php esc_html_e( 'Verifica connessione', 'bookcreator' ); ?></button>
    <span id="bookcreator_claude_test_connection_status" class="bookcreator-status" aria-live="polite"></span>
    <p class="description"><?php esc_html_e( 'Esegui un test rapido per assicurarti che le impostazioni siano corrette e che la chiave API sia valida.', 'bookcreator' ); ?></p>
    <?php
}

function bookcreator_claude_settings_field_default_model() {
    $settings = bookcreator_get_claude_settings();
    $models   = bookcreator_get_allowed_claude_models();
    ?>
    <select name="bookcreator_claude_settings[default_model]" id="bookcreator_claude_default_model">
        <?php foreach ( $models as $model_value => $model_label ) : ?>
            <option value="<?php echo esc_attr( $model_value ); ?>" <?php selected( $settings['default_model'], $model_value ); ?>><?php echo esc_html( $model_label ); ?></option>
        <?php endforeach; ?>
    </select>
    <p class="description"><?php esc_html_e( 'Seleziona il modello predefinito che verrà utilizzato dalle future integrazioni con Claude AI.', 'bookcreator' ); ?></p>
    <?php
}

function bookcreator_claude_settings_field_request_timeout() {
    $settings = bookcreator_get_claude_settings();
    ?>
    <input type="number" name="bookcreator_claude_settings[request_timeout]" id="bookcreator_claude_request_timeout" value="<?php echo esc_attr( $settings['request_timeout'] ); ?>" min="5" max="120" step="1" />
    <p class="description"><?php esc_html_e( 'Tempo massimo di attesa, in secondi, per le chiamate alle API prima che vengano interrotte.', 'bookcreator' ); ?></p>
    <?php
}

function bookcreator_get_claude_api_key() {
    if ( defined( 'BOOKCREATOR_CLAUDE_API_KEY' ) && BOOKCREATOR_CLAUDE_API_KEY ) {
        return BOOKCREATOR_CLAUDE_API_KEY;
    }

    $settings = bookcreator_get_claude_settings();

    return isset( $settings['api_key'] ) ? $settings['api_key'] : '';
}

function bookcreator_is_claude_enabled() {
    $settings = bookcreator_get_claude_settings();

    if ( empty( $settings['enabled'] ) ) {
        return false;
    }

    $api_key = bookcreator_get_claude_api_key();

    return ! empty( $api_key );
}

function bookcreator_ajax_test_claude_connection() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error(
            array( 'message' => __( 'Non hai i permessi per eseguire il test di connessione.', 'bookcreator' ) ),
            403
        );
    }

    check_ajax_referer( 'bookcreator_claude_test_connection', 'nonce' );

    $api_key = bookcreator_get_claude_api_key();

    if ( empty( $api_key ) ) {
        wp_send_json_error(
            array( 'message' => __( 'Impossibile eseguire il test perché non è stata configurata alcuna API key.', 'bookcreator' ) )
        );
    }

    $settings     = bookcreator_get_claude_settings();
    $request_time = isset( $settings['request_timeout'] ) ? (int) $settings['request_timeout'] : 30;

    $response = wp_remote_get(
        'https://api.anthropic.com/v1/models',
        array(
            'timeout' => max( 5, min( 120, $request_time ) ),
            'headers' => array(
                'x-api-key'         => $api_key,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
                'accept'            => 'application/json',
            ),
        )
    );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error(
            array(
                'message' => sprintf(
                    /* translators: %s: error message. */
                    __( 'Errore di connessione: %s', 'bookcreator' ),
                    $response->get_error_message()
                ),
            )
        );
    }

    $status_code = (int) wp_remote_retrieve_response_code( $response );

    if ( 200 === $status_code ) {
        wp_send_json_success(
            array( 'message' => __( 'Connessione a Claude AI riuscita.', 'bookcreator' ) )
        );
    }

    if ( 401 === $status_code || 403 === $status_code ) {
        wp_send_json_error(
            array( 'message' => __( 'API key non valida o priva dei permessi necessari.', 'bookcreator' ) )
        );
    }

    wp_send_json_error(
        array(
            'message' => sprintf(
                /* translators: %d: HTTP status code. */
                __( 'Il test non è riuscito. Codice di risposta: %d.', 'bookcreator' ),
                $status_code
            ),
        )
    );
}
add_action( 'wp_ajax_bookcreator_test_claude_connection', 'bookcreator_ajax_test_claude_connection' );

/**
 * Register custom post type and taxonomy.
 */
function bookcreator_register_post_type() {
    $labels = array(
        'name'               => __( 'Books', 'bookcreator' ),
        'singular_name'      => __( 'Book', 'bookcreator' ),
        'menu_name'          => __( 'Books', 'bookcreator' ),
        'name_admin_bar'     => __( 'Book', 'bookcreator' ),
        'add_new'            => __( 'Add New', 'bookcreator' ),
        'add_new_item'       => __( 'Add New Book', 'bookcreator' ),
        'new_item'           => __( 'New Book', 'bookcreator' ),
        'edit_item'          => __( 'Edit Book', 'bookcreator' ),
        'view_item'          => __( 'View Book', 'bookcreator' ),
        'all_items'          => __( 'All Books', 'bookcreator' ),
        'search_items'       => __( 'Search Books', 'bookcreator' ),
        'not_found'          => __( 'No books found.', 'bookcreator' ),
        'not_found_in_trash' => __( 'No books found in Trash.', 'bookcreator' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'supports'           => array( 'title', 'thumbnail' ),
        'rewrite'            => array( 'slug' => 'book' ),
        'has_archive'        => true,
        'menu_icon'          => 'dashicons-book-alt',
        'taxonomies'         => array( 'book_genre' ),
    );

    register_post_type( 'book_creator', $args );

    $chapter_labels = array(
        'name'               => __( 'Chapters', 'bookcreator' ),
        'singular_name'      => __( 'Chapter', 'bookcreator' ),
        'menu_name'          => __( 'Chapters', 'bookcreator' ),
        'name_admin_bar'     => __( 'Chapter', 'bookcreator' ),
        'add_new'            => __( 'Add New', 'bookcreator' ),
        'add_new_item'       => __( 'Add New Chapter', 'bookcreator' ),
        'new_item'           => __( 'New Chapter', 'bookcreator' ),
        'edit_item'          => __( 'Edit Chapter', 'bookcreator' ),
        'view_item'          => __( 'View Chapter', 'bookcreator' ),
        'all_items'          => __( 'All Chapters', 'bookcreator' ),
        'search_items'       => __( 'Search Chapters', 'bookcreator' ),
        'not_found'          => __( 'No chapters found.', 'bookcreator' ),
        'not_found_in_trash' => __( 'No chapters found in Trash.', 'bookcreator' ),
    );

    $chapter_args = array(
        'labels'        => $chapter_labels,
        'public'        => false,
        'show_ui'       => true,
        // Display "Chapters" under the "Books" menu.
        'show_in_menu'  => 'edit.php?post_type=book_creator',
        'supports'      => array( 'title', 'editor', 'thumbnail' ),
        'has_archive'   => false,
        'rewrite'       => false,
        'menu_icon'     => 'dashicons-media-document',
    );

    register_post_type( 'bc_chapter', $chapter_args );

    $taxonomy_labels = array(
        'name'              => __( 'Book Genres', 'bookcreator' ),
        'singular_name'     => __( 'Book Genre', 'bookcreator' ),
        'search_items'      => __( 'Search Genres', 'bookcreator' ),
        'all_items'         => __( 'All Genres', 'bookcreator' ),
        'parent_item'       => __( 'Parent Genre', 'bookcreator' ),
        'parent_item_colon' => __( 'Parent Genre:', 'bookcreator' ),
        'edit_item'         => __( 'Edit Genre', 'bookcreator' ),
        'update_item'       => __( 'Update Genre', 'bookcreator' ),
        'add_new_item'      => __( 'Add New Genre', 'bookcreator' ),
        'new_item_name'     => __( 'New Genre Name', 'bookcreator' ),
        'menu_name'         => __( 'Book Genres', 'bookcreator' ),
    );

    $taxonomy_args = array(
        'labels'            => $taxonomy_labels,
        'hierarchical'      => true,
        'show_admin_column' => true,
        'show_ui'           => true,
        'rewrite'           => array( 'slug' => 'book-genre' ),
    );

    register_taxonomy( 'book_genre', array( 'book_creator' ), $taxonomy_args );
}
add_action( 'init', 'bookcreator_register_post_type' );

function bookcreator_register_paragraph_post_type() {
    $labels = array(
        'name'               => __( 'Paragraphs', 'bookcreator' ),
        'singular_name'      => __( 'Paragraph', 'bookcreator' ),
        'menu_name'          => __( 'Paragraphs', 'bookcreator' ),
        'name_admin_bar'     => __( 'Paragraph', 'bookcreator' ),
        'add_new'            => __( 'Add New', 'bookcreator' ),
        'add_new_item'       => __( 'Add New Paragraph', 'bookcreator' ),
        'new_item'           => __( 'New Paragraph', 'bookcreator' ),
        'edit_item'          => __( 'Edit Paragraph', 'bookcreator' ),
        'view_item'          => __( 'View Paragraph', 'bookcreator' ),
        'all_items'          => __( 'All Paragraphs', 'bookcreator' ),
        'search_items'       => __( 'Search Paragraphs', 'bookcreator' ),
        'not_found'          => __( 'No paragraphs found.', 'bookcreator' ),
        'not_found_in_trash' => __( 'No paragraphs found in Trash.', 'bookcreator' ),
    );

    $args = array(
        'labels'       => $labels,
        'public'       => false,
        'show_ui'      => true,
        // Display "Paragraphs" directly under the "Books" menu.
        'show_in_menu' => 'edit.php?post_type=book_creator',
        'supports'     => array( 'title', 'editor', 'thumbnail' ),
        'has_archive'  => false,
        'rewrite'      => false,
        'menu_icon'    => 'dashicons-media-text',
    );

    register_post_type( 'bc_paragraph', $args );
}
add_action( 'init', 'bookcreator_register_paragraph_post_type' );

function bookcreator_add_thumbnail_support() {
    add_theme_support( 'post-thumbnails', array( 'book_creator', 'bc_chapter', 'bc_paragraph' ) );
}
add_action( 'after_setup_theme', 'bookcreator_add_thumbnail_support' );

/**
 * Flush rewrite rules on activation/deactivation and ensure default term exists.
 */
function bookcreator_activate() {
    bookcreator_register_post_type();
    bookcreator_register_paragraph_post_type();
    if ( ! term_exists( 'Book', 'book_genre' ) ) {
        wp_insert_term( 'Book', 'book_genre' );
    }
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'bookcreator_activate' );

function bookcreator_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'bookcreator_deactivate' );

/**
 * Add meta boxes.
 */
function bookcreator_add_meta_boxes() {
    add_meta_box( 'bc_identification', __( 'Identification', 'bookcreator' ), 'bookcreator_meta_box_identification', 'book_creator', 'normal', 'high' );
    add_meta_box( 'bc_descriptive', __( 'Descriptive', 'bookcreator' ), 'bookcreator_meta_box_descriptive', 'book_creator', 'normal', 'default' );
    add_meta_box( 'bc_prelim', __( 'Preliminary Parts', 'bookcreator' ), 'bookcreator_meta_box_prelim', 'book_creator', 'normal', 'default' );
    add_meta_box( 'bc_final', __( 'Final Parts', 'bookcreator' ), 'bookcreator_meta_box_final', 'book_creator', 'normal', 'default' );
    add_meta_box( 'bc_chapter_books', __( 'Books', 'bookcreator' ), 'bookcreator_meta_box_chapter_books', 'bc_chapter', 'side', 'default' );
    add_meta_box( 'bc_paragraph_chapters', __( 'Chapters', 'bookcreator' ), 'bookcreator_meta_box_paragraph_chapters', 'bc_paragraph', 'side', 'default' );
    add_meta_box( 'bc_paragraph_footnotes', __( 'Footnotes', 'bookcreator' ), 'bookcreator_meta_box_paragraph_footnotes', 'bc_paragraph', 'normal', 'default' );
    add_meta_box( 'bc_paragraph_citations', __( 'Citations', 'bookcreator' ), 'bookcreator_meta_box_paragraph_citations', 'bc_paragraph', 'normal', 'default' );
}
add_action( 'add_meta_boxes', 'bookcreator_add_meta_boxes' );

function bookcreator_meta_box_identification( $post ) {
    wp_nonce_field( 'bookcreator_save_meta', 'bookcreator_meta_nonce' );
    ?>
    <p><label for="bc_subtitle"><?php esc_html_e( 'Sottotitolo', 'bookcreator' ); ?></label><br/>
    <input type="text" name="bc_subtitle" id="bc_subtitle" value="<?php echo esc_attr( get_post_meta( $post->ID, 'bc_subtitle', true ) ); ?>" class="widefat" /></p>

    <p><label for="bc_author"><?php esc_html_e( 'Autore principale', 'bookcreator' ); ?></label><br/>
    <input type="text" name="bc_author" id="bc_author" value="<?php echo esc_attr( get_post_meta( $post->ID, 'bc_author', true ) ); ?>" class="widefat" /></p>

    <p><label for="bc_coauthors"><?php esc_html_e( 'Co-autori', 'bookcreator' ); ?></label><br/>
    <input type="text" name="bc_coauthors" id="bc_coauthors" value="<?php echo esc_attr( get_post_meta( $post->ID, 'bc_coauthors', true ) ); ?>" class="widefat" /></p>

    <p><label for="bc_publisher"><?php esc_html_e( 'Editore', 'bookcreator' ); ?></label><br/>
    <input type="text" name="bc_publisher" id="bc_publisher" value="<?php echo esc_attr( get_post_meta( $post->ID, 'bc_publisher', true ) ); ?>" class="widefat" /></p>

    <p><label for="bc_isbn"><?php esc_html_e( 'ISBN', 'bookcreator' ); ?></label><br/>
    <input type="text" name="bc_isbn" id="bc_isbn" value="<?php echo esc_attr( get_post_meta( $post->ID, 'bc_isbn', true ) ); ?>" class="widefat" /></p>

    <p><label for="bc_pub_date"><?php esc_html_e( 'Data di pubblicazione', 'bookcreator' ); ?></label><br/>
    <input type="date" name="bc_pub_date" id="bc_pub_date" value="<?php echo esc_attr( get_post_meta( $post->ID, 'bc_pub_date', true ) ); ?>" /></p>

    <p><label for="bc_edition"><?php esc_html_e( 'Edizione/Versione', 'bookcreator' ); ?></label><br/>
    <input type="text" name="bc_edition" id="bc_edition" value="<?php echo esc_attr( get_post_meta( $post->ID, 'bc_edition', true ) ); ?>" class="widefat" /></p>
    <?php
}

function bookcreator_get_language_options() {
    return array(
        'it' => __( 'Italiano', 'bookcreator' ),
        'en' => __( 'Inglese', 'bookcreator' ),
        'fr' => __( 'Francese', 'bookcreator' ),
        'de' => __( 'Tedesco', 'bookcreator' ),
        'es' => __( 'Spagnolo', 'bookcreator' ),
        'pt' => __( 'Portoghese', 'bookcreator' ),
        'zh' => __( 'Cinese', 'bookcreator' ),
        'ja' => __( 'Giapponese', 'bookcreator' ),
        'ru' => __( 'Russo', 'bookcreator' ),
    );
}

function bookcreator_get_language_label( $code ) {
    $code     = (string) $code;
    $languages = bookcreator_get_language_options();

    if ( '' === $code ) {
        return '';
    }

    if ( isset( $languages[ $code ] ) ) {
        return $languages[ $code ];
    }

    $normalized = strtolower( $code );
    if ( isset( $languages[ $normalized ] ) ) {
        return $languages[ $normalized ];
    }

    return $code;
}

function bookcreator_meta_box_descriptive( $post ) {
    $languages = bookcreator_get_language_options();
    $language = get_post_meta( $post->ID, 'bc_language', true );

    if ( $language && ! isset( $languages[ $language ] ) ) {
        $languages = array( $language => $language ) + $languages;
    }
    ?>
    <p><label for="bc_language"><?php esc_html_e( 'Lingua', 'bookcreator' ); ?></label><br/>
    <select name="bc_language" id="bc_language">
        <?php foreach ( $languages as $code => $label ) : ?>
            <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $language, $code ); ?>><?php echo esc_html( $label ); ?></option>
        <?php endforeach; ?>
    </select></p>

    <p><label for="bc_description"><?php esc_html_e( 'Descrizione', 'bookcreator' ); ?></label></p>
    <?php
    $description = get_post_meta( $post->ID, 'bc_description', true );
    wp_editor( $description, 'bc_description', array(
        'textarea_name' => 'bc_description',
        'textarea_rows' => 4,
    ) );
    ?>

    <p><label for="bc_keywords"><?php esc_html_e( 'Parole chiave', 'bookcreator' ); ?></label><br/>
    <input type="text" name="bc_keywords" id="bc_keywords" value="<?php echo esc_attr( get_post_meta( $post->ID, 'bc_keywords', true ) ); ?>" class="widefat" /></p>

    <p><label for="bc_audience"><?php esc_html_e( 'Pubblico', 'bookcreator' ); ?></label><br/>
    <input type="text" name="bc_audience" id="bc_audience" value="<?php echo esc_attr( get_post_meta( $post->ID, 'bc_audience', true ) ); ?>" class="widefat" /></p>
    <?php
}

function bookcreator_meta_box_prelim( $post ) {
    $cover_id       = get_post_meta( $post->ID, 'bc_cover', true );
    ?>
    <p><label for="bc_cover"><?php esc_html_e( 'Copertina', 'bookcreator' ); ?></label><br/>
    <input type="file" name="bc_cover" id="bc_cover" /><br/>
    <?php if ( $cover_id ) { echo wp_get_attachment_image( $cover_id, array( 100, 100 ) ); } ?></p>


    <p><label for="bc_frontispiece"><?php esc_html_e( 'Frontespizio', 'bookcreator' ); ?></label></p>
    <?php
    $frontispiece = get_post_meta( $post->ID, 'bc_frontispiece', true );
    wp_editor( $frontispiece, 'bc_frontispiece', array(
        'textarea_name' => 'bc_frontispiece',
        'textarea_rows' => 3,
    ) );
    ?>

    <p><label for="bc_copyright"><?php esc_html_e( 'Copyright', 'bookcreator' ); ?></label><br/>
    <textarea name="bc_copyright" id="bc_copyright" class="widefat" rows="3"><?php echo esc_textarea( get_post_meta( $post->ID, 'bc_copyright', true ) ); ?></textarea></p>

    <p><label for="bc_dedication"><?php esc_html_e( 'Dedica', 'bookcreator' ); ?></label><br/>
    <textarea name="bc_dedication" id="bc_dedication" class="widefat" rows="3"><?php echo esc_textarea( get_post_meta( $post->ID, 'bc_dedication', true ) ); ?></textarea></p>

    <p><label for="bc_preface"><?php esc_html_e( 'Prefazione', 'bookcreator' ); ?></label><br/>
    <textarea name="bc_preface" id="bc_preface" class="widefat" rows="3"><?php echo esc_textarea( get_post_meta( $post->ID, 'bc_preface', true ) ); ?></textarea></p>
    <?php
}

function bookcreator_meta_box_final( $post ) {
    ?>
    <p><label for="bc_appendix"><?php esc_html_e( 'Appendice', 'bookcreator' ); ?></label><br/>
    <textarea name="bc_appendix" id="bc_appendix" class="widefat" rows="3"><?php echo esc_textarea( get_post_meta( $post->ID, 'bc_appendix', true ) ); ?></textarea></p>

    <p><label for="bc_bibliography"><?php esc_html_e( 'Bibliografia', 'bookcreator' ); ?></label></p>
    <?php
    $bibliography = get_post_meta( $post->ID, 'bc_bibliography', true );
    wp_editor( $bibliography, 'bc_bibliography', array(
        'textarea_name' => 'bc_bibliography',
        'textarea_rows' => 3,
    ) );
    ?>

    <p><label for="bc_author_note"><?php esc_html_e( 'Nota dell\'autore', 'bookcreator' ); ?></label><br/>
    <textarea name="bc_author_note" id="bc_author_note" class="widefat" rows="3"><?php echo esc_textarea( get_post_meta( $post->ID, 'bc_author_note', true ) ); ?></textarea></p>
    <?php
}

function bookcreator_meta_box_chapter_books( $post ) {
    wp_nonce_field( 'bookcreator_save_chapter_meta', 'bookcreator_chapter_meta_nonce' );
    $books    = get_posts( array( 'post_type' => 'book_creator', 'numberposts' => -1 ) );
    $selected = (array) get_post_meta( $post->ID, 'bc_books', true );
    echo '<ul>';
    foreach ( $books as $book ) {
        $book_id = (string) $book->ID;
        echo '<li><label><input type="checkbox" name="bc_books[]" value="' . esc_attr( $book_id ) . '" ' . checked( in_array( $book_id, $selected, true ), true, false ) . ' /> ' . esc_html( $book->post_title ) . '</label></li>';
    }
    echo '</ul>';
}

function bookcreator_meta_box_paragraph_chapters( $post ) {
    wp_nonce_field( 'bookcreator_save_paragraph_meta', 'bookcreator_paragraph_meta_nonce' );

    $chapters = get_posts( array( 'post_type' => 'bc_chapter', 'numberposts' => -1 ) );
    $books    = get_posts( array( 'post_type' => 'book_creator', 'numberposts' => -1 ) );
    $selected = (array) get_post_meta( $post->ID, 'bc_chapters', true );

    echo '<p><label for="bc_chapter_book_filter">' . esc_html__( 'Filter by Book', 'bookcreator' ) . '</label><br />';
    echo '<select id="bc_chapter_book_filter"><option value="">' . esc_html__( 'All Books', 'bookcreator' ) . '</option>';
    foreach ( $books as $book ) {
        echo '<option value="' . esc_attr( $book->ID ) . '">' . esc_html( $book->post_title ) . '</option>';
    }
    echo '</select></p>';

    echo '<ul id="bc_chapters_list">';
    foreach ( $chapters as $chapter ) {
        $chapter_id    = (string) $chapter->ID;
        $chapter_books = (array) get_post_meta( $chapter_id, 'bc_books', true );
        $data_books    = implode( ' ', array_map( 'strval', $chapter_books ) );
        echo '<li data-books="' . esc_attr( $data_books ) . '"><label><input type="checkbox" name="bc_chapters[]" value="' . esc_attr( $chapter_id ) . '" ' . checked( in_array( $chapter_id, $selected, true ), true, false ) . ' /> ' . esc_html( $chapter->post_title ) . '</label></li>';
    }
    echo '</ul>';

    ?>
    <script>
    jQuery(function($){
        $('#bc_chapter_book_filter').on('change', function(){
            var book = $(this).val();
            $('#bc_chapters_list li').show();
            if (book) {
                $('#bc_chapters_list li').each(function(){
                    var books = ($(this).data('books') + '').split(' ');
                    if ($.inArray(book, books) === -1) {
                        $(this).hide();
                    }
                });
            }
        });
    });
    </script>
    <?php
}

function bookcreator_meta_box_paragraph_footnotes( $post ) {
    $footnotes = get_post_meta( $post->ID, 'bc_footnotes', true );
    wp_editor( $footnotes, 'bc_footnotes', array( 'textarea_name' => 'bc_footnotes' ) );
}

function bookcreator_meta_box_paragraph_citations( $post ) {
    $citations = get_post_meta( $post->ID, 'bc_citations', true );
    wp_editor( $citations, 'bc_citations', array( 'textarea_name' => 'bc_citations' ) );
}

/**
 * Save meta box data.
 */
function bookcreator_save_meta( $post_id ) {
    if ( ! isset( $_POST['bookcreator_meta_nonce'] ) || ! wp_verify_nonce( $_POST['bookcreator_meta_nonce'], 'bookcreator_save_meta' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $fields = array(
        'bc_subtitle'      => 'sanitize_text_field',
        'bc_author'        => 'sanitize_text_field',
        'bc_coauthors'     => 'sanitize_text_field',
        'bc_publisher'     => 'sanitize_text_field',
        'bc_isbn'          => 'sanitize_text_field',
        'bc_pub_date'      => 'sanitize_text_field',
        'bc_edition'       => 'sanitize_text_field',
        'bc_language'      => 'sanitize_text_field',
        'bc_description'   => 'wp_kses_post',
        'bc_keywords'      => 'sanitize_text_field',
        'bc_audience'      => 'sanitize_text_field',
        'bc_frontispiece'  => 'wp_kses_post',
        'bc_copyright'     => 'wp_kses_post',
        'bc_dedication'    => 'wp_kses_post',
        'bc_preface'       => 'wp_kses_post',
        'bc_appendix'      => 'wp_kses_post',
        'bc_bibliography'  => 'wp_kses_post',
        'bc_author_note'   => 'wp_kses_post',
    );

    foreach ( $fields as $field => $sanitize ) {
        if ( isset( $_POST[ $field ] ) ) {
            $value = call_user_func( $sanitize, wp_unslash( $_POST[ $field ] ) );
            update_post_meta( $post_id, $field, $value );
        }
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    if ( ! empty( $_FILES['bc_cover']['name'] ) ) {
        $cover_id = media_handle_upload( 'bc_cover', $post_id );
        if ( ! is_wp_error( $cover_id ) ) {
            update_post_meta( $post_id, 'bc_cover', $cover_id );
        }
    }

    delete_post_meta( $post_id, 'bc_retina_cover' );
}
add_action( 'save_post_book_creator', 'bookcreator_save_meta' );

/**
 * Remove legacy retina cover metadata from the database.
 */
function bookcreator_cleanup_retina_cover_meta() {
    if ( get_option( 'bookcreator_retina_cover_meta_removed' ) ) {
        return;
    }

    delete_metadata( 'post', 0, 'bc_retina_cover', '', true );
    update_option( 'bookcreator_retina_cover_meta_removed', 1 );
}
add_action( 'plugins_loaded', 'bookcreator_cleanup_retina_cover_meta' );

function bookcreator_admin_enqueue( $hook ) {
    if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
        return;
    }
    $screen = get_current_screen();
    if ( 'book_creator' !== $screen->post_type ) {
        return;
    }
    wp_enqueue_script( 'konva', 'https://cdn.jsdelivr.net/npm/konva@9.3.0/konva.min.js', array(), '9.3.0', true );
    wp_enqueue_script( 'bookcreator-admin', plugin_dir_url( __FILE__ ) . 'js/admin.js', array( 'jquery', 'konva' ), '1.1', true );
}
add_action( 'admin_enqueue_scripts', 'bookcreator_admin_enqueue' );

function bookcreator_save_chapter_meta( $post_id ) {
    if ( ! isset( $_POST['bookcreator_chapter_meta_nonce'] ) || ! wp_verify_nonce( $_POST['bookcreator_chapter_meta_nonce'], 'bookcreator_save_chapter_meta' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $old_books = (array) get_post_meta( $post_id, 'bc_books', true );
    // Store book IDs as strings to allow reliable meta queries regardless of numeric type.
    $books     = isset( $_POST['bc_books'] ) ? array_map( 'strval', (array) $_POST['bc_books'] ) : array();
    update_post_meta( $post_id, 'bc_books', $books );

    $all_books = array_unique( array_merge( $old_books, $books ) );
    foreach ( $all_books as $book_id ) {
        bookcreator_sync_chapter_menu( $book_id );
    }
}
add_action( 'save_post_bc_chapter', 'bookcreator_save_chapter_meta' );

function bookcreator_save_paragraph_meta( $post_id ) {
    if ( ! isset( $_POST['bookcreator_paragraph_meta_nonce'] ) || ! wp_verify_nonce( $_POST['bookcreator_paragraph_meta_nonce'], 'bookcreator_save_paragraph_meta' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $old_chapters = (array) get_post_meta( $post_id, 'bc_chapters', true );
    $chapters     = isset( $_POST['bc_chapters'] ) ? array_map( 'strval', (array) $_POST['bc_chapters'] ) : array();
    update_post_meta( $post_id, 'bc_chapters', $chapters );

    $footnotes = isset( $_POST['bc_footnotes'] ) ? wp_kses_post( wp_unslash( $_POST['bc_footnotes'] ) ) : '';
    update_post_meta( $post_id, 'bc_footnotes', $footnotes );

    $citations = isset( $_POST['bc_citations'] ) ? wp_kses_post( wp_unslash( $_POST['bc_citations'] ) ) : '';
    update_post_meta( $post_id, 'bc_citations', $citations );

    $books = array();
    foreach ( $chapters as $chapter_id ) {
        $chapter_books = (array) get_post_meta( $chapter_id, 'bc_books', true );
        foreach ( $chapter_books as $book_id ) {
            $books[] = (string) $book_id;
        }
    }
    $books = array_unique( $books );
    update_post_meta( $post_id, 'bc_books', $books );

    $all_chapters = array_unique( array_merge( $old_chapters, $chapters ) );
    foreach ( $all_chapters as $chapter_id ) {
        bookcreator_sync_paragraph_menu( $chapter_id );
    }
}
add_action( 'save_post_bc_paragraph', 'bookcreator_save_paragraph_meta' );

/**
 * Customize columns in the books list.
 */
function bookcreator_set_custom_columns( $columns ) {
    $columns = array(
        'cb'                  => $columns['cb'],
        'title'               => $columns['title'],
        'taxonomy-book_genre' => __( 'Book Genre', 'bookcreator' ),
        'bc_language'         => __( 'Language', 'bookcreator' ),
        'bc_cover'            => __( 'Cover', 'bookcreator' ),
        'date'                => $columns['date'],
    );

    return $columns;
}
add_filter( 'manage_book_creator_posts_columns', 'bookcreator_set_custom_columns' );

/**
 * Render custom column content.
 */
function bookcreator_render_custom_columns( $column, $post_id ) {
    if ( 'bc_language' === $column ) {
        $code  = get_post_meta( $post_id, 'bc_language', true );
        $label = bookcreator_get_language_label( $code );
        echo $label ? esc_html( $label ) : '—';
    }

    if ( 'bc_cover' === $column ) {
        $cover_id = get_post_meta( $post_id, 'bc_cover', true );
        if ( $cover_id ) {
            echo wp_get_attachment_image( $cover_id, array( 50, 50 ) );
        } else {
            echo '—';
        }
    }

}
add_action( 'manage_book_creator_posts_custom_column', 'bookcreator_render_custom_columns', 10, 2 );

/**
 * Customize columns in the chapters list.
 */
function bookcreator_set_chapter_columns( $columns ) {
    $columns = array(
        'cb'       => $columns['cb'],
        'title'    => $columns['title'],
        'bc_books' => __( 'Books', 'bookcreator' ),
        'date'     => $columns['date'],
    );

    return $columns;
}
add_filter( 'manage_bc_chapter_posts_columns', 'bookcreator_set_chapter_columns' );

/**
 * Render custom column content for chapters.
 */
function bookcreator_render_chapter_columns( $column, $post_id ) {
    if ( 'bc_books' === $column ) {
        $books = (array) get_post_meta( $post_id, 'bc_books', true );
        if ( $books ) {
            $titles = array();
            foreach ( $books as $book_id ) {
                $titles[] = esc_html( get_the_title( $book_id ) );
            }
            echo implode( ', ', $titles );
        } else {
            echo '—';
        }
    }
}
add_action( 'manage_bc_chapter_posts_custom_column', 'bookcreator_render_chapter_columns', 10, 2 );

/**
 * Customize columns in the paragraphs list.
 */
function bookcreator_set_paragraph_columns( $columns ) {
    $columns = array(
        'cb'          => $columns['cb'],
        'title'       => $columns['title'],
        'bc_chapters' => __( 'Chapters', 'bookcreator' ),
        'date'        => $columns['date'],
    );

    return $columns;
}
add_filter( 'manage_bc_paragraph_posts_columns', 'bookcreator_set_paragraph_columns' );

/**
 * Render custom column content for paragraphs.
 */
function bookcreator_render_paragraph_columns( $column, $post_id ) {
    if ( 'bc_chapters' === $column ) {
        $chapters = (array) get_post_meta( $post_id, 'bc_chapters', true );
        if ( $chapters ) {
            $titles = array();
            foreach ( $chapters as $chapter_id ) {
                $chapter_title = esc_html( get_the_title( $chapter_id ) );
                $books        = (array) get_post_meta( $chapter_id, 'bc_books', true );
                if ( $books ) {
                    $book_titles = array();
                    foreach ( $books as $book_id ) {
                        $book_titles[] = esc_html( get_the_title( $book_id ) );
                    }
                    $titles[] = sprintf( '%s (%s)', $chapter_title, implode( ', ', $book_titles ) );
                } else {
                    $titles[] = $chapter_title;
                }
            }
            echo implode( ', ', $titles );
        } else {
            echo '—';
        }
    }
}
add_action( 'manage_bc_paragraph_posts_custom_column', 'bookcreator_render_paragraph_columns', 10, 2 );

function bookcreator_form_enctype() {
    global $post;
    if ( $post && 'book_creator' === $post->post_type ) {
        echo ' enctype="multipart/form-data"';
    }
}
add_action( 'post_edit_form_tag', 'bookcreator_form_enctype' );

function bookcreator_get_chapter_menu_id( $book_id ) {
    $slug = 'chapters-book-' . $book_id;
    $menu = wp_get_nav_menu_object( $slug );
    if ( ! $menu ) {
        return wp_create_nav_menu( $slug );
    }
    return $menu->term_id;
}

function bookcreator_sync_chapter_menu( $book_id ) {
    require_once ABSPATH . 'wp-admin/includes/nav-menu.php';
    $menu_id  = bookcreator_get_chapter_menu_id( $book_id );
    $items    = wp_get_nav_menu_items( $menu_id );
    $existing = array();

    if ( $items ) {
        foreach ( $items as $item ) {
            if ( 'bc_chapter' === $item->object ) {
                $existing[ $item->object_id ] = $item->ID;
            }
        }
    }

    $chapters = get_posts( array(
        'post_type'   => 'bc_chapter',
        'numberposts' => -1,
        'post_status' => 'any',
        'meta_query'  => array(
            array(
                'key'     => 'bc_books',
                'value'   => '"' . $book_id . '"',
                'compare' => 'LIKE',
            ),
        ),
    ) );

    foreach ( $chapters as $chapter ) {
        if ( isset( $existing[ $chapter->ID ] ) ) {
            unset( $existing[ $chapter->ID ] );
            continue;
        }
        wp_update_nav_menu_item( $menu_id, 0, array(
            'menu-item-title'     => $chapter->post_title,
            'menu-item-object-id' => $chapter->ID,
            'menu-item-object'    => 'bc_chapter',
            'menu-item-type'      => 'post_type',
            'menu-item-status'    => 'publish',
        ) );
    }

    foreach ( $existing as $item_id ) {
        wp_delete_post( $item_id, true );
    }

    return $menu_id;
}

function bookcreator_get_paragraph_menu_id( $chapter_id ) {
    $slug = 'paragraphs-chapter-' . $chapter_id;
    $menu = wp_get_nav_menu_object( $slug );
    if ( ! $menu ) {
        return wp_create_nav_menu( $slug );
    }
    return $menu->term_id;
}

function bookcreator_sync_paragraph_menu( $chapter_id ) {
    require_once ABSPATH . 'wp-admin/includes/nav-menu.php';
    $menu_id  = bookcreator_get_paragraph_menu_id( $chapter_id );
    $items    = wp_get_nav_menu_items( $menu_id );
    $existing = array();

    if ( $items ) {
        foreach ( $items as $item ) {
            if ( 'bc_paragraph' === $item->object ) {
                $existing[ $item->object_id ] = $item->ID;
            }
        }
    }

    $paragraphs = get_posts( array(
        'post_type'   => 'bc_paragraph',
        'numberposts' => -1,
        'post_status' => 'any',
        'meta_query'  => array(
            array(
                'key'     => 'bc_chapters',
                'value'   => '"' . $chapter_id . '"',
                'compare' => 'LIKE',
            ),
        ),
    ) );

    foreach ( $paragraphs as $paragraph ) {
        if ( isset( $existing[ $paragraph->ID ] ) ) {
            unset( $existing[ $paragraph->ID ] );
            continue;
        }
        wp_update_nav_menu_item( $menu_id, 0, array(
            'menu-item-title'     => $paragraph->post_title,
            'menu-item-object-id' => $paragraph->ID,
            'menu-item-object'    => 'bc_paragraph',
            'menu-item-type'      => 'post_type',
            'menu-item-status'    => 'publish',
        ) );
    }

    foreach ( $existing as $item_id ) {
        wp_delete_post( $item_id, true );
    }

    return $menu_id;
}

/**
 * Retrieve ordered chapter objects for a book.
 *
 * @param int $book_id Book post ID.
 * @return WP_Post[]
 */
function bookcreator_get_ordered_chapters_for_book( $book_id ) {
    $chapters = array();
    $slug     = 'chapters-book-' . $book_id;
    $menu     = wp_get_nav_menu_object( $slug );

    if ( $menu ) {
        $items = wp_get_nav_menu_items( $menu->term_id );
        if ( $items ) {
            usort(
                $items,
                static function ( $a, $b ) {
                    return (int) $a->menu_order <=> (int) $b->menu_order;
                }
            );

            foreach ( $items as $item ) {
                if ( 'bc_chapter' !== $item->object ) {
                    continue;
                }

                $chapter = get_post( $item->object_id );
                if ( ! $chapter || 'trash' === $chapter->post_status ) {
                    continue;
                }

                $chapters[ $chapter->ID ] = $chapter;
            }
        }
    }

    if ( ! $chapters ) {
        $chapters = array();
        $query    = get_posts(
            array(
                'post_type'   => 'bc_chapter',
                'numberposts' => -1,
                'post_status' => array( 'publish', 'private' ),
                'meta_query'  => array(
                    array(
                        'key'     => 'bc_books',
                        'value'   => '"' . $book_id . '"',
                        'compare' => 'LIKE',
                    ),
                ),
                'orderby'     => 'menu_order title',
                'order'       => 'ASC',
            )
        );

        foreach ( $query as $chapter ) {
            $chapters[ $chapter->ID ] = $chapter;
        }
    }

    return array_values( $chapters );
}

/**
 * Retrieve ordered paragraphs for a chapter.
 *
 * @param int $chapter_id Chapter post ID.
 * @return WP_Post[]
 */
function bookcreator_get_ordered_paragraphs_for_chapter( $chapter_id ) {
    $paragraphs = array();
    $slug       = 'paragraphs-chapter-' . $chapter_id;
    $menu       = wp_get_nav_menu_object( $slug );

    if ( $menu ) {
        $items = wp_get_nav_menu_items( $menu->term_id );
        if ( $items ) {
            usort(
                $items,
                static function ( $a, $b ) {
                    return (int) $a->menu_order <=> (int) $b->menu_order;
                }
            );

            foreach ( $items as $item ) {
                if ( 'bc_paragraph' !== $item->object ) {
                    continue;
                }

                $paragraph = get_post( $item->object_id );
                if ( ! $paragraph || 'trash' === $paragraph->post_status ) {
                    continue;
                }

                $paragraphs[ $paragraph->ID ] = $paragraph;
            }
        }
    }

    if ( ! $paragraphs ) {
        $paragraphs = array();
        $query      = get_posts(
            array(
                'post_type'   => 'bc_paragraph',
                'numberposts' => -1,
                'post_status' => array( 'publish', 'private' ),
                'meta_query'  => array(
                    array(
                        'key'     => 'bc_chapters',
                        'value'   => '"' . $chapter_id . '"',
                        'compare' => 'LIKE',
                    ),
                ),
                'orderby'     => 'menu_order title',
                'order'       => 'ASC',
            )
        );

        foreach ( $query as $paragraph ) {
            $paragraphs[ $paragraph->ID ] = $paragraph;
        }
    }

    return array_values( $paragraphs );
}

/**
 * Use the bundled single template for books.
 *
 * @param string $template Current template path.
 * @return string
 */
function bookcreator_single_template( $template ) {
    if ( is_singular( 'book_creator' ) ) {
        $plugin_template = plugin_dir_path( __FILE__ ) . 'templates/single-book_creator.php';
        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }
    }

    return $template;
}
add_filter( 'single_template', 'bookcreator_single_template' );

function bookcreator_order_chapters_page() {
    echo '<div class="wrap"><h1>' . esc_html__( 'Ordina capitoli', 'bookcreator' ) . '</h1>';
    $book_id = isset( $_GET['book_id'] ) ? absint( $_GET['book_id'] ) : 0;

    echo '<form method="get"><input type="hidden" name="page" value="bc-order-chapters" /><input type="hidden" name="post_type" value="book_creator" />';
    echo '<select name="book_id"><option value="">' . esc_html__( 'Seleziona libro', 'bookcreator' ) . '</option>';
    $books = get_posts( array(
        'post_type'   => 'book_creator',
        'numberposts' => -1,
        'post_status' => 'any',
    ) );
    foreach ( $books as $book ) {
        printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $book->ID ), selected( $book_id, $book->ID, false ), esc_html( $book->post_title ) );
    }
    echo '</select>';
    submit_button( __( 'Seleziona', 'bookcreator' ), 'secondary', '', false );
    echo '</form>';

    if ( $book_id ) {
        require_once ABSPATH . 'wp-admin/includes/nav-menu.php';
        $menu_id = bookcreator_sync_chapter_menu( $book_id );

        if ( isset( $_POST['save_menu_order'] ) && check_admin_referer( 'bc_save_chapter_order' ) ) {
            if ( isset( $_POST['menu-item-db-id'] ) ) {
                $ordered_items = array_keys( $_POST['menu-item-db-id'] );

                foreach ( $ordered_items as $position => $item_id ) {
                    $args = array(
                        'menu-item-object-id' => absint( $_POST['menu-item-object-id'][ $item_id ] ),
                        'menu-item-object'    => sanitize_key( $_POST['menu-item-object'][ $item_id ] ),
                        'menu-item-parent-id' => absint( $_POST['menu-item-parent-id'][ $item_id ] ),
                        'menu-item-position'  => $position + 1,
                        'menu-item-type'      => sanitize_key( $_POST['menu-item-type'][ $item_id ] ),
                        'menu-item-title'     => sanitize_text_field( $_POST['menu-item-title'][ $item_id ] ),
                    );
                    wp_update_nav_menu_item( $menu_id, $item_id, $args );
                }
            }
        }

        echo '<form id="update-nav-menu" method="post">';
        wp_nonce_field( 'bc_save_chapter_order' );
        echo '<input type="hidden" name="menu" id="menu" value="' . esc_attr( $menu_id ) . '" />';
        echo '<input type="hidden" name="menu-name" id="menu-name" value="chapters-book-' . esc_attr( $book_id ) . '" />';
        wp_nav_menu( array(
            'menu'        => $menu_id,
            'walker'      => new Walker_Nav_Menu_Edit,
            'container'   => false,
            'items_wrap'  => '<ul id="menu-to-edit" class="menu">%3$s</ul>',
            'fallback_cb' => false,
        ) );
        submit_button( __( 'Salva ordine', 'bookcreator' ), 'primary menu-save', 'save_menu_order' );
        echo '</form>';
    }
    echo '</div>';
}

function bookcreator_register_order_chapters_page() {
    add_submenu_page( 'edit.php?post_type=book_creator', __( 'Ordina capitoli', 'bookcreator' ), __( 'Ordina capitoli', 'bookcreator' ), 'manage_options', 'bc-order-chapters', 'bookcreator_order_chapters_page' );
}
add_action( 'admin_menu', 'bookcreator_register_order_chapters_page' );

function bookcreator_order_chapters_enqueue( $hook ) {
    if ( 'book_creator_page_bc-order-chapters' === $hook ) {
        wp_enqueue_script( 'nav-menu' );
    }
}
add_action( 'admin_enqueue_scripts', 'bookcreator_order_chapters_enqueue' );

function bookcreator_order_paragraphs_page() {
    echo '<div class="wrap"><h1>' . esc_html__( 'Ordina paragrafi', 'bookcreator' ) . '</h1>';
    $book_id    = isset( $_GET['book_id'] ) ? absint( $_GET['book_id'] ) : 0;
    $chapter_id = isset( $_GET['chapter_id'] ) ? absint( $_GET['chapter_id'] ) : 0;

    echo '<form method="get"><input type="hidden" name="page" value="bc-order-paragraphs" /><input type="hidden" name="post_type" value="book_creator" />';
    echo '<select name="book_id" onchange="if ( this.form.chapter_id ) { this.form.chapter_id.selectedIndex = 0; } this.form.submit();"><option value="">' . esc_html__( 'Seleziona libro', 'bookcreator' ) . '</option>';
    $books = get_posts( array(
        'post_type'   => 'book_creator',
        'numberposts' => -1,
        'post_status' => 'any',
    ) );
    foreach ( $books as $book ) {
        printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $book->ID ), selected( $book_id, $book->ID, false ), esc_html( $book->post_title ) );
    }
    echo '</select>';

    if ( $book_id ) {
        echo '<select name="chapter_id"><option value="">' . esc_html__( 'Seleziona capitolo', 'bookcreator' ) . '</option>';
        $chapters = get_posts( array(
            'post_type'   => 'bc_chapter',
            'numberposts' => -1,
            'post_status' => 'any',
            'meta_query'  => array(
                array(
                    'key'     => 'bc_books',
                    'value'   => '"' . $book_id . '"',
                    'compare' => 'LIKE',
                ),
            ),
        ) );
        foreach ( $chapters as $chapter ) {
            printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $chapter->ID ), selected( $chapter_id, $chapter->ID, false ), esc_html( $chapter->post_title ) );
        }
        echo '</select>';
    }

    submit_button( __( 'Seleziona', 'bookcreator' ), 'secondary', '', false );
    echo '</form>';

    if ( $chapter_id ) {
        require_once ABSPATH . 'wp-admin/includes/nav-menu.php';
        $menu_id = bookcreator_sync_paragraph_menu( $chapter_id );

        if ( isset( $_POST['save_menu_order'] ) && check_admin_referer( 'bc_save_paragraph_order' ) ) {
            if ( isset( $_POST['menu-item-db-id'] ) ) {
                $ordered_items = array_keys( $_POST['menu-item-db-id'] );

                foreach ( $ordered_items as $position => $item_id ) {
                    $args = array(
                        'menu-item-object-id' => absint( $_POST['menu-item-object-id'][ $item_id ] ),
                        'menu-item-object'    => sanitize_key( $_POST['menu-item-object'][ $item_id ] ),
                        'menu-item-parent-id' => absint( $_POST['menu-item-parent-id'][ $item_id ] ),
                        'menu-item-position'  => $position + 1,
                        'menu-item-type'      => sanitize_key( $_POST['menu-item-type'][ $item_id ] ),
                        'menu-item-title'     => sanitize_text_field( $_POST['menu-item-title'][ $item_id ] ),
                    );
                    wp_update_nav_menu_item( $menu_id, $item_id, $args );
                }
            }
        }

        echo '<form id="update-nav-menu" method="post">';
        wp_nonce_field( 'bc_save_paragraph_order' );
        echo '<input type="hidden" name="menu" id="menu" value="' . esc_attr( $menu_id ) . '" />';
        echo '<input type="hidden" name="menu-name" id="menu-name" value="paragraphs-chapter-' . esc_attr( $chapter_id ) . '" />';
        wp_nav_menu( array(
            'menu'        => $menu_id,
            'walker'      => new Walker_Nav_Menu_Edit,
            'container'   => false,
            'items_wrap'  => '<ul id="menu-to-edit" class="menu">%3$s</ul>',
            'fallback_cb' => false,
        ) );
        submit_button( __( 'Salva ordine', 'bookcreator' ), 'primary menu-save', 'save_menu_order' );
        echo '</form>';
    }
    echo '</div>';
}

function bookcreator_register_order_paragraphs_page() {
    // Expose paragraph ordering directly in the main Books menu.
    add_submenu_page( 'edit.php?post_type=book_creator', __( 'Ordina paragrafi', 'bookcreator' ), __( 'Ordina paragrafi', 'bookcreator' ), 'manage_options', 'bc-order-paragraphs', 'bookcreator_order_paragraphs_page' );
}
add_action( 'admin_menu', 'bookcreator_register_order_paragraphs_page' );

function bookcreator_order_paragraphs_enqueue( $hook ) {
    if ( 'book_creator_page_bc-order-paragraphs' === $hook ) {
        wp_enqueue_script( 'nav-menu' );
    }
}
add_action( 'admin_enqueue_scripts', 'bookcreator_order_paragraphs_enqueue' );

function bookcreator_get_epub_library_instruction_text() {
    return __( 'Per generare gli ePub è necessario che l\'estensione ZipArchive di PHP sia attiva.', 'bookcreator' );
}

function bookcreator_get_epub_library_notice_markup() {
    return '<strong>' . esc_html__( 'Generazione ePub non disponibile', 'bookcreator' ) . '</strong><br />' . esc_html( bookcreator_get_epub_library_instruction_text() );
}

function bookcreator_load_epub_library() {
    static $loaded = null;

    if ( null !== $loaded ) {
        return $loaded;
    }

    global $bookcreator_epub_library_error;
    $bookcreator_epub_library_error = '';

    if ( ! class_exists( 'ZipArchive' ) ) {
        $bookcreator_epub_library_error = bookcreator_get_epub_library_instruction_text();
        $loaded                           = false;

        return $loaded;
    }

    $loaded = true;

    return $loaded;
}

function bookcreator_get_epub_library_error_message() {
    global $bookcreator_epub_library_error;

    if ( $bookcreator_epub_library_error ) {
        return $bookcreator_epub_library_error;
    }

    return bookcreator_get_epub_library_instruction_text();
}

function bookcreator_is_epub_library_available() {
    return bookcreator_load_epub_library();
}

function bookcreator_get_epub_font_family_options() {
    return array(
        'times'           => array(
            'label' => __( 'Times New Roman / Liberation Serif / Times', 'bookcreator' ),
            'css'   => "\"Times New Roman\", \"Liberation Serif\", \"Times\", serif",
        ),
        'georgia'         => array(
            'label' => __( 'Georgia / Times New Roman', 'bookcreator' ),
            'css'   => "\"Georgia\", \"Times New Roman\", serif",
        ),
        'palatino'        => array(
            'label' => __( 'Palatino Linotype / Book Antiqua', 'bookcreator' ),
            'css'   => "\"Palatino Linotype\", \"Book Antiqua\", serif",
        ),
        'arial'           => array(
            'label' => __( 'Arial / Helvetica Neue / Liberation Sans', 'bookcreator' ),
            'css'   => "\"Arial\", \"Helvetica Neue\", \"Liberation Sans\", sans-serif",
        ),
        'verdana'         => array(
            'label' => __( 'Verdana / Geneva', 'bookcreator' ),
            'css'   => "\"Verdana\", \"Geneva\", sans-serif",
        ),
        'trebuchet'       => array(
            'label' => __( 'Trebuchet MS / Lucida Grande', 'bookcreator' ),
            'css'   => "\"Trebuchet MS\", \"Lucida Grande\", sans-serif",
        ),
    );
}

function bookcreator_expand_css_box_values( $value ) {
    $value = trim( (string) $value );
    if ( '' === $value ) {
        return array( '', '', '', '' );
    }

    $parts = preg_split( '/\s+/', $value );
    $parts = array_values( array_filter( $parts, 'strlen' ) );

    $count = count( $parts );

    if ( 0 === $count ) {
        return array( '', '', '', '' );
    }

    if ( 1 === $count ) {
        return array( $parts[0], $parts[0], $parts[0], $parts[0] );
    }

    if ( 2 === $count ) {
        return array( $parts[0], $parts[1], $parts[0], $parts[1] );
    }

    if ( 3 === $count ) {
        return array( $parts[0], $parts[1], $parts[2], $parts[1] );
    }

    return array( $parts[0], $parts[1], $parts[2], $parts[3] );
}

function bookcreator_build_css_box_values( $top, $right, $bottom, $left ) {
    $values = array( $top, $right, $bottom, $left );
    $values = array_map( 'trim', $values );

    return implode( ' ', $values );
}

function bookcreator_sanitize_numeric_value( $value ) {
    if ( null === $value || '' === $value ) {
        return '';
    }

    if ( is_array( $value ) ) {
        return '';
    }

    $value = trim( (string) $value );

    if ( '' === $value ) {
        return '';
    }

    $value = str_replace( ',', '.', $value );

    if ( ! is_numeric( $value ) ) {
        if ( ! preg_match( '/^-?(?:\d+\.?\d*|\.\d+)/', $value, $matches ) ) {
            return '';
        }

        $value = $matches[0];
    }

    $float_value = (float) $value;

    if ( 0.0 === $float_value ) {
        return '0';
    }

    return rtrim( rtrim( sprintf( '%.6F', $float_value ), '0' ), '.' );
}

function bookcreator_format_css_numeric_value( $value, $unit = '' ) {
    $value = bookcreator_sanitize_numeric_value( $value );

    if ( '' === $value ) {
        return '';
    }

    if ( 0.0 === (float) $value ) {
        return '0';
    }

    return $unit ? $value . $unit : $value;
}

function bookcreator_format_css_box_numeric_values( $values, $unit = '' ) {
    if ( ! is_array( $values ) ) {
        $values = array();
    }

    $formatted = array();

    foreach ( $values as $value ) {
        $formatted_value = bookcreator_format_css_numeric_value( $value, $unit );
        $formatted[]     = '' === $formatted_value ? '0' : $formatted_value;
    }

    $has_values = false;

    foreach ( $formatted as $formatted_value ) {
        if ( '' !== $formatted_value ) {
            $has_values = true;
            break;
        }
    }

    if ( ! $has_values ) {
        return '';
    }

    return implode( ' ', $formatted );
}

function bookcreator_get_epub_style_base_defaults() {
    return array(
        'font_size'       => '1',
        'line_height'     => '1.4',
        'font_family'     => 'georgia',
        'font_style'      => 'normal',
        'font_weight'     => '400',
        'hyphenation'     => 'auto',
        'color'           => '#333333',
        'background_color' => '',
        'text_align'      => 'left',
        'margin_top'      => '0',
        'margin_right'    => '0',
        'margin_bottom'   => '0',
        'margin_left'     => '0',
        'padding_top'     => '0',
        'padding_right'   => '0',
        'padding_bottom'  => '0',
        'padding_left'    => '0',
        'margin'          => '0 0 0 0',
        'padding'         => '0 0 0 0',
    );
}

function bookcreator_get_epub_style_defaults( $field_key ) {
    $defaults = bookcreator_get_epub_style_base_defaults();

    switch ( $field_key ) {
        case 'book_title':
            $defaults['font_size']     = '2.4';
            $defaults['line_height']   = '1.2';
            $defaults['font_weight']   = '700';
            $defaults['text_align']    = 'center';
            $defaults['margin_bottom'] = '0.2';
            break;
        case 'book_subtitle':
            $defaults['font_size']     = '1.6';
            $defaults['line_height']   = '1.3';
            $defaults['font_style']    = 'italic';
            $defaults['text_align']    = 'center';
            $defaults['margin_top']    = '0.4';
            $defaults['margin_bottom'] = '0.4';
            break;
        case 'book_author':
            $defaults['font_size']  = '1.3';
            $defaults['text_align'] = 'center';
            break;
        case 'book_coauthors':
            $defaults['font_size']  = '1.1';
            $defaults['text_align'] = 'center';
            break;
        case 'book_publisher':
            $defaults['text_align']    = 'center';
            $defaults['margin_top']    = '0.6';
            $defaults['margin_bottom'] = '0.2';
            break;
        case 'book_frontispiece':
            $defaults['text_align']    = 'center';
            $defaults['margin_bottom'] = '1';
            break;
        case 'book_description':
        case 'book_frontispiece_extra':
        case 'book_preface':
        case 'book_author_note':
        case 'chapter_content':
        case 'paragraph_content':
            $defaults['line_height'] = '1.6';
            $defaults['text_align']  = 'justify';
            break;
        case 'book_copyright':
        case 'book_dedication':
        case 'book_appendix':
        case 'book_bibliography':
            $defaults['line_height'] = '1.6';
            break;
        case 'book_index':
            $defaults['margin_top']    = '1';
            $defaults['margin_bottom'] = '1';
            break;
        case 'chapter_titles':
            $defaults['font_size']     = '1.8';
            $defaults['font_weight']   = '700';
            $defaults['margin_top']    = '1.2';
            $defaults['margin_bottom'] = '0.6';
            break;
        case 'paragraph_titles':
            $defaults['font_size']     = '1.4';
            $defaults['font_weight']   = '600';
            $defaults['margin_top']    = '0.8';
            $defaults['margin_bottom'] = '0.4';
            break;
        case 'paragraph_footnotes':
        case 'paragraph_citations':
            $defaults['font_size']     = '0.9';
            $defaults['line_height']   = '1.4';
            $defaults['margin_top']    = '1';
            $defaults['padding_top']   = '0.5';
            break;
    }

    $defaults['margin']  = bookcreator_build_css_box_values(
        $defaults['margin_top'],
        $defaults['margin_right'],
        $defaults['margin_bottom'],
        $defaults['margin_left']
    );
    $defaults['padding'] = bookcreator_build_css_box_values(
        $defaults['padding_top'],
        $defaults['padding_right'],
        $defaults['padding_bottom'],
        $defaults['padding_left']
    );

    return $defaults;
}

function bookcreator_get_epub_book_title_style_defaults() {
    return bookcreator_get_epub_style_defaults( 'book_title' );
}

function bookcreator_get_epub_style_fields() {
    return array(
        'book_title' => array(
            'label'     => __( 'Titolo del libro', 'bookcreator' ),
            'selectors' => array( '.bookcreator-frontispiece__title' ),
            'stylable'  => true,
            'description' => __( 'Definisci lo stile del titolo frontespizio nel file ePub generato.', 'bookcreator' ),
        ),
        'book_subtitle' => array(
            'label'     => __( 'Sottotitolo del libro', 'bookcreator' ),
            'selectors' => array( '.bookcreator-frontispiece__subtitle' ),
            'stylable'  => true,
        ),
        'book_author' => array(
            'label'     => __( 'Autore principale', 'bookcreator' ),
            'selectors' => array( '.bookcreator-frontispiece__field-bc_author' ),
            'stylable'  => true,
        ),
        'book_coauthors' => array(
            'label'     => __( 'Coautori', 'bookcreator' ),
            'selectors' => array( '.bookcreator-frontispiece__field-bc_coauthors' ),
            'stylable'  => true,
        ),
        'book_publisher' => array(
            'label'     => __( 'Editore', 'bookcreator' ),
            'selectors' => array( '.bookcreator-frontispiece__field-bc_publisher' ),
            'stylable'  => true,
        ),
        'book_language' => array(
            'label'     => __( 'Lingua', 'bookcreator' ),
            'selectors' => array( '.bookcreator-frontispiece__field-bc_language' ),
        ),
        'book_frontispiece' => array(
            'label'     => __( 'Frontespizio', 'bookcreator' ),
            'selectors' => array( '.bookcreator-frontispiece' ),
            'stylable'  => true,
        ),
        'book_description' => array(
            'label'     => __( 'Descrizione', 'bookcreator' ),
            'selectors' => array( '.bookcreator-frontispiece__description' ),
            'stylable'  => true,
        ),
        'book_frontispiece_extra' => array(
            'label'     => __( 'Contenuti extra frontespizio', 'bookcreator' ),
            'selectors' => array( '.bookcreator-frontispiece__extra' ),
            'stylable'  => true,
        ),
        'book_cover' => array(
            'label'     => __( 'Copertina', 'bookcreator' ),
            'selectors' => array( '.bookcreator-cover' ),
        ),
        'book_index' => array(
            'label'     => __( 'Indice', 'bookcreator' ),
            'selectors' => array( '.bookcreator-book__index', '#toc' ),
            'stylable'  => true,
        ),
        'book_copyright' => array(
            'label'     => __( 'Sezione Copyright', 'bookcreator' ),
            'selectors' => array( '.bookcreator-copyright' ),
            'stylable'  => true,
        ),
        'book_dedication' => array(
            'label'     => __( 'Sezione Dedica', 'bookcreator' ),
            'selectors' => array( '.bookcreator-dedication' ),
            'stylable'  => true,
        ),
        'book_preface' => array(
            'label'     => __( 'Sezione Prefazione', 'bookcreator' ),
            'selectors' => array( '.bookcreator-preface' ),
            'stylable'  => true,
        ),
        'book_appendix' => array(
            'label'     => __( 'Sezione Appendice', 'bookcreator' ),
            'selectors' => array( '.bookcreator-section-bc_appendix' ),
            'stylable'  => true,
        ),
        'book_bibliography' => array(
            'label'     => __( 'Sezione Bibliografia', 'bookcreator' ),
            'selectors' => array( '.bookcreator-section-bc_bibliography' ),
            'stylable'  => true,
        ),
        'book_author_note' => array(
            'label'     => __( 'Sezione Nota dell\'autore', 'bookcreator' ),
            'selectors' => array( '.bookcreator-section-bc_author_note' ),
            'stylable'  => true,
        ),
        'chapter_sections' => array(
            'label'     => __( 'Capitoli', 'bookcreator' ),
            'selectors' => array( '.bookcreator-chapter' ),
        ),
        'chapter_titles' => array(
            'label'     => __( 'Titoli dei capitoli', 'bookcreator' ),
            'selectors' => array( '.bookcreator-chapter__title' ),
            'stylable'  => true,
        ),
        'chapter_content' => array(
            'label'     => __( 'Contenuto dei capitoli', 'bookcreator' ),
            'selectors' => array( '.bookcreator-chapter__content' ),
            'stylable'  => true,
        ),
        'paragraph_sections' => array(
            'label'     => __( 'Paragrafi', 'bookcreator' ),
            'selectors' => array( '.bookcreator-paragraph' ),
        ),
        'paragraph_titles' => array(
            'label'     => __( 'Titoli dei paragrafi', 'bookcreator' ),
            'selectors' => array( '.bookcreator-paragraph__title' ),
            'stylable'  => true,
        ),
        'paragraph_content' => array(
            'label'     => __( 'Contenuto dei paragrafi', 'bookcreator' ),
            'selectors' => array( '.bookcreator-paragraph__content' ),
            'stylable'  => true,
        ),
        'paragraph_footnotes' => array(
            'label'     => __( 'Note a piè di pagina', 'bookcreator' ),
            'selectors' => array( '.bookcreator-footnotes' ),
            'stylable'  => true,
        ),
        'paragraph_citations' => array(
            'label'     => __( 'Citazioni', 'bookcreator' ),
            'selectors' => array( '.bookcreator-citations' ),
            'stylable'  => true,
        ),
    );
}

function bookcreator_get_epub_stylable_fields() {
    $stylable = array();

    foreach ( bookcreator_get_epub_style_fields() as $field_key => $field ) {
        if ( ! empty( $field['stylable'] ) ) {
            $stylable[ $field_key ] = $field;
        }
    }

    return $stylable;
}

function bookcreator_get_epub_style_settings_config() {
    $config = array();

    foreach ( bookcreator_get_epub_stylable_fields() as $field_key => $field ) {
        $config[ $field_key . '_styles' ] = array(
            'default' => bookcreator_get_epub_style_defaults( $field_key ),
        );
    }

    return $config;
}

function bookcreator_normalize_epub_style_values( $value, $defaults, $settings, $setting_key ) {
    $value = is_array( $value ) ? $value : array();
    $value = wp_parse_args( $value, $defaults );

    $value['font_family'] = sanitize_key( $value['font_family'] );

    $font_families = bookcreator_get_epub_font_family_options();
    if ( ! isset( $font_families[ $value['font_family'] ] ) ) {
        $value['font_family'] = $defaults['font_family'];
    }

    $allowed_styles = array( 'normal', 'italic', 'oblique' );
    if ( ! in_array( $value['font_style'], $allowed_styles, true ) ) {
        $value['font_style'] = $defaults['font_style'];
    }

    $allowed_weights = array( 'normal', 'bold', '100', '200', '300', '400', '500', '600', '700', '800', '900' );
    if ( ! in_array( $value['font_weight'], $allowed_weights, true ) ) {
        $value['font_weight'] = $defaults['font_weight'];
    }

    $allowed_hyphenations = array( 'auto', 'manual', 'none' );
    $value['hyphenation']  = sanitize_key( $value['hyphenation'] );
    if ( ! in_array( $value['hyphenation'], $allowed_hyphenations, true ) ) {
        if ( 'book_title_styles' === $setting_key ) {
            $legacy_hyphenation = isset( $settings['hyphenation'] ) ? sanitize_key( $settings['hyphenation'] ) : '';
            if ( in_array( $legacy_hyphenation, $allowed_hyphenations, true ) ) {
                $value['hyphenation'] = $legacy_hyphenation;
            } else {
                $value['hyphenation'] = $defaults['hyphenation'];
            }
        } else {
            $value['hyphenation'] = $defaults['hyphenation'];
        }
    }

    $allowed_alignments = array( 'left', 'center', 'right', 'justify' );
    $value['text_align'] = sanitize_text_field( $value['text_align'] );
    if ( ! in_array( $value['text_align'], $allowed_alignments, true ) ) {
        $value['text_align'] = $defaults['text_align'];
    }

    $value['font_size']   = bookcreator_sanitize_numeric_value( $value['font_size'] );
    $value['line_height'] = bookcreator_sanitize_numeric_value( $value['line_height'] );

    $box_fields = array(
        'margin'  => array( 'margin_top', 'margin_right', 'margin_bottom', 'margin_left' ),
        'padding' => array( 'padding_top', 'padding_right', 'padding_bottom', 'padding_left' ),
    );

    foreach ( $box_fields as $legacy_key => $box_keys ) {
        $has_new_values = false;

        foreach ( $box_keys as $box_key ) {
            if ( '' !== $value[ $box_key ] ) {
                $has_new_values = true;
                break;
            }
        }

        if ( ! $has_new_values && ! empty( $value[ $legacy_key ] ) ) {
            $expanded = bookcreator_expand_css_box_values( $value[ $legacy_key ] );
            foreach ( $box_keys as $index => $box_key ) {
                $value[ $box_key ] = isset( $expanded[ $index ] ) ? $expanded[ $index ] : '';
            }
        }

        $sanitized_values = array();
        foreach ( $box_keys as $box_key ) {
            $value[ $box_key ]  = bookcreator_sanitize_numeric_value( $value[ $box_key ] );
            $sanitized_values[] = $value[ $box_key ];
        }

        $value[ $legacy_key ] = bookcreator_build_css_box_values(
            $sanitized_values[0],
            $sanitized_values[1],
            $sanitized_values[2],
            $sanitized_values[3]
        );
    }

    $color = sanitize_hex_color( $value['color'] );
    if ( ! $color ) {
        $color = $defaults['color'];
    }
    $value['color'] = $color;

    $background_color = sanitize_hex_color( $value['background_color'] );
    if ( ! $background_color ) {
        $background_color = $defaults['background_color'];
    }
    $value['background_color'] = $background_color;

    return $value;
}

function bookcreator_get_posted_epub_style_values( $field_key ) {
    $prefix = 'bookcreator_template_epub_' . $field_key . '_';

    $font_size   = isset( $_POST[ $prefix . 'font_size' ] ) ? bookcreator_sanitize_numeric_value( wp_unslash( $_POST[ $prefix . 'font_size' ] ) ) : '';
    $line_height = isset( $_POST[ $prefix . 'line_height' ] ) ? bookcreator_sanitize_numeric_value( wp_unslash( $_POST[ $prefix . 'line_height' ] ) ) : '';
    $font_family = isset( $_POST[ $prefix . 'font_family' ] ) ? sanitize_key( wp_unslash( $_POST[ $prefix . 'font_family' ] ) ) : '';
    $font_style  = isset( $_POST[ $prefix . 'font_style' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $prefix . 'font_style' ] ) ) : '';
    $font_weight = isset( $_POST[ $prefix . 'font_weight' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $prefix . 'font_weight' ] ) ) : '';
    $hyphenation = isset( $_POST[ $prefix . 'hyphenation' ] ) ? sanitize_key( wp_unslash( $_POST[ $prefix . 'hyphenation' ] ) ) : '';
    $text_align  = isset( $_POST[ $prefix . 'text_align' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $prefix . 'text_align' ] ) ) : '';
    $color       = isset( $_POST[ $prefix . 'color' ] ) ? sanitize_hex_color( wp_unslash( $_POST[ $prefix . 'color' ] ) ) : '';

    if ( 'book_title' === $field_key && ! $color && isset( $_POST['bookcreator_template_title_color'] ) ) {
        $color = sanitize_hex_color( wp_unslash( $_POST['bookcreator_template_title_color'] ) );
    }

    $background_color = isset( $_POST[ $prefix . 'background_color' ] ) ? sanitize_hex_color( wp_unslash( $_POST[ $prefix . 'background_color' ] ) ) : '';

    $margin = array(
        'top'    => isset( $_POST[ $prefix . 'margin_top' ] ) ? bookcreator_sanitize_numeric_value( wp_unslash( $_POST[ $prefix . 'margin_top' ] ) ) : '',
        'right'  => isset( $_POST[ $prefix . 'margin_right' ] ) ? bookcreator_sanitize_numeric_value( wp_unslash( $_POST[ $prefix . 'margin_right' ] ) ) : '',
        'bottom' => isset( $_POST[ $prefix . 'margin_bottom' ] ) ? bookcreator_sanitize_numeric_value( wp_unslash( $_POST[ $prefix . 'margin_bottom' ] ) ) : '',
        'left'   => isset( $_POST[ $prefix . 'margin_left' ] ) ? bookcreator_sanitize_numeric_value( wp_unslash( $_POST[ $prefix . 'margin_left' ] ) ) : '',
    );

    $padding = array(
        'top'    => isset( $_POST[ $prefix . 'padding_top' ] ) ? bookcreator_sanitize_numeric_value( wp_unslash( $_POST[ $prefix . 'padding_top' ] ) ) : '',
        'right'  => isset( $_POST[ $prefix . 'padding_right' ] ) ? bookcreator_sanitize_numeric_value( wp_unslash( $_POST[ $prefix . 'padding_right' ] ) ) : '',
        'bottom' => isset( $_POST[ $prefix . 'padding_bottom' ] ) ? bookcreator_sanitize_numeric_value( wp_unslash( $_POST[ $prefix . 'padding_bottom' ] ) ) : '',
        'left'   => isset( $_POST[ $prefix . 'padding_left' ] ) ? bookcreator_sanitize_numeric_value( wp_unslash( $_POST[ $prefix . 'padding_left' ] ) ) : '',
    );

    return array(
        'font_size'       => $font_size,
        'line_height'     => $line_height,
        'font_family'     => $font_family,
        'font_style'      => $font_style,
        'font_weight'     => $font_weight,
        'hyphenation'     => $hyphenation,
        'color'           => $color,
        'background_color' => $background_color,
        'text_align'      => $text_align,
        'margin_top'      => $margin['top'],
        'margin_right'    => $margin['right'],
        'margin_bottom'   => $margin['bottom'],
        'margin_left'     => $margin['left'],
        'padding_top'     => $padding['top'],
        'padding_right'   => $padding['right'],
        'padding_bottom'  => $padding['bottom'],
        'padding_left'    => $padding['left'],
        'margin'          => bookcreator_build_css_box_values( $margin['top'], $margin['right'], $margin['bottom'], $margin['left'] ),
        'padding'         => bookcreator_build_css_box_values( $padding['top'], $padding['right'], $padding['bottom'], $padding['left'] ),
    );
}

function bookcreator_get_epub_default_visible_fields() {
    $defaults = array();

    foreach ( bookcreator_get_epub_style_fields() as $field_key => $field ) {
        $defaults[ $field_key ] = true;
    }

    return $defaults;
}

function bookcreator_get_template_types_config() {
    return array(
        'epub' => array(
            'label'    => __( 'Template ePub', 'bookcreator' ),
            'settings' => array_merge(
                array(
                    'title_color' => array(
                        'default' => '#333333',
                    ),
                ),
                bookcreator_get_epub_style_settings_config(),
                array(
                    'visible_fields' => array(
                        'default' => bookcreator_get_epub_default_visible_fields(),
                    ),
                )
            ),
        ),
        'pdf'  => array(
            'label'    => __( 'Template PDF', 'bookcreator' ),
            'settings' => array(
                'page_format' => array(
                    'default' => 'A4',
                    'choices' => array( 'A4', 'A5', 'Letter' ),
                ),
                'margin_top'    => array(
                    'default' => 20,
                ),
                'margin_right'  => array(
                    'default' => 15,
                ),
                'margin_bottom' => array(
                    'default' => 20,
                ),
                'margin_left'   => array(
                    'default' => 15,
                ),
                'font_size'     => array(
                    'default' => 12,
                ),
            ),
        ),
    );
}

function bookcreator_get_template_type_label( $type ) {
    $config = bookcreator_get_template_types_config();

    return isset( $config[ $type ] ) ? $config[ $type ]['label'] : $type;
}

function bookcreator_get_default_template_settings( $type = 'epub' ) {
    $config = bookcreator_get_template_types_config();
    if ( ! isset( $config[ $type ] ) ) {
        $type = 'epub';
    }

    $defaults = array();
    foreach ( $config[ $type ]['settings'] as $setting_key => $setting_args ) {
        $defaults[ $setting_key ] = $setting_args['default'];
    }

    return $defaults;
}

function bookcreator_normalize_template_settings( $settings, $type = 'epub' ) {
    $config = bookcreator_get_template_types_config();
    if ( ! isset( $config[ $type ] ) ) {
        $type = 'epub';
    }

    $settings = wp_parse_args( (array) $settings, bookcreator_get_default_template_settings( $type ) );

    foreach ( $config[ $type ]['settings'] as $key => $args ) {
        $value = isset( $settings[ $key ] ) ? $settings[ $key ] : $args['default'];

        if ( 'epub' === $type && '_styles' === substr( $key, -7 ) ) {
            $field_key = substr( $key, 0, -7 );
            $defaults  = bookcreator_get_epub_style_defaults( $field_key );
            $value     = bookcreator_normalize_epub_style_values( $value, $defaults, $settings, $key );
            $settings[ $key ] = $value;
            continue;
        }

        switch ( $key ) {
            case 'title_color':
                $value = sanitize_hex_color( $value );
                if ( ! $value ) {
                    $value = $args['default'];
                }
                break;
            case 'visible_fields':
                if ( 'epub' === $type ) {
                    $value          = is_array( $value ) ? $value : array();
                    $normalized_set = array();
                    foreach ( bookcreator_get_epub_style_fields() as $field_key => $field ) {
                        $normalized_set[ $field_key ] = ! empty( $value[ $field_key ] );
                    }
                    $value = $normalized_set;
                }
                break;
            case 'page_format':
                $value = sanitize_text_field( $value );
                if ( empty( $args['choices'] ) || ! in_array( $value, $args['choices'], true ) ) {
                    $value = $args['default'];
                }
                break;
            case 'font_size':
                $value = absint( $value );
                if ( $value <= 0 ) {
                    $value = $args['default'];
                }
                break;
            case 'margin_top':
            case 'margin_right':
            case 'margin_bottom':
            case 'margin_left':
                $value = is_numeric( $value ) ? (float) $value : $args['default'];
                if ( $value < 0 ) {
                    $value = $args['default'];
                }
                break;
            default:
                $value = sanitize_text_field( $value );
                break;
        }

        $settings[ $key ] = $value;
    }

    if ( isset( $settings['hyphenation'] ) ) {
        unset( $settings['hyphenation'] );
    }

    if ( 'epub' === $type && isset( $settings['book_title_styles']['color'] ) ) {
        $settings['title_color'] = $settings['book_title_styles']['color'];
    }

    return $settings;
}

function bookcreator_get_templates() {
    $templates = get_option( 'bookcreator_templates', array() );
    if ( ! is_array( $templates ) ) {
        return array();
    }

    $config = bookcreator_get_template_types_config();

    foreach ( $templates as $id => $template ) {
        if ( empty( $template['id'] ) && is_string( $id ) ) {
            $templates[ $id ]['id'] = $id;
        }

        if ( empty( $templates[ $id ]['type'] ) || ! isset( $config[ $templates[ $id ]['type'] ] ) ) {
            $templates[ $id ]['type'] = 'epub';
        }

        $templates[ $id ]['settings'] = bookcreator_normalize_template_settings(
            isset( $template['settings'] ) ? $template['settings'] : array(),
            $templates[ $id ]['type']
        );
    }

    return $templates;
}

function bookcreator_get_template( $template_id ) {
    $templates = bookcreator_get_templates();

    if ( isset( $templates[ $template_id ] ) ) {
        return $templates[ $template_id ];
    }

    return null;
}

function bookcreator_get_templates_by_type( $type ) {
    $templates = bookcreator_get_templates();

    $filtered = array();
    foreach ( $templates as $template ) {
        $template_type = isset( $template['type'] ) ? $template['type'] : 'epub';
        if ( $template_type === $type ) {
            $filtered[] = $template;
        }
    }

    return $filtered;
}

function bookcreator_handle_template_actions() {
    if ( empty( $_POST['bookcreator_template_action'] ) ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    check_admin_referer( 'bookcreator_manage_template', 'bookcreator_template_nonce' );

    $action    = sanitize_key( wp_unslash( $_POST['bookcreator_template_action'] ) );
    $templates = bookcreator_get_templates();
    $config    = bookcreator_get_template_types_config();

    $requested_type = isset( $_POST['bookcreator_template_type'] ) ? sanitize_key( wp_unslash( $_POST['bookcreator_template_type'] ) ) : '';
    if ( ! isset( $config[ $requested_type ] ) ) {
        $requested_type = 'epub';
    }

    $redirect  = add_query_arg(
        array(
            'post_type' => 'book_creator',
            'page'      => ( 'pdf' === $requested_type ) ? 'bc-templates-pdf' : 'bc-templates-epub',
        ),
        admin_url( 'edit.php' )
    );

    $status  = 'success';
    $message = '';

    if ( 'save' === $action ) {
        $template_id = isset( $_POST['bookcreator_template_id'] ) ? sanitize_text_field( wp_unslash( $_POST['bookcreator_template_id'] ) ) : '';
        $name        = isset( $_POST['bookcreator_template_name'] ) ? sanitize_text_field( wp_unslash( $_POST['bookcreator_template_name'] ) ) : '';
        $type        = $requested_type;

        if ( '' === $name ) {
            $status  = 'error';
            $message = __( 'Il nome del template è obbligatorio.', 'bookcreator' );
        } elseif ( ! $type ) {
            if ( $template_id && isset( $templates[ $template_id ] ) ) {
                $type = $templates[ $template_id ]['type'];
            } else {
                $status  = 'error';
                $message = __( 'Seleziona una tipologia di template valida.', 'bookcreator' );
            }
        }

        if ( 'success' === $status && ! isset( $config[ $type ] ) ) {
            $status  = 'error';
            $message = __( 'Tipologia di template non valida.', 'bookcreator' );
        }

        if ( 'success' === $status && $template_id && isset( $templates[ $template_id ] ) ) {
            $original_type = isset( $templates[ $template_id ]['type'] ) ? $templates[ $template_id ]['type'] : 'epub';
            if ( $original_type !== $type ) {
                $status  = 'error';
                $message = __( 'Non è possibile modificare la tipologia del template.', 'bookcreator' );
            }
        }

        if ( 'success' === $status ) {
            $settings = array();

            if ( 'epub' === $type ) {
                $stylable_fields = array_keys( bookcreator_get_epub_stylable_fields() );
                foreach ( $stylable_fields as $field_key ) {
                    $style_values = bookcreator_get_posted_epub_style_values( $field_key );
                    $settings[ $field_key . '_styles' ] = $style_values;

                    if ( 'book_title' === $field_key ) {
                        $settings['title_color'] = $style_values['color'];
                    }
                }

                $visible_fields_input = isset( $_POST['bookcreator_template_epub_visible_fields'] ) ? (array) wp_unslash( $_POST['bookcreator_template_epub_visible_fields'] ) : array();
                $settings['visible_fields'] = array();

                foreach ( bookcreator_get_epub_style_fields() as $field_key => $field ) {
                    $settings['visible_fields'][ $field_key ] = array_key_exists( $field_key, $visible_fields_input );
                }
            } elseif ( 'pdf' === $type ) {
                $settings['page_format']  = isset( $_POST['bookcreator_template_pdf_page_format'] ) ? sanitize_text_field( wp_unslash( $_POST['bookcreator_template_pdf_page_format'] ) ) : '';
                $settings['margin_top']   = isset( $_POST['bookcreator_template_pdf_margin_top'] ) ? wp_unslash( $_POST['bookcreator_template_pdf_margin_top'] ) : '';
                $settings['margin_right'] = isset( $_POST['bookcreator_template_pdf_margin_right'] ) ? wp_unslash( $_POST['bookcreator_template_pdf_margin_right'] ) : '';
                $settings['margin_bottom'] = isset( $_POST['bookcreator_template_pdf_margin_bottom'] ) ? wp_unslash( $_POST['bookcreator_template_pdf_margin_bottom'] ) : '';
                $settings['margin_left']   = isset( $_POST['bookcreator_template_pdf_margin_left'] ) ? wp_unslash( $_POST['bookcreator_template_pdf_margin_left'] ) : '';
                $settings['font_size']     = isset( $_POST['bookcreator_template_pdf_font_size'] ) ? wp_unslash( $_POST['bookcreator_template_pdf_font_size'] ) : '';
            }

            if ( ! $template_id || ! isset( $templates[ $template_id ] ) ) {
                $template_id = wp_generate_uuid4();
            }

            $templates[ $template_id ] = array(
                'id'       => $template_id,
                'name'     => $name,
                'type'     => $type,
                'settings' => bookcreator_normalize_template_settings( $settings, $type ),
            );

            update_option( 'bookcreator_templates', $templates );

            $status  = 'success';
            $message = __( 'Template salvato correttamente.', 'bookcreator' );
        }
    } elseif ( 'delete' === $action ) {
        $template_id = isset( $_POST['bookcreator_template_id'] ) ? sanitize_text_field( wp_unslash( $_POST['bookcreator_template_id'] ) ) : '';
        if ( $template_id && isset( $templates[ $template_id ] ) && $requested_type === ( isset( $templates[ $template_id ]['type'] ) ? $templates[ $template_id ]['type'] : 'epub' ) ) {
            unset( $templates[ $template_id ] );
            update_option( 'bookcreator_templates', $templates );
            $status  = 'success';
            $message = __( 'Template eliminato.', 'bookcreator' );
        } else {
            $status  = 'error';
            $message = __( 'Template non trovato.', 'bookcreator' );
        }
    }

    if ( $message ) {
        $redirect = add_query_arg(
            array(
                'bc_template_status'  => $status,
                'bc_template_message' => rawurlencode( $message ),
            ),
            $redirect
        );
    }

    wp_safe_redirect( $redirect );
    exit;
}
add_action( 'admin_init', 'bookcreator_handle_template_actions' );

function bookcreator_render_templates_page( $current_type ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $config = bookcreator_get_template_types_config();
    if ( ! isset( $config[ $current_type ] ) ) {
        $current_type = 'epub';
    }

    $action        = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'list';
    $page_slug     = ( 'pdf' === $current_type ) ? 'bc-templates-pdf' : 'bc-templates-epub';
    $default_url   = add_query_arg(
        array(
            'post_type' => 'book_creator',
            'page'      => $page_slug,
        ),
        admin_url( 'edit.php' )
    );
    $templates     = bookcreator_get_templates_by_type( $current_type );
    $type_config   = $config[ $current_type ];
    $type_label    = $type_config['label'];

    echo '<div class="wrap">';
    echo '<h1>' . esc_html( $type_label ) . '</h1>';

    if ( isset( $_GET['bc_template_status'], $_GET['bc_template_message'] ) ) {
        $status  = sanitize_key( wp_unslash( $_GET['bc_template_status'] ) );
        $message = sanitize_text_field( rawurldecode( wp_unslash( $_GET['bc_template_message'] ) ) );
        $class   = ( 'error' === $status ) ? 'notice notice-error is-dismissible' : 'notice notice-success is-dismissible';
        echo '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( $message ) . '</p></div>';
    }

    if ( 'add' === $action || 'edit' === $action ) {
        $is_edit     = ( 'edit' === $action );
        $template    = null;
        $template_id = '';

        if ( $is_edit ) {
            $template_id = isset( $_GET['template'] ) ? sanitize_text_field( wp_unslash( $_GET['template'] ) ) : '';
            $template    = $template_id ? bookcreator_get_template( $template_id ) : null;
            if ( ! $template || ( isset( $template['type'] ) ? $template['type'] : 'epub' ) !== $current_type ) {
                echo '<div class="notice notice-error"><p>' . esc_html__( 'Template non trovato.', 'bookcreator' ) . '</p></div>';
                echo '<p><a href="' . esc_url( $default_url ) . '">' . esc_html__( 'Torna all\'elenco dei template', 'bookcreator' ) . '</a></p>';
                echo '</div>';

                return;
            }
        }

        $name    = $template ? $template['name'] : '';
        $values  = $template ? bookcreator_normalize_template_settings( $template['settings'], $current_type ) : bookcreator_get_default_template_settings( $current_type );

        echo '<form method="post" class="bc-template-form">';
        wp_nonce_field( 'bookcreator_manage_template', 'bookcreator_template_nonce' );
        echo '<input type="hidden" name="bookcreator_template_action" value="save" />';
        echo '<input type="hidden" name="bookcreator_template_type" value="' . esc_attr( $current_type ) . '" />';
        if ( $is_edit ) {
            echo '<input type="hidden" name="bookcreator_template_id" value="' . esc_attr( $template_id ) . '" />';
        }

        echo '<table class="form-table"><tbody>';
        echo '<tr>';
        echo '<th scope="row"><label for="bookcreator_template_name">' . esc_html__( 'Nome', 'bookcreator' ) . '</label></th>';
        echo '<td><input name="bookcreator_template_name" id="bookcreator_template_name" type="text" class="regular-text" value="' . esc_attr( $name ) . '" required /></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th scope="row">' . esc_html__( 'Tipologia', 'bookcreator' ) . '</th>';
        echo '<td>' . esc_html( $type_label ) . '</td>';
        echo '</tr>';
        echo '</tbody></table>';

        echo '<div class="bookcreator-template-settings" data-template-type="' . esc_attr( $current_type ) . '">';
        echo '<h2>' . esc_html__( 'Impostazioni', 'bookcreator' ) . '</h2>';
        echo '<table class="form-table"><tbody>';

        if ( 'epub' === $current_type ) {
            $stylable_fields      = bookcreator_get_epub_stylable_fields();
            $font_families        = bookcreator_get_epub_font_family_options();
            $hyphenation_choices  = array( 'auto', 'manual', 'none' );
            $hyphenation_labels   = array(
                'auto'   => __( 'Automatica', 'bookcreator' ),
                'manual' => __( 'Manuale', 'bookcreator' ),
                'none'   => __( 'Nessuna', 'bookcreator' ),
            );
            $font_style_options   = array(
                'normal'  => __( 'Normale', 'bookcreator' ),
                'italic'  => __( 'Corsivo', 'bookcreator' ),
                'oblique' => __( 'Obliquo', 'bookcreator' ),
            );
            $font_weight_options  = array(
                'normal' => __( 'Normale', 'bookcreator' ),
                'bold'   => __( 'Grassetto', 'bookcreator' ),
                '300'    => '300',
                '400'    => '400',
                '500'    => '500',
                '600'    => '600',
                '700'    => '700',
                '800'    => '800',
                '900'    => '900',
            );
            $alignment_options    = array(
                'left'    => __( 'Sinistra', 'bookcreator' ),
                'center'  => __( 'Centro', 'bookcreator' ),
                'right'   => __( 'Destra', 'bookcreator' ),
                'justify' => __( 'Giustificato', 'bookcreator' ),
            );
            $margin_fields        = array(
                'top'    => __( 'Superiore', 'bookcreator' ),
                'right'  => __( 'Destra', 'bookcreator' ),
                'bottom' => __( 'Inferiore', 'bookcreator' ),
                'left'   => __( 'Sinistra', 'bookcreator' ),
            );
            $padding_fields       = array(
                'top'    => __( 'Superiore', 'bookcreator' ),
                'right'  => __( 'Destra', 'bookcreator' ),
                'bottom' => __( 'Inferiore', 'bookcreator' ),
                'left'   => __( 'Sinistra', 'bookcreator' ),
            );

            $total_fields = count( $stylable_fields );
            $index        = 0;

            foreach ( $stylable_fields as $field_key => $field ) {
                $index++;
                $setting_key = $field_key . '_styles';
                $defaults    = bookcreator_get_epub_style_defaults( $field_key );
                $stored      = isset( $values[ $setting_key ] ) ? (array) $values[ $setting_key ] : array();

                if ( 'book_title' === $field_key && empty( $stored['color'] ) && ! empty( $values['title_color'] ) ) {
                    $stored['color'] = $values['title_color'];
                }

                $styles = bookcreator_normalize_epub_style_values( $stored, $defaults, $values, $setting_key );

                if ( ! isset( $font_families[ $styles['font_family'] ] ) ) {
                    $styles['font_family'] = $defaults['font_family'];
                }

                $label = isset( $field['label'] ) ? $field['label'] : ucfirst( str_replace( '_', ' ', $field_key ) );
                $description = isset( $field['description'] ) && $field['description'] ? $field['description'] : sprintf( __( 'Definisci lo stile di %s nel file ePub generato.', 'bookcreator' ), $label );

                echo '<tr>';
                echo '<th scope="row">' . esc_html( $label ) . '</th>';
                echo '<td>';
                echo '<p class="description">' . esc_html( $description ) . '</p>';

                $hyphenation_value = isset( $styles['hyphenation'] ) && in_array( $styles['hyphenation'], $hyphenation_choices, true ) ? $styles['hyphenation'] : $defaults['hyphenation'];

                echo '<div class="bookcreator-style-grid bookcreator-style-grid--two-columns">';

                echo '<div class="bookcreator-style-grid__column">';

                $font_size_id = 'bookcreator_template_epub_' . $field_key . '_font_size';
                echo '<div class="bookcreator-style-grid__item">';
                echo '<label for="' . esc_attr( $font_size_id ) . '">' . esc_html__( 'Dimensione font (rem)', 'bookcreator' ) . '</label>';
                echo '<input type="number" step="0.1" min="0" id="' . esc_attr( $font_size_id ) . '" name="' . esc_attr( $font_size_id ) . '" value="' . esc_attr( $styles['font_size'] ) . '" placeholder="' . esc_attr__( 'es. 1.2', 'bookcreator' ) . '" inputmode="decimal" />';
                echo '</div>';

                $line_height_id = 'bookcreator_template_epub_' . $field_key . '_line_height';
                echo '<div class="bookcreator-style-grid__item">';
                echo '<label for="' . esc_attr( $line_height_id ) . '">' . esc_html__( 'Altezza riga (valore)', 'bookcreator' ) . '</label>';
                echo '<input type="number" step="0.1" min="0" id="' . esc_attr( $line_height_id ) . '" name="' . esc_attr( $line_height_id ) . '" value="' . esc_attr( $styles['line_height'] ) . '" placeholder="' . esc_attr__( 'es. 1.4', 'bookcreator' ) . '" inputmode="decimal" />';
                echo '</div>';

                $font_family_id = 'bookcreator_template_epub_' . $field_key . '_font_family';
                echo '<div class="bookcreator-style-grid__item">';
                echo '<label for="' . esc_attr( $font_family_id ) . '">' . esc_html__( 'Famiglia font', 'bookcreator' ) . '</label>';
                echo '<select id="' . esc_attr( $font_family_id ) . '" name="' . esc_attr( $font_family_id ) . '">';
                foreach ( $font_families as $family_key => $family ) {
                    $selected = selected( $styles['font_family'], $family_key, false );
                    echo '<option value="' . esc_attr( $family_key ) . '"' . $selected . '>' . esc_html( $family['label'] ) . '</option>';
                }
                echo '</select>';
                echo '</div>';

                $font_style_id = 'bookcreator_template_epub_' . $field_key . '_font_style';
                echo '<div class="bookcreator-style-grid__item">';
                echo '<label for="' . esc_attr( $font_style_id ) . '">' . esc_html__( 'Stile font', 'bookcreator' ) . '</label>';
                echo '<select id="' . esc_attr( $font_style_id ) . '" name="' . esc_attr( $font_style_id ) . '">';
                foreach ( $font_style_options as $style_key => $style_label ) {
                    $selected = selected( $styles['font_style'], $style_key, false );
                    echo '<option value="' . esc_attr( $style_key ) . '"' . $selected . '>' . esc_html( $style_label ) . '</option>';
                }
                echo '</select>';
                echo '</div>';

                $font_weight_id = 'bookcreator_template_epub_' . $field_key . '_font_weight';
                echo '<div class="bookcreator-style-grid__item">';
                echo '<label for="' . esc_attr( $font_weight_id ) . '">' . esc_html__( 'Peso font', 'bookcreator' ) . '</label>';
                echo '<select id="' . esc_attr( $font_weight_id ) . '" name="' . esc_attr( $font_weight_id ) . '">';
                foreach ( $font_weight_options as $weight_key => $weight_label ) {
                    $selected = selected( $styles['font_weight'], $weight_key, false );
                    echo '<option value="' . esc_attr( $weight_key ) . '"' . $selected . '>' . esc_html( $weight_label ) . '</option>';
                }
                echo '</select>';
                echo '</div>';

                $hyphenation_id = 'bookcreator_template_epub_' . $field_key . '_hyphenation';
                echo '<div class="bookcreator-style-grid__item">';
                echo '<label for="' . esc_attr( $hyphenation_id ) . '">' . esc_html__( 'Sillabazione', 'bookcreator' ) . '</label>';
                echo '<select id="' . esc_attr( $hyphenation_id ) . '" name="' . esc_attr( $hyphenation_id ) . '">';
                foreach ( $hyphenation_choices as $hyphenation_choice ) {
                    $label_value = isset( $hyphenation_labels[ $hyphenation_choice ] ) ? $hyphenation_labels[ $hyphenation_choice ] : $hyphenation_choice;
                    $selected    = selected( $hyphenation_value, $hyphenation_choice, false );
                    echo '<option value="' . esc_attr( $hyphenation_choice ) . '"' . $selected . '>' . esc_html( $label_value ) . '</option>';
                }
                echo '</select>';
                echo '</div>';

                echo '</div>';

                echo '<div class="bookcreator-style-grid__column">';

                $text_align_id = 'bookcreator_template_epub_' . $field_key . '_text_align';
                echo '<div class="bookcreator-style-grid__item">';
                echo '<label for="' . esc_attr( $text_align_id ) . '">' . esc_html__( 'Allineamento', 'bookcreator' ) . '</label>';
                echo '<select id="' . esc_attr( $text_align_id ) . '" name="' . esc_attr( $text_align_id ) . '">';
                foreach ( $alignment_options as $align_key => $align_label ) {
                    $selected = selected( $styles['text_align'], $align_key, false );
                    echo '<option value="' . esc_attr( $align_key ) . '"' . $selected . '>' . esc_html( $align_label ) . '</option>';
                }
                echo '</select>';
                echo '</div>';

                $color_id = 'bookcreator_template_epub_' . $field_key . '_color';
                echo '<div class="bookcreator-style-grid__item">';
                echo '<label for="' . esc_attr( $color_id ) . '">' . esc_html__( 'Colore', 'bookcreator' ) . '</label>';
                echo '<input type="text" id="' . esc_attr( $color_id ) . '" name="' . esc_attr( $color_id ) . '" class="bookcreator-color-field" value="' . esc_attr( $styles['color'] ) . '" data-default-color="' . esc_attr( $defaults['color'] ) . '" />';
                echo '</div>';

                $background_id = 'bookcreator_template_epub_' . $field_key . '_background_color';
                echo '<div class="bookcreator-style-grid__item">';
                echo '<label for="' . esc_attr( $background_id ) . '">' . esc_html__( 'Colore sfondo', 'bookcreator' ) . '</label>';
                echo '<input type="text" id="' . esc_attr( $background_id ) . '" name="' . esc_attr( $background_id ) . '" class="bookcreator-color-field" value="' . esc_attr( $styles['background_color'] ) . '" data-default-color="' . esc_attr( $defaults['background_color'] ) . '" />';
                echo '</div>';

                echo '<div class="bookcreator-style-grid__item">';
                echo '<span class="bookcreator-style-grid__group-title">' . esc_html__( 'Margine (em)', 'bookcreator' ) . '</span>';
                echo '<div class="bookcreator-style-split">';
                foreach ( $margin_fields as $direction => $direction_label ) {
                    $field_suffix = 'margin_' . $direction;
                    $input_id     = 'bookcreator_template_epub_' . $field_key . '_' . $field_suffix;
                    echo '<div class="bookcreator-style-split__field">';
                    echo '<label for="' . esc_attr( $input_id ) . '">' . esc_html( sprintf( '%s (em)', $direction_label ) ) . '</label>';
                    echo '<input type="number" step="0.1" id="' . esc_attr( $input_id ) . '" name="' . esc_attr( $input_id ) . '" value="' . esc_attr( $styles[ $field_suffix ] ) . '" placeholder="' . esc_attr__( 'es. 0.2', 'bookcreator' ) . '" inputmode="decimal" />';
                    echo '</div>';
                }
                echo '</div>';
                echo '</div>';

                echo '<div class="bookcreator-style-grid__item">';
                echo '<span class="bookcreator-style-grid__group-title">' . esc_html__( 'Padding (em)', 'bookcreator' ) . '</span>';
                echo '<div class="bookcreator-style-split">';
                foreach ( $padding_fields as $direction => $direction_label ) {
                    $field_suffix = 'padding_' . $direction;
                    $input_id     = 'bookcreator_template_epub_' . $field_key . '_' . $field_suffix;
                    echo '<div class="bookcreator-style-split__field">';
                    echo '<label for="' . esc_attr( $input_id ) . '">' . esc_html( sprintf( '%s (em)', $direction_label ) ) . '</label>';
                    echo '<input type="number" step="0.1" id="' . esc_attr( $input_id ) . '" name="' . esc_attr( $input_id ) . '" value="' . esc_attr( $styles[ $field_suffix ] ) . '" placeholder="' . esc_attr__( 'es. 0.2', 'bookcreator' ) . '" inputmode="decimal" />';
                    echo '</div>';
                }
                echo '</div>';
                echo '</div>';

                echo '</div>';

                echo '</div>';

                if ( $index < $total_fields ) {
                    echo '<hr class="bookcreator-style-separator" />';
                }

                echo '</td>';
                echo '</tr>';
            }

            $visible_fields = isset( $values['visible_fields'] ) ? (array) $values['visible_fields'] : array();
            $style_fields   = bookcreator_get_epub_style_fields();

            echo '<tr>';
            echo '<th scope="row">' . esc_html__( 'Elementi del libro', 'bookcreator' ) . '</th>';
            echo '<td>';
            foreach ( $style_fields as $field_key => $field ) {
                $checked = ! empty( $visible_fields[ $field_key ] );
                echo '<label style="display:block;margin-bottom:4px;">';
                echo '<input type="checkbox" name="bookcreator_template_epub_visible_fields[' . esc_attr( $field_key ) . ']" value="1"' . checked( $checked, true, false ) . ' /> ';
                echo esc_html( $field['label'] );
                echo '</label>';
            }
            echo '<p class="description">' . esc_html__( 'Deseleziona un elemento per nasconderlo nell\'ePub generato.', 'bookcreator' ) . '</p>';
            echo '</td>';
            echo '</tr>';
        } else {
            $page_format   = $values['page_format'];
            $margin_top    = $values['margin_top'];
            $margin_right  = $values['margin_right'];
            $margin_bottom = $values['margin_bottom'];
            $margin_left   = $values['margin_left'];
            $font_size     = $values['font_size'];

            echo '<tr>';
            echo '<th scope="row"><label for="bookcreator_template_pdf_page_format">' . esc_html__( 'Formato pagina', 'bookcreator' ) . '</label></th>';
            echo '<td><select name="bookcreator_template_pdf_page_format" id="bookcreator_template_pdf_page_format">';
            foreach ( $type_config['settings']['page_format']['choices'] as $choice ) {
                $selected = selected( $page_format, $choice, false );
                echo '<option value="' . esc_attr( $choice ) . '"' . $selected . '>' . esc_html( $choice ) . '</option>';
            }
            echo '</select></td>';
            echo '</tr>';

            echo '<tr>';
            echo '<th scope="row"><label for="bookcreator_template_pdf_margin_top">' . esc_html__( 'Margine superiore (mm)', 'bookcreator' ) . '</label></th>';
            echo '<td><input name="bookcreator_template_pdf_margin_top" id="bookcreator_template_pdf_margin_top" type="number" class="small-text" step="0.1" min="0" value="' . esc_attr( $margin_top ) . '" /></td>';
            echo '</tr>';

            echo '<tr>';
            echo '<th scope="row"><label for="bookcreator_template_pdf_margin_right">' . esc_html__( 'Margine destro (mm)', 'bookcreator' ) . '</label></th>';
            echo '<td><input name="bookcreator_template_pdf_margin_right" id="bookcreator_template_pdf_margin_right" type="number" class="small-text" step="0.1" min="0" value="' . esc_attr( $margin_right ) . '" /></td>';
            echo '</tr>';

            echo '<tr>';
            echo '<th scope="row"><label for="bookcreator_template_pdf_margin_bottom">' . esc_html__( 'Margine inferiore (mm)', 'bookcreator' ) . '</label></th>';
            echo '<td><input name="bookcreator_template_pdf_margin_bottom" id="bookcreator_template_pdf_margin_bottom" type="number" class="small-text" step="0.1" min="0" value="' . esc_attr( $margin_bottom ) . '" /></td>';
            echo '</tr>';

            echo '<tr>';
            echo '<th scope="row"><label for="bookcreator_template_pdf_margin_left">' . esc_html__( 'Margine sinistro (mm)', 'bookcreator' ) . '</label></th>';
            echo '<td><input name="bookcreator_template_pdf_margin_left" id="bookcreator_template_pdf_margin_left" type="number" class="small-text" step="0.1" min="0" value="' . esc_attr( $margin_left ) . '" /></td>';
            echo '</tr>';

            echo '<tr>';
            echo '<th scope="row"><label for="bookcreator_template_pdf_font_size">' . esc_html__( 'Dimensione font (pt)', 'bookcreator' ) . '</label></th>';
            echo '<td><input name="bookcreator_template_pdf_font_size" id="bookcreator_template_pdf_font_size" type="number" class="small-text" step="1" min="6" value="' . esc_attr( $font_size ) . '" /></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';

        submit_button( $is_edit ? __( 'Aggiorna template', 'bookcreator' ) : __( 'Crea template', 'bookcreator' ) );
        echo ' <a href="' . esc_url( $default_url ) . '" class="button-secondary">' . esc_html__( 'Annulla', 'bookcreator' ) . '</a>';
        echo '</form>';
        echo '</div>';

        return;
    }

    echo '<a href="' . esc_url( add_query_arg( array( 'action' => 'add' ), $default_url ) ) . '" class="page-title-action">' . esc_html__( 'Aggiungi nuovo', 'bookcreator' ) . '</a>';

    if ( empty( $templates ) ) {
        echo '<p>' . esc_html__( 'Nessun template disponibile. Crea il primo template per iniziare.', 'bookcreator' ) . '</p>';
        echo '</div>';

        return;
    }

    echo '<table class="widefat striped">';
    echo '<thead><tr>';
    echo '<th scope="col">' . esc_html__( 'Nome', 'bookcreator' ) . '</th>';
    echo '<th scope="col">' . esc_html__( 'Impostazioni', 'bookcreator' ) . '</th>';
    echo '<th scope="col" class="column-actions">' . esc_html__( 'Azioni', 'bookcreator' ) . '</th>';
    echo '</tr></thead>';
    echo '<tbody>';

    foreach ( $templates as $template ) {
        $settings = bookcreator_normalize_template_settings( isset( $template['settings'] ) ? $template['settings'] : array(), $current_type );
        $edit_url = add_query_arg(
            array(
                'action'   => 'edit',
                'template' => $template['id'],
            ),
            $default_url
        );

        echo '<tr>';
        echo '<td>' . esc_html( $template['name'] ) . '</td>';
        echo '<td>';

        if ( 'pdf' === $current_type ) {
            $top    = number_format_i18n( $settings['margin_top'], 1 );
            $right  = number_format_i18n( $settings['margin_right'], 1 );
            $bottom = number_format_i18n( $settings['margin_bottom'], 1 );
            $left   = number_format_i18n( $settings['margin_left'], 1 );

            echo '<strong>' . esc_html__( 'Formato pagina', 'bookcreator' ) . ':</strong> ' . esc_html( $settings['page_format'] ) . '<br />';
            echo '<strong>' . esc_html__( 'Margini (T/D/B/S)', 'bookcreator' ) . ':</strong> ' . esc_html( $top . ' / ' . $right . ' / ' . $bottom . ' / ' . $left . ' mm' ) . '<br />';
            echo '<strong>' . esc_html__( 'Dimensione font', 'bookcreator' ) . ':</strong> ' . esc_html( $settings['font_size'] ) . ' pt';
        } else {
            $title_color    = $settings['title_color'];
            $visible_fields = isset( $settings['visible_fields'] ) ? (array) $settings['visible_fields'] : array();
            $style_fields   = bookcreator_get_epub_style_fields();
            $hidden_labels  = array();

            foreach ( $style_fields as $field_key => $field ) {
                $is_visible = isset( $visible_fields[ $field_key ] ) ? (bool) $visible_fields[ $field_key ] : true;
                if ( ! $is_visible ) {
                    $hidden_labels[] = $field['label'];
                }
            }

            echo '<span class="bookcreator-color-sample" style="background-color: ' . esc_attr( $title_color ) . ';"></span>' . esc_html( $title_color );
            echo '<br /><strong>' . esc_html__( 'Elementi nascosti', 'bookcreator' ) . ':</strong> ';
            echo $hidden_labels ? esc_html( implode( ', ', $hidden_labels ) ) : esc_html__( 'Nessuno', 'bookcreator' );
        }

        echo '</td>';
        echo '<td>';
        echo '<a class="button button-small" href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Modifica', 'bookcreator' ) . '</a> ';
        echo '<form method="post" style="display:inline;" onsubmit="return confirm(\'' . esc_js( __( 'Sei sicuro di voler eliminare questo template?', 'bookcreator' ) ) . '\');">';
        wp_nonce_field( 'bookcreator_manage_template', 'bookcreator_template_nonce' );
        echo '<input type="hidden" name="bookcreator_template_action" value="delete" />';
        echo '<input type="hidden" name="bookcreator_template_type" value="' . esc_attr( $current_type ) . '" />';
        echo '<input type="hidden" name="bookcreator_template_id" value="' . esc_attr( $template['id'] ) . '" />';
        submit_button( __( 'Elimina', 'bookcreator' ), 'delete button-small', '', false );
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

function bookcreator_templates_page_epub() {
    bookcreator_render_templates_page( 'epub' );
}

function bookcreator_templates_page_pdf() {
    bookcreator_render_templates_page( 'pdf' );
}

function bookcreator_register_templates_page() {
    add_submenu_page(
        'edit.php?post_type=book_creator',
        __( 'Template ePub', 'bookcreator' ),
        __( 'Template ePub', 'bookcreator' ),
        'manage_options',
        'bc-templates-epub',
        'bookcreator_templates_page_epub'
    );

    add_submenu_page(
        'edit.php?post_type=book_creator',
        __( 'Template PDF', 'bookcreator' ),
        __( 'Template PDF', 'bookcreator' ),
        'manage_options',
        'bc-templates-pdf',
        'bookcreator_templates_page_pdf'
    );
}
add_action( 'admin_menu', 'bookcreator_register_templates_page' );

function bookcreator_templates_admin_enqueue( $hook ) {
    if ( ! in_array( $hook, array( 'book_creator_page_bc-templates-epub', 'book_creator_page_bc-templates-pdf' ), true ) ) {
        return;
    }

    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_style( 'bookcreator-admin-styles', plugin_dir_url( __FILE__ ) . 'css/admin.css', array(), '1.2' );
    wp_enqueue_script( 'wp-color-picker' );
    wp_enqueue_script( 'bookcreator-templates', plugin_dir_url( __FILE__ ) . 'js/templates.js', array( 'jquery', 'wp-color-picker' ), '1.2', true );
}
add_action( 'admin_enqueue_scripts', 'bookcreator_templates_admin_enqueue' );

function bookcreator_get_epub_styles( $template = null ) {
    if ( $template && ( ! isset( $template['type'] ) || 'epub' !== $template['type'] ) ) {
        $template = null;
    }

    $settings         = $template ? bookcreator_normalize_template_settings( isset( $template['settings'] ) ? $template['settings'] : array(), 'epub' ) : bookcreator_get_default_template_settings( 'epub' );
    $title_color      = $settings['title_color'];
    $visible_fields   = isset( $settings['visible_fields'] ) ? (array) $settings['visible_fields'] : bookcreator_get_epub_default_visible_fields();
    $style_fields     = bookcreator_get_epub_style_fields();
    $hidden_selectors = array();

    $stylable_fields  = bookcreator_get_epub_stylable_fields();
    $font_families    = bookcreator_get_epub_font_family_options();
    $font_imports     = array();
    $normalized_styles = array();

    foreach ( $stylable_fields as $field_key => $field ) {
        $setting_key = $field_key . '_styles';
        $defaults    = bookcreator_get_epub_style_defaults( $field_key );
        $raw_styles  = isset( $settings[ $setting_key ] ) ? (array) $settings[ $setting_key ] : array();

        if ( 'book_title' === $field_key && empty( $raw_styles['color'] ) && $title_color ) {
            $raw_styles['color'] = $title_color;
        }

        $normalized_styles[ $field_key ] = bookcreator_normalize_epub_style_values( $raw_styles, $defaults, $settings, $setting_key );
    }

    foreach ( $style_fields as $field_key => $field ) {
        $is_visible = isset( $visible_fields[ $field_key ] ) ? (bool) $visible_fields[ $field_key ] : true;
        if ( ! $is_visible && ! empty( $field['selectors'] ) ) {
            $hidden_selectors = array_merge( $hidden_selectors, $field['selectors'] );
        }
    }

    $book_title_defaults = bookcreator_get_epub_style_defaults( 'book_title' );
    $book_title_styles   = isset( $normalized_styles['book_title'] ) ? $normalized_styles['book_title'] : $book_title_defaults;
    $book_title_color    = $book_title_styles['color'] ? $book_title_styles['color'] : ( $title_color ? $title_color : $book_title_defaults['color'] );

    $styles = array(
        'body {',
        '  font-family: serif;',
        '  line-height: 1.6;',
        '  margin: 1em;',
        '}',
        'img {',
        '  max-width: 100%;',
        '  height: auto;',
        '}',
        'h1, h2, h3 {',
        '  font-family: sans-serif;',
        '  margin-top: 1.2em;',
        '  margin-bottom: 0.6em;',
        '}',
        '.bookcreator-meta {',
        '  margin: 0;',
        '}',
        '.bookcreator-meta dt {',
        '  font-weight: bold;',
        '  margin-top: 0.8em;',
        '}',
        '.bookcreator-meta dd {',
        '  margin: 0 0 0.5em 0;',
        '}',
        '.bookcreator-field-label {',
        '  font-weight: bold;',
        '}',
        '.bookcreator-copyright__meta {',
        '  margin: 0;',
        '}',
        '.bookcreator-copyright__meta dt {',
        '  font-weight: bold;',
        '  margin-top: 0.8em;',
        '}',
        '.bookcreator-copyright__meta dd {',
        '  margin: 0 0 0.5em 0;',
        '}',
        '.bookcreator-section {',
        '  margin-bottom: 1.5em;',
        '}',
        '.bookcreator-chapter {',
        '  margin-bottom: 2.5em;',
        '}',
        '.bookcreator-paragraph {',
        '  margin-bottom: 2em;',
        '}',
        '.bookcreator-paragraph__title {',
        '  margin-top: 1.2em;',
        '  margin-bottom: 0.6em;',
        '}',
        '.bookcreator-paragraph__content {',
        '  margin-bottom: 1em;',
        '}',
        '.bookcreator-cover {',
        '  text-align: center;',
        '  margin: 0 auto 2em;',
        '}',
        '.bookcreator-cover img {',
        '  display: block;',
        '  margin: 0 auto;',
        '}',
        '.bookcreator-book__index ol, .bookcreator-preface__index ol, #toc ol {',
        '  list-style: none;',
        '  margin: 0;',
        '  padding-left: 0;',
        '}',
        '.bookcreator-book__index ol ol, .bookcreator-preface__index ol ol, #toc ol ol {',
        '  margin-left: 1.5em;',
        '}',
        '.bookcreator-footnotes, .bookcreator-citations {',
        '  font-size: 0.9em;',
        '  border-top: 1px solid #cccccc;',
        '  margin-top: 1em;',
        '  padding-top: 0.5em;',
        '}',
        '.bookcreator-book-title {',
        '  color: ' . $book_title_color . ';',
        '}',
    );

    foreach ( $stylable_fields as $field_key => $field ) {
        $selectors = isset( $field['selectors'] ) ? (array) $field['selectors'] : array();
        $selectors = array_filter( array_map( 'trim', $selectors ) );

        if ( empty( $selectors ) ) {
            continue;
        }

        $defaults     = bookcreator_get_epub_style_defaults( $field_key );
        $field_styles = isset( $normalized_styles[ $field_key ] ) ? $normalized_styles[ $field_key ] : $defaults;

        $font_family_key = $field_styles['font_family'];
        if ( ! isset( $font_families[ $font_family_key ] ) ) {
            $font_family_key = $defaults['font_family'];
        }
        $font_family = $font_families[ $font_family_key ];
        if ( ! empty( $font_family['import'] ) ) {
            $font_imports[] = $font_family['import'];
        }

        $font_size_value = '' !== $field_styles['font_size'] ? $field_styles['font_size'] : $defaults['font_size'];
        $font_size       = bookcreator_format_css_numeric_value( $font_size_value, 'rem' );
        if ( '' === $font_size ) {
            $font_size = bookcreator_format_css_numeric_value( $defaults['font_size'], 'rem' );
        }

        $line_height_value = '' !== $field_styles['line_height'] ? $field_styles['line_height'] : $defaults['line_height'];
        $line_height       = bookcreator_format_css_numeric_value( $line_height_value );
        if ( '' === $line_height ) {
            $line_height = bookcreator_format_css_numeric_value( $defaults['line_height'] );
        }

        $text_align = '' !== $field_styles['text_align'] ? $field_styles['text_align'] : $defaults['text_align'];

        $color = $field_styles['color'] ? $field_styles['color'] : $defaults['color'];
        if ( 'book_title' === $field_key && $book_title_color ) {
            $color = $book_title_color;
        }

        $background_color = $field_styles['background_color'] ? $field_styles['background_color'] : $defaults['background_color'];

        $margin_values = array(
            '' !== $field_styles['margin_top'] ? $field_styles['margin_top'] : $defaults['margin_top'],
            '' !== $field_styles['margin_right'] ? $field_styles['margin_right'] : $defaults['margin_right'],
            '' !== $field_styles['margin_bottom'] ? $field_styles['margin_bottom'] : $defaults['margin_bottom'],
            '' !== $field_styles['margin_left'] ? $field_styles['margin_left'] : $defaults['margin_left'],
        );
        $margin = bookcreator_format_css_box_numeric_values( $margin_values, 'em' );

        $padding_values = array(
            '' !== $field_styles['padding_top'] ? $field_styles['padding_top'] : $defaults['padding_top'],
            '' !== $field_styles['padding_right'] ? $field_styles['padding_right'] : $defaults['padding_right'],
            '' !== $field_styles['padding_bottom'] ? $field_styles['padding_bottom'] : $defaults['padding_bottom'],
            '' !== $field_styles['padding_left'] ? $field_styles['padding_left'] : $defaults['padding_left'],
        );
        $padding = bookcreator_format_css_box_numeric_values( $padding_values, 'em' );

        $hyphenation = isset( $field_styles['hyphenation'] ) && in_array( $field_styles['hyphenation'], array( 'auto', 'manual', 'none' ), true ) ? $field_styles['hyphenation'] : $defaults['hyphenation'];

        $styles[] = implode( ', ', $selectors ) . ' {';
        if ( $font_size ) {
            $styles[] = '  font-size: ' . $font_size . ';';
        }
        if ( $line_height ) {
            $styles[] = '  line-height: ' . $line_height . ';';
        }
        $styles[] = '  font-family: ' . $font_family['css'] . ';';
        $styles[] = '  font-style: ' . $field_styles['font_style'] . ';';
        $styles[] = '  font-weight: ' . $field_styles['font_weight'] . ';';
        $styles[] = '  color: ' . $color . ';';
        if ( $background_color ) {
            $styles[] = '  background-color: ' . $background_color . ';';
        }
        $styles[] = '  text-align: ' . $text_align . ';';
        if ( $margin ) {
            $styles[] = '  margin: ' . $margin . ';';
        }
        if ( $padding ) {
            $styles[] = '  padding: ' . $padding . ';';
        }
        $styles[] = '  -webkit-hyphens: ' . $hyphenation . ';';
        $styles[] = '  -moz-hyphens: ' . $hyphenation . ';';
        $styles[] = '  hyphens: ' . $hyphenation . ';';
        $styles[] = '}';
    }

    $styles[] = '.bookcreator-paragraph__featured-image {';
    $styles[] = '  margin: 1em 0;';
    $styles[] = '}';
    $styles[] = '.bookcreator-paragraph__featured-image img {';
    $styles[] = '  width: 100%;';
    $styles[] = '  height: auto;';
    $styles[] = '  display: block;';
    $styles[] = '}';

    if ( $hidden_selectors ) {
        $hidden_selectors = array_unique( $hidden_selectors );
        $styles[]         = implode( ', ', $hidden_selectors ) . ' {';
        $styles[]         = '  display: none !important;';
        $styles[]         = '}';
    }

    if ( $font_imports ) {
        $font_imports = array_unique( $font_imports );
        $styles       = array_merge( $font_imports, $styles );
    }

    return implode( "\n", $styles );
}

function bookcreator_prepare_epub_content( $content ) {
    if ( empty( $content ) ) {
        return '';
    }

    $filtered = apply_filters( 'the_content', $content );
    $filtered = force_balance_tags( $filtered );
    $filtered = wp_kses_post( $filtered );
    $filtered = preg_replace( '#\s+$#', '', $filtered );

    return $filtered;
}

function bookcreator_build_epub_document( $title, $body, $language = 'en' ) {
    $language      = $language ? strtolower( str_replace( '_', '-', $language ) ) : 'en';
    $language_attr = bookcreator_escape_xml( $language );

    $document  = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
    $document .= '<!DOCTYPE html>' . "\n";
    $document .= '<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops" xml:lang="' . $language_attr . '" lang="' . $language_attr . '">' . "\n";
    $document .= '<head>' . "\n";
    $document .= '<meta charset="utf-8" />' . "\n";
    $document .= '<title>' . bookcreator_escape_xml( $title ) . '</title>' . "\n";
    $document .= '<link rel="stylesheet" type="text/css" href="styles/bookcreator.css" />' . "\n";
    $document .= '</head>' . "\n";
    $document .= '<body>' . "\n";
    $document .= $body . "\n";
    $document .= '</body>' . "\n";
    $document .= '</html>';

    return $document;
}


function bookcreator_escape_xml( $value ) {
    return htmlspecialchars( (string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8' );
}

function bookcreator_guess_mime_type( $file_path ) {
    $type = wp_check_filetype( $file_path );
    if ( ! empty( $type['type'] ) ) {
        return $type['type'];
    }

    $extension = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );
    switch ( $extension ) {
        case 'svg':
            return 'image/svg+xml';
        case 'bmp':
            return 'image/bmp';
        default:
            return 'application/octet-stream';
    }
}

function bookcreator_resolve_epub_asset_path( $url, $upload_dir ) {
    if ( empty( $url ) ) {
        return '';
    }

    $clean = $url;
    $hash_position = strpos( $clean, '#' );
    if ( false !== $hash_position ) {
        $clean = substr( $clean, 0, $hash_position );
    }

    $query_position = strpos( $clean, '?' );
    if ( false !== $query_position ) {
        $clean = substr( $clean, 0, $query_position );
    }

    if ( '' === $clean || 0 === strpos( $clean, 'data:' ) ) {
        return '';
    }

    if ( 0 === strpos( $clean, '//' ) ) {
        $clean = ( is_ssl() ? 'https:' : 'http:' ) . $clean;
    }

    $clean = wp_normalize_path( $clean );

    if ( preg_match( '#^https?://#i', $clean ) ) {
        $baseurl = trailingslashit( wp_normalize_path( $upload_dir['baseurl'] ) );
        if ( 0 === strpos( $clean, $baseurl ) ) {
            $relative = substr( $clean, strlen( $baseurl ) );
            $path     = wp_normalize_path( trailingslashit( $upload_dir['basedir'] ) . $relative );
            if ( file_exists( $path ) ) {
                return $path;
            }
        }

        $home_url = trailingslashit( wp_normalize_path( home_url( '/' ) ) );
        if ( 0 === strpos( $clean, $home_url ) ) {
            $relative = substr( $clean, strlen( $home_url ) );
            $path     = wp_normalize_path( trailingslashit( ABSPATH ) . $relative );
            if ( file_exists( $path ) ) {
                return $path;
            }
        }

        return '';
    }

    if ( 0 === strpos( $clean, '/' ) ) {
        $path = wp_normalize_path( trailingslashit( ABSPATH ) . ltrim( $clean, '/' ) );
        if ( file_exists( $path ) ) {
            return $path;
        }
    } else {
        $candidate = wp_normalize_path( trailingslashit( $upload_dir['basedir'] ) . ltrim( $clean, '/' ) );
        if ( file_exists( $candidate ) ) {
            return $candidate;
        }
    }

    return '';
}

function bookcreator_register_epub_image_asset( $source_path, array &$assets, array &$asset_map, array $args = array() ) {
    if ( isset( $asset_map[ $source_path ] ) ) {
        $index = $asset_map[ $source_path ];
        if ( isset( $args['properties'] ) && $args['properties'] && empty( $assets[ $index ]['properties'] ) ) {
            $assets[ $index ]['properties'] = $args['properties'];
        }

        return $assets[ $index ];
    }

    $info      = pathinfo( $source_path );
    $extension = isset( $info['extension'] ) ? $info['extension'] : '';
    $filename  = isset( $args['filename'] ) ? $args['filename'] : ( isset( $info['basename'] ) ? $info['basename'] : 'image' );
    $href      = isset( $args['href'] ) ? $args['href'] : 'images/' . $filename;

    if ( empty( $args['href'] ) ) {
        $existing_hrefs = wp_list_pluck( $assets, 'href' );
        $name           = isset( $info['filename'] ) ? $info['filename'] : 'image';
        $counter        = 1;
        while ( in_array( $href, $existing_hrefs, true ) ) {
            $suffix = $counter++;
            $href   = 'images/' . $name . '-' . $suffix . ( $extension ? '.' . $extension : '' );
        }
    }

    $asset = array(
        'id'         => isset( $args['id'] ) ? $args['id'] : 'img-' . ( count( $assets ) + 1 ),
        'href'       => $href,
        'media_type' => bookcreator_guess_mime_type( $source_path ),
        'source'     => $source_path,
        'properties' => isset( $args['properties'] ) ? $args['properties'] : '',
    );

    $assets[]                  = $asset;
    $asset_map[ $source_path ] = count( $assets ) - 1;

    return $asset;
}

function bookcreator_process_epub_images( $html, array &$assets, array &$asset_map ) {
    if ( false === stripos( $html, '<img' ) ) {
        return $html;
    }

    $upload_dir = wp_upload_dir();
    if ( ! empty( $upload_dir['error'] ) || empty( $upload_dir['basedir'] ) ) {
        return $html;
    }

    return preg_replace_callback(
        '/(<img[^>]+src=["\'])([^"\']+)(["\'])/i',
        function ( $matches ) use ( &$assets, &$asset_map, $upload_dir ) {
            $src   = $matches[2];
            $local = bookcreator_resolve_epub_asset_path( $src, $upload_dir );
            if ( ! $local ) {
                return $matches[0];
            }

            $asset = bookcreator_register_epub_image_asset( $local, $assets, $asset_map );

            return $matches[1] . esc_attr( $asset['href'] ) . $matches[3];
        },
        $html
    );
}

function bookcreator_build_nav_items_html( array $items ) {
    $html = '';

    foreach ( $items as $item ) {
        if ( empty( $item['title'] ) || empty( $item['href'] ) ) {
            continue;
        }

        $html .= '<li><a href="' . bookcreator_escape_xml( $item['href'] ) . '">' . bookcreator_escape_xml( $item['title'] ) . '</a>';

        if ( ! empty( $item['children'] ) && is_array( $item['children'] ) ) {
            $html .= '<ol>';
            $html .= bookcreator_build_nav_items_html( $item['children'] );
            $html .= '</ol>';
        }

        $html .= '</li>' . "\n";
    }

    return $html;
}

function bookcreator_build_nav_document( $book_title, $chapters, $language = 'en' ) {
    $language      = $language ? strtolower( str_replace( '_', '-', $language ) ) : 'en';
    $language_attr = bookcreator_escape_xml( $language );
    $title         = bookcreator_escape_xml( sprintf( __( 'Indice - %s', 'bookcreator' ), $book_title ) );
    $heading       = bookcreator_escape_xml( __( 'Indice', 'bookcreator' ) );

    $items_html = bookcreator_build_nav_items_html( $chapters );

    $doc = <<<NAV
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops" xml:lang="{$language_attr}" lang="{$language_attr}">
<head>
<meta charset="utf-8" />
<title>{$title}</title>
<link rel="stylesheet" type="text/css" href="styles/bookcreator.css" />
</head>
<body>
<nav epub:type="toc" id="toc">
<h1>{$heading}</h1>
<ol>
{$items_html}
</ol>
</nav>
</body>
</html>
NAV;

    return $doc;
}

/**
 * Build the HTML index shown within the preface of the ePub.
 *
 * @param array $chapters_data Structured chapter data.
 * @return string
 */
function bookcreator_build_epub_preface_index( $chapters_data ) {
    if ( empty( $chapters_data ) ) {
        return '';
    }

    $html  = '<nav class="bookcreator-preface__index">';
    $html .= '<h2>' . esc_html__( 'Indice', 'bookcreator' ) . '</h2>';
    $html .= '<ol>';

    foreach ( $chapters_data as $chapter_data ) {
        if ( empty( $chapter_data['href'] ) || empty( $chapter_data['number'] ) ) {
            continue;
        }

        $chapter_title = isset( $chapter_data['title'] ) ? $chapter_data['title'] : '';
        if ( '' === $chapter_title ) {
            $chapter_title = sprintf( __( 'Capitolo %s', 'bookcreator' ), $chapter_data['number'] );
        }

        $chapter_label = $chapter_data['number'] . '.';
        $html         .= '<li>';
        $html         .= '<a href="' . esc_attr( $chapter_data['href'] ) . '">' . esc_html( $chapter_label . ' ' . $chapter_title ) . '</a>';

        if ( ! empty( $chapter_data['paragraphs'] ) && is_array( $chapter_data['paragraphs'] ) ) {
            $html .= '<ol>';

            foreach ( $chapter_data['paragraphs'] as $paragraph_data ) {
                if ( empty( $paragraph_data['href'] ) || empty( $paragraph_data['number'] ) ) {
                    continue;
                }

                $paragraph_title = isset( $paragraph_data['title'] ) && '' !== $paragraph_data['title']
                    ? $paragraph_data['title']
                    : sprintf( __( 'Paragrafo %s', 'bookcreator' ), $paragraph_data['number'] );

                $html .= '<li>';
                $html .= '<a href="' . esc_attr( $paragraph_data['href'] ) . '">' . esc_html( $paragraph_data['number'] . ' ' . $paragraph_title ) . '</a>';
                $html .= '</li>';
            }

            $html .= '</ol>';
        }

        $html .= '</li>';
    }

    $html .= '</ol>';
    $html .= '</nav>';

    return $html;
}

function bookcreator_delete_directory( $directory ) {
    if ( ! is_dir( $directory ) ) {
        return;
    }

    $items = scandir( $directory );
    if ( false === $items ) {
        return;
    }

    foreach ( $items as $item ) {
        if ( '.' === $item || '..' === $item ) {
            continue;
        }

        $path = $directory . DIRECTORY_SEPARATOR . $item;
        if ( is_dir( $path ) ) {
            bookcreator_delete_directory( $path );
        } else {
            @unlink( $path );
        }
    }

    @rmdir( $directory );
}

function bookcreator_create_epub_from_book( $book_id, $template_id = '' ) {
    if ( ! bookcreator_load_epub_library() ) {
        return new WP_Error( 'bookcreator_epub_missing_library', bookcreator_get_epub_library_error_message() );
    }

    $book_post = get_post( $book_id );
    if ( ! $book_post || 'book_creator' !== $book_post->post_type ) {
        return new WP_Error( 'bookcreator_epub_invalid_book', __( 'Libro non valido.', 'bookcreator' ) );
    }

    $title = get_the_title( $book_post );

    $template = $template_id ? bookcreator_get_template( $template_id ) : null;
    if ( $template && ( ! isset( $template['type'] ) || 'epub' !== $template['type'] ) ) {
        return new WP_Error( 'bookcreator_epub_invalid_template', __( 'Il template selezionato non è valido per gli ePub.', 'bookcreator' ) );
    }

    $language = get_post_meta( $book_id, 'bc_language', true );
    if ( ! $language ) {
        $site_language = get_bloginfo( 'language' );
        if ( $site_language ) {
            $language = strtolower( str_replace( '_', '-', $site_language ) );
        } else {
            $language = 'en';
        }
    } else {
        $language = strtolower( str_replace( '_', '-', $language ) );
    }

    $identifier_meta  = get_post_meta( $book_id, 'bc_isbn', true );
    $identifier_value = $identifier_meta ? $identifier_meta : $permalink;
    if ( ! $identifier_value ) {
        $identifier_value = 'bookcreator-' . $book_id;
    }

    $modified_gmt    = gmdate( 'Y-m-d\TH:i:s\Z', current_time( 'timestamp', true ) );
    $publication_raw = get_post_meta( $book_id, 'bc_pub_date', true );
    if ( $publication_raw ) {
        $publication_date = mysql2date( 'Y-m-d', $publication_raw );
    } else {
        $publication_date = gmdate( 'Y-m-d', get_post_time( 'U', true, $book_post ) );
    }

    $description_meta = get_post_meta( $book_id, 'bc_description', true );
    $publisher        = get_post_meta( $book_id, 'bc_publisher', true );
    $rights_meta      = get_post_meta( $book_id, 'bc_copyright', true );

    $author    = get_post_meta( $book_id, 'bc_author', true );
    $coauthors = get_post_meta( $book_id, 'bc_coauthors', true );
    $author_display = trim( $author . ( $coauthors ? ', ' . $coauthors : '' ) );

    $subjects = array();
    $genres   = get_the_terms( $book_id, 'book_genre' );
    if ( ! empty( $genres ) && ! is_wp_error( $genres ) ) {
        foreach ( $genres as $genre ) {
            $subjects[] = $genre->name;
        }
    }

    $keywords = get_post_meta( $book_id, 'bc_keywords', true );
    if ( $keywords ) {
        $parts = preg_split( '/[,;]+/', $keywords );
        if ( $parts ) {
            foreach ( $parts as $part ) {
                $part = trim( $part );
                if ( '' !== $part ) {
                    $subjects[] = $part;
                }
            }
        }
    }

    $upload_dir = wp_upload_dir();
    if ( ! empty( $upload_dir['error'] ) ) {
        return new WP_Error( 'bookcreator_epub_upload_dir', $upload_dir['error'] );
    }

    $base_dir = trailingslashit( $upload_dir['basedir'] ) . 'bookcreator-epubs';
    if ( ! wp_mkdir_p( $base_dir ) ) {
        return new WP_Error( 'bookcreator_epub_directory', __( 'Impossibile creare la cartella per gli ePub.', 'bookcreator' ) );
    }

    $temp_dir = trailingslashit( get_temp_dir() ) . 'bookcreator-epub-' . wp_generate_uuid4();
    if ( ! wp_mkdir_p( $temp_dir ) ) {
        return new WP_Error( 'bookcreator_epub_temp', __( 'Impossibile creare la cartella temporanea per l\'ePub.', 'bookcreator' ) );
    }

    $meta_inf_dir = trailingslashit( $temp_dir ) . 'META-INF';
    $oebps_dir    = trailingslashit( $temp_dir ) . 'OEBPS';
    $styles_dir   = trailingslashit( $oebps_dir ) . 'styles';

    foreach ( array( $meta_inf_dir, $oebps_dir, $styles_dir ) as $dir ) {
        if ( ! wp_mkdir_p( $dir ) ) {
            bookcreator_delete_directory( $temp_dir );

            return new WP_Error( 'bookcreator_epub_temp', __( 'Impossibile creare la cartella temporanea per l\'ePub.', 'bookcreator' ) );
        }
    }

    if ( false === file_put_contents( trailingslashit( $temp_dir ) . 'mimetype', 'application/epub+zip' ) ) {
        bookcreator_delete_directory( $temp_dir );

        return new WP_Error( 'bookcreator_epub_write', __( 'Impossibile preparare i file temporanei per l\'ePub.', 'bookcreator' ) );
    }

    $container_xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container">
  <rootfiles>
    <rootfile full-path="OEBPS/content.opf" media-type="application/oebps-package+xml"/>
  </rootfiles>
</container>
XML;

    if ( false === file_put_contents( $meta_inf_dir . '/container.xml', $container_xml ) ) {
        bookcreator_delete_directory( $temp_dir );

        return new WP_Error( 'bookcreator_epub_write', __( 'Impossibile preparare i file temporanei per l\'ePub.', 'bookcreator' ) );
    }

    if ( false === file_put_contents( $styles_dir . '/bookcreator.css', bookcreator_get_epub_styles( $template ) ) ) {
        bookcreator_delete_directory( $temp_dir );

        return new WP_Error( 'bookcreator_epub_write', __( 'Impossibile preparare i file temporanei per l\'ePub.', 'bookcreator' ) );
    }

    $assets    = array();
    $asset_map = array();
    $chapters  = array();

    $cover_asset = null;
    $cover_id    = (int) get_post_meta( $book_id, 'bc_cover', true );
    if ( $cover_id ) {
        $cover_path = get_attached_file( $cover_id );
        if ( $cover_path && file_exists( $cover_path ) ) {
            $extension   = pathinfo( $cover_path, PATHINFO_EXTENSION );
            $cover_href  = 'images/cover' . ( $extension ? '.' . $extension : '' );
            $cover_asset = bookcreator_register_epub_image_asset(
                $cover_path,
                $assets,
                $asset_map,
                array(
                    'id'         => 'cover-image',
                    'href'       => $cover_href,
                    'properties' => 'cover-image',
                )
            );

            $cover_body  = '<div class="bookcreator-cover"><img src="' . esc_attr( $cover_asset['href'] ) . '" alt="' . esc_attr( $title ) . '" /></div>';
            $chapters[] = array(
                'id'       => 'cover',
                'title'    => __( 'Copertina', 'bookcreator' ),
                'filename' => 'cover.xhtml',
                'href'     => 'cover.xhtml',
                'content'  => bookcreator_build_epub_document( __( 'Copertina', 'bookcreator' ), $cover_body, $language ),
                'children' => array(),
            );
        }
    }

    $frontispiece_body  = '<div class="bookcreator-frontispiece">';

    if ( $author || $coauthors ) {
        $frontispiece_body .= '<div class="bookcreator-frontispiece__authors">';

        if ( $author ) {
            $frontispiece_body .= '<p class="bookcreator-frontispiece__field bookcreator-frontispiece__field-bc_author">' . esc_html( $author ) . '</p>';
        }

        if ( $coauthors ) {
            $frontispiece_body .= '<p class="bookcreator-frontispiece__field bookcreator-frontispiece__field-bc_coauthors">' . esc_html( $coauthors ) . '</p>';
        }

        $frontispiece_body .= '</div>';
    }

    $frontispiece_body .= '<h1 class="bookcreator-frontispiece__title bookcreator-book-title">' . esc_html( $title ) . '</h1>';

    $subtitle = get_post_meta( $book_id, 'bc_subtitle', true );
    if ( $subtitle ) {
        $frontispiece_body .= '<p class="bookcreator-frontispiece__subtitle">' . esc_html( $subtitle ) . '</p>';
    }

    if ( $publisher ) {
        $frontispiece_body .= '<p class="bookcreator-frontispiece__field bookcreator-frontispiece__field-bc_publisher">' . esc_html( $publisher ) . '</p>';
    }

    $language_label = bookcreator_get_language_label( get_post_meta( $book_id, 'bc_language', true ) );
    if ( $language_label ) {
        $frontispiece_body .= '<p class="bookcreator-frontispiece__field bookcreator-frontispiece__field-bc_language">' . esc_html( $language_label ) . '</p>';
    }

    if ( $description_meta ) {
        $frontispiece_body .= '<section class="bookcreator-frontispiece__description">';
        $frontispiece_body .= bookcreator_prepare_epub_content( $description_meta );
        $frontispiece_body .= '</section>';
    }

    $custom_frontispiece = get_post_meta( $book_id, 'bc_frontispiece', true );
    if ( $custom_frontispiece ) {
        $frontispiece_body .= '<section class="bookcreator-frontispiece__extra">';
        $frontispiece_body .= bookcreator_prepare_epub_content( $custom_frontispiece );
        $frontispiece_body .= '</section>';
    }

    $frontispiece_body .= '</div>';
    $frontispiece_body  = bookcreator_process_epub_images( $frontispiece_body, $assets, $asset_map );

    $chapters[] = array(
        'id'       => 'frontispiece',
        'title'    => __( 'Frontespizio', 'bookcreator' ),
        'filename' => 'frontispiece.xhtml',
        'href'     => 'frontispiece.xhtml',
        'content'  => bookcreator_build_epub_document( __( 'Frontespizio', 'bookcreator' ), $frontispiece_body, $language ),
        'children' => array(),
    );

    $copyright_items = array();

    $isbn = get_post_meta( $book_id, 'bc_isbn', true );
    if ( $isbn ) {
        $copyright_items[] = array(
            'label' => __( 'ISBN', 'bookcreator' ),
            'value' => $isbn,
        );
    }

    if ( $publication_date ) {
        $display_publication_date = mysql2date( get_option( 'date_format' ), $publication_date );
        $copyright_items[]        = array(
            'label' => __( 'Data di pubblicazione', 'bookcreator' ),
            'value' => $display_publication_date,
        );
    }

    $legal_notice = get_post_meta( $book_id, 'bc_copyright', true );

    if ( $copyright_items || $legal_notice ) {
        $copyright_body  = '<div class="bookcreator-copyright">';
        $copyright_body .= '<h1>' . esc_html__( 'Copyright', 'bookcreator' ) . '</h1>';

        if ( $copyright_items ) {
            $copyright_body .= '<dl class="bookcreator-copyright__meta">';
            foreach ( $copyright_items as $item ) {
                $copyright_body .= '<dt>' . esc_html( $item['label'] ) . '</dt>';
                $copyright_body .= '<dd>' . esc_html( $item['value'] ) . '</dd>';
            }
            $copyright_body .= '</dl>';
        }

        if ( $legal_notice ) {
            $copyright_body .= '<section class="bookcreator-copyright__legal">';
            $copyright_body .= bookcreator_prepare_epub_content( $legal_notice );
            $copyright_body .= '</section>';
        }

        $copyright_body .= '</div>';
        $copyright_body  = bookcreator_process_epub_images( $copyright_body, $assets, $asset_map );

        $chapters[] = array(
            'id'       => 'copyright',
            'title'    => __( 'Copyright', 'bookcreator' ),
            'filename' => 'copyright.xhtml',
            'href'     => 'copyright.xhtml',
            'content'  => bookcreator_build_epub_document( __( 'Copyright', 'bookcreator' ), $copyright_body, $language ),
            'children' => array(),
        );
    }

    $ordered_chapter_posts = bookcreator_get_ordered_chapters_for_book( $book_id );
    $ordered_chapters      = array();

    if ( $ordered_chapter_posts ) {
        foreach ( $ordered_chapter_posts as $index => $chapter_post ) {
            $chapter_title = get_the_title( $chapter_post );
            $chapter_slug  = sanitize_title( $chapter_post->post_name ? $chapter_post->post_name : $chapter_title );

            if ( ! $chapter_slug ) {
                $chapter_slug = (string) $chapter_post->ID;
            }

            $file_slug       = 'chapter-' . ( $index + 1 ) . '-' . $chapter_slug . '.xhtml';
            $chapter_number  = (string) ( $index + 1 );
            $paragraph_posts = bookcreator_get_ordered_paragraphs_for_chapter( $chapter_post->ID );
            $paragraphs_data = array();

            if ( $paragraph_posts ) {
                foreach ( $paragraph_posts as $paragraph_index => $paragraph_post ) {
                    $paragraph_number = $chapter_number . '.' . ( $paragraph_index + 1 );

                    $paragraphs_data[] = array(
                        'post'   => $paragraph_post,
                        'title'  => get_the_title( $paragraph_post ),
                        'number' => $paragraph_number,
                        'href'   => $file_slug . '#paragraph-' . $paragraph_post->ID,
                    );
                }
            }

            $ordered_chapters[] = array(
                'post'       => $chapter_post,
                'title'      => $chapter_title,
                'file_slug'  => $file_slug,
                'href'       => $file_slug,
                'number'     => $chapter_number,
                'paragraphs' => $paragraphs_data,
            );
        }
    }

    $dedication = get_post_meta( $book_id, 'bc_dedication', true );
    if ( $dedication ) {
        $dedication_body  = '<div class="bookcreator-dedication">';
        $dedication_body .= '<h1>' . esc_html__( 'Dedica', 'bookcreator' ) . '</h1>';
        $dedication_body .= bookcreator_prepare_epub_content( $dedication );
        $dedication_body .= '</div>';
        $dedication_body  = bookcreator_process_epub_images( $dedication_body, $assets, $asset_map );

        $chapters[] = array(
            'id'       => 'dedication',
            'title'    => __( 'Dedica', 'bookcreator' ),
            'filename' => 'dedication.xhtml',
            'href'     => 'dedication.xhtml',
            'content'  => bookcreator_build_epub_document( __( 'Dedica', 'bookcreator' ), $dedication_body, $language ),
            'children' => array(),
        );
    }

    $preface = get_post_meta( $book_id, 'bc_preface', true );
    if ( $preface || $ordered_chapters ) {
        $preface_body  = '<div class="bookcreator-preface">';
        $preface_body .= '<h1>' . esc_html__( 'Prefazione', 'bookcreator' ) . '</h1>';

        if ( $preface ) {
            $preface_body .= bookcreator_prepare_epub_content( $preface );
        }

        if ( $ordered_chapters ) {
            $preface_body .= bookcreator_build_epub_preface_index( $ordered_chapters );
        }

        $preface_body .= '</div>';
        $preface_body  = bookcreator_process_epub_images( $preface_body, $assets, $asset_map );

        $chapters[] = array(
            'id'       => 'preface',
            'title'    => __( 'Prefazione', 'bookcreator' ),
            'filename' => 'preface.xhtml',
            'href'     => 'preface.xhtml',
            'content'  => bookcreator_build_epub_document( __( 'Prefazione', 'bookcreator' ), $preface_body, $language ),
            'children' => array(),
        );
    }

    if ( $ordered_chapters ) {
        foreach ( $ordered_chapters as $index => $chapter_data ) {
            $chapter       = $chapter_data['post'];
            $chapter_title = isset( $chapter_data['title'] ) ? $chapter_data['title'] : '';
            if ( '' === $chapter_title ) {
                $chapter_title = sprintf( __( 'Capitolo %s', 'bookcreator' ), $chapter_data['number'] );
            }
            $file_slug      = $chapter_data['file_slug'];
            $chapter_body   = '<section class="bookcreator-chapter">';
            $chapter_body  .= '<h1 class="bookcreator-chapter__title">' . esc_html( $chapter_title ) . '</h1>';
            $chapter_paragraph_items = array();

            if ( $chapter && $chapter->post_content ) {
                $chapter_body .= '<div class="bookcreator-chapter__content">';
                $chapter_body .= bookcreator_prepare_epub_content( $chapter->post_content );
                $chapter_body .= '</div>';
            }

            if ( ! empty( $chapter_data['paragraphs'] ) ) {
                foreach ( $chapter_data['paragraphs'] as $paragraph_data ) {
                    $paragraph = $paragraph_data['post'];
                    if ( ! $paragraph ) {
                        continue;
                    }

                    $paragraph_title = $paragraph_data['title'];
                    if ( '' === $paragraph_title ) {
                        $paragraph_title = sprintf( __( 'Paragrafo %s', 'bookcreator' ), $paragraph_data['number'] );
                    }

                    $chapter_body .= '<section class="bookcreator-paragraph" id="paragraph-' . esc_attr( $paragraph->ID ) . '">';
                    $chapter_body .= '<h2 class="bookcreator-paragraph__title">' . esc_html( $paragraph_title ) . '</h2>';

                    if ( has_post_thumbnail( $paragraph ) ) {
                        $thumbnail_id = get_post_thumbnail_id( $paragraph );
                        if ( $thumbnail_id ) {
                            $image_src = wp_get_attachment_image_src( $thumbnail_id, 'full' );
                            if ( $image_src ) {
                                $alt_text = get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true );
                                if ( ! $alt_text ) {
                                    $alt_text = $paragraph_title;
                                }
                                $chapter_body .= '<figure class="bookcreator-paragraph__featured-image">';
                                $chapter_body .= '<img src="' . esc_url( $image_src[0] ) . '" alt="' . esc_attr( $alt_text ) . '" />';
                                $chapter_body .= '</figure>';
                            }
                        }
                    }

                    $chapter_paragraph_items[] = array(
                        'title'    => $paragraph_title,
                        'href'     => $paragraph_data['href'],
                        'children' => array(),
                    );

                    if ( $paragraph->post_content ) {
                        $chapter_body .= '<div class="bookcreator-paragraph__content">';
                        $chapter_body .= bookcreator_prepare_epub_content( $paragraph->post_content );
                        $chapter_body .= '</div>';
                    }

                    $footnotes = get_post_meta( $paragraph->ID, 'bc_footnotes', true );
                    if ( $footnotes ) {
                        $chapter_body .= '<div class="bookcreator-footnotes">';
                        $chapter_body .= '<h3>' . esc_html__( 'Note', 'bookcreator' ) . '</h3>';
                        $chapter_body .= bookcreator_prepare_epub_content( $footnotes );
                        $chapter_body .= '</div>';
                    }

                    $citations = get_post_meta( $paragraph->ID, 'bc_citations', true );
                    if ( $citations ) {
                        $chapter_body .= '<div class="bookcreator-citations">';
                        $chapter_body .= '<h3>' . esc_html__( 'Citazioni', 'bookcreator' ) . '</h3>';
                        $chapter_body .= bookcreator_prepare_epub_content( $citations );
                        $chapter_body .= '</div>';
                    }

                    $chapter_body .= '</section>';
                }
            }

            $chapter_body .= '</section>';
            $chapter_body = bookcreator_process_epub_images( $chapter_body, $assets, $asset_map );
            $chapters[] = array(
                'id'       => 'chapter-' . ( $index + 1 ),
                'title'    => $chapter_title,
                'filename' => $file_slug,
                'href'     => $file_slug,
                'content'  => bookcreator_build_epub_document( $chapter_title, $chapter_body, $language ),
                'children' => $chapter_paragraph_items,
            );
        }
    }

    $final_sections = array(
        'bc_appendix'     => array(
            'id'       => 'appendix',
            'title'    => __( 'Appendice', 'bookcreator' ),
            'filename' => 'appendix.xhtml',
        ),
        'bc_bibliography' => array(
            'id'       => 'bibliography',
            'title'    => __( 'Bibliografia', 'bookcreator' ),
            'filename' => 'bibliography.xhtml',
        ),
        'bc_author_note'  => array(
            'id'       => 'author-note',
            'title'    => __( 'Nota dell\'autore', 'bookcreator' ),
            'filename' => 'author-note.xhtml',
        ),
    );

    foreach ( $final_sections as $meta_key => $section ) {
        $content = get_post_meta( $book_id, $meta_key, true );
        if ( ! $content ) {
            continue;
        }

        $section_body  = '<div class="bookcreator-section bookcreator-section-' . esc_attr( $meta_key ) . '">';
        $section_body .= '<h1>' . esc_html( $section['title'] ) . '</h1>';
        $section_body .= bookcreator_prepare_epub_content( $content );
        $section_body .= '</div>';
        $section_body  = bookcreator_process_epub_images( $section_body, $assets, $asset_map );

        $chapters[] = array(
            'id'       => $section['id'],
            'title'    => $section['title'],
            'filename' => $section['filename'],
            'href'     => $section['filename'],
            'content'  => bookcreator_build_epub_document( $section['title'], $section_body, $language ),
            'children' => array(),
        );
    }

    $nav_document = bookcreator_build_nav_document( $title, $chapters, $language );

    if ( false === file_put_contents( $oebps_dir . '/nav.xhtml', $nav_document ) ) {
        bookcreator_delete_directory( $temp_dir );

        return new WP_Error( 'bookcreator_epub_write', __( 'Impossibile preparare i file temporanei per l\'ePub.', 'bookcreator' ) );
    }

    foreach ( $chapters as $chapter ) {
        if ( false === file_put_contents( $oebps_dir . '/' . $chapter['filename'], $chapter['content'] ) ) {
            bookcreator_delete_directory( $temp_dir );

            return new WP_Error( 'bookcreator_epub_write', __( 'Impossibile preparare i file temporanei per l\'ePub.', 'bookcreator' ) );
        }
    }

    if ( $assets ) {
        foreach ( $assets as $asset ) {
            $target_path = $oebps_dir . '/' . $asset['href'];
            if ( ! wp_mkdir_p( dirname( $target_path ) ) ) {
                bookcreator_delete_directory( $temp_dir );

                return new WP_Error( 'bookcreator_epub_write', __( 'Impossibile preparare i file temporanei per l\'ePub.', 'bookcreator' ) );
            }

            if ( ! copy( $asset['source'], $target_path ) ) {
                bookcreator_delete_directory( $temp_dir );

                return new WP_Error( 'bookcreator_epub_write', __( 'Impossibile preparare i file temporanei per l\'ePub.', 'bookcreator' ) );
            }
        }
    }

    $manifest_items = array(
        array(
            'id'         => 'style-bookcreator',
            'href'       => 'styles/bookcreator.css',
            'media_type' => 'text/css',
            'properties' => '',
        ),
        array(
            'id'         => 'nav',
            'href'       => 'nav.xhtml',
            'media_type' => 'application/xhtml+xml',
            'properties' => 'nav',
        ),
    );

    foreach ( $chapters as $chapter ) {
        $manifest_items[] = array(
            'id'         => $chapter['id'],
            'href'       => $chapter['filename'],
            'media_type' => 'application/xhtml+xml',
            'properties' => '',
        );
    }

    if ( $assets ) {
        foreach ( $assets as $asset ) {
            $manifest_items[] = array(
                'id'         => $asset['id'],
                'href'       => $asset['href'],
                'media_type' => $asset['media_type'],
                'properties' => $asset['properties'],
            );
        }
    }

    $opf  = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
    $opf .= '<package xmlns="http://www.idpf.org/2007/opf" version="3.0" unique-identifier="book-id" xml:lang="' . bookcreator_escape_xml( $language ) . '">\n';
    $opf .= "  <metadata xmlns:dc=\"http://purl.org/dc/elements/1.1/\" xmlns:opf=\"http://www.idpf.org/2007/opf\">\n";
    $opf .= '    <dc:identifier id="book-id">' . bookcreator_escape_xml( $identifier_value ) . "</dc:identifier>\n";
    if ( $identifier_meta && $permalink ) {
        $opf .= '    <dc:identifier id="book-link">' . bookcreator_escape_xml( $permalink ) . "</dc:identifier>\n";
    }
    $opf .= '    <meta property="dcterms:modified">' . bookcreator_escape_xml( $modified_gmt ) . "</meta>\n";
    if ( $publication_date ) {
        $opf .= '    <dc:date>' . bookcreator_escape_xml( $publication_date ) . "</dc:date>\n";
    }
    $opf .= '    <dc:title>' . bookcreator_escape_xml( $title ) . "</dc:title>\n";
    $opf .= '    <dc:language>' . bookcreator_escape_xml( $language ) . "</dc:language>\n";
    if ( $author_display ) {
        $opf .= '    <dc:creator>' . bookcreator_escape_xml( $author_display ) . "</dc:creator>\n";
    }
    if ( $publisher ) {
        $opf .= '    <dc:publisher>' . bookcreator_escape_xml( $publisher ) . "</dc:publisher>\n";
    }
    if ( $edition ) {
        $opf .= '    <meta property="dcterms:hasVersion">' . bookcreator_escape_xml( $edition ) . "</meta>\n";
    }
    if ( $description_meta ) {
        $opf .= '    <dc:description>' . bookcreator_escape_xml( wp_strip_all_tags( $description_meta ) ) . "</dc:description>\n";
    }
    if ( $rights_meta ) {
        $opf .= '    <dc:rights>' . bookcreator_escape_xml( wp_strip_all_tags( $rights_meta ) ) . "</dc:rights>\n";
    }
    if ( $permalink ) {
        $opf .= '    <dc:source>' . bookcreator_escape_xml( $permalink ) . "</dc:source>\n";
    }
    if ( $subjects ) {
        foreach ( $subjects as $subject ) {
            $opf .= '    <dc:subject>' . bookcreator_escape_xml( $subject ) . "</dc:subject>\n";
        }
    }
    if ( $cover_asset ) {
        $opf .= '    <meta name="cover" content="' . bookcreator_escape_xml( $cover_asset['id'] ) . '" />\n';
    }
    $opf .= "  </metadata>\n";
    $opf .= "  <manifest>\n";

    foreach ( $manifest_items as $item ) {
        $opf .= '    <item id="' . bookcreator_escape_xml( $item['id'] ) . '" href="' . bookcreator_escape_xml( $item['href'] ) . '" media-type="' . bookcreator_escape_xml( $item['media_type'] ) . '"';
        if ( ! empty( $item['properties'] ) ) {
            $opf .= ' properties="' . bookcreator_escape_xml( $item['properties'] ) . '"';
        }
        $opf .= " />\n";
    }

    $opf .= "  </manifest>\n";
    $opf .= "  <spine>\n";

    $start_index = 0;
    if ( ! empty( $chapters ) && 'cover' === $chapters[0]['id'] ) {
        $opf .= '    <itemref idref="' . bookcreator_escape_xml( $chapters[0]['id'] ) . '" />\n';
        $start_index = 1;
    }

    $opf .= "    <itemref idref=\"nav\" linear=\"no\" />\n";

    $chapters_count = count( $chapters );
    for ( $i = $start_index; $i < $chapters_count; $i++ ) {
        $chapter = $chapters[ $i ];
        $opf .= '    <itemref idref="' . bookcreator_escape_xml( $chapter['id'] ) . '" />\n';
    }
    $opf .= "  </spine>\n";
    $opf .= "</package>";

    if ( false === file_put_contents( $oebps_dir . '/content.opf', $opf ) ) {
        bookcreator_delete_directory( $temp_dir );

        return new WP_Error( 'bookcreator_epub_write', __( 'Impossibile preparare i file temporanei per l\'ePub.', 'bookcreator' ) );
    }

    $file_slug = sanitize_title( $title );
    if ( ! $file_slug ) {
        $file_slug = 'book-' . $book_id;
    }

    $zip_filename = $file_slug . '.epub';
    $zip_path     = trailingslashit( $base_dir ) . $zip_filename;

    $zip = new ZipArchive();
    if ( true !== $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
        bookcreator_delete_directory( $temp_dir );

        return new WP_Error( 'bookcreator_epub_zip', __( 'Impossibile creare l\'archivio ePub.', 'bookcreator' ) );
    }

    $zip->addFile( trailingslashit( $temp_dir ) . 'mimetype', 'mimetype' );
    $zip->setCompressionName( 'mimetype', ZipArchive::CM_STORE );

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator( $temp_dir, FilesystemIterator::SKIP_DOTS ),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    $base_length = strlen( trailingslashit( $temp_dir ) );
    foreach ( $iterator as $file_info ) {
        if ( $file_info->isDir() ) {
            continue;
        }

        $file_path     = $file_info->getPathname();
        $relative_path = substr( $file_path, $base_length );
        $relative_path = str_replace( '\\', '/', $relative_path );

        if ( 'mimetype' === $relative_path ) {
            continue;
        }

        $zip->addFile( $file_path, $relative_path );
    }

    $zip->close();

    bookcreator_delete_directory( $temp_dir );

    $full_path = $zip_path;
    $url       = trailingslashit( $upload_dir['baseurl'] ) . 'bookcreator-epubs/' . $zip_filename;

    update_post_meta(
        $book_id,
        'bc_epub_file',
        array(
            'file'      => $zip_filename,
            'generated' => current_time( 'mysql' ),
        )
    );

    return array(
        'file' => $zip_filename,
        'path' => $full_path,
        'url'  => $url,
    );
}


function bookcreator_generate_pdf_from_book( $book_id, $template_id = '' ) {
    if ( ! bookcreator_load_mpdf_library() ) {
        return new WP_Error( 'bookcreator_pdf_missing_library', bookcreator_get_pdf_library_error_message() );
    }

    $book_post = get_post( $book_id );
    if ( ! $book_post || 'book_creator' !== $book_post->post_type ) {
        return new WP_Error( 'bookcreator_pdf_invalid_book', __( 'Libro non valido.', 'bookcreator' ) );
    }

    $title = get_the_title( $book_post );

    $template = $template_id ? bookcreator_get_template( $template_id ) : null;
    if ( $template && ( ! isset( $template['type'] ) || 'pdf' !== $template['type'] ) ) {
        return new WP_Error( 'bookcreator_pdf_invalid_template', __( 'Il template selezionato non è valido per i PDF.', 'bookcreator' ) );
    }
    $pdf_settings = $template ? bookcreator_normalize_template_settings( $template['settings'], 'pdf' ) : bookcreator_get_default_template_settings( 'pdf' );

    $subtitle         = get_post_meta( $book_id, 'bc_subtitle', true );
    $description_meta = get_post_meta( $book_id, 'bc_description', true );
    $custom_front     = get_post_meta( $book_id, 'bc_frontispiece', true );
    $publisher        = get_post_meta( $book_id, 'bc_publisher', true );
    $legal_notice     = get_post_meta( $book_id, 'bc_copyright', true );
    $dedication       = get_post_meta( $book_id, 'bc_dedication', true );
    $preface          = get_post_meta( $book_id, 'bc_preface', true );
    $acknowledgments  = get_post_meta( $book_id, 'bc_acknowledgments', true );
    $edition          = get_post_meta( $book_id, 'bc_edition', true );

    $author    = get_post_meta( $book_id, 'bc_author', true );
    $coauthors = get_post_meta( $book_id, 'bc_coauthors', true );

    $publication_raw = get_post_meta( $book_id, 'bc_pub_date', true );
    if ( $publication_raw ) {
        $publication_date = mysql2date( 'Y-m-d', $publication_raw );
    } else {
        $publication_date = gmdate( 'Y-m-d', get_post_time( 'U', true, $book_post ) );
    }

    $isbn = get_post_meta( $book_id, 'bc_isbn', true );

    $css        = bookcreator_get_epub_styles();
    $body_parts = array();

    $cover_id = (int) get_post_meta( $book_id, 'bc_cover', true );
    if ( $cover_id ) {
        $cover_url = wp_get_attachment_url( $cover_id );
        if ( $cover_url ) {
            $body_parts[] = '<div class="bookcreator-cover"><img src="' . esc_url( $cover_url ) . '" alt="' . esc_attr( $title ) . '" /></div>';
        }
    }

    $frontispiece_html  = '<div class="bookcreator-frontispiece">';

    if ( $author || $coauthors ) {
        $frontispiece_html .= '<div class="bookcreator-frontispiece__authors">';

        if ( $author ) {
            $frontispiece_html .= '<p class="bookcreator-frontispiece__field bookcreator-frontispiece__field-bc_author">' . esc_html( $author ) . '</p>';
        }

        if ( $coauthors ) {
            $frontispiece_html .= '<p class="bookcreator-frontispiece__field bookcreator-frontispiece__field-bc_coauthors">' . esc_html( $coauthors ) . '</p>';
        }

        $frontispiece_html .= '</div>';
    }

    $frontispiece_html .= '<h1 class="bookcreator-frontispiece__title bookcreator-book-title">' . esc_html( $title ) . '</h1>';

    if ( $subtitle ) {
        $frontispiece_html .= '<p class="bookcreator-frontispiece__subtitle">' . esc_html( $subtitle ) . '</p>';
    }

    if ( $publisher ) {
        $frontispiece_html .= '<p class="bookcreator-frontispiece__field bookcreator-frontispiece__field-bc_publisher">' . esc_html( $publisher ) . '</p>';
    }

    $language_label = bookcreator_get_language_label( get_post_meta( $book_id, 'bc_language', true ) );
    if ( $language_label ) {
        $frontispiece_html .= '<p class="bookcreator-frontispiece__field bookcreator-frontispiece__field-bc_language">' . esc_html( $language_label ) . '</p>';
    }

    if ( $description_meta ) {
        $frontispiece_html .= '<section class="bookcreator-frontispiece__description">';
        $frontispiece_html .= bookcreator_prepare_epub_content( $description_meta );
        $frontispiece_html .= '</section>';
    }

    if ( $custom_front ) {
        $frontispiece_html .= '<section class="bookcreator-frontispiece__extra">';
        $frontispiece_html .= bookcreator_prepare_epub_content( $custom_front );
        $frontispiece_html .= '</section>';
    }

    $frontispiece_html .= '</div>';
    $body_parts[]        = $frontispiece_html;

    $copyright_items = array();

    if ( $isbn ) {
        $copyright_items[] = array(
            'label' => __( 'ISBN', 'bookcreator' ),
            'value' => $isbn,
        );
    }

    if ( $edition ) {
        $copyright_items[] = array(
            'label' => __( 'Edizione', 'bookcreator' ),
            'value' => $edition,
        );
    }

    if ( $publication_date ) {
        $display_publication_date = mysql2date( get_option( 'date_format' ), $publication_date );
        $copyright_items[]        = array(
            'label' => __( 'Data di pubblicazione', 'bookcreator' ),
            'value' => $display_publication_date,
        );
    }

    if ( $copyright_items || $legal_notice ) {
        $copyright_html  = '<div class="bookcreator-copyright">';
        $copyright_html .= '<h1>' . esc_html__( 'Copyright', 'bookcreator' ) . '</h1>';

        if ( $copyright_items ) {
            $copyright_html .= '<dl class="bookcreator-copyright__meta">';
            foreach ( $copyright_items as $item ) {
                $copyright_html .= '<dt>' . esc_html( $item['label'] ) . '</dt>';
                $copyright_html .= '<dd>' . esc_html( $item['value'] ) . '</dd>';
            }
            $copyright_html .= '</dl>';
        }

        if ( $legal_notice ) {
            $copyright_html .= '<section class="bookcreator-copyright__legal">';
            $copyright_html .= bookcreator_prepare_epub_content( $legal_notice );
            $copyright_html .= '</section>';
        }

        $copyright_html .= '</div>';
        $body_parts[]     = $copyright_html;
    }

    if ( $dedication ) {
        $dedication_html  = '<div class="bookcreator-section bookcreator-section-dedication">';
        $dedication_html .= '<h1>' . esc_html__( 'Dedica', 'bookcreator' ) . '</h1>';
        $dedication_html .= bookcreator_prepare_epub_content( $dedication );
        $dedication_html .= '</div>';
        $body_parts[]      = $dedication_html;
    }

    if ( $preface ) {
        $preface_html  = '<div class="bookcreator-section bookcreator-section-preface">';
        $preface_html .= '<h1>' . esc_html__( 'Prefazione', 'bookcreator' ) . '</h1>';
        $preface_html .= bookcreator_prepare_epub_content( $preface );
        $preface_html .= '</div>';
        $body_parts[]   = $preface_html;
    }

    if ( $acknowledgments ) {
        $ack_html  = '<div class="bookcreator-section bookcreator-section-acknowledgments">';
        $ack_html .= '<h1>' . esc_html__( 'Ringraziamenti', 'bookcreator' ) . '</h1>';
        $ack_html .= bookcreator_prepare_epub_content( $acknowledgments );
        $ack_html .= '</div>';
        $body_parts[] = $ack_html;
    }

    $chapters_posts = bookcreator_get_ordered_chapters_for_book( $book_id );
    if ( $chapters_posts ) {
        foreach ( $chapters_posts as $chapter ) {
            $chapter_title = get_the_title( $chapter );

            $chapter_html  = '<section class="bookcreator-section bookcreator-chapter">';
            $chapter_html .= '<h1>' . esc_html( $chapter_title ) . '</h1>';

            if ( $chapter->post_content ) {
                $chapter_html .= bookcreator_prepare_epub_content( $chapter->post_content );
            }

            $paragraphs = bookcreator_get_ordered_paragraphs_for_chapter( $chapter->ID );
            if ( $paragraphs ) {
                foreach ( $paragraphs as $paragraph ) {
                    $chapter_html .= '<section class="bookcreator-paragraph" id="paragraph-' . esc_attr( $paragraph->ID ) . '">';
                    $chapter_html .= '<h2>' . esc_html( get_the_title( $paragraph ) ) . '</h2>';

                    if ( $paragraph->post_content ) {
                        $chapter_html .= bookcreator_prepare_epub_content( $paragraph->post_content );
                    }

                    $footnotes = get_post_meta( $paragraph->ID, 'bc_footnotes', true );
                    if ( $footnotes ) {
                        $chapter_html .= '<div class="bookcreator-footnotes">';
                        $chapter_html .= '<h3>' . esc_html__( 'Note', 'bookcreator' ) . '</h3>';
                        $chapter_html .= bookcreator_prepare_epub_content( $footnotes );
                        $chapter_html .= '</div>';
                    }

                    $citations = get_post_meta( $paragraph->ID, 'bc_citations', true );
                    if ( $citations ) {
                        $chapter_html .= '<div class="bookcreator-citations">';
                        $chapter_html .= '<h3>' . esc_html__( 'Citazioni', 'bookcreator' ) . '</h3>';
                        $chapter_html .= bookcreator_prepare_epub_content( $citations );
                        $chapter_html .= '</div>';
                    }

                    $chapter_html .= '</section>';
                }
            }

            $chapter_html .= '</section>';
            $body_parts[]   = $chapter_html;
        }
    }

    $final_sections = array(
        'bc_appendix'     => array(
            'title' => __( 'Appendice', 'bookcreator' ),
            'slug'  => 'appendix',
        ),
        'bc_bibliography' => array(
            'title' => __( 'Bibliografia', 'bookcreator' ),
            'slug'  => 'bibliography',
        ),
        'bc_author_note'  => array(
            'title' => __( 'Nota dell\'autore', 'bookcreator' ),
            'slug'  => 'author-note',
        ),
    );

    foreach ( $final_sections as $meta_key => $section ) {
        $content = get_post_meta( $book_id, $meta_key, true );
        if ( ! $content ) {
            continue;
        }

        $section_html  = '<div class="bookcreator-section bookcreator-section-' . esc_attr( $section['slug'] ) . '">';
        $section_html .= '<h1>' . esc_html( $section['title'] ) . '</h1>';
        $section_html .= bookcreator_prepare_epub_content( $content );
        $section_html .= '</div>';
        $body_parts[]   = $section_html;
    }

    $body_html = implode( "\n", $body_parts );

    $upload_dir = wp_upload_dir();
    if ( ! empty( $upload_dir['error'] ) ) {
        return new WP_Error( 'bookcreator_pdf_upload_dir', $upload_dir['error'] );
    }

    $base_dir = trailingslashit( $upload_dir['basedir'] ) . 'bookcreator-pdfs';
    if ( ! wp_mkdir_p( $base_dir ) ) {
        return new WP_Error( 'bookcreator_pdf_directory', __( 'Impossibile creare la cartella per i PDF.', 'bookcreator' ) );
    }

    $file_slug = sanitize_title( $title );
    if ( ! $file_slug ) {
        $file_slug = 'book-' . $book_id;
    }

    $pdf_filename = $file_slug . '.pdf';
    $pdf_path     = trailingslashit( $base_dir ) . $pdf_filename;

    try {
        $mpdf = new \Mpdf\Mpdf(
            array(
                'format'        => $pdf_settings['page_format'],
                'margin_top'    => $pdf_settings['margin_top'],
                'margin_right'  => $pdf_settings['margin_right'],
                'margin_bottom' => $pdf_settings['margin_bottom'],
                'margin_left'   => $pdf_settings['margin_left'],
                'default_font_size' => $pdf_settings['font_size'],
            )
        );
        if ( $title ) {
            $mpdf->SetTitle( $title );
        }
        if ( $author || $coauthors ) {
            $mpdf->SetAuthor( trim( $author . ( $coauthors ? ', ' . $coauthors : '' ) ) );
        }
        $mpdf->WriteHTML( $css, \Mpdf\HTMLParserMode::HEADER_CSS );
        $mpdf->WriteHTML( $body_html, \Mpdf\HTMLParserMode::HTML_BODY );
        $mpdf->Output( $pdf_path, \Mpdf\Output\Destination::FILE );
    } catch ( \Throwable $exception ) {
        $raw_message       = $exception->getMessage();
        $sanitized_message = $raw_message ? wp_strip_all_tags( $raw_message ) : '';

        if ( $exception instanceof \Mpdf\MpdfException ) {
            $message = $sanitized_message ? $sanitized_message : __( 'Errore durante la generazione del PDF.', 'bookcreator' );
        } else {
            if ( $sanitized_message ) {
                /* translators: %s: PDF generation error message. */
                $message = sprintf( __( 'Errore inatteso durante la generazione del PDF: %s', 'bookcreator' ), $sanitized_message );
            } else {
                $message = __( 'Errore inatteso durante la generazione del PDF.', 'bookcreator' );
            }

            error_log( sprintf( 'BookCreator PDF generation error: %s', $raw_message ) );
        }

        return new WP_Error( 'bookcreator_pdf_write', $message );
    }

    $pdf_url = trailingslashit( $upload_dir['baseurl'] ) . 'bookcreator-pdfs/' . $pdf_filename;

    update_post_meta(
        $book_id,
        'bc_pdf_file',
        array(
            'file'      => $pdf_filename,
            'generated' => current_time( 'mysql' ),
        )
    );

    return array(
        'file' => $pdf_filename,
        'path' => $pdf_path,
        'url'  => $pdf_url,
    );
}

function bookcreator_get_translation_prompt_example() {
    return __( 'Traduci il contenuto seguente in {{LINGUA_TARGET}} mantenendo invariati i tag HTML, gli attributi e la struttura. Mantieni i marcatori [SECTION_*_START] e [SECTION_*_END] esattamente come forniti.', 'bookcreator' );
}

function bookcreator_translate_book_with_claude( $book_id, $args = array() ) {
    if ( ! bookcreator_is_claude_enabled() ) {
        return new WP_Error( 'bookcreator_translation_claude_disabled', __( 'Configura l\'integrazione con Claude AI prima di eseguire una traduzione.', 'bookcreator' ) );
    }

    $defaults = array(
        'target_language'  => '',
        'prompt'           => '',
        'translated_title' => '',
        'template_id'      => '',
    );

    $args = wp_parse_args( $args, $defaults );

    $book_id = (int) $book_id;
    if ( $book_id <= 0 ) {
        return new WP_Error( 'bookcreator_translation_invalid_book', __( 'Libro non valido.', 'bookcreator' ) );
    }

    $book_post = get_post( $book_id );
    if ( ! $book_post || 'book_creator' !== $book_post->post_type ) {
        return new WP_Error( 'bookcreator_translation_invalid_book', __( 'Libro non valido.', 'bookcreator' ) );
    }

    $target_language = trim( (string) $args['target_language'] );
    if ( '' === $target_language ) {
        return new WP_Error( 'bookcreator_translation_missing_language', __( 'Inserisci la lingua di destinazione.', 'bookcreator' ) );
    }

    $target_language_sanitized = strtolower( $target_language );
    $target_language_sanitized = str_replace( array( ' ', '_' ), '-', $target_language_sanitized );
    $target_language_sanitized = preg_replace( '/[^a-z0-9\-]/', '', $target_language_sanitized );
    if ( '' === $target_language_sanitized ) {
        return new WP_Error( 'bookcreator_translation_invalid_language', __( 'La lingua di destinazione non è valida.', 'bookcreator' ) );
    }

    $prompt           = isset( $args['prompt'] ) ? trim( (string) $args['prompt'] ) : '';
    $translated_title = isset( $args['translated_title'] ) ? trim( (string) $args['translated_title'] ) : '';
    $template_id      = isset( $args['template_id'] ) ? (string) $args['template_id'] : '';

    $claude_settings = bookcreator_get_claude_settings();
    $model           = isset( $claude_settings['default_model'] ) ? $claude_settings['default_model'] : 'claude-3-opus-20240229';
    $timeout         = isset( $claude_settings['request_timeout'] ) ? (int) $claude_settings['request_timeout'] : 30;
    $api_key         = bookcreator_get_claude_api_key();

    if ( empty( $api_key ) ) {
        return new WP_Error( 'bookcreator_translation_missing_api_key', __( 'API key di Claude mancante.', 'bookcreator' ) );
    }

    $epub_result = bookcreator_create_epub_from_book( $book_id, $template_id );
    if ( is_wp_error( $epub_result ) ) {
        return $epub_result;
    }

    if ( empty( $epub_result['path'] ) || ! file_exists( $epub_result['path'] ) ) {
        return new WP_Error( 'bookcreator_translation_epub_missing', __( 'Impossibile individuare il file ePub di origine.', 'bookcreator' ) );
    }

    $zip = new ZipArchive();
    if ( true !== $zip->open( $epub_result['path'] ) ) {
        return new WP_Error( 'bookcreator_translation_epub_open', __( 'Impossibile aprire l\'archivio ePub di origine.', 'bookcreator' ) );
    }

    $extract_dir = trailingslashit( get_temp_dir() ) . 'bookcreator-translation-' . wp_generate_uuid4();
    if ( ! wp_mkdir_p( $extract_dir ) ) {
        $zip->close();
        return new WP_Error( 'bookcreator_translation_extract', __( 'Impossibile preparare la cartella temporanea per la traduzione.', 'bookcreator' ) );
    }

    if ( ! $zip->extractTo( $extract_dir ) ) {
        $zip->close();
        bookcreator_delete_directory( $extract_dir );
        return new WP_Error( 'bookcreator_translation_extract', __( 'Impossibile estrarre i contenuti dell\'ePub.', 'bookcreator' ) );
    }

    $zip->close();

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator( $extract_dir, FilesystemIterator::SKIP_DOTS ),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    $text_files = array();
    $base_length = strlen( trailingslashit( $extract_dir ) );
    foreach ( $iterator as $file_info ) {
        if ( $file_info->isDir() ) {
            continue;
        }

        $extension = strtolower( $file_info->getExtension() );
        if ( ! in_array( $extension, array( 'xhtml', 'html', 'htm' ), true ) ) {
            continue;
        }

        $relative_path = substr( $file_info->getPathname(), $base_length );
        $relative_path = str_replace( '\\', '/', $relative_path );

        if ( false !== strpos( $relative_path, '/styles/' ) || false !== strpos( $relative_path, '/css/' ) ) {
            continue;
        }

        if ( preg_match( '/\.(css|js)$/i', $relative_path ) ) {
            continue;
        }

        if ( false !== strpos( $relative_path, 'META-INF' ) || false !== strpos( $relative_path, 'content.opf' ) || false !== strpos( $relative_path, 'toc.ncx' ) ) {
            continue;
        }

        $text_files[] = $relative_path;
    }

    $text_files = array_values( array_unique( $text_files ) );
    sort( $text_files );

    if ( ! $text_files ) {
        bookcreator_delete_directory( $extract_dir );
        return new WP_Error( 'bookcreator_translation_no_sections', __( 'Impossibile individuare i file di testo da tradurre.', 'bookcreator' ) );
    }

    $sections = array();
    $index    = 1;

    foreach ( $text_files as $relative_path ) {
        $absolute_path = trailingslashit( $extract_dir ) . $relative_path;
        if ( ! file_exists( $absolute_path ) ) {
            continue;
        }

        $contents = file_get_contents( $absolute_path );
        if ( false === $contents ) {
            bookcreator_delete_directory( $extract_dir );
            return new WP_Error( 'bookcreator_translation_read', sprintf( __( 'Impossibile leggere il file %s.', 'bookcreator' ), $relative_path ) );
        }

        if ( ! preg_match( '/(<body[^>]*>)(.*)(<\/body>)/is', $contents, $matches ) ) {
            continue;
        }

        $body_open  = $matches[1];
        $body_inner = $matches[2];
        $body_close = $matches[3];

        $before_body = substr( $contents, 0, strpos( $contents, $matches[0] ) );
        $after_body  = substr( $contents, strpos( $contents, $matches[0] ) + strlen( $matches[0] ) );

        $marker = 'SECTION_' . str_pad( (string) $index, 3, '0', STR_PAD_LEFT );
        $index++;

        $sections[] = array(
            'marker'      => $marker,
            'path'        => $relative_path,
            'before_body' => $before_body,
            'body_open'   => $body_open,
            'body_inner'  => $body_inner,
            'body_close'  => $body_close,
            'after_body'  => $after_body,
        );
    }

    if ( ! $sections ) {
        bookcreator_delete_directory( $extract_dir );
        return new WP_Error( 'bookcreator_translation_no_sections', __( 'Non sono stati trovati contenuti testuali da tradurre.', 'bookcreator' ) );
    }

    $max_chunks     = 3;
    $chunk_limit    = 60000;
    $chunks         = array();
    $current_text   = '';
    $current_indices = array();

    foreach ( $sections as $section_index => $section ) {
        $section_text  = '[' . $section['marker'] . '_START path="' . $section['path'] . '"]' . "\n";
        $section_text .= trim( $section['body_inner'] ) . "\n";
        $section_text .= '[' . $section['marker'] . '_END]';

        $section_text_with_spacing = $section_text . "\n\n";

        if ( $current_text && ( strlen( $current_text ) + strlen( $section_text_with_spacing ) > $chunk_limit ) && count( $chunks ) < ( $max_chunks - 1 ) ) {
            $chunks[] = array(
                'text'     => $current_text,
                'indices'  => $current_indices,
            );
            $current_text    = '';
            $current_indices = array();
        }

        $current_text    .= $section_text_with_spacing;
        $current_indices[] = $section_index;
    }

    if ( $current_text ) {
        $chunks[] = array(
            'text'    => $current_text,
            'indices' => $current_indices,
        );
    }

    if ( ! $chunks ) {
        bookcreator_delete_directory( $extract_dir );
        return new WP_Error( 'bookcreator_translation_chunking', __( 'Errore nella preparazione dei contenuti da tradurre.', 'bookcreator' ) );
    }

    $translations = array();
    $total_chunks = count( $chunks );

    foreach ( $chunks as $chunk_position => $chunk ) {
        $chunk_text = trim( $chunk['text'] );

        $instructions  = __( 'Sei un assistente di traduzione per ePub. Traduci il testo nella lingua richiesta mantenendo intatta la struttura HTML.', 'bookcreator' ) . "\n";
        $instructions .= sprintf( __( 'Lingua di destinazione: %s', 'bookcreator' ), $target_language_sanitized ) . "\n";
        $instructions .= __( 'Mantieni invariati i marcatori [SECTION_*_START] e [SECTION_*_END] e restituiscili insieme al contenuto tradotto.', 'bookcreator' ) . "\n";
        $instructions .= __( 'Conserva i tag, gli attributi e le entità HTML, traducendo solo il testo leggibile.', 'bookcreator' ) . "\n";
        $instructions .= __( 'Non aggiungere testo fuori dai marcatori.', 'bookcreator' ) . "\n";

        if ( $prompt ) {
            $instructions .= "\n" . __( 'Istruzioni aggiuntive:', 'bookcreator' ) . "\n" . $prompt . "\n";
        }

        if ( $total_chunks > 1 ) {
            /* translators: 1: current chunk number, 2: total chunk number. */
            $instructions .= sprintf( __( 'Parte %1$d di %2$d del contenuto del libro.', 'bookcreator' ), $chunk_position + 1, $total_chunks ) . "\n";
        }

        $full_prompt = $instructions . "\n" . $chunk_text;

        $response = wp_remote_post(
            'https://api.anthropic.com/v1/messages',
            array(
                'timeout' => max( 5, min( 120, $timeout ) ),
                'headers' => array(
                    'x-api-key'         => $api_key,
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                    'accept'            => 'application/json',
                ),
                'body'    => wp_json_encode(
                    array(
                        'model'      => $model,
                        'max_tokens' => 4096,
                        'messages'   => array(
                            array(
                                'role'    => 'user',
                                'content' => $full_prompt,
                            ),
                        ),
                    )
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            bookcreator_delete_directory( $extract_dir );
            return new WP_Error( 'bookcreator_translation_request', sprintf( __( 'Errore durante la chiamata a Claude: %s', 'bookcreator' ), $response->get_error_message() ) );
        }

        $status_code = (int) wp_remote_retrieve_response_code( $response );
        $body_raw    = wp_remote_retrieve_body( $response );

        if ( 200 !== $status_code ) {
            bookcreator_delete_directory( $extract_dir );
            return new WP_Error( 'bookcreator_translation_response', sprintf( __( 'Claude ha restituito un errore (%d): %s', 'bookcreator' ), $status_code, $body_raw ) );
        }

        $body_data = json_decode( $body_raw, true );
        if ( ! is_array( $body_data ) || empty( $body_data['content'] ) || ! is_array( $body_data['content'] ) ) {
            bookcreator_delete_directory( $extract_dir );
            return new WP_Error( 'bookcreator_translation_response', __( 'Risposta inattesa da Claude AI.', 'bookcreator' ) );
        }

        $text_response = '';
        foreach ( $body_data['content'] as $segment ) {
            if ( isset( $segment['type'] ) && 'text' === $segment['type'] && isset( $segment['text'] ) ) {
                $text_response .= $segment['text'];
            }
        }

        if ( '' === $text_response ) {
            bookcreator_delete_directory( $extract_dir );
            return new WP_Error( 'bookcreator_translation_empty', __( 'La risposta di Claude non contiene il testo tradotto.', 'bookcreator' ) );
        }

        foreach ( $chunk['indices'] as $section_index ) {
            $section = $sections[ $section_index ];
            $pattern = '/\[' . preg_quote( $section['marker'] . '_START', '/' ) . '[^\]]*\](.*?)\[' . preg_quote( $section['marker'] . '_END', '/' ) . '\]/is';
            if ( ! preg_match( $pattern, $text_response, $matches ) ) {
                bookcreator_delete_directory( $extract_dir );
                return new WP_Error( 'bookcreator_translation_missing_section', sprintf( __( 'La risposta di Claude non contiene il marcatore %s.', 'bookcreator' ), $section['marker'] ) );
            }

            $translations[ $section_index ] = trim( $matches[1] );
        }
    }

    foreach ( $sections as $section_index => $section ) {
        if ( ! isset( $translations[ $section_index ] ) ) {
            bookcreator_delete_directory( $extract_dir );
            return new WP_Error( 'bookcreator_translation_missing_section', __( 'Alcune sezioni non sono state tradotte.', 'bookcreator' ) );
        }

        $translated_body = $translations[ $section_index ];

        $new_contents = $section['before_body'] . $section['body_open'] . $translated_body . $section['body_close'] . $section['after_body'];

        if ( $target_language_sanitized ) {
            $new_contents = preg_replace( '/xml:lang="[^"]*"/i', 'xml:lang="' . esc_attr( $target_language_sanitized ) . '"', $new_contents, 1, $xml_lang_replaced );
            if ( ! $xml_lang_replaced ) {
                $new_contents = preg_replace( '/<html(?![^>]*xml:lang)/i', '<html xml:lang="' . esc_attr( $target_language_sanitized ) . '"', $new_contents, 1 );
            }

            $new_contents = preg_replace( '/lang="[^"]*"/i', 'lang="' . esc_attr( $target_language_sanitized ) . '"', $new_contents, 1, $lang_replaced );
            if ( ! $lang_replaced ) {
                $new_contents = preg_replace( '/<html(?![^>]*lang)/i', '<html lang="' . esc_attr( $target_language_sanitized ) . '"', $new_contents, 1 );
            }
        }

        $absolute_path = trailingslashit( $extract_dir ) . $section['path'];
        if ( false === file_put_contents( $absolute_path, $new_contents ) ) {
            bookcreator_delete_directory( $extract_dir );
            return new WP_Error( 'bookcreator_translation_write', sprintf( __( 'Impossibile scrivere il file tradotto %s.', 'bookcreator' ), $section['path'] ) );
        }
    }

    $opf_path = trailingslashit( $extract_dir ) . 'OEBPS/content.opf';
    if ( file_exists( $opf_path ) ) {
        $opf_contents = file_get_contents( $opf_path );
        if ( false !== $opf_contents ) {
            if ( $target_language_sanitized ) {
                $language_tag = '<dc:language>' . bookcreator_escape_xml( $target_language_sanitized ) . '</dc:language>';
                if ( preg_match( '/<dc:language>.*?<\/dc:language>/is', $opf_contents ) ) {
                    $opf_contents = preg_replace( '/<dc:language>.*?<\/dc:language>/is', $language_tag, $opf_contents, 1 );
                } else {
                    $opf_contents = preg_replace( '/<metadata[^>]*>/i', '$0' . "\n    " . $language_tag, $opf_contents, 1 );
                }
            }

            if ( $translated_title ) {
                $title_tag = '<dc:title>' . bookcreator_escape_xml( $translated_title ) . '</dc:title>';
                if ( preg_match( '/<dc:title>.*?<\/dc:title>/is', $opf_contents ) ) {
                    $opf_contents = preg_replace( '/<dc:title>.*?<\/dc:title>/is', $title_tag, $opf_contents, 1 );
                }
            }

            file_put_contents( $opf_path, $opf_contents );
        }
    }

    $upload_dir = wp_upload_dir();
    if ( ! empty( $upload_dir['error'] ) ) {
        bookcreator_delete_directory( $extract_dir );
        return new WP_Error( 'bookcreator_translation_upload_dir', $upload_dir['error'] );
    }

    $base_dir = trailingslashit( $upload_dir['basedir'] ) . 'bookcreator-epubs';
    if ( ! wp_mkdir_p( $base_dir ) ) {
        bookcreator_delete_directory( $extract_dir );
        return new WP_Error( 'bookcreator_translation_directory', __( 'Impossibile creare la cartella di destinazione per gli ePub tradotti.', 'bookcreator' ) );
    }

    $book_title = get_the_title( $book_post );
    $file_slug  = sanitize_title( $book_title );
    if ( ! $file_slug ) {
        $file_slug = 'book-' . $book_id;
    }

    $translated_filename = sanitize_file_name( $file_slug . '-' . $target_language_sanitized . '.epub' );
    $translated_path     = trailingslashit( $base_dir ) . $translated_filename;

    $zip = new ZipArchive();
    if ( true !== $zip->open( $translated_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
        bookcreator_delete_directory( $extract_dir );
        return new WP_Error( 'bookcreator_translation_zip', __( 'Impossibile creare l\'ePub tradotto.', 'bookcreator' ) );
    }

    $zip->addFile( trailingslashit( $extract_dir ) . 'mimetype', 'mimetype' );
    $zip->setCompressionName( 'mimetype', ZipArchive::CM_STORE );

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator( $extract_dir, FilesystemIterator::SKIP_DOTS ),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ( $iterator as $file_info ) {
        if ( $file_info->isDir() ) {
            continue;
        }

        $file_path     = $file_info->getPathname();
        $relative_path = substr( $file_path, $base_length );
        $relative_path = str_replace( '\\', '/', $relative_path );

        if ( 'mimetype' === $relative_path ) {
            continue;
        }

        $zip->addFile( $file_path, $relative_path );
    }

    $zip->close();

    bookcreator_delete_directory( $extract_dir );

    $url = trailingslashit( $upload_dir['baseurl'] ) . 'bookcreator-epubs/' . $translated_filename;

    $translations_meta = get_post_meta( $book_id, 'bc_translated_epubs', true );
    if ( ! is_array( $translations_meta ) ) {
        $translations_meta = array();
    }

    $translations_meta = array_values(
        array_filter(
            $translations_meta,
            static function ( $entry ) use ( $target_language_sanitized, $translated_filename ) {
                if ( ! is_array( $entry ) ) {
                    return false;
                }

                if ( isset( $entry['file'] ) && $entry['file'] === $translated_filename ) {
                    return false;
                }

                if ( isset( $entry['language'] ) && $entry['language'] === $target_language_sanitized ) {
                    return false;
                }

                return true;
            }
        )
    );

    $translations_meta[] = array(
        'language'        => $target_language_sanitized,
        'file'            => $translated_filename,
        'url'             => $url,
        'generated'       => current_time( 'mysql' ),
        'model'           => $model,
        'translated_title'=> $translated_title,
    );

    update_post_meta( $book_id, 'bc_translated_epubs', $translations_meta );

    return array(
        'file'      => $translated_filename,
        'path'      => $translated_path,
        'url'       => $url,
        'language'  => $target_language_sanitized,
        'title'     => $translated_title,
        'model'     => $model,
    );
}

function bookcreator_render_translation_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $books = get_posts(
        array(
            'post_type'   => 'book_creator',
            'numberposts' => -1,
            'post_status' => array( 'publish', 'draft', 'private' ),
        )
    );

    $selected_book = isset( $_POST['book_id'] ) ? (int) $_POST['book_id'] : 0;
    $target_lang   = isset( $_POST['translation_target_language'] ) ? sanitize_text_field( wp_unslash( $_POST['translation_target_language'] ) ) : '';
    $translated_title = isset( $_POST['translation_title'] ) ? sanitize_text_field( wp_unslash( $_POST['translation_title'] ) ) : '';
    $prompt_value  = isset( $_POST['translation_prompt'] ) ? trim( (string) wp_unslash( $_POST['translation_prompt'] ) ) : '';

    $notice = '';
    $notice_class = '';

    if ( isset( $_POST['bookcreator_translate_book'] ) ) {
        check_admin_referer( 'bookcreator_translate_book', 'bookcreator_translate_book_nonce' );

        $template_id = isset( $_POST['translation_template'] ) ? sanitize_text_field( wp_unslash( $_POST['translation_template'] ) ) : '';

        $result = bookcreator_translate_book_with_claude(
            $selected_book,
            array(
                'target_language'  => $target_lang,
                'prompt'           => $prompt_value,
                'translated_title' => $translated_title,
                'template_id'      => $template_id,
            )
        );

        if ( is_wp_error( $result ) ) {
            $notice       = $result->get_error_message();
            $notice_class = 'notice notice-error';
        } else {
            $notice       = sprintf( __( 'Traduzione completata. File generato: %s', 'bookcreator' ), $result['file'] );
            $notice_class = 'notice notice-success';
        }
    }

    $claude_enabled = bookcreator_is_claude_enabled();

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__( 'Gestione traduzioni', 'bookcreator' ) . '</h1>';

    if ( ! $claude_enabled ) {
        echo '<div class="notice notice-warning"><p>' . esc_html__( 'Configura le impostazioni di Claude AI per abilitare le traduzioni automatiche.', 'bookcreator' ) . '</p></div>';
    }

    if ( $notice ) {
        echo '<div class="' . esc_attr( $notice_class ) . '"><p>' . esc_html( $notice ) . '</p></div>';
    }

    if ( ! $books ) {
        echo '<p>' . esc_html__( 'Nessun libro disponibile.', 'bookcreator' ) . '</p>';
        echo '</div>';
        return;
    }

    $epub_templates = bookcreator_get_templates_by_type( 'epub' );

    echo '<form method="post" class="bookcreator-translation-form">';
    wp_nonce_field( 'bookcreator_translate_book', 'bookcreator_translate_book_nonce' );

    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th scope="row"><label for="bookcreator_translation_book">' . esc_html__( 'Libro da tradurre', 'bookcreator' ) . '</label></th>';
    echo '<td>';
    echo '<select name="book_id" id="bookcreator_translation_book">';
    echo '<option value="">' . esc_html__( 'Seleziona un libro', 'bookcreator' ) . '</option>';
    foreach ( $books as $book ) {
        $selected = selected( $selected_book, $book->ID, false );
        echo '<option value="' . esc_attr( $book->ID ) . '"' . $selected . '>' . esc_html( get_the_title( $book ) ) . '</option>';
    }
    echo '</select>';
    echo '</td>';
    echo '</tr>';

    echo '<tr>';
    echo '<th scope="row"><label for="bookcreator_translation_template">' . esc_html__( 'Template ePub di riferimento', 'bookcreator' ) . '</label></th>';
    echo '<td>';
    echo '<select name="translation_template" id="bookcreator_translation_template">';
    echo '<option value="">' . esc_html__( 'Template predefinito', 'bookcreator' ) . '</option>';
    foreach ( $epub_templates as $template ) {
        $selected = selected( isset( $_POST['translation_template'] ) ? sanitize_text_field( wp_unslash( $_POST['translation_template'] ) ) : '', $template['id'], false );
        echo '<option value="' . esc_attr( $template['id'] ) . '"' . $selected . '>' . esc_html( $template['name'] ) . '</option>';
    }
    echo '</select>';
    echo '</td>';
    echo '</tr>';

    echo '<tr>';
    echo '<th scope="row"><label for="bookcreator_translation_language">' . esc_html__( 'Lingua di destinazione', 'bookcreator' ) . '</label></th>';
    echo '<td><input type="text" name="translation_target_language" id="bookcreator_translation_language" value="' . esc_attr( $target_lang ) . '" class="regular-text" placeholder="es. en, es, fr" /></td>';
    echo '</tr>';

    echo '<tr>';
    echo '<th scope="row"><label for="bookcreator_translation_title">' . esc_html__( 'Titolo tradotto (opzionale)', 'bookcreator' ) . '</label></th>';
    echo '<td><input type="text" name="translation_title" id="bookcreator_translation_title" value="' . esc_attr( $translated_title ) . '" class="regular-text" /></td>';
    echo '</tr>';

    $prompt_placeholder = bookcreator_get_translation_prompt_example();
    if ( ! $prompt_value ) {
        $prompt_value = $prompt_placeholder;
    }

    echo '<tr>';
    echo '<th scope="row"><label for="bookcreator_translation_prompt">' . esc_html__( 'Istruzioni per Claude', 'bookcreator' ) . '</label></th>';
    echo '<td>';
    echo '<textarea name="translation_prompt" id="bookcreator_translation_prompt" rows="6" class="large-text" placeholder="' . esc_attr( $prompt_placeholder ) . '">' . esc_textarea( $prompt_value ) . '</textarea>';
    echo '<p class="description">' . esc_html__( 'Personalizza le istruzioni per definire tono, terminologia e richieste aggiuntive per la lingua selezionata.', 'bookcreator' ) . '</p>';
    echo '</td>';
    echo '</tr>';

    echo '</table>';

    submit_button( __( 'Traduci con Claude', 'bookcreator' ), 'primary', 'bookcreator_translate_book', false, $claude_enabled ? array() : array( 'disabled' => 'disabled' ) );

    echo '</form>';

    echo '<h2>' . esc_html__( 'ePub tradotti', 'bookcreator' ) . '</h2>';

    echo '<table class="widefat striped">';
    echo '<thead><tr>';
    echo '<th scope="col">' . esc_html__( 'Libro', 'bookcreator' ) . '</th>';
    echo '<th scope="col">' . esc_html__( 'Lingua', 'bookcreator' ) . '</th>';
    echo '<th scope="col">' . esc_html__( 'Titolo tradotto', 'bookcreator' ) . '</th>';
    echo '<th scope="col">' . esc_html__( 'Ultima generazione', 'bookcreator' ) . '</th>';
    echo '<th scope="col">' . esc_html__( 'File', 'bookcreator' ) . '</th>';
    echo '</tr></thead>';
    echo '<tbody>';

    $upload_dir = wp_upload_dir();
    $epub_base_dir = trailingslashit( $upload_dir['basedir'] ) . 'bookcreator-epubs/';
    $epub_base_url = trailingslashit( $upload_dir['baseurl'] ) . 'bookcreator-epubs/';

    foreach ( $books as $book ) {
        $translations_meta = get_post_meta( $book->ID, 'bc_translated_epubs', true );
        if ( ! is_array( $translations_meta ) || ! $translations_meta ) {
            echo '<tr>';
            echo '<td>' . esc_html( get_the_title( $book ) ) . '</td>';
            echo '<td colspan="4">' . esc_html__( 'Nessuna traduzione disponibile.', 'bookcreator' ) . '</td>';
            echo '</tr>';
            continue;
        }

        foreach ( $translations_meta as $translation ) {
            $file_name = isset( $translation['file'] ) ? $translation['file'] : '';
            $file_path = $file_name ? $epub_base_dir . $file_name : '';
            $file_url  = $file_name ? $epub_base_url . $file_name : '';

            $file_cell = esc_html__( 'File mancante', 'bookcreator' );
            if ( $file_path && file_exists( $file_path ) ) {
                $file_cell = '<a href="' . esc_url( $file_url ) . '" target="_blank" rel="noopener">' . esc_html( $file_name ) . '</a>';
            }

            $generated = isset( $translation['generated'] ) ? $translation['generated'] : '';
            if ( $generated ) {
                $generated = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $generated ) );
            } else {
                $generated = '—';
            }

            echo '<tr>';
            echo '<td>' . esc_html( get_the_title( $book ) ) . '</td>';
            echo '<td>' . esc_html( isset( $translation['language'] ) ? $translation['language'] : '' ) . '</td>';
            echo '<td>' . esc_html( isset( $translation['translated_title'] ) ? $translation['translated_title'] : '' ) . '</td>';
            echo '<td>' . esc_html( $generated ) . '</td>';
            echo '<td>' . $file_cell . '</td>';
            echo '</tr>';
        }
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

function bookcreator_register_translation_page() {
    add_submenu_page(
        'edit.php?post_type=book_creator',
        __( 'Gestione traduzioni', 'bookcreator' ),
        __( 'Gestione traduzioni', 'bookcreator' ),
        'manage_options',
        'bc-translation-manager',
        'bookcreator_render_translation_page'
    );
}
add_action( 'admin_menu', 'bookcreator_register_translation_page' );


function bookcreator_handle_generate_exports_action() {
    $is_epub = isset( $_POST['bookcreator_generate_epub'] );
    $is_pdf  = isset( $_POST['bookcreator_generate_pdf'] );

    if ( ! $is_epub && ! $is_pdf ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( ! isset( $_POST['bookcreator_generate_exports_nonce'] ) ) {
        return;
    }

    check_admin_referer( 'bookcreator_generate_exports', 'bookcreator_generate_exports_nonce' );

    $book_id = isset( $_POST['book_id'] ) ? absint( wp_unslash( $_POST['book_id'] ) ) : 0;
    if ( ! $book_id ) {
        return;
    }

    $templates = bookcreator_get_templates();
    $field     = $is_epub ? 'book_template_epub' : 'book_template_pdf';
    $expected  = $is_epub ? 'epub' : 'pdf';
    $meta_key  = $is_epub ? 'bc_last_template_epub' : 'bc_last_template_pdf';

    $template_id = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';
    if ( $template_id && ( ! isset( $templates[ $template_id ] ) || $templates[ $template_id ]['type'] !== $expected ) ) {
        $template_id = '';
    }

    if ( $template_id ) {
        update_post_meta( $book_id, $meta_key, $template_id );
    } else {
        delete_post_meta( $book_id, $meta_key );
    }

    // Rimuove il vecchio meta non più utilizzato.
    delete_post_meta( $book_id, 'bc_last_template' );

    if ( $is_epub ) {
        $result  = bookcreator_create_epub_from_book( $book_id, $template_id );
        $context = 'epub';
    } else {
        $result  = bookcreator_generate_pdf_from_book( $book_id, $template_id );
        $context = 'pdf';
    }

    if ( is_wp_error( $result ) ) {
        $status  = 'error';
        $message = $result->get_error_message();
    } else {
        $status  = 'success';
        if ( 'pdf' === $context ) {
            /* translators: %s: PDF filename. */
            $message = sprintf( __( 'PDF creato correttamente: %s', 'bookcreator' ), $result['file'] );
        } else {
            /* translators: %s: ePub filename. */
            $message = sprintf( __( 'ePub creato correttamente: %s', 'bookcreator' ), $result['file'] );
        }
    }

    $redirect = add_query_arg(
        array(
            'post_type'       => 'book_creator',
            'page'            => 'bc-generate-epub',
            'bc_export_status'  => $status,
            'bc_export_message' => rawurlencode( $message ),
        ),
        admin_url( 'edit.php' )
    );

    wp_safe_redirect( $redirect );
    exit;
}
add_action( 'admin_init', 'bookcreator_handle_generate_exports_action' );

function bookcreator_generate_exports_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $epub_library_available = bookcreator_is_epub_library_available();
    $pdf_library_available  = bookcreator_is_pdf_library_available();

    $books = get_posts(
        array(
            'post_type'   => 'book_creator',
            'numberposts' => -1,
            'post_status' => array( 'publish', 'draft', 'private' ),
        )
    );

    $epub_templates = bookcreator_get_templates_by_type( 'epub' );
    $pdf_templates  = bookcreator_get_templates_by_type( 'pdf' );

    $upload_dir     = wp_upload_dir();
    $epub_base_url  = trailingslashit( $upload_dir['baseurl'] ) . 'bookcreator-epubs/';
    $epub_base_dir  = trailingslashit( $upload_dir['basedir'] ) . 'bookcreator-epubs/';
    $pdf_base_url   = trailingslashit( $upload_dir['baseurl'] ) . 'bookcreator-pdfs/';
    $pdf_base_dir   = trailingslashit( $upload_dir['basedir'] ) . 'bookcreator-pdfs/';

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__( 'Genera ePub/PDF', 'bookcreator' ) . '</h1>';

    if ( ! $epub_library_available ) {
        echo '<div class="notice notice-warning"><p>' . bookcreator_get_epub_library_notice_markup() . '</p></div>';
    }

    if ( ! $pdf_library_available ) {
        echo '<div class="notice notice-warning"><p>' . bookcreator_get_pdf_library_notice_markup() . '</p></div>';
    }

    if ( isset( $_GET['bc_export_status'], $_GET['bc_export_message'] ) ) {
        $status  = sanitize_key( wp_unslash( $_GET['bc_export_status'] ) );
        $message = sanitize_text_field( rawurldecode( wp_unslash( $_GET['bc_export_message'] ) ) );
        $class   = ( 'error' === $status ) ? 'notice notice-error is-dismissible' : 'notice notice-success is-dismissible';
        echo '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( $message ) . '</p></div>';
    }

    if ( ! $books ) {
        echo '<p>' . esc_html__( 'Nessun libro disponibile.', 'bookcreator' ) . '</p>';
        echo '</div>';
        return;
    }

    echo '<table class="widefat striped">';
    echo '<thead><tr>';
    echo '<th scope="col">' . esc_html__( 'Libro', 'bookcreator' ) . '</th>';
    echo '<th scope="col">' . esc_html__( 'Ultima generazione ePub', 'bookcreator' ) . '</th>';
    echo '<th scope="col">' . esc_html__( 'File ePub', 'bookcreator' ) . '</th>';
    echo '<th scope="col">' . esc_html__( 'Ultima generazione PDF', 'bookcreator' ) . '</th>';
    echo '<th scope="col">' . esc_html__( 'File PDF', 'bookcreator' ) . '</th>';
    echo '<th scope="col" class="column-actions">' . esc_html__( 'Azioni', 'bookcreator' ) . '</th>';
    echo '</tr></thead>';
    echo '<tbody>';

    foreach ( $books as $book ) {
        $epub_meta      = get_post_meta( $book->ID, 'bc_epub_file', true );
        $epub_generated = '—';
        $epub_file_cell = '—';

        if ( is_array( $epub_meta ) && ! empty( $epub_meta['file'] ) ) {
            $epub_file_path = $epub_base_dir . $epub_meta['file'];
            if ( file_exists( $epub_file_path ) ) {
                $epub_file_url  = $epub_base_url . $epub_meta['file'];
                $epub_file_cell = '<a href="' . esc_url( $epub_file_url ) . '" target="_blank" rel="noopener">' . esc_html( $epub_meta['file'] ) . '</a>';
                if ( ! empty( $epub_meta['generated'] ) ) {
                    $epub_generated = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $epub_meta['generated'] ) );
                }
            } else {
                $epub_file_cell = esc_html__( 'File mancante', 'bookcreator' );
            }
        }

        $pdf_meta      = get_post_meta( $book->ID, 'bc_pdf_file', true );
        $pdf_generated = '—';
        $pdf_file_cell = '—';

        if ( is_array( $pdf_meta ) && ! empty( $pdf_meta['file'] ) ) {
            $pdf_file_path = $pdf_base_dir . $pdf_meta['file'];
            if ( file_exists( $pdf_file_path ) ) {
                $pdf_file_url  = $pdf_base_url . $pdf_meta['file'];
                $pdf_file_cell = '<a href="' . esc_url( $pdf_file_url ) . '" target="_blank" rel="noopener">' . esc_html( $pdf_meta['file'] ) . '</a>';
                if ( ! empty( $pdf_meta['generated'] ) ) {
                    $pdf_generated = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $pdf_meta['generated'] ) );
                }
            } else {
                $pdf_file_cell = esc_html__( 'File mancante', 'bookcreator' );
            }
        }

        echo '<tr>';
        echo '<td>' . esc_html( get_the_title( $book ) ) . '</td>';
        echo '<td>' . esc_html( $epub_generated ) . '</td>';
        echo '<td>' . $epub_file_cell . '</td>';
        echo '<td>' . esc_html( $pdf_generated ) . '</td>';
        echo '<td>' . $pdf_file_cell . '</td>';
        echo '<td>';
        echo '<form method="post">';
        wp_nonce_field( 'bookcreator_generate_exports', 'bookcreator_generate_exports_nonce' );
        echo '<input type="hidden" name="book_id" value="' . esc_attr( $book->ID ) . '" />';
        $last_template_epub = get_post_meta( $book->ID, 'bc_last_template_epub', true );
        if ( ! $last_template_epub ) {
            $last_template_epub = get_post_meta( $book->ID, 'bc_last_template', true );
        }
        $last_template_pdf = get_post_meta( $book->ID, 'bc_last_template_pdf', true );
        if ( ! $last_template_pdf ) {
            $last_template_pdf = get_post_meta( $book->ID, 'bc_last_template', true );
        }

        echo '<div class="bookcreator-template-select-group">';
        echo '<p class="bookcreator-template-select">';
        echo '<label for="book_template_epub_' . esc_attr( $book->ID ) . '">' . esc_html__( 'Template ePub', 'bookcreator' ) . '</label>';
        echo '<select name="book_template_epub" id="book_template_epub_' . esc_attr( $book->ID ) . '">';
        echo '<option value="">' . esc_html__( 'Template predefinito', 'bookcreator' ) . '</option>';
        foreach ( $epub_templates as $template ) {
            $selected = selected( $last_template_epub, $template['id'], false );
            echo '<option value="' . esc_attr( $template['id'] ) . '"' . $selected . '>' . esc_html( $template['name'] ) . '</option>';
        }
        echo '</select>';
        echo '</p>';

        echo '<p class="bookcreator-template-select">';
        echo '<label for="book_template_pdf_' . esc_attr( $book->ID ) . '">' . esc_html__( 'Template PDF', 'bookcreator' ) . '</label>';
        echo '<select name="book_template_pdf" id="book_template_pdf_' . esc_attr( $book->ID ) . '">';
        echo '<option value="">' . esc_html__( 'Template predefinito', 'bookcreator' ) . '</option>';
        foreach ( $pdf_templates as $template ) {
            $selected = selected( $last_template_pdf, $template['id'], false );
            echo '<option value="' . esc_attr( $template['id'] ) . '"' . $selected . '>' . esc_html( $template['name'] ) . '</option>';
        }
        echo '</select>';
        echo '</p>';
        echo '</div>';

        $epub_button_attrs = $epub_library_available ? array() : array( 'disabled' => 'disabled' );
        $pdf_button_attrs  = $pdf_library_available ? array() : array( 'disabled' => 'disabled' );

        submit_button( __( 'Crea ePub', 'bookcreator' ), 'secondary', 'bookcreator_generate_epub', false, $epub_button_attrs );
        submit_button( __( 'Crea PDF', 'bookcreator' ), 'secondary', 'bookcreator_generate_pdf', false, $pdf_button_attrs );
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

function bookcreator_register_generate_exports_page() {
    add_submenu_page(
        'edit.php?post_type=book_creator',
        __( 'Genera ePub/PDF', 'bookcreator' ),
        __( 'Genera ePub/PDF', 'bookcreator' ),
        'manage_options',
        'bc-generate-epub',
        'bookcreator_generate_exports_page'
    );
}
add_action( 'admin_menu', 'bookcreator_register_generate_exports_page' );

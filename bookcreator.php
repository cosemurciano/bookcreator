<?php
/**
 * Plugin Name: BookCreator
 * Description: Custom post type and management interface for creating books.
 * Version: 1.1
 * Author: Cosè Murciano
 * Text Domain: bookcreator
 * Domain Path: /languages
 */

if ( ! defined( 'BOOKCREATOR_PLUGIN_VERSION' ) ) {
    define( 'BOOKCREATOR_PLUGIN_VERSION', '1.1' );
}

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

/**
 * Ensure that WordPress media helper files are loaded before handling uploads.
 */
function bookcreator_require_media_includes() {
    static $loaded = false;

    if ( $loaded ) {
        return;
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $loaded = true;
}

function bookcreator_get_post_type_capabilities_map( $singular, $plural ) {
    $edit_plural_cap = 'edit_' . $plural;

    return array(
        'edit_post'              => 'edit_' . $singular,
        'read_post'              => 'read_' . $singular,
        'delete_post'            => 'delete_' . $singular,
        'edit_posts'             => $edit_plural_cap,
        'create_posts'           => 'create_' . $plural,
        'edit_others_posts'      => 'edit_others_' . $plural,
        'publish_posts'          => 'publish_' . $plural,
        'read_private_posts'     => 'read_private_' . $plural,
        'delete_posts'           => 'delete_' . $plural,
        'delete_private_posts'   => 'delete_private_' . $plural,
        'delete_published_posts' => 'delete_published_' . $plural,
        'delete_others_posts'    => 'delete_others_' . $plural,
        'edit_private_posts'     => 'edit_private_' . $plural,
        'edit_published_posts'   => 'edit_published_' . $plural,
    );
}

function bookcreator_get_bookcreator_role_capabilities() {
    $base_capabilities = array(
        'read'    => true,
        'level_0' => true,
    );

    $subscriber_role = get_role( 'subscriber' );
    if ( $subscriber_role instanceof WP_Role ) {
        $base_capabilities = array_merge( $base_capabilities, $subscriber_role->capabilities );
    }

    $bookcreator_capabilities = array(
        'read'                                   => true,
        'upload_files'                           => true,
        'edit_bookcreator_book'                  => true,
        'read_bookcreator_book'                  => true,
        'edit_bookcreator_books'                 => true,
        'create_bookcreator_books'               => true,
        'publish_bookcreator_books'              => true,
        'edit_published_bookcreator_books'       => true,
        'delete_bookcreator_books'               => true,
        'delete_published_bookcreator_books'     => true,
        'delete_bookcreator_book'                => true,
        'read_bookcreator_chapter'               => true,
        'edit_bookcreator_chapter'               => true,
        'edit_bookcreator_chapters'              => true,
        'create_bookcreator_chapters'            => true,
        'publish_bookcreator_chapters'           => true,
        'edit_private_bookcreator_chapters'      => true,
        'read_private_bookcreator_chapters'      => true,
        'edit_published_bookcreator_chapters'    => true,
        'delete_bookcreator_chapters'            => true,
        'delete_private_bookcreator_chapters'    => true,
        'delete_published_bookcreator_chapters'  => true,
        'delete_bookcreator_chapter'             => true,
        'edit_others_bookcreator_chapters'       => false,
        'delete_others_bookcreator_chapters'     => false,
        'read_bookcreator_paragraph'             => true,
        'edit_bookcreator_paragraph'             => true,
        'edit_bookcreator_paragraphs'            => true,
        'create_bookcreator_paragraphs'          => true,
        'publish_bookcreator_paragraphs'         => true,
        'edit_private_bookcreator_paragraphs'    => true,
        'read_private_bookcreator_paragraphs'    => true,
        'edit_published_bookcreator_paragraphs'  => true,
        'delete_bookcreator_paragraphs'          => true,
        'delete_private_bookcreator_paragraphs'  => true,
        'delete_published_bookcreator_paragraphs' => true,
        'delete_bookcreator_paragraph'           => true,
        'edit_others_bookcreator_paragraphs'     => false,
        'delete_others_bookcreator_paragraphs'   => false,
        'bookcreator_manage_templates'           => true,
        'bookcreator_manage_structures'          => true,
        'bookcreator_generate_exports'           => true,
    );

    return array_merge( $base_capabilities, $bookcreator_capabilities );
}

function bookcreator_register_roles() {
    $caps = bookcreator_get_bookcreator_role_capabilities();

    $role = get_role( 'bookcreator' );
    if ( ! $role ) {
        $role = add_role( 'bookcreator', __( 'BookCreator', 'bookcreator' ), $caps );
    }

    if ( $role ) {
        foreach ( $caps as $cap => $grant ) {
            if ( $grant ) {
                $role->add_cap( $cap );
            } else {
                $role->remove_cap( $cap );
            }
        }
    }

    $admin = get_role( 'administrator' );
    if ( $admin ) {
        $admin_caps = array_merge(
            array(
                'edit_bookcreator_books',
                'edit_others_bookcreator_books',
                'create_bookcreator_books',
                'publish_bookcreator_books',
                'read_private_bookcreator_books',
                'edit_private_bookcreator_books',
                'delete_bookcreator_books',
                'delete_others_bookcreator_books',
                'delete_private_bookcreator_books',
                'delete_published_bookcreator_books',
                'edit_published_bookcreator_books',
                'edit_bookcreator_book',
                'read_bookcreator_book',
                'delete_bookcreator_book',
                'manage_bookcreator_genres',
            ),
            array(
                'edit_bookcreator_chapter',
                'edit_bookcreator_chapters',
                'edit_others_bookcreator_chapters',
                'create_bookcreator_chapters',
                'publish_bookcreator_chapters',
                'read_private_bookcreator_chapters',
                'edit_private_bookcreator_chapters',
                'delete_bookcreator_chapter',
                'delete_bookcreator_chapters',
                'delete_others_bookcreator_chapters',
                'delete_private_bookcreator_chapters',
                'delete_published_bookcreator_chapters',
                'edit_published_bookcreator_chapters',
                'read_bookcreator_chapter',
            ),
            array(
                'edit_bookcreator_paragraph',
                'edit_bookcreator_paragraphs',
                'edit_others_bookcreator_paragraphs',
                'create_bookcreator_paragraphs',
                'publish_bookcreator_paragraphs',
                'read_private_bookcreator_paragraphs',
                'edit_private_bookcreator_paragraphs',
                'delete_bookcreator_paragraph',
                'delete_bookcreator_paragraphs',
                'delete_others_bookcreator_paragraphs',
                'delete_private_bookcreator_paragraphs',
                'delete_published_bookcreator_paragraphs',
                'edit_published_bookcreator_paragraphs',
                'read_bookcreator_paragraph',
            ),
            array(
                'bookcreator_manage_templates',
                'bookcreator_manage_structures',
                'bookcreator_generate_exports',
            )
        );

        foreach ( $admin_caps as $cap ) {
            $admin->add_cap( $cap );
        }
    }
}
add_action( 'init', 'bookcreator_register_roles', 0 );

function bookcreator_current_user_is_bookcreator() {
    $user = wp_get_current_user();

    return $user instanceof WP_User && in_array( 'bookcreator', (array) $user->roles, true );
}

function bookcreator_limit_media_library_to_current_user( $query ) {
    if ( ! is_admin() || ! bookcreator_current_user_is_bookcreator() ) {
        return;
    }

    if ( ! $query instanceof WP_Query || ! $query->is_main_query() ) {
        return;
    }

    $post_type = $query->get( 'post_type' );

    if ( 'attachment' !== $post_type && ( ! is_array( $post_type ) || ! in_array( 'attachment', $post_type, true ) ) ) {
        return;
    }

    $query->set( 'author', get_current_user_id() );
}
add_action( 'pre_get_posts', 'bookcreator_limit_media_library_to_current_user' );

function bookcreator_filter_ajax_attachments_to_current_user( $query ) {
    if ( bookcreator_current_user_is_bookcreator() ) {
        $query['author'] = get_current_user_id();
    }

    return $query;
}
add_filter( 'ajax_query_attachments_args', 'bookcreator_filter_ajax_attachments_to_current_user' );

function bookcreator_get_default_claude_settings() {
    return array(
        'enabled'         => false,
        'api_key'         => '',
        'default_model'   => 'claude-3-5-sonnet-20240620',
        'request_timeout' => 30,
        'output_margin'   => 0.8,
        'translation_prompt' => '',
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
    $settings['output_margin']   = isset( $settings['output_margin'] ) ? (float) $settings['output_margin'] : $defaults['output_margin'];
    $settings['translation_prompt'] = isset( $settings['translation_prompt'] ) ? (string) $settings['translation_prompt'] : '';

    if ( $settings['output_margin'] <= 0 || $settings['output_margin'] > 1 ) {
        $settings['output_margin'] = $defaults['output_margin'];
    }

    return $settings;
}

function bookcreator_get_allowed_claude_models() {
    $models = array(
        'claude-3-5-sonnet-20240620' => __( 'Claude 3.5 Sonnet (giugno 2024)', 'bookcreator' ),
        'claude-3-sonnet-20240229'   => __( 'Claude 3 Sonnet (febbraio 2024)', 'bookcreator' ),
        'claude-3-haiku-20240307'    => __( 'Claude 3 Haiku (marzo 2024)', 'bookcreator' ),
    );

    return apply_filters( 'bookcreator_claude_allowed_models', $models );
}

function bookcreator_get_claude_model_limits() {
    $limits = array(
        'claude-3-5-sonnet-20240620' => array(
            'max_output_tokens' => 8192,
            'max_input_tokens'  => 200000,
        ),
        'claude-3-sonnet-20240229'   => array(
            'max_output_tokens' => 6000,
            'max_input_tokens'  => 200000,
        ),
        'claude-3-haiku-20240307'    => array(
            'max_output_tokens' => 4096,
            'max_input_tokens'  => 200000,
        ),
    );

    foreach ( $limits as $model => $model_limits ) {
        if ( ! isset( $model_limits['max_chunk_chars'] ) ) {
            $limits[ $model ]['max_chunk_chars'] = (int) floor( $model_limits['max_output_tokens'] * 4.0 );
        }
    }

    return apply_filters( 'bookcreator_claude_model_limits', $limits );
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

    if ( isset( $input['output_margin'] ) ) {
        $margin_raw = $input['output_margin'];

        if ( is_string( $margin_raw ) ) {
            $margin_raw = str_replace( ',', '.', $margin_raw );
        }

        $margin = (float) $margin_raw;

        if ( $margin > 1 ) {
            $margin /= 100;
        }

        if ( $margin <= 0 || $margin > 1 ) {
            $margin = $defaults['output_margin'];
        }

        $margin = max( 0.1, min( 0.95, $margin ) );

        $output['output_margin'] = $margin;
    }

    if ( isset( $input['translation_prompt'] ) ) {
        $prompt = sanitize_textarea_field( $input['translation_prompt'] );
        $output['translation_prompt'] = trim( $prompt );
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

    add_settings_field(
        'bookcreator_claude_output_margin',
        __( 'Margine di sicurezza output', 'bookcreator' ),
        'bookcreator_claude_settings_field_output_margin',
        'bookcreator-settings',
        'bookcreator_claude_section'
    );

    add_settings_field(
        'bookcreator_claude_translation_prompt',
        __( 'Prompt predefinito per le traduzioni', 'bookcreator' ),
        'bookcreator_claude_settings_field_translation_prompt',
        'bookcreator-settings',
        'bookcreator_claude_section'
    );
}
add_action( 'admin_init', 'bookcreator_register_claude_settings' );

function bookcreator_render_dashboard_page() {
    if ( ! current_user_can( 'edit_bookcreator_books' ) ) {
        wp_die( esc_html__( 'Non hai i permessi per accedere a questa pagina.', 'bookcreator' ) );
    }

    $book_list_url      = admin_url( 'edit.php?post_type=book_creator' );
    $new_book_url       = admin_url( 'post-new.php?post_type=book_creator' );
    $chapters_url       = admin_url( 'edit.php?post_type=bc_chapter' );
    $paragraphs_url     = admin_url( 'edit.php?post_type=bc_paragraph' );
    $order_chapters_url = admin_url( 'admin.php?page=bc-order-chapters&post_type=book_creator' );
    $order_paragraphs_url = admin_url( 'admin.php?page=bc-order-paragraphs&post_type=book_creator' );
    $templates_epub_url = admin_url( 'admin.php?page=bc-templates-epub&post_type=book_creator' );
    $templates_pdf_url  = admin_url( 'admin.php?page=bc-templates-pdf&post_type=book_creator' );
    $texts_url          = admin_url( 'admin.php?page=bookcreator-template-texts&post_type=book_creator' );
    $settings_url       = admin_url( 'admin.php?page=bookcreator-settings&post_type=book_creator' );
    $exports_url        = admin_url( 'admin.php?page=bookcreator-generate-exports&post_type=book_creator' );

    ?>
    <div class="wrap bookcreator-dashboard">
        <h1><?php echo esc_html__( 'Dashboard BookCreator', 'bookcreator' ); ?></h1>
        <p class="description">
            <?php esc_html_e( 'Benvenuto! Da qui puoi conoscere le funzionalità principali del plugin, seguire un breve tutorial introduttivo e accedere rapidamente alle aree più utilizzate.', 'bookcreator' ); ?>
        </p>

        <h2><?php esc_html_e( 'Funzionalità principali', 'bookcreator' ); ?></h2>
        <ul>
            <li><?php esc_html_e( 'Organizza libri, capitoli e paragrafi direttamente dall’area di amministrazione di WordPress.', 'bookcreator' ); ?></li>
            <li><?php esc_html_e( 'Genera automaticamente ePub e PDF personalizzando template, stili tipografici e testi ricorrenti.', 'bookcreator' ); ?></li>
            <li><?php esc_html_e( 'Gestisci traduzioni, metadati editoriali e materiali di accompagnamento come prefazioni, appendici e ringraziamenti.', 'bookcreator' ); ?></li>
            <li><?php esc_html_e( 'Ordina con facilità la struttura del libro e sincronizza la numerazione di capitoli e paragrafi.', 'bookcreator' ); ?></li>
        </ul>

        <h2><?php esc_html_e( 'Tutorial rapido', 'bookcreator' ); ?></h2>
        <ol>
            <li><?php printf( esc_html__( 'Crea un nuovo libro dalla schermata %s e compila i metadati di base (autore, descrizione, copertina).', 'bookcreator' ), '<a href="' . esc_url( $new_book_url ) . '">' . esc_html__( 'Aggiungi nuovo', 'bookcreator' ) . '</a>' ); ?></li>
            <li><?php printf( esc_html__( 'Aggiungi capitoli e paragrafi dedicati usando le relative voci di menu oppure importa contenuti esistenti.', 'bookcreator' ) ); ?></li>
            <li><?php printf( esc_html__( 'Ordina capitoli e paragrafi dalle pagine %1$s e %2$s per definire la struttura del libro.', 'bookcreator' ), '<a href="' . esc_url( $order_chapters_url ) . '">' . esc_html__( 'Ordina capitoli', 'bookcreator' ) . '</a>', '<a href="' . esc_url( $order_paragraphs_url ) . '">' . esc_html__( 'Ordina paragrafi', 'bookcreator' ) . '</a>' ); ?></li>
            <li><?php printf( esc_html__( 'Personalizza testi ricorrenti e template grafici aprendo le pagine %1$s, %2$s e %3$s.', 'bookcreator' ), '<a href="' . esc_url( $texts_url ) . '">' . esc_html__( 'Testi template', 'bookcreator' ) . '</a>', '<a href="' . esc_url( $templates_epub_url ) . '">' . esc_html__( 'Template ePub', 'bookcreator' ) . '</a>', '<a href="' . esc_url( $templates_pdf_url ) . '">' . esc_html__( 'Template PDF', 'bookcreator' ) . '</a>' ); ?></li>
            <li><?php printf( esc_html__( 'Quando sei pronto esporta il libro in ePub o PDF dalla sezione %s.', 'bookcreator' ), '<a href="' . esc_url( $exports_url ) . '">' . esc_html__( 'Genera esportazioni', 'bookcreator' ) . '</a>' ); ?></li>
        </ol>

        <h2><?php esc_html_e( 'Scorciatoie utili', 'bookcreator' ); ?></h2>
        <div class="bookcreator-dashboard__shortcuts">
            <ul>
                <li><a class="button button-secondary" href="<?php echo esc_url( $book_list_url ); ?>"><?php esc_html_e( 'Gestisci libri', 'bookcreator' ); ?></a></li>
                <li><a class="button button-secondary" href="<?php echo esc_url( $chapters_url ); ?>"><?php esc_html_e( 'Gestisci capitoli', 'bookcreator' ); ?></a></li>
                <li><a class="button button-secondary" href="<?php echo esc_url( $paragraphs_url ); ?>"><?php esc_html_e( 'Gestisci paragrafi', 'bookcreator' ); ?></a></li>
                <li><a class="button button-secondary" href="<?php echo esc_url( $templates_epub_url ); ?>"><?php esc_html_e( 'Template ePub', 'bookcreator' ); ?></a></li>
                <li><a class="button button-secondary" href="<?php echo esc_url( $templates_pdf_url ); ?>"><?php esc_html_e( 'Template PDF', 'bookcreator' ); ?></a></li>
                <li><a class="button button-secondary" href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'Impostazioni plugin', 'bookcreator' ); ?></a></li>
            </ul>
        </div>
    </div>
    <?php
}

function bookcreator_register_dashboard_page() {
    add_submenu_page(
        'edit.php?post_type=book_creator',
        __( 'Dashboard BookCreator', 'bookcreator' ),
        __( 'Dashboard', 'bookcreator' ),
        'edit_bookcreator_books',
        'bookcreator-dashboard',
        'bookcreator_render_dashboard_page',
        0
    );
}
add_action( 'admin_menu', 'bookcreator_register_dashboard_page', 5 );

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
add_action( 'admin_menu', 'bookcreator_register_settings_page', 100 );

function bookcreator_reorder_book_submenu() {
    global $submenu;

    if ( ! isset( $submenu['edit.php?post_type=book_creator'] ) ) {
        return;
    }

    $dashboard_item = null;
    foreach ( $submenu['edit.php?post_type=book_creator'] as $index => $item ) {
        if ( isset( $item[2] ) && 'bookcreator-dashboard' === $item[2] ) {
            $dashboard_item = $item;
            unset( $submenu['edit.php?post_type=book_creator'][ $index ] );
            break;
        }
    }

    if ( ! $dashboard_item ) {
        return;
    }

    array_unshift( $submenu['edit.php?post_type=book_creator'], $dashboard_item );
    $submenu['edit.php?post_type=book_creator'] = array_values( $submenu['edit.php?post_type=book_creator'] );
}
add_action( 'admin_menu', 'bookcreator_reorder_book_submenu', 999 );

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
    $books    = get_posts(
        array(
            'post_type'   => 'book_creator',
            'numberposts' => -1,
            'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ),
            'orderby'     => 'title',
            'order'       => 'ASC',
        )
    );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__( 'Impostazioni BookCreator', 'bookcreator' ); ?></h1>
        <?php settings_errors( 'bookcreator_settings' ); ?>
        <?php
        $book_import_status = isset( $_GET['book_import_status'] ) ? sanitize_key( wp_unslash( $_GET['book_import_status'] ) ) : '';
        if ( $book_import_status ) {
            $message = isset( $_GET['book_import_message'] ) ? sanitize_text_field( wp_unslash( rawurldecode( $_GET['book_import_message'] ) ) ) : '';
            $class   = 'notice notice-error';
            if ( 'success' === $book_import_status ) {
                $class = 'notice notice-success';
                if ( ! $message ) {
                    $message = __( 'Libro importato con successo.', 'bookcreator' );
                }
            } elseif ( ! $message ) {
                $message = __( 'Impossibile importare il libro selezionato.', 'bookcreator' );
            }
            echo '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( $message ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        $book_export_status = isset( $_GET['book_export_status'] ) ? sanitize_key( wp_unslash( $_GET['book_export_status'] ) ) : '';
        if ( $book_export_status ) {
            $message = isset( $_GET['book_export_message'] ) ? sanitize_text_field( wp_unslash( rawurldecode( $_GET['book_export_message'] ) ) ) : '';
            $class   = 'notice notice-error';
            if ( 'success' === $book_export_status ) {
                $class = 'notice notice-success';
                if ( ! $message ) {
                    $message = __( 'Esportazione completata con successo.', 'bookcreator' );
                }
            } elseif ( ! $message ) {
                $message = __( 'Impossibile esportare il libro selezionato.', 'bookcreator' );
            }
            echo '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( $message ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        ?>
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
        <hr />
        <h2><?php esc_html_e( 'Esporta e importa libri', 'bookcreator' ); ?></h2>
        <p><?php esc_html_e( 'Salva una copia dei contenuti dei libri (capitoli, paragrafi e indice) in formato JSON oppure importa un file precedentemente esportato.', 'bookcreator' ); ?></p>
        <div class="bookcreator-settings-book-transfer">
            <div class="bookcreator-settings-book-transfer__section">
                <h3><?php esc_html_e( 'Esporta libro', 'bookcreator' ); ?></h3>
                <?php if ( $books ) : ?>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <?php wp_nonce_field( 'bookcreator_export_book', 'bookcreator_export_book_nonce' ); ?>
                        <input type="hidden" name="action" value="bookcreator_export_book" />
                        <p>
                            <label for="bookcreator_export_book_id"><?php esc_html_e( 'Scegli il libro da esportare', 'bookcreator' ); ?></label><br />
                            <select name="book_id" id="bookcreator_export_book_id">
                                <?php foreach ( $books as $book ) : ?>
                                    <option value="<?php echo esc_attr( $book->ID ); ?>"><?php echo esc_html( $book->post_title ? $book->post_title : sprintf( __( 'Libro senza titolo (%d)', 'bookcreator' ), $book->ID ) ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                        <?php submit_button( __( 'Esporta libro', 'bookcreator' ), 'secondary', 'bookcreator_export_submit', false ); ?>
                    </form>
                <?php else : ?>
                    <p><?php esc_html_e( 'Non ci sono libri disponibili da esportare.', 'bookcreator' ); ?></p>
                <?php endif; ?>
            </div>
            <div class="bookcreator-settings-book-transfer__section">
                <h3><?php esc_html_e( 'Importa libro', 'bookcreator' ); ?></h3>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
                    <?php wp_nonce_field( 'bookcreator_import_book', 'bookcreator_import_book_nonce' ); ?>
                    <input type="hidden" name="action" value="bookcreator_import_book" />
                    <p>
                        <label for="bookcreator_import_file"><?php esc_html_e( 'Seleziona il file JSON esportato', 'bookcreator' ); ?></label><br />
                        <input type="file" name="bookcreator_import_file" id="bookcreator_import_file" accept="application/json,.json" required />
                    </p>
                    <?php submit_button( __( 'Importa libro', 'bookcreator' ), 'primary', 'bookcreator_import_submit', false ); ?>
                </form>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Retrieve the URL for the plugin settings page.
 *
 * @return string
 */
function bookcreator_get_settings_page_url() {
    return add_query_arg(
        array(
            'post_type' => 'book_creator',
            'page'      => 'bookcreator-settings',
        ),
        admin_url( 'edit.php' )
    );
}

/**
 * Prepare translation data for export.
 *
 * @param int    $post_id   Post identifier.
 * @param string $post_type Post type.
 * @return array<string, array<string, mixed>>
 */
function bookcreator_prepare_translation_export_data( $post_id, $post_type ) {
    $translations = bookcreator_get_translations_for_post( $post_id, $post_type );
    if ( ! $translations ) {
        return array();
    }

    $exported = array();
    foreach ( $translations as $language => $translation ) {
        $fields    = isset( $translation['fields'] ) && is_array( $translation['fields'] ) ? $translation['fields'] : array();
        $generated = isset( $translation['generated'] ) ? $translation['generated'] : '';

        $exported[ $language ] = array(
            'language'  => $language,
            'fields'    => $fields,
            'generated' => $generated,
        );
    }

    return $exported;
}

/**
 * Build the export data structure for a paragraph.
 *
 * @param WP_Post $paragraph Paragraph post object.
 * @param int     $position  Position within the chapter.
 * @return array<string, mixed>
 */
function bookcreator_prepare_paragraph_export_data( $paragraph, $position ) {
    $meta = array(
        'bc_footnotes' => get_post_meta( $paragraph->ID, 'bc_footnotes', true ),
        'bc_citations' => get_post_meta( $paragraph->ID, 'bc_citations', true ),
    );

    return array(
        'title'        => $paragraph->post_title,
        'slug'         => $paragraph->post_name,
        'content'      => $paragraph->post_content,
        'excerpt'      => $paragraph->post_excerpt,
        'status'       => $paragraph->post_status,
        'position'     => (int) $position,
        'meta'         => $meta,
        'translations' => bookcreator_prepare_translation_export_data( $paragraph->ID, 'bc_paragraph' ),
    );
}

/**
 * Build the export data structure for a chapter.
 *
 * @param WP_Post $chapter  Chapter post object.
 * @param int     $position Chapter position.
 * @return array<string, mixed>
 */
function bookcreator_prepare_chapter_export_data( $chapter, $position ) {
    $paragraphs         = array();
    $ordered_paragraphs = bookcreator_get_ordered_paragraphs_for_chapter( $chapter->ID );
    $paragraph_index    = 0;

    foreach ( $ordered_paragraphs as $paragraph ) {
        $paragraph_index++;
        $paragraphs[] = bookcreator_prepare_paragraph_export_data( $paragraph, $paragraph_index );
    }

    return array(
        'title'        => $chapter->post_title,
        'slug'         => $chapter->post_name,
        'content'      => $chapter->post_content,
        'excerpt'      => $chapter->post_excerpt,
        'status'       => $chapter->post_status,
        'position'     => (int) $position,
        'meta'         => array(),
        'translations' => bookcreator_prepare_translation_export_data( $chapter->ID, 'bc_chapter' ),
        'paragraphs'   => $paragraphs,
    );
}

/**
 * Build the index representation for the exported data.
 *
 * @param array<int, array<string, mixed>> $chapters Exported chapters.
 * @return array<int, array<string, mixed>>
 */
function bookcreator_build_book_index_from_export( $chapters ) {
    $index = array();

    foreach ( $chapters as $chapter ) {
        $position = isset( $chapter['position'] ) ? (int) $chapter['position'] : 0;
        $entry    = array(
            'title'      => isset( $chapter['title'] ) ? (string) $chapter['title'] : '',
            'position'   => $position,
            'number'     => $position > 0 ? (string) $position : '',
            'paragraphs' => array(),
        );

        if ( ! empty( $chapter['paragraphs'] ) && is_array( $chapter['paragraphs'] ) ) {
            foreach ( $chapter['paragraphs'] as $paragraph ) {
                $paragraph_position = isset( $paragraph['position'] ) ? (int) $paragraph['position'] : 0;
                $paragraph_entry    = array(
                    'title'    => isset( $paragraph['title'] ) ? (string) $paragraph['title'] : '',
                    'position' => $paragraph_position,
                    'number'   => $position > 0 ? $position . '.' . $paragraph_position : (string) $paragraph_position,
                );
                $entry['paragraphs'][] = $paragraph_entry;
            }
        }

        $index[] = $entry;
    }

    return $index;
}

/**
 * Prepare the full export structure for a book.
 *
 * @param int $book_id Book identifier.
 * @return array<string, mixed>|WP_Error
 */
function bookcreator_prepare_book_export_data( $book_id ) {
    $book = get_post( $book_id );
    if ( ! $book || 'book_creator' !== $book->post_type ) {
        return new WP_Error( 'bookcreator_invalid_book', __( 'Il libro selezionato non è valido.', 'bookcreator' ) );
    }

    $meta_schema = bookcreator_get_book_content_meta_schema();
    $meta        = array();
    foreach ( $meta_schema as $key => $callback ) {
        $meta[ $key ] = get_post_meta( $book_id, $key, true );
    }

    $chapters              = array();
    $ordered_chapter_posts = bookcreator_get_ordered_chapters_for_book( $book_id );
    $chapter_index         = 0;

    foreach ( $ordered_chapter_posts as $chapter_post ) {
        $chapter_index++;
        $chapters[] = bookcreator_prepare_chapter_export_data( $chapter_post, $chapter_index );
    }

    return array(
        'format'         => 'bookcreator_book',
        'format_version' => 1,
        'plugin_version' => defined( 'BOOKCREATOR_PLUGIN_VERSION' ) ? BOOKCREATOR_PLUGIN_VERSION : '',
        'exported_at'    => gmdate( 'c' ),
        'book'           => array(
            'title'        => $book->post_title,
            'slug'         => $book->post_name,
            'content'      => $book->post_content,
            'excerpt'      => $book->post_excerpt,
            'status'       => $book->post_status,
            'meta'         => $meta,
            'translations' => bookcreator_prepare_translation_export_data( $book_id, 'book_creator' ),
            'chapters'     => $chapters,
            'index'        => bookcreator_build_book_index_from_export( $chapters ),
        ),
    );
}

/**
 * Generate a filename for the exported book data.
 *
 * @param array<string, mixed> $book    Book export data.
 * @param int                  $book_id Original book identifier.
 * @return string
 */
function bookcreator_get_book_export_filename( $book, $book_id ) {
    $slug = '';
    if ( isset( $book['slug'] ) && $book['slug'] ) {
        $slug = sanitize_title( $book['slug'] );
    }

    if ( ! $slug && isset( $book['title'] ) ) {
        $slug = sanitize_title( $book['title'] );
    }

    if ( ! $slug ) {
        $slug = 'book-' . absint( $book_id );
    }

    return sprintf( 'bookcreator-%s-%s.json', $slug, gmdate( 'Ymd-His' ) );
}

/**
 * Redirect back to the settings page with an export message.
 *
 * @param string $status  Status key.
 * @param string $message Optional message.
 */
function bookcreator_redirect_with_export_message( $status, $message = '' ) {
    $args = array( 'book_export_status' => $status );
    if ( $message ) {
        $args['book_export_message'] = rawurlencode( $message );
    }

    wp_safe_redirect( add_query_arg( $args, bookcreator_get_settings_page_url() ) );
    exit;
}

/**
 * Redirect back to the settings page with an import message.
 *
 * @param string $status  Status key.
 * @param string $message Optional message.
 */
function bookcreator_redirect_with_import_message( $status, $message = '' ) {
    $args = array( 'book_import_status' => $status );
    if ( $message ) {
        $args['book_import_message'] = rawurlencode( $message );
    }

    wp_safe_redirect( add_query_arg( $args, bookcreator_get_settings_page_url() ) );
    exit;
}

/**
 * Handle book export requests.
 */
function bookcreator_handle_book_export_action() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Non hai i permessi per esportare libri.', 'bookcreator' ) );
    }

    if ( ! isset( $_POST['bookcreator_export_book_nonce'] ) || ! wp_verify_nonce( $_POST['bookcreator_export_book_nonce'], 'bookcreator_export_book' ) ) {
        bookcreator_redirect_with_export_message( 'error', __( 'Token di sicurezza non valido.', 'bookcreator' ) );
    }

    $book_id = isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0;
    if ( ! $book_id ) {
        bookcreator_redirect_with_export_message( 'error', __( 'Seleziona un libro valido da esportare.', 'bookcreator' ) );
    }

    $export_data = bookcreator_prepare_book_export_data( $book_id );
    if ( is_wp_error( $export_data ) ) {
        bookcreator_redirect_with_export_message( 'error', $export_data->get_error_message() );
    }

    $json = wp_json_encode( $export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    if ( false === $json ) {
        bookcreator_redirect_with_export_message( 'error', __( 'Impossibile generare il file di esportazione.', 'bookcreator' ) );
    }

    $filename = bookcreator_get_book_export_filename( $export_data['book'], $book_id );

    nocache_headers();
    header( 'Content-Type: application/json; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
    header( 'Content-Length: ' . strlen( $json ) );
    echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    exit;
}
add_action( 'admin_post_bookcreator_export_book', 'bookcreator_handle_book_export_action' );

/**
 * Normalize post status values received during import.
 *
 * @param string $status Raw status value.
 * @return string
 */
function bookcreator_normalize_import_post_status( $status ) {
    $status  = sanitize_key( (string) $status );
    $allowed = array( 'publish', 'draft', 'pending', 'private', 'future' );

    if ( in_array( $status, $allowed, true ) ) {
        return $status;
    }

    return 'draft';
}

/**
 * Store translation data for a specific post during import.
 *
 * @param int    $post_id      Post identifier.
 * @param string $post_type    Post type.
 * @param mixed  $translations Raw translations data.
 */
function bookcreator_store_translation_data_for_post( $post_id, $post_type, $translations ) {
    $meta_key = bookcreator_get_translation_meta_key( $post_type );
    if ( ! $meta_key ) {
        return;
    }

    if ( ! is_array( $translations ) ) {
        delete_post_meta( $post_id, $meta_key );

        return;
    }

    $normalized = array();

    foreach ( $translations as $language_key => $translation ) {
        if ( ! is_array( $translation ) ) {
            continue;
        }

        $language = '';
        if ( isset( $translation['language'] ) ) {
            $language = $translation['language'];
        } elseif ( is_string( $language_key ) ) {
            $language = $language_key;
        }

        $language = bookcreator_sanitize_translation_language( $language );
        if ( '' === $language ) {
            continue;
        }

        $fields = array();
        if ( isset( $translation['fields'] ) && is_array( $translation['fields'] ) ) {
            $fields = bookcreator_sanitize_translation_fields( $translation['fields'], $post_type );
        }

        $generated = '';
        if ( isset( $translation['generated'] ) ) {
            $generated = sanitize_text_field( $translation['generated'] );
        }

        $normalized[ $language ] = array(
            'language'          => $language,
            'fields'            => $fields,
            'generated'         => $generated,
            'cover_id'          => 0,
            'publisher_logo_id' => 0,
        );
    }

    if ( $normalized ) {
        update_post_meta( $post_id, $meta_key, $normalized );
    } else {
        delete_post_meta( $post_id, $meta_key );
    }
}

/**
 * Remove created content when an import fails.
 *
 * @param array<string, mixed> $created_posts Data about created posts.
 */
function bookcreator_import_cleanup( $created_posts ) {
    require_once ABSPATH . 'wp-admin/includes/nav-menu.php';

    if ( ! empty( $created_posts['paragraphs'] ) ) {
        foreach ( $created_posts['paragraphs'] as $paragraph_id ) {
            wp_delete_post( (int) $paragraph_id, true );
        }
    }

    if ( ! empty( $created_posts['chapters'] ) ) {
        foreach ( array_reverse( $created_posts['chapters'] ) as $chapter_id ) {
            $chapter_id = (int) $chapter_id;
            $menu       = wp_get_nav_menu_object( 'paragraphs-chapter-' . $chapter_id );
            if ( $menu ) {
                wp_delete_nav_menu( $menu->term_id );
            }
            wp_delete_post( $chapter_id, true );
        }
    }

    if ( ! empty( $created_posts['book'] ) ) {
        $book_id = (int) $created_posts['book'];
        $menu    = wp_get_nav_menu_object( 'chapters-book-' . $book_id );
        if ( $menu ) {
            wp_delete_nav_menu( $menu->term_id );
        }
        wp_delete_post( $book_id, true );
    }
}

/**
 * Reset all items for a navigation menu.
 *
 * @param int $menu_id Menu term identifier.
 */
function bookcreator_reset_nav_menu_items( $menu_id ) {
    $items = wp_get_nav_menu_items( $menu_id );
    if ( ! $items ) {
        return;
    }

    foreach ( $items as $item ) {
        wp_delete_post( $item->ID, true );
    }
}

/**
 * Build navigation menus for an imported book.
 *
 * @param int   $book_id          Book identifier.
 * @param array $chapters_created Chapters created with their paragraph data.
 * @return true|WP_Error
 */
function bookcreator_build_navigation_from_import( $book_id, $chapters_created ) {
    require_once ABSPATH . 'wp-admin/includes/nav-menu.php';

    $touched_menus   = array();
    $chapter_menu_id = bookcreator_get_chapter_menu_id( $book_id );
    $touched_menus[] = $chapter_menu_id;
    bookcreator_reset_nav_menu_items( $chapter_menu_id );

    foreach ( $chapters_created as $chapter ) {
        $chapter_id = isset( $chapter['id'] ) ? (int) $chapter['id'] : 0;
        if ( ! $chapter_id ) {
            continue;
        }

        $position = isset( $chapter['position'] ) ? (int) $chapter['position'] : 0;
        $result   = wp_update_nav_menu_item(
            $chapter_menu_id,
            0,
            array(
                'menu-item-title'     => get_the_title( $chapter_id ),
                'menu-item-object-id' => $chapter_id,
                'menu-item-object'    => 'bc_chapter',
                'menu-item-type'      => 'post_type',
                'menu-item-status'    => 'publish',
                'menu-item-position'  => $position,
            )
        );

        if ( is_wp_error( $result ) ) {
            foreach ( $touched_menus as $menu_id ) {
                bookcreator_reset_nav_menu_items( $menu_id );
            }

            return $result;
        }

        $paragraph_menu_id = bookcreator_get_paragraph_menu_id( $chapter_id );
        $touched_menus[]   = $paragraph_menu_id;
        bookcreator_reset_nav_menu_items( $paragraph_menu_id );

        if ( ! empty( $chapter['paragraphs'] ) && is_array( $chapter['paragraphs'] ) ) {
            foreach ( $chapter['paragraphs'] as $paragraph ) {
                $paragraph_id = isset( $paragraph['id'] ) ? (int) $paragraph['id'] : 0;
                if ( ! $paragraph_id ) {
                    continue;
                }

                $paragraph_position = isset( $paragraph['position'] ) ? (int) $paragraph['position'] : 0;
                $paragraph_result   = wp_update_nav_menu_item(
                    $paragraph_menu_id,
                    0,
                    array(
                        'menu-item-title'     => get_the_title( $paragraph_id ),
                        'menu-item-object-id' => $paragraph_id,
                        'menu-item-object'    => 'bc_paragraph',
                        'menu-item-type'      => 'post_type',
                        'menu-item-status'    => 'publish',
                        'menu-item-position'  => $paragraph_position,
                    )
                );

                if ( is_wp_error( $paragraph_result ) ) {
                    foreach ( $touched_menus as $menu_id ) {
                        bookcreator_reset_nav_menu_items( $menu_id );
                    }

                    return $paragraph_result;
                }
            }
        }
    }

    return true;
}

/**
 * Import a book from decoded export data.
 *
 * @param mixed $data Decoded JSON data.
 * @return int|WP_Error New book ID on success.
 */
function bookcreator_import_book_from_data( $data ) {
    if ( ! is_array( $data ) ) {
        return new WP_Error( 'bookcreator_invalid_export', __( 'Il file caricato non contiene dati di esportazione validi.', 'bookcreator' ) );
    }

    $format = isset( $data['format'] ) ? sanitize_key( $data['format'] ) : '';
    if ( 'bookcreator_book' !== $format ) {
        return new WP_Error( 'bookcreator_invalid_export', __( 'Il file caricato non è stato generato da BookCreator.', 'bookcreator' ) );
    }

    $version = isset( $data['format_version'] ) ? (int) $data['format_version'] : 1;
    if ( $version > 1 ) {
        return new WP_Error( 'bookcreator_unsupported_version', __( 'La versione del file esportato non è supportata.', 'bookcreator' ) );
    }

    if ( empty( $data['book'] ) || ! is_array( $data['book'] ) ) {
        return new WP_Error( 'bookcreator_missing_book', __( 'Dati del libro mancanti o non validi.', 'bookcreator' ) );
    }

    $book_data = $data['book'];

    $title = isset( $book_data['title'] ) ? sanitize_text_field( $book_data['title'] ) : '';
    if ( '' === $title ) {
        $title = __( 'Libro importato', 'bookcreator' );
    }

    $status = isset( $book_data['status'] ) ? bookcreator_normalize_import_post_status( $book_data['status'] ) : 'draft';
    $slug   = isset( $book_data['slug'] ) ? sanitize_title( $book_data['slug'] ) : '';

    $book_args = array(
        'post_type'    => 'book_creator',
        'post_title'   => $title,
        'post_status'  => $status,
        'post_content' => isset( $book_data['content'] ) ? $book_data['content'] : '',
        'post_excerpt' => isset( $book_data['excerpt'] ) ? $book_data['excerpt'] : '',
    );

    if ( $slug ) {
        $book_args['post_name'] = $slug;
    }

    $book_id = wp_insert_post( wp_slash( $book_args ), true );
    if ( is_wp_error( $book_id ) ) {
        return $book_id;
    }

    $created_posts = array(
        'book'       => $book_id,
        'chapters'   => array(),
        'paragraphs' => array(),
    );

    $meta_schema = bookcreator_get_book_content_meta_schema();
    $meta        = isset( $book_data['meta'] ) && is_array( $book_data['meta'] ) ? $book_data['meta'] : array();

    foreach ( $meta_schema as $key => $callback ) {
        if ( isset( $meta[ $key ] ) ) {
            $value = $meta[ $key ];
            if ( $callback && is_callable( $callback ) ) {
                $value = call_user_func( $callback, $value );
            }
            update_post_meta( $book_id, $key, $value );
        } else {
            delete_post_meta( $book_id, $key );
        }
    }

    if ( isset( $book_data['translations'] ) ) {
        bookcreator_store_translation_data_for_post( $book_id, 'book_creator', $book_data['translations'] );
    }

    $chapters_data     = isset( $book_data['chapters'] ) && is_array( $book_data['chapters'] ) ? $book_data['chapters'] : array();
    $chapters_created  = array();
    $chapter_position  = 0;

    foreach ( $chapters_data as $chapter_data ) {
        if ( ! is_array( $chapter_data ) ) {
            continue;
        }

        $chapter_position++;
        $chapter_title  = isset( $chapter_data['title'] ) ? sanitize_text_field( $chapter_data['title'] ) : sprintf( __( 'Capitolo %d', 'bookcreator' ), $chapter_position );
        $chapter_status = isset( $chapter_data['status'] ) ? bookcreator_normalize_import_post_status( $chapter_data['status'] ) : 'draft';
        $chapter_slug   = isset( $chapter_data['slug'] ) ? sanitize_title( $chapter_data['slug'] ) : '';

        $chapter_args = array(
            'post_type'    => 'bc_chapter',
            'post_title'   => $chapter_title,
            'post_status'  => $chapter_status,
            'post_content' => isset( $chapter_data['content'] ) ? $chapter_data['content'] : '',
            'post_excerpt' => isset( $chapter_data['excerpt'] ) ? $chapter_data['excerpt'] : '',
            'menu_order'   => $chapter_position,
        );

        if ( $chapter_slug ) {
            $chapter_args['post_name'] = $chapter_slug;
        }

        $chapter_id = wp_insert_post( wp_slash( $chapter_args ), true );
        if ( is_wp_error( $chapter_id ) ) {
            bookcreator_import_cleanup( $created_posts );

            return $chapter_id;
        }

        $created_posts['chapters'][] = $chapter_id;

        update_post_meta( $chapter_id, 'bc_books', array( (string) $book_id ) );

        if ( isset( $chapter_data['translations'] ) ) {
            bookcreator_store_translation_data_for_post( $chapter_id, 'bc_chapter', $chapter_data['translations'] );
        }

        $paragraphs_data    = isset( $chapter_data['paragraphs'] ) && is_array( $chapter_data['paragraphs'] ) ? $chapter_data['paragraphs'] : array();
        $paragraph_position = 0;
        $paragraphs_created = array();

        foreach ( $paragraphs_data as $paragraph_data ) {
            if ( ! is_array( $paragraph_data ) ) {
                continue;
            }

            $paragraph_position++;
            $paragraph_title  = isset( $paragraph_data['title'] ) ? sanitize_text_field( $paragraph_data['title'] ) : sprintf( __( 'Paragrafo %s', 'bookcreator' ), $chapter_position . '.' . $paragraph_position );
            $paragraph_status = isset( $paragraph_data['status'] ) ? bookcreator_normalize_import_post_status( $paragraph_data['status'] ) : 'draft';
            $paragraph_slug   = isset( $paragraph_data['slug'] ) ? sanitize_title( $paragraph_data['slug'] ) : '';

            $paragraph_args = array(
                'post_type'    => 'bc_paragraph',
                'post_title'   => $paragraph_title,
                'post_status'  => $paragraph_status,
                'post_content' => isset( $paragraph_data['content'] ) ? $paragraph_data['content'] : '',
                'post_excerpt' => isset( $paragraph_data['excerpt'] ) ? $paragraph_data['excerpt'] : '',
                'menu_order'   => $paragraph_position,
            );

            if ( $paragraph_slug ) {
                $paragraph_args['post_name'] = $paragraph_slug;
            }

            $paragraph_id = wp_insert_post( wp_slash( $paragraph_args ), true );
            if ( is_wp_error( $paragraph_id ) ) {
                bookcreator_import_cleanup( $created_posts );

                return $paragraph_id;
            }

            $created_posts['paragraphs'][] = $paragraph_id;
            $paragraphs_created[]          = array(
                'id'       => $paragraph_id,
                'position' => $paragraph_position,
            );

            update_post_meta( $paragraph_id, 'bc_chapters', array( (string) $chapter_id ) );
            update_post_meta( $paragraph_id, 'bc_books', array( (string) $book_id ) );

            $paragraph_meta = isset( $paragraph_data['meta'] ) && is_array( $paragraph_data['meta'] ) ? $paragraph_data['meta'] : array();
            if ( isset( $paragraph_meta['bc_footnotes'] ) ) {
                update_post_meta( $paragraph_id, 'bc_footnotes', wp_kses_post( $paragraph_meta['bc_footnotes'] ) );
            } else {
                delete_post_meta( $paragraph_id, 'bc_footnotes' );
            }

            if ( isset( $paragraph_meta['bc_citations'] ) ) {
                update_post_meta( $paragraph_id, 'bc_citations', wp_kses_post( $paragraph_meta['bc_citations'] ) );
            } else {
                delete_post_meta( $paragraph_id, 'bc_citations' );
            }

            if ( isset( $paragraph_data['translations'] ) ) {
                bookcreator_store_translation_data_for_post( $paragraph_id, 'bc_paragraph', $paragraph_data['translations'] );
            }
        }

        $chapters_created[] = array(
            'id'         => $chapter_id,
            'position'   => $chapter_position,
            'paragraphs' => $paragraphs_created,
        );
    }

    $navigation_result = bookcreator_build_navigation_from_import( $book_id, $chapters_created );
    if ( is_wp_error( $navigation_result ) ) {
        bookcreator_import_cleanup( $created_posts );

        return $navigation_result;
    }

    return $book_id;
}

/**
 * Handle book import requests.
 */
function bookcreator_handle_book_import_action() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Non hai i permessi per importare libri.', 'bookcreator' ) );
    }

    if ( ! isset( $_POST['bookcreator_import_book_nonce'] ) || ! wp_verify_nonce( $_POST['bookcreator_import_book_nonce'], 'bookcreator_import_book' ) ) {
        bookcreator_redirect_with_import_message( 'error', __( 'Token di sicurezza non valido.', 'bookcreator' ) );
    }

    if ( empty( $_FILES['bookcreator_import_file'] ) || ! is_array( $_FILES['bookcreator_import_file'] ) ) {
        bookcreator_redirect_with_import_message( 'error', __( 'Carica un file di esportazione valido.', 'bookcreator' ) );
    }

    $file  = $_FILES['bookcreator_import_file'];
    $error = isset( $file['error'] ) ? (int) $file['error'] : 0;
    if ( $error && ( ! defined( 'UPLOAD_ERR_OK' ) || UPLOAD_ERR_OK !== $error ) ) {
        bookcreator_redirect_with_import_message( 'error', __( 'Si è verificato un errore durante il caricamento del file.', 'bookcreator' ) );
    }

    $tmp_name = isset( $file['tmp_name'] ) ? $file['tmp_name'] : '';
    if ( ! $tmp_name || ! file_exists( $tmp_name ) ) {
        bookcreator_redirect_with_import_message( 'error', __( 'File di importazione non trovato.', 'bookcreator' ) );
    }

    $contents = file_get_contents( $tmp_name );
    if ( false === $contents ) {
        bookcreator_redirect_with_import_message( 'error', __( 'Impossibile leggere il file caricato.', 'bookcreator' ) );
    }

    $decoded = json_decode( $contents, true );
    if ( null === $decoded && json_last_error() !== JSON_ERROR_NONE ) {
        bookcreator_redirect_with_import_message( 'error', __( 'Il file fornito non contiene un JSON valido.', 'bookcreator' ) );
    }

    $result = bookcreator_import_book_from_data( $decoded );
    if ( is_wp_error( $result ) ) {
        bookcreator_redirect_with_import_message( 'error', $result->get_error_message() );
    }

    $message = __( 'Libro importato con successo.', 'bookcreator' );
    if ( $result ) {
        $message = sprintf( __( 'Libro importato con successo (ID %d).', 'bookcreator' ), (int) $result );
    }

    bookcreator_redirect_with_import_message( 'success', $message );
}
add_action( 'admin_post_bookcreator_import_book', 'bookcreator_handle_book_import_action' );

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

function bookcreator_claude_settings_field_output_margin() {
    $settings     = bookcreator_get_claude_settings();
    $margin_value = isset( $settings['output_margin'] ) ? (float) $settings['output_margin'] : 0.8;
    $display      = round( $margin_value * 100, 1 );
    ?>
    <input type="number" name="bookcreator_claude_settings[output_margin]" id="bookcreator_claude_output_margin" value="<?php echo esc_attr( $display ); ?>" min="10" max="95" step="1" />
    <p class="description"><?php esc_html_e( 'Percentuale del limite massimo di token di output da utilizzare (il resto rimane come margine di sicurezza).', 'bookcreator' ); ?></p>
    <?php
}

function bookcreator_claude_settings_field_translation_prompt() {
    $settings = bookcreator_get_claude_settings();
    $value    = isset( $settings['translation_prompt'] ) ? $settings['translation_prompt'] : '';
    ?>
    <textarea name="bookcreator_claude_settings[translation_prompt]" id="bookcreator_claude_translation_prompt" rows="5" class="large-text" aria-describedby="bookcreator_claude_translation_prompt_help"><?php echo esc_textarea( $value ); ?></textarea>
    <p id="bookcreator_claude_translation_prompt_help" class="description"><?php esc_html_e( 'Testo aggiuntivo che verrà incluso in tutte le richieste di traduzione inviate a Claude.', 'bookcreator' ); ?></p>
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

function bookcreator_send_claude_message( $prompt, $args = array() ) {
    if ( ! bookcreator_is_claude_enabled() ) {
        return new WP_Error( 'bookcreator_claude_disabled', __( 'Configura le impostazioni di Claude AI prima di eseguire questa azione.', 'bookcreator' ) );
    }

    $claude_settings        = bookcreator_get_claude_settings();
    $default_claude_settings = bookcreator_get_default_claude_settings();
    $default_model          = isset( $default_claude_settings['default_model'] ) ? $default_claude_settings['default_model'] : 'claude-3-5-sonnet-20240620';
    $model                  = isset( $claude_settings['default_model'] ) ? $claude_settings['default_model'] : $default_model;
    $timeout                = isset( $claude_settings['request_timeout'] ) ? (int) $claude_settings['request_timeout'] : 30;
    $output_margin          = isset( $claude_settings['output_margin'] ) ? (float) $claude_settings['output_margin'] : 0.8;
    $api_key                = bookcreator_get_claude_api_key();

    if ( empty( $api_key ) ) {
        return new WP_Error( 'bookcreator_claude_missing_api_key', __( 'API key di Claude mancante.', 'bookcreator' ) );
    }

    $model_limits      = bookcreator_get_claude_model_limits();
    $selected_capab    = isset( $model_limits[ $model ] ) ? $model_limits[ $model ] : array();
    $max_output_tokens = isset( $selected_capab['max_output_tokens'] ) ? (int) $selected_capab['max_output_tokens'] : 4096;
    if ( $max_output_tokens <= 0 ) {
        $max_output_tokens = 4096;
    }

    if ( $output_margin <= 0 || $output_margin > 1 ) {
        $output_margin = 0.8;
    }

    $requested_tokens = isset( $args['max_tokens'] ) ? (int) $args['max_tokens'] : 0;
    if ( $requested_tokens <= 0 ) {
        $requested_tokens = (int) floor( $max_output_tokens * $output_margin );
    }

    $requested_tokens = max( 256, min( $max_output_tokens, $requested_tokens ) );

    $request_timeout = max( 5, min( 120, $timeout ) );

    $send_request = static function ( $model_name ) use ( $prompt, $request_timeout, $api_key, $requested_tokens ) {
        return wp_remote_post(
            'https://api.anthropic.com/v1/messages',
            array(
                'timeout' => $request_timeout,
                'headers' => array(
                    'x-api-key'         => $api_key,
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                    'accept'            => 'application/json',
                ),
                'body'    => wp_json_encode(
                    array(
                        'model'      => $model_name,
                        'max_tokens' => $requested_tokens,
                        'messages'   => array(
                            array(
                                'role'    => 'user',
                                'content' => $prompt,
                            ),
                        ),
                    )
                ),
            )
        );
    };

    $model_notice = '';
    $response     = $send_request( $model );

    if ( is_wp_error( $response ) ) {
        return new WP_Error( 'bookcreator_claude_request', sprintf( __( 'Errore durante la chiamata a Claude: %s', 'bookcreator' ), $response->get_error_message() ) );
    }

    $status_code = (int) wp_remote_retrieve_response_code( $response );
    $body_raw    = wp_remote_retrieve_body( $response );

    if ( 200 !== $status_code ) {
        $body_data  = json_decode( $body_raw, true );
        $error_type = '';
        if ( is_array( $body_data ) ) {
            if ( isset( $body_data['type'] ) && is_string( $body_data['type'] ) ) {
                $error_type = $body_data['type'];
            } elseif ( isset( $body_data['error']['type'] ) && is_string( $body_data['error']['type'] ) ) {
                $error_type = $body_data['error']['type'];
            }
        }

        if ( 'not_found_error' === $error_type && $model !== $default_model ) {
            $previous_model = $model;
            $model          = $default_model;
            $selected_capab = isset( $model_limits[ $model ] ) ? $model_limits[ $model ] : array();
            $max_output_tokens = isset( $selected_capab['max_output_tokens'] ) ? (int) $selected_capab['max_output_tokens'] : 4096;
            if ( $max_output_tokens <= 0 ) {
                $max_output_tokens = 4096;
            }
            $requested_tokens = max( 256, min( $max_output_tokens, $requested_tokens ) );

            $response = $send_request( $model );

            if ( is_wp_error( $response ) ) {
                return new WP_Error( 'bookcreator_claude_request', sprintf( __( 'Errore durante la chiamata a Claude: %s', 'bookcreator' ), $response->get_error_message() ) );
            }

            $status_code = (int) wp_remote_retrieve_response_code( $response );
            $body_raw    = wp_remote_retrieve_body( $response );

            if ( 200 !== $status_code ) {
                return new WP_Error( 'bookcreator_claude_response', sprintf( __( 'Claude ha restituito un errore (%1$d): %2$s', 'bookcreator' ), $status_code, $body_raw ) );
            }

            $allowed_models = bookcreator_get_allowed_claude_models();
            $from_label     = isset( $allowed_models[ $previous_model ] ) ? $allowed_models[ $previous_model ] : $previous_model;
            $to_label       = isset( $allowed_models[ $model ] ) ? $allowed_models[ $model ] : $model;
            $model_notice   = sprintf( __( 'Il modello %1$s non è disponibile. È stato utilizzato %2$s.', 'bookcreator' ), $from_label, $to_label );
        } else {
            return new WP_Error( 'bookcreator_claude_response', sprintf( __( 'Claude ha restituito un errore (%1$d): %2$s', 'bookcreator' ), $status_code, $body_raw ) );
        }
    }

    $body_data = json_decode( $body_raw, true );
    if ( ! is_array( $body_data ) || empty( $body_data['content'] ) || ! is_array( $body_data['content'] ) ) {
        return new WP_Error( 'bookcreator_claude_unexpected', __( 'Risposta inattesa da Claude AI.', 'bookcreator' ) );
    }

    $text_response = '';
    foreach ( $body_data['content'] as $segment ) {
        if ( isset( $segment['type'] ) && 'text' === $segment['type'] && isset( $segment['text'] ) ) {
            $text_response .= $segment['text'];
        }
    }

    if ( '' === $text_response ) {
        return new WP_Error( 'bookcreator_claude_empty', __( 'La risposta di Claude non contiene testo.', 'bookcreator' ) );
    }

    return array(
        'text'         => $text_response,
        'model_notice' => $model_notice,
    );
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

function bookcreator_ajax_generate_translation() {
    check_ajax_referer( 'bookcreator_generate_translation', 'nonce' );

    $post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
    $language = isset( $_POST['language'] ) ? sanitize_text_field( wp_unslash( $_POST['language'] ) ) : '';

    if ( $post_id <= 0 ) {
        wp_send_json_error( array( 'message' => __( 'Contenuto non valido.', 'bookcreator' ) ) );
    }

    $post = get_post( $post_id );
    if ( ! $post ) {
        wp_send_json_error( array( 'message' => __( 'Contenuto non valido.', 'bookcreator' ) ) );
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        wp_send_json_error( array( 'message' => __( 'Non hai i permessi per eseguire questa operazione.', 'bookcreator' ) ), 403 );
    }

    $result = bookcreator_generate_translation_for_post( $post, $language );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( array( 'message' => $result->get_error_message() ) );
    }

    wp_send_json_success(
        array(
            'language'     => $result['language'],
            'warnings'     => $result['warnings'],
            'model_notice' => $result['model_notice'],
            'message'      => __( 'Traduzione generata correttamente.', 'bookcreator' ),
        )
    );
}
add_action( 'wp_ajax_bookcreator_generate_translation', 'bookcreator_ajax_generate_translation' );

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
        'capability_type'    => array( 'bookcreator_book', 'bookcreator_books' ),
        'capabilities'       => bookcreator_get_post_type_capabilities_map( 'bookcreator_book', 'bookcreator_books' ),
        'map_meta_cap'       => true,
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
        'capability_type' => array( 'bookcreator_chapter', 'bookcreator_chapters' ),
        'capabilities'    => bookcreator_get_post_type_capabilities_map( 'bookcreator_chapter', 'bookcreator_chapters' ),
        'map_meta_cap'    => true,
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
        'default_term'      => array(
            'name' => 'Book',
            'slug' => 'book',
        ),
        'capabilities'      => array(
            'manage_terms' => 'manage_bookcreator_genres',
            'edit_terms'   => 'manage_bookcreator_genres',
            'delete_terms' => 'manage_bookcreator_genres',
            'assign_terms' => 'edit_bookcreator_books',
        ),
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
        'capability_type' => array( 'bookcreator_paragraph', 'bookcreator_paragraphs' ),
        'capabilities'    => bookcreator_get_post_type_capabilities_map( 'bookcreator_paragraph', 'bookcreator_paragraphs' ),
        'map_meta_cap'    => true,
    );

    register_post_type( 'bc_paragraph', $args );
}
add_action( 'init', 'bookcreator_register_paragraph_post_type' );

function bookcreator_add_thumbnail_support() {
    add_theme_support( 'post-thumbnails', array( 'book_creator', 'bc_chapter', 'bc_paragraph' ) );
}
add_action( 'after_setup_theme', 'bookcreator_add_thumbnail_support' );

function bookcreator_get_default_book_genre_id() {
    $term_id = (int) get_option( 'bookcreator_default_book_genre_id' );
    if ( $term_id ) {
        $term = get_term( $term_id, 'book_genre' );
        if ( $term && ! is_wp_error( $term ) ) {
            return (int) $term->term_id;
        }
    }

    $term = get_term_by( 'slug', 'book', 'book_genre' );
    if ( ! $term || is_wp_error( $term ) ) {
        $term = get_term_by( 'name', 'Book', 'book_genre' );
    }

    if ( $term && ! is_wp_error( $term ) ) {
        update_option( 'bookcreator_default_book_genre_id', (int) $term->term_id );

        return (int) $term->term_id;
    }

    return 0;
}

function bookcreator_force_default_book_genre( $terms, $object_id, $taxonomy, $append ) {
    if ( 'book_genre' !== $taxonomy ) {
        return $terms;
    }

    if ( current_user_can( 'manage_bookcreator_genres' ) ) {
        return $terms;
    }

    $default_id = bookcreator_get_default_book_genre_id();
    if ( ! $default_id ) {
        return $terms;
    }

    return array( (int) $default_id );
}
add_filter( 'pre_set_object_terms', 'bookcreator_force_default_book_genre', 10, 4 );

function bookcreator_ensure_default_book_genre_on_save( $post_id, $post, $update ) {
    if ( current_user_can( 'manage_bookcreator_genres' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    $default_id = bookcreator_get_default_book_genre_id();
    if ( ! $default_id ) {
        return;
    }

    $current_terms = wp_get_post_terms( $post_id, 'book_genre', array( 'fields' => 'ids' ) );
    if ( empty( $current_terms ) || ! in_array( $default_id, $current_terms, true ) ) {
        wp_set_post_terms( $post_id, array( $default_id ), 'book_genre', false );
    }
}
add_action( 'save_post_book_creator', 'bookcreator_ensure_default_book_genre_on_save', 20, 3 );

function bookcreator_maybe_remove_book_genre_metabox() {
    if ( current_user_can( 'manage_bookcreator_genres' ) ) {
        return;
    }

    remove_meta_box( 'book_genrediv', 'book_creator', 'side' );
}
add_action( 'add_meta_boxes_book_creator', 'bookcreator_maybe_remove_book_genre_metabox', 99 );

function bookcreator_limit_admin_posts_to_author( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() ) {
        return;
    }

    $post_type = $query->get( 'post_type' );

    if ( ! $post_type ) {
        return;
    }

    $capability_map = array(
        'book_creator' => 'edit_others_bookcreator_books',
        'bc_chapter'   => 'edit_others_bookcreator_chapters',
        'bc_paragraph' => 'edit_others_bookcreator_paragraphs',
    );

    if ( isset( $capability_map[ $post_type ] ) && ! current_user_can( $capability_map[ $post_type ] ) ) {
        $query->set( 'author', get_current_user_id() );
    }
}
add_action( 'pre_get_posts', 'bookcreator_limit_admin_posts_to_author' );

function bookcreator_get_author_post_counts( $post_type, $author_id ) {
    global $wpdb;

    $cache_key = sprintf( 'bookcreator_%s_counts_%d', $post_type, $author_id );
    $counts    = wp_cache_get( $cache_key, 'counts' );

    if ( false === $counts ) {
        $counts = array();

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s AND post_author = %d GROUP BY post_status",
                $post_type,
                $author_id
            ),
            ARRAY_A
        );

        foreach ( (array) $results as $row ) {
            $counts[ $row['post_status'] ] = (int) $row['num_posts'];
        }

        $counts = (object) $counts;
        wp_cache_set( $cache_key, $counts, 'counts' );
    }

    return $counts;
}

function bookcreator_user_needs_limited_counts( $post_type ) {
    $capability_map = array(
        'book_creator' => 'edit_others_bookcreator_books',
        'bc_chapter'   => 'edit_others_bookcreator_chapters',
        'bc_paragraph' => 'edit_others_bookcreator_paragraphs',
    );

    return isset( $capability_map[ $post_type ] ) && ! current_user_can( $capability_map[ $post_type ] );
}

function bookcreator_replace_view_count( $html, $count ) {
    if ( ! preg_match( '/\(\d+\)/', $html ) ) {
        return $html;
    }

    return preg_replace( '/\(\d+\)/', '(' . (int) $count . ')', $html );
}

function bookcreator_filter_views_counts_for_author( $views, $post_type ) {
    if ( ! is_array( $views ) || ! bookcreator_user_needs_limited_counts( $post_type ) ) {
        return $views;
    }

    $author_id = get_current_user_id();
    $counts    = bookcreator_get_author_post_counts( $post_type, $author_id );

    $all_statuses     = get_post_stati( array( 'show_in_admin_all_list' => true ) );
    $status_list_keys = array_keys( $views );

    $all_total = 0;
    foreach ( $all_statuses as $status ) {
        if ( isset( $counts->$status ) ) {
            $all_total += (int) $counts->$status;
        }
    }

    if ( isset( $views['all'] ) ) {
        $views['all'] = bookcreator_replace_view_count( $views['all'], $all_total );
    }

    foreach ( $status_list_keys as $key ) {
        if ( 'all' === $key || ! isset( $views[ $key ] ) ) {
            continue;
        }

        $count = 0;

        switch ( $key ) {
            case 'publish':
                $count = isset( $counts->publish ) ? (int) $counts->publish : 0;
                break;
            case 'draft':
                $count = isset( $counts->draft ) ? (int) $counts->draft : 0;
                break;
            case 'pending':
                $count = isset( $counts->pending ) ? (int) $counts->pending : 0;
                break;
            case 'future':
                $count = isset( $counts->future ) ? (int) $counts->future : 0;
                break;
            case 'private':
                $count = isset( $counts->private ) ? (int) $counts->private : 0;
                break;
            case 'trash':
                $count = isset( $counts->trash ) ? (int) $counts->trash : 0;
                break;
            default:
                if ( isset( $counts->$key ) ) {
                    $count = (int) $counts->$key;
                }
        }

        $views[ $key ] = bookcreator_replace_view_count( $views[ $key ], $count );
    }

    return $views;
}

function bookcreator_filter_chapter_views( $views ) {
    return bookcreator_filter_views_counts_for_author( $views, 'bc_chapter' );
}
add_filter( 'views_edit-bc_chapter', 'bookcreator_filter_chapter_views' );

function bookcreator_filter_paragraph_views( $views ) {
    return bookcreator_filter_views_counts_for_author( $views, 'bc_paragraph' );
}
add_filter( 'views_edit-bc_paragraph', 'bookcreator_filter_paragraph_views' );

/**
 * Flush rewrite rules on activation/deactivation and ensure default term exists.
 */
function bookcreator_activate() {
    bookcreator_register_roles();
    bookcreator_register_post_type();
    bookcreator_register_paragraph_post_type();
    if ( ! term_exists( 'Book', 'book_genre' ) ) {
        $term = wp_insert_term( 'Book', 'book_genre', array( 'slug' => 'book' ) );
        if ( ! is_wp_error( $term ) && isset( $term['term_id'] ) ) {
            update_option( 'bookcreator_default_book_genre_id', (int) $term['term_id'] );
        }
    } else {
        $term = get_term_by( 'name', 'Book', 'book_genre' );
        if ( $term && ! is_wp_error( $term ) ) {
            update_option( 'bookcreator_default_book_genre_id', (int) $term->term_id );
        }
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
    add_meta_box( 'bc_book_translations_languages', __( 'Traduzioni', 'bookcreator' ), 'bookcreator_meta_box_translations_languages', 'book_creator', 'side', 'default' );
    add_meta_box( 'bc_book_translations_content', __( 'Contenuti tradotti', 'bookcreator' ), 'bookcreator_meta_box_translations_content', 'book_creator', 'normal', 'low' );
    add_meta_box( 'bc_chapter_books', __( 'Books', 'bookcreator' ), 'bookcreator_meta_box_chapter_books', 'bc_chapter', 'side', 'default' );
    add_meta_box( 'bc_chapter_translations_languages', __( 'Traduzioni', 'bookcreator' ), 'bookcreator_meta_box_translations_languages', 'bc_chapter', 'side', 'default' );
    add_meta_box( 'bc_chapter_translations_content', __( 'Contenuti tradotti', 'bookcreator' ), 'bookcreator_meta_box_translations_content', 'bc_chapter', 'normal', 'low' );
    add_meta_box( 'bc_paragraph_chapters', __( 'Chapters', 'bookcreator' ), 'bookcreator_meta_box_paragraph_chapters', 'bc_paragraph', 'side', 'default' );
    add_meta_box( 'bc_paragraph_footnotes', __( 'Footnotes', 'bookcreator' ), 'bookcreator_meta_box_paragraph_footnotes', 'bc_paragraph', 'normal', 'default' );
    add_meta_box( 'bc_paragraph_citations', __( 'Citations', 'bookcreator' ), 'bookcreator_meta_box_paragraph_citations', 'bc_paragraph', 'normal', 'default' );
    add_meta_box( 'bc_paragraph_translations_languages', __( 'Traduzioni', 'bookcreator' ), 'bookcreator_meta_box_translations_languages', 'bc_paragraph', 'side', 'default' );
    add_meta_box( 'bc_paragraph_translations_content', __( 'Contenuti tradotti', 'bookcreator' ), 'bookcreator_meta_box_translations_content', 'bc_paragraph', 'normal', 'low' );
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

function bookcreator_sanitize_translation_language( $language ) {
    $language = strtolower( (string) $language );
    $language = str_replace( array( ' ', '_' ), '-', $language );
    $language = preg_replace( '/[^a-z0-9\-]/', '', $language );

    return $language;
}

function bookcreator_get_translation_meta_key( $post_type ) {
    switch ( $post_type ) {
        case 'book_creator':
            return 'bc_translations';
        case 'bc_chapter':
            return 'bc_chapter_translations';
        case 'bc_paragraph':
            return 'bc_paragraph_translations';
    }

    return '';
}

function bookcreator_get_translation_fields_config( $post_type ) {
    if ( 'book_creator' === $post_type ) {
        return array(
            'post_title'      => array(
                'label'             => __( 'Titolo', 'bookcreator' ),
                'type'              => 'text',
                'source'            => 'post',
                'sanitize_callback' => 'sanitize_text_field',
                'format'            => 'text',
            ),
            'bc_subtitle'     => array(
                'label'             => __( 'Sottotitolo', 'bookcreator' ),
                'type'              => 'text',
                'source'            => 'meta',
                'sanitize_callback' => 'sanitize_text_field',
                'format'            => 'text',
            ),
            'bc_author'       => array(
                'label'             => __( 'Autore principale', 'bookcreator' ),
                'type'              => 'text',
                'source'            => 'meta',
                'sanitize_callback' => 'sanitize_text_field',
                'format'            => 'text',
            ),
            'bc_coauthors'    => array(
                'label'             => __( 'Co-autori', 'bookcreator' ),
                'type'              => 'text',
                'source'            => 'meta',
                'sanitize_callback' => 'sanitize_text_field',
                'format'            => 'text',
            ),
            'bc_publisher'    => array(
                'label'             => __( 'Editore', 'bookcreator' ),
                'type'              => 'text',
                'source'            => 'meta',
                'sanitize_callback' => 'sanitize_text_field',
                'format'            => 'text',
            ),
            'bc_isbn'         => array(
                'label'             => __( 'ISBN', 'bookcreator' ),
                'type'              => 'text',
                'source'            => 'meta',
                'sanitize_callback' => 'sanitize_text_field',
                'format'            => 'text',
            ),
            'bc_pub_date'     => array(
                'label'             => __( 'Data di pubblicazione', 'bookcreator' ),
                'type'              => 'text',
                'source'            => 'meta',
                'sanitize_callback' => 'sanitize_text_field',
                'format'            => 'text',
            ),
            'bc_edition'      => array(
                'label'             => __( 'Edizione/Versione', 'bookcreator' ),
                'type'              => 'text',
                'source'            => 'meta',
                'sanitize_callback' => 'sanitize_text_field',
                'format'            => 'text',
            ),
            'bc_language'     => array(
                'label'             => __( 'Lingua', 'bookcreator' ),
                'type'              => 'text',
                'source'            => 'meta',
                'sanitize_callback' => 'sanitize_text_field',
                'format'            => 'text',
                'translatable'      => false,
            ),
            'bc_description'  => array(
                'label'             => __( 'Descrizione', 'bookcreator' ),
                'type'              => 'editor',
                'source'            => 'meta',
                'sanitize_callback' => 'wp_kses_post',
                'format'            => 'html',
            ),
            'bc_frontispiece' => array(
                'label'             => __( 'Frontespizio', 'bookcreator' ),
                'type'              => 'editor',
                'source'            => 'meta',
                'sanitize_callback' => 'wp_kses_post',
                'format'            => 'html',
            ),
            'bc_copyright'    => array(
                'label'             => __( 'Copyright', 'bookcreator' ),
                'type'              => 'textarea',
                'source'            => 'meta',
                'sanitize_callback' => 'wp_kses_post',
                'format'            => 'html',
            ),
            'bc_dedication'   => array(
                'label'             => __( 'Dedica', 'bookcreator' ),
                'type'              => 'textarea',
                'source'            => 'meta',
                'sanitize_callback' => 'wp_kses_post',
                'format'            => 'html',
            ),
            'bc_preface'      => array(
                'label'             => __( 'Prefazione', 'bookcreator' ),
                'type'              => 'textarea',
                'source'            => 'meta',
                'sanitize_callback' => 'wp_kses_post',
                'format'            => 'html',
            ),
            'bc_acknowledgments' => array(
                'label'             => __( 'Ringraziamenti', 'bookcreator' ),
                'type'              => 'textarea',
                'source'            => 'meta',
                'sanitize_callback' => 'wp_kses_post',
                'format'            => 'html',
            ),
            'bc_appendix'     => array(
                'label'             => __( 'Appendice', 'bookcreator' ),
                'type'              => 'textarea',
                'source'            => 'meta',
                'sanitize_callback' => 'wp_kses_post',
                'format'            => 'html',
            ),
            'bc_bibliography' => array(
                'label'             => __( 'Bibliografia', 'bookcreator' ),
                'type'              => 'editor',
                'source'            => 'meta',
                'sanitize_callback' => 'wp_kses_post',
                'format'            => 'html',
            ),
            'bc_author_note'  => array(
                'label'             => __( 'Nota dell\'autore', 'bookcreator' ),
                'type'              => 'textarea',
                'source'            => 'meta',
                'sanitize_callback' => 'wp_kses_post',
                'format'            => 'html',
            ),
        );
    }

    if ( 'bc_chapter' === $post_type ) {
        return array(
            'post_title'   => array(
                'label'             => __( 'Titolo del capitolo', 'bookcreator' ),
                'type'              => 'text',
                'source'            => 'post',
                'sanitize_callback' => 'sanitize_text_field',
                'format'            => 'text',
            ),
            'post_content' => array(
                'label'             => __( 'Contenuto', 'bookcreator' ),
                'type'              => 'editor',
                'source'            => 'post',
                'sanitize_callback' => 'wp_kses_post',
                'format'            => 'html',
            ),
        );
    }

    if ( 'bc_paragraph' === $post_type ) {
        return array(
            'post_title'   => array(
                'label'             => __( 'Titolo del paragrafo', 'bookcreator' ),
                'type'              => 'text',
                'source'            => 'post',
                'sanitize_callback' => 'sanitize_text_field',
                'format'            => 'text',
            ),
            'post_content' => array(
                'label'             => __( 'Contenuto', 'bookcreator' ),
                'type'              => 'editor',
                'source'            => 'post',
                'sanitize_callback' => 'wp_kses_post',
                'format'            => 'html',
            ),
            'bc_footnotes' => array(
                'label'             => __( 'Note', 'bookcreator' ),
                'type'              => 'editor',
                'source'            => 'meta',
                'sanitize_callback' => 'wp_kses_post',
                'format'            => 'html',
            ),
            'bc_citations' => array(
                'label'             => __( 'Citazioni', 'bookcreator' ),
                'type'              => 'editor',
                'source'            => 'meta',
                'sanitize_callback' => 'wp_kses_post',
                'format'            => 'html',
            ),
        );
    }

    return array();
}

function bookcreator_get_translation_marker( $field_key ) {
    $marker = strtoupper( preg_replace( '/[^a-z0-9]+/i', '_', (string) $field_key ) );

    return 'FIELD_' . $marker;
}

function bookcreator_get_translations_for_post( $post_id, $post_type ) {
    $meta_key = bookcreator_get_translation_meta_key( $post_type );
    if ( ! $meta_key ) {
        return array();
    }

    $stored = get_post_meta( $post_id, $meta_key, true );
    if ( ! is_array( $stored ) ) {
        $stored = array();
    }

    $translations = array();

    foreach ( $stored as $language_code => $translation ) {
        $language = isset( $translation['language'] ) ? $translation['language'] : $language_code;
        $language = bookcreator_sanitize_translation_language( $language );

        if ( '' === $language ) {
            continue;
        }

        $fields    = isset( $translation['fields'] ) && is_array( $translation['fields'] ) ? $translation['fields'] : array();
        $generated = isset( $translation['generated'] ) ? $translation['generated'] : '';
        $cover_id           = isset( $translation['cover_id'] ) ? (int) $translation['cover_id'] : 0;
        $publisher_logo_id  = isset( $translation['publisher_logo_id'] ) ? (int) $translation['publisher_logo_id'] : 0;

        $translations[ $language ] = array(
            'language'  => $language,
            'fields'    => $fields,
            'generated' => $generated,
            'cover_id'  => $cover_id,
            'publisher_logo_id' => $publisher_logo_id,
        );
    }

    ksort( $translations );

    return $translations;
}

function bookcreator_get_translation_language_labels( $post_id, $post_type ) {
    $translations = bookcreator_get_translations_for_post( $post_id, $post_type );
    if ( ! $translations ) {
        return array();
    }

    $labels = array();
    foreach ( $translations as $language => $translation ) {
        $label    = bookcreator_get_language_label( $language );
        $labels[] = $label ? $label : $language;
    }

    sort( $labels );

    return $labels;
}

function bookcreator_get_translation_for_language( $post_id, $post_type, $language ) {
    $language = bookcreator_sanitize_translation_language( $language );
    if ( '' === $language ) {
        return null;
    }

    $translations = bookcreator_get_translations_for_post( $post_id, $post_type );
    if ( isset( $translations[ $language ] ) ) {
        return $translations[ $language ];
    }

    return null;
}

function bookcreator_get_translation_field_value( $translation, $field_key, $default = '' ) {
    if ( ! is_array( $translation ) ) {
        return $default;
    }

    if ( isset( $translation['fields'][ $field_key ] ) ) {
        $value = $translation['fields'][ $field_key ];
        if ( '' !== $value && null !== $value ) {
            return $value;
        }
    }

    return $default;
}

function bookcreator_get_template_texts_definitions() {
    return array(
        'cover_title' => array(
            'label'   => __( 'Titolo sezione Copertina', 'bookcreator' ),
            'default' => __( 'Copertina', 'bookcreator' ),
        ),
        'frontispiece_title' => array(
            'label'   => __( 'Titolo sezione Frontespizio', 'bookcreator' ),
            'default' => __( 'Frontespizio', 'bookcreator' ),
        ),
        'copyright_title' => array(
            'label'   => __( 'Titolo sezione Copyright', 'bookcreator' ),
            'default' => __( 'Copyright', 'bookcreator' ),
        ),
        'dedication_title' => array(
            'label'   => __( 'Titolo sezione Dedica', 'bookcreator' ),
            'default' => __( 'Dedica', 'bookcreator' ),
        ),
        'preface_title' => array(
            'label'   => __( 'Titolo sezione Prefazione', 'bookcreator' ),
            'default' => __( 'Prefazione', 'bookcreator' ),
        ),
        'preface_index_heading' => array(
            'label'   => __( 'Titolo indice nella prefazione', 'bookcreator' ),
            'default' => __( 'Indice', 'bookcreator' ),
        ),
        'toc_document_title' => array(
            'label'        => __( 'Titolo documento indice', 'bookcreator' ),
            'default'      => __( 'Indice - %s', 'bookcreator' ),
            'placeholders' => array( '%s' ),
            'description'  => __( 'Usa %s come segnaposto per il titolo del libro.', 'bookcreator' ),
        ),
        'toc_heading' => array(
            'label'   => __( 'Intestazione indice', 'bookcreator' ),
            'default' => __( 'Indice', 'bookcreator' ),
        ),
        'book_index_heading' => array(
            'label'   => __( 'Titolo indice nel template', 'bookcreator' ),
            'default' => __( 'Indice', 'bookcreator' ),
        ),
        'chapter_fallback_title' => array(
            'label'        => __( 'Titolo predefinito dei capitoli', 'bookcreator' ),
            'default'      => __( 'Capitolo %s', 'bookcreator' ),
            'placeholders' => array( '%s' ),
        ),
        'paragraph_fallback_title' => array(
            'label'        => __( 'Titolo predefinito dei paragrafi', 'bookcreator' ),
            'default'      => __( 'Paragrafo %s', 'bookcreator' ),
            'placeholders' => array( '%s' ),
        ),
        'footnotes_heading' => array(
            'label'   => __( 'Titolo sezione Note', 'bookcreator' ),
            'default' => __( 'Note', 'bookcreator' ),
        ),
        'citations_heading' => array(
            'label'   => __( 'Titolo sezione Citazioni', 'bookcreator' ),
            'default' => __( 'Citazioni', 'bookcreator' ),
        ),
        'publication_date_label' => array(
            'label'   => __( 'Etichetta data di pubblicazione', 'bookcreator' ),
            'default' => __( 'Data di pubblicazione', 'bookcreator' ),
        ),
        'appendix_title' => array(
            'label'   => __( 'Titolo sezione Appendice', 'bookcreator' ),
            'default' => __( 'Appendice', 'bookcreator' ),
        ),
        'bibliography_title' => array(
            'label'   => __( 'Titolo sezione Bibliografia', 'bookcreator' ),
            'default' => __( 'Bibliografia', 'bookcreator' ),
        ),
        'author_note_title' => array(
            'label'   => __( 'Titolo sezione Nota dell\'autore', 'bookcreator' ),
            'default' => __( 'Nota dell\'autore', 'bookcreator' ),
        ),
        'acknowledgments_title' => array(
            'label'   => __( 'Titolo sezione Ringraziamenti', 'bookcreator' ),
            'default' => __( 'Ringraziamenti', 'bookcreator' ),
        ),
        'cover_caption' => array(
            'label'   => __( 'Didascalia copertina', 'bookcreator' ),
            'default' => __( 'Copertina', 'bookcreator' ),
        ),
    );
}

function bookcreator_get_template_texts_storage() {
    $stored = get_option( 'bookcreator_template_texts', array() );

    return is_array( $stored ) ? $stored : array();
}

function bookcreator_get_template_texts_base_overrides() {
    $storage = bookcreator_get_template_texts_storage();
    $base    = isset( $storage['base'] ) && is_array( $storage['base'] ) ? $storage['base'] : array();
    $definitions = bookcreator_get_template_texts_definitions();
    $overrides   = array();

    foreach ( $definitions as $key => $definition ) {
        if ( isset( $base[ $key ] ) && '' !== $base[ $key ] ) {
            $overrides[ $key ] = sanitize_text_field( $base[ $key ] );
        }
    }

    return $overrides;
}

function bookcreator_get_template_texts_translations() {
    $storage      = bookcreator_get_template_texts_storage();
    $translations = isset( $storage['translations'] ) && is_array( $storage['translations'] ) ? $storage['translations'] : array();
    $normalized   = array();

    foreach ( $translations as $language => $data ) {
        $language = isset( $data['language'] ) ? $data['language'] : $language;
        $language = bookcreator_sanitize_translation_language( $language );
        if ( '' === $language ) {
            continue;
        }

        $fields = isset( $data['fields'] ) && is_array( $data['fields'] ) ? $data['fields'] : array();
        $sanitized_fields = array();
        foreach ( bookcreator_get_template_texts_definitions() as $key => $definition ) {
            if ( isset( $fields[ $key ] ) && '' !== $fields[ $key ] ) {
                $sanitized_fields[ $key ] = sanitize_text_field( $fields[ $key ] );
            }
        }

        $generated = isset( $data['generated'] ) ? sanitize_text_field( $data['generated'] ) : '';

        $normalized[ $language ] = array(
            'language'  => $language,
            'fields'    => $sanitized_fields,
            'generated' => $generated,
        );
    }

    ksort( $normalized );

    return $normalized;
}

function bookcreator_get_template_text_marker( $key ) {
    $marker = strtoupper( preg_replace( '/[^a-z0-9]+/i', '_', (string) $key ) );

    return 'TEMPLATE_TEXT_' . $marker;
}

function bookcreator_normalize_template_text_value( $value, $definition, $fallback = null, &$warnings = array() ) {
    $value = sanitize_text_field( (string) $value );
    $placeholders = isset( $definition['placeholders'] ) ? (array) $definition['placeholders'] : array();

    if ( $placeholders ) {
        foreach ( $placeholders as $placeholder ) {
            if ( false === strpos( $value, $placeholder ) ) {
                $label        = isset( $definition['label'] ) ? $definition['label'] : '';
                $warnings[]   = sprintf( __( 'Il testo "%1$s" deve contenere il segnaposto %2$s. È stato mantenuto il contenuto originale.', 'bookcreator' ), $label, $placeholder );
                $default      = isset( $definition['default'] ) ? $definition['default'] : '';
                $fallback_val = null !== $fallback ? $fallback : $default;

                return sanitize_text_field( (string) $fallback_val );
            }
        }
    }

    return $value;
}

function bookcreator_sanitize_template_texts_base_input( $input, &$warnings = array() ) {
    $definitions = bookcreator_get_template_texts_definitions();
    $sanitized   = array();

    foreach ( $definitions as $key => $definition ) {
        if ( ! isset( $input[ $key ] ) ) {
            continue;
        }

        $raw_value = (string) $input[ $key ];
        if ( '' === trim( $raw_value ) ) {
            continue;
        }

        $normalized = bookcreator_normalize_template_text_value( $raw_value, $definition, isset( $definition['default'] ) ? $definition['default'] : '', $warnings );

        if ( '' === $normalized ) {
            continue;
        }

        $default_value = isset( $definition['default'] ) ? sanitize_text_field( (string) $definition['default'] ) : '';
        if ( $normalized === $default_value ) {
            continue;
        }

        $sanitized[ $key ] = $normalized;
    }

    return $sanitized;
}

function bookcreator_sanitize_template_text_translations_input( $input, &$warnings = array(), $base_texts = null ) {
    $definitions = bookcreator_get_template_texts_definitions();
    if ( null === $base_texts ) {
        $base_texts = bookcreator_get_all_template_texts();
    }
    $sanitized   = array();

    foreach ( $input as $language => $data ) {
        $language = isset( $data['language'] ) ? $data['language'] : $language;
        $language = bookcreator_sanitize_translation_language( $language );
        if ( '' === $language ) {
            continue;
        }

        $fields_input = isset( $data['fields'] ) && is_array( $data['fields'] ) ? $data['fields'] : array();
        $fields       = array();
        $has_content  = false;

        foreach ( $definitions as $key => $definition ) {
            $raw_value = isset( $fields_input[ $key ] ) ? $fields_input[ $key ] : '';
            if ( '' === trim( (string) $raw_value ) ) {
                $fields[ $key ] = '';
                continue;
            }

            $fallback = isset( $base_texts[ $key ] ) ? $base_texts[ $key ] : ( isset( $definition['default'] ) ? $definition['default'] : '' );
            $normalized = bookcreator_normalize_template_text_value( $raw_value, $definition, $fallback, $warnings );
            if ( '' !== $normalized ) {
                $fields[ $key ] = $normalized;
                $has_content    = true;
            }
        }

        if ( ! $has_content ) {
            continue;
        }

        $generated = isset( $data['generated'] ) ? sanitize_text_field( $data['generated'] ) : '';

        $sanitized[ $language ] = array(
            'language'  => $language,
            'fields'    => $fields,
            'generated' => $generated,
        );
    }

    ksort( $sanitized );

    return $sanitized;
}

function bookcreator_update_template_texts_storage( $base_overrides, $translations ) {
    $storage = array(
        'base'         => is_array( $base_overrides ) ? $base_overrides : array(),
        'translations' => is_array( $translations ) ? $translations : array(),
    );

    update_option( 'bookcreator_template_texts', $storage );
}

function bookcreator_get_all_template_texts( $language = '' ) {
    $definitions   = bookcreator_get_template_texts_definitions();
    $base_overrides = bookcreator_get_template_texts_base_overrides();
    $translations   = bookcreator_get_template_texts_translations();
    $language       = bookcreator_sanitize_translation_language( $language );
    $language_fields = array();

    if ( $language && isset( $translations[ $language ]['fields'] ) ) {
        $language_fields = $translations[ $language ]['fields'];
    }

    $texts = array();

    foreach ( $definitions as $key => $definition ) {
        if ( $language && isset( $language_fields[ $key ] ) && '' !== $language_fields[ $key ] ) {
            $texts[ $key ] = $language_fields[ $key ];
        } elseif ( isset( $base_overrides[ $key ] ) && '' !== $base_overrides[ $key ] ) {
            $texts[ $key ] = $base_overrides[ $key ];
        } else {
            $texts[ $key ] = isset( $definition['default'] ) ? $definition['default'] : '';
        }
    }

    return $texts;
}

function bookcreator_get_template_text( $key, $language = '' ) {
    $texts = bookcreator_get_all_template_texts( $language );

    return isset( $texts[ $key ] ) ? $texts[ $key ] : '';
}

function bookcreator_store_template_text_translation( $language, $fields, $generated ) {
    $language = bookcreator_sanitize_translation_language( $language );
    if ( '' === $language ) {
        return;
    }

    $storage      = bookcreator_get_template_texts_storage();
    $translations = isset( $storage['translations'] ) && is_array( $storage['translations'] ) ? $storage['translations'] : array();

    $translations[ $language ] = array(
        'language'  => $language,
        'fields'    => $fields,
        'generated' => $generated,
    );

    ksort( $translations );

    $storage['translations'] = $translations;

    update_option( 'bookcreator_template_texts', $storage );
}

function bookcreator_generate_template_text_translation( $language ) {
    if ( ! bookcreator_is_claude_enabled() ) {
        return new WP_Error( 'bookcreator_template_texts_claude_disabled', __( 'Configura l\'integrazione con Claude AI prima di generare una traduzione.', 'bookcreator' ) );
    }

    $language = bookcreator_sanitize_translation_language( $language );
    if ( '' === $language ) {
        return new WP_Error( 'bookcreator_template_texts_invalid_language', __( 'La lingua selezionata non è valida.', 'bookcreator' ) );
    }

    $language_label = bookcreator_get_language_label( $language );
    $definitions    = bookcreator_get_template_texts_definitions();
    $source_texts   = bookcreator_get_all_template_texts();

    $instructions  = sprintf( __( 'Traduci i testi statici del template nella lingua %s.', 'bookcreator' ), $language_label ? $language_label : strtoupper( $language ) ) . "\n";
    $instructions .= __( 'Mantieni invariati i marcatori [TEMPLATE_TEXT_*_START] e [TEMPLATE_TEXT_*_END] e non aggiungere testo al di fuori di essi.', 'bookcreator' ) . "\n";

    $placeholders = array();
    foreach ( $definitions as $definition ) {
        if ( ! empty( $definition['placeholders'] ) ) {
            foreach ( (array) $definition['placeholders'] as $placeholder ) {
                if ( ! in_array( $placeholder, $placeholders, true ) ) {
                    $placeholders[] = $placeholder;
                }
            }
        }
    }

    if ( $placeholders ) {
        $instructions .= sprintf( __( 'Mantieni i segnaposto nei testi: %s.', 'bookcreator' ), implode( ', ', $placeholders ) ) . "\n";
    }

    $claude_settings = bookcreator_get_claude_settings();
    $global_prompt   = isset( $claude_settings['translation_prompt'] ) ? trim( (string) $claude_settings['translation_prompt'] ) : '';

    if ( '' !== $global_prompt ) {
        $instructions .= "\n" . __( 'Istruzioni aggiuntive:', 'bookcreator' ) . "\n" . $global_prompt . "\n";
    }

    $body = '';
    foreach ( $definitions as $key => $definition ) {
        $marker = bookcreator_get_template_text_marker( $key );
        $value  = isset( $source_texts[ $key ] ) ? (string) $source_texts[ $key ] : '';

        $body .= '[' . $marker . '_START]' . "\n";
        $body .= $value . "\n";
        $body .= '[' . $marker . '_END]' . "\n\n";
    }

    $prompt = $instructions . "\n" . $body;

    $response = bookcreator_send_claude_message( $prompt );
    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $warnings = array();
    $fields   = array();

    foreach ( $definitions as $key => $definition ) {
        $marker  = bookcreator_get_template_text_marker( $key );
        $pattern = '/\[' . preg_quote( $marker . '_START', '/' ) . '\](.*?)\[' . preg_quote( $marker . '_END', '/' ) . '\]/is';
        $fallback = isset( $source_texts[ $key ] ) ? $source_texts[ $key ] : ( isset( $definition['default'] ) ? $definition['default'] : '' );

        $raw_value = null;
        if ( preg_match( $pattern, $response['text'], $matches ) ) {
            $raw_value = trim( $matches[1] );
        }

        if ( null === $raw_value ) {
            $raw_value = $fallback;
            $warnings[] = sprintf( __( 'Il testo %s non è stato trovato nella risposta di Claude. È stato mantenuto il contenuto originale.', 'bookcreator' ), isset( $definition['label'] ) ? $definition['label'] : $key );
        }

        $fields[ $key ] = bookcreator_normalize_template_text_value( $raw_value, $definition, $fallback, $warnings );
    }

    $generated = current_time( 'mysql' );
    bookcreator_store_template_text_translation( $language, $fields, $generated );

    return array(
        'language'     => $language,
        'fields'       => $fields,
        'warnings'     => $warnings,
        'generated'    => $generated,
        'model_notice' => isset( $response['model_notice'] ) ? $response['model_notice'] : '',
    );
}

function bookcreator_render_translation_languages_box( $post ) {
    $post_id   = (int) $post->ID;
    $post_type = $post->post_type;
    $translations = bookcreator_get_translations_for_post( $post_id, $post_type );
    $languages    = bookcreator_get_language_options();

    foreach ( $translations as $language => $translation ) {
        if ( ! isset( $languages[ $language ] ) ) {
            $languages[ $language ] = $language;
        }
    }

    $existing_languages = implode( ',', array_keys( $translations ) );
    $claude_enabled     = bookcreator_is_claude_enabled();

    $no_languages_text = __( 'Nessuna traduzione disponibile.', 'bookcreator' );

    echo '<div class="bookcreator-translation-languages" data-existing-languages="' . esc_attr( $existing_languages ) . '">';

    echo '<p><strong>' . esc_html__( 'Lingue disponibili', 'bookcreator' ) . '</strong></p>';

    if ( $translations ) {
        echo '<p class="bookcreator-translation-no-languages" style="display:none;">' . esc_html( $no_languages_text ) . '</p>';
        echo '<ul class="bookcreator-translation-languages-list">';
        foreach ( $translations as $language => $translation ) {
            $label       = bookcreator_get_language_label( $language );
            $section_id  = 'bookcreator-translation-' . sanitize_html_class( $language );
            $generated   = isset( $translation['generated'] ) ? $translation['generated'] : '';
            $display_generated = '';
            if ( $generated ) {
                $display_generated = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $generated ) );
            }

            $display_label = $label ? $label : $language;

            echo '<li>';
            echo '<a href="#' . esc_attr( $section_id ) . '">' . esc_html( $display_label ) . '</a>';
            if ( $display_generated ) {
                echo '<br /><small>' . esc_html( $display_generated ) . '</small>';
            }
            echo ' <button type="button" class="bookcreator-translation-delete button-link" data-language="' . esc_attr( $language ) . '" data-section="' . esc_attr( $section_id ) . '" data-label="' . esc_attr( $display_label ) . '"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span><span class="screen-reader-text">' . esc_html__( 'Elimina traduzione', 'bookcreator' ) . '</span></button>';
            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p class="bookcreator-translation-no-languages">' . esc_html( $no_languages_text ) . '</p>';
    }

    if ( 'auto-draft' === $post->post_status || ! $post_id ) {
        echo '<p>' . esc_html__( 'Salva il contenuto prima di generare una traduzione.', 'bookcreator' ) . '</p>';
        echo '</div>';

        return;
    }

    $button_label = __( 'Genera traduzione con Claude', 'bookcreator' );

    switch ( $post_type ) {
        case 'bc_chapter':
            $button_label = __( 'Traduci capitolo con Claude', 'bookcreator' );
            break;
        case 'bc_paragraph':
            $button_label = __( 'Traduci paragrafo con Claude', 'bookcreator' );
            break;
    }

    echo '<hr />';
    echo '<p><label for="bookcreator-translation-language-select">' . esc_html__( 'Nuova lingua', 'bookcreator' ) . '</label></p>';
    echo '<select id="bookcreator-translation-language-select" class="bookcreator-translation-language-select">';
    echo '<option value="">' . esc_html__( 'Seleziona una lingua', 'bookcreator' ) . '</option>';
    foreach ( $languages as $code => $label ) {
        $option_attrs = '';
        if ( isset( $translations[ $code ] ) ) {
            $option_attrs .= ' data-existing="1"';
        }
        echo '<option value="' . esc_attr( $code ) . '"' . $option_attrs . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select>';

    echo '<p><button type="button" class="button button-primary bookcreator-translation-generate" data-post-id="' . esc_attr( $post_id ) . '" data-post-type="' . esc_attr( $post_type ) . '"' . ( $claude_enabled ? '' : ' disabled="disabled"' ) . '>' . esc_html( $button_label ) . '</button> <span class="spinner"></span></p>';

    if ( ! $claude_enabled ) {
        echo '<p class="description">' . esc_html__( 'Configura l\'integrazione con Claude AI per abilitare questa funzionalità.', 'bookcreator' ) . '</p>';
    }

    echo '<div class="bookcreator-translation-feedback" aria-live="polite"></div>';
    echo '</div>';
}

function bookcreator_render_translation_content_box( $post ) {
    $post_id   = (int) $post->ID;
    $post_type = $post->post_type;
    $translations = bookcreator_get_translations_for_post( $post_id, $post_type );
    $fields_config = bookcreator_get_translation_fields_config( $post_type );
    $meta_key      = bookcreator_get_translation_meta_key( $post_type );

    if ( $meta_key ) {
        echo '<input type="hidden" name="' . esc_attr( $meta_key ) . '_submitted" value="1" />';
    }

    if ( ! $translations ) {
        echo '<p>' . esc_html__( 'Genera una traduzione per visualizzare i campi in questa sezione.', 'bookcreator' ) . '</p>';

        return;
    }

    $empty_message        = __( 'Genera una traduzione per visualizzare i campi in questa sezione.', 'bookcreator' );
    $include_media_fields = ( 'book_creator' === $post_type );

    echo '<div class="bookcreator-translation-sections" data-empty-text="' . esc_attr( $empty_message ) . '">';

    foreach ( $translations as $language => $translation ) {
        $section_id = 'bookcreator-translation-' . sanitize_html_class( $language );
        $label      = bookcreator_get_language_label( $language );
        $fields     = isset( $translation['fields'] ) ? $translation['fields'] : array();
        $generated  = isset( $translation['generated'] ) ? $translation['generated'] : '';

        echo '<div id="' . esc_attr( $section_id ) . '" class="bookcreator-translation-section">';
        echo '<h3>' . esc_html( $label ) . '</h3>';
        if ( $generated ) {
            echo '<p><em>' . esc_html( sprintf( __( 'Ultimo aggiornamento: %s', 'bookcreator' ), date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $generated ) ) ) ) . '</em></p>';
        }

        echo '<input type="hidden" name="' . esc_attr( $meta_key ) . '[' . esc_attr( $language ) . '][language]" value="' . esc_attr( $language ) . '" />';
        echo '<input type="hidden" name="' . esc_attr( $meta_key ) . '[' . esc_attr( $language ) . '][generated]" value="' . esc_attr( $generated ) . '" />';

        if ( $include_media_fields ) {
            $cover_id                  = isset( $translation['cover_id'] ) ? (int) $translation['cover_id'] : 0;
            $cover_field_name          = $meta_key . '[' . $language . '][cover]';
            $cover_id_field_name       = $meta_key . '[' . $language . '][cover_id]';
            $remove_field_name         = $meta_key . '[' . $language . '][remove_cover]';
            $cover_input_id            = $section_id . '-cover';
            $cover_preview_markup      = '';
            $publisher_logo_id         = isset( $translation['publisher_logo_id'] ) ? (int) $translation['publisher_logo_id'] : 0;
            $publisher_logo_field_name = $meta_key . '[' . $language . '][publisher_logo]';
            $publisher_logo_id_field   = $meta_key . '[' . $language . '][publisher_logo_id]';
            $remove_logo_field_name    = $meta_key . '[' . $language . '][remove_publisher_logo]';
            $publisher_logo_input_id   = $section_id . '-publisher-logo';
            $publisher_logo_preview    = '';

            if ( $cover_id ) {
                $preview = wp_get_attachment_image( $cover_id, array( 100, 100 ) );
                if ( $preview ) {
                    $cover_preview_markup = '<div class="bookcreator-translation-cover-preview">' . $preview . '</div>';
                }
            }

            if ( $publisher_logo_id ) {
                $preview = wp_get_attachment_image( $publisher_logo_id, array( 100, 100 ) );
                if ( $preview ) {
                    $publisher_logo_preview = '<div class="bookcreator-translation-cover-preview">' . $preview . '</div>';
                }
            }

            echo '<div class="bookcreator-translation-field bookcreator-translation-field--cover">';
            echo '<label for="' . esc_attr( $cover_input_id ) . '">' . esc_html__( 'Copertina', 'bookcreator' ) . '</label>';
            echo '<input type="hidden" name="' . esc_attr( $cover_id_field_name ) . '" value="' . esc_attr( $cover_id ) . '" />';
            if ( $cover_preview_markup ) {
                echo $cover_preview_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
            echo '<p><input type="file" id="' . esc_attr( $cover_input_id ) . '" name="' . esc_attr( $cover_field_name ) . '" /></p>';
            if ( $cover_id ) {
                echo '<p><label><input type="checkbox" name="' . esc_attr( $remove_field_name ) . '" value="1" /> ' . esc_html__( 'Rimuovi copertina', 'bookcreator' ) . '</label></p>';
            }
            echo '</div>';

            echo '<div class="bookcreator-translation-field bookcreator-translation-field--publisher-logo">';
            echo '<label for="' . esc_attr( $publisher_logo_input_id ) . '">' . esc_html__( 'Logo editore', 'bookcreator' ) . '</label>';
            echo '<input type="hidden" name="' . esc_attr( $publisher_logo_id_field ) . '" value="' . esc_attr( $publisher_logo_id ) . '" />';
            if ( $publisher_logo_preview ) {
                echo $publisher_logo_preview; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
            echo '<p><input type="file" id="' . esc_attr( $publisher_logo_input_id ) . '" name="' . esc_attr( $publisher_logo_field_name ) . '" /></p>';
            if ( $publisher_logo_id ) {
                echo '<p><label><input type="checkbox" name="' . esc_attr( $remove_logo_field_name ) . '" value="1" /> ' . esc_html__( 'Rimuovi logo editore', 'bookcreator' ) . '</label></p>';
            }
            echo '</div>';
        }

        foreach ( $fields_config as $field_key => $field_config ) {
            $field_label = isset( $field_config['label'] ) ? $field_config['label'] : $field_key;
            $field_type  = isset( $field_config['type'] ) ? $field_config['type'] : 'text';
            $field_value = isset( $fields[ $field_key ] ) ? $fields[ $field_key ] : '';
            $field_name  = $meta_key . '[' . $language . '][fields][' . $field_key . ']';
            $field_id    = $section_id . '-' . sanitize_html_class( $field_key );

            echo '<div class="bookcreator-translation-field bookcreator-translation-field--' . esc_attr( $field_type ) . '">';
            echo '<label for="' . esc_attr( $field_id ) . '">' . esc_html( $field_label ) . '</label>';

            if ( 'editor' === $field_type ) {
                wp_editor(
                    $field_value,
                    $field_id,
                    array(
                        'textarea_name' => $field_name,
                        'textarea_rows' => 6,
                    )
                );
            } elseif ( 'textarea' === $field_type ) {
                echo '<textarea class="widefat" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" rows="5">' . esc_textarea( $field_value ) . '</textarea>';
            } else {
                echo '<input type="text" class="widefat" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" />';
            }

            echo '</div>';
        }

        echo '</div>';
    }

    echo '</div>';
}

function bookcreator_meta_box_translations_languages( $post ) {
    bookcreator_render_translation_languages_box( $post );
}

function bookcreator_meta_box_translations_content( $post ) {
    bookcreator_render_translation_content_box( $post );
}

function bookcreator_sanitize_translation_fields( $fields_input, $post_type ) {
    $fields_config = bookcreator_get_translation_fields_config( $post_type );
    $sanitized     = array();

    foreach ( $fields_config as $field_key => $field_config ) {
        $value = isset( $fields_input[ $field_key ] ) ? $fields_input[ $field_key ] : '';
        if ( is_array( $value ) ) {
            $value = ''; // Prevent unexpected arrays from being stored.
        }

        $callback = isset( $field_config['sanitize_callback'] ) ? $field_config['sanitize_callback'] : null;
        if ( $callback && is_callable( $callback ) ) {
            $value = call_user_func( $callback, $value );
        } else {
            $value = sanitize_text_field( $value );
        }

        $sanitized[ $field_key ] = $value;
    }

    return $sanitized;
}

function bookcreator_handle_translations_save( $post_id, $post_type, $translations_input ) {
    $meta_key = bookcreator_get_translation_meta_key( $post_type );
    if ( ! $meta_key ) {
        return;
    }

    if ( ! is_array( $translations_input ) ) {
        $translations_input = array();
    }

    $translations = array();
    $existing     = bookcreator_get_translations_for_post( $post_id, $post_type );
    $include_media_fields = ( 'book_creator' === $post_type );

    foreach ( $translations_input as $language_key => $translation_data ) {
        $language = '';
        if ( isset( $translation_data['language'] ) ) {
            $language = $translation_data['language'];
        } elseif ( is_string( $language_key ) ) {
            $language = $language_key;
        }

        $language = bookcreator_sanitize_translation_language( $language );
        if ( '' === $language ) {
            continue;
        }

        $fields_input = array();
        if ( isset( $translation_data['fields'] ) && is_array( $translation_data['fields'] ) ) {
            $fields_input = wp_unslash( $translation_data['fields'] );
        }

        $sanitized_fields  = bookcreator_sanitize_translation_fields( $fields_input, $post_type );
        $generated         = '';
        $cover_id          = $include_media_fields && isset( $existing[ $language ]['cover_id'] ) ? (int) $existing[ $language ]['cover_id'] : 0;
        $publisher_logo_id = $include_media_fields && isset( $existing[ $language ]['publisher_logo_id'] ) ? (int) $existing[ $language ]['publisher_logo_id'] : 0;

        if ( isset( $translation_data['generated'] ) ) {
            $generated = sanitize_text_field( $translation_data['generated'] );
        } elseif ( isset( $existing[ $language ]['generated'] ) ) {
            $generated = $existing[ $language ]['generated'];
        }

        if ( $include_media_fields ) {
            if ( isset( $translation_data['cover_id'] ) ) {
                $cover_id = (int) wp_unslash( $translation_data['cover_id'] );
            }

            if ( isset( $translation_data['publisher_logo_id'] ) ) {
                $publisher_logo_id = (int) wp_unslash( $translation_data['publisher_logo_id'] );
            }

            $uploaded_cover_id = bookcreator_process_translation_cover_upload( $post_id, $language );
            if ( $uploaded_cover_id ) {
                $cover_id = $uploaded_cover_id;
            }

            $uploaded_logo_id = bookcreator_process_translation_image_upload( $post_id, $language, 'publisher_logo' );
            if ( $uploaded_logo_id ) {
                $publisher_logo_id = $uploaded_logo_id;
            }
            if ( ! empty( $translation_data['remove_cover'] ) ) {
                $cover_id = 0;
            }

            if ( ! empty( $translation_data['remove_publisher_logo'] ) ) {
                $publisher_logo_id = 0;
            }
        }

        $translations[ $language ] = array(
            'language'  => $language,
            'fields'    => $sanitized_fields,
            'generated' => $generated,
            'cover_id'  => $include_media_fields ? $cover_id : 0,
            'publisher_logo_id' => $include_media_fields ? $publisher_logo_id : 0,
        );
    }

    if ( empty( $translations ) ) {
        delete_post_meta( $post_id, $meta_key );

        return;
    }

    update_post_meta( $post_id, $meta_key, $translations );
}

function bookcreator_process_translation_cover_upload( $post_id, $language ) {
    return bookcreator_process_translation_image_upload( $post_id, $language, 'cover' );
}

function bookcreator_process_translation_image_upload( $post_id, $language, $field ) {
    if ( 'book_creator' !== get_post_type( $post_id ) ) {
        return 0;
    }

    if ( empty( $_FILES['bc_translations'] ) || ! is_array( $_FILES['bc_translations'] ) ) {
        return 0;
    }

    $language = bookcreator_sanitize_translation_language( $language );
    if ( '' === $language ) {
        return 0;
    }

    $field = sanitize_key( $field );
    if ( '' === $field ) {
        return 0;
    }

    $files_root = $_FILES['bc_translations'];

    if ( empty( $files_root['name'][ $language ][ $field ] ) ) {
        return 0;
    }

    $error = isset( $files_root['error'][ $language ][ $field ] ) ? (int) $files_root['error'][ $language ][ $field ] : 0;

    if ( defined( 'UPLOAD_ERR_NO_FILE' ) && UPLOAD_ERR_NO_FILE === $error ) {
        return 0;
    }

    if ( $error && ( ! defined( 'UPLOAD_ERR_OK' ) || UPLOAD_ERR_OK !== $error ) ) {
        return 0;
    }

    $file_data = array(
        'name'     => $files_root['name'][ $language ][ $field ],
        'type'     => isset( $files_root['type'][ $language ][ $field ] ) ? $files_root['type'][ $language ][ $field ] : '',
        'tmp_name' => isset( $files_root['tmp_name'][ $language ][ $field ] ) ? $files_root['tmp_name'][ $language ][ $field ] : '',
        'error'    => $error,
        'size'     => isset( $files_root['size'][ $language ][ $field ] ) ? $files_root['size'][ $language ][ $field ] : 0,
    );

    if ( ! $file_data['tmp_name'] ) {
        return 0;
    }

    bookcreator_require_media_includes();

    $temp_key              = 'bookcreator_translation_' . $field . '_temp';
    $_FILES[ $temp_key ]   = $file_data;
    $attachment_id         = media_handle_upload( $temp_key, $post_id );
    unset( $_FILES[ $temp_key ] );

    if ( is_wp_error( $attachment_id ) ) {
        return 0;
    }

    return (int) $attachment_id;
}

function bookcreator_collect_translation_source_data( $post, $post_type ) {
    $fields_config = bookcreator_get_translation_fields_config( $post_type );
    $data          = array();

    foreach ( $fields_config as $field_key => $field_config ) {
        $source = isset( $field_config['source'] ) ? $field_config['source'] : 'meta';

        if ( 'post' === $source ) {
            if ( 'post_title' === $field_key ) {
                $value = $post->post_title;
            } elseif ( 'post_content' === $field_key ) {
                $value = $post->post_content;
            } else {
                $value = get_post_field( $field_key, $post );
            }
        } else {
            $value = get_post_meta( $post->ID, $field_key, true );
        }

        if ( is_array( $value ) ) {
            $value = ''; // Prevent arrays from being passed to the translator.
        }

        $data[ $field_key ] = (string) $value;
    }

    return $data;
}

function bookcreator_prepare_translation_prompt( $post_type, $fields_config, $source_data, $target_language ) {
    $language_display = bookcreator_get_language_label( $target_language );
    if ( $language_display && $language_display !== $target_language ) {
        $language_display .= ' (' . $target_language . ')';
    } else {
        $language_display = $target_language;
    }

    switch ( $post_type ) {
        case 'bc_chapter':
            $context_label = __( 'capitolo', 'bookcreator' );
            break;
        case 'bc_paragraph':
            $context_label = __( 'paragrafo', 'bookcreator' );
            break;
        default:
            $context_label = __( 'libro', 'bookcreator' );
            break;
    }

    $html_fields = array();
    foreach ( $fields_config as $field_key => $field_config ) {
        $format = isset( $field_config['format'] ) ? $field_config['format'] : 'text';
        $label  = isset( $field_config['label'] ) ? $field_config['label'] : $field_key;

        if ( 'html' === $format ) {
            $html_fields[] = $label;
        }
    }

    $instructions  = sprintf( __( 'Traduci i campi del %1$s nella lingua %2$s.', 'bookcreator' ), $context_label, $language_display ) . "\n";
    $instructions .= __( 'Mantieni invariati i marcatori [FIELD_*_START] e [FIELD_*_END] e non aggiungere testo fuori da essi.', 'bookcreator' ) . "\n";

    if ( $html_fields ) {
        $instructions .= sprintf( __( 'I seguenti campi contengono HTML e devono mantenere gli stessi tag: %s.', 'bookcreator' ), implode( ', ', $html_fields ) ) . "\n";
    }

    $instructions .= __( 'Restituisci esattamente gli stessi marcatori con il testo tradotto al loro interno.', 'bookcreator' ) . "\n";

    $claude_settings = bookcreator_get_claude_settings();
    $global_prompt   = isset( $claude_settings['translation_prompt'] ) ? trim( (string) $claude_settings['translation_prompt'] ) : '';

    if ( '' !== $global_prompt ) {
        $instructions .= "\n" . __( 'Istruzioni aggiuntive:', 'bookcreator' ) . "\n" . $global_prompt . "\n";
    }

    $instructions .= "\n";

    $body = '';
    foreach ( $fields_config as $field_key => $field_config ) {
        if ( isset( $field_config['translatable'] ) && false === $field_config['translatable'] ) {
            continue;
        }

        $marker = bookcreator_get_translation_marker( $field_key );
        $value  = isset( $source_data[ $field_key ] ) ? (string) $source_data[ $field_key ] : '';

        $body .= '[' . $marker . '_START]' . "\n";
        $body .= $value . "\n";
        $body .= '[' . $marker . '_END]' . "\n\n";
    }

    return array(
        'prompt' => $instructions . $body,
        'body'   => $body,
    );
}

function bookcreator_parse_translation_response( $response_text, $fields_config, $source_data ) {
    $results  = array();
    $warnings = array();

    foreach ( $fields_config as $field_key => $field_config ) {
        $marker = bookcreator_get_translation_marker( $field_key );
        $pattern = '/\[' . preg_quote( $marker . '_START', '/' ) . '\](.*?)\[' . preg_quote( $marker . '_END', '/' ) . '\]/is';

        $raw_value = null;
        if ( isset( $field_config['translatable'] ) && false === $field_config['translatable'] ) {
            $raw_value = isset( $source_data[ $field_key ] ) ? $source_data[ $field_key ] : '';
        } elseif ( preg_match( $pattern, $response_text, $matches ) ) {
            $raw_value = trim( $matches[1] );
        }

        if ( null === $raw_value ) {
            $raw_value = isset( $source_data[ $field_key ] ) ? $source_data[ $field_key ] : '';
            $label     = isset( $field_config['label'] ) ? $field_config['label'] : $field_key;
            $warnings[] = sprintf( __( 'Il campo %s non è stato trovato nella risposta di Claude. È stato mantenuto il contenuto originale.', 'bookcreator' ), $label );
        }

        $callback = isset( $field_config['sanitize_callback'] ) ? $field_config['sanitize_callback'] : null;
        if ( $callback && is_callable( $callback ) ) {
            $value = call_user_func( $callback, $raw_value );
        } else {
            $value = sanitize_text_field( $raw_value );
        }

        $results[ $field_key ] = $value;
    }

    return array(
        'fields'   => $results,
        'warnings' => $warnings,
    );
}

function bookcreator_store_translation_result( $post_id, $post_type, $language, $fields, $generated_at ) {
    $meta_key = bookcreator_get_translation_meta_key( $post_type );
    if ( ! $meta_key ) {
        return;
    }

    $language             = bookcreator_sanitize_translation_language( $language );
    $translations         = bookcreator_get_translations_for_post( $post_id, $post_type );
    $include_media_fields = ( 'book_creator' === $post_type );
    $existing_cover_id    = $include_media_fields && isset( $translations[ $language ]['cover_id'] ) ? (int) $translations[ $language ]['cover_id'] : 0;
    $existing_logo_id     = $include_media_fields && isset( $translations[ $language ]['publisher_logo_id'] ) ? (int) $translations[ $language ]['publisher_logo_id'] : 0;
    $translations[ $language ] = array(
        'language'  => $language,
        'fields'    => $fields,
        'generated' => $generated_at,
        'cover_id'  => $include_media_fields ? $existing_cover_id : 0,
        'publisher_logo_id' => $include_media_fields ? $existing_logo_id : 0,
    );

    update_post_meta( $post_id, $meta_key, $translations );
}

function bookcreator_generate_translation_for_post( $post, $target_language ) {
    if ( ! $post || ! $post->ID ) {
        return new WP_Error( 'bookcreator_translation_invalid_post', __( 'Contenuto non valido.', 'bookcreator' ) );
    }

    $post_type = $post->post_type;
    $fields_config = bookcreator_get_translation_fields_config( $post_type );
    if ( ! $fields_config ) {
        return new WP_Error( 'bookcreator_translation_unsupported', __( 'Questo contenuto non supporta le traduzioni automatiche.', 'bookcreator' ) );
    }

    $language = bookcreator_sanitize_translation_language( $target_language );
    if ( '' === $language ) {
        return new WP_Error( 'bookcreator_translation_invalid_language', __( 'La lingua di destinazione non è valida.', 'bookcreator' ) );
    }

    $source_data = bookcreator_collect_translation_source_data( $post, $post_type );

    $prompt_parts = bookcreator_prepare_translation_prompt( $post_type, $fields_config, $source_data, $language );
    if ( empty( trim( $prompt_parts['body'] ) ) ) {
        return new WP_Error( 'bookcreator_translation_missing_fields', __( 'Nessun campo disponibile per la traduzione.', 'bookcreator' ) );
    }
    $response     = bookcreator_send_claude_message( $prompt_parts['prompt'] );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $parsed = bookcreator_parse_translation_response( $response['text'], $fields_config, $source_data );
    $fields = bookcreator_sanitize_translation_fields( $parsed['fields'], $post_type );

    if ( 'book_creator' === $post_type && isset( $fields['bc_language'] ) ) {
        $fields['bc_language'] = $language;
    }

    $generated_at = current_time( 'mysql' );
    bookcreator_store_translation_result( $post->ID, $post_type, $language, $fields, $generated_at );

    return array(
        'language'     => $language,
        'warnings'     => $parsed['warnings'],
        'model_notice' => isset( $response['model_notice'] ) ? $response['model_notice'] : '',
    );
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

    <?php
}

function bookcreator_meta_box_prelim( $post ) {
    $cover_id           = get_post_meta( $post->ID, 'bc_cover', true );
    $publisher_logo_id  = get_post_meta( $post->ID, 'bc_publisher_logo', true );
    ?>
    <p><label for="bc_cover"><?php esc_html_e( 'Copertina', 'bookcreator' ); ?></label><br/>
    <input type="file" name="bc_cover" id="bc_cover" /><br/>
    <?php if ( $cover_id ) { echo wp_get_attachment_image( $cover_id, array( 100, 100 ) ); } ?></p>

    <p><label for="bc_publisher_logo"><?php esc_html_e( 'Logo editore', 'bookcreator' ); ?></label><br/>
    <input type="file" name="bc_publisher_logo" id="bc_publisher_logo" /><br/>
    <?php if ( $publisher_logo_id ) { echo wp_get_attachment_image( $publisher_logo_id, array( 100, 100 ) ); } ?></p>


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
    $book_args = array(
        'post_type'   => 'book_creator',
        'numberposts' => -1,
        'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ),
    );

    if ( ! current_user_can( 'edit_others_bookcreator_books' ) ) {
        $book_args['author'] = get_current_user_id();
    }

    $books    = get_posts( $book_args );
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

    $chapter_args = array(
        'post_type'   => 'bc_chapter',
        'numberposts' => -1,
        'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ),
    );

    if ( ! current_user_can( 'edit_others_bookcreator_chapters' ) ) {
        $chapter_args['author'] = get_current_user_id();
    }

    $chapters = get_posts( $chapter_args );

    $book_args = array(
        'post_type'   => 'book_creator',
        'numberposts' => -1,
        'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ),
    );

    if ( ! current_user_can( 'edit_others_bookcreator_books' ) ) {
        $book_args['author'] = get_current_user_id();
    }

    $books    = get_posts( $book_args );
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
 * Retrieve the schema of meta fields that represent book content.
 *
 * @return array<string, callable|string> Associative array of meta keys and sanitize callbacks.
 */
function bookcreator_get_book_content_meta_schema() {
    return array(
        'bc_subtitle'        => 'sanitize_text_field',
        'bc_author'          => 'sanitize_text_field',
        'bc_coauthors'       => 'sanitize_text_field',
        'bc_publisher'       => 'sanitize_text_field',
        'bc_isbn'            => 'sanitize_text_field',
        'bc_pub_date'        => 'sanitize_text_field',
        'bc_edition'         => 'sanitize_text_field',
        'bc_language'        => 'sanitize_text_field',
        'bc_description'     => 'wp_kses_post',
        'bc_frontispiece'    => 'wp_kses_post',
        'bc_copyright'       => 'wp_kses_post',
        'bc_dedication'      => 'wp_kses_post',
        'bc_preface'         => 'wp_kses_post',
        'bc_acknowledgments' => 'wp_kses_post',
        'bc_appendix'        => 'wp_kses_post',
        'bc_bibliography'    => 'wp_kses_post',
        'bc_author_note'     => 'wp_kses_post',
    );
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

    $fields = bookcreator_get_book_content_meta_schema();

    foreach ( $fields as $field => $sanitize ) {
        if ( isset( $_POST[ $field ] ) ) {
            $value = call_user_func( $sanitize, wp_unslash( $_POST[ $field ] ) );
            update_post_meta( $post_id, $field, $value );
        }
    }

    $book_translations_input = array();
    if ( isset( $_POST['bc_translations'] ) && is_array( $_POST['bc_translations'] ) ) {
        $book_translations_input = $_POST['bc_translations'];
    }

    if ( isset( $_POST['bc_translations_submitted'] ) ) {
        bookcreator_handle_translations_save( $post_id, 'book_creator', $book_translations_input );
    }

    bookcreator_require_media_includes();

    if ( ! empty( $_FILES['bc_cover']['name'] ) ) {
        $cover_id = media_handle_upload( 'bc_cover', $post_id );
        if ( ! is_wp_error( $cover_id ) ) {
            update_post_meta( $post_id, 'bc_cover', $cover_id );
        }
    }

    if ( ! empty( $_FILES['bc_publisher_logo']['name'] ) ) {
        $logo_id = media_handle_upload( 'bc_publisher_logo', $post_id );
        if ( ! is_wp_error( $logo_id ) ) {
            update_post_meta( $post_id, 'bc_publisher_logo', $logo_id );
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
    if ( ! $screen || ! in_array( $screen->post_type, array( 'book_creator', 'bc_chapter', 'bc_paragraph' ), true ) ) {
        return;
    }

    $dependencies = array( 'jquery' );

    if ( 'book_creator' === $screen->post_type ) {
        wp_enqueue_script( 'konva', 'https://cdn.jsdelivr.net/npm/konva@9.3.0/konva.min.js', array(), '9.3.0', true );
        $dependencies[] = 'konva';
    }

    wp_enqueue_script( 'bookcreator-admin', plugin_dir_url( __FILE__ ) . 'js/admin.js', $dependencies, '1.2', true );

    wp_localize_script(
        'bookcreator-admin',
        'bookcreatorTranslation',
        array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'bookcreator_generate_translation' ),
            'strings' => array(
                'selectLanguage' => __( 'Seleziona una lingua prima di avviare la traduzione.', 'bookcreator' ),
                'generating'     => __( 'Traduzione in corso…', 'bookcreator' ),
                'success'        => __( 'Traduzione generata correttamente.', 'bookcreator' ),
                'error'          => __( 'Si è verificato un errore durante la traduzione.', 'bookcreator' ),
                'replaceConfirm' => __( 'Esiste già una traduzione per questa lingua. Vuoi sovrascriverla?', 'bookcreator' ),
                'deleteConfirm'  => __( 'Vuoi eliminare la traduzione %s? Verrà rimossa dopo il salvataggio.', 'bookcreator' ),
            ),
        )
    );
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
    if ( ! current_user_can( 'edit_others_bookcreator_books' ) ) {
        $books = array_values(
            array_filter(
                $books,
                static function ( $book_id ) {
                    return current_user_can( 'edit_post', (int) $book_id );
                }
            )
        );
    }
    update_post_meta( $post_id, 'bc_books', $books );

    $all_books = array_unique( array_merge( $old_books, $books ) );
    foreach ( $all_books as $book_id ) {
        bookcreator_sync_chapter_menu( $book_id );
    }

    $chapter_translations_input = array();
    if ( isset( $_POST['bc_chapter_translations'] ) && is_array( $_POST['bc_chapter_translations'] ) ) {
        $chapter_translations_input = $_POST['bc_chapter_translations'];
    }

    if ( isset( $_POST['bc_chapter_translations_submitted'] ) ) {
        bookcreator_handle_translations_save( $post_id, 'bc_chapter', $chapter_translations_input );
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
    if ( ! current_user_can( 'edit_others_bookcreator_chapters' ) ) {
        $chapters = array_values(
            array_filter(
                $chapters,
                static function ( $chapter_id ) {
                    return current_user_can( 'edit_post', (int) $chapter_id );
                }
            )
        );
    }
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
    if ( ! current_user_can( 'edit_others_bookcreator_books' ) ) {
        $books = array_values(
            array_filter(
                $books,
                static function ( $book_id ) {
                    return current_user_can( 'edit_post', (int) $book_id );
                }
            )
        );
    }
    update_post_meta( $post_id, 'bc_books', $books );

    $all_chapters = array_unique( array_merge( $old_chapters, $chapters ) );
    foreach ( $all_chapters as $chapter_id ) {
        bookcreator_sync_paragraph_menu( $chapter_id );
    }

    $paragraph_translations_input = array();
    if ( isset( $_POST['bc_paragraph_translations'] ) && is_array( $_POST['bc_paragraph_translations'] ) ) {
        $paragraph_translations_input = $_POST['bc_paragraph_translations'];
    }

    if ( isset( $_POST['bc_paragraph_translations_submitted'] ) ) {
        bookcreator_handle_translations_save( $post_id, 'bc_paragraph', $paragraph_translations_input );
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
        'bc_translations'     => __( 'Traduzioni', 'bookcreator' ),
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

    if ( 'bc_translations' === $column ) {
        $languages = bookcreator_get_translation_language_labels( $post_id, 'book_creator' );
        if ( $languages ) {
            echo esc_html( implode( ', ', $languages ) );
        } else {
            echo '—';
        }
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
        'cb'             => $columns['cb'],
        'title'          => $columns['title'],
        'bc_books'       => __( 'Books', 'bookcreator' ),
        'bc_translations'=> __( 'Traduzioni', 'bookcreator' ),
        'date'           => $columns['date'],
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

    if ( 'bc_translations' === $column ) {
        $languages = bookcreator_get_translation_language_labels( $post_id, 'bc_chapter' );
        if ( $languages ) {
            echo esc_html( implode( ', ', $languages ) );
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
        'cb'             => $columns['cb'],
        'title'          => $columns['title'],
        'bc_chapters'    => __( 'Chapters', 'bookcreator' ),
        'bc_translations'=> __( 'Traduzioni', 'bookcreator' ),
        'date'           => $columns['date'],
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

    if ( 'bc_translations' === $column ) {
        $languages = bookcreator_get_translation_language_labels( $post_id, 'bc_paragraph' );
        if ( $languages ) {
            echo esc_html( implode( ', ', $languages ) );
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
    if ( ! current_user_can( 'bookcreator_manage_structures' ) ) {
        wp_die( esc_html__( 'Non hai i permessi per accedere a questa pagina.', 'bookcreator' ) );
    }

    echo '<div class="wrap"><h1>' . esc_html__( 'Ordina capitoli', 'bookcreator' ) . '</h1>';
    $book_id = isset( $_GET['book_id'] ) ? absint( $_GET['book_id'] ) : 0;

    if ( $book_id && ! current_user_can( 'edit_post', $book_id ) ) {
        wp_die( esc_html__( 'Non hai i permessi per modificare questo libro.', 'bookcreator' ) );
    }

    echo '<form method="get"><input type="hidden" name="page" value="bc-order-chapters" /><input type="hidden" name="post_type" value="book_creator" />';
    echo '<select name="book_id"><option value="">' . esc_html__( 'Seleziona libro', 'bookcreator' ) . '</option>';
    $book_query_args = array(
        'post_type'   => 'book_creator',
        'numberposts' => -1,
        'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ),
    );

    if ( ! current_user_can( 'edit_others_bookcreator_books' ) ) {
        $book_query_args['author'] = get_current_user_id();
    }

    $books = get_posts( $book_query_args );
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
    add_submenu_page( 'edit.php?post_type=book_creator', __( 'Ordina capitoli', 'bookcreator' ), __( 'Ordina capitoli', 'bookcreator' ), 'bookcreator_manage_structures', 'bc-order-chapters', 'bookcreator_order_chapters_page' );
}
add_action( 'admin_menu', 'bookcreator_register_order_chapters_page' );

function bookcreator_order_chapters_enqueue( $hook ) {
    if ( 'book_creator_page_bc-order-chapters' === $hook ) {
        wp_enqueue_script( 'nav-menu' );
    }
}
add_action( 'admin_enqueue_scripts', 'bookcreator_order_chapters_enqueue' );

function bookcreator_order_paragraphs_page() {
    if ( ! current_user_can( 'bookcreator_manage_structures' ) ) {
        wp_die( esc_html__( 'Non hai i permessi per accedere a questa pagina.', 'bookcreator' ) );
    }

    echo '<div class="wrap"><h1>' . esc_html__( 'Ordina paragrafi', 'bookcreator' ) . '</h1>';
    $book_id    = isset( $_GET['book_id'] ) ? absint( $_GET['book_id'] ) : 0;
    $chapter_id = isset( $_GET['chapter_id'] ) ? absint( $_GET['chapter_id'] ) : 0;

    if ( $book_id && ! current_user_can( 'edit_post', $book_id ) ) {
        wp_die( esc_html__( 'Non hai i permessi per modificare questo libro.', 'bookcreator' ) );
    }

    if ( $chapter_id && ! current_user_can( 'edit_post', $chapter_id ) ) {
        wp_die( esc_html__( 'Non hai i permessi per modificare questo capitolo.', 'bookcreator' ) );
    }

    echo '<form method="get"><input type="hidden" name="page" value="bc-order-paragraphs" /><input type="hidden" name="post_type" value="book_creator" />';
    echo '<select name="book_id" onchange="if ( this.form.chapter_id ) { this.form.chapter_id.selectedIndex = 0; } this.form.submit();"><option value="">' . esc_html__( 'Seleziona libro', 'bookcreator' ) . '</option>';
    $book_query_args = array(
        'post_type'   => 'book_creator',
        'numberposts' => -1,
        'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ),
    );

    if ( ! current_user_can( 'edit_others_bookcreator_books' ) ) {
        $book_query_args['author'] = get_current_user_id();
    }

    $books = get_posts( $book_query_args );
    foreach ( $books as $book ) {
        printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $book->ID ), selected( $book_id, $book->ID, false ), esc_html( $book->post_title ) );
    }
    echo '</select>';

    if ( $book_id ) {
        echo '<select name="chapter_id"><option value="">' . esc_html__( 'Seleziona capitolo', 'bookcreator' ) . '</option>';
        $chapter_query_args = array(
            'post_type'   => 'bc_chapter',
            'numberposts' => -1,
            'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ),
            'meta_query'  => array(
                array(
                    'key'     => 'bc_books',
                    'value'   => '"' . $book_id . '"',
                    'compare' => 'LIKE',
                ),
            ),
        );

        if ( ! current_user_can( 'edit_others_bookcreator_chapters' ) ) {
            $chapter_query_args['author'] = get_current_user_id();
        }

        $chapters = get_posts( $chapter_query_args );
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
    add_submenu_page( 'edit.php?post_type=book_creator', __( 'Ordina paragrafi', 'bookcreator' ), __( 'Ordina paragrafi', 'bookcreator' ), 'bookcreator_manage_structures', 'bc-order-paragraphs', 'bookcreator_order_paragraphs_page' );
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

function bookcreator_format_pdf_font_label( $font_key ) {
    $original = (string) $font_key;

    if ( '' === $original ) {
        return '';
    }

    $label = str_replace( array( '-', '_' ), ' ', strtolower( $original ) );
    $label = preg_replace( '/(?<=\p{Ll})(\p{Lu})/u', ' $1', $label );
    $label = preg_replace( '/\s+/', ' ', $label );
    $label = trim( $label );

    if ( '' === $label ) {
        $label = $original;
    }

    $replacements = array(
        'dejavusanscondensed' => 'DejaVu Sans Condensed',
        'dejavusansmono'      => 'DejaVu Sans Mono',
        'dejavusans'          => 'DejaVu Sans',
        'dejavuserifcondensed'=> 'DejaVu Serif Condensed',
        'dejavuserif'         => 'DejaVu Serif',
        'freesans'            => 'Free Sans',
        'freeserif'           => 'Free Serif',
        'freemono'            => 'Free Mono',
    );

    $normalized = strtolower( str_replace( ' ', '', $label ) );
    if ( isset( $replacements[ $normalized ] ) ) {
        return $replacements[ $normalized ];
    }

    return ucwords( $label );
}

function bookcreator_get_pdf_font_family_options() {
    static $fonts = null;

    if ( null !== $fonts ) {
        return $fonts;
    }

    $fonts = array();

    if ( bookcreator_load_mpdf_library() && class_exists( '\\Mpdf\\Config\\FontVariables' ) ) {
        try {
            $font_variables = new \Mpdf\Config\FontVariables();
            $defaults       = $font_variables->getDefaults();
            if ( isset( $defaults['fontdata'] ) && is_array( $defaults['fontdata'] ) ) {
                foreach ( $defaults['fontdata'] as $font_key => $font_config ) {
                    if ( ! is_string( $font_key ) || '' === $font_key ) {
                        continue;
                    }

                    $label = bookcreator_format_pdf_font_label( $font_key );
                    if ( '' === $label ) {
                        $label = $font_key;
                    }

                    $fonts[ $font_key ] = array(
                        'label' => $label,
                        'css'   => $font_key,
                    );
                }
            }
        } catch ( \Throwable $exception ) {
            // Fall back to the default hard coded list below.
        }
    }

    if ( empty( $fonts ) ) {
        $fonts = array(
            'dejavuserif'        => array(
                'label' => __( 'DejaVu Serif', 'bookcreator' ),
                'css'   => 'dejavuserif',
            ),
            'dejavusans'         => array(
                'label' => __( 'DejaVu Sans', 'bookcreator' ),
                'css'   => 'dejavusans',
            ),
            'dejavusanscondensed' => array(
                'label' => __( 'DejaVu Sans Condensed', 'bookcreator' ),
                'css'   => 'dejavusanscondensed',
            ),
            'dejavusansmono'     => array(
                'label' => __( 'DejaVu Sans Mono', 'bookcreator' ),
                'css'   => 'dejavusansmono',
            ),
        );
    } else {
        uasort(
            $fonts,
            static function ( $a, $b ) {
                $label_a = isset( $a['label'] ) ? $a['label'] : '';
                $label_b = isset( $b['label'] ) ? $b['label'] : '';

                return strcasecmp( $label_a, $label_b );
            }
        );
    }

    return $fonts;
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
        'width_percent'   => '',
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
        case 'book_publisher_logo':
            $defaults['text_align']    = 'center';
            $defaults['margin_top']    = '1';
            $defaults['margin_bottom'] = '1';
            $defaults['width_percent'] = '40';
            break;
        case 'book_frontispiece':
            $defaults['text_align']    = 'center';
            $defaults['margin_bottom'] = '1';
            break;
        case 'book_description':
        case 'book_frontispiece_extra':
        case 'book_preface':
        case 'book_preface_content':
        case 'book_author_note':
        case 'chapter_content':
        case 'paragraph_content':
            $defaults['line_height'] = '1.6';
            $defaults['text_align']  = 'justify';
            break;
        case 'book_preface_title':
            $defaults['font_size']     = '1.6';
            $defaults['font_weight']   = '700';
            $defaults['text_align']    = 'left';
            $defaults['margin_top']    = '1.2';
            $defaults['margin_bottom'] = '0.6';
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
        case 'book_index_title':
            $defaults['font_size']     = '1.6';
            $defaults['font_weight']   = '700';
            $defaults['text_align']    = 'left';
            $defaults['margin_top']    = '0.6';
            $defaults['margin_bottom'] = '0.4';
            break;
        case 'book_index_list':
            $defaults['line_height']   = '1.6';
            $defaults['margin_top']    = '0.4';
            $defaults['margin_bottom'] = '0.4';
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

function bookcreator_get_pdf_style_base_defaults() {
    return array(
        'font_size'       => '12',
        'line_height'     => '1.4',
        'font_family'     => 'dejavuserif',
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
        'width_percent'   => '',
    );
}

function bookcreator_get_pdf_style_defaults( $field_key ) {
    $defaults = bookcreator_get_pdf_style_base_defaults();

    switch ( $field_key ) {
        case 'book_title':
            $defaults['font_size']     = '28';
            $defaults['line_height']   = '1.2';
            $defaults['font_weight']   = '700';
            $defaults['text_align']    = 'center';
            $defaults['margin_bottom'] = '4';
            break;
        case 'book_subtitle':
            $defaults['font_size']     = '18';
            $defaults['line_height']   = '1.3';
            $defaults['font_style']    = 'italic';
            $defaults['text_align']    = 'center';
            $defaults['margin_top']    = '6';
            $defaults['margin_bottom'] = '6';
            break;
        case 'book_author':
            $defaults['font_size']  = '16';
            $defaults['text_align'] = 'center';
            break;
        case 'book_coauthors':
            $defaults['font_size']  = '14';
            $defaults['text_align'] = 'center';
            break;
        case 'book_publisher':
            $defaults['text_align']    = 'center';
            $defaults['margin_top']    = '8';
            $defaults['margin_bottom'] = '4';
            break;
        case 'book_publisher_logo':
            $defaults['text_align']    = 'center';
            $defaults['margin_top']    = '15';
            $defaults['margin_bottom'] = '15';
            $defaults['width_percent'] = '40';
            break;
        case 'book_frontispiece':
            $defaults['text_align']    = 'center';
            $defaults['margin_bottom'] = '15';
            break;
        case 'book_description':
        case 'book_frontispiece_extra':
        case 'book_preface':
        case 'book_preface_content':
        case 'book_author_note':
        case 'chapter_content':
        case 'paragraph_content':
            $defaults['line_height'] = '1.6';
            $defaults['text_align']  = 'justify';
            break;
        case 'book_preface_title':
            $defaults['font_size']     = '18';
            $defaults['font_weight']   = '700';
            $defaults['text_align']    = 'left';
            $defaults['margin_top']    = '15';
            $defaults['margin_bottom'] = '8';
            break;
        case 'book_copyright':
        case 'book_dedication':
        case 'book_appendix':
        case 'book_bibliography':
            $defaults['line_height'] = '1.6';
            break;
        case 'book_index':
            $defaults['margin_top']    = '15';
            $defaults['margin_bottom'] = '15';
            break;
        case 'book_index_title':
            $defaults['font_size']     = '16';
            $defaults['font_weight']   = '700';
            $defaults['margin_top']    = '10';
            $defaults['margin_bottom'] = '6';
            break;
        case 'book_index_list':
            $defaults['line_height']   = '1.6';
            $defaults['margin_top']    = '4';
            $defaults['margin_bottom'] = '4';
            break;
        case 'chapter_titles':
            $defaults['font_size']     = '20';
            $defaults['font_weight']   = '700';
            $defaults['margin_top']    = '15';
            $defaults['margin_bottom'] = '8';
            break;
        case 'paragraph_titles':
            $defaults['font_size']     = '16';
            $defaults['font_weight']   = '600';
            $defaults['margin_top']    = '10';
            $defaults['margin_bottom'] = '6';
            break;
        case 'paragraph_footnotes':
        case 'paragraph_citations':
            $defaults['font_size']     = '11';
            $defaults['line_height']   = '1.4';
            $defaults['margin_top']    = '15';
            $defaults['padding_top']   = '5';
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
        'book_publisher_logo' => array(
            'label'                 => __( 'Logo editore', 'bookcreator' ),
            'selectors'             => array( '.bookcreator-frontispiece__publisher-logo' ),
            'stylable'              => true,
            'supports_width_percent' => true,
            'description'           => __( 'Configura dimensione, allineamento e spaziatura del logo editore nel file ePub generato.', 'bookcreator' ),
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
            'label'     => __( 'Indice (contenitore)', 'bookcreator' ),
            'selectors' => array( '.bookcreator-book__index', '.bookcreator-preface__index', '#toc.bookcreator-book__index', '#toc' ),
            'stylable'  => true,
        ),
        'book_index_title' => array(
            'label'     => __( 'Titolo indice', 'bookcreator' ),
            'selectors' => array( '.bookcreator-book__index-title', '.bookcreator-preface__index-title', '#toc .bookcreator-book__index-title' ),
            'stylable'  => true,
        ),
        'book_index_list' => array(
            'label'     => __( 'Indice (elenco)', 'bookcreator' ),
            'selectors' => array( '.bookcreator-book__index-list', '.bookcreator-book__index-sublist', '.bookcreator-preface__index-list', '.bookcreator-preface__index-sublist', '#toc .bookcreator-book__index-list', '#toc .bookcreator-book__index-sublist' ),
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
        'book_preface_title' => array(
            'label'     => __( 'Titolo Prefazione', 'bookcreator' ),
            'selectors' => array( '.bookcreator-preface__title' ),
            'stylable'  => true,
        ),
        'book_preface_content' => array(
            'label'     => __( 'Contenuto Prefazione', 'bookcreator' ),
            'selectors' => array( '.bookcreator-preface__content' ),
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

function bookcreator_get_pdf_style_fields() {
    return bookcreator_get_epub_style_fields();
}

function bookcreator_get_pdf_stylable_fields() {
    $stylable = array();

    foreach ( bookcreator_get_pdf_style_fields() as $field_key => $field ) {
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

function bookcreator_get_pdf_style_settings_config() {
    $config = array();

    foreach ( bookcreator_get_pdf_stylable_fields() as $field_key => $field ) {
        $config[ $field_key . '_styles' ] = array(
            'default' => bookcreator_get_pdf_style_defaults( $field_key ),
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

    if ( array_key_exists( 'width_percent', $defaults ) ) {
        $width_value = isset( $value['width_percent'] ) ? $value['width_percent'] : '';
        $width_value = bookcreator_sanitize_numeric_value( $width_value );

        if ( '' === $width_value && '' !== $defaults['width_percent'] ) {
            $width_value = bookcreator_sanitize_numeric_value( $defaults['width_percent'] );
        }

        if ( '' !== $width_value ) {
            $width_float = (float) $width_value;
            if ( $width_float < 0 ) {
                $width_float = 0;
            }
            if ( $width_float > 100 ) {
                $width_float = 100;
            }
            $width_value = bookcreator_sanitize_numeric_value( (string) $width_float );
        }

        $value['width_percent'] = $width_value;
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

function bookcreator_normalize_pdf_style_values( $value, $defaults ) {
    $value = is_array( $value ) ? $value : array();
    $value = wp_parse_args( $value, $defaults );

    $value['font_family'] = sanitize_key( $value['font_family'] );

    $font_families = bookcreator_get_pdf_font_family_options();
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
        $value['hyphenation'] = $defaults['hyphenation'];
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

    if ( array_key_exists( 'width_percent', $defaults ) ) {
        $width_value = isset( $value['width_percent'] ) ? $value['width_percent'] : '';
        $width_value = bookcreator_sanitize_numeric_value( $width_value );

        if ( '' === $width_value && '' !== $defaults['width_percent'] ) {
            $width_value = bookcreator_sanitize_numeric_value( $defaults['width_percent'] );
        }

        if ( '' !== $width_value ) {
            $width_float = (float) $width_value;
            if ( $width_float < 0 ) {
                $width_float = 0;
            }
            if ( $width_float > 100 ) {
                $width_float = 100;
            }
            $width_value = bookcreator_sanitize_numeric_value( (string) $width_float );
        }

        $value['width_percent'] = $width_value;
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
    $width_percent = isset( $_POST[ $prefix . 'width_percent' ] ) ? bookcreator_sanitize_numeric_value( wp_unslash( $_POST[ $prefix . 'width_percent' ] ) ) : '';
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
        'width_percent'   => $width_percent,
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

function bookcreator_get_posted_pdf_style_values( $field_key ) {
    $prefix = 'bookcreator_template_pdf_' . $field_key . '_';

    $font_size   = isset( $_POST[ $prefix . 'font_size' ] ) ? bookcreator_sanitize_numeric_value( wp_unslash( $_POST[ $prefix . 'font_size' ] ) ) : '';
    $line_height = isset( $_POST[ $prefix . 'line_height' ] ) ? bookcreator_sanitize_numeric_value( wp_unslash( $_POST[ $prefix . 'line_height' ] ) ) : '';
    $font_family = isset( $_POST[ $prefix . 'font_family' ] ) ? sanitize_key( wp_unslash( $_POST[ $prefix . 'font_family' ] ) ) : '';
    $font_style  = isset( $_POST[ $prefix . 'font_style' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $prefix . 'font_style' ] ) ) : '';
    $font_weight = isset( $_POST[ $prefix . 'font_weight' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $prefix . 'font_weight' ] ) ) : '';
    $hyphenation = isset( $_POST[ $prefix . 'hyphenation' ] ) ? sanitize_key( wp_unslash( $_POST[ $prefix . 'hyphenation' ] ) ) : '';
    $text_align  = isset( $_POST[ $prefix . 'text_align' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $prefix . 'text_align' ] ) ) : '';
    $width_percent = isset( $_POST[ $prefix . 'width_percent' ] ) ? bookcreator_sanitize_numeric_value( wp_unslash( $_POST[ $prefix . 'width_percent' ] ) ) : '';
    $color       = isset( $_POST[ $prefix . 'color' ] ) ? sanitize_hex_color( wp_unslash( $_POST[ $prefix . 'color' ] ) ) : '';

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
        'width_percent'   => $width_percent,
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

function bookcreator_get_pdf_default_visible_fields() {
    $defaults = array();

    foreach ( bookcreator_get_pdf_style_fields() as $field_key => $field ) {
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
            'settings' => array_merge(
                array(
                    'page_format' => array(
                        'default' => 'A4',
                        'choices' => array( 'A4', 'A5', 'Letter', 'Custom' ),
                    ),
                    'page_width'  => array(
                        'default' => 210,
                    ),
                    'page_height' => array(
                        'default' => 297,
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
                    'title_color'   => array(
                        'default' => '#333333',
                    ),
                ),
                bookcreator_get_pdf_style_settings_config(),
                array(
                    'visible_fields' => array(
                        'default' => bookcreator_get_pdf_default_visible_fields(),
                    ),
                )
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

        if ( '_styles' === substr( $key, -7 ) ) {
            $field_key = substr( $key, 0, -7 );
            if ( 'epub' === $type ) {
                $defaults  = bookcreator_get_epub_style_defaults( $field_key );
                $value     = bookcreator_normalize_epub_style_values( $value, $defaults, $settings, $key );
                $settings[ $key ] = $value;
                continue;
            } elseif ( 'pdf' === $type ) {
                $defaults  = bookcreator_get_pdf_style_defaults( $field_key );
                $value     = bookcreator_normalize_pdf_style_values( $value, $defaults );
                $settings[ $key ] = $value;
                continue;
            }
        }

        switch ( $key ) {
            case 'title_color':
                $value = sanitize_hex_color( $value );
                if ( ! $value ) {
                    $value = $args['default'];
                }
                break;
            case 'visible_fields':
                if ( in_array( $type, array( 'epub', 'pdf' ), true ) ) {
                    $value          = is_array( $value ) ? $value : array();
                    $normalized_set = array();
                    $style_fields   = ( 'pdf' === $type ) ? bookcreator_get_pdf_style_fields() : bookcreator_get_epub_style_fields();
                    foreach ( $style_fields as $field_key => $field ) {
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
                $sanitized = bookcreator_sanitize_numeric_value( $value );
                $value     = '' !== $sanitized ? (float) $sanitized : (float) $args['default'];
                if ( $value <= 0 ) {
                    $value = (float) $args['default'];
                }
                break;
            case 'page_width':
            case 'page_height':
                $sanitized = bookcreator_sanitize_numeric_value( $value );
                $value     = '' !== $sanitized ? (float) $sanitized : (float) $args['default'];
                if ( $value <= 0 ) {
                    $value = (float) $args['default'];
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

    if ( in_array( $type, array( 'epub', 'pdf' ), true ) && isset( $settings['book_title_styles']['color'] ) ) {
        $settings['title_color'] = $settings['book_title_styles']['color'];
    }

    return $settings;
}

function bookcreator_get_template_owner_id( $template ) {
    return isset( $template['owner'] ) ? (int) $template['owner'] : 0;
}

function bookcreator_current_user_can_access_template( $template ) {
    if ( current_user_can( 'manage_options' ) ) {
        return true;
    }

    if ( ! current_user_can( 'bookcreator_manage_templates' ) ) {
        return false;
    }

    $owner = bookcreator_get_template_owner_id( $template );
    if ( 0 === $owner ) {
        return true;
    }

    return get_current_user_id() === $owner;
}

function bookcreator_current_user_can_manage_template( $template ) {
    if ( current_user_can( 'manage_options' ) ) {
        return true;
    }

    if ( ! current_user_can( 'bookcreator_manage_templates' ) ) {
        return false;
    }

    $owner = bookcreator_get_template_owner_id( $template );

    return $owner && get_current_user_id() === $owner;
}

function bookcreator_filter_templates_for_current_user( $templates ) {
    if ( current_user_can( 'manage_options' ) ) {
        return $templates;
    }

    if ( ! current_user_can( 'bookcreator_manage_templates' ) ) {
        return array();
    }

    foreach ( $templates as $template_id => $template ) {
        if ( ! bookcreator_current_user_can_access_template( $template ) ) {
            unset( $templates[ $template_id ] );
        }
    }

    return $templates;
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

        $templates[ $id ]['owner'] = bookcreator_get_template_owner_id( $template );
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

function bookcreator_prepare_template_export_data( $template ) {
    $type      = isset( $template['type'] ) ? sanitize_key( $template['type'] ) : 'epub';
    $settings  = isset( $template['settings'] ) ? $template['settings'] : array();
    $normalized = bookcreator_normalize_template_settings( $settings, $type );

    $plugin_version = '1.1';
    if ( defined( 'BOOKCREATOR_PLUGIN_VERSION' ) ) {
        $plugin_version = BOOKCREATOR_PLUGIN_VERSION;
    }

    return array(
        'bookcreator_template' => array(
            'version'     => 1,
            'description' => 'BookCreator template configuration export.',
            'exported_at' => gmdate( 'c' ),
            'plugin'      => array(
                'name'    => 'BookCreator',
                'version' => $plugin_version,
            ),
            'id'         => isset( $template['id'] ) ? (string) $template['id'] : '',
            'name'       => isset( $template['name'] ) ? (string) $template['name'] : '',
            'type'       => $type,
            'settings'   => $normalized,
        ),
    );
}

function bookcreator_get_template_export_filename( $template ) {
    $slug = isset( $template['name'] ) ? sanitize_title( $template['name'] ) : '';
    if ( ! $slug ) {
        $slug = isset( $template['id'] ) ? sanitize_title( $template['id'] ) : '';
    }
    if ( ! $slug ) {
        $slug = 'template';
    }

    $type = isset( $template['type'] ) ? sanitize_key( $template['type'] ) : 'epub';

    return $slug . '-' . $type . '-bookcreator.json';
}

function bookcreator_parse_template_import_data( $raw_json ) {
    if ( '' === trim( (string) $raw_json ) ) {
        return new WP_Error( 'bookcreator_template_import_empty', __( 'Il file JSON è vuoto.', 'bookcreator' ) );
    }

    $decoded = json_decode( $raw_json, true );

    if ( null === $decoded && JSON_ERROR_NONE !== json_last_error() ) {
        return new WP_Error( 'bookcreator_template_import_invalid_json', __( 'Il file JSON non è valido.', 'bookcreator' ) );
    }

    if ( isset( $decoded['bookcreator_template'] ) ) {
        $decoded = $decoded['bookcreator_template'];
    }

    if ( ! is_array( $decoded ) ) {
        return new WP_Error( 'bookcreator_template_import_missing_data', __( 'Il file JSON non contiene i dati del template.', 'bookcreator' ) );
    }

    $type = isset( $decoded['type'] ) ? sanitize_key( $decoded['type'] ) : '';
    $name = isset( $decoded['name'] ) ? sanitize_text_field( $decoded['name'] ) : '';
    $settings = isset( $decoded['settings'] ) && is_array( $decoded['settings'] ) ? $decoded['settings'] : null;

    if ( null === $settings ) {
        return new WP_Error( 'bookcreator_template_import_missing_settings', __( 'Le impostazioni del template non sono presenti nel file JSON.', 'bookcreator' ) );
    }

    return array(
        'type'     => $type,
        'name'     => $name,
        'settings' => $settings,
    );
}

function bookcreator_get_templates_by_type( $type ) {
    $templates = bookcreator_filter_templates_for_current_user( bookcreator_get_templates() );

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

    if ( ! current_user_can( 'bookcreator_manage_templates' ) ) {
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

    if ( 'export' === $action ) {
        $template_id = isset( $_POST['bookcreator_template_id'] ) ? sanitize_text_field( wp_unslash( $_POST['bookcreator_template_id'] ) ) : '';
        if ( $template_id && isset( $templates[ $template_id ] ) && $requested_type === ( isset( $templates[ $template_id ]['type'] ) ? $templates[ $template_id ]['type'] : 'epub' ) ) {
            $template = $templates[ $template_id ];
            if ( ! bookcreator_current_user_can_access_template( $template ) ) {
                $status  = 'error';
                $message = __( 'Template non trovato.', 'bookcreator' );
            } else {
                $export_data = bookcreator_prepare_template_export_data( $template );
                $json        = wp_json_encode( $export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

                if ( false === $json ) {
                    $status  = 'error';
                    $message = __( 'Impossibile generare il file JSON per il template.', 'bookcreator' );
                } else {
                    $filename = bookcreator_get_template_export_filename( $template );
                    $filename = sanitize_file_name( $filename );

                    nocache_headers();
                    header( 'Content-Type: application/json; charset=utf-8' );
                    header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
                    header( 'Content-Length: ' . strlen( $json ) );
                    echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    exit;
                }
            }
        } else {
            $status  = 'error';
            $message = __( 'Template non trovato.', 'bookcreator' );
        }
    } elseif ( 'import' === $action ) {
        $template_id = isset( $_POST['bookcreator_template_id'] ) ? sanitize_text_field( wp_unslash( $_POST['bookcreator_template_id'] ) ) : '';
        if ( ! $template_id || ! isset( $templates[ $template_id ] ) ) {
            $status  = 'error';
            $message = __( 'Template non trovato.', 'bookcreator' );
        } elseif ( ! bookcreator_current_user_can_manage_template( $templates[ $template_id ] ) ) {
            $status  = 'error';
            $message = __( 'Non hai i permessi per modificare questo template.', 'bookcreator' );
        } elseif ( $requested_type !== ( isset( $templates[ $template_id ]['type'] ) ? $templates[ $template_id ]['type'] : 'epub' ) ) {
            $status  = 'error';
            $message = __( 'Tipologia di template non valida.', 'bookcreator' );
        } elseif ( empty( $_FILES['bookcreator_template_import_file'] ) || ! is_array( $_FILES['bookcreator_template_import_file'] ) ) {
            $status  = 'error';
            $message = __( 'Seleziona un file JSON da importare.', 'bookcreator' );
        } else {
            $file = $_FILES['bookcreator_template_import_file'];
            if ( ! empty( $file['error'] ) ) {
                $status  = 'error';
                $message = __( 'Errore durante il caricamento del file JSON.', 'bookcreator' );
            } elseif ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
                $status  = 'error';
                $message = __( 'Il file JSON caricato non è valido.', 'bookcreator' );
            } else {
                $file_size = filesize( $file['tmp_name'] );
                $max_size  = apply_filters( 'bookcreator_template_import_max_bytes', 1048576 );
                if ( $max_size > 0 && $file_size > $max_size ) {
                    $status  = 'error';
                    $message = __( 'Il file JSON è troppo grande.', 'bookcreator' );
                } else {
                    if ( ! function_exists( 'wp_check_filetype_and_ext' ) ) {
                        require_once ABSPATH . 'wp-admin/includes/file.php';
                    }
                    $filename      = isset( $file['name'] ) ? $file['name'] : '';
                    $filetype      = wp_check_filetype_and_ext( $file['tmp_name'], $filename, array( 'json' => 'application/json' ) );
                    $has_json_ext  = $filename && 'json' === strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
                    if ( ( empty( $filetype['ext'] ) || 'json' !== $filetype['ext'] ) && ! $has_json_ext ) {
                        $status  = 'error';
                        $message = __( 'Il file caricato deve essere in formato JSON.', 'bookcreator' );
                    } else {
                        $contents = file_get_contents( $file['tmp_name'] );
                        if ( false === $contents ) {
                            $status  = 'error';
                            $message = __( 'Impossibile leggere il file JSON.', 'bookcreator' );
                        } else {
                            $parsed = bookcreator_parse_template_import_data( $contents );
                            if ( is_wp_error( $parsed ) ) {
                                $status  = 'error';
                                $message = $parsed->get_error_message();
                            } else {
                                $template_type = isset( $templates[ $template_id ]['type'] ) ? $templates[ $template_id ]['type'] : 'epub';
                                $import_type   = $parsed['type'] ? $parsed['type'] : $template_type;

                                if ( $import_type !== $template_type ) {
                                    $status  = 'error';
                                    $message = __( 'Il file JSON appartiene a una tipologia di template differente.', 'bookcreator' );
                                } else {
                                    $updated_template = $templates[ $template_id ];
                                    if ( ! empty( $parsed['name'] ) ) {
                                        $updated_template['name'] = $parsed['name'];
                                    }
                                    $updated_template['settings'] = bookcreator_normalize_template_settings( $parsed['settings'], $template_type );

                                    $templates[ $template_id ] = $updated_template;
                                    update_option( 'bookcreator_templates', $templates );

                                    $status  = 'success';
                                    $message = __( 'Impostazioni del template importate correttamente.', 'bookcreator' );
                                }
                            }
                        }
                    }
                }
            }
        }
    } elseif ( 'save' === $action ) {
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

        if ( 'success' === $status && $template_id && isset( $templates[ $template_id ] ) && ! bookcreator_current_user_can_manage_template( $templates[ $template_id ] ) ) {
            $status  = 'error';
            $message = __( 'Non hai i permessi per modificare questo template.', 'bookcreator' );
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
                $stylable_fields = array_keys( bookcreator_get_pdf_stylable_fields() );
                foreach ( $stylable_fields as $field_key ) {
                    $style_values = bookcreator_get_posted_pdf_style_values( $field_key );
                    $settings[ $field_key . '_styles' ] = $style_values;

                    if ( 'book_title' === $field_key ) {
                        $settings['title_color'] = $style_values['color'];
                    }
                }

                $visible_fields_input = isset( $_POST['bookcreator_template_pdf_visible_fields'] ) ? (array) wp_unslash( $_POST['bookcreator_template_pdf_visible_fields'] ) : array();
                $settings['visible_fields'] = array();

                foreach ( bookcreator_get_pdf_style_fields() as $field_key => $field ) {
                    $settings['visible_fields'][ $field_key ] = array_key_exists( $field_key, $visible_fields_input );
                }

                $settings['page_format']  = isset( $_POST['bookcreator_template_pdf_page_format'] ) ? sanitize_text_field( wp_unslash( $_POST['bookcreator_template_pdf_page_format'] ) ) : '';
                $settings['page_width']   = isset( $_POST['bookcreator_template_pdf_page_width'] ) ? wp_unslash( $_POST['bookcreator_template_pdf_page_width'] ) : '';
                $settings['page_height']  = isset( $_POST['bookcreator_template_pdf_page_height'] ) ? wp_unslash( $_POST['bookcreator_template_pdf_page_height'] ) : '';
                $settings['margin_top']   = isset( $_POST['bookcreator_template_pdf_margin_top'] ) ? wp_unslash( $_POST['bookcreator_template_pdf_margin_top'] ) : '';
                $settings['margin_right'] = isset( $_POST['bookcreator_template_pdf_margin_right'] ) ? wp_unslash( $_POST['bookcreator_template_pdf_margin_right'] ) : '';
                $settings['margin_bottom'] = isset( $_POST['bookcreator_template_pdf_margin_bottom'] ) ? wp_unslash( $_POST['bookcreator_template_pdf_margin_bottom'] ) : '';
                $settings['margin_left']   = isset( $_POST['bookcreator_template_pdf_margin_left'] ) ? wp_unslash( $_POST['bookcreator_template_pdf_margin_left'] ) : '';
                $settings['font_size']     = isset( $_POST['bookcreator_template_pdf_font_size'] ) ? wp_unslash( $_POST['bookcreator_template_pdf_font_size'] ) : '';
            }

            $owner_id = $template_id && isset( $templates[ $template_id ] ) ? bookcreator_get_template_owner_id( $templates[ $template_id ] ) : get_current_user_id();

            if ( ! $template_id || ! isset( $templates[ $template_id ] ) ) {
                $template_id = wp_generate_uuid4();
            }

            $templates[ $template_id ] = array(
                'id'       => $template_id,
                'name'     => $name,
                'type'     => $type,
                'settings' => bookcreator_normalize_template_settings( $settings, $type ),
                'owner'    => $owner_id,
            );

            update_option( 'bookcreator_templates', $templates );

            $status  = 'success';
            $message = __( 'Template salvato correttamente.', 'bookcreator' );
        }
    } elseif ( 'delete' === $action ) {
        $template_id = isset( $_POST['bookcreator_template_id'] ) ? sanitize_text_field( wp_unslash( $_POST['bookcreator_template_id'] ) ) : '';
        if ( $template_id && isset( $templates[ $template_id ] ) && $requested_type === ( isset( $templates[ $template_id ]['type'] ) ? $templates[ $template_id ]['type'] : 'epub' ) ) {
            if ( ! bookcreator_current_user_can_manage_template( $templates[ $template_id ] ) ) {
                $status  = 'error';
                $message = __( 'Non hai i permessi per modificare questo template.', 'bookcreator' );
            } else {
                unset( $templates[ $template_id ] );
                update_option( 'bookcreator_templates', $templates );
                $status  = 'success';
                $message = __( 'Template eliminato.', 'bookcreator' );
            }
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
    if ( ! current_user_can( 'bookcreator_manage_templates' ) ) {
        wp_die( esc_html__( 'Non hai i permessi per accedere a questa pagina.', 'bookcreator' ) );
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

            if ( ! bookcreator_current_user_can_manage_template( $template ) ) {
                echo '<div class="notice notice-error"><p>' . esc_html__( 'Non hai i permessi per modificare questo template.', 'bookcreator' ) . '</p></div>';
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

        if ( in_array( $current_type, array( 'epub', 'pdf' ), true ) ) {
            $is_epub             = ( 'epub' === $current_type );
            $stylable_fields      = $is_epub ? bookcreator_get_epub_stylable_fields() : bookcreator_get_pdf_stylable_fields();
            $style_fields         = $is_epub ? bookcreator_get_epub_style_fields() : bookcreator_get_pdf_style_fields();
            $font_families        = $is_epub ? bookcreator_get_epub_font_family_options() : bookcreator_get_pdf_font_family_options();
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

            $font_size_unit        = $is_epub ? 'rem' : 'pt';
            $font_size_step        = $is_epub ? '0.1' : '0.5';
            $font_size_placeholder = $is_epub ? __( 'es. 1.2', 'bookcreator' ) : __( 'es. 12', 'bookcreator' );
            $line_height_placeholder = __( 'es. 1.4', 'bookcreator' );
            $margin_unit           = $is_epub ? 'em' : 'mm';
            $margin_placeholder    = $is_epub ? __( 'es. 0.2', 'bookcreator' ) : __( 'es. 5', 'bookcreator' );
            $padding_placeholder   = $margin_placeholder;
            $description_format    = $is_epub ? __( 'Definisci lo stile di %s nel file ePub generato.', 'bookcreator' ) : __( 'Definisci lo stile di %s nel file PDF generato.', 'bookcreator' );
            $visible_description   = $is_epub ? __( 'Deseleziona un elemento per nasconderlo nell\'ePub generato.', 'bookcreator' ) : __( 'Deseleziona un elemento per nasconderlo nel PDF generato.', 'bookcreator' );

            $total_fields = count( $stylable_fields );
            $index        = 0;

            foreach ( $stylable_fields as $field_key => $field ) {
                $index++;
                $setting_key = $field_key . '_styles';
                $defaults    = $is_epub ? bookcreator_get_epub_style_defaults( $field_key ) : bookcreator_get_pdf_style_defaults( $field_key );
                $stored      = isset( $values[ $setting_key ] ) ? (array) $values[ $setting_key ] : array();

                if ( 'book_title' === $field_key && empty( $stored['color'] ) && ! empty( $values['title_color'] ) ) {
                    $stored['color'] = $values['title_color'];
                }

                $styles = $is_epub
                    ? bookcreator_normalize_epub_style_values( $stored, $defaults, $values, $setting_key )
                    : bookcreator_normalize_pdf_style_values( $stored, $defaults );

                if ( ! isset( $font_families[ $styles['font_family'] ] ) ) {
                    $styles['font_family'] = $defaults['font_family'];
                }

                $label = isset( $field['label'] ) ? $field['label'] : ucfirst( str_replace( '_', ' ', $field_key ) );
                $description = isset( $field['description'] ) && $field['description'] ? $field['description'] : sprintf( $description_format, $label );

                $field_prefix = 'bookcreator_template_' . $current_type . '_' . $field_key . '_';

                echo '<tr>';
                echo '<th scope="row">' . esc_html( $label ) . '</th>';
                echo '<td>';
                echo '<p class="description">' . esc_html( $description ) . '</p>';

                $hyphenation_value = isset( $styles['hyphenation'] ) && in_array( $styles['hyphenation'], $hyphenation_choices, true ) ? $styles['hyphenation'] : $defaults['hyphenation'];

                echo '<div class="bookcreator-style-grid bookcreator-style-grid--two-columns">';

                echo '<div class="bookcreator-style-grid__column">';

                $font_size_id = $field_prefix . 'font_size';
                echo '<div class="bookcreator-style-grid__item">';
                echo '<label for="' . esc_attr( $font_size_id ) . '">' . esc_html( sprintf( __( 'Dimensione font (%s)', 'bookcreator' ), $font_size_unit ) ) . '</label>';
                echo '<input type="number" step="' . esc_attr( $font_size_step ) . '" min="0" id="' . esc_attr( $font_size_id ) . '" name="' . esc_attr( $font_size_id ) . '" value="' . esc_attr( $styles['font_size'] ) . '" placeholder="' . esc_attr( $font_size_placeholder ) . '" inputmode="decimal" />';
                echo '</div>';

                $line_height_id = $field_prefix . 'line_height';
                echo '<div class="bookcreator-style-grid__item">';
                echo '<label for="' . esc_attr( $line_height_id ) . '">' . esc_html__( 'Altezza riga (valore)', 'bookcreator' ) . '</label>';
                echo '<input type="number" step="0.1" min="0" id="' . esc_attr( $line_height_id ) . '" name="' . esc_attr( $line_height_id ) . '" value="' . esc_attr( $styles['line_height'] ) . '" placeholder="' . esc_attr( $line_height_placeholder ) . '" inputmode="decimal" />';
                echo '</div>';

                $font_family_id = $field_prefix . 'font_family';
                echo '<div class="bookcreator-style-grid__item">';
                echo '<label for="' . esc_attr( $font_family_id ) . '">' . esc_html__( 'Famiglia font', 'bookcreator' ) . '</label>';
                echo '<select id="' . esc_attr( $font_family_id ) . '" name="' . esc_attr( $font_family_id ) . '">';
                foreach ( $font_families as $family_key => $family ) {
                    $selected = selected( $styles['font_family'], $family_key, false );
                    echo '<option value="' . esc_attr( $family_key ) . '"' . $selected . '>' . esc_html( $family['label'] ) . '</option>';
                }
                echo '</select>';
                echo '</div>';

                $font_style_id = $field_prefix . 'font_style';
                echo '<div class="bookcreator-style-grid__item">';
                echo '<label for="' . esc_attr( $font_style_id ) . '">' . esc_html__( 'Stile font', 'bookcreator' ) . '</label>';
                echo '<select id="' . esc_attr( $font_style_id ) . '" name="' . esc_attr( $font_style_id ) . '">';
                foreach ( $font_style_options as $style_key => $style_label ) {
                    $selected = selected( $styles['font_style'], $style_key, false );
                    echo '<option value="' . esc_attr( $style_key ) . '"' . $selected . '>' . esc_html( $style_label ) . '</option>';
                }
                echo '</select>';
                echo '</div>';

                $font_weight_id = $field_prefix . 'font_weight';
                echo '<div class="bookcreator-style-grid__item">';
                echo '<label for="' . esc_attr( $font_weight_id ) . '">' . esc_html__( 'Peso font', 'bookcreator' ) . '</label>';
                echo '<select id="' . esc_attr( $font_weight_id ) . '" name="' . esc_attr( $font_weight_id ) . '">';
                foreach ( $font_weight_options as $weight_key => $weight_label ) {
                    $selected = selected( $styles['font_weight'], $weight_key, false );
                    echo '<option value="' . esc_attr( $weight_key ) . '"' . $selected . '>' . esc_html( $weight_label ) . '</option>';
                }
                echo '</select>';
                echo '</div>';

                $hyphenation_id = $field_prefix . 'hyphenation';
                echo '<div class="bookcreator-style-grid__item">';
                echo '<label for="' . esc_attr( $hyphenation_id ) . '">' . esc_html__( 'Sillabazione', 'bookcreator' ) . '</label>';
                echo '<select id="' . esc_attr( $hyphenation_id ) . '" name="' . esc_attr( $hyphenation_id ) . '">';
                foreach ( $hyphenation_choices as $hyphenation_choice ) {
                    $label_value = isset( $hyphenation_labels[ $hyphenation_choice ] ) ? $hyphenation_labels[ $hyphenation_choice ] : strtoupper( $hyphenation_choice );
                    $selected    = selected( $hyphenation_value, $hyphenation_choice, false );
                    echo '<option value="' . esc_attr( $hyphenation_choice ) . '"' . $selected . '>' . esc_html( $label_value ) . '</option>';
                }
                echo '</select>';
                echo '</div>';

                if ( ! empty( $field['supports_width_percent'] ) ) {
                    $width_id = $field_prefix . 'width_percent';
                    echo '<div class="bookcreator-style-grid__item">';
                    echo '<label for="' . esc_attr( $width_id ) . '">' . esc_html__( 'Larghezza logo (%)', 'bookcreator' ) . '</label>';
                    echo '<input type="number" step="1" min="0" max="100" id="' . esc_attr( $width_id ) . '" name="' . esc_attr( $width_id ) . '" value="' . esc_attr( $styles['width_percent'] ) . '" placeholder="' . esc_attr__( 'es. 40', 'bookcreator' ) . '" inputmode="numeric" />';
                    echo '</div>';
                }

                echo '</div>';

                echo '<div class="bookcreator-style-grid__column">';

                $text_align_id = $field_prefix . 'text_align';
                echo '<div class="bookcreator-style-grid__item">';
                echo '<label for="' . esc_attr( $text_align_id ) . '">' . esc_html__( 'Allineamento', 'bookcreator' ) . '</label>';
                echo '<select id="' . esc_attr( $text_align_id ) . '" name="' . esc_attr( $text_align_id ) . '">';
                foreach ( $alignment_options as $align_key => $align_label ) {
                    $selected = selected( $styles['text_align'], $align_key, false );
                    echo '<option value="' . esc_attr( $align_key ) . '"' . $selected . '>' . esc_html( $align_label ) . '</option>';
                }
                echo '</select>';
                echo '</div>';

                $color_id = $field_prefix . 'color';
                echo '<div class="bookcreator-style-grid__item">';
                echo '<label for="' . esc_attr( $color_id ) . '">' . esc_html__( 'Colore', 'bookcreator' ) . '</label>';
                echo '<input type="text" id="' . esc_attr( $color_id ) . '" name="' . esc_attr( $color_id ) . '" class="bookcreator-color-field" value="' . esc_attr( $styles['color'] ) . '" data-default-color="' . esc_attr( $defaults['color'] ) . '" />';
                echo '</div>';

                $background_id = $field_prefix . 'background_color';
                echo '<div class="bookcreator-style-grid__item">';
                echo '<label for="' . esc_attr( $background_id ) . '">' . esc_html__( 'Colore sfondo', 'bookcreator' ) . '</label>';
                echo '<input type="text" id="' . esc_attr( $background_id ) . '" name="' . esc_attr( $background_id ) . '" class="bookcreator-color-field" value="' . esc_attr( $styles['background_color'] ) . '" data-default-color="' . esc_attr( $defaults['background_color'] ) . '" />';
                echo '</div>';

                echo '<div class="bookcreator-style-grid__item">';
                echo '<span class="bookcreator-style-grid__group-title">' . esc_html( sprintf( __( 'Margine (%s)', 'bookcreator' ), $margin_unit ) ) . '</span>';
                echo '<div class="bookcreator-style-split">';
                foreach ( $margin_fields as $direction => $direction_label ) {
                    $field_suffix = 'margin_' . $direction;
                    $input_id     = $field_prefix . $field_suffix;
                    echo '<div class="bookcreator-style-split__field">';
                    echo '<label for="' . esc_attr( $input_id ) . '">' . esc_html( sprintf( '%s (%s)', $direction_label, $margin_unit ) ) . '</label>';
                    echo '<input type="number" step="0.1" id="' . esc_attr( $input_id ) . '" name="' . esc_attr( $input_id ) . '" value="' . esc_attr( $styles[ $field_suffix ] ) . '" placeholder="' . esc_attr( $margin_placeholder ) . '" inputmode="decimal" />';
                    echo '</div>';
                }
                echo '</div>';
                echo '</div>';

                echo '<div class="bookcreator-style-grid__item">';
                echo '<span class="bookcreator-style-grid__group-title">' . esc_html( sprintf( __( 'Padding (%s)', 'bookcreator' ), $margin_unit ) ) . '</span>';
                echo '<div class="bookcreator-style-split">';
                foreach ( $padding_fields as $direction => $direction_label ) {
                    $field_suffix = 'padding_' . $direction;
                    $input_id     = $field_prefix . $field_suffix;
                    echo '<div class="bookcreator-style-split__field">';
                    echo '<label for="' . esc_attr( $input_id ) . '">' . esc_html( sprintf( '%s (%s)', $direction_label, $margin_unit ) ) . '</label>';
                    echo '<input type="number" step="0.1" id="' . esc_attr( $input_id ) . '" name="' . esc_attr( $input_id ) . '" value="' . esc_attr( $styles[ $field_suffix ] ) . '" placeholder="' . esc_attr( $padding_placeholder ) . '" inputmode="decimal" />';
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

            echo '<tr>';
            echo '<th scope="row">' . esc_html__( 'Elementi del libro', 'bookcreator' ) . '</th>';
            echo '<td>';
            foreach ( $style_fields as $field_key => $field ) {
                $checked = ! empty( $visible_fields[ $field_key ] );
                echo '<label style="display:block;margin-bottom:4px;">';
                echo '<input type="checkbox" name="bookcreator_template_' . esc_attr( $current_type ) . '_visible_fields[' . esc_attr( $field_key ) . ']" value="1"' . checked( $checked, true, false ) . ' /> ';
                echo esc_html( $field['label'] );
                echo '</label>';
            }
            echo '<p class="description">' . esc_html( $visible_description ) . '</p>';
            echo '</td>';
            echo '</tr>';

            if ( ! $is_epub ) {
                $page_format  = $values['page_format'];
                $page_width   = $values['page_width'];
                $page_height  = $values['page_height'];
                $margin_top   = $values['margin_top'];
                $margin_right = $values['margin_right'];
                $margin_bottom = $values['margin_bottom'];
                $margin_left  = $values['margin_left'];
                $font_size    = $values['font_size'];

                echo '<tr>';
                echo '<th scope="row"><label for="bookcreator_template_pdf_page_format">' . esc_html__( 'Formato pagina', 'bookcreator' ) . '</label></th>';
                echo '<td><select name="bookcreator_template_pdf_page_format" id="bookcreator_template_pdf_page_format">';
                $page_format_choices = isset( $type_config['settings']['page_format']['choices'] ) ? (array) $type_config['settings']['page_format']['choices'] : array();
                foreach ( $page_format_choices as $choice ) {
                    $label = ( 'Custom' === $choice ) ? __( 'Personalizzato', 'bookcreator' ) : $choice;
                    $selected = selected( $page_format, $choice, false );
                    echo '<option value="' . esc_attr( $choice ) . '"' . $selected . '>' . esc_html( $label ) . '</option>';
                }
                echo '</select></td>';
                echo '</tr>';

                $custom_size_style = ( 'Custom' === $page_format ) ? '' : ' style="display:none;"';

                echo '<tr class="bookcreator-template-pdf-custom-size"' . $custom_size_style . '>';
                echo '<th scope="row"><label for="bookcreator_template_pdf_page_width">' . esc_html__( 'Larghezza pagina (mm)', 'bookcreator' ) . '</label></th>';
                echo '<td><input name="bookcreator_template_pdf_page_width" id="bookcreator_template_pdf_page_width" type="number" class="small-text" step="0.1" min="0" value="' . esc_attr( $page_width ) . '" placeholder="' . esc_attr__( 'es. 210', 'bookcreator' ) . '" /></td>';
                echo '</tr>';

                echo '<tr class="bookcreator-template-pdf-custom-size"' . $custom_size_style . '>';
                echo '<th scope="row"><label for="bookcreator_template_pdf_page_height">' . esc_html__( 'Altezza pagina (mm)', 'bookcreator' ) . '</label></th>';
                echo '<td><input name="bookcreator_template_pdf_page_height" id="bookcreator_template_pdf_page_height" type="number" class="small-text" step="0.1" min="0" value="' . esc_attr( $page_height ) . '" placeholder="' . esc_attr__( 'es. 297', 'bookcreator' ) . '" /></td>';
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
                echo '<td><input name="bookcreator_template_pdf_font_size" id="bookcreator_template_pdf_font_size" type="number" class="small-text" step="0.5" min="1" value="' . esc_attr( $font_size ) . '" /></td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';
        echo '</div>';

        submit_button( $is_edit ? __( 'Aggiorna template', 'bookcreator' ) : __( 'Crea template', 'bookcreator' ) );
        echo ' <a href="' . esc_url( $default_url ) . '" class="button-secondary">' . esc_html__( 'Annulla', 'bookcreator' ) . '</a>';
        echo '</form>';

        if ( $is_edit ) {
            echo '<div class="bookcreator-template-import-export">';
            echo '<h2>' . esc_html__( 'Importa / Esporta impostazioni', 'bookcreator' ) . '</h2>';
            echo '<p class="description">' . esc_html__( 'Scarica un file JSON con le impostazioni attuali oppure importa un file per sovrascriverle.', 'bookcreator' ) . '</p>';

            echo '<form method="post" class="bookcreator-template-export">';
            wp_nonce_field( 'bookcreator_manage_template', 'bookcreator_template_nonce' );
            echo '<input type="hidden" name="bookcreator_template_action" value="export" />';
            echo '<input type="hidden" name="bookcreator_template_type" value="' . esc_attr( $current_type ) . '" />';
            echo '<input type="hidden" name="bookcreator_template_id" value="' . esc_attr( $template_id ) . '" />';
            submit_button( __( 'Esporta impostazioni in JSON', 'bookcreator' ), 'secondary', '', false );
            echo '</form>';

            echo '<form method="post" enctype="multipart/form-data" class="bookcreator-template-import">';
            wp_nonce_field( 'bookcreator_manage_template', 'bookcreator_template_nonce' );
            echo '<input type="hidden" name="bookcreator_template_action" value="import" />';
            echo '<input type="hidden" name="bookcreator_template_type" value="' . esc_attr( $current_type ) . '" />';
            echo '<input type="hidden" name="bookcreator_template_id" value="' . esc_attr( $template_id ) . '" />';
            echo '<label class="screen-reader-text" for="bookcreator_template_import_file">' . esc_html__( 'Seleziona file JSON del template', 'bookcreator' ) . '</label>';
            echo '<input type="file" id="bookcreator_template_import_file" name="bookcreator_template_import_file" accept="application/json,.json" required /> ';
            submit_button( __( 'Importa da JSON', 'bookcreator' ), 'secondary', '', false );
            echo '</form>';

            echo '</div>';
        }

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

            if ( 'Custom' === $settings['page_format'] ) {
                $width_display  = number_format_i18n( $settings['page_width'], 1 );
                $height_display = number_format_i18n( $settings['page_height'], 1 );
                /* translators: %1$s: page width in mm. %2$s: page height in mm. */
                $format_display = sprintf( __( 'Personalizzato (%1$s × %2$s mm)', 'bookcreator' ), $width_display, $height_display );
            } else {
                $format_display = $settings['page_format'];
            }

            echo '<strong>' . esc_html__( 'Formato pagina', 'bookcreator' ) . ':</strong> ' . esc_html( $format_display ) . '<br />';
            echo '<strong>' . esc_html__( 'Margini (T/D/B/S)', 'bookcreator' ) . ':</strong> ' . esc_html( $top . ' / ' . $right . ' / ' . $bottom . ' / ' . $left . ' mm' ) . '<br />';
            echo '<strong>' . esc_html__( 'Dimensione font', 'bookcreator' ) . ':</strong> ' . esc_html( $settings['font_size'] ) . ' pt<br />';

            $title_color    = $settings['title_color'];
            $visible_fields = isset( $settings['visible_fields'] ) ? (array) $settings['visible_fields'] : array();
            $style_fields   = bookcreator_get_pdf_style_fields();
            $hidden_labels  = array();

            foreach ( $style_fields as $field_key => $field ) {
                $is_visible = isset( $visible_fields[ $field_key ] ) ? (bool) $visible_fields[ $field_key ] : true;
                if ( ! $is_visible ) {
                    $hidden_labels[] = $field['label'];
                }
            }

            echo '<span class="bookcreator-color-sample" style="background-color: ' . esc_attr( $title_color ) . ';"></span>' . esc_html( $title_color );
            echo '<br /><strong>' . esc_html__( 'Elementi nascosti', 'bookcreator' ) . ':</strong> ' . ( $hidden_labels ? esc_html( implode( ', ', $hidden_labels ) ) : esc_html__( 'Nessuno', 'bookcreator' ) );
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
        $can_manage_template = bookcreator_current_user_can_manage_template( $template );

        echo '<td>';
        if ( $can_manage_template ) {
            echo '<a class="button button-small" href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Modifica', 'bookcreator' ) . '</a> ';
        }
        echo '<form method="post" style="display:inline;margin-right:4px;" class="bookcreator-template-export-inline">';
        wp_nonce_field( 'bookcreator_manage_template', 'bookcreator_template_nonce' );
        echo '<input type="hidden" name="bookcreator_template_action" value="export" />';
        echo '<input type="hidden" name="bookcreator_template_type" value="' . esc_attr( $current_type ) . '" />';
        echo '<input type="hidden" name="bookcreator_template_id" value="' . esc_attr( $template['id'] ) . '" />';
        submit_button( __( 'Esporta JSON', 'bookcreator' ), 'secondary button-small', '', false );
        echo '</form>';
        if ( $can_manage_template ) {
            echo '<form method="post" style="display:inline;" onsubmit="return confirm(\'' . esc_js( __( 'Sei sicuro di voler eliminare questo template?', 'bookcreator' ) ) . '\');">';
            wp_nonce_field( 'bookcreator_manage_template', 'bookcreator_template_nonce' );
            echo '<input type="hidden" name="bookcreator_template_action" value="delete" />';
            echo '<input type="hidden" name="bookcreator_template_type" value="' . esc_attr( $current_type ) . '" />';
            echo '<input type="hidden" name="bookcreator_template_id" value="' . esc_attr( $template['id'] ) . '" />';
            submit_button( __( 'Elimina', 'bookcreator' ), 'delete button-small', '', false );
            echo '</form>';
        }
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

function bookcreator_render_template_texts_translation_section( $language, $fields, $generated, $base_texts, $language_label = '' ) {
    $definitions     = bookcreator_get_template_texts_definitions();
    $raw_language    = (string) $language;
    $display_label   = $language_label ? $language_label : bookcreator_get_language_label( $raw_language );
    if ( '' === $display_label ) {
        $display_label = strtoupper( $raw_language );
    }

    $language_attr  = $raw_language;
    $language_class = '__LANG__' === $raw_language ? $raw_language : sanitize_html_class( $raw_language );

    $generated_display = '';
    if ( $generated ) {
        $timestamp = strtotime( $generated );
        if ( $timestamp ) {
            $generated_display = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
        }
    }

    echo '<div class="bookcreator-template-texts-section" data-language="' . esc_attr( $language_attr ) . '">';
    echo '<input type="hidden" class="bookcreator-template-texts-language" name="bookcreator_template_texts_translations[' . esc_attr( $language_attr ) . '][language]" value="' . esc_attr( $language_attr ) . '" />';
    echo '<input type="hidden" class="bookcreator-template-texts-generated" name="bookcreator_template_texts_translations[' . esc_attr( $language_attr ) . '][generated]" value="' . esc_attr( $generated ) . '" />';
    echo '<h3>' . esc_html( $display_label ) . '</h3>';

    $generated_label = $generated_display ? sprintf( __( 'Ultimo aggiornamento: %s', 'bookcreator' ), $generated_display ) : '';
    echo '<p class="description bookcreator-template-texts-generated-display">' . esc_html( $generated_label ) . '</p>';

    echo '<p>';
    echo '<button type="button" class="button bookcreator-template-texts-generate" data-language="' . esc_attr( $language ) . '">' . esc_html__( 'Genera traduzione con Claude', 'bookcreator' ) . '</button> ';
    echo '<span class="spinner"></span> ';
    echo '<button type="button" class="button-link bookcreator-template-texts-delete" data-language="' . esc_attr( $language ) . '">' . esc_html__( 'Rimuovi traduzione', 'bookcreator' ) . '</button>';
    echo '</p>';

    echo '<div class="bookcreator-template-texts-feedback" aria-live="polite"></div>';

    echo '<table class="form-table"><tbody>';
    foreach ( $definitions as $key => $definition ) {
        $field_id = 'bookcreator_template_texts_' . $language_class . '_' . sanitize_html_class( $key );
        if ( isset( $fields[ $key ] ) && '' !== $fields[ $key ] ) {
            $value = $fields[ $key ];
        } elseif ( isset( $base_texts[ $key ] ) ) {
            $value = $base_texts[ $key ];
        } else {
            $value = isset( $definition['default'] ) ? $definition['default'] : '';
        }

        echo '<tr>';
        echo '<th scope="row"><label for="' . esc_attr( $field_id ) . '">' . esc_html( $definition['label'] ) . '</label></th>';
        echo '<td>';
        echo '<input type="text" class="regular-text" id="' . esc_attr( $field_id ) . '" name="bookcreator_template_texts_translations[' . esc_attr( $language_attr ) . '][fields][' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '" />';
        if ( ! empty( $definition['description'] ) ) {
            echo '<p class="description">' . esc_html( $definition['description'] ) . '</p>';
        }
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
}

function bookcreator_render_template_texts_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Non hai i permessi per accedere a questa pagina.', 'bookcreator' ) );
    }

    $definitions = bookcreator_get_template_texts_definitions();

    if ( isset( $_POST['bookcreator_template_texts_action'] ) ) {
        check_admin_referer( 'bookcreator_template_texts', 'bookcreator_template_texts_nonce' );

        $raw_base         = isset( $_POST['bookcreator_template_texts_base'] ) ? wp_unslash( (array) $_POST['bookcreator_template_texts_base'] ) : array();
        $raw_translations = isset( $_POST['bookcreator_template_texts_translations'] ) ? wp_unslash( (array) $_POST['bookcreator_template_texts_translations'] ) : array();

        $warnings        = array();
        $sanitized_base  = bookcreator_sanitize_template_texts_base_input( $raw_base, $warnings );

        $base_for_translations = array();
        foreach ( $definitions as $key => $definition ) {
            if ( isset( $sanitized_base[ $key ] ) ) {
                $base_for_translations[ $key ] = $sanitized_base[ $key ];
            } else {
                $base_for_translations[ $key ] = isset( $definition['default'] ) ? $definition['default'] : '';
            }
        }

        $sanitized_translations = bookcreator_sanitize_template_text_translations_input( $raw_translations, $warnings, $base_for_translations );

        bookcreator_update_template_texts_storage( $sanitized_base, $sanitized_translations );

        add_settings_error( 'bookcreator_template_texts', 'bookcreator_template_texts_saved', __( 'Testi aggiornati con successo.', 'bookcreator' ), 'updated' );

        foreach ( $warnings as $index => $message ) {
            add_settings_error( 'bookcreator_template_texts', 'bookcreator_template_texts_warning_' . $index, $message, 'warning' );
        }
    }

    $base_texts   = bookcreator_get_all_template_texts();
    $translations = bookcreator_get_template_texts_translations();

    $existing_languages = implode( ',', array_keys( $translations ) );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__( 'Testo template', 'bookcreator' ); ?></h1>
        <?php settings_errors( 'bookcreator_template_texts' ); ?>
        <form method="post">
            <?php wp_nonce_field( 'bookcreator_template_texts', 'bookcreator_template_texts_nonce' ); ?>
            <input type="hidden" name="bookcreator_template_texts_action" value="save" />

            <h2><?php esc_html_e( 'Testi di base', 'bookcreator' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Personalizza i testi statici utilizzati nei template del libro.', 'bookcreator' ); ?></p>
            <table class="form-table">
                <tbody>
                    <?php foreach ( $definitions as $key => $definition ) :
                        $field_id = 'bookcreator_template_texts_base_' . sanitize_html_class( $key );
                        $value    = isset( $base_texts[ $key ] ) ? $base_texts[ $key ] : ( isset( $definition['default'] ) ? $definition['default'] : '' );
                        ?>
                        <tr>
                            <th scope="row"><label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $definition['label'] ); ?></label></th>
                            <td>
                                <input type="text" id="<?php echo esc_attr( $field_id ); ?>" name="bookcreator_template_texts_base[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
                                <?php if ( ! empty( $definition['description'] ) ) : ?>
                                    <p class="description"><?php echo esc_html( $definition['description'] ); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2><?php esc_html_e( 'Traduzioni', 'bookcreator' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Aggiungi o aggiorna le traduzioni dei testi per generare ePub in altre lingue.', 'bookcreator' ); ?></p>

            <p>
                <label for="bookcreator-template-texts-language"><?php esc_html_e( 'Nuova lingua', 'bookcreator' ); ?></label>
                <select id="bookcreator-template-texts-language">
                    <option value=""><?php esc_html_e( 'Seleziona una lingua', 'bookcreator' ); ?></option>
                    <?php foreach ( bookcreator_get_language_options() as $code => $label ) : ?>
                        <option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button" id="bookcreator-template-texts-add"><?php esc_html_e( 'Aggiungi lingua', 'bookcreator' ); ?></button>
            </p>

            <div id="bookcreator-template-texts-sections" class="bookcreator-template-texts-sections" data-existing-languages="<?php echo esc_attr( $existing_languages ); ?>">
                <?php if ( $translations ) : ?>
                    <?php foreach ( $translations as $language => $translation ) :
                        $fields    = isset( $translation['fields'] ) ? $translation['fields'] : array();
                        $generated = isset( $translation['generated'] ) ? $translation['generated'] : '';
                        $label     = bookcreator_get_language_label( $language );
                        bookcreator_render_template_texts_translation_section( $language, $fields, $generated, $base_texts, $label );
                    endforeach; ?>
                <?php else : ?>
                    <p class="description bookcreator-template-texts-empty"><?php esc_html_e( 'Nessuna traduzione presente. Aggiungi una lingua per iniziare.', 'bookcreator' ); ?></p>
                <?php endif; ?>
            </div>

            <script type="text/template" id="bookcreator-template-texts-section-template">
                <?php
                ob_start();
                bookcreator_render_template_texts_translation_section( '__LANG__', array(), '', $base_texts, '__LANG_LABEL__' );
                $template_markup = ob_get_clean();
                echo wp_kses_post( $template_markup );
                ?>
            </script>

            <?php submit_button( __( 'Salva testi', 'bookcreator' ) ); ?>
        </form>
    </div>
    <?php
}

function bookcreator_register_template_texts_page() {
    add_submenu_page(
        'edit.php?post_type=book_creator',
        __( 'Testo template', 'bookcreator' ),
        __( 'Testo template', 'bookcreator' ),
        'manage_options',
        'bc-template-texts',
        'bookcreator_render_template_texts_page'
    );
}
add_action( 'admin_menu', 'bookcreator_register_template_texts_page', 20 );

function bookcreator_template_texts_admin_enqueue( $hook ) {
    if ( 'book_creator_page_bc-template-texts' !== $hook ) {
        return;
    }

    wp_enqueue_script(
        'bookcreator-template-texts',
        plugin_dir_url( __FILE__ ) . 'js/template-texts.js',
        array( 'jquery' ),
        '1.0',
        true
    );

    wp_localize_script(
        'bookcreator-template-texts',
        'bookcreatorTemplateTexts',
        array(
            'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'bookcreator_generate_template_text_translation' ),
            'keys'      => array_keys( bookcreator_get_template_texts_definitions() ),
            'languages' => bookcreator_get_language_options(),
            'baseTexts' => bookcreator_get_all_template_texts(),
            'strings'   => array(
                'selectLanguage'   => __( 'Seleziona una lingua prima di aggiungerla.', 'bookcreator' ),
                'duplicateLanguage'=> __( 'Questa lingua è già presente.', 'bookcreator' ),
                'deleteConfirm'    => __( 'Vuoi rimuovere la traduzione per %s?', 'bookcreator' ),
                'generating'       => __( 'Generazione in corso…', 'bookcreator' ),
                'success'          => __( 'Traduzione aggiornata.', 'bookcreator' ),
                'error'            => __( 'Impossibile generare la traduzione. Riprova.', 'bookcreator' ),
                'replaceConfirm'   => __( 'Sostituire il contenuto esistente con la nuova traduzione?', 'bookcreator' ),
                'generatedLabel'   => __( 'Ultimo aggiornamento: %s', 'bookcreator' ),
                'noTranslations'   => __( 'Nessuna traduzione presente. Aggiungi una lingua per iniziare.', 'bookcreator' ),
            ),
        )
    );
}
add_action( 'admin_enqueue_scripts', 'bookcreator_template_texts_admin_enqueue' );

function bookcreator_ajax_generate_template_text_translation() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Non hai i permessi per eseguire questa azione.', 'bookcreator' ) ) );
    }

    check_ajax_referer( 'bookcreator_generate_template_text_translation', 'nonce' );

    $language = isset( $_POST['language'] ) ? sanitize_text_field( wp_unslash( $_POST['language'] ) ) : '';
    $language = bookcreator_sanitize_translation_language( $language );
    if ( '' === $language ) {
        wp_send_json_error( array( 'message' => __( 'La lingua selezionata non è valida.', 'bookcreator' ) ) );
    }

    $result = bookcreator_generate_template_text_translation( $language );
    if ( is_wp_error( $result ) ) {
        wp_send_json_error( array( 'message' => $result->get_error_message() ) );
    }

    $generated_display = '';
    if ( ! empty( $result['generated'] ) ) {
        $timestamp = strtotime( $result['generated'] );
        if ( $timestamp ) {
            $generated_display = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
        }
    }

    wp_send_json_success(
        array(
            'language'          => $result['language'],
            'fields'            => $result['fields'],
            'warnings'          => $result['warnings'],
            'generated'         => $result['generated'],
            'generated_display' => $generated_display,
            'model_notice'      => isset( $result['model_notice'] ) ? $result['model_notice'] : '',
            'message'           => __( 'Traduzione aggiornata.', 'bookcreator' ),
        )
    );
}
add_action( 'wp_ajax_bookcreator_generate_template_text_translation', 'bookcreator_ajax_generate_template_text_translation' );

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
        'bookcreator_manage_templates',
        'bc-templates-epub',
        'bookcreator_templates_page_epub'
    );

    add_submenu_page(
        'edit.php?post_type=book_creator',
        __( 'Template PDF', 'bookcreator' ),
        __( 'Template PDF', 'bookcreator' ),
        'bookcreator_manage_templates',
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

function bookcreator_build_template_styles( $template = null, $type = 'epub' ) {
    $allowed_types = array( 'epub', 'pdf' );
    if ( ! in_array( $type, $allowed_types, true ) ) {
        $type = 'epub';
    }

    if ( $template && ( ! isset( $template['type'] ) || $type !== $template['type'] ) ) {
        $template = null;
    }

    $settings       = $template ? bookcreator_normalize_template_settings( isset( $template['settings'] ) ? $template['settings'] : array(), $type ) : bookcreator_get_default_template_settings( $type );
    $title_color    = isset( $settings['title_color'] ) ? $settings['title_color'] : '#333333';
    $visible_fields = isset( $settings['visible_fields'] ) ? (array) $settings['visible_fields'] : ( 'pdf' === $type ? bookcreator_get_pdf_default_visible_fields() : bookcreator_get_epub_default_visible_fields() );
    $style_fields   = ( 'pdf' === $type ) ? bookcreator_get_pdf_style_fields() : bookcreator_get_epub_style_fields();

    $hidden_selectors = array();

    $stylable_fields = ( 'pdf' === $type ) ? bookcreator_get_pdf_stylable_fields() : bookcreator_get_epub_stylable_fields();
    $font_families   = ( 'pdf' === $type ) ? bookcreator_get_pdf_font_family_options() : bookcreator_get_epub_font_family_options();
    $font_imports    = array();
    $normalized_styles = array();

    foreach ( $stylable_fields as $field_key => $field ) {
        $setting_key = $field_key . '_styles';
        $defaults    = ( 'pdf' === $type ) ? bookcreator_get_pdf_style_defaults( $field_key ) : bookcreator_get_epub_style_defaults( $field_key );
        $raw_styles  = isset( $settings[ $setting_key ] ) ? (array) $settings[ $setting_key ] : array();

        if ( 'book_title' === $field_key && empty( $raw_styles['color'] ) && $title_color ) {
            $raw_styles['color'] = $title_color;
        }

        $normalized_styles[ $field_key ] = ( 'pdf' === $type )
            ? bookcreator_normalize_pdf_style_values( $raw_styles, $defaults )
            : bookcreator_normalize_epub_style_values( $raw_styles, $defaults, $settings, $setting_key );
    }

    foreach ( $style_fields as $field_key => $field ) {
        $is_visible = isset( $visible_fields[ $field_key ] ) ? (bool) $visible_fields[ $field_key ] : true;
        if ( ! $is_visible && ! empty( $field['selectors'] ) ) {
            $hidden_selectors = array_merge( $hidden_selectors, $field['selectors'] );
        }
    }

    $book_title_defaults = ( 'pdf' === $type ) ? bookcreator_get_pdf_style_defaults( 'book_title' ) : bookcreator_get_epub_style_defaults( 'book_title' );
    $book_title_styles   = isset( $normalized_styles['book_title'] ) ? $normalized_styles['book_title'] : $book_title_defaults;
    $book_title_color    = $book_title_styles['color'] ? $book_title_styles['color'] : ( $title_color ? $title_color : $book_title_defaults['color'] );

    if ( 'pdf' === $type ) {
        $chapter_defaults = bookcreator_get_pdf_style_defaults( 'chapter_content' );
        $body_font_key    = $chapter_defaults['font_family'];
        if ( isset( $normalized_styles['chapter_content']['font_family'] ) && $normalized_styles['chapter_content']['font_family'] ) {
            $body_font_key = $normalized_styles['chapter_content']['font_family'];
        }
        if ( ! isset( $font_families[ $body_font_key ] ) ) {
            $body_font_key = $chapter_defaults['font_family'];
        }
        $body_font_css = $font_families[ $body_font_key ]['css'];

        $heading_defaults = bookcreator_get_pdf_style_defaults( 'chapter_titles' );
        $heading_font_key = $heading_defaults['font_family'];
        if ( isset( $normalized_styles['chapter_titles']['font_family'] ) && $normalized_styles['chapter_titles']['font_family'] ) {
            $heading_font_key = $normalized_styles['chapter_titles']['font_family'];
        }
        if ( ! isset( $font_families[ $heading_font_key ] ) ) {
            $heading_font_key = $body_font_key;
        }
        $heading_font_css = $font_families[ $heading_font_key ]['css'];

        $styles = array(
            '@page :first {',
            '  margin-top: 0mm;',
            '  margin-right: 0mm;',
            '  margin-bottom: 0mm;',
            '  margin-left: 0mm;',
            '  margin-header: 0mm;',
            '  margin-footer: 0mm;',
            '}',
            'body {',
            '  font-family: ' . $body_font_css . ';',
            '  line-height: 1.6;',
            '  margin: 0;',
            '}',
            'img {',
            '  max-width: 100%;',
            '  height: auto;',
            '}',
            '.bookcreator-frontispiece__publisher-logo {',
            '  text-align: center;',
            '  margin: 10mm 0;',
            '}',
            '.bookcreator-frontispiece__publisher-logo-image {',
            '  display: inline-block;',
            '}',
            'h1, h2, h3 {',
            '  font-family: ' . $heading_font_css . ';',
            '  margin-top: 12mm;',
            '  margin-bottom: 6mm;',
            '}',
            '.bookcreator-meta {',
            '  margin: 0;',
            '}',
            '.bookcreator-meta dt {',
            '  font-weight: bold;',
            '  margin-top: 8mm;',
            '}',
            '.bookcreator-meta dd {',
            '  margin: 0 0 5mm 0;',
            '}',
            '.bookcreator-field-label {',
            '  font-weight: bold;',
            '}',
            '.bookcreator-copyright__meta {',
            '  margin: 0;',
            '}',
            '.bookcreator-copyright__meta dt {',
            '  font-weight: bold;',
            '  margin-top: 8mm;',
            '}',
            '.bookcreator-copyright__meta dd {',
            '  margin: 0 0 5mm 0;',
            '}',
            '.bookcreator-section {',
            '  margin-bottom: 15mm;',
            '}',
            '.bookcreator-chapter {',
            '  margin-bottom: 25mm;',
            '}',
            '.bookcreator-paragraph {',
            '  margin-bottom: 20mm;',
            '}',
            '.bookcreator-paragraph__title {',
            '  margin-top: 12mm;',
            '  margin-bottom: 6mm;',
            '}',
            '.bookcreator-paragraph__content {',
            '  margin-bottom: 10mm;',
            '}',
            '.bookcreator-cover {',
            '  text-align: center;',
            '  margin: 0;',
            '  padding: 0;',
            '  width: 100%;',
            '  page-break-after: always;',
            '  page-break-inside: avoid;',
            '}',
            '.bookcreator-cover img {',
            '  display: block;',
            '  margin: 0;',
            '  width: 100%;',
            '  height: 100%;',
            '  object-fit: cover;',
            '}',
            '.alignleft {',
            '  float: left;',
            '  margin: 0 10mm 10mm 0;',
            '}',
            '.alignright {',
            '  float: right;',
            '  margin: 0 0 10mm 10mm;',
            '}',
            '.alignleft img, .alignright img {',
            '  display: block;',
            '}',
            '.aligncenter {',
            '  display: block;',
            '  margin: 0 auto 10mm;',
            '  text-align: center;',
            '}',
            '.aligncenter img {',
            '  display: inline-block;',
            '}',
            '.bookcreator-book__index-list, .bookcreator-book__index ol, .bookcreator-preface__index-list, .bookcreator-preface__index ol, #toc .bookcreator-book__index-list, #toc ol {',
            '  list-style: none;',
            '  margin: 0;',
            '  padding-left: 0;',
            '}',
            '.bookcreator-book__index-sublist, .bookcreator-book__index-list ol, .bookcreator-preface__index-sublist, .bookcreator-preface__index-list ol, #toc .bookcreator-book__index-sublist, #toc .bookcreator-book__index-list ol {',
            '  margin-left: 15mm;',
            '}',
            '.bookcreator-footnotes, .bookcreator-citations {',
            '  font-size: 11pt;',
            '  border-top: 1px solid #cccccc;',
            '  margin-top: 10mm;',
            '  padding-top: 5mm;',
            '}',
            '.bookcreator-book-title {',
            '  color: ' . $book_title_color . ';',
            '}',
        );
    } else {
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
            '.bookcreator-frontispiece__publisher-logo {',
            '  text-align: center;',
            '  margin: 1em 0;',
            '}',
            '.bookcreator-frontispiece__publisher-logo-image {',
            '  display: inline-block;',
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
            '.alignleft {',
            '  float: left;',
            '  margin: 0 1.5em 1.5em 0;',
            '}',
            '.alignright {',
            '  float: right;',
            '  margin: 0 0 1.5em 1.5em;',
            '}',
            '.alignleft img, .alignright img {',
            '  display: block;',
            '}',
            '.aligncenter {',
            '  display: block;',
            '  margin: 0 auto 1.5em;',
            '  text-align: center;',
            '}',
            '.aligncenter img {',
            '  display: inline-block;',
            '}',
            '.bookcreator-book__index-list, .bookcreator-book__index ol, .bookcreator-preface__index-list, .bookcreator-preface__index ol, #toc .bookcreator-book__index-list, #toc ol {',
            '  list-style: none;',
            '  margin: 0;',
            '  padding-left: 0;',
            '}',
            '.bookcreator-book__index-sublist, .bookcreator-book__index-list ol, .bookcreator-preface__index-sublist, .bookcreator-preface__index-list ol, #toc .bookcreator-book__index-sublist, #toc .bookcreator-book__index-list ol {',
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
    }

    $font_unit = ( 'pdf' === $type ) ? 'pt' : 'rem';
    $box_unit  = ( 'pdf' === $type ) ? 'mm' : 'em';

    foreach ( $stylable_fields as $field_key => $field ) {
        $selectors = isset( $field['selectors'] ) ? (array) $field['selectors'] : array();
        $selectors = array_filter( array_map( 'trim', $selectors ) );

        if ( empty( $selectors ) ) {
            continue;
        }

        $defaults     = ( 'pdf' === $type ) ? bookcreator_get_pdf_style_defaults( $field_key ) : bookcreator_get_epub_style_defaults( $field_key );
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
        $font_size       = bookcreator_format_css_numeric_value( $font_size_value, $font_unit );
        if ( '' === $font_size ) {
            $font_size = bookcreator_format_css_numeric_value( $defaults['font_size'], $font_unit );
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
        $margin = bookcreator_format_css_box_numeric_values( $margin_values, $box_unit );

        $padding_values = array(
            '' !== $field_styles['padding_top'] ? $field_styles['padding_top'] : $defaults['padding_top'],
            '' !== $field_styles['padding_right'] ? $field_styles['padding_right'] : $defaults['padding_right'],
            '' !== $field_styles['padding_bottom'] ? $field_styles['padding_bottom'] : $defaults['padding_bottom'],
            '' !== $field_styles['padding_left'] ? $field_styles['padding_left'] : $defaults['padding_left'],
        );
        $padding = bookcreator_format_css_box_numeric_values( $padding_values, $box_unit );

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

    if ( isset( $normalized_styles['book_publisher_logo'] ) ) {
        $logo_styles = $normalized_styles['book_publisher_logo'];
        if ( ! empty( $logo_styles['width_percent'] ) ) {
            $width_value = bookcreator_sanitize_numeric_value( $logo_styles['width_percent'] );
            if ( '' !== $width_value ) {
                $width_float = (float) $width_value;
                if ( $width_float < 0 ) {
                    $width_float = 0;
                }
                if ( $width_float > 100 ) {
                    $width_float = 100;
                }
                $width_value = bookcreator_sanitize_numeric_value( (string) $width_float );
                if ( '' !== $width_value ) {
                    $styles[] = '.bookcreator-frontispiece__publisher-logo img {';
                    $styles[] = '  width: ' . $width_value . '%;';
                    $styles[] = '  height: auto;';
                    $styles[] = '}';
                }
            }
        }
    }

    $styles[] = '.bookcreator-paragraph__featured-image {';
    $styles[] = ( 'pdf' === $type ) ? '  margin: 10mm 0;' : '  margin: 1em 0;';
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

function bookcreator_get_epub_styles( $template = null ) {
    return bookcreator_build_template_styles( $template, 'epub' );
}

function bookcreator_get_pdf_styles( $template = null ) {
    return bookcreator_build_template_styles( $template, 'pdf' );
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
            $html .= '<ol class="bookcreator-book__index-sublist">';
            $html .= bookcreator_build_nav_items_html( $item['children'] );
            $html .= '</ol>';
        }

        $html .= '</li>' . "\n";
    }

    return $html;
}

function bookcreator_build_nav_document( $book_title, $chapters, $language = 'en', $template_texts = null ) {
    $language      = $language ? strtolower( str_replace( '_', '-', $language ) ) : 'en';
    $language_attr = bookcreator_escape_xml( $language );
    if ( null === $template_texts ) {
        $template_texts = bookcreator_get_all_template_texts( $language );
    }

    $document_title_template = isset( $template_texts['toc_document_title'] ) ? $template_texts['toc_document_title'] : __( 'Indice - %s', 'bookcreator' );
    $heading_text            = isset( $template_texts['toc_heading'] ) ? $template_texts['toc_heading'] : __( 'Indice', 'bookcreator' );

    $title   = bookcreator_escape_xml( sprintf( $document_title_template, $book_title ) );
    $heading = bookcreator_escape_xml( $heading_text );

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
<nav epub:type="toc" id="toc" class="bookcreator-book__index">
<h1 class="bookcreator-book__index-title">{$heading}</h1>
<ol class="bookcreator-book__index-list">
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
 * @param array  $chapters_data Structured chapter data.
 * @param string $language      Target language code.
 * @param array|null $template_texts Optional template texts to reuse.
 * @return string
 */
function bookcreator_build_epub_preface_index( $chapters_data, $language = '', $template_texts = null ) {
    if ( empty( $chapters_data ) ) {
        return '';
    }

    if ( null === $template_texts ) {
        $template_texts = bookcreator_get_all_template_texts( $language );
    }

    $heading_text       = isset( $template_texts['preface_index_heading'] ) ? $template_texts['preface_index_heading'] : __( 'Indice', 'bookcreator' );
    $chapter_fallback   = isset( $template_texts['chapter_fallback_title'] ) ? $template_texts['chapter_fallback_title'] : __( 'Capitolo %s', 'bookcreator' );
    $paragraph_fallback = isset( $template_texts['paragraph_fallback_title'] ) ? $template_texts['paragraph_fallback_title'] : __( 'Paragrafo %s', 'bookcreator' );

    $html  = '<nav class="bookcreator-preface__index bookcreator-book__index">';
    $html .= '<h2 class="bookcreator-preface__index-title bookcreator-book__index-title">' . esc_html( $heading_text ) . '</h2>';
    $html .= '<ol class="bookcreator-preface__index-list bookcreator-book__index-list">';

    foreach ( $chapters_data as $chapter_data ) {
        if ( empty( $chapter_data['href'] ) || empty( $chapter_data['number'] ) ) {
            continue;
        }

        $chapter_title = isset( $chapter_data['title'] ) ? $chapter_data['title'] : '';
        if ( '' === $chapter_title ) {
            $chapter_title = sprintf( $chapter_fallback, $chapter_data['number'] );
        }

        $chapter_label = $chapter_data['number'] . '.';
        $html         .= '<li>';
        $html         .= '<a href="' . esc_attr( $chapter_data['href'] ) . '">' . esc_html( $chapter_label . ' ' . $chapter_title ) . '</a>';

        if ( ! empty( $chapter_data['paragraphs'] ) && is_array( $chapter_data['paragraphs'] ) ) {
            $html .= '<ol class="bookcreator-preface__index-sublist bookcreator-book__index-sublist">';

            foreach ( $chapter_data['paragraphs'] as $paragraph_data ) {
                if ( empty( $paragraph_data['href'] ) || empty( $paragraph_data['number'] ) ) {
                    continue;
                }

                $paragraph_title = isset( $paragraph_data['title'] ) && '' !== $paragraph_data['title']
                    ? $paragraph_data['title']
                    : sprintf( $paragraph_fallback, $paragraph_data['number'] );

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

/**
 * Build the HTML index used inside generated PDFs.
 *
 * @param array  $chapters_posts Ordered chapter posts.
 * @param string $target_language Target language slug.
 * @param array  $template_texts  Template text overrides.
 * @return string
 */
function bookcreator_build_pdf_index_markup( $chapters_posts, $target_language = '', $template_texts = null ) {
    if ( empty( $chapters_posts ) ) {
        return '';
    }

    $target_language = bookcreator_sanitize_translation_language( $target_language );

    if ( null === $template_texts ) {
        $template_texts = bookcreator_get_all_template_texts( $target_language );
    }

    $heading_text       = isset( $template_texts['book_index_heading'] ) ? $template_texts['book_index_heading'] : __( 'Indice', 'bookcreator' );
    $chapter_fallback   = isset( $template_texts['chapter_fallback_title'] ) ? $template_texts['chapter_fallback_title'] : __( 'Capitolo %s', 'bookcreator' );
    $paragraph_fallback = isset( $template_texts['paragraph_fallback_title'] ) ? $template_texts['paragraph_fallback_title'] : __( 'Paragrafo %s', 'bookcreator' );

    $index_html  = '<nav class="bookcreator-book__index">';
    $index_html .= '<h1 class="bookcreator-book__index-title">' . esc_html( $heading_text ) . '</h1>';
    $index_html .= '<ol class="bookcreator-book__index-list">';

    foreach ( $chapters_posts as $chapter_index => $chapter ) {
        $chapter_number      = (string) ( $chapter_index + 1 );
        $chapter_translation = $target_language ? bookcreator_get_translation_for_language( $chapter->ID, 'bc_chapter', $target_language ) : null;
        $chapter_title_base  = get_the_title( $chapter );
        $chapter_title       = $chapter_translation ? bookcreator_get_translation_field_value( $chapter_translation, 'post_title', $chapter_title_base ) : $chapter_title_base;
        if ( '' === $chapter_title ) {
            $chapter_title = sprintf( $chapter_fallback, $chapter_number );
        }

        $index_html .= '<li>';
        $index_html .= '<a href="#chapter-' . esc_attr( $chapter->ID ) . '">' . esc_html( $chapter_number . ' ' . $chapter_title ) . '</a>';

        $paragraph_posts = bookcreator_get_ordered_paragraphs_for_chapter( $chapter->ID );
        if ( $paragraph_posts ) {
            $index_html .= '<ol class="bookcreator-book__index-sublist">';

            foreach ( $paragraph_posts as $paragraph_index => $paragraph ) {
                $paragraph_number      = $chapter_number . '.' . ( $paragraph_index + 1 );
                $paragraph_translation = $target_language ? bookcreator_get_translation_for_language( $paragraph->ID, 'bc_paragraph', $target_language ) : null;
                $paragraph_title_base  = get_the_title( $paragraph );
                $paragraph_title       = $paragraph_translation ? bookcreator_get_translation_field_value( $paragraph_translation, 'post_title', $paragraph_title_base ) : $paragraph_title_base;
                if ( '' === $paragraph_title ) {
                    $paragraph_title = sprintf( $paragraph_fallback, $paragraph_number );
                }

                $index_html .= '<li>';
                $index_html .= '<a href="#paragraph-' . esc_attr( $paragraph->ID ) . '">' . esc_html( $paragraph_number . ' ' . $paragraph_title ) . '</a>';
                $index_html .= '</li>';
            }

            $index_html .= '</ol>';
        }

        $index_html .= '</li>';
    }

    $index_html .= '</ol>';
    $index_html .= '</nav>';

    return $index_html;
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

function bookcreator_create_epub_from_book( $book_id, $template_id = '', $target_language = '' ) {
    if ( ! bookcreator_load_epub_library() ) {
        return new WP_Error( 'bookcreator_epub_missing_library', bookcreator_get_epub_library_error_message() );
    }

    $book_post = get_post( $book_id );
    if ( ! $book_post || 'book_creator' !== $book_post->post_type ) {
        return new WP_Error( 'bookcreator_epub_invalid_book', __( 'Libro non valido.', 'bookcreator' ) );
    }

    $permalink       = get_permalink( $book_post );
    $target_language = bookcreator_sanitize_translation_language( $target_language );
    $book_translation = $target_language ? bookcreator_get_translation_for_language( $book_id, 'book_creator', $target_language ) : null;

    $title = get_the_title( $book_post );

    $template = $template_id ? bookcreator_get_template( $template_id ) : null;
    if ( $template && ( ! isset( $template['type'] ) || 'epub' !== $template['type'] ) ) {
        return new WP_Error( 'bookcreator_epub_invalid_template', __( 'Il template selezionato non è valido per gli ePub.', 'bookcreator' ) );
    }

    $book_language_meta = get_post_meta( $book_id, 'bc_language', true );

    if ( $target_language ) {
        $language = $target_language;
    } else {
        $language = $book_language_meta;
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
    }

    $template_texts = bookcreator_get_all_template_texts( $language );

    $cover_section_title         = isset( $template_texts['cover_title'] ) ? $template_texts['cover_title'] : __( 'Copertina', 'bookcreator' );
    $frontispiece_section_title  = isset( $template_texts['frontispiece_title'] ) ? $template_texts['frontispiece_title'] : __( 'Frontespizio', 'bookcreator' );
    $copyright_section_title     = isset( $template_texts['copyright_title'] ) ? $template_texts['copyright_title'] : __( 'Copyright', 'bookcreator' );
    $dedication_section_title    = isset( $template_texts['dedication_title'] ) ? $template_texts['dedication_title'] : __( 'Dedica', 'bookcreator' );
    $preface_section_title       = isset( $template_texts['preface_title'] ) ? $template_texts['preface_title'] : __( 'Prefazione', 'bookcreator' );
    $appendix_section_title      = isset( $template_texts['appendix_title'] ) ? $template_texts['appendix_title'] : __( 'Appendice', 'bookcreator' );
    $bibliography_section_title  = isset( $template_texts['bibliography_title'] ) ? $template_texts['bibliography_title'] : __( 'Bibliografia', 'bookcreator' );
    $author_note_section_title   = isset( $template_texts['author_note_title'] ) ? $template_texts['author_note_title'] : __( 'Nota dell\'autore', 'bookcreator' );
    $acknowledgments_section_title = isset( $template_texts['acknowledgments_title'] ) ? $template_texts['acknowledgments_title'] : __( 'Ringraziamenti', 'bookcreator' );
    $footnotes_heading_text      = isset( $template_texts['footnotes_heading'] ) ? $template_texts['footnotes_heading'] : __( 'Note', 'bookcreator' );
    $citations_heading_text      = isset( $template_texts['citations_heading'] ) ? $template_texts['citations_heading'] : __( 'Citazioni', 'bookcreator' );
    $chapter_fallback_title      = isset( $template_texts['chapter_fallback_title'] ) ? $template_texts['chapter_fallback_title'] : __( 'Capitolo %s', 'bookcreator' );
    $paragraph_fallback_title    = isset( $template_texts['paragraph_fallback_title'] ) ? $template_texts['paragraph_fallback_title'] : __( 'Paragrafo %s', 'bookcreator' );
    $publication_date_label      = isset( $template_texts['publication_date_label'] ) ? $template_texts['publication_date_label'] : __( 'Data di pubblicazione', 'bookcreator' );

    $identifier_meta  = get_post_meta( $book_id, 'bc_isbn', true );
    $identifier_value = $identifier_meta ? $identifier_meta : $permalink;
    if ( ! $identifier_value ) {
        $identifier_value = 'bookcreator-' . $book_id;
        if ( $target_language ) {
            $identifier_value .= '-' . $language;
        }
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
    $subtitle         = get_post_meta( $book_id, 'bc_subtitle', true );
    $custom_frontispiece = get_post_meta( $book_id, 'bc_frontispiece', true );
    $dedication       = get_post_meta( $book_id, 'bc_dedication', true );
    $preface          = get_post_meta( $book_id, 'bc_preface', true );
    $acknowledgments  = get_post_meta( $book_id, 'bc_acknowledgments', true );
    $appendix         = get_post_meta( $book_id, 'bc_appendix', true );
    $bibliography     = get_post_meta( $book_id, 'bc_bibliography', true );
    $author_note      = get_post_meta( $book_id, 'bc_author_note', true );
    $edition          = get_post_meta( $book_id, 'bc_edition', true );

    $author    = get_post_meta( $book_id, 'bc_author', true );
    $coauthors = get_post_meta( $book_id, 'bc_coauthors', true );

    if ( $book_translation ) {
        $title              = bookcreator_get_translation_field_value( $book_translation, 'post_title', $title );
        $subtitle           = bookcreator_get_translation_field_value( $book_translation, 'bc_subtitle', $subtitle );
        $author             = bookcreator_get_translation_field_value( $book_translation, 'bc_author', $author );
        $coauthors          = bookcreator_get_translation_field_value( $book_translation, 'bc_coauthors', $coauthors );
        $publisher          = bookcreator_get_translation_field_value( $book_translation, 'bc_publisher', $publisher );
        $identifier_meta    = bookcreator_get_translation_field_value( $book_translation, 'bc_isbn', $identifier_meta );
        $publication_raw    = bookcreator_get_translation_field_value( $book_translation, 'bc_pub_date', $publication_raw );
        $edition            = bookcreator_get_translation_field_value( $book_translation, 'bc_edition', $edition );
        $description_meta   = bookcreator_get_translation_field_value( $book_translation, 'bc_description', $description_meta );
        $custom_frontispiece = bookcreator_get_translation_field_value( $book_translation, 'bc_frontispiece', $custom_frontispiece );
        $rights_meta        = bookcreator_get_translation_field_value( $book_translation, 'bc_copyright', $rights_meta );
        $dedication         = bookcreator_get_translation_field_value( $book_translation, 'bc_dedication', $dedication );
        $preface            = bookcreator_get_translation_field_value( $book_translation, 'bc_preface', $preface );
        $appendix           = bookcreator_get_translation_field_value( $book_translation, 'bc_appendix', $appendix );
        $bibliography       = bookcreator_get_translation_field_value( $book_translation, 'bc_bibliography', $bibliography );
        $author_note        = bookcreator_get_translation_field_value( $book_translation, 'bc_author_note', $author_note );
        $acknowledgments    = bookcreator_get_translation_field_value( $book_translation, 'bc_acknowledgments', $acknowledgments );
    }

    $author_display = trim( $author . ( $coauthors ? ', ' . $coauthors : '' ) );

    $subjects = array();
    $genres   = get_the_terms( $book_id, 'book_genre' );
    if ( ! empty( $genres ) && ! is_wp_error( $genres ) ) {
        foreach ( $genres as $genre ) {
            $subjects[] = $genre->name;
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

    $cover_asset        = null;
    $cover_id           = (int) get_post_meta( $book_id, 'bc_cover', true );
    $publisher_logo_id  = (int) get_post_meta( $book_id, 'bc_publisher_logo', true );
    if ( $book_translation && ! empty( $book_translation['cover_id'] ) ) {
        $cover_id = (int) $book_translation['cover_id'];
    }
    if ( $book_translation && ! empty( $book_translation['publisher_logo_id'] ) ) {
        $publisher_logo_id = (int) $book_translation['publisher_logo_id'];
    }
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
                'title'    => $cover_section_title,
                'filename' => 'cover.xhtml',
                'href'     => 'cover.xhtml',
                'content'  => bookcreator_build_epub_document( $cover_section_title, $cover_body, $language ),
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

    if ( $subtitle ) {
        $frontispiece_body .= '<p class="bookcreator-frontispiece__subtitle">' . esc_html( $subtitle ) . '</p>';
    }

    if ( $publisher ) {
        $frontispiece_body .= '<p class="bookcreator-frontispiece__field bookcreator-frontispiece__field-bc_publisher">' . esc_html( $publisher ) . '</p>';
    }

    if ( $publisher_logo_id ) {
        $logo_url = wp_get_attachment_url( $publisher_logo_id );
        if ( $logo_url ) {
            $alt_text = $publisher ? $publisher : __( 'Logo editore', 'bookcreator' );
            $frontispiece_body .= '<div class="bookcreator-frontispiece__publisher-logo"><img class="bookcreator-frontispiece__publisher-logo-image" src="' . esc_url( $logo_url ) . '" alt="' . esc_attr( $alt_text ) . '" /></div>';
        }
    }

    $language_label = $target_language ? bookcreator_get_language_label( $language ) : bookcreator_get_language_label( $book_language_meta );
    if ( $language_label ) {
        $frontispiece_body .= '<p class="bookcreator-frontispiece__field bookcreator-frontispiece__field-bc_language">' . esc_html( $language_label ) . '</p>';
    }

    if ( $description_meta ) {
        $frontispiece_body .= '<section class="bookcreator-frontispiece__description">';
        $frontispiece_body .= bookcreator_prepare_epub_content( $description_meta );
        $frontispiece_body .= '</section>';
    }

    if ( $custom_frontispiece ) {
        $frontispiece_body .= '<section class="bookcreator-frontispiece__extra">';
        $frontispiece_body .= bookcreator_prepare_epub_content( $custom_frontispiece );
        $frontispiece_body .= '</section>';
    }

    $frontispiece_body .= '</div>';
    $frontispiece_body  = bookcreator_process_epub_images( $frontispiece_body, $assets, $asset_map );

    $chapters[] = array(
        'id'       => 'frontispiece',
        'title'    => $frontispiece_section_title,
        'filename' => 'frontispiece.xhtml',
        'href'     => 'frontispiece.xhtml',
        'content'  => bookcreator_build_epub_document( $frontispiece_section_title, $frontispiece_body, $language ),
        'children' => array(),
    );

    $copyright_items = array();

    $isbn = $identifier_meta;
    if ( $isbn ) {
        $copyright_items[] = array(
            'label' => __( 'ISBN', 'bookcreator' ),
            'value' => $isbn,
        );
    }

    if ( $publication_date ) {
        $display_publication_date = mysql2date( get_option( 'date_format' ), $publication_date );
        $copyright_items[]        = array(
            'label' => $publication_date_label,
            'value' => $display_publication_date,
        );
    }

    $legal_notice = $rights_meta;

    if ( $copyright_items || $legal_notice ) {
        $copyright_body  = '<div class="bookcreator-copyright">';
        $copyright_body .= '<h1>' . esc_html( $copyright_section_title ) . '</h1>';

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
            'title'    => $copyright_section_title,
            'filename' => 'copyright.xhtml',
            'href'     => 'copyright.xhtml',
            'content'  => bookcreator_build_epub_document( $copyright_section_title, $copyright_body, $language ),
            'children' => array(),
        );
    }

    $ordered_chapter_posts = bookcreator_get_ordered_chapters_for_book( $book_id );
    $ordered_chapters      = array();

    if ( $ordered_chapter_posts ) {
        foreach ( $ordered_chapter_posts as $index => $chapter_post ) {
            $chapter_translation = $target_language ? bookcreator_get_translation_for_language( $chapter_post->ID, 'bc_chapter', $target_language ) : null;
            $chapter_title_base  = get_the_title( $chapter_post );
            $chapter_title       = $chapter_translation ? bookcreator_get_translation_field_value( $chapter_translation, 'post_title', $chapter_title_base ) : $chapter_title_base;
            $chapter_slug        = sanitize_title( $chapter_post->post_name ? $chapter_post->post_name : $chapter_title_base );

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

                    $paragraph_translation = $target_language ? bookcreator_get_translation_for_language( $paragraph_post->ID, 'bc_paragraph', $target_language ) : null;
                    $paragraph_title_base  = get_the_title( $paragraph_post );
                    $paragraph_title       = $paragraph_translation ? bookcreator_get_translation_field_value( $paragraph_translation, 'post_title', $paragraph_title_base ) : $paragraph_title_base;

                    $paragraphs_data[] = array(
                        'post'         => $paragraph_post,
                        'title'        => $paragraph_title,
                        'number'       => $paragraph_number,
                        'href'         => $file_slug . '#paragraph-' . $paragraph_post->ID,
                        'translation'  => $paragraph_translation,
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
                'translation' => $chapter_translation,
            );
        }
    }

    if ( $dedication ) {
        $dedication_body  = '<div class="bookcreator-dedication">';
        $dedication_body .= '<h1>' . esc_html( $dedication_section_title ) . '</h1>';
        $dedication_body .= bookcreator_prepare_epub_content( $dedication );
        $dedication_body .= '</div>';
        $dedication_body  = bookcreator_process_epub_images( $dedication_body, $assets, $asset_map );

        $chapters[] = array(
            'id'       => 'dedication',
            'title'    => $dedication_section_title,
            'filename' => 'dedication.xhtml',
            'href'     => 'dedication.xhtml',
            'content'  => bookcreator_build_epub_document( $dedication_section_title, $dedication_body, $language ),
            'children' => array(),
        );
    }

    if ( $preface || $ordered_chapters ) {
        $preface_body  = '<div class="bookcreator-preface">';
        $preface_body .= '<h1 class="bookcreator-preface__title">' . esc_html( $preface_section_title ) . '</h1>';

        $preface_body .= '<div class="bookcreator-preface__content">';

        if ( $preface ) {
            $preface_body .= bookcreator_prepare_epub_content( $preface );
        }

        if ( $ordered_chapters ) {
            $preface_body .= bookcreator_build_epub_preface_index( $ordered_chapters, $language, $template_texts );
        }

        $preface_body .= '</div>';

        $preface_body .= '</div>';
        $preface_body  = bookcreator_process_epub_images( $preface_body, $assets, $asset_map );

        $chapters[] = array(
            'id'       => 'preface',
            'title'    => $preface_section_title,
            'filename' => 'preface.xhtml',
            'href'     => 'preface.xhtml',
            'content'  => bookcreator_build_epub_document( $preface_section_title, $preface_body, $language ),
            'children' => array(),
        );
    }

    if ( $ordered_chapters ) {
        foreach ( $ordered_chapters as $index => $chapter_data ) {
            $chapter          = $chapter_data['post'];
            $chapter_translation = isset( $chapter_data['translation'] ) ? $chapter_data['translation'] : null;
            $chapter_title   = isset( $chapter_data['title'] ) ? $chapter_data['title'] : '';
            if ( '' === $chapter_title ) {
                $chapter_title = sprintf( $chapter_fallback_title, $chapter_data['number'] );
            }
            $file_slug      = $chapter_data['file_slug'];
            $chapter_body   = '<section class="bookcreator-chapter">';
            $chapter_body  .= '<h1 class="bookcreator-chapter__title">' . esc_html( $chapter_title ) . '</h1>';
            $chapter_paragraph_items = array();

            $chapter_content = $chapter && $chapter->post_content ? $chapter->post_content : '';
            if ( $chapter_translation ) {
                $chapter_content = bookcreator_get_translation_field_value( $chapter_translation, 'post_content', $chapter_content );
            }

            if ( $chapter_content ) {
                $chapter_body .= '<div class="bookcreator-chapter__content">';
                $chapter_body .= bookcreator_prepare_epub_content( $chapter_content );
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
                        $paragraph_title = sprintf( $paragraph_fallback_title, $paragraph_data['number'] );
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

                    $paragraph_translation = isset( $paragraph_data['translation'] ) ? $paragraph_data['translation'] : null;
                    $paragraph_content     = $paragraph->post_content;
                    if ( $paragraph_translation ) {
                        $paragraph_content = bookcreator_get_translation_field_value( $paragraph_translation, 'post_content', $paragraph_content );
                    }

                    if ( $paragraph_content ) {
                        $chapter_body .= '<div class="bookcreator-paragraph__content">';
                        $chapter_body .= bookcreator_prepare_epub_content( $paragraph_content );
                        $chapter_body .= '</div>';
                    }

                    $footnotes = get_post_meta( $paragraph->ID, 'bc_footnotes', true );
                    if ( $paragraph_translation ) {
                        $footnotes = bookcreator_get_translation_field_value( $paragraph_translation, 'bc_footnotes', $footnotes );
                    }
                    if ( $footnotes ) {
                        $chapter_body .= '<div class="bookcreator-footnotes">';
                        $chapter_body .= '<h3>' . esc_html( $footnotes_heading_text ) . '</h3>';
                        $chapter_body .= bookcreator_prepare_epub_content( $footnotes );
                        $chapter_body .= '</div>';
                    }

                    $citations = get_post_meta( $paragraph->ID, 'bc_citations', true );
                    if ( $paragraph_translation ) {
                        $citations = bookcreator_get_translation_field_value( $paragraph_translation, 'bc_citations', $citations );
                    }
                    if ( $citations ) {
                        $chapter_body .= '<div class="bookcreator-citations">';
                        $chapter_body .= '<h3>' . esc_html( $citations_heading_text ) . '</h3>';
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
        'bc_acknowledgments' => array(
            'id'       => 'acknowledgments',
            'title'    => $acknowledgments_section_title,
            'filename' => 'acknowledgments.xhtml',
            'content'  => $acknowledgments,
        ),
        'bc_appendix'     => array(
            'id'       => 'appendix',
            'title'    => $appendix_section_title,
            'filename' => 'appendix.xhtml',
            'content'  => $appendix,
        ),
        'bc_bibliography' => array(
            'id'       => 'bibliography',
            'title'    => $bibliography_section_title,
            'filename' => 'bibliography.xhtml',
            'content'  => $bibliography,
        ),
        'bc_author_note'  => array(
            'id'       => 'author-note',
            'title'    => $author_note_section_title,
            'filename' => 'author-note.xhtml',
            'content'  => $author_note,
        ),
    );

    foreach ( $final_sections as $meta_key => $section ) {
        $content = isset( $section['content'] ) ? $section['content'] : '';
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

    $nav_document = bookcreator_build_nav_document( $title, $chapters, $language, $template_texts );

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
    if ( $target_language ) {
        $zip_filename = $file_slug . '-' . $language . '.epub';
    }
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

    if ( $target_language ) {
        $translations_meta = get_post_meta( $book_id, 'bc_translated_epubs', true );
        if ( ! is_array( $translations_meta ) ) {
            $translations_meta = array();
        }

        $translations_meta = array_values(
            array_filter(
                $translations_meta,
                static function ( $entry ) use ( $language ) {
                    if ( ! is_array( $entry ) ) {
                        return true;
                    }

                    $entry_language = isset( $entry['language'] ) ? bookcreator_sanitize_translation_language( $entry['language'] ) : '';

                    return $entry_language !== $language;
                }
            )
        );

        $translations_meta[] = array(
            'language'  => $language,
            'file'      => $zip_filename,
            'url'       => $url,
            'generated' => current_time( 'mysql' ),
            'title'     => $title,
        );

        update_post_meta( $book_id, 'bc_translated_epubs', $translations_meta );
    } else {
        update_post_meta(
            $book_id,
            'bc_epub_file',
            array(
                'file'      => $zip_filename,
                'generated' => current_time( 'mysql' ),
            )
        );
    }

    return array(
        'file' => $zip_filename,
        'path' => $full_path,
        'url'  => $url,
        'language' => $language,
        'title'     => $title,
    );
}


function bookcreator_generate_pdf_from_book( $book_id, $template_id = '', $target_language = '' ) {
    if ( ! bookcreator_load_mpdf_library() ) {
        return new WP_Error( 'bookcreator_pdf_missing_library', bookcreator_get_pdf_library_error_message() );
    }

    $book_post = get_post( $book_id );
    if ( ! $book_post || 'book_creator' !== $book_post->post_type ) {
        return new WP_Error( 'bookcreator_pdf_invalid_book', __( 'Libro non valido.', 'bookcreator' ) );
    }

    $target_language  = bookcreator_sanitize_translation_language( $target_language );
    $book_translation = $target_language ? bookcreator_get_translation_for_language( $book_id, 'book_creator', $target_language ) : null;

    $title = get_the_title( $book_post );

    $template = $template_id ? bookcreator_get_template( $template_id ) : null;
    if ( $template && ( ! isset( $template['type'] ) || 'pdf' !== $template['type'] ) ) {
        return new WP_Error( 'bookcreator_pdf_invalid_template', __( 'Il template selezionato non è valido per i PDF.', 'bookcreator' ) );
    }
    $pdf_settings = $template ? bookcreator_normalize_template_settings( $template['settings'], 'pdf' ) : bookcreator_get_default_template_settings( 'pdf' );
    $visible_fields = isset( $pdf_settings['visible_fields'] ) ? (array) $pdf_settings['visible_fields'] : bookcreator_get_pdf_default_visible_fields();
    $index_visible  = ! ( isset( $visible_fields['book_index'] ) && ! $visible_fields['book_index'] );

    $book_language_meta = get_post_meta( $book_id, 'bc_language', true );

    if ( $target_language ) {
        $language = $target_language;
    } else {
        $language = $book_language_meta;
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
    }

    $template_texts = bookcreator_get_all_template_texts( $language );

    $copyright_section_title   = isset( $template_texts['copyright_title'] ) ? $template_texts['copyright_title'] : __( 'Copyright', 'bookcreator' );
    $dedication_section_title  = isset( $template_texts['dedication_title'] ) ? $template_texts['dedication_title'] : __( 'Dedica', 'bookcreator' );
    $preface_section_title     = isset( $template_texts['preface_title'] ) ? $template_texts['preface_title'] : __( 'Prefazione', 'bookcreator' );
    $ack_section_title         = isset( $template_texts['acknowledgments_title'] ) ? $template_texts['acknowledgments_title'] : __( 'Ringraziamenti', 'bookcreator' );
    $appendix_section_title    = isset( $template_texts['appendix_title'] ) ? $template_texts['appendix_title'] : __( 'Appendice', 'bookcreator' );
    $bibliography_section_title = isset( $template_texts['bibliography_title'] ) ? $template_texts['bibliography_title'] : __( 'Bibliografia', 'bookcreator' );
    $author_note_section_title = isset( $template_texts['author_note_title'] ) ? $template_texts['author_note_title'] : __( "Nota dell'autore", 'bookcreator' );
    $footnotes_heading_text    = isset( $template_texts['footnotes_heading'] ) ? $template_texts['footnotes_heading'] : __( 'Note', 'bookcreator' );
    $citations_heading_text    = isset( $template_texts['citations_heading'] ) ? $template_texts['citations_heading'] : __( 'Citazioni', 'bookcreator' );
    $chapter_fallback_title    = isset( $template_texts['chapter_fallback_title'] ) ? $template_texts['chapter_fallback_title'] : __( 'Capitolo %s', 'bookcreator' );
    $paragraph_fallback_title  = isset( $template_texts['paragraph_fallback_title'] ) ? $template_texts['paragraph_fallback_title'] : __( 'Paragrafo %s', 'bookcreator' );
    $publication_date_label    = isset( $template_texts['publication_date_label'] ) ? $template_texts['publication_date_label'] : __( 'Data di pubblicazione', 'bookcreator' );

    $subtitle         = get_post_meta( $book_id, 'bc_subtitle', true );
    $description_meta = get_post_meta( $book_id, 'bc_description', true );
    $custom_front     = get_post_meta( $book_id, 'bc_frontispiece', true );
    $publisher        = get_post_meta( $book_id, 'bc_publisher', true );
    $legal_notice     = get_post_meta( $book_id, 'bc_copyright', true );
    $dedication       = get_post_meta( $book_id, 'bc_dedication', true );
    $preface          = get_post_meta( $book_id, 'bc_preface', true );
    $acknowledgments  = get_post_meta( $book_id, 'bc_acknowledgments', true );
    $appendix         = get_post_meta( $book_id, 'bc_appendix', true );
    $bibliography     = get_post_meta( $book_id, 'bc_bibliography', true );
    $author_note      = get_post_meta( $book_id, 'bc_author_note', true );
    $edition          = get_post_meta( $book_id, 'bc_edition', true );

    $author    = get_post_meta( $book_id, 'bc_author', true );
    $coauthors = get_post_meta( $book_id, 'bc_coauthors', true );

    $publication_raw = get_post_meta( $book_id, 'bc_pub_date', true );
    $isbn            = get_post_meta( $book_id, 'bc_isbn', true );

    if ( $book_translation ) {
        $title            = bookcreator_get_translation_field_value( $book_translation, 'post_title', $title );
        $subtitle         = bookcreator_get_translation_field_value( $book_translation, 'bc_subtitle', $subtitle );
        $author           = bookcreator_get_translation_field_value( $book_translation, 'bc_author', $author );
        $coauthors        = bookcreator_get_translation_field_value( $book_translation, 'bc_coauthors', $coauthors );
        $publisher        = bookcreator_get_translation_field_value( $book_translation, 'bc_publisher', $publisher );
        $isbn             = bookcreator_get_translation_field_value( $book_translation, 'bc_isbn', $isbn );
        $publication_raw  = bookcreator_get_translation_field_value( $book_translation, 'bc_pub_date', $publication_raw );
        $edition          = bookcreator_get_translation_field_value( $book_translation, 'bc_edition', $edition );
        $description_meta = bookcreator_get_translation_field_value( $book_translation, 'bc_description', $description_meta );
        $custom_front     = bookcreator_get_translation_field_value( $book_translation, 'bc_frontispiece', $custom_front );
        $legal_notice     = bookcreator_get_translation_field_value( $book_translation, 'bc_copyright', $legal_notice );
        $dedication       = bookcreator_get_translation_field_value( $book_translation, 'bc_dedication', $dedication );
        $preface          = bookcreator_get_translation_field_value( $book_translation, 'bc_preface', $preface );
        $acknowledgments  = bookcreator_get_translation_field_value( $book_translation, 'bc_acknowledgments', $acknowledgments );
        $appendix         = bookcreator_get_translation_field_value( $book_translation, 'bc_appendix', $appendix );
        $bibliography     = bookcreator_get_translation_field_value( $book_translation, 'bc_bibliography', $bibliography );
        $author_note      = bookcreator_get_translation_field_value( $book_translation, 'bc_author_note', $author_note );
    }

    if ( $publication_raw ) {
        $publication_date = mysql2date( 'Y-m-d', $publication_raw );
    } else {
        $publication_date = gmdate( 'Y-m-d', get_post_time( 'U', true, $book_post ) );
    }

    $language_for_label = $target_language ? $language : $book_language_meta;
    $language_label     = $language_for_label ? bookcreator_get_language_label( $language_for_label ) : '';

    $css        = bookcreator_get_pdf_styles( $template );
    $body_parts = array();

    $chapters_posts = bookcreator_get_ordered_chapters_for_book( $book_id );
    $book_index_html = '';
    if ( $index_visible ) {
        $book_index_html = bookcreator_build_pdf_index_markup( $chapters_posts, $target_language, $template_texts );
    }
    $index_rendered = false;

    $cover_id          = (int) get_post_meta( $book_id, 'bc_cover', true );
    $publisher_logo_id = (int) get_post_meta( $book_id, 'bc_publisher_logo', true );

    if ( $book_translation && ! empty( $book_translation['cover_id'] ) ) {
        $cover_id = (int) $book_translation['cover_id'];
    }

    if ( $book_translation && ! empty( $book_translation['publisher_logo_id'] ) ) {
        $publisher_logo_id = (int) $book_translation['publisher_logo_id'];
    }
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

    if ( $publisher_logo_id ) {
        $logo_url = wp_get_attachment_url( $publisher_logo_id );
        if ( $logo_url ) {
            $alt_text = $publisher ? $publisher : __( 'Logo editore', 'bookcreator' );
            $frontispiece_html .= '<div class="bookcreator-frontispiece__publisher-logo"><img class="bookcreator-frontispiece__publisher-logo-image" src="' . esc_url( $logo_url ) . '" alt="' . esc_attr( $alt_text ) . '" /></div>';
        }
    }

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
            'label' => $publication_date_label,
            'value' => $display_publication_date,
        );
    }

    if ( $copyright_items || $legal_notice ) {
        $copyright_html  = '<div class="bookcreator-copyright">';
        $copyright_html .= '<h1>' . esc_html( $copyright_section_title ) . '</h1>';

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
        $dedication_html  = '<div class="bookcreator-section bookcreator-section-dedication bookcreator-dedication">';
        $dedication_html .= '<h1>' . esc_html( $dedication_section_title ) . '</h1>';
        $dedication_html .= bookcreator_prepare_epub_content( $dedication );
        $dedication_html .= '</div>';
        $body_parts[]      = $dedication_html;
    }

    if ( $preface ) {
        $preface_html  = '<div class="bookcreator-section bookcreator-section-preface bookcreator-preface">';
        $preface_html .= '<h1 class="bookcreator-preface__title">' . esc_html( $preface_section_title ) . '</h1>';
        $preface_html .= '<div class="bookcreator-preface__content">';
        $preface_html .= bookcreator_prepare_epub_content( $preface );
        $preface_html .= '</div>';
        $preface_html .= '</div>';
        $body_parts[]   = $preface_html;
        if ( $book_index_html ) {
            $body_parts[]   = $book_index_html;
            $index_rendered = true;
        }
    }

    if ( ! $index_rendered && $book_index_html ) {
        $body_parts[]   = $book_index_html;
        $index_rendered = true;
    }

    if ( $acknowledgments ) {
        $ack_html  = '<div class="bookcreator-section bookcreator-section-acknowledgments bookcreator-acknowledgments">';
        $ack_html .= '<h1>' . esc_html( $ack_section_title ) . '</h1>';
        $ack_html .= bookcreator_prepare_epub_content( $acknowledgments );
        $ack_html .= '</div>';
        $body_parts[] = $ack_html;
    }

    if ( $chapters_posts ) {
        foreach ( $chapters_posts as $chapter_index => $chapter ) {
            $chapter_number      = $chapter_index + 1;
            $chapter_translation = $target_language ? bookcreator_get_translation_for_language( $chapter->ID, 'bc_chapter', $target_language ) : null;
            $chapter_title_base  = get_the_title( $chapter );
            $chapter_title       = $chapter_translation ? bookcreator_get_translation_field_value( $chapter_translation, 'post_title', $chapter_title_base ) : $chapter_title_base;
            if ( '' === $chapter_title ) {
                $chapter_title = sprintf( $chapter_fallback_title, $chapter_number );
            }

            $chapter_content = $chapter->post_content;
            if ( $chapter_translation ) {
                $chapter_content = bookcreator_get_translation_field_value( $chapter_translation, 'post_content', $chapter_content );
            }

            $chapter_html  = '<section class="bookcreator-section bookcreator-chapter" id="chapter-' . esc_attr( $chapter->ID ) . '">';
            $chapter_html .= '<h1 class="bookcreator-chapter__title">' . esc_html( $chapter_title ) . '</h1>';

            if ( $chapter_content ) {
                $chapter_html .= '<div class="bookcreator-chapter__content">';
                $chapter_html .= bookcreator_prepare_epub_content( $chapter_content );
                $chapter_html .= '</div>';
            }

            $paragraphs = bookcreator_get_ordered_paragraphs_for_chapter( $chapter->ID );
            if ( $paragraphs ) {
                foreach ( $paragraphs as $paragraph_index => $paragraph ) {
                    $paragraph_number      = $chapter_number . '.' . ( $paragraph_index + 1 );
                    $paragraph_translation = $target_language ? bookcreator_get_translation_for_language( $paragraph->ID, 'bc_paragraph', $target_language ) : null;
                    $paragraph_title_base  = get_the_title( $paragraph );
                    $paragraph_title       = $paragraph_translation ? bookcreator_get_translation_field_value( $paragraph_translation, 'post_title', $paragraph_title_base ) : $paragraph_title_base;
                    if ( '' === $paragraph_title ) {
                        $paragraph_title = sprintf( $paragraph_fallback_title, $paragraph_number );
                    }

                    $paragraph_content = $paragraph->post_content;
                    if ( $paragraph_translation ) {
                        $paragraph_content = bookcreator_get_translation_field_value( $paragraph_translation, 'post_content', $paragraph_content );
                    }

                    $chapter_html .= '<section class="bookcreator-paragraph" id="paragraph-' . esc_attr( $paragraph->ID ) . '">';
                    $chapter_html .= '<h2 class="bookcreator-paragraph__title">' . esc_html( $paragraph_title ) . '</h2>';

                    $paragraph_thumbnail_id = get_post_thumbnail_id( $paragraph->ID );
                    if ( $paragraph_thumbnail_id ) {
                        $paragraph_image_url = wp_get_attachment_url( $paragraph_thumbnail_id );
                        if ( $paragraph_image_url ) {
                            $image_alt = get_post_meta( $paragraph_thumbnail_id, '_wp_attachment_image_alt', true );
                            if ( '' === $image_alt ) {
                                $image_alt = $paragraph_title;
                            }
                            $chapter_html .= '<div class="bookcreator-paragraph__featured-image"><img src="' . esc_url( $paragraph_image_url ) . '" alt="' . esc_attr( $image_alt ) . '" /></div>';
                        }
                    }

                    if ( $paragraph_content ) {
                        $chapter_html .= '<div class="bookcreator-paragraph__content">';
                        $chapter_html .= bookcreator_prepare_epub_content( $paragraph_content );
                        $chapter_html .= '</div>';
                    }

                    $footnotes = get_post_meta( $paragraph->ID, 'bc_footnotes', true );
                    if ( $paragraph_translation ) {
                        $footnotes = bookcreator_get_translation_field_value( $paragraph_translation, 'bc_footnotes', $footnotes );
                    }
                    if ( $footnotes ) {
                        $chapter_html .= '<div class="bookcreator-footnotes">';
                        $chapter_html .= '<h3>' . esc_html( $footnotes_heading_text ) . '</h3>';
                        $chapter_html .= bookcreator_prepare_epub_content( $footnotes );
                        $chapter_html .= '</div>';
                    }

                    $citations = get_post_meta( $paragraph->ID, 'bc_citations', true );
                    if ( $paragraph_translation ) {
                        $citations = bookcreator_get_translation_field_value( $paragraph_translation, 'bc_citations', $citations );
                    }
                    if ( $citations ) {
                        $chapter_html .= '<div class="bookcreator-citations">';
                        $chapter_html .= '<h3>' . esc_html( $citations_heading_text ) . '</h3>';
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

    $final_section_content = array(
        'bc_appendix'     => $appendix,
        'bc_bibliography' => $bibliography,
        'bc_author_note'  => $author_note,
    );

    $final_sections = array(
        'bc_appendix'     => array(
            'title' => $appendix_section_title,
            'slug'  => 'appendix',
        ),
        'bc_bibliography' => array(
            'title' => $bibliography_section_title,
            'slug'  => 'bibliography',
        ),
        'bc_author_note'  => array(
            'title' => $author_note_section_title,
            'slug'  => 'author-note',
        ),
    );

    foreach ( $final_sections as $meta_key => $section ) {
        $content = isset( $final_section_content[ $meta_key ] ) ? $final_section_content[ $meta_key ] : '';
        if ( ! $content ) {
            continue;
        }

        $section_classes = array(
            'bookcreator-section',
            'bookcreator-section-' . $section['slug'],
            'bookcreator-section-' . $meta_key,
        );
        $section_classes = array_map( 'sanitize_html_class', $section_classes );
        $section_html    = '<div class="' . esc_attr( implode( ' ', $section_classes ) ) . '">';
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
        $mpdf_format = $pdf_settings['page_format'];
        if ( 'Custom' === $mpdf_format ) {
            $width  = max( 1, (float) $pdf_settings['page_width'] );
            $height = max( 1, (float) $pdf_settings['page_height'] );
            $mpdf_format = array( $width, $height );
        }

        $default_font = 'dejavuserif';
        if ( isset( $pdf_settings['chapter_content_styles']['font_family'] ) ) {
            $candidate_font = $pdf_settings['chapter_content_styles']['font_family'];
            if ( $candidate_font ) {
                $default_font = $candidate_font;
            }
        }
        $available_fonts = bookcreator_get_pdf_font_family_options();
        if ( ! isset( $available_fonts[ $default_font ] ) ) {
            $chapter_defaults = bookcreator_get_pdf_style_defaults( 'chapter_content' );
            $default_font     = isset( $chapter_defaults['font_family'] ) ? $chapter_defaults['font_family'] : 'dejavuserif';
        }
        if ( ! isset( $available_fonts[ $default_font ] ) ) {
            $default_font = 'dejavuserif';
        }

        $mpdf_config = array(
            'format'            => $mpdf_format,
            'margin_top'        => (float) $pdf_settings['margin_top'],
            'margin_right'      => (float) $pdf_settings['margin_right'],
            'margin_bottom'     => (float) $pdf_settings['margin_bottom'],
            'margin_left'       => (float) $pdf_settings['margin_left'],
            'default_font_size' => (float) $pdf_settings['font_size'],
            'default_font'      => $default_font,
        );

        $mpdf = new \Mpdf\Mpdf( $mpdf_config );
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

    if ( $target_language ) {
        $translations_meta = get_post_meta( $book_id, 'bc_translated_pdfs', true );
        if ( ! is_array( $translations_meta ) ) {
            $translations_meta = array();
        }

        $translations_meta = array_values(
            array_filter(
                $translations_meta,
                static function ( $entry ) use ( $language ) {
                    if ( ! is_array( $entry ) ) {
                        return true;
                    }

                    $entry_language = isset( $entry['language'] ) ? bookcreator_sanitize_translation_language( $entry['language'] ) : '';

                    return $entry_language !== $language;
                }
            )
        );

        $translations_meta[] = array(
            'language'  => $language,
            'file'      => $pdf_filename,
            'url'       => $pdf_url,
            'generated' => current_time( 'mysql' ),
            'title'     => $title,
        );

        update_post_meta( $book_id, 'bc_translated_pdfs', $translations_meta );
    } else {
        update_post_meta(
            $book_id,
            'bc_pdf_file',
            array(
                'file'      => $pdf_filename,
                'generated' => current_time( 'mysql' ),
            )
        );
    }

    return array(
        'file'     => $pdf_filename,
        'path'     => $pdf_path,
        'url'      => $pdf_url,
        'language' => $language,
        'title'    => $title,
    );
}

function bookcreator_dom_node_to_html( $node ) {
    if ( ! $node instanceof DOMNode ) {
        return '';
    }

    $owner = $node->ownerDocument;
    if ( ! $owner instanceof DOMDocument ) {
        return '';
    }

    $html = $owner->saveHTML( $node );

    return null === $html ? '' : $html;
}

function bookcreator_split_html_into_segments( $html ) {
    $segments = array();

    if ( '' === trim( (string) $html ) ) {
        return $segments;
    }

    if ( ! class_exists( 'DOMDocument' ) ) {
        return array(
            array(
                'translatable' => true,
                'html'         => $html,
            ),
        );
    }

    $previous_error_state = libxml_use_internal_errors( true );
    $dom                  = new DOMDocument();
    $wrapper_id           = 'bookcreator-segment-wrapper';
    $html_to_parse        = '<div id="' . $wrapper_id . '">' . $html . '</div>';
    $loaded               = $dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html_to_parse, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
    libxml_clear_errors();
    libxml_use_internal_errors( $previous_error_state );

    if ( ! $loaded ) {
        return $segments;
    }

    $wrapper = $dom->getElementById( $wrapper_id );
    if ( ! $wrapper ) {
        return $segments;
    }

    foreach ( $wrapper->childNodes as $child ) {
        if ( ! $child instanceof DOMNode ) {
            continue;
        }

        if ( XML_TEXT_NODE === $child->nodeType ) {
            $text_content = $child->textContent;

            if ( '' === $text_content ) {
                continue;
            }

            if ( '' === trim( $text_content ) ) {
                $segments[] = array(
                    'translatable' => false,
                    'html'         => $text_content,
                );

                continue;
            }
        }

        $segment_html = bookcreator_dom_node_to_html( $child );

        if ( '' === trim( $segment_html ) && XML_TEXT_NODE !== $child->nodeType ) {
            continue;
        }

        $is_comment = defined( 'XML_COMMENT_NODE' ) && XML_COMMENT_NODE === $child->nodeType;

        $segments[] = array(
            'translatable' => ! $is_comment && ( XML_TEXT_NODE !== $child->nodeType || '' !== trim( $child->textContent ) ),
            'html'         => $segment_html,
        );
    }

    return $segments;
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
    $default_claude_settings = bookcreator_get_default_claude_settings();
    $default_model           = isset( $default_claude_settings['default_model'] ) ? $default_claude_settings['default_model'] : 'claude-3-5-sonnet-20240620';
    $model                   = isset( $claude_settings['default_model'] ) ? $claude_settings['default_model'] : $default_model;
    $timeout         = isset( $claude_settings['request_timeout'] ) ? (int) $claude_settings['request_timeout'] : 30;
    $api_key         = bookcreator_get_claude_api_key();
    $request_timeout = max( 5, min( 120, $timeout ) );
    $allowed_models  = bookcreator_get_allowed_claude_models();
    $model_notice    = '';
    $settings_prompt = isset( $claude_settings['translation_prompt'] ) ? trim( (string) $claude_settings['translation_prompt'] ) : '';
    $extra_prompts   = array_values( array_filter( array( $settings_prompt, $prompt ), 'strlen' ) );

    $send_claude_request = static function ( $model_name, $full_prompt, $timeout_seconds, $api_key_value, $max_tokens ) {
        $max_tokens = max( 1, (int) $max_tokens );

        return wp_remote_post(
            'https://api.anthropic.com/v1/messages',
            array(
                'timeout' => $timeout_seconds,
                'headers' => array(
                    'x-api-key'         => $api_key_value,
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                    'accept'            => 'application/json',
                ),
                'body'    => wp_json_encode(
                    array(
                        'model'      => $model_name,
                        'max_tokens' => $max_tokens,
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
    };

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

    $model_limits       = bookcreator_get_claude_model_limits();
    $selected_capab     = isset( $model_limits[ $model ] ) ? $model_limits[ $model ] : array();
    $max_output_tokens  = isset( $selected_capab['max_output_tokens'] ) ? (int) $selected_capab['max_output_tokens'] : 4096;
    $max_output_tokens  = $max_output_tokens > 0 ? $max_output_tokens : 4096;
    $max_chunk_chars    = isset( $selected_capab['max_chunk_chars'] ) ? (int) $selected_capab['max_chunk_chars'] : ( $max_output_tokens * 4 );
    $max_chunk_chars    = max( 4000, $max_chunk_chars );
    $output_margin      = isset( $claude_settings['output_margin'] ) ? (float) $claude_settings['output_margin'] : 0.8;
    if ( $output_margin <= 0 || $output_margin > 1 ) {
        $output_margin = 0.8;
    }

    $requested_max_tokens = (int) floor( $max_output_tokens * $output_margin );
    $requested_max_tokens = max( 512, min( $max_output_tokens, $requested_max_tokens ) );

    $chunk_limit = (int) floor( $max_chunk_chars * $output_margin );
    if ( $chunk_limit < 2000 ) {
        $chunk_limit = 2000;
    }

    $chunk_limit = (int) apply_filters( 'bookcreator_translation_chunk_limit', $chunk_limit, $model, $book_id, $selected_capab );
    if ( $chunk_limit < 1000 ) {
        $chunk_limit = 1000;
    }

    $segments_flat = array();

    foreach ( $sections as $section_index => &$section ) {
        $raw_segments = bookcreator_split_html_into_segments( $section['body_inner'] );

        if ( ! is_array( $raw_segments ) || ! $raw_segments ) {
            $raw_segments = array(
                array(
                    'translatable' => true,
                    'html'         => $section['body_inner'],
                ),
            );
        }

        $prepared_segments = array();
        $translatable_found = false;
        $segment_counter    = 1;

        foreach ( $raw_segments as $raw_segment ) {
            $segment_html         = isset( $raw_segment['html'] ) ? (string) $raw_segment['html'] : '';
            $segment_translatable = ! empty( $raw_segment['translatable'] );

            if ( $segment_translatable ) {
                $translatable_found = true;
                $segment_marker     = sprintf( '%s_SEG_%03d', $section['marker'], $segment_counter );
                $segment_counter++;
                $global_index = count( $segments_flat );

                $prepared_segments[] = array(
                    'translatable' => true,
                    'html'         => $segment_html,
                    'marker'       => $segment_marker,
                    'global_index' => $global_index,
                );

                $segments_flat[] = array(
                    'marker'        => $segment_marker,
                    'section_index' => $section_index,
                    'path'          => $section['path'],
                    'original_html' => $segment_html,
                );
            } else {
                $prepared_segments[] = array(
                    'translatable' => false,
                    'html'         => $segment_html,
                );
            }
        }

        if ( ! $translatable_found ) {
            $segment_marker  = sprintf( '%s_SEG_%03d', $section['marker'], 1 );
            $global_index    = count( $segments_flat );
            $prepared_segments = array(
                array(
                    'translatable' => true,
                    'html'         => $section['body_inner'],
                    'marker'       => $segment_marker,
                    'global_index' => $global_index,
                ),
            );

            $segments_flat[] = array(
                'marker'        => $segment_marker,
                'section_index' => $section_index,
                'path'          => $section['path'],
                'original_html' => $section['body_inner'],
            );
        }

        $section['segments'] = $prepared_segments;
    }
    unset( $section );

    $chunks               = array();
    $current_text         = '';
    $current_segment_list = array();

    foreach ( $segments_flat as $segment_index => $segment_data ) {
        $segment_text  = '[' . $segment_data['marker'] . '_START path="' . $segment_data['path'] . '" section="' . $sections[ $segment_data['section_index'] ]['marker'] . '"]' . "\n";
        $segment_text .= trim( $segment_data['original_html'] ) . "\n";
        $segment_text .= '[' . $segment_data['marker'] . '_END]';

        $segment_with_spacing = $segment_text . "\n\n";

        if ( '' !== $current_text && strlen( $current_text ) + strlen( $segment_with_spacing ) > $chunk_limit ) {
            $chunks[] = array(
                'text'     => rtrim( $current_text ),
                'segments' => $current_segment_list,
            );
            $current_text         = '';
            $current_segment_list = array();
        }

        if ( '' === $current_text && strlen( $segment_with_spacing ) > $chunk_limit ) {
            $chunks[] = array(
                'text'     => $segment_text,
                'segments' => array( $segment_index ),
            );

            continue;
        }

        $current_text         .= $segment_with_spacing;
        $current_segment_list[] = $segment_index;
    }

    if ( '' !== $current_text ) {
        $chunks[] = array(
            'text'     => rtrim( $current_text ),
            'segments' => $current_segment_list,
        );
    }

    if ( ! $chunks ) {
        bookcreator_delete_directory( $extract_dir );
        return new WP_Error( 'bookcreator_translation_chunking', __( 'Errore nella preparazione dei contenuti da tradurre.', 'bookcreator' ) );
    }

    $segment_translations = array();
    $total_chunks = count( $chunks );
    $translation_warnings = array();

    $append_translation_warnings = static function ( $message, $warnings ) {
        if ( empty( $warnings ) || ! is_array( $warnings ) ) {
            return $message;
        }

        $warning_strings = array();

        foreach ( $warnings as $warning ) {
            if ( ! is_array( $warning ) ) {
                continue;
            }

            $marker  = isset( $warning['marker'] ) ? (string) $warning['marker'] : '';
            $excerpt = isset( $warning['excerpt'] ) ? (string) $warning['excerpt'] : '';

            if ( '' === $marker ) {
                continue;
            }

            if ( '' !== $excerpt ) {
                $warning_strings[] = $marker . ': ' . $excerpt;
            } else {
                $warning_strings[] = $marker;
            }
        }

        if ( ! $warning_strings ) {
            return $message;
        }

        return $message . ' ' . sprintf( __( 'Avvisi: %s', 'bookcreator' ), implode( ' | ', $warning_strings ) );
    };

    foreach ( $chunks as $chunk_position => $chunk ) {
        $chunk_text = trim( $chunk['text'] );

        $instructions  = __( 'Sei un assistente di traduzione per ePub. Traduci il testo nella lingua richiesta mantenendo intatta la struttura HTML.', 'bookcreator' ) . "\n";
        $instructions .= sprintf( __( 'Lingua di destinazione: %s', 'bookcreator' ), $target_language_sanitized ) . "\n";
        $instructions .= __( 'Mantieni invariati i marcatori [SECTION_*_SEG_*_START] e [SECTION_*_SEG_*_END] (o equivalenti) e restituiscili insieme al contenuto tradotto.', 'bookcreator' ) . "\n";
        $instructions .= __( 'Conserva i tag, gli attributi e le entità HTML, traducendo solo il testo leggibile.', 'bookcreator' ) . "\n";
        $instructions .= __( 'Non aggiungere testo fuori dai marcatori.', 'bookcreator' ) . "\n";

        if ( $extra_prompts ) {
            $instructions .= "\n" . __( 'Istruzioni aggiuntive:', 'bookcreator' ) . "\n" . implode( "\n\n", $extra_prompts ) . "\n";
        }

        if ( $total_chunks > 1 ) {
            /* translators: 1: current chunk number, 2: total chunk number. */
            $instructions .= sprintf( __( 'Parte %1$d di %2$d del contenuto del libro.', 'bookcreator' ), $chunk_position + 1, $total_chunks ) . "\n";
        }

        $full_prompt = $instructions . "\n" . $chunk_text;

        $response = $send_claude_request( $model, $full_prompt, $request_timeout, $api_key, $requested_max_tokens );

        if ( is_wp_error( $response ) ) {
            bookcreator_delete_directory( $extract_dir );
            return new WP_Error( 'bookcreator_translation_request', sprintf( __( 'Errore durante la chiamata a Claude: %s', 'bookcreator' ), $response->get_error_message() ) );
        }

        $status_code = (int) wp_remote_retrieve_response_code( $response );
        $body_raw    = wp_remote_retrieve_body( $response );

        if ( 200 !== $status_code ) {
            $body_data   = json_decode( $body_raw, true );
            $error_type  = '';
            if ( is_array( $body_data ) ) {
                if ( isset( $body_data['type'] ) && is_string( $body_data['type'] ) ) {
                    $error_type = $body_data['type'];
                } elseif ( isset( $body_data['error']['type'] ) && is_string( $body_data['error']['type'] ) ) {
                    $error_type = $body_data['error']['type'];
                }
            }

            if ( 'not_found_error' === $error_type && $model !== $default_model ) {
                $previous_model = $model;
                $model          = $default_model;

                if ( isset( $model_limits[ $model ] ) ) {
                    $selected_capab     = $model_limits[ $model ];
                    $max_output_tokens  = isset( $selected_capab['max_output_tokens'] ) ? (int) $selected_capab['max_output_tokens'] : $max_output_tokens;
                    $max_output_tokens  = $max_output_tokens > 0 ? $max_output_tokens : 4096;
                    $max_chunk_chars    = isset( $selected_capab['max_chunk_chars'] ) ? (int) $selected_capab['max_chunk_chars'] : ( $max_output_tokens * 4 );
                    $max_chunk_chars    = max( 4000, $max_chunk_chars );
                    $requested_max_tokens = (int) floor( $max_output_tokens * $output_margin );
                    $requested_max_tokens = max( 512, min( $max_output_tokens, $requested_max_tokens ) );
                }

                $response       = $send_claude_request( $model, $full_prompt, $request_timeout, $api_key, $requested_max_tokens );

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

                if ( '' === $model_notice ) {
                    $from_label = isset( $allowed_models[ $previous_model ] ) ? $allowed_models[ $previous_model ] : $previous_model;
                    $to_label   = isset( $allowed_models[ $model ] ) ? $allowed_models[ $model ] : $model;
                    $model_notice = sprintf( __( 'Il modello %1$s non è disponibile. È stato utilizzato %2$s per completare la traduzione.', 'bookcreator' ), $from_label, $to_label );
                }
            } else {
                bookcreator_delete_directory( $extract_dir );
                return new WP_Error( 'bookcreator_translation_response', sprintf( __( 'Claude ha restituito un errore (%d): %s', 'bookcreator' ), $status_code, $body_raw ) );
            }
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

        foreach ( $chunk['segments'] as $segment_index ) {
            if ( ! isset( $segments_flat[ $segment_index ] ) ) {
                continue;
            }

            $segment_meta = $segments_flat[ $segment_index ];
            $pattern      = '/\[' . preg_quote( $segment_meta['marker'] . '_START', '/' ) . '[^\]]*\](.*?)\[' . preg_quote( $segment_meta['marker'] . '_END', '/' ) . '\]/is';

            if ( ! preg_match( $pattern, $text_response, $matches ) ) {
                $excerpt_source = preg_replace( '/\s+/', ' ', trim( wp_strip_all_tags( $segment_meta['original_html'] ) ) );
                if ( '' === $excerpt_source ) {
                    $excerpt_source = trim( $segment_meta['original_html'] );
                }

                if ( function_exists( 'mb_substr' ) && function_exists( 'mb_strlen' ) ) {
                    $excerpt = mb_substr( $excerpt_source, 0, 200 );
                    if ( mb_strlen( $excerpt_source ) > 200 ) {
                        $excerpt .= '…';
                    }
                } else {
                    $excerpt = substr( $excerpt_source, 0, 200 );
                    if ( strlen( $excerpt_source ) > 200 ) {
                        $excerpt .= '…';
                    }
                }

                $translation_warnings[] = array(
                    'marker'  => $segment_meta['marker'],
                    'excerpt' => $excerpt,
                );

                $fallback_prompt  = __( 'Traduci il segmento indicato mantenendo la struttura HTML.', 'bookcreator' ) . "\n";
                $fallback_prompt .= sprintf( __( 'Lingua di destinazione: %s', 'bookcreator' ), $target_language_sanitized ) . "\n";

                if ( $extra_prompts ) {
                    $fallback_prompt .= "\n" . __( 'Istruzioni aggiuntive:', 'bookcreator' ) . "\n" . implode( "\n\n", $extra_prompts ) . "\n";
                }

                $fallback_prompt .= "\n";
                $fallback_prompt .= '[' . $segment_meta['marker'] . '_START]' . "\n";
                $fallback_prompt .= trim( $segment_meta['original_html'] ) . "\n";
                $fallback_prompt .= '[' . $segment_meta['marker'] . '_END]';

                $fallback_timeout  = max( 5, min( 20, $request_timeout ) );
                $fallback_response = $send_claude_request( $model, $fallback_prompt, $fallback_timeout, $api_key, $requested_max_tokens );

                $fallback_text_response = '';

                if ( ! is_wp_error( $fallback_response ) ) {
                    $fallback_status_code = (int) wp_remote_retrieve_response_code( $fallback_response );

                    if ( 200 === $fallback_status_code ) {
                        $fallback_body_raw  = wp_remote_retrieve_body( $fallback_response );
                        $fallback_body_data = json_decode( $fallback_body_raw, true );

                        if ( is_array( $fallback_body_data ) && ! empty( $fallback_body_data['content'] ) && is_array( $fallback_body_data['content'] ) ) {
                            foreach ( $fallback_body_data['content'] as $fallback_segment ) {
                                if ( isset( $fallback_segment['type'] ) && 'text' === $fallback_segment['type'] && isset( $fallback_segment['text'] ) ) {
                                    $fallback_text_response .= $fallback_segment['text'];
                                }
                            }
                        }
                    }
                }

                $fallback_text_response = trim( $fallback_text_response );

                if ( '' !== $fallback_text_response ) {
                    if ( preg_match( $pattern, $fallback_text_response, $fallback_matches ) ) {
                        $segment_translations[ $segment_index ] = trim( $fallback_matches[1] );
                        continue;
                    }

                    $manual_wrapped = '[' . $segment_meta['marker'] . '_START]' . "\n" . $fallback_text_response . "\n" . '[' . $segment_meta['marker'] . '_END]';

                    if ( preg_match( $pattern, $manual_wrapped, $manual_matches ) ) {
                        $segment_translations[ $segment_index ] = trim( $manual_matches[1] );
                        continue;
                    }
                }

                bookcreator_delete_directory( $extract_dir );
                $message = sprintf( __( 'La risposta di Claude non contiene il marcatore %s.', 'bookcreator' ), $segment_meta['marker'] );
                $message = $append_translation_warnings( $message, $translation_warnings );
                return new WP_Error( 'bookcreator_translation_missing_section', $message );
            }

            $segment_translations[ $segment_index ] = trim( $matches[1] );
        }
    }

    foreach ( $segments_flat as $segment_index => $segment_meta ) {
        if ( ! isset( $segment_translations[ $segment_index ] ) ) {
            bookcreator_delete_directory( $extract_dir );
            $message = __( 'Alcuni segmenti non sono stati tradotti.', 'bookcreator' );
            $message = $append_translation_warnings( $message, $translation_warnings );
            return new WP_Error( 'bookcreator_translation_missing_section', $message );
        }
    }

    $section_translations = array();

    foreach ( $sections as $section_index => $section ) {
        $translated_chunks = array();

        foreach ( $section['segments'] as $segment ) {
            if ( ! empty( $segment['translatable'] ) ) {
                $global_index = isset( $segment['global_index'] ) ? (int) $segment['global_index'] : null;

                if ( null === $global_index || ! isset( $segment_translations[ $global_index ] ) ) {
                    bookcreator_delete_directory( $extract_dir );
                    $message = __( 'Alcuni segmenti non sono stati tradotti.', 'bookcreator' );
                    $message = $append_translation_warnings( $message, $translation_warnings );
                    return new WP_Error( 'bookcreator_translation_missing_section', $message );
                }

                $translated_chunks[] = $segment_translations[ $global_index ];
            } else {
                $translated_chunks[] = $segment['html'];
            }
        }

        $section_translations[ $section_index ] = implode( '', $translated_chunks );
    }

    foreach ( $sections as $section_index => $section ) {
        $translated_body = isset( $section_translations[ $section_index ] ) ? $section_translations[ $section_index ] : '';

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
        'model_notice' => $model_notice,
        'warnings'  => $translation_warnings,
    );
}

function bookcreator_delete_translated_epub( $book_id, $language ) {
    $book_id  = (int) $book_id;
    $language = bookcreator_sanitize_translation_language( $language );

    if ( ! $book_id || '' === $language ) {
        return new WP_Error( 'bookcreator_translation_invalid', __( 'Traduzione non valida.', 'bookcreator' ) );
    }

    $translations_meta = get_post_meta( $book_id, 'bc_translated_epubs', true );
    if ( ! is_array( $translations_meta ) ) {
        $translations_meta = array();
    }

    $entry_to_delete = null;
    $remaining       = array();

    foreach ( $translations_meta as $entry ) {
        if ( ! is_array( $entry ) ) {
            $remaining[] = $entry;
            continue;
        }

        $entry_language = isset( $entry['language'] ) ? bookcreator_sanitize_translation_language( $entry['language'] ) : '';
        if ( $entry_language && $entry_language === $language && ! $entry_to_delete ) {
            $entry_to_delete = $entry;
            continue;
        }

        $remaining[] = $entry;
    }

    if ( ! $entry_to_delete ) {
        return new WP_Error( 'bookcreator_translation_missing', __( 'Traduzione non trovata.', 'bookcreator' ) );
    }

    $file_name = isset( $entry_to_delete['file'] ) ? sanitize_file_name( $entry_to_delete['file'] ) : '';
    if ( $file_name ) {
        $upload_dir = wp_upload_dir();
        if ( empty( $upload_dir['error'] ) ) {
            $file_path = trailingslashit( $upload_dir['basedir'] ) . 'bookcreator-epubs/' . $file_name;
            if ( file_exists( $file_path ) && ! @unlink( $file_path ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                return new WP_Error( 'bookcreator_translation_delete_file', __( 'Impossibile eliminare il file della traduzione.', 'bookcreator' ) );
            }
        }
    }

    if ( $remaining ) {
        update_post_meta( $book_id, 'bc_translated_epubs', array_values( $remaining ) );
    } else {
        delete_post_meta( $book_id, 'bc_translated_epubs' );
    }

    return true;
}



function bookcreator_handle_generate_exports_action() {
    $is_epub = isset( $_POST['bookcreator_generate_epub'] );
    $is_pdf  = isset( $_POST['bookcreator_generate_pdf'] );

    if ( ! $is_epub && ! $is_pdf ) {
        return;
    }

    if ( ! current_user_can( 'bookcreator_generate_exports' ) ) {
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

    if ( ! current_user_can( 'edit_post', $book_id ) ) {
        return;
    }

    $templates = bookcreator_get_templates();
    $field     = $is_epub ? 'book_template_epub' : 'book_template_pdf';
    $expected  = $is_epub ? 'epub' : 'pdf';
    $meta_key  = $is_epub ? 'bc_last_template_epub' : 'bc_last_template_pdf';

    $template_id = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';
    if ( $template_id && ( ! isset( $templates[ $template_id ] ) || $templates[ $template_id ]['type'] !== $expected || ! bookcreator_current_user_can_access_template( $templates[ $template_id ] ) ) ) {
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
        $context     = 'epub';
        $base_result = bookcreator_create_epub_from_book( $book_id, $template_id );

        if ( is_wp_error( $base_result ) ) {
            $status  = 'error';
            $message = $base_result->get_error_message();
        } else {
            $results = array( $base_result );
            $errors  = array();

            $translations = bookcreator_get_translations_for_post( $book_id, 'book_creator' );
            if ( $translations ) {
                foreach ( $translations as $language => $translation_entry ) {
                    $translation_result = bookcreator_create_epub_from_book( $book_id, $template_id, $language );
                    if ( is_wp_error( $translation_result ) ) {
                        $errors[] = array(
                            'language' => $language,
                            'message'  => $translation_result->get_error_message(),
                        );
                    } else {
                        $results[] = $translation_result;
                    }
                }
            }

            $success_parts = array();
            foreach ( $results as $index => $entry ) {
                $entry_language = isset( $entry['language'] ) ? $entry['language'] : '';
                $label          = $entry_language ? bookcreator_get_language_label( $entry_language ) : '';

                if ( ! $label && $entry_language ) {
                    $label = strtoupper( $entry_language );
                }

                if ( 0 === $index ) {
                    if ( $label ) {
                        $label = sprintf( __( '%s (originale)', 'bookcreator' ), $label );
                    } else {
                        $label = __( 'Lingua originale', 'bookcreator' );
                    }
                } elseif ( ! $label && $entry_language ) {
                    $label = $entry_language;
                }

                $file = isset( $entry['file'] ) ? $entry['file'] : '';
                $success_parts[] = trim( $label . ': ' . $file );
            }

            if ( $errors ) {
                $error_parts = array();
                foreach ( $errors as $error_entry ) {
                    $entry_language = isset( $error_entry['language'] ) ? $error_entry['language'] : '';
                    $label          = $entry_language ? bookcreator_get_language_label( $entry_language ) : '';
                    if ( ! $label && $entry_language ) {
                        $label = strtoupper( $entry_language );
                    }
                    $error_parts[] = trim( ( $label ? $label : $entry_language ) . ': ' . $error_entry['message'] );
                }

                $status = 'error';
                if ( $success_parts ) {
                    $message = sprintf(
                        __( 'Alcuni ePub non sono stati creati. ePub disponibili: %1$s. Errori: %2$s', 'bookcreator' ),
                        implode( '; ', $success_parts ),
                        implode( '; ', $error_parts )
                    );
                } else {
                    $message = sprintf(
                        __( 'Errore durante la creazione degli ePub: %s', 'bookcreator' ),
                        implode( '; ', $error_parts )
                    );
                }
            } else {
                $status  = 'success';
                $message = sprintf(
                    __( 'ePub creati correttamente: %s', 'bookcreator' ),
                    implode( '; ', $success_parts )
                );
            }
        }
    } else {
        $context     = 'pdf';
        $base_result = bookcreator_generate_pdf_from_book( $book_id, $template_id );

        if ( is_wp_error( $base_result ) ) {
            $status  = 'error';
            $message = $base_result->get_error_message();
        } else {
            $results = array( $base_result );
            $errors  = array();

            $translations = bookcreator_get_translations_for_post( $book_id, 'book_creator' );
            if ( $translations ) {
                foreach ( $translations as $language => $translation_entry ) {
                    $translation_result = bookcreator_generate_pdf_from_book( $book_id, $template_id, $language );
                    if ( is_wp_error( $translation_result ) ) {
                        $errors[] = array(
                            'language' => $language,
                            'message'  => $translation_result->get_error_message(),
                        );
                    } else {
                        $results[] = $translation_result;
                    }
                }
            }

            $success_parts = array();
            foreach ( $results as $index => $entry ) {
                $entry_language = isset( $entry['language'] ) ? $entry['language'] : '';
                $label          = $entry_language ? bookcreator_get_language_label( $entry_language ) : '';

                if ( ! $label && $entry_language ) {
                    $label = strtoupper( $entry_language );
                }

                if ( 0 === $index ) {
                    if ( $label ) {
                        $label = sprintf( __( '%s (originale)', 'bookcreator' ), $label );
                    } else {
                        $label = __( 'Lingua originale', 'bookcreator' );
                    }
                } elseif ( ! $label && $entry_language ) {
                    $label = $entry_language;
                }

                $file = isset( $entry['file'] ) ? $entry['file'] : '';
                $success_parts[] = trim( $label . ': ' . $file );
            }

            if ( $errors ) {
                $error_parts = array();
                foreach ( $errors as $error_entry ) {
                    $entry_language = isset( $error_entry['language'] ) ? $error_entry['language'] : '';
                    $label          = $entry_language ? bookcreator_get_language_label( $entry_language ) : '';
                    if ( ! $label && $entry_language ) {
                        $label = strtoupper( $entry_language );
                    }
                    $error_parts[] = trim( ( $label ? $label : $entry_language ) . ': ' . $error_entry['message'] );
                }

                $status = 'error';
                if ( $success_parts ) {
                    $message = sprintf(
                        __( 'Alcuni PDF non sono stati creati. PDF disponibili: %1$s. Errori: %2$s', 'bookcreator' ),
                        implode( '; ', $success_parts ),
                        implode( '; ', $error_parts )
                    );
                } else {
                    $message = sprintf(
                        __( 'Errore durante la creazione dei PDF: %s', 'bookcreator' ),
                        implode( '; ', $error_parts )
                    );
                }
            } else {
                $status  = 'success';
                $message = sprintf(
                    __( 'PDF creati correttamente: %s', 'bookcreator' ),
                    implode( '; ', $success_parts )
                );
            }
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
    if ( ! current_user_can( 'bookcreator_generate_exports' ) ) {
        wp_die( esc_html__( 'Non hai i permessi per accedere a questa pagina.', 'bookcreator' ) );
    }

    $epub_library_available = bookcreator_is_epub_library_available();
    $pdf_library_available  = bookcreator_is_pdf_library_available();

    $book_query_args = array(
        'post_type'   => 'book_creator',
        'numberposts' => -1,
        'post_status' => array( 'publish', 'draft', 'private' ),
    );

    if ( ! current_user_can( 'edit_others_bookcreator_books' ) ) {
        $book_query_args['author'] = get_current_user_id();
    }

    $books = get_posts( $book_query_args );

    if ( $books ) {
        $books = array_values(
            array_filter(
                $books,
                static function ( $book ) {
                    return current_user_can( 'edit_post', $book->ID );
                }
            )
        );
    }

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

        $translated_epubs = get_post_meta( $book->ID, 'bc_translated_epubs', true );
        if ( is_array( $translated_epubs ) && $translated_epubs ) {
            $translation_items = array();

            foreach ( $translated_epubs as $translation_entry ) {
                if ( ! is_array( $translation_entry ) || empty( $translation_entry['file'] ) ) {
                    continue;
                }

                $translation_file = $translation_entry['file'];
                $translation_lang = isset( $translation_entry['language'] ) ? $translation_entry['language'] : '';
                $translation_label = $translation_lang ? bookcreator_get_language_label( $translation_lang ) : '';
                if ( ! $translation_label && $translation_lang ) {
                    $translation_label = strtoupper( $translation_lang );
                }

                $translation_path = $epub_base_dir . $translation_file;

                if ( file_exists( $translation_path ) ) {
                    $translation_url = $epub_base_url . $translation_file;
                    $link            = '<a href="' . esc_url( $translation_url ) . '" target="_blank" rel="noopener">' . esc_html( $translation_file ) . '</a>';
                } else {
                    $link = esc_html__( 'File mancante', 'bookcreator' );
                }

                $label = $translation_label ? $translation_label : $translation_lang;
                $translation_items[] = '<li>' . esc_html( $label ) . ': ' . $link . '</li>';
            }

            if ( $translation_items ) {
                if ( '—' === $epub_file_cell ) {
                    $epub_file_cell = '';
                }
                $epub_file_cell .= '<div class="bookcreator-translation-epubs"><strong>' . esc_html__( 'Traduzioni', 'bookcreator' ) . ':</strong><ul>' . implode( '', $translation_items ) . '</ul></div>';
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

        $translated_pdfs = get_post_meta( $book->ID, 'bc_translated_pdfs', true );
        if ( is_array( $translated_pdfs ) && $translated_pdfs ) {
            $pdf_translation_items = array();

            foreach ( $translated_pdfs as $translation_entry ) {
                if ( ! is_array( $translation_entry ) || empty( $translation_entry['file'] ) ) {
                    continue;
                }

                $translation_file = $translation_entry['file'];
                $translation_lang = isset( $translation_entry['language'] ) ? $translation_entry['language'] : '';
                $translation_label = $translation_lang ? bookcreator_get_language_label( $translation_lang ) : '';
                if ( ! $translation_label && $translation_lang ) {
                    $translation_label = strtoupper( $translation_lang );
                }

                $translation_path = $pdf_base_dir . $translation_file;

                if ( file_exists( $translation_path ) ) {
                    $translation_url = $pdf_base_url . $translation_file;
                    $link            = '<a href="' . esc_url( $translation_url ) . '" target="_blank" rel="noopener">' . esc_html( $translation_file ) . '</a>';
                } else {
                    $link = esc_html__( 'File mancante', 'bookcreator' );
                }

                $label = $translation_label ? $translation_label : $translation_lang;
                $pdf_translation_items[] = '<li>' . esc_html( $label ) . ': ' . $link . '</li>';
            }

            if ( $pdf_translation_items ) {
                if ( '—' === $pdf_file_cell ) {
                    $pdf_file_cell = '';
                }
                $pdf_file_cell .= '<div class="bookcreator-translation-pdfs"><strong>' . esc_html__( 'Traduzioni', 'bookcreator' ) . ':</strong><ul>' . implode( '', $pdf_translation_items ) . '</ul></div>';
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
        'bookcreator_generate_exports',
        'bc-generate-epub',
        'bookcreator_generate_exports_page'
    );
}
add_action( 'admin_menu', 'bookcreator_register_generate_exports_page' );

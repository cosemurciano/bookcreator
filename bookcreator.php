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

function bookcreator_meta_box_descriptive( $post ) {
    $languages = array(
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
    $language = get_post_meta( $post->ID, 'bc_language', true );
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
    $retina_id      = get_post_meta( $post->ID, 'bc_retina_cover', true );
    ?>
    <p><label for="bc_cover"><?php esc_html_e( 'Copertina', 'bookcreator' ); ?></label><br/>
    <input type="file" name="bc_cover" id="bc_cover" /><br/>
    <?php if ( $cover_id ) { echo wp_get_attachment_image( $cover_id, array( 100, 100 ) ); } ?></p>

    <p><label for="bc_retina_cover"><?php esc_html_e( 'Copertina Retina Display', 'bookcreator' ); ?></label><br/>
    <input type="file" name="bc_retina_cover" id="bc_retina_cover" /><br/>
    <?php if ( $retina_id ) { echo wp_get_attachment_image( $retina_id, array( 100, 100 ) ); } ?></p>

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

    if ( ! empty( $_FILES['bc_retina_cover']['name'] ) ) {
        $retina_cover_id = media_handle_upload( 'bc_retina_cover', $post_id );
        if ( ! is_wp_error( $retina_cover_id ) ) {
            update_post_meta( $post_id, 'bc_retina_cover', $retina_cover_id );
        }
    }

}
add_action( 'save_post_book_creator', 'bookcreator_save_meta' );

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
        'bc_retina_cover'     => __( 'Retina Cover', 'bookcreator' ),
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
        $languages = array(
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
        $code = get_post_meta( $post_id, 'bc_language', true );
        echo isset( $languages[ $code ] ) ? esc_html( $languages[ $code ] ) : '—';
    }

    if ( 'bc_cover' === $column ) {
        $cover_id = get_post_meta( $post_id, 'bc_cover', true );
        if ( $cover_id ) {
            echo wp_get_attachment_image( $cover_id, array( 50, 50 ) );
        } else {
            echo '—';
        }
    }

    if ( 'bc_retina_cover' === $column ) {
        $retina_id = get_post_meta( $post_id, 'bc_retina_cover', true );
        if ( $retina_id ) {
            echo wp_get_attachment_image( $retina_id, array( 50, 50 ) );
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
        'merriweather'    => array(
            'label'  => __( 'Merriweather (Google Fonts)', 'bookcreator' ),
            'css'    => "'Merriweather', serif",
            'import' => "@import url('https://fonts.googleapis.com/css2?family=Merriweather:wght@300;400;700&display=swap');",
        ),
        'crimson_text'    => array(
            'label'  => __( 'Crimson Text (Google Fonts)', 'bookcreator' ),
            'css'    => "'Crimson Text', serif",
            'import' => "@import url('https://fonts.googleapis.com/css2?family=Crimson+Text:wght@400;600&display=swap');",
        ),
        'lora'            => array(
            'label'  => __( 'Lora (Google Fonts)', 'bookcreator' ),
            'css'    => "'Lora', serif",
            'import' => "@import url('https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600&display=swap');",
        ),
        'eb_garamond'     => array(
            'label'  => __( 'EB Garamond (Google Fonts)', 'bookcreator' ),
            'css'    => "'EB Garamond', serif",
            'import' => "@import url('https://fonts.googleapis.com/css2?family=EB+Garamond:wght@400;500&display=swap');",
        ),
        'inter'           => array(
            'label'  => __( 'Inter (Google Fonts)', 'bookcreator' ),
            'css'    => "'Inter', sans-serif",
            'import' => "@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');",
        ),
        'source_sans_pro' => array(
            'label'  => __( 'Source Sans Pro (Google Fonts)', 'bookcreator' ),
            'css'    => "'Source Sans Pro', sans-serif",
            'import' => "@import url('https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@400;600;700&display=swap');",
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

function bookcreator_get_epub_book_title_style_defaults() {
    return array(
        'font_size'     => '2.4',
        'line_height'   => '1.2',
        'font_family'   => 'georgia',
        'font_style'    => 'normal',
        'font_weight'   => '700',
        'color'         => '#333333',
        'background_color' => '',
        'text_align'    => 'center',
        'margin_top'    => '0',
        'margin_right'  => '0',
        'margin_bottom' => '0.2',
        'margin_left'   => '0',
        'padding_top'   => '0',
        'padding_right' => '0',
        'padding_bottom' => '0',
        'padding_left'  => '0',
        'margin'        => '0 0 0.2 0',
        'padding'       => '0 0 0 0',
    );
}

function bookcreator_get_epub_style_fields() {
    return array(
        'book_title' => array(
            'label'     => __( 'Titolo del libro', 'bookcreator' ),
            'selectors' => array( '.bookcreator-frontispiece__title' ),
        ),
        'book_subtitle' => array(
            'label'     => __( 'Sottotitolo del libro', 'bookcreator' ),
            'selectors' => array( '.bookcreator-frontispiece__subtitle' ),
        ),
        'book_author' => array(
            'label'     => __( 'Autore principale', 'bookcreator' ),
            'selectors' => array( '.bookcreator-frontispiece__field-bc_author' ),
        ),
        'book_coauthors' => array(
            'label'     => __( 'Coautori', 'bookcreator' ),
            'selectors' => array( '.bookcreator-frontispiece__field-bc_coauthors' ),
        ),
        'book_publisher' => array(
            'label'     => __( 'Editore', 'bookcreator' ),
            'selectors' => array( '.bookcreator-frontispiece__field-bc_publisher' ),
        ),
        'book_frontispiece' => array(
            'label'     => __( 'Frontespizio', 'bookcreator' ),
            'selectors' => array( '.bookcreator-frontispiece' ),
        ),
        'book_description' => array(
            'label'     => __( 'Descrizione', 'bookcreator' ),
            'selectors' => array( '.bookcreator-frontispiece__description' ),
        ),
        'book_frontispiece_extra' => array(
            'label'     => __( 'Contenuti extra frontespizio', 'bookcreator' ),
            'selectors' => array( '.bookcreator-frontispiece__extra' ),
        ),
        'book_cover' => array(
            'label'     => __( 'Copertina', 'bookcreator' ),
            'selectors' => array( '.bookcreator-cover' ),
        ),
        'book_copyright' => array(
            'label'     => __( 'Sezione Copyright', 'bookcreator' ),
            'selectors' => array( '.bookcreator-copyright' ),
        ),
        'book_dedication' => array(
            'label'     => __( 'Sezione Dedica', 'bookcreator' ),
            'selectors' => array( '.bookcreator-dedication' ),
        ),
        'book_preface' => array(
            'label'     => __( 'Sezione Prefazione', 'bookcreator' ),
            'selectors' => array( '.bookcreator-preface' ),
        ),
        'book_appendix' => array(
            'label'     => __( 'Sezione Appendice', 'bookcreator' ),
            'selectors' => array( '.bookcreator-section-bc_appendix' ),
        ),
        'book_bibliography' => array(
            'label'     => __( 'Sezione Bibliografia', 'bookcreator' ),
            'selectors' => array( '.bookcreator-section-bc_bibliography' ),
        ),
        'book_author_note' => array(
            'label'     => __( 'Sezione Nota dell\'autore', 'bookcreator' ),
            'selectors' => array( '.bookcreator-section-bc_author_note' ),
        ),
        'chapter_sections' => array(
            'label'     => __( 'Capitoli', 'bookcreator' ),
            'selectors' => array( '.bookcreator-chapter' ),
        ),
        'chapter_titles' => array(
            'label'     => __( 'Titoli dei capitoli', 'bookcreator' ),
            'selectors' => array( '.bookcreator-chapter__title' ),
        ),
        'chapter_content' => array(
            'label'     => __( 'Contenuto dei capitoli', 'bookcreator' ),
            'selectors' => array( '.bookcreator-chapter__content' ),
        ),
        'paragraph_sections' => array(
            'label'     => __( 'Paragrafi', 'bookcreator' ),
            'selectors' => array( '.bookcreator-paragraph' ),
        ),
        'paragraph_titles' => array(
            'label'     => __( 'Titoli dei paragrafi', 'bookcreator' ),
            'selectors' => array( '.bookcreator-paragraph__title' ),
        ),
        'paragraph_content' => array(
            'label'     => __( 'Contenuto dei paragrafi', 'bookcreator' ),
            'selectors' => array( '.bookcreator-paragraph__content' ),
        ),
        'paragraph_footnotes' => array(
            'label'     => __( 'Note a piè di pagina', 'bookcreator' ),
            'selectors' => array( '.bookcreator-footnotes' ),
        ),
        'paragraph_citations' => array(
            'label'     => __( 'Citazioni', 'bookcreator' ),
            'selectors' => array( '.bookcreator-citations' ),
        ),
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
            'settings' => array(
                'title_color'    => array(
                    'default' => '#333333',
                ),
                'book_title_styles' => array(
                    'default' => bookcreator_get_epub_book_title_style_defaults(),
                ),
                'visible_fields' => array(
                    'default' => bookcreator_get_epub_default_visible_fields(),
                ),
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

        switch ( $key ) {
            case 'title_color':
                $value = sanitize_hex_color( $value );
                if ( ! $value ) {
                    $value = $args['default'];
                }
                break;
            case 'book_title_styles':
                $defaults = bookcreator_get_epub_book_title_style_defaults();
                $value    = is_array( $value ) ? $value : array();
                $value    = wp_parse_args( $value, $defaults );

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
                $book_title_color = isset( $_POST['bookcreator_template_epub_book_title_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['bookcreator_template_epub_book_title_color'] ) ) : '';
                if ( ! $book_title_color && isset( $_POST['bookcreator_template_title_color'] ) ) {
                    $book_title_color = sanitize_hex_color( wp_unslash( $_POST['bookcreator_template_title_color'] ) );
                }

                $margin_top    = isset( $_POST['bookcreator_template_epub_book_title_margin_top'] ) ? bookcreator_sanitize_numeric_value( wp_unslash( $_POST['bookcreator_template_epub_book_title_margin_top'] ) ) : '';
                $margin_right  = isset( $_POST['bookcreator_template_epub_book_title_margin_right'] ) ? bookcreator_sanitize_numeric_value( wp_unslash( $_POST['bookcreator_template_epub_book_title_margin_right'] ) ) : '';
                $margin_bottom = isset( $_POST['bookcreator_template_epub_book_title_margin_bottom'] ) ? bookcreator_sanitize_numeric_value( wp_unslash( $_POST['bookcreator_template_epub_book_title_margin_bottom'] ) ) : '';
                $margin_left   = isset( $_POST['bookcreator_template_epub_book_title_margin_left'] ) ? bookcreator_sanitize_numeric_value( wp_unslash( $_POST['bookcreator_template_epub_book_title_margin_left'] ) ) : '';

                $padding_top    = isset( $_POST['bookcreator_template_epub_book_title_padding_top'] ) ? bookcreator_sanitize_numeric_value( wp_unslash( $_POST['bookcreator_template_epub_book_title_padding_top'] ) ) : '';
                $padding_right  = isset( $_POST['bookcreator_template_epub_book_title_padding_right'] ) ? bookcreator_sanitize_numeric_value( wp_unslash( $_POST['bookcreator_template_epub_book_title_padding_right'] ) ) : '';
                $padding_bottom = isset( $_POST['bookcreator_template_epub_book_title_padding_bottom'] ) ? bookcreator_sanitize_numeric_value( wp_unslash( $_POST['bookcreator_template_epub_book_title_padding_bottom'] ) ) : '';
                $padding_left   = isset( $_POST['bookcreator_template_epub_book_title_padding_left'] ) ? bookcreator_sanitize_numeric_value( wp_unslash( $_POST['bookcreator_template_epub_book_title_padding_left'] ) ) : '';

                $font_size   = isset( $_POST['bookcreator_template_epub_book_title_font_size'] ) ? bookcreator_sanitize_numeric_value( wp_unslash( $_POST['bookcreator_template_epub_book_title_font_size'] ) ) : '';
                $line_height = isset( $_POST['bookcreator_template_epub_book_title_line_height'] ) ? bookcreator_sanitize_numeric_value( wp_unslash( $_POST['bookcreator_template_epub_book_title_line_height'] ) ) : '';
                $background_color = isset( $_POST['bookcreator_template_epub_book_title_background_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['bookcreator_template_epub_book_title_background_color'] ) ) : '';

                $settings['book_title_styles'] = array(
                    'font_size'     => $font_size,
                    'line_height'   => $line_height,
                    'font_family'   => isset( $_POST['bookcreator_template_epub_book_title_font_family'] ) ? sanitize_key( wp_unslash( $_POST['bookcreator_template_epub_book_title_font_family'] ) ) : '',
                    'font_style'    => isset( $_POST['bookcreator_template_epub_book_title_font_style'] ) ? sanitize_text_field( wp_unslash( $_POST['bookcreator_template_epub_book_title_font_style'] ) ) : '',
                    'font_weight'   => isset( $_POST['bookcreator_template_epub_book_title_font_weight'] ) ? sanitize_text_field( wp_unslash( $_POST['bookcreator_template_epub_book_title_font_weight'] ) ) : '',
                    'color'         => $book_title_color,
                    'background_color' => $background_color,
                    'text_align'    => isset( $_POST['bookcreator_template_epub_book_title_text_align'] ) ? sanitize_text_field( wp_unslash( $_POST['bookcreator_template_epub_book_title_text_align'] ) ) : '',
                    'margin_top'    => $margin_top,
                    'margin_right'  => $margin_right,
                    'margin_bottom' => $margin_bottom,
                    'margin_left'   => $margin_left,
                    'padding_top'   => $padding_top,
                    'padding_right' => $padding_right,
                    'padding_bottom'=> $padding_bottom,
                    'padding_left'  => $padding_left,
                    'margin'        => bookcreator_build_css_box_values( $margin_top, $margin_right, $margin_bottom, $margin_left ),
                    'padding'       => bookcreator_build_css_box_values( $padding_top, $padding_right, $padding_bottom, $padding_left ),
                );
                $settings['title_color'] = $book_title_color;

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
            $book_title_defaults = bookcreator_get_epub_book_title_style_defaults();
            $book_title_styles   = isset( $values['book_title_styles'] ) ? (array) $values['book_title_styles'] : array();
            $book_title_styles   = wp_parse_args( $book_title_styles, $book_title_defaults );

            if ( empty( $book_title_styles['color'] ) && ! empty( $values['title_color'] ) ) {
                $book_title_styles['color'] = $values['title_color'];
            }

            $font_families = bookcreator_get_epub_font_family_options();
            if ( ! isset( $font_families[ $book_title_styles['font_family'] ] ) ) {
                $book_title_styles['font_family'] = $book_title_defaults['font_family'];
            }

            $font_style_options = array(
                'normal'  => __( 'Normale', 'bookcreator' ),
                'italic'  => __( 'Corsivo', 'bookcreator' ),
                'oblique' => __( 'Obliquo', 'bookcreator' ),
            );

            $font_weight_options = array(
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

            echo '<tr>';
            echo '<th scope="row">' . esc_html__( 'Stile del titolo del libro', 'bookcreator' ) . '</th>';
            echo '<td>';
            echo '<p class="description">' . esc_html__( 'Definisci lo stile del titolo frontespizio nel file ePub generato.', 'bookcreator' ) . '</p>';
            $alignment_options = array(
                'left'    => __( 'Sinistra', 'bookcreator' ),
                'center'  => __( 'Centro', 'bookcreator' ),
                'right'   => __( 'Destra', 'bookcreator' ),
                'justify' => __( 'Giustificato', 'bookcreator' ),
            );

            $margin_fields = array(
                'top'    => __( 'Superiore', 'bookcreator' ),
                'right'  => __( 'Destra', 'bookcreator' ),
                'bottom' => __( 'Inferiore', 'bookcreator' ),
                'left'   => __( 'Sinistra', 'bookcreator' ),
            );

            $padding_fields = array(
                'top'    => __( 'Superiore', 'bookcreator' ),
                'right'  => __( 'Destra', 'bookcreator' ),
                'bottom' => __( 'Inferiore', 'bookcreator' ),
                'left'   => __( 'Sinistra', 'bookcreator' ),
            );

            echo '<div class="bookcreator-style-grid bookcreator-style-grid--two-columns">';

            echo '<div class="bookcreator-style-grid__column">';

            echo '<div class="bookcreator-style-grid__item">';
            echo '<label for="bookcreator_template_epub_book_title_font_size">' . esc_html__( 'Dimensione font (rem)', 'bookcreator' ) . '</label>';
            echo '<input type="number" step="0.1" min="0" id="bookcreator_template_epub_book_title_font_size" name="bookcreator_template_epub_book_title_font_size" value="' . esc_attr( $book_title_styles['font_size'] ) . '" placeholder="' . esc_attr__( 'es. 2.4', 'bookcreator' ) . '" inputmode="decimal" />';
            echo '</div>';

            echo '<div class="bookcreator-style-grid__item">';
            echo '<label for="bookcreator_template_epub_book_title_line_height">' . esc_html__( 'Altezza riga (valore)', 'bookcreator' ) . '</label>';
            echo '<input type="number" step="0.1" min="0" id="bookcreator_template_epub_book_title_line_height" name="bookcreator_template_epub_book_title_line_height" value="' . esc_attr( $book_title_styles['line_height'] ) . '" placeholder="' . esc_attr__( 'es. 1.2', 'bookcreator' ) . '" inputmode="decimal" />';
            echo '</div>';

            echo '<div class="bookcreator-style-grid__item">';
            echo '<label for="bookcreator_template_epub_book_title_font_family">' . esc_html__( 'Famiglia font', 'bookcreator' ) . '</label>';
            echo '<select id="bookcreator_template_epub_book_title_font_family" name="bookcreator_template_epub_book_title_font_family">';
            foreach ( $font_families as $family_key => $family ) {
                $selected = selected( $book_title_styles['font_family'], $family_key, false );
                echo '<option value="' . esc_attr( $family_key ) . '"' . $selected . '>' . esc_html( $family['label'] ) . '</option>';
            }
            echo '</select>';
            echo '</div>';

            echo '<div class="bookcreator-style-grid__item">';
            echo '<label for="bookcreator_template_epub_book_title_font_style">' . esc_html__( 'Stile font', 'bookcreator' ) . '</label>';
            echo '<select id="bookcreator_template_epub_book_title_font_style" name="bookcreator_template_epub_book_title_font_style">';
            foreach ( $font_style_options as $style_key => $style_label ) {
                $selected = selected( $book_title_styles['font_style'], $style_key, false );
                echo '<option value="' . esc_attr( $style_key ) . '"' . $selected . '>' . esc_html( $style_label ) . '</option>';
            }
            echo '</select>';
            echo '</div>';

            echo '<div class="bookcreator-style-grid__item">';
            echo '<label for="bookcreator_template_epub_book_title_font_weight">' . esc_html__( 'Peso font', 'bookcreator' ) . '</label>';
            echo '<select id="bookcreator_template_epub_book_title_font_weight" name="bookcreator_template_epub_book_title_font_weight">';
            foreach ( $font_weight_options as $weight_key => $weight_label ) {
                $selected = selected( $book_title_styles['font_weight'], $weight_key, false );
                echo '<option value="' . esc_attr( $weight_key ) . '"' . $selected . '>' . esc_html( $weight_label ) . '</option>';
            }
            echo '</select>';
            echo '</div>';

            echo '</div>';

            echo '<div class="bookcreator-style-grid__column">';

            echo '<div class="bookcreator-style-grid__item">';
            echo '<label for="bookcreator_template_epub_book_title_text_align">' . esc_html__( 'Allineamento', 'bookcreator' ) . '</label>';
            echo '<select id="bookcreator_template_epub_book_title_text_align" name="bookcreator_template_epub_book_title_text_align">';
            foreach ( $alignment_options as $align_key => $align_label ) {
                $selected = selected( $book_title_styles['text_align'], $align_key, false );
                echo '<option value="' . esc_attr( $align_key ) . '"' . $selected . '>' . esc_html( $align_label ) . '</option>';
            }
            echo '</select>';
            echo '</div>';

            echo '<div class="bookcreator-style-grid__item">';
            echo '<label for="bookcreator_template_epub_book_title_color">' . esc_html__( 'Colore', 'bookcreator' ) . '</label>';
            echo '<input type="text" id="bookcreator_template_epub_book_title_color" name="bookcreator_template_epub_book_title_color" class="bookcreator-color-field" value="' . esc_attr( $book_title_styles['color'] ) . '" data-default-color="' . esc_attr( $book_title_defaults['color'] ) . '" />';
            echo '</div>';

            echo '<div class="bookcreator-style-grid__item">';
            echo '<label for="bookcreator_template_epub_book_title_background_color">' . esc_html__( 'Colore sfondo', 'bookcreator' ) . '</label>';
            echo '<input type="text" id="bookcreator_template_epub_book_title_background_color" name="bookcreator_template_epub_book_title_background_color" class="bookcreator-color-field" value="' . esc_attr( $book_title_styles['background_color'] ) . '" data-default-color="' . esc_attr( $book_title_defaults['background_color'] ) . '" />';
            echo '</div>';

            echo '<div class="bookcreator-style-grid__item">';
            echo '<span class="bookcreator-style-grid__group-title">' . esc_html__( 'Margine (em)', 'bookcreator' ) . '</span>';
            echo '<div class="bookcreator-style-split">';
            foreach ( $margin_fields as $direction => $direction_label ) {
                $field_key = 'margin_' . $direction;
                $input_id  = 'bookcreator_template_epub_book_title_' . $field_key;
                echo '<div class="bookcreator-style-split__field">';
                echo '<label for="' . esc_attr( $input_id ) . '">' . esc_html( sprintf( '%s (em)', $direction_label ) ) . '</label>';
                echo '<input type="number" step="0.1" id="' . esc_attr( $input_id ) . '" name="' . esc_attr( $input_id ) . '" value="' . esc_attr( $book_title_styles[ $field_key ] ) . '" placeholder="' . esc_attr__( 'es. 0.2', 'bookcreator' ) . '" inputmode="decimal" />';
                echo '</div>';
            }
            echo '</div>';
            echo '</div>';

            echo '<div class="bookcreator-style-grid__item">';
            echo '<span class="bookcreator-style-grid__group-title">' . esc_html__( 'Padding (em)', 'bookcreator' ) . '</span>';
            echo '<div class="bookcreator-style-split">';
            foreach ( $padding_fields as $direction => $direction_label ) {
                $field_key = 'padding_' . $direction;
                $input_id  = 'bookcreator_template_epub_book_title_' . $field_key;
                echo '<div class="bookcreator-style-split__field">';
                echo '<label for="' . esc_attr( $input_id ) . '">' . esc_html( sprintf( '%s (em)', $direction_label ) ) . '</label>';
                echo '<input type="number" step="0.1" id="' . esc_attr( $input_id ) . '" name="' . esc_attr( $input_id ) . '" value="' . esc_attr( $book_title_styles[ $field_key ] ) . '" placeholder="' . esc_attr__( 'es. 0.2', 'bookcreator' ) . '" inputmode="decimal" />';
                echo '</div>';
            }
            echo '</div>';
            echo '</div>';

            echo '</div>';

            echo '</div>';
            echo '</td>';
            echo '</tr>';

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

    $book_title_defaults = bookcreator_get_epub_book_title_style_defaults();
    $book_title_styles   = isset( $settings['book_title_styles'] ) ? (array) $settings['book_title_styles'] : array();
    $book_title_styles   = wp_parse_args( $book_title_styles, $book_title_defaults );

    $font_families = bookcreator_get_epub_font_family_options();
    $font_family   = isset( $font_families[ $book_title_styles['font_family'] ] ) ? $font_families[ $book_title_styles['font_family'] ] : $font_families[ $book_title_defaults['font_family'] ];

    $book_title_color = $book_title_styles['color'] ? $book_title_styles['color'] : $title_color;
    if ( ! $book_title_color ) {
        $book_title_color = $book_title_defaults['color'];
    }

    $font_size_value = '' !== $book_title_styles['font_size'] ? $book_title_styles['font_size'] : $book_title_defaults['font_size'];
    $book_title_font_size = bookcreator_format_css_numeric_value( $font_size_value, 'rem' );
    if ( '' === $book_title_font_size ) {
        $book_title_font_size = bookcreator_format_css_numeric_value( $book_title_defaults['font_size'], 'rem' );
    }

    $line_height_value     = '' !== $book_title_styles['line_height'] ? $book_title_styles['line_height'] : $book_title_defaults['line_height'];
    $book_title_line_height = bookcreator_format_css_numeric_value( $line_height_value );
    if ( '' === $book_title_line_height ) {
        $book_title_line_height = bookcreator_format_css_numeric_value( $book_title_defaults['line_height'] );
    }
    $book_title_text_align = '' !== $book_title_styles['text_align'] ? $book_title_styles['text_align'] : $book_title_defaults['text_align'];

    $book_title_background_color = $book_title_styles['background_color'] ? $book_title_styles['background_color'] : $book_title_defaults['background_color'];

    $margin_values = array(
        '' !== $book_title_styles['margin_top'] ? $book_title_styles['margin_top'] : $book_title_defaults['margin_top'],
        '' !== $book_title_styles['margin_right'] ? $book_title_styles['margin_right'] : $book_title_defaults['margin_right'],
        '' !== $book_title_styles['margin_bottom'] ? $book_title_styles['margin_bottom'] : $book_title_defaults['margin_bottom'],
        '' !== $book_title_styles['margin_left'] ? $book_title_styles['margin_left'] : $book_title_defaults['margin_left'],
    );

    $padding_values = array(
        '' !== $book_title_styles['padding_top'] ? $book_title_styles['padding_top'] : $book_title_defaults['padding_top'],
        '' !== $book_title_styles['padding_right'] ? $book_title_styles['padding_right'] : $book_title_defaults['padding_right'],
        '' !== $book_title_styles['padding_bottom'] ? $book_title_styles['padding_bottom'] : $book_title_defaults['padding_bottom'],
        '' !== $book_title_styles['padding_left'] ? $book_title_styles['padding_left'] : $book_title_defaults['padding_left'],
    );

    $book_title_margin  = bookcreator_format_css_box_numeric_values( $margin_values, 'em' );
    $book_title_padding = bookcreator_format_css_box_numeric_values( $padding_values, 'em' );

    $font_imports = array();
    if ( ! empty( $font_family['import'] ) ) {
        $font_imports[] = $font_family['import'];
    }

    foreach ( $style_fields as $field_key => $field ) {
        $is_visible = isset( $visible_fields[ $field_key ] ) ? (bool) $visible_fields[ $field_key ] : true;
        if ( ! $is_visible && ! empty( $field['selectors'] ) ) {
            $hidden_selectors = array_merge( $hidden_selectors, $field['selectors'] );
        }
    }

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
        '.bookcreator-chapter__title {',
        '  margin-top: 1.5em;',
        '  margin-bottom: 0.6em;',
        '}',
        '.bookcreator-chapter__content {',
        '  margin-bottom: 1.5em;',
        '}',
        '.bookcreator-book-title {',
        '  color: ' . $book_title_color . ';',
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
        '.bookcreator-frontispiece {',
        '  text-align: center;',
        '}',
        '.bookcreator-frontispiece__subtitle {',
        '  font-style: italic;',
        '  margin-top: 0;',
        '}',
        '.bookcreator-frontispiece__field {',
        '  margin: 0.3em 0;',
        '}',
        '.bookcreator-frontispiece__description {',
        '  margin-top: 1.5em;',
        '}',
        '.bookcreator-frontispiece__extra {',
        '  margin-top: 1.5em;',
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
        '.bookcreator-footnotes, .bookcreator-citations {',
        '  font-size: 0.9em;',
        '  border-top: 1px solid #cccccc;',
        '  margin-top: 1em;',
        '  padding-top: 0.5em;',
        '}'
    );

    $styles[] = '.bookcreator-frontispiece__title {';
    if ( $book_title_font_size ) {
        $styles[] = '  font-size: ' . $book_title_font_size . ';';
    }
    if ( $book_title_line_height ) {
        $styles[] = '  line-height: ' . $book_title_line_height . ';';
    }
    $styles[] = '  font-family: ' . $font_family['css'] . ';';
    $styles[] = '  font-style: ' . $book_title_styles['font_style'] . ';';
    $styles[] = '  font-weight: ' . $book_title_styles['font_weight'] . ';';
    $styles[] = '  color: ' . $book_title_color . ';';
    if ( $book_title_background_color ) {
        $styles[] = '  background-color: ' . $book_title_background_color . ';';
    }
    $styles[] = '  text-align: ' . $book_title_text_align . ';';
    if ( $book_title_margin ) {
        $styles[] = '  margin: ' . $book_title_margin . ';';
    }
    if ( $book_title_padding ) {
        $styles[] = '  padding: ' . $book_title_padding . ';';
    }
    $styles[] = '}';

    if ( $hidden_selectors ) {
        $hidden_selectors = array_unique( $hidden_selectors );
        $styles[]         = implode( ', ', $hidden_selectors ) . ' {';
        $styles[] = '  display: none !important;';
        $styles[] = '}';
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
    if ( $preface ) {
        $preface_body  = '<div class="bookcreator-preface">';
        $preface_body .= '<h1>' . esc_html__( 'Prefazione', 'bookcreator' ) . '</h1>';
        $preface_body .= bookcreator_prepare_epub_content( $preface );
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

    $chapters_posts = bookcreator_get_ordered_chapters_for_book( $book_id );
    if ( $chapters_posts ) {
        foreach ( $chapters_posts as $index => $chapter ) {
            $chapter_title = get_the_title( $chapter );
            $chapter_slug  = sanitize_title( $chapter->post_name ? $chapter->post_name : $chapter_title );
            if ( ! $chapter_slug ) {
                $chapter_slug = (string) $chapter->ID;
            }

            $file_slug = 'chapter-' . ( $index + 1 ) . '-' . $chapter_slug . '.xhtml';

            $chapter_body            = '<section class="bookcreator-chapter">';
            $chapter_body           .= '<h1 class="bookcreator-chapter__title">' . esc_html( $chapter_title ) . '</h1>';
            $chapter_paragraph_items = array();

            if ( $chapter->post_content ) {
                $chapter_body .= '<div class="bookcreator-chapter__content">';
                $chapter_body .= bookcreator_prepare_epub_content( $chapter->post_content );
                $chapter_body .= '</div>';
            }

            $paragraphs = bookcreator_get_ordered_paragraphs_for_chapter( $chapter->ID );
            if ( $paragraphs ) {
                foreach ( $paragraphs as $paragraph ) {
                    $chapter_body .= '<section class="bookcreator-paragraph" id="paragraph-' . esc_attr( $paragraph->ID ) . '">';
                    $chapter_body .= '<h2 class="bookcreator-paragraph__title">' . esc_html( get_the_title( $paragraph ) ) . '</h2>';

                    $chapter_paragraph_items[] = array(
                        'title'    => get_the_title( $paragraph ),
                        'href'     => $file_slug . '#paragraph-' . $paragraph->ID,
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

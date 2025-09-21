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

function bookcreator_get_epub_styles() {
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
        '.bookcreator-section {',
        '  margin-bottom: 1.5em;',
        '}',
        '.bookcreator-paragraph {',
        '  margin-bottom: 2em;',
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

    return implode( "\n", $styles );
}

function bookcreator_prepare_epub_content( $content ) {
    if ( empty( $content ) ) {
        return '';
    }

    $filtered = apply_filters( 'the_content', $content );
    $filtered = preg_replace( '#\s+$#', '', $filtered );

    return $filtered;
}

function bookcreator_build_epub_document( $title, $body ) {
    $document  = '<?xml version="1.0" encoding="utf-8"?>\n';
    $document .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"\n';
    $document .= '    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">\n';
    $document .= '<html xmlns="http://www.w3.org/1999/xhtml">\n';
    $document .= '<head>\n';
    $document .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />\n';
    $document .= '<title>' . esc_html( $title ) . '</title>\n';
    $document .= '<link rel="stylesheet" type="text/css" href="styles/bookcreator.css" />\n';
    $document .= '</head>\n';
    $document .= '<body>\n';
    $document .= $body . "\n";
    $document .= '</body>\n';
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

function bookcreator_build_nav_document( $book_title, $chapters ) {
    $title   = bookcreator_escape_xml( sprintf( __( 'Indice - %s', 'bookcreator' ), $book_title ) );
    $heading = bookcreator_escape_xml( __( 'Indice', 'bookcreator' ) );

    $items = array();
    foreach ( $chapters as $chapter ) {
        $items[] = '<li><a href="' . bookcreator_escape_xml( $chapter['filename'] ) . '">' . bookcreator_escape_xml( $chapter['title'] ) . '</a></li>';
    }

    $items_html = implode( "
", $items );

    $doc = <<<NAV
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
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

function bookcreator_create_epub_from_book( $book_id ) {
    if ( ! bookcreator_load_epub_library() ) {
        return new WP_Error( 'bookcreator_epub_missing_library', bookcreator_get_epub_library_error_message() );
    }

    $book_post = get_post( $book_id );
    if ( ! $book_post || 'book_creator' !== $book_post->post_type ) {
        return new WP_Error( 'bookcreator_epub_invalid_book', __( 'Libro non valido.', 'bookcreator' ) );
    }

    $title     = get_the_title( $book_post );
    $permalink = get_permalink( $book_post );

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

    if ( false === file_put_contents( $styles_dir . '/bookcreator.css', bookcreator_get_epub_styles() ) ) {
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
                'content'  => bookcreator_build_epub_document( __( 'Copertina', 'bookcreator' ), $cover_body ),
            );
        }
    }

    $body     = '<h1>' . esc_html( $title ) . '</h1>';
    $subtitle = get_post_meta( $book_id, 'bc_subtitle', true );
    if ( $subtitle ) {
        $body .= '<p class="bookcreator-subtitle">' . esc_html( $subtitle ) . '</p>';
    }

    $meta_fields = array(
        'bc_author'      => __( 'Autore principale', 'bookcreator' ),
        'bc_coauthors'   => __( 'Co-autori', 'bookcreator' ),
        'bc_publisher'   => __( 'Editore', 'bookcreator' ),
        'bc_isbn'        => __( 'ISBN', 'bookcreator' ),
        'bc_pub_date'    => __( 'Data di pubblicazione', 'bookcreator' ),
        'bc_edition'     => __( 'Edizione/Versione', 'bookcreator' ),
        'bc_language'    => __( 'Lingua', 'bookcreator' ),
        'bc_keywords'    => __( 'Parole chiave', 'bookcreator' ),
        'bc_audience'    => __( 'Pubblico', 'bookcreator' ),
    );

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

    $body .= '<dl class="bookcreator-meta">';
    foreach ( $meta_fields as $field_key => $label ) {
        $value = get_post_meta( $book_id, $field_key, true );
        if ( ! $value ) {
            continue;
        }

        if ( 'bc_language' === $field_key && isset( $languages[ $value ] ) ) {
            $value = $languages[ $value ];
        }

        if ( 'bc_pub_date' === $field_key ) {
            $value = mysql2date( get_option( 'date_format' ), $value );
        }

        $body .= '<dt>' . esc_html( $label ) . '</dt>';
        $body .= '<dd>' . esc_html( $value ) . '</dd>';
    }

    $genres = get_the_terms( $book_id, 'book_genre' );
    if ( ! empty( $genres ) && ! is_wp_error( $genres ) ) {
        $body .= '<dt>' . esc_html__( 'Generi', 'bookcreator' ) . '</dt>';
        $body .= '<dd>' . esc_html( implode( ', ', wp_list_pluck( $genres, 'name' ) ) ) . '</dd>';
    }
    $body .= '</dl>';

    $rich_text_fields = array(
        'bc_description'  => __( 'Descrizione', 'bookcreator' ),
        'bc_frontispiece' => __( 'Frontespizio', 'bookcreator' ),
        'bc_copyright'    => __( 'Copyright', 'bookcreator' ),
        'bc_dedication'   => __( 'Dedica', 'bookcreator' ),
        'bc_preface'      => __( 'Prefazione', 'bookcreator' ),
        'bc_appendix'     => __( 'Appendice', 'bookcreator' ),
        'bc_bibliography' => __( 'Bibliografia', 'bookcreator' ),
        'bc_author_note'  => __( 'Nota dell\'autore', 'bookcreator' ),
    );

    foreach ( $rich_text_fields as $field_key => $label ) {
        $value = get_post_meta( $book_id, $field_key, true );
        if ( ! $value ) {
            continue;
        }

        $class  = sanitize_html_class( $field_key );
        $body  .= '<section class="bookcreator-section bookcreator-section-' . esc_attr( $class ) . '">';
        $body  .= '<h2>' . esc_html( $label ) . '</h2>';
        $body  .= bookcreator_prepare_epub_content( $value );
        $body  .= '</section>';
    }

    $body = bookcreator_process_epub_images( $body, $assets, $asset_map );

    $chapters[] = array(
        'id'       => 'front-matter',
        'title'    => __( 'Dettagli del libro', 'bookcreator' ),
        'filename' => 'front-matter.xhtml',
        'content'  => bookcreator_build_epub_document( __( 'Dettagli del libro', 'bookcreator' ), $body ),
    );

    $chapters_posts = bookcreator_get_ordered_chapters_for_book( $book_id );
    if ( $chapters_posts ) {
        foreach ( $chapters_posts as $index => $chapter ) {
            $chapter_title = get_the_title( $chapter );
            $chapter_body  = '<h1>' . esc_html( $chapter_title ) . '</h1>';

            if ( $chapter->post_content ) {
                $chapter_body .= bookcreator_prepare_epub_content( $chapter->post_content );
            }

            $paragraphs = bookcreator_get_ordered_paragraphs_for_chapter( $chapter->ID );
            if ( $paragraphs ) {
                foreach ( $paragraphs as $paragraph ) {
                    $chapter_body .= '<section class="bookcreator-paragraph" id="paragraph-' . esc_attr( $paragraph->ID ) . '">';
                    $chapter_body .= '<h2>' . esc_html( get_the_title( $paragraph ) ) . '</h2>';

                    if ( $paragraph->post_content ) {
                        $chapter_body .= bookcreator_prepare_epub_content( $paragraph->post_content );
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

            $chapter_body = bookcreator_process_epub_images( $chapter_body, $assets, $asset_map );

            $chapter_slug = sanitize_title( $chapter->post_name ? $chapter->post_name : $chapter_title );
            if ( ! $chapter_slug ) {
                $chapter_slug = (string) $chapter->ID;
            }

            $file_slug = 'chapter-' . ( $index + 1 ) . '-' . $chapter_slug . '.xhtml';
            $chapters[] = array(
                'id'       => 'chapter-' . ( $index + 1 ),
                'title'    => $chapter_title,
                'filename' => $file_slug,
                'content'  => bookcreator_build_epub_document( $chapter_title, $chapter_body ),
            );
        }
    }

    $nav_document = bookcreator_build_nav_document( $title, $chapters );

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
    foreach ( $chapters as $chapter ) {
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


function bookcreator_handle_generate_epub_action() {
    if ( ! isset( $_POST['bookcreator_generate_epub'], $_POST['bookcreator_generate_epub_nonce'] ) ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    check_admin_referer( 'bookcreator_generate_epub', 'bookcreator_generate_epub_nonce' );

    $book_id = isset( $_POST['book_id'] ) ? absint( wp_unslash( $_POST['book_id'] ) ) : 0;
    if ( ! $book_id ) {
        return;
    }

    $result = bookcreator_create_epub_from_book( $book_id );

    if ( is_wp_error( $result ) ) {
        $status  = 'error';
        $message = $result->get_error_message();
    } else {
        $status  = 'success';
        /* translators: %s: ePub filename. */
        $message = sprintf( __( 'ePub creato correttamente: %s', 'bookcreator' ), $result['file'] );
    }

    $redirect = add_query_arg(
        array(
            'post_type'       => 'book_creator',
            'page'            => 'bc-generate-epub',
            'bc_epub_status'  => $status,
            'bc_epub_message' => rawurlencode( $message ),
        ),
        admin_url( 'edit.php' )
    );

    wp_safe_redirect( $redirect );
    exit;
}
add_action( 'admin_init', 'bookcreator_handle_generate_epub_action' );

function bookcreator_generate_epub_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $library_available = bookcreator_is_epub_library_available();

    $books = get_posts(
        array(
            'post_type'   => 'book_creator',
            'numberposts' => -1,
            'post_status' => array( 'publish', 'draft', 'private' ),
        )
    );

    $upload_dir = wp_upload_dir();
    $base_url   = trailingslashit( $upload_dir['baseurl'] ) . 'bookcreator-epubs/';
    $base_dir   = trailingslashit( $upload_dir['basedir'] ) . 'bookcreator-epubs/';

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__( 'Genera ePub', 'bookcreator' ) . '</h1>';

    if ( ! $library_available ) {
        echo '<div class="notice notice-warning"><p>' . bookcreator_get_epub_library_notice_markup() . '</p></div>';
    }

    if ( isset( $_GET['bc_epub_status'], $_GET['bc_epub_message'] ) ) {
        $status  = sanitize_key( wp_unslash( $_GET['bc_epub_status'] ) );
        $message = sanitize_text_field( rawurldecode( wp_unslash( $_GET['bc_epub_message'] ) ) );
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
    echo '<th scope="col">' . esc_html__( 'Ultima generazione', 'bookcreator' ) . '</th>';
    echo '<th scope="col">' . esc_html__( 'File ePub', 'bookcreator' ) . '</th>';
    echo '<th scope="col" class="column-actions">' . esc_html__( 'Azioni', 'bookcreator' ) . '</th>';
    echo '</tr></thead>';
    echo '<tbody>';

    foreach ( $books as $book ) {
        $meta      = get_post_meta( $book->ID, 'bc_epub_file', true );
        $generated = '—';
        $file_cell = '—';

        if ( is_array( $meta ) && ! empty( $meta['file'] ) ) {
            $file_path = $base_dir . $meta['file'];
            if ( file_exists( $file_path ) ) {
                $file_url  = $base_url . $meta['file'];
                $file_cell = '<a href="' . esc_url( $file_url ) . '" target="_blank" rel="noopener">' . esc_html( $meta['file'] ) . '</a>';
                if ( ! empty( $meta['generated'] ) ) {
                    $generated = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $meta['generated'] ) );
                }
            } else {
                $file_cell = esc_html__( 'File mancante', 'bookcreator' );
            }
        }

        echo '<tr>';
        echo '<td>' . esc_html( get_the_title( $book ) ) . '</td>';
        echo '<td>' . esc_html( $generated ) . '</td>';
        echo '<td>' . $file_cell . '</td>';
        echo '<td>';
        echo '<form method="post">';
        wp_nonce_field( 'bookcreator_generate_epub', 'bookcreator_generate_epub_nonce' );
        echo '<input type="hidden" name="book_id" value="' . esc_attr( $book->ID ) . '" />';
        $button_attrs = $library_available ? '' : array( 'disabled' => 'disabled' );
        submit_button( __( 'Crea ePub', 'bookcreator' ), 'secondary', 'bookcreator_generate_epub', false, $button_attrs );
        echo '</form>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

function bookcreator_register_generate_epub_page() {
    add_submenu_page(
        'edit.php?post_type=book_creator',
        __( 'Genera ePub', 'bookcreator' ),
        __( 'Genera ePub', 'bookcreator' ),
        'manage_options',
        'bc-generate-epub',
        'bookcreator_generate_epub_page'
    );
}
add_action( 'admin_menu', 'bookcreator_register_generate_epub_page' );

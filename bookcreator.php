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

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

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

function bookcreator_register_template_post_type() {
    $labels = array(
        'name'               => __( 'Templates', 'bookcreator' ),
        'singular_name'      => __( 'Template', 'bookcreator' ),
        'menu_name'          => __( 'Templates', 'bookcreator' ),
        'name_admin_bar'     => __( 'Template', 'bookcreator' ),
        'add_new'            => __( 'Add New', 'bookcreator' ),
        'add_new_item'       => __( 'Add New Template', 'bookcreator' ),
        'new_item'           => __( 'New Template', 'bookcreator' ),
        'edit_item'          => __( 'Edit Template', 'bookcreator' ),
        'view_item'          => __( 'View Template', 'bookcreator' ),
        'all_items'          => __( 'All Templates', 'bookcreator' ),
        'search_items'       => __( 'Search Templates', 'bookcreator' ),
        'not_found'          => __( 'No templates found.', 'bookcreator' ),
        'not_found_in_trash' => __( 'No templates found in Trash.', 'bookcreator' ),
    );

    $args = array(
        'labels'       => $labels,
        'public'       => false,
        'show_ui'      => true,
        'show_in_menu' => 'edit.php?post_type=book_creator',
        'supports'     => array( 'title' ),
        'has_archive'  => false,
        'rewrite'      => false,
        'menu_icon'    => 'dashicons-media-spreadsheet',
    );

    register_post_type( 'bc_template', $args );
}
add_action( 'init', 'bookcreator_register_template_post_type' );

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
    bookcreator_register_template_post_type();
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
    add_meta_box( 'bc_book_template', __( 'Template', 'bookcreator' ), 'bookcreator_meta_box_book_template', 'book_creator', 'side', 'default' );
    add_meta_box( 'bc_chapter_books', __( 'Books', 'bookcreator' ), 'bookcreator_meta_box_chapter_books', 'bc_chapter', 'side', 'default' );
    add_meta_box( 'bc_paragraph_chapters', __( 'Chapters', 'bookcreator' ), 'bookcreator_meta_box_paragraph_chapters', 'bc_paragraph', 'side', 'default' );
    add_meta_box( 'bc_paragraph_footnotes', __( 'Footnotes', 'bookcreator' ), 'bookcreator_meta_box_paragraph_footnotes', 'bc_paragraph', 'normal', 'default' );
    add_meta_box( 'bc_paragraph_citations', __( 'Citations', 'bookcreator' ), 'bookcreator_meta_box_paragraph_citations', 'bc_paragraph', 'normal', 'default' );
    add_meta_box( 'bc_template_details', __( 'Document Settings', 'bookcreator' ), 'bookcreator_meta_box_template_details', 'bc_template', 'normal', 'default' );
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

function bookcreator_meta_box_book_template( $post ) {
    $templates = get_posts( array( 'post_type' => 'bc_template', 'numberposts' => -1 ) );
    $selected  = get_post_meta( $post->ID, 'bc_assigned_template', true );
    if ( ! $selected ) {
        $default_template = get_posts( array(
            'post_type'  => 'bc_template',
            'meta_key'   => 'bc_template_default',
            'meta_value' => '1',
            'numberposts' => 1,
        ) );
        if ( $default_template ) {
            $selected = $default_template[0]->ID;
        }
    }
    echo '<p><label for="bc_assigned_template">' . esc_html__( 'Template', 'bookcreator' ) . '</label><br/>';
    echo '<select name="bc_assigned_template" id="bc_assigned_template">';
    foreach ( $templates as $template ) {
        echo '<option value="' . esc_attr( $template->ID ) . '" ' . selected( $selected, $template->ID, false ) . '>' . esc_html( $template->post_title ) . '</option>';
    }
    echo '</select></p>';
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

function bookcreator_meta_box_template_details( $post ) {
    wp_nonce_field( 'bookcreator_save_template_meta', 'bookcreator_template_meta_nonce' );
    $loader = new \Twig\Loader\FilesystemLoader( plugin_dir_path( __FILE__ ) . 'templates' );
    $twig   = new \Twig\Environment( $loader );

    $default          = get_post_meta( $post->ID, 'bc_template_default', true );
    $doc_format       = get_post_meta( $post->ID, 'bc_doc_format', true );
    $doc_orientation  = get_post_meta( $post->ID, 'bc_doc_orientation', true );
    $doc_width        = get_post_meta( $post->ID, 'bc_doc_width', true );
    $doc_height       = get_post_meta( $post->ID, 'bc_doc_height', true );
    $doc_unit         = get_post_meta( $post->ID, 'bc_doc_unit', true );
    $doc_margin_top   = get_post_meta( $post->ID, 'bc_doc_margin_top', true );
    $doc_margin_right = get_post_meta( $post->ID, 'bc_doc_margin_right', true );
    $doc_margin_bottom= get_post_meta( $post->ID, 'bc_doc_margin_bottom', true );
    $doc_margin_left  = get_post_meta( $post->ID, 'bc_doc_margin_left', true );
    $font_family      = get_post_meta( $post->ID, 'bc_font_family', true );
    $text_color       = get_post_meta( $post->ID, 'bc_text_color', true );
    $background_color = get_post_meta( $post->ID, 'bc_background_color', true );
    $font_size        = get_post_meta( $post->ID, 'bc_font_size', true );
    $line_height      = get_post_meta( $post->ID, 'bc_line_height', true );
    $text_unit        = get_post_meta( $post->ID, 'bc_text_unit', true );
    $text_align       = get_post_meta( $post->ID, 'bc_text_align', true );

    $headings = array();
    for ( $i = 1; $i <= 5; $i++ ) {
        $headings[ 'h' . $i ] = array(
            'font'             => get_post_meta( $post->ID, 'bc_h' . $i . '_font', true ),
            'color'            => get_post_meta( $post->ID, 'bc_h' . $i . '_color', true ),
            'background_color' => get_post_meta( $post->ID, 'bc_h' . $i . '_background_color', true ),
            'font_size'        => get_post_meta( $post->ID, 'bc_h' . $i . '_font_size', true ),
            'line_height'      => get_post_meta( $post->ID, 'bc_h' . $i . '_line_height', true ),
            'align'            => get_post_meta( $post->ID, 'bc_h' . $i . '_align', true ),
        );
    }

    echo $twig->render( 'template-form.twig', array(
        'default_label'         => esc_html__( 'Default Template', 'bookcreator' ),
        'document_settings_label' => esc_html__( 'Document Settings', 'bookcreator' ),
        'format_label'          => esc_html__( 'Document Format', 'bookcreator' ),
        'orientation_label'     => esc_html__( 'Orientation', 'bookcreator' ),
        'portrait_label'        => esc_html__( 'Portrait', 'bookcreator' ),
        'landscape_label'       => esc_html__( 'Landscape', 'bookcreator' ),
        'width_label'           => esc_html__( 'Width', 'bookcreator' ),
        'height_label'          => esc_html__( 'Height', 'bookcreator' ),
        'margin_top_label'      => esc_html__( 'Top Margin', 'bookcreator' ),
        'margin_right_label'    => esc_html__( 'Right Margin', 'bookcreator' ),
        'margin_bottom_label'   => esc_html__( 'Bottom Margin', 'bookcreator' ),
        'margin_left_label'     => esc_html__( 'Left Margin', 'bookcreator' ),
        'body_text_label'       => esc_html__( 'Body Text', 'bookcreator' ),
        'font_label'            => esc_html__( 'Font', 'bookcreator' ),
        'text_color_label'      => esc_html__( 'Text Color', 'bookcreator' ),
        'background_color_label'=> esc_html__( 'Background Color', 'bookcreator' ),
        'font_size_label'       => esc_html__( 'Font Size', 'bookcreator' ),
        'line_height_label'     => esc_html__( 'Line Height', 'bookcreator' ),
        'text_unit_label'       => esc_html__( 'Text Unit', 'bookcreator' ),
        'alignment_label'       => esc_html__( 'Alignment', 'bookcreator' ),
        'align_left_label'      => esc_html__( 'Left', 'bookcreator' ),
        'align_right_label'     => esc_html__( 'Right', 'bookcreator' ),
        'align_center_label'    => esc_html__( 'Center', 'bookcreator' ),
        'align_justify_label'   => esc_html__( 'Justify', 'bookcreator' ),
        'heading_settings_label'=> esc_html__( 'Heading Styles', 'bookcreator' ),
        'default'               => $default,
        'doc_format'            => esc_attr( $doc_format ),
        'doc_orientation'       => esc_attr( $doc_orientation ),
        'doc_width'             => esc_attr( $doc_width ),
        'doc_height'            => esc_attr( $doc_height ),
        'doc_unit'              => esc_attr( $doc_unit ),
        'doc_margin_top'        => esc_attr( $doc_margin_top ),
        'doc_margin_right'      => esc_attr( $doc_margin_right ),
        'doc_margin_bottom'     => esc_attr( $doc_margin_bottom ),
        'doc_margin_left'       => esc_attr( $doc_margin_left ),
        'font_family'           => esc_attr( $font_family ),
        'text_color'            => esc_attr( $text_color ),
        'background_color'      => esc_attr( $background_color ),
        'font_size'             => esc_attr( $font_size ),
        'line_height'           => esc_attr( $line_height ),
        'text_unit'             => esc_attr( $text_unit ),
        'text_align'            => esc_attr( $text_align ),
        'headings'              => $headings,
    ) );
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
        'bc_assigned_template' => 'absint',
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

function bookcreator_save_template_meta( $post_id ) {
    if ( ! isset( $_POST['bookcreator_template_meta_nonce'] ) || ! wp_verify_nonce( $_POST['bookcreator_template_meta_nonce'], 'bookcreator_save_template_meta' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $fields = array(
        'bc_doc_format'      => 'sanitize_text_field',
        'bc_doc_orientation' => 'sanitize_text_field',
        'bc_doc_width'       => 'floatval',
        'bc_doc_height'      => 'floatval',
        'bc_doc_unit'        => 'sanitize_text_field',
        'bc_doc_margin_top'  => 'floatval',
        'bc_doc_margin_right'=> 'floatval',
        'bc_doc_margin_bottom'=> 'floatval',
        'bc_doc_margin_left' => 'floatval',
        'bc_font_family'     => 'sanitize_text_field',
        'bc_text_color'      => 'sanitize_hex_color',
        'bc_background_color'=> 'sanitize_hex_color',
        'bc_font_size'       => 'sanitize_text_field',
        'bc_line_height'     => 'sanitize_text_field',
        'bc_text_unit'       => 'sanitize_text_field',
        'bc_text_align'      => 'sanitize_text_field',
    );

    for ( $i = 1; $i <= 5; $i++ ) {
        $fields[ 'bc_h' . $i . '_font' ]             = 'sanitize_text_field';
        $fields[ 'bc_h' . $i . '_color' ]            = 'sanitize_hex_color';
        $fields[ 'bc_h' . $i . '_background_color' ] = 'sanitize_hex_color';
        $fields[ 'bc_h' . $i . '_font_size' ]        = 'sanitize_text_field';
        $fields[ 'bc_h' . $i . '_line_height' ]      = 'sanitize_text_field';
        $fields[ 'bc_h' . $i . '_align' ]            = 'sanitize_text_field';
    }

    foreach ( $fields as $field => $sanitize ) {
        if ( isset( $_POST[ $field ] ) ) {
            $value = call_user_func( $sanitize, wp_unslash( $_POST[ $field ] ) );
            update_post_meta( $post_id, $field, $value );
        }
    }

    $default = isset( $_POST['bc_template_default'] ) ? '1' : '0';
    update_post_meta( $post_id, 'bc_template_default', $default );

    if ( '1' === $default ) {
        $others = get_posts( array(
            'post_type'    => 'bc_template',
            'post__not_in' => array( $post_id ),
            'numberposts'  => -1,
        ) );
        foreach ( $others as $other ) {
            update_post_meta( $other->ID, 'bc_template_default', '0' );
        }
    }
}
add_action( 'save_post_bc_template', 'bookcreator_save_template_meta' );

function bookcreator_template_admin_enqueue( $hook ) {
    if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
        return;
    }
    $screen = get_current_screen();
    if ( 'bc_template' !== $screen->post_type ) {
        return;
    }
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_style( 'bookcreator-admin', plugin_dir_url( __FILE__ ) . 'css/admin.css', array(), '1.0' );
    wp_enqueue_script( 'bookcreator-admin', plugin_dir_url( __FILE__ ) . 'js/admin.js', array( 'wp-color-picker' ), '1.0', true );
}
add_action( 'admin_enqueue_scripts', 'bookcreator_template_admin_enqueue' );

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

/**
 * Render single book using Twig template.
 */
function bookcreator_render_single_template( $template ) {
    if ( is_singular( 'book_creator' ) ) {
        $loader = new \Twig\Loader\FilesystemLoader( plugin_dir_path( __FILE__ ) . 'templates' );
        $twig   = new \Twig\Environment( $loader );

        $post_id = get_queried_object_id();
        $book    = array(
            'title'        => get_the_title( $post_id ),
            'subtitle'     => get_post_meta( $post_id, 'bc_subtitle', true ),
            'author'       => get_post_meta( $post_id, 'bc_author', true ),
            'coauthors'    => get_post_meta( $post_id, 'bc_coauthors', true ),
            'publisher'    => get_post_meta( $post_id, 'bc_publisher', true ),
            'isbn'         => get_post_meta( $post_id, 'bc_isbn', true ),
            'pub_date'     => get_post_meta( $post_id, 'bc_pub_date', true ),
            'edition'      => get_post_meta( $post_id, 'bc_edition', true ),
            'language'     => get_post_meta( $post_id, 'bc_language', true ),
            'description'  => get_post_meta( $post_id, 'bc_description', true ),
            'keywords'     => get_post_meta( $post_id, 'bc_keywords', true ),
            'audience'     => get_post_meta( $post_id, 'bc_audience', true ),
            'cover'        => wp_get_attachment_url( get_post_meta( $post_id, 'bc_cover', true ) ),
            'retina_cover' => wp_get_attachment_url( get_post_meta( $post_id, 'bc_retina_cover', true ) ),
            'frontispiece' => get_post_meta( $post_id, 'bc_frontispiece', true ),
            'copyright'    => get_post_meta( $post_id, 'bc_copyright', true ),
            'dedication'   => get_post_meta( $post_id, 'bc_dedication', true ),
            'preface'      => get_post_meta( $post_id, 'bc_preface', true ),
            'appendix'     => get_post_meta( $post_id, 'bc_appendix', true ),
            'bibliography' => get_post_meta( $post_id, 'bc_bibliography', true ),
            'author_note'  => get_post_meta( $post_id, 'bc_author_note', true ),
        );
        $template_id = get_post_meta( $post_id, 'bc_assigned_template', true );
        if ( ! $template_id ) {
            $default_template = get_posts( array(
                'post_type'  => 'bc_template',
                'meta_key'   => 'bc_template_default',
                'meta_value' => '1',
                'numberposts' => 1,
            ) );
            if ( $default_template ) {
                $template_id = $default_template[0]->ID;
            }
        }
        $template_data = array();
        if ( $template_id ) {
            $template_fields = array(
                'doc_format'      => 'bc_doc_format',
                'doc_orientation' => 'bc_doc_orientation',
                'doc_width'       => 'bc_doc_width',
                'doc_height'      => 'bc_doc_height',
                'doc_unit'        => 'bc_doc_unit',
                'doc_margin_top'  => 'bc_doc_margin_top',
                'doc_margin_right'=> 'bc_doc_margin_right',
                'doc_margin_bottom'=> 'bc_doc_margin_bottom',
                'doc_margin_left' => 'bc_doc_margin_left',
                'font_family'     => 'bc_font_family',
                'text_color'      => 'bc_text_color',
                'background_color'=> 'bc_background_color',
                'font_size'       => 'bc_font_size',
                'line_height'     => 'bc_line_height',
                'text_unit'       => 'bc_text_unit',
                'text_align'      => 'bc_text_align',
            );

            for ( $i = 1; $i <= 5; $i++ ) {
                $template_fields[ 'h' . $i . '_font' ]             = 'bc_h' . $i . '_font';
                $template_fields[ 'h' . $i . '_color' ]            = 'bc_h' . $i . '_color';
                $template_fields[ 'h' . $i . '_background_color' ] = 'bc_h' . $i . '_background_color';
                $template_fields[ 'h' . $i . '_font_size' ]        = 'bc_h' . $i . '_font_size';
                $template_fields[ 'h' . $i . '_line_height' ]      = 'bc_h' . $i . '_line_height';
                $template_fields[ 'h' . $i . '_align' ]            = 'bc_h' . $i . '_align';
            }

            foreach ( $template_fields as $key => $meta_key ) {
                $template_data[ $key ] = get_post_meta( $template_id, $meta_key, true );
            }
        }

        $chapters     = array();
        $chapter_menu = wp_get_nav_menu_object( 'chapters-book-' . $post_id );
        if ( $chapter_menu ) {
            $chapter_items = wp_get_nav_menu_items( $chapter_menu->term_id );
            if ( $chapter_items ) {
                foreach ( $chapter_items as $item ) {
                    if ( 'bc_chapter' !== $item->object ) {
                        continue;
                    }
                    $chapter_id = (int) $item->object_id;
                    $paragraphs = array();
                    $para_menu  = wp_get_nav_menu_object( 'paragraphs-chapter-' . $chapter_id );
                    if ( $para_menu ) {
                        $para_items = wp_get_nav_menu_items( $para_menu->term_id );
                        if ( $para_items ) {
                            foreach ( $para_items as $p_item ) {
                                if ( 'bc_paragraph' !== $p_item->object ) {
                                    continue;
                                }
                                $pid          = (int) $p_item->object_id;
                                $paragraphs[] = array(
                                    'title'   => get_the_title( $pid ),
                                    'content' => apply_filters( 'the_content', get_post_field( 'post_content', $pid ) ),
                                );
                            }
                        }
                    }
                    $chapters[] = array(
                        'title'      => get_the_title( $chapter_id ),
                        'content'    => apply_filters( 'the_content', get_post_field( 'post_content', $chapter_id ) ),
                        'paragraphs' => $paragraphs,
                    );
                }
            }
        }

        $plugin_url = plugin_dir_url( __FILE__ );
        echo $twig->render( 'book.twig', array(
            'book'      => $book,
            'chapters'  => $chapters,
            'template'  => $template_data,
            'plugin_url'=> $plugin_url,
        ) );
        exit;
    }

    return $template;
}
add_filter( 'template_include', 'bookcreator_render_single_template' );

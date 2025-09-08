<?php
/**
 * Plugin Name: BookCreator
 * Description: Custom post type and management interface for creating books.
 * Version: 1.0.0
 * Author: OpenAI ChatGPT
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
        'supports'           => array( 'title' ),
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
        'name'              => __( 'Genere Libro', 'bookcreator' ),
        'singular_name'     => __( 'Genere Libro', 'bookcreator' ),
        'search_items'      => __( 'Search Genres', 'bookcreator' ),
        'all_items'         => __( 'All Genres', 'bookcreator' ),
        'parent_item'       => __( 'Parent Genre', 'bookcreator' ),
        'parent_item_colon' => __( 'Parent Genre:', 'bookcreator' ),
        'edit_item'         => __( 'Edit Genre', 'bookcreator' ),
        'update_item'       => __( 'Update Genre', 'bookcreator' ),
        'add_new_item'      => __( 'Add New Genre', 'bookcreator' ),
        'new_item_name'     => __( 'New Genre Name', 'bookcreator' ),
        'menu_name'         => __( 'Genere Libro', 'bookcreator' ),
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

function bookcreator_add_thumbnail_support() {
    add_theme_support( 'post-thumbnails', array( 'book_creator', 'bc_chapter' ) );
}
add_action( 'after_setup_theme', 'bookcreator_add_thumbnail_support' );

/**
 * Flush rewrite rules on activation/deactivation and ensure default term exists.
 */
function bookcreator_activate() {
    bookcreator_register_post_type();
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
    add_meta_box( 'bc_identification', __( 'Identificazione', 'bookcreator' ), 'bookcreator_meta_box_identification', 'book_creator', 'normal', 'high' );
    add_meta_box( 'bc_descriptive', __( 'Descrittivo', 'bookcreator' ), 'bookcreator_meta_box_descriptive', 'book_creator', 'normal', 'default' );
    add_meta_box( 'bc_prelim', __( 'Parti preliminari', 'bookcreator' ), 'bookcreator_meta_box_prelim', 'book_creator', 'normal', 'default' );
    add_meta_box( 'bc_final', __( 'Parti finali', 'bookcreator' ), 'bookcreator_meta_box_final', 'book_creator', 'normal', 'default' );
    add_meta_box( 'bc_chapter_books', __( 'Books', 'bookcreator' ), 'bookcreator_meta_box_chapter_books', 'bc_chapter', 'side', 'default' );
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

    <p><label for="bc_description"><?php esc_html_e( 'Descrizione', 'bookcreator' ); ?></label><br/>
    <textarea name="bc_description" id="bc_description" class="widefat" rows="4"><?php echo esc_textarea( get_post_meta( $post->ID, 'bc_description', true ) ); ?></textarea></p>

    <p><label for="bc_keywords"><?php esc_html_e( 'Parole chiave', 'bookcreator' ); ?></label><br/>
    <input type="text" name="bc_keywords" id="bc_keywords" value="<?php echo esc_attr( get_post_meta( $post->ID, 'bc_keywords', true ) ); ?>" class="widefat" /></p>

    <p><label for="bc_audience"><?php esc_html_e( 'Pubblico', 'bookcreator' ); ?></label><br/>
    <input type="text" name="bc_audience" id="bc_audience" value="<?php echo esc_attr( get_post_meta( $post->ID, 'bc_audience', true ) ); ?>" class="widefat" /></p>
    <?php
}

function bookcreator_meta_box_prelim( $post ) {
    $cover_id        = get_post_meta( $post->ID, 'bc_cover', true );
    $cover_retina_id = get_post_meta( $post->ID, 'bc_cover_retina', true );
    ?>
    <p><label for="bc_cover"><?php esc_html_e( 'Copertina', 'bookcreator' ); ?></label><br/>
    <input type="file" name="bc_cover" id="bc_cover" /><br/>
    <?php if ( $cover_id ) { echo wp_get_attachment_image( $cover_id, array( 100, 100 ) ); } ?></p>

    <p><label for="bc_cover_retina"><?php esc_html_e( 'Copertina Retina', 'bookcreator' ); ?></label><br/>
    <input type="file" name="bc_cover_retina" id="bc_cover_retina" /><br/>
    <?php if ( $cover_retina_id ) { echo wp_get_attachment_image( $cover_retina_id, array( 100, 100 ) ); } ?></p>

    <p><label for="bc_frontispiece"><?php esc_html_e( 'Frontespizio', 'bookcreator' ); ?></label><br/>
    <textarea name="bc_frontispiece" id="bc_frontispiece" class="widefat" rows="3"><?php echo esc_textarea( get_post_meta( $post->ID, 'bc_frontispiece', true ) ); ?></textarea></p>

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

    <p><label for="bc_bibliography"><?php esc_html_e( 'Bibliografia', 'bookcreator' ); ?></label><br/>
    <textarea name="bc_bibliography" id="bc_bibliography" class="widefat" rows="3"><?php echo esc_textarea( get_post_meta( $post->ID, 'bc_bibliography', true ) ); ?></textarea></p>

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
        echo '<li><label><input type="checkbox" name="bc_books[]" value="' . esc_attr( $book->ID ) . '" ' . checked( in_array( $book->ID, $selected, true ), true, false ) . ' /> ' . esc_html( $book->post_title ) . '</label></li>';
    }
    echo '</ul>';
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

    if ( ! empty( $_FILES['bc_cover']['name'] ) ) {
        $cover_id = media_handle_upload( 'bc_cover', $post_id );
        if ( ! is_wp_error( $cover_id ) ) {
            update_post_meta( $post_id, 'bc_cover', $cover_id );
        }
    }

    if ( ! empty( $_FILES['bc_cover_retina']['name'] ) ) {
        $cover_retina_id = media_handle_upload( 'bc_cover_retina', $post_id );
        if ( ! is_wp_error( $cover_retina_id ) ) {
            update_post_meta( $post_id, 'bc_cover_retina', $cover_retina_id );
        }
    }
}
add_action( 'save_post_book_creator', 'bookcreator_save_meta' );

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

/**
 * Customize columns in the books list.
 */
function bookcreator_set_custom_columns( $columns ) {
    $columns = array(
        'cb'                  => $columns['cb'],
        'title'               => $columns['title'],
        'taxonomy-book_genre' => __( 'Genere Libro', 'bookcreator' ),
        'bc_language'         => __( 'Lingua', 'bookcreator' ),
        'bc_cover'            => __( 'Copertina', 'bookcreator' ),
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
}
add_action( 'manage_book_creator_posts_custom_column', 'bookcreator_render_custom_columns', 10, 2 );

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
                foreach ( $_POST['menu-item-db-id'] as $item_id => $db_id ) {
                    $args = array(
                        'menu-item-object-id' => absint( $_POST['menu-item-object-id'][ $item_id ] ),
                        'menu-item-object'    => sanitize_key( $_POST['menu-item-object'][ $item_id ] ),
                        'menu-item-parent-id' => absint( $_POST['menu-item-parent-id'][ $item_id ] ),
                        'menu-item-position'  => absint( $_POST['menu-item-position'][ $item_id ] ),
                        'menu-item-type'      => sanitize_key( $_POST['menu-item-type'][ $item_id ] ),
                        'menu-item-title'     => sanitize_text_field( $_POST['menu-item-title'][ $item_id ] ),
                    );
                    wp_update_nav_menu_item( $menu_id, $item_id, $args );
                }
            }
        }

        echo '<form method="post">';
        wp_nonce_field( 'bc_save_chapter_order' );
        wp_nav_menu( array(
            'menu'        => $menu_id,
            'walker'      => new Walker_Nav_Menu_Edit,
            'container'   => false,
            'items_wrap'  => '<ul id="menu-to-edit" class="menu">%3$s</ul>',
            'fallback_cb' => false,
        ) );
        submit_button( __( 'Salva ordine', 'bookcreator' ), 'primary', 'save_menu_order' );
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
            'frontispiece' => get_post_meta( $post_id, 'bc_frontispiece', true ),
            'copyright'    => get_post_meta( $post_id, 'bc_copyright', true ),
            'dedication'   => get_post_meta( $post_id, 'bc_dedication', true ),
            'preface'      => get_post_meta( $post_id, 'bc_preface', true ),
            'appendix'     => get_post_meta( $post_id, 'bc_appendix', true ),
            'bibliography' => get_post_meta( $post_id, 'bc_bibliography', true ),
            'author_note'  => get_post_meta( $post_id, 'bc_author_note', true ),
        );

        echo $twig->render( 'book.twig', array( 'book' => $book ) );
        exit;
    }

    return $template;
}
add_filter( 'template_include', 'bookcreator_render_single_template' );

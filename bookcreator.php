<?php
/**
 * Plugin Name: BookCreator
 * Description: Custom post type and management interface for creating books.
 * Version: 1.0.0
 * Author: OpenAI ChatGPT
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

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
        'not_found_in_trash' => __( 'No books found in Trash.', 'bookcreator' )
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'show_ui'            => true,
        'show_in_menu'       => false,
        'supports'           => array( 'title' ),
        'rewrite'            => array( 'slug' => 'book' ),
        'has_archive'        => true,
    );

    register_post_type( 'book_creator', $args );

    $taxonomy_labels = array(
        'name'              => __( 'Generi libro', 'bookcreator' ),
        'singular_name'     => __( 'Genere libro', 'bookcreator' ),
        'search_items'      => __( 'Search Genres', 'bookcreator' ),
        'all_items'         => __( 'All Genres', 'bookcreator' ),
        'parent_item'       => __( 'Parent Genre', 'bookcreator' ),
        'parent_item_colon' => __( 'Parent Genre:', 'bookcreator' ),
        'edit_item'         => __( 'Edit Genre', 'bookcreator' ),
        'update_item'       => __( 'Update Genre', 'bookcreator' ),
        'add_new_item'      => __( 'Add New Genre', 'bookcreator' ),
        'new_item_name'     => __( 'New Genre Name', 'bookcreator' ),
        'menu_name'         => __( 'Genere libro', 'bookcreator' ),
    );

    $taxonomy_args = array(
        'labels'            => $taxonomy_labels,
        'hierarchical'      => true,
        'show_admin_column' => true,
        'show_ui'           => true,
        'show_in_menu'      => false,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'book-genre' ),
    );

    register_taxonomy( 'book_genre', array( 'book_creator' ), $taxonomy_args );
}
add_action( 'init', 'bookcreator_register_post_type' );

/**
 * Activation hook to ensure default genre term exists.
 */
function bookcreator_activate() {
    bookcreator_register_post_type();
    if ( ! term_exists( 'Book', 'book_genre' ) ) {
        wp_insert_term( 'Book', 'book_genre' );
    }
}
register_activation_hook( __FILE__, 'bookcreator_activate' );

/**
 * Admin menu setup.
 */
function bookcreator_admin_menu() {
    add_menu_page(
        __( 'BookCreator', 'bookcreator' ),
        __( 'BookCreator', 'bookcreator' ),
        'manage_options',
        'bookcreator',
        'bookcreator_create_page',
        'dashicons-book-alt',
        6
    );

    add_submenu_page(
        'bookcreator',
        __( 'Crea libro', 'bookcreator' ),
        __( 'Crea libro', 'bookcreator' ),
        'manage_options',
        'bookcreator',
        'bookcreator_create_page'
    );

    add_submenu_page(
        'bookcreator',
        __( 'Genere libro', 'bookcreator' ),
        __( 'Genere libro', 'bookcreator' ),
        'manage_options',
        'bookcreator_genres',
        'bookcreator_redirect_genres'
    );
}
add_action( 'admin_menu', 'bookcreator_admin_menu' );

/**
 * Redirects to taxonomy management page.
 */
function bookcreator_redirect_genres() {
    wp_redirect( admin_url( 'edit-tags.php?taxonomy=book_genre&post_type=book_creator' ) );
    exit;
}

/**
 * Enqueue scripts for tabs.
 */
function bookcreator_admin_assets( $hook ) {
    if ( 'toplevel_page_bookcreator' !== $hook ) {
        return;
    }
    wp_enqueue_script( 'jquery-ui-tabs' );
    wp_enqueue_media();
}
add_action( 'admin_enqueue_scripts', 'bookcreator_admin_assets' );

/**
 * Handle saving data from forms.
 */
function bookcreator_handle_save() {
    if ( ! isset( $_POST['bc_section'] ) || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $section = sanitize_text_field( wp_unslash( $_POST['bc_section'] ) );
    $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

    if ( 'identification' === $section ) {
        check_admin_referer( 'bookcreator_save_identification' );
        $title = sanitize_text_field( wp_unslash( $_POST['bc_title'] ) );
        if ( empty( $title ) ) {
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . esc_html__( 'Titolo obbligatorio.', 'bookcreator' ) . '</p></div>';
            });
            return;
        }

        $post_data = array(
            'post_type'   => 'book_creator',
            'post_title'  => $title,
            'post_status' => 'publish',
        );

        if ( $post_id ) {
            $post_data['ID'] = $post_id;
            $post_id         = wp_update_post( $post_data );
        } else {
            $post_id = wp_insert_post( $post_data );
        }

        $fields = array(
            'bc_subtitle'    => 'bc_subtitle',
            'bc_author'      => 'bc_author',
            'bc_coauthors'   => 'bc_coauthors',
            'bc_publisher'   => 'bc_publisher',
            'bc_isbn'        => 'bc_isbn',
            'bc_pub_date'    => 'bc_pub_date',
            'bc_edition'     => 'bc_edition',
        );
        foreach ( $fields as $field => $meta_key ) {
            $value = isset( $_POST[ $field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) : '';
            update_post_meta( $post_id, $meta_key, $value );
        }
    }

    if ( ! $post_id ) {
        return;
    }

    if ( 'descriptive' === $section ) {
        check_admin_referer( 'bookcreator_save_descriptive' );
        $genre = isset( $_POST['bc_genre'] ) ? array_map( 'intval', (array) $_POST['bc_genre'] ) : array();
        wp_set_post_terms( $post_id, $genre, 'book_genre', false );

        $fields = array(
            'bc_language'    => 'bc_language',
            'bc_description' => 'bc_description',
            'bc_keywords'    => 'bc_keywords',
            'bc_audience'    => 'bc_audience',
        );
        foreach ( $fields as $field => $meta_key ) {
            $value = isset( $_POST[ $field ] ) ? wp_kses_post( wp_unslash( $_POST[ $field ] ) ) : '';
            update_post_meta( $post_id, $meta_key, $value );
        }
    }

    if ( 'prelim' === $section ) {
        check_admin_referer( 'bookcreator_save_prelim' );
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
        $fields = array(
            'bc_frontispiece' => 'bc_frontispiece',
            'bc_copyright'    => 'bc_copyright',
            'bc_dedication'   => 'bc_dedication',
            'bc_preface'      => 'bc_preface',
        );
        foreach ( $fields as $field => $meta_key ) {
            $value = isset( $_POST[ $field ] ) ? wp_kses_post( wp_unslash( $_POST[ $field ] ) ) : '';
            update_post_meta( $post_id, $meta_key, $value );
        }
    }

    if ( 'final' === $section ) {
        check_admin_referer( 'bookcreator_save_final' );
        $fields = array(
            'bc_appendix'    => 'bc_appendix',
            'bc_bibliography'=> 'bc_bibliography',
            'bc_author_note' => 'bc_author_note',
        );
        foreach ( $fields as $field => $meta_key ) {
            $value = isset( $_POST[ $field ] ) ? wp_kses_post( wp_unslash( $_POST[ $field ] ) ) : '';
            update_post_meta( $post_id, $meta_key, $value );
        }
    }

    wp_redirect( add_query_arg( array( 'page' => 'bookcreator', 'post_id' => $post_id, 'updated' => 'true' ), admin_url( 'admin.php' ) ) );
    exit;
}
add_action( 'admin_post_bookcreator_save', 'bookcreator_handle_save' );

/**
 * Render the book creation page with tabs.
 */
function bookcreator_create_page() {
    $post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;

    if ( $post_id ) {
        $post = get_post( $post_id );
    } else {
        $post = null;
    }

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__( 'Crea libro', 'bookcreator' ) . '</h1>';
    if ( isset( $_GET['updated'] ) ) {
        echo '<div class="notice notice-success"><p>' . esc_html__( 'Libro salvato.', 'bookcreator' ) . '</p></div>';
    }
    ?>
    <div id="bookcreator-tabs">
        <ul>
            <li><a href="#bc-identification"><?php esc_html_e( 'Identificazione', 'bookcreator' ); ?></a></li>
            <li><a href="#bc-descriptive"><?php esc_html_e( 'Descrittivo', 'bookcreator' ); ?></a></li>
            <li><a href="#bc-prelim"><?php esc_html_e( 'Parti preliminari', 'bookcreator' ); ?></a></li>
            <li><a href="#bc-final"><?php esc_html_e( 'Parti finali', 'bookcreator' ); ?></a></li>
        </ul>
        <div id="bc-identification">
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'bookcreator_save_identification' ); ?>
                <input type="hidden" name="action" value="bookcreator_save" />
                <input type="hidden" name="bc_section" value="identification" />
                <input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>" />
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="bc_title"><?php esc_html_e( 'Titolo', 'bookcreator' ); ?>*</label></th>
                        <td><input name="bc_title" type="text" id="bc_title" value="<?php echo $post ? esc_attr( $post->post_title ) : ''; ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bc_subtitle"><?php esc_html_e( 'Sottotitolo', 'bookcreator' ); ?></label></th>
                        <td><input name="bc_subtitle" type="text" id="bc_subtitle" value="<?php echo esc_attr( get_post_meta( $post_id, 'bc_subtitle', true ) ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bc_author"><?php esc_html_e( 'Autore principale', 'bookcreator' ); ?></label></th>
                        <td><input name="bc_author" type="text" id="bc_author" value="<?php echo esc_attr( get_post_meta( $post_id, 'bc_author', true ) ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bc_coauthors"><?php esc_html_e( 'Co-autori', 'bookcreator' ); ?></label></th>
                        <td><input name="bc_coauthors" type="text" id="bc_coauthors" value="<?php echo esc_attr( get_post_meta( $post_id, 'bc_coauthors', true ) ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bc_publisher"><?php esc_html_e( 'Editore', 'bookcreator' ); ?></label></th>
                        <td><input name="bc_publisher" type="text" id="bc_publisher" value="<?php echo esc_attr( get_post_meta( $post_id, 'bc_publisher', true ) ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bc_isbn"><?php esc_html_e( 'ISBN', 'bookcreator' ); ?></label></th>
                        <td><input name="bc_isbn" type="text" id="bc_isbn" value="<?php echo esc_attr( get_post_meta( $post_id, 'bc_isbn', true ) ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bc_pub_date"><?php esc_html_e( 'Data di pubblicazione', 'bookcreator' ); ?></label></th>
                        <td><input name="bc_pub_date" type="date" id="bc_pub_date" value="<?php echo esc_attr( get_post_meta( $post_id, 'bc_pub_date', true ) ); ?>" ></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bc_edition"><?php esc_html_e( 'Edizione/Versione', 'bookcreator' ); ?></label></th>
                        <td><input name="bc_edition" type="text" id="bc_edition" value="<?php echo esc_attr( get_post_meta( $post_id, 'bc_edition', true ) ); ?>" class="regular-text"></td>
                    </tr>
                </table>
                <?php submit_button( __( 'Salva Identificazione', 'bookcreator' ) ); ?>
            </form>
        </div>
        <div id="bc-descriptive">
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'bookcreator_save_descriptive' ); ?>
                <input type="hidden" name="action" value="bookcreator_save" />
                <input type="hidden" name="bc_section" value="descriptive" />
                <input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>" />
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="bc_genre"><?php esc_html_e( 'Genere libro', 'bookcreator' ); ?></label></th>
                        <td>
                            <?php
                            $genres = get_terms( array( 'taxonomy' => 'book_genre', 'hide_empty' => false ) );
                            $selected = wp_get_object_terms( $post_id, 'book_genre', array( 'fields' => 'ids' ) );
                            if ( ! empty( $genres ) && ! is_wp_error( $genres ) ) {
                                echo '<select name="bc_genre[]" id="bc_genre" multiple size="5" style="width: 200px;">';
                                foreach ( $genres as $genre ) {
                                    echo '<option value="' . esc_attr( $genre->term_id ) . '"' . selected( in_array( $genre->term_id, (array) $selected, true ), true, false ) . '>' . esc_html( $genre->name ) . '</option>';
                                }
                                echo '</select>';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bc_language"><?php esc_html_e( 'Lingua', 'bookcreator' ); ?></label></th>
                        <td><input name="bc_language" type="text" id="bc_language" value="<?php echo esc_attr( get_post_meta( $post_id, 'bc_language', true ) ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bc_description"><?php esc_html_e( 'Descrizione', 'bookcreator' ); ?></label></th>
                        <td><?php wp_editor( get_post_meta( $post_id, 'bc_description', true ), 'bc_description', array( 'textarea_name' => 'bc_description' ) ); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bc_keywords"><?php esc_html_e( 'Parole chiave', 'bookcreator' ); ?></label></th>
                        <td><input name="bc_keywords" type="text" id="bc_keywords" value="<?php echo esc_attr( get_post_meta( $post_id, 'bc_keywords', true ) ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bc_audience"><?php esc_html_e( 'Target audience', 'bookcreator' ); ?></label></th>
                        <td><input name="bc_audience" type="text" id="bc_audience" value="<?php echo esc_attr( get_post_meta( $post_id, 'bc_audience', true ) ); ?>" class="regular-text"></td>
                    </tr>
                </table>
                <?php submit_button( __( 'Salva Descrittivo', 'bookcreator' ) ); ?>
            </form>
        </div>
        <div id="bc-prelim">
            <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'bookcreator_save_prelim' ); ?>
                <input type="hidden" name="action" value="bookcreator_save" />
                <input type="hidden" name="bc_section" value="prelim" />
                <input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>" />
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="bc_cover"><?php esc_html_e( 'Copertina', 'bookcreator' ); ?></label></th>
                        <td><input type="file" name="bc_cover" id="bc_cover"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bc_cover_retina"><?php esc_html_e( 'Copertina Retina', 'bookcreator' ); ?></label></th>
                        <td><input type="file" name="bc_cover_retina" id="bc_cover_retina"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bc_frontispiece"><?php esc_html_e( 'Frontespizio', 'bookcreator' ); ?></label></th>
                        <td><?php wp_editor( get_post_meta( $post_id, 'bc_frontispiece', true ), 'bc_frontispiece', array( 'textarea_name' => 'bc_frontispiece' ) ); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bc_copyright"><?php esc_html_e( 'Copyright/Colophon', 'bookcreator' ); ?></label></th>
                        <td><input name="bc_copyright" type="text" id="bc_copyright" value="<?php echo esc_attr( get_post_meta( $post_id, 'bc_copyright', true ) ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bc_dedication"><?php esc_html_e( 'Dedica', 'bookcreator' ); ?></label></th>
                        <td><?php wp_editor( get_post_meta( $post_id, 'bc_dedication', true ), 'bc_dedication', array( 'textarea_name' => 'bc_dedication' ) ); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bc_preface"><?php esc_html_e( 'Prefazione', 'bookcreator' ); ?></label></th>
                        <td><?php wp_editor( get_post_meta( $post_id, 'bc_preface', true ), 'bc_preface', array( 'textarea_name' => 'bc_preface' ) ); ?></td>
                    </tr>
                </table>
                <?php submit_button( __( 'Salva Parti preliminari', 'bookcreator' ) ); ?>
            </form>
        </div>
        <div id="bc-final">
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'bookcreator_save_final' ); ?>
                <input type="hidden" name="action" value="bookcreator_save" />
                <input type="hidden" name="bc_section" value="final" />
                <input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>" />
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="bc_appendix"><?php esc_html_e( 'Appendice', 'bookcreator' ); ?></label></th>
                        <td><?php wp_editor( get_post_meta( $post_id, 'bc_appendix', true ), 'bc_appendix', array( 'textarea_name' => 'bc_appendix' ) ); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bc_bibliography"><?php esc_html_e( 'Bibliografia', 'bookcreator' ); ?></label></th>
                        <td><?php wp_editor( get_post_meta( $post_id, 'bc_bibliography', true ), 'bc_bibliography', array( 'textarea_name' => 'bc_bibliography' ) ); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bc_author_note"><?php esc_html_e( 'Note biografiche autore', 'bookcreator' ); ?></label></th>
                        <td><?php wp_editor( get_post_meta( $post_id, 'bc_author_note', true ), 'bc_author_note', array( 'textarea_name' => 'bc_author_note' ) ); ?></td>
                    </tr>
                </table>
                <?php submit_button( __( 'Salva Parti finali', 'bookcreator' ) ); ?>
            </form>
        </div>
    </div>
    <script>
        jQuery(function($){
            $('#bookcreator-tabs').tabs();
        });
    </script>
    <?php
    echo '</div>';
}

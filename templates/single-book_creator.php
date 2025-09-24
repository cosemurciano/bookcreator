<?php
/**
 * Template for displaying Book Creator custom post type.
 *
 * @package BookCreator
 */

global $post;

$book_id = $post->ID;

$book_language  = get_post_meta( $book_id, 'bc_language', true );
$template_texts = bookcreator_get_all_template_texts( $book_language );

$book_index_heading       = isset( $template_texts['book_index_heading'] ) ? $template_texts['book_index_heading'] : __( 'Indice', 'bookcreator' );
$cover_caption_text       = isset( $template_texts['cover_caption'] ) ? $template_texts['cover_caption'] : __( 'Copertina', 'bookcreator' );
$publication_date_label   = isset( $template_texts['publication_date_label'] ) ? $template_texts['publication_date_label'] : __( 'Data di pubblicazione', 'bookcreator' );
$frontispiece_title_text  = isset( $template_texts['frontispiece_title'] ) ? $template_texts['frontispiece_title'] : __( 'Frontespizio', 'bookcreator' );
$copyright_section_title  = isset( $template_texts['copyright_title'] ) ? $template_texts['copyright_title'] : __( 'Copyright', 'bookcreator' );
$dedication_section_title = isset( $template_texts['dedication_title'] ) ? $template_texts['dedication_title'] : __( 'Dedica', 'bookcreator' );
$preface_section_title    = isset( $template_texts['preface_title'] ) ? $template_texts['preface_title'] : __( 'Prefazione', 'bookcreator' );
$appendix_section_title   = isset( $template_texts['appendix_title'] ) ? $template_texts['appendix_title'] : __( 'Appendice', 'bookcreator' );
$bibliography_section_title = isset( $template_texts['bibliography_title'] ) ? $template_texts['bibliography_title'] : __( 'Bibliografia', 'bookcreator' );
$author_note_section_title = isset( $template_texts['author_note_title'] ) ? $template_texts['author_note_title'] : __( "Nota dell'autore", 'bookcreator' );
$chapter_fallback_title   = isset( $template_texts['chapter_fallback_title'] ) ? $template_texts['chapter_fallback_title'] : __( 'Capitolo %s', 'bookcreator' );
$paragraph_fallback_title = isset( $template_texts['paragraph_fallback_title'] ) ? $template_texts['paragraph_fallback_title'] : __( 'Paragrafo %s', 'bookcreator' );
$footnotes_heading_text   = isset( $template_texts['footnotes_heading'] ) ? $template_texts['footnotes_heading'] : __( 'Note', 'bookcreator' );
$citations_heading_text   = isset( $template_texts['citations_heading'] ) ? $template_texts['citations_heading'] : __( 'Citazioni', 'bookcreator' );

$meta_fields = array(
    'bc_subtitle'     => __( 'Sottotitolo', 'bookcreator' ),
    'bc_publisher'    => __( 'Editore', 'bookcreator' ),
    'bc_isbn'         => __( 'ISBN', 'bookcreator' ),
    'bc_pub_date'     => $publication_date_label,
    'bc_edition'      => __( 'Edizione/Versione', 'bookcreator' ),
    'bc_language'     => __( 'Lingua', 'bookcreator' ),
);

$rich_text_fields = array(
    'bc_description'  => __( 'Descrizione', 'bookcreator' ),
    'bc_frontispiece' => $frontispiece_title_text,
    'bc_copyright'    => $copyright_section_title,
    'bc_dedication'   => $dedication_section_title,
    'bc_preface'      => $preface_section_title,
    'bc_appendix'     => $appendix_section_title,
    'bc_bibliography' => $bibliography_section_title,
    'bc_author_note'  => $author_note_section_title,
);

// Author related metadata used in the header.
$primary_author = get_post_meta( $book_id, 'bc_author', true );
$coauthors      = get_post_meta( $book_id, 'bc_coauthors', true );

$chapters = bookcreator_get_ordered_chapters_for_book( $book_id );
$chapters_data = array();

if ( $chapters ) {
    $chapter_index = 0;

    foreach ( $chapters as $chapter ) {
        $chapter_index++;
        $chapter_title = get_the_title( $chapter );
        if ( '' === $chapter_title ) {
            $chapter_title = sprintf( $chapter_fallback_title, $chapter_index );
        }
        $paragraphs = bookcreator_get_ordered_paragraphs_for_chapter( $chapter->ID );
        $paragraphs_data = array();

        if ( $paragraphs ) {
            $paragraph_index = 0;

            foreach ( $paragraphs as $paragraph ) {
                $paragraph_index++;
                $paragraph_title = get_the_title( $paragraph );
                if ( '' === $paragraph_title ) {
                    $paragraph_title = sprintf( $paragraph_fallback_title, $chapter_index . '.' . $paragraph_index );
                }
                $paragraphs_data[] = array(
                    'post'   => $paragraph,
                    'number' => $chapter_index . '.' . $paragraph_index,
                    'title'  => $paragraph_title,
                );
            }
        }

        $chapters_data[] = array(
            'post'       => $chapter,
            'number'     => (string) $chapter_index,
            'title'      => $chapter_title,
            'paragraphs' => $paragraphs_data,
        );
    }
}

$book_index_markup = '';

if ( $chapters_data ) {
    ob_start();
    ?>
    <nav class="bookcreator-book__index">
        <h2><?php echo esc_html( $book_index_heading ); ?></h2>
        <ol>
            <?php foreach ( $chapters_data as $chapter_data ) :
                $chapter        = $chapter_data['post'];
                $chapter_number = $chapter_data['number'];
                $paragraphs     = $chapter_data['paragraphs'];
                $chapter_title  = isset( $chapter_data['title'] ) ? $chapter_data['title'] : get_the_title( $chapter );
                ?>
                <li>
                    <a href="#chapter-<?php echo esc_attr( $chapter->ID ); ?>"><?php echo esc_html( $chapter_number . ' ' . $chapter_title ); ?></a>
                    <?php if ( ! empty( $paragraphs ) ) : ?>
                        <ol>
                            <?php foreach ( $paragraphs as $paragraph_data ) :
                                $paragraph       = $paragraph_data['post'];
                                $paragraph_title = isset( $paragraph_data['title'] ) ? $paragraph_data['title'] : get_the_title( $paragraph );
                                ?>
                                <li>
                                    <a href="#paragraph-<?php echo esc_attr( $paragraph->ID ); ?>"><?php echo esc_html( $paragraph_data['number'] . ' ' . $paragraph_title ); ?></a>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php
    $book_index_markup = ob_get_clean();
}

get_header();
?>

<main id="primary" class="bookcreator-single">
    <article id="post-<?php the_ID(); ?>" <?php post_class( 'bookcreator-book' ); ?>>
        <header class="bookcreator-book__header">
            <?php if ( $primary_author || $coauthors ) : ?>
                <div class="bookcreator-book__authors">
                    <?php if ( $primary_author ) : ?>
                        <p class="bookcreator-book__author"><?php echo esc_html( $primary_author ); ?></p>
                    <?php endif; ?>
                    <?php if ( $coauthors ) : ?>
                        <p class="bookcreator-book__coauthors"><?php echo esc_html( $coauthors ); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <h1 class="bookcreator-book__title"><?php the_title(); ?></h1>
            <?php if ( $subtitle = get_post_meta( $book_id, 'bc_subtitle', true ) ) : ?>
                <p class="bookcreator-book__subtitle"><?php echo esc_html( $subtitle ); ?></p>
            <?php endif; ?>
        </header>

        <section class="bookcreator-book__details">
            <h2><?php esc_html_e( 'Dettagli del libro', 'bookcreator' ); ?></h2>
            <dl>
                <?php foreach ( $meta_fields as $field_key => $label ) :
                    $value = get_post_meta( $book_id, $field_key, true );
                    if ( ! $value ) {
                        continue;
                    }

                    if ( 'bc_pub_date' === $field_key ) {
                        $value = date_i18n( get_option( 'date_format' ), strtotime( $value ) );
                    } elseif ( 'bc_language' === $field_key && function_exists( 'bookcreator_get_language_label' ) ) {
                        $value = bookcreator_get_language_label( $value );
                    }
                    ?>
                    <?php if ( 'bc_publisher' === $field_key ) : ?>
                        <dd class="bookcreator-book__publisher"><?php echo esc_html( $value ); ?></dd>
                    <?php else : ?>
                        <dt><?php echo esc_html( $label ); ?></dt>
                        <dd><?php echo esc_html( $value ); ?></dd>
                    <?php endif; ?>
                <?php endforeach; ?>
            </dl>

            <?php
            $publisher_logo_id = get_post_meta( $book_id, 'bc_publisher_logo', true );
            $cover_id          = get_post_meta( $book_id, 'bc_cover', true );
            if ( $publisher_logo_id || $cover_id ) :
                ?>
                <div class="bookcreator-book__covers">
                    <?php if ( $publisher_logo_id ) : ?>
                        <figure class="bookcreator-book__publisher-logo">
                            <figcaption><?php esc_html_e( 'Logo editore', 'bookcreator' ); ?></figcaption>
                            <?php echo wp_get_attachment_image( $publisher_logo_id, 'medium', false, array( 'class' => 'bookcreator-frontispiece__publisher-logo-image' ) ); ?>
                        </figure>
                    <?php endif; ?>
                    <?php if ( $cover_id ) : ?>
                        <figure class="bookcreator-book__cover">
                            <figcaption><?php echo esc_html( $cover_caption_text ); ?></figcaption>
                            <?php echo wp_get_attachment_image( $cover_id, 'large' ); ?>
                        </figure>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php
        $index_rendered = false;

        foreach ( $rich_text_fields as $field_key => $label ) :
            $value = get_post_meta( $book_id, $field_key, true );
            if ( ! $value ) {
                continue;
            }
            ?>
            <section class="bookcreator-book__section bookcreator-book__section--<?php echo esc_attr( $field_key ); ?>">
                <h2><?php echo esc_html( $label ); ?></h2>
                <div class="bookcreator-book__content">
                    <?php echo wp_kses_post( apply_filters( 'the_content', $value ) ); ?>
                </div>
            </section>
            <?php
            if ( ! $index_rendered && 'bc_preface' === $field_key && $book_index_markup ) {
                echo wp_kses_post( $book_index_markup );
                $index_rendered = true;
            }
        endforeach;

        if ( $book_index_markup && ! $index_rendered ) {
            echo wp_kses_post( $book_index_markup );
        }
        ?>

        <?php if ( $chapters_data ) : ?>
            <section class="bookcreator-book__chapters">
                <?php foreach ( $chapters_data as $chapter_data ) :
                    $chapter        = $chapter_data['post'];
                    $chapter_number = $chapter_data['number'];
                    $paragraphs     = $chapter_data['paragraphs'];
                    $chapter_title  = isset( $chapter_data['title'] ) ? $chapter_data['title'] : get_the_title( $chapter );
                    ?>
                    <section id="chapter-<?php echo esc_attr( $chapter->ID ); ?>" class="bookcreator-chapter">
                        <h2 class="bookcreator-chapter__title"><?php echo esc_html( $chapter_number . ' ' . $chapter_title ); ?></h2>
                        <?php if ( $chapter->post_content ) : ?>
                            <h3 class="bookcreator-chapter__content-heading"><?php esc_html_e( 'Contenuto dei capitoli', 'bookcreator' ); ?></h3>
                            <div class="bookcreator-chapter__content">
                                <?php echo apply_filters( 'the_content', $chapter->post_content ); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ( ! empty( $paragraphs ) ) : ?>
                            <div class="bookcreator-chapter__paragraphs">
                                <?php foreach ( $paragraphs as $paragraph_data ) :
                                    $paragraph = $paragraph_data['post'];
                                    $footnotes = get_post_meta( $paragraph->ID, 'bc_footnotes', true );
                                    $citations = get_post_meta( $paragraph->ID, 'bc_citations', true );
                                    $paragraph_title = isset( $paragraph_data['title'] ) ? $paragraph_data['title'] : get_the_title( $paragraph );
                                    ?>
                                    <article id="paragraph-<?php echo esc_attr( $paragraph->ID ); ?>" class="bookcreator-paragraph">
                                        <h3 class="bookcreator-paragraph__title"><?php echo esc_html( $paragraph_data['number'] . ' ' . $paragraph_title ); ?></h3>
                                        <?php
                                        $paragraph_thumbnail_id = get_post_thumbnail_id( $paragraph );
                                        if ( $paragraph_thumbnail_id ) :
                                            $paragraph_thumbnail_html = wp_get_attachment_image( $paragraph_thumbnail_id, 'full', false, array( 'class' => 'bookcreator-paragraph__featured-image-img' ) );
                                            if ( $paragraph_thumbnail_html ) :
                                                ?>
                                                <figure class="bookcreator-paragraph__featured-image">
                                                    <?php echo $paragraph_thumbnail_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                                </figure>
                                                <?php
                                            endif;
                                        endif;
                                        ?>
                                        <div class="bookcreator-paragraph__content">
                                            <?php echo apply_filters( 'the_content', $paragraph->post_content ); ?>
                                        </div>
                                        <?php if ( $footnotes ) : ?>
                                            <footer class="bookcreator-paragraph__footnotes">
                                                <h4><?php echo esc_html( $footnotes_heading_text ); ?></h4>
                                                <div><?php echo apply_filters( 'the_content', $footnotes ); ?></div>
                                            </footer>
                                        <?php endif; ?>
                                        <?php if ( $citations ) : ?>
                                            <aside class="bookcreator-paragraph__citations">
                                                <h4><?php echo esc_html( $citations_heading_text ); ?></h4>
                                                <div><?php echo apply_filters( 'the_content', $citations ); ?></div>
                                            </aside>
                                        <?php endif; ?>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </article>
</main>

<?php
get_footer();

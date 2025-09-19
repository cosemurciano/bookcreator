<?php
/**
 * Template for displaying Book Creator custom post type.
 *
 * @package BookCreator
 */

global $post;

$book_id = $post->ID;

$meta_fields = array(
    'bc_subtitle'     => __( 'Sottotitolo', 'bookcreator' ),
    'bc_author'       => __( 'Autore principale', 'bookcreator' ),
    'bc_coauthors'    => __( 'Co-autori', 'bookcreator' ),
    'bc_publisher'    => __( 'Editore', 'bookcreator' ),
    'bc_isbn'         => __( 'ISBN', 'bookcreator' ),
    'bc_pub_date'     => __( 'Data di pubblicazione', 'bookcreator' ),
    'bc_edition'      => __( 'Edizione/Versione', 'bookcreator' ),
    'bc_language'     => __( 'Lingua', 'bookcreator' ),
    'bc_keywords'     => __( 'Parole chiave', 'bookcreator' ),
    'bc_audience'     => __( 'Pubblico', 'bookcreator' ),
);

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

$chapters = bookcreator_get_ordered_chapters_for_book( $book_id );

get_header();
?>

<main id="primary" class="bookcreator-single">
    <article id="post-<?php the_ID(); ?>" <?php post_class( 'bookcreator-book' ); ?>>
        <header class="bookcreator-book__header">
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

                    if ( 'bc_language' === $field_key ) {
                        $value = isset( $languages[ $value ] ) ? $languages[ $value ] : $value;
                    }

                    if ( 'bc_pub_date' === $field_key ) {
                        $value = date_i18n( get_option( 'date_format' ), strtotime( $value ) );
                    }
                    ?>
                    <dt><?php echo esc_html( $label ); ?></dt>
                    <dd><?php echo esc_html( $value ); ?></dd>
                <?php endforeach; ?>

                <?php
                $genres = get_the_terms( $book_id, 'book_genre' );
                if ( ! empty( $genres ) && ! is_wp_error( $genres ) ) :
                    ?>
                    <dt><?php esc_html_e( 'Generi', 'bookcreator' ); ?></dt>
                    <dd>
                        <?php echo esc_html( join( ', ', wp_list_pluck( $genres, 'name' ) ) ); ?>
                    </dd>
                <?php endif; ?>
            </dl>

            <?php
            $cover_id       = get_post_meta( $book_id, 'bc_cover', true );
            $retina_cover_id = get_post_meta( $book_id, 'bc_retina_cover', true );
            if ( $cover_id || $retina_cover_id ) :
                ?>
                <div class="bookcreator-book__covers">
                    <?php if ( $cover_id ) : ?>
                        <figure class="bookcreator-book__cover">
                            <figcaption><?php esc_html_e( 'Copertina', 'bookcreator' ); ?></figcaption>
                            <?php echo wp_get_attachment_image( $cover_id, 'large' ); ?>
                        </figure>
                    <?php endif; ?>
                    <?php if ( $retina_cover_id ) : ?>
                        <figure class="bookcreator-book__cover bookcreator-book__cover--retina">
                            <figcaption><?php esc_html_e( 'Copertina Retina Display', 'bookcreator' ); ?></figcaption>
                            <?php echo wp_get_attachment_image( $retina_cover_id, 'large' ); ?>
                        </figure>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php foreach ( $rich_text_fields as $field_key => $label ) :
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
        <?php endforeach; ?>

        <?php if ( $chapters ) : ?>
            <nav class="bookcreator-book__index">
                <h2><?php esc_html_e( 'Indice', 'bookcreator' ); ?></h2>
                <ol>
                    <?php foreach ( $chapters as $chapter ) :
                        $paragraphs = bookcreator_get_ordered_paragraphs_for_chapter( $chapter->ID );
                        ?>
                        <li>
                            <a href="#chapter-<?php echo esc_attr( $chapter->ID ); ?>"><?php echo esc_html( get_the_title( $chapter ) ); ?></a>
                            <?php if ( $paragraphs ) : ?>
                                <ol>
                                    <?php foreach ( $paragraphs as $paragraph ) : ?>
                                        <li>
                                            <a href="#paragraph-<?php echo esc_attr( $paragraph->ID ); ?>"><?php echo esc_html( get_the_title( $paragraph ) ); ?></a>
                                        </li>
                                    <?php endforeach; ?>
                                </ol>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </nav>
        <?php endif; ?>

        <?php if ( $chapters ) : ?>
            <section class="bookcreator-book__chapters">
                <?php foreach ( $chapters as $chapter ) :
                    $paragraphs = bookcreator_get_ordered_paragraphs_for_chapter( $chapter->ID );
                    ?>
                    <section id="chapter-<?php echo esc_attr( $chapter->ID ); ?>" class="bookcreator-chapter">
                        <h2 class="bookcreator-chapter__title"><?php echo esc_html( get_the_title( $chapter ) ); ?></h2>
                        <?php if ( $chapter->post_content ) : ?>
                            <div class="bookcreator-chapter__content">
                                <?php echo apply_filters( 'the_content', $chapter->post_content ); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ( $paragraphs ) : ?>
                            <div class="bookcreator-chapter__paragraphs">
                                <?php foreach ( $paragraphs as $paragraph ) :
                                    $footnotes = get_post_meta( $paragraph->ID, 'bc_footnotes', true );
                                    $citations = get_post_meta( $paragraph->ID, 'bc_citations', true );
                                    ?>
                                    <article id="paragraph-<?php echo esc_attr( $paragraph->ID ); ?>" class="bookcreator-paragraph">
                                        <h3 class="bookcreator-paragraph__title"><?php echo esc_html( get_the_title( $paragraph ) ); ?></h3>
                                        <div class="bookcreator-paragraph__content">
                                            <?php echo apply_filters( 'the_content', $paragraph->post_content ); ?>
                                        </div>
                                        <?php if ( $footnotes ) : ?>
                                            <footer class="bookcreator-paragraph__footnotes">
                                                <h4><?php esc_html_e( 'Note', 'bookcreator' ); ?></h4>
                                                <div><?php echo apply_filters( 'the_content', $footnotes ); ?></div>
                                            </footer>
                                        <?php endif; ?>
                                        <?php if ( $citations ) : ?>
                                            <aside class="bookcreator-paragraph__citations">
                                                <h4><?php esc_html_e( 'Citazioni', 'bookcreator' ); ?></h4>
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

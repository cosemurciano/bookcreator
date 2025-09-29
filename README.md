# bookcreator
Plugin WordPress per la creazione di ebook e pdf tramite custom post type WordPress.

La gestione dei libri avviene tramite l'editor nativo di WordPress:

1. **Nuovo libro**: `Post > Aggiungi nuovo` con tipo `Book`.
2. **Elenco libri**: `Post > Books`.
3. **Book Genre**: sezione tassonomia "Book Genre" associata al tipo `Book`.
4. **Paragrafi**: `Books > Paragraphs` per creare e gestire i paragrafi associati ai capitoli.

## Campi disponibili per i content type

### Libro (`post_type = book_creator`)

* `post_title` – Titolo del libro, testo semplice.
* `bc_subtitle` – Sottotitolo, supporta HTML di base.
* `bc_author` – Autore principale, testo semplice.
* `bc_coauthors` – Co-autori, testo semplice.
* `bc_publisher` – Editore, testo semplice.
* `bc_isbn` – Codice ISBN, testo semplice.
* `bc_pub_date` – Data di pubblicazione, testo semplice.
* `bc_edition` – Edizione/Versione, testo semplice.
* `bc_language` – Lingua dell’opera, testo semplice.
* `bc_description` – Descrizione del libro (HTML).
* `bc_frontispiece` – Frontespizio (HTML).
* `bc_copyright` – Sezione Copyright (HTML).
* `bc_dedication` – Dedica (HTML).
* `bc_preface` – Prefazione (HTML).
* `bc_acknowledgments` – Ringraziamenti (HTML).
* `bc_appendix` – Appendice (HTML).
* `bc_bibliography` – Bibliografia (HTML).
* `bc_author_note` – Nota dell’autore (HTML).

### Capitolo (`post_type = bc_chapter`)

* `post_title` – Titolo del capitolo, testo semplice.
* `post_content` – Contenuto del capitolo, editor WordPress.
* `bc_books` – Elenco di ID dei libri a cui il capitolo è associato (array di stringhe).
* `translations` – Dati delle traduzioni associate, archiviate in `bc_chapter_translations` (array per lingua con campi personalizzati e contenuti generati).

### Paragrafo (`post_type = bc_paragraph`)

* `post_title` – Titolo del paragrafo, testo semplice.
* `post_content` – Contenuto del paragrafo, editor WordPress.
* `bc_chapters` – Elenco di ID dei capitoli collegati (array di stringhe).
* `bc_books` – Elenco di ID dei libri derivato dai capitoli selezionati (array di stringhe).
* `bc_footnotes` – Note del paragrafo (HTML).
* `bc_citations` – Citazioni del paragrafo (HTML).
* `translations` – Dati delle traduzioni associate, archiviate in `bc_paragraph_translations` (array per lingua con campi personalizzati e contenuti generati).

### Indice (struttura generata)

L’indice del libro viene generato dinamicamente dalla funzione `bookcreator_build_book_index_from_export()` e produce una struttura ad albero con i seguenti campi:

* `title` – Titolo del capitolo o del paragrafo.
* `position` – Posizione numerica all’interno del libro.
* `number` – Numerazione formattata (es. `2.1`).
* `paragraphs` – Elenco di paragrafi appartenenti a ciascun capitolo; ogni paragrafo contiene `title`, `position` e `number`.

## Costruzione del file EPUB

La generazione dell’EPUB è gestita dal plugin stesso attraverso la funzione `bookcreator_create_epub_from_book()`, che crea la struttura standard (`mimetype`, `META-INF/container.xml`, cartella `OEBPS` con manifest e spine) e impacchetta il risultato tramite `ZipArchive`. Il sistema recupera automaticamente la lingua di esportazione in base alla richiesta, alla lingua del libro o alla lingua del sito e applica eventuali traduzioni disponibili per libro, capitoli e paragrafi prima di assemblare i contenuti nelle viste configurate dal Template/Designer EPUB.【F:bookcreator.php†L8744-L8773】【F:bookcreator.php†L9093-L9141】

Per popolare i metadati del pacchetto OPF il plugin legge i campi personalizzati del libro (`bc_isbn`, `bc_pub_date`, `bc_description`, `bc_publisher`, `bc_copyright`, `bc_subtitle`, `bc_frontispiece`, `bc_dedication`, `bc_preface`, `bc_acknowledgments`, `bc_appendix`, `bc_bibliography`, `bc_author_note`, `bc_edition`, `bc_author`, `bc_coauthors`) e le tassonomie (`book_genre`) collegate, generando l’identificativo, i dati editoriali e le sezioni opzionali come frontespizio, dedica, prefazione, ringraziamenti, appendici e bibliografia.【F:bookcreator.php†L8787-L9140】

Lo stile e l’ordine delle sezioni sono determinati dal Template EPUB selezionato: i settaggi del designer definiscono sia la sequenza degli elementi visibili sia le regole CSS generate (`bookcreator_generate_epub_designer_css_rules`), permettendo di adattare titoli, frontespizio, indice, contenuti dei capitoli, note, citazioni e altri blocchi al layout desiderato.【F:bookcreator.php†L8781-L8799】【F:bookcreator.php†L8278-L8326】【F:bookcreator.php†L9145-L9188】

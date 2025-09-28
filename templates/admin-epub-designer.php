<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$books_list_url = admin_url( 'edit.php?post_type=book_creator' );
?>
<style>
body.bookcreator-epub-designer-fullscreen {
    overflow: hidden;
}

.bookcreator-epub-designer-overlay,
.bookcreator-epub-designer-overlay *,
.bookcreator-epub-designer-overlay *::before,
.bookcreator-epub-designer-overlay *::after {
    box-sizing: border-box;
}

.bookcreator-epub-designer-overlay {
    position: fixed;
    inset: 0;
    z-index: 100000;
    background: #f8f9fa;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    height: 100vh;
    overflow: hidden;
    color: #111827;
}

.bookcreator-epub-designer-overlay .designer-container {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.bookcreator-epub-designer-overlay .header {
    background: #1e293b;
    color: #ffffff;
    padding: 12px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    z-index: 1000;
}

.bookcreator-epub-designer-overlay .header h1 {
    font-size: 18px;
    font-weight: 600;
    margin: 0;
}

.bookcreator-epub-designer-overlay .header-actions {
    display: flex;
    gap: 12px;
}

.bookcreator-epub-designer-overlay .btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: inherit;
}

.bookcreator-epub-designer-overlay .btn-primary {
    background: #3b82f6;
    color: #ffffff;
}

.bookcreator-epub-designer-overlay .btn-secondary {
    background: #6b7280;
    color: #ffffff;
}

.bookcreator-epub-designer-overlay .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.bookcreator-epub-designer-overlay .main-content {
    display: flex;
    flex: 1;
    overflow: hidden;
}

.bookcreator-epub-designer-overlay .left-sidebar {
    width: 280px;
    background: #ffffff;
    border-right: 1px solid #e5e7eb;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
}

.bookcreator-epub-designer-overlay .sidebar-header {
    padding: 16px;
    border-bottom: 1px solid #e5e7eb;
    background: #f8f9fa;
}

.bookcreator-epub-designer-overlay .sidebar-header h3 {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin: 0;
}

.bookcreator-epub-designer-overlay .fields-container {
    flex: 1;
    padding: 8px;
}

.bookcreator-epub-designer-overlay .field-category {
    margin-bottom: 16px;
}

.bookcreator-epub-designer-overlay .category-header {
    background: #f3f4f6;
    padding: 8px 12px;
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-radius: 4px;
    margin-bottom: 4px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.bookcreator-epub-designer-overlay .field-item {
    padding: 10px 12px;
    margin: 2px 0;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid transparent;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.bookcreator-epub-designer-overlay .field-item:hover {
    background: #f8f9fa;
}

.bookcreator-epub-designer-overlay .field-item.active {
    background: #eff6ff;
    border-color: #bfdbfe;
}

.bookcreator-epub-designer-overlay .field-item.active .field-info {
    color: #1d4ed8;
}

.bookcreator-epub-designer-overlay .epub-preview-field {
    position: relative;
    cursor: pointer;
    transition: outline-color 0.2s ease, outline-offset 0.2s ease, box-shadow 0.2s ease;
}

.bookcreator-epub-designer-overlay .epub-preview-field.is-selected {
    outline: 2px dashed #3b82f6;
    outline-offset: 4px;
}

.bookcreator-epub-designer-overlay .epub-preview-field.is-selected::after {
    content: 'Selezionato';
    position: absolute;
    top: -12px;
    left: -8px;
    background: #3b82f6;
    color: #ffffff;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 600;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    z-index: 2;
}

.bookcreator-epub-designer-overlay .field-info {
    flex: 1;
}

.bookcreator-epub-designer-overlay .field-actions {
    display: flex;
    gap: 4px;
    align-items: center;
    transition: opacity 0.2s;
}

.bookcreator-epub-designer-overlay .visibility-btn {
    width: 24px;
    height: 24px;
    border: none;
    background: none;
    cursor: pointer;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    transition: all 0.2s;
    color: #6b7280;
}

.bookcreator-epub-designer-overlay .visibility-btn:hover {
    background: #f3f4f6;
    color: #374151;
}

.bookcreator-epub-designer-overlay .visibility-btn.hidden {
    color: #dc2626;
}

.bookcreator-epub-designer-overlay .field-name {
    font-size: 13px;
    font-weight: 500;
}

.bookcreator-epub-designer-overlay .field-type {
    font-size: 11px;
    color: #6b7280;
    background: #f3f4f6;
    padding: 2px 6px;
    border-radius: 10px;
}

.bookcreator-epub-designer-overlay .canvas-area {
    flex: 1;
    background: #f8f9fa;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px;
    position: relative;
    overflow: hidden;
}

.bookcreator-epub-designer-overlay .canvas-title {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin: 0;
}

.bookcreator-epub-designer-overlay .canvas-header-area {
    width: 80%;
    max-width: 600px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.bookcreator-epub-designer-overlay .canvas-container {
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    position: relative;
    width: 80%;
    height: 85%;
    max-width: 600px;
    max-height: 900px;
}

.bookcreator-epub-designer-overlay .canvas-header {
    background: #f8f9fa;
    padding: 12px 16px;
    border-bottom: 1px solid #e5e7eb;
    border-radius: 8px 8px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.bookcreator-epub-designer-overlay .page-info {
    font-size: 12px;
    color: #6b7280;
}

.bookcreator-epub-designer-overlay .zoom-controls {
    display: flex;
    gap: 4px;
}

.bookcreator-epub-designer-overlay .zoom-btn {
    width: 24px;
    height: 24px;
    border: 1px solid #d1d5db;
    background: #ffffff;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
}

.bookcreator-epub-designer-overlay .preview-area {
    padding: 20px;
    height: calc(100% - 60px);
    overflow: auto;
    position: relative;
}

.bookcreator-epub-designer-overlay .preview-content {
    background: #ffffff;
    min-height: 100%;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    position: relative;
}

.bookcreator-epub-designer-overlay .right-sidebar {
    width: 320px;
    background: #ffffff;
    border-left: 1px solid #e5e7eb;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
}

.bookcreator-epub-designer-overlay .properties-header {
    padding: 16px;
    border-bottom: 1px solid #e5e7eb;
    background: #f8f9fa;
}

.bookcreator-epub-designer-overlay .properties-header h3 {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin: 0 0 4px;
}

.bookcreator-epub-designer-overlay .selected-field {
    font-size: 12px;
    color: #6b7280;
    background: #eff6ff;
    padding: 4px 8px;
    border-radius: 4px;
    display: inline-block;
}

.bookcreator-epub-designer-overlay .properties-content {
    padding: 16px;
    flex: 1;
}

.bookcreator-epub-designer-overlay .property-group {
    margin-bottom: 24px;
}

.bookcreator-epub-designer-overlay .property-group-title {
    font-size: 12px;
    font-weight: 600;
    color: #374151;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.bookcreator-epub-designer-overlay .property-row {
    margin-bottom: 12px;
}

.bookcreator-epub-designer-overlay .property-label {
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 4px;
    display: block;
}

.bookcreator-epub-designer-overlay .property-input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 13px;
    transition: border-color 0.2s;
}

.bookcreator-epub-designer-overlay .property-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.bookcreator-epub-designer-overlay .property-row-split {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.bookcreator-epub-designer-overlay .property-row-quad {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.bookcreator-epub-designer-overlay .color-input-wrapper {
    display: flex;
    gap: 8px;
    align-items: center;
}

.bookcreator-epub-designer-overlay .color-picker {
    width: 40px;
    height: 32px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    cursor: pointer;
}

.bookcreator-epub-designer-overlay .select-input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 13px;
    background: #ffffff;
    cursor: pointer;
}

.bookcreator-epub-designer-overlay .status-bar {
    background: #f8f9fa;
    border-top: 1px solid #e5e7eb;
    padding: 8px 20px;
    font-size: 12px;
    color: #6b7280;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.bookcreator-epub-designer-overlay .floating-panel {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    padding: 20px;
    z-index: 10000;
    display: none;
}
</style>
<div class="bookcreator-epub-designer-overlay" role="application" aria-label="<?php esc_attr_e( 'ePub Template Designer', 'bookcreator' ); ?>">
    <div class="designer-container">
        <div class="header">
            <div style="display: flex; align-items: center; gap: 16px;">
                <a class="btn btn-secondary" href="<?php echo esc_url( $books_list_url ); ?>">&#8592; WordPress</a>
                <h1>📚 ePub Template Designer</h1>
            </div>
            <div class="header-actions">
                <button type="button" class="btn btn-secondary">Anteprima</button>
                <button type="button" class="btn btn-secondary">Esporta Template</button>
                <button type="button" class="btn btn-primary">Salva Template</button>
            </div>
        </div>
        <div class="main-content">
            <div class="left-sidebar">
                <div class="sidebar-header">
                    <h3>Campi ePub</h3>
                </div>
                <div class="fields-container">
                    <div class="field-category">
                        <div class="category-header">
                            📖 Informazioni Libro
                            <span>▼</span>
                        </div>
                        <div class="field-item active" data-field-id="bc_author" data-field-name="Autore Principale">
                            <div class="field-info">
                                <div class="field-name">Autore Principale</div>
                                <div class="field-type">bc_author</div>
                            </div>
                            <div class="field-actions">
                                <button type="button" class="visibility-btn" title="Nascondi/Mostra campo">👁️</button>
                            </div>
                        </div>
                        <div class="field-item" data-field-id="bc_coauthors" data-field-name="Co-Autori">
                            <div class="field-info">
                                <div class="field-name">Co-Autori</div>
                                <div class="field-type">bc_coauthors</div>
                            </div>
                            <div class="field-actions">
                                <button type="button" class="visibility-btn" title="Nascondi/Mostra campo">👁️</button>
                            </div>
                        </div>
                        <div class="field-item" data-field-id="post_title" data-field-name="Titolo Libro">
                            <div class="field-info">
                                <div class="field-name">Titolo Libro</div>
                                <div class="field-type">post_title</div>
                            </div>
                            <div class="field-actions">
                                <button type="button" class="visibility-btn" title="Nascondi/Mostra campo">👁️</button>
                            </div>
                        </div>
                        <div class="field-item" data-field-id="bc_subtitle" data-field-name="Sottotitolo">
                            <div class="field-info">
                                <div class="field-name">Sottotitolo</div>
                                <div class="field-type">bc_subtitle</div>
                            </div>
                            <div class="field-actions">
                                <button type="button" class="visibility-btn" title="Nascondi/Mostra campo">👁️</button>
                            </div>
                        </div>
                        <div class="field-item" data-field-id="bc_publisher" data-field-name="Editore">
                            <div class="field-info">
                                <div class="field-name">Editore</div>
                                <div class="field-type">bc_publisher</div>
                            </div>
                            <div class="field-actions">
                                <button type="button" class="visibility-btn" title="Nascondi/Mostra campo">👁️</button>
                            </div>
                        </div>
                        <div class="field-item" data-field-id="publisher_image" data-field-name="Immagine Editore">
                            <div class="field-info">
                                <div class="field-name">Immagine Editore</div>
                                <div class="field-type">publisher_image</div>
                            </div>
                            <div class="field-actions">
                                <button type="button" class="visibility-btn" title="Nascondi/Mostra campo">👁️</button>
                            </div>
                        </div>
                    </div>
                    <div class="field-category">
                        <div class="category-header">
                            📄 Contenuti Preliminari
                            <span>▼</span>
                        </div>
                        <div class="field-item" data-field-id="bc_dedication" data-field-name="Dedica">
                            <div class="field-info">
                                <div class="field-name">Dedica</div>
                                <div class="field-type">bc_dedication</div>
                            </div>
                            <div class="field-actions">
                                <button type="button" class="visibility-btn" title="Nascondi/Mostra campo">👁️</button>
                            </div>
                        </div>
                        <div class="field-item" data-field-id="bc_preface" data-field-name="Prefazione">
                            <div class="field-info">
                                <div class="field-name">Prefazione</div>
                                <div class="field-type">bc_preface</div>
                            </div>
                            <div class="field-actions">
                                <button type="button" class="visibility-btn" title="Nascondi/Mostra campo">👁️</button>
                            </div>
                        </div>
                        <div class="field-item" data-field-id="bc_acknowledgments" data-field-name="Ringraziamenti">
                            <div class="field-info">
                                <div class="field-name">Ringraziamenti</div>
                                <div class="field-type">bc_acknowledgments</div>
                            </div>
                            <div class="field-actions">
                                <button type="button" class="visibility-btn" title="Nascondi/Mostra campo">👁️</button>
                            </div>
                        </div>
                        <div class="field-item" data-field-id="bc_description" data-field-name="Descrizione Libro">
                            <div class="field-info">
                                <div class="field-name">Descrizione Libro</div>
                                <div class="field-type">bc_description</div>
                            </div>
                            <div class="field-actions">
                                <button type="button" class="visibility-btn" title="Nascondi/Mostra campo">👁️</button>
                            </div>
                        </div>
                        <div class="field-item" data-field-id="bc_copyright" data-field-name="Sezione Copyright">
                            <div class="field-info">
                                <div class="field-name">Sezione Copyright</div>
                                <div class="field-type">bc_copyright</div>
                            </div>
                            <div class="field-actions">
                                <button type="button" class="visibility-btn" title="Nascondi/Mostra campo">👁️</button>
                            </div>
                        </div>
                        <div class="field-item" data-field-id="bc_isbn" data-field-name="Codice ISBN">
                            <div class="field-info">
                                <div class="field-name">Codice ISBN</div>
                                <div class="field-type">bc_isbn</div>
                            </div>
                            <div class="field-actions">
                                <button type="button" class="visibility-btn" title="Nascondi/Mostra campo">👁️</button>
                            </div>
                        </div>
                        <div class="field-item" data-field-id="table_of_contents" data-field-name="Indice">
                            <div class="field-info">
                                <div class="field-name">Indice</div>
                                <div class="field-type">table_of_contents</div>
                            </div>
                            <div class="field-actions">
                                <button type="button" class="visibility-btn" title="Nascondi/Mostra campo">👁️</button>
                            </div>
                        </div>
                    </div>
                    <div class="field-category">
                        <div class="category-header">
                            📚 Contenuto Principale
                            <span>▼</span>
                        </div>
                        <div class="field-item" data-field-id="chapter_title" data-field-name="Titolo Capitolo">
                            <div class="field-info">
                                <div class="field-name">Titolo Capitolo</div>
                                <div class="field-type">chapter_title</div>
                            </div>
                            <div class="field-actions">
                                <button type="button" class="visibility-btn" title="Nascondi/Mostra campo">👁️</button>
                            </div>
                        </div>
                        <div class="field-item" data-field-id="chapter_content" data-field-name="Contenuto Capitolo">
                            <div class="field-info">
                                <div class="field-name">Contenuto Capitolo</div>
                                <div class="field-type">chapter_content</div>
                            </div>
                            <div class="field-actions">
                                <button type="button" class="visibility-btn" title="Nascondi/Mostra campo">👁️</button>
                            </div>
                        </div>
                        <div class="field-item" data-field-id="paragraph_title" data-field-name="Titolo Paragrafo">
                            <div class="field-info">
                                <div class="field-name">Titolo Paragrafo</div>
                                <div class="field-type">paragraph_title</div>
                            </div>
                            <div class="field-actions">
                                <button type="button" class="visibility-btn" title="Nascondi/Mostra campo">👁️</button>
                            </div>
                        </div>
                        <div class="field-item" data-field-id="paragraph_content" data-field-name="Contenuto Paragrafo">
                            <div class="field-info">
                                <div class="field-name">Contenuto Paragrafo</div>
                                <div class="field-type">paragraph_content</div>
                            </div>
                            <div class="field-actions">
                                <button type="button" class="visibility-btn" title="Nascondi/Mostra campo">👁️</button>
                            </div>
                        </div>
                        <div class="field-item" data-field-id="bc_footnotes" data-field-name="Note del Paragrafo">
                            <div class="field-info">
                                <div class="field-name">Note del Paragrafo</div>
                                <div class="field-type">bc_footnotes</div>
                            </div>
                            <div class="field-actions">
                                <button type="button" class="visibility-btn" title="Nascondi/Mostra campo">👁️</button>
                            </div>
                        </div>
                        <div class="field-item" data-field-id="bc_citations" data-field-name="Citazioni del Paragrafo">
                            <div class="field-info">
                                <div class="field-name">Citazioni del Paragrafo</div>
                                <div class="field-type">bc_citations</div>
                            </div>
                            <div class="field-actions">
                                <button type="button" class="visibility-btn" title="Nascondi/Mostra campo">👁️</button>
                            </div>
                        </div>
                    </div>
                    <div class="field-category">
                        <div class="category-header">
                            📝 Contenuti Finali
                            <span>▼</span>
                        </div>
                        <div class="field-item" data-field-id="bc_appendix" data-field-name="Appendice">
                            <div class="field-info">
                                <div class="field-name">Appendice</div>
                                <div class="field-type">bc_appendix</div>
                            </div>
                            <div class="field-actions">
                                <button type="button" class="visibility-btn" title="Nascondi/Mostra campo">👁️</button>
                            </div>
                        </div>
                        <div class="field-item" data-field-id="bc_bibliography" data-field-name="Bibliografia">
                            <div class="field-info">
                                <div class="field-name">Bibliografia</div>
                                <div class="field-type">bc_bibliography</div>
                            </div>
                            <div class="field-actions">
                                <button type="button" class="visibility-btn" title="Nascondi/Mostra campo">👁️</button>
                            </div>
                        </div>
                        <div class="field-item" data-field-id="bc_author_note" data-field-name="Nota Autore">
                            <div class="field-info">
                                <div class="field-name">Nota Autore</div>
                                <div class="field-type">bc_author_note</div>
                            </div>
                            <div class="field-actions">
                                <button type="button" class="visibility-btn" title="Nascondi/Mostra campo">👁️</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="canvas-area">
                <div class="canvas-header-area">
                    <h3 class="canvas-title">Anteprima ePub</h3>
                    <button type="button" class="btn btn-primary" style="font-size: 12px; padding: 8px 16px;">Salva Stili</button>
                </div>
                <div class="canvas-container">
                    <div class="canvas-header">
                        <div class="page-info">Pagina 1 di 1 • Template ePub</div>
                        <div class="zoom-controls">
                            <div class="zoom-btn">-</div>
                            <div class="zoom-btn">100%</div>
                            <div class="zoom-btn">+</div>
                        </div>
                    </div>
                    <div class="preview-area">
                        <div class="preview-content" id="konva-container">
                            <div style="padding: 40px; line-height: 1.6; color: #000000; font-family: serif;">
                                <h1 class="epub-preview-field" data-field-id="bc_author" data-field-name="Autore Principale" style="font-size: 2rem; margin-bottom: 1rem;">
                                    Mario Rossi
                                </h1>
                                <p class="epub-preview-field" data-field-id="bc_coauthors" data-field-name="Co-Autori" style="font-size: 1rem; margin-bottom: 0.5rem;">
                                    Con Anna Bianchi, Giuseppe Verdi
                                </p>
                                <h1 class="epub-preview-field" data-field-id="post_title" data-field-name="Titolo Libro" style="font-size: 2.5rem; margin: 2rem 0 1rem 0;">
                                    Il Mistero della Biblioteca Perduta
                                </h1>
                                <h2 class="epub-preview-field" data-field-id="bc_subtitle" data-field-name="Sottotitolo" style="font-size: 1.5rem; margin-bottom: 2rem;">
                                    Un'avventura tra storia e leggenda
                                </h2>
                                <div class="epub-preview-field" data-field-id="publisher_image" data-field-name="Immagine Editore" style="margin: 2rem 0;">
                                    <div style="width: 120px; height: 80px; background: #f0f0f0; margin: 0 auto; display: flex; align-items: center; justify-content: center; font-size: 12px;">
                                        Logo Editore
                                    </div>
                                </div>
                                <p class="epub-preview-field" data-field-id="bc_publisher" data-field-name="Editore" style="font-size: 1rem; margin-bottom: 2rem;">Edizioni Mondadori</p>
                                <div class="epub-preview-field" data-field-id="bc_dedication" data-field-name="Dedica" style="font-size: 1rem; margin: 2rem 0;">
                                    A mia nonna, che mi ha insegnato l'amore per i libri e il mistero delle storie non ancora raccontate.
                                </div>
                                <div class="epub-preview-field" data-field-id="bc_preface" data-field-name="Prefazione" style="margin: 2rem 0;">
                                    <h3 style="font-size: 1.3rem; margin: 0 0 1rem 0;">Prefazione</h3>
                                    <p style="margin: 0 0 1.5rem 0; font-size: 1rem;">
                                        Questa storia nasce da una leggenda che ho sentito da bambino, seduto accanto al camino di casa mia.
                                        È il racconto di una biblioteca che esisteva secoli fa, custode di segreti che ancora oggi...
                                    </p>
                                </div>
                                <div class="epub-preview-field" data-field-id="bc_acknowledgments" data-field-name="Ringraziamenti" style="margin: 2rem 0;">
                                    <h3 style="font-size: 1.3rem; margin: 0 0 1rem 0;">Ringraziamenti</h3>
                                    <p style="margin: 0 0 1.5rem 0; font-size: 1rem;">
                                        Un ringraziamento speciale va ai bibliotecari dell'Archivio di Stato, senza i quali questa ricerca non sarebbe stata possibile...
                                    </p>
                                </div>
                                <div class="epub-preview-field" data-field-id="bc_description" data-field-name="Descrizione Libro" style="margin: 2rem 0;">
                                    <h4 style="font-size: 1.2rem; margin-bottom: 1rem;">Descrizione</h4>
                                    <p style="font-size: 1rem;">
                                        Un thriller storico che intreccia passato e presente in una caccia al tesoro intellettuale.
                                        Quando la giovane archivista Elena scopre un manoscritto medievale...
                                    </p>
                                </div>
                                <div class="epub-preview-field" data-field-id="bc_copyright" data-field-name="Sezione Copyright" style="font-size: 0.9rem; margin: 2rem 0;">
                                    <p>© 2024 Mario Rossi. Tutti i diritti riservati.</p>
                                    <p>Nessuna parte di questa pubblicazione può essere riprodotta senza autorizzazione scritta dell'editore.</p>
                                </div>
                                <p class="epub-preview-field" data-field-id="bc_isbn" data-field-name="Codice ISBN" style="font-size: 0.9rem; margin: 1rem 0;">ISBN: 978-88-04-12345-6</p>
                                <div class="epub-preview-field" data-field-id="table_of_contents" data-field-name="Indice" style="margin: 2rem 0;">
                                    <h4 style="font-size: 1.3rem; margin-bottom: 1rem;">Indice</h4>
                                    <div style="font-size: 1rem;">
                                        <p style="margin: 0.5rem 0;">Prefazione ........................... 3</p>
                                        <p style="margin: 0.5rem 0;">Capitolo 1: La Scoperta ............. 7</p>
                                        <p style="margin: 0.5rem 0;">Capitolo 2: Il Primo Indizio ........ 23</p>
                                        <p style="margin: 0.5rem 0;">Capitolo 3: La Biblioteca Segreta ... 41</p>
                                    </div>
                                </div>
                                <h2 class="epub-preview-field" data-field-id="chapter_title" data-field-name="Titolo Capitolo" style="font-size: 1.8rem; margin: 3rem 0 1.5rem 0;">
                                    Capitolo 1: La Scoperta
                                </h2>
                                <p class="epub-preview-field" data-field-id="chapter_content" data-field-name="Contenuto Capitolo" style="margin-bottom: 1.5rem; font-size: 1rem;">
                                    Era una mattina di novembre quando Elena Marchetti entrò per la prima volta nell'Archivio di Stato.
                                    L'odore di carta antica e polvere di secoli la avvolse come un mantello, e per un momento si sentì...
                                </p>
                                <h3 class="epub-preview-field" data-field-id="paragraph_title" data-field-name="Titolo Paragrafo" style="font-size: 1.2rem; margin: 2rem 0 1rem 0;">Il Manoscritto Misterioso</h3>
                                <p class="epub-preview-field" data-field-id="paragraph_content" data-field-name="Contenuto Paragrafo" style="margin-bottom: 1.5rem; font-size: 1rem;">
                                    Nel ripiano più alto dello scaffale, nascosto dietro una collezione di documenti del XVIII secolo,
                                    Elena notò qualcosa di insolito. Un volume rilegato in pelle scura, privo di titolo...
                                </p>
                                <div class="epub-preview-field" data-field-id="bc_footnotes" data-field-name="Note del Paragrafo" style="font-size: 0.85rem; margin: 1rem 0;">
                                    Gli archivi storici di Firenze custodiscono oltre 40.000 documenti medievali ancora inesplorati.
                                </div>
                                <div class="epub-preview-field" data-field-id="bc_citations" data-field-name="Citazioni del Paragrafo" style="margin: 1.5rem 0; font-size: 1rem;">
                                    "Il sapere è come una biblioteca: più libri aggiungi, più spazio sembra mancare per quelli che ancora devi scoprire."
                                    - Umberto Eco, Il Nome della Rosa
                                </div>
                                <div class="epub-preview-field" data-field-id="bc_appendix" data-field-name="Appendice" style="margin: 3rem 0;">
                                    <h2 style="font-size: 1.5rem; margin-bottom: 1.5rem;">Appendice A: Cronologia degli Eventi</h2>
                                    <p style="margin: 0.5rem 0; font-size: 1rem;">1347 - Fondazione della Biblioteca del Monastero</p>
                                    <p style="margin: 0.5rem 0; font-size: 1rem;">1398 - Prima menzione del manoscritto perduto</p>
                                    <p style="margin: 0.5rem 0; font-size: 1rem;">1456 - Chiusura definitiva della biblioteca</p>
                                </div>
                                <div class="epub-preview-field" data-field-id="bc_bibliography" data-field-name="Bibliografia" style="margin: 3rem 0;">
                                    <h2 style="font-size: 1.5rem; margin-bottom: 1.5rem;">Bibliografia</h2>
                                    <div style="font-size: 0.95rem;">
                                        <p style="margin: 0.8rem 0;">Alberti, L. B. (1435). De re aedificatoria. Firenze: Tipografia Medicea.</p>
                                        <p style="margin: 0.8rem 0;">Eco, U. (1980). Il nome della rosa. Milano: Bompiani.</p>
                                        <p style="margin: 0.8rem 0;">Manguel, A. (1996). Una storia della lettura. Milano: Mondadori.</p>
                                    </div>
                                </div>
                                <div class="epub-preview-field" data-field-id="bc_author_note" data-field-name="Nota Autore" style="margin: 3rem 0;">
                                    <h3 style="font-size: 1.3rem; margin-bottom: 1rem;">Nota dell'Autore</h3>
                                    <p style="font-size: 1rem;">
                                        Questo romanzo è frutto di anni di ricerca negli archivi storici italiani.
                                        Sebbene i personaggi siano immaginari, molti dei documenti e delle location descritte sono reali.
                                        Ringrazio tutti coloro che hanno reso possibile questo viaggio nella storia.
                                    </p>
                                    <p style="margin-top: 1rem; font-size: 1rem;">
                                        - Mario Rossi, Firenze 2024
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="right-sidebar">
                <div class="properties-header">
                    <h3>Proprietà Stile</h3>
                    <span class="selected-field">Nessun campo selezionato</span>
                </div>
                <div class="properties-content">
                    <div class="property-group" id="image-properties" style="display: none;">
                        <div class="property-group-title">
                            🖼️ Proprietà Immagine
                        </div>
                        <div class="property-row">
                            <label class="property-label">Allineamento</label>
                            <select class="select-input" data-style-property="text-align">
                                <option value="left">Left</option>
                                <option value="center">Center</option>
                                <option value="right">Right</option>
                            </select>
                        </div>
                        <div class="property-row">
                            <label class="property-label">Dimensione (%)</label>
                            <input type="number" class="property-input" value="100" min="10" max="100" step="5" data-style-property="width" data-style-unit="%">
                        </div>
                        <div class="property-row">
                            <label class="property-label">Margine Immagine (em)</label>
                            <div class="property-row-quad">
                                <div>
                                    <input type="number" class="property-input" placeholder="Top" value="1" step="0.1" data-style-property="margin-top" data-style-unit="em">
                                </div>
                                <div>
                                    <input type="number" class="property-input" placeholder="Right" value="0" step="0.1" data-style-property="margin-right" data-style-unit="em">
                                </div>
                                <div>
                                    <input type="number" class="property-input" placeholder="Bottom" value="1" step="0.1" data-style-property="margin-bottom" data-style-unit="em">
                                </div>
                                <div>
                                    <input type="number" class="property-input" placeholder="Left" value="0" step="0.1" data-style-property="margin-left" data-style-unit="em">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="property-group">
                        <div class="property-group-title">
                            🅰️ Tipografia
                        </div>
                        <div class="property-row">
                            <label class="property-label">Famiglia Font</label>
                            <select class="select-input" data-style-property="font-family">
                                <option value="Arial, sans-serif">Arial, sans-serif</option>
                                <option value="Georgia, serif">Georgia, serif</option>
                                <option value="\"Times New Roman\", serif">Times New Roman, serif</option>
                                <option value="Helvetica, sans-serif">Helvetica, sans-serif</option>
                            </select>
                        </div>
                        <div class="property-row-split">
                            <div>
                                <label class="property-label">Dimensione (rem)</label>
                                <input type="number" class="property-input" value="1.5" step="0.1" data-style-property="font-size" data-style-unit="rem">
                            </div>
                            <div>
                                <label class="property-label">Altezza Riga</label>
                                <input type="number" class="property-input" value="1.4" step="0.1" data-style-property="line-height">
                            </div>
                        </div>
                        <div class="property-row-split">
                            <div>
                                <label class="property-label">Peso Font</label>
                                <select class="select-input" data-style-property="font-weight">
                                    <option value="400">400 - Normal</option>
                                    <option value="600">600 - Semi Bold</option>
                                    <option value="700">700 - Bold</option>
                                </select>
                            </div>
                            <div>
                                <label class="property-label">Stile Font</label>
                                <select class="select-input" data-style-property="font-style">
                                    <option value="normal">Normal</option>
                                    <option value="italic">Italic</option>
                                </select>
                            </div>
                        </div>
                        <div class="property-row-split">
                            <div>
                                <label class="property-label">Sillabazione</label>
                                <select class="select-input" data-style-property="hyphens">
                                    <option value="auto">Auto</option>
                                    <option value="none">None</option>
                                    <option value="manual">Manual</option>
                                </select>
                            </div>
                            <div>
                                <label class="property-label">Allineamento</label>
                                <select class="select-input" data-style-property="text-align">
                                    <option value="left">Left</option>
                                    <option value="center">Center</option>
                                    <option value="right">Right</option>
                                    <option value="justify">Justify</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="property-group">
                        <div class="property-group-title">
                            🎨 Colori
                        </div>
                        <div class="property-row">
                            <label class="property-label">Colore Testo</label>
                            <div class="color-input-wrapper">
                                <div class="color-picker" style="background: #374151;"></div>
                                <input type="text" class="property-input" value="#374151" data-style-property="color">
                            </div>
                        </div>
                        <div class="property-row">
                            <label class="property-label">Colore Sfondo</label>
                            <div class="color-input-wrapper">
                                <div class="color-picker" style="background: transparent; border-style: dashed;"></div>
                                <input type="text" class="property-input" value="transparent" data-style-property="background-color">
                            </div>
                        </div>
                    </div>
                    <div class="property-group">
                        <div class="property-group-title">
                            🔲 Bordi
                        </div>
                        <div class="property-row">
                            <label class="property-label">Spessore Bordo (px)</label>
                            <div class="property-row-quad">
                                <div>
                                    <input type="number" class="property-input" placeholder="Top" value="0" min="0" max="10" data-style-property="border-top-width" data-style-unit="px">
                                </div>
                                <div>
                                    <input type="number" class="property-input" placeholder="Right" value="0" min="0" max="10" data-style-property="border-right-width" data-style-unit="px">
                                </div>
                                <div>
                                    <input type="number" class="property-input" placeholder="Bottom" value="0" min="0" max="10" data-style-property="border-bottom-width" data-style-unit="px">
                                </div>
                                <div>
                                    <input type="number" class="property-input" placeholder="Left" value="0" min="0" max="10" data-style-property="border-left-width" data-style-unit="px">
                                </div>
                            </div>
                        </div>
                        <div class="property-row">
                            <label class="property-label">Colore Bordo</label>
                            <div class="color-input-wrapper">
                                <div class="color-picker" style="background: #374151;"></div>
                                <input type="text" class="property-input" value="#374151" data-style-property="border-color">
                            </div>
                        </div>
                        <div class="property-row">
                            <label class="property-label">Stile Bordo</label>
                            <select class="select-input" data-style-property="border-style">
                                <option value="solid">solid</option>
                                <option value="dashed">dashed</option>
                                <option value="dotted">dotted</option>
                                <option value="double">double</option>
                                <option value="none">none</option>
                            </select>
                        </div>
                    </div>
                    <div class="property-group">
                        <div class="property-group-title">
                            📐 Spaziatura
                        </div>
                        <div class="property-row">
                            <label class="property-label">Margine (em)</label>
                            <div class="property-row-quad">
                                <div>
                                    <input type="number" class="property-input" placeholder="Top" value="1.5" step="0.1" data-style-property="margin-top" data-style-unit="em">
                                </div>
                                <div>
                                    <input type="number" class="property-input" placeholder="Right" value="0" step="0.1" data-style-property="margin-right" data-style-unit="em">
                                </div>
                                <div>
                                    <input type="number" class="property-input" placeholder="Bottom" value="1" step="0.1" data-style-property="margin-bottom" data-style-unit="em">
                                </div>
                                <div>
                                    <input type="number" class="property-input" placeholder="Left" value="0" step="0.1" data-style-property="margin-left" data-style-unit="em">
                                </div>
                            </div>
                        </div>
                        <div class="property-row">
                            <label class="property-label">Padding (em)</label>
                            <div class="property-row-quad">
                                <div>
                                    <input type="number" class="property-input" placeholder="Top" value="0" step="0.1" data-style-property="padding-top" data-style-unit="em">
                                </div>
                                <div>
                                    <input type="number" class="property-input" placeholder="Right" value="0" step="0.1" data-style-property="padding-right" data-style-unit="em">
                                </div>
                                <div>
                                    <input type="number" class="property-input" placeholder="Bottom" value="0" step="0.1" data-style-property="padding-bottom" data-style-unit="em">
                                </div>
                                <div>
                                    <input type="number" class="property-input" placeholder="Left" value="0" step="0.1" data-style-property="padding-left" data-style-unit="em">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="status-bar">
            <div>Campo selezionato: <span class="status-selected-field">Nessuno</span> • Modifiche non salvate</div>
            <div>Zoom: 100% • Canvas: 600x800px</div>
        </div>
    </div>
</div>
<script>
(function() {
    document.body.classList.add('bookcreator-epub-designer-fullscreen');

    var cleanup = function() {
        document.body.classList.remove('bookcreator-epub-designer-fullscreen');
        window.removeEventListener('beforeunload', cleanup);
        window.removeEventListener('pagehide', cleanup);
    };

    window.addEventListener('beforeunload', cleanup);
    window.addEventListener('pagehide', cleanup);

    var overlay = document.querySelector('.bookcreator-epub-designer-overlay');
    if (!overlay) {
        return;
    }

    var fieldItems = Array.prototype.slice.call(overlay.querySelectorAll('.field-item'));
    var selectedFieldLabel = overlay.querySelector('.selected-field');
    var statusSelectedField = overlay.querySelector('.status-selected-field');
    var imageProperties = overlay.querySelector('#image-properties');
    var previewFields = Array.prototype.slice.call(overlay.querySelectorAll('.epub-preview-field'));

    var fieldItemMap = {};
    fieldItems.forEach(function(item) {
        var fieldId = item.dataset.fieldId;
        if (!fieldId) {
            var typeNode = item.querySelector('.field-type');
            if (typeNode) {
                fieldId = typeNode.textContent.trim();
                item.dataset.fieldId = fieldId;
            }
        }
        var fieldName = item.dataset.fieldName;
        if (!fieldName) {
            var nameNode = item.querySelector('.field-name');
            if (nameNode) {
                fieldName = nameNode.textContent.trim();
                item.dataset.fieldName = fieldName;
            }
        }
        if (fieldId) {
            fieldItemMap[fieldId] = item;
        }
    });

    var previewFieldMap = {};
    previewFields.forEach(function(field) {
        var fieldId = field.getAttribute('data-field-id');
        if (!fieldId) {
            return;
        }
        if (!previewFieldMap[fieldId]) {
            previewFieldMap[fieldId] = [];
        }
        previewFieldMap[fieldId].push(field);
    });

    var currentFieldId = null;
    var currentPreviewNode = null;
    var lastPreviewByFieldId = {};

    function updateColorPreview(input) {
        if (!input || typeof input.closest !== 'function') {
            return;
        }
        var wrapper = input.closest('.color-input-wrapper');
        if (!wrapper) {
            return;
        }
        var picker = wrapper.querySelector('.color-picker');
        if (!picker) {
            return;
        }
        var value = (input.value || '').trim();
        if (!value) {
            value = 'transparent';
        }
        picker.style.background = value;
        picker.style.borderStyle = value.toLowerCase() === 'transparent' ? 'dashed' : 'solid';
    }

    var inputs = Array.prototype.slice.call(overlay.querySelectorAll('.property-input, .select-input'));
    inputs.forEach(function(input) {
        if (typeof input.dataset.defaultValue === 'undefined') {
            input.dataset.defaultValue = input.value;
        }
        updateColorPreview(input);
    });

    function updateSelectedFieldLabel(fieldId, fieldName) {
        if (selectedFieldLabel) {
            if (fieldId && fieldName) {
                if (fieldName === fieldId) {
                    selectedFieldLabel.textContent = fieldId;
                } else {
                    selectedFieldLabel.textContent = fieldId + ' - ' + fieldName;
                }
            } else if (fieldId) {
                selectedFieldLabel.textContent = fieldId;
            } else {
                selectedFieldLabel.textContent = 'Nessun campo selezionato';
            }
        }
        if (statusSelectedField) {
            statusSelectedField.textContent = fieldId || 'Nessuno';
        }
    }

    function toggleImageProperties(fieldId, fieldName) {
        if (!imageProperties) {
            return;
        }
        if (!fieldId) {
            imageProperties.style.display = 'none';
            return;
        }
        var label = (fieldName || '').toLowerCase();
        if (fieldId === 'publisher_image' || label.indexOf('immagine') !== -1) {
            imageProperties.style.display = 'block';
        } else {
            imageProperties.style.display = 'none';
        }
    }

    function clearPreviewSelection() {
        previewFields.forEach(function(field) {
            field.classList.remove('is-selected');
        });
    }

    function setActiveFieldItem(fieldId, scrollIntoView) {
        var active = overlay.querySelector('.field-item.active');
        if (active) {
            active.classList.remove('active');
        }
        if (!fieldId) {
            return;
        }
        var item = fieldItemMap[fieldId];
        if (item) {
            item.classList.add('active');
            if (scrollIntoView) {
                item.scrollIntoView({ block: 'nearest' });
            }
        }
    }

    function syncControlsWithField(previewElement) {
        inputs.forEach(function(input) {
            var property = input.dataset.styleProperty;
            if (!property) {
                return;
            }
            var unit = input.dataset.styleUnit || '';
            if (!previewElement) {
                if (typeof input.dataset.defaultValue !== 'undefined') {
                    input.value = input.dataset.defaultValue;
                    updateColorPreview(input);
                }
                return;
            }
            var inlineValue = previewElement.style.getPropertyValue(property);
            if (inlineValue) {
                var normalizedValue = inlineValue.trim();
                if (unit && normalizedValue.toLowerCase().endsWith(unit.toLowerCase())) {
                    normalizedValue = normalizedValue.slice(0, -unit.length);
                }
                if (input.tagName === 'SELECT') {
                    input.value = normalizedValue;
                } else if (input.type === 'number') {
                    var numericValue = parseFloat(normalizedValue);
                    if (!isNaN(numericValue)) {
                        input.value = numericValue;
                    }
                } else {
                    input.value = normalizedValue;
                }
            } else if (typeof input.dataset.defaultValue !== 'undefined') {
                input.value = input.dataset.defaultValue;
            }
            updateColorPreview(input);
        });
    }

    function selectField(fieldId, options) {
        options = options || {};
        if (!fieldId) {
            currentFieldId = null;
            currentPreviewNode = null;
            clearPreviewSelection();
            setActiveFieldItem(null, false);
            updateSelectedFieldLabel(null, null);
            toggleImageProperties(null, null);
            syncControlsWithField(null);
            return;
        }

        var previewElement = options.element;
        if (!previewElement) {
            previewElement = lastPreviewByFieldId[fieldId];
        }
        if (!previewElement && previewFieldMap[fieldId] && previewFieldMap[fieldId].length) {
            previewElement = previewFieldMap[fieldId][0];
        }

        clearPreviewSelection();
        if (previewElement) {
            previewElement.classList.add('is-selected');
            lastPreviewByFieldId[fieldId] = previewElement;
        }

        currentFieldId = fieldId;
        currentPreviewNode = previewElement || null;

        var fieldItem = fieldItemMap[fieldId];
        var fieldName = options.fieldName || (fieldItem ? fieldItem.dataset.fieldName : null);
        if (!fieldName && previewElement) {
            fieldName = previewElement.getAttribute('data-field-name');
        }

        updateSelectedFieldLabel(fieldId, fieldName || fieldId);
        toggleImageProperties(fieldId, fieldName);
        setActiveFieldItem(fieldId, options.scrollIntoView);
        syncControlsWithField(currentPreviewNode);
    }

    fieldItems.forEach(function(item) {
        item.addEventListener('click', function(event) {
            if (event.target.closest('.visibility-btn')) {
                return;
            }
            var fieldId = item.dataset.fieldId;
            if (!fieldId) {
                return;
            }
            selectField(fieldId, {
                fieldName: item.dataset.fieldName,
                scrollIntoView: false
            });
        });
    });

    previewFields.forEach(function(field) {
        field.addEventListener('click', function(event) {
            event.stopPropagation();
            var fieldId = field.getAttribute('data-field-id');
            if (!fieldId) {
                return;
            }
            selectField(fieldId, {
                element: field,
                fieldName: field.getAttribute('data-field-name'),
                scrollIntoView: true
            });
        });
    });

    var visibilityButtons = overlay.querySelectorAll('.visibility-btn');
    visibilityButtons.forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.stopPropagation();

            if (button.classList.contains('hidden')) {
                button.classList.remove('hidden');
                button.textContent = '👁️';
                button.title = 'Nascondi campo';
            } else {
                button.classList.add('hidden');
                button.textContent = '🙈';
                button.title = 'Mostra campo';
            }
        });
    });

    function applyStyleChange(input) {
        updateColorPreview(input);
        if (!currentPreviewNode) {
            return;
        }
        var property = input.dataset.styleProperty;
        if (!property) {
            return;
        }
        var unit = input.dataset.styleUnit || '';
        var rawValue = input.value;
        if (rawValue === '' || rawValue === null) {
            currentPreviewNode.style.removeProperty(property);
        } else {
            var finalValue = rawValue;
            if (unit) {
                finalValue = rawValue + unit;
            }
            currentPreviewNode.style.setProperty(property, finalValue);
        }
    }

    inputs.forEach(function(input) {
        var handler = function() {
            applyStyleChange(input);
        };
        if (input.tagName === 'SELECT') {
            input.addEventListener('change', handler);
        } else {
            input.addEventListener('input', handler);
            input.addEventListener('change', handler);
        }
    });

    var initialActive = overlay.querySelector('.field-item.active');
    var initialFieldId = initialActive ? initialActive.dataset.fieldId : (previewFields[0] ? previewFields[0].getAttribute('data-field-id') : null);
    if (initialFieldId) {
        selectField(initialFieldId, { scrollIntoView: false });
    } else {
        updateSelectedFieldLabel(null, null);
        toggleImageProperties(null, null);
        syncControlsWithField(null);
    }

    var saveStylesButton = overlay.querySelector('.canvas-header-area .btn-primary');
    if (saveStylesButton) {
        saveStylesButton.addEventListener('click', function() {
            if (!currentFieldId) {
                window.alert('Seleziona prima un campo da salvare');
                return;
            }

            var originalText = saveStylesButton.textContent;
            saveStylesButton.textContent = '✓ Salvato';
            saveStylesButton.style.background = '#10b981';
            window.setTimeout(function() {
                saveStylesButton.textContent = originalText;
                saveStylesButton.style.background = '#3b82f6';
            }, 1500);
        });
    }
})();
</script>

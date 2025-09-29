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
    color: #ffffff;
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
    align-items: flex-start;
    gap: 16px;
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

.bookcreator-epub-designer-overlay .canvas-meta {
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex: 1;
}

.bookcreator-epub-designer-overlay .template-name-field {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.bookcreator-epub-designer-overlay .template-name-field label {
    font-size: 12px;
    font-weight: 600;
    color: #374151;
}

.bookcreator-epub-designer-overlay .template-name-input {
    padding: 6px 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 13px;
    width: 100%;
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

.bookcreator-epub-designer-overlay .preview-content p {
    font-size: inherit;
    line-height: inherit;
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
    flex-direction: column;
    gap: 6px;
    position: relative;
}

.bookcreator-epub-designer-overlay .color-preview-row {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}

.bookcreator-epub-designer-overlay .color-preview-swatch {
    width: 36px;
    height: 30px;
    border-radius: 6px;
    border: 1px solid #d1d5db;
    background-image: linear-gradient(45deg, #f3f4f6 25%, transparent 25%),
        linear-gradient(-45deg, #f3f4f6 25%, transparent 25%),
        linear-gradient(45deg, transparent 75%, #f3f4f6 75%),
        linear-gradient(-45deg, transparent 75%, #f3f4f6 75%);
    background-size: 10px 10px;
    background-position: 0 0, 0 5px, 5px -5px, -5px 0px;
    position: relative;
    overflow: hidden;
}

.bookcreator-epub-designer-overlay .color-preview-swatch::after {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--bookcreator-preview-color, transparent);
}

.bookcreator-epub-designer-overlay .color-value-text {
    font-size: 12px;
    color: #374151;
    font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    word-break: break-all;
}

.bookcreator-epub-designer-overlay .bookcreator-kanva-colorpicker-popover {
    position: fixed;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 24px 48px rgba(15, 23, 42, 0.18);
    padding: 14px 16px;
    width: auto;
    min-width: 248px;
    max-width: min(360px, calc(100vw - 24px));
    display: none;
    flex-direction: column;
    gap: 12px;
    z-index: 100001;
}

.bookcreator-epub-designer-overlay .bookcreator-kanva-colorpicker-popover.is-visible {
    display: flex;
}

.bookcreator-epub-designer-overlay .bookcreator-kanva-colorpicker-popover .kanva-colorpicker-title {
    font-size: 13px;
    font-weight: 600;
    color: #1f2937;
}

.bookcreator-epub-designer-overlay .bookcreator-kanva-colorpicker-popover .kanva-colorpicker-stage {
    width: 100%;
    height: auto;
    align-self: center;
}

.bookcreator-epub-designer-overlay .bookcreator-kanva-colorpicker-popover .kanva-colorpicker-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.bookcreator-epub-designer-overlay .bookcreator-kanva-colorpicker-popover .colorpicker-btn {
    flex: 1;
    padding: 6px 10px;
    border-radius: 6px;
    border: none;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
}

.bookcreator-epub-designer-overlay .bookcreator-kanva-colorpicker-popover .colorpicker-btn--transparent {
    background: #e0f2fe;
    color: #0369a1;
}

.bookcreator-epub-designer-overlay .bookcreator-kanva-colorpicker-popover.is-transparent .colorpicker-btn--transparent {
    box-shadow: inset 0 0 0 2px rgba(3, 105, 161, 0.35);
}

.bookcreator-epub-designer-overlay .bookcreator-kanva-colorpicker-popover .colorpicker-btn--clear {
    background: #f3f4f6;
    color: #1f2937;
}

.bookcreator-epub-designer-overlay .bookcreator-kanva-colorpicker-popover .colorpicker-btn--close {
    background: #3b82f6;
    color: #ffffff;
}

.bookcreator-epub-designer-overlay .bookcreator-kanva-colorpicker-popover .colorpicker-btn:hover {
    filter: brightness(0.95);
}

.bookcreator-epub-designer-overlay .preview-content {
    transform-origin: top center;
}

.bookcreator-epub-designer-overlay .konva-overlay {
    position: absolute;
    inset: 0;
    pointer-events: none;
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
<div class="bookcreator-epub-designer-overlay" role="application" aria-label="<?php esc_attr_e( 'ePub Template Designer', 'bookcreator' ); ?>" data-empty-color-label="<?php esc_attr_e( 'Nessun colore', 'bookcreator' ); ?>" data-transparent-color-label="<?php esc_attr_e( 'Trasparente', 'bookcreator' ); ?>" data-clear-color-label="<?php esc_attr_e( 'Rimuovi colore', 'bookcreator' ); ?>" data-close-color-picker-label="<?php esc_attr_e( 'Chiudi selettore', 'bookcreator' ); ?>" data-color-picker-title="<?php esc_attr_e( 'Seleziona colore', 'bookcreator' ); ?>">
    <div class="designer-container">
        <div class="header">
            <div style="display: flex; align-items: center; gap: 16px;">
                <a class="btn btn-secondary" href="<?php echo esc_url( $books_list_url ); ?>">&#8592; WordPress</a>
                <h1>📚 ePub Template Designer</h1>
            </div>
            <div class="header-actions">
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
                    <div class="canvas-meta">
                        <div class="template-name-field">
                            <label for="bookcreator-template-name">Titolo Template</label>
                            <input type="text" id="bookcreator-template-name" class="template-name-input" placeholder="Inserisci il nome del template">
                        </div>
                        <h3 class="canvas-title">Anteprima ePub</h3>
                    </div>
                </div>
                <div class="canvas-container">
                    <div class="canvas-header">
                        <div class="page-info">Pagina 1 di 1 • Template ePub</div>
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
                                <div class="epub-preview-field" data-field-id="bc_preface" data-field-name="Prefazione">
                                    <p>
                                        Questa storia nasce da una leggenda che ho sentito da bambino, seduto accanto al camino di casa mia.
                                        È il racconto di una biblioteca che esisteva secoli fa, custode di segreti che ancora oggi...
                                    </p>
                                </div>
                                <div class="epub-preview-field" data-field-id="bc_acknowledgments" data-field-name="Ringraziamenti">
                                    <p>
                                        Un ringraziamento speciale va ai bibliotecari dell'Archivio di Stato, senza i quali questa ricerca non sarebbe stata possibile...
                                    </p>
                                </div>
                                <div class="epub-preview-field" data-field-id="bc_description" data-field-name="Descrizione Libro">
                                    <p>
                                        Un thriller storico che intreccia passato e presente in una caccia al tesoro intellettuale.
                                        Quando la giovane archivista Elena scopre un manoscritto medievale...
                                    </p>
                                </div>
                                <div class="epub-preview-field" data-field-id="bc_copyright" data-field-name="Sezione Copyright">
                                    <p>© 2024 Mario Rossi. Tutti i diritti riservati.</p>
                                    <p>Nessuna parte di questa pubblicazione può essere riprodotta senza autorizzazione scritta dell'editore.</p>
                                </div>
                                <p class="epub-preview-field" data-field-id="bc_isbn" data-field-name="Codice ISBN" style="font-size: 0.9rem; margin: 1rem 0;">ISBN: 978-88-04-12345-6</p>
                                <div class="epub-preview-field" data-field-id="table_of_contents" data-field-name="Indice">
                                    <div>
                                        <p>Prefazione ........................... 3</p>
                                        <p>Capitolo 1: La Scoperta ............. 7</p>
                                        <p>Capitolo 2: Il Primo Indizio ........ 23</p>
                                        <p>Capitolo 3: La Biblioteca Segreta ... 41</p>
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
                                <div class="epub-preview-field" data-field-id="bc_appendix" data-field-name="Appendice">
                                    <p>1347 - Fondazione della Biblioteca del Monastero</p>
                                    <p>1398 - Prima menzione del manoscritto perduto</p>
                                    <p>1456 - Chiusura definitiva della biblioteca</p>
                                </div>
                                <div class="epub-preview-field" data-field-id="bc_bibliography" data-field-name="Bibliografia" style="margin: 3rem 0;">
                                    <div>
                                        <p>Alberti, L. B. (1435). De re aedificatoria. Firenze: Tipografia Medicea.</p>
                                        <p>Eco, U. (1980). Il nome della rosa. Milano: Bompiani.</p>
                                        <p>Manguel, A. (1996). Una storia della lettura. Milano: Mondadori.</p>
                                    </div>
                                </div>
                                <div class="epub-preview-field" data-field-id="bc_author_note" data-field-name="Nota Autore">
                                    <p>
                                        Questo romanzo è frutto di anni di ricerca negli archivi storici italiani.
                                        Sebbene i personaggi siano immaginari, molti dei documenti e delle location descritte sono reali.
                                        Ringrazio tutti coloro che hanno reso possibile questo viaggio nella storia.
                                    </p>
                                    <p>
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
                                <input type="text" class="property-input color-picker-field" value="#374151" data-style-property="color" data-color-control="true">
                            </div>
                        </div>
                        <div class="property-row">
                            <label class="property-label">Colore Sfondo</label>
                            <div class="color-input-wrapper">
                                <input type="text" class="property-input color-picker-field" value="transparent" data-style-property="background-color" data-color-control="true">
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
                                <input type="text" class="property-input color-picker-field" value="#374151" data-style-property="border-color" data-color-control="true">
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
            <div>Canvas: 600x800px</div>
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
    var previewArea = overlay.querySelector('.preview-area');
    var previewContent = overlay.querySelector('.preview-content');
    var activeColorInput = null;

    var konvaOverlayContainer = null;
    var konvaStage = null;
    var konvaLayer = null;
    var konvaFrame = null;

    if (previewArea) {
        konvaOverlayContainer = document.createElement('div');
        konvaOverlayContainer.className = 'konva-overlay';
        previewArea.appendChild(konvaOverlayContainer);
    }

    if (konvaOverlayContainer && window.Konva) {
        konvaStage = new Konva.Stage({
            container: konvaOverlayContainer,
            width: konvaOverlayContainer.clientWidth,
            height: konvaOverlayContainer.clientHeight,
            listening: false
        });
        konvaLayer = new Konva.Layer({ listening: false });
        konvaStage.add(konvaLayer);
        konvaFrame = new Konva.Rect({
            x: 0,
            y: 0,
            width: konvaStage.width(),
            height: konvaStage.height(),
            stroke: '#3b82f6',
            strokeWidth: 1,
            dash: [6, 4],
            opacity: 0.35,
            listening: false
        });
        konvaLayer.add(konvaFrame);
        konvaLayer.draw();
    }

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
    function toHexColor(value) {
        if (!value) {
            return '#000000';
        }
        var normalized = value.trim();
        var shortHexMatch = normalized.match(/^#([0-9a-fA-F]{3})$/);
        if (shortHexMatch) {
            var hex = shortHexMatch[1];
            return '#' + hex.split('').map(function(ch) { return ch + ch; }).join('');
        }
        var longHexMatch = normalized.match(/^#([0-9a-fA-F]{6})$/);
        if (longHexMatch) {
            return '#' + longHexMatch[1].toLowerCase();
        }
        var probe = document.createElement('div');
        probe.style.display = 'none';
        probe.style.color = normalized;
        document.body.appendChild(probe);
        var computed = window.getComputedStyle(probe).color;
        document.body.removeChild(probe);
        var rgbMatch = computed.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)/i);
        if (!rgbMatch) {
            return '#000000';
        }
        var toHex = function(component) {
            var hexComponent = parseInt(component, 10).toString(16);
            return hexComponent.length === 1 ? '0' + hexComponent : hexComponent;
        };
        return '#' + [rgbMatch[1], rgbMatch[2], rgbMatch[3]].map(toHex).join('');
    }

    function parseColorValue(value) {
        var normalized = (value || '').trim();
        if (!normalized) {
            return { hex: '', finalValue: '' };
        }
        if (normalized.toLowerCase() === 'transparent') {
            return { hex: '', finalValue: 'transparent' };
        }
        var rgbaMatch = normalized.match(/^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)(?:\s*,\s*(\d*\.?\d+))?\s*\)$/i);
        if (rgbaMatch) {
            var r = Math.min(255, parseInt(rgbaMatch[1], 10));
            var g = Math.min(255, parseInt(rgbaMatch[2], 10));
            var b = Math.min(255, parseInt(rgbaMatch[3], 10));
            var hex = '#' + [r, g, b].map(function(component) {
                var hexComponent = component.toString(16);
                return hexComponent.length === 1 ? '0' + hexComponent : hexComponent;
            }).join('');
            return {
                hex: hex,
                finalValue: hex
            };
        }
        var hexValue = toHexColor(normalized || '#000000');
        return {
            hex: hexValue,
            finalValue: hexValue
        };
    }

    function clamp(value, min, max) {
        if (typeof value !== 'number') {
            value = parseFloat(value);
        }
        if (isNaN(value)) {
            return min;
        }
        return Math.min(Math.max(value, min), max);
    }

    function hexToRgb(hex) {
        if (!hex) {
            return { r: 0, g: 0, b: 0 };
        }
        var normalized = hex.replace('#', '');
        if (normalized.length === 3) {
            normalized = normalized.split('').map(function(ch) { return ch + ch; }).join('');
        }
        var intVal = parseInt(normalized, 16);
        if (isNaN(intVal)) {
            return { r: 0, g: 0, b: 0 };
        }
        return {
            r: (intVal >> 16) & 255,
            g: (intVal >> 8) & 255,
            b: intVal & 255
        };
    }

    function rgbToHex(r, g, b) {
        var toHex = function(component) {
            var value = clamp(Math.round(component), 0, 255);
            var hexComponent = value.toString(16);
            return hexComponent.length === 1 ? '0' + hexComponent : hexComponent;
        };
        return '#' + [r, g, b].map(toHex).join('');
    }

    function rgbToHsv(r, g, b) {
        var rr = clamp(r, 0, 255) / 255;
        var gg = clamp(g, 0, 255) / 255;
        var bb = clamp(b, 0, 255) / 255;

        var max = Math.max(rr, gg, bb);
        var min = Math.min(rr, gg, bb);
        var delta = max - min;

        var h = 0;
        if (delta !== 0) {
            if (max === rr) {
                h = 60 * (((gg - bb) / delta) % 6);
            } else if (max === gg) {
                h = 60 * (((bb - rr) / delta) + 2);
            } else {
                h = 60 * (((rr - gg) / delta) + 4);
            }
        }
        if (h < 0) {
            h += 360;
        }

        var s = max === 0 ? 0 : delta / max;
        var v = max;

        return { h: h, s: s, v: v };
    }

    function hsvToRgb(h, s, v) {
        h = ((h % 360) + 360) % 360;
        s = clamp(s, 0, 1);
        v = clamp(v, 0, 1);

        var c = v * s;
        var x = c * (1 - Math.abs(((h / 60) % 2) - 1));
        var m = v - c;

        var rPrime = 0;
        var gPrime = 0;
        var bPrime = 0;

        if (h >= 0 && h < 60) {
            rPrime = c;
            gPrime = x;
        } else if (h >= 60 && h < 120) {
            rPrime = x;
            gPrime = c;
        } else if (h >= 120 && h < 180) {
            gPrime = c;
            bPrime = x;
        } else if (h >= 180 && h < 240) {
            gPrime = x;
            bPrime = c;
        } else if (h >= 240 && h < 300) {
            rPrime = x;
            bPrime = c;
        } else {
            rPrime = c;
            bPrime = x;
        }

        return {
            r: (rPrime + m) * 255,
            g: (gPrime + m) * 255,
            b: (bPrime + m) * 255
        };
    }

    function hsvToHex(h, s, v) {
        var rgb = hsvToRgb(h, s, v);
        return rgbToHex(rgb.r, rgb.g, rgb.b);
    }

    function createSharedKonvaColorPicker(overlayElement, options) {
        options = options || {};
        if (!overlayElement || !window.Konva) {
            return null;
        }

        var transparentLabel = options.transparentLabel || 'Transparent';
        var clearLabel = options.clearLabel || 'Clear';
        var closeLabel = options.closeLabel || 'Close';
        var titleLabel = options.titleLabel || '';

        var config = {
            padding: 12,
            hueHeight: 16,
            hueSpacing: 20,
            margin: 12,
            minSquareSize: 200,
            maxSquareSize: 320,
            squareSize: 200,
            stageWidth: 0,
            stageHeight: 0
        };

        function resolveSquareSize() {
            var overlayWidth = overlayElement && overlayElement.clientWidth ? overlayElement.clientWidth : 0;
            var viewportWidth = window.innerWidth || overlayWidth;
            var availableWidth = Math.max(overlayWidth, viewportWidth) - (config.margin * 2);
            if (!availableWidth || !isFinite(availableWidth)) {
                availableWidth = config.minSquareSize + config.padding * 2;
            }
            var desiredSquare = availableWidth - (config.padding * 2);
            return clamp(desiredSquare, config.minSquareSize, config.maxSquareSize);
        }

        config.squareSize = resolveSquareSize();
        config.stageWidth = config.padding * 2 + config.squareSize;
        config.stageHeight = config.padding * 2 + config.squareSize + config.hueSpacing + config.hueHeight;

        var container = document.createElement('div');
        container.className = 'bookcreator-kanva-colorpicker-popover';
        container.setAttribute('aria-hidden', 'true');
        container.setAttribute('role', 'dialog');

        if (titleLabel) {
            var title = document.createElement('div');
            title.className = 'kanva-colorpicker-title';
            title.textContent = titleLabel;
            container.appendChild(title);
        }

        var stageWrapper = document.createElement('div');
        stageWrapper.className = 'kanva-colorpicker-stage';
        stageWrapper.style.width = config.stageWidth + 'px';
        stageWrapper.style.height = config.stageHeight + 'px';
        container.appendChild(stageWrapper);

        var actions = document.createElement('div');
        actions.className = 'kanva-colorpicker-actions';

        var transparentButton = document.createElement('button');
        transparentButton.type = 'button';
        transparentButton.className = 'colorpicker-btn colorpicker-btn--transparent';
        transparentButton.textContent = transparentLabel;
        actions.appendChild(transparentButton);

        var clearButton = document.createElement('button');
        clearButton.type = 'button';
        clearButton.className = 'colorpicker-btn colorpicker-btn--clear';
        clearButton.textContent = clearLabel;
        actions.appendChild(clearButton);

        var closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'colorpicker-btn colorpicker-btn--close';
        closeButton.textContent = closeLabel;
        actions.appendChild(closeButton);

        container.appendChild(actions);
        overlayElement.appendChild(container);

        container.addEventListener('mousedown', function(event) {
            event.stopPropagation();
        });
        container.addEventListener('click', function(event) {
            event.stopPropagation();
        });

        var stage = new Konva.Stage({
            container: stageWrapper,
            width: config.stageWidth,
            height: config.stageHeight
        });
        var layer = new Konva.Layer();
        stage.add(layer);

        var hue = 0;
        var saturation = 1;
        var value = 1;
        var isDraggingSat = false;
        var isDraggingHue = false;
        var activeInput = null;
        var ignoreCloseOnce = false;

        var saturationRect = new Konva.Rect({
            x: config.padding,
            y: config.padding,
            width: config.squareSize,
            height: config.squareSize,
            fill: '#ff0000',
            listening: true
        });
        layer.add(saturationRect);

        var whiteGradient = new Konva.Rect({
            x: config.padding,
            y: config.padding,
            width: config.squareSize,
            height: config.squareSize,
            listening: false,
            fillLinearGradientStartPoint: { x: 0, y: 0 },
            fillLinearGradientEndPoint: { x: config.squareSize, y: 0 },
            fillLinearGradientColorStops: [0, 'rgba(255,255,255,1)', 1, 'rgba(255,255,255,0)']
        });
        layer.add(whiteGradient);

        var blackGradient = new Konva.Rect({
            x: config.padding,
            y: config.padding,
            width: config.squareSize,
            height: config.squareSize,
            listening: false,
            fillLinearGradientStartPoint: { x: 0, y: 0 },
            fillLinearGradientEndPoint: { x: 0, y: config.squareSize },
            fillLinearGradientColorStops: [0, 'rgba(0,0,0,0)', 1, 'rgba(0,0,0,1)']
        });
        layer.add(blackGradient);

        var saturationCursor = new Konva.Circle({
            x: config.padding + config.squareSize,
            y: config.padding,
            radius: Math.max(8, Math.round(config.squareSize * 0.05)),
            stroke: '#ffffff',
            strokeWidth: Math.max(2, Math.round(config.squareSize * 0.015)),
            fill: 'transparent',
            shadowColor: 'rgba(15, 23, 42, 0.35)',
            shadowBlur: 4,
            shadowOpacity: 0.6
        });
        layer.add(saturationCursor);

        var hueY = config.padding + config.squareSize + config.hueSpacing;
        var hueRect = new Konva.Rect({
            x: config.padding,
            y: hueY,
            width: config.squareSize,
            height: config.hueHeight,
            listening: true,
            fillLinearGradientStartPoint: { x: 0, y: 0 },
            fillLinearGradientEndPoint: { x: config.squareSize, y: 0 },
            fillLinearGradientColorStops: [
                0, '#ff0000',
                0.17, '#ffff00',
                0.34, '#00ff00',
                0.51, '#00ffff',
                0.68, '#0000ff',
                0.85, '#ff00ff',
                1, '#ff0000'
            ]
        });
        layer.add(hueRect);

        var hueCursor = new Konva.Rect({
            x: config.padding - 2,
            y: hueY - 3,
            width: Math.max(4, Math.round(config.squareSize * 0.015)),
            height: config.hueHeight + 6,
            fill: '#ffffff',
            stroke: '#1f2937',
            strokeWidth: 1,
            cornerRadius: 2,
            shadowColor: 'rgba(15, 23, 42, 0.35)',
            shadowBlur: 3,
            listening: false
        });
        layer.add(hueCursor);

        layer.draw();
        recalculateDimensions();

        function recalculateDimensions() {
            var newSquareSize = resolveSquareSize();
            if (!isFinite(newSquareSize)) {
                return;
            }
            if (Math.abs(newSquareSize - config.squareSize) < 1) {
                if (container.classList.contains('is-visible')) {
                    updatePosition();
                }
                return;
            }

            config.squareSize = newSquareSize;
            config.stageWidth = config.padding * 2 + config.squareSize;
            config.stageHeight = config.padding * 2 + config.squareSize + config.hueSpacing + config.hueHeight;

            stageWrapper.style.width = config.stageWidth + 'px';
            stageWrapper.style.height = config.stageHeight + 'px';

            stage.width(config.stageWidth);
            stage.height(config.stageHeight);

            saturationRect.width(config.squareSize);
            saturationRect.height(config.squareSize);

            whiteGradient.width(config.squareSize);
            whiteGradient.height(config.squareSize);
            whiteGradient.fillLinearGradientEndPoint({ x: config.squareSize, y: 0 });

            blackGradient.width(config.squareSize);
            blackGradient.height(config.squareSize);
            blackGradient.fillLinearGradientEndPoint({ x: 0, y: config.squareSize });

            var cursorRadius = Math.max(8, Math.round(config.squareSize * 0.05));
            saturationCursor.radius(cursorRadius);
            saturationCursor.strokeWidth(Math.max(2, Math.round(config.squareSize * 0.015)));

            var hueY = config.padding + config.squareSize + config.hueSpacing;
            hueRect.y(hueY);
            hueRect.width(config.squareSize);
            hueRect.fillLinearGradientEndPoint({ x: config.squareSize, y: 0 });

            hueCursor.width(Math.max(4, Math.round(config.squareSize * 0.015)));
            hueCursor.height(config.hueHeight + 6);
            hueCursor.y(hueY - 3);

            refreshVisuals();
            layer.batchDraw();
            if (container.classList.contains('is-visible')) {
                updatePosition();
            }
        }

        function markTransparent(active) {
            if (active) {
                container.classList.add('is-transparent');
            } else {
                container.classList.remove('is-transparent');
            }
        }

        function refreshVisuals() {
            saturationRect.fill(hsvToHex(hue, 1, 1));
            var cursorX = config.padding + saturation * config.squareSize;
            var cursorY = config.padding + (1 - value) * config.squareSize;
            saturationCursor.position({ x: cursorX, y: cursorY });
            var hueX = config.padding + (hue / 360) * config.squareSize;
            hueCursor.x(hueX - (hueCursor.width() / 2));
            layer.batchDraw();
        }

        function emitColorChange() {
            if (!activeInput || typeof options.onColorChange !== 'function') {
                return;
            }
            markTransparent(false);
            var hex = hsvToHex(hue, saturation, value);
            options.onColorChange(activeInput, hex);
        }

        function updateSaturationFromPointer() {
            var pointer = stage.getPointerPosition();
            if (!pointer) {
                return;
            }
            var localX = clamp(pointer.x - config.padding, 0, config.squareSize);
            var localY = clamp(pointer.y - config.padding, 0, config.squareSize);
            saturation = localX / config.squareSize;
            value = 1 - (localY / config.squareSize);
            refreshVisuals();
            emitColorChange();
        }

        function updateHueFromPointer() {
            var pointer = stage.getPointerPosition();
            if (!pointer) {
                return;
            }
            var localX = clamp(pointer.x - config.padding, 0, config.squareSize);
            hue = (localX / config.squareSize) * 360;
            refreshVisuals();
            emitColorChange();
        }

        saturationRect.on('mousedown touchstart', function(event) {
            event.evt.preventDefault();
            ignoreCloseOnce = true;
            isDraggingSat = true;
            updateSaturationFromPointer();
            setTimeout(function() {
                ignoreCloseOnce = false;
            }, 0);
        });

        hueRect.on('mousedown touchstart', function(event) {
            event.evt.preventDefault();
            ignoreCloseOnce = true;
            isDraggingHue = true;
            updateHueFromPointer();
            setTimeout(function() {
                ignoreCloseOnce = false;
            }, 0);
        });

        stage.on('mousemove touchmove', function() {
            if (isDraggingSat) {
                updateSaturationFromPointer();
            }
            if (isDraggingHue) {
                updateHueFromPointer();
            }
        });

        stage.on('mouseup touchend', function() {
            isDraggingSat = false;
            isDraggingHue = false;
            ignoreCloseOnce = false;
        });

        stage.on('mouseleave', function() {
            isDraggingSat = false;
            isDraggingHue = false;
            ignoreCloseOnce = false;
        });

        function updatePosition() {
            if (!activeInput) {
                return;
            }
            var rect = activeInput.getBoundingClientRect();
            var width = container.offsetWidth || (config.stageWidth + config.padding * 2);
            var height = container.offsetHeight || (config.stageHeight + config.padding * 2 + 48);
            var left = rect.left;
            var top = rect.bottom + config.margin;

            if (left + width > window.innerWidth - config.margin) {
                left = window.innerWidth - width - config.margin;
            }
            if (left < config.margin) {
                left = config.margin;
            }

            if (top + height > window.innerHeight - config.margin) {
                top = rect.top - height - config.margin;
                if (top < config.margin) {
                    top = Math.max(config.margin, window.innerHeight - height - config.margin);
                }
            }

            container.style.left = Math.round(left) + 'px';
            container.style.top = Math.round(top) + 'px';
        }

        function openForInput(input, rawValue) {
            if (!input) {
                return;
            }
            activeInput = input;
            ignoreCloseOnce = true;
            setTimeout(function() {
                ignoreCloseOnce = false;
            }, 0);

            container.style.display = 'flex';
            container.classList.add('is-visible');
            container.setAttribute('aria-hidden', 'false');
            recalculateDimensions();

            var state = parseColorValue(typeof rawValue === 'string' ? rawValue : '');
            if (state.finalValue && state.finalValue.toLowerCase() === 'transparent') {
                markTransparent(true);
            } else {
                markTransparent(false);
            }

            var hex = state.hex;
            if (!hex && input.dataset) {
                if (input.dataset.lastHexValue) {
                    hex = input.dataset.lastHexValue;
                } else if (input.dataset.defaultStyleValue) {
                    var defaultState = parseColorValue(input.dataset.defaultStyleValue);
                    hex = defaultState.hex;
                }
            }
            if (!hex) {
                hex = '#000000';
            }

            var rgb = hexToRgb(hex);
            var hsv = rgbToHsv(rgb.r, rgb.g, rgb.b);
            hue = hsv.h;
            saturation = hsv.s;
            value = hsv.v;
            refreshVisuals();
            updatePosition();

            if (typeof options.onOpen === 'function') {
                options.onOpen(activeInput);
            }
        }

        function hide() {
            if (!container.classList.contains('is-visible')) {
                return;
            }
            container.classList.remove('is-visible');
            container.style.display = 'none';
            container.setAttribute('aria-hidden', 'true');
            if (typeof options.onClose === 'function' && activeInput) {
                options.onClose(activeInput);
            }
            activeInput = null;
        }

        transparentButton.addEventListener('click', function() {
            if (!activeInput || typeof options.onTransparent !== 'function') {
                return;
            }
            markTransparent(true);
            options.onTransparent(activeInput);
        });

        clearButton.addEventListener('click', function() {
            if (!activeInput || typeof options.onClear !== 'function') {
                return;
            }
            markTransparent(false);
            options.onClear(activeInput);
        });

        closeButton.addEventListener('click', function() {
            hide();
        });

        function syncFromValue(input, rawValue) {
            if (!activeInput || input !== activeInput) {
                return;
            }
            var state = parseColorValue(typeof rawValue === 'string' ? rawValue : '');
            if (state.finalValue && state.finalValue.toLowerCase() === 'transparent') {
                markTransparent(true);
                return;
            }
            markTransparent(false);
            if (!state.hex) {
                return;
            }
            var rgb = hexToRgb(state.hex);
            var hsv = rgbToHsv(rgb.r, rgb.g, rgb.b);
            hue = hsv.h;
            saturation = hsv.s;
            value = hsv.v;
            refreshVisuals();
        }

        return {
            openForInput: openForInput,
            hide: hide,
            updatePosition: updatePosition,
            recalculateDimensions: recalculateDimensions,
            isOpen: function() { return container.classList.contains('is-visible'); },
            contains: function(node) { return container.contains(node); },
            shouldIgnoreClose: function() { return ignoreCloseOnce; },
            getActiveInput: function() { return activeInput; },
            syncFromValue: syncFromValue,
            markTransparent: markTransparent
        };
    }

    function ensureColorPreviewElements(input, state) {
        if (!input || typeof input.closest !== 'function') {
            return;
        }
        var wrapper = input.closest('.color-input-wrapper');
        if (!wrapper) {
            return;
        }
        var previewRow = wrapper.querySelector('.color-preview-row');
        if (!previewRow) {
            previewRow = document.createElement('div');
            previewRow.className = 'color-preview-row';

            var swatch = document.createElement('div');
            swatch.className = 'color-preview-swatch';
            previewRow.appendChild(swatch);

            var valueLabel = document.createElement('span');
            valueLabel.className = 'color-value-text';
            previewRow.appendChild(valueLabel);

            wrapper.appendChild(previewRow);
        }
        updateColorPreview(input, state ? state.finalValue : (input.dataset.styleValue || input.value || ''));
    }

    function updateColorPreview(input, overrideValue) {
        if (!input || typeof input.closest !== 'function') {
            return;
        }
        var wrapper = input.closest('.color-input-wrapper');
        if (!wrapper) {
            return;
        }
        var previewRow = wrapper.querySelector('.color-preview-row');
        if (!previewRow) {
            return;
        }
        var swatch = previewRow.querySelector('.color-preview-swatch');
        var valueLabel = previewRow.querySelector('.color-value-text');
        var value = typeof overrideValue === 'string' ? overrideValue : (input.dataset.styleValue || input.value || '');
        var displayValue = value;
        if (!displayValue) {
            displayValue = overlay.getAttribute('data-empty-color-label') || '—';
        } else if (typeof displayValue === 'string' && displayValue.toLowerCase() === 'transparent') {
            displayValue = overlay.getAttribute('data-transparent-color-label') || 'Transparent';
        }
        if (swatch) {
            swatch.style.setProperty('--bookcreator-preview-color', value || 'transparent');
        }
        if (valueLabel) {
            valueLabel.textContent = displayValue;
        }
    }

    function updateKonvaOverlay() {
        if (!konvaStage || !konvaLayer || !konvaFrame || !previewArea || !previewContent) {
            return;
        }
        var areaRect = previewArea.getBoundingClientRect();
        if (areaRect.width <= 0 || areaRect.height <= 0) {
            return;
        }
        konvaStage.width(areaRect.width);
        konvaStage.height(areaRect.height);
        var contentRect = previewContent.getBoundingClientRect();
        var offsetX = contentRect.left - areaRect.left;
        var offsetY = contentRect.top - areaRect.top;
        konvaFrame.position({ x: offsetX, y: offsetY });
        konvaFrame.width(contentRect.width);
        konvaFrame.height(contentRect.height);
        konvaLayer.batchDraw();
    }

    var sharedKonvaPicker = null;
    if (window.Konva) {
        var transparentLabel = overlay.getAttribute('data-transparent-color-label') || 'Transparent';
        var clearLabel = overlay.getAttribute('data-clear-color-label') || (overlay.getAttribute('data-empty-color-label') || 'Clear');
        var closeLabel = overlay.getAttribute('data-close-color-picker-label') || 'Chiudi';
        var titleLabel = overlay.getAttribute('data-color-picker-title') || '';
        sharedKonvaPicker = createSharedKonvaColorPicker(overlay, {
            transparentLabel: transparentLabel,
            clearLabel: clearLabel,
            closeLabel: closeLabel,
            titleLabel: titleLabel,
            onOpen: function(input) {
                activeColorInput = input;
                if (sharedKonvaPicker) {
                    if (typeof sharedKonvaPicker.recalculateDimensions === 'function') {
                        sharedKonvaPicker.recalculateDimensions();
                    }
                    sharedKonvaPicker.updatePosition();
                }
            },
            onClose: function() {
                activeColorInput = null;
            },
            onColorChange: function(input, hex) {
                var state = parseColorValue(hex);
                input.dataset.styleValue = state.finalValue;
                input.value = state.hex;
                input.dataset.lastHexValue = state.hex;
                ensureColorPreviewElements(input, state);
                updateColorPreview(input, state.finalValue);
                applyStyleChange(input);
            },
            onTransparent: function(input) {
                input.dataset.styleValue = 'transparent';
                input.value = '';
                updateColorPreview(input, 'transparent');
                applyStyleChange(input);
            },
            onClear: function(input) {
                input.dataset.styleValue = '';
                input.value = '';
                updateColorPreview(input, '');
                applyStyleChange(input);
            }
        });
        if (sharedKonvaPicker && typeof sharedKonvaPicker.recalculateDimensions === 'function') {
            sharedKonvaPicker.recalculateDimensions();
        }
    }

    var inputs = Array.prototype.slice.call(overlay.querySelectorAll('.property-input, .select-input'));
    inputs.forEach(function(input) {
        if (typeof input.dataset.defaultValue === 'undefined') {
            input.dataset.defaultValue = input.value;
        }
        updateColorPreview(input);
    });

    var colorInputs = inputs.filter(function(input) {
        return input.dataset && input.dataset.colorControl === 'true';
    });

    function initializeColorPicker(input) {
        if (!input) {
            return;
        }
        var initialValue = input.dataset.styleValue || input.value || '';
        var state = parseColorValue(initialValue);
        input.dataset.styleValue = state.finalValue;
        if (typeof input.dataset.defaultStyleValue === 'undefined') {
            input.dataset.defaultStyleValue = state.finalValue;
        }
        input.value = state.hex;
        if (state.hex) {
            input.dataset.lastHexValue = state.hex;
        } else if (input.dataset.defaultStyleValue) {
            var defaultState = parseColorValue(input.dataset.defaultStyleValue);
            if (defaultState.hex) {
                input.dataset.lastHexValue = defaultState.hex;
            }
        }
        ensureColorPreviewElements(input, state);
        updateColorPreview(input, state.finalValue);

        var wrapper = input.closest('.color-input-wrapper');
        var previewRow = wrapper ? wrapper.querySelector('.color-preview-row') : null;

        if (sharedKonvaPicker) {
            var openPicker = function(event) {
                if (event) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                var baseValue = input.dataset.styleValue || input.value || input.dataset.defaultStyleValue || '';
                sharedKonvaPicker.openForInput(input, baseValue);
                if (typeof input.focus === 'function') {
                    input.focus({ preventScroll: true });
                }
            };

            input.addEventListener('focus', function() {
                var baseValue = input.dataset.styleValue || input.value || input.dataset.defaultStyleValue || '';
                sharedKonvaPicker.openForInput(input, baseValue);
            });

            input.addEventListener('click', openPicker);

            if (previewRow) {
                previewRow.addEventListener('click', openPicker);
            }
        }
    }

    colorInputs.forEach(function(input) {
        initializeColorPicker(input);
    });

    function handleManualColorInput(input) {
        if (!input) {
            return;
        }
        var enteredValue = (input.value || '').trim();
        if (!enteredValue) {
            input.dataset.styleValue = '';
            input.value = '';
            updateColorPreview(input, '');
            if (sharedKonvaPicker) {
                sharedKonvaPicker.syncFromValue(input, '');
            }
            applyStyleChange(input);
            return;
        }
        var state = parseColorValue(enteredValue);
        input.dataset.styleValue = state.finalValue;
        input.value = state.hex;
        if (state.hex) {
            input.dataset.lastHexValue = state.hex;
        }
        ensureColorPreviewElements(input, state);
        updateColorPreview(input, state.finalValue);
        if (sharedKonvaPicker) {
            sharedKonvaPicker.syncFromValue(input, state.finalValue || state.hex);
        }
        applyStyleChange(input);
    }

    if (sharedKonvaPicker) {
        document.addEventListener('mousedown', function(event) {
            if (!sharedKonvaPicker.isOpen()) {
                return;
            }
            if (sharedKonvaPicker.shouldIgnoreClose && sharedKonvaPicker.shouldIgnoreClose()) {
                return;
            }
            if (sharedKonvaPicker.contains(event.target)) {
                return;
            }
            var activeInputElement = sharedKonvaPicker.getActiveInput ? sharedKonvaPicker.getActiveInput() : null;
            if (activeInputElement && (activeInputElement === event.target || activeInputElement.contains(event.target))) {
                return;
            }
            sharedKonvaPicker.hide();
        }, true);

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && sharedKonvaPicker.isOpen()) {
                sharedKonvaPicker.hide();
            }
        });

        overlay.addEventListener('scroll', function() {
            sharedKonvaPicker.updatePosition();
        }, true);
    }

    window.addEventListener('resize', function() {
        updateKonvaOverlay();
        if (sharedKonvaPicker) {
            if (typeof sharedKonvaPicker.recalculateDimensions === 'function') {
                sharedKonvaPicker.recalculateDimensions();
            }
            sharedKonvaPicker.updatePosition();
        }
    });

    if (previewArea) {
        previewArea.addEventListener('scroll', function() {
            updateKonvaOverlay();
        });
    }

    updateKonvaOverlay();

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

    function scrollPreviewToElement(element, alignTop) {
        if (!element || !previewArea) {
            return;
        }
        var container = previewArea;
        var elementRect = element.getBoundingClientRect();
        var containerRect = container.getBoundingClientRect();
        var isFullyVisible = elementRect.top >= containerRect.top && elementRect.bottom <= containerRect.bottom;
        if (isFullyVisible) {
            return;
        }
        var offsetTop = element.offsetTop;
        var parent = element.offsetParent;
        while (parent && parent !== container) {
            offsetTop += parent.offsetTop;
            parent = parent.offsetParent;
        }
        var target = alignTop ? offsetTop - 16 : (container.scrollTop + elementRect.top - containerRect.top);
        if (target < 0) {
            target = 0;
        }
        container.scrollTo({
            top: target,
            behavior: 'smooth'
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
            if (input.dataset && input.dataset.colorControl === 'true') {
                var baseValue;
                if (!previewElement) {
                    baseValue = input.dataset.defaultStyleValue || input.dataset.defaultValue || '';
                } else {
                    var colorInline = previewElement.style.getPropertyValue(property);
                    if (colorInline) {
                        baseValue = colorInline.trim();
                    } else {
                        baseValue = input.dataset.defaultStyleValue || input.dataset.defaultValue || '';
                    }
                }
                if (typeof baseValue === 'undefined') {
                    baseValue = '';
                }
                var colorState = parseColorValue(baseValue);
                input.dataset.styleValue = colorState.finalValue;
                input.value = colorState.hex;
                if (colorState.hex) {
                    input.dataset.lastHexValue = colorState.hex;
                }
                ensureColorPreviewElements(input, colorState);
                updateColorPreview(input, colorState.finalValue);
                if (sharedKonvaPicker) {
                    sharedKonvaPicker.syncFromValue(input, colorState.finalValue || colorState.hex);
                }
                return;
            }
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
        if (sharedKonvaPicker) {
            sharedKonvaPicker.hide();
        }
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
        if (options.scrollPreview && currentPreviewNode) {
            scrollPreviewToElement(currentPreviewNode, true);
        }
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
                scrollIntoView: false,
                scrollPreview: true
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
                scrollIntoView: true,
                scrollPreview: false
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
        var rawValue = (typeof input.dataset.styleValue !== 'undefined') ? input.dataset.styleValue : input.value;
        if (rawValue === '' || rawValue === null) {
            currentPreviewNode.style.removeProperty(property);
        } else {
            var finalValue = rawValue;
            if (unit) {
                finalValue = rawValue + unit;
            }
            currentPreviewNode.style.setProperty(property, finalValue, 'important');
        }
    }

    inputs.forEach(function(input) {
        var isColorControl = input.dataset && input.dataset.colorControl === 'true';
        if (isColorControl) {
            var colorHandler = function() {
                handleManualColorInput(input);
            };
            input.addEventListener('change', colorHandler);
            input.addEventListener('blur', colorHandler);
        } else if (input.tagName === 'SELECT') {
            input.addEventListener('change', function() {
                applyStyleChange(input);
            });
        } else {
            var handler = function() {
                applyStyleChange(input);
            };
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

})();
</script>

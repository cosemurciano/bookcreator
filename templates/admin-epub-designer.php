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
<div class=\"bookcreator-epub-designer-overlay\" role=\"application\" aria-label=\"<?php esc_attr_e( 'ePub Template Designer', 'bookcreator' ); ?>\">
    <div class=\"designer-container\">
        <div class=\"header\">
            <div style=\"display: flex; align-items: center; gap: 16px;\">
                <a class=\"btn btn-secondary\" href=\"<?php echo esc_url( $books_list_url ); ?>\">&#8592; WordPress</a>
                <h1>📚 ePub Template Designer</h1>
            </div>
            <div class=\"header-actions\">
                <button type=\"button\" class=\"btn btn-secondary\">Anteprima</button>
                <button type=\"button\" class=\"btn btn-secondary\">Esporta Template</button>
                <button type=\"button\" class=\"btn btn-primary\">Salva Template</button>
            </div>
        </div>
        <div class=\"main-content\">
            <div class=\"left-sidebar\">
                <div class=\"sidebar-header\">
                    <h3>Campi ePub</h3>
                </div>
                <div class=\"fields-container\">
                    <div class=\"field-category\">
                        <div class=\"category-header\">
                            📖 Informazioni Libro
                            <span>▼</span>
                        </div>
                        <div class=\"field-item active\">
                            <div class=\"field-info\">
                                <div class=\"field-name\">Autore Principale</div>
                                <div class=\"field-type\">bc_author</div>
                            </div>
                            <div class=\"field-actions\">
                                <button type=\"button\" class=\"visibility-btn\" title=\"Nascondi/Mostra campo\">👁️</button>
                            </div>
                        </div>
                        <div class=\"field-item\">
                            <div class=\"field-info\">
                                <div class=\"field-name\">Co-Autori</div>
                                <div class=\"field-type\">bc_coauthors</div>
                            </div>
                            <div class=\"field-actions\">
                                <button type=\"button\" class=\"visibility-btn\" title=\"Nascondi/Mostra campo\">👁️</button>
                            </div>
                        </div>
                        <div class=\"field-item\">
                            <div class=\"field-info\">
                                <div class=\"field-name\">Titolo Libro</div>
                                <div class=\"field-type\">post_title</div>
                            </div>
                            <div class=\"field-actions\">
                                <button type=\"button\" class=\"visibility-btn\" title=\"Nascondi/Mostra campo\">👁️</button>
                            </div>
                        </div>
                        <div class=\"field-item\">
                            <div class=\"field-info\">
                                <div class=\"field-name\">Sottotitolo</div>
                                <div class=\"field-type\">bc_subtitle</div>
                            </div>
                            <div class=\"field-actions\">
                                <button type=\"button\" class=\"visibility-btn\" title=\"Nascondi/Mostra campo\">👁️</button>
                            </div>
                        </div>
                        <div class=\"field-item\">
                            <div class=\"field-info\">
                                <div class=\"field-name\">Editore</div>
                                <div class=\"field-type\">bc_publisher</div>
                            </div>
                            <div class=\"field-actions\">
                                <button type=\"button\" class=\"visibility-btn\" title=\"Nascondi/Mostra campo\">👁️</button>
                            </div>
                        </div>
                        <div class=\"field-item\">
                            <div class=\"field-info\">
                                <div class=\"field-name\">Immagine Editore</div>
                                <div class=\"field-type\">publisher_image</div>
                            </div>
                            <div class=\"field-actions\">
                                <button type=\"button\" class=\"visibility-btn\" title=\"Nascondi/Mostra campo\">👁️</button>
                            </div>
                        </div>
                    </div>
                    <div class=\"field-category\">
                        <div class=\"category-header\">
                            📄 Contenuti Preliminari
                            <span>▼</span>
                        </div>
                        <div class=\"field-item\">
                            <div class=\"field-info\">
                                <div class=\"field-name\">Dedica</div>
                                <div class=\"field-type\">bc_dedication</div>
                            </div>
                            <div class=\"field-actions\">
                                <button type=\"button\" class=\"visibility-btn\" title=\"Nascondi/Mostra campo\">👁️</button>
                            </div>
                        </div>
                        <div class=\"field-item\">
                            <div class=\"field-info\">
                                <div class=\"field-name\">Prefazione</div>
                                <div class=\"field-type\">bc_preface</div>
                            </div>
                            <div class=\"field-actions\">
                                <button type=\"button\" class=\"visibility-btn\" title=\"Nascondi/Mostra campo\">👁️</button>
                            </div>
                        </div>
                        <div class=\"field-item\">
                            <div class=\"field-info\">
                                <div class=\"field-name\">Ringraziamenti</div>
                                <div class=\"field-type\">bc_acknowledgments</div>
                            </div>
                            <div class=\"field-actions\">
                                <button type=\"button\" class=\"visibility-btn\" title=\"Nascondi/Mostra campo\">👁️</button>
                            </div>
                        </div>
                        <div class=\"field-item\">
                            <div class=\"field-info\">
                                <div class=\"field-name\">Descrizione Libro</div>
                                <div class=\"field-type\">bc_description</div>
                            </div>
                            <div class=\"field-actions\">
                                <button type=\"button\" class=\"visibility-btn\" title=\"Nascondi/Mostra campo\">👁️</button>
                            </div>
                        </div>
                        <div class=\"field-item\">
                            <div class=\"field-info\">
                                <div class=\"field-name\">Sezione Copyright</div>
                                <div class=\"field-type\">bc_copyright</div>
                            </div>
                            <div class=\"field-actions\">
                                <button type=\"button\" class=\"visibility-btn\" title=\"Nascondi/Mostra campo\">👁️</button>
                            </div>
                        </div>
                        <div class=\"field-item\">
                            <div class=\"field-info\">
                                <div class=\"field-name\">Codice ISBN</div>
                                <div class=\"field-type\">bc_isbn</div>
                            </div>
                            <div class=\"field-actions\">
                                <button type=\"button\" class=\"visibility-btn\" title=\"Nascondi/Mostra campo\">👁️</button>
                            </div>
                        </div>
                        <div class=\"field-item\">
                            <div class=\"field-info\">
                                <div class=\"field-name\">Indice</div>
                                <div class=\"field-type\">table_of_contents</div>
                            </div>
                            <div class=\"field-actions\">
                                <button type=\"button\" class=\"visibility-btn\" title=\"Nascondi/Mostra campo\">👁️</button>
                            </div>
                        </div>
                    </div>
                    <div class=\"field-category\">
                        <div class=\"category-header\">
                            📚 Contenuto Principale
                            <span>▼</span>
                        </div>
                        <div class=\"field-item\">
                            <div class=\"field-info\">
                                <div class=\"field-name\">Titolo Capitolo</div>
                                <div class=\"field-type\">chapter_title</div>
                            </div>
                            <div class=\"field-actions\">
                                <button type=\"button\" class=\"visibility-btn\" title=\"Nascondi/Mostra campo\">👁️</button>
                            </div>
                        </div>
                        <div class=\"field-item\">
                            <div class=\"field-info\">
                                <div class=\"field-name\">Contenuto Capitolo</div>
                                <div class=\"field-type\">chapter_content</div>
                            </div>
                            <div class=\"field-actions\">
                                <button type=\"button\" class=\"visibility-btn\" title=\"Nascondi/Mostra campo\">👁️</button>
                            </div>
                        </div>
                        <div class=\"field-item\">
                            <div class=\"field-info\">
                                <div class=\"field-name\">Titolo Paragrafo</div>
                                <div class=\"field-type\">paragraph_title</div>
                            </div>
                            <div class=\"field-actions\">
                                <button type=\"button\" class=\"visibility-btn\" title=\"Nascondi/Mostra campo\">👁️</button>
                            </div>
                        </div>
                        <div class=\"field-item\">
                            <div class=\"field-info\">
                                <div class=\"field-name\">Contenuto Paragrafo</div>
                                <div class=\"field-type\">paragraph_content</div>
                            </div>
                            <div class=\"field-actions\">
                                <button type=\"button\" class=\"visibility-btn\" title=\"Nascondi/Mostra campo\">👁️</button>
                            </div>
                        </div>
                        <div class=\"field-item\">
                            <div class=\"field-info\">
                                <div class=\"field-name\">Note del Paragrafo</div>
                                <div class=\"field-type\">bc_footnotes</div>
                            </div>
                            <div class=\"field-actions\">
                                <button type=\"button\" class=\"visibility-btn\" title=\"Nascondi/Mostra campo\">👁️</button>
                            </div>
                        </div>
                        <div class=\"field-item\">
                            <div class=\"field-info\">
                                <div class=\"field-name\">Citazioni del Paragrafo</div>
                                <div class=\"field-type\">bc_citations</div>
                            </div>
                            <div class=\"field-actions\">
                                <button type=\"button\" class=\"visibility-btn\" title=\"Nascondi/Mostra campo\">👁️</button>
                            </div>
                        </div>
                    </div>
                    <div class=\"field-category\">
                        <div class=\"category-header\">
                            📝 Contenuti Finali
                            <span>▼</span>
                        </div>
                        <div class=\"field-item\">
                            <div class=\"field-info\">
                                <div class=\"field-name\">Appendice</div>
                                <div class=\"field-type\">bc_appendix</div>
                            </div>
                            <div class=\"field-actions\">
                                <button type=\"button\" class=\"visibility-btn\" title=\"Nascondi/Mostra campo\">👁️</button>
                            </div>
                        </div>
                        <div class=\"field-item\">
                            <div class=\"field-info\">
                                <div class=\"field-name\">Bibliografia</div>
                                <div class=\"field-type\">bc_bibliography</div>
                            </div>
                            <div class=\"field-actions\">
                                <button type=\"button\" class=\"visibility-btn\" title=\"Nascondi/Mostra campo\">👁️</button>
                            </div>
                        </div>
                        <div class=\"field-item\">
                            <div class=\"field-info\">
                                <div class=\"field-name\">Nota Autore</div>
                                <div class=\"field-type\">bc_author_note</div>
                            </div>
                            <div class=\"field-actions\">
                                <button type=\"button\" class=\"visibility-btn\" title=\"Nascondi/Mostra campo\">👁️</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class=\"canvas-area\">
                <div class=\"canvas-header-area\">
                    <h3 class=\"canvas-title\">Anteprima ePub</h3>
                    <button type=\"button\" class=\"btn btn-primary\" style=\"font-size: 12px; padding: 8px 16px;\">Salva Stili</button>
                </div>
                <div class=\"canvas-container\">
                    <div class=\"canvas-header\">
                        <div class=\"page-info\">Pagina 1 di 1 • Template ePub</div>
                        <div class=\"zoom-controls\">
                            <div class=\"zoom-btn\">-</div>
                            <div class=\"zoom-btn\">100%</div>
                            <div class=\"zoom-btn\">+</div>
                        </div>
                    </div>
                    <div class=\"preview-area\">
                        <div class=\"preview-content\" id=\"konva-container\">
                            <div style=\"padding: 40px; line-height: 1.6; color: #000000; font-family: serif;\">
                                <h1 style=\"font-size: 2rem; margin-bottom: 1rem; outline: 2px dashed #3b82f6; outline-offset: 4px; position: relative;\">
                                    Mario Rossi
                                    <div style=\"position: absolute; top: -12px; left: -8px; background: #3b82f6; color: white; font-size: 10px; padding: 2px 6px; border-radius: 4px; font-weight: 500;\">SELEZIONATO</div>
                                </h1>
                                <p style=\"font-size: 1rem; margin-bottom: 0.5rem;\">Con Anna Bianchi, Giuseppe Verdi</p>
                                <h1 style=\"font-size: 2.5rem; margin: 2rem 0 1rem 0;\">
                                    Il Mistero della Biblioteca Perduta
                                </h1>
                                <h2 style=\"font-size: 1.5rem; margin-bottom: 2rem;\">
                                    Un'avventura tra storia e leggenda
                                </h2>
                                <div style=\"margin: 2rem 0;\">
                                    <div style=\"width: 120px; height: 80px; background: #f0f0f0; margin: 0 auto; display: flex; align-items: center; justify-content: center; font-size: 12px;\">
                                        Logo Editore
                                    </div>
                                </div>
                                <p style=\"font-size: 1rem; margin-bottom: 2rem;\">Edizioni Mondadori</p>
                                <div style=\"font-size: 1rem; margin: 2rem 0;\">
                                    A mia nonna, che mi ha insegnato l'amore per i libri e il mistero delle storie non ancora raccontate.
                                </div>
                                <h3 style=\"font-size: 1.3rem; margin: 2rem 0 1rem 0;\">Prefazione</h3>
                                <p style=\"margin-bottom: 1.5rem; font-size: 1rem;\">
                                    Questa storia nasce da una leggenda che ho sentito da bambino, seduto accanto al camino di casa mia.
                                    È il racconto di una biblioteca che esisteva secoli fa, custode di segreti che ancora oggi...
                                </p>
                                <h3 style=\"font-size: 1.3rem; margin: 2rem 0 1rem 0;\">Ringraziamenti</h3>
                                <p style=\"margin-bottom: 1.5rem; font-size: 1rem;\">
                                    Un ringraziamento speciale va ai bibliotecari dell'Archivio di Stato, senza i quali questa ricerca non sarebbe stata possibile...
                                </p>
                                <div style=\"margin: 2rem 0;\">
                                    <h4 style=\"font-size: 1.2rem; margin-bottom: 1rem;\">Descrizione</h4>
                                    <p style=\"font-size: 1rem;\">
                                        Un thriller storico che intreccia passato e presente in una caccia al tesoro intellettuale.
                                        Quando la giovane archivista Elena scopre un manoscritto medievale...
                                    </p>
                                </div>
                                <div style=\"font-size: 0.9rem; margin: 2rem 0;\">
                                    <p>© 2024 Mario Rossi. Tutti i diritti riservati.</p>
                                    <p>Nessuna parte di questa pubblicazione può essere riprodotta senza autorizzazione scritta dell'editore.</p>
                                </div>
                                <p style=\"font-size: 0.9rem; margin: 1rem 0;\">ISBN: 978-88-04-12345-6</p>
                                <div style=\"margin: 2rem 0;\">
                                    <h4 style=\"font-size: 1.3rem; margin-bottom: 1rem;\">Indice</h4>
                                    <div style=\"font-size: 1rem;\">
                                        <p style=\"margin: 0.5rem 0;\">Prefazione ........................... 3</p>
                                        <p style=\"margin: 0.5rem 0;\">Capitolo 1: La Scoperta ............. 7</p>
                                        <p style=\"margin: 0.5rem 0;\">Capitolo 2: Il Primo Indizio ........ 23</p>
                                        <p style=\"margin: 0.5rem 0;\">Capitolo 3: La Biblioteca Segreta ... 41</p>
                                    </div>
                                </div>
                                <h2 style=\"font-size: 1.8rem; margin: 3rem 0 1.5rem 0;\">
                                    Capitolo 1: La Scoperta
                                </h2>
                                <p style=\"margin-bottom: 1.5rem; font-size: 1rem;\">
                                    Era una mattina di novembre quando Elena Marchetti entrò per la prima volta nell'Archivio di Stato.
                                    L'odore di carta antica e polvere di secoli la avvolse come un mantello, e per un momento si sentì...
                                </p>
                                <h3 style=\"font-size: 1.2rem; margin: 2rem 0 1rem 0;\">Il Manoscritto Misterioso</h3>
                                <p style=\"margin-bottom: 1.5rem; font-size: 1rem;\">
                                    Nel ripiano più alto dello scaffale, nascosto dietro una collezione di documenti del XVIII secolo,
                                    Elena notò qualcosa di insolito. Un volume rilegato in pelle scura, privo di titolo...
                                </p>
                                <div style=\"font-size: 0.85rem; margin: 1rem 0;\">
                                    Gli archivi storici di Firenze custodiscono oltre 40.000 documenti medievali ancora inesplorati.
                                </div>
                                <div style=\"margin: 1.5rem 0; font-size: 1rem;\">
                                    \"Il sapere è come una biblioteca: più libri aggiungi, più spazio sembra mancare per quelli che ancora devi scoprire.\"
                                    - Umberto Eco, Il Nome della Rosa
                                </div>
                                <div style=\"margin: 3rem 0;\">
                                    <h2 style=\"font-size: 1.5rem; margin-bottom: 1.5rem;\">Appendice A: Cronologia degli Eventi</h2>
                                    <p style=\"margin: 0.5rem 0; font-size: 1rem;\">1347 - Fondazione della Biblioteca del Monastero</p>
                                    <p style=\"margin: 0.5rem 0; font-size: 1rem;\">1398 - Prima menzione del manoscritto perduto</p>
                                    <p style=\"margin: 0.5rem 0; font-size: 1rem;\">1456 - Chiusura definitiva della biblioteca</p>
                                </div>
                                <div style=\"margin: 3rem 0;\">
                                    <h2 style=\"font-size: 1.5rem; margin-bottom: 1.5rem;\">Bibliografia</h2>
                                    <div style=\"font-size: 0.95rem;\">
                                        <p style=\"margin: 0.8rem 0;\">Alberti, L. B. (1435). De re aedificatoria. Firenze: Tipografia Medicea.</p>
                                        <p style=\"margin: 0.8rem 0;\">Eco, U. (1980). Il nome della rosa. Milano: Bompiani.</p>
                                        <p style=\"margin: 0.8rem 0;\">Manguel, A. (1996). Una storia della lettura. Milano: Mondadori.</p>
                                    </div>
                                </div>
                                <div style=\"margin: 3rem 0;\">
                                    <h3 style=\"font-size: 1.3rem; margin-bottom: 1rem;\">Nota dell'Autore</h3>
                                    <p style=\"font-size: 1rem;\">
                                        Questo romanzo è frutto di anni di ricerca negli archivi storici italiani.
                                        Sebbene i personaggi siano immaginari, molti dei documenti e delle location descritte sono reali.
                                        Ringrazio tutti coloro che hanno reso possibile questo viaggio nella storia.
                                    </p>
                                    <p style=\"margin-top: 1rem; font-size: 1rem;\">
                                        - Mario Rossi, Firenze 2024
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class=\"right-sidebar\">
                <div class=\"properties-header\">
                    <h3>Proprietà Stile</h3>
                    <span class=\"selected-field\">bc_author - Autore Principale</span>
                </div>
                <div class=\"properties-content\">
                    <div class=\"property-group\" id=\"image-properties\" style=\"display: none;\">
                        <div class=\"property-group-title\">
                            🖼️ Proprietà Immagine
                        </div>
                        <div class=\"property-row\">
                            <label class=\"property-label\">Allineamento</label>
                            <select class=\"select-input\">
                                <option>Left</option>
                                <option>Center</option>
                                <option>Right</option>
                            </select>
                        </div>
                        <div class=\"property-row\">
                            <label class=\"property-label\">Dimensione (%)</label>
                            <input type=\"number\" class=\"property-input\" value=\"100\" min=\"10\" max=\"100\" step=\"5\">
                        </div>
                        <div class=\"property-row\">
                            <label class=\"property-label\">Margine Immagine (em)</label>
                            <div class=\"property-row-quad\">
                                <div>
                                    <input type=\"number\" class=\"property-input\" placeholder=\"Top\" value=\"1\" step=\"0.1\">
                                </div>
                                <div>
                                    <input type=\"number\" class=\"property-input\" placeholder=\"Right\" value=\"0\" step=\"0.1\">
                                </div>
                                <div>
                                    <input type=\"number\" class=\"property-input\" placeholder=\"Bottom\" value=\"1\" step=\"0.1\">
                                </div>
                                <div>
                                    <input type=\"number\" class=\"property-input\" placeholder=\"Left\" value=\"0\" step=\"0.1\">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class=\"property-group\">
                        <div class=\"property-group-title\">
                            🅰️ Tipografia
                        </div>
                        <div class=\"property-row\">
                            <label class=\"property-label\">Famiglia Font</label>
                            <select class=\"select-input\">
                                <option>Arial, sans-serif</option>
                                <option>Georgia, serif</option>
                                <option>Times New Roman, serif</option>
                                <option>Helvetica, sans-serif</option>
                            </select>
                        </div>
                        <div class=\"property-row-split\">
                            <div>
                                <label class=\"property-label\">Dimensione (rem)</label>
                                <input type=\"number\" class=\"property-input\" value=\"1.5\" step=\"0.1\">
                            </div>
                            <div>
                                <label class=\"property-label\">Altezza Riga</label>
                                <input type=\"number\" class=\"property-input\" value=\"1.4\" step=\"0.1\">
                            </div>
                        </div>
                        <div class=\"property-row-split\">
                            <div>
                                <label class=\"property-label\">Peso Font</label>
                                <select class=\"select-input\">
                                    <option>400 - Normal</option>
                                    <option>600 - Semi Bold</option>
                                    <option>700 - Bold</option>
                                </select>
                            </div>
                            <div>
                                <label class=\"property-label\">Stile Font</label>
                                <select class=\"select-input\">
                                    <option>Normal</option>
                                    <option>Italic</option>
                                </select>
                            </div>
                        </div>
                        <div class=\"property-row-split\">
                            <div>
                                <label class=\"property-label\">Sillabazione</label>
                                <select class=\"select-input\">
                                    <option>Auto</option>
                                    <option>None</option>
                                    <option>Manual</option>
                                </select>
                            </div>
                            <div>
                                <label class=\"property-label\">Allineamento</label>
                                <select class=\"select-input\">
                                    <option>Left</option>
                                    <option>Center</option>
                                    <option>Right</option>
                                    <option>Justify</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class=\"property-group\">
                        <div class=\"property-group-title\">
                            🎨 Colori
                        </div>
                        <div class=\"property-row\">
                            <label class=\"property-label\">Colore Testo</label>
                            <div class=\"color-input-wrapper\">
                                <div class=\"color-picker\" style=\"background: #374151;\"></div>
                                <input type=\"text\" class=\"property-input\" value=\"#374151\">
                            </div>
                        </div>
                        <div class=\"property-row\">
                            <label class=\"property-label\">Colore Sfondo</label>
                            <div class=\"color-input-wrapper\">
                                <div class=\"color-picker\" style=\"background: transparent; border-style: dashed;\"></div>
                                <input type=\"text\" class=\"property-input\" value=\"transparent\">
                            </div>
                        </div>
                    </div>
                    <div class=\"property-group\">
                        <div class=\"property-group-title\">
                            🔲 Bordi
                        </div>
                        <div class=\"property-row\">
                            <label class=\"property-label\">Spessore Bordo (px)</label>
                            <div class=\"property-row-quad\">
                                <div>
                                    <input type=\"number\" class=\"property-input\" placeholder=\"Top\" value=\"0\" min=\"0\" max=\"10\">
                                </div>
                                <div>
                                    <input type=\"number\" class=\"property-input\" placeholder=\"Right\" value=\"0\" min=\"0\" max=\"10\">
                                </div>
                                <div>
                                    <input type=\"number\" class=\"property-input\" placeholder=\"Bottom\" value=\"0\" min=\"0\" max=\"10\">
                                </div>
                                <div>
                                    <input type=\"number\" class=\"property-input\" placeholder=\"Left\" value=\"0\" min=\"0\" max=\"10\">
                                </div>
                            </div>
                        </div>
                        <div class=\"property-row\">
                            <label class=\"property-label\">Colore Bordo</label>
                            <div class=\"color-input-wrapper\">
                                <div class=\"color-picker\" style=\"background: #374151;\"></div>
                                <input type=\"text\" class=\"property-input\" value=\"#374151\">
                            </div>
                        </div>
                        <div class=\"property-row\">
                            <label class=\"property-label\">Stile Bordo</label>
                            <select class=\"select-input\">
                                <option>solid</option>
                                <option>dashed</option>
                                <option>dotted</option>
                                <option>double</option>
                                <option>none</option>
                            </select>
                        </div>
                    </div>
                    <div class=\"property-group\">
                        <div class=\"property-group-title\">
                            📐 Spaziatura
                        </div>
                        <div class=\"property-row\">
                            <label class=\"property-label\">Margine (em)</label>
                            <div class=\"property-row-quad\">
                                <div>
                                    <input type=\"number\" class=\"property-input\" placeholder=\"Top\" value=\"1.5\" step=\"0.1\">
                                </div>
                                <div>
                                    <input type=\"number\" class=\"property-input\" placeholder=\"Right\" value=\"0\" step=\"0.1\">
                                </div>
                                <div>
                                    <input type=\"number\" class=\"property-input\" placeholder=\"Bottom\" value=\"1\" step=\"0.1\">
                                </div>
                                <div>
                                    <input type=\"number\" class=\"property-input\" placeholder=\"Left\" value=\"0\" step=\"0.1\">
                                </div>
                            </div>
                        </div>
                        <div class=\"property-row\">
                            <label class=\"property-label\">Padding (em)</label>
                            <div class=\"property-row-quad\">
                                <div>
                                    <input type=\"number\" class=\"property-input\" placeholder=\"Top\" value=\"0\" step=\"0.1\">
                                </div>
                                <div>
                                    <input type=\"number\" class=\"property-input\" placeholder=\"Right\" value=\"0\" step=\"0.1\">
                                </div>
                                <div>
                                    <input type=\"number\" class=\"property-input\" placeholder=\"Bottom\" value=\"0\" step=\"0.1\">
                                </div>
                                <div>
                                    <input type=\"number\" class=\"property-input\" placeholder=\"Left\" value=\"0\" step=\"0.1\">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class=\"status-bar\">
            <div>Campo selezionato: bc_author • Modifiche non salvate</div>
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

    var fieldItems = overlay.querySelectorAll('.field-item');
    var selectedField = overlay.querySelector('.selected-field');
    var imageProperties = overlay.querySelector('#image-properties');

    fieldItems.forEach(function(item) {
        item.addEventListener('click', function(event) {
            if (event.target.closest('.visibility-btn')) {
                return;
            }

            var active = overlay.querySelector('.field-item.active');
            if (active) {
                active.classList.remove('active');
            }

            item.classList.add('active');

            var fieldName = item.querySelector('.field-name').textContent;
            var fieldType = item.querySelector('.field-type').textContent;
            if (selectedField) {
                selectedField.textContent = fieldType + ' - ' + fieldName;
            }

            if (imageProperties) {
                if (fieldType === 'publisher_image' || fieldName.toLowerCase().indexOf('immagine') !== -1) {
                    imageProperties.style.display = 'block';
                } else {
                    imageProperties.style.display = 'none';
                }
            }
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

    var inputs = overlay.querySelectorAll('.property-input, .select-input');
    inputs.forEach(function(input) {
        input.addEventListener('change', function() {
            // Placeholder for live preview integration with KonvaJS.
        });
    });

    var saveStylesButton = overlay.querySelector('.canvas-header-area .btn-primary');
    if (saveStylesButton) {
        saveStylesButton.addEventListener('click', function() {
            var currentField = overlay.querySelector('.field-item.active');
            if (!currentField) {
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

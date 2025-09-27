(function () {
    if (typeof window === 'undefined' || typeof Konva === 'undefined') {
        return;
    }

    const BASE_WIDTH = 1100;
    const BASE_HEIGHT = 700;
    const PAGE_PADDING_X = 64;
    const PAGE_PADDING_TOP = 48;

    const data = window.bookcreatorEpubDesigner || {};
    const strings = Object.assign({ bookInfo: '%1$s campi simulati' }, data.strings || {});

    const container = document.getElementById('bookcreator-epub-designer-stage');
    if (!container) {
        return;
    }

    const toolbar = document.getElementById('bookcreator-epub-toolbar');
    const toolbarFieldLabel = document.getElementById('bookcreator-epub-toolbar-field');
    const closeToolbarButton = toolbar ? toolbar.querySelector('[data-epub-toolbar-close]') : null;
    const selector = document.getElementById('bookcreator-epub-designer-book');
    const titleLabel = document.getElementById('bookcreator-epub-designer-selected-title');
    const metaLabel = document.getElementById('bookcreator-epub-designer-book-meta');
    const emptyMessage = document.getElementById('bookcreator-epub-designer-empty');

    if (selector) {
        selector.disabled = true;
    }

    if (emptyMessage) {
        emptyMessage.classList.remove('is-visible');
    }

    if (titleLabel) {
        titleLabel.textContent = 'Anteprima ePub designer';
    }

    const sampleImageSrc =
        "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='640' height='360' viewBox='0 0 640 360'%3E%3Cdefs%3E%3ClinearGradient id='a' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' stop-color='%2306b6d4'/%3E%3Cstop offset='50%25' stop-color='%233b82f6'/%3E%3Cstop offset='100%25' stop-color='%238b5cf6'/%3E%3C/linearGradient%3E%3ClinearGradient id='b' x1='0%25' y1='100%25' x2='100%25' y2='0%25'%3E%3Cstop offset='0%25' stop-color='%23f8fafc' stop-opacity='.9'/%3E%3Cstop offset='100%25' stop-color='%230f172a' stop-opacity='.2'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect fill='url(%23a)' x='0' y='0' width='640' height='360' rx='36'/%3E%3Cpath fill='url(%23b)' d='M0 280c120-24 180-60 320-60s200 36 320 60v80H0z'/%3E%3Cpath fill='rgba(255,255,255,0.85)' d='M180 124h280c18 0 24 14 12 30l-48 64c-8 12-24 12-32 0l-22-30c-6-8-18-8-24 0l-52 72c-8 12-22 12-30 0l-96-134c-12-16-4-32 12-32z'/%3E%3C/svg%3E";

    const sampleFields = [
        { id: 'bc_author', label: 'Autore principale', type: 'text', text: 'Maria Rossi', defaults: { fontSize: 1.25, align: 'center', fontWeight: '600', margin: { top: 0.8, bottom: 0.3, left: 0.8, right: 0.8 } } },
        { id: 'bc_coauthors', label: 'Co-autori', type: 'text', text: 'Con la collaborazione di Luca Bianchi e Elisa Verdi', defaults: { fontSize: 1, fontStyle: 'italic', align: 'center', color: '#334155', margin: { top: 0, bottom: 0.8, left: 0.8, right: 0.8 } } },
        { id: 'post_title', label: 'Titolo del libro', type: 'text', text: 'Guida completa a Corfù', defaults: { fontSize: 2.6, align: 'center', fontWeight: '700', lineHeight: 1.1, color: '#0f172a', margin: { top: 0.8, bottom: 0.4, left: 0.4, right: 0.4 }, padding: { top: 0.6, bottom: 0.6, left: 1, right: 1 } } },
        { id: 'bc_subtitle', label: 'Sottotitolo', type: 'text', text: "Tra mito e mare: viaggio nell'isola dove Oriente e Occidente si incontrano", defaults: { fontSize: 1.35, align: 'center', fontWeight: '500', color: '#1d4ed8', margin: { top: 0, bottom: 1, left: 0.6, right: 0.6 }, backgroundColor: '#eef2ff', padding: { top: 0.8, bottom: 0.8, left: 1.4, right: 1.4 } } },
        { id: 'bc_publisher_logo', label: 'Immagine editore', type: 'image', imageMaxHeight: 220, defaults: { margin: { top: 0.8, bottom: 0.8, left: 1.6, right: 1.6 }, padding: { top: 0.8, bottom: 0.8, left: 0.8, right: 0.8 }, backgroundColor: '#ffffff' } },
        { id: 'bc_publisher', label: 'Editore', type: 'text', text: 'Sothìa · Infiniti modi di viaggiare', defaults: { fontSize: 1.05, align: 'center', color: '#0f172a', fontWeight: '600', letterSpacing: 0.08, margin: { top: 0.2, bottom: 1.2, left: 0.8, right: 0.8 } } },
        { id: 'bc_dedication', label: 'Dedica', type: 'text', text: 'A chi crede che ogni viaggio inizi da un sogno condiviso.', defaults: { fontSize: 1.05, align: 'center', fontStyle: 'italic', color: '#1f2937', backgroundColor: '#f8fafc' } },
        { id: 'bc_preface', label: 'Prefazione', type: 'text', text: 'Questa guida nasce sul mare Ionio, dove i sentieri di Corfù intrecciano storie di mercanti, filosofi e navigatori. Prima di immergerti nelle sue baie smeraldo, prenditi un momento per ascoltare il respiro dell’isola.', defaults: { fontSize: 1.05, lineHeight: 1.6, color: '#334155', margin: { top: 1.2, bottom: 1, left: 1, right: 1 } } },
        { id: 'bc_acknowledgments', label: 'Ringraziamenti', type: 'text', text: 'Un grazie speciale a chi ha condiviso sentieri, taverne e tramonti con me: le persone del posto che hanno svelato la loro isola, e i lettori che ci hanno scritto per continuare a esplorare.', defaults: { fontSize: 1.02, lineHeight: 1.6, color: '#1f2937', backgroundColor: '#fef9c3', padding: { top: 0.9, bottom: 0.9, left: 1.2, right: 1.2 } } },
        { id: 'bc_description', label: 'Descrizione del libro', type: 'text', text: 'Scopri una Corfù autentica attraverso itinerari tematici, mappe dettagliate e consigli pratici. Ogni capitolo combina curiosità storiche, percorsi naturalistici e degustazioni locali pensate per viaggiatori curiosi.', defaults: { fontSize: 1.05, lineHeight: 1.6, color: '#0f172a', margin: { top: 1, bottom: 1, left: 1, right: 1 } } },
        { id: 'bc_copyright', label: 'Sezione Copyright', type: 'text', text: '© 2024 Sothìa Edizioni. Tutti i diritti riservati. Nessuna parte di questa pubblicazione può essere riprodotta senza il consenso scritto dell’editore.', defaults: { fontSize: 0.95, lineHeight: 1.5, color: '#475569', backgroundColor: '#f8fafc', margin: { top: 0.6, bottom: 0.6, left: 1.2, right: 1.2 } } },
        { id: 'bc_isbn', label: 'Codice ISBN', type: 'text', text: 'ISBN 978-88-12345-678-9', defaults: { fontSize: 0.95, color: '#0f172a', fontWeight: '600', align: 'left', margin: { top: 0, bottom: 1, left: 1.2, right: 1.2 } } },
        { id: 'bc_index', label: 'Indice (struttura generata)', type: 'text', text: 'Prefazione............................................. 9\nCapitolo 1 · Il viaggio ha inizio...................... 17\n   1.1 Preparativi essenziali....................... 20\n   1.2 Arrivi e primi sguardi....................... 28\nCapitolo 2 · Tra mito e mare.......................... 45\nAppendice............................................ 172', defaults: { fontFamily: "'Courier New', monospace", fontSize: 0.95, lineHeight: 1.4, color: '#0f172a', backgroundColor: '#ffffff', margin: { top: 1.2, bottom: 1.2, left: 1.2, right: 1.2 }, padding: { top: 1.2, bottom: 1.2, left: 1.6, right: 1.6 } } },
        { id: 'chapter_title', label: 'Titolo del capitolo', type: 'text', text: 'Capitolo 1 · Il viaggio ha inizio', level: 1, defaults: { fontSize: 1.8, align: 'left', fontWeight: '700', color: '#1d4ed8', margin: { top: 1.4, bottom: 0.4, left: 0.6, right: 0.6 } } },
        { id: 'chapter_content', label: 'Contenuto del capitolo', type: 'text', level: 1, text: 'L’alba su Corfù illumina le pietre della città vecchia mentre il profumo degli agrumi accompagna i primi passi. Questo capitolo propone un itinerario di tre giorni tra i bastioni veneziani, i mercati delle spezie e le calette nascoste verso Paleokastritsa.', defaults: { fontSize: 1.05, lineHeight: 1.7, margin: { top: 0.4, bottom: 0.8, left: 1, right: 1 }, color: '#0f172a' } },
        { id: 'paragraph_title', label: 'Titolo del paragrafo', type: 'text', level: 2, text: '1.1 Preparativi essenziali', defaults: { fontSize: 1.3, fontWeight: '600', color: '#0f172a', margin: { top: 1.2, bottom: 0.4, left: 0.8, right: 0.8 } } },
        { id: 'paragraph_content', label: 'Contenuto del paragrafo', type: 'text', level: 2, text: 'Prima di partire, raccogli una selezione di letture di viaggio, scarica le mappe offline e prepara una playlist che alterni cantautori italiani e canti tradizionali greci. In valigia trova spazio un taccuino resistente alla salsedine per annotare sapori e incontri.', defaults: { fontSize: 1.02, lineHeight: 1.7, margin: { top: 0.2, bottom: 0.6, left: 1.2, right: 1.2 }, color: '#1f2937' } },
        { id: 'bc_footnotes', label: 'Note del paragrafo', type: 'text', level: 2, text: 'Nota 1 — Le linee di autobus interurbane partono ogni 45 minuti dalla stazione di San Rocco.', defaults: { fontSize: 0.9, lineHeight: 1.5, fontStyle: 'italic', color: '#475569', backgroundColor: '#f8fafc', margin: { top: 0.2, bottom: 0.4, left: 1.6, right: 1.6 } } },
        { id: 'bc_citations', label: 'Citazioni del paragrafo', type: 'text', level: 2, text: '«Il viaggio è una porta attraverso la quale si esce dalla realtà per entrare in un universo inesplorato.» — Guy de Maupassant', defaults: { fontSize: 0.98, lineHeight: 1.6, fontStyle: 'italic', align: 'right', color: '#0f172a', margin: { top: 0.4, bottom: 0.8, left: 1.2, right: 1.2 }, backgroundColor: '#e0f2fe' } },
        { id: 'bc_appendix', label: 'Appendice', type: 'text', text: 'Glossario di termini dialettali, consigli per il noleggio scooter e ricette essenziali per riprodurre a casa la cucina ionica.', defaults: { fontSize: 1, lineHeight: 1.6, backgroundColor: '#f1f5f9', margin: { top: 1.4, bottom: 0.6, left: 1.2, right: 1.2 }, padding: { top: 1, bottom: 1, left: 1.4, right: 1.4 } } },
        { id: 'bc_bibliography', label: 'Bibliografia', type: 'text', text: '• K. Papadopoulos, "Isole Ionie Segrete", Atene 2018\n• L. Conti, "Rotte mediterranee", Roma 2021\n• Rivista Thalassa, dossier speciale Corfù, luglio 2023', defaults: { fontSize: 0.98, lineHeight: 1.5, fontFamily: "'Times New Roman', serif", color: '#111827', margin: { top: 0.8, bottom: 0.8, left: 1.2, right: 1.2 }, padding: { top: 0.8, bottom: 0.8, left: 1.2, right: 1.2 } } },
        { id: 'bc_author_note', label: "Nota dell'autore", type: 'text', text: 'Viaggiare è un atto di fiducia: porta con te questo libro come bussola, ma lascia spazio all’imprevisto per trasformare il percorso in narrazione personale.', defaults: { fontSize: 1, lineHeight: 1.6, fontStyle: 'italic', color: '#0f172a', margin: { top: 1.2, bottom: 1.6, left: 1, right: 1 }, align: 'center' } },
    ];

    if (metaLabel) {
        const label = strings.bookInfo ? strings.bookInfo.replace('%1$s', String(sampleFields.length)) : sampleFields.length + ' campi simulati';
        metaLabel.textContent = label;
    }

    const baseStyle = {
        fontSize: 1.1,
        lineHeight: 1.55,
        fontFamily: "'Georgia', serif",
        fontStyle: 'normal',
        fontWeight: '400',
        hyphenate: false,
        align: 'left',
        color: '#1f2937',
        backgroundColor: '#ffffff',
        margin: { top: 0.6, right: 1, bottom: 0.6, left: 1 },
        padding: { top: 0.8, right: 1, bottom: 0.8, left: 1 },
    };

    function cloneSpacing(spacing) {
        return {
            top: typeof spacing.top === 'number' ? spacing.top : 0,
            right: typeof spacing.right === 'number' ? spacing.right : 0,
            bottom: typeof spacing.bottom === 'number' ? spacing.bottom : 0,
            left: typeof spacing.left === 'number' ? spacing.left : 0,
        };
    }

    function mergeStyles(base, override) {
        const style = Object.assign({}, base);
        if (override) {
            Object.keys(override).forEach(function (key) {
                if (key === 'margin' || key === 'padding') {
                    style[key] = cloneSpacing(style[key] || { top: 0, right: 0, bottom: 0, left: 0 });
                    const value = override[key] || {};
                    Object.keys(value).forEach(function (side) {
                        const numeric = parseFloat(value[side]);
                        if (!Number.isNaN(numeric)) {
                            style[key][side] = numeric;
                        }
                    });
                } else {
                    style[key] = override[key];
                }
            });
        }
        if (!style.margin) {
            style.margin = cloneSpacing(base.margin || { top: 0, right: 0, bottom: 0, left: 0 });
        }
        if (!style.padding) {
            style.padding = cloneSpacing(base.padding || { top: 0, right: 0, bottom: 0, left: 0 });
        }
        return style;
    }

    const fieldStyles = {};
    const fieldNodes = {};

    const stage = new Konva.Stage({
        container: container,
        width: BASE_WIDTH,
        height: BASE_HEIGHT,
        listening: true,
    });

    const backgroundLayer = new Konva.Layer({ listening: true });
    const contentLayer = new Konva.Layer();

    stage.add(backgroundLayer);
    stage.add(contentLayer);

    const contentGroup = new Konva.Group({
        x: 0,
        y: 0,
        listening: true,
    });

    contentLayer.add(contentGroup);

    const pageBackground = new Konva.Rect({
        x: 32,
        y: 32,
        width: BASE_WIDTH - 64,
        height: BASE_HEIGHT - 64,
        cornerRadius: 28,
        fill: '#fdfcf8',
        stroke: 'rgba(148, 163, 184, 0.35)',
        strokeWidth: 1,
        shadowColor: 'rgba(15, 23, 42, 0.25)',
        shadowBlur: 42,
        shadowOffsetY: 28,
        shadowOpacity: 0.28,
    });

    const backgroundGradient = new Konva.Rect({
        x: 0,
        y: 0,
        width: BASE_WIDTH,
        height: BASE_HEIGHT,
        fillLinearGradientStartPoint: { x: 0, y: 0 },
        fillLinearGradientEndPoint: { x: BASE_WIDTH, y: BASE_HEIGHT },
        fillLinearGradientColorStops: [0, '#e0f2fe', 0.5, '#f1f5f9', 1, '#e2e8f0'],
    });

    backgroundLayer.add(backgroundGradient);
    backgroundLayer.add(pageBackground);
    backgroundLayer.draw();

    const sampleImage = new window.Image();
    sampleImage.src = sampleImageSrc;

    function createHighlightRect() {
        return new Konva.Rect({
            cornerRadius: 26,
            stroke: '#2563eb',
            strokeWidth: 2,
            dash: [10, 6],
            opacity: 0.9,
            visible: false,
        });
    }

    function computeFontStyle(style) {
        const weight = parseInt(style.fontWeight, 10);
        const isBold = !Number.isNaN(weight) ? weight >= 600 : String(style.fontWeight).toLowerCase() === 'bold';
        const isItalic = String(style.fontStyle).toLowerCase() === 'italic';
        if (isBold && isItalic) {
            return 'italic bold';
        }
        if (isBold) {
            return 'bold';
        }
        if (isItalic) {
            return 'italic';
        }
        return 'normal';
    }

    function getSpacingPx(value, fontSizePx, fallback) {
        const numeric = typeof value === 'number' ? value : parseFloat(value);
        const spacing = Number.isNaN(numeric) ? fallback : numeric;
        return spacing * fontSizePx;
    }

    function getStyle(fieldId) {
        return fieldStyles[fieldId] || mergeStyles(baseStyle, null);
    }

    const textControlElements = [];
    if (toolbar) {
        toolbar.querySelectorAll('[data-text-control]').forEach(function (element) {
            const group = element.closest('.bookcreator-epub-toolbar__group') || element;
            if (textControlElements.indexOf(group) === -1) {
                textControlElements.push(group);
            }
        });
    }

    function toggleTextControls(disabled) {
        if (!toolbar) {
            return;
        }
        const controls = toolbar.querySelectorAll('[data-text-control]');
        controls.forEach(function (control) {
            if ('disabled' in control) {
                control.disabled = disabled;
            }
        });
        textControlElements.forEach(function (element) {
            if (disabled) {
                element.classList.add('is-disabled');
            } else {
                element.classList.remove('is-disabled');
            }
        });
    }

    sampleFields.forEach(function (field) {
        const style = mergeStyles(baseStyle, field.defaults || {});
        fieldStyles[field.id] = style;

        const group = new Konva.Group({
            id: field.id,
            name: 'bookcreator-epub-field',
            listening: true,
        });

        const highlight = createHighlightRect();
        const background = new Konva.Rect({
            cornerRadius: 24,
            stroke: 'rgba(148, 163, 184, 0.35)',
            strokeWidth: 1,
            fill: style.backgroundColor || '#ffffff',
            shadowColor: 'rgba(15, 23, 42, 0.12)',
            shadowBlur: 14,
            shadowOpacity: 0.18,
            shadowOffsetY: 6,
        });

        const label = new Konva.Text({
            text: (field.label || '').toUpperCase(),
            fontFamily: 'Inter, "Segoe UI", sans-serif',
            fontSize: 12,
            letterSpacing: 1,
            fill: 'rgba(15, 23, 42, 0.6)',
            listening: false,
        });

        let textNode = null;
        let imageNode = null;

        if (field.type === 'image') {
            imageNode = new Konva.Image({ listening: false });
            if (sampleImage.complete) {
                imageNode.image(sampleImage);
            } else {
                sampleImage.addEventListener('load', function () {
                    imageNode.image(sampleImage);
                    layoutFields();
                    contentLayer.batchDraw();
                });
            }
        } else {
            textNode = new Konva.Text({
                text: field.text || '',
                fontFamily: style.fontFamily || baseStyle.fontFamily,
                fontSize: Math.max(10, style.fontSize * 16),
                lineHeight: style.lineHeight || baseStyle.lineHeight,
                fill: style.color || baseStyle.color,
                align: style.align || 'left',
                listening: false,
                wrap: style.hyphenate ? 'char' : 'word',
            });
        }

        group.add(highlight);
        group.add(background);
        group.add(label);
        if (textNode) {
            group.add(textNode);
        }
        if (imageNode) {
            group.add(imageNode);
        }

        group.on('mouseenter', function () {
            container.style.cursor = 'pointer';
        });
        group.on('mouseleave', function () {
            container.style.cursor = 'default';
        });
        group.on('click tap', function (event) {
            event.cancelBubble = true;
            selectField(field.id);
        });

        fieldNodes[field.id] = {
            field: field,
            group: group,
            highlight: highlight,
            background: background,
            label: label,
            text: textNode,
            image: imageNode,
        };

        contentGroup.add(group);
    });

    let scrollBounds = { min: 0, max: 0 };
    let scrollY = 0;
    let lastTouchY = null;

    function layoutFields() {
        let cursorY = PAGE_PADDING_TOP;
        const pageWidth = BASE_WIDTH - PAGE_PADDING_X * 2;

        sampleFields.forEach(function (field) {
            const nodes = fieldNodes[field.id];
            if (!nodes) {
                return;
            }
            const style = getStyle(field.id);
            const fontSizePx = Math.max(10, (style.fontSize || baseStyle.fontSize) * 16);
            const lineHeight = style.lineHeight || baseStyle.lineHeight;
            const marginTop = getSpacingPx(style.margin.top, fontSizePx, baseStyle.margin.top);
            const marginRight = getSpacingPx(style.margin.right, fontSizePx, baseStyle.margin.right);
            const marginBottom = getSpacingPx(style.margin.bottom, fontSizePx, baseStyle.margin.bottom);
            const marginLeft = getSpacingPx(style.margin.left, fontSizePx, baseStyle.margin.left);
            const paddingTop = getSpacingPx(style.padding.top, fontSizePx, baseStyle.padding.top);
            const paddingRight = getSpacingPx(style.padding.right, fontSizePx, baseStyle.padding.right);
            const paddingBottom = getSpacingPx(style.padding.bottom, fontSizePx, baseStyle.padding.bottom);
            const paddingLeft = getSpacingPx(style.padding.left, fontSizePx, baseStyle.padding.left);

            const levelOffset = (field.level || 0) * 36;
            const containerX = PAGE_PADDING_X + levelOffset + marginLeft;
            const containerWidth = pageWidth - levelOffset - marginLeft - marginRight;

            nodes.label.x(containerX);
            nodes.label.y(cursorY + marginTop);
            nodes.label.fontSize(Math.max(11, fontSizePx * 0.55));
            nodes.label.opacity(0.68);
            nodes.label.width(containerWidth);
            nodes.label.align('left');

            let blockY = nodes.label.y() + nodes.label.height() + 6;
            let contentHeight = 0;

            nodes.background.x(containerX);
            nodes.background.y(blockY);
            nodes.background.width(containerWidth);
            nodes.background.fill(style.backgroundColor || '#ffffff');

            if (nodes.text) {
                const textNode = nodes.text;
                textNode.text(field.text || '');
                textNode.fontFamily(style.fontFamily || baseStyle.fontFamily);
                textNode.fontSize(fontSizePx);
                textNode.lineHeight(lineHeight);
                textNode.fill(style.color || baseStyle.color);
                textNode.align(style.align || 'left');
                textNode.wrap(style.hyphenate ? 'char' : 'word');
                textNode.fontStyle(computeFontStyle(style));

                const contentWidth = Math.max(32, containerWidth - paddingLeft - paddingRight);
                textNode.width(contentWidth);
                textNode.x(containerX + paddingLeft);
                textNode.y(blockY + paddingTop);

                contentHeight = textNode.height();
                nodes.background.height(contentHeight + paddingTop + paddingBottom);
                nodes.highlight.width(nodes.background.width());
                nodes.highlight.height(nodes.background.height());
                nodes.highlight.x(nodes.background.x());
                nodes.highlight.y(nodes.background.y());
            } else if (nodes.image) {
                const imageNode = nodes.image;
                const image = imageNode.image();
                const maxHeight = typeof field.imageMaxHeight === 'number' ? field.imageMaxHeight : 220;
                const contentWidth = Math.max(64, containerWidth - paddingLeft - paddingRight);

                if (image && image.width && image.height) {
                    const scale = Math.min(contentWidth / image.width, maxHeight / image.height, 1);
                    const displayWidth = image.width * scale;
                    const displayHeight = image.height * scale;
                    imageNode.width(displayWidth);
                    imageNode.height(displayHeight);
                    imageNode.x(containerX + paddingLeft + (contentWidth - displayWidth) / 2);
                    imageNode.y(blockY + paddingTop + (maxHeight - displayHeight) / 2);
                    contentHeight = Math.max(displayHeight, maxHeight);
                } else {
                    imageNode.width(contentWidth * 0.6);
                    imageNode.height(maxHeight * 0.6);
                    imageNode.x(containerX + paddingLeft + contentWidth * 0.2);
                    imageNode.y(blockY + paddingTop + maxHeight * 0.2);
                    contentHeight = maxHeight;
                }

                nodes.background.height(contentHeight + paddingTop + paddingBottom);
                nodes.highlight.width(nodes.background.width());
                nodes.highlight.height(nodes.background.height());
                nodes.highlight.x(nodes.background.x());
                nodes.highlight.y(nodes.background.y());
            }

            cursorY = nodes.background.y() + nodes.background.height() + marginBottom;
        });

        const totalHeight = cursorY;
        const minOffset = Math.min(0, BASE_HEIGHT - PAGE_PADDING_TOP - totalHeight);
        scrollBounds = {
            min: minOffset,
            max: 0,
        };
        scrollY = Math.max(Math.min(scrollY, scrollBounds.max), scrollBounds.min);
        contentGroup.y(scrollY);

        contentLayer.batchDraw();
    }

    layoutFields();

    function fitStage() {
        const width = container.offsetWidth;
        const height = container.offsetHeight;
        if (!width || !height) {
            return;
        }
        const scale = Math.min(width / BASE_WIDTH, height / BASE_HEIGHT);
        stage.width(BASE_WIDTH);
        stage.height(BASE_HEIGHT);
        stage.scale({ x: scale, y: scale });
        stage.batchDraw();
    }

    fitStage();
    window.requestAnimationFrame(fitStage);
    window.addEventListener('resize', Konva.Util.throttle(fitStage, 100));

    let selectedFieldId = null;

    function showToolbar() {
        if (!toolbar) {
            return;
        }
        toolbar.classList.remove('is-hidden');
        toolbar.setAttribute('aria-hidden', 'false');
    }

    function hideToolbar() {
        if (!toolbar) {
            return;
        }
        toolbar.classList.add('is-hidden');
        toolbar.setAttribute('aria-hidden', 'true');
    }

    function updateAlignButtons(activeAlign) {
        if (!toolbar) {
            return;
        }
        toolbar.querySelectorAll('[data-epub-align]').forEach(function (button) {
            if (button.dataset.epubAlign === activeAlign) {
                button.classList.add('is-active');
            } else {
                button.classList.remove('is-active');
            }
        });
    }

    function updateHyphenationButton(active) {
        if (!toolbar) {
            return;
        }
        const button = toolbar.querySelector('[data-epub-control="hyphenate"]');
        if (!button) {
            return;
        }
        if (active) {
            button.classList.add('is-active');
            button.textContent = 'Attivo';
        } else {
            button.classList.remove('is-active');
            button.textContent = 'Attiva';
        }
    }

    function populateToolbar(fieldId) {
        if (!toolbar) {
            return;
        }
        const style = getStyle(fieldId);
        const nodes = fieldNodes[fieldId];
        if (!nodes) {
            return;
        }
        const field = nodes.field;
        if (toolbarFieldLabel) {
            toolbarFieldLabel.textContent = field.label || fieldId;
        }
        const fontSizeInput = toolbar.querySelector('[data-epub-control="font-size"]');
        const lineHeightInput = toolbar.querySelector('[data-epub-control="line-height"]');
        const fontFamilySelect = toolbar.querySelector('[data-epub-control="font-family"]');
        const fontStyleSelect = toolbar.querySelector('[data-epub-control="font-style"]');
        const fontWeightSelect = toolbar.querySelector('[data-epub-control="font-weight"]');
        const colorInput = toolbar.querySelector('[data-epub-control="color"]');
        const backgroundInput = toolbar.querySelector('[data-epub-control="background-color"]');
        const marginTopInput = toolbar.querySelector('[data-epub-control="margin-top"]');
        const marginRightInput = toolbar.querySelector('[data-epub-control="margin-right"]');
        const marginBottomInput = toolbar.querySelector('[data-epub-control="margin-bottom"]');
        const marginLeftInput = toolbar.querySelector('[data-epub-control="margin-left"]');
        const paddingTopInput = toolbar.querySelector('[data-epub-control="padding-top"]');
        const paddingRightInput = toolbar.querySelector('[data-epub-control="padding-right"]');
        const paddingBottomInput = toolbar.querySelector('[data-epub-control="padding-bottom"]');
        const paddingLeftInput = toolbar.querySelector('[data-epub-control="padding-left"]');

        if (fontSizeInput) {
            fontSizeInput.value = (Math.round((style.fontSize || baseStyle.fontSize) * 100) / 100).toFixed(2);
        }
        if (lineHeightInput) {
            lineHeightInput.value = (Math.round((style.lineHeight || baseStyle.lineHeight) * 100) / 100).toFixed(2);
        }
        if (fontFamilySelect) {
            fontFamilySelect.value = style.fontFamily || baseStyle.fontFamily;
        }
        if (fontStyleSelect) {
            fontStyleSelect.value = style.fontStyle || baseStyle.fontStyle;
        }
        if (fontWeightSelect) {
            fontWeightSelect.value = style.fontWeight || baseStyle.fontWeight;
        }
        if (colorInput) {
            colorInput.value = style.color || baseStyle.color;
        }
        if (backgroundInput) {
            backgroundInput.value = style.backgroundColor || baseStyle.backgroundColor;
        }
        if (marginTopInput) {
            marginTopInput.value = (Math.round(style.margin.top * 100) / 100).toFixed(2);
        }
        if (marginRightInput) {
            marginRightInput.value = (Math.round(style.margin.right * 100) / 100).toFixed(2);
        }
        if (marginBottomInput) {
            marginBottomInput.value = (Math.round(style.margin.bottom * 100) / 100).toFixed(2);
        }
        if (marginLeftInput) {
            marginLeftInput.value = (Math.round(style.margin.left * 100) / 100).toFixed(2);
        }
        if (paddingTopInput) {
            paddingTopInput.value = (Math.round(style.padding.top * 100) / 100).toFixed(2);
        }
        if (paddingRightInput) {
            paddingRightInput.value = (Math.round(style.padding.right * 100) / 100).toFixed(2);
        }
        if (paddingBottomInput) {
            paddingBottomInput.value = (Math.round(style.padding.bottom * 100) / 100).toFixed(2);
        }
        if (paddingLeftInput) {
            paddingLeftInput.value = (Math.round(style.padding.left * 100) / 100).toFixed(2);
        }

        const isImage = field.type === 'image';
        toggleTextControls(isImage);

        updateAlignButtons(style.align || 'left');
        updateHyphenationButton(Boolean(style.hyphenate));
    }

    function selectField(fieldId) {
        if (!fieldNodes[fieldId]) {
            return;
        }
        Object.keys(fieldNodes).forEach(function (id) {
            const node = fieldNodes[id];
            if (node.highlight) {
                node.highlight.visible(id === fieldId);
            }
        });
        selectedFieldId = fieldId;
        populateToolbar(fieldId);
        showToolbar();
        contentLayer.batchDraw();
    }

    function deselectField() {
        Object.keys(fieldNodes).forEach(function (id) {
            const node = fieldNodes[id];
            if (node.highlight) {
                node.highlight.visible(false);
            }
        });
        selectedFieldId = null;
        hideToolbar();
        contentLayer.batchDraw();
    }

    stage.on('click tap', function (event) {
        if (event.target === stage || event.target === pageBackground) {
            deselectField();
        }
    });

    stage.on('wheel', function (event) {
        if (!event.evt) {
            return;
        }
        if (typeof event.evt.preventDefault === 'function') {
            event.evt.preventDefault();
        }
        const delta = event.evt.deltaY;
        if (!delta) {
            return;
        }
        scrollY = Math.max(Math.min(scrollY - delta, scrollBounds.max), scrollBounds.min);
        contentGroup.y(scrollY);
        contentLayer.batchDraw();
    });

    stage.on('touchstart', function (event) {
        const touches = event.evt && event.evt.touches ? event.evt.touches : null;
        if (touches && touches.length) {
            lastTouchY = touches[0].clientY;
        }
    });

    stage.on('touchmove', function (event) {
        const touches = event.evt && event.evt.touches ? event.evt.touches : null;
        if (!touches || !touches.length) {
            return;
        }
        const currentY = touches[0].clientY;
        if (lastTouchY == null) {
            lastTouchY = currentY;
            return;
        }
        const delta = lastTouchY - currentY;
        lastTouchY = currentY;
        if (!delta) {
            return;
        }
        scrollY = Math.max(Math.min(scrollY - delta, scrollBounds.max), scrollBounds.min);
        contentGroup.y(scrollY);
        contentLayer.batchDraw();
    });

    stage.on('touchend touchcancel', function () {
        lastTouchY = null;
    });

    if (closeToolbarButton) {
        closeToolbarButton.addEventListener('click', function () {
            deselectField();
        });
    }

    function updateStyle(fieldId, updater) {
        const style = getStyle(fieldId);
        updater(style);
        layoutFields();
    }

    function parsePositive(value, fallback) {
        const numeric = parseFloat(value);
        if (Number.isNaN(numeric) || numeric < 0) {
            return fallback;
        }
        return numeric;
    }

    if (toolbar) {
        toolbar.querySelectorAll('[data-step-control]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (!selectedFieldId) {
                    return;
                }
                const control = button.dataset.stepControl;
                const step = parseFloat(button.dataset.step || '0');
                const input = toolbar.querySelector('[data-epub-control="' + control + '"]');
                if (!input) {
                    return;
                }
                const min = parseFloat(input.min || '0');
                const max = parseFloat(input.max || '100');
                let current = parseFloat(input.value || '0');
                if (Number.isNaN(current)) {
                    current = min || 0;
                }
                let nextValue = current + step;
                if (!Number.isNaN(min)) {
                    nextValue = Math.max(min, nextValue);
                }
                if (!Number.isNaN(max)) {
                    nextValue = Math.min(max, nextValue);
                }
                input.value = (Math.round(nextValue * 100) / 100).toFixed(2);
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });

        const fontSizeInput = toolbar.querySelector('[data-epub-control="font-size"]');
        if (fontSizeInput) {
            fontSizeInput.addEventListener('change', function () {
                if (!selectedFieldId) {
                    return;
                }
                const value = parsePositive(fontSizeInput.value, baseStyle.fontSize);
                fontSizeInput.value = (Math.round(value * 100) / 100).toFixed(2);
                updateStyle(selectedFieldId, function (style) {
                    style.fontSize = value;
                });
            });
        }

        const lineHeightInput = toolbar.querySelector('[data-epub-control="line-height"]');
        if (lineHeightInput) {
            lineHeightInput.addEventListener('change', function () {
                if (!selectedFieldId) {
                    return;
                }
                const value = parsePositive(lineHeightInput.value, baseStyle.lineHeight);
                lineHeightInput.value = (Math.round(value * 100) / 100).toFixed(2);
                updateStyle(selectedFieldId, function (style) {
                    style.lineHeight = value;
                });
            });
        }

        const fontFamilySelect = toolbar.querySelector('[data-epub-control="font-family"]');
        if (fontFamilySelect) {
            fontFamilySelect.addEventListener('change', function () {
                if (!selectedFieldId) {
                    return;
                }
                const value = fontFamilySelect.value || baseStyle.fontFamily;
                updateStyle(selectedFieldId, function (style) {
                    style.fontFamily = value;
                });
            });
        }

        const fontStyleSelect = toolbar.querySelector('[data-epub-control="font-style"]');
        if (fontStyleSelect) {
            fontStyleSelect.addEventListener('change', function () {
                if (!selectedFieldId) {
                    return;
                }
                const value = fontStyleSelect.value || baseStyle.fontStyle;
                updateStyle(selectedFieldId, function (style) {
                    style.fontStyle = value;
                });
            });
        }

        const fontWeightSelect = toolbar.querySelector('[data-epub-control="font-weight"]');
        if (fontWeightSelect) {
            fontWeightSelect.addEventListener('change', function () {
                if (!selectedFieldId) {
                    return;
                }
                const value = fontWeightSelect.value || baseStyle.fontWeight;
                updateStyle(selectedFieldId, function (style) {
                    style.fontWeight = value;
                });
            });
        }

        const colorInput = toolbar.querySelector('[data-epub-control="color"]');
        if (colorInput) {
            colorInput.addEventListener('input', function () {
                if (!selectedFieldId) {
                    return;
                }
                const value = colorInput.value || baseStyle.color;
                updateStyle(selectedFieldId, function (style) {
                    style.color = value;
                });
            });
        }

        const backgroundInput = toolbar.querySelector('[data-epub-control="background-color"]');
        if (backgroundInput) {
            backgroundInput.addEventListener('input', function () {
                if (!selectedFieldId) {
                    return;
                }
                const value = backgroundInput.value || baseStyle.backgroundColor;
                updateStyle(selectedFieldId, function (style) {
                    style.backgroundColor = value;
                });
            });
        }

        toolbar.querySelectorAll('[data-epub-align]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (!selectedFieldId) {
                    return;
                }
                const align = button.dataset.epubAlign || 'left';
                updateStyle(selectedFieldId, function (style) {
                    style.align = align;
                });
                updateAlignButtons(align);
            });
        });

        const hyphenateButton = toolbar.querySelector('[data-epub-control="hyphenate"]');
        if (hyphenateButton) {
            hyphenateButton.addEventListener('click', function () {
                if (!selectedFieldId) {
                    return;
                }
                updateStyle(selectedFieldId, function (style) {
                    style.hyphenate = !style.hyphenate;
                    updateHyphenationButton(style.hyphenate);
                });
            });
        }

        const marginControls = ['margin-top', 'margin-right', 'margin-bottom', 'margin-left'];
        marginControls.forEach(function (control) {
            const input = toolbar.querySelector('[data-epub-control="' + control + '"]');
            if (!input) {
                return;
            }
            input.addEventListener('change', function () {
                if (!selectedFieldId) {
                    return;
                }
                const value = parsePositive(input.value, baseStyle.margin.top);
                input.value = (Math.round(value * 100) / 100).toFixed(2);
                const key = control.split('-')[1];
                updateStyle(selectedFieldId, function (style) {
                    style.margin[key] = value;
                });
            });
        });

        const paddingControls = ['padding-top', 'padding-right', 'padding-bottom', 'padding-left'];
        paddingControls.forEach(function (control) {
            const input = toolbar.querySelector('[data-epub-control="' + control + '"]');
            if (!input) {
                return;
            }
            input.addEventListener('change', function () {
                if (!selectedFieldId) {
                    return;
                }
                const value = parsePositive(input.value, baseStyle.padding.top);
                input.value = (Math.round(value * 100) / 100).toFixed(2);
                const key = control.split('-')[1];
                updateStyle(selectedFieldId, function (style) {
                    style.padding[key] = value;
                });
            });
        });
    }

    if (sampleFields.length) {
        selectField(sampleFields[0].id);
    }
})();

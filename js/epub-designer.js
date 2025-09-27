(function () {
    if (typeof window === 'undefined' || typeof Konva === 'undefined') {
        return;
    }

    const data = window.bookcreatorEpubDesigner || {};
    const container = document.getElementById('bookcreator-epub-designer-stage');

    if (!container) {
        return;
    }

    const booksArray = Array.isArray(data.books) ? data.books : [];
    const booksById = {};

    booksArray.forEach((book) => {
        if (book && typeof book.id !== 'undefined') {
            booksById[String(book.id)] = book;
        }
    });

    const stage = new Konva.Stage({
        container: container,
        width: container.clientWidth,
        height: container.clientHeight,
    });

    const backgroundLayer = new Konva.Layer({ listening: false });
    const pagesLayer = new Konva.Layer();

    stage.add(backgroundLayer);
    stage.add(pagesLayer);

    const emptyMessage = document.getElementById('bookcreator-epub-designer-empty');
    const selector = document.getElementById('bookcreator-epub-designer-book');
    const metaLabel = document.getElementById('bookcreator-epub-designer-book-meta');
    const titleLabel = document.getElementById('bookcreator-epub-designer-selected-title');
    const closeButton = document.querySelector('[data-epub-designer-close]');

    let currentBookId = '';
    let currentScrollY = 0;
    let currentScrollBounds = { min: 0, max: 0 };
    let selectableItems = [];
    let activeHighlight = null;
    let currentContentGroup = null;
    let requestLayoutUpdate = null;
    let lastTouchY = null;

    if (data.initialBookId && booksById[String(data.initialBookId)]) {
        currentBookId = String(data.initialBookId);
    } else if (booksArray[0] && typeof booksArray[0].id !== 'undefined') {
        currentBookId = String(booksArray[0].id);
    }

    if (selector && currentBookId) {
        selector.value = String(currentBookId);
    }

    const strings = Object.assign(
        {
            noBooks: '',
            emptyValue: '',
            bookInfo: '%1$s',
            noPages: '',
            indexBulletPrimary: '•',
            indexBulletSecondary: '◦',
        },
        data.strings || {}
    );

    if (closeButton && data.closeUrl) {
        closeButton.addEventListener('click', function (event) {
            event.preventDefault();
            window.location.href = data.closeUrl;
        });
    }

    const fontFamily = 'Times New Roman, Times, serif';
    const PAGE_PADDING_X = 48;
    const ICON_RADIUS = 14;
    const ICON_CENTER_X = ICON_RADIUS / 2;
    const TOOLTIP_OFFSET_X = ICON_RADIUS * 2 + 18;
    const TEXT_X = PAGE_PADDING_X + 48;
    const BLOCK_SPACING = 18;

    function clampScroll(value) {
        if (!currentScrollBounds) {
            return value;
        }

        const min = typeof currentScrollBounds.min === 'number' ? currentScrollBounds.min : 0;
        const max = typeof currentScrollBounds.max === 'number' ? currentScrollBounds.max : 0;

        if (value < min) {
            return min;
        }

        if (value > max) {
            return max;
        }

        return value;
    }

    function fitStage() {
        const { clientWidth, clientHeight } = container;
        stage.width(clientWidth);
        stage.height(clientHeight);
    }

    function computeLayout() {
        const width = stage.width();
        const height = stage.height();
        const stagePadding = Math.max(48, Math.round(width * 0.06));
        const pageWidth = Math.min(760, width - stagePadding * 2);
        const pageHeight = Math.max(520, height - stagePadding * 2);
        const pageX = (width - pageWidth) / 2;

        return {
            stagePadding,
            pageWidth,
            pageHeight,
            pageX,
        };
    }

    function drawBackground(layout) {
        backgroundLayer.destroyChildren();

        const width = stage.width();
        const height = stage.height();

        backgroundLayer.add(
            new Konva.Rect({
                x: 0,
                y: 0,
                width,
                height,
                fillLinearGradientStartPoint: { x: 0, y: 0 },
                fillLinearGradientEndPoint: { x: 0, y: height },
                fillLinearGradientColorStops: [0, '#f4efe4', 1, '#e6ded0'],
            })
        );

        backgroundLayer.add(
            new Konva.Rect({
                x: layout.pageX - 24,
                y: layout.stagePadding - 24,
                width: layout.pageWidth + 48,
                height: layout.pageHeight + 48,
                stroke: 'rgba(15, 23, 42, 0.08)',
                strokeWidth: 1,
                cornerRadius: 32,
                shadowColor: 'rgba(15, 23, 42, 0.12)',
                shadowBlur: 48,
                shadowOffsetX: 0,
                shadowOffsetY: 24,
                shadowOpacity: 0.8,
            })
        );

        backgroundLayer.draw();
    }

    function toggleEmptyState(visible, message) {
        if (!emptyMessage) {
            return;
        }

        if (visible) {
            const content = typeof message === 'string' ? message : strings.noBooks;

            if (content) {
                emptyMessage.classList.add('is-visible');
                emptyMessage.textContent = content;
            } else {
                emptyMessage.classList.remove('is-visible');
                emptyMessage.textContent = '';
            }
        } else {
            emptyMessage.classList.remove('is-visible');
            emptyMessage.textContent = '';
        }
    }

    function getBook(bookId) {
        if (!bookId) {
            return null;
        }

        return booksById[String(bookId)] || null;
    }

    function getBookPages(book) {
        if (!book) {
            return [];
        }

        return Array.isArray(book.pages) ? book.pages : [];
    }

    function getSequentialBlocks(book) {
        const pages = getBookPages(book);
        const blocks = [];

        pages.forEach(function (page) {
            if (!page || !Array.isArray(page.blocks)) {
                return;
            }

            page.blocks.forEach(function (block) {
                if (!block) {
                    return;
                }

                blocks.push(block);
            });
        });

        return blocks;
    }

    function isBlockVisible(block) {
        if (!block) {
            return false;
        }

        if (block.type === 'image') {
            return Boolean(block.url);
        }

        return Boolean(formatFieldValue(block));
    }

    function countVisibleBlocks(book) {
        const blocks = getSequentialBlocks(book);

        return blocks.reduce(function (total, block) {
            return total + (isBlockVisible(block) ? 1 : 0);
        }, 0);
    }

    function formatIndexValue(block) {
        if (!block || !block.value) {
            return '';
        }

        try {
            const items = JSON.parse(block.value);
            if (!Array.isArray(items) || !items.length) {
                return '';
            }

            return items
                .map((item) => {
                    if (!item || typeof item.text !== 'string' || !item.text.trim()) {
                        return '';
                    }
                    const level = typeof item.level === 'number' ? item.level : 0;
                    const bullet = level > 0 ? strings.indexBulletSecondary : strings.indexBulletPrimary;
                    const indent = level > 0 ? '    '.repeat(level) : '';

                    return indent + bullet + ' ' + item.text.trim();
                })
                .filter(Boolean)
                .join('\n');
        } catch (error) {
            return '';
        }
    }

    function formatFieldValue(block) {
        if (!block) {
            return strings.emptyValue;
        }

        if (block.format === 'index') {
            return formatIndexValue(block);
        }

        const rawValue = typeof block.value === 'string' ? block.value : block && block.value != null ? String(block.value) : '';
        const trimmedValue = rawValue.trim();

        return trimmedValue;
    }

    function createInfoIcon(label) {
        const icon = new Konva.Group({ listening: true });
        const circle = new Konva.Circle({
            radius: 14,
            stroke: '#0d7a36',
            strokeWidth: 1,
            fill: '#16a34a',
            shadowColor: 'rgba(0, 0, 0, 0.18)',
            shadowBlur: 6,
            shadowOpacity: 0.6,
            shadowOffsetX: 0,
            shadowOffsetY: 2,
        });
        icon.add(circle);
        icon.add(
            new Konva.Text({
                text: 'i',
                fontFamily,
                fontSize: 14,
                fill: '#ffffff',
                fontStyle: '700',
                x: -3.4,
                y: -7.2,
            })
        );

        const tooltip = new Konva.Label({
            visible: false,
            listening: false,
            opacity: 0.94,
        });

        tooltip.add(
            new Konva.Tag({
                fill: '#0f172a',
                pointerDirection: 'left',
                pointerWidth: 8,
                pointerHeight: 8,
                cornerRadius: 2,
            })
        );

        tooltip.add(
            new Konva.Text({
                text: label || '',
                fontFamily,
                fontSize: 12,
                fill: '#ffffff',
                padding: 6,
            })
        );

        return { icon, tooltip };
    }

    function attachTooltip(node, tooltip) {
        if (!node || !tooltip) {
            return;
        }

        const showTooltip = function () {
            tooltip.visible(true);
            container.style.cursor = 'help';
            pagesLayer.batchDraw();
        };

        const hideTooltip = function () {
            tooltip.visible(false);
            container.style.cursor = 'default';
            pagesLayer.batchDraw();
        };

        node.on('mouseenter', showTooltip);
        node.on('mouseleave', hideTooltip);
        node.on('touchstart', function () {
            tooltip.visible(!tooltip.visible());
            pagesLayer.batchDraw();
        });
    }

    function registerSelectable(group, highlight) {
        if (!group) {
            return;
        }

        selectableItems.push({ group: group, highlight: highlight || null });

        const activate = function () {
            selectableItems.forEach(function (item) {
                if (item.highlight && item.highlight !== highlight) {
                    item.highlight.visible(false);
                }
            });

            if (highlight) {
                const nextState = !highlight.visible();
                selectableItems.forEach(function (item) {
                    if (item.highlight) {
                        item.highlight.visible(false);
                    }
                });
                highlight.visible(nextState);
                activeHighlight = nextState ? highlight : null;
            } else {
                activeHighlight = null;
            }

            pagesLayer.batchDraw();
        };

        group.on('mouseenter', function () {
            container.style.cursor = 'pointer';
        });

        group.on('mouseleave', function () {
            container.style.cursor = 'default';
        });

        group.on('click tap', activate);

        if (highlight) {
            highlight.visible(false);
        }
    }

    function buildFieldGroup(block, layout, baseY) {
        const value = formatFieldValue(block);

        if (!value) {
            return null;
        }

        const group = new Konva.Group({
            x: 0,
            y: baseY,
            listening: true,
        });

        const highlight = new Konva.Rect({
            x: TEXT_X - 28,
            y: -12,
            width: layout.pageWidth - TEXT_X - PAGE_PADDING_X + 56,
            height: 0,
            cornerRadius: 14,
            fill: 'rgba(148, 163, 184, 0.22)',
            visible: false,
        });
        group.add(highlight);

        const info = createInfoIcon(block.label || '');
        info.icon.x(ICON_CENTER_X);
        info.tooltip.x(TOOLTIP_OFFSET_X);
        info.tooltip.y(-18);
        group.add(info.icon);
        group.add(info.tooltip);

        const valueText = new Konva.Text({
            x: TEXT_X,
            y: 0,
            text: value,
            fontFamily,
            fontSize: 18,
            lineHeight: 1.6,
            fill: '#0f172a',
            width: layout.pageWidth - TEXT_X - PAGE_PADDING_X,
        });

        group.add(valueText);

        info.icon.y(valueText.y() + 16);
        info.tooltip.y(valueText.y() - 18);

        highlight.height(valueText.height() + 24);

        attachTooltip(info.icon, info.tooltip);
        attachTooltip(valueText, info.tooltip);

        return {
            group,
            highlight,
            getHeight: function () {
                return valueText.height() + BLOCK_SPACING;
            },
        };
    }

    function buildImageGroup(block, layout, baseY) {
        if (!block.url) {
            return null;
        }

        const group = new Konva.Group({
            x: 0,
            y: baseY,
            listening: true,
        });

        const highlight = new Konva.Rect({
            x: TEXT_X - 28,
            y: -12,
            width: layout.pageWidth - TEXT_X - PAGE_PADDING_X + 56,
            height: 0,
            cornerRadius: 14,
            fill: 'rgba(148, 163, 184, 0.22)',
            visible: false,
        });
        group.add(highlight);

        const info = createInfoIcon(block.label || '');
        info.icon.x(ICON_CENTER_X);
        info.tooltip.x(TOOLTIP_OFFSET_X);
        info.tooltip.y(-18);
        group.add(info.icon);
        group.add(info.tooltip);

        const availableWidth = layout.pageWidth - TEXT_X - PAGE_PADDING_X;
        const maxHeight = Math.min(320, layout.pageHeight * 0.6);
        const frame = new Konva.Rect({
            x: TEXT_X,
            y: 0,
            width: availableWidth,
            height: maxHeight,
            stroke: 'rgba(100, 116, 139, 0.55)',
            strokeWidth: 1,
            dash: [5, 5],
            cornerRadius: 12,
            listening: false,
        });
        const imageNode = new Konva.Image({
            x: TEXT_X,
            y: 0,
            listening: false,
        });

        group.add(frame);
        group.add(imageNode);

        highlight.height(maxHeight + 32);
        info.icon.y(20);

        let computedHeight = maxHeight + BLOCK_SPACING;

        const image = new window.Image();

        image.onload = function () {
            const scale = Math.min(availableWidth / image.width, maxHeight / image.height, 1);
            const displayWidth = image.width * scale;
            const displayHeight = image.height * scale;

            imageNode.image(image);
            imageNode.width(displayWidth);
            imageNode.height(displayHeight);
            imageNode.x(TEXT_X + (availableWidth - displayWidth) / 2);
            imageNode.y((maxHeight - displayHeight) / 2);

            frame.width(displayWidth + 40);
            frame.height(displayHeight + 40);
            frame.x(TEXT_X + (availableWidth - frame.width()) / 2);
            frame.y(imageNode.y() - 20);

            info.icon.y(frame.y() + 20);
            info.tooltip.y(frame.y() - 18);

            highlight.height(frame.height() + 32);

            computedHeight = frame.y() + frame.height() + BLOCK_SPACING;

            if (typeof requestLayoutUpdate === 'function') {
                requestLayoutUpdate();
            }

            pagesLayer.batchDraw();
        };

        image.onerror = function () {
            group.destroy();
            if (typeof requestLayoutUpdate === 'function') {
                requestLayoutUpdate();
            }
            pagesLayer.batchDraw();
        };

        image.src = block.url;

        attachTooltip(info.icon, info.tooltip);

        return {
            group,
            highlight,
            getHeight: function () {
                return computedHeight;
            },
        };
    }

    function createBookView(book, layout) {
        const wrapper = new Konva.Group({
            x: layout.pageX,
            y: layout.stagePadding,
            width: layout.pageWidth,
            height: layout.pageHeight,
            clip: {
                x: 0,
                y: 0,
                width: layout.pageWidth,
                height: layout.pageHeight,
            },
        });

        const background = new Konva.Rect({
            x: 0,
            y: 0,
            width: layout.pageWidth,
            height: layout.pageHeight,
            fill: '#fbfaf5',
            stroke: 'rgba(30, 41, 59, 0.28)',
            strokeWidth: 1,
            cornerRadius: 18,
            listening: false,
            shadowColor: 'rgba(15, 23, 42, 0.22)',
            shadowBlur: 36,
            shadowOpacity: 0.35,
            shadowOffsetX: 0,
            shadowOffsetY: 26,
        });

        const contentGroup = new Konva.Group({
            x: 0,
            y: 0,
            listening: true,
        });

        wrapper.add(background);
        wrapper.add(contentGroup);

        const layoutBlocks = function () {
            const totalHeight = PAGE_PADDING_X * 2;

            background.height(Math.max(layout.pageHeight, totalHeight));

            currentScrollBounds = {
                min: 0,
                max: 0,
            };

            contentGroup.y(0);

            requestLayoutUpdate = layoutBlocks;
        };

        layoutBlocks();

        return {
            group: wrapper,
            content: contentGroup,
        };
    }

    stage.on('wheel', function (event) {
        if (!currentContentGroup) {
            return;
        }

        if (event.evt && typeof event.evt.preventDefault === 'function') {
            event.evt.preventDefault();
        }

        const delta = event.evt && typeof event.evt.deltaY === 'number' ? event.evt.deltaY : 0;

        if (!delta) {
            return;
        }

        currentScrollY = clampScroll(currentScrollY - delta);
        currentContentGroup.y(currentScrollY);
        pagesLayer.batchDraw();
    });

    stage.on('touchstart', function (event) {
        const touches = event.evt && event.evt.touches ? event.evt.touches : null;

        if (touches && touches.length) {
            lastTouchY = touches[0].clientY;
        }
    });

    stage.on('touchmove', function (event) {
        if (!currentContentGroup) {
            return;
        }

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

        currentScrollY = clampScroll(currentScrollY - delta);
        currentContentGroup.y(currentScrollY);
        pagesLayer.batchDraw();
    });

    stage.on('touchend touchcancel', function () {
        lastTouchY = null;
    });


    function updateToolbar(bookId) {
        const book = getBook(bookId);

        if (titleLabel) {
            titleLabel.textContent = book ? book.title : '';
        }

        if (metaLabel) {
            const count = book ? countVisibleBlocks(book) : 0;
            metaLabel.textContent = book ? strings.bookInfo.replace('%1$s', String(count)) : '';
        }
    }

    function renderBook(bookId, layout) {
        pagesLayer.destroyChildren();

        const book = getBook(bookId);

        if (!bookId || !book) {
            toggleEmptyState(true, strings.noBooks);
            currentContentGroup = null;
            currentScrollBounds = { min: 0, max: 0 };
            currentScrollY = 0;
            selectableItems = [];
            activeHighlight = null;
            requestLayoutUpdate = null;
            lastTouchY = null;
            pagesLayer.draw();
            return;
        }

        toggleEmptyState(false);

        selectableItems = [];
        activeHighlight = null;
        requestLayoutUpdate = null;
        lastTouchY = null;

        const view = createBookView(book, layout);

        currentContentGroup = view.content;
        currentScrollY = clampScroll(currentScrollY);
        currentContentGroup.y(currentScrollY);

        pagesLayer.add(view.group);
        pagesLayer.draw();
    }

    function refresh() {
        fitStage();
        const layout = computeLayout();
        drawBackground(layout);
        renderBook(currentBookId, layout);
    }

    if (selector) {
        selector.addEventListener('change', function (event) {
            const value = event.target.value;
            currentBookId = value && booksById[String(value)] ? String(value) : '';
            currentScrollY = 0;
            lastTouchY = null;
            updateToolbar(currentBookId);
            refresh();
        });
    }

    updateToolbar(currentBookId);
    refresh();

    const throttledResize = Konva.Util.throttle(function () {
        refresh();
    }, 200);

    window.addEventListener('resize', throttledResize);
})();

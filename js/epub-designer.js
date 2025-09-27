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
    const prevButton = document.querySelector('[data-epub-designer-prev]');
    const nextButton = document.querySelector('[data-epub-designer-next]');
    const navTitle = document.getElementById('bookcreator-epub-designer-page-title');
    const navIndicator = document.getElementById('bookcreator-epub-designer-page-indicator');

    let currentBookId = '';
    let currentPageIndex = 0;

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
            pageIndicator: 'Pagina %1$s di %2$s',
            pageTitleFallback: 'Pagina %1$s',
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
    const PAGE_PADDING_X = 32;
    const TEXT_X = 72;

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
                fill: '#e2e8f0',
            })
        );

        backgroundLayer.add(
            new Konva.Rect({
                x: layout.pageX - 24,
                y: layout.stagePadding - 24,
                width: layout.pageWidth + 48,
                height: layout.pageHeight + 48,
                stroke: 'rgba(15, 23, 42, 0.15)',
                strokeWidth: 1,
                dash: [6, 6],
                cornerRadius: 0,
            })
        );

        backgroundLayer.draw();
    }

    function toggleEmptyState(visible, message) {
        if (!emptyMessage) {
            return;
        }

        if (visible) {
            emptyMessage.classList.add('is-visible');
            emptyMessage.textContent = message || strings.noBooks;
        } else {
            emptyMessage.classList.remove('is-visible');
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
            const formattedIndex = formatIndexValue(block);
            return formattedIndex || strings.emptyValue;
        }

        const rawValue = typeof block.value === 'string' ? block.value : block && block.value != null ? String(block.value) : '';
        const trimmedValue = rawValue.trim();

        return trimmedValue ? trimmedValue : strings.emptyValue;
    }

    function createInfoIcon(label) {
        const icon = new Konva.Group({ listening: true });
        const circle = new Konva.Circle({
            radius: 10,
            stroke: '#0f172a',
            strokeWidth: 1,
            fill: '#ffffff',
        });
        icon.add(circle);
        icon.add(
            new Konva.Text({
                text: 'i',
                fontFamily,
                fontSize: 12,
                fill: '#0f172a',
                x: -3.6,
                y: -6.4,
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

    function buildFieldGroup(block, layout, baseY) {
        const group = new Konva.Group({
            x: 0,
            y: baseY,
            listening: true,
        });

        const info = createInfoIcon(block.label || '');
        info.icon.x(PAGE_PADDING_X);
        info.icon.y(4);
        info.tooltip.x(PAGE_PADDING_X + 24);
        info.tooltip.y(-8);
        group.add(info.icon);
        group.add(info.tooltip);

        const valueText = new Konva.Text({
            x: TEXT_X,
            y: 0,
            text: formatFieldValue(block),
            fontFamily,
            fontSize: 16,
            lineHeight: 1.5,
            fill: '#000000',
            width: layout.pageWidth - TEXT_X - PAGE_PADDING_X,
        });

        group.add(valueText);

        attachTooltip(info.icon, info.tooltip);
        attachTooltip(valueText, info.tooltip);

        return {
            group,
            height: valueText.height() + 32,
        };
    }

    function buildImageGroup(block, layout, baseY) {
        const group = new Konva.Group({
            x: 0,
            y: baseY,
            listening: true,
        });

        const info = createInfoIcon(block.label || '');
        info.icon.x(PAGE_PADDING_X);
        info.icon.y(4);
        info.tooltip.x(PAGE_PADDING_X + 24);
        info.tooltip.y(-8);
        group.add(info.icon);
        group.add(info.tooltip);

        const availableWidth = layout.pageWidth - TEXT_X - PAGE_PADDING_X;
        const maxHeight = Math.min(240, layout.pageHeight / 3);
        let groupHeight = maxHeight + 32;

        if (block.url) {
            const frame = new Konva.Rect({
                x: TEXT_X,
                y: 0,
                width: availableWidth,
                height: maxHeight,
                stroke: '#0f172a',
                strokeWidth: 1,
                listening: false,
            });

            const imageNode = new Konva.Image({
                x: TEXT_X,
                y: 0,
                listening: false,
            });

            group.add(frame);
            group.add(imageNode);

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

                frame.width(displayWidth + 24);
                frame.height(displayHeight + 24);
                frame.x(TEXT_X + (availableWidth - frame.width()) / 2);
                frame.y(imageNode.y() - 12);

                groupHeight = frame.y() + frame.height() + 20;
                pagesLayer.batchDraw();
            };

            image.onerror = function () {
                frame.destroy();
                imageNode.destroy();
                const text = new Konva.Text({
                    x: TEXT_X,
                    y: 0,
                    width: availableWidth,
                    text: strings.emptyValue,
                    fontFamily,
                    fontSize: 16,
                    lineHeight: 1.4,
                    fill: '#000000',
                });
                group.add(text);
                groupHeight = text.height() + 32;
                pagesLayer.batchDraw();
            };

            image.src = block.url;
        } else {
            const text = new Konva.Text({
                x: TEXT_X,
                y: 0,
                width: availableWidth,
                text: strings.emptyValue,
                fontFamily,
                fontSize: 16,
                lineHeight: 1.4,
                fill: '#000000',
            });
            group.add(text);
            groupHeight = text.height() + 32;
        }

        attachTooltip(info.icon, info.tooltip);

        return {
            group,
            height: groupHeight,
        };
    }

    function createPage(page, layout, animateDirection, animate) {
        const group = new Konva.Group({
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

        group.add(
            new Konva.Rect({
                x: 0,
                y: 0,
                width: layout.pageWidth,
                height: layout.pageHeight,
                fill: '#ffffff',
                stroke: '#0f172a',
                strokeWidth: 1,
                cornerRadius: 0,
                listening: false,
            })
        );

        let currentY = 32;
        const pageTitle = page && typeof page.title === 'string' ? page.title.trim() : '';

        if (pageTitle) {
            const titleNode = new Konva.Text({
                x: PAGE_PADDING_X,
                y: currentY,
                text: pageTitle,
                fontFamily,
                fontSize: 22,
                lineHeight: 1.2,
                fill: '#000000',
                width: layout.pageWidth - PAGE_PADDING_X * 2,
            });
            group.add(titleNode);
            currentY += titleNode.height() + 24;
        } else {
            currentY += 8;
        }

        const blocks = Array.isArray(page.blocks) ? page.blocks : [];
        let lastGroup = null;

        if (!blocks.length) {
            group.add(
                new Konva.Text({
                    x: PAGE_PADDING_X,
                    y: layout.pageHeight / 2 - 20,
                    width: layout.pageWidth - PAGE_PADDING_X * 2,
                    text: strings.emptyValue,
                    fontFamily,
                    fontSize: 16,
                    lineHeight: 1.4,
                    fill: '#000000',
                    align: 'center',
                })
            );
        } else {
            blocks.forEach((block) => {
                if (!block) {
                    return;
                }

                const marginTop = typeof block.marginTop === 'number' ? block.marginTop : 0;

                if (block.group && block.group !== lastGroup && block.groupName) {
                    const heading = new Konva.Text({
                        x: TEXT_X,
                        y: currentY,
                        text: block.groupName,
                        fontFamily,
                        fontSize: 16,
                        lineHeight: 1.4,
                        fill: '#000000',
                        width: layout.pageWidth - TEXT_X - PAGE_PADDING_X,
                    });
                    group.add(heading);
                    currentY += heading.height() + 12;
                    lastGroup = block.group;
                } else if (!block.group) {
                    lastGroup = null;
                }

                currentY += marginTop;

                let result;
                if (block.type === 'image') {
                    result = buildImageGroup(block, layout, currentY);
                } else {
                    result = buildFieldGroup(block, layout, currentY);
                }

                group.add(result.group);
                currentY += result.height;
            });
        }

        if (animate) {
            const direction = typeof animateDirection === 'number' && animateDirection !== 0 ? animateDirection : 1;
            const initialX = layout.pageX + direction * layout.pageWidth * 0.25;
            group.x(initialX);
            group.opacity(0);

            new Konva.Tween({
                node: group,
                duration: 0.35,
                x: layout.pageX,
                opacity: 1,
                easing: Konva.Easings.EaseInOut,
            }).play();
        }

        return group;
    }

    function updateToolbar(bookId) {
        const book = getBook(bookId);

        if (titleLabel) {
            titleLabel.textContent = book ? book.title : '';
        }

        if (metaLabel) {
            const count = book ? getBookPages(book).length : 0;
            metaLabel.textContent = book ? strings.bookInfo.replace('%1$s', String(count)) : '';
        }
    }

    function updateNavigation(bookId) {
        const book = getBook(bookId);
        const pages = getBookPages(book);
        const total = pages.length;

        if (prevButton) {
            prevButton.disabled = total <= 1 || currentPageIndex <= 0;
        }

        if (nextButton) {
            nextButton.disabled = total <= 1 || currentPageIndex >= total - 1;
        }

        if (navIndicator) {
            if (!total) {
                navIndicator.textContent = strings.noPages || '';
            } else {
                const current = currentPageIndex + 1;
                navIndicator.textContent = strings.pageIndicator
                    .replace('%1$s', String(current))
                    .replace('%2$s', String(total));
            }
        }

        if (navTitle) {
            if (!total) {
                navTitle.textContent = '';
            } else {
                const currentPage = pages[currentPageIndex] || {};
                const fallback = strings.pageTitleFallback.replace('%1$s', String(currentPageIndex + 1));
                const title = currentPage.title && currentPage.title.trim() ? currentPage.title.trim() : fallback;
                navTitle.textContent = title;
            }
        }
    }

    function renderBook(bookId, layout, animate, animateDirection) {
        pagesLayer.destroyChildren();

        const book = getBook(bookId);
        const pages = getBookPages(book);

        if (!bookId || !book) {
            toggleEmptyState(true, strings.noBooks);
            updateNavigation(bookId);
            pagesLayer.draw();
            return;
        }

        if (!pages.length) {
            toggleEmptyState(true, strings.noPages);
            updateNavigation(bookId);
            pagesLayer.draw();
            return;
        }

        toggleEmptyState(false);

        if (currentPageIndex >= pages.length) {
            currentPageIndex = pages.length - 1;
        }

        if (currentPageIndex < 0) {
            currentPageIndex = 0;
        }

        const page = pages[currentPageIndex] || {};
        const pageGroup = createPage(page, layout, animateDirection, animate);

        pagesLayer.add(pageGroup);
        pagesLayer.draw();
        updateNavigation(bookId);
    }

    function refresh(animate, direction) {
        fitStage();
        const layout = computeLayout();
        drawBackground(layout);
        renderBook(currentBookId, layout, animate, direction);
    }

    if (selector) {
        selector.addEventListener('change', function (event) {
            const value = event.target.value;
            currentBookId = value && booksById[String(value)] ? String(value) : '';
            currentPageIndex = 0;
            updateToolbar(currentBookId);
            refresh(true, 1);
        });
    }

    if (prevButton) {
        prevButton.addEventListener('click', function () {
            const book = getBook(currentBookId);
            const pages = getBookPages(book);

            if (!pages.length || currentPageIndex <= 0) {
                return;
            }

            currentPageIndex -= 1;
            refresh(true, -1);
        });
    }

    if (nextButton) {
        nextButton.addEventListener('click', function () {
            const book = getBook(currentBookId);
            const pages = getBookPages(book);

            if (!pages.length || currentPageIndex >= pages.length - 1) {
                return;
            }

            currentPageIndex += 1;
            refresh(true, 1);
        });
    }

    updateToolbar(currentBookId);
    refresh(false, 0);

    const throttledResize = Konva.Util.throttle(function () {
        refresh(false, 0);
    }, 200);

    window.addEventListener('resize', throttledResize);
})();

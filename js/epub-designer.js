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

    if (data.initialBookId && booksById[data.initialBookId]) {
        currentBookId = data.initialBookId;
    } else if (booksArray[0]) {
        currentBookId = booksArray[0].id;
    }

    if (selector && currentBookId) {
        selector.value = String(currentBookId);
    }

    const strings = Object.assign(
        {
            noBooks: '',
            emptyValue: '',
            bookInfo: '%1$s',
            leftPage: 'Left page',
            rightPage: 'Right page',
        },
        data.strings || {}
    );

    if (closeButton && data.closeUrl) {
        closeButton.addEventListener('click', function (event) {
            event.preventDefault();
            window.location.href = data.closeUrl;
        });
    }

    function fitStage() {
        const { clientWidth, clientHeight } = container;
        stage.width(clientWidth);
        stage.height(clientHeight);
    }

    function computeLayout() {
        const width = stage.width();
        const height = stage.height();
        const stagePadding = Math.max(48, Math.min(96, Math.round(width * 0.08)));
        const gutter = Math.max(40, Math.min(80, Math.round(width * 0.06)));
        const pageWidth = Math.max(280, (width - stagePadding * 2 - gutter) / 2);
        const pageHeight = Math.max(360, height - stagePadding * 2);

        return {
            stagePadding,
            gutter,
            pageWidth,
            pageHeight,
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
                fillLinearGradientEndPoint: { x: width, y: height },
                fillLinearGradientColorStops: [0, '#f8fafc', 0.4, '#eff6ff', 1, '#e0f2fe'],
            })
        );

        backgroundLayer.add(
            new Konva.Rect({
                x: layout.stagePadding - 32,
                y: layout.stagePadding - 48,
                width: width - (layout.stagePadding - 32) * 2,
                height: height - (layout.stagePadding - 32) * 2,
                cornerRadius: 48,
                stroke: 'rgba(148, 163, 184, 0.25)',
                strokeWidth: 2,
                dash: [12, 16],
                opacity: 0.6,
            })
        );

        backgroundLayer.add(
            new Konva.Rect({
                x: width / 2 - 12,
                y: layout.stagePadding - 24,
                width: 24,
                height: height - (layout.stagePadding - 24) * 2,
                fillLinearGradientStartPoint: { x: 0, y: 0 },
                fillLinearGradientEndPoint: { x: 24, y: 0 },
                fillLinearGradientColorStops: [0, 'rgba(96, 165, 250, 0)', 0.5, 'rgba(96, 165, 250, 0.25)', 1, 'rgba(96, 165, 250, 0)'],
            })
        );

        backgroundLayer.draw();
    }

    function buildFieldGroup(field, layout, baseY, palette) {
        const group = new Konva.Group({
            x: 0,
            y: baseY,
            listening: true,
        });

        const label = new Konva.Label({
            x: 40,
            y: 0,
            listening: false,
        });

        label.add(
            new Konva.Tag({
                fill: palette.tagFill,
                cornerRadius: 10,
                pointerWidth: 0,
                pointerHeight: 0,
                stroke: palette.tagStroke,
                strokeWidth: 1,
            })
        );

        label.add(
            new Konva.Text({
                text: (field.label || '').toUpperCase(),
                fontSize: 11,
                fontStyle: 'bold',
                fill: palette.tagText,
                padding: 8,
                letterSpacing: 1.4,
            })
        );

        group.add(label);

        const valueText = new Konva.Text({
            x: 40,
            y: label.height() + 10,
            text: field.value && field.value.length ? field.value : strings.emptyValue,
            fontSize: 14,
            lineHeight: 1.4,
            fill: palette.valueText,
            width: layout.pageWidth - 80,
        });

        const highlight = new Konva.Rect({
            x: 32,
            y: label.height() + 2,
            width: layout.pageWidth - 64,
            height: valueText.height() + 26,
            cornerRadius: 18,
            fill: palette.highlightFill,
            opacity: 0,
            listening: false,
        });

        group.add(highlight);
        group.add(valueText);

        const underline = new Konva.Line({
            points: [40, label.height() + valueText.height() + 28, layout.pageWidth - 40, label.height() + valueText.height() + 28],
            stroke: palette.divider,
            strokeWidth: 1,
            dash: [4, 6],
            opacity: 0.35,
        });

        group.add(underline);

        group.on('mouseenter', function () {
            container.style.cursor = 'pointer';
            highlight.to({ opacity: 1, duration: 0.18, easing: Konva.Easings.EaseInOut });
        });

        group.on('mouseleave', function () {
            container.style.cursor = 'default';
            highlight.to({ opacity: 0, duration: 0.22, easing: Konva.Easings.EaseInOut });
        });

        group.cache();

        return {
            group,
            height: label.height() + 10 + valueText.height() + 40,
        };
    }

    function createPage(fields, layout, x, palette, animateDirection, animate) {
        const group = new Konva.Group({
            x: x,
            y: layout.stagePadding,
            width: layout.pageWidth,
            height: layout.pageHeight,
            clip: { x: 0, y: 0, width: layout.pageWidth, height: layout.pageHeight },
        });

        group.add(
            new Konva.Rect({
                x: 0,
                y: 0,
                width: layout.pageWidth,
                height: layout.pageHeight,
                cornerRadius: 32,
                fillLinearGradientStartPoint: { x: 0, y: 0 },
                fillLinearGradientEndPoint: { x: layout.pageWidth, y: layout.pageHeight },
                fillLinearGradientColorStops: [0, '#ffffff', 1, '#f8fafc'],
                shadowColor: 'rgba(15, 23, 42, 0.35)',
                shadowBlur: 60,
                shadowOffset: { x: 0, y: 26 },
                shadowOpacity: 0.28,
            })
        );

        group.add(
            new Konva.Rect({
                x: 0,
                y: 0,
                width: layout.pageWidth,
                height: 14,
                fillLinearGradientStartPoint: { x: 0, y: 0 },
                fillLinearGradientEndPoint: { x: layout.pageWidth, y: 0 },
                fillLinearGradientColorStops: [0, palette.ribbonStart, 1, palette.ribbonEnd],
                opacity: 0.6,
            })
        );

        group.add(
            new Konva.Text({
                x: 40,
                y: 24,
                text: palette.pageTitle,
                fontSize: 14,
                fontStyle: 'bold',
                fill: palette.pageTitleColor,
                letterSpacing: 1.2,
                opacity: 0.85,
            })
        );

        let currentY = 80;

        fields.forEach((field) => {
            const result = buildFieldGroup(field, layout, currentY, palette);
            group.add(result.group);
            currentY += result.height;
        });

        if (!fields.length) {
            const placeholder = new Konva.Text({
                x: 40,
                y: layout.pageHeight / 2 - 20,
                width: layout.pageWidth - 80,
                text: strings.emptyValue,
                fontSize: 16,
                fontStyle: 'italic',
                fill: 'rgba(71, 85, 105, 0.85)',
                align: 'center',
            });
            group.add(placeholder);
        }

        const pageBadge = new Konva.Group({
            x: layout.pageWidth - 96,
            y: layout.pageHeight - 72,
        });

        const badgeCircle = new Konva.Circle({
            radius: 24,
            fill: palette.badgeFill,
            stroke: palette.badgeStroke,
            strokeWidth: 1,
            shadowColor: 'rgba(37, 99, 235, 0.35)',
            shadowBlur: 18,
            shadowOpacity: 0.4,
        });

        const badgeText = new Konva.Text({
            text: animateDirection < 0 ? 'L' : 'R',
            fontSize: 16,
            fontStyle: 'bold',
            fill: palette.badgeText,
            align: 'center',
            width: badgeCircle.radius() * 2,
            height: badgeCircle.radius() * 2,
            offsetX: badgeCircle.radius(),
            offsetY: badgeCircle.radius(),
        });

        badgeText.x(badgeCircle.x());
        badgeText.y(badgeCircle.y());

        pageBadge.add(badgeCircle);
        pageBadge.add(badgeText);
        group.add(pageBadge);

        if (animate) {
            const initialX = x + animateDirection * 80;
            group.x(initialX);
            group.opacity(0);
            new Konva.Tween({
                node: group,
                duration: 0.45,
                x: x,
                opacity: 1,
                easing: Konva.Easings.EaseInOut,
            }).play();
        }

        return group;
    }

    function renderBook(bookId, layout, animate) {
        pagesLayer.destroyChildren();

        if (!bookId || !booksById[bookId]) {
            toggleEmptyState(true);
            pagesLayer.draw();
            return;
        }

        toggleEmptyState(false);

        const book = booksById[bookId];
        const fields = Array.isArray(book.fields) ? book.fields : [];
        const midpoint = Math.ceil(fields.length / 2);
        const leftFields = fields.slice(0, midpoint);
        const rightFields = fields.slice(midpoint);

        const leftPalette = {
            pageTitle: strings.leftPage,
            pageTitleColor: '#1d4ed8',
            ribbonStart: 'rgba(59, 130, 246, 0.65)',
            ribbonEnd: 'rgba(59, 130, 246, 0.25)',
            tagFill: 'rgba(59, 130, 246, 0.12)',
            tagStroke: 'rgba(37, 99, 235, 0.35)',
            tagText: '#1d4ed8',
            highlightFill: 'rgba(191, 219, 254, 0.5)',
            valueText: '#0f172a',
            divider: 'rgba(148, 163, 184, 0.7)',
            badgeFill: 'rgba(191, 219, 254, 0.35)',
            badgeStroke: 'rgba(59, 130, 246, 0.45)',
            badgeText: '#1d4ed8',
        };

        const rightPalette = {
            pageTitle: strings.rightPage,
            pageTitleColor: '#0e7490',
            ribbonStart: 'rgba(14, 165, 233, 0.65)',
            ribbonEnd: 'rgba(14, 165, 233, 0.15)',
            tagFill: 'rgba(14, 165, 233, 0.12)',
            tagStroke: 'rgba(13, 148, 136, 0.35)',
            tagText: '#0e7490',
            highlightFill: 'rgba(125, 211, 252, 0.45)',
            valueText: '#083344',
            divider: 'rgba(94, 114, 135, 0.6)',
            badgeFill: 'rgba(125, 211, 252, 0.3)',
            badgeStroke: 'rgba(14, 165, 233, 0.4)',
            badgeText: '#0e7490',
        };

        const leftX = (stage.width() - (layout.pageWidth * 2 + layout.gutter)) / 2;
        const rightX = leftX + layout.pageWidth + layout.gutter;

        const leftPage = createPage(leftFields, layout, leftX, leftPalette, -1, animate);
        const rightPage = createPage(rightFields, layout, rightX, rightPalette, 1, animate);

        pagesLayer.add(leftPage);
        pagesLayer.add(rightPage);

        pagesLayer.draw();
    }

    function updateToolbar(bookId) {
        const book = bookId ? booksById[bookId] : null;

        if (titleLabel) {
            titleLabel.textContent = book ? book.title : '';
        }

        if (metaLabel) {
            const count = book && Array.isArray(book.fields) ? book.fields.length : 0;
            metaLabel.textContent = book ? strings.bookInfo.replace('%1$s', count) : '';
        }
    }

    function toggleEmptyState(visible) {
        if (!emptyMessage) {
            return;
        }

        if (visible) {
            emptyMessage.classList.add('is-visible');
            emptyMessage.textContent = strings.noBooks;
        } else {
            emptyMessage.classList.remove('is-visible');
        }
    }

    function refresh(animate) {
        fitStage();
        const layout = computeLayout();
        drawBackground(layout);
        renderBook(currentBookId, layout, animate);
    }

    if (selector) {
        selector.addEventListener('change', function (event) {
            const value = event.target.value;
            currentBookId = value && booksById[value] ? value : '';
            updateToolbar(currentBookId);
            refresh(true);
        });
    }

    if (!currentBookId) {
        toggleEmptyState(true);
    }

    updateToolbar(currentBookId);
    refresh(false);

    const throttledResize = Konva.Util.throttle(function () {
        refresh(false);
    }, 200);

    window.addEventListener('resize', throttledResize);
})();

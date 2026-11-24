<style>
    /* ===== SCROLLABLE & DRAGGABLE MARKDOWN TABLES ===== */
    .ai-table-container {
        margin: 8px 0;
        border: 1px solid rgba(5, 150, 105, 0.22);
        border-radius: 10px;
        background: #ffffff;
        overflow: hidden;
        max-width: 100%;
    }

    .ai-table-scroll {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        cursor: grab;
        scrollbar-width: thin;
        scrollbar-color: #10b981 #ecfdf5;
    }

    .ai-table-scroll.is-dragging {
        cursor: grabbing;
        user-select: none;
    }

    .ai-table-scroll::-webkit-scrollbar {
        height: 7px;
    }

    .ai-table-scroll::-webkit-scrollbar-track {
        background: #ecfdf5;
    }

    .ai-table-scroll::-webkit-scrollbar-thumb {
        background: #10b981;
        border-radius: 999px;
    }

    .ai-table-scroll::-webkit-scrollbar-thumb:hover {
        background: #059669;
    }

    .ai-table-scroll table {
        width: 100%;
        min-width: max-content;
        border-collapse: collapse;
        font-size: 0.78rem;
        text-align: left;
    }

    .ai-table-scroll th {
        background: rgba(5, 150, 105, 0.12);
        color: #065f46;
        padding: 7px 10px;
        border-bottom: 1px solid rgba(5, 150, 105, 0.22);
        font-weight: 700;
        white-space: nowrap;
    }

    .ai-table-scroll td {
        padding: 6px 10px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        vertical-align: top;
        white-space: nowrap;
    }

    .ai-table-scroll tr:nth-child(even) td {
        background: rgba(5, 150, 105, 0.03);
    }

    .ai-table-scroll tr:last-child td {
        border-bottom: none;
    }

    .ai-table-hint {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 5px;
        padding: 4px 10px;
        font-size: 0.68rem;
        font-weight: 600;
        color: #047857;
        background: #f0fdf4;
        border-top: 1px solid rgba(5, 150, 105, 0.12);
        user-select: none;
    }
</style>

<script>
    window.PharmAIMarkdown = (function () {
        function formatMarkdown(text) {
            if (!text) return '';
            let s = text
                .replace(/\r\n/g, '\n')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            const formatInline = (cell) => cell
                .replace(/`([^`]+)`/g, '<code style="background:rgba(0,0,0,0.06);padding:1px 4px;border-radius:4px;font-size:0.85em;">$1</code>')
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/(?<!\*)\*(?!\*)(.*?)(?<!\*)\*(?!\*)/g, '<em>$1</em>');

            const isTableLine = (line) => line.trim().startsWith('|');
            const isSeparatorLine = (line) => /^\|[\s:\-\|]*-{2,}[\s:\-\|]*$/.test(line.trim());
            const parseCells = (line) => line.trim().replace(/^\|/, '').replace(/\|$/, '').split('|').map(c => c.trim());

            const rawLines = s.split('\n');
            const outLines = [];
            const tablePlaceholders = [];

            for (let i = 0; i < rawLines.length; i++) {
                const headerTrim = rawLines[i].trim();
                if (
                    i + 1 < rawLines.length
                    && headerTrim.startsWith('|')
                    && headerTrim.endsWith('|')
                    && isSeparatorLine(rawLines[i + 1])
                ) {
                    const headers = parseCells(rawLines[i]);
                    const bodyRows = [];
                    let j = i + 2;
                    while (j < rawLines.length && isTableLine(rawLines[j])) {
                        bodyRows.push(parseCells(rawLines[j]));
                        j++;
                    }

                    let html = '<div class="ai-table-container"><div class="ai-table-scroll" title="Klik & geser atau scroll ke samping"><table><thead><tr>';
                    headers.forEach(h => {
                        html += '<th>' + formatInline(h) + '</th>';
                    });
                    html += '</tr></thead><tbody>';
                    bodyRows.forEach(cols => {
                        html += '<tr>';
                        headers.forEach((_, ci) => {
                            html += '<td>' + formatInline(cols[ci] ?? '') + '</td>';
                        });
                        html += '</tr>';
                    });
                    html += '</tbody></table></div><div class="ai-table-hint"><i class="fas fa-arrows-alt-h"></i> Geser tabel ke kiri / kanan</div></div>';

                    const token = '%%AI_TABLE_' + tablePlaceholders.length + '%%';
                    tablePlaceholders.push(html);
                    outLines.push(token);
                    i = j - 1;
                } else {
                    outLines.push(rawLines[i]);
                }
            }

            s = outLines.join('\n');

            // Horizontal rule ---
            s = s.replace(/^\s*-{3,}\s*$/gm, '<hr style="border:none;border-top:1px solid rgba(0,0,0,0.1);margin:8px 0;">');
            // Headings ### or ##
            s = s.replace(/^###\s+(.+)$/gm, '<div style="font-weight:700;font-size:0.95rem;margin:8px 0 4px;">$1</div>');
            s = s.replace(/^##\s+(.+)$/gm, '<div style="font-weight:800;font-size:1rem;margin:8px 0 4px;">$1</div>');
            // Inline code `code`
            s = s.replace(/`([^`]+)`/g, '<code style="background:rgba(0,0,0,0.06);padding:1px 5px;border-radius:4px;font-size:0.85em;">$1</code>');
            // Bullet list (* or -) at line start
            s = s.replace(/^\s*[-*]\s+/gm, '• ');
            // Bold **text**
            s = s.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            // Italic *text*
            s = s.replace(/(?<!\*)\*(?!\*)(.*?)(?<!\*)\*(?!\*)/g, '<em>$1</em>');
            // Line break
            s = s.replace(/\n/g, '<br>');

            tablePlaceholders.forEach((tblHtml, idx) => {
                s = s.replace('%%AI_TABLE_' + idx + '%%', tblHtml);
            });

            return s;
        }

        function streamReplyToBubble(bubble, fullText, scrollEl) {
            return new Promise((resolve) => {
                if (!fullText) {
                    bubble.innerHTML = '';
                    resolve();
                    return;
                }

                const tokens = fullText.split(/(\s+)/);
                let currentIndex = 0;
                let accumulated = '';
                const totalTokens = tokens.length;
                const step = totalTokens > 160 ? 3 : (totalTokens > 80 ? 2 : 1);
                const intervalMs = 22;

                function tick() {
                    if (currentIndex >= totalTokens) {
                        bubble.innerHTML = formatMarkdown(fullText);
                        if (scrollEl) scrollEl.scrollTop = scrollEl.scrollHeight;
                        resolve();
                        return;
                    }

                    const end = Math.min(currentIndex + step, totalTokens);
                    for (let i = currentIndex; i < end; i++) {
                        accumulated += tokens[i];
                    }
                    currentIndex = end;

                    let preview = accumulated;
                    const boldMatches = preview.match(/\*\*/g);
                    if (boldMatches && boldMatches.length % 2 !== 0) {
                        preview += '**';
                    }
                    const codeMatches = preview.match(/`/g);
                    if (codeMatches && codeMatches.length % 2 !== 0) {
                        preview += '`';
                    }

                    const prevScrolls = Array.from(bubble.querySelectorAll('.ai-table-scroll')).map(el => el.scrollLeft);
                    bubble.innerHTML = formatMarkdown(preview) + '<span class="ai-stream-cursor"></span>';
                    if (prevScrolls.length) {
                        bubble.querySelectorAll('.ai-table-scroll').forEach((el, idx) => {
                            if (typeof prevScrolls[idx] === 'number') el.scrollLeft = prevScrolls[idx];
                        });
                    }
                    if (scrollEl) {
                        scrollEl.scrollTop = scrollEl.scrollHeight;
                    }

                    setTimeout(tick, intervalMs);
                }

                tick();
            });
        }

        function bindTableDragScroll(rootEl) {
            if (!rootEl || rootEl.dataset.aiTableDragBound === '1') return;
            rootEl.dataset.aiTableDragBound = '1';

            let activeDragTable = null;
            let dragStartX = 0;
            let dragStartScrollLeft = 0;

            rootEl.addEventListener('mousedown', (e) => {
                const scrollContainer = e.target.closest('.ai-table-scroll');
                if (!scrollContainer) return;
                activeDragTable = scrollContainer;
                dragStartX = e.pageX - scrollContainer.offsetLeft;
                dragStartScrollLeft = scrollContainer.scrollLeft;
                scrollContainer.classList.add('is-dragging');
            });

            window.addEventListener('mousemove', (e) => {
                if (!activeDragTable) return;
                e.preventDefault();
                const x = e.pageX - activeDragTable.offsetLeft;
                const walk = (x - dragStartX) * 1.3;
                activeDragTable.scrollLeft = dragStartScrollLeft - walk;
            });

            const stopTableDrag = () => {
                if (!activeDragTable) return;
                activeDragTable.classList.remove('is-dragging');
                activeDragTable = null;
            };
            window.addEventListener('mouseup', stopTableDrag);
            rootEl.addEventListener('mouseleave', stopTableDrag);
        }

        return { formatMarkdown, streamReplyToBubble, bindTableDragScroll };
    })();
</script>

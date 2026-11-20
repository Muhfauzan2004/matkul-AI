{{-- FLOATING BUTTON TRIGGER (POJOK KANAN BAWAH) --}}
<button type="button" class="ai-floating-button" id="aiFloatingBtn" onclick="toggleAiDrawer()" title="Buka PharmAI - APOTEK ITU TOKO OBAT">
    <i class="fas fa-robot" id="aiBtnIcon"></i>
    <span class="ai-btn-pulse"></span>
</button>

{{-- BACKDROP OVERLAY (HANYA UNTUK MOBILE / TABLET < 992px) --}}
<div class="ai-drawer-backdrop" id="aiDrawerBackdrop" onclick="toggleAiDrawer(false)"></div>

{{-- RIGHT-SIDE AI DRAWER PANEL (MUNCUL DI SEBELAH KANAN DASHBOARD) --}}
<aside class="ai-drawer" id="aiDrawer" aria-label="PharmAI Panel">
    <!-- Header Panel Utama -->
    <div class="ai-drawer-header">
        <div class="ai-drawer-brand">
            <div class="ai-avatar-badge">
                <i class="fas fa-robot"></i>
                <span class="ai-online-dot"></span>
            </div>
            <div class="ai-drawer-title">
                <h4 id="aiHeaderTitle">PharmAI</h4>
                <span><i class="fas fa-check-circle" style="color: #4ade80; font-size: 0.65rem;"></i> Asisten Pintar &bull; APOTEK ITU TOKO OBAT</span>
            </div>
        </div>
        <div class="ai-drawer-actions">
            <!-- Tombol Riwayat Chat (Menggantikan Tombol Refresh) -->
            <button type="button" class="ai-icon-btn" onclick="toggleHistoryView()" id="aiHistoryBtn" title="Lihat Riwayat Chat">
                <i class="fas fa-history" id="aiHistoryBtnIcon"></i>
            </button>
            <!-- Tombol Layar Penuh -->
            <a href="{{ route('ai.chat.page') }}?fullscreen=1" class="ai-icon-btn" title="Buka Layar Penuh">
                <i class="fas fa-expand-alt"></i>
            </a>
            <!-- Tombol Tutup -->
            <button type="button" class="ai-icon-btn ai-close-btn" onclick="toggleAiDrawer(false)" title="Tutup Panel">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- 1. TAMPILAN CHAT AKTIF (DEFAULT) -->
    <div class="ai-view-container" id="aiChatView">
        <!-- Area Percakapan Chat -->
        <div class="ai-drawer-body" id="aiChatBox">
            <div class="ai-loading-history" id="aiHistoryLoading">
                <i class="fas fa-circle-notch fa-spin"></i> Memuat percakapan...
            </div>
        </div>

        <!-- Form Input Chat -->
        <form class="ai-drawer-footer" id="aiChatForm" onsubmit="handleAiChatSubmit(event)">
            <div class="ai-input-wrap">
                <textarea
                    id="aiMessageInput"
                    class="ai-textarea"
                    rows="1"
                    placeholder="Masukkan pertanyaan Anda"
                    maxlength="2000"
                    required
                ></textarea>
                <button type="submit" class="ai-send-btn" id="aiSendBtn" title="Kirim Pesan (Enter)">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <div class="ai-input-hint">
                <span>Tekan <strong>Enter</strong> untuk kirim &bull; <strong>Shift+Enter</strong> baris baru</span>
            </div>
        </form>
    </div>

    <!-- 2. TAMPILAN RIWAYAT LENGKAP (HISTORY VIEW) -->
    <div class="ai-view-container" id="aiHistoryView" style="display: none;">
        <!-- Toolbar Riwayat -->
        <div class="ai-history-toolbar">
            <button type="button" class="ai-history-back-btn" onclick="toggleHistoryView(false)">
                <i class="fas fa-arrow-left"></i> Kembali ke Chat
            </button>
        </div>

        <!-- Tombol Chat Baru -->
        <div class="ai-history-actions-bar">
            <button type="button" class="ai-btn-new-chat" onclick="startNewChat()">
                <i class="fas fa-plus-circle"></i> Mulai Percakapan Baru
            </button>
        </div>

        <!-- Search Bar Riwayat -->
        <div class="ai-history-search-wrap">
            <i class="fas fa-search ai-history-search-icon"></i>
            <input
                type="text"
                id="aiHistorySearchInput"
                class="ai-history-search-input"
                placeholder="Cari riwayat keluhan atau obat..."
                oninput="filterHistoryList(this.value)"
            >
        </div>

        <!-- Container Daftar Riwayat -->
        <div class="ai-history-list" id="aiHistoryList">
            <div class="ai-loading-history">
                <i class="fas fa-circle-notch fa-spin"></i> Memuat riwayat...
            </div>
        </div>
    </div>
</aside>

<style>
    /* ===== FLOATING TRIGGER BUTTON ===== */
    .ai-floating-button {
        position: fixed;
        right: 25px;
        bottom: 25px;
        width: 58px;
        height: 58px;
        border-radius: 50%;
        background: linear-gradient(135deg, #059669, #10b981);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        border: none;
        cursor: pointer;
        box-shadow: 0 6px 22px rgba(5, 150, 105, 0.44);
        z-index: 1030;
        opacity: 1;
        visibility: visible;
        transform: translateY(0) scale(1);
        transition: transform 0.95s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.85s ease, visibility 0.95s, box-shadow 0.3s ease;
    }

    .ai-floating-button i,
    .ai-btn-pulse {
        pointer-events: none;
    }

    .ai-floating-button:hover {
        transform: translateY(-4px) scale(1.08);
        box-shadow: 0 10px 28px rgba(5, 150, 105, 0.58);
    }

    .ai-floating-button:active {
        transform: translateY(0) scale(0.96);
    }

    .ai-btn-pulse {
        position: absolute;
        top: -3px;
        right: -3px;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #10b981;
        border: 2px solid #ffffff;
        animation: aiPulse 2s infinite;
    }

    @keyframes aiPulse {
        0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
        100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    /* ===== BACKDROP (HANYA AKTIF DI MOBILE LAYAR KECIL < 992px, TANPA BLUR) ===== */
    .ai-drawer-backdrop {
        display: none;
    }

    @media (max-width: 991px) {
        .ai-drawer-backdrop {
            display: block;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.35);
            z-index: 1040;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease;
        }

        .ai-drawer-backdrop.active {
            opacity: 1;
            pointer-events: auto;
        }
    }

    /* ===== RIGHT-SIDE AI DRAWER PANEL ===== */
    .ai-drawer {
        position: fixed;
        top: 0;
        bottom: 0;
        right: 0;
        left: auto;
        width: 420px;
        background: #ffffff;
        z-index: 1050;
        display: flex;
        flex-direction: column;
        border-left: 1px solid var(--gray-200, #e2e8f0);
        box-shadow: -6px 0 28px rgba(3, 4, 94, 0.12);
        transform: translateX(100%);
        opacity: 0;
        pointer-events: none;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.25s ease;
    }

    .ai-drawer.open {
        transform: translateX(0);
        opacity: 1;
        pointer-events: auto;
    }

    /* ===== MINI-SIDEBAR & MAIN LAYOUT ADJUSTMENT ON AI OPEN ===== */
    @media (min-width: 769px) {
        /* Transisi halus dua arah (buka maupun tutup) untuk sidebar dan area utama */
        .sidebar {
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            overflow-x: hidden !important;
        }

        .admin-main {
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1), margin-right 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        /* Saat AI Drawer terbuka: Sidebar mengecil menjadi mode ringkas/ikon */
        body.ai-drawer-open .sidebar {
            width: 72px !important;
        }

        body.ai-drawer-open .admin-main {
            margin-left: 72px !important;
        }

        /* Rapikan header logo & brand di sidebar */
        body.ai-drawer-open .sidebar-header {
            padding: 1.25rem 0.5rem !important;
            text-align: center !important;
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
        }

        body.ai-drawer-open .sidebar-brand {
            justify-content: center !important;
            font-size: 0 !important;
            gap: 0 !important;
            padding: 0 !important;
        }

        body.ai-drawer-open .sidebar-brand i {
            font-size: 1.35rem !important;
            margin: 0 !important;
        }

        body.ai-drawer-open .sidebar-brand-text,
        body.ai-drawer-open .sidebar-brand span:not(.sidebar-badge) {
            display: none !important;
        }

        /* Sembunyikan label kategori dan footer sidebar saat mini */
        body.ai-drawer-open .sidebar-label,
        body.ai-drawer-open .sidebar-footer {
            display: none !important;
        }

        /* Rampingkan link item menu & pusatkan ikon */
        body.ai-drawer-open .sidebar-nav {
            padding: 0.75rem 0.5rem !important;
        }

        body.ai-drawer-open .sidebar-link {
            justify-content: center !important;
            padding: 0.75rem 0.5rem !important;
            gap: 0 !important;
            font-size: 0 !important;
            position: relative !important;
            border-radius: 10px !important;
            margin-bottom: 6px !important;
        }

        body.ai-drawer-open .sidebar-link:hover {
            transform: none !important;
        }

        body.ai-drawer-open .sidebar-link i {
            width: auto !important;
            font-size: 1.15rem !important;
            margin: 0 !important;
            text-align: center !important;
        }

        body.ai-drawer-open .sidebar-link-text {
            display: none !important;
        }

        /* Badge notifikasi angka (pending) dibuat bubble pojok kanan atas ikon */
        body.ai-drawer-open .sidebar-link .sidebar-badge,
        body.ai-drawer-open .sidebar-link > span:not(.sidebar-link-text) {
            position: absolute !important;
            top: 4px !important;
            right: 6px !important;
            margin: 0 !important;
            padding: 0.1rem 0.35rem !important;
            font-size: 0.62rem !important;
            line-height: 1 !important;
            border-radius: 999px !important;
            min-width: 16px !important;
            text-align: center !important;
            z-index: 2 !important;
        }
    }

    /* Push admin-main on desktop/laptop (>= 1200px) */
    @media (min-width: 1200px) {
        body.ai-drawer-open .admin-main {
            margin-right: 420px !important;
        }
    }

    /* Saat AI Chat terbuka: Icon terbang ke atas perlahan dan menghilang secara halus */
    body.ai-drawer-open .ai-floating-button {
        transform: translateY(-110px) scale(0.3);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        box-shadow: none;
        transition: transform 0.8s cubic-bezier(0.25, 0.1, 0.25, 1), opacity 0.65s ease, visibility 0.8s;
    }

    /* ===== VIEW CONTAINERS ===== */
    .ai-view-container {
        display: flex;
        flex-direction: column;
        flex: 1;
        min-height: 0;
    }

    /* ===== HEADER ===== */
    .ai-drawer-header {
        background: linear-gradient(135deg, #022c22 0%, #065f46 55%, #059669 100%);
        color: #ffffff;
        padding: 14px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
    }

    .ai-drawer-brand {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .ai-avatar-badge {
        position: relative;
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.18);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        color: #a7f3d0;
    }

    .ai-online-dot {
        position: absolute;
        bottom: -2px;
        right: -2px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #4ade80;
        border: 2px solid #065f46;
    }

    .ai-drawer-title h4 {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
        color: #ffffff;
        letter-spacing: -0.01em;
    }

    .ai-drawer-title span {
        font-size: 0.72rem;
        color: #d1fae5;
        display: flex;
        align-items: center;
        gap: 4px;
        margin-top: 1px;
    }

    .ai-drawer-actions {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .ai-icon-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.12);
        color: #ffffff;
        border: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        text-decoration: none;
        font-size: 0.85rem;
        transition: background 0.15s, transform 0.15s;
    }

    .ai-icon-btn:hover {
        background: rgba(255, 255, 255, 0.25);
        color: #ffffff;
        transform: translateY(-1px);
    }

    .ai-icon-btn.active {
        background: #ffffff;
        color: #0077b6;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    .ai-close-btn:hover {
        background: #ef4444;
    }

    /* ===== CHAT BODY ===== */
    .ai-drawer-body {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        padding: 16px 14px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        background: #f8fafc;
    }

    .ai-loading-history {
        text-align: center;
        font-size: 0.8rem;
        color: #64748b;
        padding: 24px 0;
    }

    .ai-msg {
        display: flex;
        gap: 8px;
        max-width: 90%;
        animation: aiFadeIn 0.2s ease-out;
    }

    @keyframes aiFadeIn {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .ai-msg.user {
        align-self: flex-end;
        flex-direction: row-reverse;
    }

    .ai-msg.bot {
        align-self: flex-start;
    }

    .ai-msg.ai-highlight .ai-bubble {
        animation: aiHighlightPulse 1.5s ease;
    }

    @keyframes aiHighlightPulse {
        0%, 100% { box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        50% { box-shadow: 0 0 0 4px rgba(0, 119, 182, 0.4); transform: scale(1.02); }
    }

    .ai-msg-avatar {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: #059669;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .ai-bubble {
        padding: 10px 14px;
        border-radius: 14px;
        font-size: 0.84rem;
        line-height: 1.55;
        word-break: break-word;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .ai-msg.user .ai-bubble {
        background: linear-gradient(135deg, #059669, #10b981);
        color: #ffffff;
        border-bottom-right-radius: 3px;
    }

    .ai-msg.bot .ai-bubble {
        background: #ffffff;
        color: #1e293b;
        border: 1px solid #e2e8f0;
        border-bottom-left-radius: 3px;
    }

    .ai-bubble strong {
        font-weight: 700;
        color: inherit;
    }

    .ai-bubble hr {
        border: none;
        border-top: 1px solid rgba(0, 0, 0, 0.08);
        margin: 8px 0;
    }

    .ai-thinking-dots {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 6px;
    }

    .ai-thinking-dots span {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #059669;
        animation: aiBlink 1.4s infinite both;
    }

    .ai-thinking-dots span:nth-child(2) { animation-delay: 0.2s; }
    .ai-thinking-dots span:nth-child(3) { animation-delay: 0.4s; }

    @keyframes aiBlink {
        0%, 80%, 100% { opacity: 0.2; transform: scale(0.8); }
        40% { opacity: 1; transform: scale(1.2); }
    }

    /* ===== FOOTER INPUT ===== */
    .ai-drawer-footer {
        padding: 10px 14px 12px;
        background: #ffffff;
        border-top: 1px solid #e2e8f0;
        flex-shrink: 0;
    }

    .ai-input-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        padding: 6px 8px 6px 12px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .ai-input-wrap:focus-within {
        border-color: #059669;
        box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.16);
        background: #ffffff;
    }

    .ai-textarea {
        flex: 1;
        border: none;
        background: transparent;
        resize: none;
        outline: none;
        font-family: inherit;
        font-size: 0.85rem;
        line-height: 1.4;
        max-height: 100px;
        color: #1e293b;
        padding: 6px 4px;
        text-align: center;
    }

    .ai-textarea::placeholder {
        text-align: center;
        color: #94a3b8;
    }

    .ai-send-btn {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: #059669;
        color: #ffffff;
        border: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 0.9rem;
        flex-shrink: 0;
        transition: background 0.15s, transform 0.15s;
    }

    .ai-send-btn:hover {
        background: #065f46;
        transform: scale(1.04);
    }

    .ai-send-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }

    .ai-input-hint {
        margin-top: 5px;
        font-size: 0.68rem;
        color: #94a3b8;
        text-align: center;
    }

    /* ===== TAMPILAN RIWAYAT (HISTORY VIEW) STYLES ===== */
    .ai-history-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 14px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }

    .ai-history-back-btn {
        background: transparent;
        border: none;
        color: #059669;
        font-weight: 700;
        font-size: 0.82rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 4px 6px;
        border-radius: 6px;
        transition: background 0.15s;
    }

    .ai-history-back-btn:hover {
        background: rgba(5, 150, 105, 0.08);
    }

    .ai-history-clear-btn {
        background: transparent;
        border: none;
        color: #ef4444;
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 4px;
        padding: 4px 6px;
        border-radius: 6px;
        transition: background 0.15s;
    }

    .ai-history-clear-btn:hover {
        background: #fee2e2;
    }

    .ai-history-actions-bar {
        padding: 10px 14px;
        background: #ffffff;
        border-bottom: 1px solid #f1f5f9;
    }

    .ai-btn-new-chat {
        width: 100%;
        padding: 8px 12px;
        border-radius: 8px;
        background: linear-gradient(135deg, #059669, #10b981);
        color: #ffffff;
        border: none;
        font-size: 0.82rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        box-shadow: 0 2px 6px rgba(0, 119, 182, 0.25);
        transition: transform 0.15s, box-shadow 0.15s;
    }

    .ai-btn-new-chat:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 119, 182, 0.35);
    }

    .ai-history-search-wrap {
        padding: 8px 14px;
        background: #ffffff;
        position: relative;
        border-bottom: 1px solid #e2e8f0;
    }

    .ai-history-search-input {
        width: 100%;
        padding: 7px 10px 7px 32px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        font-size: 0.8rem;
        outline: none;
        box-sizing: border-box;
        transition: border-color 0.15s;
    }

    .ai-history-search-input:focus {
        border-color: #0077b6;
    }

    .ai-history-search-icon {
        position: absolute;
        left: 24px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 0.75rem;
        color: #94a3b8;
    }

    .ai-history-list {
        flex: 1;
        overflow-y: auto;
        padding: 12px 14px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        background: #f8fafc;
    }

    .ai-history-item {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 12px;
        cursor: pointer;
        transition: all 0.15s ease;
        text-align: left;
    }

    .ai-history-item:hover {
        border-color: #059669;
        box-shadow: 0 3px 10px rgba(5, 150, 105, 0.12);
        transform: translateY(-1px);
    }

    .ai-history-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 5px;
        gap: 8px;
    }

    .ai-history-meta-right {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
    }

    .ai-history-delete-btn {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        border: none;
        background: transparent;
        color: #94a3b8;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.74rem;
        cursor: pointer;
        opacity: 0;
        pointer-events: none;
        transform: scale(0.88);
        transition: all 0.18s ease;
    }

    .ai-history-item:hover .ai-history-delete-btn,
    .ai-history-item:focus-within .ai-history-delete-btn {
        opacity: 1;
        pointer-events: auto;
        transform: scale(1);
    }

    .ai-history-delete-btn:hover {
        background: #fee2e2;
        color: #ef4444;
    }

    @media (hover: none) {
        .ai-history-delete-btn {
            opacity: 0.8;
            pointer-events: auto;
            transform: scale(1);
        }
    }

    .ai-history-badge {
        font-size: 0.68rem;
        font-weight: 700;
        color: #059669;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .ai-history-time {
        font-size: 0.68rem;
        color: #94a3b8;
    }

    .ai-history-question {
        margin: 0 0 4px 0;
        font-size: 0.82rem;
        font-weight: 600;
        color: #1e293b;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .ai-history-answer-preview {
        margin: 0;
        font-size: 0.75rem;
        color: #64748b;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .ai-history-empty {
        text-align: center;
        padding: 40px 15px;
        color: #94a3b8;
        font-size: 0.82rem;
    }

    .ai-search-highlight {
        background: #fef08a;
        color: #713f12;
        font-weight: 700;
        padding: 0 3px;
        border-radius: 4px;
        box-shadow: 0 0 0 1px rgba(234, 179, 8, 0.35);
    }

    .ai-msg.user .ai-bubble .ai-search-highlight {
        background: #fde047;
        color: #022c22;
        box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.4);
    }

    .ai-history-empty i {
        font-size: 2rem;
        margin-bottom: 10px;
        color: #cbd5e1;
        display: block;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 991px) {
        .ai-drawer {
            right: 0;
            left: auto;
            width: min(390px, 92vw);
            z-index: 1001;
        }

        .ai-floating-button {
            width: 52px;
            height: 52px;
            right: 18px;
            bottom: 18px;
            font-size: 20px;
        }

        body.ai-drawer-open .ai-floating-button {
            display: none;
        }
    }

    /* ===== STREAMING CURSOR ===== */
    .ai-stream-cursor {
        display: inline-block;
        width: 6px;
        height: 1em;
        background: #059669;
        margin-left: 3px;
        vertical-align: text-bottom;
        border-radius: 2px;
        animation: aiCursorBlink 0.7s infinite;
    }

    @keyframes aiCursorBlink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0; }
    }
</style>

<script>
    (function() {
        const drawer = document.getElementById('aiDrawer');
        const backdrop = document.getElementById('aiDrawerBackdrop');
        const chatView = document.getElementById('aiChatView');
        const historyView = document.getElementById('aiHistoryView');
        const historyBtn = document.getElementById('aiHistoryBtn');
        const historyBtnIcon = document.getElementById('aiHistoryBtnIcon');
        const chatBox = document.getElementById('aiChatBox');
        const historyList = document.getElementById('aiHistoryList');
        const chatForm = document.getElementById('aiChatForm');
        const messageInput = document.getElementById('aiMessageInput');
        const sendBtn = document.getElementById('aiSendBtn');
        const headerTitle = document.getElementById('aiHeaderTitle');
        const floatingBtn = document.getElementById('aiFloatingBtn');
        
        let rawMessages = [];
        let historyLoaded = false;
        let isViewingHistory = false;

        function createNewSessionId() {
            return 'sess_' + Date.now() + '_' + Math.random().toString(36).substring(2, 8);
        }
        let currentSessionId = createNewSessionId();
        let activeSessionMessagesCount = 0;

        // Auto-expand textarea
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
        });

        // Submit on Enter (Shift+Enter for newline)
        messageInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                chatForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
            }
        });

        // Tampilkan sapaan ramah AI untuk sesi baru (tidak memuat riwayat lama ke layar utama)
        function showWelcomeGreeting() {
            chatBox.innerHTML = '';
            currentSessionId = createNewSessionId();
            activeSessionMessagesCount = 0;
            appendAiMessage(
                'Halo {{ auth()->user()?->name ?? 'Admin' }}! 👋 Saya **PharmAI**, asisten pintar **APOTEK ITU TOKO OBAT**.\n\nAda keluhan kesehatan, luka, rasa sakit, aturan pakai obat, atau stok apotek yang bisa saya bantu hari ini? 😊',
                'bot'
            );
        }

        // Toggle Drawer
        function toggleAiDrawer(forcedState) {
            const isOpen = drawer.classList.contains('open');
            const shouldOpen = typeof forcedState === 'boolean' ? forcedState : !isOpen;

            if (shouldOpen) {
                drawer.classList.add('open');
                backdrop.classList.add('active');
                document.body.classList.add('ai-drawer-open');
                sessionStorage.setItem('ai_drawer_open', 'true');

                // Selalu buka sesi baru dengan sapaan AI (tidak me-load chat lama ke tampilan utama)
                toggleHistoryView(false);
                if (chatBox.querySelectorAll('.ai-msg').length === 0) {
                    showWelcomeGreeting();
                }

                // Ambil data riwayat di latar belakang agar siap di panel history
                fetchHistoryData();

                setTimeout(() => messageInput.focus(), 300);
            } else {
                drawer.classList.remove('open');
                backdrop.classList.remove('active');
                document.body.classList.remove('ai-drawer-open');
                sessionStorage.setItem('ai_drawer_open', 'false');
            }
        }

        // Format Markdown sederhana untuk bubble bot
        function formatMarkdown(text) {
            if (!text) return '';
            let s = text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            // Bold **text**
            s = s.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            // Italic *text*
            s = s.replace(/(?<!\*)\*(?!\*)(.*?)(?<!\*)\*(?!\*)/g, '<em>$1</em>');
            // Line break
            s = s.replace(/\n/g, '<br>');

            return s;
        }

        function escapeHtml(text) {
            if (!text) return '';
            return text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Add Message to DOM
        function appendAiMessage(content, sender, isHtml = false, msgId = null) {
            const row = document.createElement('div');
            row.className = 'ai-msg ' + sender;
            if (msgId) row.dataset.id = msgId;

            if (sender === 'bot') {
                const avatar = document.createElement('div');
                avatar.className = 'ai-msg-avatar';
                avatar.innerHTML = '<i class="fas fa-robot"></i>';
                row.appendChild(avatar);
            }

            const bubble = document.createElement('div');
            bubble.className = 'ai-bubble';

            if (isHtml) {
                bubble.innerHTML = content;
            } else if (sender === 'bot') {
                bubble.innerHTML = formatMarkdown(content);
            } else {
                bubble.textContent = content;
            }

            row.appendChild(bubble);
            chatBox.appendChild(row);
            chatBox.scrollTop = chatBox.scrollHeight;

            return bubble;
        }

        // Ambil riwayat dari database HANYA untuk background data (tidak dimasukkan ke chat box aktif)
        async function fetchHistoryData() {
            try {
                const response = await fetch("{{ route('ai.history') }}", {
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) throw new Error('Gagal memuat riwayat.');

                const data = await response.json();
                rawMessages = data.messages || [];
                historyLoaded = true;

                if (isViewingHistory) {
                    const searchVal = document.getElementById('aiHistorySearchInput')?.value || '';
                    renderHistoryList(searchVal);
                }
            } catch (err) {
                console.warn('Gagal memuat riwayat background:', err);
            }
        }

        // Format waktu ISO ke format lokal ringkas
        function formatTime(dateStr) {
            if (!dateStr) return '';
            try {
                const d = new Date(dateStr);
                const now = new Date();
                const isToday = d.toDateString() === now.toDateString();
                const time = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                if (isToday) return 'Hari ini, ' + time;
                return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) + ' ' + time;
            } catch (e) {
                return '';
            }
        }

        // Build daftar sesi konsultasi berurutan berdasarkan session_id
        // Sesuai permintaan: Hanya pertanyaan pertama yang jadi judul riwayat,
        // semua pertanyaan lanjutan masuk ke dalam percakapan sesi tersebut.
        function getConversationSessions() {
            const sessionsMap = new Map();
            let fallbackSessionId = null;
            let lastTime = 0;

            for (let i = 0; i < rawMessages.length; i++) {
                const msg = rawMessages[i];
                const msgTime = msg.created_at ? new Date(msg.created_at).getTime() : 0;
                let sId = msg.session_id;

                // Fallback pengelompokan untuk pesan warisan yang belum memiliki session_id
                if (!sId) {
                    if (!fallbackSessionId || (lastTime && msgTime - lastTime > 45 * 60 * 1000)) {
                        fallbackSessionId = 'sess_legacy_' + (msg.id || i);
                    }
                    sId = fallbackSessionId;
                } else {
                    fallbackSessionId = sId;
                }
                lastTime = msgTime;

                if (!sessionsMap.has(sId)) {
                    sessionsMap.set(sId, {
                        session_id: sId,
                        first_user_content: '',
                        latest_content: msg.content,
                        latest_role: msg.role,
                        created_at: msg.created_at,
                        updated_at: msg.created_at,
                        messages: [],
                        user_count: 0
                    });
                }

                const sess = sessionsMap.get(sId);
                sess.messages.push(msg);
                sess.updated_at = msg.created_at || sess.updated_at;
                sess.latest_content = msg.content;
                sess.latest_role = msg.role;

                if (msg.role === 'user') {
                    sess.user_count++;
                    if (!sess.first_user_content) {
                        sess.first_user_content = msg.content;
                    }
                }
            }

            // Ambil sesi yang memiliki setidaknya satu pertanyaan user
            const list = Array.from(sessionsMap.values()).filter(s => (s.first_user_content || '').trim() !== '');

            // Urutkan percakapan: yang terbaru ada di paling atas
            return list.sort((a, b) => {
                const timeA = new Date(a.updated_at || a.created_at || 0).getTime();
                const timeB = new Date(b.updated_at || b.created_at || 0).getTime();
                return timeB - timeA;
            });
        }

        // Normalisasi teks untuk pencarian riwayat (hapus simbol markdown & rapikan spasi)
        function normalizeSearchText(str) {
            return (str || '')
                .toLowerCase()
                .replace(/[*_`#~>-]/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();
        }

        function escapeRegExp(str) {
            return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }

        // Sorot kata kunci yang dicari secara aman (anti-XSS & tidak merusak entitas HTML)
        function highlightPlainText(text, terms) {
            if (!text) return '';
            if (!terms || terms.length === 0) return escapeHtml(text);

            const uniqueTerms = [...new Set(terms.filter(Boolean))].sort((a, b) => b.length - a.length);
            if (uniqueTerms.length === 0) return escapeHtml(text);

            const regex = new RegExp('(' + uniqueTerms.map(escapeRegExp).join('|') + ')', 'gi');
            const parts = String(text).split(regex);

            return parts.map((part, idx) => {
                if (idx % 2 === 1) {
                    return '<mark class="ai-search-highlight">' + escapeHtml(part) + '</mark>';
                }
                return escapeHtml(part);
            }).join('');
        }

        // Buat cuplikan teks yang terpusat pada kata kunci yang cocok agar terlihat di kartu riwayat
        function buildMatchSnippet(rawText, terms, maxBefore = 45) {
            const clean = (rawText || '').replace(/[*_`#]/g, '').replace(/\s+/g, ' ').trim();
            if (!clean || !terms || terms.length === 0) return clean;

            const lower = clean.toLowerCase();
            let firstIdx = -1;
            for (const term of terms) {
                const idx = lower.indexOf(term.toLowerCase());
                if (idx !== -1 && (firstIdx === -1 || idx < firstIdx)) {
                    firstIdx = idx;
                }
            }

            if (firstIdx <= maxBefore) return clean;
            const start = Math.max(0, firstIdx - maxBefore);
            return '...' + clean.substring(start);
        }

        // Sorot kata kunci pada text node di dalam elemen chat yang sudah dirender
        function highlightElementTextNodes(rootEl, terms) {
            if (!rootEl || !terms || terms.length === 0) return null;
            const uniqueTerms = [...new Set(terms.filter(Boolean))].sort((a, b) => b.length - a.length);
            if (uniqueTerms.length === 0) return null;

            const regex = new RegExp('(' + uniqueTerms.map(escapeRegExp).join('|') + ')', 'gi');
            const walker = document.createTreeWalker(rootEl, NodeFilter.SHOW_TEXT, null);
            const textNodes = [];
            let node;
            while ((node = walker.nextNode())) {
                if (node.nodeValue && regex.test(node.nodeValue)) {
                    textNodes.push(node);
                }
                regex.lastIndex = 0;
            }

            let firstMark = null;
            textNodes.forEach(textNode => {
                const parts = textNode.nodeValue.split(regex);
                if (parts.length <= 1) return;
                const frag = document.createDocumentFragment();
                parts.forEach((part, idx) => {
                    if (!part) return;
                    if (idx % 2 === 1) {
                        const mark = document.createElement('mark');
                        mark.className = 'ai-search-highlight';
                        mark.textContent = part;
                        if (!firstMark) firstMark = mark;
                        frag.appendChild(mark);
                    } else {
                        frag.appendChild(document.createTextNode(part));
                    }
                });
                textNode.parentNode.replaceChild(frag, textNode);
            });

            return firstMark;
        }

        // Render List Riwayat di panel History
        function renderHistoryList(filterKeyword = '') {
            const sessions = getConversationSessions();
            const normalizedQuery = normalizeSearchText(filterKeyword);
            const terms = normalizedQuery ? normalizedQuery.split(' ').filter(Boolean) : [];

            const filtered = sessions.filter(s => {
                if (terms.length === 0) return true;
                const combinedSessionText = normalizeSearchText(
                    s.first_user_content + ' ' + s.messages.map(m => m.content || '').join(' ')
                );
                return terms.every(term => combinedSessionText.includes(term));
            });

            if (filtered.length === 0) {
                historyList.innerHTML = `
                    <div class="ai-history-empty">
                        <i class="far fa-comments"></i>
                        <p>${terms.length > 0 ? 'Tidak ada riwayat yang cocok dengan kata kunci <strong>"' + escapeHtml(filterKeyword.trim()) + '"</strong>.' : 'Belum ada riwayat percakapan.<br>Mulai konsultasi dengan asisten sekarang!'}</p>
                    </div>
                `;
                return;
            }

            historyList.innerHTML = '';
            filtered.forEach(s => {
                const item = document.createElement('div');
                item.className = 'ai-history-item';
                item.onclick = function() {
                    openHistorySession(s.session_id, terms);
                };

                // Tentukan pertanyaan & preview jawaban yang ditampilkan (prioritaskan pesan yang cocok saat mencari)
                let displayQuestion = s.first_user_content;
                const lastBotMsg = s.messages.slice().reverse().find(m => m.role === 'assistant');
                let previewText = lastBotMsg ? lastBotMsg.content : (s.latest_content || 'Menunggu jawaban...');

                if (terms.length > 0) {
                    const matchedUserMsg = s.messages.find(m =>
                        m.role === 'user' && terms.every(t => normalizeSearchText(m.content).includes(t))
                    ) || s.messages.find(m =>
                        m.role === 'user' && terms.some(t => normalizeSearchText(m.content).includes(t))
                    );

                    if (matchedUserMsg) {
                        displayQuestion = matchedUserMsg.content;
                    }

                    const matchedBotMsg = s.messages.find(m =>
                        m.role === 'assistant' && terms.every(t => normalizeSearchText(m.content).includes(t))
                    ) || s.messages.find(m =>
                        m.role === 'assistant' && terms.some(t => normalizeSearchText(m.content).includes(t))
                    );

                    if (matchedBotMsg) {
                        previewText = buildMatchSnippet(matchedBotMsg.content, terms);
                    }
                }

                const questionHtml = terms.length > 0
                    ? highlightPlainText(buildMatchSnippet(displayQuestion, terms, 60), terms)
                    : escapeHtml(displayQuestion);
                const previewHtml = terms.length > 0
                    ? highlightPlainText(previewText, terms)
                    : escapeHtml(previewText);

                item.innerHTML = `
                    <div class="ai-history-meta">
                        <span class="ai-history-badge"><i class="fas fa-comment-medical"></i> ${s.user_count > 1 ? s.user_count + ' Tanya' : 'Konsultasi'}</span>
                        <div class="ai-history-meta-right">
                            <span class="ai-history-time">${formatTime(s.updated_at || s.created_at)}</span>
                            <button type="button" class="ai-history-delete-btn" title="Hapus Riwayat Ini" aria-label="Hapus Riwayat Ini">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                    <p class="ai-history-question">${questionHtml}</p>
                    <p class="ai-history-answer-preview">${previewHtml}</p>
                `;

                const deleteBtn = item.querySelector('.ai-history-delete-btn');
                if (deleteBtn) {
                    deleteBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        confirmDeleteSession(s.session_id, displayQuestion);
                    });
                }

                historyList.appendChild(item);
            });
        }

        function filterHistoryList(keyword) {
            renderHistoryList(keyword);
        }

        // Buka seluruh riwayat satu sesi ke dalam chat box agar user bisa membaca & melanjutkan obrolan
        function openHistorySession(sessionId, highlightTerms = []) {
            toggleHistoryView(false);
            chatBox.innerHTML = '';

            const sessions = getConversationSessions();
            const sess = sessions.find(s => s.session_id === sessionId);
            let firstHighlightEl = null;

            if (sess && sess.messages.length > 0) {
                currentSessionId = sess.session_id;
                activeSessionMessagesCount = sess.messages.length;

                const banner = document.createElement('div');
                banner.style.textAlign = 'center';
                banner.style.fontSize = '0.72rem';
                banner.style.color = '#64748b';
                banner.style.padding = '6px 12px';
                banner.style.marginBottom = '12px';
                banner.style.background = '#f1f5f9';
                banner.style.borderRadius = '8px';
                banner.style.border = '1px solid #e2e8f0';
                banner.innerHTML = '<i class="fas fa-history"></i> Riwayat Konsultasi: <strong>' + formatTime(sess.created_at) + '</strong> (' + sess.messages.length + ' pesan)';
                chatBox.appendChild(banner);

                // Tampilkan semua percakapan di dalam sesi ini
                sess.messages.forEach(msg => {
                    const bubble = appendAiMessage(msg.content, msg.role === 'assistant' ? 'bot' : 'user', false, msg.id);
                    if (highlightTerms && highlightTerms.length > 0) {
                        const found = highlightElementTextNodes(bubble, highlightTerms);
                        if (found && !firstHighlightEl) {
                            firstHighlightEl = found;
                        }
                    }
                });
            } else {
                showWelcomeGreeting();
            }

            setTimeout(() => {
                if (firstHighlightEl) {
                    firstHighlightEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    chatBox.scrollTop = chatBox.scrollHeight;
                }
                messageInput.focus();
            }, 60);
        }

        // Toggle antara Tampilan Chat dan Tampilan Riwayat
        function toggleHistoryView(forcedState) {
            isViewingHistory = typeof forcedState === 'boolean' ? forcedState : !isViewingHistory;

            if (isViewingHistory) {
                chatView.style.display = 'none';
                historyView.style.display = 'flex';
                historyBtn.classList.add('active');
                historyBtnIcon.className = 'fas fa-comment-dots';
                headerTitle.textContent = 'Riwayat Chat';
                const searchVal = document.getElementById('aiHistorySearchInput')?.value || '';
                renderHistoryList(searchVal);
            } else {
                historyView.style.display = 'none';
                chatView.style.display = 'flex';
                historyBtn.classList.remove('active');
                historyBtnIcon.className = 'fas fa-history';
                headerTitle.textContent = 'PharmAI';
                setTimeout(() => chatBox.scrollTop = chatBox.scrollHeight, 100);
            }
        }

        // Mulai Percakapan Baru (+ Chat Baru)
        function startNewChat() {
            toggleHistoryView(false);
            showWelcomeGreeting();
            messageInput.value = '';
            messageInput.focus();
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        // Konfirmasi & Hapus Semua Riwayat Chat
        async function confirmClearHistory() {
            const doDelete = async () => {
                try {
                    const response = await fetch("{{ route('ai.history.clear') }}", {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (!response.ok) throw new Error('Gagal membersihkan riwayat.');

                    rawMessages = [];
                    chatBox.innerHTML = '';
                    showWelcomeGreeting();
                    renderHistoryList();
                    toggleHistoryView(false);

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Riwayat Bersih',
                            text: 'Semua riwayat percakapan telah berhasil dihapus.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                } catch (e) {
                    alert(e.message || 'Gagal menghapus riwayat.');
                }
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Hapus Semua Riwayat?',
                    text: 'Seluruh riwayat konsultasi dengan AI akan dihapus permanen.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Hapus Semua!',
                    cancelButtonText: 'Batal'
                }).then(result => {
                    if (result.isConfirmed) {
                        doDelete();
                    }
                });
            } else {
                if (confirm('Apakah Anda yakin ingin menghapus seluruh riwayat percakapan AI?')) {
                    doDelete();
                }
            }
        }

        // Konfirmasi & Hapus Satu Riwayat Sesi Tertentu
        async function confirmDeleteSession(sessionId, titleText = '') {
            const cleanTitle = (titleText || '').trim();
            const shortTitle = cleanTitle.length > 60 ? cleanTitle.substring(0, 60) + '...' : cleanTitle;

            const doDeleteSession = async () => {
                try {
                    const url = "{{ url('/ai/history') }}/" + encodeURIComponent(sessionId);
                    const response = await fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (!response.ok) throw new Error('Gagal menghapus riwayat percakapan.');

                    rawMessages = rawMessages.filter(m => m.session_id !== sessionId);

                    if (currentSessionId === sessionId) {
                        showWelcomeGreeting();
                    }

                    const searchVal = document.getElementById('aiHistorySearchInput')?.value || '';
                    renderHistoryList(searchVal);

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Terhapus',
                            text: 'Riwayat percakapan berhasil dihapus.',
                            timer: 1400,
                            showConfirmButton: false
                        });
                    }
                } catch (e) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: e.message || 'Gagal menghapus riwayat.'
                        });
                    } else {
                        alert(e.message || 'Gagal menghapus riwayat.');
                    }
                }
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Hapus Riwayat Chat Ini?',
                    text: shortTitle ? `Percakapan "${shortTitle}" akan dihapus permanen.` : 'Percakapan ini akan dihapus permanen.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then(result => {
                    if (result.isConfirmed) {
                        doDeleteSession();
                    }
                });
            } else {
                const msg = shortTitle
                    ? `Apakah Anda yakin ingin menghapus riwayat "${shortTitle}"?`
                    : 'Apakah Anda yakin ingin menghapus riwayat percakapan ini?';
                if (confirm(msg)) {
                    doDeleteSession();
                }
            }
        }

        // Efek streaming teks bertahap (word-by-word) seperti ChatGPT / Gemini / Claude
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

                    bubble.innerHTML = formatMarkdown(preview) + '<span class="ai-stream-cursor"></span>';
                    if (scrollEl) {
                        scrollEl.scrollTop = scrollEl.scrollHeight;
                    }

                    setTimeout(tick, intervalMs);
                }

                tick();
            });
        }

        // Submit Message Form
        async function handleAiChatSubmit(event) {
            event.preventDefault();
            const message = messageInput.value.trim();
            if (!message) return;

            appendAiMessage(message, 'user');
            messageInput.value = '';
            messageInput.style.height = 'auto';
            sendBtn.disabled = true;

            const thinkingBubble = appendAiMessage(
                '<div class="ai-thinking-dots"><span></span><span></span><span></span></div>',
                'bot',
                true
            );

            try {
                const response = await fetch("{{ route('ai.chat') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        message: message,
                        session_id: currentSessionId
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || 'Terjadi gangguan saat memproses jawaban.');
                }

                if (data.session_id) {
                    currentSessionId = data.session_id;
                }

                const replyText = data.reply || 'Maaf, tidak ada respon dari asisten.';

                // Tambahkan ke rawMessages lokal dengan session_id
                const nowIso = new Date().toISOString();
                rawMessages.push({ role: 'user', content: message, session_id: currentSessionId, created_at: nowIso });
                rawMessages.push({ role: 'assistant', content: replyText, session_id: currentSessionId, created_at: nowIso });
                activeSessionMessagesCount += 2;

                await streamReplyToBubble(thinkingBubble, replyText, chatBox);
            } catch (error) {
                thinkingBubble.innerHTML = '⚠️ <em>' + (error.message || 'Gagal terhubung ke server.') + '</em>';
            } finally {
                sendBtn.disabled = false;
                chatBox.scrollTop = chatBox.scrollHeight;
                messageInput.focus();
            }
        }

        // Expose functions globally to window
        window.toggleAiDrawer = toggleAiDrawer;
        window.toggleHistoryView = toggleHistoryView;
        window.fetchHistoryData = fetchHistoryData;
        window.filterHistoryList = filterHistoryList;
        window.startNewChat = startNewChat;
        window.confirmClearHistory = confirmClearHistory;
        window.confirmDeleteSession = confirmDeleteSession;
        window.handleAiChatSubmit = handleAiChatSubmit;
        window.openHistorySession = openHistorySession;

        // Auto open if parameter ?open_ai=1 is present or previously opened
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('open_ai') === '1' || sessionStorage.getItem('ai_drawer_open') === 'true') {
            toggleAiDrawer(true);
        }
    })();
</script>

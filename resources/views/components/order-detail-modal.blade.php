@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/order.css') }}">
<style>
    .order-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(2, 44, 34, 0.55);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        z-index: 1080;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }
    .order-modal-overlay.open {
        display: flex;
        animation: orderModalFade 0.2s ease;
    }
    @keyframes orderModalFade {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    .order-modal-dialog {
        background: #f8fafc;
        border-radius: 16px;
        width: 100%;
        max-width: 760px;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 24px 60px rgba(2, 44, 34, 0.28);
        border: 1px solid rgba(226, 232, 240, 0.9);
        animation: orderModalSlide 0.22s cubic-bezier(0.22, 1, 0.36, 1);
    }
    @keyframes orderModalSlide {
        from { opacity: 0; transform: translateY(14px) scale(0.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .order-modal-header {
        background: #ffffff;
        padding: 1rem 1.35rem;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-shrink: 0;
    }
    .order-modal-title {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--gray-800);
    }
    .order-modal-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .order-modal-close {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #475569;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
    }
    .order-modal-close:hover {
        background: #fee2e2;
        color: #dc2626;
        border-color: #fecaca;
    }
    .order-modal-body {
        padding: 1.25rem;
        overflow-y: auto;
        flex: 1;
    }
    .order-detail-modal-layout {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    @media (max-width: 900px) {
        .order-detail-modal-layout {
            grid-template-columns: 1fr;
        }
        .order-modal-body {
            padding: 0.9rem;
        }
    }
    @media print {
        body.order-modal-printing .sidebar,
        body.order-modal-printing .sidebar-backdrop,
        body.order-modal-printing .admin-main,
        body.order-modal-printing .ai-floating-button,
        body.order-modal-printing .ai-drawer,
        body.order-modal-printing .ai-drawer-backdrop,
        body.order-modal-printing .order-modal-header,
        body.order-modal-printing .no-print {
            display: none !important;
        }
        body.order-modal-printing .order-modal-overlay {
            position: static !important;
            display: block !important;
            background: transparent !important;
            padding: 0 !important;
        }
        body.order-modal-printing .order-modal-dialog {
            max-width: 100% !important;
            max-height: none !important;
            box-shadow: none !important;
            border: none !important;
            background: #ffffff !important;
        }
        body.order-modal-printing .order-modal-body {
            padding: 0 !important;
            overflow: visible !important;
        }
        body.order-modal-printing .order-detail-modal-layout {
            grid-template-columns: 1fr !important;
        }
    }
</style>
@endpush

<div class="order-modal-overlay" id="orderDetailModal" role="dialog" aria-modal="true" aria-labelledby="orderModalHeading">
    <div class="order-modal-dialog">
        <div class="order-modal-header no-print">
            <div class="order-modal-title" id="orderModalHeading">
                <i class="fas fa-receipt" style="color: var(--primary);"></i>
                <span>Detail Pesanan</span>
                <span id="orderModalNo" style="font-size: 0.85rem; font-weight: 600; color: var(--gray-500);"></span>
            </div>
            <div class="order-modal-actions">
                <button type="button" onclick="printOrderModal()" class="btn btn-outline btn-sm" style="border-color: var(--primary); color: var(--primary);">
                    <i class="fas fa-print"></i> Cetak Struk
                </button>
                <button type="button" class="order-modal-close" id="closeOrderModalBtn" aria-label="Tutup Pop-up">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="order-modal-body" id="orderDetailModalBody"></div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const modal = document.getElementById('orderDetailModal');
    const modalBody = document.getElementById('orderDetailModalBody');
    const modalNo = document.getElementById('orderModalNo');
    const closeBtn = document.getElementById('closeOrderModalBtn');

    if (!modal || !modalBody) return;

    // Pindahkan modal ke langsung di bawah <body> agar tidak ikut tersembunyi saat .admin-main disembunyikan ketika print
    if (modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }

    const closeModal = () => {
        modal.classList.remove('open');
        document.body.classList.remove('order-modal-printing');
        document.body.style.overflow = '';
    };

    const openOrderModal = async (url, orderNoText) => {
        if (modalNo) modalNo.textContent = orderNoText ? '#' + orderNoText.trim() : '';
        modalBody.innerHTML = '<div style="padding:3rem;text-align:center;color:#64748b;"><i class="fas fa-circle-notch fa-spin" style="font-size:1.6rem;color:var(--primary);margin-bottom:0.6rem;display:block;"></i>Memuat detail pesanan...</div>';
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';

        try {
            const sep = url.includes('?') ? '&' : '?';
            const res = await fetch(url + sep + 'modal=1', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('Gagal memuat detail pesanan.');
            modalBody.innerHTML = await res.text();
        } catch (err) {
            modalBody.innerHTML = '<div style="padding:2rem;text-align:center;color:#dc2626;">' + (err.message || 'Gagal memuat detail pesanan.') + '</div>';
        }
    };

    window.printOrderModal = function () {
        document.body.classList.add('order-modal-printing');
        window.print();
    };

    window.addEventListener('afterprint', function () {
        document.body.classList.remove('order-modal-printing');
    });

    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('.js-order-modal');
        if (!trigger) return;
        e.preventDefault();
        const url = trigger.dataset.modalUrl || trigger.getAttribute('href');
        const noText = trigger.dataset.orderNo || '';
        if (url) openOrderModal(url, noText);
    });

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('open')) {
            closeModal();
        }
    });
})();
</script>
@endpush

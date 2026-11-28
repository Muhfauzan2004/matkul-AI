@push('styles')
<style>
    .obat-modal-overlay {
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
    .obat-modal-overlay.open {
        display: flex;
        animation: obatModalFade 0.2s ease;
    }
    @keyframes obatModalFade {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    .obat-modal-dialog {
        background: #ffffff;
        border-radius: 16px;
        width: 100%;
        max-width: 720px;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 24px 60px rgba(2, 44, 34, 0.28);
        border: 1px solid rgba(226, 232, 240, 0.9);
        animation: obatModalSlide 0.22s cubic-bezier(0.22, 1, 0.36, 1);
    }
    @keyframes obatModalSlide {
        from { opacity: 0; transform: translateY(14px) scale(0.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .obat-modal-header {
        background: #ffffff;
        padding: 1rem 1.35rem;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-shrink: 0;
    }
    .obat-modal-title {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--gray-800);
    }
    .obat-modal-close {
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
    .obat-modal-close:hover {
        background: #fee2e2;
        color: #dc2626;
        border-color: #fecaca;
    }
    .obat-modal-body {
        padding: 1.25rem 1.35rem;
        overflow-y: auto;
        flex: 1;
    }
    @media (max-width: 640px) {
        .obat-modal-body {
            padding: 1rem;
        }
    }
</style>
@endpush

<div class="obat-modal-overlay" id="obatCrudModal" role="dialog" aria-modal="true" aria-labelledby="obatModalHeading">
    <div class="obat-modal-dialog">
        <div class="obat-modal-header">
            <div class="obat-modal-title" id="obatModalHeading">
                <i class="fas fa-pills" id="obatModalIcon" style="color: var(--primary);"></i>
                <span id="obatModalTitleText">Data Obat</span>
            </div>
            <button type="button" class="obat-modal-close" id="closeObatModalBtn" aria-label="Tutup Pop-up">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="obat-modal-body" id="obatCrudModalBody"></div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const modal = document.getElementById('obatCrudModal');
    const modalBody = document.getElementById('obatCrudModalBody');
    const titleText = document.getElementById('obatModalTitleText');
    const titleIcon = document.getElementById('obatModalIcon');
    const closeBtn = document.getElementById('closeObatModalBtn');

    if (!modal || !modalBody) return;

    if (modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }

    const closeModal = () => {
        modal.classList.remove('open');
        document.body.style.overflow = '';
    };
    window.closeObatCrudModal = closeModal;

    const openObatModal = async (url, title, iconClass) => {
        if (titleText) titleText.textContent = title || 'Data Obat';
        if (titleIcon && iconClass) titleIcon.className = iconClass;

        modalBody.innerHTML = '<div style="padding:2.5rem;text-align:center;color:#64748b;"><i class="fas fa-circle-notch fa-spin" style="font-size:1.5rem;color:var(--primary);margin-bottom:0.6rem;display:block;"></i>Memuat data...</div>';
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';

        try {
            const sep = url.includes('?') ? '&' : '?';
            const res = await fetch(url + sep + 'modal=1', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const html = await res.text();
            if (!res.ok) {
                modalBody.innerHTML = html || '<div class="alert alert-error">Gagal memuat data obat.</div>';
                return;
            }
            modalBody.innerHTML = html;
        } catch (err) {
            modalBody.innerHTML = '<div class="alert alert-error">' + (err.message || 'Gagal memuat data obat.') + '</div>';
        }
    };

    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('.js-obat-modal');
        if (!trigger) return;
        e.preventDefault();
        const url = trigger.dataset.modalUrl || trigger.getAttribute('href');
        const title = trigger.dataset.modalTitle || 'Data Obat';
        const icon = trigger.dataset.modalIcon || 'fas fa-pills';
        if (url) openObatModal(url, title, icon);
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

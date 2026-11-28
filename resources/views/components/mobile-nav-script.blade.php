<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.querySelector('.sidebar');
    const menuBtn = document.getElementById('mobileMenuBtn');
    const closeBtn = document.getElementById('sidebarCloseBtn');
    const backdrop = document.getElementById('sidebarBackdrop');

    const closeSidebar = () => {
        if (!sidebar) return;
        sidebar.classList.remove('active');
        if (backdrop) backdrop.classList.remove('active');
        document.body.style.overflow = '';
    };

    const openSidebar = () => {
        if (!sidebar) return;
        sidebar.classList.add('active');
        if (backdrop) backdrop.classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    if (menuBtn) {
        menuBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (sidebar && sidebar.classList.contains('active')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (backdrop) backdrop.addEventListener('click', closeSidebar);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeSidebar();
            document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
        }
    });

    if (sidebar) {
        sidebar.querySelectorAll('.sidebar-link').forEach(link => {
            link.addEventListener('click', function () {
                if (window.innerWidth <= 992) closeSidebar();
            });
        });
    }

    // Touch-friendly dropdown toggle on mobile/tablet
    document.querySelectorAll('.dropdown').forEach(drop => {
        drop.addEventListener('click', function (e) {
            if (e.target.closest('.dropdown-menu')) return;
            e.stopPropagation();
            const isOpen = drop.classList.contains('open');
            document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
            if (!isOpen) drop.classList.add('open');
        });
    });

    document.addEventListener('click', function () {
        document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
    });
});
</script>

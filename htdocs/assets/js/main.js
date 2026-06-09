document.addEventListener('DOMContentLoaded', function () {
    // Sidebar Toggle Logic
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    const sidebarCollapse = document.getElementById('sidebarCollapse');

    if (sidebarCollapse) {
        sidebarCollapse.addEventListener('click', function () {
            if (window.innerWidth >= 992) {
                // Desktop Toggle
                sidebar.classList.toggle('collapsed');
                content.classList.toggle('expanded');
            } else {
                // Mobile Toggle
                sidebar.classList.toggle('active');
            }
        });
    }

    // Close sidebar on window resize for better responsive behavior
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992) {
            sidebar.classList.remove('active');
        } else {
            sidebar.classList.remove('collapsed');
            content.classList.remove('expanded');
        }
    });

    // Add ripple effect or subtle animation on link clicks
    const sideLinks = document.querySelectorAll('.side-link');
    sideLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            // Optional: add active state if not handled by server
            // this.classList.add('active');
        });
    });
});

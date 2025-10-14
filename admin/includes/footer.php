            </main>
        </div>
    </div>

    <!-- Common JavaScript -->
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Confirm delete actions
        function confirmDelete(message = 'Are you sure you want to delete this item?') {
            return confirm(message);
        }

        // Mobile menu toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobile-menu-overlay');

            // Check if we're on mobile and hide sidebar initially
            function checkMobile() {
                if (window.innerWidth < 768) {
                    sidebar.classList.add('mobile-hidden');
                } else {
                    sidebar.classList.remove('mobile-hidden');
                }
            }

            // Initial check
            checkMobile();

            if (mobileMenuButton && sidebar && overlay) {
                // Toggle mobile menu
                mobileMenuButton.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    overlay.classList.toggle('active');
                });

                // Close menu when clicking overlay
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                });

                // Close menu when clicking a nav link (mobile)
                const navLinks = sidebar.querySelectorAll('.nav-link');
                navLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        if (window.innerWidth < 768) { // Only on mobile
                            sidebar.classList.remove('active');
                            overlay.classList.remove('active');
                        }
                    });
                });

                // Close menu on window resize
                window.addEventListener('resize', function() {
                    checkMobile();
                    if (window.innerWidth >= 768) {
                        sidebar.classList.remove('active');
                        overlay.classList.remove('active');
                    }
                });
            }
        });
    </script>
</body>
</html>
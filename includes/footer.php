</div> <!-- End main-content -->

<!-- Footer (opcional para admin panel) -->
<?php if (!isset($hideFooter) || !$hideFooter): ?>
    <footer class="footer-sweetpot py-3">
        <div class="container-fluid">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <img src="<?php echo BASE_URL; ?>/assets/images/logo-sweetpot.png" alt="logo" style="height:32px;">
                    <div>
                        <div class="fw-bold text-sweetpot-brown mb-0"><?php echo APP_NAME; ?></div>
                        <small class="text-muted">v<?php echo APP_VERSION; ?></small>
                    </div>
                </div>

                <div class="text-center mt-2 mt-md-0">
                    <small class="text-muted">© <?php echo date('Y'); ?>. Todos los derechos reservados.</small>
                </div>

                <div class="mt-2 mt-md-0 text-md-end">
                    <small class="text-muted">Última actualización: <?php echo date('d/m/Y H:i'); ?></small>
                </div>
            </div>
        </div>
    </footer>
<?php endif; ?>

<!-- Scripts básicos -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- jQuery (si es necesario) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Scripts personalizados globales -->
<script>
    // Configuración global de CSRF
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // Función global para mostrar loading
    function showLoading() {
        document.getElementById('loadingOverlay').style.display = 'flex';
    }

    function hideLoading() {
        document.getElementById('loadingOverlay').style.display = 'none';
    }

    // Función global para hacer peticiones AJAX con CSRF
    function makeRequest(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        if (CSRF_TOKEN) {
            defaultOptions.headers['X-CSRF-Token'] = CSRF_TOKEN;
        }

        return fetch(url, { ...defaultOptions, ...options });
    }

    // Auto-hide alerts después de 5 segundos
    document.addEventListener('DOMContentLoaded', function () {
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        });
    });

    // Confirmación para enlaces de eliminación
    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-confirm-delete]')) {
            e.preventDefault();
            const element = e.target.closest('[data-confirm-delete]');
            const message = element.getAttribute('data-confirm-delete') || '¿Estás seguro de que deseas eliminar este elemento?';

            Swal.fire({
                title: '¿Confirmar eliminación?',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    if (element.tagName === 'A') {
                        window.location.href = element.href;
                    } else if (element.tagName === 'FORM') {
                        element.submit();
                    }
                }
            });
        }
    });

    // Sidebar toggle para móvil
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        if (sidebar) {
            sidebar.classList.toggle('show');
        }
    }

    // Cerrar sidebar al hacer click fuera (móvil)
    document.addEventListener('click', function (e) {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.querySelector('[onclick="toggleSidebar()"]');

        if (sidebar && sidebar.classList.contains('show')) {
            if (!sidebar.contains(e.target) && e.target !== toggleBtn) {
                sidebar.classList.remove('show');
            }
        }
    });

    // Función para formatear números como moneda
    function formatCurrency(amount) {
        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN'
        }).format(amount);
    }

    // Función para formatear fechas
    function formatDate(dateString) {
        return new Intl.DateTimeFormat('es-MX', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        }).format(new Date(dateString));
    }

    // Notificación toast personalizada
    function showToast(message, type = 'info') {
        const toastHtml = `
                <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;

        const toastContainer = document.getElementById('toastContainer');
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);

        const toastElement = toastContainer.lastElementChild;
        const toast = new bootstrap.Toast(toastElement);
        toast.show();

        // Eliminar el toast después de que se oculte
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }
</script>

<style>
    /* Footer styles aligned with SweetPot theme variables */
    .footer-sweetpot {
        background: linear-gradient(90deg, var(--sp-cream, #fff), rgba(255, 255, 255, 0.95));
        border-top: 1px solid rgba(0, 0, 0, 0.04);
        color: var(--sp-dark-brown, #6b3a2f);
    }

    .footer-sweetpot img {
        filter: none;
    }

    /* Make sidebar stick to the top of the viewport on md+ screens and avoid being pushed by footer */
    @media (min-width: 768px) {
        .sidebar-sticky {
            position: sticky;
            top: 0;
            /* stick to very top as requested */
            height: 100vh;
            /* full viewport height so footer doesn't push it down */
            overflow-y: auto;
            padding-bottom: 1rem;
            z-index: 1020;
            /* stay above page content if needed */
        }
    }

    /* Improve sidebar link visuals */
    .sidebar-sweetpot .nav-link.active {
        color: var(--sp-dark-brown) !important;
        background: linear-gradient(90deg, rgba(0, 0, 0, 0.03), rgba(0, 0, 0, 0.02));
        border-radius: 8px;
        font-weight: 600;
    }

    /* Ensure no unexpected gap above footer: reset margins on main content */
    #main-content {
        margin-bottom: 0;
        /* prevent extra spacing */
        padding-bottom: 0;
        /* avoid extra padding creating gap */
    }

    /* Footer spacing control */
    .footer-sweetpot {
        padding-top: 12px;
        padding-bottom: 12px;
    }
</style>

<!-- Scripts adicionales para páginas específicas -->
<?php if (isset($additionalJS)): ?>
    <?php foreach ($additionalJS as $js): ?>
        <script src="<?php echo $js; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Inline scripts -->
<?php if (isset($inlineJS)): ?>
    <script>
        <?php echo $inlineJS; ?>
    </script>
<?php endif; ?>

</body>

</html>
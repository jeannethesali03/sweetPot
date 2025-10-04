sweetpot/
├── 📄 .htaccess                    # Configuración Apache
├── 📄 index.php                    # Página principal (redirige al login)
├── 📄 login.php                    # Sistema de acceso
├── 📄 registro.php                 # Registro de nuevos clientes
├── 📄 logout.php                   # Cierre de sesión
├── 📄 acceso_denegado.php          # Página de acceso denegado
├── 📄 README.md                    # Documentación del proyecto
│
├── 📁 config/                      # Configuración del sistema
│   ├── config.php                  # Variables globales y constantes
│   └── Database.php                # Conexión a la base de datos (PDO)
│
├── 📁 includes/                    # Componentes compartidos
│   ├── Auth.php                    # Sistema de autenticación
│   ├── helpers.php                 # Funciones de apoyo generales
│   └── qr_utils.php                # Generación de códigos QR
│
├── 📁 models/                      # Modelos del sistema (lógica de negocio)
│   ├── Usuario.php                 # Gestión de usuarios
│   ├── Producto.php                # Gestión de productos (pasteles, postres, bebidas)
│   ├── Categoria.php               # Categorías de productos
│   ├── Pedido.php                  # Gestión de pedidos y ventas
│   ├── DetallePedido.php           # Detalles de cada pedido
│   ├── Ticket.php                  # Generación de tickets de venta
│   ├── Pago.php                    # Registro de pagos (efectivo, tarjeta, etc.)
│   ├── Rol.php                     # Gestión de roles (admin, vendedor, cliente)
│   └── QRGenerator.php             # Generación de códigos QR por producto
│
├── 📁 admin/                       # Panel del administrador
│   ├── dashboard.php               # Panel de control con estadísticas
│   ├── usuarios.php                # CRUD de usuarios (clientes y vendedores)
│   ├── productos.php               # CRUD de productos
│   ├── categorias.php              # CRUD de categorías
│   ├── ventas.php                  # Consulta de todas las ventas
│   ├── reportes.php                # Reportes y gráficas de ventas
│   ├── qr_productos.php            # Generación de QR para productos
│   └── includes/
│       └── navbar.php              # Barra de navegación del panel admin
│
├── 📁 vendedor/                    # Panel del vendedor
│   ├── dashboard.php               # Resumen de ventas y pedidos
│   ├── ventas.php                  # Registro y consulta de ventas propias
│   ├── pedidos.php                 # Gestión de estados de pedidos
│   ├── generar_ticket.php          # Generación automática de ticket
│   ├── generar_qr.php              # Creación de códigos QR
│   └── includes/
│       └── navbar.php              # Barra de navegación del vendedor
│
├── 📁 cliente/                     # Interfaz del cliente
│   ├── dashboard.php               # Página de inicio del cliente
│   ├── perfil.php                  # Gestión de datos personales
│   ├── catalogo.php                # Catálogo de productos
│   ├── producto.php                # Vista individual de producto
│   ├── carrito.php                 # Carrito de compras
│   ├── checkout.php                # Proceso de pago
│   ├── pedido_confirmado.php       # Confirmación de pedido
│   ├── mis_pedidos.php             # Historial de pedidos y estados
│   ├── pedido_detalle.php          # Detalle de cada ticket
│   └── includes/
│       └── navbar.php              # Navegación del cliente
│
├── 📁 assets/                      # Recursos estáticos
│   ├── css/                        # Hojas de estilo
│   ├── js/                         # JavaScript
│   │   ├── admin-sweetalert.js     # Alertas y validaciones para admin
│   │   ├── vendedor-sweetalert.js  # Alertas para vendedor
│   │   └── cliente-sweetalert.js   # Alertas para cliente
│   └── uploads/                    # Archivos e imágenes subidas
│       └── productos/              # Imágenes de productos
│
└── 📁 database/                    # Base de datos
    └── sweetpot.sql                # Script de creación de la BD

🚀 Funcionalidades del Sistema
👤 Sistema de Usuarios

✅ Registro y autenticación (clientes, vendedores, administradores)

✅ Roles con control de acceso seguro

✅ Edición de perfil y cambio de contraseña

✅ Activación / desactivación de cuentas

✅ Sesiones seguras con control de tiempo

🍪 Gestión de Productos y Categorías

✅ CRUD completo de productos y categorías

✅ Control de inventario y alertas de stock bajo

✅ Carga de imágenes de productos

✅ Visualización filtrada por categoría

✅ Búsqueda avanzada por nombre

🛒 Carrito de Compras y Pedidos

✅ Carrito dinámico por sesión

✅ Checkout con confirmación

✅ Registro automático de pedidos

✅ Estado de pedido: pendiente, en proceso, enviado, entregado

✅ Historial de pedidos por cliente

🧾 Gestión de Tickets y Ventas

✅ Generación automática de ticket de venta (con número único)

✅ Descarga o impresión del ticket en PDF

✅ Asociación de cada ticket con su venta y cliente

✅ Registro de métodos de pago (efectivo, tarjeta, transferencia)

📦 Gestión de Envíos

✅ Seguimiento del estado del pedido

✅ Actualización por parte del vendedor

✅ Visualización en tiempo real para el cliente

✅ Confirmación de entrega

📱 Códigos QR

✅ Generación de códigos QR por producto

✅ Escaneo por el cliente que redirige al producto correspondiente

✅ QR descargables e imprimibles para etiquetar productos físicos

🔧 Panel Administrativo

✅ Dashboard con estadísticas globales

✅ CRUD completo de usuarios, productos, categorías y ventas

✅ Gráficas de ventas diarias, semanales y mensuales

✅ Reportes exportables

✅ Control total de inventario y precios

🧾 Reportes y Analíticas

✅ Reportes por categoría, producto o vendedor

✅ Ventas totales por rango de fecha

✅ Productos más vendidos

✅ Exportación a PDF o Excel con gráficos incluidos

🎉 Resultado Final

SweetPot es un sistema integral de gestión y ventas en línea para una repostería moderna, con tres roles definidos y control total sobre pedidos, productos y entregas.
El sistema permite gestionar ventas, generar tickets con QR, y ofrecer una experiencia fluida al cliente desde cualquier dispositivo.
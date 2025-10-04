# 🧁 SweetPot - Especificación Técnica Completa para Migración a Java/JSP

## 📋 CONTEXTO DEL SISTEMA

**SweetPot** es un sistema completo de gestión de ventas para repostería que incluye un catálogo de productos, carrito de compras, procesamiento de pagos, generación de tickets QR, y sistema de reportes avanzados. El sistema está diseñado con arquitectura modular y roles diferenciados.

---

## 🏗️ ARQUITECTURA DEL SISTEMA

### **Roles y Módulos:**

1. **ADMIN** - Gestión completa del sistema
2. **VENDEDOR** - Gestión de ventas y reportes (solo lectura en productos)
3. **CLIENTE** - Compras, carrito, perfil, tickets

### **Estructura de Directorios Actual (PHP):**

```
SweetPot/
├── admin/          # Módulo administrador
├── vendedor/       # Módulo vendedor
├── cliente/        # Módulo cliente
├── config/         # Configuración y base de datos
├── includes/       # Archivos compartidos (Auth, helpers)
├── models/         # Modelos de datos
├── assets/         # CSS, JS, imágenes
├── uploads/        # Archivos subidos
└── database/       # Scripts SQL
```

### **Estructura Propuesta para Java/JSP:**

```
SweetPot-Java/
├── src/main/java/
│   ├── controllers/    # Servlets (reemplaza archivos PHP principales)
│   ├── models/         # POJOs y DAOs
│   ├── services/       # Lógica de negocio
│   ├── utils/          # Utilidades (QR, Auth, helpers)
│   └── filters/        # Filtros de autenticación
├── src/main/webapp/
│   ├── WEB-INF/
│   ├── admin/          # JSPs del admin (REUTILIZAR vistas PHP)
│   ├── vendedor/       # JSPs del vendedor (REUTILIZAR vistas PHP)
│   ├── cliente/        # JSPs del cliente (REUTILIZAR vistas PHP)
│   ├── assets/         # COPIAR COMPLETO (CSS, JS, imágenes)
│   ├── includes/       # JSPs compartidos (header, footer, sidebar)
│   └── uploads/        # Archivos subidos
└── database/           # COPIAR COMPLETO (scripts SQL)
```

---

## 🗄️ ESQUEMA DE BASE DE DATOS

### **Tablas Principales:**

#### 1. **usuarios**

```sql
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    telefono VARCHAR(20),
    direccion TEXT,
    fecha_nacimiento DATE,
    rol ENUM('admin', 'vendedor', 'cliente') DEFAULT 'cliente',
    activo BOOLEAN DEFAULT TRUE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### 2. **categorias**

```sql
CREATE TABLE categorias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### 3. **productos**

```sql
CREATE TABLE productos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    stock_minimo INT DEFAULT 5,
    categoria_id INT,
    imagen VARCHAR(500),
    codigo_producto VARCHAR(100) UNIQUE,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);
```

#### 4. **ventas**

```sql
CREATE TABLE ventas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT NOT NULL,
    numero_pedido VARCHAR(50) UNIQUE NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    descuento DECIMAL(10,2) DEFAULT 0,
    impuestos DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente', 'en_proceso', 'enviado', 'entregado', 'cancelado') DEFAULT 'pendiente',
    direccion_entrega TEXT,
    comentarios TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id)
);
```

#### 5. **detalle_venta**

```sql
CREATE TABLE detalle_venta (
    id INT PRIMARY KEY AUTO_INCREMENT,
    venta_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);
```

#### 6. **pagos**

```sql
CREATE TABLE pagos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    venta_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia') DEFAULT 'efectivo',
    estado ENUM('pendiente', 'completado', 'fallido') DEFAULT 'pendiente',
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (venta_id) REFERENCES ventas(id)
);
```

#### 7. **tickets**

```sql
CREATE TABLE tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    venta_id INT NOT NULL,
    numero_ticket VARCHAR(50) UNIQUE NOT NULL,
    qr_path VARCHAR(500),
    fecha_generacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (venta_id) REFERENCES ventas(id)
);
```

#### 8. **carrito_temporal**

```sql
CREATE TABLE carrito_temporal (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id VARCHAR(255) NOT NULL,
    cliente_id INT,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id),
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);
```

---

## 🔐 SISTEMA DE AUTENTICACIÓN

### **Lógica de Autenticación (Auth.php → AuthService.java):**

```java
// Funciones principales a implementar:
public class AuthService {
    public static boolean login(String email, String password);
    public static void logout(HttpSession session);
    public static boolean isLoggedIn(HttpSession session);
    public static User getUser(HttpSession session);
    public static boolean hasRole(HttpSession session, String role);
    public static void requireRole(HttpSession session, String role);
    public static boolean requireLogin(HttpSession session, HttpServletResponse response);
}
```

### **Middleware/Filtros de Autenticación:**

- **AdminFilter**: Verificar rol 'admin' para `/admin/*`
- **VendedorFilter**: Verificar rol 'vendedor' para `/vendedor/*`
- **ClienteFilter**: Verificar rol 'cliente' para `/cliente/*`

---

## 🛒 FUNCIONALIDADES PRINCIPALES

### **MÓDULO ADMIN:**

#### **1. Dashboard (dashboard.jsp)**

- Estadísticas generales del sistema
- Gráficos Chart.js (ventas, productos, usuarios)
- Métricas en tiempo real

#### **2. Gestión de Usuarios (usuarios.jsp)**

- CRUD completo de usuarios
- Filtros por rol y estado
- Paginación
- Modal para crear/editar

#### **3. Gestión de Productos (productos.jsp)**

- CRUD completo de productos
- Generación automática de QR por producto
- Subida de imágenes o URLs
- Gestión de stock y categorías

#### **4. Gestión de Categorías (categorias.jsp)**

- CRUD de categorías
- Activar/desactivar categorías

#### **5. Gestión de Pedidos (pedidos.jsp)**

- Visualizar todos los pedidos
- Cambiar estados de pedidos
- Imprimir tickets
- Filtros avanzados

#### **6. Reportes Avanzados (reportes.jsp)**

- **Tipos de reportes**: Ventas, Productos, Clientes
- **Gráficos Chart.js**: Líneas, barras, doughnut
- **Exportación**: Excel (con estilos), CSV, PDF
- **Filtros**: Por fechas, tipos

### **MÓDULO VENDEDOR:**

#### **1. Dashboard (dashboard.jsp)**

- Vista simplificada de métricas de ventas
- Solo lectura

#### **2. Productos (productos.jsp)**

- **SOLO LECTURA** - No puede editar productos
- Visualizar catálogo completo
- Búsqueda y filtros

#### **3. Pedidos (pedidos.jsp)**

- Gestionar estados de pedidos
- Imprimir tickets
- Generar reportes de ventas

#### **4. Reportes (reportes.jsp)**

- **IDÉNTICO AL ADMIN** - Misma funcionalidad
- Exportación Excel, CSV, PDF
- Gráficos completos

### **MÓDULO CLIENTE:**

#### **1. Dashboard (dashboard.jsp)**

- Resumen de pedidos personales
- Estadísticas de compras

#### **2. Catálogo (productos.jsp)**

- Vista de productos activos
- Búsqueda y filtros por categoría
- Agregar al carrito

#### **3. Carrito (carrito.jsp)**

- Gestión del carrito temporal
- Modificar cantidades
- Procesar compra

#### **4. Pago (pago.jsp)**

- **SIMULACIÓN** de procesamiento de pago
- Generar venta y ticket QR

#### **5. Mis Pedidos (mis-pedidos.jsp)**

- Historial de pedidos personales
- Visualizar tickets QR
- Estados de pedidos

#### **6. Perfil (perfil.jsp)**

- Editar información personal
- Cambiar contraseña

---

## 🎨 FRONTEND Y ASSETS

### **CSS Framework:**

- **Bootstrap 5.3.0** (CDN)
- **Bootstrap Icons** (CDN)
- **Archivo personalizado**: `assets/css/sweetpot.css`

### **JavaScript Libraries:**

- **SweetAlert2** para modals y alertas
- **Chart.js** para gráficos de reportes
- **Bootstrap JS** para componentes

### **Colores Corporativos (CSS Variables):**

```css
:root {
  --sweetpot-pink: #ff6b9d;
  --sweetpot-brown: #8b4513;
  --sweetpot-cream: #ffeaa7;
  --sweetpot-light-pink: #ffeaa7;
  --sweetpot-white: #ffffff;
  --sweetpot-gray: #6c757d;
  --sweetpot-success: #28a745;
  --sweetpot-warning: #ffc107;
  --sweetpot-danger: #dc3545;
  --sweetpot-info: #17a2b8;
}
```

### **Archivos a COPIAR COMPLETOS:**

- `assets/css/sweetpot.css`
- `assets/js/admin-sweetalert.js`
- `assets/js/cliente-sweetalert.js`
- `assets/js/vendedor-sweetalert.js`

---

## 🔧 SISTEMA QR

### **Funcionalidad QR (QRHelper.php → QRService.java):**

```java
public class QRService {
    // Generar QR para productos
    public static String generateProductQR(int productId);

    // Generar QR para tickets
    public static String generateTicketQR(String ticketNumber);

    // Generar QR genérico con datos y tamaño
    public static String generateQR(String data, int size);

    // Limpiar QRs antiguos
    public static void cleanOldQRs();
}
```

### **URLs de QR:**

- **Productos**: `{BASE_URL}/producto.jsp?id={ID}`
- **Tickets**: `{BASE_URL}/cliente/ticket.jsp?numero={NUMERO_TICKET}`

---

## 📊 SISTEMA DE REPORTES

### **Tipos de Reportes:**

1. **Ventas por Día**: Gráfico de líneas
2. **Productos Más Vendidos**: Gráfico de barras
3. **Clientes Más Activos**: Gráfico de barras
4. **Estados de Pedidos**: Gráfico doughnut (en todos los reportes)

### **Funcionalidades de Exportación:**

#### **1. PDF (reporte-impresion.jsp)**

- Página optimizada para impresión
- Gráficos Chart.js incluidos
- Estilos profesionales

#### **2. Excel con Estilos (ExportService.java)**

```java
// Generar archivo Excel con:
// - Header corporativo con logo 🧁 SweetPot
// - Colores corporativos (#8b4513, #ff6b9d)
// - Datos tabulados con formato
// - Metadatos (fecha, período, tipo)
```

#### **3. CSV Simple**

- Archivo CSV compatible con Excel
- UTF-8 BOM para caracteres especiales
- Separador punto y coma (;)

### **Consultas SQL Críticas (Reportes):**

#### **Reporte de Ventas:**

```sql
SELECT
    DATE(v.fecha) as fecha,
    COUNT(DISTINCT v.id) as pedidos,
    COALESCE(SUM(p.monto), 0) as ventas
FROM pagos p
INNER JOIN ventas v ON p.venta_id = v.id
WHERE p.estado = 'completado'
AND DATE(v.fecha) BETWEEN ? AND ?
GROUP BY DATE(v.fecha)
ORDER BY fecha DESC
```

#### **Productos Más Vendidos:**

```sql
SELECT
    pr.nombre,
    SUM(dv.cantidad) as cantidad_vendida,
    SUM(dv.cantidad * dv.precio_unitario) as ingresos
FROM detalle_venta dv
INNER JOIN productos pr ON dv.producto_id = pr.id
INNER JOIN ventas v ON dv.venta_id = v.id
INNER JOIN pagos pg ON v.id = pg.venta_id
WHERE DATE(v.fecha) BETWEEN ? AND ?
AND pg.estado = 'completado'
GROUP BY pr.id, pr.nombre
ORDER BY cantidad_vendida DESC
```

#### **Estados de Pedidos:**

```sql
SELECT
    COUNT(*) as total_pedidos,
    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
    SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso,
    SUM(CASE WHEN estado = 'enviado' THEN 1 ELSE 0 END) as enviados,
    SUM(CASE WHEN estado = 'entregado' THEN 1 ELSE 0 END) as entregados,
    SUM(CASE WHEN estado = 'cancelado' THEN 1 ELSE 0 END) as cancelados
FROM ventas
WHERE DATE(fecha) BETWEEN ? AND ?
```

---

## 🛠️ MAPEO PHP → JAVA

### **Estructura de Archivos:**

| PHP                     | Java/JSP                                                             | Descripción           |
| ----------------------- | -------------------------------------------------------------------- | --------------------- |
| `config/Database.php`   | `utils/DatabaseUtil.java`                                            | Conexión BD           |
| `includes/Auth.php`     | `services/AuthService.java`                                          | Autenticación         |
| `models/Producto.php`   | `models/Producto.java` + `dao/ProductoDAO.java`                      | Modelo producto       |
| `models/Usuario.php`    | `models/Usuario.java` + `dao/UsuarioDAO.java`                        | Modelo usuario        |
| `admin/productos.php`   | `controllers/AdminProductosServlet.java` + `admin/productos.jsp`     | Vista productos admin |
| `vendedor/reportes.php` | `controllers/VendedorReportesServlet.java` + `vendedor/reportes.jsp` | Reportes vendedor     |
| `cliente/carrito.php`   | `controllers/ClienteCarritoServlet.java` + `cliente/carrito.jsp`     | Carrito cliente       |

### **Endpoints AJAX → Servlets:**

| PHP (AJAX)                              | Java (Servlet)                | Método |
| --------------------------------------- | ----------------------------- | ------ |
| `admin/ajax/guardar-categoria.php`      | `/admin/categorias`           | POST   |
| `cliente/ajax/agregar-carrito.php`      | `/cliente/carrito/agregar`    | POST   |
| `vendedor/ajax/exportar-reporte.php`    | `/vendedor/reportes/exportar` | GET    |
| `admin/ajax/obtener-detalle-pedido.php` | `/admin/pedidos/detalle`      | GET    |

---

## 🎯 FUNCIONALIDADES ESPECÍFICAS A IMPLEMENTAR

### **1. Carrito de Compras:**

- Persistencia en base de datos (tabla `carrito_temporal`)
- Gestión por session_id o cliente_id
- AJAX para agregar/quitar/modificar

### **2. Procesamiento de Pagos:**

- **SIMULACIÓN** - No integración real
- Estados: pendiente → completado
- Generar ticket QR automáticamente

### **3. Generación de Tickets:**

- Número único: `TK-{TIMESTAMP}`
- QR apunta a: `/cliente/ticket.jsp?numero={NUMERO}`
- Almacenar QR en `/assets/qr/`

### **4. Sistema de Estados:**

- **Pedidos**: pendiente → en_proceso → enviado → entregado
- **Pagos**: pendiente → completado/fallido
- **Productos**: activo/inactivo

### **5. Paginación:**

- Implementar en todas las listas (usuarios, productos, pedidos)
- 10 elementos por página por defecto

---

## 📱 DISEÑO RESPONSIVE

### **Breakpoints Bootstrap:**

- **xs**: < 576px (móvil)
- **sm**: ≥ 576px (móvil grande)
- **md**: ≥ 768px (tablet)
- **lg**: ≥ 992px (desktop)
- **xl**: ≥ 1200px (desktop grande)

### **Componentes Clave:**

- **Sidebar**: Colapsable en móvil
- **Cards**: Responsive grid system
- **Tablas**: Scroll horizontal en móvil
- **Gráficos**: Responsive Chart.js

---

## 🔄 FLUJO DE COMPRA COMPLETO

### **Proceso Cliente:**

1. **Catálogo** → Ver productos activos
2. **Agregar al Carrito** → AJAX a `/cliente/carrito/agregar`
3. **Ver Carrito** → Mostrar productos temporales
4. **Procesar Pago** → Simular pago exitoso
5. **Generar Venta** → Insertar en `ventas` y `detalle_venta`
6. **Crear Pago** → Insertar en `pagos` con estado 'completado'
7. **Generar Ticket** → Crear QR y guardar en `tickets`
8. **Limpiar Carrito** → Eliminar items temporales
9. **Mostrar Confirmación** → Con link al ticket QR

---

## 📋 CONFIG Y DEPLOYMENT

### **Configuración Base (config.jsp):**

```jsp
<%
    // Configuración de base de datos
    String DB_HOST = "localhost";
    String DB_NAME = "sweetpot_db";
    String DB_USER = "root";
    String DB_PASS = "";

    // URLs base
    String BASE_URL = "http://localhost:8080/SweetPot";

    // Configuración de archivos
    String UPLOAD_PATH = "/uploads/";
    String QR_PATH = "/assets/qr/";
%>
```

### **Dependencias Maven:**

```xml
<!-- Base de datos -->
<dependency>
    <groupId>mysql</groupId>
    <artifactId>mysql-connector-java</artifactId>
</dependency>

<!-- QR Generation -->
<dependency>
    <groupId>com.google.zxing</groupId>
    <artifactId>core</artifactId>
</dependency>
<dependency>
    <groupId>com.google.zxing</groupId>
    <artifactId>javase</artifactId>
</dependency>

<!-- Excel Export -->
<dependency>
    <groupId>org.apache.poi</groupId>
    <artifactId>poi</artifactId>
</dependency>

<!-- JSON -->
<dependency>
    <groupId>com.fasterxml.jackson.core</groupId>
    <artifactId>jackson-databind</artifactId>
</dependency>

<!-- Servlet API -->
<dependency>
    <groupId>javax.servlet</groupId>
    <artifactId>javax.servlet-api</artifactId>
</dependency>
```

---

## 🚀 PLAN DE MIGRACIÓN

### **FASE 1: Estructura Base**

1. Crear proyecto Maven con estructura JSP/Servlet
2. Configurar base de datos (COPIAR sweetpot.sql completo)
3. Implementar AuthService y filtros
4. COPIAR assets/ completo (CSS, JS, imágenes)

### **FASE 2: Modelos y DAOs**

1. Crear POJOs (Usuario, Producto, Venta, etc.)
2. Implementar DAOs con todas las consultas SQL
3. Crear utilidades (QRService, DatabaseUtil)

### **FASE 3: Módulo Admin**

1. AdminDashboardServlet + dashboard.jsp
2. AdminUsuariosServlet + usuarios.jsp
3. AdminProductosServlet + productos.jsp
4. AdminCategoriasServlet + categorias.jsp
5. AdminPedidosServlet + pedidos.jsp
6. AdminReportesServlet + reportes.jsp

### **FASE 4: Módulo Vendedor**

1. VendedorDashboardServlet + dashboard.jsp
2. VendedorProductosServlet + productos.jsp (solo lectura)
3. VendedorPedidosServlet + pedidos.jsp
4. VendedorReportesServlet + reportes.jsp (idéntico a admin)

### **FASE 5: Módulo Cliente**

1. ClienteDashboardServlet + dashboard.jsp
2. ClienteProductosServlet + productos.jsp
3. ClienteCarritoServlet + carrito.jsp
4. ClientePagoServlet + pago.jsp
5. ClientePedidosServlet + mis-pedidos.jsp
6. ClientePerfilServlet + perfil.jsp

### **FASE 6: Funcionalidades Avanzadas**

1. Sistema de reportes con Chart.js
2. Exportación Excel/CSV/PDF
3. Generación de QR
4. Sistema de tickets
5. AJAX endpoints

---

## ⚠️ PUNTOS CRÍTICOS A RECORDAR

### **1. Consultas SQL Correctas:**

- **USAR** tabla `pagos` con `estado='completado'` para totales financieros
- **NO USAR** `ventas.total` para cálculos de ingresos reales
- **SEPARAR** consultas de pedidos y pagos para evitar conflictos

### **2. Autenticación Robusta:**

- Validar roles en CADA servlet
- Usar filtros para proteger directorios
- Session timeout y logout seguro

### **3. QR y Assets:**

- Generar QR en `/assets/qr/` con nombres únicos
- Limpiar QR antiguos periódicamente
- URLs absolutas para QR

### **4. Exportación de Reportes:**

- Excel con estilos HTML/CSS embebido
- UTF-8 BOM para CSV
- Chart.js funcional en PDF print

### **5. Estados y Flujos:**

- Transiciones de estado válidas
- Validaciones de negocio
- Rollback en caso de errores

---

## 📖 DOCUMENTACIÓN ADICIONAL

### **Archivos de Referencia a Revisar:**

- `database/sweetpot.sql` - **COPIAR COMPLETO**
- `assets/css/sweetpot.css` - **COPIAR COMPLETO**
- `includes/Auth.php` - **LÓGICA PARA AuthService.java**
- `admin/reportes.php` - **LÓGICA PARA ReportesServlet.java**
- `cliente/carrito.php` - **LÓGICA PARA CarritoServlet.java**

### **Testing Checklist:**

- [ ] Login/logout funcional para todos los roles
- [ ] CRUD completo de productos y usuarios
- [ ] Carrito de compras con persistencia
- [ ] Procesamiento de pagos simulado
- [ ] Generación de tickets QR
- [ ] Reportes con gráficos Chart.js
- [ ] Exportación Excel/CSV/PDF
- [ ] Responsive design en todos los dispositivos
- [ ] Validaciones de seguridad y roles

---

**🎯 OBJETIVO FINAL:**
Recrear SweetPot en Java/JSP manteniendo exactamente la misma funcionalidad, diseño y experiencia de usuario, pero con arquitectura Java enterprise y mejor estructura de código.

**📧 CONTACTO DE DESARROLLO:**
Este documento contiene toda la información necesaria para recrear el sistema completo. Reutilizar las vistas JSP adaptando el PHP, mantener los assets intactos, y seguir la estructura de base de datos al pie de la letra.

---

_Documento generado automáticamente - Sistema SweetPot v1.0_
_Fecha: Octubre 2025_

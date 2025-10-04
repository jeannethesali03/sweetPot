-- Base de datos SweetPot - Sistema de Repostería
-- Versión: 1.0.0
-- Fecha: 2025-10-04

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS sweetpot_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sweetpot_db;

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    telefono VARCHAR(20),
    direccion TEXT,
    rol ENUM('administrador','cliente','vendedor') NOT NULL DEFAULT 'cliente',
    estado ENUM('activo','inactivo') DEFAULT 'activo',
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultima_conexion DATETIME NULL,
    
    INDEX idx_email (email),
    INDEX idx_rol (rol),
    INDEX idx_estado (estado)
) ENGINE=InnoDB;

-- Tabla de categorías
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    estado ENUM('activo','inactivo') DEFAULT 'activo',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_nombre (nombre),
    INDEX idx_estado (estado)
) ENGINE=InnoDB;

-- Tabla de productos
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    stock_minimo INT DEFAULT 5,
    imagen VARCHAR(500),
    codigo_producto VARCHAR(50) UNIQUE,
    estado ENUM('activo','inactivo') DEFAULT 'activo',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE,
    INDEX idx_categoria (categoria_id),
    INDEX idx_nombre (nombre),
    INDEX idx_estado (estado),
    INDEX idx_codigo (codigo_producto)
) ENGINE=InnoDB;

-- Tabla de ventas (pedidos)
CREATE TABLE ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    vendedor_id INT NULL,
    numero_pedido VARCHAR(50) UNIQUE NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(10,2) NOT NULL,
    impuestos DECIMAL(10,2) DEFAULT 0.00,
    descuento DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente','en_proceso','enviado','entregado','cancelado') DEFAULT 'pendiente',
    direccion_entrega TEXT,
    comentarios TEXT,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (vendedor_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_cliente (cliente_id),
    INDEX idx_vendedor (vendedor_id),
    INDEX idx_estado (estado),
    INDEX idx_fecha (fecha),
    INDEX idx_numero_pedido (numero_pedido)
) ENGINE=InnoDB;

-- Tabla de detalle de ventas
CREATE TABLE detalle_venta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    
    FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    INDEX idx_venta (venta_id),
    INDEX idx_producto (producto_id)
) ENGINE=InnoDB;

-- Tabla de tickets
CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT NOT NULL,
    numero_ticket VARCHAR(50) UNIQUE NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(10,2) NOT NULL,
    impuestos DECIMAL(10,2) DEFAULT 0.00,
    descuento DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL,
    metodo_pago ENUM('efectivo','tarjeta','transferencia') DEFAULT 'efectivo',
    
    FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE,
    INDEX idx_venta (venta_id),
    INDEX idx_numero_ticket (numero_ticket),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB;

-- Tabla de pagos
CREATE TABLE pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT NOT NULL,
    metodo ENUM('efectivo','tarjeta','transferencia') NOT NULL,
    referencia VARCHAR(100),
    monto DECIMAL(10,2) NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('pendiente','completado','fallido') DEFAULT 'completado',
    
    FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE,
    INDEX idx_venta (venta_id),
    INDEX idx_metodo (metodo),
    INDEX idx_estado (estado),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB;

-- Tabla de carrito de compras (temporal)
CREATE TABLE carrito (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    fecha_agregado DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cliente_producto (cliente_id, producto_id),
    INDEX idx_cliente (cliente_id),
    INDEX idx_producto (producto_id)
) ENGINE=InnoDB;

-- Tabla de sesiones (opcional, para mejor control de sesiones)
CREATE TABLE sesiones (
    id VARCHAR(128) PRIMARY KEY,
    usuario_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    datos TEXT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha_actualizacion (fecha_actualizacion)
) ENGINE=InnoDB;

-- Insertar datos iniciales

-- Usuario administrador por defecto
INSERT INTO usuarios (nombre, email, password, rol, estado) VALUES 
('Administrador SweetPot', 'admin@sweetpot.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'administrador', 'activo'),
('Vendedor Prueba', 'vendedor@sweetpot.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'vendedor', 'activo'),
('Cliente Prueba', 'cliente@sweetpot.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente', 'activo');

-- Categorías iniciales
INSERT INTO categorias (nombre, descripcion) VALUES 
('Pasteles', 'Pasteles para todo tipo de ocasiones'),
('Cupcakes', 'Cupcakes individuales decorados'),
('Galletas', 'Galletas artesanales y personalizadas'),
('Postres Fríos', 'Flanes, gelatinas y postres refrigerados'),
('Panes Dulces', 'Variedad de panes dulces tradicionales'),
('Chocolates', 'Chocolates artesanales y bombones');

-- Productos de ejemplo
INSERT INTO productos (categoria_id, nombre, descripcion, precio, stock, imagen, codigo_producto) VALUES 
(1, 'Pastel de Chocolate', 'Delicioso pastel de chocolate de 3 leches', 350.00, 10, 'https://example.com/pastel-chocolate.jpg', 'PAST001'),
(1, 'Pastel de Vainilla', 'Esponjoso pastel de vainilla con betún', 320.00, 8, 'https://example.com/pastel-vainilla.jpg', 'PAST002'),
(2, 'Cupcake Red Velvet', 'Cupcake de terciopelo rojo con queso crema', 45.00, 24, 'https://example.com/cupcake-red.jpg', 'CUP001'),
(2, 'Cupcake de Chocolate', 'Cupcake de chocolate con chispas', 40.00, 30, 'https://example.com/cupcake-choc.jpg', 'CUP002'),
(3, 'Galletas de Chispas', 'Galletas tradicionales con chispas de chocolate', 25.00, 50, 'https://example.com/galletas-chispas.jpg', 'GAL001'),
(4, 'Flan Napolitano', 'Flan casero estilo napolitano', 80.00, 15, 'https://example.com/flan.jpg', 'FLAN001');

-- Crear triggers para actualizar stock automáticamente
DELIMITER //

CREATE TRIGGER actualizar_stock_venta 
AFTER INSERT ON detalle_venta
FOR EACH ROW
BEGIN
    UPDATE productos 
    SET stock = stock - NEW.cantidad
    WHERE id = NEW.producto_id;
END//

CREATE TRIGGER generar_numero_pedido
BEFORE INSERT ON ventas
FOR EACH ROW
BEGIN
    DECLARE next_number INT;
    SET next_number = (SELECT COALESCE(MAX(CAST(SUBSTRING(numero_pedido, 4) AS UNSIGNED)), 0) + 1 FROM ventas WHERE numero_pedido LIKE 'SP-%');
    SET NEW.numero_pedido = CONCAT('SP-', LPAD(next_number, 6, '0'));
END//

CREATE TRIGGER generar_numero_ticket
BEFORE INSERT ON tickets
FOR EACH ROW
BEGIN
    DECLARE next_number INT;
    SET next_number = (SELECT COALESCE(MAX(CAST(SUBSTRING(numero_ticket, 4) AS UNSIGNED)), 0) + 1 FROM tickets WHERE numero_ticket LIKE 'TK-%');
    SET NEW.numero_ticket = CONCAT('TK-', LPAD(next_number, 8, '0'));
END//

DELIMITER ;

-- Crear vistas útiles
CREATE VIEW vista_productos_stock_bajo AS
SELECT p.*, c.nombre as categoria_nombre
FROM productos p
INNER JOIN categorias c ON p.categoria_id = c.id
WHERE p.stock <= p.stock_minimo AND p.estado = 'activo';

CREATE VIEW vista_ventas_completas AS
SELECT v.*, 
       u_cliente.nombre as cliente_nombre, u_cliente.email as cliente_email,
       u_vendedor.nombre as vendedor_nombre,
       t.numero_ticket
FROM ventas v
INNER JOIN usuarios u_cliente ON v.cliente_id = u_cliente.id
LEFT JOIN usuarios u_vendedor ON v.vendedor_id = u_vendedor.id
LEFT JOIN tickets t ON v.id = t.venta_id;

-- Comentarios sobre las passwords de ejemplo:
-- La contraseña para todos los usuarios de prueba es: "password123"
-- En producción, estas deben cambiarse inmediatamente
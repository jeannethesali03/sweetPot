-- Script para insertar datos de prueba en SweetPot
-- Ejecutar después de crear la base de datos inicial

USE sweetpot_db;

-- Insertar más usuarios de prueba
INSERT INTO usuarios (nombre, email, password, telefono, direccion, rol, estado) VALUES 
('María González', 'maria@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0001', 'Calle Principal 123', 'cliente', 'activo'),
('Carlos Rodríguez', 'carlos@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0002', 'Avenida Central 456', 'cliente', 'activo'),
('Ana Martínez', 'ana@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0003', 'Plaza Mayor 789', 'cliente', 'activo'),
('Luis Pérez', 'luis@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0004', 'Calle Dulce 321', 'cliente', 'activo');

-- Insertar ventas de prueba con diferentes estados
-- Pedido 1 - Pendiente
INSERT INTO ventas (cliente_id, vendedor_id, numero_pedido, subtotal, impuestos, descuento, total, estado, direccion_entrega, comentarios, fecha) VALUES 
(4, 2, 'SP-000001', 350.00, 35.00, 0.00, 385.00, 'pendiente', 'Calle Principal 123', 'Entrega en horario de oficina', '2025-10-01 10:30:00');

-- Detalles del pedido 1
INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES 
(1, 1, 1, 350.00, 350.00);

-- Pedido 2 - En proceso
INSERT INTO ventas (cliente_id, vendedor_id, numero_pedido, subtotal, impuestos, descuento, total, estado, direccion_entrega, comentarios, fecha) VALUES 
(5, 2, 'SP-000002', 130.00, 13.00, 0.00, 143.00, 'en_proceso', 'Avenida Central 456', 'Llamar antes de entregar', '2025-10-02 14:15:00');

-- Detalles del pedido 2
INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES 
(2, 3, 2, 45.00, 90.00),
(2, 5, 1, 25.00, 25.00),
(2, 6, 1, 15.00, 15.00);

-- Pedido 3 - Enviado
INSERT INTO ventas (cliente_id, vendedor_id, numero_pedido, subtotal, impuestos, descuento, total, estado, direccion_entrega, comentarios, fecha) VALUES 
(6, 2, 'SP-000003', 320.00, 32.00, 20.00, 332.00, 'enviado', 'Plaza Mayor 789', 'Descuento por cliente frecuente', '2025-10-03 09:45:00');

-- Detalles del pedido 3
INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES 
(3, 2, 1, 320.00, 320.00);

-- Pedido 4 - Entregado
INSERT INTO ventas (cliente_id, vendedor_id, numero_pedido, subtotal, impuestos, descuento, total, estado, direccion_entrega, comentarios, fecha) VALUES 
(7, 2, 'SP-000004', 200.00, 20.00, 0.00, 220.00, 'entregado', 'Calle Dulce 321', 'Entregado sin problemas', '2025-10-01 16:20:00');

-- Detalles del pedido 4
INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES 
(4, 4, 5, 40.00, 200.00);

-- Pedido 5 - Pendiente (otro)
INSERT INTO ventas (cliente_id, vendedor_id, numero_pedido, subtotal, impuestos, descuento, total, estado, direccion_entrega, comentarios, fecha) VALUES 
(4, 2, 'SP-000005', 75.00, 7.50, 0.00, 82.50, 'pendiente', 'Calle Principal 123', 'Segundo pedido del cliente', '2025-10-04 11:00:00');

-- Detalles del pedido 5
INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES 
(5, 5, 3, 25.00, 75.00);

-- Pedido 6 - Entregado (más ventas para estadísticas)
INSERT INTO ventas (cliente_id, vendedor_id, numero_pedido, subtotal, impuestos, descuento, total, estado, direccion_entrega, comentarios, fecha) VALUES 
(5, 2, 'SP-000006', 160.00, 16.00, 0.00, 176.00, 'entregado', 'Avenida Central 456', 'Cliente satisfecho', '2025-10-02 18:30:00');

-- Detalles del pedido 6
INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES 
(6, 4, 4, 40.00, 160.00);

-- Pedido 7 - Cancelado
INSERT INTO ventas (cliente_id, vendedor_id, numero_pedido, subtotal, impuestos, descuento, total, estado, direccion_entrega, comentarios, fecha) VALUES 
(6, 2, 'SP-000007', 350.00, 35.00, 0.00, 385.00, 'cancelado', 'Plaza Mayor 789', 'Cliente canceló por cambio de planes', '2025-10-03 20:15:00');

-- Detalles del pedido 7 (no afecta al stock por estar cancelado)
INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES 
(7, 1, 1, 350.00, 350.00);

-- Generar tickets para pedidos entregados
INSERT INTO tickets (venta_id, numero_ticket, subtotal, impuestos, descuento, total, metodo_pago, fecha) VALUES 
(4, 'TK-00000001', 200.00, 20.00, 0.00, 220.00, 'efectivo', '2025-10-01 16:25:00'),
(6, 'TK-00000002', 160.00, 16.00, 0.00, 176.00, 'tarjeta', '2025-10-02 18:35:00');

-- Generar pagos para pedidos entregados
INSERT INTO pagos (venta_id, metodo, referencia, monto, estado, fecha) VALUES 
(4, 'efectivo', 'PAGO-001', 220.00, 'completado', '2025-10-01 16:25:00'),
(6, 'tarjeta', 'TXN-123456', 176.00, 'completado', '2025-10-02 18:35:00');

-- Ajustar el stock después de las ventas (los triggers deberían hacerlo automáticamente, pero por si acaso)
UPDATE productos SET stock = stock - 2 WHERE id = 1; -- 2 pasteles de chocolate vendidos (1 entregado, 1 cancelado no cuenta)
UPDATE productos SET stock = stock - 1 WHERE id = 2; -- 1 pastel de vainilla vendido
UPDATE productos SET stock = stock - 2 WHERE id = 3; -- 2 cupcakes red velvet vendidos
UPDATE productos SET stock = stock - 9 WHERE id = 4; -- 9 cupcakes de chocolate vendidos
UPDATE productos SET stock = stock - 4 WHERE id = 5; -- 4 paquetes de galletas vendidos

-- Verificar resultados
SELECT 'Estadísticas de Estados de Pedidos:' as Info;
SELECT 
    estado,
    COUNT(*) as cantidad
FROM ventas 
GROUP BY estado 
ORDER BY cantidad DESC;

SELECT 'Productos más vendidos:' as Info;
SELECT 
    p.nombre,
    SUM(dv.cantidad) as total_vendido
FROM detalle_venta dv
INNER JOIN productos p ON dv.producto_id = p.id
INNER JOIN ventas v ON dv.venta_id = v.id
WHERE v.estado != 'cancelado'
GROUP BY p.id, p.nombre
ORDER BY total_vendido DESC;

SELECT 'Stock actual de productos:' as Info;
SELECT nombre, stock, stock_minimo FROM productos ORDER BY stock ASC;
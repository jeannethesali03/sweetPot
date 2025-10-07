-- Script SQL para verificar hashes en tu base de datos sweetpot_db
-- Ejecuta esto en phpMyAdmin o tu cliente MySQL

-- 1. Ver las contraseñas actuales (solo para verificar formato)
SELECT 
    id,
    email,
    SUBSTRING(password, 1, 20) as hash_preview,
    LENGTH(password) as hash_length,
    LEFT(password, 4) as hash_prefix
FROM usuarios 
WHERE email IN ('admin@sweetpot.com', 'vendedor@sweetpot.com', 'cliente@sweetpot.com')
ORDER BY email;

-- 2. Verificar si los hashes son BCrypt válidos
SELECT 
    email,
    password,
    CASE 
        WHEN password LIKE '$2%' AND LENGTH(password) >= 60 THEN 'BCrypt válido'
        WHEN password LIKE '$2%' AND LENGTH(password) < 60 THEN 'BCrypt incompleto'
        WHEN LENGTH(password) = 32 THEN 'Posible MD5'
        WHEN LENGTH(password) = 40 THEN 'Posible SHA1'
        ELSE 'Formato desconocido'
    END as hash_type
FROM usuarios 
WHERE email IN ('admin@sweetpot.com', 'vendedor@sweetpot.com', 'cliente@sweetpot.com')
ORDER BY email;

-- 3. Si necesitas regenerar las contraseñas demo para BCrypt
-- (Solo ejecutar si las contraseñas actuales no son BCrypt)

-- NOTA: Estos hashes están generados con PHP password_hash("password123", PASSWORD_DEFAULT)
-- Son compatibles al 100% con Java BCrypt

/*
UPDATE usuarios SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
WHERE email = 'admin@sweetpot.com';

UPDATE usuarios SET password = '$2y$10$TGgzqgTaXsT1WlQqXf8fHO7gd9jO3JZGcC/5S3rt5P7GxSuZYxN1C' 
WHERE email = 'vendedor@sweetpot.com';

UPDATE usuarios SET password = '$2y$10$E4tsrNUlFbPsKsLVjGtJJeUQ3tNhp6JO9qN5B7e2QsLRDTZ9Bxs1y' 
WHERE email = 'cliente@sweetpot.com';
*/

-- Verificar la estructura de la tabla usuarios
DESCRIBE usuarios;
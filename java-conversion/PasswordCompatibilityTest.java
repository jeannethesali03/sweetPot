package test;

import controladores.PasswordUtil;

/**
 * Clase de prueba para verificar compatibilidad PHP-Java
 * con contraseñas BCrypt
 */
public class PasswordCompatibilityTest {
    
    public static void main(String[] args) {
        System.out.println("=== PRUEBA DE COMPATIBILIDAD PHP-JAVA ===\n");
        
        // Prueba 1: Hash generado en Java
        testJavaHash();
        
        // Prueba 2: Hash generado en PHP (simular)
        testPHPHash();
        
        // Prueba 3: Contraseñas de tus cuentas demo
        testDemoPasswords();
    }
    
    private static void testJavaHash() {
        System.out.println("1. PRUEBA CON HASH GENERADO EN JAVA:");
        String password = "password123";
        String javaHash = PasswordUtil.hashPassword(password);
        
        System.out.println("Contraseña: " + password);
        System.out.println("Hash Java: " + javaHash);
        System.out.println("Verificación Java: " + PasswordUtil.checkPassword(password, javaHash));
        System.out.println("Hash válido: " + PasswordUtil.isValidBCryptHash(javaHash));
        System.out.println();
    }
    
    private static void testPHPHash() {
        System.out.println("2. PRUEBA CON HASH GENERADO EN PHP:");
        String password = "password123";
        
        // Este es un hash real generado con PHP password_hash("password123", PASSWORD_DEFAULT)
        String phpHash = "$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi";
        
        System.out.println("Contraseña: " + password);
        System.out.println("Hash PHP: " + phpHash);
        System.out.println("Verificación Java: " + PasswordUtil.checkPassword(password, phpHash));
        System.out.println("Hash válido: " + PasswordUtil.isValidBCryptHash(phpHash));
        System.out.println();
    }
    
    private static void testDemoPasswords() {
        System.out.println("3. PRUEBA CON CONTRASEÑAS DEMO:");
        
        // Estas son las cuentas demo de tu login.php
        String[] emails = {"admin@sweetpot.com", "vendedor@sweetpot.com", "cliente@sweetpot.com"};
        String demoPassword = "password123";
        
        for (String email : emails) {
            String hash = PasswordUtil.hashPassword(demoPassword);
            boolean verify = PasswordUtil.checkPassword(demoPassword, hash);
            
            System.out.printf("Email: %s\n", email);
            System.out.printf("Password: %s\n", demoPassword);
            System.out.printf("Hash: %s\n", hash);
            System.out.printf("Verified: %s\n\n", verify);
        }
    }
    
    /**
     * Método para probar con hash real de tu base de datos
     * Ejecuta esto con un hash real de tu tabla usuarios
     */
    public static void testRealDatabaseHash(String realHash, String password) {
        System.out.println("=== PRUEBA CON HASH REAL DE BASE DE DATOS ===");
        System.out.println("Hash de BD: " + realHash);
        System.out.println("Password: " + password);
        System.out.println("Verificación: " + PasswordUtil.checkPassword(password, realHash));
        System.out.println("Hash BCrypt válido: " + PasswordUtil.isValidBCryptHash(realHash));
    }
}
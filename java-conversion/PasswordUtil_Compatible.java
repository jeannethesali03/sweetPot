package controladores;

import org.mindrot.jbcrypt.BCrypt;

/**
 * Utilidad para manejo de contraseñas con BCrypt - SweetPot
 * Compatible al 100% con PHP password_hash() y password_verify()
 */
public class PasswordUtil_Compatible {
    
    // Configuración compatible con PHP PASSWORD_DEFAULT
    private static final int BCRYPT_ROUNDS = 10; // Mismo que PHP por defecto
    
    /**
     * Encripta una contraseña usando BCrypt
     * Compatible con PHP password_hash($password, PASSWORD_DEFAULT)
     * @param password La contraseña en texto plano
     * @return La contraseña encriptada
     */
    public static String hashPassword(String password) {
        // Usar mismo número de rounds que PHP por defecto
        return BCrypt.hashpw(password, BCrypt.gensalt(BCRYPT_ROUNDS));
    }
    
    /**
     * Verifica si una contraseña coincide con su hash
     * Compatible con PHP password_verify($password, $hash)
     * @param password La contraseña en texto plano
     * @param hashed La contraseña encriptada (de PHP o Java)
     * @return true si coinciden, false en caso contrario
     */
    public static boolean checkPassword(String password, String hashed) {
        try {
            // Verificar que el hash no esté vacío
            if (hashed == null || hashed.trim().isEmpty()) {
                return false;
            }
            
            // Verificar que sea un hash BCrypt válido
            if (!hashed.startsWith("$2") || hashed.length() < 50) {
                System.err.println("Hash inválido o no es BCrypt: " + hashed);
                // Solo para debugging - remover en producción
                System.err.println("Longitud del hash: " + hashed.length());
                return false;
            }
            
            // Verificación BCrypt normal
            return BCrypt.checkpw(password, hashed);
            
        } catch (Exception e) {
            System.err.println("Error verificando contraseña: " + e.getMessage());
            System.err.println("Hash recibido: " + hashed);
            return false;
        }
    }
    
    /**
     * Genera un salt aleatorio para BCrypt con rounds compatibles con PHP
     * @return Un salt aleatorio
     */
    public static String generateSalt() {
        return BCrypt.gensalt(BCRYPT_ROUNDS);
    }
    
    /**
     * Genera un salt con número de rounds personalizado
     * @param rounds Número de rounds (recomendado: 10-12)
     * @return Un salt aleatorio
     */
    public static String generateSalt(int rounds) {
        return BCrypt.gensalt(rounds);
    }
    
    /**
     * Verifica si una contraseña es lo suficientemente fuerte
     * @param password La contraseña a verificar
     * @return true si es fuerte, false en caso contrario
     */
    public static boolean isStrongPassword(String password) {
        if (password == null || password.length() < 8) {
            return false;
        }
        
        boolean hasUpper = false;
        boolean hasLower = false;
        boolean hasDigit = false;
        boolean hasSpecial = false;
        
        for (char c : password.toCharArray()) {
            if (Character.isUpperCase(c)) {
                hasUpper = true;
            } else if (Character.isLowerCase(c)) {
                hasLower = true;
            } else if (Character.isDigit(c)) {
                hasDigit = true;
            } else if (!Character.isLetterOrDigit(c)) {
                hasSpecial = true;
            }
        }
        
        // Requiere al menos 3 de los 4 tipos de caracteres
        int types = 0;
        if (hasUpper) types++;
        if (hasLower) types++;
        if (hasDigit) types++;
        if (hasSpecial) types++;
        
        return types >= 3;
    }
    
    /**
     * Verifica si un hash es válido BCrypt
     * @param hash El hash a verificar
     * @return true si es un hash BCrypt válido
     */
    public static boolean isValidBCryptHash(String hash) {
        if (hash == null || hash.trim().isEmpty()) {
            return false;
        }
        
        // Los hashes BCrypt empiezan con $2a$, $2b$, $2x$, $2y$ 
        // y tienen al menos 60 caracteres
        return hash.matches("^\\$2[abxy]\\$\\d{2}\\$.{53}$");
    }
    
    /**
     * Método de prueba para verificar compatibilidad
     * @param password Contraseña de prueba
     * @return Información de debug
     */
    public static String testCompatibility(String password) {
        String hash = hashPassword(password);
        boolean verify = checkPassword(password, hash);
        
        return String.format(
            "Password: %s\nHash: %s\nVerified: %s\nHash válido: %s", 
            password, hash, verify, isValidBCryptHash(hash)
        );
    }
    
    /**
     * Método para probar con hashes reales de PHP
     * @param password Contraseña en texto plano
     * @param phpHash Hash generado por PHP
     * @return Resultado de la verificación
     */
    public static boolean testPHPCompatibility(String password, String phpHash) {
        System.out.println("=== PRUEBA DE COMPATIBILIDAD PHP ===");
        System.out.println("Password: " + password);
        System.out.println("PHP Hash: " + phpHash);
        System.out.println("Es BCrypt válido: " + isValidBCryptHash(phpHash));
        
        boolean result = checkPassword(password, phpHash);
        System.out.println("Verificación exitosa: " + result);
        System.out.println("=====================================");
        
        return result;
    }
}

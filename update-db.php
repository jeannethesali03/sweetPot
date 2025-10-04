<?php
// Script para eliminar columnas de QR de la base de datos
require_once 'config/config.php';

echo "<h2>Actualizando estructura de base de datos</h2>";

try {
    $db = new Database();
    $conn = $db->getConnection();

    echo "<h3>1. Eliminando columna qr_code de tickets...</h3>";
    try {
        $conn->exec("ALTER TABLE tickets DROP COLUMN qr_code");
        echo "✅ Columna qr_code eliminada de tickets<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "check that column/key exists") !== false) {
            echo "⚠️ La columna qr_code ya no existe en tickets<br>";
        } else {
            echo "❌ Error: " . $e->getMessage() . "<br>";
        }
    }

    echo "<h3>2. Eliminando columna codigo_qr de productos...</h3>";
    try {
        $conn->exec("ALTER TABLE productos DROP COLUMN codigo_qr");
        echo "✅ Columna codigo_qr eliminada de productos<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "check that column/key exists") !== false) {
            echo "⚠️ La columna codigo_qr ya no existe en productos<br>";
        } else {
            echo "❌ Error: " . $e->getMessage() . "<br>";
        }
    }

    echo "<h3>✅ Actualización completada</h3>";
    echo "<p>La base de datos ha sido actualizada para usar QR dinámicos.</p>";
    echo "<p>Ya no se guardan archivos de QR en el sistema.</p>";

    echo "<hr>";
    echo "<h3>Pruebas:</h3>";
    echo "<p><a href='generate-qr.php?data=" . urlencode('http://example.com') . "&size=150' target='_blank'>Probar generador de QR</a></p>";
    echo "<p><a href='cliente/ticket.php?numero=TK-00000005' target='_blank'>Ver ticket (si existe)</a></p>";

} catch (Exception $e) {
    echo "<h3>❌ Error general:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
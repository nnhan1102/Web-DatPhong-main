<?php
echo "PHP is working!<br>";
echo "PHP Version: " . phpversion() . "<br>";

// Test MySQL extension
if (extension_loaded('pdo_mysql')) {
    echo "✅ PDO MySQL extension is loaded<br>";
} else {
    echo "❌ PDO MySQL extension is NOT loaded<br>";
}

// Test database connection
try {
    $host = "localhost";
    $dbname = "hotel_opulent";
    $username = "root";
    $password = "12345678";
    
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Database connected successfully!<br>";
    
    // Test query
    $stmt = $conn->query("SELECT COUNT(*) as count FROM rooms");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "📊 Total rooms: " . ($result['count'] ?? '0') . "<br>";
    
} catch(PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}
?>
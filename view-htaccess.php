<?php
// view-htaccess.php
$file = "C:/xampp/htdocs/Web-DatPhong-main/backend/.htaccess";
echo "<h2>Content of .htaccess</h2>";
echo "<pre style='background: #f0f0f0; padding: 10px; border: 1px solid #ccc;'>";

if (file_exists($file)) {
    $content = file_get_contents($file);
    
    // Hiển thị với line numbers
    $lines = explode("\n", $content);
    foreach ($lines as $i => $line) {
        $lineNum = $i + 1;
        
        // Highlight dòng 20 (bị lỗi)
        if ($lineNum == 20) {
            echo "<span style='background: yellow; color: red; font-weight: bold;'>";
        }
        
        echo str_pad($lineNum, 3, ' ', STR_PAD_LEFT) . ": " . htmlspecialchars($line);
        
        if ($lineNum == 20) {
            echo " ← LỖI Ở DÒNG NÀY!";
            echo "</span>";
        }
        
        echo "\n";
    }
    
    echo "</pre>";
    
    // Kiểm tra ký tự đặc biệt
    echo "<h3>Checking for special characters:</h3>";
    
    // Check for <<<
    if (strpos($content, '<<<') !== false) {
        echo "❌ Found '<<<' at position: " . strpos($content, '<<<') . "<br>";
    }
    
    // Check for >>>  
    if (strpos($content, '>>>') !== false) {
        echo "❌ Found '>>>' at position: " . strpos($content, '>>>') . "<br>";
    }
    
    // Check for BOM
    if (substr($content, 0, 3) == "\xEF\xBB\xBF") {
        echo "❌ File has UTF-8 BOM<br>";
    }
    
    // Check line endings
    $crlf = substr_count($content, "\r\n");
    $lf = substr_count($content, "\n") - $crlf;
    $cr = substr_count($content, "\r") - $crlf;
    echo "Line endings: CRLF=$crlf, LF=$lf, CR=$cr<br>";
    
} else {
    echo "File not found!";
}
?>
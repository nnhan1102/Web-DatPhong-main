<?php
echo "<h2>Testing Apache Configuration</h2>";

// Test 1: Check if mod_rewrite is loaded
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    echo "mod_rewrite loaded: " . (in_array('mod_rewrite', $modules) ? '✅ YES' : '❌ NO') . "<br>";
} else {
    echo "Cannot check modules (apache_get_modules not available)<br>";
}

// Test 2: Check .htaccess processing
echo ".htaccess test: ";
if (isset($_SERVER['HTTP_MOD_REWRITE']) && $_SERVER['HTTP_MOD_REWRITE'] == 'On') {
    echo "✅ .htaccess is being processed<br>";
} else {
    echo "⚠️ Cannot determine .htaccess status<br>";
}

// Test 3: Check AllowOverride setting
$htaccessFile = __DIR__ . '/backend/.htaccess';
if (file_exists($htaccessFile)) {
    echo ".htaccess file exists: ✅<br>";
    
    // Try to read it
    $content = file_get_contents($htaccessFile);
    echo "File size: " . strlen($content) . " bytes<br>";
    
    // Check for problematic characters
    if (strpos($content, '<<<') !== false) {
        echo "❌ Found problematic '<<<' in file<br>";
    }
} else {
    echo ".htaccess file: ❌ NOT FOUND<br>";
}

// Test 4: Show current directory permissions
echo "<h3>Directory Info:</h3>";
echo "Current dir: " . __DIR__ . "<br>";
echo "Backend dir: " . __DIR__ . "/backend/<br>";

// Test 5: Try to create a test .htaccess
$testHtaccess = __DIR__ . '/backend/test.htaccess';
file_put_contents($testHtaccess, "RewriteEngine On\n# Test file");
echo "Test .htaccess created: " . (file_exists($testHtaccess) ? '✅' : '❌') . "<br>";
unlink($testHtaccess);
?>
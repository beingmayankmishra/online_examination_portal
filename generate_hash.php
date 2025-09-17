<?php
// generate_hash.php
$password = "admin123"; // Your actual password
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Email: coe@mindpoweruniversity.ac.in<br>";
echo "Password: " . $password . "<br>";
echo "Hash: " . $hash . "<br>";

// Test verification
echo "Verification: " . (password_verify($password, $hash) ? "SUCCESS" : "FAILED");
?>
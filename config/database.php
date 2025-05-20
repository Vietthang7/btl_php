<?php
$host = 'localhost';
$dbname = 'btl_web';
$username = 'root';
$password = '123456';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES utf8");
} catch(PDOException $e) {
    die("Kết nối thất bại: " . $e->getMessage());
}
?>
<?php
$host = '203.162.166.173:11111';
$dbname = 'intern_truonghoc247';
$username = 'intern_truonghoc247';
$password = 'intern_truonghoc247@1213';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES utf8");
} catch(PDOException $e) {
    die("Kết nối thất bại: " . $e->getMessage());
}
?>
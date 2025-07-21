<?php
require_once 'config.php';

// ทำลาย session
session_destroy();

// ลบ cookies (ถ้ามี)
setcookie('remember_token', '', time() - 3600, '/');

// กลับไปหน้าหลัก
header('Location: index.php');
exit;

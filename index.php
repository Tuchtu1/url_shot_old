<?php
//index.php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบย่อลิงก์ | คณะครุศาสตร์ มหาวิทยาลัยราชภัฏยะลา</title>
    <link rel="stylesheet" href="Templates.css">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="icon" type="image/x-icon"
        href="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQSpEtF75I2P5I04H3cKdvCKbiiYoZe1zjy7g&s">
    <link
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #1e3a8a;
            --secondary-color: #3b82f6;
            --accent-color: #f59e0b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --dark-color: #1f2937;
            --light-bg: #f8fafc;
            --white: #ffffff;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        body.en {
            font-family: 'Inter', sans-serif;
        }

        /* Animated Background */
        .bg-animation {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: -1;
            opacity: 0.5;
        }

        .bg-animation::before {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, var(--secondary-color) 0%, transparent 70%);
            top: -200px;
            right: -200px;
            animation: float 20s ease-in-out infinite;
        }

        .bg-animation::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, var(--accent-color) 0%, transparent 70%);
            bottom: -150px;
            left: -150px;
            animation: float 15s ease-in-out infinite reverse;
        }

        @keyframes float {

            0%,
            100% {
                transform: translate(0, 0) rotate(0deg);
            }

            33% {
                transform: translate(30px, -30px) rotate(120deg);
            }

            66% {
                transform: translate(-20px, 20px) rotate(240deg);
            }
        }

        /* Header Styles */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 3px solid var(--primary-color);
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo {
            width: 60px;
            height: 60px;
            background: white;
            /* เปลี่ยนจากสีน้ำเงิน */
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* เพิ่ม/แก้ไข CSS สำหรับ logo */

        .logo {
            width: 60px;
            height: 60px;
            background: white;
            /* เปลี่ยนจากสีน้ำเงิน */
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .logo-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 50%;
        }

        /* ถ้าต้องการให้โลโก้ไม่เป็นวงกลม */
        .logo.logo-square {
            border-radius: 10px;
            width: auto;
            height: 50px;
            padding: 10px;
        }

        .logo-square .logo-img {
            border-radius: 0;
            height: 100%;
            width: auto;
        }

        /* Hover effect */
        .logo:hover {
            transform: scale(1.05);
            transition: transform 0.3s ease;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .logo {
                width: 50px;
                height: 50px;
            }

            .logo.logo-square {
                height: 40px;
            }
        }

        .header-text h1 {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin: 0;
            font-weight: 700;
        }

        .header-text p {
            margin: 0;
            color: var(--dark-color);
            font-size: 0.9rem;
        }

        /* Language Switcher */
        .lang-switcher {
            position: relative;
        }

        .lang-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .lang-btn:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        /* Main Container */
        .main-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        /* Hero Section */
        .hero-section {
            text-align: center;
            margin-bottom: 3rem;
            animation: fadeInUp 0.8s ease;
        }

        .hero-title {
            font-size: 2.5rem;
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .hero-subtitle {
            color: var(--dark-color);
            font-size: 1.1rem;
            opacity: 0.8;
        }

        /* Feature Box */
        .feature-box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.8);
            animation: fadeInUp 0.8s ease 0.2s both;
        }

        /* Modern Tabs */
        .nav-tabs {
            border: none;
            background: var(--light-bg);
            padding: 0.5rem;
            border-radius: 15px;
            gap: 0.5rem;
            display: flex;
        }

        .nav-tabs .nav-link {
            border: none;
            background: transparent;
            color: var(--dark-color);
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-tabs .nav-link:hover {
            background: rgba(30, 58, 138, 0.1);
        }

        .nav-tabs .nav-link.active {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(30, 58, 138, 0.3);
        }

        .nav-tabs .nav-link i {
            font-size: 1.2rem;
        }

        /* Form Styles */
        .form-label {
            color: var(--dark-color);
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-control,
        .form-select {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        /* Button Styles */
        .btn {
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(30, 58, 138, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(30, 58, 138, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%);
            color: white;
        }

        .btn-info {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #2563eb 100%);
            color: white;
        }

        /* QR Style Section */
        .style-section {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .style-section h5 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Color Picker */
        .color-picker-wrapper {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .form-control-color {
            width: 50px;
            height: 40px;
            border-radius: 10px;
            cursor: pointer;
        }

        /* QR Style Preview */
        .style-preview {
            width: 60px;
            height: 60px;
            border: 3px solid #e5e7eb;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0.25rem;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            position: relative;
            overflow: hidden;
        }

        .style-preview:hover {
            transform: scale(1.1);
            border-color: var(--secondary-color);
        }

        .style-preview.selected {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.2);
        }

        .style-preview[data-style="square"] {
            background-image:
                repeating-linear-gradient(0deg, #333 0px, #333 8px, transparent 8px, transparent 12px),
                repeating-linear-gradient(90deg, #333 0px, #333 8px, transparent 8px, transparent 12px);
            background-size: 12px 12px;
            background-position: center;
        }

        .style-preview[data-style="dots"] {
            background-image: radial-gradient(circle, #333 30%, transparent 30%);
            background-size: 12px 12px;
            background-position: center;
        }

        .style-preview[data-style="rounded"] {
            background-image:
                repeating-linear-gradient(0deg, transparent 0px, transparent 4px, #333 4px, #333 8px, transparent 8px, transparent 12px),
                repeating-linear-gradient(90deg, transparent 0px, transparent 4px, #333 4px, #333 8px, transparent 8px, transparent 12px);
            background-size: 12px 12px;
            background-position: center;
        }

        .style-preview[data-style="extra-rounded"] {
            background-image: radial-gradient(circle at 6px 6px, #333 40%, transparent 40%);
            background-size: 12px 12px;
        }

        .style-preview[data-style="classy"] {
            background-image:
                linear-gradient(45deg, transparent 40%, #333 40%, #333 60%, transparent 60%),
                linear-gradient(-45deg, transparent 40%, #333 40%, #333 60%, transparent 60%);
            background-size: 12px 12px;
            background-position: center;
        }

        /* Result Box */
        .result-box {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 2px solid var(--success-color);
            border-radius: 15px;
            padding: 2rem;
            margin-top: 2rem;
            display: none;
            animation: slideIn 0.5s ease;
        }

        .result-box h5 {
            color: var(--success-color);
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .qr-preview {
            text-align: center;
            padding: 1rem;
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow);
        }

        .qr-preview img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--secondary-color);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-color);
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
        }

        /* Notification */
        .notification {
            position: fixed;
            top: 100px;
            right: 20px;
            min-width: 300px;
            z-index: 9999;
            animation: slideInRight 0.3s ease;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Loading Animation */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid white;
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 2rem 0;
            color: var(--dark-color);
            opacity: 0.7;
            margin-top: 4rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }

            .header-text h1 {
                font-size: 1.2rem;
            }

            .nav-tabs {
                flex-direction: column;
            }

            .nav-tabs .nav-link {
                width: 100%;
                justify-content: center;
            }

            .feature-box {
                padding: 1.5rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Print Styles */
        @media print {

            .header,
            .nav-tabs,
            .style-section,
            .btn,
            .lang-switcher {
                display: none !important;
            }

            .result-box {
                border: 1px solid #000 !important;
                box-shadow: none !important;
            }
        }

        .modal {
            display: none;
        }

        .modal.show {
            display: block;
        }

        /* Prevent body scroll issues */
        body.modal-open {
            overflow: hidden !important;
            padding-right: 0 !important;
        }

        /* Ensure backdrop is behind modal */
        .modal-backdrop {
            z-index: 1040;
        }

        .modal {
            z-index: 1050;
        }

        /* Remove any inline styles that might interfere */
        .modal[style*="display: block"] {
            display: block !important;
        }

        /* Ensure proper transitions */
        .modal.fade .modal-dialog {
            transition: transform 0.3s ease-out;
            transform: translate(0, -50px);
        }

        .modal.show .modal-dialog {
            transform: none;
        }

        /* Fix for template modal specifically */
        #templateModal {
            display: none;
        }

        #templateModal.show {
            display: block;
        }

        /* Ensure content is not darkened when modal is closed */
        body:not(.modal-open) {
            overflow: auto !important;
        }

        /* Remove any leftover backdrop opacity */
        .modal-backdrop.show {
            opacity: 0.5;
        }

        .modal-backdrop:not(.show) {
            opacity: 0;
            display: none;
        }
    </style>
</head>

<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Background Animation -->
    <div class="bg-animation"></div>

    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="logo-section">
                        <div class="logo">
                            <img src="https://edu.yru.ac.th/qrcodegenerator/img/logo.png" alt="EDU Logo"
                                class="logo-img">
                        </div>
                        <div class="header-text">
                            <h1 data-lang="header.title">ระบบย่อลิงก์และสร้าง QR Code</h1>
                            <p data-lang="header.subtitle">คณะครุศาสตร์ มหาวิทยาลัยราชภัฏยะลา</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="lang-switcher">
                        <button class="lang-btn" onclick="toggleLanguage()">
                            <i class="bi bi-globe"></i>
                            <span id="langText">EN</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container main-container">
        <!-- Hero Section -->
        <div class="hero-section">
            <h2 class="hero-title" data-lang="hero.title">
                <i class="bi bi-link-45deg"></i> ย่อลิงก์ง่ายๆ สร้าง QR Code ทันที
            </h2>
            <p class="hero-subtitle" data-lang="hero.subtitle">
                ระบบจัดการลิงก์และ QR Code สำหรับอาจารย์และบุคลากร คณะครุศาสตร์
            </p>
        </div>

        <!-- Feature Box -->
        <div class="feature-box">
            <!-- Navigation Tabs -->
            <ul class="nav nav-tabs mb-4" id="mainTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="url-tab" data-bs-toggle="tab" data-bs-target="#url-content"
                        type="button">
                        <i class="bi bi-link"></i>
                        <span data-lang="tab.url">ย่อลิงก์</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="file-tab" data-bs-toggle="tab" data-bs-target="#file-content"
                        type="button">
                        <i class="bi bi-file-earmark-arrow-up"></i>
                        <span data-lang="tab.file">แนบไฟล์</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="text-tab" data-bs-toggle="tab" data-bs-target="#text-content"
                        type="button">
                        <i class="bi bi-text-paragraph"></i>
                        <span data-lang="tab.text">ข้อความ</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="wifi-tab" data-bs-toggle="tab" data-bs-target="#wifi-content"
                        type="button">
                        <i class="bi bi-wifi"></i>
                        <span data-lang="tab.wifi">WiFi</span>
                    </button>
                </li>
            </ul>

            <!-- Tab Contents -->
            <div class="tab-content" id="mainTabContent">
                <!-- URL Tab -->
                <div class="tab-pane fade show active" id="url-content" role="tabpanel">
                    <form id="urlForm" method="POST" action="create.php">
                        <input type="hidden" name="type" value="link">

                        <div class="mb-4">
                            <label for="original_url" class="form-label">
                                <i class="bi bi-globe"></i>
                                <span data-lang="form.url">URL ที่ต้องการย่อ</span>
                            </label>
                            <input type="url" class="form-control" id="original_url" name="original_url"
                                placeholder="https://example.com" required>
                        </div>

                        <div class="mb-4">
                            <label for="custom_code" class="form-label">
                                <i class="bi bi-pencil-square"></i>
                                <span data-lang="form.custom">กำหนดรหัสเอง (ไม่บังคับ)</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><?php echo SITE_URL; ?></span>
                                <input type="text" class="form-control" id="custom_code" name="custom_code"
                                    pattern="[a-zA-Z0-9_\-]+" placeholder="custom-code">
                            </div>
                            <small class="text-muted" data-lang="form.customHint">ใช้ตัวอักษร a-z, A-Z, 0-9, - และ _
                                เท่านั้น</small>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="password" class="form-label">
                                    <i class="bi bi-lock"></i>
                                    <span data-lang="form.password">รหัสผ่านป้องกัน (ไม่บังคับ)</span>
                                </label>
                                <input type="password" class="form-control" id="password" name="password">
                            </div>
                            <div class="col-md-6">
                                <label for="expires_at" class="form-label">
                                    <i class="bi bi-calendar-event"></i>
                                    <span data-lang="form.expiry">วันหมดอายุ (ไม่บังคับ)</span>
                                </label>
                                <input type="datetime-local" class="form-control" id="expires_at" name="expires_at">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i>
                            <span data-lang="btn.createLink">สร้างลิงก์สั้น</span>
                        </button>
                    </form>
                </div>

                <!-- Text Tab -->
                <div class="tab-pane fade" id="text-content" role="tabpanel">
                    <form id="textForm" method="POST" action="create.php">
                        <input type="hidden" name="type" value="text">

                        <div class="mb-4">
                            <label for="text_content" class="form-label">
                                <i class="bi bi-card-text"></i>
                                <span data-lang="form.textContent">ข้อความที่ต้องการสร้าง QR Code</span>
                            </label>
                            <textarea class="form-control" id="text_content" name="text_content" rows="5" required
                                data-lang-placeholder="form.textPlaceholder"
                                placeholder="พิมพ์ข้อความที่นี่..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-qr-code"></i>
                            <span data-lang="btn.createQR">สร้าง QR Code</span>
                        </button>
                    </form>
                </div>

                <!-- WiFi Tab -->
                <div class="tab-pane fade" id="wifi-content" role="tabpanel">
                    <form id="wifiForm" method="POST" action="create.php">
                        <input type="hidden" name="type" value="wifi">

                        <div class="mb-4">
                            <label for="ssid" class="form-label">
                                <i class="bi bi-router"></i>
                                <span data-lang="form.ssid">ชื่อ WiFi (SSID)</span>
                            </label>
                            <input type="text" class="form-control" id="ssid" name="ssid" required>
                        </div>

                        <div class="mb-4">
                            <label for="wifi_password" class="form-label">
                                <i class="bi bi-key"></i>
                                <span data-lang="form.wifiPassword">รหัสผ่าน WiFi</span>
                            </label>
                            <input type="password" class="form-control" id="wifi_password" name="wifi_password">
                        </div>

                        <div class="mb-4">
                            <label for="security_type" class="form-label">
                                <i class="bi bi-shield-lock"></i>
                                <span data-lang="form.security">ประเภทการเข้ารหัส</span>
                            </label>
                            <select class="form-select" id="security_type" name="security_type">
                                <option value="WPA2" selected>WPA/WPA2</option>
                                <option value="WEP">WEP</option>
                                <option value="nopass" data-lang="form.noPassword">ไม่มีรหัสผ่าน</option>
                            </select>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="hidden" name="hidden">
                            <label class="form-check-label" for="hidden">
                                <span data-lang="form.hiddenNetwork">เครือข่ายซ่อน (Hidden Network)</span>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-qr-code"></i>
                            <span data-lang="btn.createWifiQR">สร้าง WiFi QR Code</span>
                        </button>
                    </form>

                </div>
                <div class="tab-pane fade" id="file-content" role="tabpanel">
                    <form id="fileForm" method="POST" action="create.php" enctype="multipart/form-data">
                        <input type="hidden" name="type" value="file">

                        <div class="mb-4">
                            <label for="upload_file" class="form-label">
                                <i class="bi bi-cloud-upload"></i>
                                <span data-lang="form.uploadFile">เลือกไฟล์ที่ต้องการอัพโหลด</span>
                            </label>
                            <input type="file" class="form-control" id="upload_file" name="upload_file" required>
                            <small class="text-muted" data-lang="form.fileHint">รองรับไฟล์ทุกประเภท ขนาดไม่เกิน
                                50MB</small>
                        </div>

                        <div class="mb-4">
                            <label for="file_title" class="form-label">
                                <i class="bi bi-type"></i>
                                <span data-lang="form.fileTitle">ชื่อไฟล์/คำอธิบาย</span>
                            </label>
                            <input type="text" class="form-control" id="file_title" name="file_title"
                                placeholder="เอกสารการประชุม ครั้งที่ 1/2567">
                        </div>

                        <div class="mb-4">
                            <label for="file_custom_code" class="form-label">
                                <i class="bi bi-pencil-square"></i>
                                <span data-lang="form.custom">กำหนดรหัสเอง (ไม่บังคับ)</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><?php echo SITE_URL; ?></span>
                                <input type="text" class="form-control" id="file_custom_code" name="custom_code"
                                    pattern="[a-zA-Z0-9_\-]+" placeholder="meeting-doc-1">
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="file_password" class="form-label">
                                    <i class="bi bi-lock"></i>
                                    <span data-lang="form.password">รหัสผ่านป้องกัน (ไม่บังคับ)</span>
                                </label>
                                <input type="password" class="form-control" id="file_password" name="password">
                            </div>
                            <div class="col-md-6">
                                <label for="file_expires_at" class="form-label">
                                    <i class="bi bi-calendar-event"></i>
                                    <span data-lang="form.expiry">วันหมดอายุ (ไม่บังคับ)</span>
                                </label>
                                <input type="datetime-local" class="form-control" id="file_expires_at"
                                    name="expires_at">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">
                                <i class="bi bi-download"></i>
                                <span data-lang="form.downloadLimit">จำกัดจำนวนดาวน์โหลด (ไม่บังคับ)</span>
                            </label>
                            <input type="number" class="form-control" id="download_limit" name="download_limit" min="1"
                                placeholder="ไม่จำกัด">
                            <small class="text-muted"
                                data-lang="form.downloadLimitHint">ปล่อยว่างหากไม่ต้องการจำกัด</small>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-cloud-upload"></i>
                            <span data-lang="btn.uploadFile">อัพโหลดและสร้างลิงก์</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- QR Code Style Options -->
            <div class="style-section">
                <h5>
                    <i class="bi bi-palette"></i>
                    <span data-lang="style.title">ตกแต่ง QR Code</span>
                </h5>

                <div class="row mt-3">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" data-lang="style.bgColor">สีพื้นหลัง</label>
                        <div class="color-picker-wrapper">
                            <input type="color" class="form-control form-control-color" id="bg_color" name="bg_color"
                                value="#FFFFFF">
                            <input type="text" class="form-control" value="#FFFFFF" readonly>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" data-lang="style.fgColor">สี QR Code</label>
                        <div class="color-picker-wrapper">
                            <input type="color" class="form-control form-control-color" id="fg_color" name="fg_color"
                                value="#000000">
                            <input type="text" class="form-control" value="#000000" readonly>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-12 mb-3">
                        <label class="form-label" data-lang="style.pattern">รูปแบบจุด QR Code</label>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <div class="style-preview selected" data-style="square" title="Square - มาตรฐาน"></div>
                            <div class="style-preview" data-style="dots" title="Dots - จุดกลม"></div>
                            <div class="style-preview" data-style="rounded" title="Rounded - มุมโค้ง"></div>
                            <div class="style-preview" data-style="extra-rounded" title="Extra Rounded - โค้งมาก"></div>
                            <div class="style-preview" data-style="classy" title="Classy - หรูหรา"></div>
                        </div>
                        <input type="hidden" id="dot_style" name="dot_style" value="square">
                        <small class="text-muted" data-lang="style.patternHint">คลิกเพื่อเลือกรูปแบบ</small>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-12">
                        <label class="form-label">
                            <i class="bi bi-image"></i>
                            <span data-lang="style.logo">อัพโหลดโลโก้ (ไม่บังคับ)</span>
                        </label>
                        <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                        <small class="text-muted" data-lang="style.logoHint">รองรับไฟล์ PNG, JPG, GIF ขนาดไม่เกิน
                            2MB</small>
                    </div>
                </div>
            </div>
            <!-- QR Code Templates -->
            <div class="templates-section mt-4">
                <h5>
                    <i class="bi bi-grid-3x3-gap"></i>
                    <span data-lang="templates.title">เทมเพลต QR Code สำเร็จรูป</span>
                    <button class="btn btn-sm btn-outline-primary float-end" onclick="toggleTemplates()">
                        <span data-lang="templates.viewAll">ดูทั้งหมด</span>
                    </button>
                </h5>

                <div class="templates-grid mt-3">
                    <!-- Blank Template -->
                    <div class="template-item" onclick="selectTemplate('blank')">
                        <div class="template-preview">
                            <i class="bi bi-x-lg"></i>
                        </div>
                        <small>Blank</small>
                    </div>

                    <!-- Template 1: Modern Gradient -->
                    <div class="template-item" onclick="selectTemplate('gradient')">
                        <div class="template-preview"
                            style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <div class="qr-demo"></div>
                        </div>
                        <small>Gradient</small>
                    </div>

                    <!-- Template 2: Education Theme -->
                    <div class="template-item" onclick="selectTemplate('education')">
                        <div class="template-preview" style="background: #f0f9ff; border: 2px solid #3b82f6;">
                            <div class="qr-demo" style="background: #1e3a8a;"></div>
                            <i class="bi bi-mortarboard-fill" style="color: #3b82f6;"></i>
                        </div>
                        <small>Education</small>
                    </div>

                    <!-- Template 3: Colorful -->
                    <div class="template-item" onclick="selectTemplate('colorful')">
                        <div class="template-preview" style="background: #fef3c7;">
                            <div class="qr-demo" style="background: #f59e0b;"></div>
                            <div class="template-dots">
                                <span style="background: #ef4444;"></span>
                                <span style="background: #10b981;"></span>
                                <span style="background: #3b82f6;"></span>
                            </div>
                        </div>
                        <small>Colorful</small>
                    </div>

                    <!-- Template 4: Dark Mode -->
                    <div class="template-item" onclick="selectTemplate('dark')">
                        <div class="template-preview" style="background: #1f2937;">
                            <div class="qr-demo" style="background: white;"></div>
                        </div>
                        <small>Dark</small>
                    </div>

                    <!-- Template 5: Sakura -->
                    <div class="template-item" onclick="selectTemplate('sakura')">
                        <div class="template-preview" style="background: #fce7f3;">
                            <div class="qr-demo" style="background: #ec4899;"></div>
                            <div class="sakura-petals">
                                <i class="bi bi-flower1" style="color: #f9a8d4;"></i>
                                <i class="bi bi-flower1" style="color: #f9a8d4;"></i>
                            </div>
                        </div>
                        <small>Sakura</small>
                    </div>

                    <!-- Template 6: Tech -->
                    <div class="template-item" onclick="selectTemplate('tech')">
                        <div class="template-preview" style="background: #111827;">
                            <div class="qr-demo" style="background: #10b981;"></div>
                            <div class="tech-lines"></div>
                        </div>
                        <small>Tech</small>
                    </div>

                    <!-- Template 7: Minimal -->
                    <div class="template-item" onclick="selectTemplate('minimal')">
                        <div class="template-preview" style="background: white; border: 1px solid #e5e7eb;">
                            <div class="qr-demo" style="background: black;"></div>
                        </div>
                        <small>Minimal</small>
                    </div>

                    <!-- Template 8: Ocean -->
                    <div class="template-item" onclick="selectTemplate('ocean')">
                        <div class="template-preview" style="background: linear-gradient(to bottom, #0ea5e9, #0284c7);">
                            <div class="qr-demo" style="background: white;"></div>
                            <div class="wave-decoration"></div>
                        </div>
                        <small>Ocean</small>
                    </div>

                    <!-- More Templates Button -->
                    <div class="template-item more-templates" onclick="showMoreTemplates()">
                        <div class="template-preview">
                            <span class="more-count">+12</span>
                        </div>
                        <small data-lang="templates.more">เพิ่มเติม</small>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="templateModal" tabindex="-1" aria-labelledby="templateModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="templateModalLabel" data-lang="templates.allTemplates">เทมเพลต
                                QR Code ทั้งหมด</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <!-- Extended Templates -->
                                <div class="col-md-3">
                                    <div class="template-card" onclick="applyTemplate('frame1')">
                                        <div class="template-large-preview frame-style-1">
                                            <div class="qr-placeholder"></div>
                                        </div>
                                        <h6>Classic Frame</h6>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="template-card" onclick="applyTemplate('neon')">
                                        <div class="template-large-preview neon-style">
                                            <div class="qr-placeholder"></div>
                                        </div>
                                        <h6>Neon Glow</h6>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="template-card" onclick="applyTemplate('retro')">
                                        <div class="template-large-preview retro-style">
                                            <div class="qr-placeholder"></div>
                                        </div>
                                        <h6>Retro</h6>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="template-card" onclick="applyTemplate('nature')">
                                        <div class="template-large-preview nature-style">
                                            <div class="qr-placeholder"></div>
                                        </div>
                                        <h6>Nature</h6>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="template-card" onclick="applyTemplate('elegant')">
                                        <div class="template-large-preview elegant-style">
                                            <div class="qr-placeholder"></div>
                                        </div>
                                        <h6>Elegant</h6>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="template-card" onclick="applyTemplate('kids')">
                                        <div class="template-large-preview kids-style">
                                            <div class="qr-placeholder"></div>
                                        </div>
                                        <h6>Kids</h6>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="template-card" onclick="applyTemplate('business')">
                                        <div class="template-large-preview business-style">
                                            <div class="qr-placeholder"></div>
                                        </div>
                                        <h6>Business</h6>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="template-card" onclick="applyTemplate('festive')">
                                        <div class="template-large-preview festive-style">
                                            <div class="qr-placeholder"></div>
                                        </div>
                                        <h6>Festive</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Result Section -->
            <div id="resultBox" class="result-box">
                <h5>
                    <i class="bi bi-check-circle-fill"></i>
                    <span data-lang="result.success">สร้างสำเร็จ!</span>
                </h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label" data-lang="result.shortLink">ลิงก์สั้น:</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="shortUrl" readonly>
                                <button class="btn btn-outline-secondary" onclick="copyToClipboard()">
                                    <i class="bi bi-clipboard"></i>
                                    <span data-lang="btn.copy">คัดลอก</span>
                                </button>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button class="btn btn-success" onclick="downloadQR()">
                                <i class="bi bi-download"></i>
                                <span data-lang="btn.download">ดาวน์โหลด QR Code</span>
                            </button>
                            <button class="btn btn-info" onclick="viewStats()">
                                <i class="bi bi-bar-chart"></i>
                                <span data-lang="btn.viewStats">ดูสถิติ</span>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="qr-preview">
                            <img id="qrImage" src="" alt="QR Code">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Section (Example) 
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-link-45deg"></i>
                </div>
                <div class="stat-value">1,234</div>
                <div class="stat-label" data-lang="stats.totalLinks">ลิงก์ทั้งหมด</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-eye"></i>
                </div>
                <div class="stat-value">5,678</div>
                <div class="stat-label" data-lang="stats.totalClicks">จำนวนคลิก</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-qr-code"></i>
                </div>
                <div class="stat-value">890</div>
                <div class="stat-label" data-lang="stats.qrGenerated">QR Code ที่สร้าง</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div class="stat-value">234</div>
                <div class="stat-label" data-lang="stats.activeUsers">ผู้ใช้งาน</div>
            </div>
        </div>
    </div>-->

        <!-- Footer -->
        <footer class="footer">
            <p data-lang="footer.copyright">© 2024 คณะครุศาสตร์ มหาวิทยาลัยราชภัฏยะลา | เจ้าหน้าที่ฝ่ายเทคโนโลยีสารสนเทศ
            </p>
        </footer>

        <!-- Scripts -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

        <script>
            // Language translations
            const translations = {
                th: {
                    'header.title': 'ระบบย่อลิงก์และสร้าง QR Code',
                    'header.subtitle': 'คณะครุศาสตร์ มหาวิทยาลัยราชภัฏยะลา',
                    'hero.title': 'ย่อลิงก์ง่ายๆ สร้าง QR Code ทันที',
                    'hero.subtitle': 'ระบบจัดการลิงก์และ QR Code สำหรับอาจารย์และบุคลากร คณะครุศาสตร์',
                    'tab.url': 'ย่อลิงก์',
                    'tab.text': 'ข้อความ',
                    'tab.wifi': 'WiFi',
                    'form.url': 'URL ที่ต้องการย่อ',
                    'form.custom': 'กำหนดรหัสเอง (ไม่บังคับ)',
                    'form.customHint': 'ใช้ตัวอักษร a-z, A-Z, 0-9, - และ _ เท่านั้น',
                    'form.password': 'รหัสผ่านป้องกัน (ไม่บังคับ)',
                    'form.expiry': 'วันหมดอายุ (ไม่บังคับ)',
                    'form.textContent': 'ข้อความที่ต้องการสร้าง QR Code',
                    'form.textPlaceholder': 'พิมพ์ข้อความที่นี่...',
                    'form.ssid': 'ชื่อ WiFi (SSID)',
                    'form.wifiPassword': 'รหัสผ่าน WiFi',
                    'form.security': 'ประเภทการเข้ารหัส',
                    'form.noPassword': 'ไม่มีรหัสผ่าน',
                    'form.hiddenNetwork': 'เครือข่ายซ่อน (Hidden Network)',
                    'btn.createLink': 'สร้างลิงก์สั้น',
                    'btn.createQR': 'สร้าง QR Code',
                    'btn.createWifiQR': 'สร้าง WiFi QR Code',
                    'btn.copy': 'คัดลอก',
                    'btn.download': 'ดาวน์โหลด QR Code',
                    'btn.viewStats': 'ดูสถิติ',
                    'style.title': 'ตกแต่ง QR Code',
                    'style.bgColor': 'สีพื้นหลัง',
                    'style.fgColor': 'สี QR Code',
                    'style.pattern': 'รูปแบบจุด QR Code',
                    'style.patternHint': 'คลิกเพื่อเลือกรูปแบบ',
                    'style.logo': 'อัพโหลดโลโก้ (ไม่บังคับ)',
                    'style.logoHint': 'รองรับไฟล์ PNG, JPG, GIF ขนาดไม่เกิน 2MB',
                    'result.success': 'สร้างสำเร็จ!',
                    'result.shortLink': 'ลิงก์สั้น:',
                    'stats.totalLinks': 'ลิงก์ทั้งหมด',
                    'stats.totalClicks': 'จำนวนคลิก',
                    'stats.qrGenerated': 'QR Code ที่สร้าง',
                    'stats.activeUsers': 'ผู้ใช้งาน',
                    'footer.copyright': '© 2024 คณะครุศาสตร์ มหาวิทยาลัยราชภัฏยะลา | พัฒนาโดยฝ่ายเทคโนโลยีสารสนเทศ',
                    'notification.copied': 'คัดลอกแล้ว!',
                    'notification.processing': 'กำลังประมวลผล...',
                    'notification.error': 'เกิดข้อผิดพลาด'
                },
                en: {
                    'header.title': 'Link Shortener & QR Code Generator',
                    'header.subtitle': 'Faculty of Education, Yala Rajabhat University',
                    'hero.title': 'Shorten Links Easily, Create QR Codes Instantly',
                    'hero.subtitle': 'Link and QR Code management system for faculty and staff',
                    'tab.url': 'Shorten URL',
                    'tab.text': 'Text',
                    'tab.wifi': 'WiFi',
                    'form.url': 'URL to shorten',
                    'form.custom': 'Custom code (optional)',
                    'form.customHint': 'Use only a-z, A-Z, 0-9, - and _',
                    'form.password': 'Password protection (optional)',
                    'form.expiry': 'Expiry date (optional)',
                    'form.textContent': 'Text for QR Code',
                    'form.textPlaceholder': 'Type your text here...',
                    'form.ssid': 'WiFi Name (SSID)',
                    'form.wifiPassword': 'WiFi Password',
                    'form.security': 'Security Type',
                    'form.noPassword': 'No password',
                    'form.hiddenNetwork': 'Hidden Network',
                    'btn.createLink': 'Create Short Link',
                    'btn.createQR': 'Create QR Code',
                    'btn.createWifiQR': 'Create WiFi QR Code',
                    'btn.copy': 'Copy',
                    'btn.download': 'Download QR Code',
                    'btn.viewStats': 'View Stats',
                    'style.title': 'Customize QR Code',
                    'style.bgColor': 'Background Color',
                    'style.fgColor': 'QR Code Color',
                    'style.pattern': 'QR Code Pattern',
                    'style.patternHint': 'Click to select pattern',
                    'style.logo': 'Upload Logo (optional)',
                    'style.logoHint': 'Supports PNG, JPG, GIF up to 2MB',
                    'result.success': 'Created Successfully!',
                    'result.shortLink': 'Short Link:',
                    'stats.totalLinks': 'Total Links',
                    'stats.totalClicks': 'Total Clicks',
                    'stats.qrGenerated': 'QR Codes Generated',
                    'stats.activeUsers': 'Active Users',
                    'footer.copyright': '© 2024 Faculty of Education, Yala Rajabhat University | Developed by IT Department',
                    'notification.copied': 'Copied!',
                    'notification.processing': 'Processing...',
                    'notification.error': 'An error occurred'
                }
            };

            let currentLang = 'th';

            // Toggle language
            function toggleLanguage() {
                currentLang = currentLang === 'th' ? 'en' : 'th';
                document.documentElement.lang = currentLang;
                document.body.className = currentLang === 'en' ? 'en' : '';
                document.getElementById('langText').textContent = currentLang === 'th' ? 'EN' : 'TH';
                updateLanguage();
            }

            // Update language texts
            function updateLanguage() {
                document.querySelectorAll('[data-lang]').forEach(element => {
                    const key = element.getAttribute('data-lang');
                    if (translations[currentLang][key]) {
                        element.textContent = translations[currentLang][key];
                    }
                });

                // Update placeholders
                document.querySelectorAll('[data-lang-placeholder]').forEach(element => {
                    const key = element.getAttribute('data-lang-placeholder');
                    if (translations[currentLang][key]) {
                        element.placeholder = translations[currentLang][key];
                    }
                });
            }

            // Color picker handlers
            document.querySelectorAll('.form-control-color').forEach(input => {
                input.addEventListener('change', function() {
                    this.nextElementSibling.value = this.value.toUpperCase();
                    updateStylePreview();
                });
            });

            // Update style preview
            function updateStylePreview() {
                const fgColor = document.getElementById('fg_color').value;
                const bgColor = document.getElementById('bg_color').value;

                document.querySelectorAll('.style-preview').forEach(preview => {
                    preview.style.backgroundColor = bgColor;
                    const style = preview.dataset.style;

                    switch (style) {
                        case 'square':
                            preview.style.backgroundImage =
                                `repeating-linear-gradient(0deg, ${fgColor} 0px, ${fgColor} 8px, transparent 8px, transparent 12px),
                             repeating-linear-gradient(90deg, ${fgColor} 0px, ${fgColor} 8px, transparent 8px, transparent 12px)`;
                            break;
                        case 'dots':
                            preview.style.backgroundImage =
                                `radial-gradient(circle, ${fgColor} 30%, transparent 30%)`;
                            break;
                        case 'rounded':
                            preview.style.backgroundImage =
                                `repeating-linear-gradient(0deg, transparent 0px, transparent 4px, ${fgColor} 4px, ${fgColor} 8px, transparent 8px, transparent 12px),
                             repeating-linear-gradient(90deg, transparent 0px, transparent 4px, ${fgColor} 4px, ${fgColor} 8px, transparent 8px, transparent 12px)`;
                            break;
                        case 'extra-rounded':
                            preview.style.backgroundImage =
                                `radial-gradient(circle at 6px 6px, ${fgColor} 40%, transparent 40%)`;
                            break;
                        case 'classy':
                            preview.style.backgroundImage =
                                `linear-gradient(45deg, transparent 40%, ${fgColor} 40%, ${fgColor} 60%, transparent 60%),
                             linear-gradient(-45deg, transparent 40%, ${fgColor} 40%, ${fgColor} 60%, transparent 60%)`;
                            break;
                    }
                });
            }

            // Style preview selection
            document.querySelectorAll('.style-preview').forEach(preview => {
                preview.addEventListener('click', function() {
                    document.querySelectorAll('.style-preview').forEach(p => p.classList.remove(
                        'selected'));
                    this.classList.add('selected');
                    document.getElementById('dot_style').value = this.dataset.style;
                });
            });

            // Copy to clipboard function
            function copyToClipboard() {
                const input = document.getElementById('shortUrl');
                if (!input || !input.value) {
                    showNotification('ไม่มีข้อมูลให้คัดลอก', 'error');
                    return;
                }

                // Select and copy
                input.select();
                input.setSelectionRange(0, 99999); // For mobile devices

                try {
                    document.execCommand('copy');

                    // Update button temporarily
                    const btn = event.target.closest('button');
                    const originalHTML = btn.innerHTML;
                    btn.innerHTML = '<i class="bi bi-check"></i> คัดลอกแล้ว!';
                    btn.classList.add('btn-success');
                    btn.classList.remove('btn-outline-secondary');

                    setTimeout(() => {
                        btn.innerHTML = originalHTML;
                        btn.classList.remove('btn-success');
                        btn.classList.add('btn-outline-secondary');
                    }, 2000);

                    showNotification('คัดลอกแล้ว!', 'success');
                } catch (err) {
                    showNotification('ไม่สามารถคัดลอกได้', 'error');
                }
            }

            // Download QR Code function
            function downloadQR() {
                const qrImage = document.getElementById('qrImage');
                if (!qrImage || !qrImage.src) {
                    showNotification('ไม่พบ QR Code', 'error');
                    return;
                }

                const qrSrc = qrImage.src;

                if (qrSrc.startsWith('data:')) {
                    // Data URL - download directly
                    const link = document.createElement('a');
                    link.href = qrSrc;
                    link.download = 'qrcode_' + Date.now() + '.png';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else if (qrSrc.includes('qr_cache/')) {
                    // Local file - use download.php
                    const matches = qrSrc.match(/qr_cache\/[a-zA-Z0-9_\-]+\.png/);
                    if (matches) {
                        window.location.href = 'download.php?file=' + encodeURIComponent(matches[0]);
                    } else {
                        showNotification('ไม่สามารถดาวน์โหลดได้', 'error');
                    }
                } else {
                    // External URL - open in new window
                    window.open(qrSrc, '_blank');
                }
            }

            // Show notification function
            function showNotification(message, type = 'info') {
                // Remove existing notifications
                $('.notification').remove();

                const alertClass = type === 'success' ? 'alert-success' :
                    type === 'error' ? 'alert-danger' : 'alert-info';

                const icon = type === 'success' ? 'check-circle-fill' :
                    type === 'error' ? 'exclamation-triangle-fill' : 'info-circle-fill';

                const notification = $(`
        <div class="alert ${alertClass} alert-dismissible fade show notification" role="alert">
            <i class="bi bi-${icon}"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);

                $('body').append(notification);

                // Auto dismiss after 5 seconds
                setTimeout(() => {
                    notification.fadeOut('slow', function() {
                        $(this).remove();
                    });
                }, 5000);
            }

            // View Stats function
            function viewStats() {
                if (currentUrlId) {
                    window.open('stats.php?id=' + currentUrlId, '_blank');
                } else {
                    showNotification('ไม่พบข้อมูล URL', 'error');
                }
            }

            // Show/hide loading overlay
            function showLoading(show = true) {
                const overlay = document.getElementById('loadingOverlay');
                if (overlay) {
                    overlay.style.display = show ? 'flex' : 'none';
                }
            }

            // Global variable to store current URL ID
            let currentUrlId = null;

            // Form submission with AJAX
            $(document).ready(function() {
                // Prevent default form submission for all forms
                $('#urlForm, #textForm, #wifiForm').on('submit', function(e) {
                    e.preventDefault();

                    const form = this;
                    const formData = new FormData(form);

                    // Debug: log form data
                    console.log('Form ID:', form.id);
                    console.log('Form method:', form.method);
                    console.log('Form action:', form.action);

                    // Add QR styling data
                    formData.append('bg_color', $('#bg_color').val());
                    formData.append('fg_color', $('#fg_color').val());
                    formData.append('dot_style', $('#dot_style').val());

                    // Add logo if exists
                    const logoInput = $('#logo')[0];
                    if (logoInput && logoInput.files && logoInput.files[0]) {
                        formData.append('logo', logoInput.files[0]);
                    }

                    // Debug: log FormData contents
                    for (let pair of formData.entries()) {
                        console.log(pair[0] + ': ' + pair[1]);
                    }

                    // Show loading
                    showLoading(true);

                    const submitBtn = $(this).find('button[type="submit"]');
                    const originalText = submitBtn.html();
                    submitBtn.html(
                            `<span class="spinner-border spinner-border-sm"></span> กำลังประมวลผล...`)
                        .prop('disabled', true);

                    $.ajax({
                        url: 'create.php',
                        type: 'POST',
                        method: 'POST', // Explicitly set method
                        data: formData,
                        processData: false,
                        contentType: false,
                        cache: false,
                        beforeSend: function(xhr) {
                            // Set additional headers if needed
                            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                        },
                        success: function(response) {
                            console.log('Success response:', response);

                            if (response.success) {
                                const shortUrl = response.short_url;
                                const qrCode = response.qr_code;
                                currentUrlId = response.url_id;

                                $('#shortUrl').val(shortUrl);

                                if (qrCode) {
                                    if (qrCode.startsWith('http') || qrCode.startsWith(
                                            'data:')) {
                                        $('#qrImage').attr('src', qrCode);
                                    } else {
                                        // Handle local file path
                                        $('#qrImage').attr('src', qrCode);
                                    }
                                }

                                $('#resultBox').slideDown();
                                form.reset();

                                // Reset color inputs
                                $('#bg_color').val('#FFFFFF').trigger('change');
                                $('#fg_color').val('#000000').trigger('change');
                                $('#dot_style').val('square');
                                $('.style-preview').removeClass('selected');
                                $('.style-preview[data-style="square"]').addClass('selected');

                                showNotification('สร้างสำเร็จ!', 'success');

                                // Scroll to result
                                $('html, body').animate({
                                    scrollTop: $('#resultBox').offset().top - 100
                                }, 500);
                            } else {
                                showNotification(response.message || 'เกิดข้อผิดพลาด', 'error');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX Error:', {
                                status: status,
                                error: error,
                                responseText: xhr.responseText,
                                statusCode: xhr.status,
                                statusText: xhr.statusText
                            });

                            let errorMessage = 'เกิดข้อผิดพลาด';

                            try {
                                const errorResponse = JSON.parse(xhr.responseText);
                                if (errorResponse.message) {
                                    errorMessage = errorResponse.message;
                                }
                            } catch (e) {
                                if (xhr.responseText) {
                                    console.error('Raw server response:', xhr.responseText);
                                }
                            }

                            showNotification(errorMessage, 'error');
                        },
                        complete: function() {
                            showLoading(false);
                            submitBtn.html(originalText).prop('disabled', false);
                        }
                    });
                });
            });
            // Initialize tooltips
            $(document).ready(function() {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl)
                });

                // Initialize style preview colors
                updateStylePreview();
            });
            // Template configurations
            const qrTemplates = {
                blank: {
                    bgColor: '#FFFFFF',
                    fgColor: '#000000',
                    dotStyle: 'square'
                },
                gradient: {
                    bgColor: '#FFFFFF',
                    fgColor: '#764BA2',
                    dotStyle: 'dots'
                },
                education: {
                    bgColor: '#F0F9FF',
                    fgColor: '#1E3A8A',
                    dotStyle: 'rounded'
                },
                colorful: {
                    bgColor: '#FEF3C7',
                    fgColor: '#F59E0B',
                    dotStyle: 'dots'
                },
                dark: {
                    bgColor: '#1F2937',
                    fgColor: '#FFFFFF',
                    dotStyle: 'square'
                },
                sakura: {
                    bgColor: '#FCE7F3',
                    fgColor: '#EC4899',
                    dotStyle: 'rounded'
                },
                tech: {
                    bgColor: '#111827',
                    fgColor: '#10B981',
                    dotStyle: 'square'
                },
                minimal: {
                    bgColor: '#FFFFFF',
                    fgColor: '#000000',
                    dotStyle: 'square'
                },
                ocean: {
                    bgColor: '#DBEAFE',
                    fgColor: '#0284C7',
                    dotStyle: 'dots'
                },
                frame1: {
                    bgColor: '#FFFFFF',
                    fgColor: '#1E3A8A',
                    dotStyle: 'square'
                },
                neon: {
                    bgColor: '#0F172A',
                    fgColor: '#F0ABFC',
                    dotStyle: 'dots'
                },
                retro: {
                    bgColor: '#FEF3C7',
                    fgColor: '#92400E',
                    dotStyle: 'square'
                },
                nature: {
                    bgColor: '#DCFCE7',
                    fgColor: '#166534',
                    dotStyle: 'rounded'
                },
                elegant: {
                    bgColor: '#FAFAFA',
                    fgColor: '#18181B',
                    dotStyle: 'classy'
                },
                kids: {
                    bgColor: '#FCE7F3',
                    fgColor: '#BE185D',
                    dotStyle: 'dots'
                },
                business: {
                    bgColor: '#F8FAFC',
                    fgColor: '#1E293B',
                    dotStyle: 'square'
                },
                festive: {
                    bgColor: '#FEF2F2',
                    fgColor: '#DC2626',
                    dotStyle: 'rounded'
                }
            };

            // Select template from grid
            function selectTemplate(templateName) {
                const template = qrTemplates[templateName];
                if (template) {
                    // Update color inputs
                    document.getElementById('bg_color').value = template.bgColor;
                    document.getElementById('fg_color').value = template.fgColor;

                    // Update color display
                    document.querySelector('#bg_color').nextElementSibling.value = template.bgColor;
                    document.querySelector('#fg_color').nextElementSibling.value = template.fgColor;

                    // Update dot style
                    document.getElementById('dot_style').value = template.dotStyle;

                    // Update style preview selection
                    document.querySelectorAll('.style-preview').forEach(preview => {
                        preview.classList.remove('selected');
                        if (preview.dataset.style === template.dotStyle) {
                            preview.classList.add('selected');
                        }
                    });

                    // Update template selection visual
                    document.querySelectorAll('.template-preview').forEach(preview => {
                        preview.classList.remove('selected');
                    });
                    event.currentTarget.querySelector('.template-preview').classList.add('selected');

                    // Update style preview colors
                    updateStylePreview();

                    // Show notification
                    showTemplateNotification(templateName);
                }
            }

            // Override the applyTemplate function to ensure modal closes properly
            function applyTemplate(templateName) {
                selectTemplate(templateName);

                // Close modal properly
                const modalElement = document.getElementById('templateModal');
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                } else {
                    // Force close if instance not found
                    modalElement.classList.remove('show');
                    modalElement.style.display = 'none';
                    closeAllModals();
                }
            }
            // Add event listener for modal close button
            document.addEventListener('DOMContentLoaded', function() {
                const closeButtons = document.querySelectorAll('[data-bs-dismiss="modal"]');
                closeButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        closeAllModals();
                    });
                });
                // Add ESC key handler to close modals
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        closeAllModals();
                    }
                });
                // Close modal when clicking outside
                const modals = document.querySelectorAll('.modal');
                modals.forEach(modal => {
                    modal.addEventListener('click', function(e) {
                        if (e.target === this) {
                            closeAllModals();
                        }
                    });
                });
            });

            // Show more templates modal
            function showMoreTemplates() {
                const modal = new bootstrap.Modal(document.getElementById('templateModal'));
                modal.show();
            }

            // Toggle templates view
            function toggleTemplates() {
                const templatesGrid = document.querySelector('.templates-grid');
                const isExpanded = templatesGrid.classList.contains('expanded');

                if (isExpanded) {
                    templatesGrid.classList.remove('expanded');
                    event.target.textContent = translations[currentLang]['templates.viewAll'] || 'ดูทั้งหมด';
                } else {
                    showMoreTemplates();
                }
            }

            // Show template notification
            function showTemplateNotification(templateName) {
                const templateNames = {
                    blank: 'Blank',
                    gradient: 'Gradient',
                    education: 'Education',
                    colorful: 'Colorful',
                    dark: 'Dark Mode',
                    sakura: 'Sakura',
                    tech: 'Tech',
                    minimal: 'Minimal',
                    ocean: 'Ocean',
                    frame1: 'Classic Frame',
                    neon: 'Neon Glow',
                    retro: 'Retro',
                    nature: 'Nature',
                    elegant: 'Elegant',
                    kids: 'Kids',
                    business: 'Business',
                    festive: 'Festive'
                };

                const message = currentLang === 'th' ?
                    `เลือกเทมเพลต "${templateNames[templateName]}" แล้ว` :
                    `Selected template "${templateNames[templateName]}"`;

                showNotification(message, 'success');
            }

            // Add to translations
            const templateTranslations = {
                th: {
                    'templates.title': 'เทมเพลต QR Code สำเร็จรูป',
                    'templates.viewAll': 'ดูทั้งหมด',
                    'templates.more': 'เพิ่มเติม',
                    'templates.allTemplates': 'เทมเพลต QR Code ทั้งหมด'
                },
                en: {
                    'templates.title': 'Pre-made QR Code Templates',
                    'templates.viewAll': 'View All',
                    'templates.more': 'More',
                    'templates.allTemplates': 'All QR Code Templates'
                }
            };

            // Merge with existing translations
            Object.assign(translations.th, templateTranslations.th);
            Object.assign(translations.en, templateTranslations.en);

            // Fix modal backdrop issue on page load
            document.addEventListener('DOMContentLoaded', function() {
                // Remove any existing modal backdrops
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => backdrop.remove());

                // Remove modal-open class from body
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');

                // Hide any open modals
                const modals = document.querySelectorAll('.modal.show');
                modals.forEach(modal => {
                    modal.classList.remove('show');
                    modal.style.display = 'none';
                    modal.setAttribute('aria-hidden', 'true');
                    modal.removeAttribute('aria-modal');
                    modal.removeAttribute('role');
                });
            });
            // Fix modal closing properly
            function closeAllModals() {
                // Close all Bootstrap modals
                const modals = document.querySelectorAll('.modal');
                modals.forEach(modal => {
                    const bsModal = bootstrap.Modal.getInstance(modal);
                    if (bsModal) {
                        bsModal.hide();
                    }
                });

                // Clean up any remaining backdrops
                setTimeout(() => {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => backdrop.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.removeProperty('overflow');
                    document.body.style.removeProperty('padding-right');
                }, 300);
            }
            window.addEventListener('load', function() {
                // Remove any modal backdrops
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());

                // Reset body
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';

                // Ensure all modals are hidden
                document.querySelectorAll('.modal').forEach(modal => {
                    if (modal.classList.contains('show')) {
                        modal.classList.remove('show');
                        modal.style.display = 'none';
                    }
                });
            });

            function removeModalBackdrop() {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => {
                    backdrop.parentNode.removeChild(backdrop);
                });
            }

            // Method 2: ตรวจสอบว่า jQuery พร้อมใช้งานก่อน
            function safeRemoveBackdrop() {
                if (typeof jQuery !== 'undefined' && jQuery('.modal-backdrop').length > 0) {
                    jQuery('.modal-backdrop').remove();
                } else {
                    // Fallback to vanilla JS
                    removeModalBackdrop();
                }
            }

            // Method 3: ใช้ Bootstrap API ที่ถูกต้อง
            function properModalClose() {
                // ปิด modal ทั้งหมดอย่างถูกวิธี
                if (typeof bootstrap !== 'undefined') {
                    document.querySelectorAll('.modal').forEach(modalEl => {
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) {
                            modal.hide();
                        }
                    });
                }

                // รอให้ animation เสร็จก่อนลบ backdrop
                setTimeout(() => {
                    removeModalBackdrop();
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                }, 300);
            }

            // ใช้งานเมื่อ DOM พร้อม
            document.addEventListener('DOMContentLoaded', function() {
                // ลบ backdrop ที่ค้างอยู่
                properModalClose();
            });

            // ใช้งานเมื่อ jQuery พร้อม (ถ้ามี)
            if (typeof jQuery !== 'undefined') {
                jQuery(document).ready(function($) {
                    // ตรวจสอบและลบ backdrop ที่ค้างอยู่
                    if ($('.modal-backdrop').length > 0 && !$('.modal.show').length) {
                        // Safe backdrop removal
                        if (document.querySelectorAll('.modal-backdrop').length > 0) {
                            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                        }
                        $('body').removeClass('modal-open').css({
                            'overflow': '',
                            'padding-right': ''
                        });
                    }
                });
            }
            // Auto-fix modal issues on page load
            document.addEventListener("DOMContentLoaded", function() {
                // Remove all modal backdrops
                document.querySelectorAll(".modal-backdrop").forEach(el => el.remove());

                // Fix body
                document.body.classList.remove("modal-open");
                document.body.style.overflow = "";
                document.body.style.paddingRight = "";

                // Hide all modals
                document.querySelectorAll(".modal.show").forEach(modal => {
                    modal.classList.remove("show");
                    modal.style.display = "none";
                });

                console.log("Modal issues fixed!");
            });
        </script>
        <script>
            // เพิ่มใน translations object
            const fileTranslations = {
                th: {
                    'tab.file': 'แนบไฟล์',
                    'form.uploadFile': 'เลือกไฟล์ที่ต้องการอัพโหลด',
                    'form.fileHint': 'รองรับไฟล์ทุกประเภท ขนาดไม่เกิน 50MB',
                    'form.fileTitle': 'ชื่อไฟล์/คำอธิบาย',
                    'form.downloadLimit': 'จำกัดจำนวนดาวน์โหลด (ไม่บังคับ)',
                    'form.downloadLimitHint': 'ปล่อยว่างหากไม่ต้องการจำกัด',
                    'btn.uploadFile': 'อัพโหลดและสร้างลิงก์',
                    'file.uploading': 'กำลังอัพโหลด...',
                    'file.processing': 'กำลังประมวลผล...',
                    'file.success': 'อัพโหลดไฟล์สำเร็จ!',
                    'file.error': 'เกิดข้อผิดพลาดในการอัพโหลด',
                    'file.sizeTooLarge': 'ไฟล์มีขนาดใหญ่เกินไป (สูงสุด 50MB)',
                    'file.typeNotAllowed': 'ประเภทไฟล์นี้ไม่อนุญาต'
                },
                en: {
                    'tab.file': 'File Upload',
                    'form.uploadFile': 'Select file to upload',
                    'form.fileHint': 'Supports all file types, max 50MB',
                    'form.fileTitle': 'File name/Description',
                    'form.downloadLimit': 'Download limit (optional)',
                    'form.downloadLimitHint': 'Leave empty for unlimited',
                    'btn.uploadFile': 'Upload and Create Link',
                    'file.uploading': 'Uploading...',
                    'file.processing': 'Processing...',
                    'file.success': 'File uploaded successfully!',
                    'file.error': 'Upload error occurred',
                    'file.sizeTooLarge': 'File too large (max 50MB)',
                    'file.typeNotAllowed': 'File type not allowed'
                }
            };

            // Merge with existing translations
            Object.assign(translations.th, fileTranslations.th);
            Object.assign(translations.en, fileTranslations.en);

            // File upload handling
            $(document).ready(function() {
                // Handle file selection
                $('#upload_file').on('change', function() {
                    const file = this.files[0];
                    if (file) {
                        // Check file size (50MB)
                        const maxSize = 50 * 1024 * 1024; // 50MB in bytes
                        if (file.size > maxSize) {
                            showNotification(translations[currentLang]['file.sizeTooLarge'], 'error');
                            this.value = '';
                            return;
                        }

                        // Display file info
                        displayFileInfo(file);

                        // Auto-fill title if empty
                        if (!$('#file_title').val()) {
                            $('#file_title').val(file.name);
                        }
                    }
                });

                // Display file information
                function displayFileInfo(file) {
                    const fileSize = formatFileSize(file.size);
                    const fileType = getFileType(file.name);
                    const fileIcon = getFileIcon(fileType);

                    // You can add a file info display section if needed
                    console.log('File selected:', {
                        name: file.name,
                        size: fileSize,
                        type: fileType
                    });
                }

                // Format file size
                function formatFileSize(bytes) {
                    if (bytes === 0) return '0 Bytes';
                    const k = 1024;
                    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                }

                // Get file type
                function getFileType(filename) {
                    const ext = filename.split('.').pop().toLowerCase();
                    const types = {
                        pdf: ['pdf'],
                        doc: ['doc', 'docx', 'odt', 'rtf'],
                        excel: ['xls', 'xlsx', 'csv'],
                        image: ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'],
                        video: ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv'],
                        zip: ['zip', 'rar', '7z', 'tar', 'gz'],
                        ppt: ['ppt', 'pptx'],
                        audio: ['mp3', 'wav', 'flac', 'aac', 'ogg']
                    };

                    for (const [type, extensions] of Object.entries(types)) {
                        if (extensions.includes(ext)) {
                            return type;
                        }
                    }
                    return 'other';
                }

                // Get file icon
                function getFileIcon(type) {
                    const icons = {
                        pdf: 'bi-file-earmark-pdf',
                        doc: 'bi-file-earmark-word',
                        excel: 'bi-file-earmark-excel',
                        image: 'bi-file-earmark-image',
                        video: 'bi-file-earmark-play',
                        zip: 'bi-file-earmark-zip',
                        ppt: 'bi-file-earmark-ppt',
                        audio: 'bi-file-earmark-music',
                        other: 'bi-file-earmark'
                    };
                    return icons[type] || icons.other;
                }

                // Handle file form submission
                $('#fileForm').on('submit', function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);

                    // Add QR styling data
                    formData.append('bg_color', $('#bg_color').val());
                    formData.append('fg_color', $('#fg_color').val());
                    formData.append('dot_style', $('#dot_style').val());

                    // Add logo if exists
                    const logoInput = $('#logo')[0];
                    if (logoInput && logoInput.files && logoInput.files[0]) {
                        formData.append('logo', logoInput.files[0]);
                    }

                    // Show loading
                    showLoading(true);

                    const submitBtn = $(this).find('button[type="submit"]');
                    const originalText = submitBtn.html();
                    submitBtn.html(
                            `<span class="spinner-border spinner-border-sm"></span> ${translations[currentLang]['file.uploading']}`
                        )
                        .prop('disabled', true);

                    // Upload with progress
                    $.ajax({
                        url: 'create.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        cache: false,
                        xhr: function() {
                            const xhr = new window.XMLHttpRequest();
                            // Upload progress
                            xhr.upload.addEventListener("progress", function(evt) {
                                if (evt.lengthComputable) {
                                    const percentComplete = (evt.loaded / evt.total) *
                                        100;
                                    // Update progress bar if you have one
                                    console.log('Upload progress:', percentComplete +
                                        '%');
                                }
                            }, false);
                            return xhr;
                        },
                        success: function(response) {
                            if (response.success) {
                                const shortUrl = response.short_url;
                                const qrCode = response.qr_code;
                                currentUrlId = response.url_id;

                                $('#shortUrl').val(shortUrl);

                                if (qrCode) {
                                    $('#qrImage').attr('src', qrCode);
                                }

                                $('#resultBox').slideDown();
                                document.getElementById('fileForm').reset();

                                // Reset styling
                                $('#bg_color').val('#FFFFFF').trigger('change');
                                $('#fg_color').val('#000000').trigger('change');

                                showNotification(translations[currentLang]['file.success'],
                                    'success');

                                // Scroll to result
                                $('html, body').animate({
                                    scrollTop: $('#resultBox').offset().top - 100
                                }, 500);
                            } else {
                                showNotification(response.message || translations[currentLang][
                                    'file.error'
                                ], 'error');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Upload error:', error);
                            showNotification(translations[currentLang]['file.error'], 'error');
                        },
                        complete: function() {
                            showLoading(false);
                            submitBtn.html(originalText).prop('disabled', false);
                        }
                    });
                });
            });

            // Optional: Add drag and drop functionality
            $(document).ready(function() {
                const fileInput = $('#upload_file');
                const dropZone = fileInput.closest('.mb-4');

                // Prevent default drag behaviors
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    dropZone[0].addEventListener(eventName, preventDefaults, false);
                    document.body.addEventListener(eventName, preventDefaults, false);
                });

                function preventDefaults(e) {
                    e.preventDefault();
                    e.stopPropagation();
                }

                // Highlight drop zone when item is dragged over it
                ['dragenter', 'dragover'].forEach(eventName => {
                    dropZone[0].addEventListener(eventName, highlight, false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    dropZone[0].addEventListener(eventName, unhighlight, false);
                });

                function highlight(e) {
                    dropZone.addClass('drag-over');
                }

                function unhighlight(e) {
                    dropZone.removeClass('drag-over');
                }

                // Handle dropped files
                dropZone[0].addEventListener('drop', handleDrop, false);

                function handleDrop(e) {
                    const dt = e.dataTransfer;
                    const files = dt.files;

                    if (files.length > 0) {
                        fileInput[0].files = files;
                        fileInput.trigger('change');
                    }
                }
            });
        </script>
</body>

</html>
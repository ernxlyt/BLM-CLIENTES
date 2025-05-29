<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Clientes</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../assets/css/style.css' : 'assets/css/style.css'; ?>">
    <link rel="icon" type="image" href="assets/img/favicon.png">
    
    <style>
        .sidebar {
            display: flex;
            flex-direction: column;
            height: 100vh;
            background-color: #262626 !important; 
            color: white !important;
        }
        
        .nav-menu {
            flex: 1;
        }
        
        .bottom-logo {
            margin-top: auto;
            padding-bottom: 1rem;
        }

        /* Estilos para los elementos de navegación */
        .nav-item {
            color: white !important;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            border-left: 4px solid transparent;
        }

        /* ICONOS EN VERDE */
        .nav-item i {
            color: #23D950 !important; /* ← ICONOS EN VERDE */
            margin-right: 0.75rem;
            font-size: 1.25rem;
            width: 24px;
            text-align: center;
        }

        /* Efectos hover */
        .nav-item:hover,
        .nav-item.active {
            background-color: rgba(35, 217, 80, 0.1) !important; /* ← HOVER CON VERDE SUAVE */
            border-left: 4px solid #23D950 !important; /* ← BORDE VERDE */
        }

        /* Iconos en hover mantienen el verde pero más brillante */
        .nav-item:hover i,
        .nav-item.active i {
            color: #2dd55b !important; /* ← VERDE MÁS BRILLANTE EN HOVER */
        }

        /* Asegurar que el logo container también sea negro */
        .logo-container {
            background-color: transparent !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Estilo para el logo */
        .logo {
            max-width: 150px;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="logo-container">
                <img src="<?php echo strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../assets/img/bigleapcolor1.png' : 'assets/img/bigleapcolor1.png'; ?>" alt="Logo" class="logo">
            </div>
            <nav class="nav-menu">
                <a href="<?php echo strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../index.php' : 'index.php'; ?>" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="<?php echo strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../clients.php' : 'clients.php'; ?>" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Clientes</span>
                </a>
                <a href="<?php echo strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../redes-sociales.php' : 'redes-sociales.php'; ?>" class="nav-item">
                    <i class="fas fa-share-alt"></i>
                    <span>Redes Sociales</span>
                </a>
                <?php if(isAdmin()): ?>
                <a href="<?php echo strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../empresas.php' : 'empresas.php'; ?>" class="nav-item">
                    <i class="fas fa-building"></i>
                    <span>Empresas</span>
                </a>
                <a href="<?php echo strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../planes.php' : 'planes.php'; ?>" class="nav-item">
                    <i class="fas fa-tags"></i>
                    <span>Planes</span>
                </a>
                <a href="<?php echo strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../reports.php' : 'reports.php'; ?>" class="nav-item">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Reportes</span>
                </a>
                <a href="<?php echo strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../users.php' : 'users.php'; ?>" class="nav-item">
                    <i class="fas fa-user-shield"></i>
                    <span>Usuarios</span>
                </a>
                <a href="<?php echo strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../roles.php' : 'roles.php'; ?>" class="nav-item">
                    <i class="fas fa-user-tag"></i>
                    <span>Roles</span>
                </a>
                <?php endif; ?>
            </nav>

            <!-- Imagen pegada al fondo del sidebar -->
            <div class="logo-container bottom-logo">
                <img src="<?php echo strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../assets/img/mariangellogo.png' : 'assets/img/mariangellogo.png'; ?>" alt="Logo" class="logo">
            </div>
        </aside>

        <main class="content">
            <header class="header">
                <button class="mobile-menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title"><?php echo $page_title ?? 'Dashboard'; ?></h1>
                <div class="user-menu">
                    <span><?php echo $_SESSION['user_name'] ?? 'Usuario'; ?></span>
                    <a href="<?php echo strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../logout.php' : 'logout.php'; ?>" class="btn btn-icon btn-secondary ml-2" title="Cerrar Sesión">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </header>

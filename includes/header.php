<header class="bg-white shadow">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center py-4">
            <div>
                <h1 class="text-xl font-bold text-gray-800">CLIENTES - BLM</h1>
            </div>
            <nav>
                <ul class="flex space-x-4">
                    <li><a href="index.php" class="text-gray-600 hover:text-gray-900">Inicio</a></li>
                    <?php if(isAdmin()): ?>
                    <li><a href="users.php" class="text-gray-600 hover:text-gray-900">Usuarios</a></li>
                    <li><a href="roles.php" class="text-gray-600 hover:text-gray-900">Roles</a></li>
                    <li><a href="planes.php" class="text-gray-600 hover:text-gray-900">Planes</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php" class="text-red-600 hover:text-red-900">Cerrar Sesión</a></li>
                </ul>
            </nav>
        </div>
    </div>
</header>

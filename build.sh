#!/bin/bash

# Limpiar paquetes innecesarios antes de la instalación
nix-collect-garbage -d

# Instalar dependencias usando PNPM
pnpm install

# Ejecutar migraciones (si usas una base de datos)
php artisan migrate --force

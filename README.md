IFABAO - Sistema de Galería de Arte
📋 Descripción del Proyecto
IFABAO (Instituto de Formación Artística Bellas Artes Oruro) es una plataforma web desarrollada en PHP para la gestión y comercialización de obras de arte bolivianas. El sistema permite a artistas exhibir y vender sus obras, mientras que los compradores pueden explorar, adquirir y contactar artistas.

🎯 Características Principales
👤 Para Usuarios/Compradores
Galería de Obras: Explorar y filtrar obras de arte

Perfiles de Artistas: Conocer a los artistas y su trayectoria

Carrito de Compras: Gestión de obras seleccionadas

Sistema de Checkout: Proceso de compra seguro

Búsqueda Avanzada: Filtros por categoría, técnica y artista

🎨 Para Artistas
Dashboard Personalizado: Gestión de obras y perfil

Publicación de Obras: Subir y gestionar catálogo personal

Estadísticas: Seguimiento de ventas y vistas

Perfil Público: Mostrar biografía y trayectoria

⚙️ Para Administradores
Panel de Control: Gestión completa del sistema

Moderación de Obras: Aprobación y gestión de publicaciones

Estadísticas Globales: Métricas de ventas y usuarios

Gestión de Artistas: Administración de cuentas

🛠️ Tecnologías Utilizadas
Backend
PHP 7.4+ - Lenguaje de programación

MySQL - Base de datos

PDO - Conexión segura a base de datos

Sesiones PHP - Manejo de autenticación

Frontend
HTML5 - Estructura semántica

CSS3 - Estilos y diseño responsive

JavaScript - Interactividad del cliente

Font Awesome - Iconografía

Características de Seguridad
Tokens CSRF - Protección contra ataques

Sanitización de datos - Prevención de inyecciones

Validación de archivos - Subida segura de imágenes

Autenticación segura - Manejo de contraseñas

📁 Estructura del Proyecto
text
ifabao/
├── includes/
│   ├── config.php          # Configuración principal
│   ├── database.php        # Clase de base de datos
│   ├── header.php          # Cabecera del sitio
│   └── footer.php          # Pie de página
├── css/
│   ├── style.css           # Estilos principales
│   ├── premium.css         # Estilos avanzados
│   └── responsive.css      # Media queries
├── imagenes/               # Assets e imágenes
├── uploads/                # Archivos subidos
│   ├── obras/              # Imágenes de obras
│   └── artistas/           # Imágenes de perfil
└── Archivos principales:
    ├── index.php           # Página de inicio
    ├── galeria.php         # Galería de obras
    ├── artistas.php        # Lista de artistas
    ├── artista_perfil.php  # Perfil de artista
    ├── obra.php            # Detalle de obra
    ├── carrito.php         # Gestión de carrito
    ├── checkout.php        # Proceso de pago
    ├── login.php           # Inicio de sesión
    ├── register.php        # Registro de usuarios
    ├── dashboard.php       # Panel de artista
    └── admin_obras.php     # Panel de administración
🚀 Instalación y Configuración
Requisitos del Servidor
PHP 7.4 o superior

MySQL 5.7 o superior

Extensiones PHP: PDO, MySQLi, GD Library

Servidor web (Apache/Nginx)

Pasos de Instalación
Clonar/Descargar el proyecto

bash
git clone [url-del-repositorio]
Configurar base de datos

Crear base de datos ifabao_db

Importar el esquema SQL (si está disponible)

Configurar credenciales en includes/config.php

Configurar permisos

bash
chmod 755 uploads/
chmod 755 uploads/obras/
chmod 755 uploads/artistas/
Configurar archivo de configuración

Editar includes/config.php con datos de conexión

Configurar constantes según entorno (desarrollo/producción)

Acceder al sistema

Navegar a la URL del proyecto

Registrar primer usuario administrador

👥 Tipos de Usuario
1. Administrador
Acceso: Panel de administración completo

Funciones: Gestión de obras, artistas, estadísticas

Credenciales demo: admin@ifabao.com / password

2. Artista
Acceso: Dashboard personal, publicación de obras

Funciones: Gestionar perfil, publicar obras, ver estadísticas

Credenciales demo: artista@ifabao.com / password

3. Comprador
Acceso: Navegación, compras, perfil básico

Funciones: Explorar galería, comprar obras, contactar artistas

Credenciales demo: comprador@ifabao.com / password

💰 Sistema de Compras
Proceso de Compra
Explorar galería de obras

Agregar obras al carrito

Proceder al checkout

Completar información de envío

Seleccionar método de pago (QR/Transferencia)

Confirmar pedido

Métodos de Pago
QR Bolivia - Pago mediante código QR

Transferencia Bancaria - Transferencia tradicional

Políticas
Envío gratuito para compras mayores a Bs. 2,000

Comisión del 15% por venta para la plataforma

Garantía de autenticidad de obras

# 📸 Proyecto final de CFGS DAW - Plataforma de Rallies Fotográficos

Este proyecto corresponde al trabajo final del Ciclo Formativo de Grado Superior en Desarrollo de Aplicaciones Web (CFGS DAW). Se trata de una plataforma web para la organización y participación en **rallies fotográficos online**.

## 📝 Descripción

La plataforma permite a un **administrador** crear y gestionar rallies fotográficos. Cualquier **usuario registrado** puede inscribirse en los rallies activos y subir sus fotografías respetando los requisitos específicos establecidos por el administrador para cada evento.

Una vez subidas, las imágenes deben ser **revisadas y aprobadas** por el administrador antes de ser visibles públicamente. A partir de ahí, cualquier visitante de la web puede **votar las fotos** que más le gusten.

El sistema incluye una sección destacada con las **fotografías más votadas** de cada rally.

### Funcionalidades principales

- Registro e inicio de sesión de usuarios
- Inscripción en rallies abiertos
- Subida de fotografías por parte de los usuarios
- Moderación de imágenes por el administrador
- Votación pública de las imágenes aprobadas
- Ranking con las fotos más votadas
- Gestión completa de rallies y usuarios (crear, editar, eliminar)
- Publicación de imágenes en el servidor: las fotos subidas por los usuarios se almacenan directamente en el servidor.
- Eliminación automática de archivos relacionados: al eliminar una imagen, usuario o rally, las fotografías asociadas también se eliminan del servidor, garantizando una gestión limpia del almacenamiento.

## 🌐 Demo en producción

Puedes acceder a la versión en producción del proyecto desde el siguiente enlace:

🔗 [https://proyectorally.byethost7.com/](https://proyectorally.byethost7.com/)

## ⚙️ Tecnologías utilizadas

- PHP
- HTML5
- CSS3
- JavaScript
- Bootstrap
- Google Charts

## 🚀 Instalación y uso

### 1. Clonar el repositorio

```bash
git clone https://github.com/elasalc041/proyecto-final-DAW
cd proyecto-final-DAW
```

### 2. Configurar base de datos

En el directorio `utiles` se encuentran dos elementos clave:

- `rallies_fotos.sql`: contiene la estructura y datos iniciales de la base de datos. Debes importarlo en tu servidor de base de datos.
- `variables.php`: archivo de configuración con los datos de conexión. Modifica las siguientes variables según tu entorno:

```php
$host = "localhost";
$user = "root";
$passwordBD = "";
$bbdd = "rallies_fotos";
```

### 3. Alojar el proyecto

Puedes alojar el proyecto en un entorno local como **XAMPP** (usado en el desarrollo), o en un servidor de producción como el de **ByetHost** (u otro de tu elección). Asegúrate de:

- Tener un servidor con soporte para PHP y MySQL

- Subir los archivos del proyecto al servidor web

- Importar la base de datos

- Configurar `variables.php` con los datos reales de tu servidor

### 4. Acceso como administrador

Una vez desplegado, podrás acceder como administrador con las siguientes credenciales:

- **Usuario**: `adminer@google.es`

- **Contraseña**: `githubAdmin$`
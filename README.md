# Sistema POS – Farmacia Full Heart

Sistema de punto de venta (POS) para gestionar las ventas, el inventario, los clientes y los créditos de la **Farmacia Full Heart**. Lo desarrollamos para el curso **Herramientas de Desarrollo** (UTP – Chiclayo, 2026), usando Git y GitHub para el control de versiones.

## Integrantes

| Integrante | Código | Rama |
|---|---|---|
| Gabriela Alexandra Taboada Mimbela | U23271730 | `alexandra` |
| José Bryan Gonzales Rivera | U23243718 | `jose` / `Rama_Bryan` |
| Renato Carlos M. Chozo Montalván | U23271991 | `renato` |
| Josué Francisco Túllume Salazar | U23322776 | `josue` |

**Docente:** Mg. Ing. Janett A. Gonzales Flores · **Sección:** 26347

## Tecnologías

- **Backend:** PHP 8 (patrón MVC, PDO)
- **Base de datos:** MySQL / MariaDB
- **Frontend:** HTML, CSS y JavaScript
- **Servidor local:** XAMPP (Apache + MariaDB)
- **Control de versiones:** Git y GitHub

## Módulos

| Módulo | Qué hace |
|---|---|
| **Login** | Inicio y cierre de sesión con control de acceso por perfil |
| **Dashboard** | Resumen general del sistema |
| **Usuarios** | Registrar y editar usuarios, y activarlos o desactivarlos |
| **Perfiles** | Gestionar perfiles (Administrador, Vendedor) y sus permisos de menú |
| **Categorías** | Registrar, editar y cambiar el estado de las categorías de productos |
| **Productos** | Registrar productos con precio, stock, stock de seguridad, código de barras e imagen |
| **Clientes** | Registrar clientes con DNI o RUC |
| **Ventas** | Registrar ventas con boleta (B001) o factura (F001) al contado o a crédito desde S/ 100, con 1 a 3 cuotas mensuales |
| **Inventario** | Registrar movimientos de entrada y salida de stock |
| **Top productos** | Ver los productos más vendidos |
| **Créditos** | Consultar el cronograma de cuotas, registrar pagos (efectivo u otros métodos), anular pagos, dar prórrogas y emitir constancias |

## Estructura del proyecto

```
Farmacia_POS/
├── index.php                # Punto de entrada y enrutador (?route=...)
├── config/
│   └── database.php         # Conexión a la base de datos (PDO)
├── app/
│   ├── Controllers/         # Lógica de cada módulo
│   ├── Models/              # Consultas a la base de datos
│   └── Views/               # Pantallas (layout, login, módulos)
├── database/
│   ├── farmacia_pos.sql     # Script completo de la base de datos
│   └── migraciones/         # Cambios para bases de datos ya existentes
├── public/
│   ├── css/  js/  img/
│   └── uploads/productos/   # Imágenes subidas de productos
└── storage/sessions/        # Sesiones de PHP
```

## Instalación

1. **Instalar XAMPP** e iniciar **Apache** y **MySQL** desde el panel de control.

2. **Clonar el repositorio** dentro de `htdocs`:
   ```bash
   cd C:/xampp/htdocs
   git clone https://github.com/tarrrron/Farmacia_POS.git
   ```

3. **Crear la base de datos.** Abrir `http://localhost/phpmyadmin`, ir a **Importar** y cargar `database/farmacia_pos.sql`. Este script crea la base `farmacia_pos` con todas las tablas y los datos iniciales.

   > ⚠️ El script **borra la base `farmacia_pos` si ya existe**. Si ya tienes datos y solo quieres actualizar la base, ejecuta en orden los archivos de `database/migraciones/` (`001_creditos.sql`, `002_menu_creditos.sql`, `003_mejoras.sql`, `004_credito_cuotas.sql`, `005_monto_recibido_vuelto.sql`, `006_nota_venta.sql`). Las migraciones conservan los créditos existentes como una cuota única, asignan el total como monto recibido a las ventas al contado anteriores y agregan el comprobante Nota de venta.

4. **Revisar la conexión** en `config/database.php`. Por defecto usa `localhost`, el usuario `root` y la contraseña vacía, que es la configuración estándar de XAMPP.

5. **Configurar la consulta DNI/RUC de MiAPI Cloud (opcional).** Copia `config/miapi.local.example.php` como `config/miapi.local.php` y agrega una clave vigente de MiAPI Cloud en `token`. Este archivo local está excluido de Git. También puedes definir la variable de entorno `MIAPI_CLOUD_TOKEN`. La extensión cURL de PHP debe estar habilitada en XAMPP. Sin una clave, la venta funciona con clientes ya registrados y el formulario muestra cómo completar la configuración al intentar consultar.

   Para personalizar los tickets, copia `config/receipt.local.example.php` como `config/receipt.local.php` y completa la razón social, RUC y dirección del negocio. Desde el detalle de una venta, **Imprimir ticket / PDF** abre el formato de 80 mm; en el diálogo de impresión puedes elegir una impresora o **Guardar como PDF**.

6. **Abrir el sistema** en el navegador:
   ```
   http://localhost/Farmacia_POS/
   ```

## Acceso inicial

| Usuario | Contraseña | Perfil |
|---|---|---|
| `admin` | `1234` | Administrador |

> Se recomienda cambiar la contraseña después del primer ingreso.

**Permisos por perfil:**
- **Administrador:** acceso a todos los módulos.
- **Vendedor:** Productos, Clientes, Ventas, Inventario y Créditos.

## Seguridad

- Las contraseñas se guardan con `password_hash` (bcrypt). Las claves antiguas en SHA1 se convierten automáticamente la próxima vez que el usuario inicia sesión.
- El identificador de sesión se regenera al iniciar sesión.
- El cierre de sesión está protegido con un token CSRF.
- Si se desactiva a un usuario, su sesión se cierra automáticamente.
- Las consultas usan sentencias preparadas (PDO).

## Flujo de trabajo con Git

Cada integrante trabaja en su propia rama, y los cambios se integran a `main` cuando están revisados.

```bash
# Actualizar main
git switch main
git pull origin main

# Trabajar en la rama propia
git switch nombre-de-tu-rama
git merge main                      # traer los últimos cambios
git add .
git commit -m "feat: descripción del cambio"
git push origin nombre-de-tu-rama
```

**Ramas del repositorio:** `main`, `alexandra`, `jose`, `Rama_Bryan`, `josue`, `renato`.

**Convención para los mensajes de commit:**
- `feat:` nueva funcionalidad
- `fix:` corrección de un error
- `docs:` cambios en la documentación
- `(db)` cambios en la base de datos

## Repositorio

https://github.com/tarrrron/Farmacia_POS

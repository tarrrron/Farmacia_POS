-- Base de datos inicial para Farmacia POS
-- Avance 1: login, conexion, dashboard y estructura base

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

DROP DATABASE IF EXISTS `farmacia_pos`;
CREATE DATABASE `farmacia_pos` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `farmacia_pos`;

START TRANSACTION;

CREATE TABLE `perfil` (
  `idperfil` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idperfil`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `opcion` (
  `idopcion` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(100) NOT NULL,
  `url` varchar(200) NOT NULL,
  `icono` varchar(50) NOT NULL DEFAULT 'fa-circle',
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idopcion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `acceso` (
  `idperfil` int(11) NOT NULL,
  `idopcion` int(11) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idperfil`, `idopcion`),
  KEY `idx_acceso_opcion` (`idopcion`),
  CONSTRAINT `fk_acceso_perfil` FOREIGN KEY (`idperfil`) REFERENCES `perfil` (`idperfil`),
  CONSTRAINT `fk_acceso_opcion` FOREIGN KEY (`idopcion`) REFERENCES `opcion` (`idopcion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tipodocumento` (
  `idtipodocumento` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idtipodocumento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cliente` (
  `idcliente` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `nombre_comercial` varchar(150) DEFAULT NULL,
  `razon_social` varchar(150) DEFAULT NULL,
  `idtipodocumento` int(11) NOT NULL,
  `nrodocumento` varchar(20) NOT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `departamento` varchar(80) DEFAULT NULL,
  `provincia` varchar(80) DEFAULT NULL,
  `distrito` varchar(80) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idcliente`),
  KEY `idx_cliente_tipodocumento` (`idtipodocumento`),
  CONSTRAINT `fk_cliente_tipodocumento` FOREIGN KEY (`idtipodocumento`) REFERENCES `tipodocumento` (`idtipodocumento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `categoria` (
  `idcategoria` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idcategoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `unidad` (
  `idunidad` int(11) NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(50) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idunidad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `afectacion` (
  `idafectacion` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(4) NOT NULL,
  `descripcion` varchar(100) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idafectacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `producto` (
  `idproducto` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `codigobarra` varchar(50) DEFAULT NULL,
  `pventa` decimal(11,2) NOT NULL DEFAULT 0.00,
  `pcompra` decimal(11,2) NOT NULL DEFAULT 0.00,
  `stock` decimal(11,2) NOT NULL DEFAULT 0.00,
  `stockseguridad` decimal(11,2) NOT NULL DEFAULT 0.00,
  `idunidad` int(11) NOT NULL,
  `idcategoria` int(11) NOT NULL,
  `idafectacion` int(11) NOT NULL DEFAULT 1,
  `urlimagen` varchar(250) DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idproducto`),
  KEY `idx_producto_unidad` (`idunidad`),
  KEY `idx_producto_categoria` (`idcategoria`),
  KEY `idx_producto_afectacion` (`idafectacion`),
  CONSTRAINT `fk_producto_unidad` FOREIGN KEY (`idunidad`) REFERENCES `unidad` (`idunidad`),
  CONSTRAINT `fk_producto_categoria` FOREIGN KEY (`idcategoria`) REFERENCES `categoria` (`idcategoria`),
  CONSTRAINT `fk_producto_afectacion` FOREIGN KEY (`idafectacion`) REFERENCES `afectacion` (`idafectacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tipocomprobante` (
  `idtipocomprobante` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idtipocomprobante`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `moneda` (
  `idmoneda` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `simbolo` varchar(10) NOT NULL,
  `tipo_cambio` decimal(11,4) DEFAULT 1.0000,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idmoneda`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usuario` (
  `idusuario` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `clave` char(40) NOT NULL,
  `idperfil` int(11) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idusuario`),
  UNIQUE KEY `uk_usuario` (`usuario`),
  KEY `idx_usuario_perfil` (`idperfil`),
  CONSTRAINT `fk_usuario_perfil` FOREIGN KEY (`idperfil`) REFERENCES `perfil` (`idperfil`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `serie` (
  `idserie` int(11) NOT NULL AUTO_INCREMENT,
  `idtipocomprobante` int(11) NOT NULL,
  `serie` varchar(5) NOT NULL,
  `correlativo` int(11) NOT NULL DEFAULT 0,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idserie`),
  KEY `idx_serie_tipocomprobante` (`idtipocomprobante`),
  CONSTRAINT `fk_serie_tipocomprobante` FOREIGN KEY (`idtipocomprobante`) REFERENCES `tipocomprobante` (`idtipocomprobante`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `venta` (
  `idventa` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` datetime NOT NULL,
  `idcliente` int(11) DEFAULT NULL,
  `idtipocomprobante` int(11) NOT NULL,
  `serie` varchar(5) DEFAULT NULL,
  `correlativo` int(11) DEFAULT NULL,
  `total` decimal(11,2) NOT NULL DEFAULT 0.00,
  `idusuario` int(11) NOT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `total_gravado` decimal(11,2) NOT NULL DEFAULT 0.00,
  `total_exonerado` decimal(11,2) NOT NULL DEFAULT 0.00,
  `total_inafecto` decimal(11,2) NOT NULL DEFAULT 0.00,
  `total_igv` decimal(11,2) NOT NULL DEFAULT 0.00,
  `total_icbper` decimal(11,2) NOT NULL DEFAULT 0.00,
  `total_descuento` decimal(11,2) NOT NULL DEFAULT 0.00,
  `formapago` char(1) DEFAULT 'C',
  `idmoneda` int(11) NOT NULL DEFAULT 1,
  `vencimiento` date DEFAULT NULL,
  `guiaremision` varchar(50) DEFAULT NULL,
  `ordencompra` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`idventa`),
  KEY `idx_venta_cliente` (`idcliente`),
  KEY `idx_venta_tipocomprobante` (`idtipocomprobante`),
  KEY `idx_venta_usuario` (`idusuario`),
  KEY `idx_venta_moneda` (`idmoneda`),
  CONSTRAINT `fk_venta_cliente` FOREIGN KEY (`idcliente`) REFERENCES `cliente` (`idcliente`),
  CONSTRAINT `fk_venta_tipocomprobante` FOREIGN KEY (`idtipocomprobante`) REFERENCES `tipocomprobante` (`idtipocomprobante`),
  CONSTRAINT `fk_venta_usuario` FOREIGN KEY (`idusuario`) REFERENCES `usuario` (`idusuario`),
  CONSTRAINT `fk_venta_moneda` FOREIGN KEY (`idmoneda`) REFERENCES `moneda` (`idmoneda`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `detalle_venta` (
  `iddetalle` int(11) NOT NULL AUTO_INCREMENT,
  `idventa` int(11) NOT NULL,
  `idproducto` int(11) NOT NULL,
  `cantidad` decimal(11,2) NOT NULL,
  `unidad` varchar(20) DEFAULT NULL,
  `pventa` decimal(11,2) NOT NULL,
  `igv` decimal(11,2) DEFAULT 0.00,
  `icbper` decimal(11,2) DEFAULT 0.00,
  `descuento` decimal(11,2) DEFAULT 0.00,
  `total` decimal(11,2) NOT NULL,
  `idafectacion` int(11) NOT NULL DEFAULT 1,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`iddetalle`),
  KEY `idx_detalle_venta` (`idventa`),
  KEY `idx_detalle_producto` (`idproducto`),
  CONSTRAINT `fk_detalle_venta` FOREIGN KEY (`idventa`) REFERENCES `venta` (`idventa`),
  CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`idproducto`) REFERENCES `producto` (`idproducto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `perfil` (`idperfil`, `nombre`, `estado`) VALUES
(1, 'ADMINISTRADOR', 1),
(2, 'VENDEDOR', 1);

INSERT INTO `opcion` (`idopcion`, `descripcion`, `url`, `icono`, `estado`) VALUES
(1, 'Usuarios', 'vista/usuarios.php', 'fa-users', 1),
(2, 'Perfiles', 'vista/perfiles.php', 'fa-id-badge', 1),
(3, 'Productos', 'vista/productos.php', 'fa-shopping-basket', 1),
(4, 'Categorias', 'vista/categorias.php', 'fa-tags', 1),
(5, 'Clientes', 'vista/clientes.php', 'fa-address-book', 1),
(6, 'Ventas', 'vista/ventas.php', 'fa-shopping-cart', 1),
(7, 'Inventario', 'vista/inventario.php', 'mdi mdi-warehouse', 1),
(8, 'Top productos', 'vista/reportes_top_productos.php', 'mdi mdi-chart-bar', 1);

INSERT INTO `acceso` (`idperfil`, `idopcion`, `estado`) VALUES
(1, 1, 1),
(1, 2, 1),
(1, 3, 1),
(1, 4, 1),
(1, 5, 1),
(1, 6, 1),
(1, 7, 1),
(1, 8, 1),
(2, 3, 1),
(2, 5, 1),
(2, 6, 1),
(2, 7, 1);

INSERT INTO `tipodocumento` (`idtipodocumento`, `nombre`, `estado`) VALUES
(1, 'DNI', 1),
(2, 'RUC', 1);

INSERT INTO `categoria` (`idcategoria`, `nombre`, `estado`) VALUES
(1, 'Analgesicos', 1),
(2, 'Antibioticos', 1),
(3, 'Antigripales', 1),
(4, 'Vitaminas', 1),
(5, 'Cuidado personal', 1),
(6, 'Dispositivos medicos', 1);

INSERT INTO `unidad` (`idunidad`, `descripcion`, `estado`) VALUES
(1, 'UNIDAD', 1),
(2, 'CAJA', 1);

INSERT INTO `afectacion` (`idafectacion`, `codigo`, `descripcion`, `estado`) VALUES
(1, '10', 'Gravado - Operacion Onerosa', 1),
(2, '20', 'Exonerado - Operacion Onerosa', 1),
(3, '30', 'Inafecto - Operacion Onerosa', 1);

INSERT INTO `producto` (`idproducto`, `nombre`, `codigobarra`, `pventa`, `pcompra`, `stock`, `stockseguridad`, `idunidad`, `idcategoria`, `idafectacion`, `urlimagen`, `estado`) VALUES
(1, 'Paracetamol 500 mg', '775000000001', 2.00, 1.00, 30.00, 10.00, 1, 1, 1, NULL, 1),
(2, 'Ibuprofeno 400 mg', '775000000002', 2.50, 1.80, 25.00, 10.00, 1, 1, 1, NULL, 1),
(3, 'Naproxeno 550 mg', '775000000003', 3.80, 2.20, 20.00, 8.00, 1, 1, 1, NULL, 1),
(4, 'Cefalexina 500 mg', '775000000004', 8.00, 7.10, 18.00, 6.00, 1, 2, 1, NULL, 1),
(5, 'Vitamina C 1 g', '775000000005', 1.50, 0.90, 40.00, 12.00, 1, 4, 1, NULL, 1),
(6, 'Termometro digital', '775000000006', 18.00, 15.00, 8.00, 3.00, 1, 6, 1, NULL, 1);

INSERT INTO `tipocomprobante` (`idtipocomprobante`, `nombre`, `estado`) VALUES
(1, 'BOLETA', 1),
(2, 'FACTURA', 1);

INSERT INTO `moneda` (`idmoneda`, `nombre`, `simbolo`, `tipo_cambio`, `estado`) VALUES
(1, 'SOLES', 'S/', 1.0000, 1),
(2, 'DOLARES', '$', 3.8000, 1);

INSERT INTO `usuario` (`idusuario`, `nombre`, `usuario`, `clave`, `idperfil`, `estado`) VALUES
(1, 'Administrador', 'admin', SHA1('1234'), 1, 1);

INSERT INTO `serie` (`idserie`, `idtipocomprobante`, `serie`, `correlativo`, `estado`) VALUES
(1, 1, 'B001', 0, 1),
(2, 2, 'F001', 0, 1);

COMMIT;

-- Migracion: modulo de creditos
-- Ejecutar sobre una base farmacia_pos existente (no borra datos).

USE `farmacia_pos`;

CREATE TABLE IF NOT EXISTS `credito` (
  `idcredito` int(11) NOT NULL AUTO_INCREMENT,
  `idventa` int(11) NOT NULL,
  `idcliente` int(11) NOT NULL,
  `monto_total` decimal(11,2) NOT NULL,
  `saldo` decimal(11,2) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_vencimiento` date DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = pendiente, 2 = pagado, 0 = anulado',
  PRIMARY KEY (`idcredito`),
  UNIQUE KEY `uk_credito_venta` (`idventa`),
  KEY `idx_credito_cliente` (`idcliente`),
  KEY `idx_credito_estado` (`estado`),
  CONSTRAINT `fk_credito_venta` FOREIGN KEY (`idventa`) REFERENCES `venta` (`idventa`),
  CONSTRAINT `fk_credito_cliente` FOREIGN KEY (`idcliente`) REFERENCES `cliente` (`idcliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pago_credito` (
  `idpago` int(11) NOT NULL AUTO_INCREMENT,
  `idcredito` int(11) NOT NULL,
  `idusuario` int(11) NOT NULL,
  `monto` decimal(11,2) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `observacion` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`idpago`),
  KEY `idx_pago_credito` (`idcredito`),
  KEY `idx_pago_usuario` (`idusuario`),
  CONSTRAINT `fk_pago_credito` FOREIGN KEY (`idcredito`) REFERENCES `credito` (`idcredito`),
  CONSTRAINT `fk_pago_usuario` FOREIGN KEY (`idusuario`) REFERENCES `usuario` (`idusuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

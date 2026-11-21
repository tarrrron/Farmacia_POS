-- Cronograma mensual de cuotas para nuevas ventas a crédito.
-- Seguro de ejecutar más de una vez: conserva y completa créditos existentes.

USE `farmacia_pos`;

ALTER TABLE `credito`
  ADD COLUMN IF NOT EXISTS `numero_cuotas` tinyint(3) unsigned NOT NULL DEFAULT 1 AFTER `fecha_vencimiento`;

CREATE TABLE IF NOT EXISTS `credito_cuota` (
  `idcuota` int(11) NOT NULL AUTO_INCREMENT,
  `idcredito` int(11) NOT NULL,
  `numero_cuota` tinyint(3) unsigned NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `monto` decimal(11,2) NOT NULL,
  `saldo` decimal(11,2) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = pendiente, 2 = pagada, 0 = anulada',
  PRIMARY KEY (`idcuota`),
  UNIQUE KEY `uk_credito_cuota_numero` (`idcredito`, `numero_cuota`),
  KEY `idx_credito_cuota_vencimiento` (`fecha_vencimiento`, `estado`),
  CONSTRAINT `fk_credito_cuota_credito` FOREIGN KEY (`idcredito`) REFERENCES `credito` (`idcredito`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pago_credito_cuota` (
  `idpago_cuota` int(11) NOT NULL AUTO_INCREMENT,
  `idpago` int(11) NOT NULL,
  `idcuota` int(11) NOT NULL,
  `monto` decimal(11,2) NOT NULL,
  PRIMARY KEY (`idpago_cuota`),
  UNIQUE KEY `uk_pago_credito_cuota` (`idpago`, `idcuota`),
  KEY `idx_pago_cuota_cuota` (`idcuota`),
  CONSTRAINT `fk_pago_credito_cuota_pago` FOREIGN KEY (`idpago`) REFERENCES `pago_credito` (`idpago`),
  CONSTRAINT `fk_pago_credito_cuota_cuota` FOREIGN KEY (`idcuota`) REFERENCES `credito_cuota` (`idcuota`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Los créditos antiguos permanecen en un solo vencimiento/cuota.
INSERT INTO `credito_cuota` (`idcredito`, `numero_cuota`, `fecha_vencimiento`, `monto`, `saldo`, `estado`)
SELECT c.`idcredito`, 1, COALESCE(c.`fecha_vencimiento`, DATE(c.`fecha`)), c.`monto_total`, c.`saldo`, c.`estado`
FROM `credito` c
WHERE NOT EXISTS (
  SELECT 1 FROM `credito_cuota` q WHERE q.`idcredito` = c.`idcredito`
);

-- Conserva el vínculo de los pagos históricos con la cuota única de su crédito.
INSERT INTO `pago_credito_cuota` (`idpago`, `idcuota`, `monto`)
SELECT p.`idpago`, q.`idcuota`, p.`monto`
FROM `pago_credito` p
INNER JOIN `credito_cuota` q ON q.`idcredito` = p.`idcredito` AND q.`numero_cuota` = 1
WHERE p.`estado` = 1
  AND NOT EXISTS (
    SELECT 1 FROM `pago_credito_cuota` a WHERE a.`idpago` = p.`idpago` AND a.`idcuota` = q.`idcuota`
  );

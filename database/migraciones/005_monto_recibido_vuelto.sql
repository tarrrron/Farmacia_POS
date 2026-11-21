ALTER TABLE `venta`
  ADD COLUMN `monto_recibido` decimal(11,2) NOT NULL DEFAULT 0.00 AFTER `total`,
  ADD COLUMN `vuelto` decimal(11,2) NOT NULL DEFAULT 0.00 AFTER `monto_recibido`;

UPDATE `venta`
SET `monto_recibido` = CASE WHEN `formapago` = 'C' THEN `total` ELSE 0.00 END,
    `vuelto` = 0.00;

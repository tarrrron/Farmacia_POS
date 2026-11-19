-- Migracion: mejoras de seguridad y del modulo de creditos
-- Ejecutar sobre una base farmacia_pos existente (no borra datos). Requiere MariaDB (XAMPP).

USE `farmacia_pos`;

-- Contrasenas con password_hash (bcrypt). Las claves SHA1 existentes siguen funcionando
-- y se convierten automaticamente la proxima vez que cada usuario inicia sesion.
ALTER TABLE `usuario` MODIFY `clave` varchar(255) NOT NULL;

-- Metodo de pago y anulacion de pagos de credito
ALTER TABLE `pago_credito`
  ADD COLUMN IF NOT EXISTS `metodo_pago` varchar(20) NOT NULL DEFAULT 'EFECTIVO' AFTER `monto`,
  ADD COLUMN IF NOT EXISTS `nro_operacion` varchar(50) DEFAULT NULL AFTER `metodo_pago`,
  ADD COLUMN IF NOT EXISTS `estado` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = valido, 0 = anulado',
  ADD COLUMN IF NOT EXISTS `motivo_anulacion` varchar(150) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `fecha_anulacion` datetime DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `idusuario_anula` int(11) DEFAULT NULL;

-- Historial de prorrogas de vencimiento
CREATE TABLE IF NOT EXISTS `credito_prorroga` (
  `idprorroga` int(11) NOT NULL AUTO_INCREMENT,
  `idcredito` int(11) NOT NULL,
  `idusuario` int(11) NOT NULL,
  `fecha_anterior` date DEFAULT NULL,
  `fecha_nueva` date NOT NULL,
  `motivo` varchar(150) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idprorroga`),
  KEY `idx_prorroga_credito` (`idcredito`),
  CONSTRAINT `fk_prorroga_credito` FOREIGN KEY (`idcredito`) REFERENCES `credito` (`idcredito`),
  CONSTRAINT `fk_prorroga_usuario` FOREIGN KEY (`idusuario`) REFERENCES `usuario` (`idusuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

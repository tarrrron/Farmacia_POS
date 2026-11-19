-- Migracion: opcion de menu Creditos y permisos
-- Ejecutar sobre una base farmacia_pos existente (no borra datos).

USE `farmacia_pos`;

INSERT IGNORE INTO `opcion` (`idopcion`, `descripcion`, `url`, `icono`, `estado`) VALUES
(9, 'Creditos', 'module/creditos', 'fa-credit-card', 1);

INSERT INTO `acceso` (`idperfil`, `idopcion`, `estado`)
SELECT p.idperfil, 9, 1 FROM perfil p
WHERE p.idperfil IN (1, 2)
  AND NOT EXISTS (SELECT 1 FROM acceso a WHERE a.idperfil = p.idperfil AND a.idopcion = 9);

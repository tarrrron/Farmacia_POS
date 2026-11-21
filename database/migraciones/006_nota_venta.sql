UPDATE `tipocomprobante`
SET `estado` = 1
WHERE UPPER(TRIM(`nombre`)) = 'NOTA DE VENTA';

INSERT INTO `tipocomprobante` (`nombre`, `estado`)
SELECT 'NOTA DE VENTA', 1
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `tipocomprobante`
    WHERE UPPER(TRIM(`nombre`)) = 'NOTA DE VENTA'
);

SET @nota_venta_id = (
    SELECT `idtipocomprobante` FROM `tipocomprobante`
    WHERE UPPER(TRIM(`nombre`)) = 'NOTA DE VENTA'
    ORDER BY `idtipocomprobante` LIMIT 1
);

UPDATE `serie`
SET `estado` = 1
WHERE `idtipocomprobante` = @nota_venta_id AND `serie` = 'NV01';

INSERT INTO `serie` (`idtipocomprobante`, `serie`, `correlativo`, `estado`)
SELECT @nota_venta_id, 'NV01', 0, 1
FROM DUAL
WHERE @nota_venta_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `serie`
      WHERE `idtipocomprobante` = @nota_venta_id AND `serie` = 'NV01'
  );

<?php
$escape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
$invoiceNumber = ($sale["serie"] ?? "") . "-" . str_pad((string) ($sale["correlativo"] ?? ""), 8, "0", STR_PAD_LEFT);
$isCredit = ($sale["formapago"] ?? "C") === "R";
$logoPath = realpath(__DIR__ . "/../../../public/img/farmacia-logo.png");
$logoData = $logoPath && is_file($logoPath) ? "data:image/png;base64," . base64_encode((string) file_get_contents($logoPath)) : "";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ticket <?= $escape($invoiceNumber) ?> | <?= $escape($receiptConfig["business_name"] ?? "Farmacia") ?></title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef2f0; color: #17231f; font: 13px/1.4 Arial, Helvetica, sans-serif; }
        .ticket-toolbar { display: flex; justify-content: center; gap: 10px; padding: 16px; }
        .ticket-toolbar button, .ticket-toolbar a { border: 0; border-radius: 7px; padding: 10px 15px; background: #11745f; color: #fff; font: inherit; text-decoration: none; cursor: pointer; }
        .ticket-toolbar a { background: #e1e9e5; color: #1e4238; }
        .ticket { width: 80mm; min-height: 90mm; margin: 0 auto 24px; padding: 5mm; background: #fff; box-shadow: 0 5px 24px #17231f1c; }
        .ticket-header { text-align: center; }
        .ticket-logo { display: block; width: 54px; height: 54px; margin: 0 auto 5px; object-fit: contain; }
        .ticket-header h1 { margin: 0; font-size: 16px; text-transform: uppercase; }
        .ticket-header p { margin: 2px 0; }
        .rule { margin: 9px 0; border: 0; border-top: 1px dashed #56615c; }
        .ticket-document { margin: 7px 0 2px; text-align: center; font-weight: 700; text-transform: uppercase; }
        .ticket-number { margin: 0; text-align: center; font-size: 16px; font-weight: 700; }
        .ticket-meta p, .ticket-client p { margin: 2px 0; overflow-wrap: anywhere; }
        .ticket-section-title { margin: 0 0 5px; font-size: 12px; text-transform: uppercase; }
        .ticket-items { display: grid; gap: 7px; }
        .ticket-item { break-inside: avoid; }
        .ticket-item-name { font-weight: 700; overflow-wrap: anywhere; }
        .ticket-item-values { display: flex; justify-content: space-between; gap: 8px; }
        .ticket-totals { display: grid; gap: 3px; }
        .ticket-totals p { display: flex; justify-content: space-between; gap: 8px; margin: 0; }
        .ticket-grand-total { margin-top: 4px !important; padding-top: 5px; border-top: 1px solid #27352f; font-size: 15px; font-weight: 700; }
        .ticket-footer { margin-top: 14px; text-align: center; }
        .ticket-footer p { margin: 2px 0; }
        @page { size: 80mm auto; margin: 3mm; }
        @media print {
            body { background: #fff; font-size: 11px; }
            .ticket-toolbar { display: none !important; }
            .ticket { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
            .ticket-item, .ticket-totals, .ticket-client, .ticket-meta { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <nav class="ticket-toolbar" aria-label="Opciones del ticket">
        <button type="button" onclick="window.print()">Imprimir / Guardar como PDF</button>
        <a href="index.php?route=module/ventas/ver&amp;id=<?= (int) $sale["idventa"] ?>">Volver al comprobante</a>
    </nav>
    <main class="ticket">
        <header class="ticket-header">
            <?php if ($logoData !== ""): ?><img class="ticket-logo" src="<?= $logoData ?>" alt="Logo de la farmacia"><?php endif; ?>
            <h1><?= $escape($receiptConfig["business_name"] ?? "FULL HEART") ?></h1>
            <?php if (!empty($receiptConfig["ruc"])): ?><p>RUC: <?= $escape($receiptConfig["ruc"]) ?></p><?php endif; ?>
            <?php if (!empty($receiptConfig["address"])): ?><p><?= $escape($receiptConfig["address"]) ?></p><?php endif; ?>
        </header>
        <hr class="rule">
        <p class="ticket-document"><?= $escape($sale["comprobante"]) ?></p>
        <p class="ticket-number"><?= $escape($invoiceNumber) ?></p>
        <div class="ticket-meta">
            <p>Fecha: <?= $escape(date("d/m/Y H:i:s", strtotime($sale["fecha"]))) ?></p>
            <p>Pago: <?= $isCredit ? "CRÉDITO" : "CONTADO" ?></p>
            <?php if ($isCredit): ?><p>Vencimiento: <?= !empty($sale["credito"]["fecha_vencimiento"]) ? $escape(date("d/m/Y", strtotime($sale["credito"]["fecha_vencimiento"]))) : "—" ?></p><?php endif; ?>
            <p>Vendedor: <?= $escape($sale["vendedor"]) ?></p>
        </div>
        <hr class="rule">
        <section class="ticket-client">
            <h2 class="ticket-section-title">Cliente</h2>
            <p><?= $escape($sale["cliente"] ?: "Cliente general") ?></p>
            <?php if (!empty($sale["nrodocumento"])): ?><p>DNI/RUC: <?= $escape($sale["nrodocumento"]) ?></p><?php endif; ?>
        </section>
        <hr class="rule">
        <section>
            <h2 class="ticket-section-title">Detalle</h2>
            <div class="ticket-items">
                <?php foreach ($sale["detalles"] as $line): ?>
                    <article class="ticket-item">
                        <div class="ticket-item-name"><?= $escape($line["producto"]) ?></div>
                        <div class="ticket-item-values"><span><?= rtrim(rtrim(number_format((float) $line["cantidad"], 2, ".", ""), "0"), ".") ?> <?= $escape($line["unidad_nombre"]) ?> × S/ <?= number_format((float) $line["pventa"], 2) ?></span><strong>S/ <?= number_format((float) $line["total"], 2) ?></strong></div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <hr class="rule">
        <div class="ticket-totals">
            <?php if ((float) $sale["total_gravado"] > 0): ?><p><span>Op. gravadas</span><span>S/ <?= number_format((float) $sale["total_gravado"], 2) ?></span></p><?php endif; ?>
            <?php if ((float) $sale["total_exonerado"] > 0): ?><p><span>Op. exoneradas</span><span>S/ <?= number_format((float) $sale["total_exonerado"], 2) ?></span></p><?php endif; ?>
            <?php if ((float) $sale["total_inafecto"] > 0): ?><p><span>Op. inafectas</span><span>S/ <?= number_format((float) $sale["total_inafecto"], 2) ?></span></p><?php endif; ?>
            <?php if ((float) $sale["total_igv"] > 0): ?><p><span>IGV incluido</span><span>S/ <?= number_format((float) $sale["total_igv"], 2) ?></span></p><?php endif; ?>
            <p class="ticket-grand-total"><span>TOTAL</span><span>S/ <?= number_format((float) $sale["total"], 2) ?></span></p>
            <?php if (!$isCredit): ?>
                <p><span>Monto recibido</span><span>S/ <?= number_format((float) $sale["monto_recibido"], 2) ?></span></p>
                <p><span>Vuelto</span><span>S/ <?= number_format((float) $sale["vuelto"], 2) ?></span></p>
            <?php elseif (!empty($sale["credito"])): ?>
                <p><span>Cuotas</span><span><?= (int) $sale["credito"]["numero_cuotas"] ?></span></p>
                <p><span>Saldo crédito</span><span>S/ <?= number_format((float) $sale["credito"]["saldo"], 2) ?></span></p>
            <?php endif; ?>
        </div>
        <footer class="ticket-footer"><p>¡Gracias por su compra!</p><p>Vuelva pronto</p></footer>
    </main>
</body>
</html>

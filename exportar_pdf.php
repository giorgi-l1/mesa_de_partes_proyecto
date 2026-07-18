<?php
session_start();

if (!isset($_SESSION["auth"]) || $_SESSION["auth"] != "1") {
    header("Location: index.php");
    exit();
}

require 'conexion.php';
require 'lib/fpdf.php';

$id_usuario = $_SESSION["id_usuario"];
$id_tramite = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_tramite <= 0) {
    header("Location: principal.php");
    exit();
}

// ----------------------------------------------------
// 1. OBTENER DATOS GENERALES (mismo control de dueño que ver_detalle.php)
// ----------------------------------------------------
$query_tramite = "SELECT t.id_tramite, t.numero_expediente, t.asunto, t.descripcion_motivo,
                          t.fecha_envio, t.observacion_admin,
                          tp.nombre_tramite,
                          e.nombre_estado,
                          o.nombre_oficina, o.siglas
                   FROM tramites t
                   INNER JOIN tipos_tramite tp ON t.id_tipo_tramite = tp.id_tipo_tramite
                   INNER JOIN estados_tramite e ON t.id_estado = e.id_estado
                   INNER JOIN oficinas o ON t.id_oficina_actual = o.id_oficina
                   WHERE t.id_tramite = '$id_tramite' AND t.id_usuario = '$id_usuario'
                   LIMIT 1";
$res_tramite = mysqli_query($cn, $query_tramite);

if (!$res_tramite || mysqli_num_rows($res_tramite) == 0) {
    header("Location: principal.php?error=notfound");
    exit();
}
$tramite = mysqli_fetch_assoc($res_tramite);

// Datos del solicitante (para el encabezado de la constancia)
$nombre_usuario = "";
$documento_usuario = "";
$id_tipo = $_SESSION["id_tipo"];

if ($id_tipo == 4) {
    $q = "SELECT j.razon_social, j.ruc FROM datos_juridicos j WHERE j.id_usuario = '$id_usuario'";
    $r = mysqli_query($cn, $q);
    if ($f = mysqli_fetch_assoc($r)) {
        $nombre_usuario = $f['razon_social'];
        $documento_usuario = "RUC: " . $f['ruc'];
    }
} else {
    $q = "SELECT p.nombres, p.apellido_paterno, p.apellido_materno, p.numero_documento
          FROM datos_personales p WHERE p.id_usuario = '$id_usuario'";
    $r = mysqli_query($cn, $q);
    if ($f = mysqli_fetch_assoc($r)) {
        $nombre_usuario = $f['nombres'] . ' ' . $f['apellido_paterno'] . ' ' . $f['apellido_materno'];
        $documento_usuario = "DNI: " . $f['numero_documento'];
    }
}

// ----------------------------------------------------
// 2. DOCUMENTOS ADJUNTOS
// ----------------------------------------------------
$query_adjuntos = "SELECT nombre_adjunto, nombre_archivo, ruta_archivo, enlace_externo, fecha_subida
                    FROM documentos_adjuntos
                    WHERE id_tramite = '$id_tramite'
                    ORDER BY id_documento ASC";
$res_adjuntos = mysqli_query($cn, $query_adjuntos);
$adjuntos = [];
if ($res_adjuntos) {
    while ($row = mysqli_fetch_assoc($res_adjuntos)) {
        $adjuntos[] = $row;
    }
}

// ----------------------------------------------------
// 3. HISTORIAL DE MOVIMIENTOS
// ----------------------------------------------------
$query_movimientos = "SELECT m.numero_movimiento, m.fecha_envio, m.fecha_recepcion, m.observaciones,
                              eo.nombre_estado,
                              oo.nombre_oficina AS oficina_origen,
                              od.nombre_oficina AS oficina_destino
                       FROM movimientos_tramite m
                       INNER JOIN estados_tramite eo ON m.id_estado_mov = eo.id_estado
                       INNER JOIN oficinas oo ON m.id_oficina_origen = oo.id_oficina
                       INNER JOIN oficinas od ON m.id_oficina_destino = od.id_oficina
                       WHERE m.id_tramite = '$id_tramite'
                       ORDER BY m.numero_movimiento ASC";
$res_movimientos = mysqli_query($cn, $query_movimientos);
$movimientos = [];
if ($res_movimientos) {
    while ($row = mysqli_fetch_assoc($res_movimientos)) {
        $movimientos[] = $row;
    }
}

// ----------------------------------------------------
// 4. FUNCIONES AUXILIARES (FPDF trabaja en Latin-1)
// ----------------------------------------------------
function tx($texto) {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string) $texto);
}

function truncar($texto, $largo) {
    $texto = (string) $texto;
    if (strlen($texto) <= $largo) {
        return $texto;
    }
    return substr($texto, 0, $largo - 3) . '...';
}

// ----------------------------------------------------
// 5. GENERACIÓN DEL PDF
// ----------------------------------------------------
class PDFConstancia extends FPDF
{
    public $numero_expediente = '';

    function Header()
    {
        $this->SetFont('Helvetica', 'B', 13);
        $this->SetTextColor(10, 66, 117); // azul institucional
        $this->Cell(0, 8, tx('UNIVERSIDAD NACIONAL "JOSE FAUSTINO SANCHEZ CARRION"'), 0, 1, 'C');
        $this->SetFont('Helvetica', '', 10);
        $this->SetTextColor(90, 90, 90);
        $this->Cell(0, 6, tx('Sistema de Mesa de Partes Virtual'), 0, 1, 'C');
        $this->SetDrawColor(197, 160, 89); // dorado
        $this->SetLineWidth(0.6);
        $this->Line(15, 22, 195, 22);
        $this->Ln(8);
    }

    function Footer()
    {
        $this->SetY(-18);
        $this->SetDrawColor(197, 160, 89);
        $this->SetLineWidth(0.3);
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        $this->SetY(-15);
        $this->SetFont('Helvetica', 'I', 8);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 5, tx('Documento generado automaticamente el ' . date('d/m/Y H:i') . ' - Constancia de gestion del expediente ' . $this->numero_expediente), 0, 0, 'C');
    }

    function SeccionTitulo($titulo)
    {
        $this->SetFont('Helvetica', 'B', 11);
        $this->SetFillColor(10, 66, 117);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 8, '  ' . tx($titulo), 0, 1, 'L', true);
        $this->SetTextColor(30, 30, 30);
        $this->Ln(2);
    }

    function Campo($etiqueta, $valor, $anchoEtiqueta = 50)
    {
        $this->SetFont('Helvetica', 'B', 9.5);
        $this->SetTextColor(80, 80, 80);
        $this->Cell($anchoEtiqueta, 6, tx($etiqueta . ':'), 0, 0);
        $this->SetFont('Helvetica', '', 9.5);
        $this->SetTextColor(20, 20, 20);
        $this->MultiCell(0, 6, tx($valor));
    }
}

$pdf = new PDFConstancia();
$pdf->numero_expediente = $tramite['numero_expediente'];
$pdf->AliasNbPages();
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 22);
$pdf->AddPage();

// --- Título de la constancia ---
$pdf->SetFont('Helvetica', 'B', 15);
$pdf->SetTextColor(6, 40, 71);
$pdf->Cell(0, 10, tx('CONSTANCIA DE TRAMITE - ' . $tramite['numero_expediente']), 0, 1, 'C');
$pdf->Ln(3);

// --- Datos del solicitante ---
$pdf->SeccionTitulo('Datos del Solicitante');
$pdf->Campo('Nombre / Razon Social', $nombre_usuario);
$pdf->Campo('Documento', $documento_usuario);
$pdf->Ln(4);

// --- Datos generales del trámite ---
$pdf->SeccionTitulo('Datos Generales del Tramite');
$pdf->Campo('Numero de Expediente', $tramite['numero_expediente']);
$pdf->Campo('Tipo de Tramite', $tramite['nombre_tramite']);
$pdf->Campo('Fecha de Envio', date('d/m/Y H:i', strtotime($tramite['fecha_envio'])));
$pdf->Campo('Oficina Actual', $tramite['nombre_oficina'] . ' (' . $tramite['siglas'] . ')');
$pdf->Campo('Estado Actual', $tramite['nombre_estado']);
$pdf->Ln(2);
$pdf->Campo('Asunto', $tramite['asunto']);
$pdf->Ln(1);
$pdf->Campo('Descripcion / Motivo', $tramite['descripcion_motivo']);

if (!empty($tramite['observacion_admin'])) {
    $pdf->Ln(1);
    $pdf->Campo('Observacion de la Administracion', $tramite['observacion_admin']);
}
$pdf->Ln(4);

// --- Documentos adjuntos ---
$pdf->SeccionTitulo('Documentos Adjuntos');
if (count($adjuntos) > 0) {
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->SetFillColor(244, 246, 249);
    $pdf->Cell(70, 7, tx('Nombre'), 1, 0, 'L', true);
    $pdf->Cell(60, 7, tx('Archivo / Enlace'), 1, 0, 'L', true);
    $pdf->Cell(45, 7, tx('Fecha de subida'), 1, 1, 'L', true);

    $pdf->SetFont('Helvetica', '', 9);
    foreach ($adjuntos as $adj) {
        $ref = !empty($adj['nombre_archivo']) ? $adj['nombre_archivo'] : (!empty($adj['enlace_externo']) ? $adj['enlace_externo'] : 'N/A');
        $pdf->Cell(70, 7, tx($adj['nombre_adjunto']), 1, 0);
        $pdf->Cell(60, 7, tx(truncar($ref, 38)), 1, 0);
        $pdf->Cell(45, 7, tx(date('d/m/Y H:i', strtotime($adj['fecha_subida']))), 1, 1);
    }
} else {
    $pdf->SetFont('Helvetica', 'I', 9);
    $pdf->SetTextColor(120, 120, 120);
    $pdf->Cell(0, 7, tx('Este tramite no tiene documentos adjuntos registrados.'), 0, 1);
    $pdf->SetTextColor(20, 20, 20);
}
$pdf->Ln(4);

// --- Historial de movimientos ---
$pdf->SeccionTitulo('Historial de Movimientos');
if (count($movimientos) > 0) {
    foreach ($movimientos as $mov) {
        $pdf->SetFont('Helvetica', 'B', 9.5);
        $pdf->SetTextColor(10, 66, 117);
        $pdf->Cell(0, 6, tx('Movimiento N. ' . $mov['numero_movimiento'] . '  -  ' . date('d/m/Y H:i', strtotime($mov['fecha_envio']))), 0, 1);

        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(20, 20, 20);
        $pdf->Cell(0, 5.5, tx('De: ' . $mov['oficina_origen'] . '   ->   A: ' . $mov['oficina_destino']), 0, 1);
        $pdf->Cell(0, 5.5, tx('Estado: ' . $mov['nombre_estado']), 0, 1);

        if (!empty($mov['observaciones'])) {
            $pdf->MultiCell(0, 5.5, tx('Observaciones: ' . $mov['observaciones']));
        }
        if (!empty($mov['fecha_recepcion'])) {
            $pdf->Cell(0, 5.5, tx('Recepcionado el ' . date('d/m/Y H:i', strtotime($mov['fecha_recepcion']))), 0, 1);
        }
        $pdf->Ln(3);
    }
} else {
    $pdf->SetFont('Helvetica', 'I', 9);
    $pdf->SetTextColor(120, 120, 120);
    $pdf->Cell(0, 7, tx('Aun no se han registrado movimientos para este tramite.'), 0, 1);
}

// ----------------------------------------------------
// 6. SALIDA DEL ARCHIVO
// ----------------------------------------------------
$nombre_pdf = str_replace('-', '_', $tramite['numero_expediente']) . '_constancia.pdf';
$pdf->Output('D', $nombre_pdf); // 'D' = fuerza la descarga en el navegador
exit();

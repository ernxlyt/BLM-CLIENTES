<?php
// reports-export-excel.php - Archivo separado para la exportación a Excel
require_once 'vendor/autoload.php'; // Composer para PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Incluir archivos necesarios
include_once 'config/database.php';
include_once 'models/Report.php';
include_once 'utils/session.php';

// Verificar login
requireLogin();

// Obtener filtros de la URL
$search = $_GET['search'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Inicializar conexión a base de datos
$database = new Database();
$db = $database->getConnection();
$report = new Report($db);

// Obtener reportes con filtros aplicados
$reports_stmt = $report->readWithFilters(getCurrentUserId(), isAdmin(), $search, $dateFrom, $dateTo);

// Crear nuevo spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Configurar información del documento
$spreadsheet->getProperties()
    ->setCreator("Sistema de Reportes")
    ->setTitle("Reportes de Transacciones")
    ->setSubject("Exportación de Reportes")
    ->setDescription("Reportes exportados el " . date('d/m/Y H:i:s'));

// Configurar nombre de la hoja
$sheet->setTitle('Reportes de Transacciones');

// ENCABEZADO PRINCIPAL
$sheet->setCellValue('A1', 'REPORTES DE TRANSACCIONES');
$sheet->mergeCells('A1:I1');
$sheet->getStyle('A1')->applyFromArray([
    'font' => [
        'bold' => true,
        'size' => 16,
        'color' => ['rgb' => 'FFFFFF']
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '23D950']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ]
]);

// Información de exportación
$sheet->setCellValue('A2', 'Fecha de exportación: ' . date('d/m/Y'));

$sheet->mergeCells('A2:I2');
$sheet->mergeCells('A3:I3');

// Información de filtros aplicados
$filterInfo = [];
if (!empty($search)) {
    $filterInfo[] = "Búsqueda: \"$search\"";
}
if (!empty($dateFrom)) {
    $filterInfo[] = "Desde: " . date('d/m/Y', strtotime($dateFrom));
}
if (!empty($dateTo)) {
    $filterInfo[] = "Hasta: " . date('d/m/Y', strtotime($dateTo));
}

if (!empty($filterInfo)) {
    $sheet->setCellValue('A4', 'Filtros aplicados: ' . implode(' | ', $filterInfo));
    $sheet->mergeCells('A4:I4');
    $sheet->getStyle('A4')->applyFromArray([
        'font' => ['italic' => true, 'color' => ['rgb' => '6b7280']]
    ]);
}

// Fila vacía para separación
$headerRow = !empty($filterInfo) ? 6 : 5;

// Configurar encabezados de la tabla
$headers = [
    'A' => 'Cliente',
    'B' => 'Fecha Transacción',
    'C' => 'Método de Pago',
    'D' => 'Monto',
    'E' => 'Referencia',
    'F' => 'Período Desde',
    'G' => 'Período Hasta',
    'H' => 'Creado Por',
    'I' => 'Fecha Creación'
];

// Escribir encabezados de la tabla
foreach ($headers as $column => $value) {
    $sheet->setCellValue($column . $headerRow, $value);
}

// Estilo para encabezados de la tabla
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 11
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '1f2937']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => 'FFFFFF']
        ]
    ]
];

$sheet->getStyle('A' . $headerRow . ':I' . $headerRow)->applyFromArray($headerStyle);

// Escribir datos
$row = $headerRow + 1;
$totalRecords = 0;
$totalAmount = 0;

while ($data = $reports_stmt->fetch(PDO::FETCH_ASSOC)) {
    // Cliente
    $clientInfo = $data['nombre_cliente'];
    if ($data['nombre_plan']) {
        $clientInfo .= ' - ' . $data['nombre_plan'];
    }
    if ($data['nombre_empresa']) {
        $clientInfo .= ' • ' . $data['nombre_empresa'];
    }
    $sheet->setCellValue('A' . $row, $clientInfo);
    
    // Fecha de transacción
    $sheet->setCellValue('B' . $row, date('d/m/Y', strtotime($data['fecha_transaccion'])));
    
    // Método de pago
    $sheet->setCellValue('C' . $row, $data['metodo_pago']);
    
    // Monto
    if ($data['monto']) {
        $sheet->setCellValue('D' . $row, floatval($data['monto']));
        $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
        $totalAmount += floatval($data['monto']);
    } else {
        $sheet->setCellValue('D' . $row, 'N/A');
    }
    
    // Referencia
    $sheet->setCellValue('E' . $row, $data['numero_referencia'] ?? '-');
    
    // Período desde
    $sheet->setCellValue('F' . $row, $data['fecha_desde'] ? date('d/m/Y', strtotime($data['fecha_desde'])) : '-');
    
    // Período hasta
    $sheet->setCellValue('G' . $row, $data['fecha_hasta'] ? date('d/m/Y', strtotime($data['fecha_hasta'])) : '-');
    
    // Creado por
    $sheet->setCellValue('H' . $row, $data['nombre_usuario'] ?? 'Sistema');
    
    // Fecha de creación
    $sheet->setCellValue('I' . $row, date('d/m/Y H:i', strtotime($data['fecha_creacion'])));
    
    $row++;
    $totalRecords++;
}

// Agregar fila de totales si hay datos
if ($totalRecords > 0) {
    $totalRow = $row + 1;
    
    $sheet->setCellValue('A' . $totalRow, 'TOTALES:');
    $sheet->setCellValue('B' . $totalRow, $totalRecords . ' registros');
    $sheet->setCellValue('D' . $totalRow, $totalAmount);
    $sheet->getStyle('D' . $totalRow)->getNumberFormat()->setFormatCode('$#,##0.00');
    
    // Estilo para fila de totales
    $sheet->getStyle('A' . $totalRow . ':I' . $totalRow)->applyFromArray([
        'font' => ['bold' => true],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'f0fdf4']
        ],
        'borders' => [
            'top' => [
                'borderStyle' => Border::BORDER_THICK,
                'color' => ['rgb' => '23D950']
            ]
        ]
    ]);
}

// Configurar ancho de columnas
$columnWidths = [
    'A' => 35, // Cliente
    'B' => 15, // Fecha Transacción
    'C' => 20, // Método de Pago
    'D' => 15, // Monto
    'E' => 20, // Referencia
    'F' => 15, // Período Desde
    'G' => 15, // Período Hasta
    'H' => 20, // Creado Por
    'I' => 18  // Fecha Creación
];

foreach ($columnWidths as $column => $width) {
    $sheet->getColumnDimension($column)->setWidth($width);
}

// Aplicar bordes a toda la tabla de datos
if ($totalRecords > 0) {
    $dataRange = 'A' . $headerRow . ':I' . ($row - 1);
    $borderStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'CCCCCC']
            ]
        ]
    ];
    $sheet->getStyle($dataRange)->applyFromArray($borderStyle);
    
    // Aplicar estilo alternado a las filas de datos
    for ($i = $headerRow + 1; $i < $row; $i++) {
        if (($i - $headerRow) % 2 == 0) {
            $sheet->getStyle('A' . $i . ':I' . $i)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F8FAFC']
                ]
            ]);
        }
    }
}

// Configurar altura de filas
$sheet->getRowDimension(1)->setRowHeight(25);
for ($i = $headerRow; $i < $row; $i++) {
    $sheet->getRowDimension($i)->setRowHeight(20);
}

// Configurar nombre del archivo
$fileName = 'Reporte de Pagos_ ' . date('Y-m-d_H-i-s');

// Agregar información de filtros al nombre del archivo
$filenameParts = [];
if (!empty($search)) {
    $filenameParts[] = 'busqueda';
}
if (!empty($dateFrom)) {
    $filenameParts[] = 'desde-' . date('Y-m-d', strtotime($dateFrom));
}
if (!empty($dateTo)) {
    $filenameParts[] = 'hasta-' . date('Y-m-d', strtotime($dateTo));
}

if (!empty($filenameParts)) {
    $fileName .= '_' . implode('_', $filenameParts);
}

$fileName .= '.xlsx';

// Configurar headers para descarga AUTOMÁTICA
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');
header('Cache-Control: max-age=1');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: cache, must-revalidate');
header('Pragma: public');

// Escribir archivo y forzar descarga
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

// Limpiar memoria
$spreadsheet->disconnectWorksheets();
unset($spreadsheet);
exit;
?>

<?php
// Automated Monthly Report Generator
require_once('fpdf/fpdf.php');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "SmartMeterDB";

$conn = new mysqli($servername, $username, $password, $dbname);

class PDF extends FPDF
{
    function Header()
    {
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'Smart Meter Monthly Report', 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 10, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        $this->Ln(10);
    }
    
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }
}

// Generate monthly report
function generateMonthlyReport($location) {
    global $conn;
    
    $pdf = new PDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 12);
    
    // Summary Statistics
    $sql = "SELECT 
                COUNT(*) as total_meters,
                SUM(LastReading) as total_consumption,
                AVG(LastReading) as avg_consumption
            FROM Meters 
            WHERE Location = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $location);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
    
    $pdf->Cell(0, 10, 'Location: ' . $location, 0, 1);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 8, 'Total Meters: ' . $stats['total_meters'], 0, 1);
    $pdf->Cell(0, 8, 'Total Consumption: ' . round($stats['total_consumption'], 2) . ' kWh', 0, 1);
    $pdf->Cell(0, 8, 'Average Consumption: ' . round($stats['avg_consumption'], 2) . ' kWh', 0, 1);
    
    $pdf->Ln(10);
    
    // Meter Details Table
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 8, 'Meter ID', 1);
    $pdf->Cell(50, 8, 'Client', 1);
    $pdf->Cell(30, 8, 'Status', 1);
    $pdf->Cell(40, 8, 'Last Reading', 1);
    $pdf->Ln();
    
    $detail_sql = "SELECT MeterID, ClientName, Status, LastReading 
                   FROM Meters WHERE Location = ?";
    $detail_stmt = $conn->prepare($detail_sql);
    $detail_stmt->bind_param("s", $location);
    $detail_stmt->execute();
    $detail_result = $detail_stmt->get_result();
    
    $pdf->SetFont('Arial', '', 9);
    while($row = $detail_result->fetch_assoc()) {
        $pdf->Cell(40, 7, $row['MeterID'], 1);
        $pdf->Cell(50, 7, $row['ClientName'], 1);
        $pdf->Cell(30, 7, $row['Status'], 1);
        $pdf->Cell(40, 7, $row['LastReading'] . ' kWh', 1);
        $pdf->Ln();
    }
    
    $filename = 'reports/monthly_report_' . $location . '_' . date('Y-m') . '.pdf';
    $pdf->Output('F', $filename);
    
    return $filename;
}

// API endpoint
if (isset($_GET['location'])) {
    $location = $_GET['location'];
    $file = generateMonthlyReport($location);
    
    echo json_encode([
        "success" => true,
        "message" => "Report generated successfully",
        "file" => $file
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Location parameter required"
    ]);
}

$conn->close();
?>
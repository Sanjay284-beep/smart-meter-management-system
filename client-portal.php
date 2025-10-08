<?php
// Client Portal - View Meter Data & Bills
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "SmartMeterDB";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

// Get client meters
if (isset($_GET['client_id'])) {
    $client_id = $_GET['client_id'];
    
    $sql = "SELECT m.MeterID, m.Location, m.Status, m.LastReading, 
                   b.BillAmount, b.BillDate, b.PaymentStatus
            FROM Meters m
            LEFT JOIN Bills b ON m.MeterID = b.MeterID
            WHERE m.ClientID = ?
            ORDER BY b.BillDate DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = array();
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode([
        "success" => true,
        "meters" => $data,
        "total_meters" => count($data)
    ]);
    
    $stmt->close();
}

// Submit meter reading
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $meter_id = $input['meter_id'];
    $reading = $input['reading'];
    $reading_date = date('Y-m-d H:i:s');
    
    $sql = "INSERT INTO MeterReadings (MeterID, Units, ReadingDate, Status) 
            VALUES (?, ?, ?, 'Verified')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sds", $meter_id, $reading, $reading_date);
    
    if ($stmt->execute()) {
        // Update last reading in Meters table
        $update_sql = "UPDATE Meters SET LastReading = ? WHERE MeterID = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ds", $reading, $meter_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        echo json_encode([
            "success" => true,
            "message" => "Reading submitted successfully"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Error: " . $stmt->error
        ]);
    }
    
    $stmt->close();
}

$conn->close();
?>
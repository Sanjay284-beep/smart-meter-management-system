<?php
// Process bulk meter readings from CSV/API
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "SmartMeterDB";

$conn = new mysqli($servername, $username, $password, $dbname);

// Process CSV upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    
    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, 'r');
    
    $processed = 0;
    $errors = 0;
    
    // Skip header row
    fgetcsv($handle);
    
    while (($data = fgetcsv($handle)) !== FALSE) {
        $meter_id = $data[0];
        $reading = $data[1];
        $reading_date = $data[2];
        
        // Validate data
        if (empty($meter_id) || !is_numeric($reading)) {
            $errors++;
            continue;
        }
        
        // Insert reading
        $sql = "INSERT INTO MeterReadings (MeterID, Units, ReadingDate, Status) 
                VALUES (?, ?, ?, 'Auto-Processed')";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sds", $meter_id, $reading, $reading_date);
        
        if ($stmt->execute()) {
            // Update meter
            $update_sql = "UPDATE Meters SET LastReading = ? WHERE MeterID = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ds", $reading, $meter_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            $processed++;
        } else {
            $errors++;
        }
        
        $stmt->close();
    }
    
    fclose($handle);
    
    echo json_encode([
        "success" => true,
        "processed" => $processed,
        "errors" => $errors,
        "message" => "Processed $processed readings with $errors errors"
    ]);
}

// Calculate bill for a meter
if (isset($_GET['calculate_bill'])) {
    $meter_id = $_GET['meter_id'];
    $rate_per_unit = 7.5; // ₹7.5 per kWh
    
    $sql = "SELECT SUM(Units) as total_units 
            FROM MeterReadings 
            WHERE MeterID = ? 
            AND MONTH(ReadingDate) = MONTH(CURRENT_DATE())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $meter_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    $units = $data['total_units'] ?? 0;
    $bill_amount = $units * $rate_per_unit;
    
    // Insert bill
    $insert_sql = "INSERT INTO Bills (MeterID, BillAmount, Units, BillDate, PaymentStatus)
                   VALUES (?, ?, ?, NOW(), 'Pending')";
    
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("sdd", $meter_id, $bill_amount, $units);
    $insert_stmt->execute();
    
    echo json_encode([
        "success" => true,
        "meter_id" => $meter_id,
        "units" => $units,
        "bill_amount" => $bill_amount,
        "rate" => $rate_per_unit
    ]);
    
    $stmt->close();
    $insert_stmt->close();
}

$conn->close();
?>

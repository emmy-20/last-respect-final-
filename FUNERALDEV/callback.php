<?php
include 'db_connect.php';

$callbackJSON = file_get_contents('php://input');
file_put_contents('mpesa_callback_log.txt', $callbackJSON . PHP_EOL, FILE_APPEND); // Log the full callback for debugging

$data = json_decode($callbackJSON, true);

if (!isset($data['Body']['stkCallback'])) {
    http_response_code(400);
    exit("Invalid callback payload");
}

$stkCallback = $data['Body']['stkCallback'];
$checkoutID = $stkCallback['CheckoutRequestID'];
$resultCode = $stkCallback['ResultCode'];
$resultDesc = $stkCallback['ResultDesc'];
$amount = null;
$phone = null;
$receipt = null;

if ($resultCode === 0 && isset($stkCallback['CallbackMetadata'])) {
    foreach ($stkCallback['CallbackMetadata']['Item'] as $item) {
        if ($item['Name'] === 'Amount') {
            $amount = $item['Value'];
        } elseif ($item['Name'] === 'MpesaReceiptNumber') {
            $receipt = $item['Value'];
        } elseif ($item['Name'] === 'PhoneNumber') {
            $phone = $item['Value'];
        }
    }
}

// Log the extracted values for debugging
file_put_contents('mpesa_callback_log.txt', "Phone: $phone, Amount: $amount, Receipt: $receipt\n", FILE_APPEND);

// Ensure phone number is in correct format (e.g., 254701234567)
$phone = preg_replace('/^0/', '254', $phone); // Adjust as needed

// Check that phone and amount are not empty
if (!$phone || !$amount) {
    file_put_contents('mpesa_callback_log.txt', "Error: Missing phone or amount\n", FILE_APPEND);
    exit("Phone or amount missing");
}

// Update or insert transaction record
$stmt = $conn->prepare("
    UPDATE transactions 
    SET result_code = ?, result_desc = ?, mpesa_receipt = ?, phone = ?, amount = ?, updated_at = NOW()
    WHERE checkout_request_id = ?
");
$stmt->bind_param("ssssds", $resultCode, $resultDesc, $receipt, $phone, $amount, $checkoutID);
$stmt->execute();
$stmt->close();

// Optional: If not found, insert new transaction instead
if ($conn->affected_rows === 0) {
    $stmt = $conn->prepare("
        INSERT INTO transactions (checkout_request_id, result_code, result_desc, mpesa_receipt, phone, amount, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("sssssd", $checkoutID, $resultCode, $resultDesc, $receipt, $phone, $amount);
    $stmt->execute();
    $stmt->close();
}

// ✅ If payment is successful, update contributions table
if ($resultCode === 0 && $phone && $amount) {
    $stmt = $conn->prepare("
        UPDATE contributions 
        SET status = 'paid', payment_method = 'MPESA', amount = ?
        WHERE phone_number = ? AND status = 'pending'
        LIMIT 1
    ");
    $stmt->bind_param("ds", $amount, $phone);
    $stmt->execute();
    $stmt->close();

    if ($conn->affected_rows > 0) {
        file_put_contents('mpesa_callback_log.txt', "Contribution updated successfully for phone: $phone\n", FILE_APPEND);
    } else {
        file_put_contents('mpesa_callback_log.txt', "No matching contribution found for phone: $phone\n", FILE_APPEND);
    }
}

// Respond with success
echo json_encode(["ResultCode" => 0, "ResultDesc" => "Callback received successfully."]);
?>

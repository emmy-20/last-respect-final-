$headers = [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $accessToken
];
$curl = curl_init($stk_push_url);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($stk_payload));
$stk_response = curl_exec($curl);
curl_close($curl);

$stk_data = json_decode($stk_response, true);
$checkoutID = $stk_data['CheckoutRequestID'] ?? null;

if (!$checkoutID) {
    die("STK Push failed: " . json_encode($stk_data));
}

// Optional: Show interim message
echo "STK Push Sent. Please complete payment on your phone...<br><br>";

// Wait 5-10 seconds
sleep(6);

// Query STK Status
$query_url = 'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query';
$query_payload = [
    "BusinessShortCode" => $BusinessShortCode,
    "Password" => $Password,
    "Timestamp" => $Timestamp,
    "CheckoutRequestID" => $checkoutID
];

$curl = curl_init($query_url);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($query_payload));
$query_response = curl_exec($curl);
curl_close($curl);

$query_data = json_decode($query_response, true);
$resultCode = $query_data['ResultCode'] ?? 'UNKNOWN';
$resultDesc = $query_data['ResultDesc'] ?? 'No result description';

// Save to DB
$stmt = $conn->prepare("INSERT INTO transactions (phone, amount, checkout_request_id, result_code, result_desc, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("sdsss", $phone, $amount, $checkoutID, $resultCode, $resultDesc);
$stmt->execute();
$stmt->close();

// Show to user
if ($resultCode === "0") {
    echo "<strong>Payment Successful:</strong> $resultDesc";
} elseif ($resultCode === "1032") {
    echo "<strong>Payment Cancelled by User.</strong>";
} else {
    echo "<strong>Payment Failed or Pending:</strong> $resultDesc (Code: $resultCode)";
}
?>
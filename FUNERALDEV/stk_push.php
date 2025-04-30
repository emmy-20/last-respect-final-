<?php
include 'db_connect.php';
date_default_timezone_set('Africa/Nairobi');

// Fetch phone and marital status from the form
$raw_phone = $_POST['phone']; // e.g., 0712345678
$PartyA = preg_replace('/^0/', '254', $raw_phone); // Convert 07... to 2547... // Phone number
 // Marital status


// fixed amount 
$Amount = 1;

// M-PESA credentials
$consumerKey = 'BvwpfQjCABVUchSa2qUAC6GYoqdntkJ9Y6w61fV2H1edEdiG';
$consumerSecret = 'sIJPWwmRJV1DjBtWhpkeoAU1FxouDL9NSQk3curw1Ad6LL48qkmvPt8p7lgHFABO';
$BusinessShortCode = '174379';
$Passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';

// Account and transaction details
$AccountReference = 'Pio Spices East Africa';
$TransactionDesc = 'Contribution payment for deceased member';
$Timestamp = date('YmdHis');
$Password = base64_encode($BusinessShortCode . $Passkey . $Timestamp);


// Get access token
$access_token_url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
$credentials = base64_encode($consumerKey . ":" . $consumerSecret);

$curl = curl_init($access_token_url);
curl_setopt($curl, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($curl);

if (curl_errno($curl)) die("Access Token Request Error: " . curl_error($curl));
$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
if ($status != 200) die("Failed to get access token. HTTP Status: $status, Response: $result");

$result = json_decode($result);
if (!isset($result->access_token)) die("Invalid access token response: " . json_encode($result));
$accessToken = $result->access_token;
curl_close($curl);

// STK Push request URL
$initiate_url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
$CallBackURL = 'https://stk-push-php.herokuapp.com/callback.php';

$stkheader = [
    'Content-Type:application/json',
    'Authorization:Bearer ' . $accessToken
];

$stk_push_payload = [
    'BusinessShortCode' => $BusinessShortCode,
    'Password' => $Password,
    'Timestamp' => $Timestamp,
    'TransactionType' => 'CustomerPayBillOnline',
    'Amount' => $Amount,  // Amount to be contributed
    'PartyA' => $PartyA,  // Phone number
    'PartyB' => $BusinessShortCode,
    'PhoneNumber' => $PartyA, // Phone number
    'CallBackURL' => $CallBackURL,
    'AccountReference' => $AccountReference,
    'TransactionDesc' => $TransactionDesc
];

// Initiate the STK push request
$curl = curl_init($initiate_url);
curl_setopt($curl, CURLOPT_HTTPHEADER, $stkheader);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($stk_push_payload));
$stk_response = curl_exec($curl);

if (curl_errno($curl)) die("STK Push Request Error: " . curl_error($curl));
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
if ($http_code != 200) die("STK Push failed. HTTP Status: $http_code, Response: $stk_response");

$response_data = json_decode($stk_response, true);

// Check if STK push was successful
if (isset($response_data['ResponseCode']) && $response_data['ResponseCode'] == "0") {
    echo "<strong>STK Push initiated successfully.</strong><br>";
    echo "CheckoutRequestID: " . $response_data['CheckoutRequestID'] . "<br>";
    echo "CustomerMessage: " . $response_data['CustomerMessage'];
} else {
    echo "STK Push failed: " . json_encode($response_data);
}

curl_close($curl);
$stk_data = json_decode($stk_response, true);
$checkoutID = $stk_data['CheckoutRequestID'] ?? null;

if (!$checkoutID) {
    die("STK Push failed: " . json_encode($stk_data));
}

// Optional: Show interim message
echo "STK Push Sent. Please complete payment on your phone...<br><br>";

// Save initial pending transaction
$initial_resultCode = "PENDING";
$initial_resultDesc = "Awaiting callback";
$stmt = $conn->prepare("INSERT INTO transactions (user_id, death_id, phone, amount, checkout_request_id, result_code, result_desc, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("iisdsss", $user_id, $death_id, $PartyA, $Amount, $checkoutID, $initial_resultCode, $initial_resultDesc);
$stmt->execute();
$stmt->close();

// Wait 30 seconds before fallback query
sleep(30);

// Check if callback has already updated this transaction
$stmt = $conn->prepare("SELECT result_code, result_desc FROM transactions WHERE checkout_request_id = ?");
$stmt->bind_param("s", $checkoutID);
$stmt->execute();
$stmt->bind_result($existing_resultCode, $existing_resultDesc);
$stmt->fetch();
$stmt->close();

if ($existing_resultCode !== "PENDING") {
    echo "<br><strong>Status from Callback:</strong> $existing_resultDesc (Code: $existing_resultCode)";
} else {
    // Perform fallback STK query
    $query_url = 'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query';
    // NEW Timestamp and Password before fallback query
$Timestamp = date('YmdHis');
$Password = base64_encode($BusinessShortCode . $Passkey . $Timestamp);

// Then prepare your query payload
$query_payload = [
    "BusinessShortCode" => $BusinessShortCode,
    "Password" => $Password,
    "Timestamp" => $Timestamp,
    "CheckoutRequestID" => $checkoutID
];


    $curl = curl_init($query_url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $stkheader);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($query_payload));
    $query_response = curl_exec($curl);
    curl_close($curl);

    $query_data = json_decode($query_response, true);


    if (!$query_data || !isset($query_data['ResultCode'])) {
        $resultCode = 'UNKNOWN';
        $resultDesc = $query_data['errorMessage'] ?? 'No result description';
    } else {
        $resultCode = $query_data['ResultCode'];
        $resultDesc = $query_data['ResultDesc'] ?? 'No result description';
    }
    

    // Final message to user
    // Save to DB
$stmt = $conn->prepare("INSERT INTO transactions (phone, amount, checkout_request_id, result_code, result_desc, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
$phone = $PartyA;
$amount = $Amount;

$stmt->bind_param("sdsss", $phone, $amount, $checkoutID, $resultCode, $resultDesc);
$stmt->execute();
$stmt->close();

// Show user-friendly message
echo "<br><strong>Fallback Check:</strong> ";

// Optional: Update contributions table if payment is successful
if ($resultCode === "0") {
    $update_contrib = $conn->prepare("UPDATE contributions SET status = 'paid' WHERE phone_number = ? AND amount = ? AND status = 'pending'");
    $update_contrib->bind_param("sd", $phone, $amount);
    $update_contrib->execute();
    $update_contrib->close();
}

// if ($resultCode === "0") {
//     echo "Payment Successful - $resultDesc (Code: $resultCode)";
// } elseif ($resultCode === "1032") {
//     echo " Payment Cancelled by User (Code: $resultCode)";
// } elseif ($resultCode === "2001") {
//     echo "Payment Failed - Invalid Initiator Info (Code: $resultCode)";
// } elseif ($resultCode === "UNKNOWN") {
//     echo " Payment Status Unknown - No valid response received from Safaricom.";
// } else {
//     echo "Payment Failed or Still Pending - $resultDesc (Code: $resultCode)";
// }

    
        // Match contribution by phone number and status 'pending'
        $update_stmt = $conn->prepare("
            UPDATE contributions 
            SET status = 'paid', 
                amount = ?, 
                payment_method = 'MPESA'
            WHERE phone_number = ? AND status = 'pending'
            ORDER BY id DESC 
            LIMIT 1
        ");
        $update_stmt->bind_param("ds", $Amount, $raw_phone); // use original format (07...)
        $update_stmt->execute();
        $update_stmt->close();
    }
    



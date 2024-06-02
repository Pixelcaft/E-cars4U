<?php
// submitForm.php
include_once("IdP-map/IdP.php");

$username = "E-cars4U";
$password = "123";
$credentials = array(
    'username' => $username,
    'password' => $password,
    'exp' => time() + (60 * 60)
);

$idp = new IdP($credentials);
$token = $idp->getToken();

$APIurl = "https://localhost/E-cars4U/microservices/submitApi.php";


$ch = curl_init($APIurl);

curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
curl_setopt($ch, CURLOPT_USERPWD, "username:password");
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $token, "Content-Type: application/json"));

// Schakel SSL-verificatie uit
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

$curl_post_data = json_encode(array(
    'naam' => $_POST['naam'],
    'achternaam' => $_POST['achternaam'],
    'prijs' => $_POST['prijs'],
    'zitplaatsen' => $_POST['zitplaatsen'],
    'type' => $_POST['type'],
    'username' => $username,  // Toegevoegd voor token verificatie
    'password' => $password   // Toegevoegd voor token verificatie
));

curl_setopt($ch, CURLOPT_POSTFIELDS, $curl_post_data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

if ($response === false) {
    $error_msg = curl_error($ch);
    echo "cURL error: $error_msg";
    curl_close($ch);
    exit;
}

$resultStatus = curl_getinfo($ch);
$decoded = json_decode($response, true);

echo "<br>Raw Response: " . htmlspecialchars($response); // Voeg dit toe voor debugging

if (is_array($decoded)) {
    echo "<br>Message: " . $decoded["message"];
    echo "<br>Status: " . $decoded["status"];
} else {
    echo "<br>Failed to decode JSON response.";
}

curl_close($ch);
?>

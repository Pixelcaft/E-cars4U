<?php
include_once("IdP/IdP.php");

$username = "reisbureau ZIP";
$password = "123";
$credentials = array(
    'username' => $username,
    'password' => $password,
    'exp' => time() + (60 * 60)
);

$idp = new IdP($credentials);
$token = $idp->getToken();

$APIurl = "http://localhost/reisbureau/microservices/GetStedentripsMicroservice.php";
$ch = curl_init($APIurl);

curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
curl_setopt($ch, CURLOPT_USERPWD, "username:password");
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $token));

$curl_post_data = array(
    'apiKey' => '1234567890', 'username' => $username, 'password' => $password
);

curl_setopt($ch, CURLOPT_POSTFIELDS, $curl_post_data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

$resultStatus = curl_getinfo($ch);
$decoded = json_decode($response, true);
echo "<br>Message: " . $decoded["message"];
echo "<br>Status: " . $decoded["status"];
echo "<br>Bearer token: " . $decoded["bearerToken"];

curl_close($ch);
?>

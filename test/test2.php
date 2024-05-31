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

$APIurl = "http://localhost/reisbureau/submitApi.php";
$ch = curl_init($APIurl);

curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
curl_setopt($ch, CURLOPT_USERPWD, "username:password");
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $token, "Content-Type: application/json"));

$curl_post_data = json_encode(array(
    'naam' => $_POST['naam'],
    'achternaam' => $_POST['achternaam'],
    'prijs' => $_POST['prijs'],
    'zitplaatsen' => $_POST['zitplaatsen'],
    'type' => $_POST['type']
));

curl_setopt($ch, CURLOPT_POSTFIELDS, $curl_post_data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

$resultStatus = curl_getinfo($ch);
$decoded = json_decode($response, true);

echo "<br>Message: " . $decoded["message"];
echo "<br>Status: " . $decoded["status"];

curl_close($ch);
?>

<?php
// Include IdP class
include_once("IdP-map/IdP.php");

// Function to get token
function getToken()
{
    try {
        // Credentials
        $username = "E-cars4U";
        $password = "123";
        $credentials = array(
            'username' => $username,
            'password' => $password,
            'exp' => time() + (60 * 60) // Token expiration time
        );

        // Create IdP instance and return token
        $idp = new IdP($credentials);
        return $idp->getToken();
    } catch (Exception $e) {
        die(json_encode(array("message" => "Error getting token")));
    }
}

// Function to validate content type
function validateContentType($contentType)
{
    try {
        // List of valid content types
        $validContentTypes = ['application/json; charset=UTF-8'];
        // If content type is not valid, send 415 status code and exit
        if (!in_array($contentType, $validContentTypes)) {
            http_response_code(415);
            header('Content-Type: application/json; charset=UTF-8');
            header("X-Content-Type-Options: nosniff");
            die(json_encode(array("message" => "Unsupported Media Type")));
        }
    } catch (Exception $e) {
        die(json_encode(array("message" => "Error validating content type")));
    }
}

// Function to make cURL request
function curlRequest($url, $method, $data = null)
{
    try {
        // List of allowed HTTP methods
        $allowed_methods = array('GET');

        // If method is not allowed, send 405 status code and exit
        if (!in_array($method, $allowed_methods)) {
            http_response_code(405);
            header('Content-Type: application/json; charset=UTF-8');
            header("X-Content-Type-Options: nosniff");
            die(json_encode(array("message" => "Method not allowed")));
        }

        // Get token
        $token = getToken();

        // Initialize cURL
        $ch = curl_init($url);

        // Set cURL options
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_USERPWD, "username:password");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer " . $token,
            "Content-Type: application/json; charset=UTF-8",
            "Accept: application/json; charset=UTF-8",
            "X-Content-Type-Options: nosniff"
        ));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        // If data is provided, add it to the request
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        // Execute request and get response
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        // If there was an error with the request, close cURL and exit
        if ($response === false) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            header('Content-Type: application/json; charset=UTF-8');
            header("X-Content-Type-Options: nosniff");
            die(json_encode(array("message" => "cURL error: $error_msg")));
        }

        // Validate response content type
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        validateContentType($contentType);

        // Close cURL and return response
        curl_close($ch);
        return json_decode($response, true);
    } catch (Exception $e) {
        die(json_encode(array("message" => "Error making cURL request")));
    }
}

// Define URL and data for the request
$url = "https://localhost/E-cars4U/microservices/getDataApi.php";
$data = array('username' => 'E-cars4U', 'password' => '123', 'top' => '5');

// Make the request and get the response
$response = curlRequest($url, 'GET', $data);

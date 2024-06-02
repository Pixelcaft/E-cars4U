<?php
// Include IdP class
include_once("IdP-map/IdP.php");

// Function to get token
function getToken()
{
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
}

// Function to validate content type
function validateContentType($contentType)
{
    // List of valid content types
    $validContentTypes = ['application/json; charset=UTF-8'];
    // If content type is not valid, send 415 status code and exit
    if (!in_array($contentType, $validContentTypes)) {
        http_response_code(415);
        die("Unsupported Media Type");
    }
}

// Function to make cURL request
function curlRequest($url, $method, $data = null)
{
    // List of allowed HTTP methods
    $allowed_methods = array('GET');

    // If method is not allowed, send 405 status code and exit
    if (!in_array($method, $allowed_methods)) {
        http_response_code(405);
        die("Method not allowed");
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
        "Accept: application/json; charset=UTF-8"
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
        die("cURL error: $error_msg");
    }

    // Validate response content type
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    validateContentType($contentType);

    // Close cURL and return response
    curl_close($ch);
    return json_decode($response, true);
}

// Define URL and data for the request
$url = "https://localhost/E-cars4U/microservices/getDataApi.php";
$data = array('username' => 'E-cars4U', 'password' => '123', 'top' => '5');

// Make the request and get the response
$response = curlRequest($url, 'GET', $data);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
         table {
            width: 100%;
            border-collapse: collapse;
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        th {
            background-color: #4CAF50;
            color: white;
        }
    </style>
</head>
<body>
    <a href="dashboard-anonymous.php">Back</a>
    <br><br>
<table>
        <thead>
            <tr>
                <th>ID</th>
                <th>autoNaam</th>
                <th>Type</th>
                <th>Zitplaatsen</th>
                <th>Prijs, Per uur</th>
                <th>verhuurder</th>
                <th>Acties</th>
            </tr>
        </thead>
        <tbody>
            <?php if (isset($response['data'])) : ?>
                <?php foreach ($response['data'] as $row) : ?>
                    <tr data-id="<?php echo $row['id']; ?>">
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['autonaam']); ?></td>
                        <td><?php echo htmlspecialchars($row['type']); ?></td>
                        <td><?php echo htmlspecialchars($row['zitplaatsen']); ?></td>
                        <td><?php echo htmlspecialchars($row['prijs']); ?></td>
                        <td><?php echo htmlspecialchars($row['verhuurder']); ?></td>
                        <td>
                            <a href="car-huren.php?auto_id=<?php echo $row['id']; ?>">Huren</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="7">No records found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
<?php
ini_set('display_errors', '0');     // Don't display errors
error_reporting(E_ALL | E_STRICT);  // Report all errors

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
        $token = $idp->getToken();

        // Log the event of successful token generation
        error_log("Token generated successfully for user: $username");

        return $token;
    } catch (Exception $e) {
        // Log the error
        error_log('Error getting token: ' . $e->getMessage());
        die(json_encode(array("message" => "Error getting token")));
    }
}

// Function to validate content type
function validateContentType($contentType, $method)
{
    if ($method === 'GET') {
        return;
    }

    // List of valid content types
    $validContentTypes = ['application/json; charset=UTF-8'];
    // If content type is not valid, send 415 status code and exit
    if (!in_array($contentType, $validContentTypes)) {
        // Log the error
        error_log("Invalid content type: $contentType");
        http_response_code(415);
        header('Content-Type: application/json; charset=UTF-8');
        header("X-Content-Type-Options: nosniff");
        die(json_encode(array("message" => "Unsupported Media Type")));
    }
}

// Function to make cURL request
function curlRequest($url, $method, $data = null, $zoeken = null)
{
    try {
        // List of allowed HTTP methods
        $allowed_methods = array('GET');

        // If method is not allowed, send 405 status code and exit
        if (!in_array($method, $allowed_methods)) {
            http_response_code(405);
            header('Content-Type: application/json; charset=UTF-8');
            header("X-Content-Type-Options: nosniff");
            throw new Exception("Method not allowed");
            error_log("Method not allowed: " . $method); // Audit log
        }

        // Get token
        $token = getToken();

        // If zoeken parameter is provided, add it to the request
        if ($zoeken) {
            $url .= '?zoeken=' . urlencode($zoeken);
        }

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

        // If there was an error with the request, close cURL and throw an exception
        if ($response === false) {
            $error_msg = curl_error($ch);
            error_log("cURL error: " . $error_msg); // Audit log
            curl_close($ch);
            throw new Exception("cURL error: $error_msg");
            
        }

        // Validate response content type
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        validateContentType($contentType, $method);

        // Close cURL and return response
        curl_close($ch);
        return json_decode($response, true);
    } catch (Exception $e) {
        // Log the exception message and rethrow it
        error_log('Caught exception: ' . $e->getMessage());
        throw $e;
    }
}

// Retrieve zoeken parameter from form submission
$zoeken = isset($_GET['zoeken']) ? $_GET['zoeken'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Define URL and data for the request
    $url = "https://localhost/E-cars4U/microservices/getDataApi.php";
    $data = array(
        'username' => 'E-cars4U',
        'password' => '123',
        'zoeken' => $zoeken
    );
    // Make the request and get the response
    $response = curlRequest($url, 'GET', $data);
}
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
    <a href="index.php">Home</a>
    &nbsp; &nbsp; &nbsp;
    <a href="top-cheaper-cars.php">top 5 goedkoopste</a>
    <br><br>

    <form action="dashboard-anonymous.php">
        <input type="text" name="zoeken" placeholder="Zoeken..">
        <input type="submit" value="zoeken">
    </form>
    <br>

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
                    <tr data-id="<?php echo htmlspecialchars($row['id']); ?>">
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
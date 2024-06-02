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
    $allowed_methods = array('GET', 'POST');

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

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Define data for the request
    $data = array(
        'huren' => 'huren',
        'voornaam' => filter_input(INPUT_POST, 'voornaam', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
        'tussenvoegsel' => filter_input(INPUT_POST, 'tussenvoegsel', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
        'achternaam' => filter_input(INPUT_POST, 'achternaam', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
        'telefoonnummer' => filter_input(INPUT_POST, 'telefoonnummer', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
        'plaats' => filter_input(INPUT_POST, 'plaats', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
        'straat' => filter_input(INPUT_POST, 'straat', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
        'huisnummer' => filter_input(INPUT_POST, 'huisnummer', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
        'autoid' => filter_input(INPUT_POST, 'auto_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
        'username' => 'E-cars4U',
        'password' => '123'
    );

    // Define URL for the request
    $url = "https://localhost/E-cars4U/microservices/submitApi.php";

    // Make the request and get the response
    $response = curlRequest($url, 'POST', $data);

    // Redirect to prevent form resubmission on page refresh
    header("Location: dashboard-anonymous.php");
    exit();
}

// Define URL and data for the request
$url = "https://localhost/E-cars4U/microservices/getDataApi.php";
$data = array(
    'username' => 'E-cars4U',
    'password' => '123',
    'autoid' => filter_input(INPUT_GET, 'auto_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS)
);

// Make the request and get the response
$response = curlRequest($url, 'GET', $data);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beheer Reizen</title>
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0, 0, 0);
            background-color: rgba(0, 0, 0, 0.4);
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

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

                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="7">No records found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <br><br>
    <center>

        <form action="car-huren.php" method="post" id="hurenform">
            <input type="hidden" id="id" name="id">

            <label for="voornaam">Voornaam:</label><br>
            <input type="text" id="voornaam" name="voornaam" required><br>

            <label for="tussenvoegsel">Tussenvoegsel:</label><br>
            <input type="text" id="tussenvoegsel" name="tussenvoegsel" required><br>

            <label for="achternaam">Achternaam:</label><br>
            <input type="text" id="achternaam" name="achternaam" required><br>

            <label for="telefoonnummer">Telefoonnummer:</label><br>
            <input type="tel" id="telefoonnummer" name="telefoonnummer" required><br>

            <label for="plaats">Plaats:</label><br>
            <input type="text" id="plaats" name="plaats" required><br>

            <label for="straat">Straat:</label><br>
            <input type="text" id="straat" name="straat" required><br>

            <label for="huisnummer">Huisnummer:</label><br>
            <input type="number" id="huisnummer" name="huisnummer" required><br>

            <input type="hidden" name="auto_id" value="<?php echo $_GET['auto_id']; ?>">

            <button type="submit">Submit</button>
        </form>
    </center>
</body>

</html>
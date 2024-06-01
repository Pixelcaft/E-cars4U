<?php
include_once("IdP-map/IdP.php");

function getToken()
{
    $username = "E-cars4U";
    $password = "123";
    $credentials = array(
        'username' => $username,
        'password' => $password,
        'exp' => time() + (60 * 60)
    );

    $idp = new IdP($credentials);
    return $idp->getToken();
}

function curlRequest($url, $method, $data = null)
{
    $token = getToken();
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
    curl_setopt($ch, CURLOPT_USERPWD, "username:password");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer " . $token, "Content-Type: application/json"));
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);

    if ($response === false) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        die("cURL error: $error_msg");
    }

    curl_close($ch);
    return json_decode($response, true);
}

$url = "https://localhost/E-cars4U/microservices/getDataApi.php";
$data = array('username' => 'E-cars4U', 'password' => '123');
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
<table>
        <thead>
            <tr>
                <th>ID</th>
                <th>autoNaam</th>
                <th>Type</th>
                <th>Zitplaatsen</th>
                <th>Prijs</th>
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
                            <button onclick="editData(<?php echo $row['id']; ?>)">Edit</button>
                            <a href="dashboard-landlords.php?delete_id=<?php echo $row['id']; ?>">Delete</a>
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
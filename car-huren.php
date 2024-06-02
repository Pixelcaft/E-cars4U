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

// CRUD-acties
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = array(
        'huren' => 'huren',
        'voornaam' => $_POST['voornaam'],
        'tussenvoegsel' => $_POST['tussenvoegsel'],
        'achternaam' => $_POST['achternaam'],
        'telefoonnummer' => $_POST['telefoonnummer'],
        'plaats' => $_POST['plaats'],
        'straat' => $_POST['straat'],
        'huisnummer' => $_POST['huisnummer'],
        'autoid' => $_POST['auto_id'],
        'username' => 'E-cars4U',
        'password' => '123'
    );

    echo "dit de data ->";
    print_r($data);


    $url = "https://localhost/E-cars4U/microservices/submitApi.php";
    $response = curlRequest($url, 'POST', $data);


    // Redirect om te voorkomen dat het formulier opnieuw wordt verzonden bij paginavernieuwing
    header("Location: dashboard-anonymous.php");
    exit();
}

$url = "https://localhost/E-cars4U/microservices/getDataApi.php";
$data = array('username' => 'E-cars4U', 'password' => '123', 'autoid' => $_GET['auto_id']);
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



    <!-- <script>
        var modal = document.getElementById("myModal");
        var btn = document.getElementById("openModalBtn");
        var span = document.getElementsByClassName("close")[0];
        var form = document.getElementById("autoform");

        btn.onclick = function() {
            modal.style.display = "block";
        }

        span.onclick = function() {
            modal.style.display = "none";
            form.reset();
            document.getElementById('id').value = '';
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
                form.reset();
                document.getElementById('id').value = '';
            }
        }

        function editData(id) {
            var row = document.querySelector(`tr[data-id="${id}"]`);
            document.getElementById('id').value = row.children[0].innerText;
            document.getElementById('autonaam').value = row.children[1].innerText;
            document.getElementById('type').value = row.children[2].innerText;
            document.getElementById('zitplaatsen').value = row.children[3].innerText;
            document.getElementById('prijs').value = row.children[4].innerText;
            modal.style.display = "block";
        }
    </script> -->
</body>

</html>
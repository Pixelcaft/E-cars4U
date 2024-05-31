<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto's Beheren</title>
    <style>
        /* Stijl voor overlay */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            /* Halfdoorzichtig zwart */
            z-index: 999;
            /* Zorg ervoor dat de overlay bovenop alle andere elementen staat */
            display: none;
            /* Verberg de overlay standaard */
        }

        /* Stijl voor popup */
        .popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            z-index: 1000;
            /* Zorg ervoor dat de popup bovenop de overlay staat */
            display: none;
            /* Verberg de popup standaard */
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table,
        th,
        td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <table id="carData">
        <thead>
            <tr>
                <th>Auto</th>
                <th>Type</th>
                <th>Zitplaatsen</th>
                <th>Prijs</th>
                <th>Gehuurd</th>
                <th>Actie</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>

    <button onclick="openAddCarForm()">Auto Toevoegen</button>

    <!-- Overlay -->
    <div class="overlay" id="overlay"></div>

    <!-- Popup -->
    <div class="popup" id="popup">
        <h2>Auto Toevoegen</h2>
        <form id="AddCar" action="api/formopvangen.php">
            Auto naam: <input type="text" name="auto_naam"><br>
            Type: <input type="text" name="type"><br>
            Zitplaatsen: <input type="number" name="zitplaatsen"><br>
            Prijs: <input type="number" name="prijs"><br><br>
            <button type="button">Submit</button>
            <button type="button" onclick="closePopup()">Annuleren</button>
        </form>
    </div>


    <script>
        function openAddCarForm() {
            // Toon de overlay en popup
            document.getElementById("overlay").style.display = "block";
            document.getElementById("popup").style.display = "block";
        }

        function closePopup() {
            // Verberg de overlay en popup
            document.getElementById("overlay").style.display = "none";
            document.getElementById("popup").style.display = "none";
        }

        function addCar() {

            closePopup();
        }
    </script>
</body>

</html>
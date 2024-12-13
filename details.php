<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $conn = new PDO('mysql:host=143.47.179.70;port=443;dbname=db1', 'user1', 'user1');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id']; // ID du modèle (idModeleIA)

    // Requête pour récupérer les détails du modèle
    $sql = "SELECT * FROM modeleia WHERE IdModeleIA = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $modele = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($modele) {
        echo "<h2>Détails du Modèle</h2>";
        echo "<p><strong>Nom :</strong> " . htmlspecialchars($modele['Nom']) . "</p>";

        // Requêtes pour les performances avant
        $sqlPerfBefore = "SELECT pb.* 
                          FROM perfbefore pb
                          JOIN classressource cr ON pb.idRessource = cr.idRessource
                          WHERE cr.idModeleIA = :id";
        $stmtBefore = $conn->prepare($sqlPerfBefore);
        $stmtBefore->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtBefore->execute();
        $perfBefore = $stmtBefore->fetchAll(PDO::FETCH_ASSOC);

        // Requêtes pour les performances après
        $sqlPerfAfter = "SELECT pa.* 
                         FROM perfafter pa
                         JOIN classperfafter cpa ON pa.idPerfAfter = cpa.idPerfAfter
                         JOIN ressourceutilisée ru ON cpa.idRessource = ru.idRessource
                         WHERE pa.idModeleIA = :id";
        $stmtAfter = $conn->prepare($sqlPerfAfter);
        $stmtAfter->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtAfter->execute();
        $perfAfter = $stmtAfter->fetchAll(PDO::FETCH_ASSOC);

        // Afficher les détails des performances avant
        if (!empty($perfBefore)) {
            echo "<h3>Performances Avant</h3>";
            echo "<table border='1'>";
            echo "<tr>";
            foreach (array_keys($perfBefore[0]) as $column) {
                if (!in_array($column, ['idPerfBefore', 'idRessource'])) { // Exclure les ID
                    echo "<th>" . htmlspecialchars($column) . "</th>";
                }
            }
            echo "</tr>";
            foreach ($perfBefore as $row) {
                echo "<tr>";
                foreach ($row as $key => $value) {
                    if (!in_array($key, ['idPerfBefore', 'idRessource'])) { // Exclure les ID
                        echo "<td>" . htmlspecialchars($value) . "</td>";
                    }
                }
                echo "</tr>";
            }
            echo "</table>";
        }

        echo "<div class='spacer'></div>";

        // Afficher les détails des performances après
        if (!empty($perfAfter)) {
            echo "<h3>Performances Après</h3>";
            echo "<table border='1'>";
            echo "<tr>";
            foreach (array_keys($perfAfter[0]) as $column) {
                if (!in_array($column, ['idPerfAfter', 'idModeleIA'])) { // Exclure les ID
                    echo "<th>" . htmlspecialchars($column) . "</th>";
                }
            }
            echo "</tr>";
            foreach ($perfAfter as $row) {
                echo "<tr>";
                foreach ($row as $key => $value) {
                    if (!in_array($key, ['idPerfAfter', 'idModeleIA'])) { // Exclure les ID
                        echo "<td>" . htmlspecialchars($value) . "</td>";
                    }
                }
                echo "</tr>";
            }
            echo "</table>";
        }

        echo "<div class='spacer'></div>";

        // Création des graphiques pour chaque métrique
        $categories = ['FPS', 'Utilisation_mémoire', 'Emission_CO2', 'Précision', 'Taille', 'FLOP'];
        foreach ($categories as $metric) {
            $valueBefore = !empty($perfBefore[0][$metric]) ? $perfBefore[0][$metric] : 0;
            $valueAfter = !empty($perfAfter[0][$metric]) ? $perfAfter[0][$metric] : 0;

            echo "
            <h3>Comparaison pour $metric</h3>
            <div class='chart-container'>
                <canvas id='chart_$metric' width='400' height='200'></canvas>
            </div>
            <script src='https://cdn.jsdelivr.net/npm/chart.js'></script>
            <script>
                const ctx_$metric = document.getElementById('chart_$metric').getContext('2d');
                new Chart(ctx_$metric, {
                    type: 'bar',
                    data: {
                        labels: ['Avant', 'Après'],
                        datasets: [{
                            label: '$metric',
                            data: [$valueBefore, $valueAfter],
                            backgroundColor: ['rgba(255, 99, 132, 0.2)', 'rgba(54, 162, 235, 0.2)'],
                            borderColor: ['rgba(255, 99, 132, 1)', 'rgba(54, 162, 235, 1)'],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            </script>
            ";
        }
    } else {
        echo "<p>Aucun modèle trouvé avec cet ID.</p>";
    }
} else {
    echo "<p>Erreur : ID non spécifié.</p>";
}
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du Modèle</title>
    <style>
        body {
            background-color: black; /* Fond noir */
            color: white; /* Texte en blanc */
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin: 20px 0;
            color: white;
        }
        th, td {
            border: 1px solid #444;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #333;
        }
        tr:nth-child(even) {
            background-color: #222;
        }
        .spacer {
            margin: 40px 0; /* Espacement entre sections */
        }
        .chart-container {
            margin: 20px auto;
            padding: 10px;
            border: 2px solid white; /* Bordure blanche */
            width: 60%;
            height: 300px;
        }
    </style>
</head>

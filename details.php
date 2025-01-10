


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

$id = isset($_GET['id']) ? (int)$_GET['id'] : null; // ID du modèle
    $idRessource = isset($_GET['ressource']) ? (int)$_GET['ressource'] : null;
    $compression = isset($_GET['compression']) ? trim($_GET['compression']) : null;

    // Si le type de compression est absent, demander à l'utilisateur
    if (empty($compression)) {
        echo "<div class='compression-container'>";
        echo "<h2>Choisissez un type de compression pour afficher les détails :</h2>";
        echo "<form method='GET' action='details.php'>";
        echo "<input type='hidden' name='id' value='$id'>";
        echo "<input type='hidden' name='ressource' value='$idRessource'>";
        echo "<select name='compression' required>";
        echo "<option value=''>-- Sélectionnez un type --</option>";
        echo "<option value='pruning'>Pruning</option>";
        echo "<option value='kd'>Knowledge Distillation</option>";
        echo "<option value='quantization'>Quantization</option>";
        echo "</select>";
        echo "<button type='submit'>Afficher les détails</button>";
        echo "</form>";
        // Ajout du bouton "Retour"
        echo "<button onclick='history.back();' style='margin-top: 20px; background-color: #444; color: white; border: none; padding: 10px 20px; cursor: pointer;'>Retour</button>";

        echo "</div>";
        exit;
    }


if (isset($_GET['id'])) {

    // Utilisation directe de la compression si fournie
    echo "<h2>Compression sélectionnée : " . htmlspecialchars($compression) . "</h2>";

    // Requête pour récupérer les détails du modèle
    $sql = "SELECT * FROM modeleia WHERE IdModeleIA = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $modele = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($modele) {
        // Bouton retour en haut à droite
        echo "<button onclick='window.history.back();' style='position: fixed; top: 10px; right: 10px; background-color: #444; color: white; border: none; padding: 10px 20px; cursor: pointer;'>Retour</button>";

        echo "<div class='container'>";
        echo "<h2>Détails du Modèle</h2>";
        echo "<p><strong>Nom :</strong> " . htmlspecialchars($modele['Nom']) . "</p>";

        // Requête pour récupérer la ressource associée
        $sqlRessource = "SELECT r.* FROM ressourceutilisée r
                         JOIN classressource cr ON r.idRessource = cr.idRessource
                         WHERE cr.idModeleIA = :id";
        $stmtRessource = $conn->prepare($sqlRessource);
        $stmtRessource->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtRessource->execute();
        $ressource = $stmtRessource->fetch(PDO::FETCH_ASSOC);

        // Requête spécifique au type de compression sélectionné
        if ($compression === 'pruning') {
            $sqlCompression = "SELECT p.Methode, p.Taux_de_compression, p.Type FROM class_pruning cp
                               JOIN pruning p ON cp.idPruning = p.IdPruning
                               WHERE cp.idModele = :id";
        } elseif ($compression === 'kd') {
            $sqlCompression = "SELECT k.Alpha, k.Température FROM class_kd ck
                               JOIN kd k ON ck.idKD = k.idKD
                               WHERE ck.idModele = :id";
        } elseif ($compression === 'quantization') {
            $sqlCompression = "SELECT q.Méthode AS Methode, q.Nombre_de_bits AS Bits FROM class_quantization cq
                               JOIN quantization q ON cq.idQuantization = q.idQuantization
                               WHERE cq.idModele = :id";
        } else {
            die("<p>Type de compression invalide.</p>");
        }

        $stmtCompression = $conn->prepare($sqlCompression);
        $stmtCompression->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtCompression->execute();
        $compressionDetails = $stmtCompression->fetchAll(PDO::FETCH_ASSOC);

        // Afficher les détails des performances avant et après
        $sqlPerfBefore = "SELECT pb.* 
                          FROM perfbefore pb
                          JOIN classressource cr ON pb.idRessource = cr.idRessource
                          WHERE cr.idModeleIA = :id";
        $stmtBefore = $conn->prepare($sqlPerfBefore);
        $stmtBefore->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtBefore->execute();
        $perfBefore = $stmtBefore->fetchAll(PDO::FETCH_ASSOC);

        $sqlPerfAfter = "SELECT pa.* 
                         FROM perfafter pa
                         JOIN classperfafter cpa ON pa.idPerfAfter = cpa.idPerfAfter
                         WHERE pa.idModeleIA = :id";
        $stmtAfter = $conn->prepare($sqlPerfAfter);
        $stmtAfter->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtAfter->execute();
        $perfAfter = $stmtAfter->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($compressionDetails)) {
            echo "<h3>Détails pour la compression : " . htmlspecialchars($compression) . "</h3>";
            echo "<table>";
            echo "<thead><tr>";
            foreach (array_keys($compressionDetails[0]) as $column) {
                echo "<th>" . htmlspecialchars($column) . "</th>";
            }
            echo "</tr></thead><tbody>";
            foreach ($compressionDetails as $row) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<p>Aucun détail trouvé pour ce type de compression.</p>";
        }

        echo "<div class='spacer'></div>";

        // Comparer les performances avant et après avec des graphiques
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

        echo "</div>";
    } else {
        echo "<p>Aucun modèle trouvé avec cet ID.</p>";
    }
} else {
    echo "<p>Erreur : ID non spécifié.</p>";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du Modèle</title>
    <!-- Inclure le fichier CSS externe -->
    <link rel="stylesheet" href="style.css">
</head>
<body>
</body>
</html>


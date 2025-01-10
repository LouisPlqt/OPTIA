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

// Récupération des critères de recherche
$modele = isset($_GET['modele']) ? trim($_GET['modele']) : '';
$ressource = isset($_GET['ressource']) ? trim($_GET['ressource']) : '';
$tache = isset($_GET['tache']) ? trim($_GET['tache']) : '';
$compression = isset($_GET['compression']) ? trim($_GET['compression']) : '';

// Initialisation de la requête SQL
$sql = "SELECT m.IdModeleIA, m.Nom AS Modele, r.idRessource, r.Nom AS Ressource, t.Nom AS Tache, r.CPU, r.GPU, r.Mémoire";

if ($compression === 'pruning') {
    $sql .= ", p.Methode AS Methode, p.Taux_de_compression AS Taux, p.Type AS Type";
} elseif ($compression === 'kd') {
    $sql .= ", k.Alpha AS Alpha, k.Température AS Temp";
} elseif ($compression === 'quantization') {
    $sql .= ", q.Méthode AS MethodeQuant, q.Nombre_de_bits AS Bits";
}

$sql .= " FROM modeleia m
          LEFT JOIN tache t ON m.id_tache = t.id_tache
          LEFT JOIN classressource cr ON m.IdModeleIA = cr.idModeleIA
          LEFT JOIN ressourceutilisée r ON cr.idRessource = r.idRessource";

if ($compression === 'pruning') {
    $sql .= " LEFT JOIN class_pruning cp ON m.IdModeleIA = cp.idModele
               LEFT JOIN pruning p ON cp.idPruning = p.IdPruning";
} elseif ($compression === 'kd') {
    $sql .= " LEFT JOIN class_kd ck ON m.IdModeleIA = ck.idModele
               LEFT JOIN kd k ON ck.idKD = k.idKD";
} elseif ($compression === 'quantization') {
    $sql .= " LEFT JOIN class_quantization cq ON m.IdModeleIA = cq.idModele
               LEFT JOIN quantization q ON cq.idQuantization = q.idQuantization";
}

// Conditions dynamiques
$conditions = [];
$params = [];

if ($modele !== '') {
    $conditions[] = "m.Nom LIKE :modele";
    $params[':modele'] = '%' . $modele . '%';
}

if ($ressource !== '') {
    $conditions[] = "r.Nom LIKE :ressource";
    $params[':ressource'] = '%' . $ressource . '%';
}

if ($tache !== '') {
    $conditions[] = "t.Nom LIKE :tache";
    $params[':tache'] = '%' . $tache . '%';
}

if ($compression !== '') {
    if ($compression === 'pruning') {
        $conditions[] = "p.Methode IS NOT NULL";
    } elseif ($compression === 'kd') {
        $conditions[] = "k.Alpha IS NOT NULL";
    } elseif ($compression === 'quantization') {
        $conditions[] = "q.Méthode IS NOT NULL";
    }
}

// Ajout des conditions WHERE
if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
} else {
    die("<p>Veuillez renseigner au moins un critère de recherche.</p>");
}

$stmt = $conn->prepare($sql);

try {
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur lors de l'exécution de la requête : " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats de la recherche</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background-color: #121212;
            color: #ffffff;
            font-family: 'Roboto', sans-serif;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        h1, h2, h3 {
            color: rgb(255, 255, 255);
        }

        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #444;
        }

        th {
            background-color: #333;
        }

        tr:nth-child(even) {
            background-color: #2c2c2c;
        }

        tr:hover {
            background-color: #444;
        }

        a {
            color: rgb(255, 255, 255);
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .btn-return {
            position: fixed;
            top: 10px;
            right: 10px;
            background-color: #444;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
        }

        .btn-return:hover {
            background-color: #666;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>Résultats de la recherche</h1>

        <?php
        if (count($results) === 1 && !empty($modele) && !empty($ressource) && !empty($tache)) {
            $idModele = $results[0]['IdModeleIA'];
            header("Location: details.php?id=$idModele");
            exit;
        }

        if (count($results) > 0) {
            echo "<table>";
            echo "<tr>";

            if ($modele === '') {
                echo "<th>Modèle</th>";
            }
            if ($ressource === '') {
                echo "<th>Ressource</th>";
            }
            if ($tache === '') {
                echo "<th>Tâche</th>";
            }

            echo "<th>CPU (GHz)</th><th>GPU (Nombre de cœurs)</th><th>Mémoire (Go)</th>";

            if ($compression === 'pruning') {
                echo "<th>Méthode de Pruning</th><th>Taux de Compression</th><th>Type</th>";
            } elseif ($compression === 'kd') {
                echo "<th>Alpha</th><th>Température</th>";
            } elseif ($compression === 'quantization') {
                echo "<th>Méthode de Quantization</th><th>Nombre de Bits</th>";
            }

            echo "</tr>";

            foreach ($results as $result) {
                echo "<tr>";
                if ($modele === '') {
                    echo "<td><a href='details.php?id=" . htmlspecialchars($result['IdModeleIA'] ?? '') . "&compression=" . urlencode($compression) . "'>" . htmlspecialchars($result['Modele'] ?? '') . "</a></td>";
                }
                if ($ressource === '') {
                    echo "<td><a href='details.php?id=" . htmlspecialchars($result['IdModeleIA'] ?? '') . "&ressource=" . htmlspecialchars($result['idRessource'] ?? '') . "&compression=" . urlencode($compression) . "'>" . htmlspecialchars($result['Ressource'] ?? '') . "</a></td>";
                }
                if ($tache === '') {
                    echo "<td>" . htmlspecialchars($result['Tache'] ?? '') . "</td>";
                }
                echo "<td>" . htmlspecialchars($result['CPU'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($result['GPU'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($result['Mémoire'] ?? '') . "</td>";

                if ($compression === 'pruning') {
                    echo "<td>" . htmlspecialchars($result['Methode'] ?? '') . "</td>";
                    echo "<td>" . htmlspecialchars($result['Taux'] ?? '') . "</td>";
                    echo "<td>" . htmlspecialchars($result['Type'] ?? '') . "</td>";
                } elseif ($compression === 'kd') {
                    echo "<td>" . htmlspecialchars($result['Alpha'] ?? '') . "</td>";
                    echo "<td>" . htmlspecialchars($result['Temp'] ?? '') . "</td>";
                } elseif ($compression === 'quantization') {
                    echo "<td>" . htmlspecialchars($result['MethodeQuant'] ?? '') . "</td>";
                    echo "<td>" . htmlspecialchars($result['Bits'] ?? '') . "</td>";
                }

                echo "</tr>";
            }

            echo "</table>";
        } else {
            echo "<p>Aucun résultat trouvé pour les critères spécifiés.</p>";
        }
        ?>

        <button class="btn-return" onclick="window.history.back();">Retour</button>
    </div>

</body>
</html>

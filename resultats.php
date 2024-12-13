<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $conn = new PDO('mysql:host=143.47.179.70;port=443;dbname=db1', 'user1', 'user1');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("<div class='error'>Erreur de connexion : " . htmlspecialchars($e->getMessage()) . "</div>");
}

// Récupération des critères de recherche
$modele = isset($_GET['modele']) ? trim($_GET['modele']) : '';
$ressource = isset($_GET['ressource']) ? trim($_GET['ressource']) : '';
$tache = isset($_GET['tache']) ? trim($_GET['tache']) : '';

// Construction de la requête SQL en fonction des critères renseignés
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

if (count($conditions) > 0) {
    $sql = "SELECT m.IdModeleIA, m.Nom AS Modele, r.idRessource, r.Nom AS Ressource, t.Nom AS Tache, r.CPU, r.GPU, r.Mémoire
            FROM modeleia m
            LEFT JOIN tache t ON m.id_tache = t.id_tache
            LEFT JOIN classressource cr ON m.IdModeleIA = cr.idModeleIA
            LEFT JOIN ressourceutilisée r ON cr.idRessource = r.idRessource
            WHERE " . implode(' AND ', $conditions);
} else {
    die("<div class='error'>Veuillez renseigner au moins un critère de recherche.</div>");
}

$stmt = $conn->prepare($sql);

try {
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<div class='error'>Erreur lors de l'exécution de la requête : " . htmlspecialchars($e->getMessage()) . "</div>");
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats de la recherche</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
        font-family: Arial, sans-serif;
        background-color: #121212;
        color: #ffffff;
        margin: 0;
        padding: 0;
        }

        .container {
        width: 80%;
        margin: auto;
        padding: 20px;
        }

        h1, h2 {
    text-align: center; 
    color:rgb(255, 255, 255);
}

table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
    background-color: #1e1e1e;
    color: #ffffff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.7);
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
            color:rgb(255, 255, 255);
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
        if (count($results) > 0) {
            // Afficher un titre spécifique si la recherche est uniquement par modèle
            if (!empty($modele) && empty($ressource)) {
                echo "<h2>Modèle recherché : " . htmlspecialchars($modele) . "</h2>";
                echo "<h3>Voici les ressources capables de faire tourner le modèle :</h3>";
            }

            // Afficher un titre spécifique si la recherche est uniquement par ressource
            if (!empty($ressource) && empty($modele) && empty($tache)) {
                echo "<h2>Ressource recherchée : " . htmlspecialchars($ressource) . "</h2>";
                echo "<h3>Voici les modèles pouvant être exécutés sur la ressource entrée :</h3>";
            }

            // Afficher un titre spécifique si la recherche combine ressource et tâche
            if (!empty($ressource) && !empty($tache) && empty($modele)) {
                echo "<h2>Ressource recherchée : " . htmlspecialchars($ressource) . "</h2>";
                echo "<h2>Tâche recherchée : " . htmlspecialchars($tache) . "</h2>";
                echo "<h3>Voici les modèles compatibles avec la tâche et la ressource spécifiées :</h3>";
            }

            // Afficher un titre spécifique si la recherche est uniquement par tâche
            if (!empty($tache) && empty($modele) && empty($ressource)) {
                echo "<h2>Tâche recherchée : " . htmlspecialchars($tache) . "</h2>";
                echo "<h3>Voici les modèles et les ressources associés à cette tâche :</h3>";
            }
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats de la recherche</title>
    <style>
        body {
            background-color: #121212;
            color: #f5f5f5;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        h2, h3 {
            color: #4CAF50;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table, th, td {
            border: 1px solid #4CAF50;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #2e2e2e;
        }
        a {
            color: #4CAF50;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        .button {
            position: fixed;
            top: 10px;
            right: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
<?php
if (count($results) > 0) {
    echo "<h2>Résultats de la recherche :</h2>";

    // Affichage des messages spécifiques selon les critères
    if (!empty($modele) && empty($ressource)) {
        echo "<h3>Modèle recherché : " . htmlspecialchars($modele) . "</h3>";
        echo "<h3>Voici les ressources capables de faire tourner le modèle :</h3>";
    } elseif (!empty($ressource) && empty($modele) && empty($tache)) {
        echo "<h3>Ressource recherchée : " . htmlspecialchars($ressource) . "</h3>";
        echo "<h3>Voici les modèles pouvant être exécutés sur cette ressource :</h3>";
    } elseif (!empty($ressource) && !empty($tache) && empty($modele)) {
        echo "<h3>Ressource recherchée : " . htmlspecialchars($ressource) . "</h3>";
        echo "<h3>Tâche recherchée : " . htmlspecialchars($tache) . "</h3>";
        echo "<h3>Voici les modèles compatibles :</h3>";
    } elseif (!empty($tache) && empty($modele) && empty($ressource)) {
        echo "<h3>Tâche recherchée : " . htmlspecialchars($tache) . "</h3>";
        echo "<h3>Voici les modèles et ressources associés :</h3>";
    }

            echo "<table>";
            echo "<tr>";
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

            echo "<th>CPU</th><th>GPU</th><th>Mémoire</th>";
            echo "</tr>";

            foreach ($results as $result) {
                echo "<tr>";
                if ($modele === '') {
                    // Lien vers la page détails pour le modèle
                    echo "<td><a href='details.php?id=" . htmlspecialchars($result['IdModeleIA']) . "'>" . htmlspecialchars($result['Modele']) . "</a></td>";
                }
                if ($ressource === '') {
                    // Lien vers la page détails pour la ressource
                    echo "<td><a href='details.php?id=" . htmlspecialchars($result['idRessource']) . "'>" . htmlspecialchars($result['Ressource']) . "</a></td>";
                }
                if ($tache === '') {
                    echo "<td>" . htmlspecialchars($result['Tache']) . "</td>";
                }
                echo "<td>" . htmlspecialchars($result['CPU']) . "</td>";
                echo "<td>" . htmlspecialchars($result['GPU']) . "</td>";
                echo "<td>" . htmlspecialchars($result['Mémoire']) . "</td>";
                echo "</tr>";
            }
    foreach ($results as $result) {
        echo "<tr>";
        if ($modele === '') {
            echo "<td><a href='details.php?id=" . htmlspecialchars($result['IdModeleIA']) . "'>" . htmlspecialchars($result['Modele']) . "</a></td>";
        }
        if ($ressource === '') {
            echo "<td><a href='details.php?id=" . htmlspecialchars($result['idRessource']) . "'>" . htmlspecialchars($result['Ressource']) . "</a></td>";
        }
        if ($tache === '') {
            echo "<td>" . htmlspecialchars($result['Tache']) . "</td>";
        }
        echo "<td>" . htmlspecialchars($result['CPU']) . "</td>";
        echo "<td>" . htmlspecialchars($result['GPU']) . "</td>";
        echo "<td>" . htmlspecialchars($result['Mémoire']) . "</td>";
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
    echo "</table>";
} else {
    echo "<div class='error'>Aucun résultat trouvé pour les critères spécifiés.</div>";
}
?>
<button class="button" onclick="window.history.back();">Retour</button>
</body>
</html>

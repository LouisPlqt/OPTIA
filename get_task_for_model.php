<?php
// Définir le type de contenu renvoyé par le script
header('Content-Type: application/json');

// Vérifier si le paramètre 'model' est présent
if (isset($_GET['model'])) {
    $modelName = trim($_GET['model']);

    try {
        // Connexion à la base de données
        $conn = new PDO('mysql:host=143.47.179.70;port=443;dbname=db1;charset=utf8', 'user1', 'user1');
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Requête pour récupérer la tâche associée au modèle
        $stmt = $conn->prepare("
            SELECT t.id_tache, t.Nom AS task 
            FROM modeleia m 
            LEFT JOIN tache t ON m.id_tache = t.id_tache 
            WHERE m.Nom = :model 
            LIMIT 1
        ");
        $stmt->execute([':model' => $modelName]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            // Renvoyer l'ID et le nom de la tâche associés
            echo json_encode([
                'taskId' => $result['id_tache'],
                'taskName' => $result['task']
            ]);
        } else {
            // Si aucun résultat trouvé, renvoyer null
            echo json_encode([
                'taskId' => null,
                'taskName' => null
            ]);
        }
    } catch (PDOException $e) {
        // Gérer les erreurs de connexion ou d'exécution
        echo json_encode(['error' => 'Erreur de connexion : ' . $e->getMessage()]);
    }
} else {
    // Renvoyer une erreur si le paramètre 'model' est absent
    echo json_encode(['error' => 'Paramètre "model" manquant']);
}
?>

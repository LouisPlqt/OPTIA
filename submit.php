<?php
header('Content-Type: application/json');

try {
    // Connexion à la base de données
    $pdo = new PDO('mysql:host=143.47.179.70;port=443;dbname=db1', 'user1', 'user1');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupération des données envoyées
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['organisation']) && !empty(trim($input['organisation']))) {
        $organisation = trim($input['organisation']);

        // Vérifie si l'organisation existe déjà
        $query = $pdo->prepare("SELECT compteur FROM organisation WHERE NomOrganisation = :organisation");
        $query->execute(['organisation' => $organisation]);
        $result = $query->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            // Si l'organisation existe, incrémente le compteur
            $update = $pdo->prepare("UPDATE organisation SET compteur = compteur + 1 WHERE NomOrganisation = :organisation");
            $update->execute(['organisation' => $organisation]);
            echo json_encode(['success' => true, 'message' => 'Compteur incrémenté.']);
        } else {
            // Sinon, insère une nouvelle entrée
            $insert = $pdo->prepare("INSERT INTO organisation (NomOrganisation, compteur) VALUES (:organisation, 1)");
            $insert->execute(['organisation' => $organisation]);
            echo json_encode(['success' => true, 'message' => 'Organisation ajoutée.']);
        }
    } else {
        // Si aucune organisation n'a été envoyée
        echo json_encode(['success' => false, 'message' => 'Organisation non valide.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
}
?>

<?php
// ==================================================
// FICHIER: process.php
// TRAITEMENT DES INSCRIPTIONS ET ENVOI D'EMAILS
// ==================================================

// CONFIGURATION - À MODIFIER AVANT UTILISATION
// --------------------------------------------

/**
 * 1. CONFIGURATION DE LA BASE DE DONNÉES
 * 
 * Remplacez ces valeurs par les informations fournies par votre hébergeur
 * 
 * $db_host : Adresse du serveur MySQL (généralement "localhost")
 * $db_name : Nom de votre base de données
 * $db_user : Nom d'utilisateur MySQL
 * $db_pass : Mot de passe MySQL
 */
$db_host = "localhost";
$db_name = "successplus_db";
$db_user = "votre_utilisateur";
$db_pass = "votre_mot_de_passe";

/**
 * 2. CONFIGURATION DES EMAILS
 * 
 * $admin_email : Votre adresse email (où vous recevrez les notifications)
 * $site_email : L'adresse email qui enverra les confirmations
 */
$admin_email = "admin@votredomaine.com";
$site_email = "contact@successplus.org";

/**
 * 3. CONFIGURATION SMTP (optionnel mais recommandé)
 * 
 * Si votre hébergeur nécessite une configuration SMTP spécifique,
 * décommentez et modifiez ces lignes
 */
// ini_set('SMTP', 'smtp.votredomaine.com'); // Serveur SMTP
// ini_set('smtp_port', 587);                // Port SMTP (587 pour TLS, 465 pour SSL)
// ini_set('sendmail_from', $site_email);    // Email d'envoi

// CONNEXION À LA BASE DE DONNÉES
// ------------------------------
try {
    // Création de la connexion PDO
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    
    // Configuration des options PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
} catch (PDOException $e) {
    // En cas d'erreur de connexion
    sendJsonResponse(false, 'Erreur de connexion à la base de données: ' . $e->getMessage());
}

// TRAITEMENT DU FORMULAIRE
// ------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et validation des données
    $nom = cleanInput($_POST['nom'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $telephone = cleanInput($_POST['telephone'] ?? '');
    $formation = cleanInput($_POST['formation'] ?? '');
    $message = cleanInput($_POST['message'] ?? '');
    
    // Validation des données obligatoires
    $errors = [];
    if (empty($nom)) $errors[] = "Le nom est obligatoire";
    if (empty($email)) $errors[] = "L'email est obligatoire";
    if (empty($telephone)) $errors[] = "Le téléphone est obligatoire";
    if (empty($formation)) $errors[] = "La formation est obligatoire";
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email est invalide";
    }
    
    if (count($errors) > 0) {
        sendJsonResponse(false, implode("<br>", $errors));
    }
    
    // Insertion dans la base de données
    try {
        // Préparation de la requête SQL
        $stmt = $pdo->prepare("
            INSERT INTO inscriptions (nom, email, telephone, formation, message, date_inscription) 
            VALUES (:nom, :email, :telephone, :formation, :message, NOW())
        ");
        
        // Liaison des paramètres
        $stmt->bindParam(':nom', $nom, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':telephone', $telephone, PDO::PARAM_STR);
        $stmt->bindParam(':formation', $formation, PDO::PARAM_STR);
        $stmt->bindParam(':message', $message, PDO::PARAM_STR);
        
        // Exécution de la requête
        $stmt->execute();
        
        // Récupération de l'ID de la nouvelle inscription
        $inscription_id = $pdo->lastInsertId();
        
    } catch (PDOException $e) {
        // En cas d'erreur SQL
        sendJsonResponse(false, 'Erreur lors de la sauvegarde en base de données: ' . $e->getMessage());
    }
    
    // ENVOI DES EMAILS
    // ----------------
    
    // 1. Email à l'administrateur
    $email_admin_sent = sendAdminEmail($admin_email, $site_email, $inscription_id, $nom, $email, $telephone, $formation, $message);
    
    // 2. Email de confirmation à l'utilisateur
    $email_user_sent = sendUserConfirmationEmail($email, $site_email, $inscription_id, $nom, $formation);
    
    // RÉPONSE AU FRONTEND
    // -------------------
    if ($inscription_id && $email_admin_sent && $email_user_sent) {
        sendJsonResponse(true, 'Votre inscription a été enregistrée avec succès! Vous allez recevoir un email de confirmation.');
    } elseif ($inscription_id) {
        sendJsonResponse(true, 'Votre inscription a été enregistrée mais un problème est survenu avec l\'envoi des emails de confirmation.');
    } else {
        sendJsonResponse(false, 'Une erreur est survenue lors du traitement de votre demande. Veuillez réessayer.');
    }
    
} else {
    // Si quelqu'un accède à ce fichier directement
    sendJsonResponse(false, 'Méthode non autorisée');
}

// FONCTIONS UTILITAIRES
// ---------------------

/**
 * Nettoie les entrées utilisateur
 */
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Envoie une réponse JSON
 */
function sendJsonResponse($success, $message) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit;
}

/**
 * Envoie l'email à l'administrateur
 */
function sendAdminEmail($admin_email, $site_email, $inscription_id, $nom, $email, $telephone, $formation, $message) {
    $subject_admin = "Nouvelle inscription à la formation $formation (ID: $inscription_id)";
    
    $message_admin = "
    <html>
    <head>
      <title>Nouvelle inscription</title>
      <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; }
        .header { background-color: #FF6B00; color: white; padding: 10px; text-align: center; }
        .content { padding: 20px; }
        .info { margin-bottom: 10px; }
        .label { font-weight: bold; color: #FF6B00; }
      </style>
    </head>
    <body>
      <div class='container'>
        <div class='header'>
          <h2>Nouvelle inscription SUCCESS+</h2>
        </div>
        <div class='content'>
          <p>Une nouvelle inscription a été reçue pour la formation :</p>
          <h3>$formation</h3>
          
          <div class='info'>
            <span class='label'>ID Inscription :</span> #$inscription_id
          </div>
          <div class='info'>
            <span class='label'>Nom :</span> $nom
          </div>
          <div class='info'>
            <span class='label'>Email :</span> $email
          </div>
          <div class='info'>
            <span class='label'>Téléphone :</span> $telephone
          </div>
          <div class='info'>
            <span class='label'>Message :</span> $message
          </div>
          <div class='info'>
            <span class='label'>Date d'inscription :</span> ".date('d/m/Y H:i')."
          </div>
        </div>
      </div>
    </body>
    </html>
    ";
    
    // En-têtes pour l'email HTML
    $headers_admin = "MIME-Version: 1.0\r\n";
    $headers_admin .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers_admin .= "From: SUCCESS+ <$site_email>\r\n";
    $headers_admin .= "Reply-To: $site_email\r\n";
    
    // Envoi de l'email à l'admin
    return mail($admin_email, $subject_admin, $message_admin, $headers_admin);
}

/**
 * Envoie l'email de confirmation à l'utilisateur
 */
function sendUserConfirmationEmail($user_email, $site_email, $inscription_id, $nom, $formation) {
    $subject_user = "Confirmation d'inscription à SUCCESS+";
    
    $message_user = "
    <html>
    <head>
      <title>Confirmation d'inscription</title>
      <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; }
        .header { background-color: #28A745; color: white; padding: 10px; text-align: center; }
        .content { padding: 20px; }
        .info { margin-bottom: 10px; }
        .label { font-weight: bold; color: #28A745; }
        .cta { background-color: #FFC107; color: #333; padding: 10px 20px; text-align: center; border-radius: 5px; margin: 20px 0; }
      </style>
    </head>
    <body>
      <div class='container'>
        <div class='header'>
          <h2>Confirmation d'inscription</h2>
        </div>
        <div class='content'>
          <p>Bonjour $nom,</p>
          <p>Nous avons bien reçu votre inscription pour la formation :</p>
          <h3>$formation</h3>
          
          <p>Votre demande a été enregistrée avec succès. Voici les détails de votre inscription :</p>
          
          <div class='info'>
            <span class='label'>ID Inscription :</span> #$inscription_id
          </div>
          <div class='info'>
            <span class='label'>Date d'inscription :</span> ".date('d/m/Y H:i')."
          </div>
          
          <p>Notre équipe va examiner votre demande et vous contactera très prochainement pour la suite de votre parcours.</p>
          
          <div class='cta'>
            <p>Prochaine étape : Préparer votre projet</p>
          </div>
          
          <p>En attendant, nous vous invitons à :</p>
          <ul>
            <li>Préciser vos objectifs pour cette formation</li>
            <li>Réfléchir aux questions que vous souhaitez poser à nos formateurs</li>
            <li>Consulter les ressources préparatoires sur notre site</li>
          </ul>
          
          <p>Pour toute question, vous pouvez répondre directement à cet email.</p>
          
          <p>Merci de votre confiance,<br>
          <strong>L'équipe SUCCESS+</strong></p>
        </div>
      </div>
    </body>
    </html>
    ";
    
    // En-têtes pour l'email HTML
    $headers_user = "MIME-Version: 1.0\r\n";
    $headers_user .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers_user .= "From: SUCCESS+ <$site_email>\r\n";
    $headers_user .= "Reply-To: $site_email\r\n";
    
    // Envoi de l'email à l'utilisateur
    return mail($user_email, $subject_user, $message_user, $headers_user);
}
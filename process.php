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
 */
$db_host = "localhost";
$db_name = "successplus_db";
$db_user = "votre_utilisateur";
$db_pass = "votre_mot_de_passe";

/**
 * 2. CONFIGURATION DES EMAILS
 */
$admin_email = "admin@votredomaine.com";
$site_email = "contact@successplus.org";

/**
 * 3. CONFIGURATION SMTP (optionnel mais recommandé)
 */
// ini_set('SMTP', 'smtp.votredomaine.com');
// ini_set('smtp_port', 587);
// ini_set('sendmail_from', $site_email);

// CONNEXION À LA BASE DE DONNÉES
// ------------------------------
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    error_log('Database connection error: ' . $e->getMessage());
    sendJsonResponse(false, 'Erreur de connexion à la base de données. Veuillez réessayer plus tard.');
}

// TRAITEMENT DU FORMULAIRE
// ------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et validation des données
    $nom = cleanInput($_POST['firstName'] ?? '') . ' ' . cleanInput($_POST['lastName'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $telephone = cleanInput($_POST['phone'] ?? '');
    $formation = "Formation Développement Web Gratuite"; // Fixé car c'est la formation de cette page
    $location = cleanInput($_POST['location'] ?? '');
    $source = cleanInput($_POST['source'] ?? '');
    $niveau_experience = cleanInput($_POST['experience'] ?? '');
    $motivation = cleanInput($_POST['motivation'] ?? '');
    
    // Combiner niveau d'expérience et motivation dans le message
    $message = "Niveau d'expérience: $niveau_experience\n\nMotivation:\n$motivation";
    
    // Validation des données obligatoires
    $errors = [];
    if (empty($nom)) $errors[] = "Le nom est obligatoire";
    if (empty($email)) $errors[] = "L'email est obligatoire";
    if (empty($telephone)) $errors[] = "Le téléphone est obligatoire";
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email est invalide";
    }
    
    if (!isset($_POST['terms']) || $_POST['terms'] !== 'on') {
        $errors[] = "Vous devez accepter les conditions d'utilisation";
    }
    
    if (count($errors) > 0) {
        sendJsonResponse(false, implode("<br>", $errors));
    }
    
    // Insertion dans la base de données
    try {
        $stmt = $pdo->prepare("
            INSERT INTO inscriptions (
                nom, email, telephone, formation, message, 
                location, source, date_inscription
            ) VALUES (
                :nom, :email, :telephone, :formation, :message,
                :location, :source, NOW()
            )
        ");
        
        $stmt->bindParam(':nom', $nom, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':telephone', $telephone, PDO::PARAM_STR);
        $stmt->bindParam(':formation', $formation, PDO::PARAM_STR);
        $stmt->bindParam(':message', $message, PDO::PARAM_STR);
        $stmt->bindParam(':location', $location, PDO::PARAM_STR);
        $stmt->bindParam(':source', $source, PDO::PARAM_STR);
        
        $stmt->execute();
        
        $inscription_id = $pdo->lastInsertId();
        
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        sendJsonResponse(false, 'Erreur lors de la sauvegarde en base de données. Veuillez réessayer.');
    }
    
    // ENVOI DES EMAILS
    // ----------------
    $email_admin_sent = sendAdminEmail(
        $admin_email, 
        $site_email, 
        $inscription_id, 
        $nom, 
        $email, 
        $telephone, 
        $formation, 
        $message,
        $location,
        $source
    );
    
    $email_user_sent = sendUserConfirmationEmail(
        $email, 
        $site_email, 
        $inscription_id, 
        $nom, 
        $formation
    );
    
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
    sendJsonResponse(false, 'Méthode non autorisée');
}

// FONCTIONS UTILITAIRES
// ---------------------

function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function sendJsonResponse($success, $message) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit;
}

function sendAdminEmail($admin_email, $site_email, $inscription_id, $nom, $email, $telephone, $formation, $message, $location, $source) {
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
            <span class='label'>Localisation :</span> $location
          </div>
          <div class='info'>
            <span class='label'>Source :</span> $source
          </div>
          <div class='info'>
            <span class='label'>Message :</span> <pre>$message</pre>
          </div>
          <div class='info'>
            <span class='label'>Date d'inscription :</span> ".date('d/m/Y H:i')."
          </div>
        </div>
      </div>
    </body>
    </html>
    ";
    
    $headers_admin = "MIME-Version: 1.0\r\n";
    $headers_admin .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers_admin .= "From: SUCCESS+ <$site_email>\r\n";
    $headers_admin .= "Reply-To: $site_email\r\n";
    
    return mail($admin_email, $subject_admin, $message_admin, $headers_admin);
}

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
    
    $headers_user = "MIME-Version: 1.0\r\n";
    $headers_user .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers_user .= "From: SUCCESS+ <$site_email>\r\n";
    $headers_user .= "Reply-To: $site_email\r\n";
    
    return mail($user_email, $subject_user, $message_user, $headers_user);
}
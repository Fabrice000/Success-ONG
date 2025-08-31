<?php
// ==================================================
// FICHIER: process.php
// TRAITEMENT DES INSCRIPTIONS ET ENVOI D'EMAILS
// ==================================================

// CONFIGURATION
// --------------------------------------------

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__.'/PHPMailer/src/Exception.php';
require __DIR__.'/PHPMailer/src/PHPMailer.php';
require __DIR__.'/PHPMailer/src/SMTP.php';

// Configuration de la base de données
$db_host = "127.0.0.1";
$db_name = "succe2655432";
$db_user = "succe2655432";
$db_pass = "wr2xcw19mz";

// Configuration des emails
$admin_email = "info@successplusproject.com";
$site_email = "contact@successplusproject.com";

// Configuration SMTP unique
$smtp_host = "mail28.lwspanel.com";
$smtp_username = "contact@successplusproject.com";
$smtp_password = "prosuccess0987654321";
$smtp_secure = "ssl";
$smtp_port = 465;

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

function sendEmail($to, $subject, $body, $isHTML = true) {
    global $smtp_host, $smtp_username, $smtp_password, $smtp_secure, $smtp_port, $site_email;
    
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_username;
        $mail->Password = $smtp_password;
        $mail->SMTPSecure = $smtp_secure;
        $mail->Port = $smtp_port;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($site_email, 'SUCCESS+');
        $mail->addAddress($to);
        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        return $mail->send();
    } catch (Exception $e) {
        error_log('Email sending error: ' . $e->getMessage());
        return false;
    }
}

function sendAdminEmail($admin_email, $site_email, $inscription_id, $nom, $email, $telephone, $formation, $message, $location, $source) {
    $subject = "Nouvelle inscription à la formation $formation";
    $body = "
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
                <span class='label'>ID Inscription :</span> $inscription_id
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
    
    return sendEmail($admin_email, $subject, $body);
}

function sendUserConfirmationEmail($user_email, $site_email, $inscription_id, $nom, $formation) {
    $subject = "Confirmation d'inscription à SUCCESS+";
    $body = "
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
    
    return sendEmail($user_email, $subject, $body);
}

// TRAITEMENT DU FORMULAIRE
// ------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et validation des données
    $nom = cleanInput($_POST['firstName'] ?? '') . ' ' . cleanInput($_POST['lastName'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $telephone = cleanInput($_POST['phone'] ?? '');
    $formation = "Formation Développement Web Gratuite";
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
?>

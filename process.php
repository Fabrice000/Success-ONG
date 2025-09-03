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

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

// Configuration de la base de données
require_once 'config.php';

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

// try {
//   $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
//   $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//   $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
// } catch (PDOException $e) {
//   error_log('Database connection error: ' . $e->getMessage());
//   sendJsonResponse(false, 'Erreur de connexion à la base de données. Veuillez réessayer plus tard.');
// }

// FONCTIONS UTILITAIRES
// ---------------------

function cleanInput($data)
{
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
  return $data;
}

function sendJsonResponse($success, $message, $data = null)
{
  header('Content-Type: application/json');
  echo json_encode([
    'success' => $success,
    'message' => $message,
    'data' => $data
  ]);
  exit;
}


function sendEmail($to, $subject, $body, $isHTML = true)
{
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

function sendAdminEmail($admin_email, $site_email, $inscription_id, $nom, $email, $telephone, $formation, $message, $location, $source)
{
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
                <span class='label'>Date d'inscription :</span> " . date('d/m/Y H:i') . "
              </div>
            </div>
          </div>
        </body>
      </html>
    ";

  return sendEmail($admin_email, $subject, $body);
}

function sendUserConfirmationEmail($user_email, $site_email, $inscription_id, $nom, $formation, $certificateId)
{
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
                <span class='label'>Date d'inscription :</span> " . date('d/m/Y H:i') . "
              </div>
              <div class='info'>
                <span class='label'>ID Certificat :</span> #$certificateId
              </div>
              <p>Votre certificat sera disponible une fois le paiement effectué.</p>

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
  if (isset($_POST['action'])) {
    switch ($_POST['action']) {
      case 'submit_form':
        // Traitement du formulaire
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


        $participantName   = $nom;
        $formationName     = $formation;
        $formationDate     = date("Y-m-d");
        $dateObj = new DateTime($formationDate);
        $dateObj->modify('+2 months');
        $issueDate = $dateObj->format('Y-m-d');


        // Génération de l'ID
        $certificateId = generateCertificateId();

        // Insertion dans la table certificates
        $sql = "INSERT INTO certificates 
        (certificate_id, participant_name, formation_name, formation_date, issue_date, inscription_id)
        VALUES (:certificate_id, :participant_name, :formation_name, :formation_date, :issue_date, :inscription_id)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
          ":certificate_id"   => $certificateId,
          ":participant_name" => $participantName,
          ":formation_name"   => $formationName,
          ":formation_date"   => $formationDate,
          ":issue_date"       => $issueDate,
          ":inscription_id"   => $inscription_id
        ]);


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
          $formation,
          $certificateId
        );

        // RÉPONSE AU FRONTEND
        // -------------------
        if ($inscription_id && $email_admin_sent && $email_user_sent) {
          echo "
    <!DOCTYPE html>
    <html lang='fr'>
    <head>
        <meta charset='UTF-8'>
        <title>Confirmation inscription</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
        <script>
            Swal.fire({
                title: 'Succès 🎉',
                html: 'Votre inscription a été enregistrée avec succès !<br><br><b>ID Certificat :</b> $certificateId <br><small>(notez-le pour vos archives)</small>',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = 'certif.php';
            });
        </script>
    </body>
    </html>";
        } elseif ($inscription_id) {
          echo "
    <!DOCTYPE html>
    <html lang='fr'>
    <head>
        <meta charset='UTF-8'>
        <title>Attention</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
        <script>
            Swal.fire({
                title: 'Attention ⚠️',
                text: 'Votre inscription a été enregistrée mais un problème est survenu avec l\\'envoi des emails de confirmation. Rapprochez vous des administrateurs.',
                icon: 'warning',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = 'certif.php';
            });
        </script>
    </body>
    </html>";
        } else {
          echo "
    <!DOCTYPE html>
    <html lang='fr'>
    <head>
        <meta charset='UTF-8'>
        <title>Erreur</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
        <script>
            Swal.fire({
                title: 'Erreur ❌',
                text: 'Une erreur est survenue lors du traitement de votre demande. Veuillez réessayer.',
                icon: 'error',
                confirmButtonText: 'Réessayer'
            }).then(() => {
                window.location.href = 'certif.php';
            });
        </script>
    </body>
    </html>";
        }
        break;
      case 'verify_certificate':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'verify_certificate') {
          $certificateId = trim($_POST['certificate_id']);

          if ($certificateId) {
            $cert = verifyCertificate($pdo, $certificateId);

            if ($cert) {
              echo json_encode([
                'success' => true,
                'data' => $cert
              ]);
            } else {
              echo json_encode([
                'success' => false,
                'message' => 'Numéro de certificat introuvable'
              ]);
            }
          } else {
            echo json_encode([
              'success' => false,
              'message' => 'Aucun numéro fourni'
            ]);
          }
        }


        break;
      case 'validate_certificate':
        $certificateId = cleanInput($_POST['certificat_id'] ?? '');
        $email = cleanInput($_POST['email'] ?? '');

        if (empty($certificateId) || empty($email)) {
          sendJsonResponse(false, "Veuillez remplir tous les champs.");
        }

        try {
          $stmt = $pdo->prepare("
            SELECT c.certificate_id, i.nom, i.formation
            FROM certificates c
            JOIN inscriptions i ON c.inscription_id = i.id
            WHERE c.certificate_id = :certificate_id AND i.email = :email
            LIMIT 1
        ");
          $stmt->execute([
            ':certificate_id' => $certificateId,
            ':email' => $email
          ]);

          $cert = $stmt->fetch(PDO::FETCH_ASSOC);

          if ($cert) {
            sendJsonResponse(true, "Certificat valide.", [
              'participant_name' => $cert['nom'],
              'formation_name' => $cert['formation'],
              'certificate_id' => $cert['certificate_id']
            ]);
          } else {
            sendJsonResponse(false, "Certificat ou email invalide.");
          }
        } catch (PDOException $e) {
          sendJsonResponse(false, "Erreur BDD : " . $e->getMessage());
        }
        break;

      default:
        sendJsonResponse(false, 'Action non reconnue');
    }
  } else {
    sendJsonResponse(false, 'Méthode non autorisée');
  }
} // <-- Add this closing brace to properly close the main if ($_SERVER['REQUEST_METHOD'] === 'POST') block

// $users = getAllUsers($pdo);
// echo json_encode($users);
function getAllUsers($pdo)
{
  try {
    $stmt = $pdo->prepare("SELECT * FROM inscriptions");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    return [];
  }
}
// GENERATION DE CERTIFICAT
function generateCertificateId()
{
  $year = date("Y");
  $randomPart = str_pad(mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT); // 9 chiffres aléatoires
  return "SPC-$year-$randomPart";
}


function verifyCertificate($pdo, $certificateId)
{
  $stmt = $pdo->prepare("SELECT * FROM certificates WHERE certificate_id = :certificate_id");
  $stmt->execute([':certificate_id' => $certificateId]);
  return $stmt->fetch(PDO::FETCH_ASSOC);
}

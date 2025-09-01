<?php
require_once 'config.php';
require  __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Configurer Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

// Configuration des emails
$admin_email = "info@successplusproject.com";
$site_email  = "contact@successplusproject.com";

// Configuration SMTP
$smtp_host     = "mail28.lwspanel.com";
$smtp_username = "contact@successplusproject.com";
$smtp_password = "prosuccess0987654321";
$smtp_secure   = "ssl";
$smtp_port     = 465;

// ✅ Session sécurisée (uniquement si pas déjà démarrée)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Vérification accès admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ---------------------
// Gestion AJAX Valider
// ---------------------
if (isset($_POST['action']) && $_POST['action'] === 'valider_formation' && isset($_POST['id'])) {
    $certificateId = $_POST['id'];

    $stmt = $pdo->prepare("UPDATE certificates SET payment_status = 'validated' WHERE certificate_id = ?");
    $success = $stmt->execute([$certificateId]);

    if ($success) {
        sendCertificate($pdo, $certificateId, $smtp_host, $smtp_username, $smtp_password, $smtp_secure, $smtp_port, $site_email);
    }

    echo json_encode(['success' => $success]);
    exit;
}

// ---------------------
// Fonction envoi certificat
// ---------------------
function sendCertificate($pdo, $certificateId, $smtp_host, $smtp_username, $smtp_password, $smtp_secure, $smtp_port, $site_email)
{
    // Récupérer infos utilisateur
    $stmt = $pdo->prepare("
        SELECT i.email, c.participant_name, c.formation_name, c.certificate_id 
        FROM certificates c
        JOIN inscriptions i ON i.id = c.inscription_id
        WHERE c.certificate_id = ?
    ");
    $stmt->execute([$certificateId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) return false;

    // Générer le PDF

    // Variables récupérées depuis ta base
    $participantName = $user['participant_name'];
    $formationName   = $user['formation_name'];
    $certificateId   = $user['certificate_id'];

    // Si tu veux rajouter des champs comme date formation ou issueDate,
    // récupère-les dans ta requête SQL (par ex. depuis "inscriptions").
    $formationDate = date("d/m/Y");
    $issueDate     = date("d/m/Y");
    // On peut aussi charger ton logo
    $logoPath = __DIR__ . '/logo.png';
   $logoData = base64_encode(file_get_contents($logoPath));

    // Ton HTML avec variables PHP intégrées
    $html = "
<meta charset='UTF-8'>
<style>
body { font-family: 'DejaVu Sans', sans-serif; margin:0; padding:0; }
.certificate {
    border: 15px solid #4CAF50;
    padding: 5px;
    text-align: center;
    width: 100%;
    height: auto; 
    background: #fff;
}
h1 { font-size: 36px; margin-bottom: 20px; }
.name { font-size: 28px; margin: 20px 0; font-weight: bold; }
.details { font-size: 18px; color: #555; margin-top: 15px; }
.footer { margin-top: 50px; font-size: 14px; color: #777; }
.cert-id { margin-top: 40px; font-size: 14px; color: #aaa; }
</style>


<div class='certificate'>
    <img src='data:image/png;base64,$logoData' width='100' style='position:absolute;top:20px;left:20px;'>
    <h1>Certificat de Formation</h1>
    <p class='name'>$participantName</p>
    <p class='details'>
        a suivi la formation <b>$formationName</b><br>
        du $formationDate
    </p>
    <p class='details'>
        Délivré le <b>$issueDate</b>
    </p>
    <div class='footer'>
        <p>Signature de l\'organisme</p>
    </div>
    <div class='cert-id'>
        ID Certificat : $certificateId
    </div>
</div>
";
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape'); // paysage
    $dompdf->render();

    // Sauvegarder le PDF dans ton dossier certificats
    $output = $dompdf->output();
    // Envoi par mail
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $smtp_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_username;
        $mail->Password   = $smtp_password;
        $mail->SMTPSecure = $smtp_secure;
        $mail->Port       = $smtp_port;

        $mail->setFrom($site_email, 'SUCCESS+');
        $mail->addAddress($user['email'], $user['participant_name']);
        $mail->addReplyTo($site_email, 'Support SUCCESS+');

        $mail->Subject = "Votre certificat - {$user['formation_name']}";
        $mail->Body    = "Bonjour {$user['participant_name']},\n\nFélicitations ! Veuillez trouver ci-joint votre certificat pour la formation {$user['formation_name']}.\n\nL'équipe SUCCESS+";
        $pdfFile = __DIR__ . "/certificats/$certificateId.pdf";
        file_put_contents($pdfFile, $output);
        $mail->addAttachment($pdfFile);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

// Récupérer les utilisateurs
$stmt = $pdo->query("SELECT * FROM certificates");
$certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->query("SELECT COUNT(*) AS total_users FROM inscriptions");
$result = $stmt->fetch(PDO::FETCH_ASSOC);

$totalUsers = $result['total_users'];
$stmt = $pdo->query("SELECT COUNT(*) AS total_certified FROM certificates WHERE payment_status = 'validated'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);

$totalCertified = $result['total_certified'];
$totalPending = $totalUsers - $totalCertified;
?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SUCCESS+ - Interface Administrateur</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #FF6B00;
            --secondary: #28A745;
            --accent: #FFC107;
            --dark: #212529;
            --light: #F8F9FA;
            --danger: #DC3545;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Raleway', sans-serif;
            color: white;
            line-height: 1.7;
            background: linear-gradient(135deg, #0c1a25 0%, #0a1420 100%);
            min-height: 100vh;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: rgba(10, 20, 30, 0.95);
            backdrop-filter: blur(10px);
            padding: 2rem 1rem;
            box-shadow: 5px 0 15px rgba(0, 0, 0, 0.2);
        }

        .admin-logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .admin-logo img {
            max-width: 150px;
            height: auto;
        }

        .admin-menu {
            list-style: none;
        }

        .admin-menu li {
            margin-bottom: 0.5rem;
        }

        .admin-menu a {
            display: block;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            padding: 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .admin-menu a:hover,
        .admin-menu a.active {
            background: var(--primary);
            color: white;
        }

        .admin-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .admin-title {
            font-size: 2.2rem;
            background: linear-gradient(135deg, var(--accent) 0%, var(--primary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .admin-user {
            display: flex;
            align-items: center;
        }

        .admin-user img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }

        /* Dashboard Cards */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 1.5rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-text {
            color: rgba(255, 255, 255, 0.7);
        }

        /* Table Styles */
        .admin-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 2rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 2rem;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .card-title {
            font-size: 1.5rem;
            color: var(--accent);
        }

        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .filter-select {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            padding: 0.5rem 1rem;
        }

        .search-box {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .search-input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 3rem;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table th,
        .admin-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .admin-table th {
            font-weight: 600;
            color: var(--accent);
        }

        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: var(--accent);
        }

        .status-validated {
            background: rgba(40, 167, 69, 0.2);
            color: var(--secondary);
        }

        .status-rejected {
            background: rgba(220, 53, 69, 0.2);
            color: var(--danger);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-sm {
            padding: 0.3rem 0.6rem;
            font-size: 0.8rem;
        }

        .btn i {
            margin-right: 0.5rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: #E55E00;
        }

        .btn-success {
            background: var(--secondary);
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #BD2130;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 1.5rem;
            gap: 0.5rem;
        }

        .page-item {
            display: inline-block;
        }

        .page-link {
            padding: 0.5rem 1rem;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.08);
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .page-link:hover,
        .page-link.active {
            background: var(--primary);
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            background: linear-gradient(135deg, #1a2a3a 0%, #0c1a25 100%);
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            position: relative;
            transform: translateY(-50px);
            transition: transform 0.5s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow: hidden;
        }

        .modal-overlay.active .modal {
            transform: translateY(0);
        }

        .modal-header {
            padding: 1.5rem;
            background: rgba(0, 0, 0, 0.3);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.5rem;
            color: var(--accent);
        }

        .close-modal {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-footer {
            padding: 1.5rem;
            background: rgba(0, 0, 0, 0.3);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            font-family: 'Raleway', sans-serif;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.5s ease;
            z-index: 9999;
        }

        .toast.error {
            background: #dc3545;
        }

        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }


        @media (max-width: 992px) {
            .admin-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                padding: 1rem;
            }

            .admin-menu {
                display: flex;
                overflow-x: auto;
                gap: 0.5rem;
            }

            .admin-menu li {
                margin-bottom: 0;
            }

            .admin-menu a {
                white-space: nowrap;
            }
        }

        @media (max-width: 768px) {
            .stats-cards {
                grid-template-columns: 1fr;
            }

            .filters {
                flex-direction: column;
            }

            .admin-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>

<body>

    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="admin-logo">
                <img src="1000791665-removebg-preview(1).png" alt="SUCCESS+ Admin">
            </div>

            <ul class="admin-menu">
                <li><a href="#" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="#"><i class="fas fa-users"></i> Participants</a></li>
                <li><a href="#"><i class="fas fa-money-check-alt"></i> Paiements</a></li>
                <li><a href="#"><i class="fas fa-certificate"></i> Certificats</a></li>
                <li><a href="#"><i class="fas fa-cog"></i> Paramètres</a></li>
                <li><a href="#"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="admin-header">
                <h1 class="admin-title">Tableau de Bord Administrateur</h1>
                <div class="admin-user">
                    <img src="https://ui-avatars.com/api/?name=Admin+User&background=FF6B00&color=fff" alt="Admin">
                    <span>Administrateur</span>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-icon" style="color: var(--accent);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number">
                        <?php echo $totalUsers; ?>
                    </div>
                    <div class="stat-text">Participants Inscrits</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="color: var(--primary);">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-number">87</div>
                    <div class="stat-text">Paiements Effectués</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="color: var(--secondary);">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <div class="stat-number"><?php echo $totalCertified; ?></div>
                    <div class="stat-text">Certificats Validés</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="color: var(--danger);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-number"><?php echo $totalPending; ?></div>
                    <div class="stat-text">En Attente de Validation</div>
                </div>
            </div>

            <!-- Participants Table -->
            <div class="admin-card">
                <div class="card-header">
                    <h2 class="card-title">Participants en Attente de Validation</h2>
                    <button class="btn btn-primary" id="generate-certificate">
                        <i class="fas fa-file-pdf"></i> Générer Certificat
                    </button>
                </div>

                <div class="filters">
                    <select class="filter-select">
                        <option>Tous les formations</option>
                        <option>Développement Web</option>
                        <option>Marketing Digital</option>
                        <option>Gestion de Projet</option>
                    </select>

                    <select class="filter-select">
                        <option>Tous les statuts</option>
                        <option>En attente</option>
                        <option>Validé</option>
                        <option>Rejeté</option>
                    </select>

                    <select class="filter-select">
                        <option>Trier par date</option>
                        <option>Plus récents</option>
                        <option>Plus anciens</option>
                    </select>
                </div>

                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Rechercher un participant...">
                </div>

                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" id="select-all">
                                </th>
                                <th>Nom & Prénom</th>
                                <th>Formation</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($certificates as $p): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($p['certificate_id']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($p['participant_name']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($p['formation_name']) ?>
                                    </td>
                                    <td class="formation-status">
                                        <?php if ($p['payment_status'] == "pending"): ?>
                                            <span class="status-badge status-pending">En attente</span>
                                        <?php else: ?>
                                            <span class="status-badge status-validated">Validé</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($p['payment_status'] == "pending"): ?>
                                            <button class="btn btn-success btn-validate"
                                                data-id="<?= $p['certificate_id'] ?>">Valider</button>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        </tbody>

                        <!-- <tbody>
                            <tr>
                                <td>
                                    <input type="checkbox" class="participant-check">
                                </td>
                                <td>Jean Dupont</td>
                                <td>Développement Web</td>
                                <td>jean.dupont@email.com</td>
                                <td>+225 07 05 43 21 09</td>
                                <td>Orange Money</td>
                                <td>15/08/2023</td>
                                <td>
                                    <span class="status-badge status-pending">En attente</span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-success btn-sm" data-id="1">
                                            <i class="fas fa-check"></i> Valider
                                        </button>
                                        <button class="btn btn-danger btn-sm" data-id="1">
                                            <i class="fas fa-times"></i> Rejeter
                                        </button>
                                        <button class="btn btn-secondary btn-sm view-details" data-id="1">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <input type="checkbox" class="participant-check">
                                </td>
                                <td>Marie Koné</td>
                                <td>Marketing Digital</td>
                                <td>marie.kone@email.com</td>
                                <td>+225 05 07 12 34 56</td>
                                <td>MTN Mobile Money</td>
                                <td>16/08/2023</td>
                                <td>
                                    <span class="status-badge status-pending">En attente</span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-success btn-sm" data-id="2">
                                            <i class="fas fa-check"></i> Valider
                                        </button>
                                        <button class="btn btn-danger btn-sm" data-id="2">
                                            <i class="fas fa-times"></i> Rejeter
                                        </button>
                                        <button class="btn btn-secondary btn-sm view-details" data-id="2">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <input type="checkbox" class="participant-check">
                                </td>
                                <td>Paul Kouassi</td>
                                <td>Développement Web</td>
                                <td>paul.kouassi@email.com</td>
                                <td>+225 07 08 98 76 54</td>
                                <td>Wave</td>
                                <td>17/08/2023</td>
                                <td>
                                    <span class="status-badge status-pending">En attente</span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-success btn-sm" data-id="3">
                                            <i class="fas fa-check"></i> Valider
                                        </button>
                                        <button class="btn btn-danger btn-sm" data-id="3">
                                            <i class="fas fa-times"></i> Rejeter
                                        </button>
                                        <button class="btn btn-secondary btn-sm view-details" data-id="3">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <input type="checkbox" class="participant-check">
                                </td>
                                <td>Sophie Yao</td>
                                <td>Gestion de Projet</td>
                                <td>sophie.yao@email.com</td>
                                <td>+225 01 02 03 04 05</td>
                                <td>Moov Money</td>
                                <td>18/08/2023</td>
                                <td>
                                    <span class="status-badge status-pending">En attente</span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-success btn-sm" data-id="4">
                                            <i class="fas fa-check"></i> Valider
                                        </button>
                                        <button class="btn btn-danger btn-sm" data-id="4">
                                            <i class="fas fa-times"></i> Rejeter
                                        </button>
                                        <button class="btn btn-secondary btn-sm view-details" data-id="4">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <input type="checkbox" class="participant-check">
                                </td>
                                <td>Marc Aka</td>
                                <td>Développement Web</td>
                                <td>marc.aka@email.com</td>
                                <td>+225 07 09 08 07 06</td>
                                <td>Airtel Money</td>
                                <td>19/08/2023</td>
                                <td>
                                    <span class="status-badge status-pending">En attente</span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-success btn-sm" data-id="5">
                                            <i class="fas fa-check"></i> Valider
                                        </button>
                                        <button class="btn btn-danger btn-sm" data-id="5">
                                            <i class="fas fa-times"></i> Rejeter
                                        </button>
                                        <button class="btn btn-secondary btn-sm view-details" data-id="5">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody> -->
                    </table>
                </div>

                <div class="pagination">
                    <div class="page-item">
                        <a href="#" class="page-link"><i class="fas fa-chevron-left"></i></a>
                    </div>
                    <div class="page-item">
                        <a href="#" class="page-link active">1</a>
                    </div>
                    <div class="page-item">
                        <a href="#" class="page-link">2</a>
                    </div>
                    <div class="page-item">
                        <a href="#" class="page-link">3</a>
                    </div>
                    <div class="page-item">
                        <a href="#" class="page-link"><i class="fas fa-chevron-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Participant Details -->
    <div class="modal-overlay" id="details-modal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Détails du Participant</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Nom Complet</label>
                    <input type="text" value="Jean Dupont" readonly>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" value="jean.dupont@email.com" readonly>
                </div>

                <div class="form-group">
                    <label>Téléphone</label>
                    <input type="text" value="+225 07 05 43 21 09" readonly>
                </div>

                <div class="form-group">
                    <label>Formation</label>
                    <input type="text" value="Développement Web" readonly>
                </div>

                <div class="form-group">
                    <label>Méthode de Paiement</label>
                    <input type="text" value="Orange Money" readonly>
                </div>

                <div class="form-group">
                    <label>Date de Paiement</label>
                    <input type="text" value="15/08/2023" readonly>
                </div>

                <div class="form-group">
                    <label>Code de Transaction</label>
                    <input type="text" value="OM123456789" readonly>
                </div>

                <div class="form-group">
                    <label>Statut</label>
                    <select>
                        <option>En attente</option>
                        <option selected>Validé</option>
                        <option>Rejeté</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Notes (optionnel)</label>
                    <textarea rows="3" placeholder="Ajoutez des notes internes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="close-modal">Annuler</button>
                <button class="btn btn-primary">Enregistrer</button>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.btn-validate').forEach(btn => {
            btn.addEventListener('click', () => {
                const certificateId = btn.getAttribute('data-id');

                fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'action=valider_formation&id=' + certificateId
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const row = btn.closest('tr');
                            const statusCell = row.querySelector('.formation-status span');
                            statusCell.textContent = "Validé";
                            statusCell.className = "status-badge status-validated";
                            btn.style.display = "none";
                            showToast("✅ Formation validée et certificat envoyé !");
                        } else {
                            showToast("❌ Erreur lors de la validation.");
                        }
                    });
            });
        });

        function showToast(message, type = "success") {
            const toast = document.createElement("div");
            toast.className = "toast " + type;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => toast.classList.add("show"), 100);
            setTimeout(() => {
                toast.classList.remove("show");
                setTimeout(() => toast.remove(), 500);
            }, 4000);
        }
    </script>
</body>

</html>
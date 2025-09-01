<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SUCCESS+ - Vérification de Certificats</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #FF6B00;
            --secondary: #28A745;
            --accent: #FFC107;
            --dark: #212529;
            --light: #F8F9FA;
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
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo img {
            max-width: 200px;
            height: auto;
        }

        .verification-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 3rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            text-align: center;
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--accent) 0%, var(--primary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        p {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 2rem;
            font-size: 1.2rem;
        }

        .form-group {
            margin-bottom: 2rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 600;
            text-align: left;
        }

        .form-group input {
            width: 100%;
            padding: 1rem 1.5rem;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: white;
            font-size: 1.1rem;
            font-family: 'Raleway', sans-serif;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 107, 0, 0.3);
        }

        .btn {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
            padding: 1rem 2.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            box-shadow: 0 5px 20px rgba(255, 107, 0, 0.4);
        }

        .btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(255, 107, 0, 0.6);
        }

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
            border-radius: 25px;
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
            padding: 2rem;
            background: rgba(0, 0, 0, 0.3);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 2rem;
            color: var(--accent);
        }

        .close-modal {
            background: none;
            border: none;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .close-modal:hover {
            color: var(--primary);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 3rem;
            text-align: left;
        }

        .certificate-info {
            margin-bottom: 1.5rem;
        }

        .certificate-info h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--accent);
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .info-item {
            margin-bottom: 1rem;
        }

        .info-label {
            font-weight: 600;
            color: rgba(255, 255, 255, 0.7);
            display: block;
            margin-bottom: 0.3rem;
        }

        .info-value {
            color: white;
            font-size: 1.1rem;
        }

        .certificate-status {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid var(--secondary);
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 2rem;
            text-align: center;
        }

        .status-text {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 0.5rem;
        }

        .status-icon {
            font-size: 3rem;
            color: var(--secondary);
            margin-bottom: 1rem;
        }

        .error-message {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid #dc3545;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 1rem;
            text-align: center;
            display: none;
        }

        .error-icon {
            font-size: 2rem;
            color: #dc3545;
            margin-bottom: 1rem;
        }

        .error-text {
            color: #dc3545;
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .verification-card {
                padding: 2rem;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-body {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="1000791665-removebg-preview(1).png" alt="SUCCESS+ Logo">
        </div>
        
        <div class="verification-card">
            <h1>Vérification de Certificat</h1>
            <p>Entrez le numéro d'authentification de votre certificat pour vérifier son authenticité</p>
            
            <form id="verification-form" method="POST" action="./process.php">
                <input type="hidden" name="action" value="verify_certificate">

                <div class="form-group">
                    <label for="certificate-number">Numéro d'authentification</label>
                    <input type="text" id="certificate_id" name="certificate_id" required placeholder="Ex: SPC-2023-789654123">
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-check-circle"></i> Vérifier l'authenticité
                </button>
            </form>
            
            <div class="error-message" id="error-message">
                <div class="error-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <p class="error-text" id="error-text">Numéro de certificat introuvable ou invalide</p>
            </div>
        </div>
    </div>

    <!-- Modal pour afficher les informations du certificat -->
    <div class="modal-overlay" id="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Certificat Authentique</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="certificate-info">
                    <h3>Informations du Certificat</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Numéro d'authentification:</span>
                            <div class="info-value" id="cert-number">SPC-2023-789654123</div>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Nom du participant:</span>
                            <div class="info-value" id="participant-name">Jean Dupont</div>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Formation:</span>
                            <div class="info-value" id="formation-name">Développement Web</div>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Date de formation:</span>
                            <div class="info-value" id="formation-date">18-20 Août 2023</div>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Date d'émission:</span>
                            <div class="info-value" id="issue-date">25 Août 2023</div>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Durée de formation:</span>
                            <div class="info-value" id="formation-duration">21 heures</div>
                        </div>
                    </div>
                </div>
                
                <div class="certificate-status">
                    <div class="status-icon">
                        <i class="fas fa-shield-check"></i>
                    </div>
                    <div class="status-text">Ce certificat est authentique et valide</div>
                    <p>Il a été délivré par SUCCESS+ et vérifié dans notre système</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Références aux éléments du DOM
            const verificationForm = document.getElementById('verification-form');
            const modalOverlay = document.getElementById('modal-overlay');
            const closeModal = document.querySelector('.close-modal');
            const errorMessage = document.getElementById('error-message');
            const errorText = document.getElementById('error-text');
            
            // Données simulées (à remplacer par une connexion à la base de données réelle)
            const certificateDatabase = {
                'SPC-2023-789654123': {
                    participantName: 'Jean Dupont',
                    formationName: 'Développement Web',
                    formationDate: '18-20 Août 2023',
                    issueDate: '25 Août 2023',
                    formationDuration: '21 heures'
                },
                'SPC-2023-456789123': {
                    participantName: 'Marie Lambert',
                    formationName: 'Marketing Digital',
                    formationDate: '15-17 Septembre 2023',
                    issueDate: '22 Septembre 2023',
                    formationDuration: '18 heures'
                },
                'SPC-2023-123456789': {
                    participantName: 'Pierre Gagnon',
                    formationName: 'Gestion de Projet',
                    formationDate: '10-12 Octobre 2023',
                    issueDate: '18 Octobre 2023',
                    formationDuration: '24 heures'
                }
            };
            
            // Gestion de la soumission du formulaire
            verificationForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const certificateNumber = document.getElementById('certificate-number').value.trim();
                
                // Cacher les messages d'erreur précédents
                errorMessage.style.display = 'none';
                
                // Vérifier si le numéro existe dans la base de données
                if (certificateDatabase[certificateNumber]) {
                    // Afficher les informations du certificat
                    displayCertificateInfo(certificateNumber, certificateDatabase[certificateNumber]);
                    // Afficher la modal
                    modalOverlay.classList.add('active');
                    document.body.style.overflow = 'hidden';
                } else {
                    // Afficher un message d'erreur
                    errorText.textContent = 'Numéro de certificat introuvable. Veuillez vérifier et réessayer.';
                    errorMessage.style.display = 'block';
                }
            });
            
            // Fermer la modal
            closeModal.addEventListener('click', function() {
                modalOverlay.classList.remove('active');
                document.body.style.overflow = '';
            });
            
            // Fermer la modal en cliquant à l'extérieur
            modalOverlay.addEventListener('click', function(e) {
                if (e.target === modalOverlay) {
                    modalOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
            
            // Fonction pour afficher les informations du certificat
            function displayCertificateInfo(certNumber, certData) {
                document.getElementById('cert-number').textContent = certNumber;
                document.getElementById('participant-name').textContent = certData.participantName;
                document.getElementById('formation-name').textContent = certData.formationName;
                document.getElementById('formation-date').textContent = certData.formationDate;
                document.getElementById('issue-date').textContent = certData.issueDate;
                document.getElementById('formation-duration').textContent = certData.formationDuration;
            }
            
            // Animation d'entrée pour la carte de vérification
            const verificationCard = document.querySelector('.verification-card');
            verificationCard.style.transform = 'translateY(20px)';
            verificationCard.style.opacity = '0';
            
            setTimeout(() => {
                verificationCard.style.transition = 'transform 0.5s ease, opacity 0.5s ease';
                verificationCard.style.transform = 'translateY(0)';
                verificationCard.style.opacity = '1';
            }, 100);
        });
    </script>
</body>
</html>
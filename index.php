<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <?php
// ==========================================
// SYSTÈME DE LOCATION DE VOITURES - ALGÉRIE
// ==========================================

session_start();

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'location_voiture_dz');

// Connexion à la base de données
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch(PDOException $e) {
            die("Erreur de connexion: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
}

// Wilayas d'Algérie (69 wilayas)
$wilayas = [
    1 => 'Adrar', 2 => 'Chlef', 3 => 'Laghouat', 4 => 'Oum El Bouaghi', 
    5 => 'Batna', 6 => 'Béjaïa', 7 => 'Biskra', 8 => 'Béchar', 
    9 => 'Blida', 10 => 'Bouira', 11 => 'Tamanrasset', 12 => 'Tébessa',
    13 => 'Tlemcen', 14 => 'Tiaret', 15 => 'Tizi Ouzou', 16 => 'Alger',
    17 => 'Djelfa', 18 => 'Jijel', 19 => 'Sétif', 20 => 'Saïda',
    21 => 'Skikda', 22 => 'Sidi Bel Abbès', 23 => 'Annaba', 24 => 'Guelma',
    25 => 'Constantine', 26 => 'Médéa', 27 => 'Mostaganem', 28 => 'M\'Sila',
    29 => 'Mascara', 30 => 'Ouargla', 31 => 'Oran', 32 => 'El Bayadh',
    33 => 'Illizi', 34 => 'Bordj Bou Arreridj', 35 => 'Boumerdès', 36 => 'El Tarf',
    37 => 'Tindouf', 38 => 'Tissemsilt', 39 => 'El Oued', 40 => 'Khenchela',
    41 => 'Souk Ahras', 42 => 'Tipaza', 43 => 'Mila', 44 => 'Aïn Defla',
    45 => 'Naâma', 46 => 'Aïn Témouchent', 47 => 'Ghardaïa', 48 => 'Relizane',
    49 => 'Timimoun', 50 => 'Bordj Badji Mokhtar', 51 => 'Ouled Djellal', 
    52 => 'Béni Abbès', 53 => 'In Salah', 54 => 'In Guezzam', 55 => 'Touggourt',
    56 => 'Djanet', 57 => 'El M\'Ghair', 58 => 'El Meniaa', 59 => 'Aflou',
    60 => 'El Abiodh Sidi Cheikh', 61 => 'El Aricha', 62 => 'El Kantara',
    63 => 'Barika', 64 => 'Bou Saâda', 65 => 'Bir El Ater', 66 => 'Ksar El Boukhari',
    67 => 'Ksar Chellala', 68 => 'Aïn Oussara', 69 => 'Messaad'
];

// Modèles de voitures disponibles
$car_models = [
    'Dacia' => ['Logan', 'Sandero', 'Duster'],
    'Renault' => ['Clio', 'Megane', 'Symbol', 'Kangoo'],
    'Peugeot' => ['206', '208', '301', '308'],
    'Citroën' => ['C3', 'C4'],
    'Volkswagen' => ['Polo', 'Golf'],
    'Skoda' => ['Octavia'],
    'Toyota' => ['Yaris', 'Corolla', 'Hilux'],
    'Hyundai' => ['i10', 'i20', 'Accent'],
    'Kia' => ['Picanto', 'Rio'],
    'Nissan' => ['Sunny', 'Qashqai'],
    'Suzuki' => ['Swift'],
    'Fiat' => ['Punto', 'Tipo'],
    'Opel' => ['Corsa'],
    'Ford' => ['Fiesta', 'Focus'],
    'Mitsubishi' => ['Lancer'],
    'Chevrolet' => ['Aveo']
];

// Classe Utilisateur (User)
class User {
    protected $db;
    protected $user_id;
    protected $email;
    protected $nom;
    protected $prenom;
    protected $role;
    protected $company_id;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function login($email, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? AND password = MD5(?)");
        $stmt->execute([$email, $password]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['nom'] = $user['nom'];
            $_SESSION['prenom'] = $user['prenom'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['company_id'] = $user['company_id'];
            return true;
        }
        return false;
    }
    
    public function register($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO users (email, password, nom, prenom, telephone, role, company_id, wilaya_id) 
                VALUES (?, MD5(?), ?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([
                $data['email'],
                $data['password'],
                $data['nom'],
                $data['prenom'],
                $data['telephone'],
                $data['role'],
                $data['company_id'] ?? null,
                $data['wilaya_id']
            ]);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    public function logout() {
        session_destroy();
        header("Location: index.php");
        exit();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function getRole() {
        return $_SESSION['role'] ?? null;
    }
    
    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    public function getCompanyId() {
        return $_SESSION['company_id'] ?? null;
    }
}

// Classe Entreprise (Company)
class Company {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO companies (nom, email, telephone, adresse, wilaya_id, created_by) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([
                $data['nom'],
                $data['email'],
                $data['telephone'],
                $data['adresse'],
                $data['wilaya_id'],
                $data['created_by']
            ]);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    public function getAll() {
        $stmt = $this->db->query("
            SELECT c.*, w.nom as wilaya_nom 
            FROM companies c 
            LEFT JOIN wilayas w ON c.wilaya_id = w.wilaya_id
            ORDER BY c.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT c.*, w.nom as wilaya_nom 
            FROM companies c 
            LEFT JOIN wilayas w ON c.wilaya_id = w.wilaya_id
            WHERE c.company_id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM companies WHERE company_id = ?");
        return $stmt->execute([$id]);
    }
}

// Classe Voiture (Car)
class Car {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO cars (company_id, marque, modele, annee, matricule, couleur, 
                                  kilometrage, statut, prix_journalier, wilaya_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([
                $data['company_id'],
                $data['marque'],
                $data['modele'],
                $data['annee'],
                $data['matricule'],
                $data['couleur'],
                $data['kilometrage'],
                'disponible',
                $data['prix_journalier'],
                $data['wilaya_id']
            ]);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    public function update($car_id, $data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE cars SET marque=?, modele=?, annee=?, matricule=?, couleur=?, 
                               kilometrage=?, prix_journalier=?, wilaya_id=?
                WHERE car_id = ?
            ");
            return $stmt->execute([
                $data['marque'],
                $data['modele'],
                $data['annee'],
                $data['matricule'],
                $data['couleur'],
                $data['kilometrage'],
                $data['prix_journalier'],
                $data['wilaya_id'],
                $car_id
            ]);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    public function delete($car_id) {
        $stmt = $this->db->prepare("DELETE FROM cars WHERE car_id = ?");
        return $stmt->execute([$car_id]);
    }
    
    public function getByCompany($company_id) {
        $stmt = $this->db->prepare("
            SELECT c.*, w.nom as wilaya_nom 
            FROM cars c 
            LEFT JOIN wilayas w ON c.wilaya_id = w.wilaya_id
            WHERE c.company_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$company_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAvailableCars($company_id, $date_debut, $date_fin) {
        $stmt = $this->db->prepare("
            SELECT c.*, w.nom as wilaya_nom 
            FROM cars c 
            LEFT JOIN wilayas w ON c.wilaya_id = w.wilaya_id
            WHERE c.company_id = ? 
            AND c.statut = 'disponible'
            AND c.car_id NOT IN (
                SELECT car_id FROM reservations 
                WHERE statut != 'annulee' 
                AND NOT (date_fin < ? OR date_debut > ?)
            )
            ORDER BY c.prix_journalier ASC
        ");
        $stmt->execute([$company_id, $date_debut, $date_fin]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getById($car_id) {
        $stmt = $this->db->prepare("SELECT * FROM cars WHERE car_id = ?");
        $stmt->execute([$car_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function updateStatus($car_id, $status) {
        $stmt = $this->db->prepare("UPDATE cars SET statut = ? WHERE car_id = ?");
        return $stmt->execute([$status, $car_id]);
    }
    
    // Calculer le prix basé sur l'année et le modèle
    public static function calculatePrice($annee, $marque) {
        $current_year = date('Y');
        $age = $current_year - $annee;
        
        // Prix de base selon la marque
        $base_prices = [
            'Dacia' => 5000,
            'Renault' => 6000,
            'Peugeot' => 6500,
            'Citroën' => 6500,
            'Toyota' => 8000,
            'Volkswagen' => 7000,
            'Hyundai' => 5500,
            'Kia' => 5500,
            'Nissan' => 7000,
            'Ford' => 6500,
            'default' => 5000
        ];
        
        $prix = $base_prices[$marque] ?? $base_prices['default'];
        
        // Dépréciation par année
        $prix = $prix - ($age * 300);
        
        // Limites min/max
        if ($prix < 4000) $prix = 4000;
        if ($prix > 20000) $prix = 20000;
        
        return $prix;
    }
}

// Classe Réservation (Reservation)
class Reservation {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($data) {
        try {
            $this->db->beginTransaction();
            
            // Calculer le prix total
            $car = new Car();
            $car_info = $car->getById($data['car_id']);
            
            $date1 = new DateTime($data['date_debut']);
            $date2 = new DateTime($data['date_fin']);
            $jours = $date1->diff($date2)->days + 1;
            
            $prix_total = $car_info['prix_journalier'] * $jours;
            
            $stmt = $this->db->prepare("
                INSERT INTO reservations (user_id, car_id, company_id, date_debut, date_fin, 
                                         prix_total, statut, paiement_statut) 
                VALUES (?, ?, ?, ?, ?, ?, 'en_attente', 'non_paye')
            ");
            $stmt->execute([
                $data['user_id'],
                $data['car_id'],
                $data['company_id'],
                $data['date_debut'],
                $data['date_fin'],
                $prix_total
            ]);
            
            $reservation_id = $this->db->lastInsertId();
            
            // Mettre à jour le statut de la voiture
            $car->updateStatus($data['car_id'], 'louee');
            
            $this->db->commit();
            return $reservation_id;
        } catch(PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    public function getByUser($user_id) {
        $stmt = $this->db->prepare("
            SELECT r.*, c.marque, c.modele, c.matricule, co.nom as company_nom
            FROM reservations r
            JOIN cars c ON r.car_id = c.car_id
            JOIN companies co ON r.company_id = co.company_id
            WHERE r.user_id = ?
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getByCompany($company_id) {
        $stmt = $this->db->prepare("
            SELECT r.*, c.marque, c.modele, c.matricule, 
                   u.nom, u.prenom, u.email, u.telephone
            FROM reservations r
            JOIN cars c ON r.car_id = c.car_id
            JOIN users u ON r.user_id = u.user_id
            WHERE r.company_id = ?
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$company_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function updatePaymentStatus($reservation_id, $status) {
        $stmt = $this->db->prepare("UPDATE reservations SET paiement_statut = ? WHERE reservation_id = ?");
        return $stmt->execute([$status, $reservation_id]);
    }
    
    public function completeReservation($reservation_id) {
        try {
            $this->db->beginTransaction();
            
            // Récupérer la réservation
            $stmt = $this->db->prepare("SELECT * FROM reservations WHERE reservation_id = ?");
            $stmt->execute([$reservation_id]);
            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Mettre à jour le statut
            $stmt = $this->db->prepare("UPDATE reservations SET statut = 'terminee' WHERE reservation_id = ?");
            $stmt->execute([$reservation_id]);
            
            // Libérer la voiture
            $car = new Car();
            $car->updateStatus($reservation['car_id'], 'disponible');
            
            $this->db->commit();
            return true;
        } catch(PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    public function cancelReservation($reservation_id) {
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("SELECT * FROM reservations WHERE reservation_id = ?");
            $stmt->execute([$reservation_id]);
            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $this->db->prepare("UPDATE reservations SET statut = 'annulee' WHERE reservation_id = ?");
            $stmt->execute([$reservation_id]);
            
            $car = new Car();
            $car->updateStatus($reservation['car_id'], 'disponible');
            
            $this->db->commit();
            return true;
        } catch(PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }
}

// Initialisation des objets
$user = new User();
$company = new Company();
$car = new Car();
$reservation = new Reservation();

// Gestion des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'login':
            if ($user->login($_POST['email'], $_POST['password'])) {
                header("Location: index.php?page=dashboard");
                exit();
            } else {
                $error = "Email ou mot de passe incorrect";
            }
            break;
            
        case 'update_car':
            if ($user->getRole() === 'agent' || $user->getRole() === 'admin') {
                $_POST['prix_journalier'] = Car::calculatePrice($_POST['annee'], $_POST['marque']);
                if ($car->update($_POST['car_id'], $_POST)) {
                    $success = "Voiture mise à jour avec succès";
                } else {
                    $error = "Erreur lors de la mise à jour";
                }
            }
            break;
            
        case 'delete_car':
            if ($user->getRole() === 'agent' || $user->getRole() === 'admin') {
                if ($car->delete($_POST['car_id'])) {
                    $success = "Voiture supprimée avec succès";
                } else {
                    $error = "Erreur lors de la suppression";
                }
            }
            break;
            
        case 'make_reservation':
            if ($user->getRole() === 'client') {
                $_POST['user_id'] = $user->getUserId();
                $reservation_id = $reservation->create($_POST);
                if ($reservation_id) {
                    $success = "Réservation effectuée avec succès ! ID: " . $reservation_id;
                } else {
                    $error = "Erreur lors de la réservation";
                }
            }
            break;
            
        case 'update_payment':
            if ($user->getRole() === 'agent' || $user->getRole() === 'admin') {
                if ($reservation->updatePaymentStatus($_POST['reservation_id'], $_POST['paiement_statut'])) {
                    $success = "Statut de paiement mis à jour";
                } else {
                    $error = "Erreur lors de la mise à jour";
                }
            }
            break;
            
        case 'complete_reservation':
            if ($user->getRole() === 'agent' || $user->getRole() === 'admin') {
                if ($reservation->completeReservation($_POST['reservation_id'])) {
                    $success = "Réservation terminée";
                } else {
                    $error = "Erreur";
                }
            }
            break;
            
        case 'cancel_reservation':
            if ($reservation->cancelReservation($_POST['reservation_id'])) {
                $success = "Réservation annulée";
            } else {
                $error = "Erreur lors de l'annulation";
            }
            break;
    }
}

// Gestion de la date simulée pour tester l'application
if (!isset($_SESSION['current_date'])) {
    $_SESSION['current_date'] = date('Y-m-d');
}

if (isset($_GET['change_date'])) {
    if ($_GET['change_date'] === 'next') {
        $date = new DateTime($_SESSION['current_date']);
        $date->modify('+1 day');
        $_SESSION['current_date'] = $date->format('Y-m-d');
    } elseif ($_GET['change_date'] === 'prev') {
        $date = new DateTime($_SESSION['current_date']);
        $date->modify('-1 day');
        $_SESSION['current_date'] = $date->format('Y-m-d');
    }
    header("Location: index.php?page=" . ($_GET['page'] ?? 'dashboard'));
    exit();
}

$current_date = $_SESSION['current_date'];

// Vérifier et mettre à jour automatiquement les réservations terminées
function checkExpiredReservations() {
    global $current_date;
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        UPDATE reservations 
        SET statut = 'terminee' 
        WHERE date_fin < ? AND statut = 'en_cours'
    ");
    $stmt->execute([$current_date]);
    
    // Libérer les voitures
    $stmt = $db->prepare("
        UPDATE cars c
        JOIN reservations r ON c.car_id = r.car_id
        SET c.statut = 'disponible'
        WHERE r.date_fin < ? AND r.statut = 'terminee' AND c.statut = 'louee'
    ");
    $stmt->execute([$current_date]);
    
    // Mettre en cours les réservations qui commencent aujourd'hui
    $stmt = $db->prepare("
        UPDATE reservations 
        SET statut = 'en_cours' 
        WHERE date_debut <= ? AND date_fin >= ? AND statut = 'en_attente'
    ");
    $stmt->execute([$current_date, $current_date]);
}

checkExpiredReservations();

// Déterminer la page à afficher
$page = $_GET['page'] ?? 'home';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Location Voiture Algérie</title>
</head>
<body>

<?php if ($user->isLoggedIn()): ?>
    <!-- HEADER POUR UTILISATEURS CONNECTÉS -->
    <div class="header">
        <h1>Location Voiture DZ</h1>
        <div class="user-info">
            <span>Bienvenue, <?php echo htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></span>
            <span>Rôle: <?php echo htmlspecialchars($_SESSION['role']); ?></span>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="logout">
                <button type="submit">Déconnexion</button>
            </form>
        </div>
    </div>
    
    <!-- SIMULATEUR DE DATE -->
    <div class="date-simulator">
        <h3>Date Actuelle (Simulation): <?php echo $current_date; ?></h3>
        <a href="?page=<?php echo $page; ?>&change_date=prev">← Jour Précédent</a>
        <a href="?page=<?php echo $page; ?>&change_date=next">Jour Suivant →</a>
    </div>
    
    <!-- NAVIGATION -->
    <nav>
        <a href="?page=dashboard">Tableau de bord</a>
        
        <?php if ($user->getRole() === 'admin'): ?>
            <a href="?page=companies">Entreprises</a>
            <a href="?page=all_users">Utilisateurs</a>
            <a href="?page=all_reservations">Toutes Réservations</a>
        <?php endif; ?>
        
        <?php if ($user->getRole() === 'agent'): ?>
            <a href="?page=cars">Nos Voitures</a>
            <a href="?page=reservations">Réservations</a>
            <a href="?page=add_car">Ajouter Voiture</a>
        <?php endif; ?>
        
        <?php if ($user->getRole() === 'client'): ?>
            <a href="?page=browse_cars">Louer une Voiture</a>
            <a href="?page=my_reservations">Mes Réservations</a>
        <?php endif; ?>
    </nav>
    
    <!-- MESSAGES -->
    <?php if (isset($success)): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <!-- CONTENU PRINCIPAL -->
    <div class="main-content">
        <?php
        switch($page) {
            case 'dashboard':
                include_dashboard();
                break;
            case 'companies':
                if ($user->getRole() === 'admin') include_companies();
                break;
            case 'cars':
                if ($user->getRole() === 'agent') include_cars();
                break;
            case 'add_car':
                if ($user->getRole() === 'agent') include_add_car();
                break;
            case 'edit_car':
                if ($user->getRole() === 'agent') include_edit_car();
                break;
            case 'reservations':
                if ($user->getRole() === 'agent') include_agent_reservations();
                break;
            case 'browse_cars':
                if ($user->getRole() === 'client') include_browse_cars();
                break;
            case 'my_reservations':
                if ($user->getRole() === 'client') include_my_reservations();
                break;
            case 'all_reservations':
                if ($user->getRole() === 'admin') include_all_reservations();
                break;
            default:
                include_dashboard();
        }
        
        // FONCTION: Tableau de bord
        function include_dashboard() {
            global $user, $car, $reservation, $company;
            
            echo "<h2>Tableau de Bord</h2>";
            
            if ($user->getRole() === 'admin') {
                $companies = $company->getAll();
                echo "<h3>Statistiques Globales</h3>";
                echo "<p>Nombre d'entreprises: " . count($companies) . "</p>";
                
                $db = Database::getInstance()->getConnection();
                $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role='client'");
                $clients = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "<p>Nombre de clients: " . $clients['total'] . "</p>";
                
                $stmt = $db->query("SELECT COUNT(*) as total FROM cars");
                $cars = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "<p>Nombre de voitures: " . $cars['total'] . "</p>";
                
            } elseif ($user->getRole() === 'agent') {
                $cars = $car->getByCompany($user->getCompanyId());
                $reservations = $reservation->getByCompany($user->getCompanyId());
                
                echo "<h3>Statistiques de l'Entreprise</h3>";
                echo "<p>Nombre de voitures: " . count($cars) . "</p>";
                echo "<p>Nombre de réservations: " . count($reservations) . "</p>";
                
                $disponibles = array_filter($cars, function($c) { return $c['statut'] === 'disponible'; });
                $louees = array_filter($cars, function($c) { return $c['statut'] === 'louee'; });
                
                echo "<p>Voitures disponibles: " . count($disponibles) . "</p>";
                echo "<p>Voitures louées: " . count($louees) . "</p>";
                
            } elseif ($user->getRole() === 'client') {
                $reservations = $reservation->getByUser($user->getUserId());
                
                echo "<h3>Mes Statistiques</h3>";
                echo "<p>Nombre de réservations: " . count($reservations) . "</p>";
                
                $en_cours = array_filter($reservations, function($r) { return $r['statut'] === 'en_cours'; });
                echo "<p>Réservations en cours: " . count($en_cours) . "</p>";
            }
        }
        
        // FONCTION: Gestion des entreprises (Admin)
        function include_companies() {
            global $company, $wilayas;
            
            echo "<h2>Gestion des Entreprises</h2>";
            
            echo '<h3>Ajouter une Entreprise</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_company">
                <input type="text" name="nom" placeholder="Nom de l\'entreprise" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="text" name="telephone" placeholder="Téléphone" required>
                <textarea name="adresse" placeholder="Adresse" required></textarea>
                <select name="wilaya_id" required>
                    <option value="">Sélectionner Wilaya</option>';
            foreach ($wilayas as $id => $nom) {
                echo "<option value='$id'>$id - $nom</option>";
            }
            echo '</select>
                <button type="submit">Ajouter</button>
            </form>';
            
            $companies = $company->getAll();
            echo "<h3>Liste des Entreprises</h3>";
            echo "<table border='1'>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Wilaya</th>
                        <th>Actions</th>
                    </tr>";
            
            foreach ($companies as $comp) {
                echo "<tr>
                        <td>{$comp['company_id']}</td>
                        <td>" . htmlspecialchars($comp['nom']) . "</td>
                        <td>" . htmlspecialchars($comp['email']) . "</td>
                        <td>" . htmlspecialchars($comp['telephone']) . "</td>
                        <td>" . htmlspecialchars($comp['wilaya_nom']) . "</td>
                        <td>
                            <form method='POST' style='display:inline;'>
                                <input type='hidden' name='action' value='delete_company'>
                                <input type='hidden' name='company_id' value='{$comp['company_id']}'>
                                <button type='submit' onclick='return confirm(\"Confirmer?\")'>Supprimer</button>
                            </form>
                        </td>
                    </tr>";
            }
            echo "</table>";
        }
        
        // FONCTION: Gestion des voitures (Agent)
        function include_cars() {
            global $car, $user;
            
            echo "<h2>Nos Voitures</h2>";
            
            $cars = $car->getByCompany($user->getCompanyId());
            
            if (empty($cars)) {
                echo "<p>Aucune voiture enregistrée.</p>";
                return;
            }
            
            echo "<table border='1'>
                    <tr>
                        <th>ID</th>
                        <th>Marque</th>
                        <th>Modèle</th>
                        <th>Année</th>
                        <th>Matricule</th>
                        <th>Couleur</th>
                        <th>Prix/Jour (DA)</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>";
            
            foreach ($cars as $c) {
                echo "<tr>
                        <td>{$c['car_id']}</td>
                        <td>" . htmlspecialchars($c['marque']) . "</td>
                        <td>" . htmlspecialchars($c['modele']) . "</td>
                        <td>{$c['annee']}</td>
                        <td>" . htmlspecialchars($c['matricule']) . "</td>
                        <td>" . htmlspecialchars($c['couleur']) . "</td>
                        <td>" . number_format($c['prix_journalier'], 2) . " DA</td>
                        <td>{$c['statut']}</td>
                        <td>
                            <a href='?page=edit_car&car_id={$c['car_id']}'>Modifier</a>
                            <form method='POST' style='display:inline;'>
                                <input type='hidden' name='action' value='delete_car'>
                                <input type='hidden' name='car_id' value='{$c['car_id']}'>
                                <button type='submit' onclick='return confirm(\"Supprimer?\")'>Supprimer</button>
                            </form>
                        </td>
                    </tr>";
            }
            echo "</table>";
        }
        
        // FONCTION: Ajouter une voiture (Agent)
        function include_add_car() {
            global $wilayas, $car_models;
            
            echo "<h2>Ajouter une Voiture</h2>";
            
            echo '<form method="POST">
                <input type="hidden" name="action" value="add_car">
                
                <label>Marque:</label>
                <select name="marque" id="marque" required>
                    <option value="">Sélectionner marque</option>';
            foreach ($car_models as $marque => $modeles) {
                echo "<option value='$marque'>$marque</option>";
            }
            echo '</select>
                
                <label>Modèle:</label>
                <select name="modele" id="modele" required>
                    <option value="">Sélectionner modèle</option>
                </select>
                
                <label>Année:</label>
                <input type="number" name="annee" min="1990" max="2025" required>
                
                <label>Matricule (ex: 15231 113 31):</label>
                <input type="text" name="matricule" placeholder="15231 113 31" required pattern="[0-9]{5} [0-9]{3} [0-9]{2}">
                
                <label>Couleur:</label>
                <input type="text" name="couleur" required>
                
                <label>Kilométrage:</label>
                <input type="number" name="kilometrage" value="0" required>
                
                <label>Wilaya:</label>
                <select name="wilaya_id" required>
                    <option value="">Sélectionner Wilaya</option>';
            foreach ($wilayas as $id => $nom) {
                echo "<option value='$id'>$id - $nom</option>";
            }
            echo '</select>
                
                <button type="submit">Ajouter la Voiture</button>
            </form>';
        }
        
        // FONCTION: Modifier une voiture (Agent)
        function include_edit_car() {
            global $car, $wilayas, $car_models;
            
            $car_id = $_GET['car_id'] ?? 0;
            $car_info = $car->getById($car_id);
            
            if (!$car_info) {
                echo "<p>Voiture non trouvée</p>";
                return;
            }
            
            echo "<h2>Modifier la Voiture</h2>";
            
            echo '<form method="POST">
                <input type="hidden" name="action" value="update_car">
                <input type="hidden" name="car_id" value="' . $car_id . '">
                
                <label>Marque:</label>
                <select name="marque" required>
                    <option value="">Sélectionner marque</option>';
            foreach ($car_models as $marque => $modeles) {
                $selected = ($marque === $car_info['marque']) ? 'selected' : '';
                echo "<option value='$marque' $selected>$marque</option>";
            }
            echo '</select>
                
                <label>Modèle:</label>
                <input type="text" name="modele" value="' . htmlspecialchars($car_info['modele']) . '" required>
                
                <label>Année:</label>
                <input type="number" name="annee" value="' . $car_info['annee'] . '" min="1990" max="2025" required>
                
                <label>Matricule:</label>
                <input type="text" name="matricule" value="' . htmlspecialchars($car_info['matricule']) . '" required>
                
                <label>Couleur:</label>
                <input type="text" name="couleur" value="' . htmlspecialchars($car_info['couleur']) . '" required>
                
                <label>Kilométrage:</label>
                <input type="number" name="kilometrage" value="' . $car_info['kilometrage'] . '" required>
                
                <label>Wilaya:</label>
                <select name="wilaya_id" required>';
            foreach ($wilayas as $id => $nom) {
                $selected = ($id == $car_info['wilaya_id']) ? 'selected' : '';
                echo "<option value='$id' $selected>$id - $nom</option>";
            }
            echo '</select>
                
                <button type="submit">Mettre à Jour</button>
            </form>';
        }
            
        case 'register':
            if ($user->register($_POST)) {
                $success = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
            } else {
                $error = "Erreur lors de l'inscription";
            }
            break;
            
        case 'logout':
            $user->logout();
            break;
            
        case 'add_company':
            if ($user->getRole() === 'admin') {
                $_POST['created_by'] = $user->getUserId();
                if ($company->create($_POST)) {
                    $success = "Entreprise ajoutée avec succès";
                } else {
                    $error = "Erreur lors de l'ajout de l'entreprise";
                }
            }
            break;
            
        case 'add_car':
            if ($user->getRole() === 'agent' || $user->getRole() === 'admin') {
                $_POST['company_id'] = $user->getCompanyId();
                $_POST['prix_journalier'] = Car::calculatePrice($_POST['annee'], $_POST['marque']);
                if ($car->create($_POST)) {
                    $success = "Voiture ajoutée avec succès";
                } else {
                    $error = "Erreur lors de l'ajout de la voiture";
                }
            }
            break;

</body>
</html>
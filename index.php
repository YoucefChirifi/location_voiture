<?php
// ================== CONFIGURATION & BASE DE DONNÉES =====================
session_start();
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'location_voiture_dz');

// ===================== CLASSE DATABASE ET SÉCURITÉ ======================
class Database {
    private static $instance = null;
    private $conn;
    private function __construct() {
        try {
            $this->conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        } catch(PDOException $e) {
            die("Erreur de connexion: " . $e->getMessage());
        }
    }
    public static function getInstance() {
        if (self::$instance === null) self::$instance = new Database();
        return self::$instance;
    }
    public function getConnection() { return $this->conn; }
}
// ==================== ARRAYS INIT (wilayas, models) =====================
$wilayas = [
 1 => 'Adrar', 2 => 'Chlef', 3 => 'Laghouat', 4 => 'Oum El Bouaghi', 5 => 'Batna', 6 => 'Béjaïa', 7 => 'Biskra', 8 => 'Béchar', 
 9 => 'Blida', 10 => 'Bouira', 11 => 'Tamanrasset', 12 => 'Tébessa', 13 => 'Tlemcen', 14 => 'Tiaret', 15 => 'Tizi Ouzou', 16 => 'Alger',
 17 => 'Djelfa', 18 => 'Jijel', 19 => 'Sétif', 20 => 'Saïda', 21 => 'Skikda', 22 => 'Sidi Bel Abbès', 23 => 'Annaba', 24 => 'Guelma',
 25 => 'Constantine', 26 => 'Médéa', 27 => 'Mostaganem', 28 => 'M'Sila', 29 => 'Mascara', 30 => 'Ouargla', 31 => 'Oran', 32 => 'El Bayadh',
 33 => 'Illizi', 34 => 'Bordj Bou Arreridj', 35 => 'Boumerdès', 36 => 'El Tarf', 37 => 'Tindouf', 38 => 'Tissemsilt', 39 => 'El Oued', 40 => 'Khenchela',
 41 => 'Souk Ahras', 42 => 'Tipaza', 43 => 'Mila', 44 => 'Aïn Defla', 45 => 'Naâma', 46 => 'Aïn Témouchent', 47 => 'Ghardaïa', 48 => 'Relizane',
 49 => 'Timimoun', 50 => 'Bordj Badji Mokhtar', 51 => 'Ouled Djellal', 52 => 'Béni Abbès', 53 => 'In Salah', 54 => 'In Guezzam', 55 => 'Touggourt',
 56 => 'Djanet', 57 => 'El M'Ghair', 58 => 'El Meniaa', 59 => 'Aflou', 60 => 'El Abiodh Sidi Cheikh', 61 => 'El Aricha', 62 => 'El Kantara',
 63 => 'Barika', 64 => 'Bou Saâda', 65 => 'Bir El Ater', 66 => 'Ksar El Boukhari', 67 => 'Ksar Chellala', 68 => 'Aïn Oussara', 69 => 'Messaad'
];
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
// ===================== CLASSE USER ======================
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
// ===================== CLASSE COMPANY ======================
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
// ===================== CLASSE CAR ======================
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
    
    public static function calculatePrice($annee, $marque) {
        $current_year = date('Y');
        $age = $current_year - $annee;
        
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
        $prix = $prix - ($age * 300);
        
        if ($prix < 4000) $prix = 4000;
        if ($prix > 20000) $prix = 20000;
        
        return $prix;
    }
}
// ===================== CLASSE RESERVATION ======================
class Reservation {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($data) {
        try {
            $this->db->beginTransaction();
            
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
            
            $stmt = $this->db->prepare("SELECT * FROM reservations WHERE reservation_id = ?");
            $stmt->execute([$reservation_id]);
            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $this->db->prepare("UPDATE reservations SET statut = 'terminee' WHERE reservation_id = ?");
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
// ===================== INITIALISATION ======================
$user = new User();
$company = new Company();
$car = new Car();
$reservation = new Reservation();

// ===================== Gestion des actions POST ======================
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

// ===================== Gestion de la date simulée pour tester l'application ======================
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

// ===================== Vérifier et mettre à jour automatiquement les réservations terminées ======================
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

// ===================== Déterminer la page à afficher ======================
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
   // FONCTION: Réservations de l'entreprise (Agent)
        function include_agent_reservations() {
            global $reservation, $user;
            
            echo "<h2>Réservations de l'Entreprise</h2>";
            
            $reservations = $reservation->getByCompany($user->getCompanyId());
            
            if (empty($reservations)) {
                echo "<p>Aucune réservation pour le moment.</p>";
                return;
            }
            
            echo "<table border='1'>
                    <tr>
                        <th>ID</th>
                        <th>Client</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Voiture</th>
                        <th>Date Début</th>
                        <th>Date Fin</th>
                        <th>Prix Total (DA)</th>
                        <th>Statut</th>
                        <th>Paiement</th>
                        <th>Actions</th>
                    </tr>";
            
            foreach ($reservations as $r) {
                echo "<tr>
                        <td>{$r['reservation_id']}</td>
                        <td>" . htmlspecialchars($r['prenom'] . ' ' . $r['nom']) . "</td>
                        <td>" . htmlspecialchars($r['email']) . "</td>
                        <td>" . htmlspecialchars($r['telephone']) . "</td>
                        <td>" . htmlspecialchars($r['marque'] . ' ' . $r['modele'] . ' (' . $r['matricule'] . ')') . "</td>
                        <td>{$r['date_debut']}</td>
                        <td>{$r['date_fin']}</td>
                        <td>" . number_format($r['prix_total'], 2) . " DA</td>
                        <td>{$r['statut']}</td>
                        <td>{$r['paiement_statut']}</td>
                        <td>";
                
                if ($r['paiement_statut'] === 'non_paye') {
                    echo "<form method='POST' style='display:inline;'>
                            <input type='hidden' name='action' value='update_payment'>
                            <input type='hidden' name='reservation_id' value='{$r['reservation_id']}'>
                            <input type='hidden' name='paiement_statut' value='paye'>
                            <button type='submit'>Marquer Payé</button>
                          </form>";
                }
                
                if ($r['statut'] === 'en_cours' || $r['statut'] === 'en_attente') {
                    echo "<form method='POST' style='display:inline;'>
                            <input type='hidden' name='action' value='complete_reservation'>
                            <input type='hidden' name='reservation_id' value='{$r['reservation_id']}'>
                            <button type='submit'>Terminer</button>
                          </form>";
                }
                
                echo "</td></tr>";
            }
            echo "</table>";
        }
        
        // FONCTION: Parcourir les voitures disponibles (Client)
        function include_browse_cars() {
            global $company, $car, $wilayas;
            
            echo "<h2>Louer une Voiture</h2>";
            
            // Formulaire de recherche
            echo '<form method="GET">
                <input type="hidden" name="page" value="browse_cars">
                
                <label>Entreprise:</label>
                <select name="company_id" required>
                    <option value="">Sélectionner une entreprise</option>';
            
            $companies = $company->getAll();
            foreach ($companies as $comp) {
                $selected = (isset($_GET['company_id']) && $_GET['company_id'] == $comp['company_id']) ? 'selected' : '';
                echo "<option value='{$comp['company_id']}' $selected>" . htmlspecialchars($comp['nom']) . "</option>";
            }
            
            echo '</select>
                
                <label>Date de début:</label>
                <input type="date" name="date_debut" value="' . ($_GET['date_debut'] ?? '') . '" required>
                
                <label>Date de fin:</label>
                <input type="date" name="date_fin" value="' . ($_GET['date_fin'] ?? '') . '" required>
                
                <button type="submit">Rechercher</button>
            </form>';
            
            // Afficher les voitures disponibles
            if (isset($_GET['company_id']) && isset($_GET['date_debut']) && isset($_GET['date_fin'])) {
                $available_cars = $car->getAvailableCars($_GET['company_id'], $_GET['date_debut'], $_GET['date_fin']);
                
                if (empty($available_cars)) {
                    echo "<p>Aucune voiture disponible pour ces dates.</p>";
                } else {
                    echo "<h3>Voitures Disponibles</h3>";
                    echo "<div class='car-grid'>";
                    
                    foreach ($available_cars as $c) {
                        $date1 = new DateTime($_GET['date_debut']);
                        $date2 = new DateTime($_GET['date_fin']);
                        $jours = $date1->diff($date2)->days + 1;
                        $prix_total = $c['prix_journalier'] * $jours;
                        
                        echo "<div class='car-card'>
                                <h4>" . htmlspecialchars($c['marque'] . ' ' . $c['modele']) . "</h4>
                                <p>Année: {$c['annee']}</p>
                                <p>Matricule: " . htmlspecialchars($c['matricule']) . "</p>
                                <p>Couleur: " . htmlspecialchars($c['couleur']) . "</p>
                                <p>Wilaya: " . htmlspecialchars($c['wilaya_nom']) . "</p>
                                <p>Prix par jour: " . number_format($c['prix_journalier'], 2) . " DA</p>
                                <p><strong>Prix total ({$jours} jours): " . number_format($prix_total, 2) . " DA</strong></p>
                                
                                <form method='POST'>
                                    <input type='hidden' name='action' value='make_reservation'>
                                    <input type='hidden' name='car_id' value='{$c['car_id']}'>
                                    <input type='hidden' name='company_id' value='{$_GET['company_id']}'>
                                    <input type='hidden' name='date_debut' value='{$_GET['date_debut']}'>
                                    <input type='hidden' name='date_fin' value='{$_GET['date_fin']}'>
                                    <button type='submit'>Réserver</button>
                                </form>
                              </div>";
                    }
                    
                    echo "</div>";
                }
            }
        }
        
        // FONCTION: Mes réservations (Client)
        function include_my_reservations() {
            global $reservation, $user;
            
            echo "<h2>Mes Réservations</h2>";
            
            $reservations = $reservation->getByUser($user->getUserId());
            
            if (empty($reservations)) {
                echo "<p>Vous n'avez pas encore de réservation.</p>";
                return;
            }
            
            echo "<table border='1'>
                    <tr>
                        <th>ID</th>
                        <th>Entreprise</th>
                        <th>Voiture</th>
                        <th>Matricule</th>
                        <th>Date Début</th>
                        <th>Date Fin</th>
                        <th>Prix Total (DA)</th>
                        <th>Statut</th>
                        <th>Paiement</th>
                        <th>Actions</th>
                    </tr>";
            
            foreach ($reservations as $r) {
                echo "<tr>
                        <td>{$r['reservation_id']}</td>
                        <td>" . htmlspecialchars($r['company_nom']) . "</td>
                        <td>" . htmlspecialchars($r['marque'] . ' ' . $r['modele']) . "</td>
                        <td>" . htmlspecialchars($r['matricule']) . "</td>
                        <td>{$r['date_debut']}</td>
                        <td>{$r['date_fin']}</td>
                        <td>" . number_format($r['prix_total'], 2) . " DA</td>
                        <td>{$r['statut']}</td>
                        <td>{$r['paiement_statut']}</td>
                        <td>";
                
                if ($r['statut'] === 'en_attente' && $r['paiement_statut'] === 'non_paye') {
                    echo "<form method='POST' style='display:inline;'>
                            <input type='hidden' name='action' value='simulate_payment'>
                            <input type='hidden' name='reservation_id' value='{$r['reservation_id']}'>
                            <button type='submit'>Payer (Virtuel)</button>
                          </form>";
                }
                
                if ($r['statut'] === 'en_attente') {
                    echo "<form method='POST' style='display:inline;'>
                            <input type='hidden' name='action' value='cancel_reservation'>
                            <input type='hidden' name='reservation_id' value='{$r['reservation_id']}'>
                            <button type='submit' onclick='return confirm(\"Annuler la réservation?\")'>Annuler</button>
                          </form>";
                }
                
                echo "</td></tr>";
            }
            echo "</table>";
        }
        
        // FONCTION: Toutes les réservations (Admin)
        function include_all_reservations() {
            global $reservation;
            
            echo "<h2>Toutes les Réservations</h2>";
            
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("
                SELECT r.*, c.marque, c.modele, c.matricule, 
                       u.nom, u.prenom, u.email, co.nom as company_nom
                FROM reservations r
                JOIN cars c ON r.car_id = c.car_id
                JOIN users u ON r.user_id = u.user_id
                JOIN companies co ON r.company_id = co.company_id
                ORDER BY r.created_at DESC
            ");
            $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($reservations)) {
                echo "<p>Aucune réservation dans le système.</p>";
                return;
            }
            
            echo "<table border='1'>
                    <tr>
                        <th>ID</th>
                        <th>Entreprise</th>
                        <th>Client</th>
                        <th>Voiture</th>
                        <th>Date Début</th>
                        <th>Date Fin</th>
                        <th>Prix Total (DA)</th>
                        <th>Statut</th>
                        <th>Paiement</th>
                    </tr>";
            
            foreach ($reservations as $r) {
                echo "<tr>
                        <td>{$r['reservation_id']}</td>
                        <td>" . htmlspecialchars($r['company_nom']) . "</td>
                        <td>" . htmlspecialchars($r['prenom'] . ' ' . $r['nom'] . ' (' . $r['email'] . ')') . "</td>
                        <td>" . htmlspecialchars($r['marque'] . ' ' . $r['modele'] . ' (' . $r['matricule'] . ')') . "</td>
                        <td>{$r['date_debut']}</td>
                        <td>{$r['date_fin']}</td>
                        <td>" . number_format($r['prix_total'], 2) . " DA</td>
                        <td>{$r['statut']}</td>
                        <td>{$r['paiement_statut']}</td>
                    </tr>";
            }
            echo "</table>";
        }
        ?>
    </div>

<?php else: ?>
    <!-- PAGE DE CONNEXION / INSCRIPTION -->
    <div class="auth-container">
        <h1>Location Voiture DZ</h1>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if (!isset($_GET['mode']) || $_GET['mode'] === 'login'): ?>
            <!-- FORMULAIRE DE CONNEXION -->
            <h2>Connexion</h2>
            <form method="POST">
                <input type="hidden" name="action" value="login">
                
                <label>Email:</label>
                <input type="email" name="email" required>
                
                <label>Mot de passe:</label>
                <input type="password" name="password" required>
                
                <button type="submit">Se Connecter</button>
            </form>
            
            <p>Pas encore de compte? <a href="?mode=register">S'inscrire</a></p>
            
        <?php else: ?>
            <!-- FORMULAIRE D'INSCRIPTION -->
            <h2>Inscription</h2>
            <form method="POST">
                <input type="hidden" name="action" value="register">
                
                <label>Type de compte:</label>
                <select name="role" id="role" required>
                    <option value="client">Client</option>
                    <option value="agent">Agent d'entreprise</option>
                </select>
                
                <div id="company_select" style="display:none;">
                    <label>Entreprise:</label>
                    <select name="company_id" id="company_id">
                        <option value="">Sélectionner une entreprise</option>
                        <?php
                        $companies = $company->getAll();
                        foreach ($companies as $comp) {
                            echo "<option value='{$comp['company_id']}'>" . htmlspecialchars($comp['nom']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <label>Prénom:</label>
                <input type="text" name="prenom" required>
                
                <label>Nom:</label>
                <input type="text" name="nom" required>
                
                <label>Email:</label>
                <input type="email" name="email" required>
                
                <label>Téléphone:</label>
                <input type="text" name="telephone" required>
                
                <label>Mot de passe:</label>
                <input type="password" name="password" required>
                
                <label>Wilaya:</label>
                <select name="wilaya_id" required>
                    <option value="">Sélectionner Wilaya</option>
                    <?php foreach ($wilayas as $id => $nom): ?>
                        <option value="<?php echo $id; ?>"><?php echo $id . ' - ' . $nom; ?></option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit">S'inscrire</button>
            </form>
            
            <p>Déjà un compte? <a href="?mode=login">Se connecter</a></p>
        <?php endif; ?>
    </div>
    
    <script>
        // Afficher le sélecteur d'entreprise pour les agents
        document.addEventListener('DOMContentLoaded', function() {
            const roleSelect = document.getElementById('role');
            const companyDiv = document.getElementById('company_select');
            const companySelect = document.getElementById('company_id');
            
            if (roleSelect) {
                roleSelect.addEventListener('change', function() {
                    if (this.value === 'agent') {
                        companyDiv.style.display = 'block';
                        companySelect.required = true;
                    } else {
                        companyDiv.style.display = 'none';
                        companySelect.required = false;
                    }
                });
            }
        });
        
        // Gestion dynamique des modèles de voitures
        const carModels = <?php echo json_encode($car_models); ?>;
        
        const marqueSelect = document.getElementById('marque');
        const modeleSelect = document.getElementById('modele');
        
        if (marqueSelect && modeleSelect) {
            marqueSelect.addEventListener('change', function() {
                modeleSelect.innerHTML = '<option value="">Sélectionner modèle</option>';
                
                if (this.value && carModels[this.value]) {
                    carModels[this.value].forEach(function(modele) {
                        const option = document.createElement('option');
                        option.value = modele;
                        option.textContent = modele;
                        modeleSelect.appendChild(option);
                    });
                }
            });
        }
    </script>
<?php endif; ?>
<?php
// Gestion de l'action de paiement simulé
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'simulate_payment') {
    if ($user->getRole() === 'client') {
        if ($reservation->updatePaymentStatus($_POST['reservation_id'], 'paye')) {
            $success = "Paiement effectué avec succès (simulation)";
            header("Location: index.php?page=my_reservations");
            exit();
        } else {
            $error = "Erreur lors du paiement";
        }
    }
}

// Fonction pour créer la base de données et les tables si elles n'existent pas
function setupDatabase() {
    try {
        // Connexion sans base de données
        $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Créer la base de données
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE " . DB_NAME);
        
        // Table wilayas
        $pdo->exec("CREATE TABLE IF NOT EXISTS wilayas (
            wilaya_id INT PRIMARY KEY,
            nom VARCHAR(100) NOT NULL,
            code VARCHAR(10)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Table companies
        $pdo->exec("CREATE TABLE IF NOT EXISTS companies (
            company_id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            telephone VARCHAR(20),
            adresse TEXT,
            wilaya_id INT,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (wilaya_id) REFERENCES wilayas(wilaya_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Table users
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            user_id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            nom VARCHAR(50) NOT NULL,
            prenom VARCHAR(50) NOT NULL,
            telephone VARCHAR(20),
            role ENUM('admin', 'agent', 'client') DEFAULT 'client',
            company_id INT NULL,
            wilaya_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE SET NULL,
            FOREIGN KEY (wilaya_id) REFERENCES wilayas(wilaya_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Table cars
        $pdo->exec("CREATE TABLE IF NOT EXISTS cars (
            car_id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            marque VARCHAR(50) NOT NULL,
            modele VARCHAR(50) NOT NULL,
            annee INT NOT NULL,
            matricule VARCHAR(20) UNIQUE NOT NULL,
            couleur VARCHAR(30),
            kilometrage INT DEFAULT 0,
            statut ENUM('disponible', 'louee', 'maintenance') DEFAULT 'disponible',
            prix_journalier DECIMAL(10,2) NOT NULL,
            wilaya_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE,
            FOREIGN KEY (wilaya_id) REFERENCES wilayas(wilaya_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Table reservations
        $pdo->exec("CREATE TABLE IF NOT EXISTS reservations (
            reservation_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            car_id INT NOT NULL,
            company_id INT NOT NULL,
            date_debut DATE NOT NULL,
            date_fin DATE NOT NULL,
            prix_total DECIMAL(10,2) NOT NULL,
            statut ENUM('en_attente', 'en_cours', 'terminee', 'annulee') DEFAULT 'en_attente',
            paiement_statut ENUM('non_paye', 'paye', 'rembourse') DEFAULT 'non_paye',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (car_id) REFERENCES cars(car_id) ON DELETE CASCADE,
            FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Insérer les wilayas
        $pdo->exec("INSERT IGNORE INTO wilayas (wilaya_id, nom) VALUES
            (1, 'Adrar'), (2, 'Chlef'), (3, 'Laghouat'), (4, 'Oum El Bouaghi'),
            (5, 'Batna'), (6, 'Béjaïa'), (7, 'Biskra'), (8, 'Béchar'),
            (9, 'Blida'), (10, 'Bouira'), (11, 'Tamanrasset'), (12, 'Tébessa'),
            (13, 'Tlemcen'), (14, 'Tiaret'), (15, 'Tizi Ouzou'), (16, 'Alger'),
            (17, 'Djelfa'), (18, 'Jijel'), (19, 'Sétif'), (20, 'Saïda'),
            (21, 'Skikda'), (22, 'Sidi Bel Abbès'), (23, 'Annaba'), (24, 'Guelma'),
            (25, 'Constantine'), (26, 'Médéa'), (27, 'Mostaganem'), (28, 'M\'Sila'),
            (29, 'Mascara'), (30, 'Ouargla'), (31, 'Oran'), (32, 'El Bayadh'),
            (33, 'Illizi'), (34, 'Bordj Bou Arreridj'), (35, 'Boumerdès'), (36, 'El Tarf'),
            (37, 'Tindouf'), (38, 'Tissemsilt'), (39, 'El Oued'), (40, 'Khenchela'),
            (41, 'Souk Ahras'), (42, 'Tipaza'), (43, 'Mila'), (44, 'Aïn Defla'),
            (45, 'Naâma'), (46, 'Aïn Témouchent'), (47, 'Ghardaïa'), (48, 'Relizane'),
            (49, 'Timimoun'), (50, 'Bordj Badji Mokhtar'), (51, 'Ouled Djellal'),
            (52, 'Béni Abbès'), (53, 'In Salah'), (54, 'In Guezzam'), (55, 'Touggourt'),
            (56, 'Djanet'), (57, 'El M\'Ghair'), (58, 'El Meniaa'), (59, 'Aflou'),
            (60, 'El Abiodh Sidi Cheikh'), (61, 'El Aricha'), (62, 'El Kantara'),
            (63, 'Barika'), (64, 'Bou Saâda'), (65, 'Bir El Ater'), (66, 'Ksar El Boukhari'),
            (67, 'Ksar Chellala'), (68, 'Aïn Oussara'), (69, 'Messaad')");
        
        // Insérer un administrateur par défaut
        $pdo->exec("INSERT IGNORE INTO users (user_id, email, password, nom, prenom, telephone, role, wilaya_id) VALUES
            (1, 'admin@locationdz.com', MD5('admin123'), 'Admin', 'Système', '0555000000', 'admin', 16)");
        
        // Insérer 3 entreprises par défaut
        $pdo->exec("INSERT IGNORE INTO companies (company_id, nom, email, telephone, adresse, wilaya_id, created_by) VALUES
            (1, 'AutoLoc Alger', 'contact@autoloc-alger.dz', '0770123456', '25 Rue Didouche Mourad, Alger', 16, 1),
            (2, 'Oran Car Rental', 'info@orancar.dz', '0771234567', '15 Boulevard de la Soummam, Oran', 31, 1),
            (3, 'Constantine Auto', 'contact@constantine-auto.dz', '0772345678', '8 Rue Larbi Ben M\'Hidi, Constantine', 25, 1)");
        
        // Insérer des agents pour chaque entreprise
        $pdo->exec("INSERT IGNORE INTO users (email, password, nom, prenom, telephone, role, company_id, wilaya_id) VALUES
            ('agent1@autoloc-alger.dz', MD5('agent123'), 'Benali', 'Karim', '0660111222', 'agent', 1, 16),
            ('agent2@orancar.dz', MD5('agent123'), 'Mansouri', 'Fatima', '0661222333', 'agent', 2, 31),
            ('agent3@constantine-auto.dz', MD5('agent123'), 'Cherif', 'Ahmed', '0662333444', 'agent', 3, 25)");
        
        // Insérer des clients d'exemple
        $pdo->exec("INSERT IGNORE INTO users (email, password, nom, prenom, telephone, role, wilaya_id) VALUES
            ('client1@email.dz', MD5('client123'), 'Boudiaf', 'Sofiane', '0770999888', 'client', 16),
            ('client2@email.dz', MD5('client123'), 'Hamidi', 'Amina', '0771888777', 'client', 31),
            ('client3@email.dz', MD5('client123'), 'Meziani', 'Youcef', '0772777666', 'client', 25)");
        
        // Insérer des voitures d'exemple pour chaque entreprise
        $pdo->exec("INSERT IGNORE INTO cars (company_id, marque, modele, annee, matricule, couleur, kilometrage, statut, prix_journalier, wilaya_id) VALUES
            (1, 'Dacia', 'Logan', 2020, '12345 116 16', 'Blanc', 45000, 'disponible', 5500.00, 16),
            (1, 'Renault', 'Clio', 2021, '23456 116 16', 'Gris', 32000, 'disponible', 6200.00, 16),
            (1, 'Peugeot', '208', 2019, '34567 116 16', 'Rouge', 58000, 'disponible', 5800.00, 16),
            (2, 'Toyota', 'Corolla', 2022, '45678 131 31', 'Noir', 18000, 'disponible', 8500.00, 31),
            (2, 'Hyundai', 'i20', 2020, '56789 131 31', 'Bleu', 41000, 'disponible', 5900.00, 31),
            (2, 'Volkswagen', 'Golf', 2021, '67890 131 31', 'Blanc', 35000, 'disponible', 7200.00, 31),
            (3, 'Kia', 'Rio', 2020, '78901 125 25', 'Gris', 47000, 'disponible', 5700.00, 25),
            (3, 'Nissan', 'Qashqai', 2021, '89012 125 25', 'Noir', 29000, 'disponible', 7800.00, 25),
            (3, 'Ford', 'Focus', 2019, '90123 125 25', 'Blanc', 62000, 'disponible', 6100.00, 25)");
        
        return true;
    } catch(PDOException $e) {
        die("Erreur lors de la configuration de la base de données: " . $e->getMessage());
    }
}

// Vérifier et créer la base de données si nécessaire
try {
    $testConn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
} catch(PDOException $e) {
    setupDatabase();
}

// Fonction pour afficher tous les utilisateurs (Admin)
function include_all_users() {
    global $wilayas;
    
    echo "<h2>Gestion des Utilisateurs</h2>";
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("
        SELECT u.*, c.nom as company_nom, w.nom as wilaya_nom
        FROM users u
        LEFT JOIN companies c ON u.company_id = c.company_id
        LEFT JOIN wilayas w ON u.wilaya_id = w.wilaya_id
        ORDER BY u.created_at DESC
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Ajouter un Utilisateur</h3>";
    echo '<form method="POST">
        <input type="hidden" name="action" value="add_user">
        
        <label>Email:</label>
        <input type="email" name="email" required>
        
        <label>Mot de passe:</label>
        <input type="password" name="password" required>
        
        <label>Prénom:</label>
        <input type="text" name="prenom" required>
        
        <label>Nom:</label>
        <input type="text" name="nom" required>
        
        <label>Téléphone:</label>
        <input type="text" name="telephone" required>
        
        <label>Rôle:</label>
        <select name="role" required>
            <option value="client">Client</option>
            <option value="agent">Agent</option>
            <option value="admin">Administrateur</option>
        </select>
        
        <label>Wilaya:</label>
        <select name="wilaya_id" required>
            <option value="">Sélectionner</option>';
    foreach ($wilayas as $id => $nom) {
        echo "<option value='$id'>$id - $nom</option>";
    }
    echo '</select>
        
        <button type="submit">Ajouter</button>
    </form>';
    
    echo "<h3>Liste des Utilisateurs</h3>";
    echo "<table border='1'>
            <tr>
                <th>ID</th>
                <th>Prénom Nom</th>
                <th>Email</th>
                <th>Téléphone</th>
                <th>Rôle</th>
                <th>Entreprise</th>
                <th>Wilaya</th>
                <th>Actions</th>
            </tr>";
    
    foreach ($users as $u) {
        echo "<tr>
                <td>{$u['user_id']}</td>
                <td>" . htmlspecialchars($u['prenom'] . ' ' . $u['nom']) . "</td>
                <td>" . htmlspecialchars($u['email']) . "</td>
                <td>" . htmlspecialchars($u['telephone']) . "</td>
                <td>{$u['role']}</td>
                <td>" . htmlspecialchars($u['company_nom'] ?? 'N/A') . "</td>
                <td>" . htmlspecialchars($u['wilaya_nom']) . "</td>
                <td>";
        
        if ($u['user_id'] != 1) { // Ne pas supprimer l'admin principal
            echo "<form method='POST' style='display:inline;'>
                    <input type='hidden' name='action' value='delete_user'>
                    <input type='hidden' name='user_id' value='{$u['user_id']}'>
                    <button type='submit' onclick='return confirm(\"Supprimer?\")'>Supprimer</button>
                  </form>";
        }
        
        echo "</td></tr>";
    }
    echo "</table>";
}

// Ajouter l'action d'ajout d'utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_user' && $user->getRole() === 'admin') {
        if ($user->register($_POST)) {
            $success = "Utilisateur ajouté avec succès";
            header("Location: index.php?page=all_users");
            exit();
        } else {
            $error = "Erreur lors de l'ajout de l'utilisateur";
        }
    }
    
    if ($_POST['action'] === 'delete_user' && $user->getRole() === 'admin') {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM users WHERE user_id = ? AND user_id != 1");
        if ($stmt->execute([$_POST['user_id']])) {
            $success = "Utilisateur supprimé";
            header("Location: index.php?page=all_users");
            exit();
        }
    }
    
    if ($_POST['action'] === 'delete_company' && $user->getRole() === 'admin') {
        if ($company->delete($_POST['company_id'])) {
            $success = "Entreprise supprimée";
            header("Location: index.php?page=companies");
            exit();
        }
    }
}
?>
<script>
// ==========================================
// JAVASCRIPT - LOCATION VOITURE DZ
// ==========================================

document.addEventListener('DOMContentLoaded', function() {
    
    // ==========================================
    // GESTION DU FORMULAIRE D'INSCRIPTION
    // ==========================================
    const roleSelect = document.getElementById('role');
    const companyDiv = document.getElementById('company_select');
    const companySelect = document.getElementById('company_id');
    
    if (roleSelect) {
        roleSelect.addEventListener('change', function() {
            if (this.value === 'agent') {
                companyDiv.style.display = 'block';
                companySelect.required = true;
            } else {
                companyDiv.style.display = 'none';
                companySelect.required = false;
                companySelect.value = '';
            }
        });
    }
    
    // ==========================================
    // GESTION DYNAMIQUE DES MODÈLES DE VOITURES
    // ==========================================
    const carModelsData = {
        'Dacia': ['Logan', 'Sandero', 'Duster'],
        'Renault': ['Clio', 'Megane', 'Symbol', 'Kangoo'],
        'Peugeot': ['206', '208', '301', '308'],
        'Citroën': ['C3', 'C4'],
        'Volkswagen': ['Polo', 'Golf'],
        'Skoda': ['Octavia'],
        'Toyota': ['Yaris', 'Corolla', 'Hilux'],
        'Hyundai': ['i10', 'i20', 'Accent'],
        'Kia': ['Picanto', 'Rio'],
        'Nissan': ['Sunny', 'Qashqai'],
        'Suzuki': ['Swift'],
        'Fiat': ['Punto', 'Tipo'],
        'Opel': ['Corsa'],
        'Ford': ['Fiesta', 'Focus'],
        'Mitsubishi': ['Lancer'],
        'Chevrolet': ['Aveo']
    };
    
    const marqueSelect = document.getElementById('marque');
    const modeleSelect = document.getElementById('modele');
    
    if (marqueSelect && modeleSelect) {
        marqueSelect.addEventListener('change', function() {
            // Réinitialiser le select des modèles
            modeleSelect.innerHTML = '<option value="">Sélectionner modèle</option>';
            
            const selectedMarque = this.value;
            
            if (selectedMarque && carModelsData[selectedMarque]) {
                carModelsData[selectedMarque].forEach(function(modele) {
                    const option = document.createElement('option');
                    option.value = modele;
                    option.textContent = modele;
                    modeleSelect.appendChild(option);
                });
            }
        });
    }
    
    // ==========================================
    // VALIDATION DU MATRICULE ALGÉRIEN
    // ==========================================
    const matriculeInputs = document.querySelectorAll('input[name="matricule"]');
    
    matriculeInputs.forEach(function(input) {
        input.addEventListener('input', function() {
            // Format: 15231 113 31
            let value = this.value.replace(/[^0-9]/g, '');
            
            if (value.length > 10) {
                value = value.substr(0, 10);
            }
            
            let formatted = '';
            if (value.length > 0) {
                formatted = value.substr(0, 5);
            }
            if (value.length >= 6) {
                formatted += ' ' + value.substr(5, 3);
            }
            if (value.length >= 9) {
                formatted += ' ' + value.substr(8, 2);
            }
            
            this.value = formatted;
        });
        
        input.addEventListener('blur', function() {
            const pattern = /^[0-9]{5} [0-9]{3} [0-9]{2}$/;
            if (this.value && !pattern.test(this.value)) {
                alert('Format du matricule invalide. Format attendu: 15231 113 31');
                this.focus();
            }
        });
    });
    
    // ==========================================
    // VALIDATION DES DATES DE RÉSERVATION
    // ==========================================
    const dateDebutInputs = document.querySelectorAll('input[name="date_debut"]');
    const dateFinInputs = document.querySelectorAll('input[name="date_fin"]');
    
    dateDebutInputs.forEach(function(dateDebut, index) {
        const dateFin = dateFinInputs[index];
        
        if (dateDebut && dateFin) {
            dateDebut.addEventListener('change', function() {
                dateFin.min = this.value;
                
                if (dateFin.value && dateFin.value < this.value) {
                    dateFin.value = this.value;
                }
            });
            
            dateFin.addEventListener('change', function() {
                if (dateDebut.value && this.value < dateDebut.value) {
                    alert('La date de fin doit être après la date de début');
                    this.value = dateDebut.value;
                }
            });
        }
    });
    
    // ==========================================
    // CONFIRMATION DE SUPPRESSION
    // ==========================================
    const deleteButtons = document.querySelectorAll('button[onclick*="confirm"]');
    
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            const confirmed = confirm('Êtes-vous sûr de vouloir supprimer cet élément ?');
            if (!confirmed) {
                e.preventDefault();
                return false;
            }
        });
    });
    
    // ==========================================
    // CALCUL AUTOMATIQUE DU PRIX TOTAL
    // ==========================================
    function calculateTotalPrice() {
        const dateDebut = document.querySelector('input[name="date_debut"]');
        const dateFin = document.querySelector('input[name="date_fin"]');
        const prixJournalier = document.querySelector('[data-prix-journalier]');
        const totalDisplay = document.getElementById('prix-total-display');
        
        if (dateDebut && dateFin && prixJournalier && totalDisplay) {
            if (dateDebut.value && dateFin.value) {
                const debut = new Date(dateDebut.value);
                const fin = new Date(dateFin.value);
                const diffTime = Math.abs(fin - debut);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                const prix = parseFloat(prixJournalier.getAttribute('data-prix-journalier'));
                const total = diffDays * prix;
                
                totalDisplay.innerHTML = `Prix total: ${total.toFixed(2)} DA (${diffDays} jours)`;
            }
        }
    }
    
    // Appeler la fonction si les éléments existent
    const dateInputsForCalc = document.querySelectorAll('input[name="date_debut"], input[name="date_fin"]');
    dateInputsForCalc.forEach(function(input) {
        input.addEventListener('change', calculateTotalPrice);
    });
    
    // ==========================================
    // FILTRAGE DES TABLEAUX
    // ==========================================
    const searchInputs = document.querySelectorAll('.table-search');
    
    searchInputs.forEach(function(searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const table = this.closest('.table-container').querySelector('table');
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(function(row) {
                const text = row.textContent.toLowerCase();
                if (text.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
    
    // ==========================================
    // ANIMATION DES STATISTIQUES
    // ==========================================
    const statNumbers = document.querySelectorAll('.stat-card p');
    
    statNumbers.forEach(function(statNumber) {
        const finalValue = parseInt(statNumber.textContent);
        if (!isNaN(finalValue)) {
            let currentValue = 0;
            const increment = finalValue / 50;
            
            const timer = setInterval(function() {
                currentValue += increment;
                if (currentValue >= finalValue) {
                    statNumber.textContent = finalValue;
                    clearInterval(timer);
                } else {
                    statNumber.textContent = Math.floor(currentValue);
                }
            }, 20);
        }
    });
    
    // ==========================================
    // GESTION DES ONGLETS (SI NÉCESSAIRE)
    // ==========================================
    const tabButtons = document.querySelectorAll('.tab-button');
    
    tabButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Désactiver tous les onglets
            document.querySelectorAll('.tab-button').forEach(function(btn) {
                btn.classList.remove('active');
            });
            
            document.querySelectorAll('.tab-content').forEach(function(content) {
                content.classList.remove('active');
            });
            
            // Activer l'onglet sélectionné
            this.classList.add('active');
            document.getElementById(targetTab).classList.add('active');
        });
    });
    
    // ==========================================
    // VALIDATION DU TÉLÉPHONE ALGÉRIEN
    // ==========================================
    const phoneInputs = document.querySelectorAll('input[name="telephone"]');
    
    phoneInputs.forEach(function(input) {
        input.addEventListener('input', function() {
            // Format algérien: 05XX XX XX XX ou 06XX XX XX XX ou 07XX XX XX XX
            let value = this.value.replace(/[^0-9]/g, '');
            
            if (value.length > 10) {
                value = value.substr(0, 10);
            }
            
            this.value = value;
        });
        
        input.addEventListener('blur', function() {
            const value = this.value;
            if (value && value.length === 10) {
                if (!value.startsWith('05') && !value.startsWith('06') && !value.startsWith('07')) {
                    alert('Numéro de téléphone algérien invalide. Doit commencer par 05, 06 ou 07');
                    this.focus();
                }
            } else if (value) {
                alert('Le numéro de téléphone doit contenir 10 chiffres');
                this.focus();
            }
        });
    });
    
    // ==========================================
    // PRÉVISUALISATION D'IMAGE (SI AJOUTÉ PLUS TARD)
    // ==========================================
    const imageInputs = document.querySelectorAll('input[type="file"][accept="image/*"]');
    
    imageInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const preview = document.getElementById(this.getAttribute('data-preview'));
            if (preview && this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
    
    // ==========================================
    // TOGGLE DE MOTS DE PASSE
    // ==========================================
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    
    togglePasswordButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const passwordInput = this.previousElementSibling;
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '🙈';
        });
    });
    
    // ==========================================
    // AUTO-COMPLÉTION DU MATRICULE
    // ==========================================
    function generateMatricule(wilayaId) {
        // Générer un numéro de série aléatoire
        const serial = Math.floor(10000 + Math.random() * 90000);
        
        // Type de véhicule et année (exemple: 1 pour voiture, année 23 pour 2023)
        const type = 1;
        const year = new Date().getFullYear() % 100;
        const typeYear = type + '' + year;
        
        // Formater avec l'ID de wilaya
        const wilaya = String(wilayaId).padStart(2, '0');
        
        return `${serial} ${typeYear} ${wilaya}`;
    }
    
    const autoMatriculeButton = document.getElementById('auto-matricule');
    if (autoMatriculeButton) {
        autoMatriculeButton.addEventListener('click', function() {
            const wilayaSelect = document.querySelector('select[name="wilaya_id"]');
            const matriculeInput = document.querySelector('input[name="matricule"]');
            
            if (wilayaSelect && matriculeInput && wilayaSelect.value) {
                matriculeInput.value = generateMatricule(wilayaSelect.value);
            } else {
                alert('Veuillez d\'abord sélectionner une wilaya');
            }
        });
    }
    
    // ==========================================
    // NOTIFICATION TOAST
    // ==========================================
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background-color: ${type === 'success' ? '#27ae60' : type === 'error' ? '#e74c3c' : '#3498db'};
            color: white;
            border-radius: 4px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 10000;
            animation: slideIn 0.3s ease-in;
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(function() {
            toast.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(function() {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }
    
    // Exposer la fonction showToast globalement
    window.showToast = showToast;
    
    // ==========================================
    // CONFIRMATION DE NAVIGATION (FORMULAIRES NON SAUVEGARDÉS)
    // ==========================================
    let formChanged = false;
    
    const allForms = document.querySelectorAll('form');
    allForms.forEach(function(form) {
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(function(input) {
            input.addEventListener('change', function() {
                formChanged = true;
            });
        });
        
        form.addEventListener('submit', function() {
            formChanged = false;
        });
    });
    
    window.addEventListener('beforeunload', function(e) {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = 'Vous avez des modifications non sauvegardées. Voulez-vous vraiment quitter?';
            return e.returnValue;
        }
    });
    
    // ==========================================
    // RACCOURCIS CLAVIER
    // ==========================================
    document.addEventListener('keydown', function(e) {
        // Ctrl + S pour sauvegarder (empêcher le comportement par défaut)
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            const submitButton = document.querySelector('form button[type="submit"]');
            if (submitButton) {
                submitButton.click();
            }
        }
        
        // Echap pour fermer les modales (si ajouté plus tard)
        if (e.key === 'Escape') {
            const modals = document.querySelectorAll('.modal.active');
            modals.forEach(function(modal) {
                modal.classList.remove('active');
            });
        }
    });
    
    // ==========================================
    // MISE À JOUR DU TEMPS RÉEL
    // ==========================================
    function updateClock() {
        const clockElement = document.getElementById('current-time');
        if (clockElement) {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            clockElement.textContent = `${hours}:${minutes}:${seconds}`;
        }
    }
    
    // Mettre à jour l'heure chaque seconde
    setInterval(updateClock, 1000);
    updateClock();
    
    // ==========================================
    // SMOOTH SCROLL
    // ==========================================
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // ==========================================
    // IMPRESSION
    // ==========================================
    const printButtons = document.querySelectorAll('.print-button');
    printButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            window.print();
        });
    });
    
    console.log('Location Voiture DZ - JavaScript chargé avec succès ✅');
});

// ==========================================
// FONCTIONS GLOBALES UTILITAIRES
// ==========================================

// Formater un nombre en devise algérienne
function formatCurrency(amount) {
    return new Intl.NumberFormat('fr-DZ', {
        style: 'decimal',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount) + ' DA';
}

// Formater une date
function formatDate(dateString) {
    const date = new Date(dateString);
    return new Intl.DateFormat('fr-DZ', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    }).format(date);
}

// Calculer la différence en jours entre deux dates
function daysDifference(date1, date2) {
    const d1 = new Date(date1);
    const d2 = new Date(date2);
    const diffTime = Math.abs(d2 - d1);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
}

// Valider un email
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Copier dans le presse-papier
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showToast('Copié dans le presse-papier', 'success');
    }).catch(function() {
        showToast('Erreur lors de la copie', 'error');
    });
}

</script>
</body>

</html>
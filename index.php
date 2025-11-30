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
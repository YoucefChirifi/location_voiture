<?php
// ===================================================================
// SYSTÈME DE LOCATION DE VOITURES - ALGÉRIE
// Structure verticale: PHP (haut) → HTML (milieu) → JS (bas)
// ===================================================================

session_start();

// ===================== CONFIGURATION =====================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'location_voiture_dz');

// ===================== CLASSE DATABASE =====================
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

// ===================== SETUP DATABASE =====================
function setupDatabase() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE " . DB_NAME);
        
        // Table wilayas
        $pdo->exec("CREATE TABLE IF NOT EXISTS wilayas (
            wilaya_id INT PRIMARY KEY,
            nom VARCHAR(100) NOT NULL
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
            role ENUM('admin', 'owner', 'agent', 'client') DEFAULT 'client',
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
        
        // Insérer les wilayas (69 wilayas)
        $wilayas_data = [
            1 => 'Adrar', 2 => 'Chlef', 3 => 'Laghouat', 4 => 'Oum El Bouaghi', 5 => 'Batna', 6 => 'Béjaïa', 7 => 'Biskra', 8 => 'Béchar',
            9 => 'Blida', 10 => 'Bouira', 11 => 'Tamanrasset', 12 => 'Tébessa', 13 => 'Tlemcen', 14 => 'Tiaret', 15 => 'Tizi Ouzou', 16 => 'Alger',
            17 => 'Djelfa', 18 => 'Jijel', 19 => 'Sétif', 20 => 'Saïda', 21 => 'Skikda', 22 => 'Sidi Bel Abbès', 23 => 'Annaba', 24 => 'Guelma',
            25 => 'Constantine', 26 => 'Médéa', 27 => 'Mostaganem', 28 => 'M\'Sila', 29 => 'Mascara', 30 => 'Ouargla', 31 => 'Oran', 32 => 'El Bayadh',
            33 => 'Illizi', 34 => 'Bordj Bou Arreridj', 35 => 'Boumerdès', 36 => 'El Tarf', 37 => 'Tindouf', 38 => 'Tissemsilt', 39 => 'El Oued', 40 => 'Khenchela',
            41 => 'Souk Ahras', 42 => 'Tipaza', 43 => 'Mila', 44 => 'Aïn Defla', 45 => 'Naâma', 46 => 'Aïn Témouchent', 47 => 'Ghardaïa', 48 => 'Relizane',
            49 => 'Timimoun', 50 => 'Bordj Badji Mokhtar', 51 => 'Ouled Djellal', 52 => 'Béni Abbès', 53 => 'In Salah', 54 => 'In Guezzam', 55 => 'Touggourt',
            56 => 'Djanet', 57 => 'El M\'Ghair', 58 => 'El Meniaa', 59 => 'Aflou', 60 => 'El Abiodh Sidi Cheikh', 61 => 'El Aricha', 62 => 'El Kantara',
            63 => 'Barika', 64 => 'Bou Saâda', 65 => 'Bir El Ater', 66 => 'Ksar El Boukhari', 67 => 'Ksar Chellala', 68 => 'Aïn Oussara', 69 => 'Messaad'
        ];
        
        foreach ($wilayas_data as $id => $nom) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO wilayas (wilaya_id, nom) VALUES (?, ?)");
            $stmt->execute([$id, $nom]);
        }
        
        // Créer admin par défaut
        $stmt = $pdo->prepare("INSERT IGNORE INTO users (user_id, email, password, nom, prenom, telephone, role, wilaya_id) VALUES (1, 'admin@locationdz.com', MD5('admin123'), 'Admin', 'Système', '0555000000', 'admin', 16)");
        $stmt->execute();
        
        return true;
    } catch(PDOException $e) {
        die("Erreur lors de la configuration: " . $e->getMessage());
    }
}

// Vérifier et créer la base de données si nécessaire
try {
    $testConn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
} catch(PDOException $e) {
    setupDatabase();
}

// ===================== DONNÉES STATIQUES =====================
$wilayas = [
    1 => 'Adrar', 2 => 'Chlef', 3 => 'Laghouat', 4 => 'Oum El Bouaghi', 5 => 'Batna', 6 => 'Béjaïa', 7 => 'Biskra', 8 => 'Béchar',
    9 => 'Blida', 10 => 'Bouira', 11 => 'Tamanrasset', 12 => 'Tébessa', 13 => 'Tlemcen', 14 => 'Tiaret', 15 => 'Tizi Ouzou', 16 => 'Alger',
    17 => 'Djelfa', 18 => 'Jijel', 19 => 'Sétif', 20 => 'Saïda', 21 => 'Skikda', 22 => 'Sidi Bel Abbès', 23 => 'Annaba', 24 => 'Guelma',
    25 => 'Constantine', 26 => 'Médéa', 27 => 'Mostaganem', 28 => 'M\'Sila', 29 => 'Mascara', 30 => 'Ouargla', 31 => 'Oran', 32 => 'El Bayadh',
    33 => 'Illizi', 34 => 'Bordj Bou Arreridj', 35 => 'Boumerdès', 36 => 'El Tarf', 37 => 'Tindouf', 38 => 'Tissemsilt', 39 => 'El Oued', 40 => 'Khenchela',
    41 => 'Souk Ahras', 42 => 'Tipaza', 43 => 'Mila', 44 => 'Aïn Defla', 45 => 'Naâma', 46 => 'Aïn Témouchent', 47 => 'Ghardaïa', 48 => 'Relizane',
    49 => 'Timimoun', 50 => 'Bordj Badji Mokhtar', 51 => 'Ouled Djellal', 52 => 'Béni Abbès', 53 => 'In Salah', 54 => 'In Guezzam', 55 => 'Touggourt',
    56 => 'Djanet', 57 => 'El M\'Ghair', 58 => 'El Meniaa', 59 => 'Aflou', 60 => 'El Abiodh Sidi Cheikh', 61 => 'El Aricha', 62 => 'El Kantara',
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

// ===================== CLASSE USER =====================
class User {
    protected $db;
    
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
        header("Location: index3.php");
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
    
    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// ===================== CLASSE COMPANY =====================
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

// ===================== CLASSE CAR =====================
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
    
    public function getAll() {
        $stmt = $this->db->query("
            SELECT c.*, w.nom as wilaya_nom, co.nom as company_nom
            FROM cars c 
            LEFT JOIN wilayas w ON c.wilaya_id = w.wilaya_id
            LEFT JOIN companies co ON c.company_id = co.company_id
            ORDER BY c.created_at DESC
        ");
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
    
    public static function parseLicensePlate($matricule) {
        // Format: 15231 113 31 (serial type+year wilaya)
        $parts = explode(' ', trim($matricule));
        if (count($parts) == 3) {
            return [
                'serial' => $parts[0],
                'type' => substr($parts[1], 0, 1),
                'year' => substr($parts[1], 1),
                'wilaya' => $parts[2]
            ];
        }
        return false;
    }
}

// ===================== CLASSE RESERVATION =====================
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
    
    public function getAll() {
        $stmt = $this->db->query("
            SELECT r.*, c.marque, c.modele, c.matricule, 
                   u.nom, u.prenom, co.nom as company_nom
            FROM reservations r
            JOIN cars c ON r.car_id = c.car_id
            JOIN users u ON r.user_id = u.user_id
            JOIN companies co ON r.company_id = co.company_id
            ORDER BY r.created_at DESC
        ");
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

// ===================== INITIALISATION =====================
$user = new User();
$company = new Company();
$car = new Car();
$reservation = new Reservation();

$error = '';
$success = '';

// ===================== GESTION DES ACTIONS POST =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'login':
            if ($user->login($_POST['email'] ?? '', $_POST['password'] ?? '')) {
                header("Location: index3.php?page=dashboard");
                exit();
            } else {
                $error = "Email ou mot de passe incorrect";
            }
            break;
            
        case 'register':
            if ($user->register($_POST)) {
                $success = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
            } else {
                $error = "Erreur lors de l'inscription. Email peut-être déjà utilisé.";
            }
            break;
            
        case 'logout':
            $user->logout();
            break;
            
        case 'add_car':
            if ($user->getRole() === 'agent' || $user->getRole() === 'admin' || $user->getRole() === 'owner') {
                $_POST['prix_journalier'] = Car::calculatePrice($_POST['annee'], $_POST['marque']);
                if ($car->create($_POST)) {
                    $success = "Voiture ajoutée avec succès";
                } else {
                    $error = "Erreur lors de l'ajout";
                }
            }
            break;
            
        case 'update_car':
            if ($user->getRole() === 'agent' || $user->getRole() === 'admin' || $user->getRole() === 'owner') {
                $_POST['prix_journalier'] = Car::calculatePrice($_POST['annee'], $_POST['marque']);
                if ($car->update($_POST['car_id'], $_POST)) {
                    $success = "Voiture mise à jour avec succès";
                } else {
                    $error = "Erreur lors de la mise à jour";
                }
            }
            break;
            
        case 'delete_car':
            if ($user->getRole() === 'agent' || $user->getRole() === 'admin' || $user->getRole() === 'owner') {
                if ($car->delete($_POST['car_id'])) {
                    $success = "Voiture supprimée avec succès";
                } else {
                    $error = "Erreur lors de la suppression";
                }
            }
            break;
            
        case 'add_company':
            if ($user->getRole() === 'admin') {
                $_POST['created_by'] = $user->getUserId();
                if ($company->create($_POST)) {
                    $success = "Entreprise ajoutée avec succès";
                } else {
                    $error = "Erreur lors de l'ajout";
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
            if ($user->getRole() === 'agent' || $user->getRole() === 'admin' || $user->getRole() === 'owner') {
                if ($reservation->updatePaymentStatus($_POST['reservation_id'], $_POST['paiement_statut'])) {
                    $success = "Statut de paiement mis à jour";
                } else {
                    $error = "Erreur lors de la mise à jour";
                }
            }
            break;
            
        case 'complete_reservation':
            if ($user->getRole() === 'agent' || $user->getRole() === 'admin' || $user->getRole() === 'owner') {
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

// ===================== DÉTERMINER LA PAGE =====================
$page = $_GET['page'] ?? 'home';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Location Voiture Algérie</title>
    <!-- CSS à ajouter ici plus tard -->
</head>
<body>

<?php if (!$user->isLoggedIn()): ?>
    <!-- ===================== PAGE DE CONNEXION/INSCRIPTION ===================== -->
    <h1>Location de Voiture - Algérie</h1>
    
    <?php if ($error): ?>
        <div style="color: red;"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div style="color: green;"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <h2>Connexion</h2>
    <form method="POST">
        <input type="hidden" name="action" value="login">
        <label>Email:</label>
        <input type="email" name="email" required><br><br>
        <label>Mot de passe:</label>
        <input type="password" name="password" required><br><br>
        <button type="submit">Se connecter</button>
    </form>
    
    <hr>
    
    <h2>Inscription</h2>
    <form method="POST">
        <input type="hidden" name="action" value="register">
        <label>Nom:</label>
        <input type="text" name="nom" required><br><br>
        <label>Prénom:</label>
        <input type="text" name="prenom" required><br><br>
        <label>Téléphone:</label>
        <input type="text" name="telephone"><br><br>
        <label>Wilaya:</label>
        <select name="wilaya_id" required>
            <?php foreach($wilayas as $id => $nom): ?>
                <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($nom); ?></option>
            <?php endforeach; ?>
        </select><br><br>
        <label>Email:</label>
        <input type="email" name="email" required><br><br>
        <label>Mot de passe:</label>
        <input type="password" name="password" required><br><br>
        <label>Rôle:</label>
        <select name="role">
            <option value="client">Client</option>
            <option value="agent">Employé</option>
        </select><br><br>
        <button type="submit">S'inscrire</button>
    </form>

<?php else: ?>
    <!-- ===================== INTERFACE CONNECTÉE ===================== -->
    <header>
        <h1>Location Voiture DZ</h1>
        <p>Bienvenue, <?php echo htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?> 
           (Rôle: <?php echo htmlspecialchars($_SESSION['role']); ?>)</p>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="action" value="logout">
            <button type="submit">Déconnexion</button>
        </form>
    </header>
    
    <nav>
        <a href="?page=dashboard">Tableau de bord</a> |
        <?php if (in_array($_SESSION['role'], ['agent', 'admin', 'owner'])): ?>
            <a href="?page=cars">Gérer les voitures</a> |
        <?php endif; ?>
        <?php if ($_SESSION['role'] === 'client'): ?>
            <a href="?page=reserve">Réserver une voiture</a> |
        <?php endif; ?>
        <?php if (in_array($_SESSION['role'], ['agent', 'admin', 'owner'])): ?>
            <a href="?page=reservations">Réservations</a> |
        <?php endif; ?>
        <a href="?page=mes_reservations">Mes réservations</a>
        <?php if ($_SESSION['role'] === 'admin'): ?>
            | <a href="?page=companies">Entreprises</a>
            | <a href="?page=users">Utilisateurs</a>
        <?php endif; ?>
    </nav>
    
    <?php if ($error): ?>
        <div style="color: red;"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div style="color: green;"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <main>
        <?php
        // ===================== TABLEAU DE BORD =====================
        if ($page === 'dashboard'):
            $role = $_SESSION['role'];
            ?>
            <h2>Tableau de bord - <?php echo htmlspecialchars(ucfirst($role)); ?></h2>
            
            <?php if ($role === 'admin'): ?>
                <?php
                $companies = $company->getAll();
                $users = $user->getAll();
                $all_cars = $car->getAll();
                $all_reservations = $reservation->getAll();
                ?>
                <h3>Statistiques</h3>
                <p>Entreprises: <?php echo count($companies); ?></p>
                <p>Utilisateurs: <?php echo count($users); ?></p>
                <p>Voitures: <?php echo count($all_cars); ?></p>
                <p>Réservations: <?php echo count($all_reservations); ?></p>
                
            <?php elseif ($role === 'owner'): ?>
                <?php
                $company_id = $user->getCompanyId();
                $company_info = $company->getById($company_id);
                $cars_list = $car->getByCompany($company_id);
                $reservations_list = $reservation->getByCompany($company_id);
                ?>
                <h3>Mon Entreprise</h3>
                <?php if ($company_info): ?>
                    <p><strong><?php echo htmlspecialchars($company_info['nom']); ?></strong></p>
                    <p>Email: <?php echo htmlspecialchars($company_info['email']); ?></p>
                    <p>Téléphone: <?php echo htmlspecialchars($company_info['telephone']); ?></p>
                    <p>Wilaya: <?php echo htmlspecialchars($company_info['wilaya_nom']); ?></p>
                <?php endif; ?>
                <h3>Statistiques</h3>
                <p>Voitures: <?php echo count($cars_list); ?></p>
                <p>Réservations: <?php echo count($reservations_list); ?></p>
                
            <?php elseif ($role === 'agent'): ?>
                <?php
                $company_id = $user->getCompanyId();
                $cars_list = $car->getByCompany($company_id);
                $reservations_list = $reservation->getByCompany($company_id);
                ?>
                <h3>Statistiques</h3>
                <p>Voitures: <?php echo count($cars_list); ?></p>
                <p>Réservations: <?php echo count($reservations_list); ?></p>
                
            <?php elseif ($role === 'client'): ?>
                <?php
                $my_reservations = $reservation->getByUser($user->getUserId());
                ?>
                <h3>Mes réservations</h3>
                <p>Nombre de réservations: <?php echo count($my_reservations); ?></p>
            <?php endif; ?>
            
        <?php
        // ===================== GESTION DES VOITURES =====================
        elseif ($page === 'cars' && in_array($_SESSION['role'], ['agent', 'admin', 'owner'])):
            $company_id = $_SESSION['role'] === 'admin' ? ($_GET['company_id'] ?? null) : $user->getCompanyId();
            $cars_list = $company_id ? $car->getByCompany($company_id) : $car->getAll();
            ?>
            <h2>Gestion des Voitures</h2>
            
            <h3>Ajouter une voiture</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_car">
                <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">
                <label>Marque:</label>
                <select name="marque" id="marque_select" required>
                    <option value="">Sélectionner</option>
                    <?php foreach($car_models as $marque => $models): ?>
                        <option value="<?php echo htmlspecialchars($marque); ?>"><?php echo htmlspecialchars($marque); ?></option>
                    <?php endforeach; ?>
                </select><br><br>
                <label>Modèle:</label>
                <select name="modele" id="modele_select" required></select><br><br>
                <label>Année:</label>
                <input type="number" name="annee" min="1960" max="<?php echo date('Y'); ?>" required><br><br>
                <label>Matricule (format: 15231 113 31):</label>
                <input type="text" name="matricule" placeholder="15231 113 31" required><br><br>
                <label>Couleur:</label>
                <input type="text" name="couleur"><br><br>
                <label>Kilométrage:</label>
                <input type="number" name="kilometrage" min="0" value="0"><br><br>
                <label>Wilaya:</label>
                <select name="wilaya_id" required>
                    <?php foreach($wilayas as $id => $nom): ?>
                        <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($nom); ?></option>
                    <?php endforeach; ?>
                </select><br><br>
                <button type="submit">Ajouter</button>
            </form>
            
            <h3>Liste des voitures</h3>
            <table border="1" cellpadding="10">
                <tr>
                    <th>ID</th>
                    <th>Marque</th>
                    <th>Modèle</th>
                    <th>Année</th>
                    <th>Matricule</th>
                    <th>Prix/jour (DA)</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
                <?php foreach($cars_list as $c): ?>
                    <tr>
                        <td><?php echo $c['car_id']; ?></td>
                        <td><?php echo htmlspecialchars($c['marque']); ?></td>
                        <td><?php echo htmlspecialchars($c['modele']); ?></td>
                        <td><?php echo $c['annee']; ?></td>
                        <td><?php echo htmlspecialchars($c['matricule']); ?></td>
                        <td><?php echo number_format($c['prix_journalier'], 2); ?> DA</td>
                        <td><?php echo htmlspecialchars($c['statut']); ?></td>
                        <td>
                            <a href="?page=edit_car&id=<?php echo $c['car_id']; ?>">Modifier</a> |
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete_car">
                                <input type="hidden" name="car_id" value="<?php echo $c['car_id']; ?>">
                                <button type="submit" onclick="return confirm('Supprimer?')">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            
        <?php
        // ===================== MODIFIER UNE VOITURE =====================
        elseif ($page === 'edit_car' && in_array($_SESSION['role'], ['agent', 'admin', 'owner'])):
            $car_id = $_GET['id'] ?? 0;
            $car_data = $car->getById($car_id);
            if (!$car_data) {
                echo "Voiture non trouvée";
            } else:
            ?>
            <h2>Modifier la voiture</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_car">
                <input type="hidden" name="car_id" value="<?php echo $car_data['car_id']; ?>">
                <label>Marque:</label>
                <select name="marque" required>
                    <?php foreach($car_models as $marque => $models): ?>
                        <option value="<?php echo htmlspecialchars($marque); ?>" <?php echo $car_data['marque'] === $marque ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($marque); ?>
                        </option>
                    <?php endforeach; ?>
                </select><br><br>
                <label>Modèle:</label>
                <input type="text" name="modele" value="<?php echo htmlspecialchars($car_data['modele']); ?>" required><br><br>
                <label>Année:</label>
                <input type="number" name="annee" value="<?php echo $car_data['annee']; ?>" required><br><br>
                <label>Matricule:</label>
                <input type="text" name="matricule" value="<?php echo htmlspecialchars($car_data['matricule']); ?>" required><br><br>
                <label>Couleur:</label>
                <input type="text" name="couleur" value="<?php echo htmlspecialchars($car_data['couleur']); ?>"><br><br>
                <label>Kilométrage:</label>
                <input type="number" name="kilometrage" value="<?php echo $car_data['kilometrage']; ?>"><br><br>
                <label>Wilaya:</label>
                <select name="wilaya_id" required>
                    <?php foreach($wilayas as $id => $nom): ?>
                        <option value="<?php echo $id; ?>" <?php echo $car_data['wilaya_id'] == $id ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($nom); ?>
                        </option>
                    <?php endforeach; ?>
                </select><br><br>
                <button type="submit">Mettre à jour</button>
            </form>
            <a href="?page=cars">Retour</a>
            <?php endif; ?>
            
        <?php
        // ===================== RÉSERVER UNE VOITURE (CLIENT) =====================
        elseif ($page === 'reserve' && $_SESSION['role'] === 'client'):
            $companies_list = $company->getAll();
            $selected_company = $_GET['company_id'] ?? null;
            $date_debut = $_GET['date_debut'] ?? date('Y-m-d');
            $date_fin = $_GET['date_fin'] ?? date('Y-m-d', strtotime('+1 day'));
            $available_cars = [];
            if ($selected_company) {
                $available_cars = $car->getAvailableCars($selected_company, $date_debut, $date_fin);
            }
            ?>
            <h2>Réserver une voiture</h2>
            
            <form method="GET">
                <input type="hidden" name="page" value="reserve">
                <label>Entreprise:</label>
                <select name="company_id" required>
                    <option value="">Sélectionner</option>
                    <?php foreach($companies_list as $comp): ?>
                        <option value="<?php echo $comp['company_id']; ?>" <?php echo $selected_company == $comp['company_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($comp['nom']); ?> - <?php echo htmlspecialchars($comp['wilaya_nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select><br><br>
                <label>Date début:</label>
                <input type="date" name="date_debut" value="<?php echo $date_debut; ?>" required><br><br>
                <label>Date fin:</label>
                <input type="date" name="date_fin" value="<?php echo $date_fin; ?>" required><br><br>
                <button type="submit">Rechercher</button>
            </form>
            
            <?php if ($selected_company && count($available_cars) > 0): ?>
                <h3>Voitures disponibles</h3>
                <table border="1" cellpadding="10">
                    <tr>
                        <th>Marque</th>
                        <th>Modèle</th>
                        <th>Année</th>
                        <th>Matricule</th>
                        <th>Prix/jour (DA)</th>
                        <th>Wilaya</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach($available_cars as $c): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($c['marque']); ?></td>
                            <td><?php echo htmlspecialchars($c['modele']); ?></td>
                            <td><?php echo $c['annee']; ?></td>
                            <td><?php echo htmlspecialchars($c['matricule']); ?></td>
                            <td><?php echo number_format($c['prix_journalier'], 2); ?> DA</td>
                            <td><?php echo htmlspecialchars($c['wilaya_nom']); ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="action" value="make_reservation">
                                    <input type="hidden" name="car_id" value="<?php echo $c['car_id']; ?>">
                                    <input type="hidden" name="company_id" value="<?php echo $selected_company; ?>">
                                    <input type="hidden" name="date_debut" value="<?php echo $date_debut; ?>">
                                    <input type="hidden" name="date_fin" value="<?php echo $date_fin; ?>">
                                    <button type="submit">Réserver</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php elseif ($selected_company): ?>
                <p>Aucune voiture disponible pour ces dates.</p>
            <?php endif; ?>
            
        <?php
        // ===================== MES RÉSERVATIONS =====================
        elseif ($page === 'mes_reservations'):
            $my_reservations = $reservation->getByUser($user->getUserId());
            ?>
            <h2>Mes réservations</h2>
            <table border="1" cellpadding="10">
                <tr>
                    <th>ID</th>
                    <th>Voiture</th>
                    <th>Entreprise</th>
                    <th>Date début</th>
                    <th>Date fin</th>
                    <th>Prix total (DA)</th>
                    <th>Statut</th>
                    <th>Paiement</th>
                    <th>Action</th>
                </tr>
                <?php foreach($my_reservations as $r): ?>
                    <tr>
                        <td><?php echo $r['reservation_id']; ?></td>
                        <td><?php echo htmlspecialchars($r['marque'] . ' ' . $r['modele'] . ' (' . $r['matricule'] . ')'); ?></td>
                        <td><?php echo htmlspecialchars($r['company_nom']); ?></td>
                        <td><?php echo $r['date_debut']; ?></td>
                        <td><?php echo $r['date_fin']; ?></td>
                        <td><?php echo number_format($r['prix_total'], 2); ?> DA</td>
                        <td><?php echo htmlspecialchars($r['statut']); ?></td>
                        <td><?php echo htmlspecialchars($r['paiement_statut']); ?></td>
                        <td>
                            <?php if ($r['statut'] !== 'annulee' && $r['statut'] !== 'terminee'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="cancel_reservation">
                                    <input type="hidden" name="reservation_id" value="<?php echo $r['reservation_id']; ?>">
                                    <button type="submit" onclick="return confirm('Annuler?')">Annuler</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            
        <?php
        // ===================== RÉSERVATIONS (AGENT/ADMIN/OWNER) =====================
        elseif ($page === 'reservations' && in_array($_SESSION['role'], ['agent', 'admin', 'owner'])):
            $company_id = $_SESSION['role'] === 'admin' ? null : $user->getCompanyId();
            $reservations_list = $company_id ? $reservation->getByCompany($company_id) : $reservation->getAll();
            ?>
            <h2>Gestion des réservations</h2>
            <table border="1" cellpadding="10">
                <tr>
                    <th>ID</th>
                    <th>Client</th>
                    <th>Voiture</th>
                    <th>Date début</th>
                    <th>Date fin</th>
                    <th>Prix total (DA)</th>
                    <th>Statut</th>
                    <th>Paiement</th>
                    <th>Actions</th>
                </tr>
                <?php foreach($reservations_list as $r): ?>
                    <tr>
                        <td><?php echo $r['reservation_id']; ?></td>
                        <td><?php echo htmlspecialchars($r['nom'] . ' ' . $r['prenom']); ?></td>
                        <td><?php echo htmlspecialchars($r['marque'] . ' ' . $r['modele']); ?></td>
                        <td><?php echo $r['date_debut']; ?></td>
                        <td><?php echo $r['date_fin']; ?></td>
                        <td><?php echo number_format($r['prix_total'], 2); ?> DA</td>
                        <td><?php echo htmlspecialchars($r['statut']); ?></td>
                        <td><?php echo htmlspecialchars($r['paiement_statut']); ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="update_payment">
                                <input type="hidden" name="reservation_id" value="<?php echo $r['reservation_id']; ?>">
                                <select name="paiement_statut">
                                    <option value="non_paye" <?php echo $r['paiement_statut'] === 'non_paye' ? 'selected' : ''; ?>>Non payé</option>
                                    <option value="paye" <?php echo $r['paiement_statut'] === 'paye' ? 'selected' : ''; ?>>Payé</option>
                                    <option value="rembourse" <?php echo $r['paiement_statut'] === 'rembourse' ? 'selected' : ''; ?>>Remboursé</option>
                                </select>
                                <button type="submit">Mettre à jour</button>
                            </form>
                            <?php if ($r['statut'] === 'en_cours' || $r['statut'] === 'en_attente'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="complete_reservation">
                                    <input type="hidden" name="reservation_id" value="<?php echo $r['reservation_id']; ?>">
                                    <button type="submit">Terminer</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            
        <?php
        // ===================== GESTION DES ENTREPRISES (ADMIN) =====================
        elseif ($page === 'companies' && $_SESSION['role'] === 'admin'):
            $companies_list = $company->getAll();
            ?>
            <h2>Gestion des entreprises</h2>
            
            <h3>Ajouter une entreprise</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_company">
                <label>Nom:</label>
                <input type="text" name="nom" required><br><br>
                <label>Email:</label>
                <input type="email" name="email" required><br><br>
                <label>Téléphone:</label>
                <input type="text" name="telephone"><br><br>
                <label>Adresse:</label>
                <textarea name="adresse"></textarea><br><br>
                <label>Wilaya:</label>
                <select name="wilaya_id" required>
                    <?php foreach($wilayas as $id => $nom): ?>
                        <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($nom); ?></option>
                    <?php endforeach; ?>
                </select><br><br>
                <button type="submit">Ajouter</button>
            </form>
            
            <h3>Liste des entreprises</h3>
            <table border="1" cellpadding="10">
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th>Wilaya</th>
                    <th>Actions</th>
                </tr>
                <?php foreach($companies_list as $comp): ?>
                    <tr>
                        <td><?php echo $comp['company_id']; ?></td>
                        <td><?php echo htmlspecialchars($comp['nom']); ?></td>
                        <td><?php echo htmlspecialchars($comp['email']); ?></td>
                        <td><?php echo htmlspecialchars($comp['telephone']); ?></td>
                        <td><?php echo htmlspecialchars($comp['wilaya_nom']); ?></td>
                        <td>
                            <a href="?page=cars&company_id=<?php echo $comp['company_id']; ?>">Voir voitures</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            
        <?php
        // ===================== GESTION DES UTILISATEURS (ADMIN) =====================
        elseif ($page === 'users' && $_SESSION['role'] === 'admin'):
            $users_list = $user->getAll();
            ?>
            <h2>Gestion des utilisateurs</h2>
            <table border="1" cellpadding="10">
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Téléphone</th>
                </tr>
                <?php foreach($users_list as $u): ?>
                    <tr>
                        <td><?php echo $u['user_id']; ?></td>
                        <td><?php echo htmlspecialchars($u['nom']); ?></td>
                        <td><?php echo htmlspecialchars($u['prenom']); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo htmlspecialchars($u['role']); ?></td>
                        <td><?php echo htmlspecialchars($u['telephone']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            
        <?php endif; ?>
    </main>

<?php endif; ?>

<!-- ===================== JAVASCRIPT MINIMAL ===================== -->
<script>
// Mise à jour dynamique des modèles selon la marque
document.addEventListener('DOMContentLoaded', function() {
    const marqueSelect = document.getElementById('marque_select');
    const modeleSelect = document.getElementById('modele_select');
    
    if (marqueSelect && modeleSelect) {
        const models = <?php echo json_encode($car_models); ?>;
        
        marqueSelect.addEventListener('change', function() {
            modeleSelect.innerHTML = '';
            const selectedMarque = this.value;
            if (models[selectedMarque]) {
                models[selectedMarque].forEach(function(modele) {
                    const option = document.createElement('option');
                    option.value = modele;
                    option.textContent = modele;
                    modeleSelect.appendChild(option);
                });
            }
        });
    }
    
    // Calcul automatique du prix (si nécessaire)
    const anneeInput = document.querySelector('input[name="annee"]');
    const marqueInput = document.querySelector('select[name="marque"]');
    if (anneeInput && marqueInput) {
        function calculatePrice() {
            const annee = parseInt(anneeInput.value);
            const marque = marqueInput.value;
            if (annee && marque) {
                // Le prix sera calculé côté serveur, mais on peut afficher une estimation
                const currentYear = new Date().getFullYear();
                const age = currentYear - annee;
                // Estimation simple (sera recalculé côté serveur)
                console.log('Estimation pour ' + marque + ' année ' + annee);
            }
        }
        if (anneeInput) anneeInput.addEventListener('change', calculatePrice);
        if (marqueInput) marqueInput.addEventListener('change', calculatePrice);
    }
});
</script>

</body>
</html>


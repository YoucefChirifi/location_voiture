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

// ==========================================
// CLASSE DATABASE
// ==========================================
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

// ==========================================
// FONCTION SETUP DATABASE
// ==========================================
function setupDatabase() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
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
        
        // Insérer des agents
        $pdo->exec("INSERT IGNORE INTO users (email, password, nom, prenom, telephone, role, company_id, wilaya_id) VALUES
            ('agent1@autoloc-alger.dz', MD5('agent123'), 'Benali', 'Karim', '0660111222', 'agent', 1, 16),
            ('agent2@orancar.dz', MD5('agent123'), 'Mansouri', 'Fatima', '0661222333', 'agent', 2, 31),
            ('agent3@constantine-auto.dz', MD5('agent123'), 'Cherif', 'Ahmed', '0662333444', 'agent', 3, 25)");
        
        // Insérer des clients
        $pdo->exec("INSERT IGNORE INTO users (email, password, nom, prenom, telephone, role, wilaya_id) VALUES
            ('client1@email.dz', MD5('client123'), 'Boudiaf', 'Sofiane', '0770999888', 'client', 16),
            ('client2@email.dz', MD5('client123'), 'Hamidi', 'Amina', '0771888777', 'client', 31),
            ('client3@email.dz', MD5('client123'), 'Meziani', 'Youcef', '0772777666', 'client', 25)");
        
        // Insérer des voitures
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

// ==========================================
// WILAYAS ET MODÈLES DE VOITURES
// ==========================================
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

// ==========================================
// CLASSE USER
// ==========================================
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

// ==========================================
// CLASSE COMPANY
// ==========================================
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

// ==========================================
// CLASSE CAR
// ==========================================
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

// ==========================================
// CLASSE RESERVATION
// ==========================================
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
            
            $stmt = $this->db->prepare("UPDATE reservations SET statut = 'ann
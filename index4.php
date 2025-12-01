<?php
// ============================================================================
// PARTIE 1/12 : CONFIGURATION & DATABASE CLASS
// ============================================================================

// Démarrage de la session
session_start();

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cherifi_youssouf_agency'); // BASE DE DONNÉES CORRIGÉE

// Configuration de l'application
define('CURRENCY', 'DA');
define('MIN_PRICE', 4000);
define('MAX_PRICE', 20000);
define('DATE_FORMAT', 'd/m/Y');

// Gestion des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Variable pour la simulation de dates
if (!isset($_SESSION['current_date'])) {
    $_SESSION['current_date'] = date('Y-m-d');
}

// ============================================================================
// FONCTION POUR CRÉER LA BASE SI ELLE N'EXISTE PAS
// ============================================================================
function createDatabaseIfNotExists() {
    $host = DB_HOST;
    $user = DB_USER;
    $pass = DB_PASS;
    $dbname = DB_NAME;
    
    try {
        // Connexion sans base de données spécifique
        $conn = new mysqli($host, $user, $pass);
        
        if ($conn->connect_error) {
            die("Erreur de connexion MySQL: " . $conn->connect_error);
        }
        
        // Créer la base de données si elle n'existe pas
        $sql = "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        if ($conn->query($sql) === TRUE) {
            error_log("✅ Base de données '$dbname' créée ou déjà existante");
        } else {
            die("❌ Erreur création base: " . $conn->error);
        }
        
        $conn->close();
        
    } catch (Exception $e) {
        die("❌ Erreur lors de la création de la base: " . $e->getMessage());
    }
}

// Créer la base de données d'abord
createDatabaseIfNotExists();

// ============================================================================
// CLASS: Database - Gestion de la connexion et des requêtes
// ============================================================================
class Database {
    private $conn;
    private static $instance = null;
    
    private function __construct() {
        try {
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($this->conn->connect_error) {
                // Si la connexion échoue, créer la base et réessayer
                if ($this->conn->connect_errno == 1049) { // Base doesn't exist
                    createDatabaseIfNotExists();
                    $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                } else {
                    die("Erreur de connexion : " . $this->conn->connect_error);
                }
            }
            
            $this->conn->set_charset("utf8mb4");
            
            // Créer les tables si elles n'existent pas
            $this->createTables();
            
        } catch (Exception $e) {
            die("Erreur : " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    private function createTables() {
        // Table companies
        $sql = "CREATE TABLE IF NOT EXISTS companies (
            company_id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            phone VARCHAR(20),
            address TEXT,
            wilaya VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->conn->query($sql);
        
        // Table users
        $sql = "CREATE TABLE IF NOT EXISTS users (
            user_id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            role ENUM('admin', 'owner', 'agent', 'client') NOT NULL,
            driver_license VARCHAR(50),
            address TEXT,
            wilaya VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE
        )";
        $this->conn->query($sql);
        
        // Table cars
        $sql = "CREATE TABLE IF NOT EXISTS cars (
            car_id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            brand VARCHAR(50) NOT NULL,
            model VARCHAR(50) NOT NULL,
            year INT NOT NULL,
            license_plate VARCHAR(20) UNIQUE NOT NULL,
            color VARCHAR(30),
            daily_price DECIMAL(10,2) NOT NULL,
            status ENUM('available', 'rented', 'maintenance') DEFAULT 'available',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE
        )";
        $this->conn->query($sql);
        
        // Table reservations
        $sql = "CREATE TABLE IF NOT EXISTS reservations (
            reservation_id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT NOT NULL,
            car_id INT NOT NULL,
            company_id INT NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            total_price DECIMAL(10,2) NOT NULL,
            payment_status ENUM('paid', 'pending') DEFAULT 'pending',
            status ENUM('ongoing', 'completed', 'cancelled') DEFAULT 'ongoing',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (car_id) REFERENCES cars(car_id) ON DELETE CASCADE,
            FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE
        )";
        $this->conn->query($sql);
        
        // Insérer des données de test
        $this->insertSampleData();
    }
    
    private function insertSampleData() {
        // Vérifier si des données existent déjà
        $result = $this->conn->query("SELECT COUNT(*) as count FROM companies");
        $row = $result->fetch_assoc();
        
        if ($row['count'] == 0) {
            error_log("🔄 Insertion des données de test dans cherifi_youssouf_agency...");
            
            // Insérer 3 companies
            $companies = [
                ['AutoLoc Alger', 'contact@autoloc-alger.dz', '0770123456', '10 Rue Didouche Mourad', 'Alger'],
                ['Speed Rent Oran', 'info@speedrent-oran.dz', '0771234567', '25 Boulevard de la Soummam', 'Oran'],
                ['Atlas Cars Constantine', 'admin@atlascars-constantine.dz', '0772345678', '5 Avenue Aouati Mostefa', 'Constantine']
            ];
            
            foreach ($companies as $comp) {
                $stmt = $this->conn->prepare("INSERT INTO companies (name, email, phone, address, wilaya) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $comp[0], $comp[1], $comp[2], $comp[3], $comp[4]);
                $stmt->execute();
            }
            
            // Insérer des owners pour chaque company
            $owners = [
                [1, 'Ahmed', 'Benali', 'ahmed.benali@autoloc-alger.dz', 'owner123', '0660111222', 'owner'],
                [2, 'Karim', 'Meziane', 'karim.meziane@speedrent-oran.dz', 'owner123', '0660222333', 'owner'],
                [3, 'Yasmine', 'Brahimi', 'yasmine.brahimi@atlascars-constantine.dz', 'owner123', '0660333444', 'owner']
            ];
            
            foreach ($owners as $owner) {
                $password = password_hash($owner[4], PASSWORD_DEFAULT);
                $stmt = $this->conn->prepare("INSERT INTO users (company_id, first_name, last_name, email, password, phone, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssss", $owner[0], $owner[1], $owner[2], $owner[3], $password, $owner[5], $owner[6]);
                $stmt->execute();
            }
            
            // Insérer 1 agent par company
            $agents = [
                [1, 'Fatima', 'Khelifi', 'fatima.khelifi@autoloc-alger.dz', 'agent123', '0661111222', 'agent'],
                [2, 'Mehdi', 'Saidi', 'mehdi.saidi@speedrent-oran.dz', 'agent123', '0661222333', 'agent'],
                [3, 'Amina', 'Touati', 'amina.touati@atlascars-constantine.dz', 'agent123', '0661333444', 'agent']
            ];
            
            foreach ($agents as $agent) {
                $password = password_hash($agent[4], PASSWORD_DEFAULT);
                $stmt = $this->conn->prepare("INSERT INTO users (company_id, first_name, last_name, email, password, phone, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssss", $agent[0], $agent[1], $agent[2], $agent[3], $password, $agent[5], $agent[6]);
                $stmt->execute();
            }
            
            // Insérer 2 clients
            $clients = [
                ['Sofiane', 'Hamidi', 'sofiane.hamidi@email.dz', 'client123', '0662111222', 'client', 'DL12345A', '15 Rue Larbi Ben M\'hidi', 'Alger'],
                ['Lina', 'Bouzid', 'lina.bouzid@email.dz', 'client123', '0662222333', 'client', 'DL67890B', '8 Avenue de l\'ALN', 'Oran']
            ];
            
            foreach ($clients as $client) {
                $password = password_hash($client[3], PASSWORD_DEFAULT);
                $stmt = $this->conn->prepare("INSERT INTO users (first_name, last_name, email, password, phone, role, driver_license, address, wilaya) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssss", $client[0], $client[1], $client[2], $password, $client[4], $client[5], $client[6], $client[7], $client[8]);
                $stmt->execute();
            }
            
            error_log("✅ Données de test insérées avec succès dans cherifi_youssouf_agency");
        }
    }
    
    public function query($sql) {
        return $this->conn->query($sql);
    }
    
    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }
    
    public function escape($string) {
        return $this->conn->real_escape_string($string);
    }
}

// Initialiser la connexion
$db = Database::getInstance();

// ... LE RESTE DE VOTRE CODE RESTE IDENTIQUE ...
?>

<?php
// ============================================================================
// PARTIE 2/12 : USER & COMPANY CLASSES
// ============================================================================

// ============================================================================
// CLASS: User - Gestion des utilisateurs
// ============================================================================
class User {
    private $db;
    private $user_id;
    private $company_id;
    private $first_name;
    private $last_name;
    private $email;
    private $phone;
    private $role;
    private $driver_license;
    private $address;
    private $wilaya;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Inscription d'un nouvel utilisateur
    public function register($data) {
        $first_name = $this->db->real_escape_string($data['first_name']);
        $last_name = $this->db->real_escape_string($data['last_name']);
        $email = $this->db->real_escape_string($data['email']);
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        $phone = $this->db->real_escape_string($data['phone']);
        $role = $this->db->real_escape_string($data['role']);
        
        // Vérifier si l'email existe déjà
        $stmt = $this->db->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return ['success' => false, 'message' => 'Cet email existe déjà'];
        }
        
        // Insertion selon le rôle
        if ($role === 'client') {
            $driver_license = $this->db->real_escape_string($data['driver_license']);
            $address = $this->db->real_escape_string($data['address']);
            $wilaya = $this->db->real_escape_string($data['wilaya']);
            
            $stmt = $this->db->prepare("INSERT INTO users (first_name, last_name, email, password, phone, role, driver_license, address, wilaya) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssss", $first_name, $last_name, $email, $password, $phone, $role, $driver_license, $address, $wilaya);
        } else {
            // Pour agent (doit être lié à une company)
            $company_id = intval($data['company_id']);
            $stmt = $this->db->prepare("INSERT INTO users (company_id, first_name, last_name, email, password, phone, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssss", $company_id, $first_name, $last_name, $email, $password, $phone, $role);
        }
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Inscription réussie'];
        } else {
            return ['success' => false, 'message' => 'Erreur lors de l\'inscription'];
        }
    }
    
    // Connexion
    public function login($email, $password) {
        $email = $this->db->real_escape_string($email);
        
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Stocker les informations en session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['company_id'] = $user['company_id'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                
                return ['success' => true, 'role' => $user['role']];
            }
        }
        
        return ['success' => false, 'message' => 'Email ou mot de passe incorrect'];
    }
    
    // Déconnexion
    public static function logout() {
        session_unset();
        session_destroy();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    // Vérifier si l'utilisateur est connecté
    public static function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    // Obtenir le rôle de l'utilisateur connecté
    public static function getRole() {
        return isset($_SESSION['role']) ? $_SESSION['role'] : null;
    }
    
    // Obtenir l'ID de l'utilisateur connecté
    public static function getUserId() {
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }
    
    // Obtenir l'ID de la company de l'utilisateur connecté
    public static function getCompanyId() {
        return isset($_SESSION['company_id']) ? $_SESSION['company_id'] : null;
    }
    
    // Obtenir les informations d'un utilisateur
    public function getUserById($user_id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    // Obtenir tous les clients
    public function getAllClients() {
        $sql = "SELECT * FROM users WHERE role = 'client' ORDER BY created_at DESC";
        $result = $this->db->query($sql);
        $clients = [];
        while ($row = $result->fetch_assoc()) {
            $clients[] = $row;
        }
        return $clients;
    }
    
    // Obtenir les agents d'une company
    public function getAgentsByCompany($company_id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE company_id = ? AND role = 'agent' ORDER BY created_at DESC");
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $agents = [];
        while ($row = $result->fetch_assoc()) {
            $agents[] = $row;
        }
        return $agents;
    }
}

// ============================================================================
// CLASS: Company - Gestion des entreprises
// ============================================================================
class Company {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Créer une nouvelle company
    public function create($data) {
        $name = $this->db->real_escape_string($data['name']);
        $email = $this->db->real_escape_string($data['email']);
        $phone = $this->db->real_escape_string($data['phone']);
        $address = $this->db->real_escape_string($data['address']);
        $wilaya = $this->db->real_escape_string($data['wilaya']);
        
        $stmt = $this->db->prepare("INSERT INTO companies (name, email, phone, address, wilaya) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $phone, $address, $wilaya);
        
        if ($stmt->execute()) {
            return ['success' => true, 'company_id' => $this->db->insert_id];
        }
        return ['success' => false, 'message' => 'Erreur lors de la création'];
    }
    
    // Obtenir toutes les companies
    public function getAll() {
        $sql = "SELECT * FROM companies ORDER BY created_at DESC";
        $result = $this->db->query($sql);
        $companies = [];
        while ($row = $result->fetch_assoc()) {
            $companies[] = $row;
        }
        return $companies;
    }
    
    // Obtenir une company par ID
    public function getById($company_id) {
        $stmt = $this->db->prepare("SELECT * FROM companies WHERE company_id = ?");
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    // Mettre à jour une company
    public function update($company_id, $data) {
        $name = $this->db->real_escape_string($data['name']);
        $email = $this->db->real_escape_string($data['email']);
        $phone = $this->db->real_escape_string($data['phone']);
        $address = $this->db->real_escape_string($data['address']);
        $wilaya = $this->db->real_escape_string($data['wilaya']);
        
        $stmt = $this->db->prepare("UPDATE companies SET name = ?, email = ?, phone = ?, address = ?, wilaya = ? WHERE company_id = ?");
        $stmt->bind_param("sssssi", $name, $email, $phone, $address, $wilaya, $company_id);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Company mise à jour'];
        }
        return ['success' => false, 'message' => 'Erreur lors de la mise à jour'];
    }
    
    // Supprimer une company
    public function delete($company_id) {
        $stmt = $this->db->prepare("DELETE FROM companies WHERE company_id = ?");
        $stmt->bind_param("i", $company_id);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Company supprimée'];
        }
        return ['success' => false, 'message' => 'Erreur lors de la suppression'];
    }
    
    // Obtenir les statistiques d'une company
    public function getStats($company_id) {
        $stats = [];
        
        // Nombre de voitures
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM cars WHERE company_id = ?");
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['total_cars'] = $result->fetch_assoc()['total'];
        
        // Voitures disponibles
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM cars WHERE company_id = ? AND status = 'available'");
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['available_cars'] = $result->fetch_assoc()['total'];
        
        // Voitures louées
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM cars WHERE company_id = ? AND status = 'rented'");
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['rented_cars'] = $result->fetch_assoc()['total'];
        
        // Réservations en cours
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM reservations WHERE company_id = ? AND status = 'ongoing'");
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['ongoing_reservations'] = $result->fetch_assoc()['total'];
        
        // Revenu total
        $stmt = $this->db->prepare("SELECT SUM(total_price) as total FROM reservations WHERE company_id = ? AND payment_status = 'paid'");
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['total_revenue'] = $result->fetch_assoc()['total'] ?? 0;
        
        return $stats;
    }
}

?>
<?php
// ============================================================================
// PARTIE 3/12 : CAR & RESERVATION CLASSES
// ============================================================================

// ============================================================================
// CLASS: Car - Gestion des voitures
// ============================================================================
class Car {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Calculer le prix basé sur l'année et le modèle
    private function calculatePrice($year, $brand) {
        $current_year = date('Y');
        $age = $current_year - $year;
        
        // Prix de base selon la marque (premium vs standard)
        $premium_brands = ['Toyota', 'Volkswagen', 'Nissan', 'Hyundai'];
        $base_price = in_array($brand, $premium_brands) ? 15000 : 12000;
        
        // Réduction selon l'âge
        $price = $base_price - ($age * 500);
        
        // S'assurer que le prix est dans la fourchette
        if ($price < MIN_PRICE) $price = MIN_PRICE;
        if ($price > MAX_PRICE) $price = MAX_PRICE;
        
        return $price;
    }
    
    // Valider le format de la plaque d'immatriculation algérienne
    private function validateLicensePlate($plate) {
        // Format: 15231 113 31 ou 15231-113-31
        $pattern = '/^\d{5}[\s\-]\d{3}[\s\-]\d{2}$/';
        return preg_match($pattern, $plate);
    }
    
    // Ajouter une voiture
    public function create($data) {
        $company_id = intval($data['company_id']);
        $brand = $this->db->real_escape_string($data['brand']);
        $model = $this->db->real_escape_string($data['model']);
        $year = intval($data['year']);
        $license_plate = $this->db->real_escape_string($data['license_plate']);
        $color = $this->db->real_escape_string($data['color']);
        
        // Valider la plaque
        if (!$this->validateLicensePlate($license_plate)) {
            return ['success' => false, 'message' => 'Format de plaque invalide (ex: 15231 113 31)'];
        }
        
        // Calculer le prix automatiquement
        $daily_price = $this->calculatePrice($year, $brand);
        
        // Vérifier si la plaque existe déjà
        $stmt = $this->db->prepare("SELECT car_id FROM cars WHERE license_plate = ?");
        $stmt->bind_param("s", $license_plate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return ['success' => false, 'message' => 'Cette plaque existe déjà'];
        }
        
        $stmt = $this->db->prepare("INSERT INTO cars (company_id, brand, model, year, license_plate, color, daily_price, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'available')");
        $stmt->bind_param("isssssd", $company_id, $brand, $model, $year, $license_plate, $color, $daily_price);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Voiture ajoutée avec succès', 'car_id' => $this->db->insert_id];
        }
        return ['success' => false, 'message' => 'Erreur lors de l\'ajout'];
    }
    
    // Obtenir toutes les voitures d'une company
    public function getByCompany($company_id, $status = null) {
        if ($status) {
            $stmt = $this->db->prepare("SELECT * FROM cars WHERE company_id = ? AND status = ? ORDER BY created_at DESC");
            $stmt->bind_param("is", $company_id, $status);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM cars WHERE company_id = ? ORDER BY created_at DESC");
            $stmt->bind_param("i", $company_id);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $cars = [];
        while ($row = $result->fetch_assoc()) {
            $cars[] = $row;
        }
        return $cars;
    }
    
    // Obtenir toutes les voitures disponibles
    public function getAvailableCars($company_id = null) {
        if ($company_id) {
            $stmt = $this->db->prepare("SELECT * FROM cars WHERE company_id = ? AND status = 'available' ORDER BY brand, model");
            $stmt->bind_param("i", $company_id);
        } else {
            $sql = "SELECT c.*, co.name as company_name FROM cars c 
                    JOIN companies co ON c.company_id = co.company_id 
                    WHERE c.status = 'available' ORDER BY c.brand, c.model";
            $result = $this->db->query($sql);
            $cars = [];
            while ($row = $result->fetch_assoc()) {
                $cars[] = $row;
            }
            return $cars;
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $cars = [];
        while ($row = $result->fetch_assoc()) {
            $cars[] = $row;
        }
        return $cars;
    }
    
    // Obtenir une voiture par ID
    public function getById($car_id) {
        $stmt = $this->db->prepare("SELECT c.*, co.name as company_name FROM cars c 
                                     JOIN companies co ON c.company_id = co.company_id 
                                     WHERE c.car_id = ?");
        $stmt->bind_param("i", $car_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    // Mettre à jour une voiture
    public function update($car_id, $data) {
        $brand = $this->db->real_escape_string($data['brand']);
        $model = $this->db->real_escape_string($data['model']);
        $year = intval($data['year']);
        $license_plate = $this->db->real_escape_string($data['license_plate']);
        $color = $this->db->real_escape_string($data['color']);
        $status = $this->db->real_escape_string($data['status']);
        
        // Recalculer le prix
        $daily_price = $this->calculatePrice($year, $brand);
        
        $stmt = $this->db->prepare("UPDATE cars SET brand = ?, model = ?, year = ?, license_plate = ?, color = ?, daily_price = ?, status = ? WHERE car_id = ?");
        $stmt->bind_param("sssssdsi", $brand, $model, $year, $license_plate, $color, $daily_price, $status, $car_id);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Voiture mise à jour'];
        }
        return ['success' => false, 'message' => 'Erreur lors de la mise à jour'];
    }
    
    // Supprimer une voiture
    public function delete($car_id) {
        // Vérifier si la voiture a des réservations en cours
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM reservations WHERE car_id = ? AND status = 'ongoing'");
        $stmt->bind_param("i", $car_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            return ['success' => false, 'message' => 'Impossible de supprimer une voiture avec des réservations en cours'];
        }
        
        $stmt = $this->db->prepare("DELETE FROM cars WHERE car_id = ?");
        $stmt->bind_param("i", $car_id);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Voiture supprimée'];
        }
        return ['success' => false, 'message' => 'Erreur lors de la suppression'];
    }
    
    // Changer le statut d'une voiture
    public function updateStatus($car_id, $status) {
        $stmt = $this->db->prepare("UPDATE cars SET status = ? WHERE car_id = ?");
        $stmt->bind_param("si", $status, $car_id);
        return $stmt->execute();
    }
}

// ============================================================================
// CLASS: Reservation - Gestion des réservations
// ============================================================================
class Reservation {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Vérifier la disponibilité d'une voiture pour une période
    public function checkAvailability($car_id, $start_date, $end_date, $exclude_reservation_id = null) {
        $sql = "SELECT COUNT(*) as count FROM reservations 
                WHERE car_id = ? 
                AND status = 'ongoing' 
                AND (
                    (start_date <= ? AND end_date >= ?) OR
                    (start_date <= ? AND end_date >= ?) OR
                    (start_date >= ? AND end_date <= ?)
                )";
        
        if ($exclude_reservation_id) {
            $sql .= " AND reservation_id != ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("isssssi", $car_id, $start_date, $start_date, $end_date, $end_date, $start_date, $end_date, $exclude_reservation_id);
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("issssss", $car_id, $start_date, $start_date, $end_date, $end_date, $start_date, $end_date);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['count'] == 0;
    }
    
    // Créer une réservation
    public function create($data) {
        $client_id = intval($data['client_id']);
        $car_id = intval($data['car_id']);
        $start_date = $data['start_date'];
        $end_date = $data['end_date'];
        
        // Obtenir les infos de la voiture
        $stmt = $this->db->prepare("SELECT company_id, daily_price, status FROM cars WHERE car_id = ?");
        $stmt->bind_param("i", $car_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $car = $result->fetch_assoc();
        
        if (!$car) {
            return ['success' => false, 'message' => 'Voiture introuvable'];
        }
        
        if ($car['status'] != 'available') {
            return ['success' => false, 'message' => 'Cette voiture n\'est pas disponible'];
        }
        
        // Vérifier la disponibilité
        if (!$this->checkAvailability($car_id, $start_date, $end_date)) {
            return ['success' => false, 'message' => 'Voiture non disponible pour cette période'];
        }
        
        // Calculer le prix total
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $days = $end->diff($start)->days + 1;
        $total_price = $days * $car['daily_price'];
        
        $company_id = $car['company_id'];
        
        // Créer la réservation
        $stmt = $this->db->prepare("INSERT INTO reservations (client_id, car_id, company_id, start_date, end_date, total_price, status, payment_status) VALUES (?, ?, ?, ?, ?, ?, 'ongoing', 'pending')");
        $stmt->bind_param("iiissd", $client_id, $car_id, $company_id, $start_date, $end_date, $total_price);
        
        if ($stmt->execute()) {
            // Mettre à jour le statut de la voiture
            $this->db->query("UPDATE cars SET status = 'rented' WHERE car_id = $car_id");
            
            return ['success' => true, 'message' => 'Réservation créée avec succès', 'reservation_id' => $this->db->insert_id, 'total_price' => $total_price];
        }
        return ['success' => false, 'message' => 'Erreur lors de la création de la réservation'];
    }
    
    // Obtenir les réservations d'un client
    public function getByClient($client_id) {
        $stmt = $this->db->prepare("SELECT r.*, c.brand, c.model, c.license_plate, co.name as company_name 
                                     FROM reservations r 
                                     JOIN cars c ON r.car_id = c.car_id 
                                     JOIN companies co ON r.company_id = co.company_id 
                                     WHERE r.client_id = ? 
                                     ORDER BY r.created_at DESC");
        $stmt->bind_param("i", $client_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $reservations = [];
        while ($row = $result->fetch_assoc()) {
            $reservations[] = $row;
        }
        return $reservations;
    }
    
    // Obtenir les réservations d'une company
    public function getByCompany($company_id) {
        $stmt = $this->db->prepare("SELECT r.*, c.brand, c.model, c.license_plate, 
                                     u.first_name, u.last_name, u.email, u.phone 
                                     FROM reservations r 
                                     JOIN cars c ON r.car_id = c.car_id 
                                     JOIN users u ON r.client_id = u.user_id 
                                     WHERE r.company_id = ? 
                                     ORDER BY r.start_date DESC");
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $reservations = [];
        while ($row = $result->fetch_assoc()) {
            $reservations[] = $row;
        }
        return $reservations;
    }
    
    // Marquer le paiement comme effectué
    public function markAsPaid($reservation_id) {
        $stmt = $this->db->prepare("UPDATE reservations SET payment_status = 'paid' WHERE reservation_id = ?");
        $stmt->bind_param("i", $reservation_id);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Paiement enregistré'];
        }
        return ['success' => false, 'message' => 'Erreur lors de l\'enregistrement du paiement'];
    }
    
    // Terminer une réservation
    public function complete($reservation_id) {
        // Obtenir l'ID de la voiture
        $stmt = $this->db->prepare("SELECT car_id FROM reservations WHERE reservation_id = ?");
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $reservation = $result->fetch_assoc();
        
        if (!$reservation) {
            return ['success' => false, 'message' => 'Réservation introuvable'];
        }
        
        // Mettre à jour la réservation
        $stmt = $this->db->prepare("UPDATE reservations SET status = 'completed' WHERE reservation_id = ?");
        $stmt->bind_param("i", $reservation_id);
        
        if ($stmt->execute()) {
            // Remettre la voiture disponible
            $car_id = $reservation['car_id'];
            $this->db->query("UPDATE cars SET status = 'available' WHERE car_id = $car_id");
            
            return ['success' => true, 'message' => 'Réservation terminée'];
        }
        return ['success' => false, 'message' => 'Erreur lors de la fin de la réservation'];
    }
    
    // Annuler une réservation
    public function cancel($reservation_id) {
        // Obtenir l'ID de la voiture
        $stmt = $this->db->prepare("SELECT car_id FROM reservations WHERE reservation_id = ?");
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $reservation = $result->fetch_assoc();
        
        if (!$reservation) {
            return ['success' => false, 'message' => 'Réservation introuvable'];
        }
        
        // Mettre à jour la réservation
        $stmt = $this->db->prepare("UPDATE reservations SET status = 'cancelled' WHERE reservation_id = ?");
        $stmt->bind_param("i", $reservation_id);
        
        if ($stmt->execute()) {
            // Remettre la voiture disponible
            $car_id = $reservation['car_id'];
            $this->db->query("UPDATE cars SET status = 'available' WHERE car_id = $car_id");
            
            return ['success' => true, 'message' => 'Réservation annulée'];
        }
        return ['success' => false, 'message' => 'Erreur lors de l\'annulation'];
    }
    
    // Mettre à jour automatiquement les statuts des réservations
    public function updateExpiredReservations() {
        $current_date = $_SESSION['current_date'];
        
        // Marquer comme complétées les réservations dont la date de fin est dépassée
        $sql = "SELECT r.reservation_id, r.car_id 
                FROM reservations r 
                WHERE r.status = 'ongoing' 
                AND r.end_date < '$current_date'";
        
        $result = $this->db->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            $this->complete($row['reservation_id']);
        }
    }
}

?>
<?php
// ============================================================================
// PARTIE 4/12 : FORM PROCESSING & ACTIONS
// ============================================================================

// Initialiser les instances des classes
$userObj = new User();
$companyObj = new Company();
$carObj = new Car();
$reservationObj = new Reservation();

// Variable pour les messages
$message = '';
$message_type = '';

// ============================================================================
// TRAITEMENT DES ACTIONS
// ============================================================================

// Action: Déconnexion
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    User::logout();
}

// Action: Navigation des dates (pour tester la disponibilité automatique)
if (isset($_GET['date_action'])) {
    if ($_GET['date_action'] === 'next') {
        $current = new DateTime($_SESSION['current_date']);
        $current->modify('+1 day');
        $_SESSION['current_date'] = $current->format('Y-m-d');
    } elseif ($_GET['date_action'] === 'prev') {
        $current = new DateTime($_SESSION['current_date']);
        $current->modify('-1 day');
        $_SESSION['current_date'] = $current->format('Y-m-d');
    }
    
    // Mettre à jour les réservations expirées
    $reservationObj->updateExpiredReservations();
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// ============================================================================
// TRAITEMENT DES FORMULAIRES POST
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ========================================
    // INSCRIPTION
    // ========================================
    if (isset($_POST['action']) && $_POST['action'] === 'register') {
        $result = $userObj->register($_POST);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'error';
    }
    
    // ========================================
    // CONNEXION
    // ========================================
    if (isset($_POST['action']) && $_POST['action'] === 'login') {
        $result = $userObj->login($_POST['email'], $_POST['password']);
        if ($result['success']) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $message = $result['message'];
            $message_type = 'error';
        }
    }
    
    // ========================================
    // AJOUTER UNE VOITURE (Agent/Owner)
    // ========================================
    if (isset($_POST['action']) && $_POST['action'] === 'add_car') {
        if (User::isLoggedIn() && (User::getRole() === 'agent' || User::getRole() === 'owner')) {
            $_POST['company_id'] = User::getCompanyId();
            $result = $carObj->create($_POST);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }
    }
    
    // ========================================
    // MODIFIER UNE VOITURE (Agent/Owner)
    // ========================================
    if (isset($_POST['action']) && $_POST['action'] === 'edit_car') {
        if (User::isLoggedIn() && (User::getRole() === 'agent' || User::getRole() === 'owner')) {
            $car_id = intval($_POST['car_id']);
            $result = $carObj->update($car_id, $_POST);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }
    }
    
    // ========================================
    // SUPPRIMER UNE VOITURE (Agent/Owner)
    // ========================================
    if (isset($_POST['action']) && $_POST['action'] === 'delete_car') {
        if (User::isLoggedIn() && (User::getRole() === 'agent' || User::getRole() === 'owner')) {
            $car_id = intval($_POST['car_id']);
            $result = $carObj->delete($car_id);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }
    }
    
    // ========================================
    // CRÉER UNE RÉSERVATION (Client)
    // ========================================
    if (isset($_POST['action']) && $_POST['action'] === 'create_reservation') {
        if (User::isLoggedIn() && User::getRole() === 'client') {
            $_POST['client_id'] = User::getUserId();
            $result = $reservationObj->create($_POST);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }
    }
    
    // ========================================
    // MARQUER PAIEMENT COMME EFFECTUÉ (Agent)
    // ========================================
    if (isset($_POST['action']) && $_POST['action'] === 'mark_paid') {
        if (User::isLoggedIn() && (User::getRole() === 'agent' || User::getRole() === 'owner')) {
            $reservation_id = intval($_POST['reservation_id']);
            $result = $reservationObj->markAsPaid($reservation_id);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }
    }
    
    // ========================================
    // TERMINER UNE RÉSERVATION (Agent)
    // ========================================
    if (isset($_POST['action']) && $_POST['action'] === 'complete_reservation') {
        if (User::isLoggedIn() && (User::getRole() === 'agent' || User::getRole() === 'owner')) {
            $reservation_id = intval($_POST['reservation_id']);
            $result = $reservationObj->complete($reservation_id);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }
    }
    
    // ========================================
    // ANNULER UNE RÉSERVATION (Client/Agent)
    // ========================================
    if (isset($_POST['action']) && $_POST['action'] === 'cancel_reservation') {
        if (User::isLoggedIn()) {
            $reservation_id = intval($_POST['reservation_id']);
            $result = $reservationObj->cancel($reservation_id);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }
    }
    
    // ========================================
    // AJOUTER UNE COMPANY (Admin - pour extension future)
    // ========================================
    if (isset($_POST['action']) && $_POST['action'] === 'add_company') {
        if (User::isLoggedIn() && User::getRole() === 'admin') {
            $result = $companyObj->create($_POST);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }
    }
}

// ============================================================================
// DONNÉES POUR L'AFFICHAGE
// ============================================================================

// Wilayas algériennes (69 wilayas)
$wilayas = [
    '01' => 'Adrar', '02' => 'Chlef', '03' => 'Laghouat', '04' => 'Oum El Bouaghi',
    '05' => 'Batna', '06' => 'Béjaïa', '07' => 'Biskra', '08' => 'Béchar',
    '09' => 'Blida', '10' => 'Bouira', '11' => 'Tamanrasset', '12' => 'Tébessa',
    '13' => 'Tlemcen', '14' => 'Tiaret', '15' => 'Tizi Ouzou', '16' => 'Alger',
    '17' => 'Djelfa', '18' => 'Jijel', '19' => 'Sétif', '20' => 'Saïda',
    '21' => 'Skikda', '22' => 'Sidi Bel Abbès', '23' => 'Annaba', '24' => 'Guelma',
    '25' => 'Constantine', '26' => 'Médéa', '27' => 'Mostaganem', '28' => 'M\'Sila',
    '29' => 'Mascara', '30' => 'Ouargla', '31' => 'Oran', '32' => 'El Bayadh',
    '33' => 'Illizi', '34' => 'Bordj Bou Arreridj', '35' => 'Boumerdès', '36' => 'El Tarf',
    '37' => 'Tindouf', '38' => 'Tissemsilt', '39' => 'El Oued', '40' => 'Khenchela',
    '41' => 'Souk Ahras', '42' => 'Tipaza', '43' => 'Mila', '44' => 'Aïn Defla',
    '45' => 'Naâma', '46' => 'Aïn Témouchent', '47' => 'Ghardaïa', '48' => 'Relizane',
    '49' => 'Timimoun', '50' => 'Bordj Badji Mokhtar', '51' => 'Ouled Djellal', '52' => 'Béni Abbès',
    '53' => 'In Salah', '54' => 'In Guezzam', '55' => 'Touggourt', '56' => 'Djanet',
    '57' => 'El M\'Ghair', '58' => 'El Meniaa', '59' => 'Aflou', '60' => 'El Abiodh Sidi Cheikh',
    '61' => 'El Aricha', '62' => 'El Kantara', '63' => 'Barika', '64' => 'Bou Saâda',
    '65' => 'Bir El Ater', '66' => 'Ksar El Boukhari', '67' => 'Ksar Chellala', '68' => 'Aïn Oussara',
    '69' => 'Messaad'
];

// Marques de voitures disponibles
$car_brands = [
    'Dacia' => ['Logan', 'Sandero', 'Duster'],
    'Renault' => ['Clio', 'Megane', 'Symbol', 'Kangoo'],
    'Peugeot' => ['206', '208', '301', '308'],
    'Citroën' => ['C3', 'C4'],
    'Volkswagen' => ['Polo', 'Golf'],
    'Toyota' => ['Yaris', 'Corolla', 'Hilux'],
    'Hyundai' => ['i10', 'i20', 'Accent'],
    'Kia' => ['Picanto', 'Rio'],
    'Nissan' => ['Sunny', 'Qashqai'],
    'Ford' => ['Fiesta', 'Focus'],
    'Fiat' => ['Punto', 'Tipo'],
    'Opel' => ['Corsa']
];

// Couleurs disponibles
$colors = ['Blanc', 'Noir', 'Gris', 'Rouge', 'Bleu', 'Vert', 'Jaune', 'Orange', 'Marron', 'Argent'];

// Charger les données selon le rôle de l'utilisateur
$user_data = [];
$cars_data = [];
$reservations_data = [];
$stats_data = [];
$companies_data = [];

if (User::isLoggedIn()) {
    $role = User::getRole();
    $user_id = User::getUserId();
    $company_id = User::getCompanyId();
    
    // Mettre à jour les réservations expirées
    $reservationObj->updateExpiredReservations();
    
    if ($role === 'client') {
        // Client: voir toutes les voitures disponibles et ses réservations
        $cars_data = $carObj->getAvailableCars();
        $reservations_data = $reservationObj->getByClient($user_id);
        
    } elseif ($role === 'agent' || $role === 'owner') {
        // Agent/Owner: voir les voitures et réservations de sa company
        $cars_data = $carObj->getByCompany($company_id);
        $reservations_data = $reservationObj->getByCompany($company_id);
        $stats_data = $companyObj->getStats($company_id);
        
        // Owner: peut aussi voir les agents
        if ($role === 'owner') {
            $agents_data = $userObj->getAgentsByCompany($company_id);
        }
        
    } elseif ($role === 'admin') {
        // Admin: vue globale (pour extension future)
        $companies_data = $companyObj->getAll();
    }
}

// ============================================================================
// FONCTIONS UTILITAIRES
// ============================================================================

// Formater le prix
function formatPrice($price) {
    return number_format($price, 2, ',', ' ') . ' ' . CURRENCY;
}

// Formater la date
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

// Obtenir le badge de statut
function getStatusBadge($status) {
    $badges = [
        'available' => '<span class="badge badge-success">Disponible</span>',
        'rented' => '<span class="badge badge-warning">Louée</span>',
        'maintenance' => '<span class="badge badge-danger">Maintenance</span>',
        'ongoing' => '<span class="badge badge-info">En cours</span>',
        'completed' => '<span class="badge badge-success">Terminée</span>',
        'cancelled' => '<span class="badge badge-secondary">Annulée</span>',
        'paid' => '<span class="badge badge-success">Payé</span>',
        'pending' => '<span class="badge badge-warning">En attente</span>'
    ];
    return $badges[$status] ?? $status;
}

// Calculer le nombre de jours entre deux dates
function calculateDays($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    return $end->diff($start)->days + 1;
}

// Vérifier si une date est dans le futur
function isFutureDate($date) {
    return strtotime($date) > strtotime($_SESSION['current_date']);
}

// Vérifier si une date est aujourd'hui
function isToday($date) {
    return $date === $_SESSION['current_date'];
}

// Vérifier si une réservation est active
function isActiveReservation($start_date, $end_date) {
    $current = $_SESSION['current_date'];
    return $current >= $start_date && $current <= $end_date;
}

?>
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Location de Voitures - Algérie</title>
    <style>
/* ============================================================================
   PARTIE 9/12 : CSS BASE, RESET & VARIABLES
   ============================================================================ */

/* === RESET & BASE === */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    /* Couleurs principales */
    --primary: #2563eb;
    --primary-dark: #1e40af;
    --primary-light: #3b82f6;
    
    --secondary: #64748b;
    --secondary-dark: #475569;
    --secondary-light: #94a3b8;
    
    --success: #10b981;
    --success-dark: #059669;
    --success-light: #34d399;
    
    --warning: #f59e0b;
    --warning-dark: #d97706;
    --warning-light: #fbbf24;
    
    --danger: #ef4444;
    --danger-dark: #dc2626;
    --danger-light: #f87171;
    
    --info: #06b6d4;
    --info-dark: #0891b2;
    --info-light: #22d3ee;
    
    --money: #8b5cf6;
    
    /* Couleurs neutres */
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-300: #d1d5db;
    --gray-400: #9ca3af;
    --gray-500: #6b7280;
    --gray-600: #4b5563;
    --gray-700: #374151;
    --gray-800: #1f2937;
    --gray-900: #111827;
    
    /* Couleurs de fond */
    --bg-body: #f8fafc;
    --bg-white: #ffffff;
    --bg-dark: #0f172a;
    
    /* Ombres */
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
    --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    
    /* Bordures */
    --border-radius: 8px;
    --border-radius-sm: 4px;
    --border-radius-lg: 12px;
    --border-radius-xl: 16px;
    
    /* Transitions */
    --transition: all 0.3s ease;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    background-color: var(--bg-body);
    color: var(--gray-800);
    line-height: 1.6;
    font-size: 16px;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* === CONTENEURS === */
.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
    width: 100%;
}

.main-content {
    flex: 1;
    padding: 30px 20px;
}

/* === SIMULATEUR DE DATE === */
.date-simulator {
    background: linear-gradient(135deg, var(--gray-800) 0%, var(--gray-900) 100%);
    color: white;
    padding: 12px 0;
    box-shadow: var(--shadow-md);
}

.date-controls {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.current-date {
    background: rgba(255, 255, 255, 0.1);
    padding: 8px 20px;
    border-radius: var(--border-radius);
    font-size: 15px;
    backdrop-filter: blur(10px);
}

.btn-date {
    background: rgba(255, 255, 255, 0.15);
    color: white;
    padding: 8px 16px;
    border-radius: var(--border-radius);
    text-decoration: none;
    font-weight: 500;
    transition: var(--transition);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn-date:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

/* === NAVIGATION === */
.navbar {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    padding: 20px 0;
    box-shadow: var(--shadow-lg);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.navbar .container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.nav-brand h1 {
    font-size: 26px;
    font-weight: 700;
    margin: 0;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
}

.nav-menu {
    display: flex;
    align-items: center;
    gap: 20px;
}

.user-info {
    font-size: 15px;
}

.user-role {
    background: rgba(255, 255, 255, 0.2);
    padding: 4px 10px;
    border-radius: var(--border-radius-sm);
    font-size: 13px;
    margin-left: 8px;
}

.nav-text {
    font-size: 15px;
    opacity: 0.95;
}

/* === ALERTES === */
.alert {
    padding: 16px 20px;
    border-radius: var(--border-radius);
    margin-bottom: 20px;
    font-weight: 500;
    box-shadow: var(--shadow);
    animation: slideDown 0.3s ease;
    transition: opacity 0.5s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert-success {
    background-color: #d1fae5;
    color: var(--success-dark);
    border-left: 4px solid var(--success);
}

.alert-error {
    background-color: #fee2e2;
    color: var(--danger-dark);
    border-left: 4px solid var(--danger);
}

.alert-info {
    background-color: #dbeafe;
    color: var(--info-dark);
    border-left: 4px solid var(--info);
}

/* === BOUTONS === */
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: var(--border-radius);
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.btn:active {
    transform: translateY(0);
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
}

.btn-primary:hover {
    background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary) 100%);
}

.btn-success {
    background: linear-gradient(135deg, var(--success) 0%, var(--success-dark) 100%);
    color: white;
}

.btn-success:hover {
    background: linear-gradient(135deg, var(--success-light) 0%, var(--success) 100%);
}

.btn-warning {
    background: linear-gradient(135deg, var(--warning) 0%, var(--warning-dark) 100%);
    color: white;
}

.btn-warning:hover {
    background: linear-gradient(135deg, var(--warning-light) 0%, var(--warning) 100%);
}

.btn-danger {
    background: linear-gradient(135deg, var(--danger) 0%, var(--danger-dark) 100%);
    color: white;
}

.btn-danger:hover {
    background: linear-gradient(135deg, var(--danger-light) 0%, var(--danger) 100%);
}

.btn-info {
    background: linear-gradient(135deg, var(--info) 0%, var(--info-dark) 100%);
    color: white;
}

.btn-secondary {
    background: var(--gray-200);
    color: var(--gray-800);
}

.btn-secondary:hover {
    background: var(--gray-300);
}

.btn-logout {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.btn-logout:hover {
    background: rgba(255, 255, 255, 0.3);
}

.btn-block {
    width: 100%;
    display: block;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 13px;
}

/* === BADGES === */
.badge {
    padding: 4px 10px;
    border-radius: var(--border-radius-sm);
    font-size: 13px;
    font-weight: 600;
    display: inline-block;
}

.badge-success {
    background: var(--success-light);
    color: var(--success-dark);
}

.badge-warning {
    background: var(--warning-light);
    color: var(--warning-dark);
}

.badge-danger {
    background: var(--danger-light);
    color: var(--danger-dark);
}

.badge-info {
    background: var(--info-light);
    color: var(--info-dark);
}

.badge-secondary {
    background: var(--gray-300);
    color: var(--gray-700);
    
}    </style>
</head>
<body>

<!-- ============================================================================ -->
<!-- PARTIE 5/12 : STRUCTURE HTML & HEADER -->
<!-- ============================================================================ -->

<!-- Barre de navigation des dates (pour test) -->
<div class="date-simulator">
    <div class="container">
        <div class="date-controls">
            <a href="?date_action=prev" class="btn-date">◄ Jour précédent</a>
            <div class="current-date">
                <strong>Date actuelle (simulation):</strong> 
                <?php echo formatDate($_SESSION['current_date']); ?>
            </div>
            <a href="?date_action=next" class="btn-date">Jour suivant ►</a>
        </div>
    </div>
</div>

<!-- Barre de navigation principale -->
<nav class="navbar">
    <div class="container">
        <div class="nav-brand">
            <h1>🚗 Location Auto DZ</h1>
        </div>
        
        <?php if (User::isLoggedIn()): ?>
            <div class="nav-menu">
                <span class="user-info">
                    Bonjour, <strong><?php echo htmlspecialchars($_SESSION['first_name']); ?></strong>
                    <span class="user-role">(<?php echo ucfirst(User::getRole()); ?>)</span>
                </span>
                <a href="?action=logout" class="btn btn-logout">Déconnexion</a>
            </div>
        <?php else: ?>
            <div class="nav-menu">
                <span class="nav-text">Bienvenue sur notre plateforme</span>
            </div>
        <?php endif; ?>
    </div>
</nav>

<!-- Messages de notification -->
<?php if (!empty($message)): ?>
<div class="container">
    <div class="alert alert-<?php echo $message_type; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
</div>
<?php endif; ?>

<!-- Conteneur principal -->
<div class="container main-content">

<?php
// ============================================================================
// AFFICHAGE SELON L'ÉTAT DE CONNEXION
// ============================================================================

if (!User::isLoggedIn()):
    // ========================================
    // PAGE DE CONNEXION / INSCRIPTION
    // ========================================
?>

<div class="auth-container">
    <div class="auth-box">
        <h2>Connexion</h2>
        <form method="POST" class="form">
            <input type="hidden" name="action" value="login">
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required class="form-control" placeholder="votre@email.dz">
            </div>
            
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" required class="form-control" placeholder="••••••••">
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
        </form>
        
        <div class="auth-help">
            <p><strong>Comptes de test:</strong></p>
            <ul>
                <li><strong>Owner:</strong> ahmed.benali@autoloc-alger.dz / owner123</li>
                <li><strong>Agent:</strong> fatima.khelifi@autoloc-alger.dz / agent123</li>
                <li><strong>Client:</strong> sofiane.hamidi@email.dz / client123</li>
            </ul>
        </div>
    </div>
    
    <div class="auth-box">
        <h2>Inscription Client</h2>
        <form method="POST" class="form">
            <input type="hidden" name="action" value="register">
            <input type="hidden" name="role" value="client">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Prénom</label>
                    <input type="text" name="first_name" required class="form-control">
                </div>
                
                <div class="form-group">
                    <label>Nom</label>
                    <input type="text" name="last_name" required class="form-control">
                </div>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required class="form-control">
            </div>
            
            <div class="form-group">
                <label>Téléphone</label>
                <input type="tel" name="phone" required class="form-control" placeholder="07XXXXXXXX">
            </div>
            
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" required class="form-control" minlength="6">
            </div>
            
            <div class="form-group">
                <label>Permis de conduire</label>
                <input type="text" name="driver_license" required class="form-control" placeholder="DL12345A">
            </div>
            
            <div class="form-group">
                <label>Adresse</label>
                <textarea name="address" required class="form-control" rows="2"></textarea>
            </div>
            
            <div class="form-group">
                <label>Wilaya</label>
                <select name="wilaya" required class="form-control">
                    <option value="">Sélectionner une wilaya</option>
                    <?php foreach ($wilayas as $code => $name): ?>
                        <option value="<?php echo $name; ?>"><?php echo $code . ' - ' . $name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-success btn-block">S'inscrire</button>
        </form>
    </div>
</div>

<?php
else:
    // ========================================
    // UTILISATEUR CONNECTÉ - DASHBOARDS
    // ========================================
    
    $role = User::getRole();
    
    // ========================================
    // DASHBOARD CLIENT
    // ========================================
    if ($role === 'client'):
?>

<div class="dashboard">
    <div class="dashboard-header">
        <h2>Tableau de bord Client</h2>
        <p>Trouvez et réservez votre véhicule</p>
    </div>
    
    <!-- Statistiques personnelles -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo count($reservations_data); ?></div>
            <div class="stat-label">Mes réservations</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">
                <?php 
                $active = array_filter($reservations_data, function($r) {
                    return $r['status'] === 'ongoing';
                });
                echo count($active);
                ?>
            </div>
            <div class="stat-label">Réservations actives</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo count($cars_data); ?></div>
            <div class="stat-label">Voitures disponibles</div>
        </div>
    </div>
    
    <!-- Section: Voitures disponibles -->
    <div class="section">
        <h3>Voitures disponibles</h3>
        
        <?php if (empty($cars_data)): ?>
            <div class="empty-state">
                <p>Aucune voiture disponible pour le moment.</p>
            </div>
        <?php else: ?>
            <div class="cars-grid">
                <?php foreach ($cars_data as $car): ?>
                    <div class="car-card">
                        <div class="car-header">
                            <h4><?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?></h4>
                            <div class="car-year"><?php echo $car['year']; ?></div>
                        </div>
                        
                        <div class="car-details">
                            <div class="car-info">
                                <span class="label">Matricule:</span>
                                <span class="value"><?php echo htmlspecialchars($car['license_plate']); ?></span>
                            </div>
                            <div class="car-info">
                                <span class="label">Couleur:</span>
                                <span class="value"><?php echo htmlspecialchars($car['color']); ?></span>
                            </div>
                            <div class="car-info">
                                <span class="label">Compagnie:</span>
                                <span class="value"><?php echo htmlspecialchars($car['company_name']); ?></span>
                            </div>
                        </div>
                        
                        <div class="car-price">
                            <span class="price-label">Prix par jour:</span>
                            <span class="price-value"><?php echo formatPrice($car['daily_price']); ?></span>
                        </div>
                        
                        <button class="btn btn-primary btn-block" onclick="openReservationModal(<?php echo $car['car_id']; ?>, '<?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>', <?php echo $car['daily_price']; ?>)">
                            Réserver maintenant
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Section: Mes réservations -->
    <div class="section">
        <h3>Mes réservations</h3>
        
        <?php if (empty($reservations_data)): ?>
            <div class="empty-state">
                <p>Vous n'avez aucune réservation.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Véhicule</th>
                            <th>Matricule</th>
                            <th>Compagnie</th>
                            <th>Date début</th>
                            <th>Date fin</th>
                            <th>Jours</th>
                            <th>Prix total</th>
                            <th>Paiement</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations_data as $reservation): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reservation['brand'] . ' ' . $reservation['model']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['license_plate']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['company_name']); ?></td>
                                <td><?php echo formatDate($reservation['start_date']); ?></td>
                                <td><?php echo formatDate($reservation['end_date']); ?></td>
                                <td><?php echo calculateDays($reservation['start_date'], $reservation['end_date']); ?></td>
                                <td><?php echo formatPrice($reservation['total_price']); ?></td>
                                <td><?php echo getStatusBadge($reservation['payment_status']); ?></td>
                                <td><?php echo getStatusBadge($reservation['status']); ?></td>
                                <td>
                                    <?php if ($reservation['status'] === 'ongoing' && $reservation['payment_status'] === 'pending'): ?>
                                        <button class="btn btn-sm btn-success" onclick="payReservation(<?php echo $reservation['reservation_id']; ?>)">
                                            Payer
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($reservation['status'] === 'ongoing'): ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Voulez-vous annuler cette réservation?');">
                                            <input type="hidden" name="action" value="cancel_reservation">
                                            <input type="hidden" name="reservation_id" value="<?php echo $reservation['reservation_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Annuler</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de réservation -->
<div id="reservationModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeReservationModal()">&times;</span>
        <h3>Réserver un véhicule</h3>
        
        <form method="POST" id="reservationForm" class="form">
            <input type="hidden" name="action" value="create_reservation">
            <input type="hidden" name="car_id" id="modal_car_id">
            
            <div class="form-group">
                <label>Véhicule:</label>
                <div id="modal_car_name" class="car-name-display"></div>
            </div>
            
            <div class="form-group">
                <label>Prix journalier:</label>
                <div id="modal_daily_price" class="price-display"></div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Date de début</label>
                    <input type="date" name="start_date" id="start_date" required class="form-control" 
                           min="<?php echo $_SESSION['current_date']; ?>" 
                           value="<?php echo $_SESSION['current_date']; ?>"
                           onchange="calculateTotal()">
                </div>
                
                <div class="form-group">
                    <label>Date de fin</label>
                    <input type="date" name="end_date" id="end_date" required class="form-control" 
                           min="<?php echo $_SESSION['current_date']; ?>"
                           onchange="calculateTotal()">
                </div>
            </div>
            
            <div class="reservation-summary">
                <div class="summary-row">
                    <span>Nombre de jours:</span>
                    <span id="total_days">-</span>
                </div>
                <div class="summary-row total">
                    <span>Prix total:</span>
                    <span id="total_price">-</span>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeReservationModal()">Annuler</button>
                <button type="submit" class="btn btn-primary">Confirmer la réservation</button>
            </div>
        </form>
    </div>
</div>

<?php
    endif; // Fin dashboard client
?>

<?php
    // ========================================
    // DASHBOARD AGENT / OWNER
    // ========================================
    if ($role === 'agent' || $role === 'owner'):
        $company_info = $companyObj->getById(User::getCompanyId());
?>

<div class="dashboard">
    <div class="dashboard-header">
        <h2>Tableau de bord <?php echo $role === 'owner' ? 'Propriétaire' : 'Agent'; ?></h2>
        <p>Compagnie: <strong><?php echo htmlspecialchars($company_info['name']); ?></strong></p>
    </div>
    
    <!-- Statistiques de la compagnie -->
    <div class="stats-grid">
        <div class="stat-card stat-primary">
            <div class="stat-number"><?php echo $stats_data['total_cars']; ?></div>
            <div class="stat-label">Total véhicules</div>
        </div>
        <div class="stat-card stat-success">
            <div class="stat-number"><?php echo $stats_data['available_cars']; ?></div>
            <div class="stat-label">Disponibles</div>
        </div>
        <div class="stat-card stat-warning">
            <div class="stat-number"><?php echo $stats_data['rented_cars']; ?></div>
            <div class="stat-label">Louées</div>
        </div>
        <div class="stat-card stat-info">
            <div class="stat-number"><?php echo $stats_data['ongoing_reservations']; ?></div>
            <div class="stat-label">Réservations actives</div>
        </div>
        <div class="stat-card stat-money">
            <div class="stat-number"><?php echo formatPrice($stats_data['total_revenue']); ?></div>
            <div class="stat-label">Revenu total</div>
        </div>
    </div>
    
    <!-- Onglets de navigation -->
    <div class="tabs">
        <button class="tab-button active" onclick="showTab('cars')">Gestion des véhicules</button>
        <button class="tab-button" onclick="showTab('reservations')">Réservations</button>
        <?php if ($role === 'owner'): ?>
            <button class="tab-button" onclick="showTab('agents')">Agents</button>
        <?php endif; ?>
    </div>
    
    <!-- Onglet: Gestion des véhicules -->
    <div id="tab-cars" class="tab-content active">
        <div class="section-header">
            <h3>Mes véhicules</h3>
            <button class="btn btn-primary" onclick="openCarModal()">+ Ajouter un véhicule</button>
        </div>
        
        <?php if (empty($cars_data)): ?>
            <div class="empty-state">
                <p>Aucun véhicule enregistré. Ajoutez votre premier véhicule !</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Marque</th>
                            <th>Modèle</th>
                            <th>Année</th>
                            <th>Matricule</th>
                            <th>Couleur</th>
                            <th>Prix/jour</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cars_data as $car): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($car['brand']); ?></td>
                                <td><?php echo htmlspecialchars($car['model']); ?></td>
                                <td><?php echo $car['year']; ?></td>
                                <td><strong><?php echo htmlspecialchars($car['license_plate']); ?></strong></td>
                                <td><?php echo htmlspecialchars($car['color']); ?></td>
                                <td><?php echo formatPrice($car['daily_price']); ?></td>
                                <td><?php echo getStatusBadge($car['status']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick='editCar(<?php echo json_encode($car); ?>)'>
                                        Modifier
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce véhicule?');">
                                        <input type="hidden" name="action" value="delete_car">
                                        <input type="hidden" name="car_id" value="<?php echo $car['car_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Onglet: Réservations -->
    <div id="tab-reservations" class="tab-content">
        <div class="section-header">
            <h3>Réservations de la compagnie</h3>
        </div>
        
        <?php if (empty($reservations_data)): ?>
            <div class="empty-state">
                <p>Aucune réservation pour le moment.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client</th>
                            <th>Contact</th>
                            <th>Véhicule</th>
                            <th>Matricule</th>
                            <th>Date début</th>
                            <th>Date fin</th>
                            <th>Jours</th>
                            <th>Prix total</th>
                            <th>Paiement</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations_data as $reservation): ?>
                            <tr class="<?php echo isActiveReservation($reservation['start_date'], $reservation['end_date']) ? 'active-reservation' : ''; ?>">
                                <td>#<?php echo $reservation['reservation_id']; ?></td>
                                <td><?php echo htmlspecialchars($reservation['first_name'] . ' ' . $reservation['last_name']); ?></td>
                                <td>
                                    <div><?php echo htmlspecialchars($reservation['email']); ?></div>
                                    <div class="text-muted"><?php echo htmlspecialchars($reservation['phone']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($reservation['brand'] . ' ' . $reservation['model']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['license_plate']); ?></td>
                                <td><?php echo formatDate($reservation['start_date']); ?></td>
                                <td><?php echo formatDate($reservation['end_date']); ?></td>
                                <td><?php echo calculateDays($reservation['start_date'], $reservation['end_date']); ?></td>
                                <td><strong><?php echo formatPrice($reservation['total_price']); ?></strong></td>
                                <td><?php echo getStatusBadge($reservation['payment_status']); ?></td>
                                <td><?php echo getStatusBadge($reservation['status']); ?></td>
                                <td>
                                    <?php if ($reservation['payment_status'] === 'pending'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="mark_paid">
                                            <input type="hidden" name="reservation_id" value="<?php echo $reservation['reservation_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-success">Marquer payé</button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($reservation['status'] === 'ongoing'): ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Terminer cette réservation?');">
                                            <input type="hidden" name="action" value="complete_reservation">
                                            <input type="hidden" name="reservation_id" value="<?php echo $reservation['reservation_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-info">Terminer</button>
                                        </form>
                                        
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Annuler cette réservation?');">
                                            <input type="hidden" name="action" value="cancel_reservation">
                                            <input type="hidden" name="reservation_id" value="<?php echo $reservation['reservation_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Annuler</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($role === 'owner'): ?>
    <!-- Onglet: Agents (Owner uniquement) -->
    <div id="tab-agents" class="tab-content">
        <div class="section-header">
            <h3>Agents de la compagnie</h3>
            <button class="btn btn-primary" onclick="openAgentModal()">+ Ajouter un agent</button>
        </div>
        
        <?php if (empty($agents_data)): ?>
            <div class="empty-state">
                <p>Aucun agent enregistré.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Date d'ajout</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agents_data as $agent): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($agent['email']); ?></td>
                                <td><?php echo htmlspecialchars($agent['phone']); ?></td>
                                <td><?php echo formatDate($agent['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal d'ajout/modification de voiture -->
<div id="carModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeCarModal()">&times;</span>
        <h3 id="carModalTitle">Ajouter un véhicule</h3>
        
        <form method="POST" id="carForm" class="form">
            <input type="hidden" name="action" id="car_action" value="add_car">
            <input type="hidden" name="car_id" id="car_id">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Marque *</label>
                    <select name="brand" id="car_brand" required class="form-control" onchange="updateModels()">
                        <option value="">Sélectionner</option>
                        <?php foreach (array_keys($car_brands) as $brand): ?>
                            <option value="<?php echo $brand; ?>"><?php echo $brand; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Modèle *</label>
                    <select name="model" id="car_model" required class="form-control">
                        <option value="">Sélectionner d'abord une marque</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Année *</label>
                    <input type="number" name="year" id="car_year" required class="form-control" 
                           min="1990" max="<?php echo date('Y'); ?>" value="<?php echo date('Y'); ?>">
                </div>
                
                <div class="form-group">
                    <label>Couleur *</label>
                    <select name="color" id="car_color" required class="form-control">
                        <option value="">Sélectionner</option>
                        <?php foreach ($colors as $color): ?>
                            <option value="<?php echo $color; ?>"><?php echo $color; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Matricule * (Format: 15231 113 31)</label>
                <input type="text" name="license_plate" id="car_license_plate" required class="form-control" 
                       placeholder="15231 113 31" pattern="\d{5}[\s\-]\d{3}[\s\-]\d{2}">
                <small class="form-help">Format algérien: 5 chiffres - 3 chiffres - 2 chiffres</small>
            </div>
            
            <div class="form-group" id="status_group" style="display:none;">
                <label>Statut</label>
                <select name="status" id="car_status" class="form-control">
                    <option value="available">Disponible</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>
            
            <div class="alert alert-info">
                <strong>Note:</strong> Le prix journalier sera calculé automatiquement selon l'année et la marque (entre <?php echo formatPrice(MIN_PRICE); ?> et <?php echo formatPrice(MAX_PRICE); ?>).
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeCarModal()">Annuler</button>
                <button type="submit" class="btn btn-primary" id="carSubmitBtn">Ajouter</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal d'ajout d'agent (Owner uniquement) -->
<?php if ($role === 'owner'): ?>
<div id="agentModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeAgentModal()">&times;</span>
        <h3>Ajouter un agent</h3>
        
        <form method="POST" class="form">
            <input type="hidden" name="action" value="register">
            <input type="hidden" name="role" value="agent">
            <input type="hidden" name="company_id" value="<?php echo User::getCompanyId(); ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Prénom *</label>
                    <input type="text" name="first_name" required class="form-control">
                </div>
                
                <div class="form-group">
                    <label>Nom *</label>
                    <input type="text" name="last_name" required class="form-control">
                </div>
            </div>
            
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required class="form-control">
            </div>
            
            <div class="form-group">
                <label>Téléphone *</label>
                <input type="tel" name="phone" required class="form-control" placeholder="07XXXXXXXX">
            </div>
            
            <div class="form-group">
                <label>Mot de passe *</label>
                <input type="password" name="password" required class="form-control" minlength="6">
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeAgentModal()">Annuler</button>
                <button type="submit" class="btn btn-primary">Ajouter l'agent</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php
    endif; // Fin dashboard agent/owner
?>
<?php
    // ========================================
    // DASHBOARD ADMIN (Extension future)
    // ========================================
    if ($role === 'admin'):
?>

<div class="dashboard">
    <div class="dashboard-header">
        <h2>Tableau de bord Administrateur</h2>
        <p>Gestion globale du système</p>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card stat-primary">
            <div class="stat-number"><?php echo count($companies_data); ?></div>
            <div class="stat-label">Compagnies</div>
        </div>
    </div>
    
    <div class="section">
        <h3>Toutes les compagnies</h3>
        
        <?php if (empty($companies_data)): ?>
            <div class="empty-state">
                <p>Aucune compagnie enregistrée.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Wilaya</th>
                            <th>Date de création</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($companies_data as $company): ?>
                            <tr>
                                <td>#<?php echo $company['company_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($company['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($company['email']); ?></td>
                                <td><?php echo htmlspecialchars($company['phone']); ?></td>
                                <td><?php echo htmlspecialchars($company['wilaya']); ?></td>
                                <td><?php echo formatDate($company['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
    endif; // Fin dashboard admin
    
endif; // Fin utilisateur connecté
?>

</div> <!-- Fin container main-content -->

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <p>&copy; <?php echo date('Y'); ?> Location Auto DZ - Tous droits réservés</p>
        <p>Système de location de véhicules en Algérie</p>
    </div>
</footer>

<!-- ============================================================================ -->
<!-- PARTIE 7/12 : JAVASCRIPT -->
<!-- ============================================================================ -->

<script>
// ============================================================================
// VARIABLES GLOBALES
// ============================================================================

// Données des marques et modèles
const carBrands = <?php echo json_encode($car_brands); ?>;

// ============================================================================
// GESTION DES ONGLETS
// ============================================================================

function showTab(tabName) {
    // Cacher tous les onglets
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => tab.classList.remove('active'));
    
    // Désactiver tous les boutons
    const buttons = document.querySelectorAll('.tab-button');
    buttons.forEach(btn => btn.classList.remove('active'));
    
    // Afficher l'onglet sélectionné
    const selectedTab = document.getElementById('tab-' + tabName);
    if (selectedTab) {
        selectedTab.classList.add('active');
    }
    
    // Activer le bouton correspondant
    event.target.classList.add('active');
}

// ============================================================================
// MODAL RÉSERVATION (Client)
// ============================================================================

let currentCarPrice = 0;

function openReservationModal(carId, carName, dailyPrice) {
    currentCarPrice = dailyPrice;
    
    document.getElementById('modal_car_id').value = carId;
    document.getElementById('modal_car_name').textContent = carName;
    document.getElementById('modal_daily_price').textContent = formatPrice(dailyPrice);
    
    // Réinitialiser les dates
    const today = '<?php echo $_SESSION['current_date']; ?>';
    document.getElementById('start_date').value = today;
    document.getElementById('end_date').value = '';
    
    // Réinitialiser le résumé
    document.getElementById('total_days').textContent = '-';
    document.getElementById('total_price').textContent = '-';
    
    document.getElementById('reservationModal').style.display = 'block';
}

function closeReservationModal() {
    document.getElementById('reservationModal').style.display = 'none';
}

function calculateTotal() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    
    if (startDate && endDate) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        
        if (end < start) {
            alert('La date de fin doit être après la date de début');
            document.getElementById('end_date').value = '';
            return;
        }
        
        // Calculer le nombre de jours
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        
        // Calculer le prix total
        const totalPrice = diffDays * currentCarPrice;
        
        // Afficher les résultats
        document.getElementById('total_days').textContent = diffDays + ' jour' + (diffDays > 1 ? 's' : '');
        document.getElementById('total_price').textContent = formatPrice(totalPrice);
    }
}

// ============================================================================
// MODAL VOITURE (Agent/Owner)
// ============================================================================

function openCarModal() {
    document.getElementById('carModalTitle').textContent = 'Ajouter un véhicule';
    document.getElementById('car_action').value = 'add_car';
    document.getElementById('carSubmitBtn').textContent = 'Ajouter';
    document.getElementById('carForm').reset();
    document.getElementById('status_group').style.display = 'none';
    document.getElementById('car_model').innerHTML = '<option value="">Sélectionner d\'abord une marque</option>';
    document.getElementById('carModal').style.display = 'block';
}

function editCar(car) {
    document.getElementById('carModalTitle').textContent = 'Modifier un véhicule';
    document.getElementById('car_action').value = 'edit_car';
    document.getElementById('carSubmitBtn').textContent = 'Modifier';
    
    // Remplir le formulaire
    document.getElementById('car_id').value = car.car_id;
    document.getElementById('car_brand').value = car.brand;
    
    // Mettre à jour les modèles
    updateModels();
    
    // Sélectionner le modèle
    setTimeout(() => {
        document.getElementById('car_model').value = car.model;
    }, 100);
    
    document.getElementById('car_year').value = car.year;
    document.getElementById('car_color').value = car.color;
    document.getElementById('car_license_plate').value = car.license_plate;
    document.getElementById('car_status').value = car.status;
    
    // Afficher le champ statut pour la modification
    document.getElementById('status_group').style.display = 'block';
    
    document.getElementById('carModal').style.display = 'block';
}

function closeCarModal() {
    document.getElementById('carModal').style.display = 'none';
}

function updateModels() {
    const brandSelect = document.getElementById('car_brand');
    const modelSelect = document.getElementById('car_model');
    const selectedBrand = brandSelect.value;
    
    // Vider les modèles
    modelSelect.innerHTML = '<option value="">Sélectionner un modèle</option>';
    
    if (selectedBrand && carBrands[selectedBrand]) {
        carBrands[selectedBrand].forEach(model => {
            const option = document.createElement('option');
            option.value = model;
            option.textContent = model;
            modelSelect.appendChild(option);
        });
    }
}

// ============================================================================
// MODAL AGENT (Owner)
// ============================================================================

function openAgentModal() {
    document.getElementById('agentModal').style.display = 'block';
}

function closeAgentModal() {
    document.getElementById('agentModal').style.display = 'none';
}

// ============================================================================
// PAIEMENT VIRTUEL (Client)
// ============================================================================

function payReservation(reservationId) {
    if (confirm('Confirmer le paiement virtuel de cette réservation?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="mark_paid">
            <input type="hidden" name="reservation_id" value="${reservationId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// ============================================================================
// FONCTIONS UTILITAIRES
// ============================================================================

function formatPrice(price) {
    return new Intl.NumberFormat('fr-DZ', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(price) + ' <?php echo CURRENCY; ?>';
}

// ============================================================================
// FERMETURE MODALS EN CLIQUANT EN DEHORS
// ============================================================================

window.onclick = function(event) {
    const reservationModal = document.getElementById('reservationModal');
    const carModal = document.getElementById('carModal');
    const agentModal = document.getElementById('agentModal');
    
    if (event.target === reservationModal) {
        closeReservationModal();
    }
    if (event.target === carModal) {
        closeCarModal();
    }
    if (event.target === agentModal) {
        closeAgentModal();
    }
}

// ============================================================================
// VALIDATION DES FORMULAIRES
// ============================================================================

// Validation du format de la plaque d'immatriculation
document.addEventListener('DOMContentLoaded', function() {
    const licensePlateInput = document.getElementById('car_license_plate');
    
    if (licensePlateInput) {
        licensePlateInput.addEventListener('input', function() {
            let value = this.value.replace(/[^\d\s\-]/g, '');
            
            // Formater automatiquement: XXXXX XXX XX
            if (value.length >= 5) {
                value = value.slice(0, 5) + ' ' + value.slice(5);
            }
            if (value.length >= 9) {
                value = value.slice(0, 9) + ' ' + value.slice(9, 11);
            }
            
            this.value = value;
        });
    }
    
    // Validation de la date de fin dans le formulaire de réservation
    const endDateInput = document.getElementById('end_date');
    if (endDateInput) {
        endDateInput.addEventListener('change', function() {
            const startDate = document.getElementById('start_date').value;
            const endDate = this.value;
            
            if (startDate && endDate && endDate < startDate) {
                alert('La date de fin doit être après la date de début');
                this.value = '';
            }
        });
    }
});

// ============================================================================
// AUTO-FERMETURE DES MESSAGES APRÈS 5 SECONDES
// ============================================================================

document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });
});

// ============================================================================
// CONFIRMATION AVANT DÉCONNEXION
// ============================================================================

const logoutLinks = document.querySelectorAll('a[href*="logout"]');
logoutLinks.forEach(link => {
    link.addEventListener('click', function(e) {
        if (!confirm('Êtes-vous sûr de vouloir vous déconnecter?')) {
            e.preventDefault();
        }
    });
});

console.log('✅ Application de location de voitures chargée');
console.log('🚗 Système prêt à l\'emploi');
</script>

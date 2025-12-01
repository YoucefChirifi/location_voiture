<?php
// Partie 1: Configuration de la base de données et création des tables

// Configuration de la connexion
$host = 'localhost';
$dbname = 'location_voiture_dz';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Créer la base de données si elle n'existe pas
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE $dbname");
    
    // Table Wilaya
    $pdo->exec("CREATE TABLE IF NOT EXISTS wilaya (
        id INT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL
    )");
    
    // Table Company
    $pdo->exec("CREATE TABLE IF NOT EXISTS company (
        company_id INT PRIMARY KEY AUTO_INCREMENT,
        c_name VARCHAR(200) NOT NULL,
        id_admin INT,
        frais DECIMAL(10,2),
        special_code VARCHAR(50) UNIQUE
    )");
    
    // Table Administrator
    $pdo->exec("CREATE TABLE IF NOT EXISTS administrator (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nom VARCHAR(100) NOT NULL,
        prenom VARCHAR(100) NOT NULL,
        age INT,
        numero_tlfn VARCHAR(20),
        nationalite VARCHAR(50) DEFAULT 'Algérienne',
        nemro_cart_national VARCHAR(20),
        wilaya_id INT,
        salaire DECIMAL(10,2),
        company_id INT,
        email VARCHAR(150) UNIQUE,
        password VARCHAR(255),
        FOREIGN KEY (wilaya_id) REFERENCES wilaya(id),
        FOREIGN KEY (company_id) REFERENCES company(company_id)
    )");
    
    // Table Agent
    $pdo->exec("CREATE TABLE IF NOT EXISTS agent (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nom VARCHAR(100) NOT NULL,
        prenom VARCHAR(100) NOT NULL,
        age INT,
        numero_tlfn VARCHAR(20),
        nationalite VARCHAR(50) DEFAULT 'Algérienne',
        nemro_cart_national VARCHAR(20),
        wilaya_id INT,
        salaire DECIMAL(10,2),
        company_id INT,
        email VARCHAR(150) UNIQUE,
        password VARCHAR(255),
        FOREIGN KEY (wilaya_id) REFERENCES wilaya(id),
        FOREIGN KEY (company_id) REFERENCES company(company_id)
    )");
    
    // Table Client
    $pdo->exec("CREATE TABLE IF NOT EXISTS client (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nom VARCHAR(100) NOT NULL,
        prenom VARCHAR(100) NOT NULL,
        age INT,
        numero_tlfn VARCHAR(20),
        nationalite VARCHAR(50) DEFAULT 'Algérienne',
        nemro_cart_national VARCHAR(20),
        wilaya_id INT,
        company_id INT,
        email VARCHAR(150) UNIQUE,
        password VARCHAR(255),
        status ENUM('non réservé', 'réservé', 'payé', 'annulé') DEFAULT 'non réservé',
        FOREIGN KEY (wilaya_id) REFERENCES wilaya(id),
        FOREIGN KEY (company_id) REFERENCES company(company_id)
    )");
    
    // Table Car
    $pdo->exec("CREATE TABLE IF NOT EXISTS car (
        id_car INT PRIMARY KEY AUTO_INCREMENT,
        company_id INT,
        marque VARCHAR(100),
        model VARCHAR(100),
        color VARCHAR(50),
        annee INT,
        matricule VARCHAR(20) UNIQUE,
        category INT CHECK(category IN (1,2,3)),
        prix_day DECIMAL(10,2),
        status_voiture ENUM('1', '2', '3') DEFAULT '1',
        voiture_work ENUM('disponible', 'non disponible') DEFAULT 'disponible',
        FOREIGN KEY (company_id) REFERENCES company(company_id)
    )");
    
    // Table Payment
    $pdo->exec("CREATE TABLE IF NOT EXISTS payment (
        id_payment INT PRIMARY KEY AUTO_INCREMENT,
        status ENUM('paid', 'not_paid') DEFAULT 'not_paid',
        montant DECIMAL(10,2),
        date_payment DATETIME,
        card_number VARCHAR(16),
        card_code VARCHAR(3)
    )");
    
    // Table Reservation
    $pdo->exec("CREATE TABLE IF NOT EXISTS reservation (
        id_reservation INT PRIMARY KEY AUTO_INCREMENT,
        id_agent INT,
        id_client INT,
        id_company INT,
        car_id INT,
        id_wilaya INT,
        id_admin INT,
        period INT,
        date_debut DATE,
        date_fin DATE,
        montant DECIMAL(10,2),
        id_payment INT,
        FOREIGN KEY (id_agent) REFERENCES agent(id),
        FOREIGN KEY (id_client) REFERENCES client(id),
        FOREIGN KEY (id_company) REFERENCES company(company_id),
        FOREIGN KEY (car_id) REFERENCES car(id_car),
        FOREIGN KEY (id_wilaya) REFERENCES wilaya(id),
        FOREIGN KEY (id_admin) REFERENCES administrator(id),
        FOREIGN KEY (id_payment) REFERENCES payment(id_payment)
    )");
    
    echo "Base de données créée avec succès!<br>";
    
} catch(PDOException $e) {
    die("Erreur: " . $e->getMessage());
}
?>
<?php
// Partie 2: Insertion des données initiales (Wilayas, Company, Users, Cars)

require_once 'part1_database.php';

// Insertion des 69 Wilayas
$wilayas = [
    1=>'Adrar', 2=>'Chlef', 3=>'Laghouat', 4=>'Oum El Bouaghi', 5=>'Batna', 6=>'Béjaïa', 
    7=>'Biskra', 8=>'Béchar', 9=>'Blida', 10=>'Bouira', 11=>'Tamanrasset', 12=>'Tébessa', 
    13=>'Tlemcen', 14=>'Tiaret', 15=>'Tizi Ouzou', 16=>'Alger', 17=>'Djelfa', 18=>'Jijel', 
    19=>'Sétif', 20=>'Saïda', 21=>'Skikda', 22=>'Sidi Bel Abbès', 23=>'Annaba', 24=>'Guelma', 
    25=>'Constantine', 26=>'Médéa', 27=>'Mostaganem', 28=>'M\'Sila', 29=>'Mascara', 30=>'Ouargla', 
    31=>'Oran', 32=>'El Bayadh', 33=>'Illizi', 34=>'Bordj Bou Arreridj', 35=>'Boumerdès', 
    36=>'El Tarf', 37=>'Tindouf', 38=>'Tissemsilt', 39=>'El Oued', 40=>'Khenchela', 
    41=>'Souk Ahras', 42=>'Tipaza', 43=>'Mila', 44=>'Aïn Defla', 45=>'Naâma', 
    46=>'Aïn Témouchent', 47=>'Ghardaïa', 48=>'Relizane', 49=>'Timimoun', 50=>'Bordj Badji Mokhtar', 
    51=>'Ouled Djellal', 52=>'Béni Abbès', 53=>'In Salah', 54=>'In Guezzam', 55=>'Touggourt', 
    56=>'Djanet', 57=>'El M\'Ghair', 58=>'El Meniaa', 59=>'Aflou', 60=>'El Abiodh Sidi Cheikh', 
    61=>'El Aricha', 62=>'El Kantara', 63=>'Barika', 64=>'Bou Saâda', 65=>'Bir El Ater', 
    66=>'Ksar El Boukhari', 67=>'Ksar Chellala', 68=>'Aïn Oussara', 69=>'Messaad'
];

$stmt = $pdo->prepare("INSERT IGNORE INTO wilaya (id, nom) VALUES (?, ?)");
foreach($wilayas as $id => $nom) {
    $stmt->execute([$id, $nom]);
}

// Créer la compagnie Cherifi Youssouf Agency
$pdo->exec("INSERT IGNORE INTO company (company_id, c_name, frais, special_code) 
            VALUES (1, 'Cherifi Youssouf Agency', 85000, 'CYA2024')");

// Créer un administrateur
$adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
$pdo->exec("INSERT IGNORE INTO administrator (id, nom, prenom, age, numero_tlfn, nationalite, nemro_cart_national, wilaya_id, salaire, company_id, email, password) 
            VALUES (1, 'Cherifi', 'Youssouf', 35, '0555123456', 'Algérienne', '1234567890123456', 31, 120000, 1, 'admin@cherifi.dz', '$adminPassword')");

$pdo->exec("UPDATE company SET id_admin = 1 WHERE company_id = 1");

// Créer 7 agents
$agentPassword = password_hash('agent123', PASSWORD_DEFAULT);
$agents = [
    ['Benali', 'Ahmed', 28, '0661234567', '1987654321098765', 16, 45000],
    ['Khelifi', 'Fatima', 26, '0771234567', '1122334455667788', 31, 42000],
    ['Meziane', 'Karim', 30, '0551234567', '2233445566778899', 25, 48000],
    ['Bouzid', 'Amina', 27, '0661234568', '3344556677889900', 9, 44000],
    ['Saidi', 'Mohamed', 29, '0771234568', '4455667788990011', 19, 46000],
    ['Brahimi', 'Yasmine', 25, '0551234568', '5566778899001122', 23, 41000],
    ['Hamidi', 'Rami', 31, '0661234569', '6677889900112233', 13, 49000]
];

$stmt = $pdo->prepare("INSERT IGNORE INTO agent (nom, prenom, age, numero_tlfn, nemro_cart_national, wilaya_id, salaire, company_id, email, password) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?)");
foreach($agents as $i => $agent) {
    $email = strtolower($agent[0]) . ($i+1) . '@cherifi.dz';
    $stmt->execute([$agent[0], $agent[1], $agent[2], $agent[3], $agent[4], $agent[5], $agent[6], $email, $agentPassword]);
}

// Créer 7 clients
$clientPassword = password_hash('client123', PASSWORD_DEFAULT);
$clients = [
    ['Mansouri', 'Salim', 32, '0555234567', '7788990011223344', 31],
    ['Larbi', 'Nadia', 28, '0666234567', '8899001122334455', 16],
    ['Touati', 'Bilal', 35, '0777234567', '9900112233445566', 25],
    ['Messaoudi', 'Leila', 29, '0555234568', '0011223344556677', 9],
    ['Rahmani', 'Sofiane', 40, '0666234568', '1122334455667700', 19],
    ['Bouazza', 'Samia', 26, '0777234568', '2233445566778811', 23],
    ['Slimani', 'Amine', 33, '0555234569', '3344556677889922', 13]
];

$stmt = $pdo->prepare("INSERT IGNORE INTO client (nom, prenom, age, numero_tlfn, nemro_cart_national, wilaya_id, company_id, email, password) 
                       VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)");
foreach($clients as $i => $client) {
    $email = strtolower($client[0]) . ($i+1) . '@gmail.com';
    $stmt->execute([$client[0], $client[1], $client[2], $client[3], $client[4], $client[5], $email, $clientPassword]);
}

echo "Données initiales insérées avec succès!<br>";
?>
<?php
// Partie 3: Insertion des voitures dans la base de données

require_once 'part1_database.php';

$cars = [
    // Catégorie 1 (4000-6000 DA/jour)
    ['Toyota', 'Corolla', 'Blanc', 2020, 1, 5000],
    ['Volkswagen', 'Golf', 'Noir', 2021, 1, 4500],
    ['Renault', 'Clio', 'Gris', 2019, 1, 4200],
    ['Hyundai', 'i30', 'Bleu', 2020, 1, 4800],
    ['Kia', 'Rio', 'Rouge', 2021, 1, 4600],
    ['Peugeot', '208', 'Jaune', 2022, 1, 5500],
    ['Toyota', 'Corolla', 'Noir', 2021, 1, 5200],
    
    // Catégorie 2 (6000-12000 DA/jour)
    ['Toyota', 'Camry', 'Blanc', 2024, 2, 11000],
    ['BMW', '3 Series', 'Noir', 2020, 2, 10500],
    ['Volkswagen', 'Passat', 'Gris', 2022, 2, 8500],
    ['Audi', 'A4', 'Bleu', 2021, 2, 9800],
    ['Mercedes-Benz', 'C-Class', 'Noir', 2023, 2, 11500],
    ['Volvo', 'S60', 'Blanc', 2022, 2, 9200],
    ['Škoda', 'Superb', 'Gris', 2023, 2, 8800],
    
    // Catégorie 3 (12000-20000 DA/jour)
    ['Lexus', 'LS', 'Noir', 2023, 3, 18000],
    ['Porsche', 'Panamera', 'Bleu', 2022, 3, 19500],
    ['Mercedes-Benz', 'S-Class', 'Argent', 2024, 3, 19000],
    ['BMW', '7 Series', 'Noir', 2023, 3, 17500],
    ['Audi', 'A8', 'Gris', 2023, 3, 16800],
    ['Nissan', 'Maxima', 'Rouge', 2022, 3, 14500],
    ['Nissan', 'Cima', 'Blanc', 2023, 3, 15200]
];

$stmt = $pdo->prepare("INSERT INTO car (company_id, marque, model, color, annee, matricule, category, prix_day, status_voiture, voiture_work) 
                       VALUES (1, ?, ?, ?, ?, ?, ?, ?, '1', 'disponible')");

$serialStart = 100000;
foreach($cars as $i => $car) {
    // Générer matricule: serial(6 chiffres) + category(1) + year(2 derniers chiffres) + wilaya(31 pour Oran)
    $serial = $serialStart + $i;
    $category = $car[4];
    $yearSuffix = substr($car[3], -2);
    $wilaya = 31;
    $matricule = $serial . $category . $yearSuffix . $wilaya;
    
    $stmt->execute([$car[0], $car[1], $car[2], $car[3], $matricule, $car[4], $car[5]]);
}

echo "Voitures insérées avec succès! Total: " . count($cars) . " voitures<br>";
?>
<?php
// Partie 4: Classes POO pour la gestion de l'application

class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        try {
            $this->pdo = new PDO("mysql:host=localhost;dbname=location_voiture_dz;charset=utf8mb4", "root", "");
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("Erreur de connexion: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if(self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
}

class User {
    protected $pdo;
    protected $table;
    
    public function __construct($table) {
        $this->pdo = Database::getInstance()->getConnection();
        $this->table = $table;
    }
    
    public function login($email, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }
    
    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getAll($company_id = null) {
        if($company_id) {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE company_id = ?");
            $stmt->execute([$company_id]);
        } else {
            $stmt = $this->pdo->query("SELECT * FROM {$this->table}");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
}

class Client extends User {
    public function __construct() {
        parent::__construct('client');
    }
    
    public function create($data) {
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO client (nom, prenom, age, numero_tlfn, nationalite, nemro_cart_national, wilaya_id, company_id, email, password) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['nom'], $data['prenom'], $data['age'], $data['numero_tlfn'],
            $data['nationalite'], $data['nemro_cart_national'], $data['wilaya_id'],
            $data['company_id'], $data['email'], $password
        ]);
    }
    
    public function update($id, $data) {
        $sql = "UPDATE client SET nom=?, prenom=?, age=?, numero_tlfn=?, nationalite=?, nemro_cart_national=?, wilaya_id=? WHERE id=?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['nom'], $data['prenom'], $data['age'], $data['numero_tlfn'],
            $data['nationalite'], $data['nemro_cart_national'], $data['wilaya_id'], $id
        ]);
    }
    
    public function getReservations($client_id) {
        $stmt = $this->pdo->prepare("
            SELECT r.*, c.marque, c.model, c.color, p.status as payment_status 
            FROM reservation r
            JOIN car c ON r.car_id = c.id_car
            LEFT JOIN payment p ON r.id_payment = p.id_payment
            WHERE r.id_client = ?
            ORDER BY r.date_debut DESC
        ");
        $stmt->execute([$client_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

class Agent extends User {
    public function __construct() {
        parent::__construct('agent');
    }
}

class Administrator extends User {
    public function __construct() {
        parent::__construct('administrator');
    }
    
    public function createAgent($data) {
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO agent (nom, prenom, age, numero_tlfn, nationalite, nemro_cart_national, wilaya_id, salaire, company_id, email, password) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['nom'], $data['prenom'], $data['age'], $data['numero_tlfn'],
            $data['nationalite'], $data['nemro_cart_national'], $data['wilaya_id'],
            $data['salaire'], $data['company_id'], $data['email'], $password
        ]);
    }
}

class Car {
    private $pdo;
    
    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }
    
    public function getAvailable($company_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM car WHERE company_id = ? AND voiture_work = 'disponible' AND status_voiture = '1'");
        $stmt->execute([$company_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAll($company_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM car WHERE company_id = ?");
        $stmt->execute([$company_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function create($data) {
        $stmt = $this->pdo->prepare("INSERT INTO car (company_id, marque, model, color, annee, matricule, category, prix_day, status_voiture, voiture_work) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['company_id'], $data['marque'], $data['model'], $data['color'],
            $data['annee'], $data['matricule'], $data['category'], $data['prix_day'],
            $data['status_voiture'], $data['voiture_work']
        ]);
    }
    
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM car WHERE id_car = ?");
        return $stmt->execute([$id]);
    }
    
    public function updateStatus($id, $status) {
        $stmt = $this->pdo->prepare("UPDATE car SET voiture_work = ? WHERE id_car = ?");
        return $stmt->execute([$status, $id]);
    }
}

class Reservation {
    private $pdo;
    
    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }
    
    public function create($data) {
        $stmt = $this->pdo->prepare("INSERT INTO reservation (id_agent, id_client, id_company, car_id, id_wilaya, period, date_debut, date_fin, montant) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['id_agent'], $data['id_client'], $data['id_company'], $data['car_id'],
            $data['id_wilaya'], $data['period'], $data['date_debut'], $data['date_fin'], $data['montant']
        ]);
    }
    
    public function getAll($company_id) {
        $stmt = $this->pdo->prepare("
            SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, 
                   ca.marque, ca.model, p.status as payment_status
            FROM reservation r
            JOIN client c ON r.id_client = c.id
            JOIN car ca ON r.car_id = ca.id_car
            LEFT JOIN payment p ON r.id_payment = p.id_payment
            WHERE r.id_company = ?
            ORDER BY r.date_debut DESC
        ");
        $stmt->execute([$company_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<?php
// Partie 5: Page d'accueil (index.php)
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cherifi Youssouf Agency - Location de Voitures</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <div class="container max-w-6xl">
        <div class="text-center mb-12">
            <h1 class="text-5xl font-bold text-white mb-4">🚗 Cherifi Youssouf Agency</h1>
            <p class="text-xl text-purple-100">Location de voitures en Algérie</p>
        </div>
        
        <div class="grid md:grid-cols-2 gap-8">
            <!-- Espace Employé -->
            <div class="bg-white rounded-2xl shadow-2xl p-8 card-hover">
                <div class="text-center mb-6">
                    <div class="bg-purple-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-4xl">👔</span>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-800">Espace Employé</h2>
                </div>
                
                <div class="space-y-4">
                    <a href="login.php?role=administrator" class="block w-full bg-gradient-to-r from-purple-600 to-purple-700 text-white py-4 rounded-lg text-center font-semibold hover:from-purple-700 hover:to-purple-800 transition">
                        👨‍💼 Administrateur
                    </a>
                    <a href="login.php?role=agent" class="block w-full bg-gradient-to-r from-indigo-600 to-indigo-700 text-white py-4 rounded-lg text-center font-semibold hover:from-indigo-700 hover:to-indigo-800 transition">
                        🧑‍💻 Agent
                    </a>
                </div>
            </div>
            
            <!-- Espace Client -->
            <div class="bg-white rounded-2xl shadow-2xl p-8 card-hover">
                <div class="text-center mb-6">
                    <div class="bg-green-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-4xl">👤</span>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-800">Espace Client</h2>
                </div>
                
                <div class="space-y-4">
                    <a href="login.php?role=client" class="block w-full bg-gradient-to-r from-green-600 to-green-700 text-white py-4 rounded-lg text-center font-semibold hover:from-green-700 hover:to-green-800 transition">
                        🔑 Se connecter
                    </a>
                    <p class="text-center text-gray-600 text-sm">
                        Nouveau client? Contactez notre agence pour créer un compte
                    </p>
                </div>
            </div>
        </div>
        
        <div class="mt-12 text-center text-white">
            <p class="text-lg">📞 Contact: 0555-123-456 | 📍 Oran, Algérie</p>
        </div>
    </div>
</body>
</html>
<?php
// Partie 6: Page de connexion (login.php)
session_start();
require_once 'part4_classes.php';

$role = $_GET['role'] ?? 'client';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    $userClass = null;
    switch($role) {
        case 'administrator':
            $userClass = new Administrator();
            break;
        case 'agent':
            $userClass = new Agent();
            break;
        case 'client':
            $userClass = new Client();
            break;
    }
    
    if($userClass) {
        $user = $userClass->login($email, $password);
        if($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $role;
            $_SESSION['company_id'] = $user['company_id'];
            $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];
            
            if($remember) {
                setcookie('user_email', $email, time() + (86400 * 30), '/');
                setcookie('user_role', $role, time() + (86400 * 30), '/');
            }
            
            header("Location: dashboard_{$role}.php");
            exit;
        } else {
            $error = "Email ou mot de passe incorrect";
        }
    }
}

$roleNames = [
    'administrator' => 'Administrateur',
    'agent' => 'Agent',
    'client' => 'Client'
];
$roleIcons = [
    'administrator' => '👨‍💼',
    'agent' => '🧑‍💻',
    'client' => '👤'
];
$savedEmail = $_COOKIE['user_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - <?= $roleNames[$role] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full">
        <div class="text-center mb-8">
            <div class="text-6xl mb-4"><?= $roleIcons[$role] ?></div>
            <h2 class="text-3xl font-bold text-gray-800">Connexion</h2>
            <p class="text-gray-600"><?= $roleNames[$role] ?></p>
        </div>
        
        <?php if($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= $error ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($savedEmail) ?>" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2">Mot de passe</label>
                <input type="password" name="password" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
            </div>
            
            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="remember" class="mr-2">
                    <span class="text-gray-700">Se souvenir de moi</span>
                </label>
            </div>
            
            <button type="submit" class="w-full bg-gradient-to-r from-purple-600 to-purple-700 text-white py-3 rounded-lg font-semibold hover:from-purple-700 hover:to-purple-800 transition">
                Se connecter
            </button>
        </form>
        
        <div class="mt-6 text-center">
            <a href="index.php" class="text-purple-600 hover:underline">← Retour à l'accueil</a>
        </div>
        
        <div class="mt-8 p-4 bg-gray-50 rounded-lg">
            <p class="text-sm text-gray-600 text-center font-semibold mb-2">Comptes de démonstration:</p>
            <p class="text-xs text-gray-500">Admin: admin@cherifi.dz / admin123</p>
            <p class="text-xs text-gray-500">Agent: benali1@cherifi.dz / agent123</p>
            <p class="text-xs text-gray-500">Client: mansouri1@gmail.com / client123</p>
        </div>
    </div>
</body>
</html>
<?php
// Partie 7: Dashboard Client (dashboard_client.php)
session_start();
require_once 'part4_classes.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    header('Location: login.php?role=client');
    exit;
}

$carObj = new Car();
$clientObj = new Client();
$pdo = Database::getInstance()->getConnection();

$cars = $carObj->getAvailable($_SESSION['company_id']);
$reservations = $clientObj->getReservations($_SESSION['user_id']);

// Récupérer les wilayas
$wilayas = $pdo->query("SELECT * FROM wilaya ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Client</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-purple-600">🚗 Cherifi Youssouf Agency</h1>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Bienvenue, <?= $_SESSION['user_name'] ?></span>
                    <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Déconnexion</a>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container mx-auto px-4 py-8">
        <!-- Mes Réservations -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-6">📋 Mes Réservations</h2>
            <?php if(empty($reservations)): ?>
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded">
                    Vous n'avez aucune réservation pour le moment.
                </div>
            <?php else: ?>
                <div class="grid gap-4">
                    <?php foreach($reservations as $res): ?>
                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800"><?= $res['marque'] ?> <?= $res['model'] ?></h3>
                                    <p class="text-gray-600">Couleur: <?= $res['color'] ?></p>
                                    <p class="text-gray-600">Période: <?= $res['date_debut'] ?> → <?= $res['date_fin'] ?> (<?= $res['period'] ?> jours)</p>
                                    <p class="text-2xl font-bold text-purple-600 mt-2"><?= number_format($res['montant'], 2) ?> DA</p>
                                </div>
                                <div>
                                    <?php if($res['payment_status'] === 'paid'): ?>
                                        <span class="bg-green-100 text-green-800 px-4 py-2 rounded-full font-semibold">✓ Payé</span>
                                    <?php else: ?>
                                        <span class="bg-orange-100 text-orange-800 px-4 py-2 rounded-full font-semibold">⏳ En attente</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Voitures Disponibles -->
        <div>
            <h2 class="text-3xl font-bold text-gray-800 mb-6">🚙 Voitures Disponibles</h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach($cars as $car): ?>
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition">
                        <div class="bg-gradient-to-r from-purple-500 to-indigo-600 p-6">
                            <h3 class="text-2xl font-bold text-white"><?= $car['marque'] ?></h3>
                            <p class="text-purple-100"><?= $car['model'] ?></p>
                        </div>
                        <div class="p-6">
                            <div class="space-y-2 mb-4">
                                <p class="text-gray-700"><strong>Couleur:</strong> <?= $car['color'] ?></p>
                                <p class="text-gray-700"><strong>Année:</strong> <?= $car['annee'] ?></p>
                                <p class="text-gray-700"><strong>Matricule:</strong> <?= $car['matricule'] ?></p>
                                <p class="text-gray-700"><strong>Catégorie:</strong> 
                                    <?php
                                    $catNames = [1 => 'Économique', 2 => 'Confort', 3 => 'Luxe'];
                                    echo $catNames[$car['category']];
                                    ?>
                                </p>
                                <p class="text-3xl font-bold text-purple-600"><?= number_format($car['prix_day'], 2) ?> DA/jour</p>
                            </div>
                            <div class="flex space-x-2">
                                <button onclick="showReservationModal(<?= $car['id_car'] ?>, '<?= $car['marque'] ?> <?= $car['model'] ?>', <?= $car['prix_day'] ?>)" 
                                        class="flex-1 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 font-semibold">
                                    📅 Réserver
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal Réservation -->
    <div id="reservationModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <h3 class="text-2xl font-bold mb-4" id="modalTitle">Réserver une voiture</h3>
            <form id="reservationForm" method="POST" action="process_reservation.php">
                <input type="hidden" name="car_id" id="carId">
                <input type="hidden" name="prix_day" id="prixDay">
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Wilaya de départ</label>
                    <select name="wilaya_id" required class="w-full px-4 py-2 border rounded">
                        <?php foreach($wilayas as $w): ?>
                            <option value="<?= $w['id'] ?>"><?= $w['nom'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Date de début</label>
                    <input type="date" name="date_debut" required min="<?= date('Y-m-d') ?>" class="w-full px-4 py-2 border rounded">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Nombre de jours</label>
                    <input type="number" name="period" id="period" required min="1" onchange="calculateTotal()" class="w-full px-4 py-2 border rounded">
                </div>
                
                <div class="mb-4">
                    <p class="text-2xl font-bold text-purple-600">Total: <span id="totalAmount">0</span> DA</p>
                </div>
                
                <div class="flex space-x-2">
                    <button type="button" onclick="closeModal()" class="flex-1 bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Annuler</button>
                    <button type="submit" class="flex-1 bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Confirmer</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showReservationModal(carId, carName, prix) {
            document.getElementById('carId').value = carId;
            document.getElementById('prixDay').value = prix;
            document.getElementById('modalTitle').textContent = 'Réserver: ' + carName;
            document.getElementById('reservationModal').classList.remove('hidden');
        }
        
        function closeModal() {
            document.getElementById('reservationModal').classList.add('hidden');
        }
        
        function calculateTotal() {
            const period = parseInt(document.getElementById('period').value) || 0;
            const prixDay = parseFloat(document.getElementById('prixDay').value) || 0;
            const total = period * prixDay;
            document.getElementById('totalAmount').textContent = total.toLocaleString('fr-DZ', {minimumFractionDigits: 2});
        }
    </script>
</body>
</html>
<?php
// Partie 8: Dashboard Agent (dashboard_agent.php)
session_start();
require_once 'part4_classes.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'agent') {
    header('Location: login.php?role=agent');
    exit;
}

$clientObj = new Client();
$carObj = new Car();
$reservationObj = new Reservation();
$pdo = Database::getInstance()->getConnection();

$clients = $clientObj->getAll($_SESSION['company_id']);
$cars = $carObj->getAll($_SESSION['company_id']);
$reservations = $reservationObj->getAll($_SESSION['company_id']);
$wilayas = $pdo->query("SELECT * FROM wilaya ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

// Gérer les actions
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'add_client':
                if($_POST['age'] >= 24) {
                    $clientObj->create([
                        'nom' => $_POST['nom'],
                        'prenom' => $_POST['prenom'],
                        'age' => $_POST['age'],
                        'numero_tlfn' => $_POST['numero_tlfn'],
                        'nationalite' => $_POST['nationalite'],
                        'nemro_cart_national' => $_POST['nemro_cart_national'],
                        'wilaya_id' => $_POST['wilaya_id'],
                        'company_id' => $_SESSION['company_id'],
                        'email' => $_POST['email'],
                        'password' => $_POST['password']
                    ]);
                    header('Location: dashboard_agent.php?success=client_added');
                }
                break;
            
            case 'delete_client':
                $clientObj->delete($_POST['client_id']);
                header('Location: dashboard_agent.php?success=client_deleted');
                break;
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Agent</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-purple-600">🚗 Cherifi Youssouf Agency - Agent</h1>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Agent: <?= $_SESSION['user_name'] ?></span>
                    <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Déconnexion</a>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container mx-auto px-4 py-8">
        <!-- Onglets -->
        <div class="mb-6 border-b border-gray-200">
            <div class="flex space-x-4">
                <button onclick="showTab('clients')" id="tab-clients" class="px-4 py-2 font-semibold border-b-2 border-purple-600 text-purple-600">
                    👥 Clients
                </button>
                <button onclick="showTab('cars')" id="tab-cars" class="px-4 py-2 font-semibold text-gray-600 hover:text-purple-600">
                    🚗 Voitures
                </button>
                <button onclick="showTab('reservations')" id="tab-reservations" class="px-4 py-2 font-semibold text-gray-600 hover:text-purple-600">
                    📋 Réservations
                </button>
            </div>
        </div>
        
        <!-- Section Clients -->
        <div id="section-clients" class="tab-content">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-3xl font-bold text-gray-800">👥 Gestion des Clients</h2>
                <button onclick="showAddClientModal()" class="bg-green-500 text-white px-6 py-2 rounded-lg hover:bg-green-600 font-semibold">
                    + Ajouter un client
                </button>
            </div>
            
            <div class="bg-white rounded-lg shadow overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Nom</th>
                            <th class="px-4 py-3 text-left">Prénom</th>
                            <th class="px-4 py-3 text-left">Âge</th>
                            <th class="px-4 py-3 text-left">Téléphone</th>
                            <th class="px-4 py-3 text-left">Email</th>
                            <th class="px-4 py-3 text-left">Statut</th>
                            <th class="px-4 py-3 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($clients as $client): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3"><?= $client['id'] ?></td>
                                <td class="px-4 py-3"><?= $client['nom'] ?></td>
                                <td class="px-4 py-3"><?= $client['prenom'] ?></td>
                                <td class="px-4 py-3"><?= $client['age'] ?> ans</td>
                                <td class="px-4 py-3"><?= $client['numero_tlfn'] ?></td>
                                <td class="px-4 py-3"><?= $client['email'] ?></td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded text-sm <?= $client['status'] === 'payé' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                        <?= $client['status'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce client?')">
                                        <input type="hidden" name="action" value="delete_client">
                                        <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-800">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Section Voitures -->
        <div id="section-cars" class="tab-content hidden">
            <h2 class="text-3xl font-bold text-gray-800 mb-6">🚗 Liste des Voitures</h2>
            <div class="bg-white rounded-lg shadow overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left">Marque</th>
                            <th class="px-4 py-3 text-left">Modèle</th>
                            <th class="px-4 py-3 text-left">Couleur</th>
                            <th class="px-4 py-3 text-left">Année</th>
                            <th class="px-4 py-3 text-left">Matricule</th>
                            <th class="px-4 py-3 text-left">Prix/jour</th>
                            <th class="px-4 py-3 text-left">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($cars as $car): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3"><?= $car['marque'] ?></td>
                                <td class="px-4 py-3"><?= $car['model'] ?></td>
                                <td class="px-4 py-3"><?= $car['color'] ?></td>
                                <td class="px-4 py-3"><?= $car['annee'] ?></td>
                                <td class="px-4 py-3"><?= $car['matricule'] ?></td>
                                <td class="px-4 py-3"><?= number_format($car['prix_day'], 2) ?> DA</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded text-sm <?= $car['voiture_work'] === 'disponible' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $car['voiture_work'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Section Réservations -->
        <div id="section-reservations" class="tab-content hidden">
            <h2 class="text-3xl font-bold text-gray-800 mb-6">📋 Réservations</h2>
            <div class="bg-white rounded-lg shadow overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Client</th>
                            <th class="px-4 py-3 text-left">Voiture</th>
                            <th class="px-4 py-3 text-left">Période</th>
                            <th class="px-4 py-3 text-left">Montant</th>
                            <th class="px-4 py-3 text-left">Paiement</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($reservations as $res): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3"><?= $res['id_reservation'] ?></td>
                                <td class="px-4 py-3"><?= $res['client_nom'] ?> <?= $res['client_prenom'] ?></td>
                                <td class="px-4 py-3"><?= $res['marque'] ?> <?= $res['model'] ?></td>
                                <td class="px-4 py-3"><?= $res['date_debut'] ?> → <?= $res['date_fin'] ?></td>
                                <td class="px-4 py-3 font-bold"><?= number_format($res['montant'], 2) ?> DA</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded text-sm <?= $res['payment_status'] === 'paid' ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800' ?>">
                                        <?= $res['payment_status'] === 'paid' ? 'Payé' : 'En attente' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal Ajouter Client -->
    <div id="addClientModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-lg max-w-2xl w-full p-6 max-h-screen overflow-y-auto">
            <h3 class="text-2xl font-bold mb-4">Ajouter un nouveau client</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_client">
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Nom *</label>
                        <input type="text" name="nom" required class="w-full px-4 py-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Prénom *</label>
                        <input type="text" name="prenom" required class="w-full px-4 py-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Âge * (min 24)</label>
                        <input type="number" name="age" required min="24" class="w-full px-4 py-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Téléphone *</label>
                        <input type="text" name="numero_tlfn" required class="w-full px-4 py-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">N° Carte Nationale *</label>
                        <input type="text" name="nemro_cart_national" required class="w-full px-4 py-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Nationalité *</label>
                        <input type="text" name="nationalite" value="Algérienne" required class="w-full px-4 py-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Wilaya *</label>
                        <select name="wilaya_id" required class="w-full px-4 py-2 border rounded">
                            <?php foreach($wilayas as $w): ?>
                                <option value="<?= $w['id'] ?>"><?= $w['nom'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Email *</label>
                        <input type="email" name="email" required class="w-full px-4 py-2 border rounded">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-semibold mb-2">Mot de passe *</label>
                        <input type="password" name="password" required class="w-full px-4 py-2 border rounded">
                    </div>
                </div>
                <div class="flex space-x-2 mt-6">
                    <button type="button" onclick="closeAddClientModal()" class="flex-1 bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Annuler</button>
                    <button type="submit" class="flex-1 bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showTab(tab) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('[id^="tab-"]').forEach(el => {
                el.classList.remove('border-b-2', 'border-purple-600', 'text-purple-600');
                el.classList.add('text-gray-600');
            });
            
            document.getElementById('section-' + tab).classList.remove('hidden');
            document.getElementById('tab-' + tab).classList.add('border-b-2', 'border-purple-600', 'text-purple-600');
            document.getElementById('tab-' + tab).classList.remove('text-gray-600');
        }
        
        function showAddClientModal() {
            document.getElementById('addClientModal').classList.remove('hidden');
        }
        
        function closeAddClientModal() {
            document.getElementById('addClientModal').classList.add('hidden');
        }
    </script>
</body>
</html>

<?php
// Partie 9: Dashboard Administrateur (dashboard_administrator.php)
session_start();
require_once 'part4_classes.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrator') {
    header('Location: login.php?role=administrator');
    exit;
}

$pdo = Database::getInstance()->getConnection();
$clientObj = new Client();
$carObj = new Car();
$adminObj = new Administrator();

// Récupérer les statistiques
$company_id = $_SESSION['company_id'];

// Revenus du jour
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT SUM(r.montant) as total FROM reservation r 
                       JOIN payment p ON r.id_payment = p.id_payment 
                       WHERE r.id_company = ? AND DATE(p.date_payment) = ? AND p.status = 'paid'");
$stmt->execute([$company_id, $today]);
$revenuJour = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Revenus du mois
$month = date('Y-m');
$stmt = $pdo->prepare("SELECT SUM(r.montant) as total FROM reservation r 
                       JOIN payment p ON r.id_payment = p.id_payment 
                       WHERE r.id_company = ? AND DATE_FORMAT(p.date_payment, '%Y-%m') = ? AND p.status = 'paid'");
$stmt->execute([$company_id, $month]);
$revenuMois = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Revenus de l'année
$year = date('Y');
$stmt = $pdo->prepare("SELECT SUM(r.montant) as total FROM reservation r 
                       JOIN payment p ON r.id_payment = p.id_payment 
                       WHERE r.id_company = ? AND YEAR(p.date_payment) = ? AND p.status = 'paid'");
$stmt->execute([$company_id, $year]);
$revenuAnnee = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Statistiques générales
$totalClients = $pdo->prepare("SELECT COUNT(*) as total FROM client WHERE company_id = ?");
$totalClients->execute([$company_id]);
$nbClients = $totalClients->fetch(PDO::FETCH_ASSOC)['total'];

$totalAgents = $pdo->prepare("SELECT COUNT(*) as total FROM agent WHERE company_id = ?");
$totalAgents->execute([$company_id]);
$nbAgents = $totalAgents->fetch(PDO::FETCH_ASSOC)['total'];

$totalVoitures = $pdo->prepare("SELECT COUNT(*) as total FROM car WHERE company_id = ?");
$totalVoitures->execute([$company_id]);
$nbVoitures = $totalVoitures->fetch(PDO::FETCH_ASSOC)['total'];

$voituresDisponibles = $pdo->prepare("SELECT COUNT(*) as total FROM car WHERE company_id = ? AND voiture_work = 'disponible'");
$voituresDisponibles->execute([$company_id]);
$nbDisponibles = $voituresDisponibles->fetch(PDO::FETCH_ASSOC)['total'];

// Récupérer les données pour les graphiques (revenus mensuels)
$stmt = $pdo->prepare("SELECT MONTH(p.date_payment) as mois, SUM(r.montant) as total 
                       FROM reservation r 
                       JOIN payment p ON r.id_payment = p.id_payment 
                       WHERE r.id_company = ? AND YEAR(p.date_payment) = ? AND p.status = 'paid'
                       GROUP BY MONTH(p.date_payment)
                       ORDER BY mois");
$stmt->execute([$company_id, $year]);
$revenusMensuels = $stmt->fetchAll(PDO::FETCH_ASSOC);

$clients = $clientObj->getAll($company_id);
$cars = $carObj->getAll($company_id);
$agents = $pdo->prepare("SELECT * FROM agent WHERE company_id = ?");
$agents->execute([$company_id]);
$agentsList = $agents->fetchAll(PDO::FETCH_ASSOC);
$wilayas = $pdo->query("SELECT * FROM wilaya ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

// Gestion des actions
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    switch($_POST['action']) {
        case 'delete_car':
            $carObj->delete($_POST['car_id']);
            header('Location: dashboard_administrator.php?success=car_deleted');
            exit;
        case 'delete_agent':
            $stmt = $pdo->prepare("DELETE FROM agent WHERE id = ?");
            $stmt->execute([$_POST['agent_id']]);
            header('Location: dashboard_administrator.php?success=agent_deleted');
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrateur</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-purple-600">🚗 Cherifi Youssouf Agency - Administrateur</h1>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Admin: <?= $_SESSION['user_name'] ?></span>
                    <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Déconnexion</a>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container mx-auto px-4 py-8">
        <!-- Statistiques -->
        <div class="grid md:grid-cols-4 gap-6 mb-8">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100">Clients</p>
                        <p class="text-4xl font-bold"><?= $nbClients ?></p>
                    </div>
                    <div class="text-5xl">👥</div>
                </div>
            </div>
            
            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100">Agents</p>
                        <p class="text-4xl font-bold"><?= $nbAgents ?></p>
                    </div>
                    <div class="text-5xl">🧑‍💻</div>
                </div>
            </div>
            
            <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-100">Voitures</p>
                        <p class="text-4xl font-bold"><?= $nbVoitures ?></p>
                        <p class="text-sm text-purple-100"><?= $nbDisponibles ?> disponibles</p>
                    </div>
                    <div class="text-5xl">🚗</div>
                </div>
            </div>
            
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-orange-100">Revenu Jour</p>
                        <p class="text-3xl font-bold"><?= number_format($revenuJour, 0) ?> DA</p>
                    </div>
                    <div class="text-5xl">💰</div>
                </div>
            </div>
        </div>
        
        <!-- Chiffre d'affaire -->
        <div class="grid md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-2">📊 CA Aujourd'hui</h3>
                <p class="text-3xl font-bold text-green-600"><?= number_format($revenuJour, 2) ?> DA</p>
                <p class="text-gray-500 text-sm"><?= date('d/m/Y') ?></p>
            </div>
            
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-2">📊 CA Ce Mois</h3>
                <p class="text-3xl font-bold text-blue-600"><?= number_format($revenuMois, 2) ?> DA</p>
                <p class="text-gray-500 text-sm"><?= date('F Y') ?></p>
            </div>
            
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-2">📊 CA Cette Année</h3>
                <p class="text-3xl font-bold text-purple-600"><?= number_format($revenuAnnee, 2) ?> DA</p>
                <p class="text-gray-500 text-sm"><?= date('Y') ?></p>
            </div>
        </div>
        
        <!-- Graphique -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-4">📈 Revenus Mensuels <?= $year ?></h3>
            <canvas id="revenusChart" height="80"></canvas>
        </div>
        
        <!-- Onglets -->
        <div class="mb-6 border-b border-gray-200">
            <div class="flex space-x-4">
                <button onclick="showTab('clients')" id="tab-clients" class="px-4 py-2 font-semibold border-b-2 border-purple-600 text-purple-600">
                    👥 Clients
                </button>
                <button onclick="showTab('agents')" id="tab-agents" class="px-4 py-2 font-semibold text-gray-600 hover:text-purple-600">
                    🧑‍💻 Agents
                </button>
                <button onclick="showTab('cars')" id="tab-cars" class="px-4 py-2 font-semibold text-gray-600 hover:text-purple-600">
                    🚗 Voitures
                </button>
            </div>
        </div>
        
        <!-- Section Clients -->
        <div id="section-clients" class="tab-content">
            <h2 class="text-3xl font-bold text-gray-800 mb-6">👥 Gestion des Clients</h2>
            <div class="bg-white rounded-lg shadow overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Nom</th>
                            <th class="px-4 py-3 text-left">Prénom</th>
                            <th class="px-4 py-3 text-left">Âge</th>
                            <th class="px-4 py-3 text-left">Téléphone</th>
                            <th class="px-4 py-3 text-left">Email</th>
                            <th class="px-4 py-3 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($clients as $client): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3"><?= $client['id'] ?></td>
                                <td class="px-4 py-3"><?= $client['nom'] ?></td>
                                <td class="px-4 py-3"><?= $client['prenom'] ?></td>
                                <td class="px-4 py-3"><?= $client['age'] ?> ans</td>
                                <td class="px-4 py-3"><?= $client['numero_tlfn'] ?></td>
                                <td class="px-4 py-3"><?= $client['email'] ?></td>
                                <td class="px-4 py-3">
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer?')">
                                        <input type="hidden" name="action" value="delete_client">
                                        <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-800">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Section Agents -->
        <div id="section-agents" class="tab-content hidden">
            <h2 class="text-3xl font-bold text-gray-800 mb-6">🧑‍💻 Gestion des Agents</h2>
            <div class="bg-white rounded-lg shadow overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Nom</th>
                            <th class="px-4 py-3 text-left">Prénom</th>
                            <th class="px-4 py-3 text-left">Téléphone</th>
                            <th class="px-4 py-3 text-left">Salaire</th>
                            <th class="px-4 py-3 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($agentsList as $agent): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3"><?= $agent['id'] ?></td>
                                <td class="px-4 py-3"><?= $agent['nom'] ?></td>
                                <td class="px-4 py-3"><?= $agent['prenom'] ?></td>
                                <td class="px-4 py-3"><?= $agent['numero_tlfn'] ?></td>
                                <td class="px-4 py-3 font-bold"><?= number_format($agent['salaire'], 2) ?> DA</td>
                                <td class="px-4 py-3">
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer?')">
                                        <input type="hidden" name="action" value="delete_agent">
                                        <input type="hidden" name="agent_id" value="<?= $agent['id'] ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-800">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Section Voitures -->
        <div id="section-cars" class="tab-content hidden">
            <h2 class="text-3xl font-bold text-gray-800 mb-6">🚗 Gestion des Voitures</h2>
            <div class="bg-white rounded-lg shadow overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Marque</th>
                            <th class="px-4 py-3 text-left">Modèle</th>
                            <th class="px-4 py-3 text-left">Matricule</th>
                            <th class="px-4 py-3 text-left">Prix/jour</th>
                            <th class="px-4 py-3 text-left">Statut</th>
                            <th class="px-4 py-3 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($cars as $car): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3"><?= $car['id_car'] ?></td>
                                <td class="px-4 py-3"><?= $car['marque'] ?></td>
                                <td class="px-4 py-3"><?= $car['model'] ?></td>
                                <td class="px-4 py-3"><?= $car['matricule'] ?></td>
                                <td class="px-4 py-3 font-bold"><?= number_format($car['prix_day'], 2) ?> DA</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded text-sm <?= $car['voiture_work'] === 'disponible' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $car['voiture_work'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer?')">
                                        <input type="hidden" name="action" value="delete_car">
                                        <input type="hidden" name="car_id" value="<?= $car['id_car'] ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-800">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tab) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('[id^="tab-"]').forEach(el => {
                el.classList.remove('border-b-2', 'border-purple-600', 'text-purple-600');
                el.classList.add('text-gray-600');
            });
            
            document.getElementById('section-' + tab).classList.remove('hidden');
            document.getElementById('tab-' + tab).classList.add('border-b-2', 'border-purple-600', 'text-purple-600');
            document.getElementById('tab-' + tab).classList.remove('text-gray-600');
        }
        
        // Graphique des revenus
        const ctx = document.getElementById('revenusChart').getContext('2d');
        const moisNoms = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
        const revenus = new Array(12).fill(0);
        
        <?php foreach($revenusMensuels as $rev): ?>
            revenus[<?= $rev['mois'] - 1 ?>] = <?= $rev['total'] ?>;
        <?php endforeach; ?>
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: moisNoms,
                datasets: [{
                    label: 'Revenus (DA)',
                    data: revenus,
                    backgroundColor: 'rgba(124, 58, 237, 0.5)',
                    borderColor: 'rgba(124, 58, 237, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString() + ' DA';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
<?php
// Partie 10: process_reservation.php, process_payment.php, logout.php

/* ===== process_reservation.php ===== */
// Fichier: process_reservation.php
session_start();
require_once 'part4_classes.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    header('Location: login.php?role=client');
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $car_id = $_POST['car_id'];
    $wilaya_id = $_POST['wilaya_id'];
    $date_debut = $_POST['date_debut'];
    $period = $_POST['period'];
    $prix_day = $_POST['prix_day'];
    
    // Calculer la date de fin et le montant
    $date_fin = date('Y-m-d', strtotime($date_debut . " + $period days"));
    $montant = $period * $prix_day;
    
    // Créer un paiement en attente
    $pdo = Database::getInstance()->getConnection();
    $stmt = $pdo->prepare("INSERT INTO payment (status, montant, date_payment) VALUES ('not_paid', ?, NOW())");
    $stmt->execute([$montant]);
    $payment_id = $pdo->lastInsertId();
    
    // Créer la réservation
    $stmt = $pdo->prepare("INSERT INTO reservation (id_client, id_company, car_id, id_wilaya, period, date_debut, date_fin, montant, id_payment) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'],
        $_SESSION['company_id'],
        $car_id,
        $wilaya_id,
        $period,
        $date_debut,
        $date_fin,
        $montant,
        $payment_id
    ]);
    
    $reservation_id = $pdo->lastInsertId();
    
    // Mettre à jour le statut de la voiture
    $stmt = $pdo->prepare("UPDATE car SET voiture_work = 'non disponible' WHERE id_car = ?");
    $stmt->execute([$car_id]);
    
    // Mettre à jour le statut du client
    $stmt = $pdo->prepare("UPDATE client SET status = 'réservé' WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    
    // Rediriger vers la page de paiement
    header("Location: payment.php?reservation_id=$reservation_id&amount=$montant");
    exit;
}

/* ===== payment.php ===== */
// Fichier: payment.php
session_start();
require_once 'part4_classes.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    header('Location: login.php?role=client');
    exit;
}

$reservation_id = $_GET['reservation_id'] ?? 0;
$amount = $_GET['amount'] ?? 0;

$pdo = Database::getInstance()->getConnection();
$stmt = $pdo->prepare("SELECT r.*, c.marque, c.model, c.color FROM reservation r 
                       JOIN car c ON r.car_id = c.id_car 
                       WHERE r.id_reservation = ? AND r.id_client = ?");
$stmt->execute([$reservation_id, $_SESSION['user_id']]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$reservation) {
    header('Location: dashboard_client.php');
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $card_number = $_POST['card_number'];
    $card_code = $_POST['card_code'];
    
    // Valider la carte (16 chiffres et 3 chiffres pour le code)
    if(strlen($card_number) === 16 && strlen($card_code) === 3 && is_numeric($card_number) && is_numeric($card_code)) {
        // Mettre à jour le paiement
        $stmt = $pdo->prepare("UPDATE payment SET status = 'paid', date_payment = NOW(), card_number = ?, card_code = ? 
                               WHERE id_payment = ?");
        $stmt->execute([$card_number, $card_code, $reservation['id_payment']]);
        
        // Mettre à jour le statut du client
        $stmt = $pdo->prepare("UPDATE client SET status = 'payé' WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        header('Location: dashboard_client.php?success=payment_completed');
        exit;
    } else {
        $error = "Informations de carte invalides. Le numéro doit contenir 16 chiffres et le code 3 chiffres.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <h1 class="text-2xl font-bold text-purple-600">🚗 Paiement de Réservation</h1>
        </div>
    </nav>
    
    <div class="container mx-auto px-4 py-8 max-w-2xl">
        <!-- Facture -->
        <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
            <h2 class="text-3xl font-bold text-center text-gray-800 mb-6">🧾 Facture de Réservation</h2>
            
            <div class="border-b pb-4 mb-4">
                <p class="text-gray-600"><strong>Cherifi Youssouf Agency</strong></p>
                <p class="text-gray-600">Oran, Algérie</p>
                <p class="text-gray-600">Tél: 0555-123-456</p>
            </div>
            
            <div class="space-y-3 mb-6">
                <div class="flex justify-between">
                    <span class="text-gray-700">Véhicule:</span>
                    <span class="font-semibold"><?= $reservation['marque'] ?> <?= $reservation['model'] ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-700">Couleur:</span>
                    <span class="font-semibold"><?= $reservation['color'] ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-700">Période:</span>
                    <span class="font-semibold"><?= $reservation['date_debut'] ?> → <?= $reservation['date_fin'] ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-700">Durée:</span>
                    <span class="font-semibold"><?= $reservation['period'] ?> jours</span>
                </div>
            </div>
            
            <div class="border-t pt-4">
                <div class="flex justify-between items-center">
                    <span class="text-2xl font-bold text-gray-800">Total à payer:</span>
                    <span class="text-3xl font-bold text-purple-600"><?= number_format($reservation['montant'], 2) ?> DA</span>
                </div>
            </div>
        </div>
        
        <!-- Formulaire de paiement -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-6">💳 Informations de Paiement</h3>
            
            <?php if(isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= $error ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Numéro de Carte (16 chiffres)</label>
                    <input type="text" name="card_number" required maxlength="16" pattern="[0-9]{16}" 
                           placeholder="1234567890123456"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Code de Sécurité (3 chiffres)</label>
                    <input type="text" name="card_code" required maxlength="3" pattern="[0-9]{3}" 
                           placeholder="123"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
                </div>
                
                <button type="submit" class="w-full bg-gradient-to-r from-green-600 to-green-700 text-white py-4 rounded-lg font-bold text-lg hover:from-green-700 hover:to-green-800 transition">
                    ✓ Confirmer le Paiement
                </button>
            </form>
            
            <div class="mt-4 text-center">
                <a href="dashboard_client.php" class="text-purple-600 hover:underline">← Retour au tableau de bord</a>
            </div>
        </div>
    </div>
</body>
</html>

<?php
/* ===== logout.php ===== */
// Fichier: logout.php
session_start();
session_destroy();
setcookie('user_email', '', time() - 3600, '/');
setcookie('user_role', '', time() - 3600, '/');
header('Location: index.php');
exit;
?>
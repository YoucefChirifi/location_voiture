<?php
session_start();
ob_start();

// ==================== DATABASE CONFIGURATION ====================
define('DB_HOST', 'localhost');
define('DB_NAME', 'car_rental_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('SITE_URL', 'http://localhost/car_rental');

class Database {
    private $pdo;
    
    public function __construct() {
        try {
            $this->pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8", DB_USER, DB_PASS);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}

// Initialize database
$db = new Database();

// ==================== WILAYAS DATA ====================
$wilayas = [
    1 => 'Adrar', 2 => 'Chlef', 3 => 'Laghouat', 4 => 'Oum El Bouaghi', 5 => 'Batna',
    6 => 'Béjaïa', 7 => 'Biskra', 8 => 'Béchar', 9 => 'Blida', 10 => 'Bouira',
    11 => 'Tamanrasset', 12 => 'Tébessa', 13 => 'Tlemcen', 14 => 'Tiaret', 15 => 'Tizi Ouzou',
    16 => 'Alger', 17 => 'Djelfa', 18 => 'Jijel', 19 => 'Sétif', 20 => 'Saïda',
    21 => 'Skikda', 22 => 'Sidi Bel Abbès', 23 => 'Annaba', 24 => 'Guelma', 25 => 'Constantine',
    26 => 'Médéa', 27 => 'Mostaganem', 28 => 'M\'Sila', 29 => 'Mascara', 30 => 'Ouargla',
    31 => 'Oran', 32 => 'El Bayadh', 33 => 'Illizi', 34 => 'Bordj Bou Arreridj', 35 => 'Boumerdès',
    36 => 'El Tarf', 37 => 'Tindouf', 38 => 'Tissemsilt', 39 => 'El Oued', 40 => 'Khenchela',
    41 => 'Souk Ahras', 42 => 'Tipaza', 43 => 'Mila', 44 => 'Aïn Defla', 45 => 'Naâma',
    46 => 'Aïn Témouchent', 47 => 'Ghardaïa', 48 => 'Relizane', 49 => 'Timimoun', 50 => 'Bordj Badji Mokhtar',
    51 => 'Ouled Djellal', 52 => 'Béni Abbès', 53 => 'In Salah', 54 => 'In Guezzam', 55 => 'Touggourt',
    56 => 'Djanet', 57 => 'El M\'Ghair', 58 => 'El Meniaa', 59 => 'Aflou', 60 => 'El Abiodh Sidi Cheikh',
    61 => 'El Aricha', 62 => 'El Kantara', 63 => 'Barika', 64 => 'Bou Saâda', 65 => 'Bir El Ater',
    66 => 'Ksar El Boukhari', 67 => 'Ksar Chellala', 68 => 'Aïn Oussara', 69 => 'Messaad'
];

// ==================== CAR BRANDS & MODELS ====================
$car_brands = [
    'Dacia' => ['Logan' => [2004, 0], 'Sandero' => [2007, 0], 'Duster' => [2010, 0]],
    'Renault' => ['Clio' => [1990, 0], 'Megane' => [1995, 0], 'Symbol' => [2013, 2020], 'Kangoo' => [1997, 0]],
    'Peugeot' => ['206' => [1998, 2013], '208' => [2012, 0], '301' => [2012, 0], '308' => [2007, 0]],
    'Toyota' => ['Yaris' => [1999, 0], 'Corolla' => [1966, 0], 'Hilux' => [1968, 0]]
];

// ==================== INITIALIZE TABLES ====================
function initializeDatabase($db) {
    try {
        // Create companies table
        $db->query("CREATE TABLE IF NOT EXISTS companies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            address TEXT,
            wilaya_id INT,
            phone VARCHAR(20),
            email VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Create users table
        $db->query("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT,
            role ENUM('admin','owner','agent','client') NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            first_name VARCHAR(50),
            last_name VARCHAR(50),
            phone VARCHAR(20),
            wilaya_id INT,
            driver_license VARCHAR(50),
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Create default admin
        $db->query("INSERT IGNORE INTO users (role, email, password_hash, first_name, last_name) 
                   VALUES ('admin', 'admin@location.dz', MD5('admin123'), 'Admin', 'System')");
        
    } catch(Exception $e) {
        // Silently continue - tables might already exist
    }
}

initializeDatabase($db);
?>
<?php
// ==================== USER AUTHENTICATION CLASS ====================
class User {
    private $db;
    public $id, $company_id, $role, $email, $first_name, $last_name, $phone, $wilaya_id;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function login($email, $password) {
        $stmt = $this->db->query("SELECT * FROM users WHERE email = ? AND is_active = TRUE", [$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && md5($password) === $user['password_hash']) {
            $this->setUserSession($user);
            return true;
        }
        return false;
    }
    
    public function register($user_data) {
        $user_data['password_hash'] = md5($user_data['password']);
        unset($user_data['password']);
        
        try {
            $sql = "INSERT INTO users (company_id, role, email, password_hash, first_name, last_name, phone, wilaya_id, driver_license) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [
                $user_data['company_id'] ?? null,
                $user_data['role'],
                $user_data['email'],
                $user_data['password_hash'],
                $user_data['first_name'],
                $user_data['last_name'],
                $user_data['phone'] ?? null,
                $user_data['wilaya_id'] ?? null,
                $user_data['driver_license'] ?? null
            ]);
            
            return $this->db->getConnection()->lastInsertId();
        } catch(PDOException $e) {
            return false;
        }
    }
    
    private function setUserSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['company_id'] = $user['company_id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        
        // Set user properties
        $this->id = $user['id'];
        $this->company_id = $user['company_id'];
        $this->role = $user['role'];
        $this->email = $user['email'];
        $this->first_name = $user['first_name'];
        $this->last_name = $user['last_name'];
        $this->phone = $user['phone'];
        $this->wilaya_id = $user['wilaya_id'];
    }
    
    public function logout() {
        session_destroy();
        header('Location: ' . SITE_URL);
        exit;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            $stmt = $this->db->query("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }
}

// ==================== COMPANY CLASS ====================
class Company {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function create($company_data) {
        $sql = "INSERT INTO companies (name, address, wilaya_id, phone, email) VALUES (?, ?, ?, ?, ?)";
        $this->db->query($sql, [
            $company_data['name'],
            $company_data['address'],
            $company_data['wilaya_id'],
            $company_data['phone'],
            $company_data['email']
        ]);
        return $this->db->getConnection()->lastInsertId();
    }
    
    public function getCompanies() {
        $stmt = $this->db->query("SELECT * FROM companies ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getCompany($company_id) {
        $stmt = $this->db->query("SELECT * FROM companies WHERE id = ?", [$company_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// ==================== FORM PROCESSING ====================
$user = new User($db);
$company = new Company($db);
$errors = [];
$success = '';

// Handle login
if (isset($_POST['login'])) {
    if ($user->login($_POST['email'], $_POST['password'])) {
        header('Location: ' . SITE_URL);
        exit;
    } else {
        $errors[] = "Email ou mot de passe incorrect";
    }
}

// Handle registration
if (isset($_POST['register'])) {
    $user_data = [
        'role' => $_POST['role'],
        'email' => $_POST['email'],
        'password' => $_POST['password'],
        'first_name' => $_POST['first_name'],
        'last_name' => $_POST['last_name'],
        'phone' => $_POST['phone'],
        'wilaya_id' => $_POST['wilaya_id'],
        'driver_license' => $_POST['driver_license'] ?? null
    ];
    
    if ($user->register($user_data)) {
        $success = "Compte créé avec succès! Vous pouvez maintenant vous connecter.";
    } else {
        $errors[] = "Erreur lors de la création du compte. L'email existe peut-être déjà.";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    $user->logout();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Location de Voitures - Algérie</title>
    <style>
        /* ==================== CSS RESET & VARIABLES ==================== */
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #64748b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --light: #f8fafc;
            --dark: #1e293b;
            --border: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --radius: 8px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--dark);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        /* ==================== LAYOUT COMPONENTS ==================== */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* ==================== NAVIGATION ==================== */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary);
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            color: var(--dark);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--secondary);
        }

        /* ==================== AUTHENTICATION FORMS ==================== */
        .auth-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 80vh;
            padding: 2rem 0;
        }

        .auth-box {
            width: 100%;
            max-width: 400px;
        }

        .auth-tabs {
            display: flex;
            margin-bottom: 1rem;
            background: var(--light);
            border-radius: var(--radius);
            padding: 4px;
        }

        .auth-tab {
            flex: 1;
            padding: 0.75rem;
            text-align: center;
            background: transparent;
            border: none;
            border-radius: calc(var(--radius) - 2px);
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }

        .auth-tab.active {
            background: white;
            box-shadow: var(--shadow);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border);
            border-radius: var(--radius);
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 2rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-block {
            width: 100%;
        }

        /* ==================== MESSAGES ==================== */
        .alert {
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1rem;
        }

        .alert-error {
            background: #fee2e2;
            color: var(--danger);
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #d1fae5;
            color: var(--success);
            border: 1px solid #a7f3d0;
        }

        /* ==================== ROLE SELECTION ==================== */
        .role-selection {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .role-option {
            padding: 1rem;
            border: 2px solid var(--border);
            border-radius: var(--radius);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .role-option:hover {
            border-color: var(--primary);
        }

        .role-option.selected {
            border-color: var(--primary);
            background: var(--light);
        }

        .role-option input {
            display: none;
        }

        /* ==================== RESPONSIVE DESIGN ==================== */
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                gap: 1rem;
            }

            .role-selection {
                grid-template-columns: 1fr;
            }

            .card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Header -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="<?php echo SITE_URL; ?>" class="logo">🚗 LocationVoiture.dz</a>
            
            <div class="nav-links">
                <?php if ($user->isLoggedIn()): ?>
                    <span class="user-info">
                        Bienvenue, <?php echo $_SESSION['user_name']; ?> 
                        (<?php echo $_SESSION['user_role']; ?>)
                    </span>
                    <a href="?logout=true" class="btn">Déconnexion</a>
                <?php else: ?>
                    <a href="#login">Connexion</a>
                    <a href="#register" class="btn">Inscription</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container">
        <?php if (!$user->isLoggedIn()): ?>
            <!-- Authentication Section -->
            <section class="auth-container">
                <div class="auth-box glass-card">
                    <div class="auth-tabs">
                        <button class="auth-tab active" onclick="showAuthTab('login')">Connexion</button>
                        <button class="auth-tab" onclick="showAuthTab('register')">Inscription</button>
                    </div>

                    <!-- Error/Success Messages -->
                    <?php if (!empty($errors)): ?>
                        <?php foreach ($errors as $error): ?>
                            <div class="alert alert-error"><?php echo $error; ?></div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <!-- Login Form -->
                    <div id="login-form" class="auth-form">
                        <form method="POST">
                            <input type="hidden" name="login" value="1">
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Mot de passe</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-block">Se connecter</button>
                        </form>
                    </div>

                    <!-- Registration Form -->
                    <div id="register-form" class="auth-form" style="display: none;">
                        <form method="POST">
                            <input type="hidden" name="register" value="1">
                            
                            <!-- Role Selection -->
                            <div class="form-group">
                                <label class="form-label">Je suis</label>
                                <div class="role-selection">
                                    <label class="role-option" onclick="selectRole('client')">
                                        <input type="radio" name="role" value="client" required> 
                                        👤 Client
                                    </label>
                                    <label class="role-option" onclick="selectRole('agent')">
                                        <input type="radio" name="role" value="agent" required>
                                        🏢 Employé
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Prénom</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Nom</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Téléphone</label>
                                <input type="tel" name="phone" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Wilaya</label>
                                <select name="wilaya_id" class="form-control" required>
                                    <option value="">Choisir une wilaya</option>
                                    <?php foreach ($wilayas as $id => $name): ?>
                                        <option value="<?php echo $id; ?>"><?php echo $id . ' - ' . $name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group" id="license-field">
                                <label class="form-label">Permis de conduire</label>
                                <input type="text" name="driver_license" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Mot de passe</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            
                            <button type="submit" class="btn btn-block">Créer mon compte</button>
                        </form>
                    </div>
                </div>
            </section>

        <?php else: ?>
            <!-- Dashboard Section (Will be implemented in next parts) -->
            <section class="dashboard">
                <div class="card">
                    <h1>Bienvenue sur votre tableau de bord</h1>
                    <p>Rôle: <?php echo $_SESSION['user_role']; ?></p>
                    <p>Cette section sera développée dans les parties suivantes.</p>
                </div>
            </section>
        <?php endif; ?>
    </main>
        <!-- JavaScript Functionality -->
    <script>
        // ==================== AUTHENTICATION TABS ====================
        function showAuthTab(tabName) {
            // Hide all forms
            document.getElementById('login-form').style.display = 'none';
            document.getElementById('register-form').style.display = 'none';
            
            // Remove active class from all tabs
            document.querySelectorAll('.auth-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected form and activate tab
            document.getElementById(tabName + '-form').style.display = 'block';
            event.target.classList.add('active');
        }

        // ==================== ROLE SELECTION ====================
        function selectRole(role) {
            // Update visual selection
            document.querySelectorAll('.role-option').forEach(option => {
                option.classList.remove('selected');
            });
            event.target.classList.add('selected');
            
            // Update hidden radio button
            const radio = event.target.querySelector('input[type="radio"]');
            radio.checked = true;
            
            // Show/hide driver license field
            const licenseField = document.getElementById('license-field');
            if (role === 'client') {
                licenseField.style.display = 'block';
                licenseField.querySelector('input').setAttribute('required', 'required');
            } else {
                licenseField.style.display = 'none';
                licenseField.querySelector('input').removeAttribute('required');
            }
        }

        // ==================== FORM VALIDATION ====================
        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        function validatePhone(phone) {
            const re = /^[0-9+\-\s()]{10,}$/;
            return re.test(phone);
        }

        function validatePassword(password) {
            return password.length >= 6;
        }

        // Real-time form validation
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            
            forms.forEach(form => {
                const inputs = form.querySelectorAll('input[required]');
                
                inputs.forEach(input => {
                    input.addEventListener('blur', function() {
                        validateField(this);
                    });
                    
                    input.addEventListener('input', function() {
                        clearFieldError(this);
                    });
                });
            });
            
            // Add license field toggle based on initial role selection
            const clientRadio = document.querySelector('input[value="client"]');
            if (clientRadio) {
                clientRadio.addEventListener('change', function() {
                    toggleLicenseField(this.checked);
                });
            }
        });

        function validateField(field) {
            const value = field.value.trim();
            let isValid = true;
            let message = '';
            
            switch(field.type) {
                case 'email':
                    isValid = validateEmail(value);
                    message = 'Format d\'email invalide';
                    break;
                case 'tel':
                    isValid = validatePhone(value);
                    message = 'Numéro de téléphone invalide';
                    break;
                case 'password':
                    isValid = validatePassword(value);
                    message = 'Le mot de passe doit contenir au moins 6 caractères';
                    break;
                default:
                    isValid = value !== '';
                    message = 'Ce champ est obligatoire';
            }
            
            if (!isValid) {
                showFieldError(field, message);
            } else {
                clearFieldError(field);
            }
            
            return isValid;
        }

        function showFieldError(field, message) {
            clearFieldError(field);
            field.style.borderColor = '#ef4444';
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'field-error';
            errorDiv.style.color = '#ef4444';
            errorDiv.style.fontSize = '0.875rem';
            errorDiv.style.marginTop = '0.25rem';
            errorDiv.textContent = message;
            
            field.parentNode.appendChild(errorDiv);
        }

        function clearFieldError(field) {
            field.style.borderColor = '';
            const existingError = field.parentNode.querySelector('.field-error');
            if (existingError) {
                existingError.remove();
            }
        }

        function toggleLicenseField(show) {
            const licenseField = document.getElementById('license-field');
            const licenseInput = licenseField.querySelector('input');
            
            if (show) {
                licenseField.style.display = 'block';
                licenseInput.setAttribute('required', 'required');
            } else {
                licenseField.style.display = 'none';
                licenseInput.removeAttribute('required');
            }
        }

        // ==================== UTILITY FUNCTIONS ====================
        function formatCurrency(amount) {
            return new Intl.NumberFormat('fr-DZ', {
                style: 'currency',
                currency: 'DZD'
            }).format(amount);
        }

        function formatDate(dateString) {
            const options = { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                weekday: 'long'
            };
            return new Date(dateString).toLocaleDateString('fr-FR', options);
        }

        function showLoading(button) {
            const originalText = button.innerHTML;
            button.innerHTML = '<div class="loading-spinner"></div> Chargement...';
            button.disabled = true;
            
            return function() {
                button.innerHTML = originalText;
                button.disabled = false;
            };
        }

        // ==================== NOTIFICATION SYSTEM ====================
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                color: white;
                z-index: 10000;
                animation: slideIn 0.3s ease-out;
                max-width: 400px;
            `;
            
            const colors = {
                success: '#10b981',
                error: '#ef4444',
                warning: '#f59e0b',
                info: '#3b82f6'
            };
            
            notification.style.background = colors[type] || colors.info;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        }

        // Add CSS animations for notifications
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
            .loading-spinner {
                display: inline-block;
                width: 16px;
                height: 16px;
                border: 2px solid #ffffff;
                border-radius: 50%;
                border-top-color: transparent;
                animation: spin 1s ease-in-out infinite;
                margin-right: 8px;
            }
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);

        // ==================== DATE UTILITIES ====================
        function getToday() {
            return new Date().toISOString().split('T')[0];
        }

        function addDays(date, days) {
            const result = new Date(date);
            result.setDate(result.getDate() + days);
            return result.toISOString().split('T')[0];
        }

        function calculateDays(startDate, endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            const timeDiff = end - start;
            return Math.ceil(timeDiff / (1000 * 60 * 60 * 24));
        }

        // ==================== INITIAL SETUP ====================
        document.addEventListener('DOMContentLoaded', function() {
            // Set minimum dates for date inputs
            const today = getToday();
            document.querySelectorAll('input[type="date"]').forEach(input => {
                input.min = today;
            });
            
            // Auto-format phone numbers
            const phoneInputs = document.querySelectorAll('input[type="tel"]');
            phoneInputs.forEach(input => {
                input.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 0) {
                        value = value.match(/.{1,2}/g).join(' ');
                    }
                    e.target.value = value;
                });
            });
            
            console.log('Car Rental System initialized successfully');
        });

        // ==================== API COMMUNICATION ====================
        async function apiCall(endpoint, data = {}) {
            try {
                const formData = new FormData();
                for (const key in data) {
                    formData.append(key, data[key]);
                }
                
                const response = await fetch(endpoint, {
                    method: 'POST',
                    body: formData
                });
                
                return await response.json();
            } catch (error) {
                console.error('API call failed:', error);
                return { success: false, error: 'Network error' };
            }
        }
    </script>
</body>
</html>
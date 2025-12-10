<?php
// Avvia la sessione per mantenere lo stato di login dell'utente
session_start();

// --- INCLUSIONE FILE DI CONFIGURAZIONE DATABASE ---
// Assicurati che il file 'config.php' si trovi nella stessa cartella
if (!file_exists('config.php')) {
    die("ERRORE FATALE: Il file 'config.php' non è stato trovato. Impossibile connettersi al database.");
}
require_once 'config.php';

// --- CONFIGURAZIONE VARIABILI ---
// Recupera i dati di connessione dalle costanti definite nel config.php
$servername = DB_SERVER; 
$username_db = DB_USERNAME; 
$password_db = DB_PASSWORD; 
$dbname = DB_NAME; 

// Nomi dei campi nel database (utenti)
$table_name = "utenti"; 
$login_field = "email"; // Campo usato per l'accesso
$password_field = "password"; // Campo contenente la password

$error_message = "";
$is_logged_in = isset($_SESSION['user_id']);

// --- FUNZIONE DI CONNESSIONE AL DATABASE ---
function connect_db($servername, $username_db, $password_db, $dbname) {
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username_db, $password_db);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        // In un ambiente di produzione, si dovrebbe loggare l'errore e mostrare un messaggio generico
        return null; 
    }
}


// --- GESTIONE DEL LOGIN ---

if (!$is_logged_in && isset($_POST['submit_login'])) {
    $login_value = trim($_POST['login_value']); 
    $password_input = $_POST['password'];

    $conn = connect_db($servername, $username_db, $password_db, $dbname);
    
    if ($conn === null) {
         $error_message = "ERRORE: Impossibile connettersi al database. Controlla le impostazioni.";
    } else {
        try {


            /*
            // Usa Prepared Statements per prevenire SQL injection
            $stmt = $conn->prepare("SELECT * FROM $table_name WHERE $login_field = :login_value");
            $stmt->bindParam(':login_value', $login_value);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            */


            $stmt = $conn->query("SELECT * FROM $table_name WHERE $login_field = $login_value");
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verifica delle credenziali
            if ($user && passowrd_verify($password_input, $user[$password_field])) { 
                
                // Login Riuscito! Salva i dati nella sessione
                $_SESSION['user_id'] = $user['id']; 
                $_SESSION['user_data'] = $user;
                
                // Reindirizza l'utente alla stessa pagina (impedisce il re-invio del form)
                header("Location: index.php");
                exit();
            } else {
                $error_message = "Credenziali non valide. Riprova.";
            }
            
        } catch(Exception $e) {
            $error_message = "Si è verificato un errore durante l'accesso.";
        }
    }
}

// --- GESTIONE DEL LOGOUT ---

if (isset($_GET['logout'])) {
    session_unset(); 
    session_destroy(); 
    header("Location: index.php");
    exit();
}

// Aggiorna lo stato di login dopo le operazioni
$is_logged_in = isset($_SESSION['user_id']);

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_logged_in ? 'Profilo Utente' : 'Login'; ?> | Applicazione Tesi</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            max-width: 600px; 
            margin: 50px auto; 
            padding: 30px; 
            border: 1px solid #ddd; 
            border-radius: 10px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            background-color: #f9f9f9;
        }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; text-align: center; }
        h2 { color: #007bff; margin-top: 25px; }
        .error { color: #dc3545; background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .profile-data { margin-top: 20px; }
        .profile-data p { background: #fff; padding: 12px; border: 1px solid #eee; border-radius: 5px; margin: 8px 0; }
        .profile-data strong { color: #333; display: inline-block; width: 100px; }
        input[type="text"], input[type="password"] { 
            width: 100%; 
            padding: 10px; 
            margin: 8px 0 20px 0; 
            display: inline-block; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
            box-sizing: border-box; 
        }
        input[type="submit"] {
            background-color: #007bff;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .logout-btn { text-align: right; margin-bottom: 20px; }
        .logout-btn a { color: #dc3545; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<?php if ($is_logged_in): ?>

    <div class="logout-btn">
        <a href="?logout=true">🚪 Esci (Logout)</a>
    </div>
    
    <h1>Dashboard Utente</h1>
    
    <?php
    // Tenta di usare la colonna 'nome', altrimenti usa il campo di login (email)
    $display_name = $_SESSION['user_data']['nome'] ?? $_SESSION['user_data'][$login_field];
    ?>
    <h2>👋 Benvenuto, <?php echo htmlspecialchars($display_name); ?>!</h2> 
    
    <h3>I Tuoi Dati Salvati (Tabella '<?php echo $table_name; ?>')</h3>
    
    <div class="profile-data">
        <?php
        // Itera sui dati dell'utente salvati in sessione e li mostra
        foreach ($_SESSION['user_data'] as $key => $value) {
            // Esclude la password dalla visualizzazione per sicurezza
            if ($key !== $password_field) { 
                echo "<p><strong>" . htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) . ":</strong> " . htmlspecialchars($value) . "</p>";
            }
        }
        ?>
    </div>
    
<?php else: ?>

    <h1>🔒 Accesso Riservato</h1>
    
    <?php if (!empty($error_message)): ?>
        <p class="error"><?php echo $error_message; ?></p>
    <?php endif; ?>

    <form method="POST" action="index.php">
        <label for="login_value">👤 <?php echo ucfirst($login_field); ?>:</label>
        <input type="text" id="login_value" name="login_value" required value="<?php echo htmlspecialchars($_POST['login_value'] ?? ''); ?>">

        <label for="password">🔑 Password:</label>
        <input type="password" id="password" name="password" required>

        <input type="submit" name="submit_login" value="Accedi">
    </form>
    
    <p style="margin-top: 30px; font-size: 0.8em; text-align: center; color: #666;">
        Questa pagina utilizza il file 'config.php' per connettersi a MySQL.
    </p>

<?php endif; ?>

</body>
</html>


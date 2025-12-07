<?php
// Includi le costanti di connessione
require_once 'config.php';

$table_name = "utenti"; 
$password_field = "password"; 

$servername = DB_SERVER; 
$username_db = DB_USERNAME; 
$password_db = DB_PASSWORD; 
$dbname = DB_NAME; 

function connect_db($servername, $username_db, $password_db, $dbname) {
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username_db, $password_db);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        die("ERRORE DI CONNESSIONE: " . $e->getMessage()); 
    }
}

$conn = connect_db($servername, $username_db, $password_db, $dbname);

echo "--- Inizio Migrazione Password ---\n\n";

try {
    // 1. Legge tutti gli utenti
    $stmt_select = $conn->prepare("SELECT id, $password_field FROM $table_name");
    $stmt_select->execute();
    $users = $stmt_select->fetchAll(PDO::FETCH_ASSOC);

    $count = 0;
    foreach ($users as $user) {
        $clear_password = $user[$password_field];

        // 2. Genera l'hash PHP
        // Importante: password_hash() usa un algoritmo moderno e un sale casuale
        $hashed_password = password_hash($clear_password, PASSWORD_DEFAULT);

        // 3. Aggiorna il database con l'hash
        $stmt_update = $conn->prepare("UPDATE $table_name SET $password_field = :hash WHERE id = :id");
        $stmt_update->bindParam(':hash', $hashed_password);
        $stmt_update->bindParam(':id', $user['id']);
        $stmt_update->execute();
        
        echo "Utente ID " . $user['id'] . " aggiornato con successo.\n";
        $count++;
    }

    echo "\n--- Migrazione Completata. Aggiornati " . $count . " record. ---\n";

} catch (Exception $e) {
    echo "\nERRORE DURANTE LA MIGRAZIONE: " . $e->getMessage() . "\n";
}

?>
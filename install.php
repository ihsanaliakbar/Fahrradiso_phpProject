<?php
require 'config.php';

// Verbindung aufbauen
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
    die('Verbindung fehlgeschlagen: ' . $mysqli->connect_error);
}

// SQL-Datei einlesen
$sql = file_get_contents(__DIR__ . '/fahradiso.sql');
if ($sql === false) {
    die('fahradiso.sql nicht gefunden.');
}

// Alle Statements ausführen
if ($mysqli->multi_query($sql)) {
    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
    } while ($mysqli->more_results() && $mysqli->next_result());
}

// Ergebnis ausgeben
if ($mysqli->error) {
    echo 'Fehler: ' . htmlspecialchars($mysqli->error);
} else {
    echo 'SQL-Skript wurde erfolgreich ausgeführt.';
}

$mysqli->close();
?>

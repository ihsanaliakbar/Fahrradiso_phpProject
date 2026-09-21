<?php
require 'config.php';

// Verbindung aufbauen
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
    die('Verbindung fehlgeschlagen: ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');

// SQL-Datei mit den Beispieldaten einlesen
$sql = file_get_contents(__DIR__ . '/daten.sql');
if ($sql === false) {
    die('daten.sql nicht gefunden.');
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
    echo 'Beispieldaten wurden erfolgreich eingefügt.';
}

$mysqli->close();
?>

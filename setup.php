<?php
/**
 * setup.php
 *
 * Legt alle Tabellen des Fahrradverleihs an (fahradiso.sql) und fuellt
 * sie mit Beispieldaten (daten.sql). Kann beliebig oft aufgerufen werden:
 * vorhandene Tabellen werden vorher geloescht und neu angelegt.
 */
require 'config.php';

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
    die('Verbindung fehlgeschlagen: ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');

/**
 * Fuehrt alle Statements einer SQL-Datei nacheinander aus.
 * Bricht beim ersten Fehler ab und zeigt das fehlerhafte Statement an.
 */
function runSqlFile(mysqli $mysqli, string $file): void
{
    $sql = file_get_contents(__DIR__ . '/' . $file);
    if ($sql === false) {
        die(htmlspecialchars($file) . ' nicht gefunden.');
    }

    if (!$mysqli->multi_query($sql)) {
        die('Fehler in ' . htmlspecialchars($file) . ': ' . htmlspecialchars($mysqli->error));
    }

    $nr = 1;
    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
        if ($mysqli->error) {
            die('Fehler in ' . htmlspecialchars($file) . ' (Statement ' . $nr . '): '
                . htmlspecialchars($mysqli->error));
        }
        $nr++;
    } while ($mysqli->more_results() && $mysqli->next_result());

    if ($mysqli->error) {
        die('Fehler in ' . htmlspecialchars($file) . ' (Statement ' . $nr . '): '
            . htmlspecialchars($mysqli->error));
    }

    echo '<li>' . htmlspecialchars($file) . ' ausgeführt (' . ($nr - 1) . ' Statements)</li>';
}

echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><title>Setup Fahrradverleih</title></head><body>';
echo '<h1>Setup Fahrradverleih</h1><ul>';

runSqlFile($mysqli, 'fahradiso.sql'); // Tabellen anlegen
runSqlFile($mysqli, 'daten.sql');     // Beispieldaten einfuegen

echo '</ul>';

// Kontrolle: Zeilen pro Tabelle
$tabellen = [
    'Ihsan_Kunde',
    'Ihsan_Preisgruppe',
    'Ihsan_Fahrrad',
    'Ihsan_Ausleihe',
    'Ihsan_Sonderzubehoer',
    'Ihsan_Ausleihe_Zubehoer',
    'Ihsan_Schwierigkeitsgrad',
    'Ihsan_Tour',
    'Ihsan_Termin',
    'Ihsan_Teilnahme',
];

echo '<h2>Datensätze pro Tabelle</h2><table border="1" cellpadding="4">';
echo '<tr><th>Tabelle</th><th>Zeilen</th></tr>';
foreach ($tabellen as $t) {
    $anzahl = $mysqli->query("SELECT COUNT(*) AS n FROM `$t`")->fetch_assoc()['n'];
    echo '<tr><td>' . htmlspecialchars($t) . '</td><td style="text-align:right">' . $anzahl . '</td></tr>';
}
echo '</table>';

echo '<p><strong>Setup abgeschlossen.</strong> <a href="index.php">Zur Übersicht</a></p>';
echo '</body></html>';

$mysqli->close();

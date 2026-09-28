<?php
/**
 * auth.php – Session-Helfer für Login / Registrierung der Kunden.
 * Wird von allen Seiten per require eingebunden.
 */
require_once 'config.php';

session_start();

/** Gibt die Kundennummer des eingeloggten Kunden zurück, sonst null. */
function eingeloggterKunde(): ?int
{
    return isset($_SESSION['kundennummer']) ? (int)$_SESSION['kundennummer'] : null;
}

/** Leitet nicht eingeloggte Besucher zur Login-Seite um. */
function loginErforderlich(): void
{
    if (eingeloggterKunde() === null) {
        header('Location: login.php?weiter=' . urlencode(basename($_SERVER['SCRIPT_NAME'])));
        exit;
    }
}

/** Lädt den Datensatz des eingeloggten Kunden (ohne Passwort). */
function kundeLaden(mysqli $mysqli): ?array
{
    $nr = eingeloggterKunde();
    if ($nr === null) {
        return null;
    }
    $stmt = $mysqli->prepare(
        'SELECT Kundennummer, Anrede, Nachname, Vorname, Strasse, PLZ, Ort, Telefon, Email
         FROM Ihsan_Kunde WHERE Kundennummer = ?'
    );
    $stmt->bind_param('i', $nr);
    $stmt->execute();
    $kunde = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $kunde ?: null;
}

function h(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

/** Navigationsleiste, je nach Login-Status. */
function navigation(?array $kunde): string
{
    $html = '<nav><a href="index.php">Fahrräder nach Modell</a> | <a href="verleih.php">Neue Ausleihe</a>';
    if ($kunde) {
        $html .= ' | <span class="user">Angemeldet als ' . h($kunde['Vorname'] . ' ' . $kunde['Nachname'])
               . '</span> | <a href="logout.php">Abmelden</a>';
    } else {
        $html .= ' | <a href="login.php">Anmelden</a> | <a href="registrieren.php">Registrieren</a>';
    }
    return $html . '</nav>';
}

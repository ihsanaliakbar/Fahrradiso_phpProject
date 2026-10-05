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

/** Navigationsleiste: Links oben links, Kunde bzw. Login-Links oben rechts. */
function navigation(?array $kunde): string
{
    $links = '<a href="index.php">Fahrräder nach Modell</a> | <a href="verleih.php">Neue Ausleihe</a>';
    if ($kunde) {
        $rechts = '<strong>' . h($kunde['Vorname'] . ' ' . $kunde['Nachname']) . '</strong>'
                . ' | <a href="logout.php">Abmelden</a>';
    } else {
        $rechts = '<a href="login.php">Anmelden</a> | <a href="registrieren.php">Registrieren</a>';
    }
    // Leiste über die volle Bildschirmbreite am oberen Rand; die Seiten lassen oben Platz (body margin-top)
    return '<nav style="position:absolute;top:0;left:0;right:0;display:flex;justify-content:space-between;'
         . 'align-items:center;padding:.6rem 2rem;background:#eee;border-bottom:1px solid #ccc;'
         . 'font-family:Arial,sans-serif;box-sizing:border-box">'
         . '<span>' . $links . '</span><span>' . $rechts . '</span></nav>';
}

<?php
/**
 * registrieren.php – Neuen Kunden mit E-Mail und Passwort anlegen.
 * Entspricht dem Anwendungsfall "Neuen Kunden anlegen".
 */
require 'auth.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$mysqli->set_charset('utf8mb4');

$fehler = [];
$d = [
    'anrede'   => trim($_POST['anrede']   ?? 'Frau'),
    'vorname'  => trim($_POST['vorname']  ?? ''),
    'nachname' => trim($_POST['nachname'] ?? ''),
    'strasse'  => trim($_POST['strasse']  ?? ''),
    'plz'      => trim($_POST['plz']      ?? ''),
    'ort'      => trim($_POST['ort']      ?? ''),
    'telefon'  => trim($_POST['telefon']  ?? ''),
    'email'    => trim($_POST['email']    ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $passwort  = $_POST['passwort']  ?? '';
    $passwort2 = $_POST['passwort2'] ?? '';

    foreach (['vorname' => 'Vorname', 'nachname' => 'Nachname', 'strasse' => 'Straße',
              'plz' => 'PLZ', 'ort' => 'Ort', 'email' => 'E-Mail'] as $feld => $name) {
        if ($d[$feld] === '') {
            $fehler[] = "Bitte das Feld „{$name}“ ausfüllen.";
        }
    }
    if ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
        $fehler[] = 'Bitte eine gültige E-Mail-Adresse angeben.';
    }
    if (strlen($passwort) < 8) {
        $fehler[] = 'Das Passwort muss mindestens 8 Zeichen lang sein.';
    } elseif ($passwort !== $passwort2) {
        $fehler[] = 'Die Passwörter stimmen nicht überein.';
    }

    if (!$fehler) {
        // E-Mail schon vergeben?
        $stmt = $mysqli->prepare('SELECT 1 FROM Ihsan_Kunde WHERE Email = ?');
        $stmt->bind_param('s', $d['email']);
        $stmt->execute();
        $vergeben = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        if ($vergeben) {
            $fehler[] = 'Zu dieser E-Mail-Adresse gibt es bereits ein Konto.';
        }
    }

    if (!$fehler) {
        $hash    = password_hash($passwort, PASSWORD_DEFAULT);
        $telefon = $d['telefon'] !== '' ? $d['telefon'] : null;
        $stmt = $mysqli->prepare(
            'INSERT INTO Ihsan_Kunde (Anrede, Nachname, Vorname, Strasse, PLZ, Ort, Telefon, Email, Passwort)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('sssssssss', $d['anrede'], $d['nachname'], $d['vorname'], $d['strasse'],
                          $d['plz'], $d['ort'], $telefon, $d['email'], $hash);
        $stmt->execute();
        $stmt->close();
        $mysqli->close();
        header('Location: login.php?registriert=1');
        exit;
    }
}

$kunde = kundeLaden($mysqli);
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Fahrradverleih – Registrieren</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 4.5rem 2rem 2rem; max-width: 30rem; }
        nav a { margin: 0 .3rem; }
        label { display: block; margin-top: .8rem; }
        input, select { width: 100%; padding: .4rem; box-sizing: border-box; }
        button { margin-top: 1rem; padding: .5rem 1rem; }
        .fehler { background: #fdd; border: 1px solid #c00; padding: .5rem 1rem; margin: 1rem 0; }
    </style>
</head>
<body>
    <?= navigation($kunde) ?>
    <h1>Registrieren</h1>

    <?php if ($fehler): ?>
        <div class="fehler"><ul>
            <?php foreach ($fehler as $f): ?><li><?= h($f) ?></li><?php endforeach; ?>
        </ul></div>
    <?php endif; ?>

    <form method="post">
        <label for="anrede">Anrede</label>
        <select id="anrede" name="anrede">
            <?php foreach (['Frau', 'Herr', 'Divers'] as $a): ?>
                <option <?= $d['anrede'] === $a ? 'selected' : '' ?>><?= $a ?></option>
            <?php endforeach; ?>
        </select>
        <label for="vorname">Vorname</label>
        <input id="vorname" name="vorname" value="<?= h($d['vorname']) ?>" required>
        <label for="nachname">Nachname</label>
        <input id="nachname" name="nachname" value="<?= h($d['nachname']) ?>" required>
        <label for="strasse">Straße und Hausnummer</label>
        <input id="strasse" name="strasse" value="<?= h($d['strasse']) ?>" required>
        <label for="plz">PLZ</label>
        <input id="plz" name="plz" value="<?= h($d['plz']) ?>" required>
        <label for="ort">Ort</label>
        <input id="ort" name="ort" value="<?= h($d['ort']) ?>" required>
        <label for="telefon">Telefon (optional)</label>
        <input id="telefon" name="telefon" value="<?= h($d['telefon']) ?>">
        <label for="email">E-Mail</label>
        <input type="email" id="email" name="email" value="<?= h($d['email']) ?>" required>
        <label for="passwort">Passwort (mindestens 8 Zeichen)</label>
        <input type="password" id="passwort" name="passwort" minlength="8" required>
        <label for="passwort2">Passwort wiederholen</label>
        <input type="password" id="passwort2" name="passwort2" minlength="8" required>
        <button type="submit">Konto anlegen</button>
    </form>

    <p>Schon registriert? <a href="login.php">Zur Anmeldung</a></p>
</body>
</html>

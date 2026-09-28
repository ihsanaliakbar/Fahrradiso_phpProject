<?php
/**
 * login.php – Anmeldung eines Kunden mit E-Mail und Passwort.
 */
require 'auth.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$mysqli->set_charset('utf8mb4');

// Ziel nach erfolgreichem Login (nur eigene Seiten erlauben)
$weiter = basename($_REQUEST['weiter'] ?? 'verleih.php');
if (!preg_match('/^[a-z]+\.php$/', $weiter)) {
    $weiter = 'verleih.php';
}

$fehler = '';
$email  = trim($_POST['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $passwort = $_POST['passwort'] ?? '';

    $stmt = $mysqli->prepare('SELECT Kundennummer, Passwort FROM Ihsan_Kunde WHERE Email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row && password_verify($passwort, $row['Passwort'])) {
        session_regenerate_id(true);
        $_SESSION['kundennummer'] = (int)$row['Kundennummer'];
        header('Location: ' . $weiter);
        exit;
    }
    // Bewusst keine Angabe, ob E-Mail oder Passwort falsch war
    $fehler = 'E-Mail oder Passwort ist falsch.';
}

$kunde = kundeLaden($mysqli);
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Fahrradverleih – Anmelden</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; max-width: 30rem; }
        nav a { margin: 0 .3rem; }
        label { display: block; margin-top: .8rem; }
        input { width: 100%; padding: .4rem; box-sizing: border-box; }
        button { margin-top: 1rem; padding: .5rem 1rem; }
        .fehler { background: #fdd; border: 1px solid #c00; padding: .5rem 1rem; margin: 1rem 0; }
        .ok { background: #dfd; border: 1px solid #090; padding: .5rem 1rem; margin: 1rem 0; }
    </style>
</head>
<body>
    <?= navigation($kunde) ?>
    <h1>Anmelden</h1>

    <?php if (isset($_GET['registriert'])): ?>
        <div class="ok">Registrierung erfolgreich. Bitte melden Sie sich an.</div>
    <?php endif; ?>
    <?php if ($fehler): ?>
        <div class="fehler"><?= h($fehler) ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="weiter" value="<?= h($weiter) ?>">
        <label for="email">E-Mail</label>
        <input type="email" id="email" name="email" value="<?= h($email) ?>" required autofocus>
        <label for="passwort">Passwort</label>
        <input type="password" id="passwort" name="passwort" required>
        <button type="submit">Anmelden</button>
    </form>

    <p>Noch kein Konto? <a href="registrieren.php">Jetzt registrieren</a></p>
</body>
</html>

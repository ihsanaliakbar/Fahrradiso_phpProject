<?php
require 'config.php';

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
    die('Verbindung fehlgeschlagen: ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');

// Alle Modelle für das Dropdown laden
$modelle = [];
$result = $mysqli->query('SELECT DISTINCT Modell FROM Ihsan_Fahrrad ORDER BY Modell');
while ($row = $result->fetch_assoc()) {
    $modelle[] = $row['Modell'];
}
$result->free();

// Gewähltes Modell aus dem Formular
$gewaehlt = $_GET['modell'] ?? '';

// Fahrräder zum gewählten Modell laden (Prepared Statement)
$fahrraeder = [];
if ($gewaehlt !== '') {
    $stmt = $mysqli->prepare(
        'SELECT f.Fahrradnummer, f.Art, f.Hersteller, f.Modell, f.Rahmengroesse,
                f.Anschaffungspreis, f.Anschaffungsdatum, f.LetzteWartung,
                p.Bezeichnung AS Preisgruppe, p.Tagespreis
         FROM Ihsan_Fahrrad f
         JOIN Ihsan_Preisgruppe p ON p.PreisgruppenNr = f.PreisgruppenNr
         WHERE f.Modell = ?
         ORDER BY f.Fahrradnummer'
    );
    $stmt->bind_param('s', $gewaehlt);
    $stmt->execute();
    $fahrraeder = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$mysqli->close();

function h(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Fahrradverleih – Fahrräder nach Modell</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; }
        label { margin-right: .5rem; }
        select { padding: .3rem; }
        table { border-collapse: collapse; margin-top: 1.5rem; }
        th, td { border: 1px solid #999; padding: .4rem .8rem; text-align: left; }
        th { background: #eee; }
        td.num { text-align: right; }
    </style>
</head>
<body>
    <h1>Fahrräder nach Modell</h1>

    <form method="get">
        <label for="modell">Modell:</label>
        <select name="modell" id="modell" onchange="this.form.submit()">
            <option value="">– bitte wählen –</option>
            <?php foreach ($modelle as $m): ?>
                <option value="<?= h($m) ?>" <?= $m === $gewaehlt ? 'selected' : '' ?>><?= h($m) ?></option>
            <?php endforeach; ?>
        </select>
        <noscript><button type="submit">Anzeigen</button></noscript>
    </form>

    <?php if ($gewaehlt !== ''): ?>
        <?php if (count($fahrraeder) === 0): ?>
            <p>Keine Fahrräder zum Modell „<?= h($gewaehlt) ?>“ gefunden.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Nr.</th>
                        <th>Art</th>
                        <th>Hersteller</th>
                        <th>Modell</th>
                        <th>Rahmengröße</th>
                        <th>Anschaffungspreis</th>
                        <th>Anschaffungsdatum</th>
                        <th>Letzte Wartung</th>
                        <th>Preisgruppe</th>
                        <th>Tagespreis</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fahrraeder as $f): ?>
                        <tr>
                            <td class="num"><?= h($f['Fahrradnummer']) ?></td>
                            <td><?= h($f['Art']) ?></td>
                            <td><?= h($f['Hersteller']) ?></td>
                            <td><?= h($f['Modell']) ?></td>
                            <td><?= h($f['Rahmengroesse']) ?></td>
                            <td class="num"><?= number_format((float)$f['Anschaffungspreis'], 2, ',', '.') ?> €</td>
                            <td><?= h($f['Anschaffungsdatum']) ?></td>
                            <td><?= $f['LetzteWartung'] !== null ? h($f['LetzteWartung']) : '–' ?></td>
                            <td><?= h($f['Preisgruppe']) ?></td>
                            <td class="num"><?= number_format((float)$f['Tagespreis'], 2, ',', '.') ?> €</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>

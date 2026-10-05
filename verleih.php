<?php
/**
 * verleih.php  –  Teil B: Arbeitsprozess "Kunde leiht Fahrräder"
 *
 * Ablauf:
 *   0. Kunde ist angemeldet (login.php), neue Kunden registrieren sich (registrieren.php)
 *   1. Zeitraum wählen (vorbelegt: 30.10.2025 – 02.11.2025)
 *   2. Verfügbare Fahrräder werden ermittelt (keine überlappende Ausleihe)
 *   3. Ein oder mehrere Fahrräder + optional Zubehör wählen
 *      (Kaution je Rad ergibt sich automatisch aus der Preisgruppe)
 *   4. Buchung in einer Transaktion speichern:
 *        je Fahrrad INSERT Ausleihe  ->  INSERT Ausleihe_Zubehoer
 *   5. Bestätigung mit Preisberechnung anzeigen
 */
require 'auth.php';
loginErforderlich();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Fehler als Exception (fuer Rollback)
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$mysqli->set_charset('utf8mb4');

$kunde = kundeLaden($mysqli);
if ($kunde === null) {              // Session zeigt auf gelöschten Kunden
    header('Location: logout.php');
    exit;
}
$kundennummer = (int)$kunde['Kundennummer'];

function euro(float $betrag): string
{
    return number_format($betrag, 2, ',', '.') . ' €';
}

function datumDe(string $iso): string
{
    return date('d.m.Y', strtotime($iso));
}

// ---------------------------------------------------------------------
// Schritt 1: Zeitraum (GET oder POST), Standard laut Aufgabe
// ---------------------------------------------------------------------
$von = $_REQUEST['von'] ?? '2025-10-30';
$bis = $_REQUEST['bis'] ?? '2025-11-02';

$fehler = [];
$dVon = DateTime::createFromFormat('Y-m-d', $von);
$dBis = DateTime::createFromFormat('Y-m-d', $bis);
if (!$dVon || !$dBis) {
    $fehler[] = 'Bitte gültige Daten angeben.';
} elseif ($dBis < $dVon) {
    $fehler[] = 'Das Rückgabedatum darf nicht vor dem Ausleihdatum liegen.';
}
$tage = (!$fehler) ? $dVon->diff($dBis)->days + 1 : 0;   // 30.10.–02.11. = 4 Tage

const VERSICHERUNG_PREIS = 5.00;   // je Fahrrad, wenn Versicherung gewählt

// ---------------------------------------------------------------------
// Schritt 2: Verfügbare Fahrräder im Zeitraum
// Ein Rad ist belegt, wenn eine Ausleihe den Zeitraum überlappt.
// Rueckgabedatum NULL = noch nicht zurück, zählt als offen.
// ---------------------------------------------------------------------
$verfuegbar = [];
if (!$fehler) {
    $stmt = $mysqli->prepare(
        'SELECT f.Fahrradnummer, f.Art, f.Hersteller, f.Modell, f.Rahmengroesse,
                p.Bezeichnung AS Preisgruppe, p.Tagespreis, p.Kaution
         FROM Ihsan_Fahrrad f
         JOIN Ihsan_Preisgruppe p ON p.PreisgruppenNr = f.PreisgruppenNr
         WHERE f.Fahrradnummer NOT IN (
             SELECT a.Fahrradnummer
             FROM Ihsan_Ausleihe a
             WHERE a.Ausleihdatum <= ?
               AND COALESCE(a.Rueckgabedatum, "9999-12-31") >= ?
         )
         ORDER BY f.Art, f.Hersteller, f.Modell, f.Rahmengroesse'
    );
    $stmt->bind_param('ss', $bis, $von);
    $stmt->execute();
    $verfuegbar = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$zubehoer = $mysqli->query('SELECT ZubehoerNr, Bezeichnung, Preis FROM Ihsan_Sonderzubehoer ORDER BY Bezeichnung')
                   ->fetch_all(MYSQLI_ASSOC);

// ---------------------------------------------------------------------
// Schritt 3 + 4: Buchung verarbeiten (POST)
// ---------------------------------------------------------------------
$buchung = null;   // wird bei Erfolg gefüllt und unten als Bestätigung angezeigt

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$fehler) {
    $gewaehlteRaeder = array_map('intval', $_POST['fahrrad'] ?? []);
    $gewaehlteRaeder = array_values(array_unique($gewaehlteRaeder));
    if (count($gewaehlteRaeder) < 1) {
        $fehler[] = 'Bitte mindestens ein Fahrrad auswählen.';
    }
    // Nur Räder akzeptieren, die wirklich verfügbar sind (kein Manipulieren des Formulars)
    $verfuegbarNachNr = array_column($verfuegbar, null, 'Fahrradnummer');
    foreach ($gewaehlteRaeder as $nr) {
        if (!isset($verfuegbarNachNr[$nr])) {
            $fehler[] = "Fahrrad Nr. $nr ist im gewählten Zeitraum nicht verfügbar.";
        }
    }

    $gewaehltesZubehoer = array_map('intval', $_POST['zubehoer'] ?? []);
    $versicherung = isset($_POST['versicherung']) ? 1 : 0;

    if (!$fehler) {
        // Alles oder nichts: alle Ausleihen + Zubehör in einer Transaktion
        $mysqli->begin_transaction();
        try {
            // 4a) Pro Fahrrad eine Ausleihe; die Kaution ergibt sich aus der Preisgruppe des Rads
            $ausleihNrn = [];
            $stmt = $mysqli->prepare(
                'INSERT INTO Ihsan_Ausleihe (Kundennummer, Fahrradnummer, Ausleihdatum, Rueckgabedatum, Versicherung, Kaution)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            foreach ($gewaehlteRaeder as $nr) {
                $kaution = (float)$verfuegbarNachNr[$nr]['Kaution'];
                $stmt->bind_param('iissid', $kundennummer, $nr, $von, $bis, $versicherung, $kaution);
                $stmt->execute();
                $ausleihNrn[$nr] = $mysqli->insert_id;
            }
            $stmt->close();

            // 4b) Sonderzubehör: hängt nicht am Rad, wird der ersten Ausleihe des Vorgangs zugeordnet
            if ($gewaehltesZubehoer) {
                $ersteAusleihe = reset($ausleihNrn);
                $stmt = $mysqli->prepare(
                    'INSERT INTO Ihsan_Ausleihe_Zubehoer (AusleihNr, ZubehoerNr) VALUES (?, ?)'
                );
                foreach ($gewaehltesZubehoer as $z) {
                    $stmt->bind_param('ii', $ersteAusleihe, $z);
                    $stmt->execute();
                }
                $stmt->close();
            }

            $mysqli->commit();
        } catch (mysqli_sql_exception $e) {
            $mysqli->rollback();
            $fehler[] = 'Buchung fehlgeschlagen: ' . $e->getMessage();
        }
    }

    if (!$fehler) {
        // Schritt 5: Daten für die Bestätigung zusammenstellen
        $zubehoerNachNr = array_column($zubehoer, null, 'ZubehoerNr');
        $positionen = [];
        $summe = 0.0;
        $kautionGesamt = 0.0;
        foreach ($gewaehlteRaeder as $nr) {
            $f = $verfuegbarNachNr[$nr];
            $preis = $tage * (float)$f['Tagespreis'];
            $positionen[] = [
                'text'    => "Fahrrad Nr. $nr: {$f['Art']} {$f['Hersteller']} {$f['Modell']} ({$f['Rahmengroesse']}), "
                           . "Preisgruppe {$f['Preisgruppe']}, $tage Tage × " . euro((float)$f['Tagespreis']),
                'betrag'  => $preis,
                'kaution' => (float)$f['Kaution'],
            ];
            $summe += $preis;
            $kautionGesamt += (float)$f['Kaution'];
        }
        foreach ($gewaehltesZubehoer as $z) {
            $preis = $tage * (float)$zubehoerNachNr[$z]['Preis'];
            $positionen[] = [
                'text'    => "Zubehör: {$zubehoerNachNr[$z]['Bezeichnung']}, $tage Tage × " . euro((float)$zubehoerNachNr[$z]['Preis']),
                'betrag'  => $preis,
                'kaution' => null,
            ];
            $summe += $preis;
        }
        if ($versicherung) {
            $anzahl = count($gewaehlteRaeder);
            $preis  = $anzahl * VERSICHERUNG_PREIS;
            $positionen[] = [
                'text'    => "Versicherung: $anzahl × " . euro(VERSICHERUNG_PREIS) . ' je Fahrrad',
                'betrag'  => $preis,
                'kaution' => null,
            ];
            $summe += $preis;
        }
        $buchung = [
            'kundennummer' => $kundennummer,
            'kunde'        => $kunde,
            'ausleihNrn'   => $ausleihNrn,
            'positionen'   => $positionen,
            'summe'        => $summe,
            'kaution'      => $kautionGesamt,
            'versicherung' => $versicherung,
        ];
    }
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Fahrradverleih – Neue Ausleihe</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 4.5rem 2rem 2rem; max-width: 60rem; }
        nav a { margin-right: 1rem; }
        fieldset { margin-bottom: 1.5rem; }
        legend { font-weight: bold; }
        label.feld { display: inline-block; width: 8rem; }
        .zeile { margin: .3rem 0; }
        table { border-collapse: collapse; margin-top: .5rem; width: 100%; }
        th, td { border: 1px solid #999; padding: .4rem .6rem; text-align: left; }
        th { background: #eee; }
        td.num, th.num { text-align: right; }
        .fehler { background: #fdd; border: 1px solid #c00; padding: .5rem 1rem; margin-bottom: 1rem; }
        .ok { background: #dfd; border: 1px solid #090; padding: .5rem 1rem; margin-bottom: 1rem; }
        .hinweis { color: #555; font-size: .9em; }
    </style>
</head>
<body>
    <?= navigation($kunde) ?>
    <h1>Neue Ausleihe</h1>

    <?php if ($fehler): ?>
        <div class="fehler"><ul>
            <?php foreach ($fehler as $f): ?><li><?= h($f) ?></li><?php endforeach; ?>
        </ul></div>
    <?php endif; ?>

    <?php if ($buchung): ?>
        <div class="ok">Buchung gespeichert. Kundennummer <strong><?= $buchung['kundennummer'] ?></strong>,
            Ausleih-Nr. <strong><?= implode(', ', $buchung['ausleihNrn']) ?></strong>.</div>

        <h2>Bestätigung</h2>
        <p>
            <?= h($buchung['kunde']['Anrede']) ?> <?= h($buchung['kunde']['Vorname']) ?> <?= h($buchung['kunde']['Nachname']) ?><br>
            <?= h($buchung['kunde']['Strasse']) ?>, <?= h($buchung['kunde']['PLZ']) ?> <?= h($buchung['kunde']['Ort']) ?><br>
            Zeitraum: <?= datumDe($von) ?> bis <?= datumDe($bis) ?> (<?= $tage ?> Tage)<br>
            Versicherung: <?= $buchung['versicherung'] ? 'ja' : 'nein' ?>
        </p>
        <table>
            <tr><th>Position</th><th class="num">Mietpreis</th><th class="num">Kaution</th></tr>
            <?php foreach ($buchung['positionen'] as $p): ?>
                <tr>
                    <td><?= h($p['text']) ?></td>
                    <td class="num"><?= euro($p['betrag']) ?></td>
                    <td class="num"><?= $p['kaution'] !== null ? euro($p['kaution']) : '–' ?></td>
                </tr>
            <?php endforeach; ?>
            <tr><th>Summe</th><th class="num"><?= euro($buchung['summe']) ?></th><th class="num"><?= euro($buchung['kaution']) ?></th></tr>
            <tr><th colspan="2">Bei Abholung zu zahlen (Mietpreis + Kaution, Kaution wird bei Rückgabe erstattet)</th>
                <th class="num"><?= euro($buchung['summe'] + $buchung['kaution']) ?></th></tr>
        </table>
        <p><a href="verleih.php">Weitere Ausleihe anlegen</a></p>

    <?php else: ?>

        <form method="get">
            <fieldset>
                <legend>1. Zeitraum</legend>
                <div class="zeile">
                    <label for="von">von</label> <input type="date" id="von" name="von" value="<?= h($von) ?>" required>
                    <label for="bis">bis</label> <input type="date" id="bis" name="bis" value="<?= h($bis) ?>" required>
                    <button type="submit">Verfügbarkeit prüfen</button>
                    <?php if ($tage): ?><span class="hinweis">= <?= $tage ?> Miettage</span><?php endif; ?>
                </div>
            </fieldset>
        </form>

        <?php if (!$fehler || $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <form method="post" action="verleih.php">
            <input type="hidden" name="von" value="<?= h($von) ?>">
            <input type="hidden" name="bis" value="<?= h($bis) ?>">

            <fieldset>
                <legend>2. Fahrräder (mindestens eins auswählen)</legend>
                <?php if (!$verfuegbar): ?>
                    <p>Im Zeitraum <?= datumDe($von) ?> bis <?= datumDe($bis) ?> ist kein Fahrrad frei.</p>
                <?php else: ?>
                    <p class="hinweis">Verfügbar vom <?= datumDe($von) ?> bis <?= datumDe($bis) ?>: <?= count($verfuegbar) ?> Fahrräder.</p>
                    <table>
                        <tr><th></th><th>Nr.</th><th>Art</th><th>Hersteller</th><th>Modell</th><th>Größe</th>
                            <th>Preisgruppe</th><th class="num">Tagespreis</th><th class="num">Preis <?= $tage ?> Tage</th><th class="num">Kaution</th></tr>
                        <?php foreach ($verfuegbar as $f): ?>
                            <?php $nr = (int)$f['Fahrradnummer']; ?>
                            <tr>
                                <td><input type="checkbox" name="fahrrad[]" value="<?= $nr ?>" id="f<?= $nr ?>"
                                    <?= in_array($nr, array_map('intval', $_POST['fahrrad'] ?? []), true) ? 'checked' : '' ?>></td>
                                <td><label for="f<?= $nr ?>"><?= $nr ?></label></td>
                                <td><?= h($f['Art']) ?></td>
                                <td><?= h($f['Hersteller']) ?></td>
                                <td><?= h($f['Modell']) ?></td>
                                <td><?= h($f['Rahmengroesse']) ?></td>
                                <td><?= h($f['Preisgruppe']) ?></td>
                                <td class="num"><?= euro((float)$f['Tagespreis']) ?></td>
                                <td class="num"><?= euro($tage * (float)$f['Tagespreis']) ?></td>
                                <td class="num"><?= euro((float)$f['Kaution']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </fieldset>

            <fieldset>
                <legend>3. Sonderzubehör (optional)</legend>
                <?php foreach ($zubehoer as $z): ?>
                    <?php $znr = (int)$z['ZubehoerNr']; ?>
                    <div class="zeile">
                        <input type="checkbox" name="zubehoer[]" value="<?= $znr ?>" id="z<?= $znr ?>"
                            <?= in_array($znr, array_map('intval', $_POST['zubehoer'] ?? []), true) ? 'checked' : '' ?>>
                        <label for="z<?= $znr ?>"><?= h($z['Bezeichnung']) ?> (<?= euro((float)$z['Preis']) ?> / Tag)</label>
                    </div>
                <?php endforeach; ?>
            </fieldset>

            <fieldset>
                <legend>4. Versicherung</legend>
                <div class="zeile">
                    <input type="checkbox" name="versicherung" id="versicherung" <?= isset($_POST['versicherung']) ? 'checked' : '' ?>>
                    <label for="versicherung">Versicherung abschließen (+ <?= euro(VERSICHERUNG_PREIS) ?> je Fahrrad)</label>
                </div>
                <p class="hinweis">Die Kaution wird je Fahrrad aus der Preisgruppe übernommen (siehe Spalte „Kaution“) und bei Rückgabe erstattet.</p>
            </fieldset>

            <button type="submit" <?= count($verfuegbar) < 1 ? 'disabled' : '' ?>>Ausleihe buchen</button>
        </form>
        <?php endif; ?>

    <?php endif; ?>
</body>
</html>

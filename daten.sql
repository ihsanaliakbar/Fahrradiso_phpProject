-- =====================================================================
-- Fahrradverleih: Beispieldaten
-- Wird von setup.php direkt nach fahradiso.sql ausgefuehrt.
-- Die Tabellen sind zu diesem Zeitpunkt frisch angelegt und leer.
-- =====================================================================

INSERT INTO Ihsan_Preisgruppe (PreisgruppenNr, Bezeichnung, Tagespreis) VALUES
    (1, 'Deluxe',   25.00),
    (2, 'gehoben',  18.00),
    (3, 'Standard', 12.00),
    (4, 'Robust',    8.00);

INSERT INTO Ihsan_Fahrrad (Art, Hersteller, Modell, Rahmengroesse, Anschaffungspreis, Anschaffungsdatum, LetzteWartung, PreisgruppenNr) VALUES
    ('Trekkingrad',  'Kalkhoff',       'Endeavour',  'M', 1299.00, '2023-03-15', '2025-04-10', 2),
    ('Trekkingrad',  'Kalkhoff',       'Endeavour',  'L', 1299.00, '2023-03-15', '2025-04-10', 2),
    ('Mountainbike', 'Cube',           'Aim Race',   'M',  749.00, '2022-05-20', '2025-03-02', 3),
    ('Mountainbike', 'Cube',           'Aim Race',   'S',  749.00, '2022-05-20', NULL,         3),
    ('E-Bike',       'Riese & Müller', 'Charger4',   'L', 4599.00, '2024-02-01', '2025-05-18', 1),
    ('Citybike',     'Gazelle',        'Esprit',     'M',  599.00, '2021-08-11', '2024-11-30', 4),
    ('Citybike',     'Gazelle',        'Esprit',     'S',  599.00, '2021-08-11', '2024-11-30', 4),
    ('Rennrad',      'Canyon',         'Endurace 7', 'M', 1899.00, '2023-06-05', '2025-02-14', 2);

INSERT INTO Ihsan_Kunde (Anrede, Nachname, Vorname, Strasse, PLZ, Ort, Telefon) VALUES
    ('Frau', 'Schneider', 'Anna',   'Lindenstraße 12', '54290', 'Trier', '0651 123456'),
    ('Herr', 'Weber',     'Lukas',  'Am Markt 3',      '54292', 'Trier', '0170 9876543'),
    ('Frau', 'Hoffmann',  'Miriam', 'Moselufer 45',    '54294', 'Trier', NULL);

INSERT INTO Ihsan_Sonderzubehoer (Bezeichnung, Preis) VALUES
    ('Helm',             3.00),
    ('Kindersitz',       5.00),
    ('Fahrradanhänger', 10.00),
    ('Gepäcktaschen',    4.00),
    ('Schloss',          2.00);

-- Zwei abgeschlossene Ausleihen aus der Vergangenheit und
-- eine Reservierung, die den Zeitraum 30.10. bis 02.11.2025 ueberschneidet
-- (damit die Verfuegbarkeitspruefung in Teil B etwas zu tun hat).
INSERT INTO Ihsan_Ausleihe (Kundennummer, Fahrradnummer, Ausleihdatum, Rueckgabedatum, Versicherung, Kaution) VALUES
    (1, 1, '2025-08-02', '2025-08-04', TRUE,  100.00),
    (2, 5, '2025-09-13', '2025-09-14', FALSE, 200.00),
    (3, 3, '2025-10-31', '2025-11-03', TRUE,   80.00);

INSERT INTO Ihsan_Ausleihe_Zubehoer (AusleihNr, ZubehoerNr) VALUES
    (1, 1),
    (1, 5),
    (2, 1),
    (3, 2);

INSERT INTO Ihsan_Schwierigkeitsgrad (SchwierigkeitsNr, Bezeichnung) VALUES
    (1, 'leicht'),
    (2, 'mittel'),
    (3, 'schwer'),
    (4, 'sehr schwer');

INSERT INTO Ihsan_Tour (Bezeichnung, Kurzbeschreibung, Tourlaenge, Startort, Zielort, SchwierigkeitsNr) VALUES
    ('Moselradweg Trier-Bernkastel', 'Flache Genusstour entlang der Mosel mit Weinbergen.', 62.5, 'Trier', 'Bernkastel-Kues', 1),
    ('Hunsrück-Runde',               'Anspruchsvolle Rundtour mit vielen Höhenmetern.',    85.0, 'Trier', 'Trier',           3);

INSERT INTO Ihsan_Termin (TourNr, Beginn, Ende, MaxTeilnehmer) VALUES
    (1, '2025-10-04 09:00:00', '2025-10-04 17:00:00', 12),
    (2, '2025-10-18 08:00:00', '2025-10-18 18:30:00',  8);

INSERT INTO Ihsan_Teilnahme (TerminNr, Kundennummer) VALUES
    (1, 1),
    (1, 2),
    (2, 2);

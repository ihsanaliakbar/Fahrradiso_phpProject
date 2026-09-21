SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE Ihsan_Ausleihe_Zubehoer;
TRUNCATE TABLE Ihsan_Ausleihe;
TRUNCATE TABLE Ihsan_Fahrrad;
TRUNCATE TABLE Ihsan_Preisgruppe;
TRUNCATE TABLE Ihsan_Teilnahme;
TRUNCATE TABLE Ihsan_Termin;
TRUNCATE TABLE Ihsan_Tour;
TRUNCATE TABLE Ihsan_Schwierigkeitsgrad;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO Ihsan_Preisgruppe (PreisgruppenNr, Bezeichnung, Tagespreis) VALUES
    (1, 'Deluxe',   25.00),
    (2, 'gehoben',  18.00),
    (3, 'Standard', 12.00),
    (4, 'Robust',    8.00);

INSERT INTO Ihsan_Schwierigkeitsgrad (SchwierigkeitsNr, Bezeichnung) VALUES
    (1, 'leicht'),
    (2, 'mittel'),
    (3, 'schwer'),
    (4, 'sehr schwer');

INSERT INTO Ihsan_Fahrrad (Art, Hersteller, Modell, Rahmengroesse, Anschaffungspreis, Anschaffungsdatum, LetzteWartung, PreisgruppenNr) VALUES
    ('Trekkingrad', 'Kalkhoff', 'Endeavour',   'M', 1299.00, '2023-03-15', '2025-04-10', 2),
    ('Trekkingrad', 'Kalkhoff', 'Endeavour',   'L', 1299.00, '2023-03-15', '2025-04-10', 2),
    ('Mountainbike', 'Cube',    'Aim Race',    'M',  749.00, '2022-05-20', '2025-03-02', 3),
    ('Mountainbike', 'Cube',    'Aim Race',    'S',  749.00, '2022-05-20', NULL,         3),
    ('E-Bike',       'Riese & Müller', 'Charger4', 'L', 4599.00, '2024-02-01', '2025-05-18', 1),
    ('Citybike',     'Gazelle', 'Esprit',      'M',  599.00, '2021-08-11', '2024-11-30', 4),
    ('Citybike',     'Gazelle', 'Esprit',      'S',  599.00, '2021-08-11', '2024-11-30', 4),
    ('Rennrad',      'Canyon',  'Endurace 7',  'M', 1899.00, '2023-06-05', '2025-02-14', 2);

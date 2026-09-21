-- =====================================================================
-- Fahrradverleih: Tabellenstruktur
-- Wird von setup.php ausgefuehrt. Kann beliebig oft ausgefuehrt werden,
-- da alle Tabellen vorher geloescht werden (in umgekehrter Abhaengigkeit).
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS Ihsan_Teilnahme;
DROP TABLE IF EXISTS Ihsan_Termin;
DROP TABLE IF EXISTS Ihsan_Tour;
DROP TABLE IF EXISTS Ihsan_Schwierigkeitsgrad;
DROP TABLE IF EXISTS Ihsan_Ausleihe_Zubehoer;
DROP TABLE IF EXISTS Ihsan_Sonderzubehoer;
DROP TABLE IF EXISTS Ihsan_Ausleihe;
DROP TABLE IF EXISTS Ihsan_Fahrrad;
DROP TABLE IF EXISTS Ihsan_Preisgruppe;
DROP TABLE IF EXISTS Ihsan_Kunde;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Verleih
-- ---------------------------------------------------------------------

CREATE TABLE Ihsan_Kunde (
    Kundennummer   INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    Anrede         VARCHAR(10)      NOT NULL,
    Nachname       VARCHAR(50)      NOT NULL,
    Vorname        VARCHAR(50)      NOT NULL,
    Strasse        VARCHAR(100)     NOT NULL,
    PLZ            VARCHAR(10)      NOT NULL,
    Ort            VARCHAR(50)      NOT NULL,
    Telefon        VARCHAR(30),
    PRIMARY KEY (Kundennummer)
);

-- Kaution: fester Betrag je Preisgruppe, wird bei der Ausleihe je Rad hinterlegt
CREATE TABLE Ihsan_Preisgruppe (
    PreisgruppenNr TINYINT UNSIGNED NOT NULL,
    Bezeichnung    VARCHAR(20)      NOT NULL,
    Tagespreis     DECIMAL(6,2)     NOT NULL,
    Kaution        DECIMAL(6,2)     NOT NULL,
    PRIMARY KEY (PreisgruppenNr)
);

CREATE TABLE Ihsan_Fahrrad (
    Fahrradnummer     INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    Art               VARCHAR(30)      NOT NULL,
    Hersteller        VARCHAR(50)      NOT NULL,
    Modell            VARCHAR(50)      NOT NULL,
    Rahmengroesse     VARCHAR(10)      NOT NULL,
    Anschaffungspreis DECIMAL(8,2)     NOT NULL,
    Anschaffungsdatum DATE             NOT NULL,
    LetzteWartung     DATE,
    PreisgruppenNr    TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (Fahrradnummer),
    FOREIGN KEY (PreisgruppenNr) REFERENCES Ihsan_Preisgruppe (PreisgruppenNr)
);

CREATE TABLE Ihsan_Ausleihe (
    AusleihNr      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    Kundennummer   INT UNSIGNED NOT NULL,
    Fahrradnummer  INT UNSIGNED NOT NULL,
    Ausleihdatum   DATE         NOT NULL,
    Rueckgabedatum DATE,
    Versicherung   BOOLEAN      NOT NULL DEFAULT FALSE,
    Kaution        DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (AusleihNr),
    FOREIGN KEY (Kundennummer)  REFERENCES Ihsan_Kunde (Kundennummer),
    FOREIGN KEY (Fahrradnummer) REFERENCES Ihsan_Fahrrad (Fahrradnummer)
);

CREATE TABLE Ihsan_Sonderzubehoer (
    ZubehoerNr  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    Bezeichnung VARCHAR(50)  NOT NULL,
    Preis       DECIMAL(6,2) NOT NULL,
    PRIMARY KEY (ZubehoerNr)
);

-- m:n Ausleihe und Sonderzubehör
CREATE TABLE Ihsan_Ausleihe_Zubehoer (
    AusleihNr  INT UNSIGNED NOT NULL,
    ZubehoerNr INT UNSIGNED NOT NULL,
    PRIMARY KEY (AusleihNr, ZubehoerNr),
    FOREIGN KEY (AusleihNr)  REFERENCES Ihsan_Ausleihe (AusleihNr),
    FOREIGN KEY (ZubehoerNr) REFERENCES Ihsan_Sonderzubehoer (ZubehoerNr)
);

-- Tourenverwaltung

CREATE TABLE Ihsan_Schwierigkeitsgrad (
    SchwierigkeitsNr TINYINT UNSIGNED NOT NULL,
    Bezeichnung      VARCHAR(20)      NOT NULL,
    PRIMARY KEY (SchwierigkeitsNr)
);

CREATE TABLE Ihsan_Tour (
    TourNr           INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    Bezeichnung      VARCHAR(50)      NOT NULL,
    Kurzbeschreibung VARCHAR(255),
    Tourlaenge       DECIMAL(5,1)     NOT NULL,
    Startort         VARCHAR(50)      NOT NULL,
    Zielort          VARCHAR(50)      NOT NULL,
    SchwierigkeitsNr TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (TourNr),
    FOREIGN KEY (SchwierigkeitsNr) REFERENCES Ihsan_Schwierigkeitsgrad (SchwierigkeitsNr)
);

CREATE TABLE Ihsan_Termin (
    TerminNr      INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    TourNr        INT UNSIGNED     NOT NULL,
    Beginn        DATETIME         NOT NULL,
    Ende          DATETIME         NOT NULL,
    MaxTeilnehmer TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (TerminNr),
    FOREIGN KEY (TourNr) REFERENCES Ihsan_Tour (TourNr)
);

-- m:n Termin und Kunde
CREATE TABLE Ihsan_Teilnahme (
    TerminNr     INT UNSIGNED NOT NULL,
    Kundennummer INT UNSIGNED NOT NULL,
    PRIMARY KEY (TerminNr, Kundennummer),
    FOREIGN KEY (TerminNr)     REFERENCES Ihsan_Termin (TerminNr),
    FOREIGN KEY (Kundennummer) REFERENCES Ihsan_Kunde (Kundennummer)
);

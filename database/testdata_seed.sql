-- Synthetische Testdaten fuer das Skilltree-MVA
-- Ziel: ausschliesslich die Entwicklungs-/Testdatenbank testdata_skilltree
-- 20 direkte Soll-Skills + 6 ausschliesslich implizite Soll-Skills, 12 DAG-Kanten.
-- Erwartete Soll-Ampel mit Voraussetzungen: 3 rot, 7 gelb, 16 gruen; Ist = 23.

SET NAMES utf8mb4;
START TRANSACTION;

-- Vorhandene Testdaten in abhaengiger Reihenfolge entfernen.
DELETE FROM `employee_skill`;
DELETE FROM `task_skill`;
DELETE FROM `skill_prerequisite`;
DELETE FROM `employee`;
DELETE FROM `task`;
DELETE FROM `skill`;
-- Selbstreferenzen der Gruppen vor dem vollstaendigen Test-Reset aufloesen.
UPDATE `skill_group` SET `parent_skill_group_id` = NULL;
DELETE FROM `skill_group`;
DELETE FROM `organisation_unit`;

INSERT INTO `organisation_unit` (`id`, `name`) VALUES
    (1, 'Testorganisation Personalentwicklung');

INSERT INTO `skill_group` (`id`, `parent_skill_group_id`, `name`, `esco_concept_uri`, `esco_code`) VALUES
    (1, NULL, 'Testtaxonomie', NULL, 'TEST'),
    (2, 1, 'Analyse und Daten', NULL, 'TEST-A'),
    (3, 1, 'Organisation und Steuerung', NULL, 'TEST-B'),
    (4, 1, 'Kommunikation und Zusammenarbeit', NULL, 'TEST-C');

INSERT INTO `skill` (`id`, `skill_group_id`, `name`, `description`, `origin`, `esco_concept_uri`) VALUES
    (1, 2, 'Datenanalyse', 'Synthetischer Testskill.', 'test', NULL),
    (2, 2, 'Tabellenkalkulation', 'Synthetischer Testskill.', 'test', NULL),
    (3, 2, 'Datenvisualisierung', 'Synthetischer Testskill.', 'test', NULL),
    (4, 2, 'SQL-Grundlagen', 'Synthetischer Testskill.', 'test', NULL),
    (5, 2, 'Berichtserstellung', 'Synthetischer Testskill.', 'test', NULL),
    (6, 2, 'Prozessanalyse', 'Synthetischer Testskill.', 'test', NULL),
    (7, 3, 'Projektplanung', 'Synthetischer Testskill.', 'test', NULL),
    (8, 3, 'Terminsteuerung', 'Synthetischer Testskill.', 'test', NULL),
    (9, 3, 'Qualitätsmanagement', 'Synthetischer Testskill.', 'test', NULL),
    (10, 3, 'Dokumentation', 'Synthetischer Testskill.', 'test', NULL),
    (11, 4, 'Teamkommunikation', 'Synthetischer Testskill.', 'test', NULL),
    (12, 4, 'Präsentation', 'Synthetischer Testskill.', 'test', NULL),
    (13, 4, 'Moderation', 'Synthetischer Testskill.', 'test', NULL),
    (14, 3, 'Budgetplanung', 'Synthetischer Testskill mit genau einem Wissensträger.', 'test', NULL),
    (15, 3, 'Vergaberecht', 'Synthetischer Testskill mit genau einem Wissensträger.', 'test', NULL),
    (16, 3, 'Prozessmodellierung', 'Synthetischer Testskill mit genau einem Wissensträger.', 'test', NULL),
    (17, 2, 'Informationssicherheit', 'Synthetischer Testskill mit genau einem Wissensträger.', 'test', NULL),
    (18, 4, 'Wissensmoderation', 'Synthetischer Testskill mit genau einem Wissensträger.', 'test', NULL),
    (19, 4, 'Krisenkommunikation', 'Synthetischer Testskill ohne Wissensträger.', 'test', NULL),
    (20, 2, 'Datenmigration', 'Synthetischer Testskill ohne Wissensträger.', 'test', NULL);

INSERT INTO `employee` (`id`, `organisation_unit_id`, `first_name`, `last_name`) VALUES
    (1, 1, 'Anna', 'Adler'),
    (2, 1, 'Ben', 'Berger'),
    (3, 1, 'Carla', 'Conrad'),
    (4, 1, 'David', 'Dreher'),
    (5, 1, 'Eva', 'Engel');

INSERT INTO `task` (`id`, `organisation_unit_id`, `name`, `description`) VALUES
    (1, 1, 'Monatsreport erstellen', 'Synthetische Testaufgabe.'),
    (2, 1, 'Kennzahlen aufbereiten', 'Synthetische Testaufgabe.'),
    (3, 1, 'Datenabfragen erstellen', 'Synthetische Testaufgabe.'),
    (4, 1, 'Arbeitsabläufe analysieren', 'Synthetische Testaufgabe.'),
    (5, 1, 'Projektvorhaben planen', 'Synthetische Testaufgabe.'),
    (6, 1, 'Qualitätsprüfung koordinieren', 'Synthetische Testaufgabe.'),
    (7, 1, 'Teamworkshop durchführen', 'Synthetische Testaufgabe.'),
    (8, 1, 'Ergebnisse präsentieren', 'Synthetische Testaufgabe.'),
    (9, 1, 'Beschaffung vorbereiten', 'Synthetische Testaufgabe.'),
    (10, 1, 'Sicherheitsvorfall bearbeiten', 'Synthetische Testaufgabe.');

-- Jeder der urspruenglichen 20 Skills ist genau einer Aufgabe direkt zugeordnet.
-- Die sechs zusaetzlichen Grundlagen werden ausschliesslich ueber den DAG benoetigt.
INSERT INTO `task_skill` (`task_id`, `skill_id`) VALUES
    (1, 1), (1, 5),
    (2, 2), (2, 3),
    (3, 4), (3, 20),
    (4, 6), (4, 16),
    (5, 7), (5, 8),
    (6, 9), (6, 10),
    (7, 11), (7, 13),
    (8, 12), (8, 18),
    (9, 14), (9, 15),
    (10, 17), (10, 19);

-- Skills 1 bis 13 sind redundant vorhanden (mindestens zwei Personen).
-- Skills 14 bis 18 besitzen genau einen Wissenstraeger.
-- Skills 19 und 20 werden von keiner Person getragen.
INSERT INTO `employee_skill` (`employee_id`, `skill_id`) VALUES
    (1, 1), (1, 2), (1, 5), (1, 7), (1, 10), (1, 11), (1, 12), (1, 13), (1, 14),
    (2, 1), (2, 3), (2, 4), (2, 6), (2, 8), (2, 9), (2, 11), (2, 13), (2, 15),
    (3, 2), (3, 3), (3, 5), (3, 6), (3, 7), (3, 9), (3, 10), (3, 12), (3, 16),
    (4, 1), (4, 4), (4, 5), (4, 8), (4, 9), (4, 11), (4, 13), (4, 17),
    (5, 2), (5, 3), (5, 4), (5, 6), (5, 7), (5, 8), (5, 10), (5, 12), (5, 13), (5, 18);

-- BEGIN DAG EXTENSION
INSERT INTO `skill` (`id`, `skill_group_id`, `name`, `description`, `origin`, `esco_concept_uri`) VALUES
    (21, 2, 'Datenverständnis', 'Synthetischer impliziter Testskill als gemeinsame Grundlage mehrerer Pfade.', 'test', NULL),
    (22, 2, 'Statistische Grundlagen', 'Synthetischer impliziter Testskill für Analyse und Visualisierung.', 'test', NULL),
    (23, 2, 'Datenaufbereitung', 'Synthetischer impliziter Testskill für Analyse und Migration.', 'test', NULL),
    (24, 2, 'Migrationsplanung', 'Synthetischer impliziter Testskill ohne Wissensträger.', 'test', NULL),
    (25, 3, 'Kostenrechnung', 'Synthetischer impliziter Testskill mit einem Wissensträger.', 'test', NULL),
    (26, 2, 'Rechengrundlagen', 'Synthetischer mittelbarer Testskill mit einem Wissensträger.', 'test', NULL);

-- Spaltenrichtung: (abhaengiger Skill, Voraussetzung).
-- Diagrammkanten verlaufen umgekehrt: Voraussetzung -> abhaengiger Skill.
-- Diamant: Datenverstaendnis -> Statistik/Datenaufbereitung -> Datenanalyse.
-- Dreistufige Kette: Datenverstaendnis -> Rechengrundlagen -> Kostenrechnung -> Budgetplanung.
INSERT INTO `skill_prerequisite` (`skill_id`, `prerequisite_skill_id`) VALUES
    (1, 22), (1, 23),
    (3, 22),
    (14, 25),
    (20, 4), (20, 23), (20, 24),
    (22, 21), (23, 21), (24, 21),
    (25, 26), (26, 21);
-- END DAG EXTENSION

COMMIT;

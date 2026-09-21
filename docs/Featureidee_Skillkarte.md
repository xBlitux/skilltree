# Featureidee: Skillkarte

Vom Nutzer am 21.09.2026 freigegebene Ergänzung nach Abschluss des ursprünglichen Umfangs. **Keine offizielle Anforderung und keine Änderung des Excel-Katalogs oder seiner Markdown-Lesefassung.** Referenz: `wireframes/Mainframe_skillmap.png`.

## Ansicht und Berechnung

Der Umschalter bietet Baum, Graph und Karte. Die bisherige Graph-Startansicht bleibt erhalten. Die Karte zeigt ohne Aufklappen alle Skills des aktuellen Organisationsausschnitts, alphabetisch nach ihrer unmittelbar zugeordneten Gruppe und innerhalb der Gruppe nach Skillname. Das sind im vorbereiteten Bestand die untersten ESCO-Gruppen. Falls ein Skill direkt einer inneren Gruppe zugeordnet ist, wird er unter dieser tatsächlichen Gruppe gezeigt und nicht ausgelassen oder künstlich umgeordnet. Leere Gruppen erzeugen keine Tabellenzeile.

Die linke Spalte zeigt den Gruppennamen, daneben organisationsbezogenen Ampelstatus und Skillnamen. Danach folgt je eingeschlossenem Mitarbeiter eine Spalte: grünes Häkchen für Besitz, graues Kreuz für fehlenden Besitz. Vollständig hergeleitete Besitzmengen einschließlich impliziter Skills werden verwendet. Gruppen-/Skillspalten und Tabellenkopf bleiben beim Scrollen sichtbar; lange Namen werden umgebrochen. Der Gruppenname bleibt innerhalb seines Tabellenblocks am oberen Rand sichtbar.

Die drei rechten Spalten gelten je Skill:

- **Vorhanden:** Anzahl eindeutiger eingeschlossener Personen mit diesem Skill.
- **Benötigt:** 2 für aktuelle Soll-Skills, sonst 0. Dieser zusätzliche Zielwert beschreibt die gewünschte Zahl von Wissensträgern; er ändert nicht die bestehende mengenbasierte Soll-Berechnung.
- **Lücke:** `max(0, benötigt − vorhanden)`.

Beispiele: Datenanalyse im synthetischen Seed = 3/2/0; Budgetplanung = 1/2/1; Datenmigration = 0/2/2. Ein Katalogskill außerhalb des Solls bleibt neutral, ohne anklickbaren Ampelstatus, bei Zielwert und Lücke 0. Besitz wird auch dort vollständig angezeigt. Bei leerem Soll bleibt entsprechend der bisherigen Anwendung der ganze Katalog zugänglich; alle Zielwerte sind 0.

## Navigation und Filter

- Skillname öffnet den allgemeinen Entwicklungspfad, unabhängig von einer zuvor in Baum/Graph ausgewählten Person.
- Häkchen und Kreuz öffnen den persönlichen verbleibenden Entwicklungspfad der betreffenden Person. Bei fehlender Schätzung erscheint für beide Zugänge dieselbe schließbare Warnung wie bisher.
- Ampelpunkt öffnet die entsprechende Kategorie am betreffenden Skill: Rot/Gelb mit geöffneter Kandidatenbox, Grün mit fokussierter und kurz hervorgehobener Tabellenzeile.
- Die Mitarbeitermaske wird nur in der Karte ausgeblendet und beeinflusst deren Bewertungen nicht. Die Auswahl bleibt für Baum/Graph erhalten. Im Entwicklungspfad ist die Personenauswahl weiterhin verfügbar.
- Organisationsmaske und Simulation wirken weiterhin gemeinsam auf den angezeigten Stand. Ausgeschlossene Personen verschwinden als Spalten und Wissensträger. Zurück erhält die aktuelle Simulation; ausgeschlossene Personen werden auch bei Rückkehr nicht reaktiviert.
- Zurück aus Pfad/Kategorie stellt Karte, Organisationsmaske und deren horizontale/vertikale Scrollposition wieder her. Ein Wechsel zwischen den drei Taxonomieansichten erhält den Aufklappzustand von Baum/Graph sowie den gespeicherten Kartenausschnitt. Reset in der Karte scrollt sie nach links oben; Start stellt wie bisher den ungefilterten Graph-Ausgangszustand her.

## Technik und Prüfung

Keine neuen Pakete, API-Felder, Datenbankzugriffe oder Schreiboperationen. `GET /api/analysis` liefert weiterhin `taxonomy`, `required_skill_ids`, `skills` und `employees.available_skill_ids`; Simulation verwendet unverändert die vorhandenen Ausschlussparameter. `domain/skillMap.ts` projiziert diese Daten, `SkillMap.vue` stellt sie dar. Navigationszustand und Kategorieansprung liegen in `App.vue`.

Unverändert genutzte Regeln/Regressionen: F-04/F-08/F-10 (Organisationsausschnitt), F-11 (Personenauswahl der bisherigen Ansichten), A-03 bis A-05/A-13/A-16 (Bewertung und Simulation), S-01/S-04/S-08 (Pfadverfügbarkeit und Navigation). Dies ordnet bestehende Prüfungen zu und ergänzt keine offiziellen Anforderungen.

Ausgeführt am 21.09.2026: 20 Frontendtests, 32 Backendtests mit 159 Assertions, lesender Seed-/Simulationsabgleich in `testdata_skilltree`, TypeScript-/Vite-Build und vollständiger Edge-Browserlauf mit 19 erfolgreichen Tests (16 bestehende, drei neue Kartentests). Die Kartenprüfungen gleichen jede Besitzzeile und Kennzahl mit dem synthetischen API-Datensatz ab und decken Masken, drei Ansichtstypen, beide Pfadarten, Warnung, drei Kategorieansprünge, Scrollrückkehr, Reset/Start, Mitarbeiterausschluss und leeres Soll ab. Desktop-/Mobil-Screenshots wurden visuell geprüft. Nach der visuellen Korrektur von Gruppenposition und Namensumbruch wurden die drei Kartentests erneut erfolgreich ausgeführt und die Screenshots nochmals kontrolliert.

Beim ersten Browserlauf verhinderte ein bereits vorhandener ungültiger lokaler `DB_CHARSET`-Wert (`utf8mb4 d`) das Laden der API. Ausschließlich dieser Wert wurde in der nicht versionierten `.env` auf `utf8mb4` korrigiert. Keine Zugangsdaten oder Datenbankinhalte wurden verändert oder versioniert.

Offen bleiben Echtdaten, die Leistung bei vollständiger großer Taxonomie und weitere Browser. Die Karte rendert den vollständigen aktuellen Ausschnitt; sie verwendet keine Virtualisierung.

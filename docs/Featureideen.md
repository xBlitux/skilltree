# Featureideen und freigegebene Erweiterungen

Stand: 21.09.2026. Die Skillmatrix und ergänzende Bedienideen sind nachträgliche Erweiterungen. Es wurden keine neuen Anforderungs-IDs angelegt. Am 21.09.2026 wurden auf ausdrücklichen Wunsch nur G-04/G-05, F-03/F-11, A-07/A-12 und S-01 angepasst; IDs und Prioritäten der 54 Anforderungen bleiben erhalten. Excel ist weiterhin führend.

## Skillmatrix

Referenz: `wireframes/Mainframe_skillmap.png`. Umschalter: Baum / Graph / Matrix; Standard bleibt Graph. Die Matrix zeigt alle Skills des aktuellen Organisationsausschnitts ohne Aufklappen, nach tatsächlich zugeordneter Gruppe und Skillnamen sortiert. Das sind im Seed die untersten ESCO-Gruppen. Direkt an innere Gruppen zugeordnete Skills bleiben ebenfalls sichtbar. Leere Gruppen entfallen in allen Taxonomieansichten; gezählt werden sichtbare Skills im gesamten Unterbaum, nicht lediglich direkte Untergruppen.

Spalten: Gruppe, Skill mit Organisationsampel, Pfad, Mitarbeitende, Vorhanden, Benötigt, Lücke. Pfad zählt alle eindeutigen Skills im allgemeinen Entwicklungspfad einschließlich Zielskill; gemeinsame Vorgänger einmal. Kein geschätzter Pfad: „–“. Der Spaltenkopf „Pfad“ ist als Hinweis auf den Tooltip unterstrichen: „Gesamtlänge des allgemeinen Entwicklungspfad“.

Besitz einschließlich impliziter Grundlagen: grünes Häkchen / graues Kreuz. Vorhanden zählt alle simulativ eingeschlossenen Wissensträger, Benötigt ist 2 für Soll-Skills und sonst 0, Lücke ist `max(0, benötigt - vorhanden)`. Nicht-Soll-Skills bleiben neutral ohne Kategorie-Link; Zielwert/Lücke sind 0, auch wenn Besitz vorhanden ist. Bei leerem Soll bleibt der gesamte Katalog zugänglich.

Der Mitarbeiterfilter zeigt standardmäßig Alle. Über Checkboxen sind einer oder mehrere Mitarbeiter wählbar; eine leere Auswahl bedeutet Alle. Ein Klick außerhalb schließt das Dropdown, ohne die Auswahl zu verändern. Nur Mitarbeiterspalten werden gefiltert, nicht Ampel oder Kennzahlen. Eine Einzelauswahl wird zwischen Taxonomieansichten übernommen; beim Verlassen der Matrix mit Mehrfachauswahl wird diese verworfen und auf Keiner/Alle zurückgesetzt. Ausgeschlossene Personen verschwinden aus Spalten und Auswahl. Entfällt die gesamte Auswahl durch Simulation, gilt Alle für die verbleibenden Personen.

Skillname: allgemeiner Pfad. Besitzsymbol: persönlicher verbleibender Pfad. Status: organisationsbezogene Kategorie am betreffenden Skill, Rot/Gelb aufgeklappt, Grün fokussiert und kurz hervorgehoben. Dieselbe Statusnavigation gilt in Skill-Listen ohne Personenauswahl. In persönlich gefärbten Listen sind Statuskreise nicht anklickbar und zeigen ausschließlich Vorhanden/Nicht vorhanden als Tooltip.

Nur die anklickbaren organisationsbezogenen Skill-Statuskreise erhalten erweiterte Hovertexte: „Kritisch • Entwicklungskandidaten anzeigen“, „Handlungsbedarf • Entwicklungskandidaten anzeigen“ beziehungsweise „Unkritisch • Wissensträger anzeigen“. Tooltip auf Kreis und umgebendem Button identisch; Graphgruppen und persönliche Statuskreise bleiben unverändert.

Ohne geschätzten Pfad erscheint ein modales Popup. OK, Escape oder ein Klick auf den grauen Hintergrund schließen es; Fokus und Scrollposition bleiben erhalten. Klicks innerhalb des Dialogs und Ziehen aus dem Dialog auf den Hintergrund schließen ihn nicht. Keine zusätzliche Warnzeile. Gruppen-/Skillspalten und Tabellenkopf bleiben beim Scrollen sichtbar. Zurück erhält horizontalen und vertikalen Ausschnitt, Reset scrollt links oben. Start stellt den ungefilterten Graphzustand der aktuellen Einheit her.

## Organisationen und Szenarien

Die Auswahl enthält alle vorbereiteten Organisationseinheiten. Ohne Parameter wird die kleinste ID gewählt; bei einem Wechsel werden Simulation, Mitarbeiterfilter, Organisationsmaske, Navigation, Aufklappung, Darstellung und Distanzgrenze zurückgesetzt. Erst ein erfolgreicher Abruf ersetzt die angezeigten Daten; bei Fehlern bleibt der vorherige Datensatz erhalten, Wiederholen verwendet das angeforderte Ziel. Neuladen beginnt wieder mit der kleinsten ID. Keine Verwaltungsoberfläche.

`scenario.organisation_unit_id` ordnet jedes Szenario fest einer Organisationseinheit zu. Die API liefert ausschließlich deren erste neun Szenarien, nach ID sortiert. Die Buttons werden unabhängig von Datenbank-IDs fortlaufend mit 1–9 beschriftet; bei weniger Einträgen erscheinen entsprechend weniger Buttons. Der Tooltip lautet „Szenario [Name] aktivieren“, Hover grün. Einheiten ohne Szenarien zeigen keine Szenariobuttons. Weitere Datenbankeinträge bleiben gespeichert, werden nach den ersten neun aber nicht angeboten.

Die Verknüpfungen in `scenario_task` bestimmen die eingeschlossenen Aufgaben. Sie sollen ausschließlich Aufgaben derselben Einheit referenzieren; die Leseabfrage begrenzt Aufgaben zusätzlich auf diese Einheit. Ein Szenario ohne Aufgaben schließt alle Aufgaben aus. Mitarbeiterausschlüsse bleiben erhalten; danach sind die Checkboxen weiterhin manuell veränderbar. Das Simulationsfenster schließt auch per Klick auf den grauen Hintergrund, ohne Filter zu verwerfen oder laufende Berechnungen abzubrechen. Klicks innerhalb einschließlich Dialogabstand und Ziehen nach außen schließen es nicht. Es werden keine Szenario- oder Simulationsdaten aus der Anwendung gespeichert.

Testdatenerweiterung ausschließlich `testdata_skilltree`: zwei Einheiten, sechs Personen, sechs Aufgaben und nun neun getrennte Szenarien (drei je Einheit). Gemeinsamer Skillkatalog und ursprüngliche Einheit bleiben unverändert. Die bisher organisationsübergreifend verknüpften Szenarien wurden mit `php scripts/split-test-scenarios.php --apply` transaktional aufgeteilt; ohne Flag liest das Skript lediglich die Zuordnungen. Es akzeptiert nur den bekannten früheren Beispieldatensatz. IDs 1/2/3 bleiben bei Einheit 1, Einheit 2 erhält 4/6/8, Einheit 3 erhält 5/7/9. Die Aufgabenauswahlen der nachfolgenden Tabelle bleiben identisch.

Neuaufbau nach ursprünglichem Seed und bereitgestelltem Szenarioschema mit Organisations-Fremdschlüssel: `php scripts/extend-testdata-organisations.php --apply` erzeugt direkt den getrennten Stand; ohne Flag nur Prüfung. Das Skript verweigert fremde oder bereits erweiterte Ausgangsdaten und schreibt transaktional. Den Schemaexport mit DROP TABLE nicht zur Erweiterung importieren. Der bereitgestellte Schemaexport wurde nicht verändert oder importiert.

| Einheit | Aufgaben | Personen | Rot/Gelb/Grün |
|---|---|---|---|
| 1 Testorganisation Personalentwicklung | 1–10 | 1–5 | 3/7/16 |
| 2 Testorganisation Datenservice | 11–13 | 6–8 | 1/3/4 |
| 3 Testorganisation Verwaltung | 14–16 | 9–11 | 0/5/1 |

| Szenario | Einheit 1 | Einheit 2 | Einheit 3 |
|---|---|---|---|
| 1 Kernaufgaben | 9 Beschaffung vorbereiten, 10 Sicherheitsvorfall bearbeiten | 11 | 14 |
| 2 Analyse und Planung | 1, 2, 3 | 11, 13 | 14, 16 |
| 3 Erweiterter Betrieb | 1–10 | 11–13 | 14–16 |

## Kandidaten und Distanzgrenze

Rot/Gelb teilen eine Grenze zwischen 1 und 5, Standard 3. Der Server berechnet Kandidaten erneut mit dieser Grenze. Träger und simulativ ausgeschlossene Personen bleiben ausgeschlossen. Distanz genau gleich der Grenze ist zulässig, größer wird verworfen; anschließend werden die besten drei zulässigen Personen gewählt. Fehlende Plätze bleiben Unbesetzt.

Das Feld „Maximale Distanz“ erläutert sich beim Hover mit „Maximale Distanz zu Zielskills für Entwicklungskandidaten“. Der Warnungsdialog für nicht geschätzte Pfade ist auf Desktop 510 px breit, mobil auf 90 vw begrenzt, damit der kurze Text auf Desktop in eine Zeile passt.

Die Distanzwarnung prüft das Minimum vor Anwendung der Distanzgrenze. Nur bei Minimum größer als Grenze: Hinweis auf zusätzlichen Personaleinsatz/externe Fertigkeitsgewinnung. Unabhängig davon: „Nicht alle Kandidatenplätze sind besetzt. Weitere Maßnahmen prüfen, da geeignete interne Personen fehlen.“ Bei keiner ansonsten zulässigen Person ist das Minimum null; nur die zweite Warnung gilt. Navigation und Reset erhalten die Grenze, Start/Organisationswechsel setzen 3.

## API und Tests

`GET /api/analysis?organisation=2&maximum_distance=2&excluded_tasks=12&excluded_employees=6`. Alle Parameter optional; Standardorganisation kleinste ID, Distanz 3. Unbekannte Organisation, organisationsfremde Ausschluss-IDs und Grenzen außerhalb 1–5 liefern 400. Alle Daten stammen aus einem konsistenten Snapshot. Neue Antwortfelder: `organisations: [{id,name}]`, `simulation.scenarios: [{id,name,task_ids}]`, `development.maximum_distance`. `minimum_distance` bleibt das Minimum vor dem Distanzfilter, `slots` enthält nur zulässige Vorschläge und null-Plätze. Kein API-Aufruf beim reinen Matrix-Spaltenfilter.

Beispiele: Datenanalyse Pfad=4 und 3/2/0; Budgetplanung ohne Anna-Ausschluss minimale Kandidatendistanz=3: bei Grenze 3 keine Distanzwarnung, bei Grenze 2 drei leere Plätze und beide Warnungen. Datenmigration: bei Grenze 2 Ben und David, dritter Platz unbesetzt; bei Grenze 3 zusätzlich Anna. Szenario 1 der Einheit 1 schließt genau Aufgaben 9/10 ein.

Prüfbefehle: `composer test`, `composer test:database`, in `frontend/` `npm test`, `npm run build`, `npm run test:e2e`. Browser: lokaler Edge unter XAMPP, synthetische Daten. Echtdaten und andere Browser bleiben offen; vollständige Matrix nicht virtualisiert.

Abschlussprüfung am 21.09.2026: **33 Backendtests / 216 Assertions**, **20 Frontendtests**, **23 Edge-Browsertests**, TypeScript-/Vite-Build und lesender Datenbankabgleich erfolgreich. Excel und Markdown stimmen für alle 54 Anforderungen überein, `git diff --check` erfolgreich. Die Browsertests umfassen alle drei Einheiten, Fehler/Wiederholung beim Wechsel, Szenario 1 bei erhaltenem Mitarbeiterausschluss, Matrix-Einzel-/Mehrfach-/Alle-Auswahl und Rückkehr, organisationsbezogene Kennzahlen, Pfadanzahl, Popup mit Scroll-/Fokuswiederherstellung, Statusnavigation, Hovertexte und die Kandidatengrenze. Backendtests prüfen Grenzen 1–5, Gleichheit und Überschreitung sowie ungültige HTTP-Parameter. Desktop-/Mobilaufnahmen wurden kontrolliert. Bewusst überholte Erwartungen (leere Wurzeln, Warnzeile, Distanz 3) wurden an die freigegebenen Regeln angepasst.

Nachprüfung der Bedienergänzungen: alle 23 bisherigen Browsertests erfolgreich; drei neue Bedienungstests nach Korrektur eines mehrdeutigen Testselektors ebenfalls erfolgreich. Geprüft sind Außenklicks mit Auswahl-/Filtererhalt, Innenklick und Ziehen ohne Schließen, Status- und Distanz-Hovertexte, unterstrichenes „Pfad“, einzeiliger Warntext auf Desktop, getrennte reale Szenarien der drei Einheiten sowie synthetisch 0/10 konfigurierte Szenarien mit unabhängigen IDs (sichtbar maximal 9). Screenshot des Warnfensters und mobile Szenariobuttons visuell kontrolliert. Zusätzlich erneut erfolgreich: 33 Backendtests (216 Assertions), 20 Frontendtests, Build, lesender Datenbankabgleich inklusive Prüfung auf organisationsfremde Szenarioaufgaben, Excel-Abgleich und `git diff --check`. Der Anforderungskatalog blieb bei diesem Bedienungsschritt unverändert; betroffen sind ergänzende Bedienabläufe zu F-11, A-12/A-13 und S-01.

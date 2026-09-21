# Featureideen und freigegebene Erweiterungen

Stand: 21.09.2026. Die Skillmatrix und ergänzende Bedienideen sind nachträgliche Erweiterungen. Es wurden keine neuen Anforderungs-IDs angelegt. Am 21.09.2026 wurden auf ausdrücklichen Wunsch nur G-04/G-05, F-03/F-11, A-07/A-12 und S-01 angepasst; IDs und Prioritäten der 54 Anforderungen bleiben erhalten. Excel ist weiterhin führend.

## Skillmatrix

Referenz: `wireframes/Mainframe_skillmap.png`. Umschalter: Baum / Graph / Matrix; Standard bleibt Graph. Die Matrix zeigt alle Skills des aktuellen Organisationsausschnitts ohne Aufklappen, nach tatsächlich zugeordneter Gruppe und Skillnamen sortiert. Das sind im Seed die untersten ESCO-Gruppen. Direkt an innere Gruppen zugeordnete Skills bleiben ebenfalls sichtbar. Leere Gruppen entfallen in allen Taxonomieansichten; gezählt werden sichtbare Skills im gesamten Unterbaum, nicht lediglich direkte Untergruppen.

Spalten: Gruppe, Skill mit Organisationsampel, Pfad, Mitarbeitende, Vorhanden, Benötigt, Lücke. Pfad zählt alle eindeutigen Skills im allgemeinen Entwicklungspfad einschließlich Zielskill; gemeinsame Vorgänger einmal. Kein geschätzter Pfad: „–“. Tooltip am Spaltenkopf: „Gesamtlänge des allgemeinen Entwicklungspfad“.

Besitz einschließlich impliziter Grundlagen: grünes Häkchen / graues Kreuz. Vorhanden zählt alle simulativ eingeschlossenen Wissensträger, Benötigt ist 2 für Soll-Skills und sonst 0, Lücke ist `max(0, benötigt - vorhanden)`. Nicht-Soll-Skills bleiben neutral ohne Kategorie-Link; Zielwert/Lücke sind 0, auch wenn Besitz vorhanden ist. Bei leerem Soll bleibt der gesamte Katalog zugänglich.

Der Mitarbeiterfilter zeigt standardmäßig Alle. Über Checkboxen sind einer oder mehrere Mitarbeiter wählbar; eine leere Auswahl bedeutet Alle. Nur Mitarbeiterspalten werden gefiltert, nicht Ampel oder Kennzahlen. Eine Einzelauswahl wird zwischen Taxonomieansichten übernommen; beim Verlassen der Matrix mit Mehrfachauswahl wird diese verworfen und auf Keiner/Alle zurückgesetzt. Ausgeschlossene Personen verschwinden aus Spalten und Auswahl. Entfällt die gesamte Auswahl durch Simulation, gilt Alle für die verbleibenden Personen.

Skillname: allgemeiner Pfad. Besitzsymbol: persönlicher verbleibender Pfad. Status: organisationsbezogene Kategorie am betreffenden Skill, Rot/Gelb aufgeklappt, Grün fokussiert und kurz hervorgehoben. Dieselbe Statusnavigation gilt in Skill-Listen ohne Personenauswahl. In persönlich gefärbten Listen sind Statuskreise nicht anklickbar und zeigen ausschließlich Vorhanden/Nicht vorhanden als Tooltip.

Ohne geschätzten Pfad erscheint ein modales Popup. OK, Escape oder ein Klick auf den grauen Hintergrund schließen es; Fokus und Scrollposition bleiben erhalten. Klicks innerhalb des Dialogs und Ziehen aus dem Dialog auf den Hintergrund schließen ihn nicht. Keine zusätzliche Warnzeile. Gruppen-/Skillspalten und Tabellenkopf bleiben beim Scrollen sichtbar. Zurück erhält horizontalen und vertikalen Ausschnitt, Reset scrollt links oben. Start stellt den ungefilterten Graphzustand der aktuellen Einheit her.

## Organisationen und Szenarien

Die Auswahl enthält alle vorbereiteten Organisationseinheiten. Ohne Parameter wird die kleinste ID gewählt; bei einem Wechsel werden Simulation, Mitarbeiterfilter, Organisationsmaske, Navigation, Aufklappung, Darstellung und Distanzgrenze zurückgesetzt. Erst ein erfolgreicher Abruf ersetzt die angezeigten Daten; bei Fehlern bleibt der vorherige Datensatz erhalten, Wiederholen verwendet das angeforderte Ziel. Neuladen beginnt wieder mit der kleinsten ID. Keine Verwaltungsoberfläche.

`scenario` besitzt keinen Organisationsschlüssel. Die Buttons 1/2/3 entsprechen den ersten drei nach ID sortierten Szenarien. Die Verknüpfungen in `scenario_task` bestimmen die eingeschlossenen Aufgaben, geschnitten mit der ausgewählten Organisation. Ein Szenario ohne zugeordnete Aufgaben dieser Einheit schließt dort alle Aufgaben aus. Mitarbeiterausschlüsse bleiben erhalten. Danach sind alle Checkboxen weiterhin manuell veränderbar. Tooltip: Szenario 1/2/3 aktivieren; Hover grün. Es werden keine Szenario- oder Simulationsdaten aus der Anwendung gespeichert.

Additive Testdatenerweiterung, ausschließlich `testdata_skilltree`: zwei Einheiten, sechs Personen, sechs Aufgaben, drei Szenarien. Gemeinsamer Skillkatalog und ursprüngliche Einheit bleiben unverändert. Reproduzierbarer Befehl nach ursprünglichem Seed und bereitgestellten Szenariotabellen: `php scripts/extend-testdata-organisations.php --apply`; ohne Flag nur Prüfung. Das Skript verweigert fremde oder bereits erweiterte Ausgangsdaten und schreibt transaktional. Den Schemaexport mit DROP TABLE nicht zur Erweiterung importieren.

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

Die Distanzwarnung prüft das Minimum vor Anwendung der Distanzgrenze. Nur bei Minimum größer als Grenze: Hinweis auf zusätzlichen Personaleinsatz/externe Fertigkeitsgewinnung. Unabhängig davon: „Nicht alle Kandidatenplätze sind besetzt. Weitere Maßnahmen prüfen, da geeignete interne Personen fehlen.“ Bei keiner ansonsten zulässigen Person ist das Minimum null; nur die zweite Warnung gilt. Navigation und Reset erhalten die Grenze, Start/Organisationswechsel setzen 3.

## API und Tests

`GET /api/analysis?organisation=2&maximum_distance=2&excluded_tasks=12&excluded_employees=6`. Alle Parameter optional; Standardorganisation kleinste ID, Distanz 3. Unbekannte Organisation, organisationsfremde Ausschluss-IDs und Grenzen außerhalb 1–5 liefern 400. Alle Daten stammen aus einem konsistenten Snapshot. Neue Antwortfelder: `organisations: [{id,name}]`, `simulation.scenarios: [{id,name,task_ids}]`, `development.maximum_distance`. `minimum_distance` bleibt das Minimum vor dem Distanzfilter, `slots` enthält nur zulässige Vorschläge und null-Plätze. Kein API-Aufruf beim reinen Matrix-Spaltenfilter.

Beispiele: Datenanalyse Pfad=4 und 3/2/0; Budgetplanung ohne Anna-Ausschluss minimale Kandidatendistanz=3: bei Grenze 3 keine Distanzwarnung, bei Grenze 2 drei leere Plätze und beide Warnungen. Datenmigration: bei Grenze 2 Ben und David, dritter Platz unbesetzt; bei Grenze 3 zusätzlich Anna. Szenario 1 der Einheit 1 schließt genau Aufgaben 9/10 ein.

Prüfbefehle: `composer test`, `composer test:database`, in `frontend/` `npm test`, `npm run build`, `npm run test:e2e`. Browser: lokaler Edge unter XAMPP, synthetische Daten. Echtdaten und andere Browser bleiben offen; vollständige Matrix nicht virtualisiert.

Abschlussprüfung am 21.09.2026: **33 Backendtests / 216 Assertions**, **20 Frontendtests**, **23 Edge-Browsertests**, TypeScript-/Vite-Build und lesender Datenbankabgleich erfolgreich. Excel und Markdown stimmen für alle 54 Anforderungen überein, `git diff --check` erfolgreich. Die Browsertests umfassen alle drei Einheiten, Fehler/Wiederholung beim Wechsel, Szenario 1 bei erhaltenem Mitarbeiterausschluss, Matrix-Einzel-/Mehrfach-/Alle-Auswahl und Rückkehr, organisationsbezogene Kennzahlen, Pfadanzahl, Popup mit Scroll-/Fokuswiederherstellung, Statusnavigation, Hovertexte und die Kandidatengrenze. Backendtests prüfen Grenzen 1–5, Gleichheit und Überschreitung sowie ungültige HTTP-Parameter. Desktop-/Mobilaufnahmen wurden kontrolliert. Bewusst überholte Erwartungen (leere Wurzeln, Warnzeile, Distanz 3) wurden an die freigegebenen Regeln angepasst.

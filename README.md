# Skillbasiertes Personalentwicklungstool

Webbasiertes Minimum Viable Artifact zur Unterstützung von Personalbedarfs-, Wissensbewahrungs- und Personalentwicklungsentscheidungen im Rahmen einer Bachelorarbeit.

**Fachstand:** eingefroren am 18.09.2026.  
**Technischer Stand:** Schritte 1–7 sind implementiert: Kernberechnung, Dashboard, hierarchische Taxonomie, Kategorieübersichten, Entwicklungskandidaten, allgemeiner/personenbezogener Entwicklungs-DAG sowie temporäre Simulation mit Zurück/Reset/Start. A-07 wurde auf ausdrücklichen Nutzerwunsch am 19.09.2026 zur skillbezogenen grünen Tabelle geändert. Die Anforderungsprüfung vom 20.09.2026 einschließlich offener Echtdaten-/Evaluationspunkte steht in [docs/Abnahme_Schritt7.md](docs/Abnahme_Schritt7.md).
**Zeitbudget:** etwa 6–7 Tage Umsetzung.

## Einstieg

Die Anwendung vergleicht Aufgabenbedarf und Skillbestand einer vorbereiteten Organisationseinheit, zeigt eine Managementampel und ermittelt Entwicklungskandidaten. Die Taxonomie bietet seit dem freigegebenen Versuch vom 20.09.2026 einen aufklappbaren Gruppengraphen und das bisherige Inhaltsverzeichnis mit gemeinsamen Skill-Listen. Ein separater Entwicklungs-DAG bleibt Bestandteil des MVA. Die fachlichen Einzelregeln stehen im Katalog und in den Use-Cases.

### Graphansicht ausprobieren (20.09.2026)

Unter `http://localhost/skilltree/public/` gegebenenfalls mit Strg+F5 neu laden. Der Versuch startet mit **Graph**; **Baum** wechselt zum bisherigen Inhaltsverzeichnis und erhält geöffnete Gruppen. Kreise zeigen eindeutige Skillzahlen im aktuellen Maskenausschnitt und die bisherigen Gruppenfarben. Anklicken klappt Untergruppen auf/zu; Blattgruppen öffnen die bisherige Skill-Liste. Verschieben per Ziehen der Fläche oder Pfeiltasten; Zoom per Mausrad, +/− oder Schaltflächen; Einpassen zeigt alle geöffneten Gruppen. Reset klappt auf die oberste Ebene zurück, Start aktiviert zusätzlich wieder die ungefilterte Ausgangsanalyse und Graphansicht.

Technik: Vue/SVG mit radialer Gruppenanordnung, keine neue Abhängigkeit. Gemeinsame Berechnung in `taxonomy.ts`, reine Anordnung in `taxonomyGraph.ts`, Darstellung in `TaxonomyGraph.vue`. Keine API- oder Datenbankänderung. F-02 wurde auf Nutzeranweisung in Excel geändert und die Lesefassung neu erzeugt; F-03/F-06/F-10/F-11 und S-08 bleiben berücksichtigt. Der ursprüngliche Fachstand ist über Git nachvollziehbar.

Tatsächlich geprüft am 20.09.2026: `npm test` (20 erfolgreich), `npm run build` (TypeScript/Vite erfolgreich), 13 bestehende Edge-Browsertests unter XAMPP erfolgreich sowie drei neue Graph-Browsertests im abschließenden gezielten Lauf (`npm run test:e2e -- e2e/taxonomy-graph.spec.ts`). Die neuen Tests prüfen Auf-/Zuklappen, Zahlen, Baumwechsel, Blattlisten, Zurück/Reset, Tastatur, Zoom/Verschieben, Masken/Simulation sowie synthetisch mehrere Wurzeln, leere Gruppen und direkt zugeordnete Skills innerer Gruppen. Zwei anfängliche Fehler im neuen Test (falsche Besitzannahme und Auswahlwert für „Keiner“) wurden korrigiert. Desktop-/Mobil-Screenshots wurden visuell geprüft. Excel-/Markdown-Abgleich erfolgreich. Build/Testprozesse benötigten wegen `spawn EPERM` die Ausführung außerhalb der Sandbox. Backendtests wurden für diese reine Darstellungsänderung nicht erneut ausgeführt; Echtdaten, große Taxonomien und weitere Browser bleiben ungeprüft.

## Ablage im Repository

Die folgenden Pfade sind relativ zum Projektordner. `docs/` ist ein Unterordner des Repositorys, kein Verzeichnis direkt unter dem Betriebssystem-Laufwerk.

| Pfad | Inhalt / Verwendung |
|---|---|
| `README.md` | Einstieg, Ablage und später verifizierte Einrichtungs-/Start-/Testbefehle |
| `AGENTS.md` | Arbeitsanweisungen für den KI-Agenten im Projektstamm |
| `docs/Projektbeschreibung.md` | Kurze Projektbeschreibung und Umfangsgrenzen |
| `docs/Anforderungskatalog_16092026.xlsx` | Führender Anforderungskatalog; Dateiname historisch, Inhaltsstand 18.09.2026 |
| `docs/Anforderungskatalog_16092026.md` | Automatisch erzeugte, textgleiche Lesefassung der Anforderungen für den Agenten |
| `docs/Use-Cases_und_Entscheidungen(1).md` | Bedienabläufe, Abnahmebeispiele, Referenzzuordnung und Änderungsnachweis |
| `docs/wireframes/*.png` | Alle bereitgestellten Wireframes unverändert, einschließlich ausdrücklich als historisch eingeordneter Graphreferenz |
| `database/31082026_Structure_skilltree_db.sql` | Vorhandener Struktur-Export ohne Echtdaten, unverändert |
| `src/` | PHP-Anwendungscode; Fachlogik und Datenzugriff werden hier getrennt aufgebaut |
| `src/Domain/Analysis/` | Reine Fachberechnung und Prüfung der Skillvoraussetzungen |
| `src/Infrastructure/Database/AnalysisRepository.php` | Lesendes Laden einer konsistenten Datenbasis der Organisationseinheit |
| `src/Http/AnalysisAction.php` | JSON-Ausgabe und HTTP-Fehlerbehandlung der Kernberechnung |
| `scripts/check-analysis.php` | Expliziter, ausschließlich lesender Abgleich mit dem synthetischen Testdatensatz |
| `scripts/extend-testdata-dag.php` | Einmalige, abgesicherte Ergänzung des ursprünglichen 20-Skill-Datensatzes um sechs Skills und zwölf Voraussetzungen |
| `public/api/` | HTTP-Einstiegspunkt der Slim-API für XAMPP |
| `frontend/` | Vue-/TypeScript-Quellcode, Vite-Konfiguration und Frontendtests |
| `tests/` | PHPUnit-Tests des Backends |
| `.env.example` | Vorlage ohne Zugangsdaten für die lokale, von Git ausgeschlossene `.env` |
| `.env.setup.example` | Vorlage für den getrennten, schreibberechtigten Testdatenbank-Zugang |

Die Markdown-Dateien werden als UTF-8 gespeichert. PNGs können gemeinsam im Wireframe-Ordner liegen; ihre Geltung wird im Use-Case-Dokument erläutert. Eine Umwandlung der Bilder in PDF oder Word ist nicht erforderlich.

Der Excel-Katalog wird nur einmal gepflegt. Die Markdown-Datei ist ein abgeleiteter Export und keine zweite Spezifikation. Nach ausdrücklich freigegebenen Katalogänderungen muss sie neu aus der Excel erzeugt werden; IDs, Prioritäten und Wortlaut müssen übereinstimmen. Für diesen Ausgangsstand wurde diese Übereinstimmung geprüft.

## Technische Einrichtung

Geprüfte lokale Umgebung am 18.09.2026:

| Werkzeug | Geprüfte Version |
|---|---:|
| XAMPP for Windows | 8.2.12 (bereitgestellte Paketversion) |
| Apache | 2.4.58 |
| PHP | 8.2.12, einschließlich PDO und `pdo_mysql` |
| MariaDB | 10.4.32 |
| Composer | 2.10.3 |
| Node.js | 25.9.0 |
| npm | 11.12.1 |
| Git | 2.51.0.windows.1 |

Festgelegter Stack: PHP 8.2 mit Slim 4 und PDO für API und Datenzugriff; Vue 3 mit TypeScript und Vite für das Frontend; Cytoscape.js für den späteren Entwicklungs-DAG; PHPUnit und Vitest für automatisierte Tests. Die installierten konkreten Paketversionen stehen reproduzierbar in `composer.lock` und `frontend/package-lock.json`.

### Lokale Konfiguration

1. `.env.example` im Projektstamm nach `.env` kopieren.
2. In `.env` ausschließlich die lokalen Zugangsdaten eintragen. Für den Anwendungsbetrieb ist ein auf `SELECT` in `testdata_skilltree` begrenzter MariaDB-Benutzer vorgesehen.
3. `.env` niemals committen. Der Pfad ist in `.gitignore` ausgeschlossen; `.env.example` enthält nur Platzhalter.

Benötigte Variablen: `APP_ENV`, `APP_DEBUG`, `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` und `DB_CHARSET`.

Für bewusst ausgeführte Schema- und Testdatenschritte wird getrennt davon `.env.setup` verwendet. Diese Datei enthält ausschließlich den auf `testdata_skilltree` begrenzten Benutzer `skilltree_test_setup`, wird ebenfalls von Git ausgeschlossen und darf nicht von der laufenden Anwendung geladen werden. Ihre Vorlage ist `.env.setup.example`.

### Installation, Build und Prüfung

Im Projektstamm:

```powershell
composer install
composer test
```

Im Verzeichnis `frontend/`:

```powershell
npm install
npm run typecheck
npm test
npm run build
```

Der Frontend-Build wird nach `public/` geschrieben. Bei laufendem Apache ist die Anwendung anschließend unter `http://localhost/skilltree/public/` erreichbar. Die technischen Prüfpunkte liegen unter:

- `http://localhost/skilltree/public/api/health`
- `http://localhost/skilltree/public/api/health/database`

Der Datenbank-Prüfpunkt gibt nur Status und Datenbanknamen zurück, keine Zugangsdaten oder internen Fehlermeldungen.

| Bereich | Aktueller Status |
|---|---|
| Abhängigkeiten installieren | `composer install` und `npm install` erfolgreich geprüft |
| Datenbank bereitstellen | `testdata_skilltree` mit Schema und synthetischem Datensatz vorhanden; Soll/Ist und Ampel lesend geprüft |
| Anwendung starten | Technischer Rahmen über XAMPP unter `http://localhost/skilltree/public/` erreichbar |
| Frontend bauen | `npm run build` erfolgreich geprüft |
| Automatisierte Tests ausführen | Fachtests: `composer test`; lesender Seedabgleich: `composer test:database`; Frontend: `npm test`; lokale Edge-Klicktests: `npm run test:e2e` (aus `frontend/`, nach Build, Apache/MariaDB aktiv) |

### Schritt 3 ohne Dashboard testen

Bearbeitet in Schritt 3: D-07, D-08 und A-01 bis A-05. Zusätzlich berücksichtigt die Berechnung G-09/G-12/G-13 (eindeutige Skills, DAG ohne Zyklen, UND-Voraussetzungen), F-09 (leere Mengen), A-15 (keine Speicherung) und den gemeinsamen Berechnungsstand nach A-16. Diese direkte API-Prüfung funktioniert weiterhin unabhängig von der inzwischen ergänzten Oberfläche.

Bei laufendem XAMPP-Apache und MariaDB im Browser öffnen:

`http://localhost/skilltree/public/api/analysis`

Die Antwort ist JSON. Unter `summary` müssen für den unveränderten Testdatensatz stehen:

```json
{
  "task_count": 10,
  "employee_count": 5,
  "required_skill_count": 26,
  "available_skill_count": 23,
  "counts": { "red": 3, "yellow": 7, "green": 16 }
}
```

`skills` enthält ausschließlich Soll-Skills, jeweils mit `carrier_count`, `employee_ids` und `status`. Beispielsweise: Datenanalyse = 3 / green, Budgetplanung = 1 / yellow, Krisenkommunikation und Datenmigration = 0 / red. Die vollständige Antwort wird bei jedem Aufruf aus der Datenbank neu berechnet. Die Feldbeschreibung steht bei UC-01 im Use-Case-Dokument.

Alternativ in PowerShell im Projektstamm:

```powershell
$analysis = Invoke-RestMethod 'http://localhost/skilltree/public/api/analysis'
$analysis.summary
$analysis.skills | Format-Table id, name, carrier_count, status
composer test
composer test:database
```

`composer test` läuft ohne Datenbank und ohne Einlesen lokaler Zugangsdaten. Getestet werden mehrstufige Voraussetzungen, gemeinsame Knoten, Mehrfachzuordnungen, Ampelgrenzen einschließlich genau zwei Wissensträgern, Ist-Skills außerhalb des Solls, leere Mengen, ungültige Graphen und HTTP-Antworten. Ein rein synthetischer Test mit 12.000 verketteten Skills prüft, dass keine willkürliche Tiefengrenze eingeführt wurde.

`composer test:database` lädt `.env` intern, verlangt den lokalen Datenbanknamen `testdata_skilltree` und den Benutzer `skilltree_test_reader` und vergleicht alle 26 Skillbewertungen, Wissensträger, zwölf DAG-Kanten und transitiven Aufgabenmengen mit dem vereinbarten Seed. Er prüft zusätzlich, dass die sechs neuen Skills weder Aufgaben noch Mitarbeitenden direkt zugeordnet sind und trotzdem im Soll beziehungsweise im impliziten Ist erscheinen. Er führt nur lesende Abfragen aus und importiert oder verändert nichts. Nach bewusster Änderung des Seeds müssen seine erwarteten Werte entsprechend angepasst werden.

Unter XAMPP tatsächlich geprüft: HTTP 200 am Analyse-Endpunkt, JSON/UTF-8, `Cache-Control: no-store` und die oben angegebenen Werte. Voraussetzungsketten und gemeinsame Grundlagen sind sowohl mit Arbeitsspeicher-Testdaten als auch mit dem erweiterten MariaDB-Testdatensatz geprüft. Dashboard und Navigation wurden anschließend in Schritt 4 vom Nutzer abgenommen.

### Schritte 5/6 im Browser prüfen

Öffnen: `http://localhost/skilltree/public/` (gegebenenfalls Strg+F5). Ein weiterer Entwicklungsschritt ist zum Testen nicht erforderlich.

1. **Kritisch → Datenmigration aufklappen:** Ben Berger (Distanz 2), David Dreher (2), Anna Adler (3). Ben oder seinen Pfadlink anklicken: persönlicher verbleibender DAG, Distanz 2. Vollständig behält die Distanz; Allgemein zeigt den neutralen vollständigen DAG. Zurück erhält die geöffnete Kandidatenbox.
2. **Handlungsbedarf → Budgetplanung:** Anna Adler ist einzige Trägerin und ausgeschlossen; Ben, Carla und David haben Distanz 3. Empfehlung zur Prüfung zusätzlicher/externer Maßnahmen erscheint.
3. **Unkritisch:** 16 Zeilen, jeweils Skill zuerst und Trägernamen danach. Datenanalyse anklicken: allgemeiner DAG. Anna wählen: Distanz 0, verbleibend ein grüner Zielknoten; vollständig vier vorhandene Knoten.
4. **Start → Testtaxonomie → Datenkompetenzen → Daten verarbeiten und analysieren → Analyse und Daten:** vier Gruppenebenen, danach Skillliste. Datenverständnis öffnet als geschätzte Grundlage einen einzelnen Zielknoten. Personenauswahl in der Taxonomie wird beim Skillaufruf übernommen.
5. **Organisationsmaske deaktivieren:** Geodatenkartierung wird zusätzlich neutral sichtbar. Anklicken zeigt die schließbare Warnung statt eines erfundenen Pfads. Die anderen beiden freien Testskills liegen in den anderen Blattgruppen. Dashboard bleibt 3/7/16.
6. **DAG-Steuerung:** mit Maus verschieben/zoomen oder Pfeiltasten und +/− verwenden. Reset passt den Graphen ein, ohne Personenbezug/Pfadumfang zu ändern. Die aufklappbare Textdarstellung enthält dieselben Knoten und Kanten.

Die API liefert Kategorieempfehlungen, Pfadverfügbarkeit und Kanten zusammen mit Dashboard und Taxonomie in einer Antwort; Feldbeschreibung und Beispiele stehen bei UC-02 bis UC-08 im Use-Case-Dokument. Neue Fachlogik: `src/Domain/Analysis/DevelopmentAnalysis.php`; DAG-Projektion: `frontend/src/domain/development.ts`; Darstellung: `CategoryOverview.vue` und `DevelopmentGraph.vue`. Cytoscape 3.34.3 war bereits installiert; keine neue Laufzeitabhängigkeit wurde für Schritte 5/6 ergänzt. Playwright 1.63.0 nutzt den vorhandenen lokalen Edge für Browsertests. Der Vite-Entwicklungsserver leitet `/api` an den lokalen XAMPP-Pfad weiter; der ausgelieferte Build benötigt keinen Node-Server.

Der zusätzliche synthetische Datenstand enthält **29 Skills, zehn Gruppen und vier Hierarchieebenen**, weiterhin 26 Soll-/23 Ist-Skills und zwölf DAG-Kanten. Neue Skills 27–29 sind komplett unzugeordnet und nicht geschätzt. `php scripts/extend-testdata-taxonomy.php --apply` wurde einmalig transaktional auf `testdata_skilltree` ausgeführt; ein erneuter Aufruf wird abgelehnt. Ohne Flag erfolgt nur die Ausgangsprüfung. `database/testdata_seed.sql` enthält denselben Erweiterungsblock zur Reproduktion. Die früheren Angaben zum 26-Skill-DAG unten beschreiben dessen unveränderte fachliche Teilmenge.

Katalogpflege: `powershell -NoProfile -File scripts/export-requirements.ps1 -Check` prüft alle 54 Anforderungen gegen Excel. Ohne Flag regeneriert das Skript die Lesefassung. `-ApplyGreenRevision` ist ausschließlich die dokumentierte A-07-Änderung vom 19.09.2026, kein allgemeiner Editor.

Tatsächlich geprüft am 19.09.2026:

- `composer test`: 25 Tests, 96 Assertions erfolgreich.
- `composer test:database`: Soll/Ist, alle Bewertungen, zwölf Kanten, vier Gruppenebenen, freie Katalogskills und Kandidaten für Datenmigration erfolgreich lesend geprüft.
- `npm test`: 16 Tests erfolgreich (Masken/Farbvererbung, Distanz, Restpfad, gemeinsame Anschlussknoten, vollständige/neutralisierte Ansichten und Pfadverfügbarkeit).
- `npm run build`: TypeScript und Vite erfolgreich; Cytoscape wird erst beim ersten Pfadaufruf nachgeladen.
- `npm run test:e2e`: sechs Edge-Browsertests unter XAMPP erfolgreich, einschließlich realem Datenbank-Snapshot, Navigation, Kandidaten, allgemeinem/persönlichem DAG, Warnbox und grüner Tabelle. Leeres Soll, API-Ausfall und unbesetzte Kandidatenplätze werden im Browser durch abgefangene synthetische API-Antworten geprüft, ohne Datenbankänderung.
- Excel-/Markdown-Abgleich und `git diff --check` erfolgreich; lokale Zugangsdaten und Browser-Testartefakte bleiben von Git ausgeschlossen.

Zum damaligen Prüfstand noch nicht geprüft: Echtdaten/Produktivdatenbank, Leistungsgrenzen mit realen großen ESCO-Pfaden, andere Browser und die erst anschließend implementierte Simulation. Visuelle Feinabstimmung ist auf Nutzerwunsch nach den Grundfunktionen vorgesehen. Browserbilder wurden lokal kontrolliert; sie ersetzen nicht die persönliche Abnahme der Wireframe-Nähe.

### Schritt 7: Simulation und vollständige Anforderungsprüfung

Bei laufendem Apache/MariaDB die Anwendung öffnen und gegebenenfalls mit Strg+F5 neu laden. Das Simulationssymbol öffnet Aufgaben und Mitarbeitende als Checkboxlisten. Abwählen simuliert einen Ausfall; die Datenbank wird dabei nicht verändert. Schließen/Escape erhält die Auswahl. Ausgeschlossene Personen fehlen bei Trägern, Kandidaten und persönlicher Auswahl. Bei Ausschluss der ausgewählten Person wird ein geöffneter DAG allgemein.

| Prüfung mit dem aktuellen Testdatensatz | Erwartung Rot/Gelb/Grün |
|---|---|
| Ohne Ausschlüsse | 3 / 7 / 16 |
| Anna Adler ausschließen | 6 / 4 / 16 |
| Nur Beschaffung vorbereiten ausschließen | 3 / 3 / 16 |
| Alle Mitarbeitenden ausschließen | 26 / 0 / 0; drei unbesetzte Kandidatenplätze |
| Alle Aufgaben ausschließen (mit oder ohne Personen) | 0 / 0 / 0; vollständige Taxonomie bleibt zugänglich |

Zurück erhält die **aktuelle** Simulation und stellt die vorherige Mainframe-Ansicht wieder her. Reset verändert nur die Ansicht, nicht Maske, Person, Pfadumfang oder Ausschlüsse. Start schließt das Menü, leert die Historie, setzt Masken/Person/Ansicht zurück und lädt eine ungefilterte Analyse. Neuladen beziehungsweise ein neues Fenster beginnt ebenfalls ungefiltert. Fehlgeschlagene Berechnungen verändern den bisherigen wirksamen Stand nicht; ein sichtbarer Fehler erlaubt Wiederholung. Verspätete Antworten werden verworfen.

API: `GET /api/analysis?excluded_tasks=9&excluded_employees=1`. Parameter optional, positive kommagetrennte IDs; fehlerhafte/unbekannte Filter liefern 400. Das Antwortfeld `simulation` enthält die bestätigten Ausschlüsse und vollständigen Wiederauswahllisten. Feldvertrag, Beispiele und Detailentscheidungen stehen bei UC-09/UC-10 im Use-Case-Dokument. Keine serverseitige Sitzung, Browser-Speicherung, neuen Pakete oder DB-Schreibrechte erforderlich.

Prüfbefehle bleiben `composer test`, `composer test:database` und in `frontend/` `npm test`, `npm run build`, `npm run test:e2e`. Der vollständige Abnahmebericht ordnet jede der 54 Anforderungen einem Nachweis oder einer verbleibenden Einschränkung zu. Insbesondere ist G-04 mangels eingebundener Praxisdaten noch **nicht vollständig erfüllt**; D-10/D-11 sind hinsichtlich der echten Datenaufbereitung/ESCO-Zuordnung nicht technisch abgenommen.

Tatsächlich erfolgreich geprüft am 20.09.2026: 32 Backendtests mit 159 Assertions, lesender Datenbanktest einschließlich Simulation, 20 Frontendtests, TypeScript-/Vite-Build und 13 Edge-Browsertests unter XAMPP. Excel und Lesefassung stimmen für alle 54 Anforderungen überein. Die Browsertests umfassen auch Fehler/Wiederholung, Zurück/Reset/Start, Neuladen/neues Fenster und vierstellige Dashboardzahlen auf Desktop und Mobilansicht. Datenbankinhalte wurden nicht verändert. Details und manuelle Abnahme: [Abnahme Schritt 7](docs/Abnahme_Schritt7.md).

## Datenbank und erste fachliche Prüfung

Der SQL-Export enthält löschende Tabellenanweisungen. Ein Import erfolgt ausschließlich in die dafür identifizierte Entwicklungs-/Testdatenbank; die vorbereitete Originaldatenbank bleibt erhalten. Ein Git-Commit sichert keine Datenbankinhalte.

Für die Entwicklungsdatenbank liegt unter `database/testdata_seed.sql` ein rein synthetischer, reproduzierbarer Testdatensatz vor. Er ersetzt beim Import alle vorhandenen Inhalte der acht Fachtabellen und darf deshalb ausschließlich gegen die eindeutig geprüfte Datenbank `testdata_skilltree` ausgeführt werden. Enthalten sind eine Organisationseinheit, fünf fiktive Mitarbeitende, zehn Aufgaben und 29 Skills mit zwölf Entwicklungsvoraussetzungen. Davon sind 20 Skills direkt und sechs ausschließlich implizit im Soll; drei weitere liegen vollständig außerhalb von Soll/Ist. Erwartete organisationsbezogene Ampel: 3 rot, 7 gelb und 16 grün; eindeutiger Ist-Bestand: 23 Skills.

Die DAG-Ergänzung wurde auf ausdrücklichen Wunsch nach Schritt 3 vorgenommen. Die ursprünglichen 20 Skills, 20 Aufgaben-Skill-Zuordnungen und 45 Mitarbeiter-Skill-Zuordnungen bleiben unverändert. Der frühere Stand ohne Voraussetzungen ergab 2/5/13; die zusätzlich indirekt benötigten Skills ergeben 1/2/3 und damit insgesamt 3/7/16. Es handelt sich um synthetische Testannahmen, keine fachlich validierte Schätzung der realen Organisation.

Die sechs zusätzlichen Skills sind:

| ID | Skill | Implizite Wissensträger | Ampel |
|---:|---|---:|---|
| 21 | Datenverständnis | 5 | Grün |
| 22 | Statistische Grundlagen | 5 | Grün |
| 23 | Datenaufbereitung | 3 | Grün |
| 24 | Migrationsplanung | 0 | Rot |
| 25 | Kostenrechnung | 1 (Anna Adler) | Gelb |
| 26 | Rechengrundlagen | 1 (Anna Adler) | Gelb |

Die Kette `Datenverständnis → Rechengrundlagen → Kostenrechnung → Budgetplanung` verläuft von der Voraussetzung zum abhängigen Skill. Aufgabe 9 fordert direkt nur Budgetplanung und Vergaberecht, ihr vollständiges Soll enthält zusätzlich die IDs 21, 25 und 26. Die API liefert diese unter `tasks[].required_skill_ids`; alle ursprünglichen direkten Zuordnungen bleiben unter `direct_skill_ids` erkennbar. Ein vollständiges Diagramm der gespeicherten Abhängigkeiten steht beim technischen UC-01 im Use-Case-Dokument.

Für eine noch vorhandene ursprüngliche Testbasis ohne DAG kann `php scripts/extend-testdata-dag.php` rein lesend die Voraussetzungen prüfen; `php scripts/extend-testdata-dag.php --apply` ergänzt danach atomar genau sechs Skills und zwölf Kanten. Das Skript verwendet `.env.setup`, prüft Ziel und Ausgangsdaten, importiert ausschließlich den markierten Ergänzungsblock aus der Seed-Datei und prüft Zyklusfreiheit und Ampel vor dem Commit. Es wurde auf der lokalen Testdatenbank bereits erfolgreich ausgeführt und lehnt einen erneuten Aufruf auf dem erweiterten Bestand ab. Der vollständige, zurücksetzende Seed-Import wurde für diese Erweiterung nicht ausgeführt.

Maßgebliche weitere erwartete Ergebnisse stehen im Use-Case-Dokument: implizite Soll-/Ist-Skills, eindeutige Zählung, Distanz einschließlich Zielskill, Pfadverfügbarkeit auch impliziter Grundlagenskills, Kandidatenplätze, Simulation und Navigation. Die Prüfung der globalen Datenqualität wurde vom Nutzer berichtet; eine erneute Datenprüfung ist im Dokumentationspaket nicht enthalten.

## Persönliches Archiv und Übergabe

Frühere Excel-Stände, der ursprüngliche ausführliche Übergabetext, Chatverläufe, verworfene Entwürfe und Notizen für die Bachelorarbeit können außerhalb des aktiven Repositorys privat archiviert werden. Sie müssen dem Agenten nicht als weitere aktuelle Vorgaben vorliegen. Die fünf aktuellen Dokumente, die erzeugte Lesefassung, das Schema und die PNG-Referenzen gehören in die oben bezeichnete Projektablage.

Das eingefrorene Ausgangspaket dient als Referenz. Nach Einrichtung von Git sollte dieser Stand als erster nachvollziehbarer Commit gesichert werden. Spätere ausdrücklich vereinbarte fachliche Änderungen müssen als Änderungen erkennbar bleiben; gewöhnliche Implementierungsdetails eröffnen keine neue Konzeptionsrunde.

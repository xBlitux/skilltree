# Skillbasiertes Personalentwicklungstool

Webbasiertes Minimum Viable Artifact zur Unterstützung von Personalbedarfs-, Wissensbewahrungs- und Personalentwicklungsentscheidungen im Rahmen einer Bachelorarbeit.

**Fachstand:** eingefroren am 18.09.2026.  
**Technischer Stand dieses Pakets:** Kernberechnung für transitive Soll-/Ist-Skills, eindeutige Wissensträgerzählung und Ampel ist implementiert und über die Slim-API prüfbar. Vue-/TypeScript-Frontend und Testgerüst sind eingerichtet; Dashboard und Taxonomienavigation folgen in Schritt 4.
**Zeitbudget:** etwa 6–7 Tage Umsetzung.

## Einstieg

Die Anwendung vergleicht Aufgabenbedarf und Skillbestand einer vorbereiteten Organisationseinheit, zeigt eine Managementampel und ermittelt Entwicklungskandidaten. Die Taxonomie ist ein aufklappbares Inhaltsverzeichnis mit Skill-Listen. Ein separater Entwicklungs-DAG bleibt Bestandteil des MVA. Die fachlichen Einzelregeln stehen im Katalog und in den Use-Cases.

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
| Automatisierte Tests ausführen | `composer test`: 19 Tests / 56 Assertions erfolgreich; `composer test:database`: synthetischer Datensatz erfolgreich geprüft. Frontend-Basistest aus Schritt 1 unverändert |

### Schritt 3 ohne Dashboard testen

Bearbeitet: D-07, D-08 und A-01 bis A-05. Zusätzlich berücksichtigt die Berechnung G-09/G-12/G-13 (eindeutige Skills, DAG ohne Zyklen, UND-Voraussetzungen), F-09 (leere Mengen), A-15 (keine Speicherung) und den gemeinsamen Berechnungsstand nach A-16. Die Oberfläche, Simulation und Entwicklungskandidaten sind noch nicht implementiert.

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

Unter XAMPP tatsächlich geprüft: HTTP 200 am Analyse-Endpunkt, JSON/UTF-8, `Cache-Control: no-store` und die oben angegebenen Werte. Voraussetzungsketten und gemeinsame Grundlagen sind sowohl mit Arbeitsspeicher-Testdaten als auch mit dem erweiterten MariaDB-Testdatensatz geprüft. Eine visuelle Abnahme von Dashboard und Navigation folgt erst in Schritt 4.

## Datenbank und erste fachliche Prüfung

Der SQL-Export enthält löschende Tabellenanweisungen. Ein Import erfolgt ausschließlich in die dafür identifizierte Entwicklungs-/Testdatenbank; die vorbereitete Originaldatenbank bleibt erhalten. Ein Git-Commit sichert keine Datenbankinhalte.

Für die Entwicklungsdatenbank liegt unter `database/testdata_seed.sql` ein rein synthetischer, reproduzierbarer Testdatensatz vor. Er ersetzt beim Import alle vorhandenen Inhalte der acht Fachtabellen und darf deshalb ausschließlich gegen die eindeutig geprüfte Datenbank `testdata_skilltree` ausgeführt werden. Enthalten sind eine Organisationseinheit, fünf fiktive Mitarbeitende, zehn Aufgaben und 26 Skills mit zwölf Entwicklungsvoraussetzungen. Davon sind 20 Skills direkt und sechs ausschließlich implizit im Soll. Erwartete organisationsbezogene Ampel: 3 rot, 7 gelb und 16 grün; eindeutiger Ist-Bestand: 23 Skills.

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

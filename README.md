# Skillbasiertes Personalentwicklungstool

Webbasiertes Minimum Viable Artifact zur Unterstützung von Personalbedarfs-, Wissensbewahrungs- und Personalentwicklungsentscheidungen im Rahmen einer Bachelorarbeit.

**Fachstand:** eingefroren am 18.09.2026.  
**Technischer Stand dieses Pakets:** Technischer Projektrahmen mit Slim-API, Vue-/TypeScript-Frontend, automatisierten Basistests und geprüfter lesender Verbindung zur lokalen Testdatenbank. Die Fachfunktionen sind noch nicht implementiert.
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
| Datenbank bereitstellen | `testdata_skilltree` vorhanden und lesend erreichbar; Schema und Beispieldaten noch nicht eingespielt |
| Anwendung starten | Technischer Rahmen über XAMPP unter `http://localhost/skilltree/public/` erreichbar |
| Frontend bauen | `npm run build` erfolgreich geprüft |
| Automatisierte Tests ausführen | `composer test` und `npm test` erfolgreich geprüft; bisher nur technische Basistests |

## Datenbank und erste fachliche Prüfung

Der SQL-Export enthält löschende Tabellenanweisungen. Ein Import erfolgt ausschließlich in die dafür identifizierte Entwicklungs-/Testdatenbank; die vorbereitete Originaldatenbank bleibt erhalten. Ein Git-Commit sichert keine Datenbankinhalte.

Der Projektverantwortliche stellt kleine Beispieldaten bereit. Maßgebliche erwartete Ergebnisse stehen im Use-Case-Dokument: implizite Soll-/Ist-Skills, eindeutige Zählung, Distanz einschließlich Zielskill, Pfadverfügbarkeit auch impliziter Grundlagenskills, Kandidatenplätze, Simulation und Navigation. Die Prüfung der globalen Datenqualität wurde vom Nutzer berichtet; eine erneute Datenprüfung ist im Dokumentationspaket nicht enthalten.

## Persönliches Archiv und Übergabe

Frühere Excel-Stände, der ursprüngliche ausführliche Übergabetext, Chatverläufe, verworfene Entwürfe und Notizen für die Bachelorarbeit können außerhalb des aktiven Repositorys privat archiviert werden. Sie müssen dem Agenten nicht als weitere aktuelle Vorgaben vorliegen. Die fünf aktuellen Dokumente, die erzeugte Lesefassung, das Schema und die PNG-Referenzen gehören in die oben bezeichnete Projektablage.

Das eingefrorene Ausgangspaket dient als Referenz. Nach Einrichtung von Git sollte dieser Stand als erster nachvollziehbarer Commit gesichert werden. Spätere ausdrücklich vereinbarte fachliche Änderungen müssen als Änderungen erkennbar bleiben; gewöhnliche Implementierungsdetails eröffnen keine neue Konzeptionsrunde.

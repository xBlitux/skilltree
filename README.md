# Skillbasiertes Personalentwicklungstool

Webbasiertes Minimum Viable Artifact zur Unterstützung von Personalbedarfs-, Wissensbewahrungs- und Personalentwicklungsentscheidungen im Rahmen einer Bachelorarbeit.

**Fachstand:** eingefroren am 18.09.2026.  
**Technischer Stand:** Schritte 1–7 sind implementiert: Kernberechnung, Dashboard, hierarchische Taxonomie, Kategorieübersichten, Entwicklungskandidaten, allgemeiner/personenbezogener Entwicklungs-DAG sowie temporäre Simulation mit Zurück/Reset/Start. A-07 wurde auf ausdrücklichen Nutzerwunsch am 19.09.2026 zur skillbezogenen grünen Tabelle geändert. Die Anforderungsprüfung vom 20.09.2026 einschließlich offener Echtdaten-/Evaluationspunkte steht in [docs/Abnahme_Schritt7.md](docs/Abnahme_Schritt7.md).
**Zeitbudget:** etwa 6–7 Tage Umsetzung.

## Aktueller Datenbankstand: m:n-Zuordnungen (24.09.2026)

Auf ausdrücklichen Nutzerwunsch wurde die **leere lokale Strukturkopie `rework_skilltree`**
unter XAMPP/MariaDB 10.4.32 umgebaut. Die lokale `.env` verweist bereits auf diese
Datenbank mit dem bereitgestellten Konto `db_architect`; Zugangsdaten wurden nicht
geändert oder in Git aufgenommen. Andere Datenbanken wurden nicht verändert.
Die Anwendung benötigt ab diesem Stand das neue Schema.

- `organisation_unit_employee`: eindeutige Mitgliedschaften `(organisation_unit_id, employee_id)`.
- `organisation_unit_task`: eindeutige Aufgabenzuordnungen `(organisation_unit_id, task_id)`.
- `employee` und `task` enthalten keine `organisation_unit_id` mehr.
- `employee_skill`: Primärschlüssel `(organisation_unit_id, employee_id, skill_id)`;
  zusammengesetzter Fremdschlüssel auf die Mitgliedschaft. Derselbe Skill darf je
  Person in mehreren Einheiten separat gespeichert werden. Fehlender Eintrag bedeutet
  fehlenden direkten Besitz; implizite Voraussetzungen werden weiterhin hergeleitet.
- `task_skill` bleibt global und gilt identisch in allen zugeordneten Einheiten.
- `scenario_task` enthält ebenfalls `organisation_unit_id`. Zusammengesetzte
  Fremdschlüssel stellen sicher, dass Szenario und Aufgabe derselben Einheit angehören.
  Referenzierte Zuordnungen lassen sich nicht versehentlich löschen oder umhängen
  (`RESTRICT`); abhängige Datensätze müssen zuerst ausdrücklich entfernt werden.

**Eigene Daten einpflegen:** Organisationen, Personen und Aufgaben anlegen, danach
die beiden Mitgliedschaftstabellen befüllen und erst dann die organisationsbezogenen
Mitarbeiter-Skills. Aufgaben-Skills werden einmal je Aufgabe hinterlegt. Für Szenarien
zuerst das organisationsgebundene Szenario anlegen, dann dessen Aufgaben mit derselben
Organisations-ID zuordnen. Es gibt keine automatische Übernahme von Mitarbeiter-Skills
beim Hinzufügen einer weiteren Mitgliedschaft. Einheiten dürfen zunächst leer sein.

Aktueller, datenfreier Export für Workbench:
[`database/24092026_Structure_rework_skilltree.sql`](database/24092026_Structure_rework_skilltree.sql).
Die beiden älteren Exporte bleiben historische Referenzen. Der neue Export enthält
keine `DROP TABLE`-Anweisungen und ist nur für eine leere Zieldatenbank gedacht.
Der einmalige Umbau liegt unter `database/migrations/20260924_organisation_memberships.sql`;
`php scripts/migrate-rework.php --apply` prüft das konkrete alte Schema und lehnt
gefüllte oder bereits migrierte Datenbanken ab. MariaDB-DDL besitzt implizite Commits;
die Ausgangsstruktur wurde deshalb vorab unter `.local/rework-before-*.sql` gesichert
(von Git ausgeschlossen). `php scripts/migrate-rework.php --export` erzeugt den aktuellen
Strukturexport; `php scripts/inspect-rework.php` zeigt Schema und Zeilenzahlen ohne Datenwerte.

Die PHP-Analyse lädt Mitgliedschaften, direkten Besitz und Szenarien im ausgewählten
Organisationskontext. API-Felder und Frontend-Bedienung bleiben kompatibel. Revisionen:
**G-05/G-06, D-07/D-08**, synchron in Excel, erzeugter Lesefassung und Use-Cases.
Abnahmebeispiel und API-Werte: Abschnitt „Vorher/Nachher“ im Use-Case-Dokument.

Reproduzierbare Prüfungen im Projektstamm:

```powershell
composer test
php scripts/check-rework.php
php scripts/check-rework.php --browser
powershell -NoProfile -File scripts/export-requirements.ps1 -Check
```

`check-rework.php` verlangt ausdrücklich die **vollständig leere `rework_skilltree`**.
Es fügt synthetische Testdaten vorübergehend ein, prüft den bisherigen Abnahmebestand,
m:n-Isolation und Constraints und entfernt danach ausschließlich seine Testzeilen,
auch bei einem regulären Testfehler. Der Browserlauf benötigt laufendes Apache/MariaDB,
Edge und einen aktuellen Build (`npm run build` in `frontend/`). Er führt die bestehenden
Klicktests und anschließend den neuen Vorher-/Nachher-Test aus. Während dieses Laufs
keine eigenen Daten einpflegen; nach Beginn der eigenen Datenpflege wird der Test
absichtlich abgelehnt. Ein hart beendeter Prozess kann seine Bereinigung nicht ausführen.
Der neue Browsertest wird außerhalb dieses Testablaufs übersprungen.

Die Seed-Dateien und `extend-testdata-organisations.php` verwenden ebenfalls das neue
Schema. Der historische `split-test-scenarios.php`-Umbau ist stillgelegt: Aus einer
Aufgabe lässt sich bei m:n keine eindeutige Organisation mehr ableiten. Die bisherigen
Sicherungen/Originaldatenbanken bleiben erhalten. `composer test:database` ist weiterhin
der lesende Test für einen entsprechend vorbereiteten `testdata_skilltree` mit Lesekonto;
für die aktuelle leere Strukturkopie dient `check-rework.php`. Die folgenden älteren
Abschnitte beschreiben historische Datenbank- und Prüfstände.

Am 24.09.2026 tatsächlich geprüft:

- `composer test`: **33 Tests, 216 Assertions** erfolgreich (PHP 8.2.12, PHPUnit 11.5.56).
- `php scripts/check-rework.php`: bisheriger Abnahmebestand inklusive aller 26
  Skillbewertungen, Vererbung, Kandidaten und Simulation erfolgreich; zusätzlich
  **51 Ziel-, Constraint- und m:n-Analyseprüfungen** erfolgreich unter MariaDB 10.4.32.
- `npm test` in `frontend/`: **20 Tests** erfolgreich; `npm run build`: TypeScript/Vite
  erfolgreich. Vite 7.3.6, Node 25.9.0, bestehende Abhängigkeiten verwendet.
- XAMPP/Edge: **28 bestehende Browsertests** erfolgreich mit `check-rework.php --browser`;
  der neue Vorher-/Nachher-Test anschließend erfolgreich mit
  `php scripts/check-rework.php --browser-rework`. Beim ersten neuen Testlauf wurde
  eine falsche Testannahme zum Auswahlwert „Keiner“ korrigiert; kein Anwendungsfehler.
  `--browser-rework` wiederholt gezielt nur diesen Browserablauf mit temporären Daten.
- PHP-Syntaxprüfung aller Projekt-PHP-Dateien, Excel-/Markdown-Abgleich aller **54**
  Anforderungen und `git diff --check` erfolgreich. XAMPP-Datenbankstatus: HTTP 200,
  `database: rework_skilltree`. Abschließende Zählung: **alle zwölf Tabellen leer**.

Build, Frontendtests und Edge benötigten wegen `spawn EPERM` die Ausführung außerhalb
der Sandbox. Noch nicht geprüft: die anschließend vom Nutzer einzupflegenden Echtdaten,
deren fachliche Qualität und Leistungsgrenzen sowie andere Browser. Eine Migration
bestehender gefüllter Datenbanken wurde weder ausgeführt noch freigegeben; der
Migrationsbefehl lehnt sie ab. Ohne eingepflegte Organisation zeigt die Anwendung
zunächst den bestehenden Fehlerzustand „Keine Organisationseinheit vorhanden“
(Analyse-API HTTP 409); die Struktur ist bereit zur eigenen Datenpflege.

## Erweiterungen vom 21.09.2026

Organisation oben wählen; Wechsel setzt die Ansicht vollständig zurück. **Matrix** bietet Mitarbeiter-Mehrfachauswahl und Pfadlänge. Im Simulationsmenü aktivieren bis zu **neun fortlaufend nummerierte Buttons** die Szenarien der gewählten Einheit; Hover zeigt den Szenarionamen. `scenario.organisation_unit_id` ist erforderlich. Die Beispieldaten enthalten drei eigene Szenarien je Einheit. Mitarbeiter-Dropdown und Simulationsdialog schließen auch per Außenklick, ohne die Auswahl zu verlieren. Rot/Gelb besitzen eine gemeinsame **maximale Distanz (1–5, Standard 3)**; weiter entfernte Kandidaten werden ausgeschlossen. Statuspunkte in Skill-Listen ohne Personenauswahl öffnen die Kategorie. Nicht geschätzte Pfade zeigen ein modales Popup mit unveränderter Scrollposition. Leere Taxonomiegruppen werden ausgeblendet.

Die Testdatenbank enthält nun drei synthetische Organisationseinheiten. Additive Einrichtung und Beispielwerte: [Featureideen](docs/Featureideen.md). Es wurden nur bestehende Anforderungen geändert (G-04/G-05, F-03/F-11, A-07/A-12, S-01), keine neuen IDs oder Prioritäten. Die folgenden älteren Prüfberichte beschreiben den jeweiligen historischen Stand.

Abschließend geprüft: 33 Backendtests (216 Assertions), 20 Frontendtests, 23 Edge-Browsertests unter XAMPP, lesender Datenbankabgleich aller drei Einheiten, TypeScript-/Vite-Build und Excel-/Markdown-Abgleich erfolgreich. Zum Ausprobieren `http://localhost/skilltree/public/` mit Strg+F5 neu laden. Echtdaten, große Matrixbestände und andere Browser bleiben ungeprüft.

Nach der Bedienergänzung erneut geprüft: 23 bestehende und drei neue Edge-Browsertests erfolgreich, außerdem Backend-/Frontendtests, Build und Datenbankabgleich. Die früher gemeinsam genutzten Beispielszenarien wurden in neun getrennte Szenarien aufgeteilt; Neuaufbau und einmalige Migration sind in [Featureideen](docs/Featureideen.md) beschrieben. Der vom Nutzer ergänzte Schemaexport bleibt unverändert.

## Markenanpassung vom 21.09.2026

Rein kosmetische Anpassung: weißes Logo aus `src/res/Zukunft-Logo.png`, Navigation mit Verlauf von Cyanblau `#009ee3` zu Magenta `#c40079`, sonst weiße/hellgraue Flächen, neutrale Schriftfarben und sparsame Cyan-Akzente. Der Taxonomiegraph hat einen weißen Hintergrund. Die fachlichen Statusfarben Rot/Gelb/Grün/Grau und ihre Zuordnung bleiben erhalten; Magenta wird ausschließlich im Navigationsverlauf eingesetzt.

Roboto liegt samt SIL-OFL-Lizenz in `frontend/src/assets/fonts/` und wird per `@font-face` eingebunden. Vite übernimmt Schrift und Logo beim Build nach `public/assets/`; der Browser lädt sie direkt von XAMPP. Keine externe Font-Anfrage oder Installation auf den Endgeräten erforderlich. Auch die Canvas-Beschriftungen im Entwicklungs-DAG verwenden Roboto. Quelle und Lizenz sind im Schriftordner dokumentiert. Für einen vollständigen Git-Stand gehören das verwendete Logo und der Schriftordner zu den Quellen.

Der Footer enthält zusätzlich den Copyright-Hinweis „Copyright 2011 The Roboto Project Authors“ und einen lokalen Link zur vollständigen SIL Open Font License 1.1. Vite liefert die unveränderte `OFL.txt` zusammen mit der Schrift in `public/assets/` aus. Die eingebundene Roboto-Version verwendet diese Lizenz, nicht Apache 2.0.

Betroffene Darstellung: F-01/F-02, F-06/F-10/F-11, A-03 bis A-07 und S-06 bis S-08. Anforderungen, API, Fachberechnung und Stammdaten wurden nicht geändert. Geprüfte Abnahmebeispiele: Dashboard 3/7/16, Taxonomie-Aufklappen/Masken, Kandidaten und allgemeiner/persönlicher DAG sowie Matrix und Simulation.

Prüfung unter XAMPP/Edge: `npm test` (20 erfolgreich), `npm run build` (TypeScript/Vite erfolgreich), `npm run test:e2e` (24 von 26 erfolgreich). Zwei bestehende Szenariotests scheitern an den aktuellen lokalen Daten: zehn statt neun Szenarien sowie doppelte Bezeichnung „Kernaufgaben“ in Einheit 1. Die API-Abweichung wurde lesend bestätigt; Daten und Tests wurden dafür nicht angepasst. Build/Testprozesse benötigten wegen `spawn EPERM` die Ausführung außerhalb der Sandbox.

Zusätzlich wurden Desktop-/Mobilansichten, Matrix, Taxonomie und Entwicklungs-DAG visuell geprüft. Eine Browserkontrolle bestätigt tatsächlich verwendetes Roboto, genau einen lokalen Font-Download mit HTTP 200, keine externen Anfragen und keinen horizontalen Seitenüberlauf bei 320/390/768/1440 Pixeln. Backendtests wurden für diese Darstellungsänderung nicht erneut ausgeführt; andere Browser und Echtdaten bleiben ungeprüft. Aufruf: `http://localhost/skilltree/public/`, gegebenenfalls mit Strg+F5 neu laden.

Nach der Skizze `src/res/Beispiel_Navigationsleiste.png` wurde die Navigation weiter vereinfacht: Organisationsname und Auswahlpfeil erscheinen als weißer Text ohne Kasten; „Start“ steht ebenfalls ohne Kasten oder Zusatzsymbol in der Leiste. Das native Auswahlmenü öffnet per Klick und schließt bei Außenklick oder Escape, wobei die gewählte Organisation erhalten bleibt. Darstellung zu G-04/G-05 und Navigation gemäß G-15/S-08; keine geänderte Fachregel. Erneut erfolgreich geprüft: `npm run build` sowie drei bestehende Edge-Browsertests (`npm run test:e2e -- e2e/dashboard.spec.ts e2e/extensions.spec.ts -g "dashboard|empty Soll|failed organisation"`). Zusätzliche Browserprüfung bestätigt Außenklick/Escape ohne Auswahlverlust und Desktop-/Mobilansichten ohne Seitenüberlauf bei 1440/390/320 Pixeln. Weitere Browser wurden für diesen Schritt nicht geprüft.

## Skillbeschreibungen beim Hover (24.09.2026)

Skillnamen in Skill-Liste, Matrix und grüner Ampelansicht zeigen vorhandene Beschreibungen als native Browser-Infotexte. In Rot/Gelb erscheinen diese nur am Skillnamen einer aufgeklappten Box. Fehlende, leere oder nur aus Leerraum bestehende Beschreibungen erzeugen keinen Infotext. Die vorhandenen Klickaktionen bleiben erhalten. Das vorhandene Datenbankfeld `skill.description` wird dafür lesend in `skills[]` und `taxonomy.skills[]` der Analyse-API übernommen. Darstellung zu F-07/A-07/A-08; API-Beispiel und Bedienregeln stehen bei UC-02/UC-05 im Use-Case-Dokument.

Erfolgreich geprüft: `composer test` (33 Tests, 216 Assertions), `npm run build` sowie unter XAMPP/Edge `npm run test:e2e -- e2e/skill-descriptions.spec.ts e2e/development.spec.ts e2e/skill-map.spec.ts` (acht Tests). Der neue Browsertest prüft das API-Feld und verwendet für Leerraum/null/fehlende Beschreibungen ausschließlich synthetische Browserantworten. Stammdaten wurden nicht verändert. Die Anzeigeverzögerung und Gestaltung des Infotexts bestimmt der Browser; andere Browser und Touch-Hover wurden nicht geprüft.

## Organisations-Dummy (24.09.2026)

„Hinzufügen...“ steht immer am Ende der Organisationsauswahl. Die Option öffnet nur
einen Hinweis auf zukünftige Erweiterbarkeit, geschlossen durch „Verstanden“,
Außenklick oder Escape. Organisation, Simulation und Ansicht bleiben erhalten;
der Dummy löst keinen API-Aufruf aus. Vorhandene Organisationen lassen sich weiterhin
wechseln. Bezug: G-04, G-15/S-08 und UC-01/UC-10; keine Stammdatenpflege.

Geprüft: `npm run build` erfolgreich; drei Edge-Browsertests unter XAMPP erfolgreich
(`npm run test:e2e -- e2e/organisation-dummy.spec.ts e2e/development.spec.ts e2e/extensions.spec.ts -g "organisation dummy|failed organisation|four-level taxonomy"`).
Abgedeckt: letzte Dropdownposition, wiederholtes Öffnen, alle drei Schließwege,
Fokusrückgabe, Erhalt der Simulation/Matrix ohne API-Anfrage, echter Organisationswechsel,
Fehler/Wiederholung sowie bisherige Skill-Warnung. Mobile Popupdarstellung visuell geprüft.
Backend und Datenbank wurden nicht geändert; andere Browser wurden nicht erneut geprüft.

## Einstieg

Die Anwendung vergleicht Aufgabenbedarf und Skillbestand einer vorbereiteten Organisationseinheit, zeigt eine Managementampel und ermittelt Entwicklungskandidaten. Die Taxonomie bietet seit dem freigegebenen Versuch vom 20.09.2026 einen aufklappbaren Gruppengraphen und das bisherige Inhaltsverzeichnis mit gemeinsamen Skill-Listen. Ein separater Entwicklungs-DAG bleibt Bestandteil des MVA. Die fachlichen Einzelregeln stehen im Katalog und in den Use-Cases.

### Graphansicht ausprobieren (20.09.2026)

Unter `http://localhost/skilltree/public/` gegebenenfalls mit Strg+F5 neu laden. Der Versuch startet mit **Graph**; **Baum** wechselt zum bisherigen Inhaltsverzeichnis und erhält geöffnete Gruppen. Kreise zeigen eindeutige Skillzahlen im aktuellen Maskenausschnitt und die bisherigen Gruppenfarben. Anklicken klappt Untergruppen auf/zu; Blattgruppen öffnen die bisherige Skill-Liste. Verschieben per Ziehen der Fläche oder Pfeiltasten; Zoom per Mausrad, +/− oder Schaltflächen; Einpassen zeigt alle geöffneten Gruppen. Reset klappt auf die oberste Ebene zurück, Start aktiviert zusätzlich wieder die ungefilterte Ausgangsanalyse und Graphansicht.

Technik: Vue/SVG mit radialer Gruppenanordnung, keine neue Abhängigkeit. Gemeinsame Berechnung in `taxonomy.ts`, reine Anordnung in `taxonomyGraph.ts`, Darstellung in `TaxonomyGraph.vue`. Keine API- oder Datenbankänderung. F-02 wurde auf Nutzeranweisung in Excel geändert und die Lesefassung neu erzeugt; F-03/F-06/F-10/F-11 und S-08 bleiben berücksichtigt. Der ursprüngliche Fachstand ist über Git nachvollziehbar.

Tatsächlich geprüft am 20.09.2026: `npm test` (20 erfolgreich), `npm run build` (TypeScript/Vite erfolgreich), 13 bestehende Edge-Browsertests unter XAMPP erfolgreich sowie drei neue Graph-Browsertests im abschließenden gezielten Lauf (`npm run test:e2e -- e2e/taxonomy-graph.spec.ts`). Die neuen Tests prüfen Auf-/Zuklappen, Zahlen, Baumwechsel, Blattlisten, Zurück/Reset, Tastatur, Zoom/Verschieben, Masken/Simulation sowie synthetisch mehrere Wurzeln, leere Gruppen und direkt zugeordnete Skills innerer Gruppen. Zwei anfängliche Fehler im neuen Test (falsche Besitzannahme und Auswahlwert für „Keiner“) wurden korrigiert. Desktop-/Mobil-Screenshots wurden visuell geprüft. Excel-/Markdown-Abgleich erfolgreich. Build/Testprozesse benötigten wegen `spawn EPERM` die Ausführung außerhalb der Sandbox. Backendtests wurden für diese reine Darstellungsänderung nicht erneut ausgeführt; Echtdaten, große Taxonomien und weitere Browser bleiben ungeprüft.

## Ablage im Repository

Zusätzliche Featureidee vom 21.09.2026: **Matrix** ergänzt Baum und Graph um eine Skill-/Mitarbeitermatrix mit Status, Besitz sowie Vorhanden/Benötigt/Lücke. Organisationsmaske und Simulation bleiben wirksam; der Mitarbeiterfilter begrenzt nur die sichtbaren Spalten. Skillname, Status und Besitzsymbol öffnen die jeweils passende Detailansicht. Umsetzung, Bedienregeln und Prüfungen: [Featureidee Skillmatrix](docs/Featureideen.md). Die Matrix war zunächst eine reine Featureidee. Die später ausdrücklich freigegebenen Änderungen bestehender Anforderungen vom 21.09.2026 sind in Featureideen.md nachgewiesen. Nach `npm run build` in `frontend/` unter `http://localhost/skilltree/public/` neu laden und **Matrix** wählen.

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

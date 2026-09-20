# Anforderungskatalog – Lesefassung

**Stand:** Fachstand 18.09.2026; A-07 am 19.09.2026 und F-02 (Graphversuch) am 20.09.2026 auf Nutzeranweisung geändert; 54 aktive Anforderungen.

Automatisch erzeugt aus `Anforderungskatalog_16092026.xlsx`. IDs, Prioritäten und Anforderungstexte sind identisch. Die Excel ist die führende Quelle; diese Datei nicht unabhängig bearbeiten. Zusammenführungen und Prioritätsänderungen sind in `Use-Cases_und_Entscheidungen(1).md`, Abschnitt 8, nachgewiesen.

Prioritäten: Muss = verbindliche Umsetzungspflicht; Sollte = Umsetzungswunsch; Wird = verbindliche Umsetzungsabsicht oder Modellierungsentscheidung; Darf nicht = verbindlicher Ausschluss.

## Global, Architektur und Datenmodell

### G-01 — Muss

Das Artefakt muss als webbasiertes Tool umgesetzt werden.

### G-02 — Muss

Das Artefakt muss lokal unter XAMPP mit MariaDB ausführbar sein. Die technische Architektur muss möglichst einfach bleiben; Entwicklungswerkzeuge dürfen zusätzlich erforderlich sein.

### G-04 — Muss

Für Entwicklung und Evaluation muss genau eine mit Echtdaten befüllte Organisationseinheit des Praxispartners vorhanden und standardmäßig ausgewählt sein.

### G-16 — Muss

Das Artefakt muss die Evaluation der zentralen Funktionen Soll-/Ist-Abgleich, Navigation, Entwicklungskandidaten, Entwicklungs-DAG und Simulation ermöglichen.

### G-06 — Wird

Jeder Mitarbeiter wird genau einer Organisationseinheit zugeordnet; eine gleichzeitige Zuordnung zu mehreren Organisationseinheiten ist ausgeschlossen.

### G-08 — Wird

Fertigkeiten werden binär betrachtet: vorhanden oder nicht vorhanden. Fertigkeitsstufen oder Ausprägungsgrade werden nicht berücksichtigt.

### G-09 — Wird

Der Fertigkeitsbedarf wird qualitativ betrachtet. Im Soll zählt jeder Skill einmal, auch wenn mehrere Aufgaben oder Voraussetzungspfade ihn benötigen. Taxonomiegruppen werden nicht als Skills gezählt.

### G-11 — Wird

Fertigkeiten und ihre Entwicklungsvoraussetzungen werden in einem gerichteten azyklischen Graphen (DAG) modelliert. Die Richtung einer Kante verläuft von der Voraussetzung zum abhängigen Skill.

### G-13 — Wird

Mehrere Voraussetzungen einer Fertigkeit werden ausschließlich als logische UND-Verknüpfung interpretiert.

### G-15 — Wird

Simulationszustände werden nicht dauerhaft gespeichert. Navigation und das Schließen des Simulationsmenüs erhalten die Filter; die Aktion Start sowie Schließen und erneutes Öffnen der Webseite stellen den Ausgangszustand wieder her.

### G-05 — Sollte

Das Webinterface sollte eine Organisationseinheitsauswahl als funktionslosen UI-Dummy zeigen, um spätere Erweiterbarkeit anzudeuten. Daraus entsteht keine Verwaltungsfunktion.

### G-12 — Darf nicht

Fertigkeitsabhängigkeiten dürfen keine direkten oder indirekten Zyklen einschließlich Selbstverweisen enthalten.

## Datenbasis: Aufgaben, Mitarbeitende und Kompetenzen

### D-05 — Muss

Die gespeicherten Skillzuordnungen von Aufgaben und Mitarbeitenden müssen ausschließlich Skills der zugrunde liegenden Taxonomie referenzieren.

### D-07 — Muss

Für jede eingeschlossene Aufgabe müssen ihre direkt zugeordneten Skills und sämtliche eindeutigen direkten und indirekten Voraussetzungen als benötigt gelten. Die impliziten Zuordnungen werden bei der Auswertung hergeleitet und nicht zusätzlich dauerhaft gespeichert.

### D-08 — Muss

Für jeden Mitarbeiter müssen seine direkt zugeordneten Skills und sämtliche eindeutigen direkten und indirekten Voraussetzungen als vorhanden gelten. Die impliziten Zuordnungen werden bei der Auswertung hergeleitet und nicht zusätzlich dauerhaft gespeichert.

### D-10 — Wird

Die LLM-gestützte Ableitung und ESCO-Normalisierung der Aufgaben-Skills sowie die Schätzung der Entwicklungsvoraussetzungen sind vorgelagerte Datenaufbereitung. Direkte und implizite Soll-Skills wurden dabei geschätzt. Die Webanwendung wertet die vorbereiteten Daten aus und führt keine neue LLM-Schätzung durch.

### D-11 — Wird

Die Skills werden innerhalb der zugrunde liegenden ESCO-Gruppenhierarchie thematisch eingeordnet.

### D-02 — Sollte

Einer Aufgabe sollte im vorbereiteten Datenbestand mindestens eine Fertigkeit direkt zugeordnet sein. Diese Datenanforderung verlangt keine Pflegefunktion im Webinterface.

### D-06 — Darf nicht

Der Nutzer darf im Artefakt weder Taxonomie noch Mitarbeitende, Aufgaben, Skills oder deren persistente Zuordnungen anlegen, bearbeiten oder löschen. Temporäre Simulationsfilter gemäß A-13 bleiben möglich.

## Taxonomie und Navigation

### F-01 — Muss

Für die hinterlegte Organisationseinheit muss der gemeinsame, vorbereitete Skillkatalog mit seiner globalen Taxonomie verwendet werden.

### F-02 — Muss

Die Taxonomie muss zum visuellen Vergleich zwischen hierarchischem, auf- und zuklappbarem Inhaltsverzeichnis und aufklappbarem Gruppengraphen umschaltbar sein. Die Graphansicht ist für diesen Versuch die Startansicht. Ein Klick auf eine Gruppe mit Untergruppen blendet deren direkte Untergruppen ein oder aus; Gruppen ohne Untergruppen öffnen ihre Skill-Liste im Mainframe. Skills erscheinen nicht als Graphknoten. Die Kreise übernehmen die Gruppenbewertung und zeigen die Anzahl aller eindeutigen Skills im aktuellen Maskenausschnitt unterhalb der Gruppe einschließlich direkt zugeordneter Skills, unabhängig vom Aufklappzustand. Gruppennamen stehen unter den Kreisen. Der separate Entwicklungs-DAG bleibt bestehen.

### F-03 — Muss

Beim Start muss die oberste Hierarchieebene der Taxonomie vollständig sichtbar sein; tiefere Gruppen sind zunächst eingeklappt.

### F-04 — Muss

Die Organisationsmaske muss beim Start aktiv sein und den betrachteten Skillbestand auf die aktuellen Soll-Skills einschließlich ihrer Voraussetzungen begrenzen. Eine zusätzliche Personenauswahl erweitert diesen Ausschnitt nicht um weitere Ist-Skills.

### F-06 — Muss

Aktuelle Soll-Skills müssen in der Organisationsansicht gemäß A-03 bis A-05 farblich bewertet werden. Die kritischste Bewertung wird über alle Vorfahrengruppen vererbt (Rot vor Gelb vor Grün), unabhängig von eingeklappten Bereichen oder der Anzeige der Skills in Listen. Bei Personenauswahl gilt die Besitzbewertung gemäß F-11.

### F-08 — Muss

Ist kein Soll-Bedarf bestimmbar, insbesondere bei fehlenden oder vollständig ausgeschlossenen Aufgaben, muss die gesamte Taxonomie durch Aufklappen und Skill-Listen zugänglich bleiben. Vollständiger Zugang bedeutet nicht vollständig aufgeklappt.

### F-09 — Muss

Ist kein Soll-Bedarf bestimmbar, muss das Dashboard für alle drei Ampelkategorien 0 Skills anzeigen. Ist Soll-Bedarf vorhanden, aber kein Mitarbeiter eingeschlossen, gelten dagegen sämtliche Soll-Skills als rot.

### F-10 — Muss

Der Nutzer muss die Organisationsmaske deaktivieren und erneut aktivieren können. Ohne Maske ist der gesamte Skillkatalog zugänglich; die Organisationsampel bewertet weiterhin ausschließlich aktuelle Soll-Skills. Personenauswahl und Simulationsfilter bleiben erhalten.

### F-11 — Muss

Der Nutzer muss höchstens einen Mitarbeiter auswählen, wechseln oder über Keiner abwählen können. Die Mitarbeitermaske bewertet Skills im aktuellen Taxonomieausschnitt als vorhanden (grün) oder fehlend (rot), ohne Gelb. Gruppen zeigen eine dazu passende personenbezogene Bewertung. Die Organisationsmaske bestimmt weiterhin den Ausschnitt; Dashboard und Kategorieübersichten bleiben organisationsbezogen. Beim Start ist keine Person ausgewählt.

### F-07 — Wird

Jeder Skill besitzt genau eine thematische Position in der Taxonomie. Entwicklungsvoraussetzungen werden ausschließlich im separaten Skilltree/DAG dargestellt und verändern die thematische Einordnung nicht.

## Soll-/Ist-Abgleich, Managementübersicht und Simulation

### A-01 — Muss

Das System muss den aktuellen Soll-Skillbedarf aus eingeschlossenen Aufgaben und den Ist-Skillbestand aus eingeschlossenen Mitarbeitenden der Organisationseinheit bestimmen, jeweils einschließlich impliziter Voraussetzungen gemäß D-07 und D-08.

### A-02 — Muss

Für jeden aktuellen Soll-Skill muss die Anzahl eingeschlossener Mitarbeitender mit diesem Skill bestimmt werden. Jede Person zählt je Skill höchstens einmal, auch bei mehreren direkten oder impliziten Besitzwegen.

### A-03 — Muss

Ein bei mindestens zwei eingeschlossenen Mitarbeitenden vorhandener Soll-Skill muss als grün beziehungsweise unkritisch und redundant vorhanden klassifiziert werden.

### A-04 — Muss

Ein bei genau einem eingeschlossenen Mitarbeiter vorhandener Soll-Skill muss als gelb beziehungsweise Handlungsbedarf und Einzelabhängigkeit klassifiziert werden.

### A-05 — Muss

Ein bei keinem eingeschlossenen Mitarbeiter vorhandener Soll-Skill muss als rot beziehungsweise kritisch und Fertigkeitslücke klassifiziert werden.

### A-06 — Muss

Das Dashboard muss die Anzahl eindeutiger aktueller Soll-Skills je Ampelkategorie in der Reihenfolge Rot, Gelb, Grün mit Zahlen und Bezeichnungen anzeigen. Zahl oder Bezeichnung öffnet die zugehörige Kategorieübersicht. Ansichts- und Personenmasken ändern diese organisationsbezogenen Zahlen nicht; Simulationsfilter werden berücksichtigt.

### A-07 — Muss

Die grüne Kategorieübersicht muss eine Tabelle mit einer Zeile je grünem Soll-Skill anzeigen: zuerst die Skill-Bezeichnung, danach alle eingeschlossenen Mitarbeitenden, die diesen Skill tragen. Ein Klick auf die Skill-Bezeichnung öffnet unabhängig von der Mitarbeitermaske den allgemeinen Entwicklungspfad gemäß S-01/S-07; ist kein geschätzter Pfad verfügbar, erscheint die schließbare Warnbox.

### A-08 — Muss

Für jeden gelben Soll-Skill muss der einzige eingeschlossene Wissensträger angezeigt werden.

### A-09 — Muss

Für rote und gelbe Soll-Skills müssen interne Entwicklungskandidaten aus der aktuell eingeschlossenen Belegschaft anhand ihrer Fertigkeitsdistanz ermittelt werden. Simulativ ausgeschlossene Mitarbeitende gehören nicht zum Kandidatenpool.

### A-10 — Muss

Bei einem gelben Soll-Skill muss der bereits fertigkeitstragende Mitarbeiter aus den Entwicklungsvorschlägen ausgeschlossen werden.

### A-11 — Muss

Je rotem oder gelbem Soll-Skill müssen drei Kandidatenplätze vorgesehen sein. Höchstens die drei am besten geeigneten realen Kandidaten werden gemäß S-03/S-09 mit Distanz und Zugang zum personenbezogenen Pfad angezeigt. Unbesetzte Plätze bleiben als solche erkennbar und enthalten weder erfundene Personen noch eine Distanz.

### A-12 — Muss

Beträgt die kleinste Distanz der zulässigen Kandidaten mindestens 3, muss auf die Prüfung zusätzlichen Personaleinsatzes oder externer Fertigkeitsgewinnung hingewiesen werden. Unabhängig davon lösen unbesetzte Kandidatenplätze eine Handlungsempfehlung wegen fehlender weiterer interner Personen aus. Bei null Kandidaten gilt dieser zweite Auslöser; eine kleinste Distanz wird nicht erfunden.

### A-13 — Muss

Aufgaben und Mitarbeitende müssen über ein Simulationsmenü temporär aus- und wieder eingeschlossen werden können. Ausgeschlossene Aufgaben tragen nicht zum Soll bei; ausgeschlossene Mitarbeitende nicht zum Ist oder Kandidatenpool. Stammdaten und persistente Zuordnungen bleiben unverändert.

### A-15 — Muss

Soll-/Ist-Ergebnisse werden nicht dauerhaft gespeichert, sondern bei Bedarf aus den gespeicherten Daten und den aktuell gültigen Simulationsfiltern berechnet.

### A-16 — Muss

Nach Änderungen relevanter Daten oder Simulationsfilter müssen alle anschließend dargestellten Ergebnisse auf demselben aktuellen Berechnungszustand beruhen, einschließlich Dashboard, Skill- und Gruppenbewertung, Wissensträgern und Entwicklungskandidaten.

### A-17 — Muss

Die gelbe und rote Kategorieübersicht muss je Skill einen aufklappbaren Detailbereich mit Kandidaten, Distanzen, Pfadzugängen und gegebenenfalls Handlungsempfehlungen bereitstellen. Bei Gelb wird zusätzlich der einzige Wissensträger angezeigt.

## Skilltree, Kompetenzdistanz und Entwicklungspfade

### S-01 — Muss

Für einen geschätzten Skill muss ein Entwicklungs-DAG mit Zielskill und sämtlichen direkten und indirekten Voraussetzungen verfügbar sein. Geschätzt sind alle direkten und impliziten Soll-Skills des ungefilterten Aufgabenbestands sowie Skills mit mindestens einer eigenen gespeicherten Voraussetzung. Ein geschätzter Skill ohne Voraussetzungen hat einen DAG nur aus dem Zielknoten. Für andere Skills erscheint beim Pfadaufruf eine schließbare Warnbox; es wird kein DAG geöffnet.

### S-02 — Muss

Die Fertigkeitsdistanz muss die Anzahl eindeutiger fehlender Skills aus der Menge aus Zielskill und allen direkten und indirekten Voraussetzungen sein. Der Skillbestand der Person umfasst ihre impliziten Voraussetzungen. Gemeinsame Knoten zählen einmal; vorhandener Zielskill ergibt Distanz 0. Die Distanz misst weder Lernzeit noch Fertigkeitsstufen.

### S-03 — Muss

Entwicklungskandidaten müssen für einen Zielskill nach Fertigkeitsdistanz aufsteigend sortiert werden.

### S-04 — Muss

Die Auswahl eines Kandidaten aus einer Handlungsempfehlung oder der Aufruf eines Skills mit ausgewählter Person muss den personenbezogenen verbleibenden Entwicklungspfad zum Zielskill öffnen, sofern der Skill gemäß S-01 geschätzt ist. In der Pfadansicht muss der Personenbezug gewechselt oder über Allgemein aufgehoben werden können.

### S-05 — Muss

Der verbleibende personenbezogene DAG muss alle fehlenden Skills bis einschließlich des fehlenden Zielskills zeigen. Zusätzlich bleiben vorhandene Skills sichtbar, die unmittelbar Voraussetzung eines dargestellten fehlenden Skills sind. Hinter diesen Anschlussknoten wird der jeweilige Zweig gekürzt; weitere vorhandene Voraussetzungen bleiben nur sichtbar, wenn sie andernorts als Anschlussknoten benötigt werden.

### S-06 — Muss

Bei Personenbezug muss zwischen Verbleibend und Vollständig umgeschaltet werden können. Vollständig zeigt alle Voraussetzungen einschließlich Zielskill und behält die personenbezogene Besitzkennzeichnung bei. Die Distanz ändert sich durch diesen Darstellungswechsel nicht.

### S-07 — Muss

Ein Skillaufruf ohne ausgewählte Person muss bei Verfügbarkeit gemäß S-01 den vollständigen allgemeinen DAG ohne personenbezogene Besitzbewertung öffnen. Die Organisationsmaske darf keine Voraussetzungen aus Berechnung oder fachlichem Umfang des gewählten DAGs entfernen.

### S-08 — Muss

Zurück muss zur tatsächlichen vorherigen Mainframe-Ansicht unter Erhalt von Kontext und Filtern führen. Reset setzt ausschließlich die Ansicht zurück. Start stellt den vollständigen Ausgangszustand mit aktiver Organisationsmaske, ohne Personenauswahl oder Simulationsausschlüsse her und scrollt die Seite nach oben.

### S-09 — Muss

Bei gleicher Fertigkeitsdistanz müssen Kandidaten alphabetisch aufsteigend sortiert werden.

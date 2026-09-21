# Skillbasiertes Personalentwicklungstool

**Stand:** 18.09.2026 – fachlicher Ausgangsstand für die Entwicklung eingefroren.  
**Kontext:** Bachelorarbeit; Minimum Viable Artifact (MVA); verbleibende Umsetzungszeit etwa 6–7 Tage.

## Ziel und Nutzen

Das webbasierte Artefakt unterstützt Personalbedarfs-, Wissensbewahrungs- und Personalentwicklungsentscheidungen. Es stellt den aus Aufgaben abgeleiteten Skillbedarf einer Organisationseinheit dem Skillbestand ihrer Mitarbeitenden gegenüber. Ein Ampelsystem macht fehlende Skills, kritische Einzelabhängigkeiten und redundant vorhandene Skills sichtbar. Für rote und gelbe Soll-Skills werden interne Entwicklungskandidaten anhand ihrer Fertigkeitsdistanz vorgeschlagen. Temporäre Filter zeigen die Auswirkungen von Aufgaben- und Mitarbeiterausfällen.

Das Artefakt dient der explorativen Entscheidungsunterstützung. Eine Fertigkeitsdistanz ist eine Anzahl fehlender Skills; sie ist keine Schätzung von Lernzeit oder Fertigkeitsniveau.

## Verbindlicher Umfang

- Für die Evaluation bleibt die vorbereitete Praxiseinheit vorgesehen. Seit der Nutzerrevision vom 21.09.2026 können weitere vorbereitete Organisationseinheiten vorhanden sein und ausgewählt werden; initial gilt die kleinste ID. Die lokale Testbasis enthält drei ausdrücklich synthetische Einheiten.
- Soll und Ist werden aus den gespeicherten Zuordnungen einschließlich sämtlicher direkter und indirekter Voraussetzungen hergeleitet. Die impliziten Zuordnungen werden nicht zusätzlich dauerhaft gespeichert.
- Die Taxonomie wird als **hierarchisches, aufklappbares Inhaltsverzeichnis** und seit der ausdrücklichen Versuchsfreigabe vom 20.09.2026 zusätzlich als **umschaltbarer Gruppengraph** umgesetzt (F-02). Der Graph startet nur mit der obersten Gruppenebene; Kreise zeigen Ampelfarbe und eindeutige Skillzahl, Namen stehen darunter. Gruppen ohne Untergruppen öffnen weiterhin ihre Skill-Liste. Masken, Farbvererbung und Zurück-Navigation bleiben erhalten.
- Ein **separater Entwicklungs-DAG** zeigt allgemeine oder personenbezogene Entwicklungspfade. Bei Personenbezug sind verbleibende und vollständige Darstellung umschaltbar.
- Dashboard, Kategorieübersichten, Kandidaten und Simulation beruhen auf demselben aktuellen Berechnungszustand. Die Auswahl einer Person verändert nicht die organisationsbezogene Managementampel.
- Die Webanwendung wertet vorbereitete Daten aus. Es gibt keine Stammdatenpflege, keine Bearbeitung der Taxonomie, keine laufende LLM-Schätzung und keine dauerhafte Speicherung von Simulation oder Berechnungsergebnissen.
- Die Organisationsauswahl wechselt zwischen vorbereiteten Einheiten und setzt den Bedienzustand zurück. Eine Verwaltungsoberfläche sowie Rollen-/Berechtigungssystem bleiben außerhalb des Umfangs. Ergänzende Bedienideen sind in `Featureideen.md` dokumentiert.

## Datengrundlage

Die relationale MariaDB enthält Organisationseinheit, Mitarbeitende, Aufgaben, Skills, Skillgruppen, direkte Skillzuordnungen sowie Entwicklungsvoraussetzungen. Taxonomiegruppen bilden die thematische Hierarchie; `skill_prerequisite` bildet separat die Voraussetzungen ab. Ein Eintrag `skill_id = Z`, `prerequisite_skill_id = A` bedeutet: A ist Voraussetzung von Z.

LLM-gestützte Ableitung, ESCO-Normalisierung und Schätzung der Entwicklungspfade sind abgeschlossene Arbeiten der Datenerhebung. **Auch indirekte Voraussetzungen von Soll-Skills wurden geschätzt.** Ihre Einstufung bleibt bei Simulationsausschlüssen bestehen. Die genaue Verfügbarkeitsregel ist in S-01 und im Use-Case-Dokument festgehalten.

Laut Projektverantwortlichem wurden die Pfade zu einem globalen DAG zusammengeführt und auf Zyklen, Selbstverweise, Duplikate sowie transitive Redundanzen geprüft und bereinigt. Die Dokumentationskonsolidierung ersetzt keine technische Prüfung der tatsächlichen Daten.

Das Strukturabbild liegt unter `../database/31082026_Structure_skilltree_db.sql`. Kleine Beispieldaten für die Entwicklungsdatenbank werden durch den Projektverantwortlichen bereitgestellt. Der Schemaexport enthält keine Echtdaten.

## Größenordnungen und Umgebung

| Gegenstand | Vorliegende Angabe |
|---|---|
| Skillkatalog | Bis zu etwa 12.000 Skills einschließlich organisationsspezifischer Skills |
| Taxonomie | Etwa 800 Gruppen; keine gleichzeitige Graphdarstellung erforderlich |
| Organisationsrelevante Soll-Skills | Etwa 400; ob diese Angabe alle impliziten Voraussetzungen umfasst, ist noch nicht durch Datenzählung geprüft |
| Entwicklungs-DAGs | Typischerweise etwa 9, gegebenenfalls bis ungefähr 30 sichtbare Skills; keine harte fachliche Obergrenze |
| Betrieb | Lokal unter XAMPP mit MariaDB auf Windows |
| Vorhandene Versionen laut Nutzer | VS Code 1.138, XAMPP for Windows 8.2.12, MariaDB 10.4.32 |

Die Zahlen sind Planungsangaben, keine gemessenen Leistungswerte. Entwicklungswerkzeuge dürfen zusätzlich benötigt werden. Der lokale Betrieb des fertigen Artefakts unter XAMPP bleibt verbindlich.

Der bisherige technische Vorschlag umfasst PHP mit Slim/PDO, Vue mit TypeScript, Cytoscape.js für den Entwicklungs-DAG, Vite und Composer sowie Git/GitHub. Diese Bibliotheksauswahl wird beim technischen Projektstart passend zur vorhandenen Umgebung konkretisiert; sie ist keine zusätzliche fachliche Anforderung.

## Verbindliche Dokumentation

- [Anforderungskatalog_16092026.xlsx](Anforderungskatalog_16092026.xlsx): führender Katalog mit stabilen IDs und Prioritäten. Der historische Dateiname bleibt erhalten; der Inhaltsstand ist 18.09.2026.
- [Anforderungskatalog_16092026.md](Anforderungskatalog_16092026.md): automatisch erzeugte, textgleiche Lesefassung für den Coding-Assistenten; nicht separat pflegen.
- [Use-Cases_und_Entscheidungen(1).md](Use-Cases_und_Entscheidungen%281%29.md): Bedienabläufe, konkrete Beispiele, Referenzzuordnung und Konsolidierungsnachweis.
- `wireframes/`: unveränderte PNG-Referenzen. Textliche Festlegungen gelten bei Abweichungen. `Knowledge_Graph_Taxonomie.png` dient seit dem 20.09.2026 als visuelle Referenz für den freigegebenen Graphversuch.

Prioritäten: **Muss** = verbindliche Umsetzungspflicht; **Sollte** = Umsetzungswunsch; **Wird** = verbindliche Umsetzungsabsicht beziehungsweise festgelegte Modellierungsentscheidung; **Darf nicht** = verbindlicher Ausschluss. Der eingefrorene Stand wird nur durch eine ausdrückliche neue fachliche Entscheidung geändert. Normale technische Detailentscheidungen erweitern den Funktionsumfang nicht.

# Skillbasiertes Personalentwicklungstool

Webbasiertes Minimum Viable Artifact zur Unterstützung von Personalbedarfs-, Wissensbewahrungs- und Personalentwicklungsentscheidungen im Rahmen einer Bachelorarbeit.

**Fachstand:** eingefroren am 18.09.2026.  
**Technischer Stand dieses Pakets:** Dokumentationsgrundlage und vorhandenes Datenbankschema; noch kein implementiertes oder startbares Programm.  
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

Die Markdown-Dateien werden als UTF-8 gespeichert. PNGs können gemeinsam im Wireframe-Ordner liegen; ihre Geltung wird im Use-Case-Dokument erläutert. Eine Umwandlung der Bilder in PDF oder Word ist nicht erforderlich.

Der Excel-Katalog wird nur einmal gepflegt. Die Markdown-Datei ist ein abgeleiteter Export und keine zweite Spezifikation. Nach ausdrücklich freigegebenen Katalogänderungen muss sie neu aus der Excel erzeugt werden; IDs, Prioritäten und Wortlaut müssen übereinstimmen. Für diesen Ausgangsstand wurde diese Übereinstimmung geprüft.

## Technische Vorbereitung

Zielumgebung laut Nutzer: Windows, XAMPP for Windows 8.2.12, MariaDB 10.4.32 und VS Code 1.138. PHP-Version 8.2.12, Composer-Version 2.10.3, Erweiterungen und Toolverfügbarkeit sind auf dem Zielrechner vor der Einrichtung zu prüfen. Der lokale Betrieb unter XAMPP ist verbindlich; zusätzliche Entwicklungswerkzeuge sind möglich.

Der bisherige Stack-Vorschlag steht in der Projektbeschreibung. Versionsauswahl, Verzeichnisaufbau des Codes und konkrete Projektbefehle werden beim technischen Start festgelegt und hier dokumentiert. Dieses Paket installiert weder Composer-/npm-Abhängigkeiten noch eine Datenbank.

| Bereich | Aktueller Status |
|---|---|
| Abhängigkeiten installieren | Noch keine Projektmanifeste oder freigegebenen Installationsbefehle vorhanden |
| Datenbank bereitstellen | Schema vorhanden; getrennte Entwicklungs-/Testdatenbank und Beispieldaten noch lokal anzulegen |
| Anwendung starten | Noch kein Anwendungscode und kein geprüfter Startbefehl vorhanden |
| Frontend bauen | Noch kein Buildskript vorhanden |
| Automatisierte Tests ausführen | Noch kein Testgerüst oder geprüfter Testbefehl vorhanden |

Bei der Implementierung ersetzt der Agent diese Statusangaben durch die tatsächlich geprüften Befehle, benötigte Versionen, lokale URL und relevante Konfigurationshinweise. Zugangsdaten gehören in lokale Konfiguration, nicht in diese Datei.

## Datenbank und erste fachliche Prüfung

Der SQL-Export enthält löschende Tabellenanweisungen. Ein Import erfolgt ausschließlich in die dafür identifizierte Entwicklungs-/Testdatenbank; die vorbereitete Originaldatenbank bleibt erhalten. Ein Git-Commit sichert keine Datenbankinhalte.

Der Projektverantwortliche stellt kleine Beispieldaten bereit. Maßgebliche erwartete Ergebnisse stehen im Use-Case-Dokument: implizite Soll-/Ist-Skills, eindeutige Zählung, Distanz einschließlich Zielskill, Pfadverfügbarkeit auch impliziter Grundlagenskills, Kandidatenplätze, Simulation und Navigation. Die Prüfung der globalen Datenqualität wurde vom Nutzer berichtet; eine erneute Datenprüfung ist im Dokumentationspaket nicht enthalten.

## Persönliches Archiv und Übergabe

Frühere Excel-Stände, der ursprüngliche ausführliche Übergabetext, Chatverläufe, verworfene Entwürfe und Notizen für die Bachelorarbeit können außerhalb des aktiven Repositorys privat archiviert werden. Sie müssen dem Agenten nicht als weitere aktuelle Vorgaben vorliegen. Die fünf aktuellen Dokumente, die erzeugte Lesefassung, das Schema und die PNG-Referenzen gehören in die oben bezeichnete Projektablage.

Das eingefrorene Ausgangspaket dient als Referenz. Nach Einrichtung von Git sollte dieser Stand als erster nachvollziehbarer Commit gesichert werden. Spätere ausdrücklich vereinbarte fachliche Änderungen müssen als Änderungen erkennbar bleiben; gewöhnliche Implementierungsdetails eröffnen keine neue Konzeptionsrunde.

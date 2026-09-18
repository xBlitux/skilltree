# Arbeitsregeln für den Coding-Assistenten

## Auftrag und Ausgangsstand

Entwickle das MVA zur skillbasierten Personalentwicklung schrittweise auf Basis des am 18.09.2026 eingefrorenen Fachstands. Der verfügbare Umsetzungszeitraum beträgt etwa 6–7 Tage. Arbeite in kleinen, prüfbaren Funktionsschritten. Erweitere den fachlichen Umfang nicht eigenständig.

## Dokumente lesen

1. `README.md`: Einstieg, Ablage und tatsächlicher technischer Projektstand.
2. `docs/Projektbeschreibung.md`: Ziel, Grenzen und Datenbasis.
3. `docs/Anforderungskatalog_16092026.md`: automatisch erzeugte Lesefassung des verbindlichen Excel-Katalogs mit IDs und Prioritäten.
4. `docs/Use-Cases_und_Entscheidungen(1).md`: passende Bedienabläufe, Beispiele und Referenzzuordnung.
5. Bei Datenzugriffen `database/31082026_Structure_skilltree_db.sql`; bei UI-Aufgaben die relevanten PNGs unter `docs/wireframes/`.

Der Excel-Katalog ist die führende Quelle für Anforderungen und Prioritäten. Seine Markdown-Lesefassung darf nicht unabhängig bearbeitet werden. Bei Abweichungen den aktuellen Excel-Inhalt auslesen und die Lesefassung daraus neu erzeugen. Die Use-Cases ergänzen die Anforderungen; Wireframes sind visuelle Referenzen. Historische Zeichnungen oder unbestätigte Vorschläge begründen keine neuen Funktionen. Ein echter Widerspruch wird gezielt gemeldet und nicht stillschweigend durch eine Annahme ersetzt.

## Umsetzung

- Nenne die bearbeiteten Anforderungs-IDs und nutze die zugehörigen Abnahmebeispiele. Bereits bestätigte Regeln nicht erneut zur Diskussion stellen.
- Prüfe vor Änderungen vorhandenen Code, Abhängigkeiten und Projektbefehle. Die README beschreibt anfangs nur den Dokumentationsstand; es gibt noch keine bestätigten Start-, Build- oder Testbefehle.
- Konkretisiere die technische Einrichtung passend zur lokalen XAMPP-/MariaDB-Umgebung. Der Stack in der Projektbeschreibung ist ein bisheriger Vorschlag; behaupte keine erfolgte Installation. Halte tatsächlich gewählte Versionen und geprüfte Befehle in der README fest.
- Halte HTTP-Verarbeitung, Fachberechnung, Datenzugriff und Darstellung nachvollziehbar getrennt. API-Antworten und Beispielwerte pro Feature kurz im zugehörigen Use-Case dokumentieren, solange kein separates API-Dokument erforderlich ist.
- Nutze bestehende Strukturen und Abhängigkeiten. Keine zusätzliche Plattform, Pflegeoberfläche oder externe Laufzeitabhängigkeit allein aus einer Idee der KI einführen.
- Normale Implementierungsdetails innerhalb des bestätigten Rahmens selbst entscheiden und bei Bedarf knapp dokumentieren. Nur fachliche Widersprüche, fehlende notwendige Eingaben oder tatsächliche Zugriffsgrenzen gezielt klären.

## Daten und Prüfung

- Der bereitgestellte SQL-Export enthält `DROP TABLE IF EXISTS`. Nicht ungeprüft gegen die Originaldatenbank ausführen. Entwicklungs-/Testdatenbank vor Import und zurücksetzenden Tests eindeutig identifizieren.
- Die Anwendung wertet vorbereitete Daten aus; kein Schreiben von impliziten Zuordnungen, Simulation oder berechneten Ergebnissen in die Stammdaten.
- Verwende für reproduzierbare Tests kleine vereinbarte Beispieldaten. Prüfe gezielt Vererbung, eindeutige Zählung, Distanz, Kandidaten und Simulation sowie den jeweiligen Bedienablauf. Zusätzliche Tests sollen ein konkretes Fehlerrisiko abdecken.
- Berichte getrennt: tatsächlich ausgeführte Prüfungen, deren Ergebnisse und noch nicht lokal unter XAMPP geprüfte Punkte. Keine erfundenen Testergebnisse.
- Keine Zugangsdaten, lokale Geheimnisse oder nicht ausdrücklich freigegebenen personenbezogenen Echtdaten in Git aufnehmen. Testdaten eindeutig als solche kennzeichnen.

## Abschluss eines Funktionsschritts

Fasse Änderung, betroffene Anforderungen, Prüfung und verbleibende Einschränkungen knapp zusammen. Bereite einen überschaubaren Git-Änderungsstand vor; schütze vorhandene Nutzeränderungen. Fachliche Änderungen nur auf ausdrückliche Anweisung vornehmen und synchron in Katalog, Lesefassung und betroffenen Use-Cases nachziehen. Der initial eingefrorene Stand bleibt über Git beziehungsweise die private Archivkopie nachvollziehbar.

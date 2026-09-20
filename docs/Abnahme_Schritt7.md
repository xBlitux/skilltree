# Anforderungsprüfung nach Schritt 7

Stand: 20.09.2026. Grundlage: 54 Anforderungen aus dem Excel-Katalog einschließlich der ausdrücklich geänderten A-07. Die Lesefassung wurde mit der gespeicherten Excel verglichen. Keine fachlichen Anforderungen wurden in Schritt 7 geändert.

## Ergebnis und Grenzen

Die softwareseitigen Grundfunktionen sind implementiert und mit synthetischen Daten geprüft. Eine pauschale Aussage „alle Anforderungen vollständig erfüllt“ wäre dennoch falsch: G-04 fordert ausdrücklich Echtdaten, die weiterhin nicht eingebunden sind. Auch die reale vorgelagerte Schätzung (D-10) und ESCO-Einordnung (D-11) sind in dieser Umgebung nicht technisch verifiziert. Die synthetische Testtaxonomie ist keine echte ESCO-Taxonomie.

„Geprüft“ bezeichnet die unten benannten automatisierten Tests oder nachvollziehbare Code-/Schemaprüfungen, keine formale Fehlerfreiheitsgarantie. Die Evaluation mit Praxisdaten/Anwendern, browserübergreifende Abnahme, endgültiger visueller Feinschliff und reale Lastgrenzen stehen aus. Der bisherige Umfang enthält keine neue Stammdatenpflege oder externe Schätzung.

## Prüfgrundlage

- Backend: `composer test` prüft Fachberechnung und HTTP-Verträge ohne Datenbank.
- Integration: `composer test:database` liest ausschließlich `testdata_skilltree` mit dem Lesebenutzer, prüft den 29-Skill-Seed und Simulation, vergleicht die Stammdaten vor/nach den Berechnungen.
- Frontend: `npm test` prüft Masken, Vererbung, DAG-Projektion, API-Aufrufe und Schutz vor verspäteten/fehlgeschlagenen Antworten.
- Browser: `npm run test:e2e` gegen den gebauten XAMPP-Stand im lokalen Edge; eigene Simulationstests ergänzen die bisherigen Dashboard-/DAG-Tests. Fehlerfälle werden durch abgefangene Antworten simuliert, ohne Datenbankänderung.
- `powershell -NoProfile -File scripts/export-requirements.ps1 -Check`: Excel-/Markdown-Übereinstimmung.

## Tatsächlich ausgeführte Prüfungen am 20.09.2026

| Prüfung | Ergebnis |
|---|---|
| Backend | 32 Tests, 159 Assertions erfolgreich |
| Datenbankintegration | Erfolgreich; Ausgangsbestand und simulierte Ampeln geprüft, Stammdaten unverändert |
| Frontend | 20 Tests erfolgreich |
| TypeScript und Vite | Build erfolgreich |
| Lokaler Edge unter XAMPP | 13 Browsertests erfolgreich, einschließlich Simulation, Navigation, Fehler/Wiederholung, neuem Fenster und vierstelligen Zahlen auf Desktop/Mobilansicht |
| Excel gegen Lesefassung | Alle 54 Anforderungen identisch |

Apache und MariaDB liefen für die Integrations- und Browsertests lokal. Der Screenshot des Simulationsmenüs wurde zusätzlich visuell kontrolliert. Es wurden keine Datenbankinhalte geändert und keine Produktivdaten abgefragt.

## Einzelprüfung aller 54 Anforderungen

| ID | Ergebnis | Nachweis / Einschränkung |
|---|---|---|
| G-01 | Geprüft | Vue-Oberfläche und Slim-API im Browser |
| G-02 | Geprüft | Build unter lokalem XAMPP; Node nur für Entwicklung/Build |
| G-04 | Offen: Echtdaten | Genau eine synthetische Organisation automatisch gewählt; Praxisdaten noch nicht integriert |
| G-16 | Funktional prüfbar | Soll/Ist, Navigation, Kandidaten, DAG und Simulation verfügbar; wissenschaftliche Evaluation noch ausstehend |
| G-06 | Modell geprüft | Employee besitzt genau einen nicht-null Organisation-Fremdschlüssel |
| G-08 | Geprüft | Binärer Besitz in Kernberechnung, Maske und DAG; keine Stufen |
| G-09 | Geprüft | Mengenbildung bei mehreren Aufgaben und gemeinsamen Voraussetzungen; Gruppen zählen nicht |
| G-11 | Geprüft | DAG-Pfeile Voraussetzung → abhängiger Skill; API und Projektionstests |
| G-13 | Geprüft | Alle Voraussetzungen werden gemeinsam berücksichtigt; keine Alternativzweige |
| G-15 | Geprüft | Simulation bleibt bei Navigation/Schließen bestehen; Start/Neuladen ohne Filter; keine Speicherung |
| G-05 | Geprüft | Nicht bedienbare Organisationsauswahl als UI-Dummy |
| G-12 | Geprüft | Globale Zyklus-/Selbstverweisprüfung, auch außerhalb des aktuellen Solls |
| D-05 | Modell und Testdaten geprüft | Fremdschlüssel und unbekannte Skillreferenzen; reale Zuordnungen noch nicht eingelesen |
| D-07 | Geprüft | Vollständige transitive Aufgabenhülle; simulativ ausgeschlossene Aufgaben entfallen |
| D-08 | Geprüft | Vollständige Besitzhülle; Wissensträger eindeutig; ausgeschlossene Personen entfallen |
| D-10 | Anwendung geprüft, Echtdaten offen | Keine LLM-Aufrufe; vorgelagerte echte Schätzung nicht technisch geprüft |
| D-11 | Struktur geprüft, Echtdaten offen | Vierstufige thematische Testhierarchie; noch keine eingelesene reale ESCO-Zuordnung |
| D-02 | Testdaten geprüft | Zehn Aufgaben mit je zwei direkten Skills; keine Pflegefunktion eingeführt |
| D-06 | Geprüft | Keine schreibende Webroute; Simulation verarbeitet nur flüchtige Filter |
| F-01 | Geprüft | Globaler Katalog aus gemeinsamem Lesesnapshot; Nicht-Soll-/Nicht-Ist-Skills enthalten |
| F-02 | Geprüft | Aufklappbare Gruppen, Blattgruppe öffnet Skillliste; Taxonomie kein Graph |
| F-03 | Geprüft | Wurzelebene sichtbar, darunter eingeklappt beim Start |
| F-04 | Geprüft | Aktive Organisationsmaske beschränkt auf transitive Soll-Menge; keine Soll/Ist-Vereinigung |
| F-06 | Geprüft | Schlechteste Farbe über alle Vorfahren unabhängig vom Aufklappen |
| F-08 | Geprüft | Kein Soll: kompletter Katalog zugänglich, nicht automatisch voll aufgeklappt |
| F-09 | Geprüft | Kein Soll → 0/0/0; Soll ohne Personen → alles rot |
| F-10 | Geprüft | Maskenwechsel erhält Person und Simulation; Dashboard unverändert |
| F-11 | Geprüft | Höchstens eine eingeschlossene Person, Keiner, binäre Farben; Managementwerte unabhängig |
| F-07 | Modell und Testdaten geprüft | Ein Gruppen-Fremdschlüssel pro Skill; Voraussetzungskanten separat |
| A-01 | Geprüft | Soll/Ist aus eingeschlossenen Aufgaben/Personen einschließlich Hüllen |
| A-02 | Geprüft | Eindeutige Wissensträger, mehrere Besitzpfade ohne Mehrfachzählung |
| A-03 | Geprüft | Grün ab zwei Trägern, Grenzwert separat getestet |
| A-04 | Geprüft | Gelb bei genau einem Träger; Ausschluss ändert Bewertung |
| A-05 | Geprüft | Rot bei keinem Träger, einschließlich leerer Belegschaft |
| A-06 | Geprüft | Reihenfolge Rot/Gelb/Grün, anklickbare Zahlen/Labels; Simulation wirkt, Personenmaske nicht |
| A-07 | Geprüft | Eine Zeile je grünem Soll-Skill, Träger dahinter; Klick immer Allgemein |
| A-08 | Geprüft | Einziger eingeschlossener Träger in gelber Detailbox |
| A-09 | Geprüft | Kandidaten nur aus eingeschlossener Belegschaft; ausgeschlossene Person fehlt |
| A-10 | Geprüft | Gelber Wissensträger wird nicht als Kandidat angeboten |
| A-11 | Geprüft | Genau drei Plätze, maximal drei reale Personen, leere Plätze ohne Distanz |
| A-12 | Geprüft | Distanzschwelle und unbesetzte Plätze unabhängig; null Personen ohne erfundene Mindestdistanz |
| A-13 | Geprüft | Aufgaben/Personen einzeln oder gemeinsam aus-/wiedereinschließen, keine DB-Schreiboperation |
| A-15 | Geprüft | Keine gespeicherten Ergebnisse, keine Simulation in Session/Browser-Speicher |
| A-16 | Geprüft | Ein Datenbank-Snapshot und atomarer Ergebniswechsel; alte Antworten ignoriert, Fehler erhalten konsistenten Altstand |
| A-17 | Geprüft | Aufklappbare Rot-/Gelbboxen mit Kandidaten, Distanz, Links und Empfehlungen |
| S-01 | Geprüft | Ungefilterte Pfadverfügbarkeit bleibt bei Simulation erhalten; geschätzte Grundlage als Einzelknoten, ungeschätzter Skill mit Warnbox |
| S-02 | Geprüft | Eindeutige fehlende Skills einschließlich Ziel; gemeinsame Knoten einmal; vorhandenes Ziel Distanz 0 |
| S-03 | Geprüft | Kandidaten aufsteigend nach Distanz |
| S-04 | Geprüft; Abweichung korrigiert | Taxonomie sowie Rot/Gelb übernehmen ausgewählte Person; Kandidatenlink persönlich; Grün hat Ausnahme A-07 |
| S-05 | Geprüft | Restpfad mit allen fehlenden Skills und benötigten vorhandenen Anschlussknoten pro Zweig |
| S-06 | Geprüft | Vollständig behält Besitzfarben; Distanz gleich; Reset verändert den Pfadumfang nicht |
| S-07 | Geprüft | Allgemein vollständig/neutral, Organisationsmaske entfernt keine Voraussetzungen |
| S-08 | Geprüft | Zurück echte vorherige Ansicht bei aktuellen Simulationsfiltern; Reset nur Ansicht; Start vollständig zurück |
| S-09 | Geprüft | Distanzgleichstand nach Nachname/Vorname, abschließend ID; Umlaute wie Grundbuchstaben |

## Entscheidungen und behobene Abweichungen

Bei Ausschluss einer ausgewählten Person wird diese abgewählt; ein geöffneter Pfad wird allgemein. Eine in der Navigationshistorie gespeicherte ausgeschlossene Person wird nicht reaktiviert. Simulationsfilter bleiben global aktuell, Zurück stellt keine historischen Ergebnisdaten wieder her. Diese Implementierungsdetails sind im Use-Case-Dokument festgehalten.

Im Abgleich korrigiert: Skilllinks der roten/gelben Übersicht ignorierten die Mitarbeitermaske; sie übernehmen sie jetzt gemäß S-04. Außerdem wurde die Checkboxanzeige nach einem fehlgeschlagenen Simulationsabruf berichtigt: sie zeigt wieder den letzten wirksamen Filterstand, passend zum unveränderten Dashboard.

## Manuelle Kurzabnahme

1. Start: 3/7/16, Organisationsmaske aktiv, keine Person, keine Ausschlüsse.
2. Simulation → Anna Adler abwählen → Schließen: 6/4/16. Rot → Budgetplanung: Anna ist keine Kandidatin.
3. Zurück und Reset: Ausschluss bleibt. Menü erneut öffnen: Anna weiterhin abgewählt. Wieder einschließen: 3/7/16.
4. Alle Mitarbeitenden ausschließen: 26/0/0, drei unbesetzte Plätze je rotem Skill. Alle Aufgaben ausschließen: 0/0/0, gesamte Taxonomie zugänglich.
5. Trotz leerem Soll Datenverständnis öffnen: Einzelknoten weiterhin verfügbar. Geodatenkartierung: Warnung statt erfundenem Pfad.
6. Start: 3/7/16, Menü geschlossen, Historie leer, alles eingeschlossen. Neu laden: gleicher Ausgangszustand.

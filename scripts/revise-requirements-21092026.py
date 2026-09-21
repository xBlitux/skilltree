"""Explicitly approved revisions of existing IDs only. Regenerate markdown afterwards."""
from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED
import xml.etree.ElementTree as ET

path = Path(__file__).resolve().parent.parent / 'docs/Anforderungskatalog_16092026.xlsx'
ns = {'s': 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'}
ET.register_namespace('', ns['s'])
revisions = {
 'G-04': 'Für Entwicklung und Evaluation muss eine mit Echtdaten befüllte Organisationseinheit des Praxispartners vorhanden sein. Weitere vorbereitete Organisationseinheiten dürfen vorhanden sein und ausgewählt werden; beim ersten Laden wird die Organisationseinheit mit der kleinsten ID ausgewählt.',
 'G-05': 'Das Webinterface sollte eine funktionsfähige Auswahl der in der Datenbank vorhandenen Organisationseinheiten anbieten. Ein Wechsel lädt ausschließlich die Aufgaben und Mitarbeitenden der gewählten Einheit und setzt Simulation, Masken, Navigation, Ansicht und Distanzgrenze auf den Ausgangszustand zurück. Daraus entsteht keine Verwaltungsfunktion.',
 'F-03': 'Beim Start muss die oberste Hierarchieebene der Taxonomie sichtbar sein; tiefere Gruppen sind zunächst eingeklappt. Gruppen ohne sichtbare Skills im gesamten Unterbaum einschließlich direkt zugeordneter Skills werden auf allen Ebenen ausgeblendet, mit und ohne Organisationsmaske.',
 'F-11': 'In Baum, Graph und Skill-Listen muss der Nutzer höchstens einen Mitarbeiter auswählen, wechseln oder über Keiner abwählen können. Die Mitarbeitermaske bewertet Skills im aktuellen Taxonomieausschnitt als vorhanden (grün) oder fehlend (rot), ohne Gelb. Gruppen zeigen eine dazu passende personenbezogene Bewertung. In der ergänzenden Skillmatrix filtert die Auswahl Alle, einer oder mehrerer Mitarbeiter ausschließlich die sichtbaren Mitarbeiterspalten; Bewertung und Kennzahlen bleiben organisationsbezogen. Eine Einzelauswahl wird beim Ansichtswechsel übernommen; eine Mehrfachauswahl wird beim Verlassen der Matrix auf Keiner beziehungsweise Alle zurückgesetzt. Die Organisationsmaske bestimmt weiterhin den Ausschnitt; Dashboard und Kategorieübersichten bleiben organisationsbezogen. Beim Start ist keine Person ausgewählt.',
 'A-12': 'Die maximale Fertigkeitsdistanz für Kandidaten beträgt standardmäßig 3 und ist in den roten und gelben Kategorieübersichten gemeinsam zwischen 1 und 5 einstellbar. Kandidaten mit größerer Distanz dürfen nicht vorgeschlagen werden; dadurch fehlende Plätze bleiben Unbesetzt. Beträgt bereits die kleinste Distanz der ansonsten zulässigen Kandidaten vor diesem Distanzfilter mehr als die eingestellte Grenze, muss auf die Prüfung zusätzlichen Personaleinsatzes oder externer Fertigkeitsgewinnung hingewiesen werden. Unabhängig davon lösen unbesetzte Kandidatenplätze die Warnung aus: Nicht alle Kandidatenplätze sind besetzt. Weitere Maßnahmen prüfen, da geeignete interne Personen fehlen. Bei null Kandidaten wird keine kleinste Distanz erfunden. Die Grenze bleibt bei Navigation erhalten und wird bei Start oder Organisationswechsel auf 3 zurückgesetzt.',
}
with ZipFile(path) as archive:
    entries = [(info, archive.read(info.filename)) for info in archive.infolist()]
sheet = ET.fromstring(dict((i.filename, b) for i,b in entries)['xl/worksheets/sheet1.xml'])
found = set()
for row in sheet.findall('.//s:row', ns):
    cells = {''.join(filter(str.isalpha, c.attrib['r'])): c for c in row.findall('s:c', ns)}
    ident = cells.get('B')
    ident = ident.find('s:v', ns).text if ident is not None and ident.find('s:v', ns) is not None else None
    if ident in revisions:
        cells['C'].find('s:v', ns).text = revisions[ident]; found.add(ident)
        row.set('ht', '150')
    if ident in ('A-07', 'S-01'):
        value = cells['C'].find('s:v', ns)
        value.text = value.text.replace('die schließbare Warnbox', 'ein bestätigungspflichtiges Popup ohne Änderung der bisherigen Scrollposition').replace('eine schließbare Warnbox', 'ein bestätigungspflichtiges Popup ohne Änderung der bisherigen Scrollposition')
    if row.attrib.get('r') == '2':
        cells['A'].find('s:v', ns).text = 'Fachstand 18.09.2026 mit freigegebenen Revisionen bis 21.09.2026; 54 aktive Anforderungen, keine neuen IDs'
assert found == set(revisions)
temporary = path.with_suffix('.revision.xlsx')
with ZipFile(temporary, 'w', ZIP_DEFLATED) as archive:
    for info, content in entries:
        archive.writestr(info, ET.tostring(sheet, encoding='utf-8', xml_declaration=True) if info.filename == 'xl/worksheets/sheet1.xml' else content)
temporary.replace(path)
print('Aktualisiert: G-04, G-05, F-03, F-11, A-07, A-12, S-01; IDs/Prioritäten unverändert.')

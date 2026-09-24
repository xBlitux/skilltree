param([switch]$ApplyGreenRevision, [switch]$ApplyTaxonomyGraphRevision, [switch]$ApplyOrganisationRevision, [switch]$Check)
$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression.FileSystem
Add-Type -AssemblyName System.IO.Compression
$project = Split-Path $PSScriptRoot -Parent
$file = Join-Path $project 'docs/Anforderungskatalog_16092026.xlsx'

$mode = if ($ApplyGreenRevision -or $applyTaxonomyGraphRevision -or $ApplyOrganisationRevision) { [IO.Compression.ZipArchiveMode]::Update } else { [IO.Compression.ZipArchiveMode]::Read }
$archive = [IO.Compression.ZipFile]::Open($file, $mode)
try {
    $entry = $archive.GetEntry('xl/worksheets/sheet1.xml')
    $reader = [IO.StreamReader]::new($entry.Open())
    [xml]$sheet = $reader.ReadToEnd()
    $reader.Dispose()
    $ns = [Xml.XmlNamespaceManager]::new($sheet.NameTable)
    $ns.AddNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main')
    $old = 'Die grüne Kategorieübersicht muss eine Tabelle mit einer Zeile je eingeschlossenem Mitarbeiter anzeigen: Name des Mitarbeiters und alle von ihm getragenen grünen Soll-Skills.'
    $new = 'Die grüne Kategorieübersicht muss eine Tabelle mit einer Zeile je grünem Soll-Skill anzeigen: zuerst die Skill-Bezeichnung, danach alle eingeschlossenen Mitarbeitenden, die diesen Skill tragen. Ein Klick auf die Skill-Bezeichnung öffnet unabhängig von der Mitarbeitermaske den allgemeinen Entwicklungspfad gemäß S-01/S-07; ist kein geschätzter Pfad verfügbar, erscheint die schließbare Warnbox.'
    if ($ApplyGreenRevision) {
        $row = $sheet.SelectSingleNode('//s:row[s:c/s:v="A-07"]', $ns)
        $cell = $row.SelectSingleNode('s:c[starts-with(@r,"C")]/s:v', $ns)
        if ($cell.InnerText -ne $old -and $cell.InnerText -ne $new) { throw 'A-07 hat einen unerwarteten Inhalt.' }
        $cell.InnerText = $new
        $row.SetAttribute('ht', '92')
        $sheet.SelectSingleNode('//s:c[@r="A2"]/s:v', $ns).InnerText = 'Fachstand 18.09.2026; A-07 auf Nutzeranweisung am 19.09.2026 geändert; 54 aktive Anforderungen'
        $stream = $entry.Open()
        $stream.SetLength(0)
        $sheet.Save($stream)
        $stream.Dispose()
    }
    if ($applyTaxonomyGraphRevision) {
        $row = $sheet.SelectSingleNode('//s:row[s:c/s:v="F-02"]', $ns)
        $cell = $row.SelectSingleNode('s:c[starts-with(@r,"C")]/s:v', $ns)
        $oldTaxonomy = 'Die Taxonomie muss als hierarchisches, auf- und zuklappbares Inhaltsverzeichnis umgesetzt werden. Gruppen ohne Untergruppen öffnen ihre Skill-Liste im Mainframe. Die Taxonomie wird im MVA nicht als Graph dargestellt; der separate Entwicklungs-DAG bleibt bestehen.'
        $newTaxonomy = 'Die Taxonomie muss zum visuellen Vergleich zwischen hierarchischem, auf- und zuklappbarem Inhaltsverzeichnis und aufklappbarem Gruppengraphen umschaltbar sein. Die Graphansicht ist für diesen Versuch die Startansicht. Ein Klick auf eine Gruppe mit Untergruppen blendet deren direkte Untergruppen ein oder aus; Gruppen ohne Untergruppen öffnen ihre Skill-Liste im Mainframe. Skills erscheinen nicht als Graphknoten. Die Kreise übernehmen die Gruppenbewertung und zeigen die Anzahl aller eindeutigen Skills im aktuellen Maskenausschnitt unterhalb der Gruppe einschließlich direkt zugeordneter Skills, unabhängig vom Aufklappzustand. Gruppennamen stehen unter den Kreisen. Der separate Entwicklungs-DAG bleibt bestehen.'
        if ($cell.InnerText -ne $oldTaxonomy -and $cell.InnerText -ne $newTaxonomy) { throw 'F-02 hat einen unerwarteten Inhalt.' }
        $cell.InnerText = $newTaxonomy
        $row.SetAttribute('ht', '180')
        $sheet.SelectSingleNode('//s:c[@r="A2"]/s:v', $ns).InnerText = 'Fachstand 18.09.2026; A-07 am 19.09.2026 und F-02 (Graphversuch) am 20.09.2026 auf Nutzeranweisung geändert; 54 aktive Anforderungen'
        $stream = $entry.Open()
        $stream.SetLength(0)
        $sheet.Save($stream)
        $stream.Dispose()
    }
    if ($ApplyOrganisationRevision) {
        $revisions = @{
            'G-06' = 'Mitarbeitende und Aufgaben werden Organisationseinheiten über m:n-Zuordnungen zugeordnet. Derselbe Mitarbeiter und dieselbe Aufgabe können mehreren Einheiten angehören, ohne ihre Stammdatensätze zu duplizieren. Vorher- und Nachher-Stände werden als getrennte vorbereitete Organisationseinheiten betrachtet. Direkte Mitarbeiter-Skills werden je Organisationszuordnung separat gespeichert; derselbe Skill darf für dieselbe Person in mehreren Einheiten vorkommen, innerhalb einer Einheit jedoch nur einmal. Skillzuordnungen setzen eine gültige Organisationszuordnung der Person voraus.'
            'G-05' = 'Das Webinterface sollte eine funktionsfähige Auswahl der in der Datenbank vorhandenen Organisationseinheiten anbieten. Ein Wechsel lädt ausschließlich die zugeordneten Aufgaben und Mitarbeitenden sowie die für diese Einheit gespeicherten Mitarbeiter-Skills und setzt Simulation, Masken, Navigation, Ansicht und Distanzgrenze auf den Ausgangszustand zurück. Daraus entsteht keine Verwaltungsfunktion.'
            'D-07' = 'Für jede eingeschlossene Aufgabe müssen ihre direkt zugeordneten Skills und sämtliche eindeutigen direkten und indirekten Voraussetzungen als benötigt gelten. Die direkten Skillanforderungen einer Aufgabe gelten in allen zugeordneten Organisationseinheiten identisch. Die impliziten Zuordnungen werden bei der Auswertung hergeleitet und nicht zusätzlich dauerhaft gespeichert.'
            'D-08' = 'Für jeden eingeschlossenen Mitarbeiter müssen ausschließlich seine in der ausgewählten Organisationseinheit direkt zugeordneten Skills und sämtliche eindeutigen direkten und indirekten Voraussetzungen als vorhanden gelten. Skillzuordnungen derselben Person in anderen Einheiten werden nicht übernommen. Die impliziten Zuordnungen werden bei der Auswertung hergeleitet und nicht zusätzlich dauerhaft gespeichert.'
        }
        foreach ($id in $revisions.Keys) {
            $row = $sheet.SelectSingleNode("//s:row[s:c/s:v='$id']", $ns)
            if (!$row) { throw "Anforderung $id fehlt." }
            $row.SelectSingleNode('s:c[starts-with(@r,"C")]/s:v', $ns).InnerText = $revisions[$id]
            $row.SetAttribute('ht', $(if ($id -eq 'G-06') { '150' } else { '105' }))
        }
        $sheet.SelectSingleNode('//s:c[@r="A2"]/s:v', $ns).InnerText = 'Fachstand 18.09.2026 mit freigegebenen Revisionen bis 24.09.2026; 54 aktive Anforderungen, keine neuen IDs'
        $stream = $entry.Open()
        $stream.SetLength(0)
        $sheet.Save($stream)
        $stream.Dispose()
    }
    $lines = [Collections.Generic.List[string]]::new()
    $lines.Add('# Anforderungskatalog – Lesefassung'); $lines.Add('')
    $revision = $sheet.SelectSingleNode('//s:c[@r="A2"]/s:v', $ns).InnerText
    $lines.Add("**Stand:** $revision."); $lines.Add('')
    $lines.Add('Automatisch erzeugt aus `Anforderungskatalog_16092026.xlsx`. IDs, Prioritäten und Anforderungstexte sind identisch. Die Excel ist die führende Quelle; diese Datei nicht unabhängig bearbeiten. Zusammenführungen und Prioritätsänderungen sind in `Use-Cases_und_Entscheidungen(1).md`, Abschnitt 8, nachgewiesen.')
    $lines.Add(''); $lines.Add('Prioritäten: Muss = verbindliche Umsetzungspflicht; Sollte = Umsetzungswunsch; Wird = verbindliche Umsetzungsabsicht oder Modellierungsentscheidung; Darf nicht = verbindlicher Ausschluss.')
    $priority = ''; $count = 0
    foreach ($row in $sheet.SelectNodes('//s:sheetData/s:row', $ns)) {
        if ([int]$row.r -lt 3) { continue }
        $a = $row.SelectSingleNode('s:c[starts-with(@r,"A")]/s:v', $ns)
        $b = $row.SelectSingleNode('s:c[starts-with(@r,"B")]/s:v', $ns)
        $c = $row.SelectSingleNode('s:c[starts-with(@r,"C")]/s:v', $ns)
        if ($b -and $b.InnerText -match '^[GDFAS]-\d{2}$') {
            if (!$priority -or !$c) { throw 'Unvollständige Anforderung.' }
            $lines.Add(''); $lines.Add("### $($b.InnerText) — $priority"); $lines.Add(''); $lines.Add($c.InnerText); $count++
        } elseif ($a -and $a.InnerText) {
            if ($a.InnerText -in @('Muss', 'Sollte', 'Wird', 'Darf nicht')) { $priority = $a.InnerText }
            else { $lines.Add(''); $lines.Add("## $($a.InnerText)") }
        }
    }
    if ($count -ne 54) { throw "Erwartet: 54 Anforderungen, gefunden: $count" }
    $text = ($lines -join "`n") + "`n"
    $markdown = Join-Path $project 'docs/Anforderungskatalog_16092026.md'
    if ($Check) {
        if ([IO.File]::ReadAllText($markdown).Replace("`r`n", "`n") -ne $text) { throw 'Lesefassung weicht von Excel ab.' }
        Write-Output 'OK: 54 Anforderungen stimmen mit Excel ueberein.'
    } else {
        [IO.File]::WriteAllText($markdown, $text, [Text.UTF8Encoding]::new($false))
        Write-Output 'OK: Lesefassung aus 54 Excel-Anforderungen erzeugt.'
    }
} finally { $archive.Dispose() }

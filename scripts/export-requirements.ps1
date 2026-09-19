param([switch]$ApplyGreenRevision, [switch]$Check)
$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression.FileSystem
Add-Type -AssemblyName System.IO.Compression
$project = Split-Path $PSScriptRoot -Parent
$file = Join-Path $project 'docs/Anforderungskatalog_16092026.xlsx'
$mode = if ($ApplyGreenRevision) { [IO.Compression.ZipArchiveMode]::Update } else { [IO.Compression.ZipArchiveMode]::Read }
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

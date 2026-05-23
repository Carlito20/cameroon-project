param([string]$imagePath = "", [string]$printer = "")

Add-Type -AssemblyName System.Drawing

# Auto-detect if no printer specified
if (-not $printer) {
    $printer = Get-Printer | Where-Object { $_.Name -match 'munbyn' } | Select-Object -First 1 -ExpandProperty Name
}
if (-not $printer) { Write-Error "No Munbyn printer found"; exit 1 }

$img = $null
$pd  = $null

try {
    $img = [System.Drawing.Image]::FromFile($imagePath)
    $pd  = New-Object System.Drawing.Printing.PrintDocument
    $pd.PrinterSettings.PrinterName = $printer
    $pd.DefaultPageSettings.Margins = New-Object System.Drawing.Printing.Margins(0, 0, 0, 0)

    # Match paper size to image dimensions (units: 1/100 inch)
    $dpi = if ($img.HorizontalResolution -gt 0) { $img.HorizontalResolution } else { 203 }
    $w100 = [int]($img.Width  / $dpi * 100)
    $h100 = [int]($img.Height / $dpi * 100)
    $pd.DefaultPageSettings.PaperSize = New-Object System.Drawing.Printing.PaperSize("Label", $w100, $h100)

    $captured = $img
    $pd.add_PrintPage({
        param($s, $e)
        $e.Graphics.DrawImage($captured, $e.PageBounds)
        $e.HasMorePages = $false
    }.GetNewClosure())

    $pd.Print()
    Write-Host "OK:printed"
} catch {
    Write-Error $_.Exception.Message
    exit 1
} finally {
    if ($img) { $img.Dispose() }
    if ($pd)  { $pd.Dispose() }
}

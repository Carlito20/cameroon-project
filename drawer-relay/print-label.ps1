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

    # Fixed 3" x 2" label size (units: 1/100 inch)
    $pd.DefaultPageSettings.PaperSize = New-Object System.Drawing.Printing.PaperSize("Label", 300, 200)

    $captured = $img
    $pd.add_PrintPage({
        param($s, $e)
        # dst width=200 fills the full 3" label width at this printer's DPI.
        # dst height=133 = 200 * (2/3) preserves the 3:2 label aspect ratio so
        # the full canvas height maps to the full 2" label height.
        $dst = New-Object System.Drawing.RectangleF(0, 0, 200, 133)
        $src = New-Object System.Drawing.RectangleF(0, 0, $captured.Width, $captured.Height)
        $e.Graphics.DrawImage($captured, $dst, $src, [System.Drawing.GraphicsUnit]::Pixel)
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

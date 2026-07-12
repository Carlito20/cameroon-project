param(
    [string]$imagePath = "",
    [string]$printer = "",
    [int]$widthHundredths = 300,
    [int]$heightHundredths = 200,
    [double]$scaleFactor = 0.6667
)

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

    # Label size (units: 1/100 inch), e.g. 300x200 = 3"x2", 200x100 = 2"x1"
    $pd.DefaultPageSettings.PaperSize = New-Object System.Drawing.Printing.PaperSize("Label", $widthHundredths, $heightHundredths)

    # This printer/driver combo doesn't map DrawImage's destRect 1:1 to the
    # requested paper size — a ~2/3 scale factor was reverse-engineered for
    # the original 3"x2" label (300 hundredths-inch requested -> 200 wide
    # destRect prints edge-to-edge). Reuse that same ratio for other label
    # sizes; if a new label size prints too small/large, adjust -scaleFactor.
    $destW = $widthHundredths * $scaleFactor
    $destH = $destW * ($img.Height / $img.Width)
    Write-Host "INFO:paper=${widthHundredths}x${heightHundredths} dest=${destW}x${destH}"

    $captured = $img
    $pd.add_PrintPage({
        param($s, $e)
        $dst = New-Object System.Drawing.RectangleF(0, 0, $destW, $destH)
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

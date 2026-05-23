param(
  [string]$barcode = "",
  [string]$name    = "",
  [string]$printer = ""
)

Add-Type @"
using System;
using System.Runtime.InteropServices;
public class Spooler {
    [DllImport("winspool.drv", CharSet=CharSet.Ansi)]
    public static extern bool OpenPrinter(string n, out IntPtr h, IntPtr d);
    [DllImport("winspool.drv")]
    public static extern bool ClosePrinter(IntPtr h);
    [DllImport("winspool.drv", CharSet=CharSet.Ansi)]
    public static extern int StartDocPrinter(IntPtr h, int level, ref DOC_INFO_1 info);
    [DllImport("winspool.drv")]
    public static extern bool EndDocPrinter(IntPtr h);
    [DllImport("winspool.drv")]
    public static extern bool StartPagePrinter(IntPtr h);
    [DllImport("winspool.drv")]
    public static extern bool EndPagePrinter(IntPtr h);
    [DllImport("winspool.drv")]
    public static extern bool WritePrinter(IntPtr h, byte[] b, int n, out int w);
    [StructLayout(LayoutKind.Sequential, CharSet=CharSet.Ansi)]
    public struct DOC_INFO_1 {
        public string pDocName;
        public string pOutputFile;
        public string pDataType;
    }
}
"@

# Auto-detect printer if not specified
if (-not $printer) {
    $all = Get-Printer | Select-Object -ExpandProperty Name
    $printer = $all | Where-Object { $_ -match 'munbyn|thermal|label' } | Select-Object -First 1
    if (-not $printer) { $printer = $all[0] }
}
if (-not $printer) { Write-Error "No printer found"; exit 1 }

# Truncate name to 2 lines of ~24 chars
$nameLine1 = if ($name.Length -gt 24) { $name.Substring(0, 24) } else { $name }
$nameLine2 = if ($name.Length -gt 24) { $name.Substring(24, [Math]::Min($name.Length - 24, 24)) } else { "" }

$bytes = [System.Collections.Generic.List[byte]]::new()

# ESC @ — initialize
$bytes.Add([byte]0x1B); $bytes.Add([byte]0x40)
# ESC a 1 — center align
$bytes.Add([byte]0x1B); $bytes.Add([byte]0x61); $bytes.Add([byte]1)
# ESC E 1 — bold on
$bytes.Add([byte]0x1B); $bytes.Add([byte]0x45); $bytes.Add([byte]1)
foreach ($b in [System.Text.Encoding]::ASCII.GetBytes("AMERICAN SELECT")) { $bytes.Add($b) }
$bytes.Add([byte]0x0A)
# ESC E 0 — bold off
$bytes.Add([byte]0x1B); $bytes.Add([byte]0x45); $bytes.Add([byte]0)
# GS h 80 — barcode height 80 dots
$bytes.Add([byte]0x1D); $bytes.Add([byte]0x68); $bytes.Add([byte]80)
# GS w 3 — barcode width multiplier 3
$bytes.Add([byte]0x1D); $bytes.Add([byte]0x77); $bytes.Add([byte]3)
# GS H 2 — HRI characters below barcode
$bytes.Add([byte]0x1D); $bytes.Add([byte]0x48); $bytes.Add([byte]2)
# GS k 73 — CODE128 barcode
$barcodeBytes = [System.Text.Encoding]::ASCII.GetBytes($barcode)
$bytes.Add([byte]0x1D); $bytes.Add([byte]0x6B); $bytes.Add([byte]0x49)
$bytes.Add([byte]$barcodeBytes.Length)
foreach ($b in $barcodeBytes) { $bytes.Add($b) }
$bytes.Add([byte]0x0A)
# Product name line 1
foreach ($b in [System.Text.Encoding]::ASCII.GetBytes($nameLine1)) { $bytes.Add($b) }
$bytes.Add([byte]0x0A)
if ($nameLine2) {
    foreach ($b in [System.Text.Encoding]::ASCII.GetBytes($nameLine2)) { $bytes.Add($b) }
    $bytes.Add([byte]0x0A)
}
# Feed and cut
$bytes.Add([byte]0x0A); $bytes.Add([byte]0x0A)
$bytes.Add([byte]0x1D); $bytes.Add([byte]0x56); $bytes.Add([byte]0x42); $bytes.Add([byte]0x05)

$data = $bytes.ToArray()

$h = [IntPtr]::Zero
if (-not [Spooler]::OpenPrinter($printer, [ref]$h, [IntPtr]::Zero)) {
    Write-Error "Cannot open printer: $printer"; exit 1
}
$doc = New-Object Spooler+DOC_INFO_1
$doc.pDocName = "Barcode"; $doc.pDataType = "RAW"
[Spooler]::StartDocPrinter($h, 1, [ref]$doc) | Out-Null
[Spooler]::StartPagePrinter($h) | Out-Null
$written = 0
[Spooler]::WritePrinter($h, $data, $data.Length, [ref]$written) | Out-Null
[Spooler]::EndPagePrinter($h) | Out-Null
[Spooler]::EndDocPrinter($h) | Out-Null
[Spooler]::ClosePrinter($h) | Out-Null
Write-Host "OK:$written"

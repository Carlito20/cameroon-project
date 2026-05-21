param([string]$printerName = "")

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
if (-not $printerName) {
    $all = Get-Printer | Select-Object -ExpandProperty Name
    Write-Host "Available printers: $($all -join ', ')"

    # Try to find a thermal/receipt printer
    $printerName = $all | Where-Object { $_ -match 'POS|thermal|receipt|munbyn|epson|star|citizen|bixolon' } | Select-Object -First 1

    # Fall back to default printer
    if (-not $printerName) {
        $printerName = (Get-WmiObject -Query "SELECT * FROM Win32_Printer WHERE Default=True").Name
        Write-Host "Using default printer: $printerName"
    } else {
        Write-Host "Using matched printer: $printerName"
    }
}

if (-not $printerName) { Write-Error "No printer found"; exit 1 }

$h = [IntPtr]::Zero
if (-not [Spooler]::OpenPrinter($printerName, [ref]$h, [IntPtr]::Zero)) {
    Write-Error "Cannot open printer: $printerName"; exit 1
}
$doc = New-Object Spooler+DOC_INFO_1
$doc.pDocName = "CashDrawer"; $doc.pDataType = "RAW"
[Spooler]::StartDocPrinter($h, 1, [ref]$doc) | Out-Null
[Spooler]::StartPagePrinter($h) | Out-Null
$bytes = [byte[]](0x1B, 0x70, 0x00, 0x19, 0xFA)
$written = 0
[Spooler]::WritePrinter($h, $bytes, $bytes.Length, [ref]$written) | Out-Null
[Spooler]::EndPagePrinter($h) | Out-Null
[Spooler]::EndDocPrinter($h) | Out-Null
[Spooler]::ClosePrinter($h) | Out-Null
Write-Host "OK:$written"

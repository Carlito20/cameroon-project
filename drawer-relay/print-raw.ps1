param([string]$filePath = "", [string]$printer = "POS-80C")

Add-Type @"
using System;
using System.Runtime.InteropServices;
public class RawSpooler {
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

$data = [System.IO.File]::ReadAllBytes($filePath)
$h = [IntPtr]::Zero
if (-not [RawSpooler]::OpenPrinter($printer, [ref]$h, [IntPtr]::Zero)) {
    Write-Error "Cannot open printer: $printer"; exit 1
}
$doc = New-Object RawSpooler+DOC_INFO_1
$doc.pDocName = "Receipt"; $doc.pDataType = "RAW"
[RawSpooler]::StartDocPrinter($h, 1, [ref]$doc) | Out-Null
[RawSpooler]::StartPagePrinter($h) | Out-Null
$written = 0
[RawSpooler]::WritePrinter($h, $data, $data.Length, [ref]$written) | Out-Null
[RawSpooler]::EndPagePrinter($h) | Out-Null
[RawSpooler]::EndDocPrinter($h) | Out-Null
[RawSpooler]::ClosePrinter($h) | Out-Null
Write-Host "OK:$written"

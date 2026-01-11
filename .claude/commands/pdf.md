---
allowed-tools: Bash(python:*)
argument-hint: <input_file> <output_file> [options]
description: Convert HTML files or URLs to PDF using wkhtmltopdf
---

# HTML to PDF Conversion

Convert HTML documents or web pages to PDF format using the html2pdf conversion agent.

## Usage

```
/pdf input.html output.pdf
/pdf https://example.com webpage.pdf
/pdf input.html output.pdf --landscape --page-size Letter
```

## Arguments

- **input_file**: Path to HTML file or URL to convert
- **output_file**: Output PDF filename
- **options** (optional):
  - `--landscape` - Use landscape orientation
  - `--page-size <size>` - Set page size (A4, Letter, Legal, etc.)
  - `--margin-top <mm>` - Top margin in millimeters
  - `--margin-bottom <mm>` - Bottom margin
  - `--margin-left <mm>` - Left margin
  - `--margin-right <mm>` - Right margin

## Examples

Convert a local HTML file:
```
/pdf report.html report.pdf
```

Convert from a URL:
```
/pdf https://example.com/page webpage.pdf
```

With custom options:
```
/pdf document.html document.pdf --landscape --page-size A4
```

## How It Works

The command runs the html2pdf_agent.py script which uses wkhtmltopdf to perform the conversion. All arguments are passed directly to the agent.

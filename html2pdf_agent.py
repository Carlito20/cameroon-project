#!/usr/bin/env python3
"""
HTML to PDF Conversion Agent
Uses wkhtmltopdf to convert HTML files or URLs to PDF
"""

import subprocess
import sys
import os
from pathlib import Path

# Path to wkhtmltopdf
WKHTMLTOPDF_PATH = r"C:\ProgramData\chocolatey\bin\wkhtmltopdf.exe"

def print_usage():
    """Print usage information"""
    print("""
HTML to PDF Conversion Agent
=============================

Usage:
    python html2pdf_agent.py <input> <output> [options]

Arguments:
    input   - HTML file path or URL to convert
    output  - Output PDF file path

Options:
    --landscape         - Set landscape orientation (default: portrait)
    --page-size <size>  - Set page size (A4, Letter, Legal, etc.)
    --margin-top <mm>   - Set top margin in mm
    --margin-bottom <mm> - Set bottom margin in mm
    --margin-left <mm>  - Set left margin in mm
    --margin-right <mm> - Set right margin in mm

Examples:
    python html2pdf_agent.py myfile.html output.pdf
    python html2pdf_agent.py https://example.com webpage.pdf
    python html2pdf_agent.py input.html output.pdf --landscape --page-size Letter
""")

def convert_html_to_pdf(input_source, output_pdf, options=None):
    """
    Convert HTML to PDF using wkhtmltopdf

    Args:
        input_source: HTML file path or URL
        output_pdf: Output PDF file path
        options: List of additional wkhtmltopdf options
    """
    if options is None:
        options = []

    # Build command
    cmd = [WKHTMLTOPDF_PATH] + options + [input_source, output_pdf]

    try:
        print(f"Converting: {input_source} -> {output_pdf}")
        print(f"Command: {' '.join(cmd)}\n")

        # Run wkhtmltopdf
        result = subprocess.run(cmd, capture_output=True, text=True)

        if result.returncode == 0:
            print(f"\nSuccess! PDF created: {output_pdf}")

            # Show file size
            if os.path.exists(output_pdf):
                size = os.path.getsize(output_pdf)
                print(f"File size: {size:,} bytes ({size/1024:.2f} KB)")
            return True
        else:
            print(f"\nError during conversion!")
            if result.stderr:
                print(f"Error details: {result.stderr}")
            return False

    except FileNotFoundError:
        print(f"Error: wkhtmltopdf not found at {WKHTMLTOPDF_PATH}")
        print("Please check the installation path.")
        return False
    except Exception as e:
        print(f"Error: {e}")
        return False

def main():
    """Main function"""
    args = sys.argv[1:]

    # Check for help or no arguments
    if not args or '--help' in args or '-h' in args:
        print_usage()
        return

    # Need at least input and output
    if len(args) < 2:
        print("Error: Not enough arguments!")
        print_usage()
        return

    input_source = args[0]
    output_pdf = args[1]

    # Parse additional options
    wkhtmltopdf_options = []
    i = 2
    while i < len(args):
        arg = args[i]

        if arg == '--landscape':
            wkhtmltopdf_options.append('--orientation')
            wkhtmltopdf_options.append('Landscape')
            i += 1
        elif arg == '--page-size':
            if i + 1 < len(args):
                wkhtmltopdf_options.append('--page-size')
                wkhtmltopdf_options.append(args[i + 1])
                i += 2
            else:
                print(f"Error: {arg} requires a value")
                return
        elif arg.startswith('--margin-'):
            if i + 1 < len(args):
                wkhtmltopdf_options.append(arg)
                wkhtmltopdf_options.append(args[i + 1])
                i += 2
            else:
                print(f"Error: {arg} requires a value")
                return
        else:
            print(f"Warning: Unknown option '{arg}' - ignoring")
            i += 1

    # Validate input (if it's a file, check it exists)
    if not input_source.startswith('http://') and not input_source.startswith('https://'):
        if not os.path.exists(input_source):
            print(f"Error: Input file not found: {input_source}")
            return

    # Convert
    convert_html_to_pdf(input_source, output_pdf, wkhtmltopdf_options)

if __name__ == '__main__':
    main()

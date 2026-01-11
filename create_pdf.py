#!/usr/bin/env python3
"""
Convert the business plan markdown to a professional PDF
"""
import os
import sys

try:
    # Try using markdown and weasyprint
    import markdown
    from weasyprint import HTML, CSS
    USE_WEASYPRINT = True
except ImportError:
    USE_WEASYPRINT = False
    try:
        # Fallback to reportlab
        from reportlab.lib.pagesizes import letter, A4
        from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
        from reportlab.lib.units import inch
        from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, PageBreak
        from reportlab.lib.enums import TA_CENTER, TA_LEFT
        USE_REPORTLAB = True
    except ImportError:
        USE_REPORTLAB = False

def convert_with_weasyprint(md_file, pdf_file):
    """Convert markdown to PDF using weasyprint"""
    with open(md_file, 'r', encoding='utf-8') as f:
        md_content = f.read()

    # Convert markdown to HTML
    html_content = markdown.markdown(md_content, extensions=['tables', 'fenced_code'])

    # Add CSS styling
    html_with_style = f"""
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <style>
            body {{
                font-family: Arial, sans-serif;
                line-height: 1.6;
                max-width: 800px;
                margin: 40px auto;
                padding: 20px;
                color: #333;
            }}
            h1 {{
                color: #2c3e50;
                border-bottom: 3px solid #3498db;
                padding-bottom: 10px;
            }}
            h2 {{
                color: #34495e;
                margin-top: 30px;
                border-bottom: 2px solid #95a5a6;
                padding-bottom: 5px;
            }}
            h3 {{
                color: #7f8c8d;
                margin-top: 20px;
            }}
            ul, ol {{
                margin-left: 20px;
            }}
            code {{
                background: #f4f4f4;
                padding: 2px 6px;
                border-radius: 3px;
            }}
            pre {{
                background: #f4f4f4;
                padding: 15px;
                border-radius: 5px;
                overflow-x: auto;
            }}
            hr {{
                border: none;
                border-top: 1px solid #ddd;
                margin: 30px 0;
            }}
        </style>
    </head>
    <body>
        {html_content}
    </body>
    </html>
    """

    # Convert to PDF
    HTML(string=html_with_style).write_pdf(pdf_file)
    print(f"✓ PDF created successfully: {pdf_file}")

def convert_with_reportlab(md_file, pdf_file):
    """Convert markdown to PDF using reportlab (basic conversion)"""
    with open(md_file, 'r', encoding='utf-8') as f:
        lines = f.readlines()

    # Create PDF
    doc = SimpleDocTemplate(pdf_file, pagesize=letter,
                          topMargin=0.75*inch, bottomMargin=0.75*inch)

    styles = getSampleStyleSheet()
    story = []

    # Custom styles
    title_style = ParagraphStyle(
        'CustomTitle',
        parent=styles['Heading1'],
        fontSize=24,
        textColor='#2c3e50',
        spaceAfter=30,
        alignment=TA_CENTER
    )

    heading2_style = ParagraphStyle(
        'CustomHeading2',
        parent=styles['Heading2'],
        fontSize=16,
        textColor='#34495e',
        spaceAfter=12,
        spaceBefore=20
    )

    for line in lines:
        line = line.strip()
        if not line:
            story.append(Spacer(1, 0.2*inch))
        elif line.startswith('# '):
            text = line[2:]
            story.append(Paragraph(text, title_style))
        elif line.startswith('## '):
            text = line[3:]
            story.append(Paragraph(text, heading2_style))
        elif line.startswith('### '):
            text = line[4:]
            story.append(Paragraph(text, styles['Heading3']))
        elif line.startswith('---'):
            story.append(Spacer(1, 0.3*inch))
        elif line.startswith('- ') or line.startswith('* '):
            text = '• ' + line[2:]
            story.append(Paragraph(text, styles['Normal']))
        else:
            if line and not line.startswith('```'):
                story.append(Paragraph(line, styles['Normal']))

    doc.build(story)
    print(f"✓ PDF created successfully: {pdf_file}")

def main():
    script_dir = os.path.dirname(os.path.abspath(__file__))
    md_file = os.path.join(script_dir, 'Business_Plan_Clean.md')
    pdf_file = os.path.join(script_dir, 'Essential_Goods_CM_Business_Plan.pdf')

    if not os.path.exists(md_file):
        print(f"Error: {md_file} not found!")
        sys.exit(1)

    print("Converting business plan to PDF...")

    if USE_WEASYPRINT:
        print("Using WeasyPrint...")
        convert_with_weasyprint(md_file, pdf_file)
    elif USE_REPORTLAB:
        print("Using ReportLab...")
        convert_with_reportlab(md_file, pdf_file)
    else:
        print("Error: No PDF library available!")
        print("Please install one of the following:")
        print("  pip install markdown weasyprint")
        print("  OR")
        print("  pip install reportlab")
        sys.exit(1)

if __name__ == '__main__':
    main()

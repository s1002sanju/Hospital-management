#!/usr/bin/env python3
"""
CityCare Hospital Appointment Booking System
Exact Academic Project Report Generator
Course: 24CAC631 Cloud Application Development
Degree: Master of Computer Applications (MCA)
Institution: Kumaraguru College of Technology, Coimbatore — 641049
Student: SANJANA S (25MCA047)
Guide: Dr. Hameed Ibrahim
"""

import os
import sys
from reportlab.lib.pagesizes import A4
from reportlab.lib import colors
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, PageBreak, KeepTogether, HRFlowable
)
from reportlab.graphics.shapes import Drawing, Rect, String, Line, Group, Circle
from reportlab.pdfgen import canvas
import pypdf

# Exact A4 dimensions in points: 595.28 x 841.89
# 1 inch margins = 72 pt -> Printable width = 595.28 - 144 = 451.28 pt

class AcademicCanvas(canvas.Canvas):
    """
    Implements exact academic page numbering matching the Anna University / KCT format:
    - Cover page (1): No number
    - Certificate (2): No number
    - Declaration (3): No number
    - Table of Contents (4, 5, 6): No number
    - Abstract (7): 'i'
    - List of Figures (8): 'ii'
    - List of Tables (9): 'iii'
    - List of Abbreviations (10): 'iv'
    - Chapter 1 onwards (11 to 44): Arabic numbers '1', '2', '3', ..., '34'
    """
    def __init__(self, *args, **kwargs):
        super(AcademicCanvas, self).__init__(*args, **kwargs)
        self._saved_page_states = []

    def showPage(self):
        self._saved_page_states.append(dict(self.__dict__))
        self._startPage()

    def save(self):
        num_pages = len(self._saved_page_states)
        for state in self._saved_page_states:
            self.__dict__.update(state)
            self.draw_page_decorations()
            super(AcademicCanvas, self).showPage()
        super(AcademicCanvas, self).save()

    def draw_page_decorations(self):
        page = self._pageNumber
        self.saveState()
        self.setFont("Times-Roman", 11)
        self.setFillColor(colors.black)
        
        # Roman numerals for front matter
        roman_map = {7: "i", 8: "ii", 9: "iii", 10: "iv"}
        if page in roman_map:
            self.drawCentredString(297.64, 36, roman_map[page])
        elif page >= 11:
            arabic_num = str(page - 10)
            self.drawCentredString(297.64, 36, arabic_num)
            
        self.restoreState()


def get_academic_styles():
    styles = getSampleStyleSheet()
    
    # Standard Academic Times New Roman hierarchy
    styles.add(ParagraphStyle(
        'CoverTitle',
        parent=styles['Normal'],
        fontName='Times-Bold',
        fontSize=18,
        leading=24,
        textColor=colors.black,
        alignment=1, # Center
        spaceAfter=15
    ))

    styles.add(ParagraphStyle(
        'CoverSubTitle',
        parent=styles['Normal'],
        fontName='Times-Bold',
        fontSize=13,
        leading=18,
        textColor=colors.black,
        alignment=1,
        spaceAfter=15
    ))

    styles.add(ParagraphStyle(
        'CoverMetaBold',
        parent=styles['Normal'],
        fontName='Times-Bold',
        fontSize=12,
        leading=17,
        textColor=colors.black,
        alignment=1,
        spaceAfter=6
    ))

    styles.add(ParagraphStyle(
        'CoverMetaRegular',
        parent=styles['Normal'],
        fontName='Times-Roman',
        fontSize=12,
        leading=17,
        textColor=colors.black,
        alignment=1,
        spaceAfter=6
    ))

    styles.add(ParagraphStyle(
        'AcademicChapterHeading',
        parent=styles['Normal'],
        fontName='Times-Bold',
        fontSize=13,
        leading=18,
        textColor=colors.black,
        spaceBefore=10,
        spaceAfter=10,
        keepWithNext=True
    ))

    styles.add(ParagraphStyle(
        'AcademicSectionHeading',
        parent=styles['Normal'],
        fontName='Times-Bold',
        fontSize=12,
        leading=16,
        textColor=colors.black,
        spaceBefore=10,
        spaceAfter=6,
        keepWithNext=True
    ))

    styles.add(ParagraphStyle(
        'AcademicBody',
        parent=styles['Normal'],
        fontName='Times-Roman',
        fontSize=11,
        leading=16.5,
        textColor=colors.black,
        spaceAfter=8,
        alignment=4 # Justified
    ))

    styles.add(ParagraphStyle(
        'AcademicBullet',
        parent=styles['Normal'],
        fontName='Times-Roman',
        fontSize=11,
        leading=16,
        textColor=colors.black,
        leftIndent=18,
        firstLineIndent=-12,
        spaceAfter=4,
        alignment=4
    ))

    styles.add(ParagraphStyle(
        'TableHeadText',
        parent=styles['Normal'],
        fontName='Times-Bold',
        fontSize=9.5,
        leading=13,
        textColor=colors.black,
        alignment=1 # Center
    ))

    styles.add(ParagraphStyle(
        'TableBodyText',
        parent=styles['Normal'],
        fontName='Times-Roman',
        fontSize=9,
        leading=12.5,
        textColor=colors.black,
        alignment=0 # Left
    ))

    styles.add(ParagraphStyle(
        'TableBodyCenter',
        parent=styles['Normal'],
        fontName='Times-Roman',
        fontSize=9,
        leading=12.5,
        textColor=colors.black,
        alignment=1 # Center
    ))

    styles.add(ParagraphStyle(
        'TOCChapter',
        parent=styles['Normal'],
        fontName='Times-Bold',
        fontSize=8.5,
        leading=10.5,
        textColor=colors.black
    ))

    styles.add(ParagraphStyle(
        'TOCSection',
        parent=styles['Normal'],
        fontName='Times-Roman',
        fontSize=8,
        leading=10,
        textColor=colors.black,
        leftIndent=12
    ))

    styles.add(ParagraphStyle(
        'TOCPage',
        parent=styles['Normal'],
        fontName='Times-Roman',
        fontSize=8.5,
        leading=10.5,
        textColor=colors.black,
        alignment=1
    ))

    styles.add(ParagraphStyle(
        'FigCaption',
        parent=styles['Normal'],
        fontName='Times-Bold',
        fontSize=10.5,
        leading=14,
        textColor=colors.black,
        alignment=1,
        spaceBefore=6,
        spaceAfter=10
    ))

    styles.add(ParagraphStyle(
        'CodeBox',
        parent=styles['Normal'],
        fontName='Courier',
        fontSize=8.5,
        leading=11.5,
        textColor=colors.black
    ))

    return styles

def create_styled_table(data, col_widths, is_header=True, custom_padding=4):
    t = Table(data, colWidths=col_widths, repeatRows=1 if is_header else 0)
    ts = [
        ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor('#D9E1F2')),
        ('TEXTCOLOR', (0, 0), (-1, 0), colors.black),
        ('ALIGN', (0, 0), (-1, -1), 'LEFT'),
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('BOTTOMPADDING', (0, 0), (-1, -1), custom_padding),
        ('TOPPADDING', (0, 0), (-1, -1), custom_padding),
        ('LEFTPADDING', (0, 0), (-1, -1), 4),
        ('RIGHTPADDING', (0, 0), (-1, -1), 4),
        ('GRID', (0, 0), (-1, -1), 0.5, colors.black),
    ]
    t.setStyle(TableStyle(ts))
    return t

print("Academic style module ready.")

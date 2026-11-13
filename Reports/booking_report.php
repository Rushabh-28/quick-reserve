<?php
/** PDF report of all bookings (replaces the old reports that queried tables from another project). */
require_once __DIR__ . '/../includes/bootstrap.php';
$me = require_admin();
require_once __DIR__ . '/fpdf.php';

$rows = db_all('SELECT b.Booking_ref, u.User_name, h.Hotel_name, r.Room_no, b.Check_in_date, b.Check_out_date, b.Total_price, b.Booking_status
                  FROM booking b
                  JOIN `user` u ON u.User_id = b.User_id
                  JOIN room r ON r.Room_id = b.Room_id
                  JOIN hotel h ON h.Hotel_id = r.Hotel_id
                 ORDER BY b.Booking_id');

class ReportPDF extends FPDF
{
    public array $cols = ['Ref' => 34, 'Guest' => 42, 'Hotel' => 52, 'Room' => 14, 'Check-in' => 27, 'Check-out' => 27, 'Total' => 30, 'Status' => 28];

    function Header(): void
    {
        $this->SetFont('Helvetica', 'B', 15);
        $this->Cell(0, 10, 'Quick Reserve - Booking Report', 0, 1, 'C');
        $this->SetFont('Helvetica', '', 9);
        $this->Cell(0, 6, 'Generated ' . date('Y-m-d H:i'), 0, 1, 'C');
        $this->Ln(2);
        $this->SetFont('Helvetica', 'B', 10);
        $this->SetFillColor(230, 230, 240);
        foreach ($this->cols as $label => $w) {
            $this->Cell($w, 8, $label, 1, 0, 'L', true);
        }
        $this->Ln();
    }

    function Footer(): void
    {
        $this->SetY(-15);
        $this->SetFont('Helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

function pdf_text(string $s): string { return iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $s) ?: $s; }

$pdf = new ReportPDF('L', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Helvetica', '', 9);
$w = array_values($pdf->cols);
$total = 0.0;
foreach ($rows as $r) {
    $pdf->Cell($w[0], 7, (string) $r['Booking_ref'], 1);
    $pdf->Cell($w[1], 7, pdf_text(mb_strimwidth($r['User_name'], 0, 28, '...')), 1);
    $pdf->Cell($w[2], 7, pdf_text(mb_strimwidth($r['Hotel_name'], 0, 32, '...')), 1);
    $pdf->Cell($w[3], 7, pdf_text($r['Room_no']), 1);
    $pdf->Cell($w[4], 7, $r['Check_in_date'], 1);
    $pdf->Cell($w[5], 7, $r['Check_out_date'], 1);
    $pdf->Cell($w[6], 7, number_format((float) $r['Total_price'], 2), 1, 0, 'R');
    $pdf->Cell($w[7], 7, ucfirst($r['Booking_status']), 1);
    $pdf->Ln();
    if ($r['Booking_status'] !== 'cancelled') { $total += (float) $r['Total_price']; }
}
if (!$rows) {
    $pdf->Cell(array_sum($w), 8, 'No bookings yet.', 1, 1, 'C');
}
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->Cell(array_sum($w) - $w[6] - $w[7], 8, 'Total (excluding cancelled)', 1, 0, 'R');
$pdf->Cell($w[6] + $w[7], 8, number_format($total, 2), 1, 1, 'R');
$pdf->Output('I', 'booking-report.pdf');

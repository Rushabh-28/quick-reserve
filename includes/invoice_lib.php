<?php
/** Builds the PDF tax invoice for a paid booking. */
require_once __DIR__ . '/../Reports/fpdf.php';

function pdf_t(string $s): string
{
    return iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $s) ?: $s;
}

class InvoicePDF extends FPDF
{
    function Footer(): void
    {
        $this->SetY(-18);
        $this->SetDrawColor(210, 214, 220);
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(120, 126, 138);
        $this->Cell(0, 5, 'This is a computer-generated invoice and does not require a signature.', 0, 1, 'C');
        $this->Cell(0, 5, 'Thank you for choosing Quick Reserve.', 0, 0, 'C');
    }
}

/** Load everything the invoice needs, or null. */
function invoice_data(string $ref): ?array
{
    $b = db_one('SELECT b.*, u.User_name, u.User_email, u.User_phoneno, u.User_address,
                        r.Room_no, rt.Room_type_name, rt.Room_type_price_per_night AS ppn,
                        h.Hotel_name, h.Hotel_address, h.Hotel_city, h.Hotel_phone_no, h.Hotel_email
                   FROM booking b
                   JOIN `user` u ON u.User_id = b.User_id
                   JOIN room r ON r.Room_id = b.Room_id
                   JOIN room_type rt ON rt.Room_type_id = r.Room_type_id
                   JOIN hotel h ON h.Hotel_id = r.Hotel_id
                  WHERE b.Booking_ref = ?', 's', [$ref]);
    if (!$b) { return null; }
    $b['payments'] = db_all("SELECT * FROM payment WHERE Booking_id = ? AND Status = 'paid' ORDER BY Payment_date", 'i', [(int) $b['Booking_id']]);
    return $b;
}

function build_invoice_pdf(array $b): InvoicePDF
{
    global $qr_config;
    $pdf = new InvoicePDF('P', 'mm', 'A4');
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 22);
    $pdf->AddPage();

    // --- Header
    $logo = __DIR__ . '/../assets/images/qr-logo.png';
    if (is_file($logo)) { $pdf->Image($logo, 15, 14, 52); }
    $pdf->SetFont('Helvetica', 'B', 22);
    $pdf->SetTextColor(13, 110, 110);
    $pdf->SetXY(100, 14);
    $pdf->Cell(95, 10, 'TAX INVOICE', 0, 1, 'R');
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(70, 76, 88);
    $pdf->SetX(100);
    $pdf->Cell(95, 5, pdf_t('Invoice No: ' . ($b['Invoice_no'] ?: 'PENDING')), 0, 1, 'R');
    $pdf->SetX(100);
    $pdf->Cell(95, 5, 'Date: ' . date('d M Y', strtotime(($b['payments'][0]['Payment_date'] ?? $b['Booking_date']))), 0, 1, 'R');
    $pdf->SetX(100);
    $pdf->Cell(95, 5, 'Booking Ref: ' . $b['Booking_ref'], 0, 1, 'R');

    if ($b['Payment_status'] === 'paid') {
        $pdf->SetFont('Helvetica', 'B', 16);
        $pdf->SetTextColor(34, 160, 100);
        $pdf->SetDrawColor(34, 160, 100);
        $pdf->SetXY(150, 40);
        $pdf->Cell(45, 10, 'PAID', 1, 0, 'C');
        $pdf->SetDrawColor(210, 214, 220);
    }
    $pdf->SetTextColor(70, 76, 88);
    $pdf->SetY(54);
    $y = $pdf->GetY();
    // --- Seller / Buyer boxes
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->SetTextColor(13, 110, 110);
    $pdf->SetXY(15, $y); $pdf->Cell(88, 5, 'SOLD BY', 0, 0);
    $pdf->SetXY(108, $y); $pdf->Cell(87, 5, 'BILLED TO', 0, 1);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(40, 44, 52);
    $seller = $qr_config['business_name'] . "\n" . $qr_config['business_address'] . "\n"
            . ($qr_config['business_gstin'] ? 'GSTIN: ' . $qr_config['business_gstin'] . "\n" : '') . $qr_config['business_email'];
    $buyer  = $b['User_name'] . "\n" . $b['User_email'] . "\n" . $b['User_phoneno'] . "\n" . $b['User_address'];
    $pdf->SetXY(15, $y + 5);  $pdf->MultiCell(88, 4.6, pdf_t($seller));
    $endSeller = $pdf->GetY();
    $pdf->SetXY(108, $y + 5); $pdf->MultiCell(87, 4.6, pdf_t($buyer));
    $pdf->SetY(max($endSeller, $pdf->GetY()) + 6);

    // --- Stay details
    $pdf->SetFillColor(240, 247, 247);
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetTextColor(13, 110, 110);
    $pdf->Cell(180, 8, '  STAY DETAILS', 0, 1, 'L', true);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(40, 44, 52);
    $rows = [
        ['Hotel',    $b['Hotel_name'] . ', ' . $b['Hotel_city']],
        ['Address',  $b['Hotel_address']],
        ['Room',     $b['Room_type_name'] . ' (Room ' . $b['Room_no'] . ')'],
        ['Check-in', date('D, d M Y', strtotime($b['Check_in_date']))],
        ['Check-out', date('D, d M Y', strtotime($b['Check_out_date']))],
        ['Guests / Nights', $b['Guests'] . ' guest(s) / ' . nights_between($b['Check_in_date'], $b['Check_out_date']) . ' night(s)'],
    ];
    foreach ($rows as [$k, $v]) {
        $pdf->SetFont('Helvetica', 'B', 9); $pdf->Cell(38, 6.5, '  ' . $k, 0, 0);
        $pdf->SetFont('Helvetica', '', 9);  $pdf->Cell(142, 6.5, pdf_t($v), 0, 1);
    }
    $pdf->Ln(5);

    // --- Charges table
    $nights = nights_between($b['Check_in_date'], $b['Check_out_date']);
    $rate   = gst_rate((float) $b['ppn']);
    $pdf->SetFillColor(13, 110, 110);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(86, 8, '  Description', 0, 0, 'L', true);
    $pdf->Cell(30, 8, 'Rate (Rs.)', 0, 0, 'R', true);
    $pdf->Cell(24, 8, 'Nights', 0, 0, 'R', true);
    $pdf->Cell(40, 8, 'Amount (Rs.)  ', 0, 1, 'R', true);
    $pdf->SetTextColor(40, 44, 52);
    $pdf->SetFont('Helvetica', '', 9);
    $line = function (string $label, string $rateTxt, string $nTxt, string $amt, bool $bold = false) use ($pdf) {
        $pdf->SetFont('Helvetica', $bold ? 'B' : '', 9);
        $pdf->Cell(86, 7.5, '  ' . pdf_t($label), 'B', 0);
        $pdf->Cell(30, 7.5, $rateTxt, 'B', 0, 'R');
        $pdf->Cell(24, 7.5, $nTxt, 'B', 0, 'R');
        $pdf->Cell(40, 7.5, $amt . '  ', 'B', 1, 'R');
    };
    $line('Room charges - ' . $b['Room_type_name'], money($b['ppn']), (string) $nights, money($b['Subtotal']));
    if ((float) $b['Discount'] > 0) {
        $line('Discount (coupon ' . $b['Coupon_code'] . ')', '', '', '- ' . money($b['Discount']));
    }
    $taxable = (float) $b['Subtotal'] - (float) $b['Discount'];
    $line('Taxable value', '', '', money($taxable), true);
    if ($rate > 0) {
        $half = (float) $b['Tax'] / 2;
        $line('CGST @ ' . rtrim(rtrim(number_format($rate / 2, 1), '0'), '.') . '%', '', '', money($half));
        $line('SGST @ ' . rtrim(rtrim(number_format($rate / 2, 1), '0'), '.') . '%', '', '', money((float) $b['Tax'] - $half));
    } else {
        $line('GST (tariff up to Rs. 1,000 per night: exempt)', '', '', money(0));
    }
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->SetFillColor(240, 247, 247);
    $pdf->Cell(140, 10, 'TOTAL  ', 0, 0, 'R', true);
    $pdf->Cell(40, 10, 'Rs. ' . money($b['Total_price']) . '  ', 0, 1, 'R', true);
    $pdf->Ln(6);

    // --- Payments
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetTextColor(13, 110, 110);
    $pdf->Cell(180, 8, '  PAYMENT DETAILS', 0, 1, 'L', true);
    $pdf->SetTextColor(40, 44, 52);
    $pdf->SetFont('Helvetica', 'B', 8.5);
    $pdf->Cell(38, 7, '  Date', 'B', 0); $pdf->Cell(30, 7, 'Method', 'B', 0);
    $pdf->Cell(76, 7, 'Reference', 'B', 0); $pdf->Cell(36, 7, 'Amount (Rs.)  ', 'B', 1, 'R');
    $pdf->SetFont('Helvetica', '', 8.5);
    if (!$b['payments']) {
        $pdf->Cell(180, 7, '  No payment received yet.', 0, 1);
    }
    foreach ($b['payments'] as $p) {
        $pdf->Cell(38, 7, '  ' . date('d M Y H:i', strtotime($p['Payment_date'])), 0, 0);
        $pdf->Cell(30, 7, ucfirst($p['Payment_method']), 0, 0);
        $pdf->Cell(76, 7, pdf_t($p['Gateway_payment_id'] ?: ($p['Gateway_order_id'] ?: '-')), 0, 0);
        $pdf->Cell(36, 7, money($p['Amount']) . '  ', 0, 1, 'R');
    }

    $pdf->Ln(14);
    $pdf->SetTextColor(100, 106, 118);
    $pdf->SetFont('Helvetica', '', 8);
    $pdf->MultiCell(180, 4.2, pdf_t("Terms: Check-in from 12:00, check-out until 11:00. Free cancellation until the day before check-in; refunds for paid bookings are processed to the original payment method within 5-7 working days. Disputes are subject to Bengaluru jurisdiction."));
    return $pdf;
}

/** Send the invoice to the browser. $download = true forces a file download. */
function output_invoice(array $b, bool $download): never
{
    $pdf = build_invoice_pdf($b);
    $name = 'Invoice-' . ($b['Invoice_no'] ?: $b['Booking_ref']) . '.pdf';
    $pdf->Output($download ? 'D' : 'I', $name);
    exit;
}

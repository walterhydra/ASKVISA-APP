<?php
// generate_pdf.php
session_start();
require 'db.php';

if (!isset($_GET['order_id'])) {
    die('Order ID required');
}

$order_id = intval($_GET['order_id']);

// Helper function to determine file type
function getFileType($file_path) {
    $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    
    if (empty($extension) && strpos($file_path, 'fetch_file.php') !== false) {
        // Parse URL to get actual file extension
        $parts = parse_url($file_path);
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
            if (isset($query['path'])) {
                $extension = strtolower(pathinfo($query['path'], PATHINFO_EXTENSION));
            }
        }
    }
    
    $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];
    $pdf_extensions = ['pdf'];
    
    if (in_array($extension, $image_extensions)) {
        return 'image';
    } elseif (in_array($extension, $pdf_extensions)) {
        return 'pdf';
    } else {
        return 'other';
    }
}

// Helper function to get absolute file path
function getAbsoluteFilePath($file_path) {
    $home_dir = dirname($_SERVER['DOCUMENT_ROOT']);
    
    if (strpos($file_path, 'fetch_file.php') !== false) {
        // Parse the fetch_file.php URL
        $parts = parse_url($file_path);
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
            if (isset($query['path'])) {
                return $home_dir . '/gov_id/' . $query['path'];
            }
        }
    } elseif (strpos($file_path, 'gov_id/') === 0) {
        // Relative path starting with gov_id/
        return $home_dir . '/' . $file_path;
    } elseif (strpos($file_path, '/gov_id/') !== false) {
        // Absolute path
        return $home_dir . str_replace('/gov_id/', '/gov_id/', $file_path);
    }
    
    return $file_path;
}

try {
    // Fetch all data for the order
    $stmt = $pdo->prepare("SELECT 
        vo.id as order_id,
        vo.email as order_email,
        vo.phone as order_phone,
        vo.total_amount,
        vo.currency,
        vo.payment_status,
        vo.visa_status,
        vo.created_at as order_date,
        c.country_name,
        c.country_code
        FROM visa_orders vo 
        JOIN countries c ON vo.country_id = c.id 
        WHERE vo.id = ?");
    $stmt->execute([$order_id]);
    $order_info = $stmt->fetch();
    
    if (!$order_info) {
        die('Order not found');
    }
    
    // Fetch all applicants for this order
    $stmt = $pdo->prepare("SELECT 
        a.id as applicant_id,
        a.applicant_no,
        a.applicant_email,
        a.applicant_phone,
        a.visa_status as applicant_visa_status
        FROM applicants a 
        WHERE a.order_id = ? 
        ORDER BY a.applicant_no ASC");
    $stmt->execute([$order_id]);
    $applicants = $stmt->fetchAll();
    
    // Fetch questions for this country
    $stmt = $pdo->prepare("SELECT 
        cq.id as question_id,
        cq.label as question_label,
        cq.field_type,
        cq.field_key
        FROM country_questions cq
        WHERE cq.country_id = (SELECT country_id FROM visa_orders WHERE id = ?)
        ORDER BY cq.sort_order ASC");
    $stmt->execute([$order_id]);
    $questions = $stmt->fetchAll();
    
    // Organize question data
    $question_data = [];
    foreach ($questions as $q) {
        $question_data[$q['question_id']] = $q;
    }
    
    // Fetch all answers for all applicants in this order
    $answers = [];
    $files = [];
    
    $stmt = $pdo->prepare("SELECT 
        aa.order_id,
        aa.applicant_id,
        aa.question_id,
        aa.answer_type,
        aa.answer_text,
        a.applicant_no
        FROM applicant_answers aa
        JOIN applicants a ON aa.applicant_id = a.id
        WHERE aa.order_id = ?
        ORDER BY a.applicant_no ASC, aa.question_id ASC");
    $stmt->execute([$order_id]);
    $answers_result = $stmt->fetchAll();
    
    foreach ($answers_result as $ans) {
        $answers[$ans['applicant_no']][$ans['question_id']] = $ans['answer_text'];
    }
    
    // Fetch all files for this order
    $stmt = $pdo->prepare("SELECT 
        af.order_id,
        af.applicant_id,
        af.question_id,
        af.file_path,
        a.applicant_no
        FROM applicant_files af
        JOIN applicants a ON af.applicant_id = a.id
        WHERE af.order_id = ?
        ORDER BY a.applicant_no ASC, af.question_id ASC");
    $stmt->execute([$order_id]);
    $files_result = $stmt->fetchAll();
    
    // Store file information with paths
    foreach ($files_result as $file) {
        $files[$file['applicant_no']][$file['question_id']] = [
            'path' => $file['file_path'],
            'type' => getFileType($file['file_path'])
        ];
    }
    
    // Load TCPDF
    require_once('tcpdf/tcpdf.php');
    
    // Create new PDF document
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Ask Visa Portal');
    $pdf->SetAuthor('Ask Visa Portal');
    $pdf->SetTitle('Visa Application Summary - Order #' . $order_id);
    $pdf->SetSubject('Visa Application Summary');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 25);
    
    // Add a page
    $pdf->AddPage();
    
    // Title
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->SetTextColor(67, 97, 238); // Blue color
    $pdf->Cell(0, 10, 'Visa Application Summary', 0, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 8, $order_info['country_name'] . ' Visa Application', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 6, 'Order ID: #' . $order_id, 0, 1, 'C');
    $pdf->Cell(0, 6, 'Generated on: ' . date('d-m-Y H:i:s'), 0, 1, 'C');
    
    $pdf->Ln(10);
    
    // Order Information Section
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetFillColor(67, 97, 238); // Blue
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 10, 'Order Information', 0, 1, 'L', true);
    $pdf->Ln(5);
    
    // Reset text color
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 11);
    
    // Order details table
    $order_details = [
        ['Order ID:', '#' . $order_id],
        ['Country:', $order_info['country_name']],
        ['Primary Email:', $order_info['order_email']],
        ['Primary Phone:', $order_info['order_phone']],
        ['Total Amount:', $order_info['currency'] . ' ' . $order_info['total_amount']],
        ['Payment Status:', $order_info['payment_status']],
        ['Visa Status:', $order_info['visa_status']],
        ['Order Date:', date('d-m-Y H:i', strtotime($order_info['order_date']))]
    ];
    
    foreach ($order_details as $detail) {
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(40, 7, $detail[0], 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 11);
        
        if ($detail[0] == 'Payment Status:') {
            $pdf->SetTextColor($detail[1] == 'paid' ? 0 : 255, $detail[1] == 'paid' ? 128 : 0, $detail[1] == 'paid' ? 0 : 0); // Green for paid, red for unpaid
            $pdf->Cell(0, 7, ucfirst($detail[1]), 0, 1, 'L');
            $pdf->SetTextColor(0, 0, 0);
        } else {
            $pdf->Cell(0, 7, $detail[1], 0, 1, 'L');
        }
    }
    
    $pdf->Ln(15);
    
    // Process each applicant
    foreach ($applicants as $index => $applicant) {
        // Start a new page for each applicant (except the first one)
        if ($index > 0) {
            $pdf->AddPage();
        }
        
        // Applicant Header
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetFillColor(76, 175, 80); // Green color
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0, 12, 'Applicant #' . $applicant['applicant_no'], 0, 1, 'L', true);
        $pdf->Ln(8);
        
        // Reset text color
        $pdf->SetTextColor(0, 0, 0);
        
        // Applicant Basic Info
        $pdf->SetFont('helvetica', 'B', 13);
        $pdf->SetTextColor(67, 97, 238);
        $pdf->Cell(0, 8, 'Personal Information', 0, 1, 'L');
        $pdf->SetTextColor(0, 0, 0);
        
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(30, 6, 'Email:', 0, 0, 'L');
        $pdf->Cell(0, 6, $applicant['applicant_email'], 0, 1, 'L');
        $pdf->Cell(30, 6, 'Phone:', 0, 0, 'L');
        $pdf->Cell(0, 6, $applicant['applicant_phone'], 0, 1, 'L');
        $pdf->Cell(30, 6, 'Status:', 0, 0, 'L');
        $pdf->Cell(0, 6, $applicant['applicant_visa_status'], 0, 1, 'L');
        
        $pdf->Ln(10);
        
        // Application Details Section
        $has_details = false;
        $applicant_no = $applicant['applicant_no'];
        
        // Check if this applicant has any answers
        if (isset($answers[$applicant_no]) || isset($files[$applicant_no])) {
            $has_details = true;
            
            $pdf->SetFont('helvetica', 'B', 13);
            $pdf->SetTextColor(67, 97, 238);
            $pdf->Cell(0, 8, 'Application Details', 0, 1, 'L');
            $pdf->SetTextColor(0, 0, 0);
            
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Ln(5);
            
            // Process text answers first
            if (isset($answers[$applicant_no])) {
                foreach ($answers[$applicant_no] as $qid => $answer) {
                    // Skip file answers (they'll be handled separately)
                    if (strpos($answer, 'fetch_file.php') !== false || 
                        strpos($answer, '.jpg') !== false || 
                        strpos($answer, '.jpeg') !== false || 
                        strpos($answer, '.png') !== false || 
                        strpos($answer, '.pdf') !== false) {
                        continue;
                    }
                    
                    if (isset($question_data[$qid])) {
                        $question_label = $question_data[$qid]['question_label'];
                        
                        // Check if we need a page break
                        if ($pdf->GetY() > 250) {
                            $pdf->AddPage();
                        }
                        
                        $pdf->SetFont('helvetica', 'B', 10);
                        $pdf->Cell(0, 6, $question_label . ':', 0, 1, 'L');
                        $pdf->SetFont('helvetica', '', 10);
                        
                        // Handle long answers with MultiCell
                        $pdf->MultiCell(0, 6, $answer, 0, 'L');
                        $pdf->Ln(3);
                    }
                }
            }
            
            // Process files
            if (isset($files[$applicant_no]) && count($files[$applicant_no]) > 0) {
                // Check if we need a page break before starting files section
                if ($pdf->GetY() > 200) {
                    $pdf->AddPage();
                }
                
                $pdf->SetFont('helvetica', 'B', 13);
                $pdf->SetTextColor(67, 97, 238);
                $pdf->Cell(0, 10, 'Attached Files', 0, 1, 'L');
                $pdf->SetTextColor(0, 0, 0);
                $pdf->Ln(5);
                
                foreach ($files[$applicant_no] as $qid => $file_info) {
                    $file_path = $file_info['path'];
                    $file_type = $file_info['type'];
                    $question_label = isset($question_data[$qid]) ? $question_data[$qid]['question_label'] : 'Document';
                    
                    // Extract filename from path
                    $filename = basename($file_path);
                    if (strpos($file_path, 'fetch_file.php') !== false) {
                        // Parse URL to extract filename
                        $parts = parse_url($file_path);
                        if (isset($parts['query'])) {
                            parse_str($parts['query'], $query);
                            if (isset($query['path'])) {
                                $filename = basename($query['path']);
                            }
                        }
                    }
                    
                    // Check if we need a page break
                    if ($pdf->GetY() > 200) {
                        $pdf->AddPage();
                    }
                    
                    // File information header
                    $pdf->SetFont('helvetica', 'B', 11);
                    $pdf->SetFillColor(240, 240, 240);
                    $pdf->Cell(0, 8, $question_label, 0, 1, 'L', true);
                    $pdf->SetFont('helvetica', '', 10);
                    $pdf->Cell(0, 6, 'File: ' . $filename, 0, 1, 'L');
                    
                    // Try to embed image files
                    if ($file_type == 'image') {
                        $full_path = getAbsoluteFilePath($file_path);
                        
                        if (file_exists($full_path)) {
                            // Calculate position and size
                            $page_width = $pdf->getPageWidth() - 30; // Account for margins
                            $max_width = 150; // Maximum image width in mm
                            $max_height = 100; // Maximum image height in mm
                            
                            // Get image dimensions
                            $image_info = @getimagesize($full_path);
                            if ($image_info !== false) {
                                list($img_width, $img_height) = $image_info;
                                
                                // Calculate aspect ratio
                                $aspect_ratio = $img_width / $img_height;
                                
                                // Calculate display dimensions
                                if ($aspect_ratio > 1) {
                                    // Landscape
                                    $display_width = min($max_width, $page_width);
                                    $display_height = $display_width / $aspect_ratio;
                                } else {
                                    // Portrait
                                    $display_height = min($max_height, 150);
                                    $display_width = $display_height * $aspect_ratio;
                                }
                                
                                // Check if we need a page break for the image
                                if ($pdf->GetY() + $display_height > 250) {
                                    $pdf->AddPage();
                                }
                                
                                // Add the image
                                try {
                                    $pdf->Image($full_path, null, $pdf->GetY(), $display_width, $display_height, '', '', 'T', false, 300, '', false, false, 0, false, false, false);
                                    $pdf->Ln($display_height + 5);
                                } catch (Exception $e) {
                                    // If image embedding fails, show a placeholder
                                    $pdf->SetFont('helvetica', 'I', 10);
                                    $pdf->SetTextColor(255, 0, 0);
                                    $pdf->Cell(0, 6, '[Image could not be embedded: ' . $filename . ']', 0, 1, 'L');
                                    $pdf->SetTextColor(0, 0, 0);
                                    $pdf->Ln(5);
                                }
                            } else {
                                // Not a valid image
                                $pdf->SetFont('helvetica', 'I', 10);
                                $pdf->SetTextColor(255, 0, 0);
                                $pdf->Cell(0, 6, '[Invalid image file: ' . $filename . ']', 0, 1, 'L');
                                $pdf->SetTextColor(0, 0, 0);
                                $pdf->Ln(5);
                            }
                        } else {
                            $pdf->SetFont('helvetica', 'I', 10);
                            $pdf->SetTextColor(255, 0, 0);
                            $pdf->Cell(0, 6, '[Image file not found: ' . $filename . ']', 0, 1, 'L');
                            $pdf->SetTextColor(0, 0, 0);
                            $pdf->Ln(5);
                        }
                    } elseif ($file_type == 'pdf') {
                        // For PDF files, show a nice representation
                        if ($pdf->GetY() > 220) {
                            $pdf->AddPage();
                        }
                        
                        // PDF icon representation
                        $pdf->SetFillColor(244, 67, 54); // Red for PDF
                        $pdf->SetTextColor(255, 255, 255);
                        $pdf->SetFont('helvetica', 'B', 11);
                        $pdf->Cell(0, 12, 'PDF Document', 0, 1, 'C', true);
                        $pdf->SetTextColor(0, 0, 0);
                        
                        $pdf->SetFont('helvetica', '', 10);
                        $pdf->Cell(0, 8, 'Filename: ' . $filename, 0, 1, 'C');
                        $pdf->Cell(0, 8, 'Document Type: PDF', 0, 1, 'C');
                        $pdf->Cell(0, 8, 'Stored in application system', 0, 1, 'C');
                        
                        // Add PDF icon using text
                        $pdf->SetFont('helvetica', '', 48);
                        $pdf->SetTextColor(244, 67, 54);
                        $pdf->Cell(0, 20, 'PDF', 0, 1, 'C');
                        $pdf->SetTextColor(0, 0, 0);
                        
                        $pdf->Ln(10);
                    }
                    
                    $pdf->Ln(5);
                }
            }
        }
        
        // If no details, show message
        if (!$has_details) {
            $pdf->SetFont('helvetica', 'I', 11);
            $pdf->SetTextColor(128, 128, 128);
            $pdf->Cell(0, 10, 'No application details available for this applicant.', 0, 1, 'C');
            $pdf->SetTextColor(0, 0, 0);
        }
    }
    
    // Payment Information Section (on a new page)
    if (count($applicants) > 0) {
        $pdf->AddPage();
    }
    
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetFillColor(255, 152, 0); // Orange color
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 12, 'Payment Information', 0, 1, 'L', true);
    $pdf->Ln(10);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 11);
    
    // Fetch payment details
    $stmt = $pdo->prepare("SELECT 
        provider,
        provider_payment_id,
        amount,
        currency,
        status,
        created_at
        FROM payments 
        WHERE order_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1");
    $stmt->execute([$order_id]);
    $payment_info = $stmt->fetch();
    
    if ($payment_info) {
        $payment_data = [
            ['Payment ID:', $payment_info['provider_payment_id']],
            ['Payment Provider:', $payment_info['provider']],
            ['Amount Paid:', $payment_info['currency'] . ' ' . $payment_info['amount']],
            ['Payment Status:', $payment_info['status']],
            ['Payment Date:', date('d-m-Y H:i', strtotime($payment_info['created_at']))]
        ];
        
        foreach ($payment_data as $detail) {
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(40, 8, $detail[0], 0, 0, 'L');
            $pdf->SetFont('helvetica', '', 11);
            
            if ($detail[0] == 'Payment Status:') {
                $pdf->SetTextColor($detail[1] == 'success' ? 0 : 255, $detail[1] == 'success' ? 128 : 0, $detail[1] == 'success' ? 0 : 0);
                $pdf->Cell(0, 8, ucfirst($detail[1]), 0, 1, 'L');
                $pdf->SetTextColor(0, 0, 0);
            } else {
                $pdf->Cell(0, 8, $detail[1], 0, 1, 'L');
            }
        }
    } else {
        $pdf->SetFont('helvetica', 'I', 11);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->Cell(0, 10, 'No payment information available.', 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);
    }
    
    // Footer
    $pdf->SetY(-15);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->SetTextColor(128, 128, 128);
    $pdf->Cell(0, 10, 'Page ' . $pdf->getAliasNumPage() . '/' . $pdf->getAliasNbPages(), 0, 0, 'C');
    
    // Close and output PDF document
    $pdf->Output('Visa_Application_' . $order_id . '_' . date('Ymd_His') . '.pdf', 'D');
    
} catch (Exception $e) {
    // For debugging, you can enable this temporarily:
    // error_log('PDF Generation Error: ' . $e->getMessage());
    // die('Error generating PDF: ' . $e->getMessage());
    
    // For production, show a generic error
    die('Error generating PDF. Please contact support.');
}
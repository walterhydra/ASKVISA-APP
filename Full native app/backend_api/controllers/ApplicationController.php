<?php
class ApplicationController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getQuestions($country_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, label, field_key, field_type, validation_rules, sort_order FROM country_questions WHERE country_id = ? ORDER BY sort_order ASC");
            $stmt->execute([$country_id]);
            $questions = $stmt->fetchAll();
            
            // Also fetch corresponding options for 'select' type questions
            foreach ($questions as &$q) {
                if ($q['field_type'] === 'select') {
                    $optStmt = $this->pdo->prepare("SELECT option_value, option_label FROM question_options WHERE question_id = ? ORDER BY sort_order ASC");
                    $optStmt->execute([$q['id']]);
                    $q['options'] = $optStmt->fetchAll();
                } else {
                    $q['options'] = [];
                }
            }
            jsonResponse(true, ['data' => $questions]);
        } catch (Exception $e) {
            jsonResponse(false, $e->getMessage(), 500);
        }
    }

    public function createOrder() {
        $data = getJsonBody();
        $country_id = $data['country_id'] ?? null;
        $total_people = $data['total_people'] ?? 1;
        $email = $data['email'] ?? '';
        $phone = $data['phone'] ?? '';
        $visa_type_id = $data['visa_type_id'] ?? null;
        $amount = $data['amount'] ?? 0;
        $currency = $data['currency'] ?? 'INR';

        if (!$country_id || !$email || !$phone) {
            jsonResponse(false, 'Missing required fields', 400);
        }

        try {
            $stmt = $this->pdo->prepare("INSERT INTO visa_orders (country_id, visa_type_id, email, phone, total_amount, currency, payment_status, visa_status) VALUES (?, ?, ?, ?, ?, ?, 'pending', 'started')");
            $stmt->execute([$country_id, $visa_type_id, $email, $phone, $amount, $currency]);
            $order_id = $this->pdo->lastInsertId();
            
            jsonResponse(true, ['order_id' => $order_id, 'message' => 'Order created successfully']);
        } catch (Exception $e) {
            jsonResponse(false, $e->getMessage(), 500);
        }
    }

    public function submitApplicants() {
        $data = getJsonBody();
        $order_id = $data['order_id'] ?? null;
        $applicants = $data['applicants'] ?? []; // Array of applicants with their answers

        if (!$order_id || empty($applicants)) {
            jsonResponse(false, 'Missing order_id or applicants data', 400);
        }

        try {
            $this->pdo->beginTransaction();

            foreach ($applicants as $index => $app) {
                $applicant_no = $index + 1;
                $email = $app['email'] ?? '';
                $phone = $app['phone'] ?? '';
                
                $stmt = $this->pdo->prepare("INSERT INTO applicants (order_id, applicant_no, applicant_email, applicant_phone, visa_status) VALUES (?, ?, ?, ?, 'submitted')");
                $stmt->execute([$order_id, $applicant_no, $email, $phone]);
                $applicant_id = $this->pdo->lastInsertId();

                if (!empty($app['answers'])) {
                    foreach ($app['answers'] as $question_id => $answerData) {
                        $answer_type = $answerData['type'] ?? 'text'; // text, file, date, etc
                        $answer_text = $answerData['value'] ?? '';
                        
                        if ($answer_type === 'file') {
                            $ansStmt = $this->pdo->prepare("INSERT INTO applicant_files (order_id, applicant_id, question_id, file_path) VALUES (?, ?, ?, ?)");
                            $ansStmt->execute([$order_id, $applicant_id, $question_id, $answer_text]);
                        } else {
                            $ansStmt = $this->pdo->prepare("INSERT INTO applicant_answers (order_id, applicant_id, question_id, answer_type, answer_text) VALUES (?, ?, ?, ?, ?)");
                            $ansStmt->execute([$order_id, $applicant_id, $question_id, $answer_type, $answer_text]);
                        }
                    }
                }
            }

            $this->pdo->commit();
            jsonResponse(true, ['message' => 'Applicants submitted successfully']);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            jsonResponse(false, $e->getMessage(), 500);
        }
    }

    public function getOrderSummary($order_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM visa_orders WHERE id = ?");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();

            if (!$order) {
                jsonResponse(false, 'Order not found', 404);
            }
            
            jsonResponse(true, ['data' => $order]);
        } catch (Exception $e) {
            jsonResponse(false, $e->getMessage(), 500);
        }
    }
}

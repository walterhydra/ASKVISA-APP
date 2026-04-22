<?php
class CountryController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAllCountries() {
        try {
            $stmt = $this->pdo->query("SELECT id, country_name FROM countries ORDER BY country_name ASC");
            $countries = $stmt->fetchAll();
            jsonResponse(true, ['data' => $countries]);
        } catch (Exception $e) {
            jsonResponse(false, $e->getMessage(), 500);
        }
    }

    public function getVisaTypes($country_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM visa_types WHERE country_id = ?");
            $stmt->execute([$country_id]);
            $visaTypes = $stmt->fetchAll();
            jsonResponse(true, ['data' => $visaTypes]);
        } catch (Exception $e) {
            jsonResponse(false, $e->getMessage(), 500);
        }
    }
}

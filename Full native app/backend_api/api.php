<?php
header('Content-Type: application/json');
require_once 'config.php';

// Simple Router
$request = $_GET['request'] ?? '';
$parts = explode('/', trim($request, '/'));

$resource = $parts[0] ?? '';
$action = $parts[1] ?? '';
$id = $parts[2] ?? null;

$method = $_SERVER['REQUEST_METHOD'];

switch ($resource) {
    case 'countries':
        require_once 'controllers/CountryController.php';
        $controller = new CountryController($pdo);
        if ($method === 'GET') {
            $controller->getAllCountries();
        }
        break;

    case 'visa-types':
        require_once 'controllers/CountryController.php';
        $controller = new CountryController($pdo);
        if ($method === 'GET') {
            $country_id = $_GET['country_id'] ?? null;
            if ($country_id) {
                $controller->getVisaTypes($country_id);
            } else {
                jsonResponse(false, 'country_id is required', 400);
            }
        }
        break;

    case 'questions':
        require_once 'controllers/ApplicationController.php';
        $controller = new ApplicationController($pdo);
        if ($method === 'GET') {
            $country_id = $_GET['country_id'] ?? null;
            if ($country_id) {
                $controller->getQuestions($country_id);
            } else {
                jsonResponse(false, 'country_id is required', 400);
            }
        }
        break;

    case 'orders':
        require_once 'controllers/ApplicationController.php';
        $controller = new ApplicationController($pdo);
        if ($method === 'POST') {
            $controller->createOrder();
        } else if ($method === 'GET' && $action === 'summary' && $id) {
            $controller->getOrderSummary($id);
        }
        break;

    case 'applicants':
        require_once 'controllers/ApplicationController.php';
        $controller = new ApplicationController($pdo);
        if ($method === 'POST') {
            $controller->submitApplicants();
        }
        break;

    case 'upload':
        require_once 'controllers/UploadController.php';
        $controller = new UploadController($pdo);
        if ($method === 'POST') {
            $controller->uploadFile();
        }
        break;

    case 'payment':
        require_once 'controllers/PaymentController.php';
        $controller = new PaymentController($pdo);
        if ($method === 'POST' && $action === 'init') {
            $controller->initPayment();
        } else if ($method === 'POST' && $action === 'verify') {
            $controller->verifyPayment();
        }
        break;

    default:
        jsonResponse(false, 'Endpoint not found', 404);
        break;
}

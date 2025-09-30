<?php
// bootstrap.php

// Load dependencies and SalesforceClient
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/SalesforceClient.php';

$wsdlPath = __DIR__ . '/wsdl.jsp.xml'; // ✅ Ensure the file exists here

// ---------------------
// 🧾 Fetch & Sanitize Input
// ---------------------
$sf_api_key       = isset($_REQUEST['api_key']) ? trim($_REQUEST['api_key']) : '';
$sf_user          = isset($_REQUEST['sf_username']) ? trim($_REQUEST['sf_username']) : '';
$sf_password      = isset($_REQUEST['sf_password']) ? trim($_REQUEST['sf_password']) : '';
$sf_function_name = isset($_REQUEST['functionName']) ? trim($_REQUEST['functionName']) : 'fetchList';
$sf_survey_id     = isset($_REQUEST['survey_id']) ? trim($_REQUEST['survey_id']) : '';
$sf_list          = isset($_REQUEST['sf_list']) && trim($_REQUEST['sf_list']) !== '' ? trim($_REQUEST['sf_list']) : 'Contact';

// ---------------------
// 🔍 Validate Required Parameters
// ---------------------
$missingParams = [];

if (empty($sf_api_key))       $missingParams[] = 'api_key';
if (empty($sf_user))          $missingParams[] = 'sf_username';
if (empty($sf_password))      $missingParams[] = 'sf_password';
if (empty($sf_function_name)) $missingParams[] = 'functionName';
// if (empty($sf_survey_id))  $missingParams[] = 'survey_id'; // Optional?

if (!empty($missingParams)) {
    echo "❌ Missing required parameters: " . implode(', ', $missingParams);
    exit;
}

// ---------------------
// 🔐 Static Salesforce Credentials (for testing)
// ---------------------
$username      = 'raman946@agentforce.com';
$password      = 'aB@123456';
$securityToken = 'RsmpS9ZvW6sXVcuwypJ7uaVvN';

// ---------------------
// 🛠️ Initialize SalesforceClient
// ---------------------
try {
    if (!file_exists($wsdlPath)) {
        throw new Exception("WSDL file not found at: $wsdlPath");
    }

    $sfClient = new SalesforceClient($username, $password, $securityToken, $wsdlPath);

} catch (SoapFault $sf) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'SOAP Fault: ' . $sf->getMessage()
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Client Init Error: ' . $e->getMessage()
    ]);
    exit;
}

// ---------------------
// 🧠 Function Routing
// ---------------------
switch ($sf_function_name) {

    // 📋 Return picklist
    case "fetchList":
        try {
            $_retHtml = "<option>Please select a list</option>";

            switch ($sf_list) {
                case "Contact":
                    $_retHtml .= "<option value='Lead'>Lead</option>";
                    $_retHtml .= "<option value='Contact' selected>Contact</option>";
                    $_retHtml .= "<option value='Case'>Case</option>";
                    break;

                case "Lead":
                    $_retHtml .= "<option value='Lead' selected>Lead</option>";
                    $_retHtml .= "<option value='Contact'>Contact</option>";
                    $_retHtml .= "<option value='Case'>Case</option>";
                    break;

                case "Case":
                    $_retHtml .= "<option value='Lead'>Lead</option>";
                    $_retHtml .= "<option value='Contact'>Contact</option>";
                    $_retHtml .= "<option value='Case' selected>Case</option>";
                    break;

                default:
                    $_retHtml .= "<option value='Lead'>Lead</option>";
                    $_retHtml .= "<option value='Contact'>Contact</option>";
                    $_retHtml .= "<option value='Case'>Case</option>";
                    break;
            }

            echo $_retHtml;

        } catch (Exception $e) {
            echo "fail##InvalidLogIn";
        }
        break;

    // ➕ Add records
    case "add":

        // 🔹 Create Case
        $caseSubject = 'Issue with Order #12345 - ' . date('Y-m-d H:i:s');
        $response = $sfClient->createRecord('Case', [
            'Subject'     => $caseSubject,
            'Description' => 'Customer reported an issue with the recent order.',
            'Priority'    => 'High',
            'Origin'      => 'Web',
            'Status'      => 'New'
        ]);
        echo $response['success'] ? "✅ Case created! ID: {$response['id']}" : "❌ Case error: " . json_encode($response);

        // 🔹 Create Contact
        $response = $sfClient->createRecord('Contact', [
            'FirstName'         => 'zaheen',
            'LastName'          => 'haidar', // Required
            'Email'             => 'john.doe@example.com',
            'Phone'             => '123-456-7890',
            'Title'             => 'Manager',
            'Fax'               => '123-456-7891',
            'Birthdate'         => '1985-07-15',
            'AssistantName'     => 'Jane Assistant',
            'AssistantPhone'    => '123-456-7892',
            'Department'        => 'Sales',
            'Description'       => 'Lead from PHP script.',
            'MailingStreet'     => '123 Main St',
            'MailingCity'       => 'New York',
            'MailingState'      => '',
            'MailingPostalCode' => '10001',
            'MailingCountry'    => '',
            'MobilePhone'       => '987-654-3210',
            'OtherPhone'        => '111-222-3333',
            'HomePhone'         => '444-555-6666',
        ]);
        echo $response['success'] ? "✅ Contact created! ID: {$response['id']}" : "❌ Contact error: " . json_encode($response);

        // 🔹 Create Lead
        $response = $sfClient->createRecord('Lead', [
            'FirstName' => 'haidar',
            'LastName'  => 'Smith',
            'Company'   => 'ACME Corp',
            'Email'     => 'jane@acme.com'
        ]);
        echo $response['success'] ? "✅ Lead created! ID: {$response['id']}" : "❌ Lead error: " . json_encode($response);

        // 🔹 Create Account
        $response = $sfClient->createRecord('Account', [
            'Name'              => 'Proprofs ltd',
            'Phone'             => '123-456-7890',
            'Website'           => 'https://www.acme.com',
            'Industry'          => 'Technology',
            'Type'              => 'Customer',
            'BillingStreet'     => '123 Main St',
            'BillingCity'       => 'San Francisco',
            'BillingState'      => 'CA',
            'BillingPostalCode' => '94105',
            'BillingCountry'    => 'USA',
            'Description'       => 'Created via PHP SOAP client'
        ]);
        echo $response['success'] ? "✅ Account created! ID: {$response['id']}" : "❌ Account error: " . json_encode($response);

        break;

    // ❌ Unknown function
    default:
        echo "❌ Invalid functionName: $sf_function_name";
        break;
}

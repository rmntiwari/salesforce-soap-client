<?php

class SalesforceClient
{
    private $username;
    private $password;
    private $securityToken;
    private $wsdlPath;
    private $client;

    public function __construct($username, $password, $securityToken, $wsdlPath)
    {
        $this->username = $username;
        $this->password = $password;
        $this->securityToken = $securityToken;
        $this->wsdlPath = $wsdlPath;
        $this->login();
    }

    private function login()
    {
        $this->client = new SoapClient($this->wsdlPath, [
            'trace' => 1,
            'exceptions' => 1,
            'connection_timeout' => 30
        ]);

        $loginResult = $this->client->login([
            'username' => $this->username,
            'password' => $this->password . $this->securityToken
        ]);

        // Set session and server URL
        $this->client->__setLocation($loginResult->result->serverUrl);

        $sessionHeader = new SoapHeader(
            'urn:partner.soap.sforce.com',
            'SessionHeader',
            ['sessionId' => $loginResult->result->sessionId]
        );

        $this->client->__setSoapHeaders([$sessionHeader]);
    }   
   
    // Generic method to create any Salesforce object (contact, case, account, lead record)
    public function createRecord(string $objectType, array $fields)
    {
        // Sanity check for required fields
        if (empty($fields)) {
            return ['success' => false, 'error' => 'No data provided for record creation.'];
        }

        // Remove null or empty values
        $filteredFields = array_filter($fields, function ($v) {
            return $v !== null && $v !== '';
        });

        // Create stdClass and assign fields
        $sObjectData = new stdClass();
        foreach ($filteredFields as $key => $value) {
            $sObjectData->$key = $value;
        }

        // Wrap in SoapVar with correct type and namespace
        $soapObject = new SoapVar($sObjectData, SOAP_ENC_OBJECT, $objectType, 'urn:sobject.partner.soap.sforce.com');

        try {
            $createResult = $this->client->create(['sObjects' => [$soapObject]]);

           /*  // Log request/response (optional)
            echo "\n🔵 SOAP Create Request:\n" . $this->client->__getLastRequest();
            echo "\n🟢 SOAP Create Response:\n" . $this->client->__getLastResponse(); */

            $result = is_array($createResult->result) ? $createResult->result[0] : $createResult->result;

            if ($result->success) {
                return ['success' => true, 'id' => $result->id];
            } else {
                return ['success' => false, 'errors' => $result];
            }
        } catch (SoapFault $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'request' => $this->client->__getLastRequest(),
                'response' => $this->client->__getLastResponse()
            ];
        }
    }
}

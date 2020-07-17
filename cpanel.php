<?php

/**
 * \class cpanel
 * \brief This is class which allow to perform 
 * administrative tasks by WHM API 1
 * functions pass user parameters to executeQuery function
 * executeQuery parse html request and return server response
 */
class cpanel
{

    /**
     * class private variables
     * username & password for WHM panel
     * server IP
     */
    private $username;  //login
    private $password;  //hasło     
    private $host;      //serwer

    /**
     * class cpanel constructor
     */
    function __construct($username, $password, $host)
    {
        $pass = json_encode(htmlspecialchars($password));
        $pass = substr($password, 1, -1);
        $this->username = $username;
        $this->password = $pass;
        $this->host = $host;
    }

    /**
     * Authorization parameters setter
     */
    public function setAuthorization($username, $password, $host)
    {
        $this->username = $username;
        $this->password = $password;
        $this->host = $host;
    }

    /**
     * Password getter
     */
    private function getPassword()
    {
        return $this->password;
    }

    /**
     * Username getter
     */
    private function getUsername()
    {
        return $this->username;
    }

    /**
     * Host getter
     */
    private function getHost()
    {
        return $this->host;
    }

    /**
     * Create new account
     * Parse parameters as an associative array
     */
    public function createAccount($username, $domain, $contactemail, $password, $plan)
    {
        return $this->executeQuery('createacct', [
            'username' => $username,
            'domain' => $domain,
            'contactemail' => $contactemail,
            'password' => $password,
            'plan' => $plan
        ]);
    }

    /**
     * Remove existing account
     */
    public function removeAccount($username)
    {
        return $this->executeQuery('removeacct', [
            'username' => $username
        ]);
    }

    /**
     * List account
     */
    public function listAccount($username)
    {
        return $this->executeQuery('listaccts', [
            'search' => $username,
            'searchtype' => 'username'
        ]);
    }

    /**
     * Change account plan
     */
    public function changePlan($username, $plan)
    {
        return $this->executeQuery('changepackage', [
            'user' => $username,
            'pkg' => $plan
        ]);
    }

    /**
     * Request parser
     */
    private function createQuery($host, $request, $parameters = [])
    {
        $parlist = http_build_query($parameters);
        return $query = "https://{$host}/json-api/{$request}?api.version=1&{$parlist}";
    }

    /*
        Request execution by cURL session
        cURL commands based on WHM API 1 documentation
    */
    function executeQuery($request, $parameters = [])
    {
        $username = $this->getUsername();
        $password = $this->getPassword();
        $host = $this->getHost();

        $query = $this->createQuery($host, $request, $parameters);

        $curl = curl_init();                                    // Create Curl Object
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);          // Allow self-signed certs
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);          // Allow certs that do not match the hostname
        curl_setopt($curl, CURLOPT_HEADER, 0);                  // Do not include header in output
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);          // Return contents of transfer on curl_exec
        $header[0] = "Authorization: Basic " . base64_encode($username . ":" . $password) . "\n\r";
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);        // set the username and password
        curl_setopt($curl, CURLOPT_URL, $query);                // execute the query
        $result = curl_exec($curl);

        /**
         * cURL error info
         */
        if ($result == false) {
            echo "curl_exec threw error \"" . curl_error($curl) . "\"";    // error notification            
        }
        /**
         * Check HTTP connection status
         */
        if (!curl_errno($curl)) {
            switch ($http_error = curl_getinfo($curl, CURLINFO_HTTP_CODE)) {
                case 200:
                    echo "Request succeded <br>";
                    break;
                case 403:
                    echo "Invalid credentials <br>";
                    break;
                case 404:
                    echo "Invalid IP adress <br>";
                    break;
                default:
                    echo "Unexpected HTTP error: {$http_error} <br>";
            }
        }
        curl_close($curl);
        echo "$result";
        $json = (json_decode($result, true));
        echo "WHM API error: {$json['cpanelresult']['error']}";
    }
}

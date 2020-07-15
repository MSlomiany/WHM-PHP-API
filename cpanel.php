<?php
class cpanel
{
    private $username;  //login
    private $password;  //hasło     
    private $host;      //serwer

    //konstruktor
    function __construct($username, $password, $host)
    {
        $this->username = $username;
        $this->password = $password;
        $this->host = $host;
    }
    //zmiana parametrów
    public function setAuth($username, $password, $host)
    {
        $this->username = $username;
        $this->password = $password;
        $this->host = $host;
    }
    //zwraca hasło
    private function getPassword()
    {
        return $this->password;
    }
    private function getUsername()
    {
        return $this->username;
    }
    private function getHost()
    {
        return $this->host;
    }

    /*
        Tworzenie nowego konta
        - nazwa
        - domena
        - mail
        - hasło
        - plan 
    */
    public function createAccount($username, $domain, $contactemail, $password, $plan)
    {
    }

    /*
        Usuwanie konta
        - nazwa
    */
    public function removeAccount($username)
    {
    }

    /*
        handler wykonujący zapytania do serwera wraz z autoryzacją
        używa curla
        za dokumentacją WHM API 1
    */
    function runQuery()
    {
        $username = $this->getUsername();
        $password = $this->getPassword();
        $host = $this->getHost();

        $query = "https://127.0.0.1:2087/json-api/listaccts?api.version=1";

        $curl = curl_init();                                    // Create Curl Object
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);          // Allow self-signed certs
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);          // Allow certs that do not match the hostname
        curl_setopt($curl, CURLOPT_HEADER, 0);                  // Do not include header in output
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);          // Return contents of transfer on curl_exec
        $header[0] = "Authorization: Basic " . base64_encode($username . ":" . $password) . "\n\r";
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);        // set the username and password
        curl_setopt($curl, CURLOPT_URL, $query);                // execute the query
        $result = curl_exec($curl);
        if ($result == false) {
            error_log("curl_exec threw error \"" . curl_error($curl) . "\" for $query");    // log error if curl exec fails
            echo "Invalid request";                             // informacja o błędzie                    
        }
        curl_close($curl);
        print $result;
    }
}

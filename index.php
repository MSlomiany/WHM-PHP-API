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
    private $password;  //password/TOKEN 
    private $host;      //server

    /**
     * class cpanel constructor
     */
    function __construct($username, $password, $host)
    {
        $this->username = $username;
        $this->password = $password;
        $this->host = $host;
        echo "New instance of class cpanel has been created. You can change credentials by calling setAuthorization function<br>";
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
     * Pass parameters as an associative array
     * Require username and domain, other parameters are optional
     */
    public function createAccount($username, $domain, $contactemail = '', $password = '', $plan = '')
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
            'searchtype' => 'user'
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
     * Check connection
     */
    public function checkConnection()
    {
        try {
            $this->executeQuery('WHM-PHP-API_by_MS', null);
        } catch (Exception $e) {
            switch ($e->getMessage()) {
                case 403:
                    echo "HTTP error 403: Invalid credentials <br>";
                    break;
                case 404:
                    echo "HTTP error 404: Invalid IP adress <br>";
                    break;
                default:
                    echo "HTTP error: Unexpected HTTP error: {$e->getMessage()} <br>";
            }
        }
    }

    /**
     * Request parser
     */
    private function createQuery($host, $request, $parameters = [])
    {
        if (isset($parameters)) {
            $parlist = http_build_query($parameters);
        } else {
            $parlist = null;
        }
        return $query = "https://{$host}/json-api/{$request}?api.version=1&{$parlist}";
    }

    /**
     * Print results
     */
    private function printResults($data)
    {
        $json = (json_decode($data, true));

        if (isset($json['metadata']['reason'])) {
            echo "Result: {$json['metadata']['reason']}<br>";
            //echo "Result: {$json['data']}<br>";
        } else if (isset($json['cpanel']['error'])) {
            echo "WHM API error: {$json['cpanelresult']['error']}<br>";
        } else {
            echo "Fatal error<br>";
        }
    }

    /*
        Request execution by cURL session
        cURL commands based on WHM API 1 documentation
        evaluate server response
    */
    private function executeQuery($request, $parameters = [])
    {
        $username = $this->getUsername();
        $password = $this->getPassword();
        $host = $this->getHost();

        $query = $this->createQuery($host, $request, $parameters);

        /**
         * I have problems with base64_encode function
         */
        // $password = json_encode(htmlspecialchars($password));
        // $password = substr($password, 1, -1);
        // echo "$password <br>";

        $curl = curl_init();                                    // Create Curl Object
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);          // Allow self-signed certs
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);          // Allow certs that do not match the hostname
        //curl_setopt($curl, CURLOPT_HEADER, 0);                // Do not include header in output
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);          // Return contents of transfer on curl_exec
        //$header[0] = "Authorization: Basic " . base64_encode($username . ":" . $password) . "\n\r";
        $header[0] = "Authorization: whm $username:$password";
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);        // set the username and password
        curl_setopt($curl, CURLOPT_URL, $query);                // execute the query
        $result = curl_exec($curl);

        if ($result == false) {
            echo "curl_exec threw error \"" . curl_error($curl) . "\"";    // error notification            
        }
        /**
         * Check HTTP connection status by throwing exception
         * for checkConnection function
         */
        if ((!curl_errno($curl)) != 200) {
            throw new Exception($http_error = curl_getinfo($curl, CURLINFO_HTTP_CODE));
        } else {
            echo "HTTP status OK: Request succeded <br>";
        }
        curl_close($curl);
        $this->printResults($result);
    }
}

?>

<html>

<body>
    <?php
    session_start();
    /* $cpanel = new cpanel($_POST['login1'], $_POST['token1'], $_POST['server1']); */
    if (isset($_POST['login1']) && isset($_POST['token1']) && isset($_POST['server1'])) {
        $cpanel = new cpanel($_POST['login1'], $_POST['token1'], $_POST['server1']);
        $_SESSION['cpanel'] = $cpanel;
        $_SESSION['login'] = $_POST['login1'];
        $_SESSION['token'] = $_POST['token1'];
        $_SESSION['server'] = $_POST['server1'];
    } else if (isset($_SESSION['cpanel'])) {
        $cpanel = $_SESSION['cpanel'];
    }
    if (isset($_POST['name1']) && isset($_POST['domain1']) && isset($_SESSION['cpanel'])) {
        $cpanel->createAccount($_POST['name1'], $_POST['domain1']);
    }
    if (isset($_POST['name2']) && isset($_SESSION['cpanel'])) {
        $cpanel->removeAccount($_POST['name2']);
    }
    if (isset($_POST['logout'])) {
        session_unset();
        echo "Wylogowano pomyślnie. Użyj ponownie funkcji logowania. <br>";
    }
    ?>
    <form action="" method="POST">
        <p>login: <input type="input" name="login1"></p>
        <p>token: <input type="input" name="token1"></p>
        <p>host: <input type="input" name="server1"></p>
        <p><button type="submit">Zaloguj</button>
    </form>
    <form action="" method="POST">
        <p>name: <input type="input" name="name1"></p>
        <p>domain: <input type="input" name="domain1"></p>
        <p><button type="submit">Utwórz konto</button>
    </form>
    <form action="" method="POST">
        <p>name: <input type="input" name="name2"></p>
        <p><button type="submit">Usuń konto</button>
    </form>
    <form action="" method="POST">
        <p><input type="submit" value="Wyloguj" name="logout">
    </form>
</body>

</html>
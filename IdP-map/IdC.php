<?php
include_once("jwt_helper.php");

class IdC extends JWT {
    protected $bearerCredentials = "";
    protected $bearerToken = "";

    public function __construct($bearerToken, $bearerCredentials) {
        $this->bearerToken = $bearerToken;
        $this->bearerCredentials = $bearerCredentials;
    }

    public function decodeToken() {
        try {
            $decoded = JWT::decode($this->bearerToken, 'secret_server_keys', array('HS256'));
            
            if (($decoded->username == $this->bearerCredentials['username']) &&
                ($decoded->password == $this->bearerCredentials['password']) &&
                ($decoded->exp > time())) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
    }
}
?>

<?php
include_once("jwt_helper.php");
class IdP extends JWT
{
    protected $bearerCredentials = "";
    protected $bearerToken = "";
    protected $credentials = "";
    public function __construct($bearerCredentials)
    {
        $this->bearerCredentials = $bearerCredentials;
    }
    public function getToken()
    {
        $token = JWT::encode(
            $this->bearerCredentials,
            "secret_server_keys"
        );
        return $token;
    }
}
?>
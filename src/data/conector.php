<?php

class Conector {
    private $conexao;

    public function __construct() {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $host = "localhost";
        $user = "root";
        $pass = "";
        $dbname = "condominiodigital";
        $port = 3307;

        $this->conexao = mysqli_connect($host, $user, $pass, $dbname, $port);
    }

    public function getConexao() {
        return $this->conexao;
    }
}
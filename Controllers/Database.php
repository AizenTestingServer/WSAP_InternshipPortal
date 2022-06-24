<?php
require_once "Config.php";

class Database {
    private $serverName = DB_HOST;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $dbName = DB_NAME; 

    private $isConnected = false;
    private $conn;
    private $dsn;
    private $error;
    private $stmt ="";

    public function __construct() {
        $this->dsn = "mysql:host=".$this->serverName.";dbname=".$this->dbName;
        $options = array(PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);
        try {
            $this->conn = new PDO($this->dsn,$this->username,$this->password,$options);
            $this->isConnected = true;
            // echo ($this->isConnected) ? "is connected" : "is not connected";
        } catch(PDOException $e){
            $this->error = $e->getMessage();
            $this->isConnected = false;
        }
    }

    public function query($sql) {
        $this->stmt = $this->conn->prepare($sql);
    }

    public function execute() {
        return $this->stmt->execute();
    }

    public function statement() {
        return $this->stmt;
    }

    public function fetch() {
        return $this->stmt->fetch();
    }
    
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function fetchObject() {
        return $this->stmt->fetch(PDO::FETCH_OBJ);
    }

    public function connected(): bool {
        return $this->isConnected;
    }

    public function getError() {
        return $this->error;
    }

    public function rowCount() {
        return $this->stmt->rowCount();
    }

    function closeStmt() {
        return $this->stmt->closeCursor();
    }

    function setInternId($intern_id) {
        $this->stmt->bindValue(':intern_id', $intern_id);
    }

    function timeIn($attendance) {
        $this->stmt->bindValue(':intern_id', $attendance[0]);
        $this->stmt->bindValue(':att_date', $attendance[1]);
        $this->stmt->bindValue(':time_in', $attendance[2]);
        $this->stmt->bindValue(':time_out', $attendance[3]);
    }

    function timeOut($attendance) {
        $this->stmt->bindValue(':time_out', $attendance[0]);
        $this->stmt->bindValue(':intern_id', $attendance[1]);
        $this->stmt->bindValue(':id', $attendance[2]);
    }

    function setAbsent($attendance) {
        $this->stmt->bindValue(':time_in', $attendance[0]);
        $this->stmt->bindValue(':time_out', $attendance[1]);
        $this->stmt->bindValue(':intern_id', $attendance[2]);
        $this->stmt->bindValue(':id', $attendance[3]);
    }

    function setAttendance($attendance) {
        $this->stmt->bindValue(':intern_id', $attendance[0]);
        $this->stmt->bindValue(':att_date', $attendance[1]);
        $this->stmt->bindValue(':time_in', $attendance[2]);
        $this->stmt->bindValue(':time_out', $attendance[3]);
    }

    function setImg($upload_img) {
        $this->stmt->bindValue(':img', $upload_img[0]);
        $this->stmt->bindValue(':intern_id', $upload_img[1]);
    }

    function setPersonalInfo($personal_info) {
        $this->stmt->bindValue(':last_name', $personal_info[0]);
        $this->stmt->bindValue(':first_name', $personal_info[1]);
        $this->stmt->bindValue(':middle_name', $personal_info[2]);
        $this->stmt->bindValue(':gender', $personal_info[3]);
        $this->stmt->bindValue(':birthday', $personal_info[4]);
        $this->stmt->bindValue(':intern_id', $personal_info[5]);
    }

    function setWSAPInfo($wsap_info) {
        $this->stmt->bindValue(':dept_id', $wsap_info[0]);
        $this->stmt->bindValue(':status', $wsap_info[1]);
        $this->stmt->bindValue(':onboard_date', $wsap_info[2]);
        $this->stmt->bindValue(':email_address', $wsap_info[3]);
        $this->stmt->bindValue(':mobile_number', $wsap_info[4]);
        $this->stmt->bindValue(':mobile_number_2', $wsap_info[5]);
        $this->stmt->bindValue(':intern_id', $wsap_info[6]);
    }
}

?>
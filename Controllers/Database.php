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

    function updateRenderedHours($computed_rendered_hours) {
        $this->stmt->bindValue(':rendered_hours', $computed_rendered_hours[0]);
        $this->stmt->bindValue(':intern_id', $computed_rendered_hours[1]);
    }

    function uploadImage($upload_image) {
        $this->stmt->bindValue(':intern_id', $upload_image[0]);
        $this->stmt->bindValue(':image_path', $upload_image[1]);
    }

    function setProfileImage($profile_image) {
        $this->stmt->bindValue(':image', $profile_image[0]);
        $this->stmt->bindValue(':intern_id', $profile_image[1]);
    }

    function insertPersonalInfo($personal_info) {
        $this->stmt->bindValue(':intern_id', $personal_info[0]);
        $this->stmt->bindValue(':last_name', $personal_info[1]);
        $this->stmt->bindValue(':first_name', $personal_info[2]);
        $this->stmt->bindValue(':middle_name', $personal_info[3]);
    }

    function setPersonalInfo($personal_info) {
        $this->stmt->bindValue(':last_name', $personal_info[0]);
        $this->stmt->bindValue(':first_name', $personal_info[1]);
        $this->stmt->bindValue(':middle_name', $personal_info[2]);
        $this->stmt->bindValue(':gender', $personal_info[3]);
        $this->stmt->bindValue(':birthday', $personal_info[4]);
        $this->stmt->bindValue(':intern_id', $personal_info[5]);
    }

    function insertWSAPInfo($wsap_info) {
        $this->stmt->bindValue(':intern_id', $wsap_info[0]);
        $this->stmt->bindValue(':dept_id', $wsap_info[1]);
        $this->stmt->bindValue(':status', $wsap_info[2]);
        $this->stmt->bindValue(':onboard_date', $wsap_info[3]);
        $this->stmt->bindValue(':target_rendering_hours', $wsap_info[4]);
        $this->stmt->bindValue(':rendered_hours', $wsap_info[5]);
        $this->stmt->bindValue(':email_address', $wsap_info[6]);
        $this->stmt->bindValue(':mobile_number', $wsap_info[7]);
        $this->stmt->bindValue(':mobile_number_2', $wsap_info[8]);
        $this->stmt->bindValue(':image', $wsap_info[9]);
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

    function insertEducationalInfo($educational_info) {
        $this->stmt->bindValue(':intern_id', $educational_info[0]);
        $this->stmt->bindValue(':university', $educational_info[1]);
        $this->stmt->bindValue(':university_abbreviation', $educational_info[2]);
        $this->stmt->bindValue(':course', $educational_info[3]);
        $this->stmt->bindValue(':course_abbreviation', $educational_info[4]);
        $this->stmt->bindValue(':year', $educational_info[5]);
    }

    function setEducationalInfo($educational_info) {
        $this->stmt->bindValue(':university', $educational_info[0]);
        $this->stmt->bindValue(':course', $educational_info[1]);
        $this->stmt->bindValue(':university_abbreviation', $educational_info[2]);
        $this->stmt->bindValue(':course_abbreviation', $educational_info[3]);
        $this->stmt->bindValue(':year', $educational_info[4]);
        $this->stmt->bindValue(':intern_id', $educational_info[5]);
    }

    function insertAccount($account_info) {
        $this->stmt->bindValue(':intern_id', $account_info[0]);
        $this->stmt->bindValue(':password', $account_info[1]);
        $this->stmt->bindValue(':date_created', $account_info[2]);
    }

    function updatePassword($new_password) {
        $this->stmt->bindValue(':password', $new_password[0]);
        $this->stmt->bindValue(':intern_id', $new_password[1]);
    }

    function selectDate($date) {
        $this->stmt->bindValue(':att_date', $date);
    }

    function selectInternsAttendance($interns_attendance) {
        $this->stmt->bindValue(':att_date', $interns_attendance[0]);
        $this->stmt->bindValue(':dept_name', $interns_attendance[1]);
    }

    function selectInternsAttendance2($interns_attendance) {
        $this->stmt->bindValue(':att_date', $interns_attendance[0]);
        $this->stmt->bindValue(':intern_name', $interns_attendance[1]);
    }

    function selectInternsAttendance3($interns_attendance) {
        $this->stmt->bindValue(':att_date', $interns_attendance[0]);
        $this->stmt->bindValue(':dept_name', $interns_attendance[1]);
        $this->stmt->bindValue(':intern_name', $interns_attendance[2]);
    }
}

?>
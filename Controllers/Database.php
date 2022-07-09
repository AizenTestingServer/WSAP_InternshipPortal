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

    function setId($id) {
        $this->stmt->bindValue(":id", $id);
    }

    function setInternId($intern_id) {
        $this->stmt->bindValue(":intern_id", $intern_id);
    }

    function setDeptId($department_id) {
        $this->stmt->bindValue(":department_id", $department_id);
    }

    function setAttDate($date) {
        $this->stmt->bindValue(":att_date", $date);
    }

    function setMonthYear($month_year) {
        $this->stmt->bindValue(":month", $month_year[0]);
        $this->stmt->bindValue(":year", $month_year[1]);
    }

    function setDate($date_value) {
        $this->stmt->bindValue(":day", $date_value[0]);
        $this->stmt->bindValue(":month", $date_value[1]);
        $this->stmt->bindValue(":year", $date_value[2]);
    }

    function setOvertimeData($overtime_data) {
        $this->stmt->bindValue(":intern_id", $overtime_data[0]);
        $this->stmt->bindValue(":start_week_date", $overtime_data[1]);
        $this->stmt->bindValue(":overtime_hours_left", $overtime_data[2]);
    }

    function updateOvertimeData($overtime_data) {
        $this->stmt->bindValue(":overtime_hours_left", $overtime_data[0]);
        $this->stmt->bindValue(":intern_id", $overtime_data[1]);
        $this->stmt->bindValue(":start_week_date", $overtime_data[2]);
    }

    function setOvertimeHoursLeft($new_overtime_hours_left) {
        $this->stmt->bindValue(":overtime_hours_left", $new_overtime_hours_left);
    }

    function timeIn($attendance) {
        $this->stmt->bindValue(":intern_id", $attendance[0]);
        $this->stmt->bindValue(":att_date", $attendance[1]);
        $this->stmt->bindValue(":time_in", $attendance[2]);
        $this->stmt->bindValue(":time_out", $attendance[3]);
        $this->stmt->bindValue(":rendered_hours", $attendance[4]);
    }

    function timeOut($attendance) {
        $this->stmt->bindValue(":time_out", $attendance[0]);
        $this->stmt->bindValue(":id", $attendance[1]);
    }

    function setAttRenderedHours($attendance) {
        $this->stmt->bindValue(":rendered_hours", $attendance[0]);
        $this->stmt->bindValue(":id", $attendance[1]);
    }

    function selectInternIdAndTimeOut($value) {
        $this->stmt->bindValue(":intern_id", $value[0]);
        $this->stmt->bindValue(":time_out", $value[1]);
    }

    function selectTimeOut($time_out) {
        $this->stmt->bindValue(":time_out", $time_out);
    }

    function setAbsent($attendance) {
        $this->stmt->bindValue(":time_in", $attendance[0]);
        $this->stmt->bindValue(":time_out", $attendance[1]);
        $this->stmt->bindValue(":intern_id", $attendance[2]);
        $this->stmt->bindValue(":id", $attendance[3]);
    }

    function setAttendance($attendance) {
        $this->stmt->bindValue(":intern_id", $attendance[0]);
        $this->stmt->bindValue(":att_date", $attendance[1]);
        $this->stmt->bindValue(":time_in", $attendance[2]);
        $this->stmt->bindValue(":time_out", $attendance[3]);
    }

    function updateRenderedHours($computed_rendered_hours) {
        $this->stmt->bindValue(":rendered_hours", $computed_rendered_hours[0]);
        $this->stmt->bindValue(":intern_id", $computed_rendered_hours[1]);
    }

    function setOffboard($offboard) {
        $this->stmt->bindValue(":offboard_date", $offboard[0]);
        $this->stmt->bindValue(":status", $offboard[1]);
        $this->stmt->bindValue(":intern_id", $offboard[2]);
    }

    function uploadImage($upload_image) {
        $this->stmt->bindValue(":intern_id", $upload_image[0]);
        $this->stmt->bindValue(":image_path", $upload_image[1]);
        $this->stmt->bindValue(":image_name", $upload_image[2]);
    }

    function setProfileImage($profile_image) {
        $this->stmt->bindValue(":image", $profile_image[0]);
        $this->stmt->bindValue(":intern_id", $profile_image[1]);
    }

    function selectImage($image) {
        $this->stmt->bindValue(":intern_id", $image[0]);
        $this->stmt->bindValue(":image_name", $image[1]);
    }

    function insertPersonalInfo($personal_info) {
        $this->stmt->bindValue(":intern_id", $personal_info[0]);
        $this->stmt->bindValue(":last_name", $personal_info[1]);
        $this->stmt->bindValue(":first_name", $personal_info[2]);
        $this->stmt->bindValue(":middle_name", $personal_info[3]);
        $this->stmt->bindValue(":gender", $personal_info[4]);
    }

    function setPersonalInfo($personal_info) {
        $this->stmt->bindValue(":last_name", $personal_info[0]);
        $this->stmt->bindValue(":first_name", $personal_info[1]);
        $this->stmt->bindValue(":middle_name", $personal_info[2]);
        $this->stmt->bindValue(":gender", $personal_info[3]);
        $this->stmt->bindValue(":birthdate", $personal_info[4]);
        $this->stmt->bindValue(":intern_id", $personal_info[5]);
    }

    function insertWSAPInfo($wsap_info) {
        $this->stmt->bindValue(":intern_id", $wsap_info[0]);
        $this->stmt->bindValue(":status", $wsap_info[1]);
        $this->stmt->bindValue(":onboard_date", $wsap_info[2]);
        $this->stmt->bindValue(":target_rendering_hours", $wsap_info[3]);
        $this->stmt->bindValue(":email_address", $wsap_info[4]);
        $this->stmt->bindValue(":mobile_number", $wsap_info[5]);
        $this->stmt->bindValue(":mobile_number_2", $wsap_info[6]);
        $this->stmt->bindValue(":image", $wsap_info[7]);
    }

    function setWSAPInfo($wsap_info) {
        $this->stmt->bindValue(":status", $wsap_info[0]);
        $this->stmt->bindValue(":onboard_date", $wsap_info[1]);
        $this->stmt->bindValue(":target_rendering_hours", $wsap_info[2]);
        $this->stmt->bindValue(":email_address", $wsap_info[3]);
        $this->stmt->bindValue(":mobile_number", $wsap_info[4]);
        $this->stmt->bindValue(":mobile_number_2", $wsap_info[5]);
        $this->stmt->bindValue(":image", $wsap_info[6]);
        $this->stmt->bindValue(":intern_id", $wsap_info[7]);
    }

    function setWSAPInfo2($wsap_info) {
        $this->stmt->bindValue(":dept_id", $wsap_info[0]);
        $this->stmt->bindValue(":status", $wsap_info[1]);
        $this->stmt->bindValue(":onboard_date", $wsap_info[2]);
        $this->stmt->bindValue(":offboard_date", $wsap_info[3]);
        $this->stmt->bindValue(":rendered_hours", $wsap_info[4]);
        $this->stmt->bindValue(":target_rendering_hours", $wsap_info[5]);
        $this->stmt->bindValue(":intern_id", $wsap_info[6]);
    }

    function setWSAPInfo3($wsap_info) {
        $this->stmt->bindValue(":email_address", $wsap_info[0]);
        $this->stmt->bindValue(":mobile_number", $wsap_info[1]);
        $this->stmt->bindValue(":mobile_number_2", $wsap_info[2]);
        $this->stmt->bindValue(":intern_id", $wsap_info[3]);
    }

    function setWSAPInfo4($wsap_info) {
        $this->stmt->bindValue(":offboard_date", $wsap_info[0]);
        $this->stmt->bindValue(":intern_id", $wsap_info[1]);
    }

    function insertEducationalInfo($educational_info) {
        $this->stmt->bindValue(":intern_id", $educational_info[0]);
        $this->stmt->bindValue(":university", $educational_info[1]);
        $this->stmt->bindValue(":university_abbreviation", $educational_info[2]);
        $this->stmt->bindValue(":course", $educational_info[3]);
        $this->stmt->bindValue(":course_abbreviation", $educational_info[4]);
        $this->stmt->bindValue(":year", $educational_info[5]);
    }

    function setEducationalInfo($educational_info) {
        $this->stmt->bindValue(":university", $educational_info[0]);
        $this->stmt->bindValue(":course", $educational_info[1]);
        $this->stmt->bindValue(":university_abbreviation", $educational_info[2]);
        $this->stmt->bindValue(":course_abbreviation", $educational_info[3]);
        $this->stmt->bindValue(":year", $educational_info[4]);
        $this->stmt->bindValue(":intern_id", $educational_info[5]);
    }

    function insertAccount($account_info) {
        $this->stmt->bindValue(":intern_id", $account_info[0]);
        $this->stmt->bindValue(":password", $account_info[1]);
        $this->stmt->bindValue(":date_activated", $account_info[2]);
    }

    function updatePassword($new_password) {
        $this->stmt->bindValue(":password", $new_password[0]);
        $this->stmt->bindValue(":intern_id", $new_password[1]);
    }

    function selectInternName($intern_name) {
        $this->stmt->bindValue(":intern_name", $intern_name);
    }

    function selectDepartment($dept_name) {
        $this->stmt->bindValue(":dept_name", $dept_name);
    }

    function selectRoleName($role_name) {
        $this->stmt->bindValue(":role_name", $role_name);
    }

    function selectBrand($brand_name) {
        $this->stmt->bindValue(":brand_name", $brand_name);
    }

    function search($search) {
        $this->stmt->bindValue(":search", $intern_name);
    }

    function setRoleId($role_id) {
        $this->stmt->bindValue(":role_id", $role_id);
    }

    function insertRole($roles) {
        $this->stmt->bindValue(":role_name", $roles[0]);
        $this->stmt->bindValue(":brand_id", $roles[1]);
        $this->stmt->bindValue(":dept_id", $roles[2]);
        $this->stmt->bindValue(":max_overtime_hours", $roles[3]);
        $this->stmt->bindValue(":admin", $roles[4]);
        $this->stmt->bindValue(":level", $roles[5]);
    }

    function updateRole($roles) {
        $this->stmt->bindValue(":role_name", $roles[0]);
        $this->stmt->bindValue(":brand_id", $roles[1]);
        $this->stmt->bindValue(":dept_id", $roles[2]);
        $this->stmt->bindValue(":max_overtime_hours", $roles[3]);
        $this->stmt->bindValue(":admin", $roles[4]);
        $this->stmt->bindValue(":level", $roles[5]);
        $this->stmt->bindValue(":role_id", $roles[6]);
    }

    function assignRole($intern_roles) {
        $this->stmt->bindValue(":intern_id", $intern_roles[0]);
        $this->stmt->bindValue(":role_id", $intern_roles[1]);
        $this->stmt->bindValue(":role_type", $intern_roles[2]);
    }

    function log($log) {
        $this->stmt->bindValue(":timestamp", $log[0]);
        $this->stmt->bindValue(":intern_id", $log[1]);
        $this->stmt->bindValue(":log", $log[2]);
    }

    function selectLog($log) {
        $this->stmt->bindValue(":log", $log);
    }

    function insertTask($task) {
        $this->stmt->bindValue(":intern_id", $task[0]);
        $this->stmt->bindValue(":title", $task[1]);
        $this->stmt->bindValue(":description", $task[2]);
        $this->stmt->bindValue(":start_date", $task[3]);
        $this->stmt->bindValue(":progress", $task[4]);
    }

    function updateTask($task) {
        $this->stmt->bindValue(":title", $task[0]);
        $this->stmt->bindValue(":description", $task[1]);
        $this->stmt->bindValue(":start_date", $task[2]);
        $this->stmt->bindValue(":progress", $task[3]);
        $this->stmt->bindValue(":id", $task[4]);
    }
}

?>
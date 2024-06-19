<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Opname
 *
 * @author dheo
 */
class Opname {
  public $dblink_name = "DBSAP_IIP";
  public function uploadFromCSV($data) {
    $return = array();
    if(count($data) > 0) {
      $conn = new PDO(DB_DSN_PDO,DB_USERNAME,DB_PASSWORD) or die("Database Error");
      //truncate data 1st;
      $sql = "TRUNCATE TABLE production.STO_BATCH_MASTER";
      $stmt = $conn->prepare($sql);
      $exec = $stmt->execute() or die("Truncate Error");
      
      $stmt = null;    
      
      $now = date("d-m-Y");
      
      /*oracle*/
      $sql = "BEGIN ";      
      foreach ($data as $val) {
        if($val == null || $val == "") {
          continue;  
        }else{
          $sql .= "INSERT INTO production.STO_BATCH_MASTER (CHARG, UPDDT) VALUES ('".$val."',to_date('$now','DD-MM-YYYY')); ";
        }
        
      }
      $sql .= " END;";      
      
      //echo $sql;
      /*mysql
      $sql = "INSERT INTO STO_BATCH_MASTER (CHARG, UPDDT) VALUES ";
      $arr_val = array();
      foreach ($data as $val) {
        $str_val = "('$val',STR_TO_DATE('$now','%d-%m-%Y'))";
        $arr_val[] = $str_val;
      }
      $sql .= implode(",",$arr_val);
      */
      $stmt = $conn->prepare($sql) or die("Query Error");
      $exec = $stmt->execute() or die("Insert Error :" .print_r($conn->errorInfo()));
      $return["status"] = true;
      $return["msg"] = "Data Inserted";
      $stmt = null;
      $conn = null;
    } else {
      $return["status"] = false;
      $return["msg"] = "Data Empty";
    }
    return $return;
  }

  public function downloadMaster() {
    $conn = new PDO(DB_DSN_PDO,DB_USERNAME,DB_PASSWORD) or die("Database Error");
    $sql = "SELECT * FROM production.sto_batch_master";
    $data = $this->querySelect($conn, $sql);
    $conn = null;
    return $data;
  }
  
  public function resetDataOpname() {
    $conn = new PDO(DB_DSN_PDO,DB_USERNAME,DB_PASSWORD) or die("Database Error");
        
    $sql = "TRUNCATE TABLE production.STO_BATCH_MASTER";
    $stmt = $conn->prepare($sql);
    if(!$stmt){
      return false;
    }
    $exec = $stmt->execute() or die("Truncate Error");
    $stmt = null;
    
    $sql = "TRUNCATE TABLE production.STO_BATCH_OUTPUT";
    $stmt = $conn->prepare($sql);
    if(!$stmt){
      return false;
    }
    $exec = $stmt->execute() or die("Truncate Error");
    $stmt = null;
    
    $conn = null;
    
    return true;
  }
  
  public function uploadDataOutput($user, $data_output = array()) {
    $return = array();
    if(count($data_output) > 0) {
      $conn = new PDO(DB_DSN_PDO,DB_USERNAME,DB_PASSWORD) or die("Database Error");
      
      $i = 0;
      //oracle
      $sql = "BEGIN ";      
      foreach($data_output as $output) {
        $i++;
        $sql .= "INSERT INTO production.sto_batch_output (charg, status, users) VALUES ('".$output["charg"]."','".$output["status"]."','".$user."'); ";
      }
      $sql .= " END;";
      
      //mysql
      /*$sql = "INSERT INTO sto_batch_output (charg, status, users) VALUES ";
      $arr_val = array();
      foreach($data_output as $output) {
        $i++;
        $arr_val[] = "('".$output["charg"]."','".$output["status"]."','".$user."')";
      }
      $sql .= implode(",",$arr_val);
      */
      $stmt = $conn->prepare($sql);
      $exec = $stmt->execute() or die("Insert Error : ".trim(preg_replace('/\s+/', ' ', $stmt->errorInfo()[2])));
      $return["status"] = "OK";
      $return["msg"] = "Upload done, $i data inserted";
      $conn = null;
    } else {
      $return["status"] = "FAIL";
      $return["msg"] = "Data tidak boleh kosong";
    }
    return $return;
  }
  
  public function downloadDataOutput() {
    $conn = new PDO(DB_DSN_PDO,DB_USERNAME,DB_PASSWORD) or die("Database Error");
    $sql = "select rowid, charg, users, status, to_char(dats1, 'DD.MM.YYYY') as tanggal, to_char(dats1, 'HH24:MI:SS') as jam, to_char(dats1, 'YYYYMMDDHH24MISS') as timez
						from production.STO_BATCH_OUTPUT 
						order by timez asc";
    $data = $this->querySelect($conn, $sql);
    $conn = null;
    return $data;
  }


  public function querySelect($conn, $sql) {
    $data = array();
    $stmt = $conn->prepare($sql);
    if(!$stmt){
      return false;
    }
    if($stmt->execute()){
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data[] = $row;
      }
    } else {
      return false;
    }
      
    $stmt = null;
    return $data;
  }
}

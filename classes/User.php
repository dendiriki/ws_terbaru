<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of User
 *
 * @author dheo
 */
class User {
  //put your code here
  public function login($userid, $userpass, $mac_addr) {
    $return = array();
    if(empty($userid) || empty($userpass) || empty($mac_addr)) {
      $return["status"] = false;
      $return["message"] = "Parameter empty";
    } else {
      $conn = new PDO(DB_DSN_PDO,DB_USERNAME,DB_PASSWORD);
      $sql = "SELECT a.vc_username, a.vc_name, a.vc_emp_del, a.vc_emp_pass, b.rl_id, c.rl_name FROM hrpay.int_mst_user a 
              INNER JOIN hrpay.int_trx_user_role b on b.vc_username = a.vc_username
              INNER JOIN hrpay.int_mst_role c ON c.rl_id = b.rl_id
              WHERE a.vc_username = :userid AND c.rl_name = 'DISPATCH_LOADING'";
      $stmt = $conn->prepare($sql);
      $stmt->bindValue(":userid", strtoupper($userid), PDO::PARAM_STR);
      if($stmt->execute()) {
        $hashpass = null;
        if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
          if($row["VC_EMP_DEL"] == "0") {
            $hashpass = $row["VC_EMP_PASS"];
            if(md5($userpass) == $hashpass) {
              $return["status"] = true;
              $return["username"] = $row["VC_NAME"];
							//insert log
							$sql2 = "INSERT INTO production.SCN_DEV_LOGIN_LOG (mac_addr,vc_username,vc_name,log_date) VALUES (:mac_addr,:vc_username,:vc_name,SYSDATE)";
							$stmt2 = $conn->prepare($sql2);
							$stmt2->bindValue(":mac_addr", strtoupper($mac_addr), PDO::PARAM_STR);
							$stmt2->bindValue(":vc_username", strtoupper($userid), PDO::PARAM_STR);
							$stmt2->bindValue(":vc_name", $row["VC_NAME"], PDO::PARAM_STR);
							$stmt2->execute() or die($stmt2->errorInfo()[2]);
            } else {
              $return["status"] = false;
              $return["message"] = "Wrong Password";
            }
          } else {
            $return["status"] = false;
            $return["message"] = "User INACTIVE, please contact Administrator";
          }
            
        } else {
          $return["status"] = false;
          $return["message"] = "User not found";
        }   
      } else {
        $return["status"] = false;
        $error = $stmt->errorInfo();
        $return["message"] = trim(str_replace("\n", " ", $error[2]));
      }
      $stmt = null;
      $conn = null;
    }
    
    return $return;
  }
  
  public function getName($userid) {    
    if(empty($userid)) {
      return false;
    } else {
      $conn = new PDO(DB_DSN_PDO,DB_USERNAME,DB_PASSWORD);
      $sql = "SELECT a.vc_name FROM hrpay.int_mst_user a 
              WHERE a.vc_username = :userid";
      $stmt = $conn->prepare($sql);
      $stmt->bindValue(":userid", strtoupper($userid), PDO::PARAM_STR);
      if($stmt->execute()) {
        if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
          return $row["VC_NAME"];
        } else {
          return false;
        }   
      } else {
        return false;
      }
    }    
  }
}

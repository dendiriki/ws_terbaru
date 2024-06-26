<?php

set_time_limit(0); //prevent script to be terminated in 30 second
require( "config.php" );

$action = isset($_GET['action']) ? $_GET['action'] : null;

$material = new Material();
$opname = new Opname();
$client = MANDT;

$app_ver = null;
if(isset($_REQUEST["app_ver"])) {
  $app_ver = $_REQUEST["app_ver"];
}

/*if($app_ver != APP_VERION) {
  $data["status"] = "FAIL";
  $data["msg"] = "App Version Mismatch, please bring device to IT for update";
  echo json_encode($data);
  die();
}*/

switch ($action) {
  case "ajax_login" :
    $userid = $_REQUEST["userid"];
    $userpass = $_REQUEST["userpass"];
		$mac_addr = $_REQUEST["mac_addr"];
    $user = new User();
    echo json_encode($user->login($userid, $userpass, $mac_addr));
    break;
  case "connection_check" :
    $data = array();
    $data["status"] = "OK";
    $data["msg"] = "Connection to ".ENVIRONMENT." OK";
    echo json_encode($data);
    break;
  case "download_material" :    
    $matnr = $_REQUEST["matnr"];
    $vbeln = $_REQUEST["vbeln"];
    $wsnum = $_REQUEST["wsnum"];
    $qty = $_REQUEST["qty"];
    $return = array();
    if(empty($vbeln)) {
      $return["status"] = "FAIL";
      $return["msg"] = "Silahkan bawa scanner ke IT untuk di update";
    } else {
      if (empty($matnr)) {
        $return["status"] = "FAIL";
        $return["msg"] = "Material Number Empty";
      } else {
        //cek apakah ada perubahan DO
        $cek_do = $material->cekSingleDO($client, $wsnum, $vbeln, $matnr, $qty);
        if($cek_do["status"] == true) {
          //get material details by material number
          $return = $material->getClassMaster($client, $matnr);
        } else {
          $return["status"] = "FAIL";
          $return["msg"] = $cek_do["msg"];
        } 
      }
    }
    echo json_encode($return);
    break;
  case "download_stock":
    $return = array();

    $data_la = $_REQUEST["data_la"];
    $error = false;
    if (count($data_la) > 0) {
      $data_stock = array();
      $x = 0;
      foreach ($data_la as $loading_advice) {
        $result = $material->getBatchStock($client, $loading_advice);
        if ($result["status"] == "OK") {
          $cl_data_stock = array_column($data_stock, "charg");
          foreach ($result["stock"] as $stock) {
            $found_stock = array_search($stock["charg"], $cl_data_stock);
            //jika nggak ketemu baru insert
            if ($found_stock === false) {
              if(isset($stock["charg"])) {
                $data_stock[] = $stock;
              }
            }
          }
        } else {
          $error = true;
          $return = $result;
          break;
        }
      }

      if ($error == false) {
        $return["status"] = "OK";
        $arr_batch = array_column($data_stock, 'charg');
        array_multisort($arr_batch, SORT_ASC, $data_stock);
        $return["stock"] = $data_stock;
      }
    } else {
      $return["status"] = "FAIL";
      $return["msg"] = "Material Number Empty";
    }

    echo json_encode($return);
    break;
  case "dl_test_download_stock":
    $return = array();

    $data_la = array();
		$data_la[0]["matnr"]="11002120";
		$data_la[0]["trnum"]="002810138433";
		$data_la[0]["vbeln"]="1810172720";
		$data_la[0]["grade"]="SWRM6";
		$data_la[0]["barcode"]="002810138433110021201810172720020003";
		$data_la[0]["qty"]="3";
		$data_la[0]["posnr"]="020";
		$data_la[0]["size"]="15";
    $error = false;
    if (count($data_la) > 0) {
      //print_r($data_la);
      $data_stock = array();
      $x = 0;
			/*$conn = new PDO(DB_DSN_PDO,DB_USERNAME,DB_PASSWORD) or die("Database Error");
			$matnr = str_pad($data_la[0]["matnr"], 18, '0', STR_PAD_LEFT);
      $sql = "select xchpf from sapsr3.mara@dbsap_iip where mandt = '600' and matnr = '$matnr'";
			$stmt = $conn->prepare($sql) or die("SQL parse error : ".$sql);
			if($stmt->execute() or die( $stmt->errorInfo()[2] )) {
				while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
					$xchpf = strtoupper($row["XCHPF"]);
				}
			}
			die($xchpf);*/
      //$conn = oci_connect(DB_USERNAME, DB_PASSWORD, DB_DSN) or die("Cannot connect to database");
      foreach ($data_la as $loading_advice) {
        echo "<br>Begin Download Stock FROM SAP<br>";
        print_r($loading_advice);
        $result = $material->getBatchStockPDO($client, $loading_advice);
        if ($result["status"] == "OK") {
          $cl_data_stock = array_column($data_stock, "charg");
          foreach ($result["stock"] as $stock) {
            $found_stock = array_search($stock["charg"], $cl_data_stock);
            //jika nggak ketemu baru insert
            if ($found_stock === false) {
              if(isset($stock["charg"])) {
                $data_stock[] = $stock;
              }              
            }
          }
        } else {
          $error = true;
          $return = $result;
          break;
        }
      }

      if ($error == false) {
        $return["status"] = "OK";
        $return["stock"] = $data_stock;
      }
    } else {
      $return["status"] = "FAIL";
      $return["msg"] = "Material Number Empty";
    }

    echo json_encode($return);
    break;
  case "upload_loading_advice" :
    $return = array();
    $data_output = $_REQUEST["data_output"];
		
    if (count($data_output) > 0) {
      //test format data output apakah sudah terbaru
      $cek_output = $data_output[0];
      if(isset($cek_output["coil"])) {
				
        // dengan pengecekan
        // Cek Quantity Sebelum Upload, agar tidak boleh kelebihan/kurang
        // kelompokkan data sesuai trnum, vbeln, posnr, matnr
        // trnum, vbeln, posnr, matnr, charg, qty
        $i=0;
				$trnum = "";
        foreach ($data_output as $val) {
          $data_output[$i]["barcode"] = $val["trnum"].$val["vbeln"].$val["posnr"].$val["matnr"];
          $i++;
					$trnum = $val["trnum"];
        }
				//cek loading advice
				$cek = $material->checkLAQuantity($client, $trnum, $i);
				if(strtoupper($cek["status"]) == "OK") {
					
          //IF OK, then 
          //cek per barcode
					$sum_output = array();
					foreach ($data_output as $output) {
						$key = array_search($output["barcode"], array_column($sum_output, "barcode"));
						if($key === false) {
							$sum_output[] = $output;
						} else {
							continue;
						}
					}
					$cek = $material->checkDOQuantity($client, $sum_output);
					
					if(strtoupper($cek["status"]) == "OK") {
						//IF OK, then upload
						$return = $material->uploadLABatchToSAP($client, $data_output);
					} else {
						$return = $cek;
					}
        } else {
          $return = $cek;
        }					
      } else {
        $return["status"] = "FAIL";
        $return["msg"] = "Silahkan bawa scanner ke IT untuk di update";
      }
        
      /* end of - dengan pengecekan */

      /* tanpa pengecekan */
      // $return = $material->uploadLABatchToSAP($client, $data_output);
      /* end of - tanpa pengecekan */
    } else {
      $return["status"] = "FAIL";
      $return["msg"] = "Belum Scan";
    }
    echo json_encode($return);
    break;
  case "sto_upload_master_batch" :
    if (isset($_FILES["master"])) {
      $master_upload = $_FILES["master"];
      $file_name = $master_upload["name"];
      $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
      if ($file_type === "csv") {
        require(CLASS_PATH . '/spreadsheet-reader/php-excel-reader/excel_reader2.php');

        require(CLASS_PATH . '/spreadsheet-reader/SpreadsheetReader.php');
        $file_tmp = $master_upload["tmp_name"];
        $Reader = new SpreadsheetReader($file_tmp);
        $i = 0;
        $data_count = 0;
        $data_batch = array();
        
        foreach ($Reader as $row) {
          if($i == 0) {
            if(is_int($row[0])) {
              $data_batch[] = $row[0];
              $data_count++;
            }
          } else {
            $data_batch[] = $row[0];
            $data_count++;
          }
          $i++;
        }
        //var_dump($data_batch);
        $insert = $opname->uploadFromCSV($data_batch);
        if($insert["status"] == true) {
          echo "<p>Upload Done ".$data_count." data inserted</p>";
          echo "<p><a href='index.php'>Home</a></p>";
        } else {
          echo "<h1>Error</h1>";
          echo "<p>".$insert["msg"]."</p>";
          echo "<a href='index.php'>Home</a>";
        }
      } else {
        echo "<p>Only CSV file allowed</p>";
        echo "<a href='index.php'>Home</a>";
      }
    } else {
      require(TEMPLATE_PATH . "/tmpl_upload_master_batch_stock_op.php");
    }
    break;
  case "sto_reset_data":
    $reset = $opname->resetDataOpname();
    if($reset == true) {
      header("Location: index.php");
    } else {
      echo "<h1>Error</h1>";
    }
    break;
  case "sto_download_master" :
    $data = array();
    $data["master"] = $opname->downloadMaster();
    if(count($data["master"]) == 0 || $data["master"] == false) {
      $data["status"] = "FAIL";
      $data["msg"] = "Data stock empty, please call IT";
    } else {
      $data["status"] = "OK";
      $data["msg"] = "OK";
    }
    echo json_encode($data);
    break;
  case "sto_view_master" :
    $data = array();
    $data["batch"] = $opname->downloadMaster();
    require(TEMPLATE_PATH . "/tmpl_view_master_batch_stock_op.php");
    break;
  case "sto_upload_output" :
    $return = array();
    $data_output = json_decode($_REQUEST["data_output"],true);
    $data_user = $_REQUEST["user"];
    if (count($data_output) > 0) { 
      if(empty($data_user)) {
        $return["status"] = "FAIL";
        $return["msg"] = "USER Upload Kosong";
      } else {
        $return = $opname->uploadDataOutput($data_user, $data_output);
      }
      
    } else {
      $return["status"] = "FAIL";
      $return["msg"] = "Data tidak boleh kosong";
    }
    echo json_encode($return);
    break;
  case "sto_view_upload" :
    $data = array();
    $data["batch"] = $opname->downloadDataOutput();
    require(TEMPLATE_PATH . "/tmpl_view_output_batch_op.php");
    break;
  default:
    echo "<h1>ISPAT Barcode Scanner Web Service</h1>";
    echo "<h1>Stock Opname</h1>";
    echo "<ul>";
    echo "<li><a href='?action=sto_upload_master_batch'>Upload Master Batch Stock Opname</a></li>";
    echo "<li><a href='?action=sto_reset_data'>Reset Data Stock Opname</a></li>";
    echo "<li><a href='?action=sto_view_master'>Lihat Data Master</a></li>";
    echo "<li><a href='?action=sto_view_upload'>Lihat Data Output</a></li>";
    echo "</ul>";
    
    echo "<h1>Dispatch Loading</h1>";
    echo "<ul>";
    echo "<li><a href='?action=dl_test_download_stock'>Download Stock</a></li>";
		echo "<li><a href='media/dispatch_loading.apk'>Download Aplikasi Android</a></li>";
    echo "</ul>";
    break;
}
?>
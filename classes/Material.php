<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Material
 *
 * @author dheo
 */
class Material {
  public $dblink_name = "DBSAP_IIP";
  //put your code here
  public function getClassMaster($client, $matnr) {
    $return = array();
    $matnr = str_pad($matnr, 18, '0', STR_PAD_LEFT);
    $conn = oci_connect(DB_USERNAME, DB_PASSWORD, DB_DSN);


    $sql = "SELECT cuobj FROM sapsr3.inob@dbsap_iip WHERE MANDT = :mandt AND objek = :matnr";
    $st = oci_parse($conn, $sql);
    oci_bind_by_name($st, ":mandt", $client, -1, SQLT_CHR);
    oci_bind_by_name($st, ":matnr", $matnr, -1, SQLT_CHR);

    if (!$st) {
      $return["status"] = "FAIL";
      $return["msg"] = oci_error($conn)['message'];
      return $return;
    }

    oci_execute($st);

    if (($row = oci_fetch_assoc($st)) != false) {
      $cuobj = $row["CUOBJ"];
      if (!empty($cuobj)) {
        $st = null;
        $sql = "select atinn, atwrt from sapsr3.ausp@dbsap_iip where mandt = :mandt and objek = :cuobj and klart = '023'";
        $st = oci_parse($conn, $sql);
        oci_bind_by_name($st, ":mandt", $client, -1, SQLT_CHR);
        oci_bind_by_name($st, ":cuobj", $cuobj, -1, SQLT_CHR);
        if (!$st) {
          $return["status"] = "FAIL";
          $return["msg"] = oci_error($conn)['message'];
          return $return;
        }

        oci_execute($st);

        while (($row = oci_fetch_assoc($st)) != false) {
          $return["status"] = "OK";
          $return["matnr"] = ltrim($matnr, '0');
          if ($row["ATINN"] == "0000000816") {
            $return["grade"] = $row["ATWRT"];
          }

          if ($row["ATINN"] == "0000000817") {
            $return["size"] = $row["ATWRT"];
          }
        }
      } else {
        $return["status"] = "FAIL";
        $return["msg"] = "[CUOBJ]No Data Found for material #".$matnr;
      }
    } else {
      $return["status"] = "FAIL";
      $return["msg"] = "[INOB]No Data Found for material #".$matnr;
    }

    oci_free_statement($st);
    oci_close($conn);
	/*$return["status"] = "OK";
	$return["matnr"] = "11002647";
	$return["grade"] = "SAE1006";
	$return["size"] = "8.5";*/
    return $return;
  }

  public function getBatchStock($client, $loading_advice = array()) {
    $return = array();
    $data_length = count($loading_advice);

    if ($data_length > 0) {
      //echo "Connecting to database, please wait...";
      $conn = oci_connect(DB_USERNAME, DB_PASSWORD, DB_DSN) or die("Cannot connect to database");
      $wsnum = str_pad($loading_advice["trnum"], 12, '0', STR_PAD_LEFT);
      $vbeln = $loading_advice["vbeln"];
      $posnr = str_pad($loading_advice["posnr"], 6, '0', STR_PAD_LEFT);
      $qty = (int)$loading_advice["qty"];
      $matnr = str_pad($loading_advice["matnr"], 18, '0', STR_PAD_LEFT);
      //get material batch management status

      $sql = "select xchpf from sapsr3.mara@".$this->dblink_name." where mandt = :mandt and matnr = :matnr";
      $st = oci_parse($conn, $sql) or die("SQL parse error : ".$sql);
      oci_bind_by_name($st, ":mandt", $client, -1, SQLT_CHR);
      oci_bind_by_name($st, ":matnr", $matnr, -1, SQLT_CHR);

      if (!$st) {
        $return["status"] = "FAIL";
        $return["msg"] = oci_error($conn)['message'];
        return $return;
      }
      //echo "Exec SQL : ".$sql."<br>";
      oci_execute($st) or die(oci_error($conn)['message']);
      $xchpf = null;
      while (($row = oci_fetch_assoc($st)) != false) {
        $xchpf = strtoupper($row["XCHPF"]);
      }
      //die("Exec SQL : ".$sql." DONE<br>");
      oci_free_statement($st);
			oci_close($conn);
      //$st = null;
			
      $conn = oci_connect(DB_USERNAME, DB_PASSWORD, DB_DSN) or die("Cannot connect to database");
      $sql = "select bukrs, lfimg from sapsr3.zsd_wb_itm@".$this->dblink_name." where mandt = :mandt and wsnum = :wsnum and vbeln = :vbeln and posnr = :posnr";
      $st = oci_parse($conn, $sql);
      oci_bind_by_name($st, ":mandt", $client, -1, SQLT_CHR);
      oci_bind_by_name($st, ":wsnum", $wsnum, -1, SQLT_CHR);
      oci_bind_by_name($st, ":vbeln", $vbeln, -1, SQLT_CHR);
      oci_bind_by_name($st, ":posnr", $posnr, -1, SQLT_CHR);
      if (!$st) {
        $return["status"] = "FAIL";
        $return["msg"] = oci_error($conn)['message'];
        return $return;
      }
      //echo "Exec SQL : ".$sql."<br>";
      oci_execute($st) or die(oci_error($conn)['message']);
      $lfimg = null;
      $company = "INDO";
      while (($row = oci_fetch_assoc($st)) != false) {
        $lfimg = $row["LFIMG"];
        $company = $row["BUKRS"];
      }
			oci_free_statement($st);
			oci_close($conn);
      //$st = null;
      
      if ($xchpf == "X") {
        //cek status dan S.Loc Delivery Order MTO or MTS
				$conn = oci_connect(DB_USERNAME, DB_PASSWORD, DB_DSN) or die("Cannot connect to database");
        $sql = "select sobkz, lgort from sapsr3.lips@".$this->dblink_name." where mandt = :mandt and vbeln = :vbeln and posnr = :posnr";
        $st = oci_parse($conn, $sql);
        oci_bind_by_name($st, ":mandt", $client, -1, SQLT_CHR);
        oci_bind_by_name($st, ":vbeln", $vbeln, -1, SQLT_CHR);
        oci_bind_by_name($st, ":posnr", $posnr, -1, SQLT_CHR);
        
        if (!$st) {
          $return["status"] = "FAIL";
          $return["msg"] = oci_error($conn)['message'];
          return $return;
        }
        //echo "Exec SQL : ".$sql."<br>";
        oci_execute($st) or die(oci_error($conn)['message']);
        $sobkz = null;
        $lgort = null;        
        while (($row = oci_fetch_assoc($st)) != false) {
          $sobkz = $row["SOBKZ"];
          $lgort = $row["LGORT"];  
        }
        $t_mto = array(); 
        $t_mts = array();
        oci_free_statement($st);
				oci_close($conn);
        if($sobkz == "E") {
          //MTO
          //select nomor SO dan Item SO
          //if MTO tambah kolom nomor DO
					$conn = oci_connect(DB_USERNAME, DB_PASSWORD, DB_DSN) or die("Cannot connect to database");
          $sql = "select vbelv, posnv from sapsr3.vbfa@".$this->dblink_name." where mandt = :mandt and vbeln = :vbeln and posnn = :posnr and vbtyp_n = 'J' and vbtyp_v = 'C'";
          $st = oci_parse($conn, $sql);
          oci_bind_by_name($st, ":mandt", $client, -1, SQLT_CHR);
          oci_bind_by_name($st, ":vbeln", $vbeln, -1, SQLT_CHR);
          oci_bind_by_name($st, ":posnr", $posnr, -1, SQLT_CHR);

          if (!$st) {
            $return["status"] = "FAIL";
            $return["msg"] = oci_error($conn)['message'];
            return $return;
          }
          //echo "Exec SQL : ".$sql."<br>";
          oci_execute($st);
          $vbelv = null;
          $posnv = null;        
          while (($row = oci_fetch_assoc($st)) != false) {
            $vbelv = $row["VBELV"];
            $posnv = str_pad($row["POSNV"], 6, "0", STR_PAD_LEFT);  
          }
          oci_free_statement($st);
					oci_close($conn);
          //oci_free_statement($st);
          $conn = oci_connect(DB_USERNAME, DB_PASSWORD, DB_DSN) or die("Cannot connect to database");
          $sql = "select a.matnr, a.werks, a.lgort, a.charg, a.sobkz, a.vbeln, a.posnr, a.kalab, a.ersda ".
                 " from sapsr3.mska@".$this->dblink_name." a ".
                 " LEFT JOIN sapsr3.zpp_rms_ib_gr_pr@".$this->dblink_name." b on b.z_bundle_no = a.charg ".
                 " where a.mandt = :mandt and a.matnr = :matnr ".
                 " and a.werks = 'INDO' ".
                 " and a.lgort = :lgort ".
                 " and a.vbeln = :vbelv ".
                 " and a.posnr = :posnv ".
                 " and a.kalab > 0 ".
                 " ORDER BY MATNR, CHARG ASC";
          $st = oci_parse($conn, $sql);
          oci_bind_by_name($st, ":mandt", $client, -1, SQLT_CHR);
          oci_bind_by_name($st, ":matnr", $matnr, -1, SQLT_CHR);
          oci_bind_by_name($st, ":lgort", $lgort, -1, SQLT_CHR);
          oci_bind_by_name($st, ":vbelv", $vbelv, -1, SQLT_CHR);
          oci_bind_by_name($st, ":posnv", $posnv, -1, SQLT_CHR);
          
          if (!$st) {
            $return["status"] = "FAIL";
            $return["msg"] = oci_error($conn)['message'];
            return $return;
          }
          //echo "Exec SQL : ".$sql."<br>";
          oci_execute($st) or die(oci_error($conn)['message']);
          while (($row = oci_fetch_assoc($st)) != false) {
            if(trim($row["LGORT"]) == "FINY" || trim($row["LGORT"]) == "REJ") {
              $t_mto[] = $row;              
            }
          } 
					oci_free_statement($st);
					oci_close($conn);
          //oci_free_statement($st);
          $kalab = 0;
          if(count($t_mto) > 0) {
            foreach ($t_mto as $value) {
              $kalab += (float)$value["KALAB"];
            }
            
            $lfimg = round($lfimg, 3, PHP_ROUND_HALF_UP);
            $kalab = round($kalab, 3, PHP_ROUND_HALF_UP);
            $sisa = $kalab - $lfimg;
            if($sisa < 0) {
              $return["status"] = "FAIL";
              $return["msg"] = "Insuficient Customer Stock ".$sisa." for material ". ltrim($matnr, '0') . " Stock = ".$kalab.", Requrement = ".$lfimg;
              return $return;
            }
          } else {
            $return["status"] = "FAIL";
            $return["msg"] = "Customer Stock for material ". ltrim($matnr, '0') . " is not available";
            return $return;
          }
        } else {
          //MTS
					$conn = oci_connect(DB_USERNAME, DB_PASSWORD, DB_DSN) or die("Cannot connect to database");
          $sql = " select a.matnr, a.werks, a.lgort, a.charg, a.clabs, a.ersda ".//, b.zact_date ".
                 " from sapsr3.mchb@".$this->dblink_name." a ".
                 /*" LEFT JOIN sapsr3.zpp_rms_ib_gr_pr@".$this->dblink_name." b on b.z_bundle_no = a.charg ".*/
                 " where a.mandt = :mandt and a.matnr = :matnr ".
                 " and a.werks = 'INDO' ".
                 " and a.lgort = :lgort ".
                 " and a.clabs > 0 ".
                 " ORDER BY MATNR, CHARG ASC ";
          $st = oci_parse($conn, $sql);
          oci_bind_by_name($st, ":mandt", $client, -1, SQLT_CHR);
          oci_bind_by_name($st, ":matnr", $matnr, -1, SQLT_CHR);
          oci_bind_by_name($st, ":lgort", $lgort, -1, SQLT_CHR);
          
          if (!$st) {
            $return["status"] = "FAIL";
            $return["msg"] = oci_error($conn)['message'];
            return $return;
          }
          //echo "Exec SQL : ".$sql."<br>";
          oci_execute($st) or die(oci_error($conn)['message']);      
          while (($row = oci_fetch_assoc($st)) != false) {
            if(trim($row["LGORT"]) == "FINY" || trim($row["LGORT"]) == "REJ") {
              $t_mts[] = $row; 
            }             
          }          
          oci_free_statement($st);
          oci_close($conn);
          if(count($t_mts) > 0) {
            $clabs = 0;
            foreach ($t_mts as $value) {
              $clabs += (float)$value["CLABS"];
            }
            $lfimg = round($lfimg, 3, PHP_ROUND_HALF_UP);
            $clabs = round($clabs, 3, PHP_ROUND_HALF_UP);
            $sisa = $clabs - $lfimg;
            if($sisa < 0) {
              $return["status"] = "FAIL";
              $return["msg"] = "Unrestricted Stock for material ". ltrim($matnr, '0') . " is not available";
              return $return;
            }
          }
        }
        
        $t_stock = array();
        $lnum = 0;
        if(count($t_mto) > 0) {
          foreach ($t_mto as $val) {
            $t_stock[$lnum]["matnr"] = ltrim($val["MATNR"], "0");
            $t_stock[$lnum]["charg"] = $val["CHARG"];
            $t_stock[$lnum]["vbeln"] = $vbeln;
            $t_stock[$lnum]["posnr"] = intval($posnr);
            $t_stock[$lnum]["zdate"] = $val["ZACT_DATE"];
            if(empty($val["ZACT_DATE"])) {
              $t_stock[$lnum]["zdate"] = $val["ERSDA"];
            }
            $t_stock[$lnum]["recom"] = null;
            $lnum++;
          }
        }
        
        if(count($t_mts) > 0) {
          foreach ($t_mts as $val) {
            $t_stock[$lnum]["matnr"] = ltrim($val["MATNR"], "0");
            $t_stock[$lnum]["charg"] = $val["CHARG"];
            $t_stock[$lnum]["vbeln"] = "X";
            $t_stock[$lnum]["posnr"] = "MTS";
            $t_stock[$lnum]["zdate"] = $val["ZACT_DATE"];
            if(empty($val["ZACT_DATE"])) {
              $t_stock[$lnum]["zdate"] = $val["ERSDA"];
            }
            $t_stock[$lnum]["recom"] = null;
            $lnum++;
          }
        }
        
        if(count($t_stock) > 0) {
          $return["status"] = "OK";
          
          $arr_batch = array_column($t_stock, 'charg');
          array_multisort($arr_batch, SORT_ASC, $t_stock);
          $recomended_batch = array();
          $cnt = 0;
          $conn = oci_connect(DB_USERNAME, DB_PASSWORD, DB_DSN) or die("Cannot connect to database");
          $sql = "DELETE FROM sapsr3.zsd_rec_batch@".$this->dblink_name." WHERE mandt = :mandt AND werks = :werks AND wsnum = :wsnum AND vbeln = :vbeln AND posnr = :posnr ";
          $st = oci_parse($conn, $sql);
          oci_bind_by_name($st, ":mandt", $client, -1, SQLT_CHR);
          oci_bind_by_name($st, ":werks", $company, -1, SQLT_CHR);
          oci_bind_by_name($st, ":wsnum", $wsnum, -1, SQLT_CHR);
          oci_bind_by_name($st, ":vbeln", $vbeln, -1, SQLT_CHR);
          oci_bind_by_name($st, ":posnr", $posnr, -1, SQLT_CHR);
          
          if (!$st) {
            $return["status"] = "FAIL";
            $return["msg"] = oci_error($conn)['message'];
            return $return;
          }
          
          oci_execute($st) or die("Delete Recomended Batch Error: ".oci_error($conn)['message']);
          oci_free_statement($st);          
          oci_close($conn);
					
					$conn = oci_connect(DB_USERNAME, DB_PASSWORD, DB_DSN) or die("Cannot connect to database");
          //select batch yang sudah disimpan sebelumnya dengan nomor WSNUM yang sama agar tidak ada rekomendasi yang sama jika ada material yang sama          
          $sql = "SELECT charg FROM sapsr3.zsd_rec_batch@".$this->dblink_name." WHERE mandt = :mandt AND werks = :werks AND wsnum = :wsnum";
          $st = oci_parse($conn, $sql);
          oci_bind_by_name($st, ":mandt", $client, -1, SQLT_CHR);
          oci_bind_by_name($st, ":werks", $company, -1, SQLT_CHR);
          oci_bind_by_name($st, ":wsnum", $wsnum, -1, SQLT_CHR);
          oci_execute($st) or die("Select Recomended Batch Error: ".oci_error($conn)['message']);
          $prev_batch = array();
          while (($row = oci_fetch_assoc($st)) != false) {
            $prev_batch[] = $row["CHARG"];  
          }
          oci_free_statement($st);
          oci_close($conn);
					
					$conn = oci_connect(DB_USERNAME, DB_PASSWORD, DB_DSN) or die("Cannot connect to database");
          $sql = "BEGIN ";
          $y = 0;
          foreach($t_stock as $stock) {
            if($cnt == $qty) {
              break;
            }
            $t_stock[$lnum]["recom"] = "X";
            $found_batch = array_search($stock["charg"], $prev_batch);
            if ($found_batch === false) {
              $recomended_batch[$cnt]["MANDT"] = $client;
              $recomended_batch[$cnt]["WERKS"] = $company;
              $recomended_batch[$cnt]["WSNUM"] = $wsnum;
              $recomended_batch[$cnt]["VBELN"] = $vbeln;
              $recomended_batch[$cnt]["POSNR"] = $posnr;
              $recomended_batch[$cnt]["CHARG"] = $stock["charg"];
              $recomended_batch[$cnt]["ZDATE"] = $stock["zdate"];
              $recomended_batch[$cnt]["PROFL"] = "N";
              $sql .= "INSERT INTO sapsr3.zsd_rec_batch@".$this->dblink_name." VALUES ('$client','$company','$wsnum','$vbeln','$posnr','".$stock["charg"]."','".$stock["zdate"]."','N');";
              $t_stock[$y]["recom"] = "X";
              $cnt++; 
            }
            $y++;
          }
          $sql .= " END;";
          
          $st = oci_parse($conn, $sql);
          if (!$st) {
            $return["status"] = "FAIL";
            $return["msg"] = oci_error($conn)['message'];
            return $return;
          }
          oci_execute($st) or die("Insert Recomended Batch Error: ".oci_error($conn)['message']);
					oci_free_statement($st);
					oci_close($conn);
          //$return["stock"] = $recomended_batch; //buat testing sortingnya
					$return["stock"] = $t_stock;
        } else {
          $return["status"] = "FAIL";
          $return["msg"] = "Sufficient Stock for material " . ltrim($matnr, '0') . " is not available";
        }
      } else {
        $return["status"] = "FAIL";
        $return["msg"] = "Sufficient Stock for material " . ltrim($matnr, '0') . " is not available";
      }
      //oci_close($conn);
    } else {
      $return["status"] == "FAIL";
      $return["msg"] = "Loading Advice Empty";
    }
    return $return;
  }
	
	public function getBatchStockPDO($client, $loading_advice = array()) {
    $return = array();
	echo "OK-";
    $data_length = count($loading_advice);

    if ($data_length > 0) {
      //echo "Connecting to database, please wait...";
      $conn = new PDO(DB_DSN_PDO,DB_USERNAME,DB_PASSWORD) or die("Database Error");
      $wsnum = str_pad($loading_advice["trnum"], 12, '0', STR_PAD_LEFT);
      $vbeln = $loading_advice["vbeln"];
      $posnr = str_pad($loading_advice["posnr"], 6, '0', STR_PAD_LEFT);
      $qty = (int)$loading_advice["qty"];
      $matnr = str_pad($loading_advice["matnr"], 18, '0', STR_PAD_LEFT);
      //get material batch management status
			//die($matnr);
      $sql = "select xchpf from sapsr3.mara@".$this->dblink_name." where mandt = '600' and matnr = '$matnr'";
      $stmt = $conn->prepare($sql) or die("SQL parse error : ".$sql);
			/*$stmt->bindValue(":mandt", $client, PDO::PARAM_STR);
			$stmt->bindValue(":matnr", $matnr, PDO::PARAM_STR);*/
      //echo "Exec SQL : ".$sql."<br>";
			$xchpf = null;
			//die("Before Execute");
			//"1500135542 8100000810"
			if($stmt->execute() or die( $stmt->errorInfo()[2] )) {
				while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
					$xchpf = strtoupper($row["XCHPF"]);
				}
			}
			//die($xchpf);
			$stmt = null;
			$conn = null;	
			echo "-XCHPF";	
      //die("Exec SQL : ".$sql." DONE<br>");
      //$st = null;
			
      $conn = new PDO(DB_DSN_PDO,DB_USERNAME,DB_PASSWORD) or die("Database Error");
      $sql = "select bukrs, lfimg from sapsr3.zsd_wb_itm@".$this->dblink_name." where mandt = :mandt and wsnum = :wsnum and vbeln = :vbeln and posnr = :posnr";
      $stmt = $conn->prepare($sql) or die("SQL parse error : ".$sql);
			$stmt->bindValue(":mandt", $client, PDO::PARAM_STR);
			$stmt->bindValue(":wsnum", $wsnum, PDO::PARAM_STR);
			$stmt->bindValue(":vbeln", $vbeln, PDO::PARAM_STR);
			$stmt->bindValue(":posnr", $posnr, PDO::PARAM_STR);
      //echo "Exec SQL : ".$sql."<br>";
			$lfimg = null;
      $company = "INDO";
			//die("Before SQL ".$sql);
      if($stmt->execute() or die( $stmt->errorInfo()[2] )) {      
				while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
					$lfimg = $row["LFIMG"];
					$company = $row["BUKRS"];
				}
			}
			//die($lfimg);
			$stmt = null;
			$conn = null;
	   echo "-ITM";
      //$st = null;
      
      if ($xchpf == "X") {
        //cek status dan S.Loc Delivery Order MTO or MTS
				$conn = new PDO(DB_DSN_PDO,DB_USERNAME,DB_PASSWORD) or die("Database Error");
        $sql = "select sobkz, lgort from sapsr3.lips@".$this->dblink_name." where mandt = :mandt and vbeln = :vbeln and posnr = :posnr";
        $stmt = $conn->prepare($sql) or die("SQL parse error : ".$sql);
				$stmt->bindValue(":mandt", $client, PDO::PARAM_STR);
				$stmt->bindValue(":vbeln", $vbeln, PDO::PARAM_STR);
				$stmt->bindValue(":posnr", $posnr, PDO::PARAM_STR);
        //echo "Exec SQL : ".$sql."<br>";
				$sobkz = null;
        $lgort = null;
				if($stmt->execute() or die( $stmt->errorInfo()[2] )) {
					while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
						$sobkz = $row["SOBKZ"];
						$lgort = $row["LGORT"];  
					}
				}                
					
        $t_mto = array(); 
        $t_mts = array();
        $stmt = null;
		$conn = null;
		echo "-LIPS";
        if($sobkz == "E") {
          //MTO
          //select nomor SO dan Item SO
          //if MTO tambah kolom nomor DO
					$conn = new PDO(DB_DSN_PDO,DB_USERNAME,DB_PASSWORD) or die("Database Error");
          $sql = "select vbelv, posnv from sapsr3.vbfa@".$this->dblink_name." where mandt = :mandt and vbeln = :vbeln and posnn = :posnr and vbtyp_n = 'J' and vbtyp_v = 'C'";
          $stmt = $conn->prepare($sql) or die("SQL parse error : ".$sql);
          $stmt->bindValue(":mandt", $client, PDO::PARAM_STR);
					$stmt->bindValue(":vbeln", $vbeln, PDO::PARAM_STR);
					$stmt->bindValue(":posnr", $posnr, PDO::PARAM_STR);
					
					$vbelv = null;
          $posnv = null;  
          if ($stmt->execute() or die( $stmt->errorInfo()[2] )) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
							$vbelv = $row["VBELV"];
							$posnv = str_pad($row["POSNV"], 6, "0", STR_PAD_LEFT);  
						}
          }
          //echo "Exec SQL : ".$sql."<br>"; 
						
          $stmt = null;
					$conn = null;
          //oci_free_statement($st);
          $conn = new PDO(DB_DSN_PDO,DB_USERNAME,DB_PASSWORD) or die("Database Error");
          $sql = "select a.matnr, a.werks, a.lgort, a.charg, a.sobkz, a.vbeln, a.posnr, a.kalab, a.ersda ".
                 " from sapsr3.mska@".$this->dblink_name." a ".
                 " LEFT JOIN sapsr3.zpp_rms_ib_gr_pr@".$this->dblink_name." b on b.z_bundle_no = a.charg ".
                 " where a.mandt = :mandt and a.matnr = :matnr ".
                 " and a.werks = 'INDO' ".
                 " and a.lgort = :lgort ".
                 " and a.vbeln = :vbelv ".
                 " and a.posnr = :posnv ".
                 " and a.kalab > 0 ".
                 " ORDER BY MATNR, CHARG ASC";
          $stmt = $conn->prepare($sql) or die("SQL parse error : ".$sql);
					$stmt->bindValue(":mandt", $client, PDO::PARAM_STR);
					$stmt->bindValue(":matnr", $matnr, PDO::PARAM_STR);
					$stmt->bindValue(":lgort", $lgort, PDO::PARAM_STR);
					$stmt->bindValue(":vbelv", $vbelv, PDO::PARAM_STR);
					$stmt->bindValue(":posnv", $posnv, PDO::PARAM_STR);
          
          if ($stmt->execute() or die( $stmt->errorInfo()[2] )) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
							if(trim($row["LGORT"]) == "FINY" || trim($row["LGORT"]) == "REJ") {
								$t_mto[] = $row;              
							}
						} 
          }
          //echo "Exec SQL : ".$sql."<br>";						
					$stmt = null;
					$conn = null;
          //oci_free_statement($st);
          $kalab = 0;
          if(count($t_mto) > 0) {
            foreach ($t_mto as $value) {
              $kalab += (float)$value["KALAB"];
            }
            
            $lfimg = round($lfimg, 3, PHP_ROUND_HALF_UP);
            $kalab = round($kalab, 3, PHP_ROUND_HALF_UP);
            $sisa = $kalab - $lfimg;
            if($sisa < 0) {
              $return["status"] = "FAIL";
              $return["msg"] = "Insuficient Customer Stock ".$sisa." for material ". ltrim($matnr, '0') . " Stock = ".$kalab.", Requrement = ".$lfimg;
              return $return;
            }
          } else {
            $return["status"] = "FAIL";
            $return["msg"] = "Customer Stock for material ". ltrim($matnr, '0') . " is not available";
            return $return;
          }
        } else {
          //MTS
					$conn = new PDO(DB_DSN_PDO,DB_USERNAME,DB_PASSWORD) or die("Database Error");
          $sql = " select a.matnr, a.werks, a.lgort, a.charg, a.clabs, a.ersda ".//, b.zact_date ".
                 " from sapsr3.mchb@".$this->dblink_name." a ".
                 /*" LEFT JOIN sapsr3.zpp_rms_ib_gr_pr@".$this->dblink_name." b on b.z_bundle_no = a.charg ".*/
                 " where a.mandt = :mandt and a.matnr = :matnr ".
                 " and a.werks = 'INDO' ".
                 " and a.lgort = :lgort ".
                 " and a.clabs > 0 ".
                 " ORDER BY MATNR, CHARG ASC ";
          $stmt = $conn->prepare($sql) or die("SQL parse error : ".$sql);
					$stmt->bindValue(":mandt", $client, PDO::PARAM_STR);
					$stmt->bindValue(":matnr", $matnr, PDO::PARAM_STR);
					$stmt->bindValue(":lgort", $lgort, PDO::PARAM_STR);
          
          if ($stmt->execute() or die( $stmt->errorInfo()[2] )) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
							if(trim($row["LGORT"]) == "FINY" || trim($row["LGORT"]) == "REJ") {
								$t_mts[] = $row; 
							}         
						}
          }
		  echo "-MCHB";
          //echo "Exec SQL : ".$sql."<br>";
          $stmt = null;
					$conn = null;
          if(count($t_mts) > 0) {
            $clabs = 0;
            foreach ($t_mts as $value) {
              $clabs += (float)$value["CLABS"];
            }
            $lfimg = round($lfimg, 3, PHP_ROUND_HALF_UP);
            $clabs = round($clabs, 3, PHP_ROUND_HALF_UP);
            $sisa = $clabs - $lfimg;
            if($sisa < 0) {
              $return["status"] = "FAIL";
              $return["msg"] = "Unrestricted Stock for material ". ltrim($matnr, '0') . " is not available";
              return $return;
            }
          }
		  echo "-MCHB2";
        }
        
        $t_stock = array();
        $lnum = 0;
        if(count($t_mto) > 0) {
          foreach ($t_mto as $val) {
            $t_stock[$lnum]["matnr"] = ltrim($val["MATNR"], "0");
            $t_stock[$lnum]["charg"] = $val["CHARG"];
            $t_stock[$lnum]["vbeln"] = $vbeln;
            $t_stock[$lnum]["posnr"] = intval($posnr);
            $t_stock[$lnum]["zdate"] = $val["ZACT_DATE"];
            if(empty($val["ZACT_DATE"])) {
              $t_stock[$lnum]["zdate"] = $val["ERSDA"];
            }
            $t_stock[$lnum]["recom"] = null;
            $lnum++;
          }
        }
        
        if(count($t_mts) > 0) {
          foreach ($t_mts as $val) {
            $t_stock[$lnum]["matnr"] = ltrim($val["MATNR"], "0");
            $t_stock[$lnum]["charg"] = $val["CHARG"];
            $t_stock[$lnum]["vbeln"] = "X";
            $t_stock[$lnum]["posnr"] = "MTS";
            $t_stock[$lnum]["zdate"] = $val["ZACT_DATE"];
            if(empty($val["ZACT_DATE"])) {
              $t_stock[$lnum]["zdate"] = $val["ERSDA"];
            }
            $t_stock[$lnum]["recom"] = null;
            $lnum++;
			print_r ($val["CHARG"]);
			echo "-";
          }
        }
        
        if(count($t_stock) > 0) {
          $return["status"] = "OK";
          
          $arr_batch = array_column($t_stock, 'charg');
          array_multisort($arr_batch, SORT_ASC, $t_stock);
          $recomended_batch = array();
          $cnt = 0;
          $conn = new PDO(DB_DSN_PDO,DB_USERNAME,DB_PASSWORD) or die("Database Error");
          $sql = "DELETE FROM sapsr3.zsd_rec_batch@".$this->dblink_name." WHERE mandt = :mandt AND werks = :werks AND wsnum = :wsnum AND vbeln = :vbeln AND posnr = :posnr ";
          $stmt = $conn->prepare($sql) or die("SQL parse error : ".$sql);
          $stmt->bindValue(":mandt", $client, PDO::PARAM_STR);
					$stmt->bindValue(":werks", $company, PDO::PARAM_STR);
					$stmt->bindValue(":wsnum", $wsnum, PDO::PARAM_STR);
					$stmt->bindValue(":vbeln", $vbeln, PDO::PARAM_STR);
					$stmt->bindValue(":posnr", $posnr, PDO::PARAM_STR);
          $stmt->execute() or die( $stmt->errorInfo()[2] );                    
					$stmt = null;
					$conn = null;
					$conn = new PDO(DB_DSN_PDO,DB_USERNAME,DB_PASSWORD) or die("Database Error");
          //select batch yang sudah disimpan sebelumnya dengan nomor WSNUM yang sama agar tidak ada rekomendasi yang sama jika ada material yang sama          
          $sql = "SELECT charg FROM sapsr3.zsd_rec_batch@".$this->dblink_name." WHERE mandt = :mandt AND werks = :werks AND wsnum = :wsnum";
          $stmt = $conn->prepare($sql) or die("SQL parse error : ".$sql);
          $stmt->bindValue(":mandt", $client, PDO::PARAM_STR);
					$stmt->bindValue(":werks", $company, PDO::PARAM_STR);
					$stmt->bindValue(":wsnum", $wsnum, PDO::PARAM_STR);
					$prev_batch = array();
          if($stmt->execute() or die( $stmt->errorInfo()[2] )) {
						while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
							$prev_batch[] = $row["CHARG"];  
						}
					}
          $stmt = null;
					$conn = null;
					
					$conn = new PDO(DB_DSN_PDO,DB_USERNAME,DB_PASSWORD) or die("Database Error");
          $sql = "BEGIN ";
          $y = 0;
          foreach($t_stock as $stock) {
            if($cnt == $qty) {
              break;
            }
            $t_stock[$lnum]["recom"] = "X";
            $found_batch = array_search($stock["charg"], $prev_batch);
            if ($found_batch === false) {
              $recomended_batch[$cnt]["MANDT"] = $client;
              $recomended_batch[$cnt]["WERKS"] = $company;
              $recomended_batch[$cnt]["WSNUM"] = $wsnum;
              $recomended_batch[$cnt]["VBELN"] = $vbeln;
              $recomended_batch[$cnt]["POSNR"] = $posnr;
              $recomended_batch[$cnt]["CHARG"] = $stock["charg"];
              $recomended_batch[$cnt]["ZDATE"] = $stock["zdate"];
              $recomended_batch[$cnt]["PROFL"] = "N";
              $sql .= "INSERT INTO sapsr3.zsd_rec_batch@".$this->dblink_name." VALUES ('$client','$company','$wsnum','$vbeln','$posnr','".$stock["charg"]."','".$stock["zdate"]."','N');";
              $t_stock[$y]["recom"] = "X";
              $cnt++; 
            }
            $y++;
          }
		  
          $sql .= " END;";
          //print_r ($sql);
		  
		  $stmt = $conn->prepare($sql) or die("SQL parse error : ".$sql);
					$stmt->execute() or die( $stmt->errorInfo()[2] );
          
					$stmt = null;
					$conn = null;
          //$return["stock"] = $recomended_batch; //buat testing sortingnya
					echo "tes2";
					$return["stock"] = $t_stock;
        } else {
          $return["status"] = "FAIL";
          $return["msg"] = "Sufficient Stock for material " . ltrim($matnr, '0') . " is not available";
        }
      } else {
        $return["status"] = "FAIL";
        $return["msg"] = "Sufficient Stock for material " . ltrim($matnr, '0') . " is not available";
      }
      //oci_close($conn);
    } else {
      $return["status"] == "FAIL";
      $return["msg"] = "Loading Advice Empty";
    }
    return $return;
  }
  
  function uploadLABatchToSAP($client, $data = array()) {
    $return = array();
    if(count($data) == 0) {
      $return["status"] = "FAIL";
      $return["msg"] = "Data Bundle Kosong";
    } else {
      $conn = oci_connect(DB_USERNAME, DB_PASSWORD, DB_DSN);
      $t_mska = array();
      $t_mchb = array();
      $p_ok = false;
      //Pengecekan data sebelum benar-benar di-insert ke table zsd_wb_batch
      $x = 0;
      foreach ($data as $output) {
        $sql = "SELECT DISTINCT WSNUM FROM sapsr3.zsd_wb_batch@".$this->dblink_name." ".
               " WHERE MANDT = '".$client."' ".
               " AND WERKS = 'INDO' ".
               " AND CHARG = '" .$output["charg"]. "'";
        $st = oci_parse($conn, $sql);
        if (!$st) {
          $p_ok = false;
          $return["status"] = "FAIL";
          $return["msg"] = oci_error($conn)['message'];
          break;
        }
        oci_execute($st) or die(oci_error($conn)["message"]);
        $wsnum = null;
        if (($row = oci_fetch_assoc($st)) != false) {
          $wsnum = ltrim($row["WSNUM"], "0");  
        }
        oci_free_statement($st);
        if(!empty($wsnum)) {
          $p_ok = false;
          $return["status"] = "FAIL";
          if($wsnum == $output["trnum"]) {
            $return["msg"] = "Batch ".$output["charg"]." already picked WSNo. ".$wsnum;
          } else {
            $return["msg"] = "Batch ".$output["charg"]." WSNo. [".$wsnum."] sudah pernah di scan & upload!";
          }
          break;
        }
        
        //Cek Aktual Stok
        //Ada kemungkinan setelah scan stok kosong karena relokasi
        //SELECT data MTO
        $sql = "SELECT matnr, werks, lgort, charg, sobkz, vbeln, posnr, kalab ".
               " FROM sapsr3.MSKA@".$this->dblink_name." ".
               " WHERE MANDT = '".$client."' ".
               " AND MATNR = '" .str_pad($output["matnr"],18,"0",STR_PAD_LEFT). "' ".
               " AND CHARG = '" .$output["charg"]. "' ".
               " AND KALAB > 0";
        $st = oci_parse($conn, $sql);
        if (!$st) {
          $p_ok = false;
          $return["status"] = "FAIL";
          $return["msg"] = oci_error($conn)['message'];
          break;
        }
        oci_execute($st) or die(oci_error($conn)["message"]);
        while (($row = oci_fetch_assoc($st)) != false) {
          $t_mska[] = $row;  
        }        
        oci_free_statement($st);
        
        //SELECT data MTS        
        $sql = "SELECT matnr, werks, lgort, charg, clabs ".
               " FROM sapsr3.MCHB@".$this->dblink_name." ".
               " WHERE MANDT = '".$client."' ".
               " AND MATNR = '" .str_pad($output["matnr"],18,"0",STR_PAD_LEFT).  "' ".
               " AND CHARG = '" .$output["charg"]. "' ".
               " AND CLABS > 0 ";
        $st = oci_parse($conn, $sql);
        if (!$st) {
          $p_ok = false;
          $return["status"] = "FAIL";
          $return["msg"] = oci_error($conn)['message'];
          break;
        }
        oci_execute($st) or die(oci_error($conn)["message"]);
        while (($row = oci_fetch_assoc($st)) != false) {
          $t_mchb[] = $row;
        }
        oci_free_statement($st);
        
        if(count($t_mska) == 0 && count($t_mchb) == 0) {
          $p_ok = false;
          $return["status"] = "FAIL";
          $return["msg"] = "Stok Aktual Material[".$output["matnr"]." (".$output["charg"].")] Kosong";
          break;
        } else {
          foreach($t_mska as $w_mska) {
            $data[$x]["werks"] = $w_mska["WERKS"];
          }
          
          foreach($t_mchb as $w_mchb) {
            $data[$x]["werks"] = $w_mchb["WERKS"];
          }
        }
        
        $p_ok = true;
        $x++;
      }
      //$p_ok = true; //buat testing only jangan di uncomment bila tidak perlu debugging
      if($p_ok == true) {
        //Jika OK baru insert ke zsd_wb_batch
        $data_ke = 1;
        $z = 0;
        $data_sync_output = array();
        foreach($data as $output) {
          //trnum, vbeln, posnr, matnr, charg, qty
          $sql = "INSERT INTO sapsr3.zsd_wb_batch@".$this->dblink_name." (mandt, werks, charg, wsnum, vbeln, posnr) "
                  . "VALUES (:mandt, :werks, :charg, :wsnum, :vbeln, :posnr)";
          $st = oci_parse($conn, $sql);
          
          //$dev_client = "400";
          //oci_bind_by_name($st, ":mandt", $dev_client, -1, SQLT_CHR); //tes insert ke 400 dulu
          $wsnum = str_pad($output["trnum"],12,"0",STR_PAD_LEFT);          
          
          oci_bind_by_name($st, ":mandt", $client, -1, SQLT_CHR);
          oci_bind_by_name($st, ":werks", $output["werks"], -1, SQLT_CHR);
          oci_bind_by_name($st, ":charg", $output["charg"], -1, SQLT_CHR);
          oci_bind_by_name($st, ":wsnum", $wsnum, -1, SQLT_CHR);
          oci_bind_by_name($st, ":vbeln", $output["vbeln"], -1, SQLT_CHR);
          oci_bind_by_name($st, ":posnr", str_pad($output["posnr"],6,"0",STR_PAD_LEFT), -1, SQLT_CHR);
          if (!$st) {
            $return["status"] = "FAIL";
            $return["msg"] = "Error On Data #".$data_ke." | ".oci_error($conn)['message'];
            break;
          }
          //add to data_sync_output for FIFO
          $data_sync_output[$z]["mandt"] = $client;
          $data_sync_output[$z]["werks"] = $output["werks"];
          $data_sync_output[$z]["charg"] = $output["charg"];
          $data_sync_output[$z]["wsnum"] = $wsnum;
          $data_sync_output[$z]["vbeln"] = $output["vbeln"];
          $data_sync_output[$z]["posnr"] = str_pad($output["posnr"],6,"0",STR_PAD_LEFT);
          
          if($wsnum == "000000000000" || intval($wsnum) == 0) {
            $return["status"] = "FAIL";
            $return["msg"] = $data_ke." masalah pada No. Transaksi = ".$wsnum;
            break;
          } else {
            $insert = oci_execute($st) or die(oci_error($conn)["message"]);
            oci_commit($conn);
            //$insert = true;
            if($insert) {
              //oci_free_statement($st);
              $return["status"] = "OK";
              $return["msg"] = $data_ke." data Inserted";
            } else {
              //oci_free_statement($st);
              $return["status"] = "FAIL";
              $return["msg"] = $data_ke." data Failed";
              break;
            }
          }
          
          $data_ke++;
        }
        
        if(count($data_sync_output) > 0) {
          foreach($data_sync_output as $row) {
            $sql = "UPDATE sapsr3.zsd_rec_batch@".$this->dblink_name." SET profl = 'P' "
                    . "WHERE mandt = :mandt "
                    . "AND WERKS = :werks "
                    . "AND wsnum = :wsnum "
                    . "AND vbeln = :vbeln "
                    . "AND posnr = :posnr "
                    . "AND charg = :charg ";
            
            $st = oci_parse($conn, $sql);
            oci_bind_by_name($st, ":mandt", $client, -1, SQLT_CHR);
            oci_bind_by_name($st, ":werks", $row["werks"], -1, SQLT_CHR);
            oci_bind_by_name($st, ":wsnum", $row["wsnum"], -1, SQLT_CHR);
            oci_bind_by_name($st, ":vbeln", $row["vbeln"], -1, SQLT_CHR);
            oci_bind_by_name($st, ":posnr", $row["posnr"], -1, SQLT_CHR);
            oci_bind_by_name($st, ":charg", $row["charg"], -1, SQLT_CHR);
            
            oci_execute($st) or die(oci_error($conn)["message"]);
            oci_commit($conn);
          }
        }
      }
      oci_close($conn);
    }
    
    return $return;
  }
  
  function checkDOQuantity($client, $data = array()) {
    $return = array();
    
    if(count($data) > 0) {
      $conn = oci_connect(DB_USERNAME, DB_PASSWORD, DB_DSN);
      foreach ($data as $value) {
        $sql = "SELECT BUNDLE_NO as qty "
                . "FROM sapsr3.ZSD_WB_ITM@".$this->dblink_name." "
                . "WHERE MANDT = '$client'"
                . "AND BUKRS = 'INDO' "
                . "AND WSNUM = '".str_pad($value["trnum"],12,"0",STR_PAD_LEFT)."' "
                . "AND VBELN = '".$value["vbeln"]."' "
                . "AND POSNR = '".str_pad($value["posnr"],6,"0",STR_PAD_LEFT)."' "
                . "AND MATNR = '".str_pad($value["matnr"],18,"0",STR_PAD_LEFT)."' ";
        $hasil = $this->querySelect($conn, $sql);
        if(count($hasil) > 0) {
          $coil_no = 0;
          foreach ($hasil as $val) {
            $coil_no = $val["QTY"];
          }
          if($coil_no == $value["coil"]) {
            $return["status"] = "OK";
          } else {
            $return["status"] = "FAIL";
            $return["msg"] = "DO.#".$value["vbeln"]."(".$value["posnr"].") Changed, Qty: ".$coil_no;
            break;
          }
        } else {
          $return["status"] = "FAIL";
          $return["msg"] = "Loading Advice Not Found";
          break;
        }
        $return["status"] = "OK";
      }
    } else {
      $return["status"] = "FAIL";
      $return["msg"] = "Data Empty";
    }
    
    return $return;
  }
  
	function checkLAQuantity($client, $trnum, $qty) {
    $return = array();
    
		$conn = oci_connect(DB_USERNAME, DB_PASSWORD, DB_DSN);
		$sql = "SELECT BUNDLE_NO as qty "
						. "FROM sapsr3.ZSD_WB_ITM@".$this->dblink_name." "
						. "WHERE MANDT = '$client'"
						. "AND BUKRS = 'INDO' "
						. "AND WSNUM = '".str_pad($trnum,12,"0",STR_PAD_LEFT)."' ";
		$hasil = $this->querySelect($conn, $sql);
		if(count($hasil) > 0) {
			$coil_no = 0;
			foreach ($hasil as $val) {
				$coil_no += intval($val["QTY"]);
			}
			if($coil_no == $qty) {
				$return["status"] = "OK";
			} else {
				$return["status"] = "FAIL";
				$return["msg"] = "Jumlah Coil tidak sesuai Loading Advice : ".$trnum;
			}
		} else {
			$return["status"] = "FAIL";
			$return["msg"] = "Loading Advice Not Found";
		}
		$return["status"] = "OK";
    oci_close($conn);
    return $return;
  }
	
  function cekSingleDO($client, $wsnum, $vbeln, $matnr, $qty) {
    $return = array();
    if(empty($wsnum) || empty($vbeln) || empty($matnr) || empty($qty)) {
      $return["status"] = false;
      $return["msg"] = "One of parameter empty";
    } else {
      $conn = oci_connect(DB_USERNAME, DB_PASSWORD, DB_DSN) or die(oci_error());
      $sql = "SELECT BUNDLE_NO as qty "
              . "FROM sapsr3.ZSD_WB_ITM@".$this->dblink_name." "
              . "WHERE mandt = '$client' "
              . "AND BUKRS = 'INDO' "
              . "AND WSNUM = '".str_pad($wsnum,12,"0",STR_PAD_LEFT)."' "
              . "AND VBELN = '".$vbeln."' "
              . "AND MATNR = '".str_pad($matnr,18,"0",STR_PAD_LEFT)."' ";
      $hasil = $this->querySelect($conn, $sql);
      if(count($hasil) > 0) {
        $coil_no = 0;
        foreach($hasil as $val) {
          $coil_no = $val["QTY"];
        }
        
        if($coil_no == $qty) {
          $return["status"] = true;
        } else {
          $return["status"] = false;
          $return["msg"] = "DO #".$vbeln." Changed, Qty: ".$coil_no;
        }
      } else {
        $return["status"] = false;
        $return["msg"] = "Loading Advice Not Found";
      }
      oci_close($conn);
    }
    
    return $return;
  }
  
  public function querySelect($conn, $sql) {
    $data = array();
    $st = oci_parse($conn, $sql);
    if(!$st){
      return false;
    }
    oci_execute($st) or die(oci_error($conn)["message"]);
    while(($row = oci_fetch_assoc($st)) != false) {
      $data[] = $row;
    }
    oci_free_statement($st);
    return $data;
  }
}
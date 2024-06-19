<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
  <head>
    <meta charset="UTF-8">
    <title></title>
    <style>
    table {
      border: 1px solid black;
    }
    </style>
  </head>
  <body>
    <?php 
    $jml_data = count($data["batch"]);
    ?>
    <h1>View Output Batch For Stock Opname</h1>
    <h3><?php echo "Jumlah Data Output ".$jml_data; ?></h3>
    <button onclick="exceller()">Download</button>
    <table id="the_table">
      <thead>
        <tr>
          <th>User</th>
          <th>Batch</th>
          <th>Status</th>
					<th>Tanggal</th>
					<th>Jam</th>
        </tr>
      </thead>
      <tbody>
        <?php        
        if($jml_data > 0) {
          foreach($data["batch"] as $batch) {
            echo "<tr><td>".$batch["USERS"]."</td>"
										. "<td>".$batch["CHARG"]."</td>"
										. "<td  style='float:right'>".$batch["STATUS"]."</td>"
										. "<td>".$batch["TANGGAL"]."</td>"
										. "<td>".$batch["JAM"]."</td>"
										. "</tr>";
          }          
        } else {
          echo "<tr><td colspan='2'>No Data Yet...</td></tr>";
        }
        ?>
      </tbody>
    </table>
    <a href="index.php">Home</a>
    <script>
    function exceller() {
      var uri = 'data:application/vnd.ms-excel;base64,',
        template = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>{worksheet}</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body><table>{table}</table></body></html>',
        base64 = function(s) {
          return window.btoa(unescape(encodeURIComponent(s)))
        },
        format = function(s, c) {
          return s.replace(/{(\w+)}/g, function(m, p) {
            return c[p];
          })
        }
      var toExcel = document.getElementById("the_table").innerHTML;
      var ctx = {
        worksheet: name || '',
        table: toExcel
      };
      var link = document.createElement("a");
      link.download = "export.xls";
      link.href = uri + base64(format(template, ctx))
      link.click();
    }
    </script>
  </body>
</html>

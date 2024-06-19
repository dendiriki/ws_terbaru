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
    <h1>View Master Batch For Stock Opname</h1>
    <h3><?php echo "Jumlah Data Master ".$jml_data; ?></h3>
    <table>
      <thead>
        <tr>
          <th>Batch</th>
          <th>Updated Date</th>
        </tr>
      </thead>
      <tbody>
        <?php        
        if($jml_data > 0) {
          foreach($data["batch"] as $batch) {
            echo "<tr><td>".$batch["CHARG"]."</td><td  style='float:right'>".$batch["UPDDT"]."</td></tr>";
          }          
        } else {
          echo "<tr><td colspan='2'>No Data Yet...</td></tr>";
        }
        ?>
      </tbody>
    </table>
    <a href="index.php">Home</a>
  </body>
</html>

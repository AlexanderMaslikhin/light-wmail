<?php
  ini_set('error_reporting', E_ALL);
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  $basedir = "/home/sasha/upwork";
  $monthes = array (
    1 => 31,
    2 => 29,
    3 => 31,
    4 => 30,
    5 => 31,
    6 => 30,
    7 => 31,
    8 => 31,
    9 => 30,
    10 => 31,
    11 => 30,
    12 => 31
  );
?>
<!doctype html>
<html lang="ru">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">

    <title>Восстановление базы из бэкапов</title>
  </head>
  <body>
    <div class="container">
      <table class="table table-sm">
        <thead class="thead-dark">
          <tr>
            <th scope="col">#</th>
            <th scope="col">Дата</th>
            <th scole="col">Тип</th>
            <th scope="col">..</th>
          </tr>
        </thead>
        <tbody class="table-hover">
<?php
  $dirname = $basedir;
  if(!empty($_GET['action']) && $_GET['action'] == 'select' && !empty($_GET['date']) ) {
//проверка корректности 
      if(preg_match("/^(\d{2}?)-(\d{2}?)-(\d{4}?)$/", $_GET['date'], $matches)) {
        $cur_god = intval(date("Y"));
        $chislo = intval($matches[1]);
        $mes = intval($matches[2]);
        $god = intval($matches[3]);
//проверка правильности введенного числа, месяца и года
        if( ($mes>0 && $mes<=12) && ($chislo>0 && $chislo<=$monthes[$mes]) && ($god >= 2018 && $god<=$cur_god) ) {
//проверка, что дата не больше текущей
            $current = strtotime(date("d-m-Y"));
            $inputed = strtotime(date($_GET['date']));
            if($inputed <= $current) {
              if($chislo < 10) $chislo = "0".$chislo;
              if($mes < 10) $mes = "0".$mes;
              $dirname = $basedir."/".$chislo."_".$mes."_".$god;
              print_r($dirname);
            }
        }
      }  
  }
  $dirname = $basedir;
  $content = array_diff(scandir($dirname), array('..','.'));
  $files = array();
  $dirs = array();
  foreach($content as $a) {
    if(is_dir($dirname."/".$a)) $dirs[] = $a;
    else $files[] = $a;
  }
  $i=1;
  foreach($dirs as $d) {
    echo "<tr class='table-primary'><th scope='col'>".$i."</th><td>".$d."</td><td>папка</td><td><a href='#' class='btn btn-link btn-sm'>перейти</td></tr>";
    $i++;
  }
  foreach($files as $f) {
    echo "<tr><th scope='col'>".$i."</th><td>".$f."</td><td>файл</td><td><a href='#' class='btn btn-link btn-sm'>восстановить</td></tr>";
    $i++;
  }

?>          
        </tbody>
      </table>
    </div>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
  </body>
</html>
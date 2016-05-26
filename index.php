<?php 
  // format:
  // 3 4-bajtowe unsigned inty
  // 1 - poczatek dlugosc nazwy wraz z danymi
  // 2 - poczatek dzwieki w pliku dat
  // 3 - dlugosc dzwieku w pliku dat
  // nazwa i padding do dlugosci z pozycji 1
  error_reporting(0);
  
  // Dekompozycja pliku - GOTOWE!
  if(isset($_POST['decompress'])){
    $name = $_FILES['inx']['name'];
    $file = file_get_contents($_FILES['inx']['tmp_name']);
    $dat = file_get_contents($_FILES['dat']['tmp_name']);
    $err = false;
    $outFolder = 'out/o' . rand(0, 100000) . '/';
    mkdir($outFolder);
    $startPos = 0;
    while($startPos < strlen($file)){
      $soundData = unpack('L3', substr($file, $startPos, 12));
      if($soundData[1] % 4 != 0 || $soundData[1] < 16 || $soundData[1] > 50){
        $err = "Incorrect format of INX file";
        break;
      }
      $soundName = preg_replace('#[^a-z0-9\.]#i', '', substr($file, $startPos + 12, $soundData[1] - 12));
      
      $startPos += $soundData[1];
      if($soundData[2] > strlen($dat)){
        $err = "Incorrect format of DAT file, or the DAT file is not corresponding with uploaded INX file.";
        break;
      }
      file_put_contents($outFolder . $soundName, substr($dat, $soundData[2], $soundData[3]));
    }
    
    if(!$err){
      $zip = new ZipArchive();
      $zipFilename = current(explode('.', $name)) . '.zip';
      $zip->open($outFolder . $zipFilename, ZipArchive::CREATE);
      $dir = opendir($outFolder);
      while($file = readdir($dir)){
        if(is_dir($outFolder . $file))continue;
        $zip->addFile($outFolder . $file, $file . '.ogg');
      }
      $zip->close();
      header("Content-Type: application/zip");
		  header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
      echo file_get_contents($outFolder . $zipFilename);
    }
    else echo $err;
    
    rrmdir(substr($outFolder, 0, -1));
    
    die();
  }
  else if(isset($_POST['compress'])){
    // kompozycja pliku
    $name = $_FILES['zip']['name'];
    $err = false;
    $outFolder = 'out/o' . rand(0, 100000) . '/';
    mkdir($outFolder);
    $zip = new ZipArchive();
    $zip->open($_FILES['zip']['tmp_name']);
    $zip->extractTo($outFolder);
    $zip->close();
    
    $dirHandle = opendir($outFolder);
    $files = array();
    while($file = readdir($dirHandle)){
      if(is_dir($outFolder . $file))continue;
      if(preg_match('#([a-z0-9]+)\.ogg#', $file, $match)){
        $files[] = $match[1];
      }
    }
    closedir($dirHandle);
    sort($files);
    $inx = '';
    $dat = '';
    foreach($files as $file){
      $soundData = file_get_contents($outFolder . $file . '.ogg');
      $inxDataLen = 12 + strlen($file) + 1;
      // zaokraglam do 4
      $padding = 0;
      if($inxDataLen % 4)
        $padding = 4 - ($inxDataLen % 4);
      $inxDataLen += $padding;
      $inx .= pack('L3', $inxDataLen, strlen($dat), strlen($soundData)) . pack('a' . (strlen($file) + $padding + 1), $file);
      $dat .= $soundData;
    }

    if(!$err){
      $zip = new ZipArchive();
      $name = current(explode('.', $name));
      $zip->open($outFolder . $name . '.zip', ZipArchive::CREATE);
      $zip->addFromString("$name.inx", $inx);
      $zip->addFromString("$name.dat", $dat);
      $zip->close();
      header("Content-Type: application/zip");
		  header('Content-Disposition: attachment; filename="' . $name . '.zip"');
      echo file_get_contents($outFolder . $name . '.zip');
    }
    else echo $err;
    
    rrmdir(substr($outFolder, 0, -1));
    
    die();
  }
  

  // kompozycja pliku
 /* $dir = 'Czesio';
  $dirHandle = opendir($dir);
  $files = array();
  while($file = readdir($dirHandle)){
    if(is_dir($dir . '/' . $file))continue;
    if(preg_match('#([a-z0-9]+)\.ogg#', $file, $match)){
      $files[] = $match[1];
    }
  }
  closedir($dirHandle);
  sort($files);
  $inx = '';
  $dat = '';
  foreach($files as $file){
    $soundData = file_get_contents($dir . '/' . $file . '.ogg');
    $inxDataLen = 12 + strlen($file) + 1;
    // zaokraglam do 4
    $padding = 0;
    if($inxDataLen % 4)
      $padding = 4 - ($inxDataLen % 4);
    $inxDataLen += $padding;
    $inx .= pack('L3', $inxDataLen, strlen($dat), strlen($soundData)) . pack('a' . (strlen($file) + $padding + 1), $file);
    $dat .= $soundData;
  }
  
  file_put_contents('out.inx', $inx);
  file_put_contents('out.dat', $dat); */
?>


<html>
	<head>
		<title>CoPilot INX and DAT speech files converter</title>
		<style type="text/css">

		</style>
	</head>
	<body>
		<h1>CoPilot speech (voice) files converter (.ogg files &harr; .inx and .dat files)</h1>
		<p>
			It was really simple before CoPilot 8.2 - all voices were just sets of .ogg sound files and if you wanted
			to change some of them - you just replaced sound files with desired ones.
		</p>
		<p>
			Since CoPilot 8.2, all voices are packed into .dat and .inx files. Here you can convert your voices from one method to another
			- just use forms below.
		</p>
		<p>
			If you want to change only few sounds in your speech (most cases):
			<ol>
				<li>Upload you original .inx and .dat files with the first form</li>
				<li>Download generated archive</li>
				<li>Edit or replace desired sounds in the archive</li>
				<li>Upload the archive using the second form</li>
				<li>Download archive with new .inx and .dat files</li>
				<li>Replace them with your current .inx and .dat files on your phone</li>
				<li>Enjoy!</li>
			</ol>
		</p>
		<p>
			<big>Do not change sound filenames in the archive!<br>Always remember to backup you original speech data!</big>
		</p>
		<center>
  		<fieldset>
  			<legend>Convert from .inx and .dat into set of .ogg sounds</legend>
  			<form method="post" enctype="multipart/form-data">
  				.dat file: <input type="file" name="dat">
  				<br>
  				.inx file: <input type="file" name="inx">
  				<br>
  				<input type="submit" value="Convert to OGG" name="decompress" />
  			</form>
  		</fieldset>
  		<fieldset>
  			<legend>Convert from archive with set of .ogg files into .inx and .dat files</legend>
  			<form method="post" enctype="multipart/form-data">
  				.zip file with all sounds for speech: <input type="file" name="zip">
  				<br>
  				<input type="submit" value="Convert to INX and DAT" name="compress" />
  			</form>
  		</fieldset>
			<br><br>
			<a href="http://vojtek.pl" target="_blank" style="text-decoration:none; color: #000"><span style="color:#F60">v</span>ojtek.pl</a>
		</center>
	</body>	
</html>

<?php 
  function rrmdir($dir) {
   if (is_dir($dir)) {
     $objects = scandir($dir);
     foreach ($objects as $object) {
       if ($object != "." && $object != "..") {
         if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
       }
     }
     reset($objects);
     rmdir($dir);
   }
 }
?>
<?php
	$dirname=dirname(__FILE__);
	$filename=$_GET["file"];
	$thumbfile = $dirname."\\thumb\\".$filename;
	$newfile = $dirname."\\new_files\\".$filename;
	//echo "newfilename is ".$newfile;
	//echo "<br>";
	//echo "thumbfilename is ".$thumbfile;
$command="del ".$thumbfile;
exec($command,$status);

$command1="del ".$newfile;
exec($command1,$status);

echo "done";


?>


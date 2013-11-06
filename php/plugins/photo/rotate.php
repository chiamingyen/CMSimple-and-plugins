<?php
	//必須要將thumb與new_files中的圖面同時旋轉
	
	//$dirname=dirname(__FILE__);
	$dirname="./";
	$filename=$_GET["file"];
	$thumbfile = $dirname."\\photo_thumb\\".$filename;
	$newfile = $dirname."\\photo\\".$filename;
	//echo "newfilename is ".$newfile;
	//echo "<br>";
	//echo "thumbfilename is ".$thumbfile;
/*這一段原先是del圖片時所使用,必須要改為rotate
$command="del ".$thumbfile;
exec($command,$status);

$command1="del ".$newfile;
exec($command1,$status);
*/
rotateImage($thumbfile,3,100);
rotateImage($newfile,3,100);

echo "done";

function rotateImage($src, $count = 1, $quality = 95)
{
   if (!file_exists($src)) {
       return false;
   }

   list($w, $h) = getimagesize($src);

   if (($in = imageCreateFromJpeg($src)) === false) {
       echo "Failed create from source<br>";
       return false;
   }

   $angle = 360 - ((($count > 0 && $count < 4) ? $count : 0 ) * 90);

   if ($w == $h || $angle == 180) {
       $out = imageRotate($in, $angle, 0);
   } elseif ($angle == 90 || $angle == 270) {
       $size = ($w > $h ? $w : $h);
       // Create a square image the size of the largest side of our src image
       if (($tmp = imageCreateTrueColor($size, $size)) == false) {
           echo "Failed create square trueColor<br>";
           return false;
       }

       // Exchange sides
       if (($out = imageCreateTrueColor($h, $w)) == false) {
           echo "Failed create trueColor<br>";
           return false;
       }

       // Now copy our src image to tmp where we will rotate and then copy that to $out
       imageCopy($tmp, $in, 0, 0, 0, 0, $w, $h);
       $tmp2 = imageRotate($tmp, $angle, 0);

       // Now copy tmp2 to $out;
       imageCopy($out, $tmp2, 0, 0, ($angle == 270 ? abs($w - $h) : 0), 0, $h, $w);
       imageDestroy($tmp);
       imageDestroy($tmp2);
   } elseif ($angle == 360) {
       imageDestroy($in);
       return true;
   }

   imageJpeg($out, $src, $quality);
   imageDestroy($in);
   imageDestroy($out);
   return true;
}


?>

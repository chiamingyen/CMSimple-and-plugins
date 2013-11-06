<?php
// 2012.10.11 將 photo 延伸程式轉到 CMSimmple XH
// 將分頁改為可以移往最前與最後頁面的版本
// 假如加上 readbeanPHP 則可以考慮透過目錄管理或關鍵字查詢等功能
// 相片放在 downloads 下的 $subdir 子目錄下, 希望讓程式自動檢查對應的 thumb 目錄, 若無對應的小圖, 則自動在列出時進行縮圖處理

function photoMain($subdir) {

    global $dir_location;
	$action=$_GET['action'];
	if ($action=="rotate")
	{
		$the_file=$_GET['file'];
		do_rotate($the_file);
		return "done rotate";
	}
	elseif ($action=="rotate2")
	{
		$the_file=$_GET['file'];
		do_rotate2($the_file);
		return "done rotate2";
	}
	elseif ($action=="delete")
	{
		$the_file=$_GET['file'];
		do_delete($the_file);
		return "done delete";
	}
	else
	{
    // 希望用輸入變數來指定相片目錄
    //這是影像檔位於CMSimple根目錄下的目錄名稱,相對於$dir_location,thumb檔則放在$dir_location."_thumb"
	$dir_location = "./downloads/".$subdir;
	$string = "<table border='0' cellpadding='5' cellspacing='5'>";
    $string .= ls_recursive($dir_location);
	$string .= "</table>";
	//$string.="<br>".$_GET['file'];
	return $string;
	}
}

function ls_recursive($dir)
{
	global $string,$sn,$su;
	global $sub_dirname;
	//初始值設定;
	$num_in_a_row = 4;
    // 讓陣列起始, 由 1 開始
	$i = 1;
    // $k 目前沒有使用
	//$k = 0;
	$thumb_dir = trim($dir,"/")."_thumb/";
    $picture = array();
    
    if (is_dir($dir))
    {
        $files = scandir($dir);
          
        foreach ($files as $file)
        {
         $currentfile = $dir.$file."/";

            if (is_dir($currentfile))
            {	     
                if ($file != '.' && $file != '..')
                {
                    ls_recursive($currentfile);
                }
            }
            else
            {
                //進入這裡表示進入目錄讀檔案
                $is_graphics = preg_match("/.gif|.GIF|.jpg|.JPG|.png|.PNG/i",$file);

                if($is_graphics)
                {
                    //為了順利處理檔名為中文的圖檔,將big5碼轉為utf-8
                    $utf_filename = iconv("big-5","utf-8",$file);
                    $picture[$i] = $utf_filename;
                    $i++;
                }
            }
        }
    }

    // 開始進行分頁的設計
    // 這裡希望改為新的分頁設計

    if (isset($_GET['page']))
    {
        $page=$_GET['page'];
    }
    else
    {
        $page=1;
    }

    if(isset($_GET['item_per_page']))
    {
        $item_per_page=$_GET['item_per_page'];
    }
    else
    {
        $item_per_page=16;
    }

    $total_number = $i-1;

    /*
    此段程式可直接以 ceil() 取代
    if (($total_number % $item_per_page)==0)
        $totalpage=$total_number/$item_per_page;
    else
        $totalpage=(int)($total_number/$item_per_page)+1;
    */

    $totalpage = ceil($total_number/$item_per_page);

    // 這裡的第一張照片, 其索引值由 1 開始
    $starti = $item_per_page * ($page - 1) + 1;
    $endi = $starti + $item_per_page - 1;

    // $total_number > 0 表示至少一張照片, 否則傳回沒有資料的訊息
    If ($total_number > 0)
    {
        If ((int)($page * $item_per_page) < $total_number)
        {
            $notlast = true;
            $output .= "<br /><br />全部有&nbsp;".$total_number."&nbsp;張照片,&nbsp;";
            $output.=picturelist($picture,$starti,$endi);
        }
        else
        {
            // this is the last page
            $output .= "<br /><br />全部有&nbsp;".$total_number."&nbsp;張照片,&nbsp;";
            $output .= picturelist($picture,$starti,$total_number);
        }
        $output.="</table>";

        If ($page > 1)
        {
            // 列出前往第一頁的連結
            $page_output = "&nbsp;<a href=\"";
            $page_output .= $sn."?".$su."&page=1&item_per_page=".$item_per_page;
            $page_output .= "\"><<</a>&nbsp;";
            
            // 列出上一頁連結
            $page_num = $page-1;
            $page_output .= "&nbsp;<a href=";
            $page_output .= $sn."?".$su."&page=".$page_num."&item_per_page=".$item_per_page;
            $page_output .= ">上一頁</a>&nbsp;";
        }
        
        // 這裡希望能夠將總頁數以每 20 頁進行分段顯示,最多顯示出 $range * 2 的頁數
        $range = 10;
        for ($j=$page-$range;$j<$page+$range;$j++)
        {
            if(($j>=0) && ($j<$totalpage))
            {
              $page_now=$j+1;
              if($page_now==$page)
              {
                $page_output .="<font size=\"+1\" color=\"red\">".$page."</font>";
              }
              else
              {
                $page_output .= "&nbsp;<a href=\"";
                $page_output .= $sn."?".$su."&amp;page=".$page_now."&amp;item_per_page=".$item_per_page;
                $page_output .= "\">".$page_now."</a>&nbsp;";
              }
            }
        }

        If ($notlast == true)
        {
          $nextpage=$page+1;
          $page_output .= "&nbsp;<a href=\"";
          $page_output .= $sn."?".$su."&amp;page=".$nextpage."&amp;item_per_page=".$item_per_page;
          $page_output .= "\">下一頁</a>&nbsp;";

          // 列出前往最後一頁的連結
            $page_output .= "&nbsp;<a href=\"";
            $page_output .= $sn."?".$su."&amp;page=".$totalpage."&amp;item_per_page=".$item_per_page;
            $page_output .= "\">>></a>&nbsp;";
        }

        $string .= $page_output;
        $string .= $output;
        $string .= $page_output;
    }
    else
    {
        $string = "沒有任何資料!";
    }
    return $string;
}

function picturelist($picture,$from,$to)
{
    global $dir_location;
    $string.= "顯示第&nbsp;".$from."&nbsp;張到第&nbsp;".$to."&nbsp;張圖.<br />";
    $string.="<br />";
    $width=4;
    for($i=$from;$i<=$to;$i++)
    {
        $mod=$i%$width;

        if($mod==1)
        {
            $string .= "<tr>";
        }
        $string .= "<td>";
        $string .= $i."<br />";
        // 改用 JavaScript 以 window.open 檢視圖檔
        $string .= "<a href=\"javascript:;\" onClick=\"window.open('".$dir_location."/".$picture[$i]."','images','catalogmode,scrollbars')\"><img src=\"".trim($dir_location,"/")."_thumb/".$picture[$i]."\" border=\"0\"></a>";
        // 決定是否要讓管理者擁有刪除與線上處理圖檔的權限
        /*
        //這裡要列出刪除的連結
        $output.="<a href=\"delete.php?file=".$picture[$i]."\">d</a> ";
        //echo "<br>";
        //這裡要列出旋轉圖面的連結(-90度)
        $output.="<a href=\"rotate.php?file=".$picture[$i]."\">-90</a> ";
        //這裡要列出旋轉圖面的連結(+90度)
        $output.="<a href=\"rotate2.php?file=".$picture[$i]."\">+90</a>";
        */
        $string.="</td>";
        if ($mod==0)
        {
            $string.="</tr>";
        }
    }
return $string;
}

function rotateImage($src, $count = 1, $quality = 95)
{
   if (!file_exists($src)) {
       return false;
   }

   list($w, $h) = getimagesize($src);

   if (($in = imageCreateFromJpeg($src)) === false) {
       echo "Failed create from source<br />";
       return false;
   }

   $angle = 360 - ((($count > 0 && $count < 4) ? $count : 0 ) * 90);

   if ($w == $h || $angle == 180) {
       $out = imageRotate($in, $angle, 0);
   } elseif ($angle == 90 || $angle == 270) {
       $size = ($w > $h ? $w : $h);
       // Create a square image the size of the largest side of our src image
       if (($tmp = imageCreateTrueColor($size, $size)) == false) {
           echo "Failed create square trueColor<br />";
           return false;
       }

       // Exchange sides
       if (($out = imageCreateTrueColor($h, $w)) == false) {
           echo "Failed create trueColor<br />";
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

function do_rotate2($file)
{
	//必須要將thumb與new_files中的圖面同時旋轉
	
	//$dirname=dirname(__FILE__);
	$dirname="./";
	$filename=$_GET["file"];
	$thumbfile = $dirname."\\photo_thumb\\".$filename;
	$newfile = $dirname."\\photo\\".$filename;

rotateImage2($thumbfile,2,100);
rotateImage2($newfile,2,100);

rotateImage2($thumbfile,3,100);
rotateImage2($newfile,3,100);

}

function rotateImage2($src, $count = 1, $quality = 95)
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

function do_delete($file)
{
	$dirname="./";
	$filename=$_GET["file"];
	$thumbfile = $dirname."\\photo_thumb\\".$filename;
	$newfile = $dirname."\\photo\\".$filename;
$command="del ".$thumbfile;
exec($command,$status);

$command1="del ".$newfile;
exec($command1,$status);	
}

?>
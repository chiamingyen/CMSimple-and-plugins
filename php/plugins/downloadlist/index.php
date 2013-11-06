<?php

function downloadlistMain(){
    global $sn, $su;
    $output = "";
    // 這裡採用相對目錄, 因為 plugin 執行所在的目錄仍為 CMSimple 的根目錄.
    if ($handle = opendir("downloads")) {
    /* 列印出目錄對應的 handle
    $output .= "Directory handle: $handle<br />";
    $output .= "Entries:<br />";
    */
    $output .= <<< EOF
    <script>
    function keywordSearch(){
        var oform = document.forms["form1"];
        // 取elements集合中name屬性為username的值
        var getKeyword = oform.elements.keyword.value;
        if(getKeyword != ""){
            window.location = "?Download_List&keyword="+getKeyword;
        }
    }
    </script>
        <form name="form1">
        <input type="text" id="keyword" />
        <input type="button" id="send" value="查詢" onClick="keywordSearch()"/> (輸入關鍵字按下查詢後列出相關資料)
        </form><br />
EOF;
    // 預計要將所有檔案名稱放入一個 array 中
    $rowarray = array();

    /* This is the correct way to loop over the directory. */
    while (false !== ($entry = readdir($handle))) {
        if($entry != "." && $entry != ".." && $entry != "index.html" && $entry != "XHdebug.txt"){
	    // 只列出根目錄內容, 不包含子目錄
	    if(!is_dir("downloads/".$entry)){
            $file_size = downloadlist_formatSizeUnits(filesize("downloads/".$entry));
            array_push($rowarray, $entry.":".$file_size);
            //$output .= "<a href=?download=".urlencode($entry).">".$entry."</a><br /><br />";
            }
        }
        // ends if
    }
    // ends while
    closedir($handle);
    }
    // ends if

    // 以上的 rowarray 為全部的資料

    $keyword = $_GET["keyword"];
    session_start();
    if(isset($keyword)){
      $_SESSION["download_keyword"] = $keyword;
      $rowarray = downloadlist_search_keys($rowarray, $keyword);
    }else{
      $_SESSION["download_keyword"] = "";
    }

    $total_rows=count($rowarray);
    $item_per_page = $_GET["item_per_page"];
    $page = $_GET["page"];
		
    // 設定頁面控制內定參數
    if(!isset($item_per_page))
    {
        $item_per_page = 9;
    }
    if(!isset($page))
    {
        $page = 1;
    }
    $totalpage = ceil($total_rows/$item_per_page);

    $starti = $item_per_page * ($page - 1) + 1;
    $endi = $starti + $item_per_page - 1;
		
if ($total_rows > 0) {
    // 準備在表格之前列印頁數資料
    // 開始最前頭的頁數資料列印
    $output .= "<br />";
    if ((int)($page * $item_per_page) < $total_rows)
    {
        $notlast = true;
    }
    if ($page > 1)
    {
    // 列出前往第一頁的連結
        $output .= "<a href=\"";
        $output .= $sn."?".$su."&amp;page=1&amp;item_per_page=".$item_per_page."&amp;keyword=".$_SESSION["download_keyword"];
        $output .= "\"><<</a> ";

        $page_num=$page-1;
        $output .= "<a href=\"";
        $output .= $sn."?".$su."&amp;page=".$page_num."&amp;item_per_page=".$item_per_page."&amp;keyword=".$_SESSION["download_keyword"];
        $output .= "\">上一頁</a> ";
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
                    $output .="<font size=\"+1\" color=\"red\">".$page." </font>";
                }
                else
                {
                    $output .= "<a href=\"";
                    $output .=$sn."?".$su."&amp;page=".$page_now."&amp;item_per_page=".$item_per_page."&amp;keyword=".$_SESSION["download_keyword"];
                    $output .= "\">".$page_now."</a> ";
                }
            }
    }

    if ($notlast == true)
    {
      $nextpage=$page+1;
      $output .= " <a href=\"";
      $output .= $sn."?".$su."&amp;page=".$nextpage."&amp;item_per_page=".$item_per_page."&amp;keyword=".$_SESSION["download_keyword"];
      $output .= "\">下一頁</a>";

      // 列出前往最後一頁的連結
        $output .= " <a href=\"";
        $output .= $sn."?".$su."&amp;page=".$totalpage."&amp;item_per_page=".$item_per_page."&amp;keyword=".$_SESSION["download_keyword"];
        $output .= "\">>></a><br /><br />";
    }
    // 完成最前頭的頁數資料
    //列印最外圍的內容
    if ((int)($page * $item_per_page) < $total_rows)
    {
        $notlast = true;

        $output .= downloadlist_access_list($rowarray,$starti,$endi);
    }
    else
    {
        $output .= "<br /><br />";
        $output .= downloadlist_access_list($rowarray,$starti,$total_rows);
    }
	
    if ($page > 1)
    {
    // 列出前往第一頁的連結
        $output .= "<a href=\"";
        $output .= $sn."?".$su."&amp;page=1&amp;item_per_page=".$item_per_page."&amp;keyword=".$_SESSION["download_keyword"];
        $output .= "\"><<</a> ";
        
        $page_num=$page-1;
        $output .= "<a href=\"";
        $output .= $sn."?".$su."&amp;page=".$page_num."&amp;item_per_page=".$item_per_page."&amp;keyword=".$_SESSION["download_keyword"];
        $output .= "\">上一頁</a> ";
    }
// 這裡希望能夠將總頁數以每 20 頁進行分段顯示,最多顯示出 $range * 2 的頁數
$range = 10;

    for ($j=$page-$range;$j<$page+$range;$j++)
    {
        if(($j>=0) && ($j<$totalpage)){
          $page_now=$j+1;
          if($page_now==$page)
          {
               $output .="<font size=\"+1\" color=\"red\">".$page." </font>";
          }
          else
          {
              $output .= "<a href=\"";
              $output .=$sn."?".$su."&amp;page=".$page_now."&amp;item_per_page=".$item_per_page."&amp;keyword=".$_SESSION["download_keyword"];
              $output .= "\">".$page_now."</a> ";
          }
        }
    }

    if ($notlast == true)
    {
        $nextpage=$page+1;
        $output .= " <a href=\"";
        $output .= $sn."?".$su."&amp;page=".$nextpage."&amp;item_per_page=".$item_per_page."&amp;keyword=".$_SESSION["download_keyword"];
        $output .= "\">下一頁</a>";
      
      // 列出前往最後一頁的連結
        $output .= " <a href=\"";
        $output .= $sn."?".$su."&amp;page=".$totalpage."&amp;item_per_page=".$item_per_page."&amp;keyword=".$_SESSION["download_keyword"];
        $output .= "\">>></a><br /><br />";
    }
}
else{
    $output .= "沒有資料!";
}
    return $output;
}

function downloadlist_access_list($rowarray,$starti,$endi){
    // $rowarray 為全部要列出的所有資料,  從$starti 列到 $endi
    for($i=$starti-1;$i<=$endi-1;$i++){
        $filename_and_size = explode(":", $rowarray[$i]);
        $output .= "<a href=?download=".downloadlist_mb_rawurlencode($filename_and_size[0]).">".$filename_and_size[0]."</a> (".$filename_and_size[1].")<br /><br />";
    }
    return $output;
}


function downloadlist_mb_rawurlencode($url){
    $encoded='';
    $length=mb_strlen($url);
    for($i=0;$i<$length;$i++){
        $encoded.='%'.wordwrap(bin2hex(mb_substr($url,$i,1)),2,'%',true);
    }
    return $encoded;
}

function downloadlist_search_keys($input, $keyword){ 

    $output = array();
    
    foreach($input as $k=>$v){
        if(preg_match("/$keyword/i", $v)){
            array_push($output, $v);
        }
    }
    return $output;
}

function downloadlist_formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }

        return $bytes;
}
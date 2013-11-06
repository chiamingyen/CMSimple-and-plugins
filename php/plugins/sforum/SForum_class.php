<?php
//copyright  2013, chiamingyen@gmail.com
/*********************************
* SForum class v1.4
**********************************
*
* This class makes very Simle Forum.
* The forum is for everybody. There
* is no autorisation module.
* If you post message you fill
* only four inputs: title, text,
* your name or nick and e-mail.
* You do not need registration nor
* authorization just start using it.
* The class working with MySQL but in
* the near future it will spread on
* other DBs (with your help)
*
* I am looking forward your help.
*
**********************************
* How to use the class  (Quick Start)
**********************************
*
* At first create DB -> SForum.sql
*
* Include the class file
* include_once('SForum_class.php');
*
* A new object is created in the class
*
* 1. If... statment for active form
*    if (isset($_POST['submit'])) {
*        $forum->add_new_post($_POST['frm_ptitle'],$_POST['frm_text'],$_POST['frm_mail'],$_POST['frm_ip'],$_POST['frm_name'],$_POST['frm_wid']);
*    }
* 2. Display 1st message of threads or
*    all messages form thread
*    if (isset($_GET['wid'])) {
*        $forum->Show_SForum_Threads($_GET['wid']);
*        $forum->pansw = $_GET['wid'];
*    } else {
*        $forum->Show_SForum()
*        $forum->pansw = 0;
*    }
* 3. Show form w or w/o title of the
*    main thread you are answering
*    $forum->Show_frm($forum->ptitle);
*
*
**********************************
*
* @autor: Wojciech Napiera�a XII.2004
* @help: Ondra Marek ondra.marek@interval.cz;Pepe josegiambruno@adinet.com.uy
* @
* voytass@op.pl
*
*
* @license: GNU
**********************************

改為 ADODB 資料庫連線
改為 CMSimple Plugin
2010.02.10 copyright chiamingyen@gmail.com
2010.10.12 放到 kmol1/data2 作為教學輔助系統.
2011.05.04 改為 PDO 資料庫連線
*/

class SForum {
//$SFname 為本 plugin 所在的目錄名稱
  var $SFname = "sforum";
  var $ptitle;
  var $react;              // number of answers in a thread
  var $pansw;
  var $title;
	
	var $dbc;
	var $outcount;
	var $output;
	var $mysn;
	var $mysu;
	var $myadm;
	var $database_name;
	var $url_head = "./";
	var $listorder = "DESC";
        
function __construct(){
		// 將原先的資料庫連接,搬到 connect()成員函式,依不同的輸入資料庫變數,連接不同資料庫
		// 請注意,直接將資料庫連結,放在 constructor,無法接受外部資料作為參數(原因不明)
		// 關掉所有的錯誤回報
		error_reporting(0);
		}

// SForum: constructor, connecting to DB
// 2011.05.05 改為 PDO
function connect($database_name) {
		global $sn,$su,$adm;
		$this->mysn = $sn;
		$this->mysu = $su."&normal";
		$this->myadm = $adm;
		$this->database_name = $database_name;
		
        $this->ptitle = NULL;
        $this->pansw = 0;
        $this->title = "<title>$this->SFname</title>";
        //print($this->title);
		// 暫時蓋掉資料庫標題
		//$this->output = $this->title;
		// 改為 ADODB 資料庫連線
		// include('/adodb5/adodb.inc.php');
    // 改為 PDO 資料庫連線
    include_once("config.php");  // DB Config Data
/*		
// for MySQL connection
$this->dbc= ADONewConnection('mysql');
$this->dbc->debug = false;
// for UTF-8 charset
//$dbc->charPage=CP_UTF8;
$this->dbc->charPage='65001';
//$dbc->PConnect('your_db_host','your_db_account','your_db_password','your_db_name');
$this->dbc->PConnect($dbhost,$dbuname,$dbpass,$dbname);
*/
// for SQLite 3.0
//$this->dbc = NewADOConnection('pdo');
//$this->dbc->debug = false;
//$this->dbc->SetFetchMode(2);
// 資料庫由輸入的變數決定
$connstr="sqlite:".dirname(__FILE__)."/db/".$this->database_name;
$this->dbc = new PDO($connstr) or die("Couldn't connect to server.<br>\n");
// for UTF-8 charset
//$this->dbc->charPage=CP_UTF8;
//$dbstatus=$this->dbc->Connect($connstr) or die ("Couldn't connect to server.<br>\n");

// these are orig db connection
        //$pol = mysql_connect($dbhost,$dbuname,$dbpass) or die ("Couldn't connect to server.<br>\n");
        //$db = mysql_select_db($dbname,$pol) or die ("Couldn't connect to database.<br>\n");
    }

    // Show_frm: displays the form
function Show_frm($ptitle=NULL) {
      if(!empty($ptitle)) {
        $this->ptitle = "Re: ".$ptitle;
    }
		
		$this->output .= "
<script type=\"text/javascript\" language=\"javascript\">
<!--
		var uploadFields = [];
		var uploadForm = null;

		function initDocument()
		{
			uploadForm = document.getElementById(\"dynamicUpload\");
			uploadForm.enctype = \"multipart/form-data\";
			uploadForm.type = \"post\";
			uploadForm.action = \"".$this->mysn."?".$this->mysu."\";
			addUploadField();	
		}

		function UploadField_Altered(e)
		{
			var maxUploadFile = -1;

			if (uploadFields[uploadFields.length - 1].value.length > 0)
			{	

				if (maxUploadFile < 0 ||  maxUploadFile > uploadFields.length)
				{
					addUploadField();	
				}
			}
		}

		function addUploadField()
		{
            var br = document.createElement(\"br\");
			var newField = document.createElement(\"input\");
            var submit = document.createElement(\"input\");
			submit.name = \"submit\";
			submit.type = \"submit\";
			submit.value = \"send\";
			newField.type = \"file\";
			newField.className = \"uploadField\";
			newField.name = \"uploadField\" + uploadFields.length;
			newField.size = \"50\";
			newField.style.width = \"420px\";
			newField.ChangedHandler = UploadField_Altered;
			newField.onchange = newField.ChangedHandler;
            newField.onkeypress = disableEvent;
            newField.onkeydown = disableEvent;
            newField.onpaste = disableEvent;
            newField.oncut = disableEvent;
            newField.oncontextmenu = disableEvent;
			uploadForm.appendChild(newField);
			uploadForm.appendChild(submit);
			uploadForm.appendChild(br);
			uploadFields.push(newField);
		}

        function disableEvent()
        {
            return false;
        }
//-->
</script>
		";
		//這是原先的 submit
		//. "<td COLLSPAN=\"2\"><input type=\"submit\" name=\"submit\" value=\"Post\"></td>\n"
		//由於在 index.php 中,必須檢查 name 為 submit 的欄位(type submit),必須有值,才會取其他欄位
		//因此由 javascript 產生的表單,也要配合,否則取不到值
        $zawartosc = "\n\n<form name=\"dynamicUpload\" method=\"post\" id=\"dynamicUpload\" enctype=\"multipart/form-data\" action=\"".$this->mysn."?".$this->mysu."\">\n"
        . "<table><tr>\n"
        . "<td>Title:</td><td><input type=\"text\" name=\"frm_ptitle\" value=\"".$this->ptitle."\" size=\"65\"></input></td>\n"
        . "</tr><tr>\n"
        . "<td>Text:</td><td><textarea name=\"frm_text\" cols=\"50\" rows=\"10\"></textarea></td>\n"
        . "</tr><tr>\n"
        . "<td>Name or nick:</td><td><input type=\"text\" name=\"frm_name\" value=\"\" size=\"25\"></input></td>\n"
        . "</tr><tr>\n"
        . "<td>e-mail:</td><td><input type=\"text\" name=\"frm_mail\" value=\"\" size=\"25\"></input></td>\n"
        . "</tr><tr>\n"
        . "<td><input type=\"hidden\" name=\"frm_ip\" value=\"".$_SERVER['REMOTE_ADDR']."\"></input>\n"
        . "<input type=\"hidden\" name=\"frm_wid\" value=\"".$this->pansw."\"></input>\n"
        . "附加檔案:<br />\n"
        . "<script type=\"text/javascript\">\n"
        . "<!--\n"
        . "initDocument();\n"
        . "//-->\n"
        . "</script>\n"
		. "</td></tr></table>\n"
        . "</form>\n\n";
        //print($zawartosc);
		$this->output .=$zawartosc;
		return $this->output;
    }
	
function access_query($sql)
{
  //執行SQL運作
  //global $dbc;

  //SetFetchMode為2表示利用欄位名稱取值
  //若SetFetchMode為1則為利用欄位次序取值
  //$this->dbc->SetFetchMode(2);
  //$rs=$this->dbc->Execute($sql);
  $rs = $this->dbc->query($sql);
  //$rowarray = $result->fetchAll(PDO::FETCH_ASSOC);
  //$rs = $result->fetchAll(PDO::FETCH_ASSOC);
  return $rs;
}

function access_exec($sql)
{
  $cnt = $this->dbc->exec($sql);
  if($cnt != false){
    $output =  "Rows affected: ".$cnt;
    $output .= "<br />";
    $output .=  "Last inserted id: ".$this->dbc->lastInsertId();
    $output .= "<br />";
  }
  else
  {
    $output .= "Error";
  }

  return $output;
}
// Show_SFname : Show SForum name as text
function Show_SFname() {
      //print("<h1>$this->SFname</h1>\n");
      $output .="<b><font size=+1>$this->SFname</font></b><br /><br />\n";
      return $output;
    }

    // Add_new_post: Adds new record to DB
function Add_new_post($ptitle,$text,$mail,$ip,$name,$frm_wid) {
    if($ptitle=="" or $text==""){
            return;
	    }
		if(ini_get('magic_quotes_gpc')=="1")
        {
        //$this->ptitle = stripslashes(htmlspecialchars(trim($ptitle),ENT_QUOTES,'UTF-8'));
			$this->ptitle = stripslashes($this->keephtml(trim($ptitle)));
        //$this->text = stripslashes(htmlspecialchars(trim($text),ENT_QUOTES,'UTF-8'));
			$this->text = stripslashes($this->keephtml(trim($text)));
		}
		else
		{
        //$this->ptitle = htmlspecialchars(trim($ptitle),ENT_QUOTES,'UTF-8');
			$this->ptitle = $this->keephtml(trim($ptitle));
        //$this->text = htmlspecialchars(trim($text),ENT_QUOTES,'UTF-8');
			$this->text = $this->keephtml(trim($text));
		}
    
    $now = date("Y m d G:i:s");
		
		//這裡要取得多檔案上傳的資料
    $filesize = array();
    $filename = array();
    $m=0;
    while($_FILES['uploadField'.$m]['name'] != "")
    {
    $filename[$m] = $_FILES['uploadField'.$m]['name'];
    $filesize[$m] = $_FILES['uploadField'.$m]['size'];
    $output .= $filename[$m]."+".$filesize[$m];
    $output .= "<br />";
    $m++;
    }
    
    if($m>0)
    {
    $uploadfile = 1;
    }
    else
    {
    $uploadfile = 0;
    }

    if ($frm_wid == 0) {
        $zapytanie = "INSERT INTO SForum (wid,for_ptitle,for_text,for_mail,for_data,for_dataw,for_ip,for_name,uploadfile) VALUES('$frm_wid', '$this->ptitle', '$this->text', '$mail', '$now', '$now', '$ip', '$name', '$uploadfile')";
        //$sql = mysql_query($zapytanie) or die (mysql_error());
        //改為 PDO
        $rs = $this->access_exec($zapytanie);
		} else {
        // Yen add for_dataw field insert
        $zapytanie = "INSERT INTO SForum (wid,for_ptitle,for_text,for_mail,for_data,for_dataw,for_ip,for_name,uploadfile) VALUES('$frm_wid', '$this->ptitle', '$this->text', '$mail', '$now', '$now', '$ip', '$name', '$uploadfile')";
        //$sql = mysql_query($zapytanie) or die (mysql_error());
			  $rs = $this->access_exec($zapytanie);
    }
    //$id = mysql_insert_id();
		// 利用 ADODB insert_id() 替代 mysql_insert_id()
		//$id = $this->dbc->Insert_Id();
    // 改為 PDO
    $id = $this->dbc->lastInsertId();
      if ($frm_wid == 0) {
        $zapytanie = "UPDATE SForum SET wid='$id' WHERE id='$id'";
        #print $zapytanie;
        //$sql = mysql_query($zapytanie) or die (mysql_error());
			  $rs = $this->access_exec($zapytanie);
        } else {
          $zapytanie = "UPDATE SForum SET for_dataw='$now' WHERE id='$frm_wid'";
          #print $zapytanie;
          //$sql = mysql_query($zapytanie) or die (mysql_error());
			    $rs = $this->access_exec($zapytanie);
        }

//建立SQL指令
//新增資料的version欄位,取原始版次值0.1
$version=0.1;
//處理日期與資料擁有者
$date = date("Y m d G:i:s");
$tablename = "uploadfile";
$path = dirname(__FILE__)."/upload_files/";

/*
//請注意,第一階段,沒有取得$auto_index
//這裡還要取得上傳檔的附檔名
$ext = ereg_replace("^.+\\.([^.]+)$", "\\1", $filename[0]);
//這個$auto_index,$verion,與資料表名稱uploadfile,會組成上傳檔的唯一檔名
$saved_filename = $version."_".$tablename."_".$auto_index.".".$ext;
//處理資料上傳(start)
$filename_big5=iconv("UTF-8",$file_charset,$saved_filename);
//move_uploaded_file($_FILES['attachedfile']['tmp_name'], $folder."/files/".$filename);
//若沒有放置檔案的目錄,則新增目錄
if(!is_dir($path))
{
	mkdir($path,0777);
}
	//move_uploaded_file($_FILES['uploadField0']['tmp_name'], $folder.$path."/".$saved_filename);
//這裡要取得所有的上傳檔名,放到uploadfile資料表,並且完成檔案上傳搬遷
*/

$ini_version = "0.1";
$total_num_file = count(array_keys($filename));
$file_ext = array();
$upload_filename = array();
// Yen 2009 年 0728的修改
// 假如要改成 download取檔,考量一下該如何修改
    for ($k=0;$k<$total_num_file;$k++)
    {
      $file_ext[$k] = ereg_replace("^.+\\.([^.]+)$", "\\1", $filename[$k]);
      //這裡要加入檔案附檔名管制,只有允許的附檔名檔案可以存檔
      //if (eregi('(jpg|png|gif)',$file_ext))
      if(stristr('(jpg|png|gif|7z|doc|ppt|xls|gz|zip|pdf|wnk|swf|flv|stl)',$file_ext[$k]))
      {
        $sql="insert into uploadfile(tablename,follow,version,fileorder,filename,filesize) values ('$this->database_name','$id','$ini_version','$k','$filename[$k]','$filesize[$k]')";
        $rs = $this->access_query($sql);
        $output .= "已將第".$k."筆資料存入資料庫<br />";
        //這裡是真正上傳檔的檔名
        $upload_filename[$k] = $ini_version."_".$this->database_name."_".$id."_".$k.".".$file_ext[$k];
        //這裡要處理圖檔,令其產生特定大小的圖形檔案,以資利用
        if(@move_uploaded_file($_FILES['uploadField'.$k]['tmp_name'], $path.$upload_filename[$k]))
        {
          if(stristr('(jpg|png|gif)',$file_ext[$k]))
          {
              $this->thumbnail($upload_filename[$k],$ini_version."_".$this->database_name."_".$id."_display_".$k.".".$file_ext[$k],150);
          }
        $output .= "已將第".$k."筆資料,以".$upload_filename[$k]."存檔";
        $output .= "<br />";
        }
        else
        {
          $output .= "<br />".$path.$upload_filename[$k]." 檔案儲存發生問題<br />";
        }
          /*
          $upload_filename[$k] = $ini_version."_".$this->database_name."_".$id."_".$k.".".$file_ext[$k];
          move_uploaded_file($_FILES['uploadField'.$k]['tmp_name'], $path.$upload_filename[$k]);
          $output .= "已將第".$k."筆資料,以".$upload_filename[$k]."存檔";
          $output .= "<br />";
          */
      }
      else
      {
        //$output .= "路徑為".$path.$upload_filename[$k];
        //$output .= "<br />";
        $output .= "只允許(jpg|png|gif|7z|doc|ppt|xls|gz|zip|pdf|wnk|swf|flv|stl)等格式存檔<br />";
        $output .= "上傳檔案".$filename[$k]."存檔有問題";
        $output .= "<br />";
      }
    }
$output .= "已新增一筆資料!<br />";
return $output;
}
	
  // 請注意, 2011.05.05 Sandra 前往泰國,這裡要修改為 PDO select limit 的分頁處理
  // 1. 利用分頁數字計算 limit 查詢的範圍, 取出 $rowarray 之後, 再設法列出內容
  // 2. 整個邏輯全部要更動, 但是速度應該會快一些!!!
  // Show_SForum: Displays the main message of threads
	// 請注意 Show_SForum()的 $output 為 local variable
function Show_SForum() {
  // 嘗試將刪除處理搬到最前面
  //處理 deleteid
  // 權限修改,bug
  if($this->myadm)
  {
    $delete_id = $_GET["deleteid"];
    if(isset($delete_id))
    {
      $output .= $this->do_delete($delete_id);
    }
    // 處理 editid
    $edit_id = $_GET["editid"];
    if(isset($edit_id))
    {
      $output .= $this->edit_form($edit_id);
    }
  }
	$flat_list = $_GET["flat"];
    if($flat_list == 1)
    {
      // 改成以下就成為 flat listing
      $sql = "SELECT * FROM SForum ORDER BY id ".$this->listorder;
    }
    else
    {
      //$sql = 'SELECT * FROM SForum WHERE id=wid ORDER BY for_dataw DESC';
      $sql = "SELECT * FROM SForum WHERE id=wid ORDER BY id ".$this->listorder;
    }
		$rs = $this->access_query($sql);
    $rowarray = $rs->fetchAll(PDO::FETCH_ASSOC);
		//$total_rows=$rs->RecordCount();
    // 改為 PDO, 所傳回的 $rs 已經是查詢完後的所有 $rowarray 資料
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
    // 改為 PDO 時, 要考慮如何修改分頁的程式邏輯
//Yen

        //$rs->MoveFirst();
		
if ($total_rows > 0) {
// 準備在表格之前列印頁數資料
// 開始最前頭的頁數資料列印
$output .= "<br />";
If ((int)($page * $item_per_page) < $total_rows)
{
    $notlast = true;
}
If ($page > 1)
{
// 列出前往第一頁的連結
    $output .= "<a href=\"";
    $output .= $this->mysn."?".$this->mysu."&amp;page=1&amp;item_per_page=".$item_per_page;
    $output .= "\"><<</a> ";

    $page_num=$page-1;
    $output .= "<a href=\"";
    $output .= $this->mysn."?".$this->mysu."&amp;page=".$page_num."&amp;item_per_page=".$item_per_page;
    $output .= "\">上一頁</a> ";
}
// 這裡希望能夠將總頁數以每 20 頁進行分段顯示,最多顯示出 $range * 2 的頁數
$range = 10;

//for ($j=0;$j<$totalpage;$j++)
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
		    $output .=$this->mysn."?".$this->mysu."&amp;page=".$page_now."&amp;item_per_page=".$item_per_page;
        $output .= "\">".$page_now."</a> ";
      }
	}
}

If ($notlast == true)
{
  $nextpage=$page+1;
  $output .= " <a href=\"";
  $output .= $this->mysn."?".$this->mysu."&amp;page=".$nextpage."&amp;item_per_page=".$item_per_page;
  $output .= "\">下一頁</a>";

  // 列出前往最後一頁的連結
    $output .= " <a href=\"";
	$output .= $this->mysn."?".$this->mysu."&amp;page=".$totalpage."&amp;item_per_page=".$item_per_page;
    $output .= "\">>></a>";
}
// 完成最前頭的頁數資料
//列印最外圍的內容
If ((int)($page * $item_per_page) < $total_rows)
{
  $notlast = true;
  // 這裡是否改為透過 $rs 來呼叫 access_list()
  $output .= $this->access_list($rowarray,$starti,$endi);
}
else
{
//yen
	$output .= $this->access_list($rowarray,$starti,$total_rows);
}
	
If ($page > 1)
{
// 列出前往第一頁的連結
    $output .= "<a href=\"";
	$output .= $this->mysn."?".$this->mysu."&amp;page=1&amp;item_per_page=".$item_per_page;
    $output .= "\"><<</a> ";
	
    $page_num=$page-1;
    $output .= "<a href=\"";
	$output .= $this->mysn."?".$this->mysu."&amp;page=".$page_num."&amp;item_per_page=".$item_per_page;
    $output .= "\">上一頁</a> ";
}
// 這裡希望能夠將總頁數以每 20 頁進行分段顯示,最多顯示出 $range * 2 的頁數
$range = 10;

//for ($j=0;$j<$totalpage;$j++)
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
		    $output .=$this->mysn."?".$this->mysu."&amp;page=".$page_now."&amp;item_per_page=".$item_per_page;
        $output .= "\">".$page_now."</a> ";
      }
	}
}

If ($notlast == true)
{
  $nextpage=$page+1;
  $output .= " <a href=\"";
  $output .= $this->mysn."?".$this->mysu."&amp;page=".$nextpage."&amp;item_per_page=".$item_per_page;
  $output .= "\">下一頁</a>";
  
  // 列出前往最後一頁的連結
    $output .= " <a href=\"";
	$output .= $this->mysn."?".$this->mysu."&amp;page=".$totalpage."&amp;item_per_page=".$item_per_page;
    $output .= "\">>></a>";
}
        } else {
            //print("No threads<br>\n");
			$output .= "此篇文章尚無回應<br />\n";
        }
        $this->ptitle = ""; //the new thread's title is empty
		return $output;
    }

function access_list($rs,$from,$to)
{
// 請注意,這裡要加上附加檔案的檢查,若 fileupload 欄位為 1 則列印資料時,必須附帶檢查 uploadfile 資料表,將附加
// 的檔案列出
	$output .="<table border=\"1\" width=\"98%\" cellspacing=\"0\" cellpadding=\"3\">\n";
	// 表單 banner
	$output .= "<tr><td width=\"65%\" bgcolor=\"ccddff\" align=\"center\" colspan=\"2\">標題</td><td align=\"center\" width=\"15%\" bgcolor=\"CCDDFF\">回應數
  </td><td align=\"center\"  width=\"20%\" bgcolor=\"CCDDFF\">發表日期</td></tr>\n\n";

    for ($i=$from-1;$i<$to;$i++)
    {
    //Yen 改為 PDO 暫時蓋掉
    //$rs->Move($i);

    // 請注意 $this->outcount reset 的位置
    $this->outcount = 0;
    $sql1 = "SELECT count(wid)-1 as num FROM SForum WHERE wid='".$rs[$i]['id']."' GROUP BY wid";
    $rs1 = $this->access_query($sql1);
    $row1array = $rs1->fetchAll(PDO::FETCH_ASSOC);
    // 改為 PDO, $this->react 為該筆資料的回應數
    $this->react=$row1array['num'];
    //$this->react=$row1->fields['num'];
    if ($rs[$i]['for_name'] == "") {
        $rs[$i]['for_name'] = "Guest";
    }
    // Yen try to get the correct no of response, orig is $this->react
    $total_number = $this->SForum_Threads_count($rs[$i]['id']);

    if(ini_get('magic_quotes_gpc')=="1")
    {
      $this->ptitle = stripslashes($rs[$i]['for_ptitle']);
    }
    else
    {
      $this->ptitle = $rs[$i]['for_ptitle'];
    }
    $output .= "<tr><td width=\"5%\"><img src=\"plugins/sforum/images/normal_post.gif\" alt=\"normal_post\"></img></td><td width=\"60%\">&nbsp;
    <a href=\"".$this->mysn."?".$this->mysu."&amp;wid=".$rs[$i]['id']."\">".$this->ptitle."</a><br />&nbsp;Started by ".$rs[$i]['for_name'];
    // 這裡檢查 uploadfile 欄位,這裡已經不再透過 uploadfile 欄位判斷,而是直接進入查檔
    //if ($rs->fields['uploadfile'] == 1)

    if($this->uploadfile_list($rs[$i]['id']) != "")
    {
    $output .= " <img src=\"plugins/sforum/images/clip.gif\" alt=\"clip\"></img>";
    }
    $no_of_response = $total_number-1;
    if($no_of_response < 0)
    {
        $no_of_response = 0;
    }
    $output .= "</td><td align=\"center\" width=\"15%\">".$no_of_response."&nbsp;則回應</td><td align=\"center\"  width=\"20%\">".$rs[$i]['for_dataw']."<br />by "
    .$rs[$i]['for_name']."</td></tr>\n\n";
    //$rs->MoveNext();
}
    unset($this->react);
    $output .= "</table>\n";
        
return $output;
}

// 請注意, 這裡的 $rs 為 $rowarray, 而不是 PDOStatement Object
function thread_access_list($rs,$from,$to)
{
// 請注意,這裡要加上附加檔案的檢查,若 fileupload 欄位為 1 則列印資料時,必須附帶檢查 uploadfile 資料表,將附加
// 的檔案列出
	//$output .="<table border=\"1\" width=\"98%\" cellspacing=\"0\" cellpadding=\"3\">\n";
	// 表單 banner
	//$output .= "<tr><td width=\"65%\" bgcolor=\"ccddff\" align=\"center\" colspan=\"2\">標題</td><td align=\"center\" width=\"15%\" bgcolor=\"CCDDFF\">回應數</td><td align=\"center\"  width=\"20%\" bgcolor=\"CCDDFF\">發表日期</td></tr>\n\n";
  for($i=$from-1;$i<$to;$i++)
  {
    // 改為 PDO
    //$rs->Move($i);
    // while (!$rs->EOF) {
    $this->outcount++;
    if(ini_get('magic_quotes_gpc')=="1")
    {
      $this->ptitle = stripslashes($rs[$i]['for_ptitle']);
    }
    else
    {
      $this->ptitle = $rs[$i]['for_ptitle'];
    }

    if($this->outcount==1)
    {
      // 希望以第一筆的主訊息標題作為頁面標題
      // 設定頁面標題
      $GLOBALS['cf']['site']['title'] = $site_title." - ".$this->ptitle;
      $output .="<tr><td bgcolor=\"ccddff\" width=15%>作者</td><td bgcolor=\"ccddff\">主題: <b>".$this->ptitle."</b></td></tr>";
    }
    if(ini_get('magic_quotes_gpc')=="1")
    {
      // 由於 nl2br 會讓畫面 layout 看起來很鬆散,因此去除
      //$this->text = nl2br(stripslashes($rs->fields['for_text']));
      $this->text = stripslashes($rs[$i]['for_text']);
    }
    else
    {
      //$this->text = nl2br($rs->fields['for_text']);
      $this->text = $rs[$i]['for_text'];
    }
    // 嘗試透過 outcount 的計數,來進行分頁
    if ($rs[$i]['for_name'] == "") {
        $rs[$i]['for_name'] = "Guest";
    }
      if ($rs[$i]['for_mail'] !== "") {
          $pmail = "<a href=\"mailto:".$rs[$i]['for_mail']."\">";
          $kmail = "</a>";
      } else {
        $pmail = NULL;
        $kmail = NULL;
      }
      // 為了讓回應控制在 serial flow,暫時不讓各筆資料可以連結,以下為多緒功能,暫時蓋掉
      //$output .= "<tr><td bgcolor=\"CCDDFF\" rowspan=2 valign=top><i><u>".$pmail.$rs->fields['for_name'].$kmail."</u></i></td><td bgcolor=\"ccddff\">".$this->outcount."<b><a href=".$this->mysn."?".$this->mysu."&amp;wid=".$rs->fields['id'].">".$this->ptitle."</a></b><br>On: <font size=\"1\">".$rs->fields['for_data']."</font>\n\n";
      // 以下為蓋掉回應連結的版本
      // 有上傳檔, rowspan =3,沒有上傳檔 rowspan=2
      //if ($rs->fields['uploadfile'] == 1),請注意,這裡不再透過 sforum 欄位 uploadfile 查檔,而是
      // 直接進入 uploadfile 資料表查詢是否有上傳檔
      if($this->uploadfile_list($rs[$i]['id']) != "")
      {
        // 只有管理者可以看到 delete.gif(後續要植入功能)
        if ($this->myadm)
        {
        $output .= "<tr><td rowspan=3 valign=top width=15%><i><u>".$pmail.$rs[$i]['for_name'].$kmail."</u></i></td><td bgcolor=\"#CCEEFF\"><img src=\"plugins/sforum/images/xx.gif\" alt=\"xx\"></img> Message ".$rs[$i]['id'].
        " - <b>".$this->ptitle."</b><br />On: <font size=\"1\">".$rs[$i]['for_data']."</font><a href=".$this->mysn."?".$this->mysu."&amp;deleteid=".$rs[$i]['id']."><img src=\"plugins/sforum/images/delete.gif\" alt=\"delete\"></img></a>";
        $output .= "&nbsp;&nbsp;<a href=".$this->mysn."?".$this->mysu."&amp;editid=".$rs[$i]['id']."><img src=\"plugins/sforum/images/edit.gif\" alt=\"edit\"></a>\n\n";
        }
        else
        {
            $output .= "<tr><td rowspan=3 valign=top width=15%><i><u>".$pmail.$rs[$i]['for_name'].$kmail."</u></i></td><td bgcolor=\"#cceeff\"><img src=\"plugins/sforum/images/xx.gif\" alt=\"xx\"></img> Message ".$rs[$i]['id'].
            " - <b>".$this->ptitle."</b><br />On: <font size=\"1\">".$rs[$i]['for_data']."</font>\n\n";
        }
      }
      else
      {
        // 只有管理者可以看到 delete.gif(後續要植入功能)
        if($this->myadm)
        {
          $output .= "<tr><td rowspan=2 valign=top width=15%><i><u>".$pmail.$rs[$i]['for_name'].$kmail."</u></i></td><td bgcolor=\"#cceeff\"><img src=\"plugins/sforum/images/xx.gif\" alt=\"xx\"> Message ".$rs[$i]['id']." - <b>"
          .$this->ptitle."</b><br>On: <font size=\"1\">".$rs[$i]['for_data']."</font><a href=".$this->mysn."?".$this->mysu."&amp;deleteid=".$rs[$i]['id']."><img src=\"plugins/sforum/images/delete.gif\" alt=\"delete\"></a>";
          $output .= "&nbsp;&nbsp;<a href=".$this->mysn."?".$this->mysu."&amp;editid=".$rs[$i]['id']."><img src=\"plugins/sforum/images/edit.gif\" alt=\"edit\"></img></a>\n\n";
        }
        else
        {
          $output .= "<tr><td rowspan=2 valign=top width=15%><i><u>".$pmail.$rs[$i]['for_name'].$kmail."</u></i></td><td bgcolor=\"#cceeff\"><img src=\"plugins/sforum/images/xx.gif\" alt=\"xx\"></img> Message ".$rs[$i]['id'].
          " - <b>".$this->ptitle."</b><br />On: <font size=\"1\">".$rs[$i]['for_data']."</font>\n\n";
        }
     }
      // 在這裡利用 <pre> 與 </pre> 保留原始資料的格式設定,尤其是程式內容的縮排
      $output .= "</td></tr><tr><td><pre style=\"white-space: pre-wrap;\">".$this->text."<br /><br /></pre>";
      // 其實目前沒有開放單一的執行緒回覆功能,應該不會用到下面的
      // Recursive list, 因此將下列的四行蓋掉
      /*
      if($wid != $rs->fields['id'])
      {
          $this->Show_SForum_Threads($rs->fields['id']);
      }
       */
      // 至此,應該是可以根據 $this->outcount 來進行分頁列印
//print("</td></tr>\n\n");
      // 嘗試往上搬
      $output .= "</td></tr>\n\n";
      // 這裡要列出上傳資料
      // 針對 uploadfile 欄位的資料,無論是否為1都進入列檔流程
      //if ($rs->fields['uploadfile'] == 1)
      if($this->uploadfile_list($rs[$i]['id']) != "")
      {
      $output .= "<tr><td>&nbsp;<img src=\"plugins/sforum/images/clip.gif\" alt=\"clip\"></img><br />";
      // 在這裡呼叫上傳檔列印函式(輸入參數為 $rs->fields['id'])
      $output .= $this->uploadfile_list($rs[$i]['id']);
      $output .= "</td></tr>\n\n";
      }
      // 改為 PDO
      //$rs->MoveNext();
    }
    unset($this->react);
    //$output .= "</table>\n";
return $output;
}

// 改為 PDO 後, $rs 必須為 $rowarray
function search_access_list($rs,$from,$to)
{
// 請注意,這裡要加上附加檔案的檢查,若 fileupload 欄位為 1 則列印資料時,必須附帶檢查 uploadfile 資料表,將附加
// 的檔案列出
	$output .="<table border=\"1\" width=\"98%\" cellspacing=\"0\" cellpadding=\"3\">\n";
	// 表單 banner
	$output .= "<tr><td width=\"65%\" bgcolor=\"CCDDFF\" align=\"center\" colspan=\"2\">標題</td><td align=\"center\" width=\"15%\" bgcolor=\"ccddff\">回應數</td><td align=\"center\"  width=\"20%\" bgcolor=\"ccddff\">發表日期</td></tr>\n\n";
	
    for ($i=$from-1;$i<$to;$i++)
    {
    // 改為 PDO
    //$rs->Move($i);

	// 請注意 $this->outcount reset 的位置
				$this->outcount = 0;
				$sql1 = "SELECT count(wid)-1 as num FROM SForum WHERE wid='".$rs[$i]['id']."' GROUP BY wid";
				$rs1 = $this->access_query($sql1);
        $row1array = $rs1->fetchAll(PDO::FETCH_ASSOC);
        // 改為 PDO, $this->react 為該筆資料的回應數
        $this->react=$row1array['num'];
				//$this->react=$row1->fields['num'];
				if ($rs[$i]['for_name'] == "") {
						$rs[$i]['for_name'] = "Guest";
                }
				// Yen try to get the correct no of response, orig is $this->react
				$total_number = $this->SForum_Threads_count($rs[$i]['id']);
				if(ini_get('magic_quotes_gpc')=="1")
				{
				    $this->ptitle = stripslashes($rs[$i]['for_ptitle']);
				}
				else
				{
				    $this->ptitle = $rs[$i]['for_ptitle'];
				}
                                // 20101012 除了列出連結至 wid 的資料樹最頭端,也希望能列出個別資料
                                // 因此需要建立一個 &id=絕對序號的資料列表函式
				$output .= "<tr><td width=\"5%\"><img src=\"plugins/sforum/images/normal_post.gif\" alt=\"normal_post\"></td><td width=\"60%\">&nbsp;<a href=\"".$this->mysn."?".$this->mysu."&amp;wid=".$rs[$i]['wid']."\">".$this->ptitle.
        "</a> (<a href=\"".$this->mysn."?".$this->mysu."&amp;id=".$rs[$i]['id']."\">顯示單筆資料</a>)<br />&nbsp;Started by ".$rs[$i]['for_name'];
                // 這裡檢查 uploadfile 欄位,這裡已經不再透過 uploadfile 欄位判斷,而是直接進入查檔
				//if ($rs->fields['uploadfile'] == 1)
				if($this->uploadfile_list($rs[$i]['id']) != "")
				{
				$output .= " <img src=\"plugins/sforum/images/clip.gif\" alt=\"clip\"></img>";
				}
				$no_of_response = $total_number-1;
				if($no_of_response < 0)
				{
				    $no_of_response = 0;
				}
				$output .= "</td><td align=\"center\" width=\"15%\">".$no_of_response."&nbsp;則回應</td><td align=\"center\"  width=\"20%\">".$rs[$i]['for_dataw'].
        "<br />by ".$rs[$i]['for_name']."</td></tr>\n\n";
        // 改成 PDO 後, 利用 [$i] 進行增量, 無需 movenext
				//$rs->MoveNext();
    }
    unset($this->react);
		$output .= "</table>\n";
  return $output;
}
	
// start thread count
function SForum_Threads_count($wid) {
    //$this->pansw = $wid;
    $zapytanie = "SELECT * FROM SForum WHERE wid='$wid' ORDER BY for_data ASC";
    //print $zapytanie;
    //$sql = mysql_query($zapytanie) or die (mysql_error());
		$rs = $this->access_query($zapytanie);
    // 改為 PDO
    $rowarray = $rs->fetchAll(PDO::FETCH_ASSOC);
    $total_rows=count($rowarray);
		//$total_rows=$rs->RecordCount();
		if ($total_rows > 0) {
      //print("<table border=\"1\" width=\"90%\">\n");
      //while (!$rowarray->EOF) {
      foreach($rowarray as $row){
          $this->outcount++;
          $this->ptitle = stripslashes($row['for_ptitle']);
          $this->text = nl2br(stripslashes($row['for_text']));
          if ($row['for_name'] == "") {
              $row['for_name'] = "Guest";
                      }
      if ($row['for_mail'] !== "") {
          $pmail = "<a href=\"mailto:".$row['for_mail']."\">";
                  $kmail = "</a>";
              } else {
                  $pmail = NULL;
                  $kmail = NULL;
              }
      if($wid != $row['id'])
          $this->SForum_Threads_count($row['id']);
          //print("</td></tr>\n\n");
      // 已經透過 PDO foreach 取得 outcount 的增量值, 無需 movenext
      //$rs->MoveNext();
      }
          //print("</table>\n");
      } else {
          //print("No threads<br>\n");
      }
    $zapytanie = "SELECT * FROM SForum WHERE id='$wid' LIMIT 0,1";
		$rs = $this->access_query($zapytanie);
    $rowarray = $rs->fetchAll(PDO::FETCH_ASSOC);
		$this->ptitle = stripslashes($rowarray['for_ptitle']);
		return $this->outcount;
    }
// ends thread count

// 20101012 配合在多資料緒中,新增連結至單一資料進行檢視與處理的功能
function Show_SForum_SinglePost($id)
{
global $site_title;
$zapytanie = "SELECT * FROM SForum WHERE id='$id'";
$rs = $this->access_query($zapytanie);
$rowarray = $rs->fetchAll(PDO::FETCH_ASSOC);
$this->outcount++;
    if(ini_get('magic_quotes_gpc')=="1")
   {
        $this->ptitle = stripslashes($rowarray[0]['for_ptitle']);
    }
    else
    {
        $this->ptitle = $rowarray[0]['for_ptitle'];
    }
    // 設定頁面標題
    $GLOBALS['cf']['site']['title'] = $site_title." - ".$this->ptitle;
    $output .="<table border=\"1\" width=\"98%\" cellspacing=\"0\" cellpadding=\"3\">\n";
    $output .="<tr><td bgcolor=\"ccddff\" width=15%>作者</td><td bgcolor=\"ccddff\">主題: <b>".$this->ptitle."</b></td></tr>";
    if(ini_get('magic_quotes_gpc')=="1")
    {
        // 由於 nl2br 會讓畫面 layout 看起來很鬆散,因此去除
        //$this->text = nl2br(stripslashes($rs->fields['for_text']));
        $this->text = stripslashes($rowarray[0]['for_text']);
    }
    else
    {
        //$this->text = nl2br($rs->fields['for_text']);
        $this->text = $rowarray[0]['for_text'];
    }
        if ($rowarray[0]['for_name'] == "") {
        $rowarray[0]['for_name'] = "Guest";
        }
if ($rowarray[0]['for_mail'] !== "") {
    $pmail = "<a href=\"mailto:".$rowarray[0]['for_mail']."\">";
    $kmail = "</a>";
   } else {
    $pmail = NULL;
    $kmail = NULL;
   }
  // 為了讓回應控制在 serial flow,暫時不讓各筆資料可以連結,以下為多緒功能,暫時蓋掉
  //$output .= "<tr><td bgcolor=\"CCDDFF\" rowspan=2 valign=top><i><u>".$pmail.$rs->fields['for_name'].$kmail."</u></i></td><td bgcolor=\"ccddff\">".$this->outcount."<b><a href=".$this->mysn."?".$this->mysu."&amp;wid=".$rs->fields['id'].">".$this->ptitle."</a></b><br>On: <font size=\"1\">".$rs->fields['for_data']."</font>\n\n";
  // 以下為蓋掉回應連結的版本
  // 有上傳檔, rowspan =3,沒有上傳檔 rowspan=2
  //if ($rs->fields['uploadfile'] == 1),請注意,這裡不再透過 sforum 欄位 uploadfile 查檔,而是
  // 直接進入 uploadfile 資料表查詢是否有上傳檔
  if($this->uploadfile_list($rowarray[0]['id']) != "")
  {
    // 只有管理者可以看到 delete.gif(後續要植入功能)
    if ($this->myadm)
    {
      $output .= "<tr><td rowspan=3 valign=top width=15%><i><u>".$pmail.$rowarray[0]['for_name'].$kmail."</u></i></td><td bgcolor=\"#CCEEFF\">
      <img src=\"plugins/sforum/images/xx.gif\" alt=\"xx\"></img> Message ".$rowarray[0]['id'].
      " - <b>".$this->ptitle."</b><br />On: <font size=\"1\">".$rowarray[0]['for_data']."</font><a href=".$this->mysn."?".$this->mysu."&amp;deleteid=".$rowarray[0]['id'].">
      <img src=\"plugins/sforum/images/delete.gif\" alt=\"delete\"></img></a>";
      $output .= "&nbsp;&nbsp;<a href=".$this->mysn."?".$this->mysu."&amp;editid=".$rowarray[0]['id'].">
      <img src=\"plugins/sforum/images/edit.gif\" alt=\"edit\"></a>\n\n";
    }
    else
    {
      $output .= "<tr><td rowspan=3 valign=top width=15%><i><u>".$pmail.$rowarray[0]['for_name'].$kmail."</u></i></td><td bgcolor=\"#cceeff\">
      <img src=\"plugins/sforum/images/xx.gif\" alt=\"xx\"></img> Message ".$rowarray[0]['id'].
      " - <b>".$this->ptitle."</b><br />On: <font size=\"1\">".$rowarray[0]['for_data']."</font>\n\n";
    }
  }
  else
  {
    // 只有管理者可以看到 delete.gif(後續要植入功能)
    if($this->myadm)
    {
      $output .= "<tr><td rowspan=2 valign=top width=15%><i><u>".$pmail.$rowarray[0]['for_name'].$kmail."</u></i></td><td bgcolor=\"#cceeff\">
      <img src=\"plugins/sforum/images/xx.gif\" alt=\"xx\"> Message "
      .$rowarray[0]['id']." - <b>".$this->ptitle."</b><br>On: <font size=\"1\">".$rowarray[0]['for_data']."
      </font><a href=".$this->mysn."?".$this->mysu."&amp;deleteid=".$rowarray[0]['id'].
      "><img src=\"plugins/sforum/images/delete.gif\" alt=\"delete\"></a>";
      $output .= "&nbsp;&nbsp;<a href=".$this->mysn."?".$this->mysu."&amp;editid=".$rowarray[0]['id'].">
      <img src=\"plugins/sforum/images/edit.gif\" alt=\"edit\"></img></a>\n\n";
    }
    else
    {
      $output .= "<tr><td rowspan=2 valign=top width=15%><i><u>".$pmail.$rowarray[0]['for_name'].$kmail."</u></i></td><td bgcolor=\"#cceeff\">
      <img src=\"plugins/sforum/images/xx.gif\" alt=\"xx\"></img> Message "
      .$rowarray[0]['id']." - <b>".$this->ptitle."</b><br />On: <font size=\"1\">".$rowarray[0]['for_data']."</font>\n\n";
    }

  }
  // 在這裡利用 <pre> 與 </pre> 保留原始資料的格式設定,尤其是程式內容的縮排
  $output .= "</td></tr><tr><td><pre style=\"white-space: pre-wrap;\">".$this->text."<br /><br /></pre>";
  // 其實目前沒有開放單一的執行緒回覆功能,應該不會用到下面的
  // Recursive list, 因此將下列的四行蓋掉
  /*
  if($wid != $rs->fields['id'])
  {
      $this->Show_SForum_Threads($rs->fields['id']);
  }
   */
  // 至此,應該是可以根據 $this->outcount 來進行分頁列印
//print("</td></tr>\n\n");
    // 嘗試往上搬
    $output .= "</td></tr>\n\n";
    // 這裡要列出上傳資料
    // 針對 uploadfile 欄位的資料,無論是否為1都進入列檔流程
    //if ($rs->fields['uploadfile'] == 1)
    if($this->uploadfile_list($rowarray[0]['id']) != "")
    {
    $output .= "<tr><td>&nbsp;<img src=\"plugins/sforum/images/clip.gif\" alt=\"clip\"></img><br />";
    // 在這裡呼叫上傳檔列印函式(輸入參數為 $rs->fields['id'])
    $output .= $this->uploadfile_list($rowarray[0]['id']);
    $output .= "</td></tr>\n\n";
    }
    $output .= "</table><br />\n";
    //$output.="here we list the single post";
  return $output;
}

// Show_SForum_Threads: Displays all messages of a thread
// 請注意 $this->output 與 $output 的使用差異
function Show_SForum_Threads($wid) {
	// 設定頁面標題
	global $site_title;
    //$this->pansw = $wid;
    //$zapytanie = "SELECT * FROM SForum WHERE wid='$wid' ORDER BY for_data ASC";
    $zapytanie = "SELECT * FROM SForum WHERE wid='$wid' ORDER BY id ASC";
    //print $zapytanie;
    //$sql = mysql_query($zapytanie) or die (mysql_error());
		$rs = $this->access_query($zapytanie);
    $rowarray = $rs->fetchAll(PDO::FETCH_ASSOC);
    // 呼叫 thread_access_list 的 $rs 改為 $rowarray
    $total_rows=count($rowarray);
		//$total_rows=$rs->RecordCount();
		if ($total_rows > 0) {
    // 開始準備進行分頁
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
    // 改為 PDO 後蓋掉下一行
    //$rs->MoveFirst();
    //
    //$from =1;
    //$to=$total_rows;
    //$output .= $this->thread_access_list($rs, $from, $to);
    //
    //try start
    //if ($total_rows > 0) {
    // 表格之前的頁數列印開始
    If ($page > 1)
    {
    // 列出前往第一頁的連結
      $output .= "<a href=\"";
      $output .= $this->mysn."?".$this->mysu."&amp;wid=".$wid."&amp;page=1&amp;item_per_page=".$item_per_page;
      $output .= "\"><<</a> ";

      $page_num=$page-1;
      $output .= "<a href=\"";
      $output .= $this->mysn."?".$this->mysu."&amp;wid=".$wid."&amp;page=".$page_num."&amp;item_per_page=".$item_per_page;
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
          $output .=$this->mysn."?".$this->mysu."&amp;wid=".$wid."&amp;page=".$page_now."&amp;item_per_page=".$item_per_page;
          $output .= "\">".$page_now."</a> ";
        }
      }
    }
    
    If ((int)($page * $item_per_page) < $total_rows)
    {
      $notlast = true;
    }

    If ($notlast == true)
    {
      $nextpage=$page+1;
      $output .= " <a href=\"";
      $output .= $this->mysn."?".$this->mysu."&amp;wid=".$wid."&amp;page=".$nextpage."&amp;item_per_page=".$item_per_page;
      $output .= "\">下一頁</a>";

      // 列出前往最後一頁的連結
      $output .= " <a href=\"";
      $output .= $this->mysn."?".$this->mysu."&amp;wid=".$wid."&amp;page=".$totalpage."&amp;item_per_page=".$item_per_page;
      $output .= "\">>></a>";
    }
    $output .= "<br /><br />";
// 改成 PDO 到此都還正確
    // 表格之前的頁數列印結束
   $output .= "<table border=\"1\" width=\"98%\" cellspacing=\"0\" cellpadding=\"3\">\n";
    //列印最外圍的內容
    if ((int)($page * $item_per_page) < $total_rows)
    {
      $notlast = true;
      $output .= $this->thread_access_list($rowarray,$starti,$endi);
    }
    else
    {
      $output .= $this->thread_access_list($rowarray,$starti,$total_rows);
    }
// 表格列印部分將在這裡結束
    $output .= "</table><br />\n";

    If ($page > 1)
    {
    // 列出前往第一頁的連結
      $output .= "<a href=\"";
      $output .= $this->mysn."?".$this->mysu."&amp;wid=".$wid."&amp;page=1&amp;item_per_page=".$item_per_page;
      $output .= "\"><<</a> ";

      $page_num=$page-1;
      $output .= "<a href=\"";
      $output .= $this->mysn."?".$this->mysu."&amp;wid=".$wid."&amp;page=".$page_num."&amp;item_per_page=".$item_per_page;
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
          $output .=$this->mysn."?".$this->mysu."&amp;wid=".$wid."&amp;page=".$page_now."&amp;item_per_page=".$item_per_page;
          $output .= "\">".$page_now."</a> ";
        }
      }
    }

    If ($notlast == true)
    {
      $nextpage=$page+1;
      $output .= " <a href=\"";
      $output .= $this->mysn."?".$this->mysu."&amp;wid=".$wid."&amp;page=".$nextpage."&amp;item_per_page=".$item_per_page;
      $output .= "\">下一頁</a>";

      // 列出前往最後一頁的連結
      $output .= " <a href=\"";
      $output .= $this->mysn."?".$this->mysu."&amp;wid=".$wid."&amp;page=".$totalpage."&amp;item_per_page=".$item_per_page;
      $output .= "\">>></a>";
    }
//} yen try to get rid of one if
//try end
//與 廣告進行區隔
$output .= "<br /><br />";
// 這裡放上 Adsense 廣告
/*
$output .= "
<script type=\"text/javascript\"><!--
google_ad_client=\"pub-2140091590744860\";
google_ad_host=\"pub-1556223355139109\";
google_ad_width=468;
google_ad_height=60;
google_ad_format=\"468x60_as\";
google_ad_type=\"text\";
google_color_border=\"FFFFFF\";
google_color_bg=\"FFFFFF\";
google_color_link=\"000000\";
google_color_url=\"000000\";
google_color_text=\"000000\";
//--></script>
<script type=\"text/javascript\"
  src=\"http://pagead2.googlesyndication.com/pagead/show_ads.js\">
</script>
";
 */
  } else {
  //print("No threads<br>\n");
  // 配合多緒回應關閉,以下列印也關閉
  //$output .= "此篇文章尚無回應<br>\n";
    }
    $zapytanie = "SELECT * FROM SForum WHERE id='$wid' LIMIT 0,1";
		$result = $this->access_query($zapytanie);
    $rs1 = $result->fetchAll(PDO::FETCH_ASSOC);
    //$sql = mysql_query($zapytanie) or die (mysql_error());
    //$row = mysql_fetch_array($sql);
		$this->ptitle = stripslashes($rs1[0]['for_ptitle']);  //Re title for form
		// 請注意,這裡的討論緒直接 return,以便利用 $adm 控制輸入權
	  return $output;
  }

	function uploadfile_list($id)
	{
    $sql="select * from uploadfile where follow ='".$id."';";
    $rs = $this->access_query($sql);
    $rowarray = $rs->fetchAll(PDO::FETCH_ASSOC);
    // 改為 PDO
    $total_rows=count($rowarray);
		//$total_rows=$rs->RecordCount();
                /*
		for($i=0;$i<$total_rows;$i++)
		{
		$ext = ereg_replace("^.+\\.([^.]+)$", "\\1", $rs->fields["filename"]);
		$filename = $rs->fields["version"]."_".$rs->fields["tablename"]."_".$id."_".$rs->fields["fileorder"].".".$ext;
		// 這裡希望針對檔案的類別加以區分,圖檔與 swf 可以直接連結,但是其他檔案則以 download 下載
		if(stristr('(jpg|png|gif|swf|flv)',$ext))
		{
		    $output .= ($i+1).".&nbsp;<a href=\"plugins/sforum/upload_files/".$filename."\">".$rs->fields["filename"]."</a> (".round($rs->fields["filesize"]/1024,0)." KB)<br />";
		}
		else
		{
		    $output .= ($i+1).".&nbsp;<a href=\"".$this->mysn."?".$this->mysu."&amp;act=download&amp;wid=".$id."&amp;fileorder=".$rs->fields["fileorder"]."\">".$rs->fields["filename"]."</a> (".round($rs->fields["filesize"]/1024,0)." KB)<br />";		
		}
		$rs->MoveNext();
		}
		return $output;
	}
        */
		if($total_rows == 0)
		{
		    return "";
		}
		else
		{
			$output .= "<table>";
			for($i=0;$i<$total_rows;$i++)
			{
			$ext = ereg_replace("^.+\\.([^.]+)$", "\\1", $rowarray[$i]["filename"]);
			$filename = $rowarray[$i]["version"]."_".$rowarray[$i]["tablename"]."_".$id."_".$rowarray[$i]["fileorder"].".".$ext;
			$display_filename = $rowarray[$i]["version"]."_".$rowarray[$i]["tablename"]."_".$id."_display_".$rowarray[$i]["fileorder"].".".$ext;
			// 這裡希望植入每三個項目才跳下一行,以 table 完成?
			// 這裡希望針對檔案的類別加以區分,圖檔與 swf 可以直接連結,但是其他檔案則以 download 下載
			if($i%4 == 0)
			{
				$output .= "<tr>";
			}
				if(stristr('(jpg|png|gif)',$ext))
				{
					//$output .= ($i+1).".&nbsp;<a href=\"plugins/sforum/upload_files/".$filename."\">".$rs->fields["filename"]."</a> (".round($rs->fields["filesize"]/1024,0)." KB)<br />";
					$output .= "<td><a href=\"javascript:;\" onClick=\"window.open('".$this->url_head."plugins/".$this->SFname."/upload_files/".$filename."','images','catalogmode,scrollbars')\"><img src=\""
          .$this->url_head."plugins/".$this->SFname."/upload_files/".$display_filename."\"></img></a><br />(".$rowarray[$i]["filename"].")<br />(".round($rowarray[$i]["filesize"]/1024,0)." KB)</td>";
				}
				elseif(stristr('(swf)',$ext))
				{
					//direct link
					//$output .= "<td><a href=\"".$this->url_head."plugins/".$this->SFname."/upload_files/".$filename."\">".$rs->fields["filename"]."</a><br />(".round($rs->fields["filesize"]/1024,0)." KB)</td>";
					//open new window
					$output .= "<td><a href=\"javascript:;\" onClick=\"window.open('".$this->url_head."/plugins/".$this->SFname."/upload_files/".$filename."','images','catalogmode,scrollbars')\">"
          .$rowarray[$i]["filename"]."</a><br />(".round($rowarray[$i]["filesize"]/1024,0)." KB)</td>";
				}
        elseif(stristr('(flv|stl)',$ext))
        {
            // 這裡不處理 flv 檔案,留給後面,一個檔案一大格
        }
				else
				{
					$output .= "<td><a href=\"".$this->mysn."?".$this->mysu."&amp;act=download&amp;wid=".$id."&amp;fileorder=".$rowarray[$i]["fileorder"]."\">".$rowarray[$i]["filename"].
          "</a><br />(".round($rowarray[$i]["filesize"]/1024,0)." KB)</td>";
				}
			if($i%4 == 3)
			{
				$output .= "</tr>";
			}
			if(stristr('(flv)',$ext))
      {
        //$output .= "<td><object type=\"application/x-shockwave-flash\" data=\"player_flv_multi.swf\" width=\"600\" height=\"450\">";
            /*
        $output .= "<td><object type=\"application/x-shockwave-flash\" data=\"player_flv_multi.swf\" width=\"400\" height=\"300\">";
        $output .= "<param name=\"allowFullScreen\" value=\"true\" />";
        $output .= "<param name=\"FlashVars\" value=\"flv=".$this->url_head."plugins/".$this->SFname."/upload_files/".$filename."&autoload=1&autoplay=1&showstop=1&showvolume=1&showtime=1&showfullscreen=1\"/>";
        $output .= "</object></td>";
        */
        $output .= "<tr><td colspan=\"4\">";
        $output .= $this->jaris($this->url_head."plugins/".$this->SFname."/upload_files/"
        .$filename,"600","450");
        $output .="<br />(".$rowarray[$i]["filename"].":".round($rowarray[$i]["filesize"]/1024,0)." KB)</td></tr>";
      }
     // 處理 stl 檔案的檢視
    if(stristr('(stl)',$ext))
      {
        //$output .= "<td><object type=\"application/x-shockwave-flash\" data=\"player_flv_multi.swf\" width=\"600\" height=\"450\">";
        /*
        $output .= "<td><object type=\"application/x-shockwave-flash\" data=\"player_flv_multi.swf\" width=\"400\" height=\"300\">";
        $output .= "<param name=\"allowFullScreen\" value=\"true\" />";
        $output .= "<param name=\"FlashVars\" value=\"flv=".$this->url_head."plugins/".$this->SFname."/upload_files/".$filename."&autoload=1&autoplay=1&showstop=1&showvolume=1&showtime=1&showfullscreen=1\"/>";
        $output .= "</object></td>";
        */
        $output .= "<tr><td colspan=\"4\">";
        $output .= $this->stlview($this->url_head."plugins/".$this->SFname."/upload_files/".$filename,"600","450");
        $output .="<br />(".$rowarray[$i]["filename"].":".round($rowarray[$i]["filesize"]/1024,0)." KB)</td></tr>";
      }
      //改成 PDO 由 [$i] 負責增量  
			//$rs->MoveNext();
			}
			$output .= "</table>";
			return $output;
		}
	}

	function do_delete($id)
	{
    // 先根據取得的 $id,查出子串所有對應的資料筆數與對應 id, 然後先刪除 uploadfile 欄位中對應的檔案,然後刪除 
		// uploadfile 對應資料庫內容,然後再刪除 SForum 中的所有資料
		// 1. 取得的 $id,查出子串所有對應的資料筆數與對應 id
		$sql = "SELECT id from SForum where wid ='".$id."';";
		$result = $this->access_query($sql);
    $rs = $result->fetchAll(PDO::FETCH_ASSOC);
    // 改為 PDO
    $total_rows = count($rs);
		//$total_rows=$rs->RecordCount();
		$tablename = "uploadfile";
		if($total_rows > 0)
		{
    //while(!$rs->EOF)
    for($j=0;$j<$total_rows;$j++)
    {
			$sql2 = "SELECT * from uploadfile where follow ='".$rs[$j]["id"]."';";
			$result2 = $this->access_query($sql2);
      $rs2 = $result2->fetchAll(PDO::FETCH_ASSOC);
      $total_rows2 = count($rs2);
			//$total_rows2 = $rs2->RecordCount();
      //while(!$rs2->EOF)
      for($i=0;$i<$total_rows2;$i++)
      {
				$file_version = $rs2[$i]["version"];
				$file_order = $rs2[$i]["fileorder"];
				$file_follow = $rs2[$i]["follow"];
				$file_ext = ereg_replace("^.+\\.([^.]+)$", "\\1", $rs2[$i]["filename"]);
				// 實際對應的檔案名稱為 $file_version."_".$this->database_name."_".$file_follow."_".$fileorder.".".$file_ext;
				$filename = $file_version."_".$this->database_name."_".$file_follow."_".$file_order.".".$file_ext;
				//$output .= "刪除的檔案為".$filename;
				//$output.="<br />";
				// 也要同時刪除對應的上傳檔案
				$this->delete_file($filename);
        // 改成 PDO 後, 以 [$i] 增量, 無需 movenext
				//$rs2->MoveNext();
      }
      // 改成 PDO 後, 以 [$j] 增量, 無需 movenext
			//$rs->MoveNext();
			}
		// 這裡要刪除的為對應到 $id 的所有子串相對的所有資料,因此使用 wid=$id 刪除
		$sql3 = "DELETE from uploadfile where follow ='".$id."';";
		$this->access_exec($sql3);
		$sql4 = "DELETE from SForum where wid ='".$id."';";
		$this->access_exec($sql4);
		$output .= "第 ".$id." 筆 and all reated db & files were deleted.<br />";
		}
		else
		// 上面考量有回應的資料處理,以下處理單一資料存在的附檔處理
		{
		// 表示為單一筆無回應資料的處理
		$sql21 = "SELECT * from uploadfile where follow ='".$id."';";
		$result21 = $this->access_query($sql21);
    $rs21 = $result21->fetchAll(PDO::FETCH_ASSOC);
    $total_rows2 = count($rs21);
		//$total_rows2 = $rs2->RecordCount();
    for($j=0;$j<$total_rows2;$j++)
    //while(!$rs21->EOF)
    {
			$file_version = $rs21[$j]["version"];
			$file_order = $rs21[$j]["fileorder"];
			$file_follow = $rs21[$j]["follow"];
			$file_ext = ereg_replace("^.+\\.([^.]+)$", "\\1", $rs21[$j]["filename"]);
			// 實際對應的檔案名稱為 $file_version."_".$this->database_name."_".$file_follow."_".$fileorder.".".$file_ext;
			$filename = $file_version."_".$this->database_name."_".$file_follow."_".$file_order.".".$file_ext;
			//$output .= "刪除的檔案為".$filename;
			//$output.="<br>";
			// 也要同時刪除對應的上傳檔案
			$this->delete_file($filename);
      // 改用 PDO 無需 movenext
			//$rs21->MoveNext();
			}
	    // 這裡要刪除的為對應到 $id 的所有子串相對的所有資料,因此使用 wid=$id 刪除
		$sql3 = "DELETE from uploadfile where follow ='".$id."';";
		$this->access_exec($sql3);
		$sql4 = "DELETE from SForum where id ='".$id."';";
		$this->access_exec($sql4);
		$output .= "第 ".$id." 筆 and all reated db & files were deleted.<br />";
		}
		return $output;
	}
	
	// delete the file physically
	function delete_file($filename)
	{
    $path = dirname(__FILE__)."/upload_files/";
	  unlink($path.$filename);
	}
	
function edit_form($id)
{
  $tablename = "uploadfile";
  // 透過 $id 取得各欄位資料
  $sql = "SELECT * FROM SForum WHERE id='".$id."';";
  $result = $this->access_query($sql);
  $rs = $result->fetchAll(PDO::FETCH_ASSOC);
  //這裡要從uploadfile資料表中,取得與某一筆資料所對應的上傳檔案
  $sql2 = "SELECT * from uploadfile where follow='".$id."';";
  $result2 = $this->access_query($sql2);
  $rs2 = $result2->fetchAll(PDO::FETCH_ASSOC);
  $total_rows2 = count($rs2);
  //$rs2->MoveFirst();

$upload_filename = array();
$file_ext = array();
$file_version = array();
$file_follow = array();
if($total_rows2 == 0)
//if($rs2->EOF)
{
	$largestorder = 0;
	$attachfile = "原資料沒有附掛任何檔案";
	$attachfile .= "<br /><br />";
}
else
{
	$attachfile .= "已附掛檔案:";
	$attachfile .= "<br />";
	$i=0;
	$attachfile .= "<table>";
  for($i=0;$i<$total_rows2;$i++)
  //while (!$rs2->EOF)
  {
    $file_ext[$i] = ereg_replace("^.+\\.([^.]+)$", "\\1", $rs2[$i]["filename"]);
    $file_version[$i] = $rs2[$i]["version"];
    $file_follow[$i] = $rs2[$i]["follow"];
    $upload_filename[$i] = $file_version[$i]."_".$this->database_name."_".$file_follow[$i]."_".$rs2[$i]["fileorder"].".".$file_ext[$i];
    $display_upload_filename[$i] = $file_version[$i]."_".$this->database_name."_".$file_follow[$i]."_display_".$rs2[$i]["fileorder"].".".$file_ext[$i];
    //$attachfile .= "<tr><td><a href=\"plugins/sforum/upload_files/".$upload_filename[$i]."\">".$rs2->fields["filename"]."</a></td>";
    if(stristr('(jpg|png|gif)',$file_ext[$i]))
    {
        $attachfile .= "<tr><td><a href=\"javascript:;\" onClick=\"window.open('".$this->url_head."plugins/".$this->SFname."/upload_files/".$upload_filename[$i].
        "','images','catalogmode,scrollbars')\"><img src=\"plugins/".$this->SFname."/upload_files/".$display_upload_filename[$i]."\"></img></a><br />".$rs2[$i]["filename"].
        ")<br />(".round($rs2[$i]["filesize"]/1024,0)." KB)</td>";
    }
    else
    {
        $attachfile .= "<tr><td><a href=\"javascript:;\" onClick=\"window.open('".$this->url_head."plugins/".$this->SFname."/upload_files/".$upload_filename[$i].
        "','images','catalogmode,scrollbars')\">".$rs2[$i]["filename"]."</a><br />(".round($rs2[$i]["filesize"]/1024,0)." KB)</td>";
    }
      $attachfile .= "<td><input type=\"checkbox\" name=\"delete".$rs2[$i]["fileorder"]."\" value=\"on\"></input>刪除檔案</td>";
			//這裡要列出取代上傳檔的表單,只要透過資料表uploadfile,index與fileorder就可以更新每一個上傳檔
			$attachfile .= "<td>取代檔案: <input name=\"uploadField".$rs2[$i]["fileorder"]."\" type=\"file\"></input></td>";
			$attachfile .= "</tr>";
      // 請注意, 改用 for 迴圈後, 無需再增量
			//$i++;
			$largestorder = $rs2[$i]["fileorder"];
      // 改用 PDO 無需 movenext
			//$rs2->MoveNext();
    }
	$attachfile .= "</table>";
//將最大的order數加1,就是接下來的上傳檔所要用的order數.
$largestorder++;
}
$output .="largestorder is now ".$largestorder."<br />";
$javascript1 = "
<script type=\"text/javascript\">
<!--
		var uploadFields = [];
		var uploadForm = null;

		function initDocument()
		{
			uploadForm = document.getElementById(\"dynamicUpload\");
			uploadForm.enctype = \"multipart/form-data\";
			uploadForm.type = \"post\";
			uploadForm.action = \"".$this->mysn."?".$this->mysu."&amp;act=doedit\";
			addUploadField();	
		}
		
		function UploadField_Altered(e)
		{
			var maxUploadFile = -1;

			if (uploadFields[uploadFields.length - 1].value.length > 0)
			{	
				if (maxUploadFile < 0 ||  maxUploadFile > uploadFields.length)
				{
					addUploadField();	
				}
			}
		}

		function addUploadField()
		{
      var br = document.createElement(\"br\");
			var newField = document.createElement(\"input\");
            var submit = document.createElement(\"input\");
			submit.name = \"doedit\";
			submit.type = \"submit\";
			submit.value = \"send\";
			newField.type = \"file\";
			newField.className = \"uploadField\";
		";
		//請注意,這裡所對應的欄位名稱,必須接續在舊有的上傳檔序號之後
		$javascript2 ="newField.name = \"uploadField\" + (uploadFields.length+".$largestorder.");";
		$javascript3 ="
			newField.size = \"50\";
			newField.style.width = \"420px\";

			newField.ChangedHandler = UploadField_Altered;

			newField.onchange = newField.ChangedHandler;
            newField.onkeypress = disableEvent;
            newField.onkeydown = disableEvent;
            newField.onpaste = disableEvent;
            newField.oncut = disableEvent;
            newField.oncontextmenu = disableEvent;

			uploadForm.appendChild(newField);
			uploadForm.appendChild(submit);
			uploadForm.appendChild(br);
			uploadFields.push(newField);
		}

        function disableEvent()
        {
            return false;
        }
	initDocument();
//-->
</script>
		";
        $editform = "\n\n<form name=\"dynamicUpload\" method=\"post\" id=\"dynamicUpload\" enctype=\"multipart/form-data\" action=\"".$this->mysn."?".$this->mysu."&amp;act=doedit\">\n"
        . "<table><tr>\n"
        . "<td>Title:</td><td><input type=\"text\" name=\"frm_ptitle\" value=\"".$rs[0]["for_ptitle"]."\" size=\"65\"></td>\n"
        . "</tr><tr>\n"
        . "<td>Text:</td><td><textarea name=\"frm_text\" cols=\"50\" rows=\"10\">".$rs[0]["for_text"]."</textarea></td>\n"
        . "</tr><tr>\n"
        // do not allow to edit frm_name field
        //. "<td>Name or nick:</td><td><input type=\"text\" name=\"frm_name\" value=\"".$rs->fields["for_name"]."\" size=\"25\"></td>\n"
        . "<td>Name or nick:</td><td>".$rs[0]["for_name"]."</td>\n"
        . "</tr><tr>\n"
        . "<td>e-mail:</td><td><input type=\"text\" name=\"frm_mail\" value=\"".$rs[0]["for_mail"]."\" size=\"25\"></td>\n"
        . "</tr><tr>\n"
        . "<td><input type=\"hidden\" name=\"frm_ip\" value=\"".$_SERVER['REMOTE_ADDR']."\">\n"
        . "<input type=\"hidden\" name=\"frm_id\" value=\"".$rs[0]["id"]."\">\n"
        . "<input type=\"hidden\" name=\"frm_wid\" value=\"".$rs[0]["wid"]."\">\n"
        . "<input type=\"hidden\" name=\"fileorder\" value=\"".$largestorder."\">\n"
        . "附加檔案:<br />\n"
        . "</td></tr></table>\n";

		$output .= $editform;
		$output .= $attachfile;
		$output .= $javascript1.$javascript2.$javascript3;
		$output .= "</form>\n\n";
		return $output;
	}
	
	function Edit_post($ptitle,$text,$mail,$ip,$name,$frm_wid,$id,$fileorder)
	{
	// 實際對應的檔案名稱為 $file_version."_".$this->database_name."_".$file_follow."_".$fileorder.".".$file_ext;
	//$filename = $file_version."_".$this->database_name."_".$file_follow."_".$file_order.".".$file_ext;
    if(ini_get('magic_quotes_gpc')=="1")
		{
			//$ptitle = stripslashes(htmlspecialchars(trim($ptitle),ENT_QUOTES,'UTF-8'));
			$ptitle = stripslashes($this->keephtml(trim($ptitle)));
			//$text = stripslashes(htmlspecialchars(trim($text),ENT_QUOTES,'UTF-8'));
			$text = stripslashes($this->keephtml(trim($text)));
		}
		else
		{
			//$ptitle = htmlspecialchars(trim($ptitle),ENT_QUOTES,'UTF-8');
			$ptitle = $this->keephtml(trim($ptitle));
			//$text = htmlspecialchars(trim($text),ENT_QUOTES,'UTF-8');
			$text = $this->keephtml(trim($text));
		}
    // do not allow to edit for_name
    //$sql = "UPDATE SForum set for_ptitle='".$ptitle."',for_text='".$text."',for_mail='".$mail."',for_ip='".$ip."',for_name='".$name."',wid='".$frm_wid."' where id=".$id.";";
    $sql = "UPDATE SForum set for_ptitle='".$ptitle."',for_text='".$text."',for_mail='".$mail."',for_ip='".$ip."',wid='".$frm_wid."' where id=".$id.";";
    $this->access_exec($sql);
		$output .= "資料庫內容已經更新,fileorder is ".$fileorder."<br />";
		// 接下來處理實體檔案的更新與改版
		$path = dirname(__FILE__)."/upload_files/";
		$tablename = "uploadfile";
    //這裡要取得多檔案上傳的資料
    $filesize = array();
    $filename = array();
		$fileversion = array();
    $filedelete = array();
	//先處理將舊檔案更新的部分,這一部分,必須要將所對應order的舊檔案刪除,然後換成新的檔案,最後再更新uploadfile資料表
		for ($i=0;$i<$fileorder;$i++)
	    {
		//因為若取代的上傳欄位有值,則舊檔形同刪除,因此不需要額外處理,只有當取代欄位為空白且選了刪除,才單獨處理
			if($_POST['delete'.$i] == "on" && $_FILES['uploadField'.$i]['name'] == "")
			{
			//先取得$i所對應的檔案資料
			$sql2="select * from uploadfile where follow ='".$id."' and fileorder ='".$i."';";
			$result2= $this->access_query($sql2);
      $rs2 = $result2->fetchAll(PDO::FETCH_ASSOC);
      // 應該只有一筆資料
      $total_rows2 = count($rs2);
			//刪除$i所對應的資料庫欄位與所對應的上傳檔
			$file_ext[$i] = ereg_replace("^.+\\.([^.]+)$", "\\1", $rs2[0]["filename"]);
			$file_version[$i] = $rs2[0]["version"];
      if(stristr('(jpg|png|gif)',$file_ext[$i]))
      {
      $filetodelete = $file_version[$i]."_".$this->database_name."_".$id."_".$rs2[0]["fileorder"].".".$file_ext[$i];
      unlink($path.$filetodelete);
      $display_filetodelete = $file_version[$i]."_".$this->database_name."_".$id."_display_".$rs2[0]["fileorder"].".".$file_ext[$i];
      unlink($path.$display_filetodelete);
			}
			else
			{
        $filetodelete = $file_version[$i]."_".$this->database_name."_".$id."_".$rs2[0]["fileorder"].".".$file_ext[$i];
        unlink($path.$filetodelete);
			}
			$sql3="delete from uploadfile where follow ='".$id."' and fileorder ='".$i."';";
			$rs3=$this->access_exec($sql3);
			$output .= "已經刪除第".$i."個上傳檔".$filetodelete;
			$output .= "<br>";
			}
    }
		for ($i=0;$i<$fileorder;$i++)
		{
			if($_FILES['uploadField'.$i]['name'] != "")
			{
			$filename[$i] = $_FILES['uploadField'.$i]['name'];
			$filesize[$i] = $_FILES['uploadField'.$i]['size'];
			//先取得$i所對應的檔案資料,刪除舊檔,上傳新檔,然後以更新的方式將資料更新
			$sql2="select * from uploadfile where follow ='".$id."' and fileorder ='".$i."';";
			$result2=$this->access_query($sql2);
      $rs2 = $result2->fetchAll(PDO::FETCH_ASSOC);
      // 應該只有一筆
      $total_rows2 = count($rs2);
			//刪除$i所對應的資料庫欄位與所對應的上傳檔
			$file_ext[$i] = ereg_replace("^.+\\.([^.]+)$", "\\1", $rs2[0]["filename"]);
			$file_version[$i] = $rs2[0]["version"];
        if(stristr('(jpg|png|gif)',$file_ext[$i]))
        {
				$filetodelete = $file_version[$i]."_".$this->database_name."_".$id."_".$rs2[0]["fileorder"].".".$file_ext[$i];
				unlink($path.$filetodelete);
				$display_filetodelete = $file_version[$i]."_".$this->database_name."_".$id."_display_".$rs2[0]["fileorder"].".".$file_ext[$i];
				unlink($path.$display_filetodelete);
			}
			else
			{
				$filetodelete = $file_version[$i]."_".$this->database_name."_".$id."_".$rs2[0]["fileorder"].".".$file_ext[$i];
				unlink($path.$filetodelete);
			}

			$file_ext[$i] = ereg_replace("^.+\\.([^.]+)$", "\\1", $filename[$i]);
			$upload_filename[$i] = $file_version[$i]."_".$this->database_name."_".$id."_".$i.".".$file_ext[$i];
				if(stristr('(jpg|png|gif|7z|doc|ppt|xls|gz|zip|pdf|wnk|swf|flv|stl)',$file_ext[$i]))
				{
          move_uploaded_file($_FILES['uploadField'.$i]['tmp_name'], $path.$upload_filename[$i]);

            if(stristr('(jpg|png|gif)',$file_ext[$i]))
				    {
					    $this->thumbnail($upload_filename[$i],$file_version[$i]."_".$this->database_name."_".$id."_display_".$i.".".$file_ext[$i],150);
				    }

          $sql3="update uploadfile set filename='".$filename[$i]."',filesize='".$filesize[$i]."' where follow ='".$id."' and fileorder ='".$i."';";
					$rs3=$this->access_exec($sql3);
					$output .= "已經替換以下的檔案<br />";
					$output .= $filename[$i];
					$output .= "<br />";
					$output .= $filesize[$i];
					$output .= "<br />";
				}
				else
				{
				    $output .= "只允許(jpg|png|gif|7z|doc|ppt|xls|gz|zip|pdf|wnk|swf|flv|stl)檔案上傳<br />";
					$output .= "上傳檔案".$filename[$i]."替換有問題<br />";
				}
			}
		}
	//接著處理後續新增的上傳檔案,此一部分則類似新增上傳檔的程序,move好上傳檔,然後在uploadfile資料表中新增對應的資料
	$m=$fileorder;
    //取主資料的$current_version作為上傳檔的最後version
	//由於目前主資料沒有版次,因此設為 0.1
	$ini_version="0.1";
    while($_FILES['uploadField'.$m]['name'] != "")
    {
    $filename[$m] = $_FILES['uploadField'.$m]['name'];
    $filesize[$m] = $_FILES['uploadField'.$m]['size'];
    $file_ext[$m] = ereg_replace("^.+\\.([^.]+)$", "\\1", $filename[$m]);
	    if(stristr('(jpg|png|gif|7z|doc|ppt|xls|gz|zip|pdf|wnk|swf|flv|stl)',$file_ext[$m]))
		{
		    $sql3="insert into uploadfile(tablename,follow,version,fileorder,filename,filesize) values ('$this->database_name','$id','$ini_version','$m','$filename[$m]','$filesize[$m]')";
        $rs3=$this->access_exec($sql3);
        $output .= "已將第".$m."筆資料存入資料庫";
        $output .= "<br />";
			$upload_filename[$m] = "0.1_".$this->database_name."_".$id."_".$m.".".$file_ext[$m];
		    move_uploaded_file($_FILES['uploadField'.$m]['tmp_name'], $path.$upload_filename[$m]);
        if(stristr('(jpg|png|gif)',$file_ext[$m]))
		    {
		        $this->thumbnail($upload_filename[$m],"0.1_".$this->database_name."_".$id."_display_".$m.".".$file_ext[$m],150);
		    }
			$output .= "以下為新增的上傳檔<br />";
			$output .= $path;
			$output .= "<br />檔名<br />";
			$output .= $filename[$m];
			$output .= "<br>檔案大小<br>";
			$output .= $filesize[$m];
			$output .= "<br />";
		}
		else
		{
      $output .= "只允許(jpg|png|gif|7z|doc|ppt|xls|ga|zip|pdf|wnk|swf|flv|stl)檔案上傳<br />";
			$output .= "新增檔案".$filename[$m]."存檔有問題<br />";
		}
		$m++;
    }
	return $output;
	}
	
	function search_form()
	{
	    $searchform = "\n\n<form method=\"post\" action=\"".$this->mysn."?".$this->mysu."&amp;act=dosearch\">\n"
		. "<br />關鍵字:<input type=\"text\" name=\"keyword\" size=\"25\"></input>\n"
		. "<input type=\"submit\" value=\"查詢\"></input>\n"
		."</form>\n";
		$output .= $searchform;
		return $output;
	}
	
	function do_search($keyword)
	{
		$sql = "SELECT * FROM SForum WHERE for_ptitle like '%".$keyword."%' or for_text like '%".$keyword."%' or for_name like '%".$keyword."%' ORDER BY id DESC";
		$result = $this->access_query($sql);
    $rs = $result->fetchAll(PDO::FETCH_ASSOC);
    // 改為 PDO
    $total_rows = count($rs);
		//$total_rows=$rs->RecordCount();
		
		$item_per_page = $_GET["item_per_page"];
		$page = $_GET["page"];
		
		// 設定頁面控制內定參數
		if(!isset($item_per_page))
		{
		$item_per_page = 10;
		}
		if(!isset($page))
		{
		$page = 1;
		}
		$totalpage = ceil($total_rows/$item_per_page);

		$starti = $item_per_page * ($page - 1) + 1;
    $endi = $starti + $item_per_page - 1;
    
    //$rs->MoveFirst();
		
      if ($total_rows > 0) {
			//列印最外圍的內容
			if ((int)($page * $item_per_page) < $total_rows)
			{
				$notlast = true;
				// 請注意,在搜尋結果列印時,若內容在"子"訊息,則必須透過 search_access_list,列出"主訊息",而非"子"訊息的回應
				// access_list 與 search_access_list 的差別,在於 access_list 之 wid 為 id,而 search_access_list 則採用 wid 為 wid
				$output .= $this->search_access_list($rs,$starti,$endi);
			}
			else
			{
				$output .= $this->search_access_list($rs,$starti,$total_rows);
		    }

			if ($page > 1)
			{
				$page_num=$page-1;
				$output .= "<a href=\"";
				$output .= $this->mysn."?".$this->mysu."&amp;page=".$page_num."&amp;item_per_page=".$item_per_page."&amp;act=dosearch&amp;keyword=".$keyword;
				$output .= "\">上一頁</a>< ";
			}

			for ($j=0;$j<$totalpage;$j++)
			{
				  $page_now=$j+1;
				  if($page_now==$page)
				  {
				 $output .="<font size=\"+1\" color=\"red\">".$page." </font>";
				  }
				  else
				  {
					$output .= "<a href=\"";
					$output .=$this->mysn."?".$this->mysu."&amp;page=".$page_now."&amp;item_per_page=".$item_per_page."&amp;act=dosearch&keyword=".$keyword;
					$output .= "\">".$page_now."</a> ";
				  }
			}

			if ($notlast == true)
			{
			  $nextpage=$page+1;
			  $output .= "><a href=\"";
			  $output .= $this->mysn."?".$this->mysu."&amp;page=".$nextpage."&amp;item_per_page=".$item_per_page."&amp;act=dosearch&amp;keyword=".$keyword;
			  $output .= "\">下一頁</a>";
			}
        } else {
			$output .= "<br />目前沒有與關鍵字\"".$keyword."\"相關的資料<br />\n";
            }
    $this->ptitle = ""; //the new thread's title is empty
	return $output;
	}

  function keephtml($string){
          // 只去除 table 相關的標註,因為會影響資料顯示畫面
		  // 最理想的狀況為分段處理,在 code 內保留所有 tags, 外圍則不允許 table 相關 tags
		  
		  // 最先除去這三個標註的方法,改為換成 html 特殊符號
          //$res = $this->strip_only($string, '<table><tr><td>');
		  $res = $this->fixcodeblocks($string);
		  // 將單引號換成 html 特殊符號
		  $res = str_replace("'","&apos;",$res);
		  // 將 <table><tr><td> 換成 html 特殊符號
		  //$res = str_replace("<table>","&lt;table&gt;",$res);
		  $res = preg_replace("#(<[\s]tr[\s]>)#i","&lt;tr&gt;",$res);
		  $res = preg_replace("#(<[\s]td[\s]>)#i","&lt;td&gt;",$res);
		  $res = preg_replace("#(<[\s]table[\s]>)#i","&lt;table&gt;",$res);
		  
		  // 將</table></tr></td> 換成 html 特殊符號
		  $res = preg_replace('#</?tr[^>]*>#is',"&lt;/tr&gt;",$res);
		  $res = preg_replace('#</?td[^>]*>#is',"&lt;/td&gt;",$res);
		  $res = preg_replace('#</?table[^>]*>#is',"&lt;/table&gt;",$res);
		  
          //$res = htmlentities($res,ENT_QUOTES,'UTF-8');
		  /*
          $res = str_replace("&lt;","<",$res);
          $res = str_replace("&gt;",">",$res);
          $res = str_replace("&quot;",'"',$res);
          $res = str_replace("&amp;",'&',$res);
		  */
		  //$res = preg_replace(array("'&(quot|#34);'i", "'&(amp|#38);'i", "'&(apos|#39);'i", "'&(lt|#60);'i", "'&(gt|#62);'i", "'&(nbsp|#160);'i"), array("\"", "&", "'", "<", ">", " "), $res);
		return $res;
    }

function strip_only($str, $tags) {
    if(!is_array($tags)) {
        $tags = (strpos($str, '>') !== false ? explode('>', str_replace('<', '', $tags)) : array($tags));
        if(end($tags) == '') array_pop($tags);
    }
    foreach($tags as $tag) $str = preg_replace('#</?'.$tag.'[^>]*>#is', '', $str);
    return $str;
}

function fixcodeblocks($string) {
	// Create a new array to hold our converted string
	$newstring = array();
	
	// This variable will be true if we are currently between two code tags
	$code = false;
	
	// The total length of our HTML string
	$j = mb_strlen($string);
	
	// Loop through the string one character at a time
	for ($k = 0; $k < $j; $k++) {
		// The current character
		$char = mb_substr($string, $k, 1);
		
		if ($code) {
			// We are between code tags
			// Check for end code tag
			if ($this->atendtag($string, $k)) {
				// We're at the end of a code block
				$code = false;
				
				// Add current character to array
				array_push($newstring, $char);
				
			} else {
				// Change special HTML characters
				$newchar = htmlspecialchars($char, ENT_QUOTES,'UTF-8');
				
				// Add character code to array
				array_push($newstring, $newchar);
			}
		} else {
			// We are not between code tags
			// Check for start code tag
			if ($this->atstarttag($string, $k)) {
				// We are at the start of a code block
				$code = true;
			}
			// Add current character to array
			array_push($newstring, $char);
		}
	}
	//Turn the new array into a string
	$newstring = join("", $newstring);
	
	// Return the new string
	return $newstring;
}

function atstarttag($string, $pos) {
	// Only check if the last 6 characters are the start code tag
	// if we are more then 6 characters into the string
	if ($pos > 4) {
		// Get previous 6 characters
		$prev = mb_substr($string, $pos - 5, 6);
		
		// Check for a match
		if ($prev == "<code>") {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function atendtag($string, $pos) {
	// Get length of string
	$slen = mb_strlen($string);
	
	// Only check if the next 7 characters are the end code tag
	// if we are more than 6 characters from the end
	if ($pos + 7 <= $slen) {
		// Get next 7 characters
		$next = mb_substr($string, $pos, 7);
		
		// Check for a match
		if ($next == "</code>") {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function do_download($wid,$fileorder)
{
    // 由 $wid 取得相關的上傳檔案資料
    $sql="select * from uploadfile where follow ='".$wid."' and fileorder='".$fileorder."';";
    $result = $this->access_query($sql);
    $rs = $result->fetchAll(PDO::FETCH_ASSOC);
		//$total_rows=$rs->RecordCount();
		// 只有一筆資料
		$orig_filename = $rs[0]["filename"];
		$file_version = $rs[0]["version"];
		$file_ext = ereg_replace("^.+\\.([^.]+)$", "\\1", $orig_filename);
		// 實際對應的檔案名稱為 $file_version."_".$this->database_name."_".$file_follow."_".$fileorder.".".$file_ext;
		$plugin_fl = $file_version."_".$this->database_name."_".$wid."_".$fileorder.".".$file_ext;
    // 當使用 Windows 作為伺服器時
    //$plugin_download_path = dirname(__FILE__)."\\upload_files\\";
    // 當使用 Linux 作為伺服器時
		$plugin_download_path = dirname(__FILE__)."/upload_files/";
		// 這裡可以使用在 cms.php 中已經引入的 broswer_detection()
     $plugin_a_browser_data = browser_detection('full');
     $plugin_utf8_filename = $orig_filename;
	   $plugin_big5_filename = iconv("utf-8","big-5",$plugin_utf8_filename);
	   // 取檔用的檔名
	   $plugin_full_filename = $plugin_download_path.$plugin_fl;

	if (!file_exists($plugin_full_filename))
	{
		$output = "file not found error<br />";
		$output .=$plugin_utf8_filename;
		$output .="<br />";
		$output .= '<p>File '.$plugin_full_filename.'</p>';
		return $output;
	}
	else 
	{
	// for debug
	/*
	$output .= $plugin_utf8_filename;
	$output .="<br>";
	$output .=filesize($plugin_full_filename);
	$output .="<br>";
	$output .=$plugin_full_filename;
	$output .="<br>";
	$output .= $plugin_a_browser_data[0];
	return $output;
	*/
	
    @ob_end_clean();
    @set_time_limit(0);
    
  // for IE??
  header('Cache-Control: maxage=3600');
  header('Pragma: public');
  // for IE??
	header('Content-Type: application/save-as');

		if ( $plugin_a_browser_data[0] != 'ie' )
		{
			header('Content-Disposition: attachment; filename="'.$plugin_utf8_filename.'"');
			header('Content-Length:'.filesize($plugin_full_filename));
		}
		else
		{
			if ( $plugin_a_browser_data[1] >= 5 )
			{
        if(ini_get('magic_quotes_gpc')=="1")
          {
            $plugin_big5_filename=stripslashes($plugin_big5_filename);
          }
          else
          {}
			header('Content-Disposition: attachment; filename="'.$plugin_big5_filename.'"');
			header('Content-Length:'.filesize($plugin_full_filename));
			}
		}
		header('Content-Transfer-Encoding: binary');
		@ob_clean();
    flush();
		readfile($plugin_full_filename);
	
		/*
		if ($plugin_fh = @fopen($plugin_full_filename, "rb")) {
			while (!feof($plugin_fh))
			{
			echo fread($plugin_fh, filesize($plugin_full_filename));
			}
			fclose($plugin_fh);
		}
		*/
		exit;
	}
}

    // Main_page: Show back-to-main link
    function Main_page() {
        //print("<a href=\"".$_SERVER['PHP_SELF']."\">Go to Main Page</A>\n");
		$output .= "<a href=\"".$this->mysn."?".$this->mysu."\">Go to Main Page</a>";
		// 目前只讓管理者查詢
		//if($this->myadm)
		//{
		$output .= $this->search_form()."\n\n";
		//}
		return $output;
    }

	function thumbnail($from_name,$to_name,$max_x,$max_y)
	{
		###############################################################
		# Thumbnail Image Generator 1.3
		###############################################################
		# Visit http://www.zubrag.com/scripts/ for updates
		###############################################################

		// REQUIREMENTS:
		// PHP 4.0.6 and GD 2.0.1 or later
		// May not work with GIFs if GD2 library installed on your server
		// does not support GIF functions in full

		// Parameters:
		// src - path to source image
		// dest - path to thumb (where to save it)
		// x - max width
		// y - max height
		// q - quality (applicable only to JPG, 1 to 100, 100 - best)
		// t - thumb type. "-1" - same as source, 1 = GIF, 2 = JPG, 3 = PNG
		// f - save to file (1) or output to browser (0).

		// Sample usage:
		// 1. save thumb on server
		// http://www.zubrag.com/thumb.php?src=test.jpg&dest=thumb.jpg&x=100&y=50
		// 2. output thumb to browser
		// http://www.zubrag.com/thumb.php?src=test.jpg&x=50&y=50&f=0


		// Below are default values (if parameter is not passed)

		// save to file (true) or output to browser (false)
		$save_to_file = true;

		// Quality for JPEG and PNG.
		// 0 (worst quality, smaller file) to 100 (best quality, bigger file)
		// Note: PNG quality is only supported starting PHP 5.1.2
		$image_quality = 100;

		// resulting image type (1 = GIF, 2 = JPG, 3 = PNG)
		// enter code of the image type if you want override it
		// or set it to -1 to determine automatically
		$image_type = -1;

		// maximum thumb side size
		//if(!$max_x) $max_x = 200;
		//if(!$max_y) $max_y = 1024;

		// cut image before resizing. Set to 0 to skip this.
		$cut_x = 0;
		$cut_y = 0;

		// Folder where source images are stored (thumbnails will be generated from these images).
		// MUST end with slash.
		$images_folder = dirname(__FILE__)."/upload_files/";

		// Folder to save thumbnails, full path from the root folder, MUST end with slash.
		// Only needed if you save generated thumbnails on the server.
		// Sample for windows:     c:/wwwroot/thumbs/
		// Sample for unix/linux:  /home/site.com/htdocs/thumbs/
		$thumbs_folder = dirname(__FILE__)."/upload_files/";


		///////////////////////////////////////////////////
		/////////////// DO NOT EDIT BELOW
		///////////////////////////////////////////////////

		//$to_name = '';
		/*
		if (isset($_REQUEST['f'])) {
		  $save_to_file = intval($_REQUEST['f']) == 1;
		}

		if (isset($_REQUEST['src'])) {
		  $from_name = urldecode($_REQUEST['src']);
		}
		else {
		  die("Source file name must be specified.");
		}

		if (isset($_REQUEST['dest'])) {
		  $to_name = urldecode($_REQUEST['dest']);
		}
		else if ($save_to_file) {
		  die("Thumbnail file name must be specified.");
		}

		if (isset($_REQUEST['q'])) {
		  $image_quality = intval($_REQUEST['q']);
		}

		if (isset($_REQUEST['t'])) {
		  $image_type = intval($_REQUEST['t']);
		}

		if (isset($_REQUEST['x'])) {
		  $max_x = intval($_REQUEST['x']);
		}

		if (isset($_REQUEST['y'])) {
		  $max_y = intval($_REQUEST['y']);
		}
		*/

		if (!file_exists($images_folder)) die('Images folder does not exist (update $images_folder in the script)');
		if ($save_to_file && !file_exists($thumbs_folder)) die('Thumbnails folder does not exist (update $thumbs_folder in the script)');

		// Allocate all necessary memory for the image.
		// Special thanks to Alecos for providing the code.
		ini_set('memory_limit', '-1');

		// include image processing code
		require_once('image.class.php');

		$img = new Zubrag_image;

		// initialize
		$img->max_x        = $max_x;
		$img->max_y        = $max_y;
		$img->cut_x        = $cut_x;
		$img->cut_y        = $cut_y;
		$img->quality      = $image_quality;
		$img->save_to_file = $save_to_file;
		$img->image_type   = $image_type;

		// generate thumbnail
		$img->GenerateThumbFile($images_folder . $from_name, $thumbs_folder . $to_name);
    }

	function jaris($flv_source,$flv_width,$flv_height)
	{

	$output = '<object
  classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"
  codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=10,0,45,2"
  width="'.$flv_width.'" height="'.$flv_height.'"
>
	<param name="allowFullscreen" value="true">
	<param name="allowScriptAccess" value="always">
	<param name="movie" value="JarisFLVPlayer.swf">
	<param name="bgcolor" value="#000000">
	<param name="quality" value="high">
	<param name="scale" value="noscale">
	<param name="wmode" value="opaque">
	<param name="flashvars" value="source='.$flv_source.'&type=video&streamtype=file&poster=poster.png&autostart=false&logo=logo.png&logoposition=top left&logoalpha=30&logowidth=130&logolink=http://jaris.sourceforge.net&hardwarescaling=false&darkcolor=000000&brightcolor=4c4c4c&controlcolor=FFFFFF&hovercolor=67A8C1">
	<param name="seamlesstabbing" value="false">
	<embed
	  type="application/x-shockwave-flash"
	  pluginspage="http://www.adobe.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash"
	  width="'.$flv_width.'" height="'.$flv_height.'"
	  src="JarisFLVPlayer.swf"
	  allowfullscreen="true"
	  allowscriptaccess="always"
	  bgcolor="#000000"
	  quality="high"
	  scale="noscale"
	  wmode="opaque"
	  flashvars="source='.$flv_source.'&type=video&streamtype=file&poster=poster.png&autostart=false&logo=logo.png&logoposition=top left&logoalpha=30&logowidth=130&logolink=http://jaris.sourceforge.net&hardwarescaling=false&darkcolor=000000&brightcolor=4c4c4c&controlcolor=FFFFFF&hovercolor=67A8C1"
	  seamlesstabbing="false"
	>
	  <noembed>
	  </noembed>
	</embed>
</object>';
    return $output;
    }
    
	function stlview($stl_source,$stl_width,$stl_height)
	{

	$output = '<applet code="org.jdesktop.applet.util.JNLPAppletLauncher" width="'.$stl_width.'" height="'.$stl_height.'" 
archive="http://www.mde.tw/jogl/applet-launcher.jar, 
http://www.mde.tw/jogl/jogl.jar, 
http://www.mde.tw/jogl/gluegen-rt.jar,http://www.mde.tw/bin_stl_viewer.jar">
<param name="codebase_lookup" value="false">
<param name="draggable" value="true">
<param name="noddraw.check" value="true">
<param name="progressbar" value="true">
<param name="jnlpNumExtensions" value="1">
<param name="jnlpExtension1" value="http://www.mde.tw/jogl/jogl.jnlp">
<param name="jnlp_href" value="http://www.mde.tw/applet-stl.jnlp">
<param name="stlFile" value="'.$stl_source.'" />
</applet>';
    return $output;
    }
}

if (!isset($forum)) {
    $forum = new SForum;
}

?>

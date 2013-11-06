<?php
// 2010 fall 希望加上 Flat list,可以按照特定順序列出所有的 posts
// 並且透過查詢,可以直接指到特定某一筆資料,以便進行修改或刪除
// 刪除必須要新增單筆刪除,目前為整串刪除
/*
本系統允許 jpg, png, gif, 7z, gz, zip, pdf 等格式的上傳檔案.
任何人都可以新增資料, 管理者可以更新與刪除資料
{{{PLUGIN:sforumMain("phpforum2.db","0","DESC");}}}
只有管理者可以新增並管理資料
{{{PLUGIN:sforumMain("phpforum2.db","1","DESC");}}}
*/
//
require_once("SForum_class.php");
require_once("email_validation.php");
// 直接設定時區為台北
date_default_timezone_set("Asia/Taipei");

function sforumMain($dbname,$close,$listorder)
{
global $adm;

$validator=new email_validation_class;

$forum = new SForum();
//將論壇名稱指為 $dbname
//這個名稱與圖檔及 flv 的絕對路徑有關,採用 class 中的設定值
//$forum->SFname = $dbname;
$forum->connect($dbname);
$forum->listorder = $listorder;

//將論壇名稱蓋掉
//$output .= $forum->Show_SFname();
$output .= $forum->Main_page();
//print("<br><br>\n");
$output .= "<br /><br />\n";

if (isset($_POST['submit'])) {
    $for_mail = $_POST['frm_mail'];
    if (!empty($_POST['frm_mail'])) {
	    $valmail = $validator->ValidateEmailAddress($_POST['frm_mail']);
        if ($valmail == 0) {
            $output .= "Your mail was invalid so was droped!<br /><br />\n";
            $for_mail = "";      // if mail invalid then dropped
        }
    }
//改為由 ldap 取得帳號,作為 data author
    //$output .= $forum->Add_new_post($_POST['frm_ptitle'],$_POST['frm_text'],$for_mail,$_POST['frm_ip'],$_POST['frm_name'],$_POST['frm_wid']);
    $output .= $forum->Add_new_post($_POST['frm_ptitle'],$_POST['frm_text'],$for_mail,$_POST['frm_ip'],$_SESSION["account"],$_POST['frm_wid']);
}

// 這裡處理 $_POST['doedit']
if (isset($_GET['amp;act']) && $_GET['amp;act'] == "doedit" && $adm)
{
    $ptitle = $_POST["frm_ptitle"];
	$text = $_POST["frm_text"];
	$mail = $_POST["frm_mail"];
	$ip = $_POST["frm_ip"];
	$name = $_POST["frm_name"];
	$wid = $_POST["frm_wid"];
	$id = $_POST["frm_id"];
	$fileorder = $_POST["fileorder"];
	
    $output .= $forum->Edit_post($ptitle,$text,$mail,$ip,$name,$wid,$id,$fileorder);
}
elseif (isset($_GET['act']) && $_GET['act'] == "dosearch")
{
    $keyword = $_POST["keyword"];
	if($keyword != "")
	{
	    $output .= "以下為包含\"". $keyword."\"的相關內容";
		$output .= $forum->do_search($keyword);
	}
	elseif($_GET["keyword"] !="")
	{
		$output .= "以下為包含\"". $_GET["keyword"]."\"的相關內容";
		$output .= $forum->do_search($_GET["keyword"]);
	}
	else
	{
	    $output .= "請輸入關鍵字後查詢";
	}
}
elseif (isset($_GET['act']) && $_GET['act'] == "download")
{
    $download_wid = $_GET["wid"];
	$fileorder = $_GET["fileorder"];
	$output .= $forum->do_download($download_wid,$fileorder);
}

if (isset($_GET['wid'])) {
    $output .= $forum->Show_SForum_Threads($_GET['wid']);
    $forum->pansw = $_GET['wid'];
} elseif (isset($_GET['id']))
{
    $output .= $forum->Show_SForum_SinglePost($_GET['id']);
    //$forum->pansw = $_GET['id'];
    $forum->pansw = $_GET['id'];
} else{
    $output .= $forum->Show_SForum();
    $forum->pansw = 0;
}
// 設計成若 $close ==1, 則只有 admin 可以看見表單
if($close == "1")
{
    if($adm && !$_GET["editid"])
    {
        $output .= $forum->Show_frm($forum->ptitle);
    }
}
else
{
// 在編輯模式下,不列印新增表單
   if(!$_GET["editid"])
   {
        $output .= $forum->Show_frm($forum->ptitle);
	}
}

//print("<br><br>\n");
$output .= "<br /><br />\n";
$output .= $forum->Main_page();

return $output;
}
?>

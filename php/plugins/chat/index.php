<?php

function chatMain($chatroom)
{
    //if($_SESSION["account"])
	//{
		require_once dirname(__FILE__).'/chat/src/phpfreechat.class.php';
		$params["serverid"] = md5(__FILE__);
		// save the serverid to session
    $_SESSION["serverid"] = $params["serverid"];
    // $params["channels"] = array("room1");
    //$params["frozen_channels"] default is empty array to allow user create their own channel
  
    $params["channels"] = array($chatroom);
    // 加上下列設定, 就可以移除外部的 chat 目錄 20110515 
  $params["data_public_url"] = "./plugins/chat/chat/data/public";
  $params["data_public_path"] = "./plugins/chat/chat/data/public";
		$params["language"] = "zh_TW";
		//$params["nick"] = $_SESSION["account"];
    $params["frozen_nick"] = true;	
    $params["isadmin"] = false;
		$chat = new phpFreeChat($params);
		$output .= $chat->printChat(true);
	//}
  //else
	//{
    //$output = header("Location:/logout.php");
	//}
	return $output;
}


function get_chat_record()
{
require_once('tcpdf/config/lang/eng.php');
require_once('tcpdf/tcpdf.php');
// 在 XH 執行, 必須要清除 buffer
// clean the output buffer
ob_clean();
// Extend the TCPDF class to create custom Header and Footer
class MYPDF extends TCPDF {
    //Page header
    public function Header() {
        // Logo
        $image_file = K_PATH_IMAGES.'kmol.png';
        $this->Image($image_file, 10, 10, 40, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        // Set font
        $this->SetFont('arialunicid0', 'B', 14);
        // 以台灣時區的時間為準
        date_default_timezone_set ("Asia/Taipei");
        // Title
        $this->Cell(0, 15, '<< '.date("F j, Y, g:i:s a").' 的會議記錄 >>', 0, false, 'C', 0, '', 0, false, 'M', 'M');
    }

    // Page footer
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}
// create new PDF document
//$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false); 
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false); 

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('KMOL User');
$pdf->SetTitle('TCPDF Chat Example');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF, example, kmol, chat');
// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
//set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
//set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
//set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO); 
//set some language-dependent strings
$pdf->setLanguageArray($l); 
// ---------------------------------------------------------
// set font
$pdf->SetFont('arialunicid0', 'U', 12);
// add protection
//$pdf->SetProtection(array('print','modify','copy','annot-forms'), '123');
// add a page
$pdf->AddPage();
// print a line using Cell()
//$pdf->Cell(0, 10, 'こんにちは', 1, 1, 'C');
// get esternal file content
$utf8text = file_get_contents('./plugins/chat/chat/data/private/logs/'.$_SESSION["serverid"].'/chat.log', false);
// ---------------------------------------------------------
// write the text
$pdf->Write(5, $utf8text, '', 0, '', false, 0, false, false, 0);
//Close and output PDF document
$pdf->Output('meeting_report.pdf', 'I');
}


function reset_meeting()
{
    $dir = dirname(__FILE__);
    $output = rrmdir($dir."/chat/data");
    $output = smartCopy($dir."/chat/data_backup",$dir."/chat/data",0755,0644);
    $output = "";
    $output .= "會議已經重置!";
    return $output;
}

//smartCopy("./users/user_src","./users/".$_POST["site_title"],0755,0644);
function smartCopy($source, $dest, $folderPermission=0755,$filePermission=0644){

# source=file & dest=dir => copy file from source-dir to dest-dir
# source=file & dest=file / not there yet => copy file from source-dir to dest and overwrite a file there, if present
# source=dir & dest=dir => copy all content from source to dir
# source=dir & dest not there yet => copy all content from source to a, yet to be created, dest-dir
    $result=false;

    if (is_file($source)) {
			# $source is file
        if(is_dir($dest)) {
				# $dest is folder
            if ($dest[strlen($dest)-1]!='/')
			# add '/' if necessary
                $__dest=$dest."/";
            $__dest .= basename($source);
            }
        else {
				# $dest is (new) filename
            $__dest=$dest;
            }
        $result=copy($source, $__dest);
        chmod($__dest,$filePermission);
        }
    elseif(is_dir($source)) {
			# $source is dir
        if(!is_dir($dest)) {
				# dest-dir not there yet, create it
            @mkdir($dest,$folderPermission);
            chmod($dest,$folderPermission);
            }
        if ($source[strlen($source)-1]!='/')
		# add '/' if necessary
            $source=$source."/";
        if ($dest[strlen($dest)-1]!='/')
		# add '/' if necessary
            $dest=$dest."/";

    # find all elements in $source
        $result = true;
		# in case this dir is empty it would otherwise return false
        $dirHandle=opendir($source);
        while($file=readdir($dirHandle)) {
				# note that $file can also be a folder
            if($file!="." && $file!="..") {
					# filter starting elements and pass the rest to this function again
                # echo "$source$file ||| $dest$file<br />\n";
                $result=smartCopy($source.$file, $dest.$file, $folderPermission, $filePermission);
                }
            }
        closedir($dirHandle);
        }
    else {
        $result=false;
        }
    return $result;
}
// end SmartCopy function

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
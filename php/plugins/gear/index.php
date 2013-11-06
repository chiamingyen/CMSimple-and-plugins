<?php
/*
 * 齒輪資料庫欄位由中文改為英文, 並且連線方式由 ADODB 改為 PDO
 * CREATE TABLE steel ( 
    serialno      INTEGER,
    unsno         TEXT,
    aisino        TEXT,
    treatment     TEXT,
    yield_str     INTEGER,
    tensile_str   INTEGER,
    stretch_ratio INTEGER,
    sectional_shr INTEGER,
    brinell       INTEGER 
);

CREATE TABLE lewis ( 
    serialno INTEGER PRIMARY KEY
                     NOT NULL,
    gearno   INTEGER,
    type1    NUMERIC,
    type4    NUMERIC,
    type3    NUMERIC,
    type2    NUMERIC 
);
 */

function gearMain()
{
	global $Paths;
	global $sn,$su;
// 此地的 prototype.js 取用位於 cmsimple 系統中 jscript 目錄下的共用檔案
$output.="<script src=\"jscript/prototype.js\" language=\"JavaScript\" type=\"text/javascript\"></script>";
$output.="<script type=\"text/javascript\" language=\"JavaScript\">";
$output.="function submitEntryForm(event)";
$output.="{";
$output.="  var updater = new Ajax.Updater({success:'var_list',error:'error_list'},'./plugins/gear/designaction.php', {parameters:{horsepower:$('horsepower').value,rpm:$('rpm').value,ratio:$('ratio').value,toothtype:$('toothtype').value,safetyfactor:$('safetyfactor').value,material:$('material').value,npinion:$('npinion').value}});
";
$output.="event.preventDefault();";
$output.="}";
$output.="function addObservers(event)";
$output.="{";
$output.="$('entry').observe('submit',submitEntryForm);";
$output.="}";
$output.="Event.observe(window,'load',addObservers);";
$output.="</script>";
/*
	$output.="本程式的目的在輔助設計者選擇齒輪的尺寸大小，";
	$output.="由於相囓合的兩齒輪其徑節 (Diametral Pitch) 相同";
	$output.="，齒的大小也相同。因徑節為每單位直徑的齒數，因此徑節愈大，則其齒的尺寸愈小";
	$output.="；反之，徑節愈小，則齒的尺寸則愈大。";
	$output.="一般在設計齒輪對時，為避免使用過大的齒及過寬的齒面厚度，因此必須要就齒輪大小與強度與負載加以設計。";
	$output.="一般而言是希望齒輪面的寬度 (Face Width) 能大於3倍周節 (Circular Pitch)，以避免選用太大的齒尺寸。";
	$output.="並且希望齒輪面的寬度 (Face Width) 能小於5倍周節，以便齒面傳遞負載時能有較為均勻的分佈，因此";
	$output.="設 d 為齒輪的節圓直徑(Pitch Diameter)，單位為英吋<br>";
	$output.="N 為齒數<br>";
	$output.="P 為徑節， 即單位英吋的齒數<br>";
	$output.="因此 d=N/P<br>";
	$output.="設 V 為節線速度(Pitch Line Velocity)，單位為英呎/分鐘<br>";
	$output.="因此 V=(PI)*d*n/12<br>";
	$output.="其中 n 為齒輪轉速，單位為 rpm<br>";
	$output.="設傳輸負載大小為 W，單位為 pounds<br>";
	$output.="因此 W=33000H/V<br>";
	$output.="其中 H 為傳輸功率，單位為 hourse power<br>";
	$output.="若設 K 為速度因子(Velocity Factor)<br>";
	$output.="因此 K=1200/(1200+V)<br>";
	$output.="最後可求出齒輪的齒面寬度(Face Width) F ，單位為英吋<br>";
	$output.="即 F=WP/KYS<br>";
	$output.="其中 S 為齒面的材料彎曲應力強度";
	$output.="設計要求:控制所選齒的尺寸大小，在滿足強度與傳輸負載的要求下，讓齒面厚度介於3倍周節與5倍周節之間。<br>";
	$output.="設計者可以選擇的參數:<br>";
	$output.="安全係數(建議值為3以上)<br>";
	$output.="齒輪減速比<br>";
	$output.="馬達傳輸功率，單位為 horse power<br>";
	$output.="馬達轉速，單位為 rpm<br>";
	$output.="齒制(Gear System)<br>";
	$output.="齒輪材料與強度<br>";
    */
	// 以下利用資料庫表格呼叫,印出本設計所使用之Lewis Form Factor 表格
	//$output.="本設計運算時所查詢的Lewis Form Factor表列如下:";
	
	//$output.="<form method=post action=".$sn."?".$su."&menu=designaction>";
  $output.="<form id=entry method=post action=\"\">";
	$output.="請填妥下列參數，以完成適當的齒尺寸大小設計。<br>";
	$output.="馬達馬力:<input type=text name=horsepower id=horsepower value=100 size=10>horse power<br>";
	$output.="馬達轉速:<input type=text name=rpm id=rpm value=1120 size=10>rpm<br>";
	$output.="齒輪減速比: <input type=text name=ratio id=ratio value=4 size=10><br>";
	$output.="齒形:<select name=toothtype id=toothtype>";
	$output.="<option value=type1>壓力角20度,a=0.8,b=1.0";
	$output.="<option value=type2>壓力角20度,a=1.0,b=1.25";
	$output.="<option value=type3>壓力角25度,a=1.0,b=1.25";
	$output.="<option value=type4>壓力角25度,a=1.0,b=1.35";
	$output.="</select><br>";
	$output.="安全係數:<input type=text name=safetyfactor id=safetyfactor value=3 size=10><br>";
	include("db_mdesign.inc.php");
    if ($dbc==NULL)
    {
    $output="<br>抱歉!資料庫無法連線!<br>";
	include("db.close.php"); 
	return $output;
	exit();   
    }

//$dbc->SetFetchMode(2);

$sql="select serialno,unsno,treatment from steel";

$result=$dbc->query($sql);
$rs = $result->fetchAll(PDO::FETCH_ASSOC);
$total_rows=count($rs);

//$rs=$dbc->Execute($sql);
//$rs->MoveFirst();
//if($rs->EOF)
if($total_rows == 0)
{
	$output="<br>no data<br>";
	include("db.close.php"); 
	exit(); 
}
else
{
	$output.="齒輪材質:<select name=material id=material>";
	//while(!$rs->EOF)
        for($i=0;$i<$total_rows;$i++)
	{
		$output.="<option value=".$rs[$i]["serialno"].">UNS - ".$rs[$i]["unsno"]." - ".$rs[$i]["treatment"];
		//$rs->MoveNext();
	}
	$output.="</select><br>";
}

	include("db.close.php");

	$output.="小齒輪齒數:<input type=text name=npinion id=npinion value=18 size=10><br>";
	$output.="<input type=submit id=submit value=進行運算>";
	$output.="</form>";

       // 交由外部 cdesign plugin 呼叫, 因此蓋掉局部的執行表單
	//$output.=printmenu();
    
$output.="<br><br>以下為設計結果:";
$output.="<ul id=\"var_list\"></div>";
$output.="還沒有設計結果:";
$output.="<ul id=\"error_list\"></div>";


return $output;
}
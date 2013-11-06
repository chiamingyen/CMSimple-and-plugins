<?php
// #####################################################################
// 以下為程式的內容 2011.05.29 改為 PDO
// #####################################################################

//必須增加有關設計參數的約束
$horsepower = $_POST["horsepower"];
If ($horsepower <= 0)
$result="抱歉!馬達的功率不能為零或負數!";
$rpm = $_POST["rpm"];
If ($rpm <= 0)
$result="抱歉!馬達的轉速不能為零或負數!";
$ratio = $_POST["ratio"];
If ($ratio <= 0)
$result="抱歉!齒輪的減速比不能為零或負數!";
$safetyfactor = $_POST["safetyfactor"];
If ($safetyfactor <= 0)
$result="抱歉!設計上的安全係數不能為零或負數!";
$toothtype = $_POST["toothtype"];
$material = $_POST["material"];
$npinion = $_POST["npinion"];
If ($npinion <= 0)
$result="抱歉!小齒輪的齒數不能為零或負數!";

//透過toothtype轉回真正的齒形描述
If ($toothtype == "type1")
{
$pressureangle = "20";
}
elseIf ($toothtype == "type2")
{
$pressureangle = "20";
}
elseIf ($toothtype == "type3")
{
$pressureangle = "25";
}
else
{
$pressureangle = "25";
}

//最小齒數取決於壓力角
If ($pressureangle == "20")
$minnpinion = 18;
elseIf ($pressureangle == "25")
$minnpinion = 12;
else
$minnpinion = 12;

//直接設最小齒數
If ($npinion <= $minnpinion)
$npinion = $minnpinion;

//大於400的齒數則視為齒條(Rack)
If ($npinion >= 400)
$npinion = 400;

//由 material之序號查steel表以得材料之降伏強度S單位為kpsi因此查得的值要成乘上1000
include("db_mdesign.inc.php");

    //if ($dbc->Connect($connstr)==NULL)
    if ($dbc==NULL)
    {
    $output="<br>抱歉!資料庫無法連線!<br>";
	include("db.close.php"); 
	exit();   
    }

//$dbc->SetFetchMode(2);

$sql="select unsno,treatment,yield_str ,brinell from steel where serialno=".$material;

//$rs=$dbc->Execute($sql);
$rs1=$dbc->query($sql);
$rs = $rs1->fetchAll(PDO::FETCH_ASSOC);
$total_rows=count($rs);

$unsnumber = $rs[0]["unsno"];
$process = $rs[0]["treatment"];
$strengthstress = $rs[0]["yield_str"] * 1000;
$brinellhardness = $rs[0]["brinell"];
$result="";
$result.="您所選的設計要求為:<br>";
$result.="傳遞功率:".$horsepower."<br>";
$result.="轉速:".$rpm."<br>";
$result.="減速比:".$ratio."<br>";
$result.="齒形:".$toothtype."<br>";
$result.="壓力角:".$pressureangle."<br>";
$result.="小齒數:".$npinion."<br>";
$result.="所選的材料為:".$material."<br>";
$result.="所選的材料降服強度為:".$strengthstress."<br>";
//由小齒輪的齒數與齒形類別,查詢lewis form factor
$sql="select ".$toothtype." from lewis where gearno=".$npinion;
//$rs=$dbc->Execute($sql);
$rs1=$dbc->query($sql);
$rs = $rs1->fetchAll(PDO::FETCH_ASSOC);
$total_rows=count($rs);
//if($rs->EOF)
if($total_rows == 0)
{
	$result.="必須使用內插法求值<br>";
	// 找出比目標齒數大的其中的最小的,就是最鄰近的大值
	$sql="select ".$toothtype.",gearno from lewis where gearno >".$npinion;
	//$rs=$dbc->Execute($sql);
       $rs1=$dbc->query($sql);
       $rs = $rs1->fetchAll(PDO::FETCH_ASSOC);
       $total_rows=count($rs);
	//if($rs->EOF)
       if($total_rows == 0)
	{
		//假如資料庫中沒有比目標齒數大的值,就將目標齒數設為資料庫中的最大值來加以對應
		//事實上,之前已經將最大的小齒輪齒數設為400,因此應該不會有進入這裡的機會
		$larger_toothnumber=400;
		$result.="最鄰近的大齒數為:".$larger_toothnumber."<br>";
	}
	else
	{
	//$rs->MoveFirst();
	$larger_toothnumber=$rs[0]["gearno"];
	$larger_formfactor=$rs[0][$toothtype];
	$result.="最鄰近的大齒數為:".$larger_toothnumber."<br>";
	$result.="最鄰近的大對應的formfactor為:".$larger_formfactor."<br>";
	}

	// 找出比目標齒數小的其中的最大的,就是最鄰近的小值
	$sql="select ".$toothtype.",gearno from lewis where gearno <".$npinion." order by gearno DESC";
	//$rs=$dbc->Execute($sql);
       $rs1=$dbc->query($sql);
       $rs = $rs1->fetchAll(PDO::FETCH_ASSOC);
       $total_rows=count($rs);
	//if($rs->EOF)
       if($total_rows == 0)
	{
		//假如資料庫中沒有比目標齒數小的值,就將目標齒數設為資料庫中的最小值來加以對應
		$smaller_toothnumber=12;
		$result.="最鄰近的小齒數為:".$smaller_toothnumber."<br>";
	}
	else
	{
	//$rs->MoveFirst();
	$smaller_toothnumber=$rs[0]["gearno"];
	$smaller_formfactor=$rs[0][$toothtype];
	$result.="最鄰近的較小齒數為:".$smaller_toothnumber."<br>";
	$result.="最鄰近的較小齒數對應的formfactor為:".$smaller_formfactor."<br>";
	}
	//進行內插運算,以便求出對應的小齒輪formfactor查表值
	$formfactor = $larger_formfactor + ($npinion - $larger_toothnumber) * ($larger_formfactor - $smaller_formfactor) / ($larger_toothnumber - $smaller_toothnumber);
	$result.="內插運算後的formfactor為:".$formfactor."<br>";
	
}
else
{
    $formfactor=$rs[0][$toothtype];
}
$result.="formfactor為:".$formfactor."<br>";
//開始進行設計運算

$ngear = $npinion * $ratio;

//重要的最佳化設計---儘量用整數的diametralpitch
//先嘗試用整數算若diametralpitch找到100仍無所獲則改用0.25作為增量再不行則宣告fail
	$counter=0;
        $i = 0.1;
        $facewidth = 0;
        $circularpitch = 0;
        While ($facewidth <= 3 * $circularpitch Or $facewidth >= 5 * $circularpitch)
	{
        $diametralpitch = $i;
        $circularpitch = 3.14159 / $diametralpitch;
        $pitchdiameter = $npinion / $diametralpitch;
        $pitchlinevelocity = 3.14159 * $pitchdiameter * $rpm / 12;
        $transmittedload = 33000 * $horsepower / $pitchlinevelocity;
        $velocityfactor = 1200 / (1200 + $pitchlinevelocity);
        //formfactor is Lewis form factor;
        //formfactor need to get from table 13-3 and determined ty teeth number and type of tooth
        //formfactor = 0.293
        //90 is the value get from table corresponding to material type
        $facewidth = $transmittedload * $diametralpitch * $safetyfactor / $velocityfactor / $formfactor / $strengthstress;

		if($counter>5000)
	{
		$result.="超過5000次的設計運算,仍無法找到答案!<br>";
		$result.="可能所選用的傳遞功率過大,或無足夠強度的材料可以使用!<br>";
		//離開while迴圈
		break;
	}
	
	$i+=0.1;
	$counter++;
	}
	if($counter<5000)
	{
	$result.="進行".$counter."次重複運算後,得到合用的facewidth值為:".$facewidth."<br>";
	}

include("db.close.php"); 

echo $result;

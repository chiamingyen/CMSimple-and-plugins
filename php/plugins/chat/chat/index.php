<?php
session_start();
$_SESSION['account']=$_POST['account'];
$_SESSION['password']=$_POST['password'];
exit();
if ($_SESSION['account']!="" || $_SESSION['password']!="")
{
	if (@ldapcheck($_SESSION['account'],$_SESSION['password']))
	{
	 // get the pass session
	 $_SESSION["pass"]="on";
	 // $_SESSION["account"] 前面已經取自表單
	 //$_SESSION["account"]=$rs->fields["username"];

require_once dirname(__FILE__)."/src/phpfreechat.class.php";
$params = array();
$params["title"] = "Quick chat";
// remove the nick parameter will force the guest to input their name
//$params["nick"] = "guest".rand(1,1000);  // setup the intitial nickname
// Yen if we enforce ldap login here maybe we will link both together...
// to store the messages and nickname to the designated path
//$params["data_private_path"] = "/dev/shm/mychat";

$params["nick"] = $_SESSION["account"];
$params["frozen_nick"] = true;

//$params['firstisadmin'] = true;
//$params["isadmin"] = true; // makes everybody admin: do not use it on production servers ;)
$params["serverid"] = md5(__FILE__); // calculate a unique id for this chat
$params["debug"] = false;
$chat = new phpFreeChat( $params );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
 <head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <title>phpFreeChat- Sources Index</title>
  <link rel="stylesheet" title="classic" type="text/css" href="style/generic.css" />
  <link rel="stylesheet" title="classic" type="text/css" href="style/header.css" />
  <link rel="stylesheet" title="classic" type="text/css" href="style/footer.css" />
  <link rel="stylesheet" title="classic" type="text/css" href="style/menu.css" />
  <link rel="stylesheet" title="classic" type="text/css" href="style/content.css" />  
 </head>
 <body>

<div class="header">
      <img alt="phpFreeChat" src="style/logo.gif" class="logo2" />
</div>

<div class="menu">
      <ul>
        <li class="sub title">General</li>
        <li>
          <ul class="sub">
            <li class="item">
              <a href="demo/">Demos</a>
            </li>
            <?php if (file_exists(dirname(__FILE__)."/checkmd5.php")) { ?>
            <li>
              <a href="checkmd5.php">Check md5</a>
            </li>
            <?php } ?>
            <!--
            <li class="item">
              <a href="admin/">Administration</a>
            </li>
            -->
          </ul>
        </li>
        <li class="sub title">Documentation</li>
        <li>
          <ul>
            <li class="item">
              <a href="http://www.phpfreechat.net/overview">Overview</a>
            </li>
            <li class="item">
              <a href="http://www.phpfreechat.net/quickstart">Quickstart</a>
            </li>
            <li class="item">
              <a href="http://www.phpfreechat.net/parameters">Parameters list</a>
            </li>
            <li class="item">
              <a href="http://www.phpfreechat.net/faq">FAQ</a>
            </li>
            <li class="item">
              <a href="http://www.phpfreechat.net/advanced-configuration">Advanced configuration</a>
            </li>
            <li class="item">
              <a href="http://www.phpfreechat.net/customize">Customize</a>
            </li>
          </ul>
        </li>
      </ul>
      <p class="partner">
        <a href="http://www.phpfreechat.net"><img alt="phpfreechat.net" src="style/logo_88x31.gif" /></a><br/>
      </p>
</div>

<div class="content">
  <?php $chat->printChat(); ?>
  <?php if (isset($params["isadmin"]) && $params["isadmin"]) { ?>
    <p style="color:red;font-weight:bold;">Warning: because of "isadmin" parameter, everybody is admin. Please modify this script before using it on production servers !</p>
  <?php } ?>
</div>

<div class="footer">
  <span class="partners">phpFreeChat partners:</span>
  <a href="http://www.jeu-gratuit.net">jeux gratuits</a> |
  <a href="http://jeux-flash.jeu-gratuit.net">jeux flash</a> |
  <a href="http://www.pronofun.com">pronofun</a> |
  <a href="http://areno.jeu-gratuit.net">areno</a> |
  <a href="http://www.micropolia.com">micropolia</a> |
  <a href="http://www.zeitoun.net">zeitoun</a> |
  <a href="http://federation.jeu-gratuit.net">federation</a>
</div>
    
</body></html>
<?php
	}
	else
	{
	 // output the warning
	 //echo "can not login!";
	 $_SESSION['account']="";
	 $_SESSION['password']="";
	 echo "帳密不對";
	 header("Location:index.php");
	 }
}
else
{
	echo "<html>";
	echo "<head>";
	echo "<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\">";
	echo "<title>";
	echo "登入表單";
	echo "</title>";
	echo "</head>";
	echo "<body>";
	echo "<form method=post action=\"\">";
	echo "帳號:<input type=text name=account size=15>";
	echo "<br>";
	echo "密碼:<input type=password name=password size=15>";
	echo "<br><br>";
	echo "<input type=submit value=登入>";
	echo "<input type=reset value=reset>";
	echo "</form>";
	echo "</body>";
	echo "</html>";
}

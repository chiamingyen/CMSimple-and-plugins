<?php
	/*
if(isset($clock))
{
 $admin= isset($_POST['admin']) ? $_POST['admin'] : $_GET['admin'];
 $action= isset($_POST['action']) ? $_POST['action'] : $_GET['action'];
 
 $plugin=basename(dirname(__FILE__),"/");
 
 $o.=print_plugin_admin('off');
 if($admin<>'plugin_main'){$o.=plugin_admin_common($action,$admin,$plugin);}
 if($admin=='')$o.="clock plugin ver 1";
}
*/

if(isset($clock))
{
 $admin= isset($_POST['admin']) ? $_POST['admin'] : $_GET['admin'];
 $action= isset($_POST['action']) ? $_POST['action'] : $_GET['action'];
 
 $plugin=basename(dirname(__FILE__),"/");
 

 $o.=print_plugin_admin('on');
 if($admin<>'plugin_main'){$o.=plugin_admin_common($action,$admin,$plugin);}
 if($admin=='')$o.="clock plugin ver 1";

  if ($admin == 'plugin_main')
  {
   $o.="This could be more php code";
  }
}

?>


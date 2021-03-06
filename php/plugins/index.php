<?php

/* utf8-marker = äöüß */
/**
 * Pluginloader of CMSimple_XH 1.5.5
 * Handles loading of pluginloader-2.0 and -2.1 compatible plugins.
 *
 * Created after discussion at CMSimpleforum.com with:
 * Martin, mvwd, Till, johnjdoe, Holger and Gert in May 2009.
 * 
 * @author Developer-Team at CMSimpleforum.com
 * @link http://www.cmsimpleforum.com
 * @version $Id: index.php 291 2012-09-20 16:08:47Z cmb69 $
 * @package pluginloader
 *
 * Modified after a long discussion at CMSimple forum with Martin, mvwd, 
 * Till, johnjdoe, Holger and Gert (Mai 2009)
 * Changes:
 * - The pluginloader contains a new subfolder "page_data" which holds 
 *   synchronised page based data for new plugins like the "meta_tags" 
 *   and "page_params" plugins, written by Martin
 * - OOP-written plugins can load a "required_classes.php" from a new 
 *   plugin subfolder "classes"
 * - Order of loading plugins changed: 1. classes, 2. config, 3. languages, 
 *   4. index, 5. css
 * - Direct access check changed (code mentioned by Martin & mvwd)
 * - For more security, a new constant "PLUGINLOADER_VERSION" included:
 *   Plugins can check the Pluginloader version for compatibility or to 
 *   perform a direct access check with code like below
 *       if(!defined('PLUGINLOADER_VERSION') or !constant('PLUGINLOADER_VERSION'))
 *        die('Direct access not allowed!');
 *       if(PLUGINLOADER_VERSION < 2.101) die('Your Pluginloader is outdated!');
 * - Plugin-Pre-Call: can be used for calling a plugin without CMSimple-Scripting.
 *   Comes with following improvements:
 *   - no limitations in size of page (instead of scripting gets not parsed in 
 *     pages >100kB).
 *   - checks if function is available, prints out an error-message (instead of 
 *     website is dying) or simply nothing.
 *   - use multiple plugins on one page (instead of just use one script per page).
 *   - place your plugin wherever you want in content (instead of plugins will be 
 *     returned by standard on bottom of page).
 *   - you can use the classic CMSimple-Scripting AND pluginPreCall.
 * 
 * Plugin Loader version 2.0 beta 11 for CMSimple v2.6, v2.7 . . . 3.0, 3.2 .....
 * Modified by Till after discussion in the German CMSimple forum with Holger and 
 * Gert (October 2008)
 * 
 * Edited by © Jan Neugebauer (December, 1st 2006) 
 * http://www.internet-setup.de/cmsimple/
 * 
 * Based on original script by © Michael Svarrer 
 * http://cmsimpleplugins.svarrer.dk
 * 
 * Updated 22. September 2006 by Peter Andreas Harteg and released under the CMSimple 
 * license in the CMSimple distrubution with permission by copyright holders.
 * 
 * For licence see notice in /cmsimple/cms.php and http://www.cmsimple.com/?Licence
 * 
 * 
 * Usage: This plugin loader will load compatible plugins avaliable under 
 *        the plugins folder into CMSimple.
 * Step 1 - Create a folder for the plugins (here we call it: "plugins")
 * Step 2 - Upload this pluginloader-package to the newly created folder. 
 * Step 3 - In /cmsimple/config.php set $cf['plugins']['folder']="plugins"; 
 *          (or set plugins_folder to plugins under CMSimple configuration settings)
 * Step 4 - Installation is done and you should be ready to install plugins
 *          Install plugins in an extra subfolder per plugin within the folder "plugins"
 */
/**
 * Catch all neccessary CMSimple-Variables as global
 *
 * @global array $cf CMSimple's Config-Array
 * @global string $hjs CMSimple's head-/javascript-section
 * @global string $sl CMSimple's selected language
 * @global string $sn CMSimple's script-name (relative adress including $_GET)
 */
 
 // 設定 php 所在位置, 因為啟動時, 已經將 php 解譯路徑加入 $path, 因此這裡直接使用 php.exe
 // 以免 php 解譯器改版後, 仍然必須配合設定
 // 延伸程式碼查驗, 需要最後兩個查驗函式
$php_interpreter = "php";
// 一般而言, 在隨身碟使用時, 可以將 $plugin_check 設為  TRUE, 若送到遠端 Linux, 則設為 FALSE
$plugin_check = FALSE;
//
//
global $cf, $hjs, $sl, $sn;

/**
 * Deny direct access of Plugin Loader file
 */
$pluginloader_backtrace = debug_backtrace();
$pluginloader_caller = $pluginloader_backtrace[0]['file'];
if ((!function_exists('content')) OR ($pluginloader_caller !== realpath($pth['file']['cms']))) {
    die('access denied');
}

define('PLUGINLOADER', TRUE);
define('PLUGINLOADER_VERSION', 2.111);

/**
 * Debug-Mode
 * - first: turn off for compatibility with the "original" CMSimple
 * - second: call xh_debugmode(), which checks if debug was activated
 * @author Holger
 * @since V.2.1.09
 */
ini_set('display_errors', 0);
error_reporting(0);
if (function_exists('xh_debugmode')) {
    xh_debugmode();
}

/**
 * @global array $pluginloader_cfg Plugin-Loader's Config-Array
 */
global $pluginloader_cfg;
$pluginloader_cfg = array();
$pluginloader_tx = array();

if (!isset($hjs)) {
    $hjs = '';
}


if (!isset($cf['plugins']['folder']) OR empty($cf['plugins']['folder']) OR !is_dir($cf['plugins']['folder'])) {
    $cf['plugins']['folder'] = 'plugins';
}

$pluginloader_cfg['folder_down'] = $pth['folder']['base'];
$pluginloader_cfg['language'] = $sl;
$pluginloader_cfg['foldername_pluginloader'] = 'pluginloader';
$pluginloader_cfg['folder_pluginloader'] = $pluginloader_cfg['folder_down'] . $cf['plugins']['folder'] . '/' . $pluginloader_cfg['foldername_pluginloader'] . '/';
$pluginloader_cfg['folder_css'] = $pluginloader_cfg['folder_pluginloader'] . 'css/';
$pluginloader_cfg['folder_languages'] = $pluginloader_cfg['folder_pluginloader'] . 'languages/';
$pluginloader_cfg['file_css'] = $pluginloader_cfg['folder_css'] . 'stylesheet.css';
$pluginloader_cfg['file_language'] = $pluginloader_cfg['folder_languages'] . $pluginloader_cfg['language'] . '.php';
$pluginloader_cfg['form_namespace'] = 'PL3bbeec384_';

// include Plugin Loader stylesheet and add it to CMSimple
$hjs .= "\n" . tag('link rel="stylesheet" href="' . $pluginloader_cfg['file_css'] . '" type="text/css"') . "\n";

// if pluginloader language is missing, copy default.php or en.php
if (!file_exists($pluginloader_cfg['file_language'])) {
    if (file_exists($pluginloader_cfg['folder_languages'] . 'default.php')) {
        copy($pluginloader_cfg['folder_languages'] . 'default.php', $pluginloader_cfg['file_language']);
    } elseif (file_exists($pluginloader_cfg['folder_languages'] . 'en.php')) {
        copy($pluginloader_cfg['folder_languages'] . 'en.php', $pluginloader_cfg['file_language']);
    }
} 

// Load default plugin language
if (file_exists($pluginloader_cfg['folder_languages'] . 'default.php')) {
    include $pluginloader_cfg['folder_languages'] . 'default.php';
}

// include Plugin Loader language file
if (is_readable($pluginloader_cfg['file_language'])) {
    include $pluginloader_cfg['file_language'];
} else {
    e('cntopen', 'language', $pluginloader_cfg['file_language']);
}

/**
 * If admin is logged in create a select box with all available plugins.
 */
if ($adm) {
    $i = 0;
    $pluginloader_plugin_selectbox = '';
    $pluginloader_plugin_selectbox .= "\n" . '<b>' . $pluginloader_tx['menu']['available_plugins'] . '</b>';
    $pluginloader_plugin_selectbox .= "\n" . '<form style="display: inline; margin-bottom: 0px;">' . "\n";
    $pluginloader_plugin_selectbox .= "\n" . '<select name="Plugins" onchange="location.href=this.options[this.selectedIndex].value">' . "\n";
    $pluginloader_plugin_selectbox .= '<option value="?&amp;normal">' . $pluginloader_tx['menu']['select_plugin'] . '</option>' . "\n";
    $handle = opendir($pth['folder']['plugins']);
    $found_plugins = false;

    while (FALSE !== ($plugin = readdir($handle))) {
        if ($plugin != '.' && $plugin != '..' && is_dir($pth['folder']['plugins'] . $plugin)) {
            PluginFiles($plugin);
            if (file_exists($pth['file']['plugin_admin'])) {
                $admin_plugins[$i] = $plugin;
                $found_plugins = true;
                $i++;
            }
        }
    }
    natcasesort($admin_plugins);
    foreach ($admin_plugins as $plugin) {
        PluginFiles($plugin);
        $pluginloader_plugin_selectbox .= '<option value="' . $sn . '?&amp;' . $plugin . '&amp;normal"';
        reset($_GET);
        list ($firstgetkey) = each($_GET);
        if ($firstgetkey == $plugin) {
            $pluginloader_plugin_selectbox .= ' selected="selected"';
        }
        $pluginloader_plugin_selectbox .= '>' . ucfirst($plugin) . '</option>' . "\n";
    }
    $pluginloader_plugin_selectbox .= '</select>' . "\n" . '</form>' . "\n";

 //   PluginMenu('ROW', '', '', '');
 //   PluginMenu('DATA', '', '', $pluginloader_plugin_selectbox);

 //   $o .= PluginMenu('SHOW');
    $o .= ' ';
} // if($adm)


/**
 * Bridge to page data
 */
require_once $pth['folder']['plugins'] . $pluginloader_cfg['foldername_pluginloader'] . '/page_data/index.php';

/**
 * Include plugin (and plugin files)
 */
$handle = opendir($pth['folder']['plugins']);
while (FALSE !== ($plugin = readdir($handle))) {
    if ($plugin != "." AND $plugin != ".." AND $plugin != $pluginloader_cfg['foldername_pluginloader'] AND is_dir($pth['folder']['plugins'] . $plugin)) {
        PluginFiles($plugin);

        // Load plugin required_classes
        if (file_exists($pth['file']['plugin_classes'])) {
            include($pth['file']['plugin_classes']);
        }
    } // if($plugin)
} // while (FALSE !== ($plugin = readdir($handle)))*/
rewinddir($handle);

while (FALSE !== ($plugin = readdir($handle))) {


    if ($plugin != "." AND $plugin != ".." AND $plugin != $pluginloader_cfg['foldername_pluginloader'] AND is_dir($pth['folder']['plugins'] . $plugin)) {

        PluginFiles($plugin);

        // Load plugin config
	if (file_exists($pth['folder']['plugins'].$plugin.'/config/defaultconfig.php')) {
	    include($pth['folder']['plugins'].$plugin.'/config/defaultconfig.php');
	}
        if (file_exists($pth['file']['plugin_config'])) {
            include($pth['file']['plugin_config']);
        }
    
    // If plugin language is missing, copy default.php or en.php
    if (!file_exists($pth['file']['plugin_language'])) {
        if (file_exists($pth['folder']['plugins'].$plugin.'/languages/default.php')) {
        copy($pth['folder']['plugins'].$plugin.'/languages/default.php', $pth['file']['plugin_language']);
        } elseif (file_exists($pth['folder']['plugins'].$plugin.'/languages/en.php')) {
        copy($pth['folder']['plugins'].$plugin.'/languages/en.php', $pth['file']['plugin_language']);
        }
    }
    
    // Load default plugin language
    if (file_exists($pth['folder']['plugins'] . $plugin . '/languages/default.php')) {
         include $pth['folder']['plugins'] . $plugin . '/languages/default.php';
    }
        // Load plugin language
        if (file_exists($pth['file']['plugin_language'])) {
            include($pth['file']['plugin_language']);
        }

    if($plugin_check){
        // 程式語法查驗必須避開系統所提供的 plugin 程式
        $system_plugins = array("utf8", "tinymce", "page_params", "pagemanager", "meta_tags", "jquery", "filebrowser", "axuploader", "editor", "sforum");
        if (file_exists($pth['file']['plugin_index'])){
            $plugin_name = preg_split('/\//', $pth['file']['plugin_index']); 
            if(!in_array($plugin_name[2], $system_plugins)){
                // 若 index.php 語法正確, 才導入執行.
                if(php_check_syntax($pth['file']['plugin_index'], true) == "1"){
                    include($pth['file']['plugin_index']);
                }
            }else{
                // 針對系統 plugin 程式, 則直接引入, 不查驗程式碼的語法
                include($pth['file']['plugin_index']);
            }
        }else{
            die($pluginloader_tx['error']['plugin_error'] . $pluginloader_tx['error']['cntopen'] . $pth['file']['plugin_index']);
        }
    }else{

        // 原先沒能事先查驗 plugin 程式語法的程式段, 以上面的程式碼取代, 加入程式碼語法查驗
        // Load plugin index.php or die
        if (file_exists($pth['file']['plugin_index']) AND !include($pth['file']['plugin_index'])) {
            die($pluginloader_tx['error']['plugin_error'] . $pluginloader_tx['error']['cntopen'] . $pth['file']['plugin_index']);
        }
    }

        // Add plugin css to the header of CMSimple/Template
        if (file_exists($pth['file']['plugin_stylesheet'])) {
            $hjs .= tag('link rel="stylesheet" href="' . $pth['file']['plugin_stylesheet'] . '" type="text/css"') . "\n";
        }
    } // if($plugin)
} // while (FALSE !== ($plugin = readdir($handle)))


/**
 * Load admin functions (admin.php, if exists) of plugin
 */
if ($adm) {
    // Load common plugin admin functions or die
    //if(!include($pth['folder']['plugins'].'/admin_common.php')) {
    //	die($pluginloader_tx['error']['plugin_error'].$pluginloader_tx['error']['cntopen'].$pth['folder']['plugins'].'/admin_common.php');
    //}
    if ($found_plugins == true) {
        foreach ($admin_plugins as $plugin) {
            PluginFiles($plugin);
            if (file_exists($pth['file']['plugin_admin'])) {
                include($pth['file']['plugin_admin']);
            }
        }
    }
    // ########## bridge to page data ##########
    $o .= $pd_router->create_tabs($s);
    // #########################################
}

/**
 * Pre-Call Plugins
 */
preCallPlugins();

// Plugin functions
unset($plugin);

/**
 * Function PluginFiles()
 * Set plugin filenames.
 *
 * @param string $plugin Name of the plugin, the filenames 
 * will be set for.
 *
 * @global array $cf CMSimple's Config-Array
 * @global string $pth CMSimple's configured pathes in an array
 * @global string $sl CMSimple's selected language
 * @global array $pluginloader_cfg Plugin-Loader's Config-Array
 */
function PluginFiles($plugin) {

    global $cf, $pth, $sl;
    global $pluginloader_cfg;

    $pth['folder']['plugin'] = $pth['folder']['plugins'] . $plugin . '/';
    $pth['folder']['plugin_classes'] = $pth['folder']['plugins'] . $plugin . '/classes/';
    $pth['folder']['plugin_config'] = $pth['folder']['plugins'] . $plugin . '/config/';
    $pth['folder']['plugin_content'] = $pth['folder']['plugins'] . $plugin . '/content/';
    $pth['folder']['plugin_css'] = $pth['folder']['plugins'] . $plugin . '/css/';
    $pth['folder']['plugin_help'] = $pth['folder']['plugins'] . $plugin . '/help/';
    $pth['folder']['plugin_includes'] = $pth['folder']['plugins'] . $plugin . '/includes/';
    $pth['folder']['plugin_languages'] = $pth['folder']['plugins'] . $plugin . '/languages/';

    $pth['file']['plugin_index'] = $pth['folder']['plugin'] . 'index.php';
    $pth['file']['plugin_admin'] = $pth['folder']['plugin'] . 'admin.php';

    $pth['file']['plugin_language'] = $pth['folder']['plugin_languages'] . strtolower($sl) . '.php';

    $pth['file']['plugin_classes'] = $pth['folder']['plugin_classes'] . 'required_classes.php';
    $pth['file']['plugin_config'] = $pth['folder']['plugin_config'] . 'config.php';
    $pth['file']['plugin_stylesheet'] = $pth['folder']['plugin_css'] . 'stylesheet.css';

    $pth['file']['plugin_help'] = $pth['folder']['plugin_help'] . 'help_' . strtolower($pluginloader_cfg['language']) . '.htm';
    if (!file_exists($pth['file']['plugin_help'])) {
        $pth['file']['plugin_help'] = $pth['folder']['plugin_help'] . 'help_en.htm';
    }
    if (!file_exists($pth['file']['plugin_help']) AND file_exists($pth['folder']['plugin_help'] . 'help.htm')) {
        $pth['file']['plugin_help'] = $pth['folder']['plugin_help'] . 'help.htm';
    }
}

/**
 * Function PluginMenu()
 * Create menu of plugin (add row, add tab), constructed as a table.
 *
 * @param string $add Add a ROW, a TAB or DATA (Userdefineable content). SHOW will return the menu.
 * @param string $link The link, the TAB will lead to.
 * @param string $target Target of the link (with(!) 'target=').
 * @param string $text Description of the TAB.
 * @param array $style Array with style-data for the containing table-cell
 */
function PluginMenu($add='', $link='', $target='', $text='', $style=ARRAY()) {

    $add = strtoupper($add);

    if (!isset($GLOBALS['pluginloader']['plugin_menu'])) {
        $GLOBALS['pluginloader']['plugin_menu'] = '';
    }
    if (!isset($style['row'])) {
        $style['row'] = 'class="edit" style="width: 100%;"';
    }
    if (!isset($style['tab'])) {
        $style['tab'] = '';
    }
    if (!isset($style['link'])) {
        $style['link'] = '';
    }
    if (!isset($style['data'])) {
        $style['data'] = '';
    }

    $menu_row = '<table {{STYLE_ROW}} cellpadding="1" cellspacing="0">' . "\n" . '<tr>' . "\n" . '{{TAB}}</tr>' . "\n" . '</table>' . "\n" . "\n";
    $menu_tab = '<td {{STYLE_TAB}}><a{{STYLE_LINK}} href="{{LINK}}" {{TARGET}}>{{TEXT}}</a></td>' . "\n";
    $menu_tab_data = '<td {{STYLE_DATA}}>{{TEXT}}</td>' . "\n";

    // Add new row for menu of plugin (or Plugin Loader)
    if ($add == 'ROW') {
        $new_menu_row = $menu_row;
        $new_menu_row = str_replace('{{STYLE_ROW}}', $style['row'], $new_menu_row);
        $GLOBALS['pluginloader']['plugin_menu'] .= $new_menu_row;
    }

    // Add a new tab to the menu row
    if ($add == 'TAB') {
        $new_menu_tab = $menu_tab;
        $new_menu_tab = str_replace('{{STYLE_TAB}}', $style['tab'], $new_menu_tab);
        $new_menu_tab = str_replace('{{STYLE_LINK}}', $style['link'], $new_menu_tab);
        $new_menu_tab = str_replace('{{LINK}}', $link, $new_menu_tab);
        $new_menu_tab = str_replace('{{TARGET}}', $target, $new_menu_tab);
        $new_menu_tab = str_replace('{{TEXT}}', $text, $new_menu_tab);

        // Add tab to row
        $GLOBALS['pluginloader']['plugin_menu'] = str_replace('{{TAB}}', $new_menu_tab . '{{TAB}}', $GLOBALS['pluginloader']['plugin_menu']);
    }

    // Add a new tab to the menu row
    // Here: user defineable data
    if ($add == 'DATA') {
        $new_menu_tab_data = $menu_tab_data;
        $new_menu_tab_data = str_replace('{{STYLE_DATA}}', $style['data'], $new_menu_tab_data);
        $new_menu_tab_data = str_replace('{{TEXT}}', $text, $new_menu_tab_data);

        // Add tab to row
        $GLOBALS['pluginloader']['plugin_menu'] = str_replace('{{TAB}}', $new_menu_tab_data . '{{TAB}}', $GLOBALS['pluginloader']['plugin_menu']);
    }

    // Show complete menu
    if ($add == 'SHOW') {
        $GLOBALS['pluginloader']['plugin_menu'] = str_replace('{{TAB}}', '', $GLOBALS['pluginloader']['plugin_menu']);
        $menu = $GLOBALS['pluginloader']['plugin_menu'];
        $GLOBALS['pluginloader']['plugin_menu'] = '';
        return $menu;
    }
}

/**
 * Function PluginReadFile()
 * Read content from a given file.
 *
 * @param string $file Name of the file to read.
 *
 * @return array If succesfull, return an array with data and success-message.
 */
function PluginReadFile($file='') {
    global $pluginloader_tx;

    $is_read = ARRAY();
    $is_read['success'] = FALSE;
    $is_read['msg'] = '';
    $is_read['content'] = '';

    if (!file_exists($file)) {
        $is_read['msg'] = $pluginloader_tx['error']['cntopen'] . $file;
    } else {
        if (!is_readable($file)) {
            $is_read['msg'] = $pluginloader_tx['error']['notreadable'] . $file;
        } else {
            if ($fh = fopen($file, "rb")) {
                $is_read['content'] = fread($fh, filesize($file));
                fclose($fh);
                $is_read['content'] = str_replace("\r\n", "\n", $is_read['content']);
                $is_read['content'] = str_replace("\r", "\n", $is_read['content']);
                $is_read['msg'] = $pluginloader_tx['success']['saved'] . $file;
                $is_read['success'] = TRUE;
            }
        }
    }
    return $is_read;
}

/**
 * Function PluginWriteFile()
 * Write content to a given file.
 *
 * @global array $pluginloader_tx Plugin-loader's text-array
 *
 * @param string $file Name of the file to write.
 * @param string $content Content, that will be written in the file.
 * @param string $exists If set to TRUE, the function returns error-message if file is not available.
 * @param string $append If set to TRUE, data will be appended to existing data in the file.
 *
 * @return array If succesfull, return an array with success-message.
 */
function PluginWriteFile($file='', $content='', $exists=FALSE, $append=FALSE) {
    global $pluginloader_tx;

    if ($append == TRUE) {
        $write_mode = 'a+b';
    } else {
        $write_mode = 'w+b';
    }

    $is_written = ARRAY();
    $is_written['success'] = FALSE;
    $is_written['msg'] = '';

    $do_write = TRUE;

    if (!file_exists($file) AND $exists == TRUE) {
        $do_write = FALSE;
        $is_written['msg'] = $pluginloader_tx['error']['cntopen'] . $file;
    }

    if (file_exists($file) AND !is_writeable($file)) {
        $do_write = FALSE;
        $is_written['msg'] = '<div class="pluginerror">' . $pluginloader_tx['error']['cntwriteto'] . $file . '</div>';
    }

    if ($do_write == TRUE) {
        if ($fh = fopen($file, $write_mode)) {
            fwrite($fh, $content);
            fclose($fh);
            $is_written['msg'] = $pluginloader_tx['success']['saved'] . $file;
            $is_written['success'] = TRUE;
        }
    }

    return $is_written;
}

/**
 * Function PluginPrepareConfigData()
 * Prepare config data for writing to file.
 *
 * @param string $var_name Name of the variable ($cf => $var_name = cf)
 * @param string $data Array of data, that will be converted to text for saving in (php-)textfile.
 * @param string $plugin If filled, the created variable will be extended by a sub-array with this index.
 *
 * @return string Returns the prepared data for saving.
 */
function PluginPrepareConfigData($var_name='', $data=ARRAY(), $plugin='') {
    $save_data = "<?php\n\n";
    foreach ($data as $key => $value) {
        $save_data .= "\t" . '$' . $var_name;
        if (!empty($plugin)) {
            $save_data .= '[\'' . $plugin . '\']';
        }
        $save_data .= '[\'' . $key . '\']="' . trim(str_replace("\\'", "'", ((get_magic_quotes_gpc() === 1) ? $value : addslashes($value)))) . '";' . "\n";
    }
    $save_data .= "\n?>";
    return $save_data;
}

/**
 * Function PluginPrepareTextData()
 * Prepare text data for writing to file.
 *
 * @param string $data The text-data, that will be prepared for saving to file.
 *
 * @return string Returns the prepared data for saving.
 */
function PluginPrepareTextData($data) {
    trigger_error('Function PluginPrepareTextData() is deprecated', E_USER_DEPRECATED);
    
    return (get_magic_quotes_gpc() === 1) ? stripslashes($data) : $data;
}


/**
 * Function PluginSaveForm()
 * Creates form for config data.
 *
 * If $hint['mode_donotshowvarnames'] == TRUE, variable 
 * indexes are not shown, but text information.
 * (e.g. for index 'my_name' -> $plugin_tx['example_plugin']['cf_my_name'])
 *
 * @global array $pluginloader_tx Plugin-loader's text-array
 *
 * @param array $form Array, that contains data how the form will be created.
 * @param array $style Array, that contains style-data for the div and the form.
 * @param array $data Data, that will be shown in the form (in a textarea or in input-fields).
 * @param array $hint Array with hints in popups for the config-variables.
 *
 * @return string Returns the created form.
 */
function PluginSaveForm($form, $style=ARRAY(), $data=ARRAY(), $hint=ARRAY()) {
    global $pluginloader_tx;
    $saveform = '';

    if ($form['type'] != 'TEXT' AND $form['type'] != 'CONFIG') {
        trigger_error('invalid argument', E_USER_WARNING);
    } elseif ($form['type'] == 'CONFIG' AND (!is_array($data) OR count($data) == 0)) {
        trigger_error('empty', E_USER_WARNING);
    } else {

        $form_keys = ARRAY('action', 'caption', 'errormsg', 'method', 'value_action', 'value_admin', 'value_submit');
        $style_keys = ARRAY('div', 'divcaption', 'form', 'submit', 'table', 'tdcaption', 'tdconfig', 'tdhint', 'textarea', 'input', 'inputmax');
        foreach ($form_keys AS $key) {
            if (!isset($form[$key])) {
                $form[$key] = '';
            }
        }
        foreach ($style_keys AS $key) {
            if (!isset($style[$key])) {
                $style[$key] = '';
            }
        }

        $saveform .= $form['errormsg'];
        $saveform .= '<div ' . $style['div'] . '>' . "\n";

        $saveform .= '<form ' . $style['form'] . ' action="' . $form['action'] . '" method="' . $form['method'] . '">' . "\n";
        if (!empty($form['caption'])) {
            $saveform .= '<div ' . $style['divcaption'] . '>' . "\n" . $form['caption'] . "\n" . '</div>' . "\n";
        }

        if ($form['type'] == 'TEXT') {
            $saveform .= '<textarea ' . $style['textarea'] . ' name="' . $form['textarea_name'] . '">' . $data . '</textarea>';
        }

        if ($form['type'] == 'CONFIG') {
            $saveform .= '<table ' . $style['table'] . ' cellspacing="0" cellpadding="0">' . "\n";
            $last_cap = '';
            ksort($data);
            foreach ($data as $key => $value) {
                global $pluginloader_cfg;
                $var_name = '';
                $val_cap = explode('_', $key);

                if (!isset($hint['mode_donotshowvarnames']) OR $hint['mode_donotshowvarnames'] == FALSE) {
                    if ($val_cap[0] != $last_cap) {
                        $last_cap = $val_cap[0];
                        $saveform .= '<tr>' . "\n" . '<td colspan="2" ' . $style['tdcaption'] . '>' . $last_cap . '</td>' . "\n" . '</tr>' . "\n";
                    }
                }
                if (isset($hint['mode_donotshowvarnames']) AND $hint['mode_donotshowvarnames'] == TRUE AND isset($hint['cf_' . $key]) AND !empty($hint['cf_' . $key])) {
                    $var_name = $hint['cf_' . $key];
                } else {
                    $var_name = (isset($hint['cf_' . $key]) AND !empty($hint['cf_' . $key])) ? '<a href="#" onclick="return false" class="pl_tooltip">' . tag('img src = "' . $pluginloader_cfg['folder_pluginloader'] . '/css/help_icon.png" alt="" class="helpicon"') . '<span>' . $hint['cf_' . $key] . '</span></a> ' . str_replace("_", " ", $key) . ': ' : str_replace("_", " ", $key) . ':';
                }
                $saveform .= '<tr>' . "\n" . '<td ' . $style['tdconfig'] . '>' . $var_name . '</td>' . "\n" . '<td>';
                $style_textarea = $style['input'];
                if (utf8_strlen($value) > 50) {
                    $style_textarea = $style['inputmax'];
                }
                $saveform .= '<textarea ' . $style_textarea . ' name="' . $pluginloader_cfg['form_namespace'] . $key . '" rows="1" cols="40">' . htmlspecialchars($value, ENT_NOQUOTES, 'UTF-8') . '</textarea>';
                $saveform .= '</td>' . "\n" . '</tr>' . "\n";
            }
            $saveform .= '</table>' . "\n" . "\n";
        } // if($form['type'] == 'CONFIG')

        $saveform .= tag('input type="hidden" name="admin" value="' . $form['value_admin'] . '"') . "\n";
        $saveform .= tag('input type="hidden" name="action" value="' . $form['value_action'] . '"') . "\n" . tag('br') . "\n" . tag('input type="submit" ' . $style['submit'] . ' name="plugin_submit" value="' . $form['value_submit'] . '"') . "\n";
        $saveform .= '</form>' . "\n";
    }
    $saveform .= "\n" . '</div>' . "\n";

    return $saveform;
}

/**
 * Function print_plugin_admin()
 * Create plugin menu tabs.
 *
 * @param string $main Set to OFF, if menu is not needed.
 *
 * @return string Returns the created plugin-menu.
 */
function print_plugin_admin($main) {

    global $sn, $plugin, $pth, $sl, $cf;
    global $pluginloader_tx, $plugin_tx;

    initvar('plugin_text');
    initvar('action');
    initvar('admin');

    $t = '';
    $css = '';
    $config = '';
    $language = '';
    $help = '';

    $main = strtoupper($main);

    PluginFiles($plugin);

    if (file_exists($pth['file']['plugin_stylesheet'])) {
        $css = 'ON';
    }
    if (file_exists($pth['file']['plugin_config'])) {
        //include($pth['file']['plugin_config']);
        $config = 'ON';
    }
    if (file_exists($pth['file']['plugin_language'])) {
        //include($pth['file']['plugin_language']);
        $language = 'ON';
    }
    if (file_exists($pth['file']['plugin_help'])) {
        $help = 'ON';
    }

    /**
     *  Use preset texts for the menu, if the plugin itself did not define text for the menu
     */
    if (isset($plugin_tx[$plugin]['menu_main']) AND !empty($plugin_tx[$plugin]['menu_main'])) {
        $pluginloader_tx['menu']['tab_main'] = $plugin_tx[$plugin]['menu_main'];
    }
    if (isset($plugin_tx[$plugin]['menu_css']) AND !empty($plugin_tx[$plugin]['menu_css'])) {
        $pluginloader_tx['menu']['tab_css'] = $plugin_tx[$plugin]['menu_css'];
    }
    if (isset($plugin_tx[$plugin]['menu_config']) AND !empty($plugin_tx[$plugin]['menu_config'])) {
        $pluginloader_tx['menu']['tab_config'] = $plugin_tx[$plugin]['menu_config'];
    }
    if (isset($plugin_tx[$plugin]['menu_language']) AND !empty($plugin_tx[$plugin]['menu_language'])) {
        $pluginloader_tx['menu']['tab_language'] = $plugin_tx[$plugin]['menu_language'];
    }
    if (isset($plugin_tx[$plugin]['menu_help']) AND !empty($plugin_tx[$plugin]['menu_help'])) {
        $pluginloader_tx['menu']['tab_help'] = $plugin_tx[$plugin]['menu_help'];
    }

    $plugin_menu_style = ARRAY();

    PluginMenu('ROW', '', '', '', $plugin_menu_style);

    if ($main == 'ON') {
        PluginMenu('TAB', $sn . '?&amp;' . $plugin . '&amp;admin=plugin_main&amp;action=plugin_text', '', $pluginloader_tx['menu']['tab_main'], $plugin_menu_style);
    }

    if($css == 'ON') {
        PluginMenu('TAB', $sn.'?&amp;'.$plugin.'&amp;admin=plugin_stylesheet&amp;action=plugin_text', '', $pluginloader_tx['menu']['tab_css'], $plugin_menu_style);	
    }

    if($config == 'ON') { 
        PluginMenu('TAB', $sn.'?&amp;'.$plugin.'&amp;admin=plugin_config&amp;action=plugin_edit', '', $pluginloader_tx['menu']['tab_config'], ''); 
    }

    if ($language == 'ON') {
        PluginMenu('TAB', $sn . '?&amp;' . $plugin . '&amp;admin=plugin_language&amp;action=plugin_edit', '', $pluginloader_tx['menu']['tab_language'], '');
    }
    if ($help == 'ON') {
        PluginMenu('TAB', $pth['file']['plugin_help'], 'target="_blank"', $pluginloader_tx['menu']['tab_help'], '');
    }

    $t .= PluginMenu('SHOW');

    return $t;
}

/**
 * Function plugin_admin_common()
 *
 * Handles reading and writing of plugin files
 * (e.g. en.php, config.php, stylesheet.css)
 *
 * @param bool $action Possible values: 'empty' = Error: empty value detected
 * @param array $admin Array, that contains debug_backtrace()-data
 * @param bool $plugin The called varname
 * @param bool $hint Array with hints for the variables (will be shown as popup)
 *
 * @global string $sn CMSimple's script-name (relative adress including $_GET)
 * @global string $action Ordered action
 * @global string $admin Ordered admin-action
 * @global string $plugin Name of the plugin, that called this function
 * @global string $pth CMSimple's configured pathes in an array
 * @global string $tx CMSimple's text-array
 * @global string $pluginloader_tx Plugin-Loader's text-array
 * @global string $plugin_tx Plugin's text-array
 * @global string $plugin_cf Plugin's config-array
 *
 * @return string Returns the created form or the result of saving the data
 */
function plugin_admin_common($action, $admin, $plugin, $hint=ARRAY()) {

    global $sn, $action, $admin, $plugin, $pth, $tx;
    global $pluginloader_tx, $plugin_tx, $plugin_cf;

    $data = '';
    $t = '';
    $error_msg = ($admin == '' OR is_writeable($pth['file'][$admin])) ? '' : '<div class="pluginerror">' . "\n" . '<b>' . $tx['error']['notwritable'] . ':</b>' . "\n" . '</div>' . "\n" . '<ul>' . "\n" . '<li>' . $pth['file'][$admin] . '</li>' . "\n" . '</ul>' . "\n";

    if ($admin == 'plugin_config') {
        $var_name = 'plugin_cf';
        $data = $plugin_cf[$plugin];
    }
    if ($admin == 'plugin_language') {
        $var_name = 'plugin_tx';
        $data = $plugin_tx[$plugin];
    }
    if ($admin == 'plugin_stylesheet') {
        $var_name = 'plugin_text';
    }
    if ($admin == 'plugin_main') {
        $var_name = 'plugin_text';
    }

    if ($action == 'plugin_text' OR $action == 'plugin_edit') {
        if (isset($plugin_tx[$plugin])) {
            $hint = array_merge($hint, $plugin_tx[$plugin]);
        }
        $form = ARRAY();
        $style = ARRAY();

        $style['form'] = 'class="plugineditform"';
        $style['divcaption'] = 'class="plugineditcaption"';
        $style['submit'] = 'class="submit"';

        $form['action'] = $sn . '?&amp;' . $plugin;
        $form['method'] = 'POST';
        $form['value_admin'] = $admin;
        $form['value_submit'] = utf8_ucfirst($tx['action']['save']);
        $form['caption'] = ucfirst(str_replace("_", " ", $plugin));
        $form['errormsg'] = $error_msg;

        if ($action == 'plugin_text') {
            $file_data = PluginReadFile($pth['file'][$admin]);
            $data = $file_data['content'];
            $form['type'] = 'TEXT';
            $form['value_action'] = 'plugin_textsave';
            $form['textarea_name'] = 'plugin_text';
            $style['div'] = 'class="plugintext"';
            $style['textarea'] = 'class="plugintextarea"';
        }
        if ($action == 'plugin_edit') {
            $form['type'] = 'CONFIG';
            $form['value_action'] = 'plugin_save';
            $style['div'] = 'class="pluginedit"';
            $style['table'] = 'class="pluginedittable"';
            $style['tdcaption'] = 'class="plugincfcap"';
            $style['tdhint'] = 'class="plugincfhint"';
            $style['tdconfig'] = 'class="plugincf"';
            $style['input'] = 'class="plugininput"';
            $style['inputmax'] = 'class="plugininputmax"';
        }
        $t .= PluginSaveForm($form, $style, $data, $hint);
    }

    if ($action == 'plugin_save' OR $action == 'plugin_textsave') {
        if ($action == 'plugin_save') {
            $config_data = ARRAY();
            foreach ($data as $key => $value) {
                global $pluginloader_cfg;
                $config_data[$key] = $_POST[$pluginloader_cfg['form_namespace'] . $key];
            }
            $save_data = PluginPrepareConfigData($var_name, $config_data, $plugin);
        }
        if ($action == 'plugin_textsave') {
            $text_data = $_POST[$var_name];
            $save_data = stsl($text_data);
        }
        $is_saved = PluginWriteFile($pth['file'][$admin], $save_data);
        $t .= tag('br') . '<b>' . $is_saved['msg'] . '</b>' . tag('br');
    }
    return $t;
}

/**
 * Function PluginDebugger()
 *
 * @param bool $error Possible values: 'empty' = Error: empty value detected
 * @param array $caller Array, that contains debug_backtrace()-data
 * @param bool $varname The called varname
 * @param bool $value The value of the var
 *
 * @global array $pluginloader_tx Plugin-loader's text-array
 *
 * @return string Returns result of debugging as text
 */
function PluginDebugger($error=FALSE, $caller=FALSE, $varname=FALSE, $value=FALSE) {
    global $pluginloader_tx;
    
    trigger_error('Function PluginDebugger() is deprecated', E_USER_DEPRECATED);
    
    $debug = '';
    $debug .= $pluginloader_tx['error']['plugin_error'] . '';
    switch ($error) {
        case 'empty': $debug .= 'empty/no data (' . $varname . ')';
            break;
        default: $debug .= 'undefined error (' . $varname . ')';
    }
    $debug .= tag('br') . 'in' . tag('br');
    foreach ($caller AS $call) {
        $debug .= 'File: ' . $call['file'] . ' - ' . $call['function'] . ' - Line: ' . $call['line'] . tag('br');
    }
    return $debug;
}

/**
 * Function preCallPlugins() => Pre-Call of Plugins.
 *
 * All Plugins which are called through a function-call
 * can use this. At the moment it is'nt possible to do
 * this with class-based plugins. They need to be called
 * through standard-CMSimple-Scripting.
 *
 * Call a plugin: place this in your code (example):
 * {{{PLUGIN:pluginfunction('parameters');}}}
 *
 * Call a built-in function (at the moment only one for
 * demonstration):
 * {{{HOME}}} or: {{{HOME:name_of_Link}}}
 * This creates a link to the first page of your CMSimple-
 * Installation.
 * 
 * @param pageIndex - added for search
 * @global bool $edit TRUE if edit-mode is active
 * @global array $c Array containing all contents of all CMSimple-pages
 * @global integer $s Pagenumber of active page
 * @global array $u Array containing URLs to all CMSimple-pages
 * 
 * @author mvwd
 * @since V.2.1.02
 */
function preCallPlugins($pageIndex = -1) {
    global $edit, $c, $s, $u;

    if (!$edit) {
        if ((int) $pageIndex > - 1 && (int) $pageIndex < count($u)) {
            $as = $pageIndex;
        } else {
            $as = $s < 0 ? 0 : $s;
        }
	$c[$as] = evaluate_plugincall($c[$as]);
    }
}

// 用來查驗 plugin 程式碼是否可以正確執行
function php_check_syntax( $php, $isFile=false )
{
    // 由函式外導入 $php_interpreter 變數
    global $php_interpreter;
    # Get the string tokens
    $tokens = token_get_all( '<?php '.trim( $php  ));
    
    # Drop our manually entered opening tag
    array_shift( $tokens );
    token_fix( $tokens );

    # Check to see how we need to proceed
    # prepare the string for parsing
    if( isset( $tokens[0][0] ) && $tokens[0][0] === T_OPEN_TAG )
       $evalStr = $php;
    else
        $evalStr = "<?php\n{$php}?>";

    if( $isFile OR ( $tf = tempnam( NULL, 'parse-' ) AND file_put_contents( $tf, $php ) !== FALSE ) AND $tf = $php )
    {
        # Prevent output
        ob_start();
        system($php_interpreter.' -c "'.dirname(__FILE__).'/php.ini" -l < '.$php, $ret );
        $output = ob_get_clean();

        if( $ret !== 0 )
        {
            # Parse error to report?
            if( (bool)preg_match( '/Parse error:\s*syntax error,(.+?)\s+in\s+.+?\s*line\s+(\d+)/', $output, $match ) )
            {
                return array(
                    'line'    =>    (int)$match[2],
                    'msg'    =>    $match[1]
                );
            }
        }
        return true;
    }
    return false;
}

// 用來查驗 plugin 程式碼是否可以正確執行
//fixes related bugs: 29761, 34782 => token_get_all returns <?php NOT as T_OPEN_TAG
function token_fix( &$tokens ) {
    if (!is_array($tokens) || (count($tokens)<2)) {
        return;
    }
   //return of no fixing needed
    if (is_array($tokens[0]) && (($tokens[0][0]==T_OPEN_TAG) || ($tokens[0][0]==T_OPEN_TAG_WITH_ECHO)) ) {
        return;
    }
    //continue
    $p1 = (is_array($tokens[0])?$tokens[0][1]:$tokens[0]);
    $p2 = (is_array($tokens[1])?$tokens[1][1]:$tokens[1]);
    $p3 = '';

    if (($p1.$p2 == '<?') || ($p1.$p2 == '<%')) {
        $type = ($p2=='?')?T_OPEN_TAG:T_OPEN_TAG_WITH_ECHO;
        $del = 2;
        //update token type for 3rd part?
        if (count($tokens)>2) {
            $p3 = is_array($tokens[2])?$tokens[2][1]:$tokens[2];
            $del = (($p3=='php') || ($p3=='='))?3:2;
            $type = ($p3=='=')?T_OPEN_TAG_WITH_ECHO:$type;
        }
        //rebuild erroneous token
        $temp = array($type, $p1.$p2.$p3);
        if (version_compare(phpversion(), '5.2.2', '<' )===false)
            $temp[] = isset($tokens[0][2])?$tokens[0][2]:'unknown';

        //rebuild
        $tokens[1] = '';
        if ($del==3) $tokens[2]='';
        $tokens[0] = $temp;
    }
    return;
}
?>

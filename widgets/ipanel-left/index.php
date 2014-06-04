<?php
/**
* @project ApPHP Hotel Site
* @copyright (c) 2010 - 2013 ApPHP
* @author ApPHP <info@apphp.com>
* @license http://www.gnu.org/licenses/
*/

$host = isset($_GET['host']) ? urldecode(base64_decode($_GET['host'])) : '';
$key = isset($_GET['key']) ? base64_decode($_GET['key']) : '';
$hids = isset($_GET['hids']) ? str_ireplace('-', ',', base64_decode($_GET['hids'])) : '';

$basedir = '../../';

require_once($basedir.'include/base.inc.php');
if($key != INSTALLATION_KEY) exit(0);

require_once($basedir.'include/shared.inc.php');
require_once($basedir.'include/settings.inc.php');
require_once($basedir.'include/functions.database.'.(DB_TYPE == 'PDO' ? 'pdo.' : '').'inc.php');
require_once($basedir.'include/functions.common.inc.php');
require_once($basedir.'include/functions.html.inc.php');

// autoloading classes
//------------------------------------------------------------------------------
function __autoload($class_name){
    global $basedir;
    require_once($basedir.'include/classes/'.$class_name.'.class.php');
}

define('APPHP_BASE', get_base_url());
@date_default_timezone_set(TIME_ZONE);

$objSession 		= new Session();
$objLogin 			= new Login();
$objSettings 		= new Settings();
$objSiteDescription = new SiteDescription();
Modules::Init();
ModulesSettings::Init();
Application::Init();
Languages::Init();

require_once($basedir.'include/messages.en.inc.php');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
    <title>Reservation Form</title>
    <script type="text/javascript" src="<?php echo $host; ?>js/main.js"></script>
    <script type="text/javascript" src="<?php echo $host; ?>js/jquery-1.4.2.min.js"></script>
    <link href="<?php echo $host; ?>templates/default/css/style.css" type="text/css" rel="stylesheet" />    
</head>
<body>
    <?php
        echo '<h2>'._RESERVATION.'</h2>';				
        Rooms::DrawSearchAvailabilityBlock(false, '', $hids, 8, 3, 'main-vertical', $host, '_parent', true);
        Rooms::DrawSearchAvailabilityFooter('', $host);
    ?>
</body>
</html>
<?php
$stop = false;
if (isset($_GET['controller']) && $_GET['controller'] == 'pjInstaller')
{
	$stop = true;
	if (isset($_GET['install']))
	{
		switch ($_GET['install'])
		{
			case 1:
				$stop = true;
				break;
			default:
				$stop = false;
				break;
		}
	}
  if (in_array($_GET['action'], array('pjActionLicense', 'pjActionHash')) || strpos($_GET['action'], 'pjActionSecure') === 0)
	{
		$stop = false;
	}
}

if (!$stop)
{
	require dirname(__FILE__) . '/config.inc.php';

	if (!defined("PJ_HOST") || preg_match('/\[hostname\]/', PJ_HOST))
	{
		header("Location: index.php?controller=pjInstaller&action=pjActionStep1&install=1");
		exit;
	}
}

if (!defined("PJ_APP_PATH")) define("PJ_APP_PATH", ROOT_PATH . "app/");
if (!defined("PJ_CORE_PATH")) define("PJ_CORE_PATH", ROOT_PATH . "core/");
if (!defined("PJ_LIBS_PATH")) define("PJ_LIBS_PATH", "core/libs/");
if (!defined("PJ_THIRD_PARTY_PATH")) define("PJ_THIRD_PARTY_PATH", "core/third-party/");
if (!defined("PJ_FRAMEWORK_PATH")) define("PJ_FRAMEWORK_PATH", PJ_CORE_PATH . "framework/");
if (!defined("PJ_FRAMEWORK_LIBS_PATH")) define("PJ_FRAMEWORK_LIBS_PATH", "core/framework/libs/");
if (!defined("PJ_CONFIG_PATH")) define("PJ_CONFIG_PATH", PJ_APP_PATH . "config/");
if (!defined("PJ_CONTROLLERS_PATH")) define("PJ_CONTROLLERS_PATH", PJ_APP_PATH . "controllers/");
if (!defined("PJ_COMPONENTS_PATH")) define("PJ_COMPONENTS_PATH", PJ_APP_PATH . "controllers/components/");
if (!defined("PJ_MODELS_PATH")) define("PJ_MODELS_PATH", PJ_APP_PATH . "models/");
if (!defined("PJ_PLUGINS_PATH")) define("PJ_PLUGINS_PATH", PJ_APP_PATH . "plugins/");
if (!defined("PJ_VIEWS_PATH")) define("PJ_VIEWS_PATH", PJ_APP_PATH . "views/");
if (!defined("PJ_WEB_PATH")) define("PJ_WEB_PATH", PJ_APP_PATH . "web/");
if (!defined("PJ_CSS_PATH")) define("PJ_CSS_PATH", "app/web/css/");
if (!defined("PJ_IMG_PATH")) define("PJ_IMG_PATH", "app/web/img/");
if (!defined("PJ_FONT_PATH")) define("PJ_FONT_PATH", "app/web/fonts/");
if (!defined("PJ_JS_PATH")) define("PJ_JS_PATH", "app/web/js/");
if (!defined("PJ_UPLOAD_PATH")) define("PJ_UPLOAD_PATH", "app/web/upload/");

if (!defined("PJ_SCRIPT_VERSION")) define("PJ_SCRIPT_VERSION", "4.3");
if (!defined("PJ_SCRIPT_ID")) define("PJ_SCRIPT_ID", "24");
if (!defined("PJ_SCRIPT_BUILD")) define("PJ_SCRIPT_BUILD", "4.3.0");
if (!defined("PJ_SCRIPT_PREFIX")) define("PJ_SCRIPT_PREFIX", "abcalendar_");
if (!defined("PJ_TEST_MODE")) define("PJ_TEST_MODE", false);
if (!defined("PJ_DISABLE_MYSQL_CHECK")) define("PJ_DISABLE_MYSQL_CHECK", false);

if (!defined("PJ_INVOICE_PLUGIN")) define("PJ_INVOICE_PLUGIN", 'index.php?controller=pjAdminReservations&action=pjActionUpdate&uuid={ORDER_ID}');

if (!defined("PJ_RSA_MODULO")) define("PJ_RSA_MODULO", '1481520313354086969195005236818182195268088406845365735502215319550493699869327120616729967038217547');
if (!defined("PJ_RSA_PRIVATE")) define("PJ_RSA_PRIVATE", '7');

if (!defined("PJ_DB_HOST")) define("PJ_DB_HOST", 'localhost');
if (!defined("PJ_DB_USERNAME")) define("PJ_DB_USERNAME", 'root');
if (!defined("PJ_DB_PASS")) define("PJ_DB_PASS", 'root');
if (!defined("PJ_DB_NAME")) define("PJ_DB_NAME", 'igtrip');

if (!defined("PJ_URL_LARAVEL")) define("PJ_URL_LARAVEL", 'http://localhost:8000');
if (!defined("PJ_URL_BOOKING")) define("PJ_URL_BOOKING", 'http://localhost/Booking');

if (!defined("PJ_PAYPAL_URL")) define("PJ_PAYPAL_URL", 'http://localhost/Booking/index.php?controller=pjAdmin&action=pjActionLogin&rk=');
if (!defined("PJ_O_PAYPAL_ADDRESS")) define("PJ_O_PAYPAL_ADDRESS", 'iwannatrip1@gmail.com');

if (!defined("PJ_O_SMTP_HOST")) define("PJ_O_SMTP_HOST", 'smtp.gmail.com');
if (!defined("PJ_O_SMTP_PASS")) define("PJ_O_SMTP_PASS", 'iwannatrip123');
if (!defined("PJ_O_SMTP_PORT")) define("PJ_O_SMTP_PORT", '587');
if (!defined("PJ_O_SMTP_USER")) define("PJ_O_SMTP_USER", 'iwannatriptest@gmail.com');

if (!defined("PJ_O_AUHTORIZE_HASH")) define("PJ_O_AUHTORIZE_HASH", 'SIMON');
if (!defined("PJ_O_AUHTORIZE_KEY")) define("PJ_O_AUHTORIZE_KEY", '59C8zvj42qPZ66Ff');
if (!defined("PJ_O_AUHTORIZE_MID")) define("PJ_O_AUHTORIZE_MID", '287qPpCha');


$CONFIG = array();
$CONFIG['plugins'] = array('pjLocale', 'pjBackup', 'pjLog', 'pjInstaller', 'pjPaypal', 'pjAuthorize', 'pjOneAdmin', 'pjSms', 'pjInvoice', 'pjPrice', 'pjPeriod', 'pjCountry');
?>
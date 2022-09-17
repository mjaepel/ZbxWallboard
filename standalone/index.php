<?php

ini_set('display_errors',1);
ini_set('memcached.sess_prefix','memc.sess.' . $_SERVER["SCRIPT_NAME"] . '.');

//=================================================================== Session
session_start();

//=================================================================== Includes
require_once('config.php');
require_once('classes/RemoteData_Zabbix.php');
require_once('classes/RemoteData_Icinga.php');
require_once('classes/Wallboard.php');
require_once('classes/ExceptionHandler.php');

//=================================================================== Some config logic
$CONFIG['SCRIPT_PATH'] = $CONFIG['REVERSE_PROXY_PATH'] . $_SERVER['SCRIPT_NAME'];

//=================================================================== Exception Handler
$EXCEPTION_HANDLER = new ExceptionHandler();
set_exception_handler(array($EXCEPTION_HANDLER, 'error'));

//=================================================================== Read login credentials
if (isset($_SESSION["password"])) {
	if (isset($_SESSION["username"])) {
		$CONFIG['ZABBIX']['USERNAME'] = $_SESSION["username"];
		$CONFIG['ZABBIX']['PASSWORD'] = openssl_decrypt($_SESSION["password"],'aes-256-cbc',$_COOKIE["zbxwallboard_pw_crypt_key"],OPENSSL_RAW_DATA,$iv=$_SESSION["iv"]);
	}
}

//=================================================================== API Data
if ($CONFIG['ZABBIX']['ENABLED']) {
	$BACKEND_ZBX = new RemoteData_Zabbix($CONFIG['ZABBIX']['URL'],$CONFIG['ZABBIX']['USERNAME'],$CONFIG['ZABBIX']['PASSWORD'],$CONFIG['ZABBIX']['BASIC_AUTH']);
	$HOSTGROUPS = $BACKEND_ZBX->get_hostgroups($CONFIG['HOSTGROUP_SEARCH_PARAMS']);
}
else {
	$HOSTGROUPS = array();
}

if ($CONFIG['ICINGA']['ENABLED']) {
	$BACKEND_ICINGA = new RemoteData_Icinga($CONFIG['ICINGA']['URL'],$CONFIG['ICINGA']['USERNAME'],$CONFIG['ICINGA']['PASSWORD']);
}

//=================================================================== Request Hostgroup, Severity and Display Options
// Hostgroup Filtering
if (isset($_GET['groupid'])) {
	if (is_numeric($_GET['groupid'])) {
		if ($_GET['groupid'] === 'all') {
			if (isset($_SESSION['groupid'])) {
				unset($_SESSION["groupid"]);
				unset($_SESSION["group_name"]);
			}
		}
		else {
			$HG_INDEX = array_search($_GET['groupid'],array_column($HOSTGROUPS, 'groupid'));
			if ($HG_INDEX !== NULL) {
				$CONFIG['TRIGGER_SEARCH_PARAMS']['groupids'] = $_GET['groupid'];
				$_SESSION['group_name'] = $HOSTGROUPS[$HG_INDEX]['name'];
				$_SESSION['groupid'] = $_GET['groupid'];
			}
		}
	}
	elseif (is_array($_GET['groupid'])) {
		if (in_array('all',$_GET['groupid'])) {
			if (isset($_SESSION['groupid'])) {
				unset($_SESSION["groupid"]);
				unset($_SESSION["group_name"]);
			}
		}
		else {
			$CONFIG['TRIGGER_SEARCH_PARAMS']['groupids'] = array();
			foreach ($_GET['groupid'] as $groupid) {
				if (is_numeric($groupid)) {
					$HG_INDEX = array_search($groupid,array_column($HOSTGROUPS, 'groupid'));
					if ($HG_INDEX !== NULL) {
						$CONFIG['TRIGGER_SEARCH_PARAMS']['groupids'][] = $groupid;
						$_SESSION['group_name'] = 'Filtered';
						$_SESSION['groupid'] = $CONFIG['TRIGGER_SEARCH_PARAMS']['groupids'];
					}
				}
			}
		}
	}
}
elseif (isset($_SESSION['groupid'])) {
	if (is_numeric($_SESSION['groupid'])) {
		$HG_INDEX = array_search($_SESSION['groupid'],array_column($HOSTGROUPS, 'groupid'));
		if ($HG_INDEX !== NULL) {
			$CONFIG['TRIGGER_SEARCH_PARAMS']['groupids'] = $_SESSION['groupid'];
		}
	}
	elseif (is_array($_SESSION['groupid'])) {
		$CONFIG['TRIGGER_SEARCH_PARAMS']['groupids'] = array();
		foreach ($_SESSION['groupid'] as $groupid) {
			$HG_INDEX = array_search($groupid,array_column($HOSTGROUPS, 'groupid'));
			if ($HG_INDEX !== NULL) {
				$CONFIG['TRIGGER_SEARCH_PARAMS']['groupids'][] = $groupid;
			}
		}
	}
}

// Severity Filtering
if (isset($_GET['severity'])) {
	if (isset($CONFIG['SEVERITIES'][$_GET['severity']])) {
		$CONFIG['TRIGGER_SEARCH_PARAMS']['min_severity'] = $_GET['severity'];
		$_SESSION['severity'] = $_GET['severity'];
		$_SESSION['severity_name'] = $CONFIG['SEVERITIES'][$_GET['severity']];
	}
}
elseif (isset($_SESSION['severity'])) {
	if (isset($CONFIG['SEVERITIES'][$_SESSION['severity']])) {
		$CONFIG['TRIGGER_SEARCH_PARAMS']['min_severity'] = $_SESSION['severity'];
	}
}

// Hide Acknowledged
if (isset($_GET['hide_acked'])) {
	if ($_GET['hide_acked'] === "1") {
		$CONFIG['TRIGGER_SEARCH_PARAMS']['withLastEventUnacknowledged'] = True;
		$_SESSION['hide_acked'] = True;
	}
	elseif ($_GET['hide_acked'] === "0") {
		$CONFIG['TRIGGER_SEARCH_PARAMS']['withLastEventUnacknowledged'] = NULL;
		$_SESSION['hide_acked'] = NULL;
	}
}
elseif (isset($_SESSION['hide_acked'])) {
	$CONFIG['TRIGGER_SEARCH_PARAMS']['withLastEventUnacknowledged'] = $_SESSION['hide_acked'];
}

// Hide Maintenance
if (isset($_GET['hide_maint'])) {
	if ($_GET['hide_maint'] === "1") {
		$CONFIG['TRIGGER_SEARCH_PARAMS']['maintenance'] = False;
		$_SESSION['hide_maint'] = False;
	}
	elseif ($_GET['hide_maint'] === "0") {
		$CONFIG['TRIGGER_SEARCH_PARAMS']['maintenance'] = NULL;
		$_SESSION['hide_maint'] = NULL;
	}
}
elseif (isset($_SESSION['hide_maint'])) {
	$CONFIG['TRIGGER_SEARCH_PARAMS']['maintenance'] = $_SESSION['hide_maint'];
}

//=================================================================== Create Wallboard
$WALLBOARD = new Wallboard($CONFIG['SCRIPT_PATH'],$CONFIG['DISPLAY']);

//=================================================================== Create Main Content
if (isset($_REQUEST["action"])) {
	switch ($_REQUEST["action"]) {
		case 'details':
			if (isset($_REQUEST["eventid"])) {
				if (is_numeric($_REQUEST["eventid"])) {
					$CONFIG['EVENT_SEARCH_PARAMS']['eventids'] = $_REQUEST["eventid"];
					$DETAILS = $BACKEND_ZBX->get_eventdetails($CONFIG['EVENT_SEARCH_PARAMS']);
					$WALLBOARD->ajax_event_details($DETAILS);
				}
			}
			break;
		case 'add_acknowledge':
			if (isset($_REQUEST["eventid"])) {
				if (isset($_REQUEST["ack_msg"])) {
					if (is_numeric($_REQUEST["eventid"]) && (is_string($_REQUEST["ack_msg"]))) {
						if (isset($_SESSION["username"])) {
							$BACKEND_ZBX->add_acknowledge($_REQUEST["eventid"],$_REQUEST["ack_msg"]);
							header('Location: ' . $WALLBOARD->gen_script_path());
							exit;
						}
					}
				}
			}
			break;
		case 'login':
			if (isset($_REQUEST["username"])) {
				if (isset($_REQUEST["password"])) {
					$_SESSION["username"] = $_REQUEST["username"];
					$_SESSION["iv"] = openssl_random_pseudo_bytes(16);
					$ENC_KEY = bin2hex(openssl_random_pseudo_bytes(16));
					$_SESSION["password"] = openssl_encrypt($_REQUEST["password"],'aes-256-cbc',$ENC_KEY,OPENSSL_RAW_DATA,$iv=$_SESSION["iv"]);
					setcookie('zbxwallboard_pw_crypt_key',$ENC_KEY);

					header('Location: ' . $WALLBOARD->gen_script_path());
					exit;
				}
			}
			break;
		case 'logout':
			if (isset($_COOKIE["zbxwallboard_pw_crypt_key"])) {
				unset($_COOKIE["zbxwallboard_pw_crypt_key"]);
				setcookie('zbxwallboard_pw_crypt_key', null, -1, '/');
			}
			session_destroy();
			header('Location: ' . $WALLBOARD->gen_script_path());
			break;
		default:
			throw new Exception('Unknown action',100);
	}
}
else {
	$TRIGGERS_ZBX = array();
	$TRIGGERS_ICINGA = array();

	if ($CONFIG['ZABBIX']['ENABLED']) {
		$TRIGGERS_ZBX = $BACKEND_ZBX->get_triggers($CONFIG['TRIGGER_SEARCH_PARAMS']);
		if (count($TRIGGERS_ZBX) === 1) {
			if ($TRIGGERS_ZBX[0] === false) {
				$TRIGGERS_ZBX = array();
			}
		}
	}

	if ($CONFIG['ICINGA']['ENABLED']) {
		$TRIGGERS_ICINGA = $BACKEND_ICINGA->get_triggers($CONFIG['ICINGA_SEARCH_PARAMS']);
	}

	$TRIGGERS = array_merge($TRIGGERS_ZBX, $TRIGGERS_ICINGA);
	$WALLBOARD->gen_main_content($TRIGGERS);
}

//=================================================================== Create Menu
$WALLBOARD->gen_menu($HOSTGROUPS,$CONFIG['SEVERITIES']);

//=================================================================== Finish
$WALLBOARD->publish_content();

/*
echo "<pre>";
var_dump($_SESSION);
var_dump($_REQUEST);
var_dump($CONFIG['TRIGGER_SEARCH_PARAMS']['groupids']);
echo "</pre>";
*/
?>

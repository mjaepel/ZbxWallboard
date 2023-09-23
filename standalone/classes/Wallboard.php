<?php

class Wallboard {
	private $SCRIPT_PATH;
	private $TITLE = 'ZbxWallboard';
	
	private $PROBLEM_COUNT;
	private $PROBLEM_COUNT_SHOW = false;

	private $LUNCH_REMINDER = false;
	private $LUNCH_REMINDER_START = 1200;
	private $LUNCH_REMINDER_END = 1230;
	
	private $MENU = '';
	private $MAIN_CONTENT = '';
	
	private $AJAX_REQUEST = False;
	private $AJAX_OUTPUT = '';
	
	public function __construct($SCRIPT_PATH = '', $DISPLAY = array()) {
		$this->SCRIPT_PATH = $SCRIPT_PATH;
		
		if (isset($DISPLAY['TITLE']))
			$this->TITLE = $DISPLAY['TITLE'];
	
		if (isset($DISPLAY['PROBLEM_COUNT_SHOW']))
			$this->PROBLEM_COUNT_SHOW = $DISPLAY['PROBLEM_COUNT_SHOW'];
		
		if (isset($DISPLAY['LUNCH_REMINDER']))
			$this->LUNCH_REMINDER = $DISPLAY['LUNCH_REMINDER'];

		if (isset($DISPLAY['LUNCH_REMINDER_START']))
			$this->LUNCH_REMINDER_START = $DISPLAY['LUNCH_REMINDER_START'];

		if (isset($DISPLAY['LUNCH_REMINDER_END']))
			$this->LUNCH_REMINDER_END = $DISPLAY['LUNCH_REMINDER_END'];
	}
	
	private function get_severity_color($SEVERITY) {
		switch($SEVERITY) {
			case 0:
				$COLOR = 'text-shadow';
				break;
			case 1:
				$COLOR = 'fg-white bg-emerald text-shadow';
				break;
			case 2:
				$COLOR = 'fg-white bg-amber text-shadow';
				break;
			case 3:
				$COLOR = 'fg-white bg-orange text-shadow';
				break;
			case 4:
				$COLOR = 'fg-white bg-red text-shadow';
				break;
			case 5:
				$COLOR = 'fg-white bg-darkMagenta text-shadow';
				break;
			default:
				$COLOR = '';
		}

		return $COLOR;
	}

	public function gen_script_path($REQ_PARAMS=array()) {
		$PARAMS = array();
		
		if (isset($REQ_PARAMS['groupid'])) { 
			$PARAMS['groupid'] = $REQ_PARAMS['groupid'];
		}
		elseif (isset($_SESSION['groupid'])) {
			$PARAMS['groupid'] = $_SESSION['groupid'];
		}

		if (isset($PARAMS['groupid'])) {
			if (is_array($PARAMS['groupid'])) {
				if (count($PARAMS['groupid']) == 0) {
					$PARAMS['groupid'] = array('all');
				}
			}
		}	
	
		if (isset($REQ_PARAMS['severity'])) {
			$PARAMS['severity'] = $REQ_PARAMS['severity'];
		}
		elseif (isset($_SESSION['severity'])) {
			$PARAMS['severity'] = $_SESSION['severity'];
		}
		
		if (isset($REQ_PARAMS['action'])) {
			$PARAMS['action'] = $REQ_PARAMS['action'];
		}
		
		if (isset($REQ_PARAMS['eventid'])) {
			$PARAMS['eventid'] = $REQ_PARAMS['eventid'];
		}

		if (isset($REQ_PARAMS['hide_acked'])) {
			$PARAMS['hide_acked'] = $REQ_PARAMS['hide_acked'];
		}
		elseif (isset($_SESSION['hide_acked'])) {
			if ($_SESSION['hide_acked'] === True) {
				$PARAMS['hide_acked'] = 1;
			}
			else {
				$PARAMS['hide_acked'] = 0;
			}
		}
		
		if (isset($REQ_PARAMS['hide_maint'])) {
			$PARAMS['hide_maint'] = $REQ_PARAMS['hide_maint'];
		}
		elseif (isset($_SESSION['hide_maint'])) {
			if ($_SESSION['hide_maint'] === False) {
				$PARAMS['hide_maint'] = 1;
			}
			else {
				$PARAMS['hide_maint'] = 0;
			}
		}
		
		$URL_REQUEST = $this->SCRIPT_PATH . '?' . http_build_query($PARAMS);
		return $URL_REQUEST;
	}
	
	private function gen_html_header() {
		$HEADER = "<!DOCTYPE html>
			<html lang='en'>
				<head>
					<title>" . $this->TITLE . "</title>

					<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
					<meta http-equiv='expires' content='0'>
					
					<link href='css/metro.min.css' rel='stylesheet'>
					<link href='css/metro-icons.min.css' rel='stylesheet'>
					<link href='css/metro-responsive.min.css' rel='stylesheet'>
					<link href='css/metro-schemes.min.css' rel='stylesheet'>
					<link href='css/style.css' rel='stylesheet'>

					<script src='js/jquery-3.7.1.min.js'></script>
					<script src='js/metro.min.js'></script>
					<script src='js/wallboard.js'></script>
					<script src='js/scale.js'></script>
				</head>";
		
		return $HEADER;
	}
	
	private function gen_html_body() {
		$BODY = "<body class='bg-white'>";
		$BODY .= $this->MENU;
		$BODY .= $this->MAIN_CONTENT;
		$BODY .= "</body></html>";
		return $BODY;
	}
	
	private function gen_html_tiles($TRIGGERS) {
		$OUTPUT = '';
		if ($TRIGGERS) {
			for ($TRIGGER_CT = 0; $TRIGGER_CT < $this->PROBLEM_COUNT_SHOW; $TRIGGER_CT++) {
				$TRIGGER = $TRIGGERS[$TRIGGER_CT];
				$MAINTENANCE = False;
				$ACKNOWLEDGED = False;

				if (array_search('1',array_column($TRIGGER['hosts'], 'maintenance_status')) !== False) {
					$MAINTENANCE = True;
				}
				if (isset($TRIGGER['lastEvent']['acknowledged'])) {
					if ($TRIGGER['lastEvent']['acknowledged'] === '1') {
						$ACKNOWLEDGED = True;
					}
				}

				if (($MAINTENANCE) | ($ACKNOWLEDGED)) {
					$COLOR = '';
				}
				else {
					$COLOR = $this->get_severity_color($TRIGGER['priority']);
				}

				if (isset($TRIGGER['lastEvent']['eventid'])) {
					$ONCLICK = "onclick='showDialogDetails(\"#dialog_details\",\"" . $TRIGGER['lastEvent']['eventid'] . "\");'";
				}
				else {
					$ONCLICK = '';
				}
				
				$OUTPUT .= '<div class="tile-wide ' . $COLOR . ' no-margin-right shadow" data-role="tile" ' . $ONCLICK . '>';
				$OUTPUT .= '<div class="tile-content">';
				$OUTPUT .= '<p class="align-center text-default">' . date('Y-m-d H:i:s ', $TRIGGER['lastchange']) . '</p>';

				$HOSTNAME = $TRIGGER['hosts'][0]['name'];
				if (strlen($TRIGGER['hosts'][0]['name']) > 32) {
					$OUTPUT .= '<p class="align-center text-accent-small">' . $HOSTNAME . '</p>';
					$OUTPUT .= '<p class="align-center text-accent hidden">' . $HOSTNAME . '</p>';
				}
				else {
					$OUTPUT .= '<p class="align-center text-accent-small hidden">' . $HOSTNAME . '</p>';
					$OUTPUT .= '<p class="align-center text-accent">' . $HOSTNAME . '</p>';
				}

				if (strlen($TRIGGER['description']) > 64) {
					$OUTPUT .= '<p class="align-center text-default-small">' . $TRIGGER['description'] . '</p>';
					$OUTPUT .= '<p class="align-center text-default hidden">' . $TRIGGER['description'] . '</p>';
				}
				else {
					$OUTPUT .= '<p class="align-center text-default-small hidden">' . $TRIGGER['description'] . '</p>';
					$OUTPUT .= '<p class="align-center text-default">' . $TRIGGER['description'] . '</p>';
				}

				
				$BADGES = array();
				if (array_key_exists('backend_icinga', $TRIGGER)) {
					$BADGES[] = '<span>Icinga</span>';
				}
				else {
					// we know the default backend :)
					// $BADGES[] = '<span>Zabbix</span>';
				}
				
				if ($MAINTENANCE) {
					$BADGES[] = '<span class="mif-wrench"></span>';
				}
				
				if ($ACKNOWLEDGED) {
					$BADGES[] = '<span class="mif-checkmark"></span>';
				}
				
				if (count($BADGES) > 0) {
					$OUTPUT .= '<span class="tile-badge bg-emerald">';
					for ($BADGE_ID = 0; $BADGE_ID < count($BADGES); $BADGE_ID++) {
						$OUTPUT .= $BADGES[$BADGE_ID];
						if ($BADGE_ID != count($BADGES)-1) {
							$OUTPUT .= ' | ';
						}
					}
					$OUTPUT .= '</span>';
				}

				$OUTPUT .= '</div></div>';
			}
		}
		else {
			$OUTPUT .= '<div class="row flex-just-center">&nbsp;</div>';
			$OUTPUT .= '	<div class="row flex-just-center">';
			$OUTPUT .= '		<div class="cell"></div>';
			$OUTPUT .= '			<div class="panel success">';
			$OUTPUT .= '				<div class="heading">';
			$OUTPUT .= '					<span class="icon mif-thumbs-up"></span>';
			$OUTPUT .= '					<span class="title">No issues!</span>';
			$OUTPUT .= '				</div>';
			$OUTPUT .= '				<div class="content">';
			$OUTPUT .= '					<p class="align-center text-default">';
			$OUTPUT .= '										There are no issues in this hostgroup. Good Job!<br />&nbsp;<br />';

			/* Lunch Reminder :D */
			if ($this->LUNCH_REMINDER 
				and intval(date('Hi')) >= $this->LUNCH_REMINDER_START 
				and intval(date('Hi')) <= $this->LUNCH_REMINDER_END) {
					$OUTPUT .= '											<span class="mif-spoon-fork mif-ani-flash fg-emerald" style="font-size: 30em;"></span>';
			}
			else {
				$OUTPUT .= '											<span class="mif-thumbs-up fg-emerald" style="font-size: 30em;"></span>';
			}
			$OUTPUT .= '									</p>';
			$OUTPUT .= '				</div>';
			$OUTPUT .= '			</div>';
			$OUTPUT .= '		</div>';
			$OUTPUT .= '</div>';
		}


		$OUTPUT .= '<div data-role="dialog" id="dialog_details" data-close-button="true" data-width="50%" data-height="75%">';
		$OUTPUT .= '<div class="dialog_details_content" id="dialog_details_content"><h1>Details</h1></div>';
		$OUTPUT .= '</div>';

		return $OUTPUT;
	}
	
	function ajax_event_details($EVENT) {
		$this->AJAX_REQUEST = True;
		$this->AJAX_OUTPUT .= "<h1>Acknowledges</h1>";

		if (isset($_SESSION["username"])) {
			$this->AJAX_OUTPUT .= "<form action='" . $this->gen_script_path() . "' method='post'>
				<div class='input-control text full-size' data-role='input'>
					<input type='text' name='ack_msg' maxlength='255'>
					<span class='label'>New Acknowledgement </span>
					<input type='hidden' name='eventid' value='" . $EVENT[0]['eventid'] . "'>
					<button class='button' type='submit' value='add_acknowledge' name='action' ><span class='mif-plus mif-ani-heartbeat'></span></button>
				</div>
			</form>

			<hr />";
		}

		$this->AJAX_OUTPUT .= "<ul class='step-list'>";

		foreach ($EVENT[0]["acknowledges"] as $ACKED_MSG) {
			$this->AJAX_OUTPUT .=  "<li>
					<div class='flex-grid'>
						<div class='row cell-auto-size'>
							<div class='cell'>
								<h2 class='no-margin-top no-margin-bottom'>" . $ACKED_MSG["name"] . " " . $ACKED_MSG["surname"] . "</h2>
							</div>
							<div class='cell'>
								<h2 class='no-margin-top no-margin-top align-right'><small>" . date("Y-m-d H:i:s ", $ACKED_MSG["clock"]) . "</small></h2>
							</div>
						</div>
					</div>

					<hr class='bg-red no-margin' />
					<div>" . $ACKED_MSG["message"] . "</div>
				</li>";
		}

		$this->AJAX_OUTPUT .= "</ul>";
	}

	public function gen_menu($HOSTGROUPS,$SEVERITIES) {
		//=================================================================== Define App bar
		$MENU = "<div class='app-bar' data-role='appbar'>";
		
		//=================================================================== Left: Toolname
		$MENU .= "<a class='app-bar-element branding'>" . $this->TITLE . "</a>
			<span class='app-bar-divider'></span>";
		
		//=================================================================== Left: Hostgroups
		$MENU .= "<ul class='app-bar-menu small-dropdown'>
				<li>";

		if (isset($_SESSION['group_name'])) {
			if ($_SESSION['group_name'] != '') {
				$MENU .= "<a href='' class='dropdown-toggle'>Hostgroups (" . $_SESSION['group_name'] . ")</a>";
			}
		}
		else {
			$MENU .= "<a href='' class='dropdown-toggle'>Hostgroups</a>";
		}

		$MENU .= "<ul class='d-menu menu-scroll' data-role='dropdown'>";
		$MENU .= "<li><a href='" . $this->gen_script_path(array('groupid'=>array('all'))) . "'>&nbsp;&nbsp;All</a></li>";
		$MENU .= "<hr />";

		foreach ($HOSTGROUPS as $HOSTGROUP) {
			if (isset($_SESSION['groupid'])) {
				if (is_numeric($_SESSION['groupid'])) {
					if ($_SESSION['groupid'] == $HOSTGROUP['groupid']) {
						$MENU .= "<li><a href='" . $this->gen_script_path(array('groupid'=>array())) . "'><span class='mif-checkmark icon fg-green'></span>&nbsp;&nbsp;" . $HOSTGROUP["name"] . "</a></li>";
					}
					else {
						$MENU .= "<li><a href='" . $this->gen_script_path(array('groupid'=>array($_SESSION['groupid'],$HOSTGROUP["groupid"]))) . "'>&nbsp;&nbsp;" . $HOSTGROUP["name"] . "</a></li>";
					}
				}
				elseif (is_array($_SESSION['groupid'])) {
					if (in_array($HOSTGROUP["groupid"],$_SESSION['groupid'])) {
						$URL_GROUPS = $_SESSION['groupid'];
						$GROUP_KEY = array_search($HOSTGROUP["groupid"],$_SESSION['groupid']);
						if ($GROUP_KEY !== false){
							unset($URL_GROUPS[$GROUP_KEY]);
						}

						$MENU .= "<li><a href='" . $this->gen_script_path(array('groupid'=>$URL_GROUPS)) . "'><span class='mif-checkmark icon fg-green'></span>&nbsp;&nbsp;" . $HOSTGROUP["name"] . "</a></li>";
					}
					else {
						$GROUPS = $_SESSION['groupid'];
						$GROUPS[] = $HOSTGROUP["groupid"];
						$MENU .= "<li><a href='" . $this->gen_script_path(array('groupid'=>$GROUPS)) . "'>&nbsp;&nbsp;" . $HOSTGROUP["name"] . "</a></li>";
					}
				}
			}
			else {
				$MENU .= "<li><a href='" . $this->gen_script_path(array('groupid'=>array($HOSTGROUP["groupid"]))) . "'>" . $HOSTGROUP["name"] . "</a></li>";
			}
		}

		$MENU .= "</ul></li>";
		
		//=================================================================== Left: Severities
		$MENU .= "<li>";
		
		if (isset($_SESSION['severity_name'])) {
			if ($_SESSION['severity_name'] != '') {
				$MENU .= "<a href='' class='dropdown-toggle'>Minimum Severity (" . $_SESSION['severity_name'] . ")</a>";
			}
		}
		else {
			$MENU .= "<a href='' class='dropdown-toggle'>Minimum Severity</a>";
		}

		$MENU .= "<ul class='d-menu' data-role='dropdown'>";

		foreach (array_keys($SEVERITIES) as $SEVERITY_KEY) {
			$MENU .= "<li><a href='" . $this->gen_script_path(array('severity'=>$SEVERITY_KEY)) . "'>" . $SEVERITIES[$SEVERITY_KEY] . "</a></li>";
		}

		$MENU .= "</ul></li>";
		
		//=================================================================== Left: Options
		$MENU .= "<li>
			<a href='' class='dropdown-toggle'>Display Options</a>
			<ul class='d-menu' data-role='dropdown'>";
			
		if (isset($_SESSION['hide_acked'])) {
			if ($_SESSION['hide_acked']) {
				$MENU .= "<li><a href='" . $this->gen_script_path(array('hide_acked'=>0)) . "'><span class='mif-checkmark icon fg-green'></span>&nbsp;&nbsp;Hide acknowledged</a></li>";
			}
			else {
				$MENU .= "<li><a href='" . $this->gen_script_path(array('hide_acked'=>1)) . "'>&nbsp;&nbsp;Hide acknowledged</a></li>";
			}
		}
		else {
			$MENU .= "<li><a href='" . $this->gen_script_path(array('hide_acked'=>1)) . "'>&nbsp;&nbsp;Hide acknowledged</a></li>";
		}

		if (isset($_SESSION['hide_maint'])) {
			if ($_SESSION['hide_maint'] === False) {
				$MENU .= "<li><a href='" . $this->gen_script_path(array('hide_maint'=>0)) . "'><span class='mif-checkmark icon fg-green'></span>&nbsp;&nbsp;Hide maintenance</a></li>";
			}
			else {
				$MENU .= "<li><a href='" . $this->gen_script_path(array('hide_maint'=>1)) . "'>&nbsp;&nbsp;Hide acknowledged</a></li>";
			}
		}
		else {
			$MENU .= "<li><a href='" . $this->gen_script_path(array('hide_maint'=>1)) . "'>&nbsp;&nbsp;Hide maintenance</a></li>";
		}		

		$MENU .= "</ul></li>";

		//=================================================================== Right
		$MENU .= "</ul>";
		$MENU .= "<div class='app-bar-element place-right'>";

		//=================================================================== Right: Login/Logout
		if (isset($_SESSION["username"])) {
			$MENU .= "<a href='" . $this->gen_script_path(array('action'=>'logout')) . "' class='fg-white'><span class='mif-enter'></span> Logout</a>";
		}
		else {
			$MENU .= "<a class='dropdown-toggle fg-white'><span class='mif-enter'></span> Login</a>
					<div class='app-bar-drop-container bg-white fg-dark place-right' data-role='dropdown' data-no-close='true'>
						<div class='padding20'>
							<form action='" . $this->gen_script_path() . "' method='post'>
								<h4 class='text-light'>Login</h4>
								<div class='input-control modern text iconic' data-role='input'>
									<input type='text' name='username'>
									<span class='placeholder'>Username</span>
									<span class='icon mif-user'></span>
								</div>
								<div class='input-control modern password iconic' data-role='input'>
									<input type='password' name='password'>
									<span class='placeholder'>Password</span>
									<span class='icon mif-lock'></span>
								</div>
								<div class='form-actions'>
									<button class='button full-size' type='submit' name='action' value='login'>Login</button>
								</div>
							</form>
						</div>
					</div>";
		}
		$MENU .= "</div><span class='app-bar-divider place-right'></span>";
				
		//=================================================================== Right: Username
		$MENU .= "<div class='app-bar-element place-right'><span class='app-bar-element'>";

		if (isset($_SESSION["username"])) {
			$MENU .= $_SESSION["username"];
		}
		else {
			$MENU .= "Guest";
		}

		$MENU .= "</span></div><span class='app-bar-divider place-right'></span>";
				
		//=================================================================== Right: Time
		$MENU .= "<div class='app-bar-element place-right'>
					<span class='app-bar-element'>" . date("H:i:s") . "</span>
				</div>
				<span class='app-bar-divider place-right'></span>";

		//=================================================================== Right: Trigger Counter
		$MENU .= "<div class='app-bar-element place-right'>
					<span class='app-bar-element'>Triggers: " . $this->PROBLEM_COUNT_SHOW . " / " . $this->PROBLEM_COUNT . "</span>
				</div>
				<span class='app-bar-divider place-right'></span>";

		//=================================================================== Right: End
		$MENU .= "</div>";
		
		$this->MENU = $MENU;
	}
	
	public function gen_main_content($TRIGGERS) {
		$this->PROBLEM_COUNT = count($TRIGGERS);
		if ($this->PROBLEM_COUNT_SHOW === 0 or $this->PROBLEM_COUNT_SHOW > $this->PROBLEM_COUNT) {
			$this->PROBLEM_COUNT_SHOW = $this->PROBLEM_COUNT;
		}
		$this->MAIN_CONTENT = $this->gen_html_tiles($TRIGGERS);
	}
	
	public function error($ERROR_CODE,$ERROR_MSG,$ERROR_TRACE) {
		$this->MAIN_CONTENT = '<div class="row flex-just-center">&nbsp;</div>
						<div class="row flex-just-center">
							<div class="cell"></div>
								<div class="panel alert">
									<div class="heading">
										<span class="icon mif-warning"></span>
										<span class="title">Error Code ' . $ERROR_CODE . ' </span>
									</div>
									<div class="content">
										<p class="align-center text-default">' . $ERROR_MSG . '</p>
									</div>
								</div>
							</div>
						</div>';
	}
	
	public function publish_content() {
		if ($this->AJAX_REQUEST) {
			$CONTENT = $this->AJAX_OUTPUT;
		}
		else {
			$CONTENT = $this->gen_html_header();
			$CONTENT .= $this->gen_html_body();
		}
		
		echo $CONTENT;
	}
}

?>

<?php

class Wallboard {
	private $SCRIPT_PATH;
	private $TITLE;
	
	private $TRIGGER_COUNT;
	private $TRIGGER_COUNT_SHOW;
	
	private $MENU = '';
	private $MAIN_CONTENT = '';
	
	private $AJAX_REQUEST = False;
	private $AJAX_OUTPUT = '';
	
	public function __construct($SCRIPT_PATH = '', $TITLE = 'ZbxWallboard') {
		$this->SCRIPT_PATH = $SCRIPT_PATH;
		$this->TITLE = $TITLE;
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

					<script src='js/jquery-3.3.1.min.js'></script>
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
	
	private function gen_html_table($TRIGGERS) {
		$OUTPUT_LEFT = '';
		$OUTPUT_RIGHT = '';
		$DO_RIGHT = False;

		for ($TRIGGER_CT = 0; $TRIGGER_CT < $this->TRIGGER_COUNT_SHOW; $TRIGGER_CT++) {
			$TRIGGER = $TRIGGERS[$TRIGGER_CT];
			if ($TRIGGER) {
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
				
				$ICONS = False;
				if ($MAINTENANCE) {
					$ICONS .= '<span class="mif-wrench"></span>';
				}
				if (($MAINTENANCE) & ($ACKNOWLEDGED)) {
					$ICONS .= ' | ';
				}
				if ($ACKNOWLEDGED) {
					$ICONS .= '<span class="mif-checkmark"></span>';
				}
				
				if ($DO_RIGHT) {
					$OUTPUT_RIGHT .= '<tr class="' . $COLOR . '" ' . $ONCLICK . '>';
					$OUTPUT_RIGHT .= '<td class="no-margin" style="padding: 4px; font-size: 0.75em;">' . date('Y-m-d H:i:s ', $TRIGGER['lastchange']) . '</td>';
					$OUTPUT_RIGHT .= '<td class="no-margin" style="padding: 4px; font-size: 0.75em;">' . $TRIGGER['hostname'] . '</td>';
					if ($ICONS) {
						$OUTPUT_RIGHT .= '<td class="no-margin" style="padding: 4px; font-size: 0.75em;">' . $TRIGGER['description'] . '</td>';
						$OUTPUT_RIGHT .= '<td class="no-margin bg-emerald align-right" style="padding: 4px; font-size: 0.75em;">' . $ICONS . '</td>';
					}
					else {
						$OUTPUT_RIGHT .= '<td class="no-margin" style="padding: 4px; font-size: 0.75em;" colspan="2">' . $TRIGGER['description'] . '</td>';
					}
					$OUTPUT_RIGHT .= '</tr>';
					$DO_RIGHT = False;
				}
				else {
					$OUTPUT_LEFT .= '<tr class="' . $COLOR . '" ' . $ONCLICK . '>';
					$OUTPUT_LEFT .= '<td class="no-margin" style="padding: 4px; font-size: 0.75em;">' . date('Y-m-d H:i:s ', $TRIGGER['lastchange']) . '</td>';
					$OUTPUT_LEFT .= '<td class="no-margin" style="padding: 4px; font-size: 0.75em;">' . $TRIGGER['hostname'] . '</td>';
					if ($ICONS) {
						$OUTPUT_LEFT .= '<td class="no-margin" style="padding: 4px; font-size: 0.75em;">' . $TRIGGER['description'] . '</td>';
						$OUTPUT_LEFT .= '<td class="no-margin bg-emerald align-right" style="padding: 4px; font-size: 0.75em;">' . $ICONS . '</td>';
					}
					else {
						$OUTPUT_LEFT .= '<td class="no-margin" style="padding: 4px; font-size: 0.75em;" colspan="2">' . $TRIGGER['description'] . '</td>';
					}
					$OUTPUT_LEFT .= '</tr>';
					$DO_RIGHT = True;
				}
			}
		}

		$OUTPUT = '';
		$OUTPUT .= '<div class="flex-grid"><div class="row"><div class="cell size-p50 padding10">';
		$OUTPUT .= '<table class="table striped hovered border bordered">';
		$OUTPUT .= $OUTPUT_LEFT;
		$OUTPUT .= '</table></div>';
		$OUTPUT .= '<div class="cell size-p50 padding10"><table class="table striped hovered border bordered">';
		$OUTPUT .= $OUTPUT_RIGHT;
		$OUTPUT .= '</table></div></div></div>';

		$OUTPUT .= '<div data-role="dialog" id="dialog_details" data-close-button="true" data-width="50%" data-height="75%">';
		$OUTPUT .= '<div class="dialog_details_content" id="dialog_details_content"><h1>Details</h1></div>';
		$OUTPUT .= '</div>';
		
		return $OUTPUT;
	}
	
	private function gen_html_tiles($TRIGGERS) {
		$OUTPUT = '';
		for ($TRIGGER_CT = 0; $TRIGGER_CT < $this->TRIGGER_COUNT_SHOW; $TRIGGER_CT++) {
			$TRIGGER = $TRIGGERS[$TRIGGER_CT];
			if ($TRIGGER) {
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
				$OUTPUT .= '<p class="align-center text-accent">' . str_pad($TRIGGER['hosts'][0]['name'],32) . '</p>';
				$OUTPUT .= '<p class="align-center text-default">' . $TRIGGER['description'] . '</p>';

				if (($MAINTENANCE) | ($ACKNOWLEDGED)) {
					$OUTPUT .= '<span class="tile-badge bg-emerald">';
					if ($MAINTENANCE) {
						$OUTPUT .= '<span class="mif-wrench"></span>';
					}
					if (($MAINTENANCE) & ($ACKNOWLEDGED)) {
						$OUTPUT .= ' | ';
					}
					if ($ACKNOWLEDGED) {
						$OUTPUT .= '<span class="mif-checkmark"></span>';
					}
					$OUTPUT .= '</span>';
				}

				$OUTPUT .= '</div></div>';

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
				//Disabled Lunch Reminder
				//if (intval(date('Hi')) >= 1200 and intval(date('Hi')) <= 1230) {
				if (FALSE) {
					$OUTPUT .= '											<span class="mif-spoon-fork mif-ani-flash fg-emerald" style="font-size: 30em;"></span>';
//					$OUTPUT .= '						<p class="align-center fg-red" style="font-size: 5em;">Heute 15 Minuten sp&auml;ter!</p>';
				}
				else {
					$OUTPUT .= '										<span class="mif-thumbs-up fg-emerald" style="font-size: 30em;"></span>';
				}
				$OUTPUT .= '									</p>';
				$OUTPUT .= '				</div>';
				$OUTPUT .= '			</div>';
				$OUTPUT .= '		</div>';
				$OUTPUT .= '</div>';
			}
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

		$MENU .= "<ul class='d-menu' data-role='dropdown'>";
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
					<span class='app-bar-element'>Triggers: " . $this->TRIGGER_COUNT_SHOW . " / " . $this->TRIGGER_COUNT . "</span>
				</div>
				<span class='app-bar-divider place-right'></span>";

		//=================================================================== Right: End
		$MENU .= "</div>";
		
		$this->MENU = $MENU;
	}
	
	public function gen_main_content($TRIGGERS) {
		$this->TRIGGER_COUNT = count($TRIGGERS);

// Added Zooming		
//		if ($this->TRIGGER_COUNT <= 36) {
		if ($this->TRIGGER_COUNT <= 36000000) {
			$this->TRIGGER_COUNT_SHOW = $this->TRIGGER_COUNT;
			$CONTENT = $this->gen_html_tiles($TRIGGERS);
		}
		elseif ($this->TRIGGER_COUNT <= 86) {
			$this->TRIGGER_COUNT_SHOW = $this->TRIGGER_COUNT;
			$CONTENT = $this->gen_html_table($TRIGGERS);
		}
		elseif ($this->TRIGGER_COUNT > 86) {
			$this->TRIGGER_COUNT_SHOW = 86;
			$CONTENT = $this->gen_html_table($TRIGGERS);
		}
		
		$this->MAIN_CONTENT = $CONTENT;
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

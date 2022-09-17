<?php

class RemoteData_Icinga {
	protected $URL;
	protected $USERNAME;
	protected $PASSWORD;
	
	public function __construct($URL,$USERNAME,$PASSWORD) {
		$this->URL = $URL;
		$this->USERNAME = $USERNAME;
		$this->PASSWORD = $PASSWORD;
	}
	
	public function __destruct() {

	}
	
	public function get_hostgroups($PARAMS) {
		/* TBD */

		return array();
	}
	
	public function get_triggers($PARAMS) {
		$SERVICES = $this->api_fetch_array('services',$PARAMS['SERVICES']);
		$HOSTS = $this->api_fetch_array('hosts',$PARAMS['HOSTS']);

		return array_merge($SERVICES, $HOSTS);
	}
	
	public function get_eventdetails($PARAMS) {
		$EVENTDETAILS = $this->api_fetch_array('event.get',$PARAMS);
		foreach ($EVENTDETAILS[0]['acknowledges'] as $ACKED_KEY => $ACKED_FIELD) {
			if (!isset($EVENTDETAILS[0]['acknowledges'][$ACKED_KEY]['alias'])) {
				$EVENTDETAILS[0]['acknowledges'][$ACKED_KEY]['name'] = "Inaccessible UserID";
				$EVENTDETAILS[0]['acknowledges'][$ACKED_KEY]['surname'] = $EVENTDETAILS[0]['acknowledges'][$ACKED_KEY]['userid'];
			}
			else {
				if (!isset($EVENTDETAILS[0]['acknowledges'][$ACKED_KEY]['name'])) {
					$EVENTDETAILS[0]['acknowledges'][$ACKED_KEY]['name'] = '';
				}
				if (!isset($EVENTDETAILS[0]['acknowledges'][$ACKED_KEY]['surname'])) {
					$EVENTDETAILS[0]['acknowledges'][$ACKED_KEY]['surname'] = '';
				}
				if ($EVENTDETAILS[0]['acknowledges'][$ACKED_KEY]['name'] === '' AND $EVENTDETAILS[0]['acknowledges'][$ACKED_KEY]['surname'] === '') {
					$EVENTDETAILS[0]['acknowledges'][$ACKED_KEY]['name'] = $EVENTDETAILS[0]['acknowledges'][$ACKED_KEY]['alias'];
				}
			}
		}
		return $EVENTDETAILS;
	}
	
	public function add_acknowledge($EVENTID,$MESSAGE) {
		/* TBD */
	}
	
	private function api_fetch_array($METHOD, $PARAMS) {
		$RETURN_ARRAY = array();

		if ($METHOD === 'services') {
			$URL_EXTEND = 'monitoring/list/services?format=json&service_state!=Ok&' . $PARAMS;
			$DATA_JSON = $this->api_curl($this->URL . $URL_EXTEND);
			
			$DATA = json_decode($DATA_JSON, true);
			
			# forcing Icinga output into zabbix trigger format :P
			if ($DATA) {
				foreach($DATA as $ELEMENT) {
					$NEW_ELEMENT = array();
					$NEW_ELEMENT['description'] = $ELEMENT['service_display_name'] . ': ' . $ELEMENT['service_output'];
					$NEW_ELEMENT['lastchange'] = $ELEMENT['service_last_state_change'];
					$NEW_ELEMENT['hosts'] = array();
					$NEW_ELEMENT['hosts'][0]['name'] = $ELEMENT['host_name'];
					$NEW_ELEMENT['hosts'][0]['maintenance_status'] = $ELEMENT['service_in_downtime'];
					$NEW_ELEMENT['lastEvent'] = array();
					$NEW_ELEMENT['lastEvent']['acknowledged'] = $ELEMENT['service_acknowledged'];
					$NEW_ELEMENT['backend_icinga'] = true;
					
					
					// Mapping Icinga OK/WARN/CRIT to Zabbix severities
					if ($ELEMENT['service_state'] == '1') {
						$NEW_ELEMENT['priority'] = 2;
					}
					elseif ($ELEMENT['service_state'] == '2') {
						$NEW_ELEMENT['priority'] = 5;
					}
					else {
						$NEW_ELEMENT['priority'] = 0;
					}
					
					$RETURN_ARRAY[] = $NEW_ELEMENT;
				}
			}
		}
		
		elseif ($METHOD === 'hosts') {
			$URL_EXTEND = 'monitoring/list/hosts?format=json&host_state!=UP&' . $PARAMS;
			$DATA_JSON = $this->api_curl($this->URL . $URL_EXTEND);

			$DATA = json_decode($DATA_JSON, true);
			
			# forcing Icinga output into zabbix trigger format :P
			if ($DATA) {
				foreach($DATA as $ELEMENT) {
					$NEW_ELEMENT = array();
					$NEW_ELEMENT['description'] = $ELEMENT['host_output'];
					$NEW_ELEMENT['lastchange'] = $ELEMENT['host_last_state_change'];
					$NEW_ELEMENT['hosts'] = array();
					$NEW_ELEMENT['hosts'][0]['name'] = $ELEMENT['host_name'];
					$NEW_ELEMENT['hosts'][0]['maintenance_status'] = $ELEMENT['host_in_downtime'];
					$NEW_ELEMENT['lastEvent'] = array();
					$NEW_ELEMENT['lastEvent']['acknowledged'] = $ELEMENT['host_acknowledged'];
					$NEW_ELEMENT['backend_icinga'] = true;
					
					
					// Mapping Icinga OK/WARN/CRIT to Zabbix severities
					if ($ELEMENT['host_state'] == '1') {
						$NEW_ELEMENT['priority'] = 5;
					}
					else {
						$NEW_ELEMENT['priority'] = 0;
					}
					
					$RETURN_ARRAY[] = $NEW_ELEMENT;
				}
			}
		}

		return $RETURN_ARRAY;
	}

	private function api_curl($URL, $DATA = false) {
		$CURL = curl_init($URL);

		$HEADERS = array();
		$HEADERS[]  = 'Accept: application/json';
		$HEADERS[]  = 'User-Agent: ZbxWallboard';

		$CURL_OPTS = array(
			CURLOPT_RETURNTRANSFER => true,     // Allows for the return of a curl handle
			CURLOPT_TIMEOUT => 30,              // Maximum number of seconds to allow curl to process the entire request
			CURLOPT_CONNECTTIMEOUT => 5,        // Maximm number of seconds to establish a connection, shouldn't take 5 seconds
			CURLOPT_SSL_VERIFYHOST => false,    // Incase we have a fake SSL Cert...
			CURLOPT_SSL_VERIFYPEER => false,    // Ditto
			CURLOPT_FOLLOWLOCATION => false,    // Incase there's a redirect in place (moved zabbix url), follow it automatically
			CURLOPT_FRESH_CONNECT => true,      // Ensures we don't use a cached connection or response
			CURLOPT_ENCODING => 'gzip',
			CURLOPT_HTTPHEADER => $HEADERS,
			CURLOPT_CUSTOMREQUEST => 'GET',
			CURLOPT_HTTPAUTH => CURLOPT_HTTPAUTH,
			CURLOPT_USERPWD => $this->USERNAME . ":" . $this->PASSWORD
		);
		
		curl_setopt_array($CURL, $CURL_OPTS);
		$RESULT = @curl_exec($CURL);
		$RESULT_META = curl_getinfo($CURL);
		if ($RESULT_META['http_code'] != 200) {
			throw new Exception('Icinga API Error: [' . $RESULT_META['http_code'] . '] ' . $RESULT,12);
		}
		curl_close($CURL);
		return $RESULT;
	}
}

?>

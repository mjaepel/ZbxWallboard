<?php

declare(strict_types = 1);

namespace Modules\ZbxWallboard;

class CScreenWallboard extends \CScreenProblem {
	private $config;
	
	public function __construct(array $options = []) {
		parent::__construct($options);
		
		$config = select_config();

		$this->config = [
			'search_limit' => $config['search_limit'],
			'severity_color_0' => $config['severity_color_0'],
			'severity_color_1' => $config['severity_color_1'],
			'severity_color_2' => $config['severity_color_2'],
			'severity_color_3' => $config['severity_color_3'],
			'severity_color_4' => $config['severity_color_4'],
			'severity_color_5' => $config['severity_color_5'],
			'severity_name_0' => $config['severity_name_0'],
			'severity_name_1' => $config['severity_name_1'],
			'severity_name_2' => $config['severity_name_2'],
			'severity_name_3' => $config['severity_name_3'],
			'severity_name_4' => $config['severity_name_4'],
			'severity_name_5' => $config['severity_name_5']
		];
	}
	
	public function get() {
		$this->dataId = 'problem';

		$url = (new \CUrl('zabbix.php'))->setArgument('action', 'zbxwallboard.view');

		$data = self::getData($this->data['filter'], $this->config, true);
		$data = self::sortData($data, $this->config, $this->data['sort'], $this->data['sortorder']);

		$data = self::makeData($data, $this->data['filter'], true);

		if ($data['triggers']) {
			$triggerids = array_keys($data['triggers']);

			$db_triggers = \API::Trigger()->get([
				'output' => [],
				'selectDependencies' => ['triggerid'],
				'triggerids' => $triggerids,
				'preservekeys' => true
			]);

			foreach ($data['triggers'] as $triggerid => &$trigger) {
				$trigger['dependencies'] = array_key_exists($triggerid, $db_triggers)
					? $db_triggers[$triggerid]['dependencies']
					: [];
			}
			unset($trigger);
		}

		if ($data['problems']) {
			$triggers_hosts = getTriggersHostsList($data['triggers']);
		}

		$tile_container = new \CDiv();
		$tile_container
			->addStyle('border: 0px solid #ffffff;')
			->addStyle('display: block;')
			->addStyle('margin: 0px;')
			->addStyle('padding: 0px;')
			->addStyle('overflow: hidden;')
			->addStyle('box-sizing: border-box;')
			->addStyle('width: 100%;');
		
		if ($data['problems']) {
			$triggers_hosts = makeTriggersHostsList($triggers_hosts);
		}

		// Make trigger dependencies.
		if ($data['triggers']) {
			$dependencies = getTriggerDependencies($data['triggers']);
		}

		foreach ($data['problems'] as $eventid => $problem) {
			$data['actions']['severities'][$problem['eventid']] = array('count' => 0, 'original_severity' => True, 'current_severity' => True);
			$data['actions']['actions'][$problem['eventid']] = array('count' => 0, 'actions' => [], 'has_uncomplete_action' => False, 'has_failed_action' => False);

			$trigger = $data['triggers'][$problem['objectid']];

			$cell_clock = zbx_date2str(DATE_TIME_FORMAT_SECONDS, $problem['clock']);


			if ($problem['r_eventid'] != 0) {
				$value = TRIGGER_VALUE_FALSE;
			}
			else {
				$in_closing = false;

				foreach ($problem['acknowledges'] as $acknowledge) {
					if (($acknowledge['action'] & ZBX_PROBLEM_UPDATE_CLOSE) == ZBX_PROBLEM_UPDATE_CLOSE) {
						$in_closing = true;
						break;
					}
				}

				$value = $in_closing ? TRIGGER_VALUE_FALSE : TRIGGER_VALUE_TRUE;
			}

			$is_acknowledged = ($problem['acknowledged'] == EVENT_ACKNOWLEDGED);

			// Info.
			$info_icons = [];
			if (array_key_exists('suppression_data', $problem) && $problem['suppression_data']) {
				$info_icons[] = makeSuppressedProblemIcon($problem['suppression_data']);
			}
			$tile_icons = makeInformationList($info_icons);

			$description = array_key_exists($trigger['triggerid'], $dependencies)
				? makeTriggerDependencies($dependencies[$trigger['triggerid']])
				: [];
			$description[] = (new \CLinkAction($problem['name']))
				->setMenuPopup(\CMenuPopupHelper::getTrigger($trigger['triggerid'], $problem['eventid']));

			$tile = new \CDiv();
			$tile
				->addStyle('width: 150px;')
				->addStyle('display: block;')
				->addStyle('float: left;')
				->addStyle('margin: 5px;')
				->addStyle('box-sizing: border-box;')
				->addStyle('box-shadow: inset 0 0 1px #FFFFCC;')
				->addStyle('cursor: pointer;')
				->addStyle('position: relative;')
				->addStyle('overflow: hidden;')
				->addStyle('-webkit-user-select: none;')
				->addStyle('-moz-user-select: none;')
				->addStyle('-ms-user-select: none;')
				->addStyle('user-select: none;')
				->addStyle('overflow: visible;')
				->addStyle('width: 280px;')
				->addStyle('height: 150px;')
				->addStyle('margin-right: 0 !important;')
				->addStyle('padding-top: 10px;')
				->addStyle('box-shadow: 0 2px 4px rgba(0, 0, 0, 0.35);')
				->addClass(getSeverityStyle($problem['severity']))
				->addClass('zbxwallboard-tile');

			$tile_clock = new \CDiv();
			$tile_clock
				->addStyle('display: block;')
				->addStyle('margin-block-start: 1em;')
				->addStyle('margin-block-end: 1em;')
				->addStyle('margin-inline-start: 0px;')
				->addStyle('margin-inline-end: 0px;')
				->addStyle('text-align: center;')
				->addStyle("font-size: 0.875rem;")
				->addStyle('text-shadow: 2px 2px 4px rgba(255,255,255,.4);')
				->addStyle('-webkit-font-smoothing: antialiased;')
				->addStyle('font-smoothing: antialiased;')
				->addClass('zbxwallboard-text-normal');
			$tile_clock->addItem($cell_clock);
			$tile->addItem($tile_clock);

			$tile_host = new \CDiv();
			$tile_host
				->addStyle('display: block;')
				->addStyle('margin-block-start: 1em;')
				->addStyle('margin-block-end: 1em;')
				->addStyle('margin-inline-start: 0px;')
				->addStyle('margin-inline-end: 0px;')
				->addStyle('text-align: center;')
				->addStyle('font-size: 1.1rem;')
				->addStyle('text-shadow: 2px 2px 4px rgba(255,255,255,.4);')
				->addStyle('-webkit-font-smoothing: antialiased;')
				->addStyle('font-smoothing: antialiased;')
				->addClass('zbxwallboard-text-big');
			$tile_host->addItem($triggers_hosts[$trigger['triggerid']]);
			$tile->addItem($tile_host);
			
			$tile_description = new \CDiv();
			$tile_description
				->addStyle('display: block;')
				->addStyle('margin-block-start: 1em;')
				->addStyle('margin-block-end: 1em;')
				->addStyle('margin-inline-start: 0px;')
				->addStyle('margin-inline-end: 0px;')
				->addStyle('text-align: center;')
				->addStyle('font-size: 0.875rem;')
				->addStyle('text-shadow: 2px 2px 4px rgba(255,255,255,.4);')
				->addStyle('-webkit-font-smoothing: antialiased;')
				->addStyle('font-smoothing: antialiased;')
				->addClass('zbxwallboard-text-normal');
			$tile_description->addItem($description);
			$tile->addItem($tile_description);
			
			$tile_info = new \CDiv();
			$tile_info
				->addStyle('display: block;')
				->addStyle('margin-block-start: 1em;')
				->addStyle('margin-block-end: 1em;')
				->addStyle('margin-inline-start: 0px;')
				->addStyle('margin-inline-end: 0px;')
				->addStyle('text-align: center;')
				->addStyle("font-size: 0.875rem;")
				->addStyle('text-shadow: 2px 2px 4px rgba(255,255,255,.4);')
				->addStyle('-webkit-font-smoothing: antialiased;')
				->addStyle('font-smoothing: antialiased;')
				->addClass('zbxwallboard-text-normal');
			$tile_info->addItem($tile_icons);

			if ($is_acknowledged == 1) {
				$tile_info_ack = new \CSpan();
				$tile_info_ack
					->addClass('icon-ackn');
				$tile_info->addItem($tile_info_ack);
			}
			$tile->addItem($tile_info);
			
			$tile_container->addItem($tile);
		}

		return $this->getOutput($tile_container, true, $this->data);

	}
}

?>
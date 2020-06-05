<?php
/*
** Zabbix
** Copyright (C) 2001-2020 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

/*
** Copy of app/views/monitoring.problem.view.php (Zabbix 5.0.1)
** Changes:
**   - removed some filter options
**   - replaced CScreenBuilder by own CScreenWallboard class
**   - replaced includeJsFile
**   - renamed title "Problems" by "Wallboard"
**   - replaced action name "problem.view" by "zbxwallboard.view"
**/

$options = [
	'resourcetype' => SCREEN_RESOURCE_PROBLEM,
	'mode' => SCREEN_MODE_JS,
	'dataId' => 'problem',
	'page' => $data['page'],
	'data' => [
		'action' => $data['action'],
		'sort' => $data['sort'],
		'sortorder' => $data['sortorder'],
		'filter' => [
			'show' => $data['filter']['show'],
			'groupids' => $data['filter']['groupids'],
			'hostids' => $data['filter']['hostids'],
			'application' => $data['filter']['application'],
			'triggerids' => $data['filter']['triggerids'],
			'name' => $data['filter']['name'],
			'severities' => $data['filter']['severities'],
			'inventory' => $data['filter']['inventory'],
			'evaltype' => $data['filter']['evaltype'],
			'tags' => $data['filter']['tags'],
			'show_tags' => $data['filter']['show_tags'],
			'tag_name_format' => $data['filter']['tag_name_format'],
			'tag_priority' => Null,
			'show_suppressed' => True,
			'unacknowledged' => $data['filter']['unacknowledged'],
			'compact_view' => True,
			'show_timeline' => False,
			'details' => False,
			'highlight_row' => $data['filter']['highlight_row'],
			'show_opdata' => $data['filter']['show_opdata'],
			'age_state' => $data['filter']['age_state'],
			'age' => $data['filter']['age']
		]
	]
];

$screen = new Modules\ZbxWallboard\CScreenWallboard($options);

if ($data['action'] === 'zbxwallboard.view') {
	$this->addJsFile('gtlc.js');
	$this->addJsFile('multiselect.js');
	$this->addJsFile('layout.mode.js');

	$this->includeJsFile('monitoring.wallboard.view.zbx.js.php');
	$this->includeJsFile('monitoring.wallboard.view.js.php');

	$this->enableLayoutModes();
	$web_layout_mode = $this->getLayoutMode();

	$filter_column1 = (new CFormList())
		->addRow((new CLabel(_('Host groups'), 'filter_groupids__ms')),
			(new CMultiSelect([
				'name' => 'filter_groupids[]',
				'object_name' => 'hostGroup',
				'data' => $data['filter']['groups'],
				'popup' => [
					'parameters' => [
						'srctbl' => 'host_groups',
						'srcfld1' => 'groupid',
						'dstfrm' => 'zbx_filter',
						'dstfld1' => 'filter_groupids_',
						'real_hosts' => true,
						'enrich_parent_groups' => true
					]
				]
			]))->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH)
		)
		->addRow((new CLabel(_('Hosts'), 'filter_hostids__ms')),
			(new CMultiSelect([
				'name' => 'filter_hostids[]',
				'object_name' => 'hosts',
				'data' => $data['filter']['hosts'],
				'popup' => [
					'filter_preselect_fields' => [
						'hostgroups' => 'filter_groupids_'
					],
					'parameters' => [
						'srctbl' => 'hosts',
						'srcfld1' => 'hostid',
						'dstfrm' => 'zbx_filter',
						'dstfld1' => 'filter_hostids_'
					]
				]
			]))->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH)
		)
		->addRow(_('Application'), [
			(new CTextBox('filter_application', $data['filter']['application']))
				->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH),
			(new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
			(new CButton('filter_application_select', _('Select')))
				->onClick('return PopUp("popup.generic", jQuery.extend('.
					json_encode([
						'srctbl' => 'applications',
						'srcfld1' => 'name',
						'dstfrm' => 'zbx_filter',
						'dstfld1' => 'filter_application',
						'with_applications' => '1',
						'real_hosts' => '1'
					]).', getFirstMultiselectValue("filter_hostids_")), null, this);'
				)
				->addClass(ZBX_STYLE_BTN_GREY)
		])
		->addRow((new CLabel(_('Triggers'), 'filter_triggerids__ms')),
			(new CMultiSelect([
				'name' => 'filter_triggerids[]',
				'object_name' => 'triggers',
				'data' => $data['filter']['triggers'],
				'popup' => [
					'filter_preselect_fields' => [
						'hosts' => 'filter_hostids_'
					],
					'parameters' => [
						'srctbl' => 'triggers',
						'srcfld1' => 'triggerid',
						'dstfrm' => 'zbx_filter',
						'dstfld1' => 'filter_triggerids_',
						'monitored_hosts' => true,
						'with_monitored_triggers' => true,
						'noempty' => true
					]
				]
			]))->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH)
		)
		->addRow(_('Problem'),
			(new CTextBox('filter_name', $data['filter']['name']))->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH)
		)
		->addRow(_('Severity'),
			(new CSeverityCheckBoxList('filter_severities'))->setChecked($data['filter']['severities'])
		);

	$filter_age = (new CNumericBox('filter_age', $data['filter']['age'], 3, false, false, false))
		->setWidth(ZBX_TEXTAREA_NUMERIC_STANDARD_WIDTH);
	if ($data['filter']['age_state'] == 0) {
		$filter_age->setAttribute('disabled', 'disabled');
	}

	$filter_column1
		->addRow(_('Age less than'), [
			(new CCheckBox('filter_age_state'))
				->setChecked($data['filter']['age_state'] == 1)
				->onClick('javascript: jQuery("#filter_age").prop("disabled", !this.checked)'),
			(new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
			$filter_age,
			(new CDiv())->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
			_('days')
		]);

	$filter_inventory = $data['filter']['inventory'];
	if (!$filter_inventory) {
		$filter_inventory = [['field' => '', 'value' => '']];
	}

	$filter_inventory_table = new CTable();
	$filter_inventory_table->setId('filter-inventory');
	$i = 0;
	foreach ($filter_inventory as $field) {
		$filter_inventory_table->addRow([
			new CComboBox('filter_inventory['.$i.'][field]', $field['field'], null, $data['filter']['inventories']),
			(new CTextBox('filter_inventory['.$i.'][value]', $field['value']))->setWidth(ZBX_TEXTAREA_FILTER_SMALL_WIDTH),
			(new CCol(
				(new CButton('filter_inventory['.$i.'][remove]', _('Remove')))
					->addClass(ZBX_STYLE_BTN_LINK)
					->addClass('element-table-remove')
			))->addClass(ZBX_STYLE_NOWRAP)
		], 'form_row');

		$i++;
	}
	$filter_inventory_table->addRow(
		(new CCol(
			(new CButton('filter_inventory_add', _('Add')))
				->addClass(ZBX_STYLE_BTN_LINK)
				->addClass('element-table-add')
		))->setColSpan(3)
	);

	$filter_tags = $data['filter']['tags'];
	if (!$filter_tags) {
		$filter_tags = [['tag' => '', 'value' => '', 'operator' => TAG_OPERATOR_LIKE]];
	}

	$filter_tags_table = new CTable();
	$filter_tags_table->setId('filter-tags');

	$filter_tags_table->addRow(
		(new CCol(
			(new CRadioButtonList('filter_evaltype', (int) $data['filter']['evaltype']))
				->addValue(_('And/Or'), TAG_EVAL_TYPE_AND_OR)
				->addValue(_('Or'), TAG_EVAL_TYPE_OR)
				->setModern(true)
		))->setColSpan(4)
	);

	$i = 0;
	foreach ($filter_tags as $tag) {
		$filter_tags_table->addRow([
			(new CTextBox('filter_tags['.$i.'][tag]', $tag['tag']))
				->setAttribute('placeholder', _('tag'))
				->setWidth(ZBX_TEXTAREA_FILTER_SMALL_WIDTH),
			(new CRadioButtonList('filter_tags['.$i.'][operator]', (int) $tag['operator']))
				->addValue(_('Contains'), TAG_OPERATOR_LIKE)
				->addValue(_('Equals'), TAG_OPERATOR_EQUAL)
				->setModern(true),
			(new CTextBox('filter_tags['.$i.'][value]', $tag['value']))
				->setAttribute('placeholder', _('value'))
				->setWidth(ZBX_TEXTAREA_FILTER_SMALL_WIDTH),
			(new CCol(
				(new CButton('filter_tags['.$i.'][remove]', _('Remove')))
					->addClass(ZBX_STYLE_BTN_LINK)
					->addClass('element-table-remove')
			))->addClass(ZBX_STYLE_NOWRAP)
		], 'form_row');

		$i++;
	}
	$filter_tags_table->addRow(
		(new CCol(
			(new CButton('filter_tags_add', _('Add')))
				->addClass(ZBX_STYLE_BTN_LINK)
				->addClass('element-table-add')
		))->setColSpan(3)
	);

	$filter_column2 = (new CFormList())
		->addRow(_('Host inventory'), $filter_inventory_table)
		->addRow(_('Tags'), $filter_tags_table)
		->addRow(_('Show suppressed problems'), [
			(new CCheckBox('filter_show_suppressed'))
				->setChecked($data['filter']['show_suppressed'] == ZBX_PROBLEM_SUPPRESSED_TRUE),
			(new CDiv([
				(new CLabel(_('Show unacknowledged only'), 'filter_unacknowledged'))
					->addClass(ZBX_STYLE_SECOND_COLUMN_LABEL),
				(new CCheckBox('filter_unacknowledged'))
					->setChecked($data['filter']['unacknowledged'] == 1)
			]))->addClass(ZBX_STYLE_TABLE_FORMS_SECOND_COLUMN)
		]);

	$filter = (new CFilter((new CUrl('zabbix.php'))->setArgument('action', 'zbxwallboard.view')))
		->setProfile($data['profileIdx'])
		->setActiveTab($data['active_tab'])
		->addFormItem((new CVar('action', 'zbxwallboard.view'))->removeId());

	$filter->addFilterTab(_('Filter'), [$filter_column1, $filter_column2]);

	$widget = (new CWidget())
		->setTitle(_('Wallboard'))
		->setWebLayoutMode($web_layout_mode)
		->setControls(
			(new CTag('nav', true,
				(new CList())
					->addItem(get_icon('kioskmode', ['mode' => $web_layout_mode]))
			))->setAttribute('aria-label', _('Content controls'))
		);

	if ($web_layout_mode == ZBX_LAYOUT_NORMAL) {
		$widget->addItem($filter);
	}

	$widget
		->addItem($screen->get())
		->show();
}
else {
	echo $screen->get();
}

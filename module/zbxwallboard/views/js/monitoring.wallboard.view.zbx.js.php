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
** Zabbix Flickerfree JS modified for ZbxWallboard
** Changes:
**  - other jsrpc URL
**  - added scaling call to refresh function
**  - removed unused functions of refreshing other components
**/
?>

<script type="text/x-jquery-tmpl" id="filter-inventory-row">
	<?= (new CRow([
			new CComboBox('filter_inventory[#{rowNum}][field]', null, null, $data['filter']['inventories']),
			(new CTextBox('filter_inventory[#{rowNum}][value]'))->setWidth(ZBX_TEXTAREA_FILTER_SMALL_WIDTH),
			(new CCol(
				(new CButton('filter_inventory[#{rowNum}][remove]', _('Remove')))
					->addClass(ZBX_STYLE_BTN_LINK)
					->addClass('element-table-remove')
			))->addClass(ZBX_STYLE_NOWRAP)
		]))
			->addClass('form_row')
			->toString()
	?>
</script>

<script type="text/x-jquery-tmpl" id="filter-tag-row-tmpl">
	<?= (new CRow([
			(new CTextBox('filter_tags[#{rowNum}][tag]'))
				->setAttribute('placeholder', _('tag'))
				->setWidth(ZBX_TEXTAREA_FILTER_SMALL_WIDTH),
			(new CRadioButtonList('filter_tags[#{rowNum}][operator]', TAG_OPERATOR_LIKE))
				->addValue(_('Contains'), TAG_OPERATOR_LIKE)
				->addValue(_('Equals'), TAG_OPERATOR_EQUAL)
				->setModern(true),
			(new CTextBox('filter_tags[#{rowNum}][value]'))
				->setAttribute('placeholder', _('value'))
				->setWidth(ZBX_TEXTAREA_FILTER_SMALL_WIDTH),
			(new CCol(
				(new CButton('filter_tags[#{rowNum}][remove]', _('Remove')))
					->addClass(ZBX_STYLE_BTN_LINK)
					->addClass('element-table-remove')
			))->addClass(ZBX_STYLE_NOWRAP)
		]))
			->addClass('form_row')
			->toString()
	?>
</script>

<script type="text/javascript">
	jQuery(function($) {
		$(function() {
			$('#filter-inventory').dynamicRows({template: '#filter-inventory-row'});
			$('#filter-tags').dynamicRows({template: '#filter-tag-row-tmpl'});
		});

		$('#filter_show').change(function() {
			var	filter_show = jQuery('input[name=filter_show]:checked').val();

			$('#filter_age').closest('li').toggle(filter_show == <?= TRIGGERS_OPTION_RECENT_PROBLEM ?>
				|| filter_show == <?= TRIGGERS_OPTION_IN_PROBLEM ?>);
		});

		$('#filter_show').trigger('change');

		$('#filter_compact_view').change(function() {
			if ($(this).is(':checked')) {
				$('#filter_show_timeline, #filter_details').prop('disabled', true);
				$('input[name=filter_show_opdata]').prop('disabled', true);
				$('#filter_highlight_row').prop('disabled', false);
			}
			else {
				$('#filter_show_timeline, #filter_details').prop('disabled', false);
				$('input[name=filter_show_opdata]').prop('disabled', false);
				$('#filter_highlight_row').prop('disabled', true);
			}
		});

		$('#filter_show_tags').change(function() {
			var disabled = $(this).find('[value = "<?= PROBLEMS_SHOW_TAGS_NONE ?>"]').is(':checked');
			$('#filter_tag_priority').prop('disabled', disabled);
			$('#filter_tag_name_format input').prop('disabled', disabled);
		});

		$(document).on({
			mouseenter: function() {
				if ($(this)[0].scrollWidth > $(this)[0].offsetWidth) {
					$(this).attr({title: $(this).text()});
				}
			},
			mouseleave: function() {
				if ($(this).is('[title]')) {
					$(this).removeAttr('title');
				}
			}
		}, 'table.<?= ZBX_STYLE_COMPACT_VIEW ?> a.<?= ZBX_STYLE_LINK_ACTION ?>');

		$.subscribe('acknowledge.create', function(event, response) {
			// Clear all selected checkboxes in Monitoring->Problems.
			if (chkbxRange.prefix === 'problem') {
				chkbxRange.checkObjectAll(chkbxRange.pageGoName, false);
				chkbxRange.clearSelectedOnFilterChange();
			}

			window.flickerfreeScreen.refresh('problem');

			clearMessages();
			addMessage(makeMessageBox('good', response.message, null, true));
		});

		$(document).on('submit', '#problem_form', function(e) {
			e.preventDefault();

			var eventids = $('[id^="eventids_"]:checked', $(this)).map(function() {
					return $(this).val();
				}).get();

			acknowledgePopUp({eventids: eventids}, this);
		});
	});
</script>

<script type="text/javascript">
(function($) {

	window.flickerfreeScreen = {

		screens: [],
		responsiveness: 10000,

		/**
		 * Set or reset UI in progress state for element with id.
		 *
		 * @param {boolean} in_progress
		 * @param {string}  id
		 */
		setElementProgressState: function(id, in_progress) {
			var elm = $('#flickerfreescreen_'+id);

			if (in_progress) {
				elm.addClass('is-loading is-loading-fadein delayed-15s');
			}
			else {
				elm.removeClass('is-loading is-loading-fadein delayed-15s');
			}
		},

		add: function(screen) {
			// switch off time control refreshing using full page refresh
			timeControl.refreshPage = false;

			// init screen item
			this.screens[screen.id] = screen;
			this.screens[screen.id].interval = (screen.interval > 0) ? screen.interval * 1000 : 0;
			this.screens[screen.id].timestamp = 0;
			this.screens[screen.id].timestampResponsiveness = 0;
			this.screens[screen.id].timestampActual = 0;
			this.screens[screen.id].isRefreshing = false;
			this.screens[screen.id].isReRefreshRequire = false;
			this.screens[screen.id].error = 0;

			// SCREEN_RESOURCE_MAP
			if (screen.resourcetype == 2) {
				this.screens[screen.id].data = new SVGMap(this.screens[screen.id].data);
				$(screen.data.container).attr({'aria-label': screen.data.options.aria_label, 'tabindex': 0})
					.find('svg').attr('aria-hidden', 'true');
			}

			// init refresh plan
			if (screen.isFlickerfree && screen.interval > 0) {
				this.screens[screen.id].timeoutHandler = window.setTimeout(
					function() {
						window.flickerfreeScreen.refresh(screen.id);
					},
					this.screens[screen.id].interval
				);
			}
		},

		remove: function(screen) {
			if (typeof screen.id !== 'undefined' && typeof this.screens[screen.id] !== 'undefined') {
				if (typeof this.screens[screen.id].timeoutHandler !== 'undefined') {
					window.clearTimeout(this.screens[screen.id].timeoutHandler);
				}

				delete this.screens[screen.id];
			}
		},

		refresh: function(id) {
			var screen = this.screens[id];

			if (empty(screen.id)) {
				return;
			}

			// Do not update screen if displaying static hintbox.
			if ($('#flickerfreescreen_' + id + ' [data-expanded="true"]').length) {
				if (screen.isFlickerfree && screen.interval > 0) {
					clearTimeout(screen.timeoutHandler);
					screen.timeoutHandler = setTimeout(() => flickerfreeScreen.refresh(id), 1000);
				}

				return;
			}

			var type_params = {
					'24': ['mode', 'resourcetype', 'data', 'page']
				},
				params_index = type_params[screen.resourcetype] ? screen.resourcetype : 'default';
				ajax_url = new Curl('zabbix.php?action=zbxwallboard.jsrpc'),
				self = this;

			ajax_url.setArgument('type', 9); // PAGE_TYPE_TEXT
			ajax_url.setArgument('method', 'screen.get');
			// TODO: remove, do not use timestamp passing to server and back to ensure newest content will be shown.
			ajax_url.setArgument('timestamp', screen.timestampActual);

			$.each(type_params[params_index], function (i, name) {
				ajax_url.setArgument(name, empty(screen[name]) ? null : screen[name]);
			});

			// set actual timestamp
			screen.timestampActual = new CDate().getTime();

			// timeline params
			// SCREEN_RESOURCE_HTTPTEST_DETAILS, SCREEN_RESOURCE_DISCOVERY, SCREEN_RESOURCE_HTTPTEST
			if ($.inArray(screen.resourcetype, [21, 22, 23]) === -1) {
				ajax_url.setArgument('from', screen.timeline.from);
				ajax_url.setArgument('to', screen.timeline.to);
			}

			self.refreshHtml(id, ajax_url);

			// set next refresh execution time
			if (screen.isFlickerfree && screen.interval > 0) {
				clearTimeout(screen.timeoutHandler);
				screen.timeoutHandler = setTimeout(() => flickerfreeScreen.refresh(id), screen.interval);
			}
		},

		refreshHtml: function(id, ajaxUrl) {
			var screen = this.screens[id],
				request_start = new CDate().getTime();

			if (screen.isRefreshing) {
				this.calculateReRefresh(id);
			}
			else {
				screen.isRefreshing = true;
				screen.timestampResponsiveness = new CDate().getTime();
				this.setElementProgressState(id, true);

				var ajaxRequest = $.ajax({
					url: ajaxUrl.getUrl(),
					type: 'post',
					cache: false,
					data: {},
					dataType: 'html',
					success: function(html) {
						var html = $(html);

						// Replace existing markup with server response.
						if (request_start > screen.timestamp) {
							screen.timestamp = request_start;
							screen.isRefreshing = false;

							$('.wrapper .msg-bad').remove();
							$('#flickerfreescreen_' + id).replaceWith(html);
							$('.wrapper .msg-bad').insertBefore('.wrapper main');

							var $window = $(window);

							if ($('.sidebar').length > 0) {
								$('.zbxwallboard-tile').scaledgrid(function() {
									return [$window.width() - $('.sidebar').width() - 40, $window.height() - $('header').height() - $('.ui-tabs').height() - $('footer').height() - 100];
								});
							} 
							else {
								$('.zbxwallboard-tile').scaledgrid(function() {
									return [$window.width() - 40, $window.height() - 20];
								});
							};
							
							window.flickerfreeScreen.setElementProgressState(id, false);
						}
						else if (!html.length) {
							$('#flickerfreescreen_' + id).remove();
						}

						chkbxRange.init();
					},
					error: function() {
						window.flickerfreeScreen.calculateReRefresh(id);
					}
				});

				$.when(ajaxRequest).always(function() {
					if (screen.isReRefreshRequire) {
						screen.isReRefreshRequire = false;
						window.flickerfreeScreen.refresh(id);
					}
				});
			}
		},

		calculateReRefresh: function(id) {
			var screen = this.screens[id],
				time = new CDate().getTime();

			if (screen.timestamp + this.responsiveness < time
					&& screen.timestampResponsiveness + this.responsiveness < time) {
				// take of busy flags
				screen.isRefreshing = false;
				screen.isReRefreshRequire = false;

				// refresh anyway
				window.flickerfreeScreen.refresh(id);
			}
			else {
				screen.isReRefreshRequire = true;
			}
		}
	};
}(jQuery));
</script>

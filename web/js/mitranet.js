/*
 * mitranet.js - MitraNet WebUI Core Interactivity
 * Licensed under the Apache License, Version 2.0.
 */

$(function() {
	// Global Modal configuration: prevent accidental auto-close when clicking inside or on backdrop
	if ($.fn.modal) {
		$.fn.modal.Constructor.DEFAULTS.backdrop = 'static';
		$.fn.modal.Constructor.DEFAULTS.keyboard = false;
	}

	// Attach collapsable behaviour to select options
	(function()
	{
		var selects = $('select[data-toggle="collapse"]');

		selects.on('change', function(){
			var options = $(this).find('option');
			var selectedValue = $(this).find(':selected').val();

			options.each(function(){
				if ($(this).val() == selectedValue)
					return;

				targets = $('.toggle-'+ $(this).val() +'.in:not(.toggle-'+ selectedValue +')');

				// Hide related collapsables which are visible (.in)
				targets.collapse('hide');

				// Disable all invisible inputs
				targets.find(':input').prop('disabled', true);
			});

			$('.toggle-' + selectedValue).collapse('show').find(':input').prop('disabled', false);
		});

		// Trigger change to open currently selected item
		selects.trigger('change');
	})();


	// Add +/- buttons to certain Groups; to allow adding multiple entries
	// This time making the buttons col-2 wide so they can fit on the same line as the
	// rest of the group (providing the total width of the group is col-8 or less)
	(function()
	{
		var groups = $('div.form-group.user-duplication-horiz');
		var controlsContainer = $('<div class="col-sm-2"></div>');
		var plus = $('<a class="btn btn-sm btn-success"><i class="fa-solid fa-plus icon-embed-btn"></i>Add</a>');
		var minus = $('<a class="btn btn-sm btn-warning"><i class="fa-solid fa-trash-can icon-embed-btn"></i>Delete</a>');

		minus.on('click', function(){
			$(this).parents('div.form-group').remove();
		});

		plus.on('click', function(){
			var group = $(this).parents('div.form-group');

			var clone = group.clone(true);
			clone.find('*').val('');
			clone.appendTo(group.parent());
		});

		groups.each(function(idx, group){
			var controlsClone = controlsContainer.clone(true).appendTo(group);
			minus.clone(true).appendTo(controlsClone);

			if (group == group.parentNode.lastElementChild)
				plus.clone(true).appendTo(controlsClone);
		});
	})();

	// Add +/- buttons to certain Groups; to allow adding multiple entries
	(function()
	{
		var groups = $('div.form-group.user-duplication');
		var controlsContainer = $('<div class="col-sm-10 col-sm-offset-2 controls"></div>');
		var plus = $('<a class="btn btn-xs btn-success"><i class="fa-solid fa-plus icon-embed-btn"></i>Add</a>');
		var minus = $('<a class="btn btn-xs btn-warning"><i class="fa-solid fa-trash-can icon-embed-btn"></i>Delete</a>');

		minus.on('click', function(){
			$(this).parents('div.form-group').remove();
		});

		plus.on('click', function(){
			var group = $(this).parents('div.form-group');

			var clone = group.clone(true);
			clone.find('*').removeAttr('value');
			clone.appendTo(group.parent());
		});

		groups.each(function(idx, group){
			var controlsClone = controlsContainer.clone(true).appendTo(group);
			minus.clone(true).appendTo(controlsClone);

			if (group == group.parentNode.lastElementChild)
				plus.clone(true).appendTo(controlsClone);
		});
	})();

	// Add +/- buttons to certain Groups; to allow adding multiple entries
	(function()
	{
		var groups = $('div.form-listitem.user-duplication');
		var fg = $('<div class="form-group"></div>');
		var controlsContainer = $('<div class="col-sm-10 col-sm-offset-2 controls"></div>');
		var plus = $('<a class="btn btn-xs btn-success"><i class="fa-solid fa-plus icon-embed-btn"></i>Add</a>');
		var minus = $('<a class="btn btn-xs btn-warning"><i class="fa-solid fa-trash-can icon-embed-btn"></i>Delete</a>');

		minus.on('click', function(){
			var groups = $('div.form-listitem.user-duplication');
			if (groups.length > 1) {
				$(this).parents('div.form-listitem').remove();
			}
		});

		plus.on('click', function(){
			var group = $(this).parents('div.form-listitem');
			var clone = group.clone(true);
			bump_input_id(clone);
			clone.appendTo(group.parent());
		});

		groups.each(function(idx, group){
			var fgClone = fg.clone(true).appendTo(group);
			var controlsClone = controlsContainer.clone(true).appendTo(fgClone);
			minus.clone(true).appendTo(controlsClone);
			plus.clone(true).appendTo(controlsClone);
		});
	})();

	// Automatically change IpAddress mask selectors to 128/32 options for IPv6/IPv4 addresses
	$('span.pfIpMask + select').each(function (idx, select){
		var input = $(select).prevAll('input[type=text]');

		input.on('change', function(e){
			var isV6 = (input.val().indexOf(':') != -1), min = 0, max = 128;

			if (!isV6)
				max = 32;

			if (input.val() == "") {
				return;
			}

			var attr = $(select).attr('disabled');

			// Don't do anything if the mask selector is disabled
			if (typeof attr === typeof undefined || attr === false) {
				// Eat all of the options with a value greater than max. We don't want them to be available
				while (select.options[0].value > max)
					select.remove(0);

				if (select.options.length < max) {
					for (var i=select.options.length; i<=max; i++)
						select.options.add(new Option(i, i), 0);

					if (isV6) {
						// Make sure index 0 is selected otherwise it will stay in "32" for V6
						select.options.selectedIndex = "0";
					}
				}
			}
		});

		// Fire immediately
		input.change();
	});

	// Add confirm to all btn-danger buttons and fa-trash-can icons
	// Use element title in the confirmation message, or if not available
	// the element value
	$('.btn-danger, .fa-trash-can').on('click', function(e){
		if (!($(this).hasClass('no-confirm')) && !($(this).hasClass('icon-embed-btn'))) {
			// Anchors using the automatic get2post system (mitranetHelpers.js) perform the confirmation dialog
			// in those functions
			var attr = $(this).attr('usepost');
			if (typeof attr === typeof undefined || attr === false) {
				var msg = $.trim(this.textContent).toLowerCase();

				if (!msg)
					var msg = $.trim(this.value).toLowerCase();

				var q = 'Are you sure you wish to '+ msg +'?';

				if ($(this).attr('title') != undefined)
					q = 'Are you sure you wish to '+ $(this).attr('title').toLowerCase() + '?';

				if (!confirm(q)) {
					e.preventDefault();
					e.stopPropagation();	// Don't leave ancestor(s) selected.
				}
			}
		}
	});

	// Add toggle-all when there are multiple checkboxes
	$('.control-label + .checkbox.multi').each(function() {
		var a = $('<a name="btntoggleall" class="btn btn-xs btn-info"><i class="fa-regular fa-square-check icon-embed-btn"></i>Toggle All</a>');

		a.on('click', function() {
			var wrap = $(this).parents('.form-group').find('.checkbox.multi'),
				all = wrap.find('input[type=checkbox]'),
				checked = wrap.find('input[type=checkbox]:checked');

			all.prop('checked', (all.length != checked.length));
		});

		if ( ! $(this).parent().hasClass("notoggleall")) {
			a.appendTo($(this));
		}
	});

	// The need to NOT hide the advanced options if the elements therein are not set to the system
	// default values makes it better to handle advanced option hiding in each PHP file so this is being
	// disabled for now by changing the class name it acts on to "auto-advanced"

	// Hide advanced inputs by default
	if ($('.auto-advanced').length > 0)
	{
		var advButt = $('<a id="toggle-advanced" class="btn btn-default">toggle advanced options</a>');
		advButt.on('click', function() {
			$('.advanced').parents('.form-group').collapse('toggle');
		});

		advButt.insertAfter($('#save'));

		$('.auto-advanced').parents('.form-group').collapse({toggle: true});
	}

	var originalLeave = $.fn.popover.Constructor.prototype.leave;
	$.fn.popover.Constructor.prototype.leave = function(obj){
	  var self = obj instanceof this.constructor ?
	    obj : $(obj.currentTarget)[this.type](this.getDelegateOptions()).data('bs.' + this.type)
	  var container, timeout;

	  originalLeave.call(this, obj);

	  if (self.$tip && self.$tip.length) {
	    container = self.$tip;
	    timeout = self.timeout;
	    container.one('mouseenter', function(){
	      //We entered the actual popover - call off the dogs
	      clearTimeout(timeout);
	      //Let's monitor popover content instead
	      container.one('mouseleave', function(){
	        $.fn.popover.Constructor.prototype.leave.call(self, self);
	      });
	    })
	  }
	};

	// Bootstrap 3.4.1 sanitizes the contents of popovers even when data-html is specified
	// Add table tags to the list of elements permitted by the sanitizer
	var defaultWhiteList = $.fn.tooltip.Constructor.DEFAULTS.whiteList

	defaultWhiteList.table = []
	defaultWhiteList.thead = []
	defaultWhiteList.tr = ["class"]
	defaultWhiteList.th = ["style"]
	defaultWhiteList.tbody = []
	defaultWhiteList.td = ["style"]

	// Enable popovers globally
	$('[data-toggle="popover"]').popover({ delay: {show: 50, hide: 400} });

	// Force correct initial state for toggleable checkboxes
	$('input[type=checkbox][data-toggle="collapse"]:not(:checked)').each(function() {
		$( $(this).data('target') ).addClass('collapse');
	});

	$('input[type=checkbox][data-toggle="disable"]:not(:checked)').each(function() {
		$( $(this).data('target') ).prop('disabled', true);
	});

	$('.table-rowdblclickedit>tbody>tr').dblclick(function () {
		$(this).find(".fa-pencil")[0].click();
	});

	// Focus first input
	$(':input:enabled:visible:first').focus();

	$(".resizable").each(function() {
		$(this).css('height', 80).resizable({minHeight: 80, minWidth: 200}).parent().css('padding-bottom', 0);
		$(this).css('height', 78);
	});

	// Run in-page defined events
	if (window.events && Array.isArray(window.events)) {
		while (window.events.length > 0) {
			var func = window.events.shift();
			if (typeof func === 'function') {
				func();
			}
		}
	}
});

// Implement data-toggle=disable
// Source: https://github.com/visionappscz/bootstrap-ui/blob/master/src/js/disable.js
;(function($, window, document) {
	'use strict';

	var Disable = function($element) {
		this.$element = $element;
	};

	Disable.prototype.toggle = function() {
		this.$element.prop('disabled', !this.$element.prop('disabled'));
	};

	function Plugin(options) {
		$(document).trigger('toggle.sui.disable');

		this.each(function() {
			var $this = $(this);
			var data = $this.data('sui.disable');

			if (!data) {
				$this.data('sui.disable', (data = new Disable($this)));
			}

			if (options === 'toggle') {
				data.toggle();
			}
		});

		$(document).trigger('toggled.sui.disable');

		return this;
	}

	var old = $.fn.disable;

	$.fn.disable = Plugin;
	$.fn.disable.Constructor = Disable;

	$.fn.disable.noConflict = function() {
		$.fn.disable = old;
		return this;
	};

	(function(Plugin, $, window) {
		$(window).on("load", function() {
			var $controls = $('[data-toggle=disable]');

			$controls.each(function() {
				var $this = $(this);
				var eventType = $this.data('disable-event');
				if (!eventType) {
					eventType = 'change';
				}
				$this.on(eventType + '.sui.disable.data-api', function() {
					Plugin.call($($this.data('target')), 'toggle');
				});
			});
		});
	}(Plugin, $, window, document));
}(jQuery, window, document));

/* ==========================================================================
 * Interface Assignments Table — enhancements
 * Applies to .iface-assign-table (interfaces_assign.php)
 * ========================================================================== */

(function ($) {
	'use strict';

	// Only run on pages that have the interface assignment table
	if (!$('.iface-assign-table').length) return;

	/* ------------------------------------------------------------------
	 * 1. Auto-refresh with countdown indicator
	 * ------------------------------------------------------------------ */
	var REFRESH_SECS = 30;
	var $refreshBtn  = $('a[href="interfaces.php"][role="button"]').first();

	if ($refreshBtn.length) {
		var $badge = $('<span class="badge iface-refresh-badge">' + REFRESH_SECS + 's</span>');
		$refreshBtn.append('\u00a0').append($badge);

		var remaining = REFRESH_SECS;
		var timer = setInterval(function () {
			remaining--;
			$badge.text(remaining + 's');
			if (remaining <= 5) $badge.addClass('iface-refresh-badge-urgent');
			if (remaining <= 0) {
				clearInterval(timer);
				window.location.href = 'interfaces.php';
			}
		}, 1000);

		// Cancel auto-refresh if user clicks a button or form
		$('form, .btn').one('click', function () {
			clearInterval(timer);
			$badge.text('\u2014').removeClass('iface-refresh-badge-urgent');
		});
	}

	/* ------------------------------------------------------------------
	 * 2. Row click → navigate to interface config page
	 *    Skip clicks inside the Actions column
	 * ------------------------------------------------------------------ */
	$('.iface-assign-table tbody tr').each(function () {
		var $row  = $(this);
		var $link = $row.find('a.iface-name-link').first();
		if (!$link.length) return;

		$row.css('cursor', 'pointer').on('click', function (e) {
			if ($(e.target).closest('td.iface-col-actions, button, form, a').length) return;
			window.location.href = $link.attr('href');
		});
	});
	$('.iface-assign-table tbody td.iface-col-actions').css('cursor', 'default');

	/* ------------------------------------------------------------------
	 * 3. Column header sort
	 * ------------------------------------------------------------------ */
	var sortDir = {};

	$('.iface-assign-table thead th').each(function (colIdx) {
		$(this).css({ cursor: 'pointer', userSelect: 'none' });
	}).on('click', function () {
		var colIdx = $(this).index();
		var asc    = !sortDir[colIdx];
		sortDir    = {};
		sortDir[colIdx] = asc;

		$('.iface-assign-table thead th .sort-ind').remove();
		$(this).append('<span class="sort-ind text-muted" style="font-size:10px">' + (asc ? ' \u25b2' : ' \u25bc') + '</span>');

		var $tbody = $('.iface-assign-table tbody');
		var rows   = $tbody.find('tr').toArray();

		rows.sort(function (a, b) {
			var av = $('td', a).eq(colIdx).text().trim().toLowerCase();
			var bv = $('td', b).eq(colIdx).text().trim().toLowerCase();
			var an = parseFloat(av.replace(/[^0-9.]/g, ''));
			var bn = parseFloat(bv.replace(/[^0-9.]/g, ''));
			if (!isNaN(an) && !isNaN(bn)) return asc ? an - bn : bn - an;
			return asc ? av.localeCompare(bv) : bv.localeCompare(av);
		});

		$.each(rows, function (i, row) { $tbody.append(row); });
	});

	/* ------------------------------------------------------------------
	 * 4. Traffic badge tooltips
	 * ------------------------------------------------------------------ */
	$('.iface-assign-table .badge-traffic, .iface-assign-table .badge-pkts').tooltip({
		placement: 'top',
		trigger:   'hover',
		container: 'body'
	});

}(jQuery));

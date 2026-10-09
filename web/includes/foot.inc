<?php
/*
 * foot.inc
 *
 * Adapted from pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2013 BSD Perimeter
 * Copyright (c) 2013-2016 Electric Sheep Fencing
 * Copyright (c) 2014-2026 Rubicon Communications, LLC (Netgate)
 * Adapted for MitraNet Rinjani 1.0.2 Native Linux Appliance
 *
 * Licensed under the Apache License, Version 2.0.
 */
?>
	</div>
	<footer class="footer">
		<div class="footer-inner">
			<div class="footer-left">
				<span class="footer-brand">MitraNet</span>
				<span class="footer-badge">Community Edition</span>
				<span class="footer-sep">&bull;</span>
				<span class="footer-text">developed &amp; maintained for <a target="_blank" href="https://github.com/qomaruddindjamal/mitranet" class="footer-link">Linux Appliance</a></span>
			</div>
			<div class="footer-right">
				<span class="footer-copyright">&copy; ESF 2004 - 2026</span>
				<span class="footer-sep">&bull;</span>
				<a target="_blank" href="https://github.com/qomaruddindjamal/mitranet" class="footer-link">View License</a>
				<a id="tpr" href="#" title="Top of page" class="footer-back-top"><i class="fa-solid fa-chevron-up"></i></a>
			</div>
		</div>
	</footer>

	<script src="/vendor/jquery/jquery-3.7.1.min.js"></script>
	<script src="/vendor/jquery-ui/jquery-ui-1.13.2.min.js"></script>
	<script src="/vendor/bootstrap/js/bootstrap.min.js"></script>
	<script src="/vendor/sweetalert2/sweetalert2.all.min.js"></script>
	<script src="/js/mitranet-ui.js"></script>
	<script src="/js/mitranet.js"></script>
	<script src="/js/mitranetHelpers.js"></script>
	<script src="/vendor/sortable/sortable.js"></script>

	<script type="text/javascript">
	//<![CDATA[
		$(document).ready(function() {
			var isDesktop = function() { return $(window).width() >= 992; };

			// Restore collapsed sidebar state on desktop
			var isCollapsed = localStorage.getItem('mitranet_sidebar_collapsed') === 'true';
			if (isCollapsed && isDesktop()) {
				$('body').addClass('sidebar-collapsed');
			}

			// Sidebar toggle button (Hamburger)
			$('#sidebar-toggle-btn').on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				if (isDesktop()) {
					$('body').toggleClass('sidebar-collapsed');
					localStorage.setItem('mitranet_sidebar_collapsed', $('body').hasClass('sidebar-collapsed'));
					// Hide any open flyouts on collapse toggle
					$('.sidebar-item').removeClass('open pinned').find('.sidebar-flyout').hide();
				} else {
					$('body').toggleClass('sidebar-mobile-open');
				}
			});

			// Mobile close button and backdrop
			$('#sidebar-close-btn, #sidebar-backdrop').on('click', function(e) {
				e.preventDefault();
				$('body').removeClass('sidebar-mobile-open');
			});

			// Desktop: Show & align sidebar drop-right panel
			function showDropRight($item) {
				if (!isDesktop()) return;
				var $flyout = $item.children('.sidebar-flyout');
				if (!$flyout.length) return; // Direct links (KVM / Terminal) have no flyout
				$('.sidebar-item').not($item).removeClass('open').find('.sidebar-flyout').hide();
				$item.addClass('open');
				if ($flyout.length) {
					var itemRect = $item[0].getBoundingClientRect();
					var sidebarWidth = $('#pf-sidebar').outerWidth();
					var flyoutTop = itemRect.top;

					$flyout.css({
						'display': 'block',
						'position': 'fixed',
						'left': sidebarWidth + 'px',
						'top': flyoutTop + 'px',
						'z-index': '99999'
					});

					// Smart boundary detection: keep flyout above fixed footer and in viewport
					var flyoutHeight = $flyout.outerHeight();
					var footerHeight = $('.footer').outerHeight() || 38;
					var maxBottom = $(window).height() - footerHeight - 4;
					if (flyoutTop + flyoutHeight > maxBottom) {
						var adjustedTop = Math.max(52, maxBottom - flyoutHeight);
						$flyout.css('top', adjustedTop + 'px');
					}
				}
			}

			function hideDropRight($item) {
				if (!isDesktop()) return;
				if (!$item.hasClass('pinned')) {
					$item.removeClass('open');
					$item.children('.sidebar-flyout').hide();
				}
			}

			// Desktop: Hover handling
			$('.sidebar-item').on('mouseenter', function() {
				if (isDesktop()) {
					showDropRight($(this));
				}
			}).on('mouseleave', function() {
				if (isDesktop()) {
					hideDropRight($(this));
				}
			});

			// Click handler: Mobile accordion toggle OR Desktop click-to-pin flyout
			$('.sidebar-link').on('click', function(e) {
				if ($(this).hasClass('direct-link')) {
					if (!isDesktop()) {
						$('body').removeClass('sidebar-mobile-open');
					}
					return; // Direct links navigate directly
				}
				e.preventDefault();
				var $item = $(this).closest('.sidebar-item');

				if (isDesktop()) {
					var isPinned = $item.hasClass('pinned');
					$('.sidebar-item').removeClass('pinned open').find('.sidebar-flyout').hide();
					if (!isPinned) {
						$item.addClass('pinned');
						showDropRight($item);
					}
				} else {
					var wasOpen = $item.hasClass('open');
					$('.sidebar-item').removeClass('open').find('.sidebar-flyout').hide();
					if (!wasOpen) {
						$item.addClass('open');
						$item.find('.sidebar-flyout').show();
					}
				}
			});

			// Close pinned drop-right when clicking outside on desktop
			// NOTE: jangan tutup flyout saat link di dalamnya diklik (biarkan navigasi terjadi dulu)
			$(document).on('click', function(e) {
				if (!isDesktop()) return;
				var $target = $(e.target);
				// Jika klik terjadi di dalam sidebar atau flyout (termasuk link navlnk), jangan tutup
				if ($target.closest('#pf-sidebar, .sidebar-flyout').length) return;
				$('.sidebar-item').removeClass('pinned open').find('.sidebar-flyout').hide();
			});

			// Close mobile drawer when tapping any submenu link (desktop: biarkan flyout tetap ada saat navigasi)
			$('.sidebar-submenu a').on('click', function(e) {
				var href = $(this).attr('href') || '';
				if (href.indexOf('reboot.php') !== -1) {
					e.preventDefault();
					if (!isDesktop()) $('body').removeClass('sidebar-mobile-open');
					$('.sidebar-item').removeClass('pinned open').find('.sidebar-flyout').hide();
					MitraNet.rebootSystem();
					return false;
				}
				if (href.indexOf('halt.php') !== -1) {
					e.preventDefault();
					if (!isDesktop()) $('body').removeClass('sidebar-mobile-open');
					$('.sidebar-item').removeClass('pinned open').find('.sidebar-flyout').hide();
					MitraNet.shutdownSystem();
					return false;
				}

				if (!isDesktop()) {
					$('body').removeClass('sidebar-mobile-open');
				}
				// Desktop: flyout tetap terbuka - browser akan navigasi dan page akan fresh
			});

			// Highlight active link and parent category
			var currentScript = window.location.pathname.split('/').pop().split('?')[0];
			if (!currentScript || currentScript === '' || currentScript === 'index.php') {
				$('.sidebar-item').removeClass('active');
				$('.sidebar-submenu li').removeClass('active');
				return;
			}

			// Help should never be highlighted as active page menu
			$('#sub-help').closest('.sidebar-item').removeClass('active');

			if (currentScript === 'diag_terminal.php') {
				$('.sidebar-item').removeClass('active');
				$('.sidebar-link.direct-link[href="/diag_terminal.php"]').closest('.sidebar-item').addClass('active');
				return;
			}

			if (currentScript === 'services_virtual.php') {
				$('.sidebar-item').removeClass('active');
				$('.sidebar-link.direct-link[href="/services_virtual.php"]').closest('.sidebar-item').addClass('active');
				return;
			}

			$('.sidebar-submenu a').each(function() {
				var href = $(this).attr('href');
				if (href && !href.startsWith('http://') && !href.startsWith('https://') && href !== '#') {
					var hrefScript = href.split('?')[0].split('/').pop();
					if (hrefScript && hrefScript === currentScript) {
						$(this).parent().addClass('active');
						var $parentItem = $(this).closest('.sidebar-item');
						$parentItem.addClass('active');
						if (!isDesktop()) {
							$parentItem.addClass('open');
							$parentItem.find('.sidebar-flyout').show();
						}
					}
				}
			});

			$('.sidebar-link.direct-link').each(function() {
				var href = $(this).attr('href');
				if (href) {
					var hrefScript = href.split('?')[0].split('/').pop();
					if (hrefScript && hrefScript === currentScript) {
						$(this).closest('.sidebar-item').addClass('active');
					}
				}
			});
		});

		// Smooth scroll to top
		$(document).on('click', '#tpr, .footer-back-top', function(e) {
			e.preventDefault();
			$('html, body').animate({ scrollTop: 0 }, 200);
		});

		// Un-hide the "Top of page" icons if the page is larger than the window
		if ($(document).height() > $(window).height()) {
		    $('[id^=tp]').show();
		}
	//]]>
	</script>
</body>
</html>

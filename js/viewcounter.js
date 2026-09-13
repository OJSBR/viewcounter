/**
 * plugins/generic/viewcounter/js/viewcounter.js
 *
 * Copyright (c) 2024-2026 OJSBR (https://ojsbr.com.br)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Moves each badge from its hook location to the article title, when the
 * theme uses the default markup (.obj_article_summary .title / h1.page_title).
 * If the markup differs, the badge simply stays where the hook printed it.
 *
 * The title becomes a flex container so the badge can sit at its right edge,
 * and in a flex container every child box becomes a flex item -- including the
 * <em>, <sub> and <mjx-container> elements a title may be made of, which would
 * be torn apart into separate columns. So the title's own content is first
 * wrapped in a single element, and that wrapper is the flex item.
 */
(function () {
	'use strict';

	/**
	 * Wraps everything the title currently holds in one element, so that the
	 * title's inline content keeps laying out as inline content.
	 */
	function wrapContent(host) {
		var existing = host.querySelector('.viewcounter-title-text');
		if (existing && existing.parentNode === host) {
			return existing;
		}
		var text = document.createElement('span');
		text.className = 'viewcounter-title-text';
		while (host.firstChild) {
			text.appendChild(host.firstChild);
		}
		host.appendChild(text);
		return text;
	}

	function place() {
		var badges = document.querySelectorAll('.viewcounter-stats[data-vc-place]:not([data-vc-placed])');
		Array.prototype.forEach.call(badges, function (badge) {
			var host = null;
			if (badge.getAttribute('data-vc-place') === 'summary') {
				var card = badge.closest ? badge.closest('.obj_article_summary') : null;
				host = card ? card.querySelector('.title') : null;
			} else {
				var details = badge.closest ? badge.closest('.obj_article_details') : null;
				host = (details || document).querySelector('h1.page_title, .page_title');
			}
			if (!host || host.contains(badge)) {
				return;
			}
			wrapContent(host);
			host.classList.add('viewcounter-title-host');
			host.appendChild(badge);
			badge.setAttribute('data-vc-placed', '1');
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', place);
	} else {
		place();
	}
})();

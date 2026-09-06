/**
 * plugins/generic/viewcounter/js/viewcounter.js
 *
 * Copyright (c) 2024-2026 OJSBR (https://ojsbr.com.br)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Moves each badge from its hook location to the article title, when the
 * theme uses the default markup (.obj_article_summary .title / h1.page_title).
 * If the markup differs, the badge simply stays where the hook printed it.
 */
(function () {
	'use strict';

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
			if (!host) {
				return;
			}
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

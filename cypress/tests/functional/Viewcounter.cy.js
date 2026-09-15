/**
 * @file cypress/tests/functional/Viewcounter.cy.js
 *
 * Copyright (c) 2024-2026 OJSBR (https://ojsbr.com)
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Functional tests: the badge on the issue and article pages, and the settings.
 *
 * Parameters (--env): contextPath, adminUser, adminPassword (captcha on login
 * must be off for the run). The defaults match the data set of PKP's
 * continuous integration: a journal with a current issue. The first test enables
 * the plugin when it is off, and every setting touched is put back as it was.
 */

describe('View Counter plugin', function() {
	const contextPath = Cypress.env('contextPath') || 'publicknowledge';
	const adminUser = Cypress.env('adminUser') || 'admin';
	const adminPassword = Cypress.env('adminPassword') || 'admin';

	const rowName = 'viewcounterplugin';
	const settingsForm = 'form[id="viewcounterSettingsForm"]';

	// ---- OJSBR spec helpers (padrão v2): work on OJS/OMP 3.3, 3.4 and 3.5 and in PKP's CI ----

	const pageUrl = (path) => '/index.php/' + contextPath + (path ? '/' + path : '');

	// Same as PKP's cy.waitJQuery(), which the support files of OJS 3.3 test sites may lack.
	// The Plugins tab can keep requests open for a while (the plugin gallery), hence the timeout.
	const waitJQuery = () => cy.window().its('jQuery.active', {timeout: 60000}).should('eq', 0);

	// Requests carry the browser's User-Agent: OJS 3.3 drops a session whose agent changes.
	const request = (options) => cy.window({log: false}).then((win) => cy.request(Object.assign(
		typeof options === 'string' ? {url: options} : options,
		{headers: Object.assign({'User-Agent': win.navigator.userAgent}, (typeof options === 'string' ? {} : options.headers) || {})}
	)));

	// Signs in through requests (the login page can re-render while it is typed into), then
	// falls back to the form when the session did not stick (OJS 3.3 cookie handling).
	const login = (username, password) => {
		cy.clearCookies();
		request(pageUrl('login')).then((response) => {
			const token = /name="csrfToken" value="([^"]+)"/.exec(response.body)[1];
			// The form posts to the URL with the language: a redirect would turn the POST into a GET.
			const action = /<form[^>]*id="login"[^>]*action="([^"]+)"/.exec(response.body)[1];
			request({method: 'POST', url: action, form: true, body: {csrfToken: token, username: username, password: password}, log: false});
		});
		cy.visit(pageUrl('submissions') + '?reload=' + Date.now());
		cy.get('body').then(($body) => {
			if ($body.find('form#login').length) {
				cy.get('form#login input[name="username"]').type(username, {delay: 0});
				cy.get('form#login input[name="password"]').type(password, {delay: 0, log: false});
				cy.get('form#login').submit();
				cy.get('form#login', {timeout: 30000}).should('not.exist');
			}
		});
	};

	// REST API calls made from the page itself, so they carry the browser's own session.
	const api = (path, options = {}) => cy.window({log: false}).then((win) => cy.wrap(
		win.fetch(path, Object.assign({credentials: 'same-origin'}, options)).then((response) => {
			if (!response.ok) {
				return response.text().then((text) => {
					throw new Error(path + ' answered ' + response.status + ': ' + text.slice(0, 300));
				});
			}
			return response.json();
		}),
		{log: false, timeout: 30000}
	));

	// The website settings page on its Plugins tab (a new query string forces a load). Load it
	// once per test: loading it again while its plugin gallery request is pending stalls the
	// web server of PKP's CI; API calls and settings modals work on the page already open.
	const openPluginsTab = () => {
		cy.visit(pageUrl('management/settings/website') + '?reload=' + Date.now() + '#plugins');
		cy.get('button[id="plugins-button"]', {timeout: 60000}).click();
		cy.get('button[id="plugins-button"]').should('have.attr', 'aria-selected', 'true');
		waitJQuery();
	};

	// Enables the plugin in the grid when it is off (never turns it off).
	const enablePlugin = (rowName) => {
		cy.get('input[id^="select-cell-' + rowName + '-enabled"]', {timeout: 30000}).then(($checkbox) => {
			if (!$checkbox.is(':checked')) {
				cy.wrap($checkbox).click();
				waitJQuery();
			}
		});
		cy.get('input[id^="select-cell-' + rowName + '-enabled"]').should('be.checked');
	};

	// Opens the settings modal from the grid, without reloading the page: a reload right
	// after saving can stall the web server of PKP's CI. The form is fetched each time.
	const openPluginSettings = (rowName, formSelector) => {
		cy.get('a[id*="-row-' + rowName + '-settings-button-"]', {timeout: 30000}).then(($link) => {
			if (!$link.is(':visible')) {
				cy.get('tr[id$="-row-' + rowName + '"] a.show_extras').first().click();
			}
		});
		// The grid may still be animating the extras row: the link is clicked once it exists.
		cy.get('a[id*="-row-' + rowName + '-settings-button-"]').first().click({force: true});
		waitJQuery();
		cy.window().should((win) => {
			expect(win.jQuery(formSelector).data('pkp.handler')).to.exist;
		});
	};

	// ---- end of helpers ----

	const openSettings = () => openPluginSettings(rowName, settingsForm);

	const save = () => {
		cy.get(settingsForm + ' button[id^="submitFormButton-"]').click({force: true});
		waitJQuery();
		cy.get(settingsForm).should('not.exist');
	};

	// A badge with the two counts, each a number with an accessible label.
	const checkBadge = (badge) => {
		cy.wrap(badge).find('span[aria-label]').should('have.length', 2).each((count) => {
			expect(count.attr('aria-label')).to.match(/: \d+$/);
		});
	};

	it('Enables the plugin with both places on', function() {
		login(adminUser, adminPassword);
		openPluginsTab();
		enablePlugin(rowName);
		openSettings();
		cy.get(settingsForm + ' input[name="showInSummary"]').then(($summary) => {
			cy.get(settingsForm + ' input[name="showInDetails"]').then(($details) => {
				if (!$summary.is(':checked')) {
					cy.wrap($summary).click({force: true});
				}
				if (!$details.is(':checked')) {
					cy.wrap($details).click({force: true});
				}
			});
		});
		save();
	});

	it('Shows the badge next to the titles of the current issue and of an article', function() {
		cy.visit(pageUrl('issue/current'));
		cy.get('.obj_article_summary .viewcounter-stats--summary', {timeout: 30000}).should('have.length.at.least', 1).first().then(($badge) => {
			checkBadge($badge);
			// The script moves the badge into the title and wraps the title's own content, so
			// italic or MathJax parts of a title keep flowing as one line.
			expect($badge.closest('.title').length, 'badge inside the title').to.equal(1);
			expect($badge.closest('.title').children('.viewcounter-title-text').length).to.equal(1);
		});

		cy.get('.obj_article_summary .title a').first().invoke('attr', 'href').then((href) => {
			cy.visit(href);
			cy.get('.viewcounter-stats--details', {timeout: 30000}).should('have.length', 1).then(checkBadge);
			cy.get('h1.page_title .viewcounter-stats--details').should('have.length', 1);
		});
	});

	it('Saves each place on its own', function() {
		login(adminUser, adminPassword);
		openPluginsTab();
		openSettings();
		cy.get(settingsForm + ' input[name="showInDetails"]').click({force: true});
		save();

		openSettings();
		cy.get(settingsForm + ' input[name="showInDetails"]').should('not.be.checked');
		cy.get(settingsForm + ' input[name="showInSummary"]').should('be.checked');

		// Put it back as it was.
		cy.get(settingsForm + ' input[name="showInDetails"]').click({force: true});
		save();
		openSettings();
		cy.get(settingsForm + ' input[name="showInDetails"]').should('be.checked');
	});
});

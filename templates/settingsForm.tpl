{**
 * plugins/generic/viewcounter/templates/settingsForm.tpl
 *
 * Configurações do plugin View counter (OJSBR).
 *}
<script>
	$(function() {ldelim}
		$('#viewcounterSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="viewcounterSettingsForm" method="post" action="{url router=\PKP\core\PKPApplication::ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="viewcounterSettingsFormNotification"}

	<div id="description">{translate key="plugins.generic.viewcounter.description"}</div>

	<h3>{translate key="plugins.generic.viewcounter.settings"}</h3>

	{fbvFormArea id="viewcounterSettingsFormArea"}
		{fbvFormSection list=true}
			{fbvElement type="checkbox" id="showInSummary" name="showInSummary" checked=$showInSummary label="plugins.generic.viewcounter.settings.showInSummary"}
			{fbvElement type="checkbox" id="showInDetails" name="showInDetails" checked=$showInDetails label="plugins.generic.viewcounter.settings.showInDetails"}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons submitText="common.save" hideCancel=true}
</form>

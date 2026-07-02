{**
 * plugins/generic/viewcounter/templates/frontend/objects/article_details.tpl
 *
 * Override dos detalhes do artigo (base: OJS 3.4) com contadores do plugin
 * viewcounter (visualizações | downloads) discretos no topo-direito do título.
 * Usa flexbox (sem float) para nunca colidir com outros elementos.
 *}
 {if !$heading}
 	{assign var="heading" value="h3"}
 {/if}
<article class="obj_article_details">

	{* Indicate if this is only a preview *}
	{if $publication->getData('status') !== \PKP\submission\PKPSubmission::STATUS_PUBLISHED}
	<div class="cmp_notification notice">
		{capture assign="submissionUrl"}{url page="workflow" op="access" path=$article->getId()}{/capture}
		{translate key="submission.viewingPreview" url=$submissionUrl}
	</div>
	{* Notification that this is an old version *}
	{elseif $currentPublication->getId() !== $publication->getId()}
		<div class="cmp_notification notice">
			{capture assign="latestVersionUrl"}{url page="article" op="view" path=$article->getBestId()}{/capture}
			{translate key="submission.outdatedVersion"
				datePublished=$publication->getData('datePublished')|date_format:$dateFormatShort
				urlRecentVersion=$latestVersionUrl|escape
			}
		</div>
	{/if}

	{* viewcounter: total de downloads = soma das visualizações das galés *}
	{assign var=vcDownloads value=0}
	{foreach from=$article->getGalleys() item=vcGalley}{assign var=vcDownloads value=$vcDownloads+$vcGalley->getViews()}{/foreach}

	<h1 class="page_title" style="display:flex;align-items:flex-start;gap:16px;">
		<span style="flex:1 1 auto;min-width:0;">{$publication->getLocalizedTitle(null, 'html')|strip_unsafe_html}</span>
		{* viewcounter: contadores discretos (visualizações | downloads) *}
		<span class="viewcounter-stats" style="flex:0 0 auto;display:inline-flex;gap:14px;align-items:center;color:#6b7280;font-size:14px;font-weight:normal;line-height:1.6;white-space:nowrap;">
			<span title="Visualizações" aria-label="Visualizações: {$article->getViews()}" style="display:inline-flex;align-items:center;gap:5px;cursor:default;">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
				{$article->getViews()}
			</span>
			<span title="Downloads" aria-label="Downloads: {$vcDownloads}" style="display:inline-flex;align-items:center;gap:5px;cursor:default;">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
				{$vcDownloads}
			</span>
		</span>
	</h1>

	{if $publication->getLocalizedData('subtitle')}
		<h2 class="subtitle">
			{$publication->getLocalizedSubTitle(null, 'html')|strip_unsafe_html}
		</h2>
	{/if}

	<div class="row">
		<div class="main_entry">

			{if $publication->getData('authors')}
				<section class="item authors">
					<h2 class="pkp_screen_reader">{translate key="article.authors"}</h2>
					<ul class="authors">
					{foreach from=$publication->getData('authors') item=author}
						<li>
							<span class="name">
								{$author->getFullName()|escape}
							</span>
							{if $author->getLocalizedData('affiliation')}
								<span class="affiliation">
									{$author->getLocalizedData('affiliation')|escape}
									{if $author->getData('rorId')}
										<a href="{$author->getData('rorId')|escape}">{$rorIdIcon}</a>
									{/if}
								</span>
							{/if}
							{assign var=authorUserGroup value=$userGroupsById[$author->getData('userGroupId')]}
							{if $authorUserGroup->getShowTitle()}
								<span class="userGroup">
									{$authorUserGroup->getLocalizedName()|escape}
								</span>
							{/if}
							{if $author->getData('orcid')}
								<span class="orcid">
									{if $author->getData('orcidAccessToken')}
										{$orcidIcon}
									{/if}
									<a href="{$author->getData('orcid')|escape}" target="_blank">
										{$author->getData('orcid')|escape}
									</a>
								</span>
							{/if}
						</li>
					{/foreach}
					</ul>
				</section>
			{/if}

			{* DOI *}
			{assign var=doiObject value=$article->getCurrentPublication()->getData('doiObject')}
			{if $doiObject}
				{assign var="doiUrl" value=$doiObject->getData('resolvingUrl')|escape}
				<section class="item doi">
					<h2 class="label">
						{capture assign=translatedDOI}{translate key="doi.readerDisplayName"}{/capture}
						{translate key="semicolon" label=$translatedDOI}
					</h2>
					<span class="value">
						<a href="{$doiUrl}">
							{$doiUrl}
						</a>
					</span>
				</section>
			{/if}


			{* Keywords *}
			{if !empty($publication->getLocalizedData('keywords'))}
			<section class="item keywords">
				<h2 class="label">
					{capture assign=translatedKeywords}{translate key="article.subject"}{/capture}
					{translate key="semicolon" label=$translatedKeywords}
				</h2>
				<span class="value">
					{foreach name="keywords" from=$publication->getLocalizedData('keywords') item="keyword"}
						{$keyword|escape}{if !$smarty.foreach.keywords.last}{translate key="common.commaListSeparator"}{/if}
					{/foreach}
				</span>
			</section>
			{/if}

			{* Abstract *}
			{if $publication->getLocalizedData('abstract')}
				<section class="item abstract">
					<h2 class="label">{translate key="article.abstract"}</h2>
					{$publication->getLocalizedData('abstract')|strip_unsafe_html}
				</section>
			{/if}

			{call_hook name="Templates::Article::Main"}

			{* Usage statistics chart*}
			{if $activeTheme->getOption('displayStats') != 'none'}
				{$activeTheme->displayUsageStatsGraph($article->getId())}
				<section class="item downloads_chart">
					<h2 class="label">
						{translate key="plugins.themes.default.displayStats.downloads"}
					</h2>
					<div class="value">
						<canvas class="usageStatsGraph" data-object-type="Submission" data-object-id="{$article->getId()|escape}"></canvas>
						<div class="usageStatsUnavailable" data-object-type="Submission" data-object-id="{$article->getId()|escape}">
							{translate key="plugins.themes.default.displayStats.noStats"}
						</div>
					</div>
				</section>
			{/if}

			{* Author biographies *}
			{assign var="hasBiographies" value=0}
			{foreach from=$publication->getData('authors') item=author}
				{if $author->getLocalizedData('biography')}
					{assign var="hasBiographies" value=$hasBiographies+1}
				{/if}
			{/foreach}
			{if $hasBiographies}
				<section class="item author_bios">
					<h2 class="label">
						{if $hasBiographies > 1}
							{translate key="submission.authorBiographies"}
						{else}
							{translate key="submission.authorBiography"}
						{/if}
					</h2>
					<ul class="authors">
					{foreach from=$publication->getData('authors') item=author}
						{if $author->getLocalizedData('biography')}
							<li class="sub_item">
								<div class="label">
									{if $author->getLocalizedData('affiliation')}
										{capture assign="authorName"}{$author->getFullName()|escape}{/capture}
										{capture assign="authorAffiliation"} {$author->getLocalizedData('affiliation')|escape} {/capture}
										{translate key="submission.authorWithAffiliation" name=$authorName affiliation=$authorAffiliation}
									{else}
										{$author->getFullName()|escape}
									{/if}
								</div>
								<div class="value">
									{$author->getLocalizedData('biography')|strip_unsafe_html}
								</div>
							</li>
						{/if}
					{/foreach}
					</ul>
				</section>
			{/if}

			{* References *}
			{if $parsedCitations || $publication->getData('citationsRaw')}
				<section class="item references">
					<h2 class="label">
						{translate key="submission.citations"}
					</h2>
					<div class="value">
						{if $parsedCitations}
							{foreach from=$parsedCitations item="parsedCitation"}
								<p>{$parsedCitation->getCitationWithLinks()|strip_unsafe_html} {call_hook name="Templates::Article::Details::Reference" citation=$parsedCitation}</p>
							{/foreach}
						{else}
							{$publication->getData('citationsRaw')|escape|nl2br}
						{/if}
					</div>
				</section>
			{/if}

		</div><!-- .main_entry -->

		<div class="entry_details">

			{* Article/Issue cover image *}
			{if $publication->getLocalizedData('coverImage') || ($issue && $issue->getLocalizedCoverImage())}
				<div class="item cover_image">
					<div class="sub_item">
						{if $publication->getLocalizedData('coverImage')}
							{assign var="coverImage" value=$publication->getLocalizedData('coverImage')}
							<img
								src="{$publication->getLocalizedCoverImageUrl($article->getData('contextId'))|escape}"
								alt="{$coverImage.altText|escape|default:''}"
							>
						{else}
							<a href="{url page="issue" op="view" path=$issue->getBestIssueId()}">
								<img src="{$issue->getLocalizedCoverImageUrl()|escape}" alt="{$issue->getLocalizedCoverImageAltText()|escape|default:''}">
							</a>
						{/if}
					</div>
				</div>
			{/if}

			{* Article Galleys *}
			{if $primaryGalleys}
				<div class="item galleys">
					<h2 class="pkp_screen_reader">
						{translate key="submission.downloads"}
					</h2>
					<ul class="value galleys_links">
						{foreach from=$primaryGalleys item=galley}
							<li>
								{include file="frontend/objects/galley_link.tpl" parent=$article publication=$publication galley=$galley purchaseFee=$currentJournal->getData('purchaseArticleFee') purchaseCurrency=$currentJournal->getData('currency')}
							</li>
						{/foreach}
					</ul>
				</div>
			{/if}
			{if $supplementaryGalleys}
				<div class="item galleys">
					<h3 class="pkp_screen_reader">
						{translate key="submission.additionalFiles"}
					</h3>
					<ul class="value supplementary_galleys_links">
						{foreach from=$supplementaryGalleys item=galley}
							<li>
								{include file="frontend/objects/galley_link.tpl" parent=$article publication=$publication galley=$galley isSupplementary="1"}
							</li>
						{/foreach}
					</ul>
				</div>
			{/if}

			{if $publication->getData('datePublished')}
			<div class="item published">
				<section class="sub_item">
					<h2 class="label">
						{translate key="submissions.published"}
					</h2>
					<div class="value">
						{* If this is the original version *}
						{if $firstPublication->getId() === $publication->getId()}
							<span>{$firstPublication->getData('datePublished')|date_format:$dateFormatShort}</span>
						{* If this is an updated version *}
						{else}
							<span>{translate key="submission.updatedOn" datePublished=$firstPublication->getData('datePublished')|date_format:$dateFormatShort dateUpdated=$publication->getData('datePublished')|date_format:$dateFormatShort}</span>
						{/if}
					</div>
				</section>
				{if count($article->getPublishedPublications()) > 1}
					<section class="sub_item versions">
						<h2 class="label">
							{translate key="submission.versions"}
						</h2>
						<ul class="value">
							{foreach from=array_reverse($article->getPublishedPublications()) item=iPublication}
								{capture assign="name"}{translate key="submission.versionIdentity" datePublished=$iPublication->getData('datePublished')|date_format:$dateFormatShort version=$iPublication->getData('version')}{/capture}
								<li>
									{if $iPublication->getId() === $publication->getId()}
										{$name}
									{elseif $iPublication->getId() === $currentPublication->getId()}
										<a href="{url page="article" op="view" path=$article->getBestId()}">{$name}</a>
									{else}
										<a href="{url page="article" op="view" path=$article->getBestId()|to_array:"version":$iPublication->getId()}">{$name}</a>
									{/if}
								</li>
							{/foreach}
						</ul>
					</section>
				{/if}
			</div>
			{/if}

			{* Data Availability Statement *}
			{if $publication->getLocalizedData('dataAvailability')}
				<section class="item dataAvailability">
					<h2 class="label">{translate key="submission.dataAvailability"}</h2>
					{$publication->getLocalizedData('dataAvailability')|strip_unsafe_html}
				</section>
			{/if}

			{* Issue article appears in *}
			{if $issue || $section || $categories}
				<div class="item issue">

					{if $issue}
						<section class="sub_item">
							<h2 class="label">
								{translate key="issue.issue"}
							</h2>
							<div class="value">
								<a class="title" href="{url page="issue" op="view" path=$issue->getBestIssueId()}">
									{$issue->getIssueIdentification()}
								</a>
							</div>
						</section>
					{/if}

					{if $section}
						<section class="sub_item">
							<h2 class="label">
								{translate key="section.section"}
							</h2>
							<div class="value">
								{$section->getLocalizedTitle()|escape}
							</div>
						</section>
					{/if}

					{if $categories}
						<section class="sub_item">
							<h2 class="label">
								{translate key="category.category"}
							</h2>
							<div class="value">
								<ul class="categories">
									{foreach from=$categories item=category}
										<li><a href="{url router=\PKP\core\PKPApplication::ROUTE_PAGE page="catalog" op="category" path=$category->getPath()|escape}">{$category->getLocalizedTitle()|escape}</a></li>
									{/foreach}
								</ul>
							</div>
						</section>
					{/if}
				</div>
			{/if}

			{* PubIds (requires plugins) *}
			{foreach from=$pubIdPlugins item=pubIdPlugin}
				{if $pubIdPlugin->getPubIdType() == 'doi'}
					{continue}
				{/if}
				{assign var=pubId value=$article->getStoredPubId($pubIdPlugin->getPubIdType())}
				{if $pubId}
					<section class="item pubid">
						<h2 class="label">
							{$pubIdPlugin->getPubIdDisplayType()|escape}
						</h2>
						<div class="value">
							{if $pubIdPlugin->getResolvingURL($currentJournal->getId(), $pubId)|escape}
								<a id="pub-id::{$pubIdPlugin->getPubIdType()|escape}" href="{$pubIdPlugin->getResolvingURL($currentJournal->getId(), $pubId)|escape}">
									{$pubIdPlugin->getResolvingURL($currentJournal->getId(), $pubId)|escape}
								</a>
							{else}
								{$pubId|escape}
							{/if}
						</div>
					</section>
				{/if}
			{/foreach}

			{* Licensing info *}
			{if $currentContext->getLocalizedData('licenseTerms') || $publication->getData('licenseUrl')}
				<div class="item copyright">
					<h2 class="label">
						{translate key="submission.license"}
					</h2>
					{if $publication->getData('licenseUrl')}
						{if $ccLicenseBadge}
							{if $publication->getLocalizedData('copyrightHolder')}
								<p>{translate key="submission.copyrightStatement" copyrightHolder=$publication->getLocalizedData('copyrightHolder') copyrightYear=$publication->getData('copyrightYear')}</p>
							{/if}
							{$ccLicenseBadge}
						{else}
							<a href="{$publication->getData('licenseUrl')|escape}" class="copyright">
								{if $publication->getLocalizedData('copyrightHolder')}
									{translate key="submission.copyrightStatement" copyrightHolder=$publication->getLocalizedData('copyrightHolder') copyrightYear=$publication->getData('copyrightYear')}
								{else}
									{translate key="submission.license"}
								{/if}
							</a>
						{/if}
					{/if}
					{$currentContext->getLocalizedData('licenseTerms')}
				</div>
			{/if}

			{call_hook name="Templates::Article::Details"}

		</div><!-- .entry_details -->
	</div><!-- .row -->

</article>

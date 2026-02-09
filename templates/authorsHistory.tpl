{**
 * plugins/generic/AuthorsHistory/templates/authorsHistory.tpl
 *
 * Copyright (c) 2020-2023 Lepidus Tecnologia
 * Copyright (c) 2020-2023 SciELO
 * Distributed under the GNU GPL v3. For full terms see LICENSE or https://www.gnu.org/licenses/gpl-3.0.txt
 * 
 * @brief Template for display the list of submissions of an author
 *}
<div id="authorsHistory">
    <div id="historyHeader">
        <h2>{translate key="plugins.generic.authorsHistory.displayName"}</h2>
    </div>
    <div id="historyBody">
        {if empty($listDataAuthors)}
            <p class="authorPublications">{translate key="plugins.generic.authorsHistory.noPublications"}</p>
        {/if}

        {foreach from=$listDataAuthors item=authorData}
            <div class="authorHistory">
                <h3>{$authorData['name']|escape}</h3>
                {if $authorData['correspondingAuthor']}
                    <span>{translate key="submission.submit.selectPrincipalContact"}</span><br>
                {/if}
                {if $authorData['orcid']}
                    <a href="{$authorData['orcid']|escape}" target="_blank" rel="noopener noreferrer">
                        <strong>{translate key="plugins.generic.authorsHistory.orcid"}:</strong> {$authorData['orcid']|escape}
                    </a>
                {else}
                    <span>{translate key="plugins.generic.authorsHistory.noORCID"}</span>
                {/if}
                <br><span><strong>{translate key="email.email"}:</strong> {$authorData['email']|escape}</span>

                {if empty($authorData['submissions'])}
                    <p class="authorPublications">{translate key="plugins.generic.authorsHistory.noPublications"}</p>
                {else}
                    {$totalAuthorSubmissions = count($authorData['submissions'])}
                    {$totalPages = ceil($totalAuthorSubmissions / $itemsPerPage)}

                    <div
                        class="authorPublications"
                        data-author-index="{$authorData@index|escape}"
                        data-items-per-page="{$itemsPerPage|escape}"
                    >
                    {foreach from=$authorData['submissions'] item=sub}
                            <div class="authorPublication">
                                <div class="submissionId">
                                    <span>{$sub->getId()|escape}</span>
                                </div>
                                <div class="submissionTitle">
                                    {if $userCanAccessWorkflow}
                                        <a href="{url page="workflow" op="access" path=$sub->getBestId()}" target="_blank" rel="noopener noreferrer">
                                            {$sub->getCurrentPublication()->getLocalizedFullTitle()|escape}
                                        </a>
                                    {else}
                                        <span>
                                            {$sub->getCurrentPublication()->getLocalizedFullTitle()|escape}
                                        </span>
                                    {/if}
                                </div>
                            <div class="submissionStatus">
                                    {if $sub->getData('status') == STATUS_PUBLISHED}
                                        <a href="{url page=$submissionType op="view" path=$sub->getBestId()}" target="_blank" rel="noopener noreferrer">
                                            {translate key=$sub->getStatusKey()}
                                        </a>
                                    {else}
                                        <span>{translate key=$sub->getStatusKey()}</span>
                                    {/if}
                                </div>
                            </div>
                    {/foreach}
                    </div>

                    {if $totalPages > 1}
                        <div class="authorsHistoryPagination" data-author-index="{$authorData@index|escape}">
                            <span>{translate key="plugins.generic.authorsHistory.pages"} &gt;&gt;</span>
                            {for $currentPage=1 to $totalPages}
                                <button class="pageButtons" data-page="{$currentPage|escape}" type="button">
                                    {$currentPage}
                                </button>
                            {/for}
                        </div>
                    {/if}
                {/if}
            </div>
        {/foreach}
    </div>
</div>

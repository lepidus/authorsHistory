{**
 * plugins/generic/AuthorsHistory/templates/authorsHistory.tpl
 *
 * Template for display the list of submissions of an author
 *}

<link rel="stylesheet" type="text/css" href="/plugins/generic/authorsHistory/styles/authorsHistory.css">
<script type="text/javascript" src="/plugins/generic/authorsHistory/templates/pagination.js"></script>

{$currentAuthor = 0}
<div id="authorsHistory">
    <div id="historyHeader">
        <h2>{translate key="plugins.generic.authorsHistory.displayName"}</h2>
    </div>
    <div id="historyBody">
        {foreach from=$listDataAuthors item=authorData}
            <div class="authorHistory">
                <h3>{$authorData['name']}</h3>
                {if $authorData['correspondingAuthor']}
                    <span>{translate key="submission.submit.selectPrincipalContact"}</span><br>
                {/if}
                {if $authorData['orcid']}
                    <a href="{$authorData['orcid']}" target="_blank" rel="noopener noreferrer">
                        <strong>ORCID:</strong> {$authorData['orcid']}
                    </a>
                {else}
                    <span>{translate key="plugins.generic.authorsHistory.noORCID"}</span>
                {/if}
                <br><span><strong>E-mail:</strong> {$authorData['email']}</span>

                {if empty($authorData['submissions'])}
                    <p class="authorPublications">{translate key="plugins.generic.authorsHistory.noPublications"}</p>
                {else}

                <div class="authorPublications">

                {$totalAuthorSubmissions = count($authorData['submissions']) }
                {$totalPages = ceil($totalAuthorSubmissions/ {$itemsPerPage}) }
        
                    {foreach from=$authorData['submissions'] item=sub}
                            <div class="authorPublication">
                                <div class="submissionId">
                                    <span>{$sub->getId()}</span>
                                </div>
                                <div class="submissionTitle">
                                    {if $userIsManager}
                                        <a href="{url page="workflow" op="access" path=$sub->getBestId()}" target="_blank" rel="noopener noreferrer">
                                            {$sub->getCurrentPublication()->getLocalizedFullTitle()}
                                        </a>
                                    {else}
                                        <span>
                                            {$sub->getCurrentPublication()->getLocalizedFullTitle()}
                                        </span>
                                    {/if}
                                </div>
                                <div class="submissionStatus">
                                    {if $sub->getStatus() == STATUS_PUBLISHED}
                                        <a href="{url page="preprint" op="view" path=$sub->getBestId()}" target="_blank" rel="noopener noreferrer">
                                            {translate key="{$sub->getStatusKey()}"}
                                        </a>
                                    {else}
                                        <span>{translate key="{$sub->getStatusKey()}"}</span>
                                    {/if}
                                </div>
                            </div>
                                     
                    {/foreach}

                    <script>
                    var authors = document.getElementsByClassName("authorPublications")[{$currentAuthor}];
                    var authorInformation = authors.getElementsByClassName("authorPublication");

                    if({$itemsPerPage} < {$totalAuthorSubmissions}){
                        for(iterator= 0; iterator < ( {$totalAuthorSubmissions} - {$itemsPerPage} ); iterator++)
                        authorInformation[iterator].style.display = 'none';
                    }
                    </script>

                </div>
                {$currentAuthor = $currentAuthor + 1}
                
                {/if}

                {translate key="plugins.generic.authorsHistory.pages"} >>
                {for $currentPage=1 to $totalPages}
                    <button class="pageButtons" onclick="showSubmissionsPage({$itemsPerPage},{$currentPage},{$totalAuthorSubmissions},{$currentAuthor})" type="button">
                    {$currentPage}
                    </button>
                {/for} 

            </div>
        {/foreach}
    </div>
</div>
{**
 * plugins/generic/AuthorsHistory/templates/authorsHistory.tpl
 *
 * Template for display the list of submissions of an author
 *}

<link rel="stylesheet" type="text/css" href="/plugins/generic/AuthorsHistory/styles/authorsHistory.css">
<script type="text/javascript" src="/plugins/generic/AuthorsHistory/templates/pagination.js"></script>

{$autorAtual = 0}
<div id="authorsHistory">
    <div id="historyHeader">
        <h2>{translate key="plugins.generic.authorsHistory.displayName"}</h2>
    </div>
    <div id="historyBody">
        {foreach from=$listDataAuthors item=dadosAutor}
            <div class="historyAuthor">
                <h3>{$dadosAutor['name']}</h3>
                {if $dadosAutor['correspondingAuthor']}
                    <span>{translate key="submission.submit.selectPrincipalContact"}</span><br>
                {/if}
                {if $dadosAutor['orcid']}
                    <a href="{$dadosAutor['orcid']}" target="_blank" rel="noopener noreferrer">
                        <strong>ORCID:</strong> {$dadosAutor['orcid']}
                    </a>
                {else}
                    <span>{translate key="plugins.generic.authorsHistory.noORCID"}</span>
                {/if}
                <br><span><strong>E-mail:</strong> {$dadosAutor['email']}</span>

                {if empty($dadosAutor['submissions'])}
                    <p class="authorPublications">{translate key="plugins.generic.authorsHistory.noPublications"}</p>
                {else}

                <div class="authorPublications">

                {$submissoesAutor = count($dadosAutor['submissions']) }
                {$paginas = ceil($submissoesAutor/ {$itemsPerPage}) }
        
                    {foreach from=$dadosAutor['submissions'] item=sub}
                            <div class="publicationAuthor">
                                <div class="idSubmission">
                                    <span>{$sub->getId()}</span>
                                </div>
                                <div class="titleSubmission">
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
                    var autores = document.getElementsByClassName("authorPublications")[{$autorAtual}];
                    var informacaoAutor = autores.getElementsByClassName("publicationAuthor");

                    if({$itemsPerPage} < {$submissoesAutor}){
                        for(iterador= 0; iterador < ( {$submissoesAutor} - {$itemsPerPage} ); iterador++)
                        informacaoAutor[iterador].style.display = 'none';
                    }
                    </script>

                </div>
                {$autorAtual = $autorAtual + 1}
                
                {/if}

                {translate key="plugins.generic.authorsHistory.pages"} >>
                {for $paginaAtual=1 to $paginas}
                    <button class="pageButtons" onclick="showSubmissionsPage({$itemsPerPage},{$paginaAtual},{$submissoesAutor},{$autorAtual})" type="button">
                    {$paginaAtual}
                    </button>
                {/for} 

            </div>
        {/foreach}
    </div>
</div>
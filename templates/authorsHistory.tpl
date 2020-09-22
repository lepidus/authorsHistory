{**
 * plugins/generic/AuthorsHistory/templates/authorsHistory.tpl
 *
 * Template for display the list of submissions of an author
 *}

<link rel="stylesheet" type="text/css" href="/plugins/generic/AuthorsHistory/styles/authorsHistory.css">

<div id="authorsHistory">
    <div id="historyHeader">
        <h2>{translate key="plugins.generic.authorsHistory.displayName"}</h2>
    </div>
    <div id="historyBody">
        {foreach from=$listaDadosAutores item=dadosAutor}
            <div class="historicoAutor">
                <h3>{$dadosAutor['nome']}</h3>
                {if $dadosAutor['autorCorrespondente']}
                    <span>{translate key="submission.submit.selectPrincipalContact"}</span><br>
                {/if}
                {if $dadosAutor['orcid']}
                    <a href="{$dadosAutor['orcid']}" target="_blank" rel="noopener noreferrer"><strong>ORCID:</strong> {$dadosAutor['orcid']}</a>
                {else}
                    <span>{translate key="plugins.generic.authorsHistory.noORCID"}</span>
                {/if}
                <br><span><strong>E-mail:</strong> {$dadosAutor['email']}</span>

                {if empty($dadosAutor['submissions'])}
                    <p class="publicacoesAutor">{translate key="plugins.generic.authorsHistory.noPublications"}</p>
                {else}
                <div class="publicacoesAutor">
                    {foreach from=$dadosAutor['submissions'] item=sub}
                        <div class="publicacaoAutor">
                            <div class="idSubmissao">
                                <span>{$sub->getId()}</span>
                            </div>
                            <div class="tituloSubmissao">
                                <span>{$sub->getCurrentPublication()->getLocalizedFullTitle()}</span>
                            </div>
                            <div class="statusSubmissao">
                                <span>{translate key="{$sub->getStatusKey()}"}</span>
                            </div>
                        </div>
                    {/foreach}
                </div>
                {/if}
            </div>
        {/foreach}
    </div>
</div>
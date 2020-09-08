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
                <h4>{$dadosAutor['nome']}</h4>
                {if $dadosAutor['orcid']}
                    <span>ORCID: {$dadosAutor['orcid']}</span>
                {else}
                    <span>{translate key="plugins.generic.authorsHistory.noORCID"}</span>
                {/if}
                <span>E-mail: {$dadosAutor['email']}</span>

                {if empty($dadosAutor['submissions'])}
                    <p>{translate key="plugins.generic.authorsHistory.noPublications"}</p>
                {else}
                <div class="publicacoesAutor">
                    {foreach from=$dadosAutor['submissions'] item=sub}
                        <div class="publicacaoAutor">
                            <span>{$sub->getId()}</span>
                            <span>{$sub->getCurrentPublication()->getLocalizedFullTitle()}</span>
                            <span>{translate key="{$sub->getStatusKey()}"}</span>
                        </div>
                    {/foreach}
                </div>
                {/if}
            </div>
        {/foreach}
    </div>
</div>
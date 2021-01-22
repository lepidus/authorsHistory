{**
 * plugins/generic/AuthorsHistory/templates/authorsHistory.tpl
 *
 * Template for display the list of submissions of an author
 *}

<link rel="stylesheet" type="text/css" href="/plugins/generic/AuthorsHistory/styles/authorsHistory.css">
<script type="text/javascript" src="/plugins/generic/AuthorsHistory/templates/Paginacao.js"></script>

{$autorAtual = 0}
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
                    <a href="{$dadosAutor['orcid']}" target="_blank" rel="noopener noreferrer">
                        <strong>ORCID:</strong> {$dadosAutor['orcid']}
                    </a>
                {else}
                    <span>{translate key="plugins.generic.authorsHistory.noORCID"}</span>
                {/if}
                <br><span><strong>E-mail:</strong> {$dadosAutor['email']}</span>

                {if empty($dadosAutor['submissions'])}
                    <p class="publicacoesAutor">{translate key="plugins.generic.authorsHistory.noPublications"}</p>
                {else}

                <div class="publicacoesAutor">

                {$submissoesAutor = count($dadosAutor['submissions']) }
                {$paginas = ceil($submissoesAutor/ {$itensPorPagina}) }
        
                    {foreach from=$dadosAutor['submissions'] item=sub}
                            <div class="publicacaoAutor">
                                <div class="idSubmissao">
                                    <span>{$sub->getId()}</span>
                                </div>
                                <div class="tituloSubmissao">
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
                                <div class="statusSubmissao">
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
                    var autores = document.getElementsByClassName("publicacoesAutor")[{$autorAtual}];
                    var informacaoAutor = autores.getElementsByClassName("publicacaoAutor");

                    if({$itensPorPagina} < {$submissoesAutor}){
                        for(iterador= 0; iterador < ( {$submissoesAutor} - {$itensPorPagina} ); iterador++)
                        informacaoAutor[iterador].style.display = 'none';
                    }
                    </script>

                </div>
                {$autorAtual = $autorAtual + 1}
                
                {/if}

                {translate key="plugins.generic.authorsHistory.pages"} >>
                {for $paginaAtual=1 to $paginas}
                    <button class="botoesPagina" onclick="indexadorSubmissoes({$itensPorPagina},{$paginaAtual},{$submissoesAutor},{$autorAtual})" type="button">
                    {$paginaAtual}
                    </button>
                {/for} 

            </div>
        {/foreach}
    </div>
</div>
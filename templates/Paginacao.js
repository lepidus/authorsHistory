function submissoesDestaque(submissoes,intervaloInicial,IntervaloFinal,totalSubmissoes){
    var submissoesDaPagina = [];
    
    for(iterador = intervaloInicial; iterador < IntervaloFinal && iterador < totalSubmissoes ; iterador++){
        submissoesDaPagina.push(submissoes[iterador]);
    }            
    
    return submissoesDaPagina;
}

function submissoesRestante(submissoes,intervaloInicial,IntervaloFinal,totalSubmissoes){
    var submissoesRestante = [];

    for(var iterador = 0; iterador < totalSubmissoes; iterador++){
        if(iterador < intervaloInicial)
            submissoesRestante.push(submissoes[iterador]);
        
            if(iterador >= IntervaloFinal)
            submissoesRestante.push(submissoes[iterador]);
    }

    return submissoesRestante;
}

function indexadorSubmissoes(itensPorPagina, paginaAtual,totalSubmissoes,autorAtual){
    var autores = document.getElementsByClassName("publicacoesAutor")[autorAtual-1];
    var informacaoAutor = autores.getElementsByClassName("publicacaoAutor");
    var submissoesPagina = [];
    var demaisSubmissoes = [];

    var inicio = (paginaAtual - 1) * itensPorPagina;
    var fim = inicio + itensPorPagina;

   submissoesPagina = submissoesDestaque(informacaoAutor,inicio,fim,totalSubmissoes);
   demaisSubmissoes = submissoesRestante(informacaoAutor,inicio,fim,totalSubmissoes);

   for(iterador = 0; iterador < submissoesPagina.length ; iterador++)
       submissoesPagina[iterador].style.display = 'flex';

   for(iterador = 0; iterador < demaisSubmissoes.length ; iterador++)
       demaisSubmissoes[iterador].style.display = 'none';
}





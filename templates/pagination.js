
function featuredSubmissions(submissions,inferiorLimit,upperLimit,totalSubmissions){
    var pageSubmissions = [];
    
    for(iterator = inferiorLimit; iterator < upperLimit && iterator < totalSubmissions ; iterator++)
        pageSubmissions.push(submissions[iterator]);    
    
        return pageSubmissions;
}

function remainingSubmissions(submissoes,inferiorLimit,upperLimit,totalSubmissions){
    var remainingSubmissionsList = [];

    for(var iterator = 0; iterator < totalSubmissions; iterator++){
        
        if(iterator < inferiorLimit)
            remainingSubmissionsList.push(submissoes[iterator]);
        
        if(iterator >= upperLimit)
            remainingSubmissionsList.push(submissoes[iterator]);
    }

    return remainingSubmissionsList;
}

function currentSubmissionsRange(currentPage,totalSubmissions,itemsPerPage){
    var inferiorLimit = totalSubmissions - ( currentPage * itemsPerPage );
    var upperLimit = inferiorLimit + itemsPerPage;
    
    if(inferiorLimit < 0)
        return [0,itemsPerPage + inferiorLimit];
    
    return [inferiorLimit, upperLimit];        
}

function oldSubmissionsRange(currentPage,itemsPerPage){
    var inferiorLimit = (currentPage - 1) * itemsPerPage;
    var upperLimit = inferiorLimit + itemsPerPage;

    return [inferiorLimit,upperLimit];
}

function showSubmissionsPage(itemsPerPage,currentPage,totalSubmissions,currentAuthor){
    var authors = document.getElementsByClassName("authorPublications")[currentAuthor-1];
    var authorInformation = authors.getElementsByClassName("publicationAuthor");
    var pageSubmissions = [];
    var otherSubmissions = [];

    var inferiorLimit = currentSubmissionsRange(currentPage,totalSubmissions,itemsPerPage)[0];
    var upperLimit = currentSubmissionsRange(currentPage,totalSubmissions,itemsPerPage)[1];

   pageSubmissions = featuredSubmissions(authorInformation,inferiorLimit,upperLimit,totalSubmissions);
   otherSubmissions = remainingSubmissions(authorInformation,inferiorLimit,upperLimit,totalSubmissions);

   for(iterator = 0; iterator < pageSubmissions.length ; iterator++)
       pageSubmissions[iterator].style.display = 'flex';

   for(iterator = 0; iterator < otherSubmissions.length ; iterator++)
       otherSubmissions[iterator].style.display = 'none';
}




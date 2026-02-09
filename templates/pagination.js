/*
 * Copyright (c) 2020-2023 Lepidus Tecnologia
 * Copyright (c) 2020-2023 SciELO
 * Distributed under the GNU GPL v3. For full terms see LICENSE or https://www.gnu.org/licenses/gpl-3.0.txt
 * 
 * @brief JavaScript file for generation of multi-pages histories.
*/

(function () {
    function showPage(authorContainer, page) {
        var itemsPerPage = parseInt(authorContainer.dataset.itemsPerPage || "0", 10);
        if (!itemsPerPage || itemsPerPage < 1) {
            return;
        }

        var publications = authorContainer.querySelectorAll(".authorPublication");
        var start = (page - 1) * itemsPerPage;
        var end = start + itemsPerPage;

        publications.forEach(function (publication, index) {
            publication.style.display = index >= start && index < end ? "flex" : "none";
        });
    }

    function initializeAuthorPagination(authorContainer, rootElement) {
        var itemsPerPage = parseInt(authorContainer.dataset.itemsPerPage || "0", 10);
        if (!itemsPerPage || itemsPerPage < 1) {
            return;
        }

        var authorIndex = authorContainer.dataset.authorIndex;
        var paginationContainer = rootElement.querySelector('.authorsHistoryPagination[data-author-index="' + authorIndex + '"]');
        if (!paginationContainer) {
            return;
        }

        var totalPublications = authorContainer.querySelectorAll(".authorPublication").length;
        if (totalPublications <= itemsPerPage) {
            showPage(authorContainer, 1);
            paginationContainer.style.display = "none";
            return;
        }

        paginationContainer.querySelectorAll(".pageButtons").forEach(function (button) {
            button.addEventListener("click", function () {
                var page = parseInt(button.dataset.page || "1", 10);
                showPage(authorContainer, page);
            });
        });

        showPage(authorContainer, 1);
    }

    function init(rootElement) {
        if (!rootElement) {
            return;
        }

        rootElement.querySelectorAll(".authorPublications").forEach(function (authorContainer) {
            initializeAuthorPagination(authorContainer, rootElement);
        });
    }

    window.AuthorsHistoryPagination = {
        init: init,
    };

    document.addEventListener("DOMContentLoaded", function () {
        init(document.getElementById("authorsHistory"));
    });
})();

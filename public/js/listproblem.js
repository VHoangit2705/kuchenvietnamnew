/**
 * JavaScript for listproblem page
 */

function redirectToDetail(id) {
    window.location.href = `${window.detailProblemRoute || ''}?id=${id}`;
}


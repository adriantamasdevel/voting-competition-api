<?php

$app->get('/', function () use ($app) {
    return $app->redirect('/swagger/index.html');
});

$customRoutes = array(
    'get_competitions' => array(
        'pattern' => $app["api.version"].'/competitions',
        'controller' => 'App\Controllers\CompetitionsController::getCompetitions',
        'method' => 'get'
    ),
    'post_competition' => array(
        'pattern' => $app["api.version"].'/competitions',
        'controller' => 'App\Controllers\CompetitionsController::postCompetition',
        'method' => 'post'
    ),
    'get_competition' => array(
        'pattern' => $app["api.version"].'/competitions/{competitionId}',
        'controller' => 'App\Controllers\CompetitionsController::getCompetition',
        'method' => 'get',
        'assert' => array(
            'competitionId' => '^([a-zA-Z0-9-_.]+)$'
        ),
    ),
    'get_competition_stats' => array(
        'pattern' => $app["api.version"].'/competitions/{competitionId}/stats',
        'controller' => 'App\Controllers\CompetitionsController::getCompetitionStats',
        'method' => 'get',
        'assert' => array(
            'competitionId' => '^([a-zA-Z0-9-_.]+)$'
        ),
    ),
    'patch_competition' => array(
        'pattern' => $app["api.version"].'/competitions/{competitionId}',
        'controller' => 'App\Controllers\CompetitionsController::patchCompetition',
        'method' => array('patch', 'post')
    ),

    'get_image' => array(
        'pattern' => $app["api.version"].'/images/{imageId}',
        'controller' => 'App\Controllers\ImagesController::getImage',
        'method' => 'get',
        'assert' => array(
            'imageId' => '^([a-zA-Z0-9-_.]+)$'
        ),
    ),
    'post_image' => array(
        'pattern' => $app["api.version"].'/images',
        'controller' => 'App\Controllers\ImagesController::postImage',
        'method' => 'post'
    ),
    'get_imageEntry' => array(
        'pattern' => $app["api.version"].'/imageEntries/{imageId}',
        'controller' => 'App\Controllers\ImageEntriesController::getImageEntry',
        'method' => 'get'
    ),
    'get_imageEntries' => array(
        'pattern' => $app["api.version"].'/imageEntries',
        'controller' => 'App\Controllers\ImageEntriesController::getImageEntries',
        'method' => 'get'
    ),
    'post_imageEntry' => array(
        'pattern' => $app["api.version"].'/imageEntries',
        'controller' => 'App\Controllers\ImageEntriesController::postImageEntry',
        'method' => 'post'
    ),
    'patch_imageEntry' => array(
        'pattern' => $app["api.version"].'/imageEntries/{imageId}',
        'controller' => 'App\Controllers\ImageEntriesController::patchImageEntry',
        'method' => array('patch', 'post')
    ),
    'get_imageEntriesWithScore' => array(
        'pattern' => $app["api.version"].'/imageEntriesWithScore',
        'controller' => 'App\Controllers\ImageEntriesWithScoreController::getImageEntriesWithScore',
        'method' => 'get'
    ),
    'post_vote' => array(
        'pattern' => $app["api.version"].'/votes',
        'controller' => 'App\Controllers\VotesController::postVote',
        'method' => 'post'
    ),
    'post_imageEntryShuffle' => array(
        'pattern' => $app["api.version"].'/imageEntriesShuffle',
        'controller' => 'App\Controllers\ImageEntriesController::postShuffle',
        'method' => 'post'
    ),
    'get_healthcheck' => array(
        'pattern' => $app["api.version"].'/healthcheck',
        'controller' => 'App\Controllers\HealthcheckController::checkApi',
        'method' => 'get'
    ),
);

return $customRoutes;

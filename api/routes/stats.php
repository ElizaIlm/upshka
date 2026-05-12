<?php

addRoute('GET', '/stats', function ($params) {
    global $mysql_connection;
    Auth::requireAdmin();
    require_once ROOT . '/backend/Contexts/StatsContext.php';

    $ctx = new StatsContext($mysql_connection);
    Response::json($ctx->getDashboard());
});

addRoute('GET', '/reports', function ($params) {
    global $mysql_connection;
    Auth::requireAdmin();
    require_once ROOT . '/backend/Contexts/StatsContext.php';

    $ctx = new StatsContext($mysql_connection);
    Response::json($ctx->getReport());
});

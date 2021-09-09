<?php

namespace App;

use Utils\Database;
use Utils\Request;
use Utils\Response;

include_once 'vendor/autoload.php';

Request::configureRequest();
// Response::error(code: 404, message: 'Page not found');
// Database::connect();
exit('Reached index.php');

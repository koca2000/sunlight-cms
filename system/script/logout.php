<?php

use Sunlight\Core;
use Sunlight\User;
use Sunlight\Util\Response;
use Sunlight\Xsrf;

require '../bootstrap.php';
Core::init('../../');

if (Xsrf::check(true)) {
    User::logout();
}

Response::redirectBack();

<?php

/*
 *
 * @package OpenEMR
 * @link    https://www.open-emr.org
 * author Sherwin Gaddis <sherwingaddis@gmail.com>
 * All Rights Reserved
 * @copyright Copyright (c) 2024.
 */

require_once dirname(__DIR__, 3) . '/globals.php';

use OpenEMR\Core\Header;

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo xlt('Hospital Charge') ?></title>
    <?php Header::setupHeader(); ?>
</head>
<body>
<div class="container">
    <h1><?php echo xlt('Hospital Charge') ?></h1>
    <p><?php echo xlt('This is the Hospital Charge module setup page') ?></p>
    <p><?php echo xlt('All settings for the module should be handled with in the module. Only use the main config if you have to.') ?></p>
</div>
</body>
</html>

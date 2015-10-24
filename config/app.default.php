<?php
$config = array(
    // Enables debug output
    'debug' => true,

    // Controller that will be loaded by default
    // (if empty or incorrect controller was passed in url)
    'default_controller' => 'index',

    // If true, controllers tree will be cached in ./tmp/
    // Cache doesn't rebuild automaticly,
    // if changes were made, you need manually delete ./tmp/controllers*
    'cache_controllers' => false,
);
?>
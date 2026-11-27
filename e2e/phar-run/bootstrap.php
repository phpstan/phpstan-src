<?php declare(strict_types = 1);

// the phar exposes symfony/deprecation-contracts' trigger_deprecation() globally,
// it has to accept format arguments on every PHP version the phar runs on
// https://github.com/phpstan/phpstan/issues/15603
trigger_deprecation('phpstan/phar-run', '1.0', 'The "%s" function is deprecated.', 'foo');

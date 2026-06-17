<?php declare(strict_types = 1);

// DRAFT config for shipmonk/coverage-guard — https://github.com/shipmonk-rnd/coverage-guard
//
// Used by .github/workflows/patch-coverage.yml to gate coverage on the lines a
// PR changes. Thresholds below are a starting point, tune to taste.

use ShipMonk\CoverageGuard\Config;
use ShipMonk\CoverageGuard\Rule\EnforceCoverageForMethodsRule;

$config = new Config();

// The coverage report is produced by PHPUnit/paratest on the same runner that
// runs the gate, so the absolute file paths inside clover.xml already live
// under this directory — git root is enough, no path mapping needed.
// (The monorepo needs addCoveragePathMapping() only because PHPUnit there runs
// inside a container at a different WORKDIR than the checkout.)
$config->setGitRoot(__DIR__);

$config->addRule(new EnforceCoverageForMethodsRule(
    requiredCoveragePercentage: 50,
    minMethodChangePercentage: 50,
    minExecutableLines: 5,
));

return $config;

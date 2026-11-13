#!/usr/bin/env bash
# @tags result-cache

function set_up() {
	e2e::set_up
}

function tear_down() {
	e2e::tear_down
}

function test_result_cache_scanned_vendor() {
	composer install
	# A package from a path repository is the project's own code and is tracked file by file;
	# record the fixture package as downloaded, like one from Packagist
	php -r '$f = "vendor/composer/installed.json"; $j = json_decode(file_get_contents($f), true); foreach ($j["packages"] as &$p) { $p["dist"]["type"] = "zip"; } file_put_contents($f, json_encode($j, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));'
	../../bin/phpstan analyse
	# A scanned file that belongs to an installed Composer package is not tracked as a file
	# dependency - packages are tracked at package granularity - so editing it still has to
	# invalidate the whole result cache instead of leaving stale results behind.
	patch -b vendor/test/scanned-lib/src/Dep.php < patch.patch
	OUTPUT=$(../../bin/phpstan analyse -vv --error-format raw 2>&1) && CODE=0 || CODE=$?
	assert_same "1" "$CODE"
	echo "$OUTPUT"
	assert_contains 'changed and belongs to a Composer package.' "$OUTPUT"
	assert_contains 'Foo.php:12:Method ResultCacheE2EScannedVendor\Foo::doFoo() should return int but returns string.' "$OUTPUT"
}

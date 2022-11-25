#!/usr/bin/env php
<?php declare(strict_types = 1);

if (!isset($argv[1])) {
	throw new Exception('glob pattern required.');
}

$glob = $argv[1];
$files = glob($glob);
if ($files === false) {
	throw new Exception('glob failed.');
}

$exitCode = 1;
foreach ($files as $filename) {
	$result = file_get_contents($filename);
	if ($result === false || $result === '') {
		continue;
	}

	$format = '<details>
 <summary>%s contained a failling test</summary>

```
%s
```

</details>';
	printf($format, basename($filename), htmlspecialchars($result, ENT_NOQUOTES, 'UTF-8'));
	echo "\n\n";

	$exitCode = 0;
}

if ($exitCode === 1) {
	echo 'no tests fail without changes in src/';
}

exit($exitCode);

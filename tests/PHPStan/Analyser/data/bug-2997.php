<?php

namespace Bug2997;

use function PHPStan\Analyser\assertType;

function (\SimpleXMLElement $xml): void {
	assertType('bool', (bool) $xml->Exists);
};

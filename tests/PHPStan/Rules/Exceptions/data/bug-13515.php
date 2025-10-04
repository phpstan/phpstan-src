<?php

namespace Bug13515;

/** @throws void */
function fromArray(string $name, string $versionWithoutMicro) : void
{
	if ($name === 'Mac OS X'
		&& \version_compare(
			$versionWithoutMicro,
			'10.12',
			'>='
		)
	) {
		$name          = 'macOS';
		$marketingName = 'macOS';
	}
}

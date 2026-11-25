<?php // lint >= 8.0

namespace DynamicSideEffectsExtensionsPurity;

use DynamicSideEffectsExtensions\Box;
use DynamicSideEffectsExtensions\Made;
use DynamicSideEffectsExtensions\PureMade;
use function DynamicSideEffectsExtensions\impureIntFunction;
use function DynamicSideEffectsExtensions\pureFunction;

/** @phpstan-pure */
function calls(Box $box): int
{
	impureIntFunction('none');
	pureFunction('io.output');
	$box->impureInt('none');
	$box->pure('io.output');
	Box::impureIntStatic('none');
	Box::pureStatic('io.output');
	new Made('none');
	new Made();
	new PureMade('io.output');

	return 1;
}

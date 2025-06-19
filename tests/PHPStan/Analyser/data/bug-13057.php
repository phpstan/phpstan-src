<?php declare(strict_types = 1);

namespace Bug13057;

use AllowDynamicProperties;
use function PHPStan\Testing\assertType;

/**
 * @property ModelB & object{extra: string} $modelB
 */
#[AllowDynamicProperties]
class ModelA
{}

/**
 * @property ModelA & object{extra: string} $modelA
 */
#[AllowDynamicProperties]
class ModelB
{}

function (ModelA $a, ModelB $b): void {
	assertType('string', $a->modelB->extra);
	assertType('string', $b->modelA->extra);
};

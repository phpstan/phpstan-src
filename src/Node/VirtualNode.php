<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PHPStan\Turbo\ReferencedByTurboExtension;

/**
 * @api
 * @api-do-not-implement
 */
#[ReferencedByTurboExtension(key: 'virtualNode')]
interface VirtualNode extends Node
{

}

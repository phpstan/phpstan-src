<?php declare(strict_types = 1);

namespace PHPStan\Process\Runnable;

use React\Promise\CancellablePromiseInterface;

interface Runnable
{

	public function getName(): string;

	public function run(): CancellablePromiseInterface;

	public function cancel(): void;

}

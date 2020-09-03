<?php declare(strict_types = 1);

namespace PHPStan\Process\Runnable;

use React\Promise\PromiseInterface;

interface Runnable
{

	public function getName(): string;

	public function run(): PromiseInterface;

	public function cancel(): void;

}

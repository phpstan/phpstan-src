<?php declare(strict_types = 1);

namespace PHPStan\Command;

/** @api */
interface Output
{

	public function writeFormatted(string $message): void;

	public function writeLineFormatted(string $message): void;

	public function writeRaw(string $message): void;

	public function getStyle(): OutputStyle;

	public function isVerbose(): bool;

	public function isVeryVerbose(): bool;

	public function isDebug(): bool;

	public function isDecorated(): bool;

}

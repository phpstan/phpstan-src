<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

/** @api */
interface ClassMemberReflection
{

	public function getDeclaringClass(): ClassReflection;

	public function isStatic(): bool;

	public function isPrivate(): bool;

	public function isPublic(): bool;

	public function getDocComment(): ?string;

}

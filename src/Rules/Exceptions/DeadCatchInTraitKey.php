<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PhpParser\Node\Stmt\Catch_;
use PHPStan\Type\Type;
use function implode;
use function sprintf;

/**
 * Identifies one caught type of one catch clause in a trait, so that the dead-catch
 * verdicts collected from every class using the trait can be compared.
 *
 * The trait is parsed from the same file for every using class, so the caught type
 * together with the line pins down the occurrence: `catch (A|B)` yields two keys,
 * one per caught type.
 */
final class DeadCatchInTraitKey
{

	public static function create(Catch_ $catchNode, Type $originalCaughtType): string
	{
		return sprintf('%s:%d', implode('|', $originalCaughtType->getObjectClassNames()), $catchNode->getStartLine());
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function str_starts_with;

/**
 * @see https://wiki.php.net/rfc/pdo_driver_specific_subclasses
 * @see https://github.com/php/php-src/pull/12804
 */
#[AutowiredService]
final class PDOConnectReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function getClass(): string
	{
		return 'PDO';
	}

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $this->phpVersion->hasPDOSubclasses() && $methodReflection->getName() === 'connect';
	}

	public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
	{
		if (count($methodCall->getArgs()) < 1) {
			return null;
		}

		$valueType = $scope->getType($methodCall->getArgs()[0]->value);
		$constantStrings = $valueType->getConstantStrings();
		if (count($constantStrings) === 0) {
			return null;
		}

		$subclasses = [];
		foreach ($constantStrings as $constantString) {
			if (str_starts_with($constantString->getValue(), 'mysql:')) {
				$subclasses['PDO\Mysql'] = 'PDO\Mysql';
			} elseif (str_starts_with($constantString->getValue(), 'firebird:')) {
				$subclasses['PDO\Firebird'] = 'PDO\Firebird';
			} elseif (str_starts_with($constantString->getValue(), 'dblib:')) {
				$subclasses['PDO\Dblib'] = 'PDO\Dblib';
			} elseif (str_starts_with($constantString->getValue(), 'odbc:')) {
				$subclasses['PDO\Odbc'] = 'PDO\Odbc';
			} elseif (str_starts_with($constantString->getValue(), 'pgsql:')) {
				$subclasses['PDO\Pgsql'] = 'PDO\Pgsql';
			} elseif (str_starts_with($constantString->getValue(), 'sqlite:')) {
				$subclasses['PDO\Sqlite'] = 'PDO\Sqlite';
			} else {
				return null;
			}
		}

		$returnTypes = [];
		foreach ($subclasses as $class) {
			$returnTypes[] = new ObjectType($class);
		}

		return TypeCombinator::union(...$returnTypes);
	}

}

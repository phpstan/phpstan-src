<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Int_;
use PHPStan\Analyser\Scope;
use function count;
use function explode;
use function in_array;
use function is_numeric;
use function sort;
use function strtolower;

final class VersionCompareHelper
{

	public const VALID_OPERATORS = [
		'<',
		'lt',
		'<=',
		'le',
		'>',
		'gt',
		'>=',
		'ge',
		'==',
		'=',
		'eq',
		'!=',
		'<>',
		'ne',
	];

	/**
	 * Parses a version_compare() function call that involves PHP_VERSION.
	 *
	 * @return array{int, int}|null [phpVersionArgIndex, versionId] or null if not applicable
	 */
	public static function parseVersionCompareFuncCall(FuncCall $funcCall, Scope $scope): ?array
	{
		if (!$funcCall->name instanceof Name) {
			return null;
		}

		if (strtolower((string) $funcCall->name) !== 'version_compare') {
			return null;
		}

		if ($funcCall->isFirstClassCallable()) {
			return null;
		}

		$args = $funcCall->getArgs();
		if (count($args) < 2) {
			return null;
		}

		$phpVersionArgIndex = self::getPhpVersionArgIndex($args[0]->value, $args[1]->value);
		if ($phpVersionArgIndex === null) {
			return null;
		}

		$otherArgIndex = $phpVersionArgIndex === 0 ? 1 : 0;
		$versionId = self::resolveVersionId($args[$otherArgIndex]->value, $scope);
		if ($versionId === null) {
			return null;
		}

		return [$phpVersionArgIndex, $versionId];
	}

	private static function getPhpVersionArgIndex(Expr $arg1, Expr $arg2): ?int
	{
		if (self::isPhpVersionConstant($arg1)) {
			return 0;
		}

		if (self::isPhpVersionConstant($arg2)) {
			return 1;
		}

		return null;
	}

	private static function isPhpVersionConstant(Expr $expr): bool
	{
		return $expr instanceof ConstFetch
			&& $expr->name->toString() === 'PHP_VERSION';
	}

	private static function resolveVersionId(Expr $expr, Scope $scope): ?int
	{
		$constantStrings = $scope->getType($expr)->getConstantStrings();
		if (count($constantStrings) !== 1) {
			return null;
		}

		return self::versionStringToId($constantStrings[0]->getValue());
	}

	public static function versionStringToId(string $version): ?int
	{
		$parts = explode('.', $version);
		if (count($parts) > 3) {
			return null;
		}

		foreach ($parts as $part) {
			if (!is_numeric($part) || (int) $part < 0) {
				return null;
			}
		}

		$major = (int) $parts[0];
		$minor = (int) ($parts[1] ?? 0);
		$patch = (int) ($parts[2] ?? 0);

		return $major * 10000 + $minor * 100 + $patch;
	}

	/**
	 * Given a 2-arg version_compare(PHP_VERSION, 'x.y') result compared
	 * against a constant integer, return the equivalent PHP_VERSION_ID comparison.
	 *
	 * @param int[] $resultSet Subset of [-1, 0, 1] that the comparison selects
	 */
	public static function resultSetToPhpVersionIdComparison(
		array $resultSet,
		int $phpVersionArgIndex,
		int $versionId,
	): ?BinaryOp
	{
		sort($resultSet);

		$phpVersionIdExpr = new ConstFetch(new Name('PHP_VERSION_ID'));
		$versionIdExpr = new Int_($versionId);

		if ($phpVersionArgIndex === 0) {
			$leftExpr = $phpVersionIdExpr;
			$rightExpr = $versionIdExpr;
		} else {
			$leftExpr = $versionIdExpr;
			$rightExpr = $phpVersionIdExpr;
		}

		if ($resultSet === [-1]) {
			return new BinaryOp\Smaller($leftExpr, $rightExpr);
		}
		if ($resultSet === [-1, 0]) {
			return new BinaryOp\SmallerOrEqual($leftExpr, $rightExpr);
		}
		if ($resultSet === [0]) {
			return new BinaryOp\Equal($leftExpr, $rightExpr);
		}
		if ($resultSet === [0, 1]) {
			return new BinaryOp\GreaterOrEqual($leftExpr, $rightExpr);
		}
		if ($resultSet === [1]) {
			return new BinaryOp\Greater($leftExpr, $rightExpr);
		}
		if ($resultSet === [-1, 1]) {
			return new BinaryOp\NotEqual($leftExpr, $rightExpr);
		}

		return null;
	}

	/**
	 * Maps version_compare operator strings to binary operator class names.
	 *
	 * @return class-string<Expr\BinaryOp>|null
	 */
	public static function operatorToComparisonClass(string $operator): ?string
	{
		if (in_array($operator, ['<', 'lt'], true)) {
			return Expr\BinaryOp\Smaller::class;
		}
		if (in_array($operator, ['<=', 'le'], true)) {
			return Expr\BinaryOp\SmallerOrEqual::class;
		}
		if (in_array($operator, ['>', 'gt'], true)) {
			return Expr\BinaryOp\Greater::class;
		}
		if (in_array($operator, ['>=', 'ge'], true)) {
			return Expr\BinaryOp\GreaterOrEqual::class;
		}
		if (in_array($operator, ['==', '=', 'eq'], true)) {
			return Expr\BinaryOp\Equal::class;
		}
		if (in_array($operator, ['!=', '<>', 'ne'], true)) {
			return Expr\BinaryOp\NotEqual::class;
		}

		return null;
	}

}

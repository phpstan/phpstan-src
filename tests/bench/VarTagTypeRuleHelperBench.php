<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PhpParser\Node\Expr;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Rules\PhpDoc\VarTagTypeRuleHelper;
use PHPStan\Type\ArrayType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use Tally\Attribute\Before;
use Tally\Attribute\Expected;
use Tally\Attribute\Warmup;
use Tally\ScratchDirectory;
use function count;
use function str_repeat;

#[Warmup(calls: 1)]
final class VarTagTypeRuleHelperBench
{

	private ?VarTagTypeRuleHelper $helper = null;

	private ?Type $varTagType = null;

	/** @var list<array{Expr, MutatingScope}> */
	private array $assigned = [];

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	#[Before]
	public function boot(): void
	{
		$analysed = AnalysedCode::analyse(
			$this->scratch,
			'<?php /** @param array<string, int> $b */ function test($b): void { ' . str_repeat('/** @var array<string, int|string> $a */ $a = $b; ', 16) . '}',
		);
		$this->helper = $analysed->getContainer()->getByType(VarTagTypeRuleHelper::class);
		$this->varTagType = new ArrayType(new StringType(), new UnionType([new IntegerType(), new StringType()]));
		foreach ($analysed->nodes(Expr\Assign::class) as [$assign, $scope]) {
			$this->assigned[] = [$assign->expr, $scope];
		}
	}

	/**
	 * Without reportWrongPhpDocTypeInVarTag, only the native type of the expression is checked
	 * against a @var tag that is not a PHPStan type.
	 */
	#[Expected(instructions: '353.4K', cycles: '467.9K', memory: '984B')]
	public function benchCheckExprTypeWithoutPhpDocCheck(): int
	{
		if ($this->helper === null || $this->varTagType === null) {
			throw new LogicException('boot() did not run.');
		}

		$errors = 0;
		foreach ($this->assigned as [$expr, $scope]) {
			$errors += count($this->helper->checkExprType($scope, $expr, $this->varTagType));
		}

		return $errors;
	}

}

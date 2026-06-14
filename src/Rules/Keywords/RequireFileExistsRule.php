<?php declare(strict_types = 1);

namespace PHPStan\Rules\Keywords;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\File\FileHelper;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantStringType;
use function array_merge;
use function dirname;
use function explode;
use function get_include_path;
use function is_file;
use function sprintf;
use const PATH_SEPARATOR;

/**
 * @implements Rule<Include_>
 */
#[RegisteredRule(level: 0)]
final class RequireFileExistsRule implements Rule
{

	public function __construct(
		#[AutowiredParameter]
		private string $currentWorkingDirectory,
		private ExprPrinter $exprPrinter,
	)
	{
	}

	public function getNodeType(): string
	{
		return Include_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($this->isInFileExists($node, $scope)) {
			return [];
		}

		$errors = [];
		$usedMagicDirFallback = false;
		$paths = $this->resolveFilePaths($node->expr, $scope, $usedMagicDirFallback);

		foreach ($paths as $path) {
			$path = $path->getValue();

			if ($this->doesFileExist($path, $scope)) {
				continue;
			}

			if ($usedMagicDirFallback) {
				$pathExpr = $this->exprPrinter->printExpr($node->expr);
			} else {
				$pathExpr = '"' . $path . '"';
			}

			$errors[] = $this->getErrorMessage($node, $pathExpr);
		}

		return $errors;
	}

	/**
	 * We cannot use `stream_resolve_include_path` as it works based on the calling script.
	 * This method simulates the behavior of `stream_resolve_include_path` but for the given scope.
	 * The priority order is the following:
	 * 	1. The current working directory.
	 * 	2. The include path.
	 *  3. The path of the script that is being executed.
	 */
	private function doesFileExist(string $path, Scope $scope): bool
	{
		$directories = array_merge(
			[$this->currentWorkingDirectory],
			explode(PATH_SEPARATOR, get_include_path()),
			[dirname($scope->getFile())],
		);

		foreach ($directories as $directory) {
			if ($this->doesFileExistForDirectory($path, $directory)) {
				return true;
			}
		}

		return false;
	}

	private function doesFileExistForDirectory(string $path, string $workingDirectory): bool
	{
		$fileHelper = new FileHelper($workingDirectory);
		$absolutePath = $fileHelper->absolutizePath($path);

		return is_file($absolutePath);
	}

	private function getErrorMessage(Include_ $node, string $filePath): IdentifierRuleError
	{
		$message = 'Path in %s() %s is not a file or it does not exist.';

		switch ($node->type) {
			case Include_::TYPE_REQUIRE:
				$type = 'require';
				$identifierType = 'require';
				break;
			case Include_::TYPE_REQUIRE_ONCE:
				$type = 'require_once';
				$identifierType = 'requireOnce';
				break;
			case Include_::TYPE_INCLUDE:
				$type = 'include';
				$identifierType = 'include';
				break;
			case Include_::TYPE_INCLUDE_ONCE:
				$type = 'include_once';
				$identifierType = 'includeOnce';
				break;
			default:
				throw new ShouldNotHappenException('Rule should have already validated the node type.');
		}

		$identifier = sprintf('%s.fileNotFound', $identifierType);

		return RuleErrorBuilder::message(
			sprintf(
				$message,
				$type,
				$filePath,
			),
		)->identifier($identifier)->build();
	}

	/**
	 * @return list<ConstantStringType>
	 */
	private function resolveFilePaths(Expr $expr, Scope $scope, bool &$magicDirFallback): array
	{
		$magicDirFallback = false;

		if (!$expr instanceof Expr\BinaryOp\Concat) {
			return $scope->getType($expr)->getConstantStrings();
		}

		if ($expr->left instanceof Node\Scalar\MagicConst\Dir) {
			$magicDirFallback = true;

			$paths = [];
			foreach ($scope->getType($expr->right)->getConstantStrings() as $constantString) {
				$paths[] = new ConstantStringType(dirname($scope->getFile()) . $constantString->getValue());
			}
			return $paths;
		}

		$paths = [];
		$rightPaths = $this->resolveFilePaths($expr->right, $scope, $magicDirFallback);
		foreach ($this->resolveFilePaths($expr->left, $scope, $magicDirFallback) as $left) {
			foreach ($rightPaths as $rightPath) {
				$paths[] = new ConstantStringType($left->getValue() . $rightPath->getValue());
			}
		}
		return $paths;
	}

	private function isInFileExists(Include_ $node, Scope $scope): bool
	{
		foreach (['file_exists', 'is_file'] as $funcName) {
			$expr = new FuncCall(new FullyQualified($funcName), [
				new Arg($node->expr),
			]);

			if ($scope->getType($expr)->isTrue()->yes()) {
				return true;
			}
		}

		return false;
	}

}

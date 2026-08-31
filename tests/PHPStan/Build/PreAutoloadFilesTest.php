<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\MagicConst\Dir;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use function dirname;
use function file_get_contents;
use function realpath;
use function sprintf;
use function str_ends_with;
use function str_replace;
use function strlen;
use function strtolower;
use function substr;

/**
 * bin/phpstan loads the turbo restart classes before the Composer autoloader,
 * so the symfony polyfills are not registered yet when they run. A call to a
 * polyfilled function there (str_contains(), ...) is a fatal error on every
 * PHP version lacking it natively - and the phar supports PHP 7.4
 * (https://github.com/phpstan/phpstan/issues/15137).
 */
final class PreAutoloadFilesTest extends TestCase
{

	/**
	 * bin/phpstan itself up to the autoloader require, plus every file it
	 * requires before it.
	 *
	 * @return iterable<string, array{string, int|null}>
	 */
	public static function dataPreAutoloadFiles(): iterable
	{
		$root = realpath(__DIR__ . '/../../..');
		self::assertNotFalse($root);
		$root = str_replace('\\', '/', $root);
		$binPath = $root . '/bin/phpstan';

		$autoloadLine = null;
		$files = [];
		foreach ((new NodeFinder())->findInstanceOf(self::parse($binPath), Include_::class) as $include) {
			$path = self::resolveIncludePath($include->expr, dirname($binPath));
			if ($path === null) {
				continue;
			}
			if (str_ends_with($path, '/vendor/autoload.php')) {
				$autoloadLine = $include->getStartLine();
				break;
			}

			$files[] = $path;
		}

		self::assertNotNull($autoloadLine, 'bin/phpstan does not require vendor/autoload.php');
		self::assertNotSame([], $files, 'bin/phpstan requires nothing before vendor/autoload.php');

		yield 'bin/phpstan' => [$binPath, $autoloadLine];
		foreach ($files as $file) {
			yield substr($file, strlen($root) + 1) => [$file, null];
		}
	}

	/**
	 * @param int|null $beforeLine Only calls above this line run before the autoloader
	 */
	#[DataProvider('dataPreAutoloadFiles')]
	public function testCallsOnlyNativeFunctions(string $file, ?int $beforeLine): void
	{
		$polyfilled = self::polyfilledFunctions();

		$violations = [];
		foreach ((new NodeFinder())->findInstanceOf(self::parse($file), FuncCall::class) as $call) {
			if (!$call->name instanceof Name) {
				continue;
			}
			if ($beforeLine !== null && $call->getStartLine() >= $beforeLine) {
				continue;
			}

			$function = strtolower($call->name->getLast());
			if (!isset($polyfilled[$function])) {
				continue;
			}

			$violations[] = sprintf('%s() on line %d', $function, $call->getStartLine());
		}

		self::assertSame([], $violations, sprintf(
			'%s runs before the Composer autoloader registers the symfony polyfills, so it must only call functions native to PHP 7.4.',
			$file,
		));
	}

	private static function resolveIncludePath(Expr $expr, string $dir): ?string
	{
		if (
			!$expr instanceof Concat
			|| !$expr->left instanceof Dir
			|| !$expr->right instanceof String_
		) {
			return null;
		}

		$path = realpath($dir . $expr->right->value);
		if ($path === false) {
			return null;
		}

		return str_replace('\\', '/', $path);
	}

	/**
	 * Every function the bundled symfony polyfills declare, i.e. every function
	 * missing natively on some PHP version PHPStan runs on.
	 *
	 * @return array<string, true>
	 */
	private static function polyfilledFunctions(): array
	{
		$functions = [];
		$finder = new Finder();
		foreach ($finder->files()->name('bootstrap*.php')->depth(0)->in(__DIR__ . '/../../../vendor/symfony/polyfill-*') as $fileInfo) {
			foreach ((new NodeFinder())->findInstanceOf(self::parse($fileInfo->getPathname()), Function_::class) as $function) {
				$functions[$function->name->toLowerString()] = true;
			}
		}

		self::assertArrayHasKey('str_contains', $functions);

		return $functions;
	}

	/**
	 * @return Node\Stmt[]
	 */
	private static function parse(string $file): array
	{
		$code = file_get_contents($file);
		self::assertNotFalse($code, sprintf('Could not read %s', $file));

		$ast = (new ParserFactory())->createForNewestSupportedVersion()->parse($code);
		self::assertNotNull($ast, sprintf('Could not parse %s', $file));

		return $ast;
	}

}

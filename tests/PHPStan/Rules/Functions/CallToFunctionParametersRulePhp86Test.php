<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\ParameterClosureTypeHelper;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CallToFunctionParametersRule>
 */
class CallToFunctionParametersRulePhp86Test extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = self::createReflectionProvider();
		return new CallToFunctionParametersRule(
			$broker,
			new FunctionCallParametersCheck(
				new RuleLevelHelper(
					$broker,
					checkNullables: true,
					checkThisOnly: false,
					checkUnionTypes: true,
					checkExplicitMixed: true,
					checkImplicitMixed: true,
					checkBenevolentUnionTypes: false,
					discoveringSymbolsTip: true,
				),
				new NullsafeCheck(),
				new UnresolvableTypeHelper(),
				new PropertyReflectionFinder(),
				$broker,
				self::getContainer()->getByType(ParameterClosureTypeHelper::class),
				checkArgumentTypes: true,
				checkArgumentsPassedByReference: true,
				checkExtraArguments: true,
				checkMissingTypehints: true,
			),
		);
	}

	public function testBug15372(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15372.php'], [
			[
				'Constant SORT_REGULAR is not allowed for parameter #3 $mode of function array_filter.',
				12,
			],
		]);
	}

	public function testBug15369ArrayWalk(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15369-array-walk.php'], [
			[
				'Parameter #1 $array of function array_walk expects array, TypeError given.',
				10,
			],
			[
				'Parameter #1 $array of function array_walk expects array, FFI\CData given.',
				23,
			],
			[
				'Parameter #1 $array of function array_walk expects array, Bug15369ArrayWalk\ArrayWalkObject1\MyClass given.',
				54,
			],
			[
				'Parameter #1 $array of function array_walk expects array, stdClass given.',
				74,
			],
			[
				'Parameter #1 $array of function array_walk expects array, Bug15369ArrayWalk\ArrayWalkObjects\test given.',
				77,
			],
			[
				'Parameter #1 $array of function array_walk expects array, string given.',
				83,
			],
			[
				'Parameter #1 $array of function array_walk_recursive expects array, stdClass given.',
				106,
			],
			[
				'Parameter #1 $array of function array_walk_recursive expects array, Bug15369ArrayWalk\ArrayWalkRecObjects\test given.',
				109,
			],
			[
				'Parameter #1 $array of function array_walk_recursive expects array, string given.',
				115,
			],
			[
				'Parameter #1 $array of function array_walk_recursive expects array, Bug15369ArrayWalk\ArrayWalkRecursiveObject1\MyClass given.',
				149,
			],
			[
				'Parameter #1 $array of function array_walk expects array, Bug15369ArrayWalk\Bug79839\Test given.',
				162,
			],
		]);
		$this->analyse([__DIR__ . '/data/bug-15369-array-walk-php84.php'], [
			[
				'Parameter #1 $array of function array_walk expects array, Bug15369ArrayWalkPhp84\LazyObjects\C given.',
				17,
			],
			[
				'Parameter #1 $array of function array_walk expects array, Bug15369ArrayWalkPhp84\Gh18268\B given.',
				44,
			],
		]);
	}

	public function testBug15369ZlibAndBz2(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15369-zlib-bz2.php'], [
			[
				'Parameter #2 $options of function deflate_init expects array, stdClass given.',
				11,
			],
			[
				'Parameter #2 $options of function deflate_init expects array, Bug15369Zlib\Gh17745\Options given.',
				16,
			],
			[
				'Parameter #2 $options of function inflate_init expects array, stdClass given.',
				25,
			],
			[
				'Parameter #2 $options of function inflate_init expects array, Bug15369Zlib\Gh17745Inflate\Options given.',
				30,
			],
			[
				'Parameter #2 $options of function deflate_init expects array, Bug15369Zlib\Gh22142\Options given.',
				42,
			],
			[
				'Parameter #2 $options of function inflate_init expects array, Bug15369Zlib\Gh22142Inflate\Options given.',
				57,
			],
			// the object filter parameters are not reported, only that fopen() can return false
			[
				'Parameter #1 $stream of function stream_filter_append expects resource, resource|false given.',
				75,
			],
			[
				'Parameter #1 $stream of function stream_filter_append expects resource, resource|false given.',
				76,
			],
			[
				'Parameter #1 $stream of function fwrite expects resource, resource|false given.',
				77,
			],
			[
				'Parameter #1 $stream of function stream_filter_append expects resource, resource|false given.',
				95,
			],
			[
				'Parameter #1 $stream of function stream_filter_append expects resource, resource|false given.',
				96,
			],
			[
				'Parameter #1 $stream of function fwrite expects resource, resource|false given.',
				97,
			],
		]);
	}

	public function testBug15369MbConvertVariables(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15369-mb-convert-variables.php'], [
			[
				'Parameter #3 $var of function mb_convert_variables expects array|string, Bug15369MbConvertVariables\Gh16261\Test given.',
				19,
			],
			[
				'Parameter #3 $var of function mb_convert_variables expects array|string, Bug15369MbConvertVariables\MbConvertVariables\foo given.',
				71,
			],
			[
				'Parameter #7 ...$vars of function mb_convert_variables expects array|string, Bug15369MbConvertVariables\MbConvertVariables\foo given.',
				81,
			],
			[
				'Parameter #3 $var of function mb_convert_variables expects array|string, int given.',
				109,
			],
		]);
	}

	public function testBug15369HttpBuildQuery(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15369-http-build-query.php'], [
			[
				'Parameter #1 $data of function http_build_query expects array, $this(Bug15369HttpBuildQuery\Bug26817\test) given.',
				19,
			],
			[
				'Parameter #1 $data of function http_build_query expects array, Bug15369HttpBuildQuery\Bug26817\test given.',
				25,
			],
			[
				'Parameter #1 $data of function http_build_query expects array, Bug15369HttpBuildQuery\ObjectBasic\KeyVal given.',
				41,
			],
			[
				'Parameter #1 $data of function http_build_query expects array, Bug15369HttpBuildQuery\ObjectEmpty\EmptyObj given.',
				51,
			],
			[
				'Parameter #1 $data of function http_build_query expects array, Bug15369HttpBuildQuery\ObjectJustStringable\StringableObject given.',
				67,
			],
			[
				'Parameter #1 $data of function http_build_query expects array, Bug15369HttpBuildQuery\ObjectJustStringable\StringableObject given.',
				69,
			],
			[
				'Parameter #1 $data of function http_build_query expects array, Bug15369HttpBuildQuery\ObjectKeyValStringable\KeyValStringable given.',
				88,
			],
			[
				'Parameter #1 $data of function http_build_query expects array, Bug15369HttpBuildQuery\ObjectNested\KeyVal given.',
				107,
			],
			[
				'Parameter #1 $data of function http_build_query expects array, Bug15369HttpBuildQuery\ObjectRecursif\KeyVal given.',
				123,
			],
			[
				'Parameter #1 $data of function http_build_query expects array, stdClass given.',
				143,
			],
			[
				'Parameter #1 $data of function http_build_query expects array, Bug15369HttpBuildQuery\Variation1\UrlBuilder given.',
				144,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/data/call-to-function-php86.neon',
		];
	}

}

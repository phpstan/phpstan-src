<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PhpParser\Node\Name;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

#[RequiresPhp('>= 8.0.0')]
class ParameterAllowedConstantsTest extends PHPStanTestCase
{

	public function testJsonEncodeFlagsAllowsJsonConstant(): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$function = $reflectionProvider->getFunction(new Name('json_encode'), null);
		$flagsParam = $function->getVariants()[0]->getParameters()[1];

		$this->assertSame('flags', $flagsParam->getName());
		$this->assertNotNull($flagsParam->getAllowedConstants());
		$this->assertTrue($flagsParam->getAllowedConstants()->isBitmask());

		$jsonThrowOnError = $reflectionProvider->getConstant(new Name('JSON_THROW_ON_ERROR'), null);
		$result = $flagsParam->checkAllowedConstants([$jsonThrowOnError]);
		$this->assertTrue($result->isOk());

		$sortRegular = $reflectionProvider->getConstant(new Name('SORT_REGULAR'), null);
		$result = $flagsParam->checkAllowedConstants([$sortRegular]);
		$this->assertFalse($result->isOk());
		$this->assertCount(1, $result->getDisallowedConstants());
		$this->assertSame('SORT_REGULAR', $result->getDisallowedConstants()[0]->getName());
	}

	public function testJsonDecodeDoesNotAllowEncodeOnlyConstants(): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$function = $reflectionProvider->getFunction(new Name('json_decode'), null);
		$flagsParam = $function->getVariants()[0]->getParameters()[3];

		$this->assertSame('flags', $flagsParam->getName());

		$jsonPrettyPrint = $reflectionProvider->getConstant(new Name('JSON_PRETTY_PRINT'), null);
		$result = $flagsParam->checkAllowedConstants([$jsonPrettyPrint]);
		$this->assertFalse($result->isOk());
		$this->assertCount(1, $result->getDisallowedConstants());

		$jsonThrowOnError = $reflectionProvider->getConstant(new Name('JSON_THROW_ON_ERROR'), null);
		$result = $flagsParam->checkAllowedConstants([$jsonThrowOnError]);
		$this->assertTrue($result->isOk());
	}

	public function testSortFlagsExclusiveGroups(): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$function = $reflectionProvider->getFunction(new Name('sort'), null);
		$flagsParam = $function->getVariants()[0]->getParameters()[1];

		$this->assertSame('flags', $flagsParam->getName());

		$config = $flagsParam->getAllowedConstants();
		$this->assertNotNull($config);
		$this->assertTrue($config->isBitmask());
		$this->assertCount(1, $config->getExclusiveGroups());
		$this->assertSame(
			['SORT_REGULAR', 'SORT_NUMERIC', 'SORT_STRING', 'SORT_LOCALE_STRING', 'SORT_NATURAL'],
			$config->getExclusiveGroups()[0],
		);

		$sortFlagCase = $reflectionProvider->getConstant(new Name('SORT_FLAG_CASE'), null);
		$result = $flagsParam->checkAllowedConstants([$sortFlagCase]);
		$this->assertTrue($result->isOk());
	}

	public function testHtmlspecialcharsMultipleExclusiveGroups(): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$function = $reflectionProvider->getFunction(new Name('htmlspecialchars'), null);
		$flagsParam = $function->getVariants()[0]->getParameters()[1];

		$this->assertSame('flags', $flagsParam->getName());

		$config = $flagsParam->getAllowedConstants();
		$this->assertNotNull($config);
		$this->assertCount(2, $config->getExclusiveGroups());
		$this->assertSame(['ENT_COMPAT', 'ENT_QUOTES', 'ENT_NOQUOTES'], $config->getExclusiveGroups()[0]);
		$this->assertSame(['ENT_HTML401', 'ENT_XML1', 'ENT_XHTML', 'ENT_HTML5'], $config->getExclusiveGroups()[1]);
	}

	public function testSingleTypeParameter(): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$function = $reflectionProvider->getFunction(new Name('round'), null);
		$modeParam = $function->getVariants()[0]->getParameters()[2];

		$this->assertSame('mode', $modeParam->getName());

		$config = $modeParam->getAllowedConstants();
		$this->assertNotNull($config);
		$this->assertFalse($config->isBitmask());
		$this->assertSame([], $config->getExclusiveGroups());

		$halfUp = $reflectionProvider->getConstant(new Name('PHP_ROUND_HALF_UP'), null);
		$result = $modeParam->checkAllowedConstants([$halfUp]);
		$this->assertTrue($result->isOk());
	}

	public function testUnmappedParameterReturnsOk(): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$function = $reflectionProvider->getFunction(new Name('strlen'), null);
		$param = $function->getVariants()[0]->getParameters()[0];

		$this->assertNull($param->getAllowedConstants());

		$anyConstant = $reflectionProvider->getConstant(new Name('JSON_THROW_ON_ERROR'), null);
		$result = $param->checkAllowedConstants([$anyConstant]);
		$this->assertTrue($result->isOk());
	}

	public function testMethodWithGlobalConstants(): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$class = $reflectionProvider->getClass('finfo');
		$method = $class->getNativeMethod('file');
		$flagsParam = $method->getVariants()[0]->getParameters()[1];

		$this->assertSame('flags', $flagsParam->getName());
		$this->assertNotNull($flagsParam->getAllowedConstants());
		$this->assertTrue($flagsParam->getAllowedConstants()->isBitmask());

		$fileinfoMime = $reflectionProvider->getConstant(new Name('FILEINFO_MIME'), null);
		$result = $flagsParam->checkAllowedConstants([$fileinfoMime]);
		$this->assertTrue($result->isOk());

		$sortRegular = $reflectionProvider->getConstant(new Name('SORT_REGULAR'), null);
		$result = $flagsParam->checkAllowedConstants([$sortRegular]);
		$this->assertFalse($result->isOk());
		$this->assertCount(1, $result->getDisallowedConstants());
	}

	public function testMethodWithClassConstants(): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$class = $reflectionProvider->getClass('PDOStatement');
		$method = $class->getNativeMethod('fetch');
		$modeParam = $method->getVariants()[0]->getParameters()[0];

		$this->assertSame('mode', $modeParam->getName());
		$this->assertNotNull($modeParam->getAllowedConstants());
		$this->assertFalse($modeParam->getAllowedConstants()->isBitmask());

		$pdoClass = $reflectionProvider->getClass('PDO');

		$fetchAssoc = $pdoClass->getConstant('FETCH_ASSOC');
		$result = $modeParam->checkAllowedConstants([$fetchAssoc]);
		$this->assertTrue($result->isOk());

		$attrErrmode = $pdoClass->getConstant('ATTR_ERRMODE');
		$result = $modeParam->checkAllowedConstants([$attrErrmode]);
		$this->assertFalse($result->isOk());
		$this->assertCount(1, $result->getDisallowedConstants());
	}

	public function testClassConstantNotAllowedWhenGlobalConstantsExpected(): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$function = $reflectionProvider->getFunction(new Name('json_encode'), null);
		$flagsParam = $function->getVariants()[0]->getParameters()[1];

		$pdoClass = $reflectionProvider->getClass('PDO');
		$fetchAssoc = $pdoClass->getConstant('FETCH_ASSOC');

		$result = $flagsParam->checkAllowedConstants([$fetchAssoc]);
		$this->assertFalse($result->isOk());
		$this->assertCount(1, $result->getDisallowedConstants());
	}

	public function testViolatedExclusiveGroupsSortFlags(): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$function = $reflectionProvider->getFunction(new Name('sort'), null);
		$flagsParam = $function->getVariants()[0]->getParameters()[1];

		$sortNumeric = $reflectionProvider->getConstant(new Name('SORT_NUMERIC'), null);
		$sortString = $reflectionProvider->getConstant(new Name('SORT_STRING'), null);
		$sortFlagCase = $reflectionProvider->getConstant(new Name('SORT_FLAG_CASE'), null);

		// Two mutually exclusive sort types
		$result = $flagsParam->checkAllowedConstants([$sortNumeric, $sortString]);
		$this->assertFalse($result->isOk());
		$this->assertSame([], $result->getDisallowedConstants());
		$this->assertCount(1, $result->getViolatedExclusiveGroups());
		$this->assertSame(['SORT_NUMERIC', 'SORT_STRING'], $result->getViolatedExclusiveGroups()[0]);

		// Sort type + modifier is fine
		$result = $flagsParam->checkAllowedConstants([$sortString, $sortFlagCase]);
		$this->assertTrue($result->isOk());
	}

	public function testViolatedExclusiveGroupsHtmlEntities(): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$function = $reflectionProvider->getFunction(new Name('htmlspecialchars'), null);
		$flagsParam = $function->getVariants()[0]->getParameters()[1];

		$entQuotes = $reflectionProvider->getConstant(new Name('ENT_QUOTES'), null);
		$entNoquotes = $reflectionProvider->getConstant(new Name('ENT_NOQUOTES'), null);
		$entHtml401 = $reflectionProvider->getConstant(new Name('ENT_HTML401'), null);
		$entHtml5 = $reflectionProvider->getConstant(new Name('ENT_HTML5'), null);
		$entSubstitute = $reflectionProvider->getConstant(new Name('ENT_SUBSTITUTE'), null);

		// Violates both exclusive groups
		$result = $flagsParam->checkAllowedConstants([$entQuotes, $entNoquotes, $entHtml401, $entHtml5]);
		$this->assertFalse($result->isOk());
		$this->assertSame([], $result->getDisallowedConstants());
		$this->assertCount(2, $result->getViolatedExclusiveGroups());
		$this->assertSame(['ENT_QUOTES', 'ENT_NOQUOTES'], $result->getViolatedExclusiveGroups()[0]);
		$this->assertSame(['ENT_HTML401', 'ENT_HTML5'], $result->getViolatedExclusiveGroups()[1]);

		// One from each group is fine
		$result = $flagsParam->checkAllowedConstants([$entQuotes, $entHtml5, $entSubstitute]);
		$this->assertTrue($result->isOk());
	}

	public function testBitmaskNotAllowedOnSingleParameter(): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$function = $reflectionProvider->getFunction(new Name('array_unique'), null);
		$flagsParam = $function->getVariants()[0]->getParameters()[1];

		$this->assertSame('flags', $flagsParam->getName());
		$this->assertNotNull($flagsParam->getAllowedConstants());
		$this->assertFalse($flagsParam->getAllowedConstants()->isBitmask());

		$sortRegular = $reflectionProvider->getConstant(new Name('SORT_REGULAR'), null);
		$sortNumeric = $reflectionProvider->getConstant(new Name('SORT_NUMERIC'), null);

		// Single constant is fine
		$result = $flagsParam->checkAllowedConstants([$sortRegular]);
		$this->assertTrue($result->isOk());
		$this->assertFalse($result->isBitmaskNotAllowed());

		// Bitmask on single-value parameter is not allowed
		$result = $flagsParam->checkAllowedConstants([$sortRegular, $sortNumeric]);
		$this->assertFalse($result->isOk());
		$this->assertTrue($result->isBitmaskNotAllowed());
	}

	public function testBitmaskAllowedOnBitmaskParameter(): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$function = $reflectionProvider->getFunction(new Name('json_encode'), null);
		$flagsParam = $function->getVariants()[0]->getParameters()[1];

		$this->assertNotNull($flagsParam->getAllowedConstants());
		$this->assertTrue($flagsParam->getAllowedConstants()->isBitmask());

		$prettyPrint = $reflectionProvider->getConstant(new Name('JSON_PRETTY_PRINT'), null);
		$unescaped = $reflectionProvider->getConstant(new Name('JSON_UNESCAPED_SLASHES'), null);

		$result = $flagsParam->checkAllowedConstants([$prettyPrint, $unescaped]);
		$this->assertTrue($result->isOk());
		$this->assertFalse($result->isBitmaskNotAllowed());
	}

	public function testBothDisallowedAndExclusiveViolation(): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$function = $reflectionProvider->getFunction(new Name('sort'), null);
		$flagsParam = $function->getVariants()[0]->getParameters()[1];

		$sortNumeric = $reflectionProvider->getConstant(new Name('SORT_NUMERIC'), null);
		$sortString = $reflectionProvider->getConstant(new Name('SORT_STRING'), null);
		$jsonThrowOnError = $reflectionProvider->getConstant(new Name('JSON_THROW_ON_ERROR'), null);

		// Wrong constant AND exclusive group violation
		$result = $flagsParam->checkAllowedConstants([$sortNumeric, $sortString, $jsonThrowOnError]);
		$this->assertFalse($result->isOk());
		$this->assertCount(1, $result->getDisallowedConstants());
		$this->assertSame('JSON_THROW_ON_ERROR', $result->getDisallowedConstants()[0]->getName());
		$this->assertCount(1, $result->getViolatedExclusiveGroups());
		$this->assertSame(['SORT_NUMERIC', 'SORT_STRING'], $result->getViolatedExclusiveGroups()[0]);
	}

}

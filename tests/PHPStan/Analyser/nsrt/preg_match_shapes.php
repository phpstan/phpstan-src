<?php // lint >= 7.2

namespace PregMatchShapes;

use function PHPStan\Testing\assertType;
use InvalidArgumentException;

function doMatch(string $s): void {
	if (preg_match('/Price: /i', $s, $matches)) {
		assertType('array{string}', $matches);
	}
	assertType('array{}|array{string}', $matches);

	if (preg_match('/Price: (£|€)\d+/', $s, $matches)) {
		assertType('array{string, non-empty-string}', $matches);
	} else {
		assertType('array{}', $matches);
	}
	assertType('array{}|array{string, non-empty-string}', $matches);

	if (preg_match('/Price: (£|€)(\d+)/i', $s, $matches)) {
		assertType('array{string, non-empty-string, numeric-string}', $matches);
	}
	assertType('array{}|array{string, non-empty-string, numeric-string}', $matches);

	if (preg_match('  /Price: (£|€)\d+/ i u', $s, $matches)) {
		assertType('array{string, non-empty-string}', $matches);
	}
	assertType('array{}|array{string, non-empty-string}', $matches);

	if (preg_match('(Price: (£|€))i', $s, $matches)) {
		assertType('array{string, non-empty-string}', $matches);
	}
	assertType('array{}|array{string, non-empty-string}', $matches);

	if (preg_match('_foo(.)\_i_i', $s, $matches)) {
		assertType('array{string, non-empty-string}', $matches);
	}
	assertType('array{}|array{string, non-empty-string}', $matches);

	if (preg_match('/(a)(b)*(c)(d)*/', $s, $matches)) {
		assertType("array{0: string, 1: 'a', 2: string, 3: 'c', 4?: non-empty-string}", $matches);
	}
	assertType("array{}|array{0: string, 1: 'a', 2: string, 3: 'c', 4?: non-empty-string}", $matches);

	if (preg_match('/(a)(?<name>b)*(c)(d)*/', $s, $matches)) {
		assertType("array{0: string, 1: 'a', name: string, 2: string, 3: 'c', 4?: non-empty-string}", $matches);
	}
	assertType("array{}|array{0: string, 1: 'a', name: string, 2: string, 3: 'c', 4?: non-empty-string}", $matches);

	if (preg_match('/(a)(b)*(c)(?<name>d)*/', $s, $matches)) {
		assertType("array{0: string, 1: 'a', 2: string, 3: 'c', name?: non-empty-string, 4?: non-empty-string}", $matches);
	}
	assertType("array{}|array{0: string, 1: 'a', 2: string, 3: 'c', name?: non-empty-string, 4?: non-empty-string}", $matches);

	if (preg_match('/(a|b)|(?:c)/', $s, $matches)) {
		assertType('array{0: string, 1?: non-empty-string}', $matches);
	}
	assertType('array{}|array{0: string, 1?: non-empty-string}', $matches);

	if (preg_match('/(foo)(bar)(baz)+/', $s, $matches)) {
		assertType("array{string, 'foo', 'bar', non-falsy-string}", $matches);
	}
	assertType("array{}|array{string, 'foo', 'bar', non-falsy-string}", $matches);

	if (preg_match('/(foo)(bar)(baz)*/', $s, $matches)) {
		assertType("array{0: string, 1: 'foo', 2: 'bar', 3?: non-falsy-string}", $matches);
	}
	assertType("array{}|array{0: string, 1: 'foo', 2: 'bar', 3?: non-falsy-string}", $matches);

	if (preg_match('/(foo)(bar)(baz)?/', $s, $matches)) {
		assertType("array{0: string, 1: 'foo', 2: 'bar', 3?: 'baz'}", $matches);
	}
	assertType("array{}|array{0: string, 1: 'foo', 2: 'bar', 3?: 'baz'}", $matches);

	if (preg_match('/(foo)(bar)(baz){0,3}/', $s, $matches)) {
		assertType("array{0: string, 1: 'foo', 2: 'bar', 3?: non-falsy-string}", $matches);
	}
	assertType("array{}|array{0: string, 1: 'foo', 2: 'bar', 3?: non-falsy-string}", $matches);

	if (preg_match('/(foo)(bar)(baz){2,3}/', $s, $matches)) {
		assertType("array{string, 'foo', 'bar', non-falsy-string}", $matches);
	}
	assertType("array{}|array{string, 'foo', 'bar', non-falsy-string}", $matches);

	if (preg_match('/(foo)(bar)(baz){2}/', $s, $matches)) {
		assertType("array{string, 'foo', 'bar', non-falsy-string}", $matches);
	}
	assertType("array{}|array{string, 'foo', 'bar', non-falsy-string}", $matches);
}

function doNonCapturingGroup(string $s): void {
	if (preg_match('/Price: (?:£|€)(\d+)/', $s, $matches)) {
		assertType('array{string, numeric-string}', $matches);
	}
	assertType('array{}|array{string, numeric-string}', $matches);
}

function doNamedSubpattern(string $s): void {
	if (preg_match('/\w-(?P<num>\d+)-(\w)/', $s, $matches)) {
		assertType('array{0: string, num: numeric-string, 1: numeric-string, 2: non-empty-string}', $matches);
	}
	assertType('array{}|array{0: string, num: numeric-string, 1: numeric-string, 2: non-empty-string}', $matches);

	if (preg_match('/^(?<name>\S+::\S+)/', $s, $matches)) {
		assertType('array{0: string, name: non-falsy-string, 1: non-falsy-string}', $matches);
	}
	assertType('array{}|array{0: string, name: non-falsy-string, 1: non-falsy-string}', $matches);

	if (preg_match('/^(?<name>\S+::\S+)(?:(?<dataname> with data set (?:#\d+|"[^"]+"))\s\()?/', $s, $matches)) {
		assertType('array{0: string, name: non-falsy-string, 1: non-falsy-string, dataname?: non-falsy-string, 2?: non-falsy-string}', $matches);
	}
	assertType('array{}|array{0: string, name: non-falsy-string, 1: non-falsy-string, dataname?: non-falsy-string, 2?: non-falsy-string}', $matches);
}

function doOffsetCapture(string $s): void {
	if (preg_match('/(foo)(bar)(baz)/', $s, $matches, PREG_OFFSET_CAPTURE)) {
		assertType("array{array{string, int<-1, max>}, array{'foo', int<-1, max>}, array{'bar', int<-1, max>}, array{'baz', int<-1, max>}}", $matches);
	}
	assertType("array{}|array{array{string, int<-1, max>}, array{'foo', int<-1, max>}, array{'bar', int<-1, max>}, array{'baz', int<-1, max>}}", $matches);
}

function doUnknownFlags(string $s, int $flags): void {
	if (preg_match('/(foo)(bar)(baz)/xyz', $s, $matches, $flags)) {
		assertType('array<array{string|null, int<-1, max>}|string|null>', $matches);
	}
	assertType('array<array{string|null, int<-1, max>}|string|null>', $matches);
}

function doMultipleAlternativeCaptureGroupsWithSameNameWithModifier(string $s): void {
	if (preg_match('/(?J)(?<Foo>[a-z]+)|(?<Foo>[0-9]+)/', $s, $matches)) {
		assertType("array{0: string, Foo: non-empty-string, 1: non-empty-string}|array{0: string, Foo: numeric-string, 1: '', 2: numeric-string}", $matches);
	}
	assertType("array{}|array{0: string, Foo: non-empty-string, 1: non-empty-string}|array{0: string, Foo: numeric-string, 1: '', 2: numeric-string}", $matches);
}

function doMultipleConsecutiveCaptureGroupsWithSameNameWithModifier(string $s): void {
	if (preg_match('/(?J)(?<Foo>[a-z]+)|(?<Foo>[0-9]+)/', $s, $matches)) {
		assertType("array{0: string, Foo: non-empty-string, 1: non-empty-string}|array{0: string, Foo: numeric-string, 1: '', 2: numeric-string}", $matches);
	}
	assertType("array{}|array{0: string, Foo: non-empty-string, 1: non-empty-string}|array{0: string, Foo: numeric-string, 1: '', 2: numeric-string}", $matches);
}

// https://github.com/hoaproject/Regex/issues/31
function hoaBug31(string $s): void {
	if (preg_match('/([\w-])/', $s, $matches)) {
		assertType('array{string, non-empty-string}', $matches);
	}
	assertType('array{}|array{string, non-empty-string}', $matches);

	if (preg_match('/\w-(\d+)-(\w)/', $s, $matches)) {
		assertType('array{string, numeric-string, non-empty-string}', $matches);
	}
	assertType('array{}|array{string, numeric-string, non-empty-string}', $matches);
}

// https://github.com/phpstan/phpstan/issues/10855#issuecomment-2044323638
function testHoaUnsupportedRegexSyntax(string $s): void {
	if (preg_match('#\QPHPDoc type array<string> of property App\Log::$fillable is not covariant with PHPDoc type array<int, string> of overridden property Illuminate\Database\E\\\\\QEloquent\Model::$fillable.\E#', $s, $matches)) {
		assertType('array{string}', $matches);
	}
	assertType('array{}|array{string}', $matches);
}

function testPregMatchSimpleCondition(string $value): void {
	if (preg_match('/%env\((.*)\:.*\)%/U', $value, $matches)) {
		assertType('array{string, string}', $matches);
	}
}


function testPregMatchIdenticalToOne(string $value): void {
	if (preg_match('/%env\((.*)\:.*\)%/U', $value, $matches) === 1) {
		assertType('array{string, string}', $matches);
	}
}

function testPregMatchIdenticalToOneFalseyContext(string $value): void {
	if (!(preg_match('/%env\((.*)\:.*\)%/U', $value, $matches) !== 1)) {
		assertType('array{string, string}', $matches);
	}
}

function testPregMatchIdenticalToOneInverted(string $value): void {
	if (1 === preg_match('/%env\((.*)\:.*\)%/U', $value, $matches)) {
		assertType('array{string, string}', $matches);
	}
}

function testPregMatchIdenticalToOneFalseyContextInverted(string $value): void {
	if (!(1 !== preg_match('/%env\((.*)\:.*\)%/U', $value, $matches))) {
		assertType('array{string, string}', $matches);
	}
}

function testPregMatchEqualToOne(string $value): void {
	if (preg_match('/%env\((.*)\:.*\)%/U', $value, $matches) == 1) {
		assertType('array{string, string}', $matches);
	}
}

function testPregMatchEqualToOneFalseyContext(string $value): void {
	if (!(preg_match('/%env\((.*)\:.*\)%/U', $value, $matches) != 1)) {
		assertType('array{string, string}', $matches);
	}
}

function testPregMatchEqualToOneInverted(string $value): void {
	if (1 == preg_match('/%env\((.*)\:.*\)%/U', $value, $matches)) {
		assertType('array{string, string}', $matches);
	}
}

function testPregMatchEqualToOneFalseyContextInverted(string $value): void {
	if (!(1 != preg_match('/%env\((.*)\:.*\)%/U', $value, $matches))) {
		assertType('array{string, string}', $matches);
	}
}

function testUnionPattern(string $s): void
{
	if (rand(0,1)) {
		$pattern = '/Price: (\d+)/i';
	} else {
		$pattern = '/Price: (\d+)(\d+)(\d+)/';
	}
	if (preg_match($pattern, $s, $matches)) {
		assertType('array{string, numeric-string, numeric-string, numeric-string}|array{string, numeric-string}', $matches);
	}
	assertType('array{}|array{string, numeric-string, numeric-string, numeric-string}|array{string, numeric-string}', $matches);
}

function doFoo(string $row): void
{
	if (preg_match('~^(a(b))$~', $row, $matches) === 1) {
		assertType("array{string, 'ab', 'b'}", $matches);
	}
	if (preg_match('~^(a(b)?)$~', $row, $matches) === 1) {
		assertType("array{0: string, 1: non-falsy-string, 2?: 'b'}", $matches);
	}
	if (preg_match('~^(a(b)?)?$~', $row, $matches) === 1) {
		assertType("array{0: string, 1?: non-falsy-string, 2?: 'b'}", $matches);
	}
}

function doFoo2(string $row): void
{
	if (preg_match('~^((?<branchCode>\\d{1,6})-)?(?<accountNumber>\\d{1,10})/(?<bankCode>\\d{4})$~', $row, $matches) !== 1) {
		return;
	}

	assertType("array{0: string, 1: string, branchCode: ''|numeric-string, 2: ''|numeric-string, accountNumber: numeric-string, 3: numeric-string, bankCode: non-falsy-string&numeric-string, 4: non-falsy-string&numeric-string}", $matches);
}

function doFoo3(string $row): void
{
	if (preg_match('~^(02,([\d.]{10}),(\d+),(\d+),(\d+),)(\d+)$~', $row, $matches) !== 1) {
		return;
	}

	assertType('array{string, non-falsy-string, non-falsy-string, numeric-string, numeric-string, numeric-string, numeric-string}', $matches);
}

function (string $size): void {
	if (preg_match('~^a\.b(c(\d+)(\d+)(\s+))?d~', $size, $matches) !== 1) {
		throw new InvalidArgumentException(sprintf('Invalid size "%s"', $size));
	}
	assertType('array{string, non-falsy-string, numeric-string, numeric-string, non-empty-string}|array{string}', $matches);
};

function (string $size): void {
	if (preg_match('~^a\.b(c(\d+))?d~', $size, $matches) !== 1) {
		throw new InvalidArgumentException(sprintf('Invalid size "%s"', $size));
	}
	assertType('array{string, non-falsy-string, numeric-string}|array{string}', $matches);
};

function (string $size): void {
	if (preg_match('~^a\.b(c(\d+)?)d~', $size, $matches) !== 1) {
		throw new InvalidArgumentException(sprintf('Invalid size "%s"', $size));
	}
	assertType('array{0: string, 1: non-falsy-string, 2?: numeric-string}', $matches);
};

function (string $size): void {
	if (preg_match('~^a\.b(c(\d+)?)?d~', $size, $matches) !== 1) {
		throw new InvalidArgumentException(sprintf('Invalid size "%s"', $size));
	}
	assertType('array{0: string, 1?: non-falsy-string, 2?: numeric-string}', $matches);
};

function (string $size): void {
	if (preg_match('~^a\.b(c(\d+))d~', $size, $matches) !== 1) {
		throw new InvalidArgumentException(sprintf('Invalid size "%s"', $size));
	}
	assertType('array{string, non-falsy-string, numeric-string}', $matches);
};

function (string $size): void {
	if (preg_match('~^a\.(b)?(c)?d~', $size, $matches) !== 1) {
		throw new InvalidArgumentException(sprintf('Invalid size "%s"', $size));
	}
	assertType("array{0: string, 1?: ''|'b', 2?: 'c'}", $matches);
};

function (string $size): void {
	if (preg_match('~^(?:(\\d+)x(\\d+)|(\\d+)|x(\\d+))$~', $size, $matches) !== 1) {
		throw new InvalidArgumentException(sprintf('Invalid size "%s"', $size));
	}
	assertType("array{string, '', '', '', numeric-string}|array{string, '', '', numeric-string}|array{string, numeric-string, numeric-string}", $matches);
};

function (string $size): void {
	if (preg_match('~^(?:(\\d+)x(\\d+)|(\\d+)|x(\\d+))?$~', $size, $matches) !== 1) {
		throw new InvalidArgumentException(sprintf('Invalid size "%s"', $size));
	}
	assertType("array{string, '', '', '', numeric-string}|array{string, '', '', numeric-string}|array{string, numeric-string, numeric-string}|array{string}", $matches);
};

function (string $size): void {
	if (preg_match('~\{(?:(include)\\s+(?:[$]?\\w+(?<!file))\\s)|(?:(include\\s+file)\\s+(?:[$]?\\w+)\\s)|(?:(include(?:Template|(?:\\s+file)))\\s+(?:\'?.*?\.latte\'?)\\s)~', $size, $matches) !== 1) {
		throw new InvalidArgumentException(sprintf('Invalid size "%s"', $size));
	}
	assertType("array{string, '', '', non-falsy-string}|array{string, '', non-falsy-string}|array{string, 'include'}", $matches);
};


function bug11277a(string $value): void
{
	if (preg_match('/^\[(.+,?)*\]$/', $value, $matches)) {
		assertType('array{0: string, 1?: non-empty-string}', $matches);
		if (count($matches) === 2) {
			assertType('array{string, string}', $matches); // could be array{string, non-empty-string}
		}
	}
}

function bug11277b(string $value): void
{
	if (preg_match('/^(?:(.+,?)|(x))*$/', $value, $matches)) {
		assertType("array{0: string, 1?: non-empty-string}|array{string, '', non-empty-string}", $matches);
	}
}

// https://www.pcre.org/current/doc/html/pcre2pattern.html#dupgroupnumber
// https://3v4l.org/09qdT
function bug11291(string $s): void {
	if (preg_match('/(?|(a)|(b)(c)|(d)(e)(f))/', $s, $matches)) {
		assertType('array{0: string, 1: non-empty-string, 2?: non-empty-string, 3?: non-empty-string}', $matches);
	} else {
		assertType('array{}', $matches);
	}
	assertType('array{}|array{0: string, 1: non-empty-string, 2?: non-empty-string, 3?: non-empty-string}', $matches);
}

function bug11323a(string $s): void
{
	if (preg_match('/Price: (?P<currency>£|€)\d+/', $s, $matches)) {
		assertType('array{0: string, currency: non-empty-string, 1: non-empty-string}', $matches);
	} else {
		assertType('array{}', $matches);
	}
	assertType('array{}|array{0: string, currency: non-empty-string, 1: non-empty-string}', $matches);
}

function bug11323b(string $s): void
{
	if (preg_match('/Price: (?<currency>£|€)\d+/', $s, $matches)) {
		assertType('array{0: string, currency: non-empty-string, 1: non-empty-string}', $matches);
	} else {
		assertType('array{}', $matches);
	}
	assertType('array{}|array{0: string, currency: non-empty-string, 1: non-empty-string}', $matches);
}

function unmatchedAsNullWithMandatoryGroup(string $s): void {
	if (preg_match('/Price: (?<currency>£|€)\d+/', $s, $matches, PREG_UNMATCHED_AS_NULL)) {
		assertType('array{0: string, currency: non-empty-string, 1: non-empty-string}', $matches);
	} else {
		assertType('array{}', $matches);
	}
	assertType('array{}|array{0: string, currency: non-empty-string, 1: non-empty-string}', $matches);
}

function (string $s): void {
	if (preg_match('{' . preg_quote('xxx') . '(z)}', $s, $matches)) {
		assertType("array{string, 'z'}", $matches);
	} else {
		assertType('array{}', $matches);
	}
	assertType("array{}|array{string, 'z'}", $matches);
};

function (string $s): void {
	if (preg_match('{' . preg_quote($s) . '(z)}', $s, $matches)) {
		assertType("array{string, 'z'}", $matches);
	} else {
		assertType('array{}', $matches);
	}
	assertType("array{}|array{string, 'z'}", $matches);
};

function (string $s): void {
	if (preg_match('/' . preg_quote($s, '/') . '(\d)/', $s, $matches)) {
		assertType('array{string, numeric-string}', $matches);
	} else {
		assertType('array{}', $matches);
	}
	assertType('array{}|array{string, numeric-string}', $matches);
};

function (string $s): void {
	if (preg_match('{' . preg_quote($s) . '(z)' . preg_quote($s) . '(?:abc)(def)?}', $s, $matches)) {
		assertType("array{0: string, 1: 'z', 2?: 'def'}", $matches);
	} else {
		assertType('array{}', $matches);
	}
	assertType("array{}|array{0: string, 1: 'z', 2?: 'def'}", $matches);
};

function (string $s, $mixed): void {
	if (preg_match('{' . preg_quote($s) . '(z)' . preg_quote($s) . '(?:abc)'. $mixed .'(def)?}', $s, $matches)) {
		assertType('array<string>', $matches);
	} else {
		assertType('array{}', $matches);
	}
	assertType('array<string>', $matches);
};

function (string $s): void {
	if (preg_match('/^%([0-9]*\$)?[0-9]*\.?[0-9]*([sbdeEfFgGhHouxX])$/', $s, $matches) === 1) {
		assertType("array{string, string, 'b'|'d'|'E'|'e'|'F'|'f'|'G'|'g'|'H'|'h'|'o'|'s'|'u'|'X'|'x'}", $matches);
	}
};

function (string $s): void {
	if (preg_match('~^((\\d{1,6})-)$~', $s, $matches) === 1) {
		assertType("array{string, non-falsy-string, numeric-string}", $matches);
	}
};

function (string $s): void {
	if (preg_match('~^((\\d{1,6}).)$~', $s, $matches) === 1) {
		assertType("array{string, non-falsy-string, numeric-string}", $matches);
	}
};

function (string $s): void {
	if (preg_match('~^([157])$~', $s, $matches) === 1) {
		assertType("array{string, '1'|'5'|'7'}", $matches);
	}
};

function (string $s): void {
	if (preg_match('~^([157XY])$~', $s, $matches) === 1) {
		assertType("array{string, '1'|'5'|'7'|'X'|'Y'}", $matches);
	}
};

function bug11323(string $s): void {
	if (preg_match('/([*|+?{}()]+)([^*|+[:digit:]?{}()]+)/', $s, $matches)) {
		assertType('array{string, non-empty-string, non-empty-string}', $matches);
	}
	if (preg_match('/\p{L}[[\]]+([-*|+?{}(?:)]+)([^*|+[:digit:]?{a-z}(\p{L})\a-]+)/', $s, $matches)) {
		assertType('array{string, non-empty-string, non-empty-string}', $matches);
	}
	if (preg_match('{([-\p{L}[\]*|\x03\a\b+?{}(?:)-]+[^[:digit:]?{}a-z0-9#-k]+)(a-z)}', $s, $matches)) {
		assertType("array{string, non-falsy-string, 'a-z'}", $matches);
	}
	if (preg_match('{(\d+)(?i)insensitive((?x-i)case SENSITIVE here(?i:insensitive non-capturing group))}', $s, $matches)) {
		assertType('array{string, numeric-string, non-falsy-string}', $matches);
	}
	if (preg_match('{([]] [^]])}', $s, $matches)) {
		assertType('array{string, non-falsy-string}', $matches);
	}
	if (preg_match('{([[:digit:]])}', $s, $matches)) {
		assertType('array{string, numeric-string}', $matches);
	}
	if (preg_match('{([\d])(\d)}', $s, $matches)) {
		assertType('array{string, numeric-string, numeric-string}', $matches);
	}
	if (preg_match('{([0-9])}', $s, $matches)) {
		assertType('array{string, numeric-string}', $matches);
	}
	if (preg_match('{(\p{L})(\p{P})(\p{Po})}', $s, $matches)) {
		assertType('array{string, non-empty-string, non-empty-string, non-empty-string}', $matches);
	}
	if (preg_match('{(a)??(b)*+(c++)(d)+?}', $s, $matches)) {
		assertType("array{string, ''|'a', string, non-empty-string, non-empty-string}", $matches);
	}
	if (preg_match('{(.\d)}', $s, $matches)) {
		assertType('array{string, non-falsy-string}', $matches);
	}
	if (preg_match('{(\d.)}', $s, $matches)) {
		assertType('array{string, non-falsy-string}', $matches);
	}
	if (preg_match('{(\d\d)}', $s, $matches)) {
		assertType('array{string, non-falsy-string&numeric-string}', $matches);
	}
	if (preg_match('{(.(\d))}', $s, $matches)) {
		assertType('array{string, non-falsy-string, numeric-string}', $matches);
	}
	if (preg_match('{((\d).)}', $s, $matches)) {
		assertType('array{string, non-falsy-string, numeric-string}', $matches);
	}
	if (preg_match('{(\d([1-4])\d)}', $s, $matches)) {
		assertType('array{string, non-falsy-string&numeric-string, numeric-string}', $matches);
	}
	if (preg_match('{(x?([1-4])\d)}', $s, $matches)) {
		assertType('array{string, non-falsy-string, numeric-string}', $matches);
	}
	if (preg_match('{([^1-4])}', $s, $matches)) {
		assertType('array{string, non-empty-string}', $matches);
	}
	if (preg_match("{([\r\n]+)(\n)([\n])}", $s, $matches)) {
		assertType('array{string, non-empty-string, "\n", "\n"}', $matches);
	}
	if (preg_match('/foo(*:first)|bar(*:second)([x])/', $s, $matches)) {
		assertType("array{0: string, 1?: 'x', MARK?: 'first'|'second'}", $matches);
	}
}

function (string $s): void {
	preg_match('/%a(\d*)/', $s, $matches);
	assertType("array{0?: string, 1?: ''|numeric-string}", $matches);
};

class Bug11376
{
	public function test(string $str): void
	{
		preg_match('~^(?:(\w+)::)?(\w+)$~', $str, $matches);
		assertType('array{0?: string, 1?: string, 2?: non-empty-string}', $matches);
	}

	public function test2(string $str): void
	{
		if (preg_match('~^(?:(\w+)::)?(\w+)$~', $str, $matches) === 1) {
			assertType('array{string, string, non-empty-string}', $matches);
		}
	}
}

function (string $s): void {
	if (rand(0,1)) {
		$p = '/Price: (£)(abc)/';
	} else {
		$p = '/Price: (\d)(b)/';
	}

	if (preg_match($p, $s, $matches)) {
		assertType("array{string, '£', 'abc'}|array{string, numeric-string, 'b'}", $matches);
	}
};

function (string $s): void {
	if (rand(0,1)) {
		$p = '/Price: (£)/';
	} else {
		$p = '/Price: (£|(\d)|(x))/';
	}

	if (preg_match($p, $s, $matches)) {
		assertType("array{0: string, 1: non-empty-string, 2?: ''|numeric-string, 3?: 'x'}", $matches);
	}
};

function (string $s): void {
	if (preg_match('/Price: ([a-z])/i', $s, $matches)) {
		assertType("array{string, non-empty-string}", $matches);
	}
};

function (string $s): void {
	if (preg_match('/Price: ([0-9])/i', $s, $matches)) {
		assertType("array{string, numeric-string}", $matches);
	}
};

function (string $s): void {
	if (preg_match('/Price: ([xXa])/i', $s, $matches)) {
		assertType("array{string, non-empty-string}", $matches);
	}
};

function (string $s): void {
	if (preg_match('/Price: ([xXa])/', $s, $matches)) {
		assertType("array{string, 'a'|'X'|'x'}", $matches);
	}
};

function (string $s): void {
	if (preg_match('/Price: (ba[rz])/', $s, $matches)) {
		assertType("array{string, 'bar'|'baz'}", $matches);
	}
};

function (string $s): void {
	if (preg_match('/Price: (b[ao][mn])/', $s, $matches)) {
		assertType("array{string, 'bam'|'ban'|'bom'|'bon'}", $matches);
	}
};

function (string $s): void {
	if (preg_match('/Price: (\s{3}|0)/', $s, $matches)) {
		assertType("array{string, non-empty-string}", $matches);
	}
};

function (string $s): void {
	if (preg_match('/Price: (a|0)/', $s, $matches)) {
		assertType("array{string, non-empty-string}", $matches);
	}
};

function (string $s): void {
	if (preg_match('/Price: (aa|0)/', $s, $matches)) {
		assertType("array{string, non-empty-string}", $matches);
	}
};

function (string $s): void {
	if (preg_match('/( \d+ )/x', $s, $matches)) {
		assertType('array{string, numeric-string}', $matches);
	}
};

function (string $s): void {
	if (preg_match('/( .? )/x', $s, $matches)) {
		assertType('array{string, string}', $matches);
	}
};

function (string $s): void {
	if (preg_match('/( .* )/x', $s, $matches)) {
		assertType('array{string, string}', $matches);
	}
};

function (string $s): void {
	if (preg_match('/( .+ )/x', $s, $matches)) {
		assertType('array{string, non-empty-string}', $matches);
	}
};

function (string $value): void
{
	if (preg_match('/^(x)*$/', $value, $matches, PREG_OFFSET_CAPTURE)) {
		assertType("array{0: array{string, int<-1, max>}, 1?: array{non-empty-string, int<-1, max>}}", $matches);
	}
};

function (string $value): void {
	if (preg_match('/^(?:(x)|(y))*$/', $value, $matches, PREG_OFFSET_CAPTURE)) {
		assertType("array{0: array{string, int<-1, max>}, 1?: array{non-empty-string, int<-1, max>}}|array{array{string, int<-1, max>}, array{'', int<-1, max>}, array{non-empty-string, int<-1, max>}}", $matches);
	}
};

class Bug11479
{
	static public function sayHello(string $source): void
	{
		$pattern = "~^(?P<dateFrom>\d)?\-?(?P<dateTo>\d)?$~";

		preg_match($pattern, $source, $matches);

		// for $source = "-1" in $matches is
		// array (
		//  0 => '-1',
		//  'dateFrom' => '',
		//  1 => '',
		//  'dateTo' => '1',
		//  2 => '1',
		//)

		assertType("array{0?: string, dateFrom?: ''|numeric-string, 1?: ''|numeric-string, dateTo?: numeric-string, 2?: numeric-string}", $matches);
	}
}

function (string $s): void {
	if (preg_match('~a|(\d)|(\s)~', $s, $matches) === 1) {
		assertType("array{0: string, 1?: numeric-string}|array{string, '', non-empty-string}", $matches);
	}
};

function (string $s): void {
	if (preg_match('~a|((u)x)|((v)y)~', $s, $matches) === 1) {
		assertType("array{string, '', '', 'vy', 'v'}|array{string, 'ux', 'u'}|array{string}", $matches);
	}
};

function (string $s): void {
	if (preg_match('~a|(\d)|(\s)~', $s, $matches, PREG_OFFSET_CAPTURE) === 1) {
		assertType("array{0: array{string, int<-1, max>}, 1?: array{numeric-string, int<-1, max>}}|array{array{string, int<-1, max>}, array{'', int<-1, max>}, array{non-empty-string, int<-1, max>}}", $matches);
	}
};

function (string $s): void {
	preg_match('~a|(\d)|(\s)~', $s, $matches);
	assertType("array{0?: string, 1?: '', 2?: non-empty-string}|array{0?: string, 1?: numeric-string}", $matches);
};

function bug11490 (string $expression): void {
	$matches = [];

	if (preg_match('/([-+])?([\d]+)%/', $expression, $matches) === 1) {
		assertType("array{string, ''|'+'|'-', numeric-string}", $matches);
	}
}

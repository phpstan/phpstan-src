<?php declare(strict_types=1);

namespace PrintfParamTypes;

class FooStringable implements \Stringable
{
	public function __toString(): string
	{
		return 'foo';
	}
}

$stream = fopen('php://stdout', 'w');
// Error
printf('%d', new FooStringable());
printf('%d', rand() ? new FooStringable() : 5);
printf('%f', new FooStringable());
echo sprintf('%d', new FooStringable());
fprintf($stream, '%f', new FooStringable());
printf('%*s', '5', 'a');
printf('%*s', 5.0, 'a');
printf('%*s', new \SimpleXMLElement('<a>7</a>'), 'a');
printf('%*s', null, 'a');
printf('%*s', true, 'a');
printf('%.*s', '5', 'a');
printf('%2$s %3$.*s', '1', 5, 'a'); // * is the first ordinary placeholder, so it matches '1'
printf('%1$-\'X10.2f', new FooStringable());
printf('%s %1$*.*f', new FooStringable(), 5, 2);
printf('%3$f', 1, 2, new FooStringable());
printf('%1$f %1$d', new FooStringable());
printf('%1$*d', 5.5);

// Strict error
printf('%d', 1.23);
printf('%d', rand() ? 1.23 : 1);
printf('%d', 'a');
printf('%d', '1.23');
printf('%d', null);
printf('%d', true);
printf('%d', new \SimpleXMLElement('<a>aaa</a>'));

printf('%f', '1.2345678901234567890123456789013245678901234567989');
printf('%f', null);
printf('%f', true);
printf('%f', new \SimpleXMLElement('<a>aaa</a>'));

printf('%s', null);
printf('%s', true);

// Error, but already reported by CallToFunctionParametersRule
printf('%d', new \stdClass());
printf('%s', []);

// Error, but already reported by PrintfParametersRule
printf('%s');
printf('%s', 1, 2);

// OK
printf('%s', 'a');
printf('%s', new FooStringable());
printf('%d', 1);
printf('%f', 1);
printf('%f', 1.1);
printf('%*s', 5, 'a');
printf('%2$*s', 5, 'a');
printf('%s %2$*s', 'a', 5, 'a');
printf('%1$-+\'X10.2f', 5);
printf('%1$*.*f %s %2$d', 5, 6, new FooStringable()); // 5.000000 foo 6

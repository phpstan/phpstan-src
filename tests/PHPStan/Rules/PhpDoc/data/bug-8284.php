<?php declare(strict_types = 1);

namespace Bug8284;

class HelloWorld
{
    /**
     * @param string|string[] $pattern
     * @param ($callable is PREG_OFFSET_CAPTURE ? (callable(array<int|string, array{string|null, int<-1,max>}>):string) : (callable(array<int|string, string|null>):string)) $replacement
     * @param string $subject
     * @param int             $count Set by method
     * @param int-mask<PREG_UNMATCHED_AS_NULL|PREG_OFFSET_CAPTURE> $flags PREG_OFFSET_CAPTURE is supported, PREG_UNMATCHED_AS_NULL is always set
     */
    public static function replaceCallback($pattern, callable $replacement, $subject, int $limit = -1, int &$count = null, int $flags = 0): string
    {
        if (!is_scalar($subject)) {
            throw new \TypeError('');
        }

        $result = preg_replace_callback($pattern, $replacement, $subject, $limit, $count, $flags | PREG_UNMATCHED_AS_NULL);
        if ($result === null) {
            throw new \RuntimeException;
        }

        return $result;
    }
}

function () {
	HelloWorld::replaceCallback('{a+}', function ($match): string {
		\PHPStan\dumpType($match);
		return (string)$match[0][0];
	}, 'abcaddsa', -1, $count, PREG_OFFSET_CAPTURE);

	HelloWorld::replaceCallback('{a+}', function ($match): string {
		\PHPStan\dumpType($match);
		return (string)$match[0];
	}, 'abcaddsa', -1, $count);
};

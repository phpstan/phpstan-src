<?php declare(strict_types = 1);

namespace Bug3499;

class JwtException extends \RuntimeException{}

final class JwtUtils{

	/**
	 * @return string[]
	 * @phpstan-return array{string, string, string}
	 * @throws JwtException
	 */
	public static function split(string $jwt) : array{
		$v = explode(".", $jwt);
		if(count($v) !== 3){
			throw new JwtException("Expected exactly 3 JWT parts, got " . count($v));
		}
		return $v;
	}
}

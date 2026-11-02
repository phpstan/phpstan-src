<?php declare(strict_types = 1);

namespace Bug15649;

use function PHPStan\Testing\assertType;

/**
 * Two nested row shapes of 127 keys each: 258 values counted recursively,
 * over the limit, although no single shape comes close to it.
 *
 * @phpstan-type Booking array{
 *     version: ''|int,
 *     name: string,
 *     ints: list<array{
 *         a1: int, a2: int, a3: int, a4: int, a5: int, a6: int, a7: int, a8: int,
 *         a9: int, a10: int, a11: int, a12: int, a13: int, a14: int, a15: int, a16: int,
 *         a17: int, a18: int, a19: int, a20: int, a21: int, a22: int, a23: int, a24: int,
 *         a25: int, a26: int, a27: int, a28: int, a29: int, a30: int, a31: int, a32: int,
 *         a33: int, a34: int, a35: int, a36: int, a37: int, a38: int, a39: int, a40: int,
 *         a41: int, a42: int, a43: int, a44: int, a45: int, a46: int, a47: int, a48: int,
 *         a49: int, a50: int, a51: int, a52: int, a53: int, a54: int, a55: int, a56: int,
 *         a57: int, a58: int, a59: int, a60: int, a61: int, a62: int, a63: int, a64: int,
 *         a65: int, a66: int, a67: int, a68: int, a69: int, a70: int, a71: int, a72: int,
 *         a73: int, a74: int, a75: int, a76: int, a77: int, a78: int, a79: int, a80: int,
 *         a81: int, a82: int, a83: int, a84: int, a85: int, a86: int, a87: int, a88: int,
 *         a89: int, a90: int, a91: int, a92: int, a93: int, a94: int, a95: int, a96: int,
 *         a97: int, a98: int, a99: int, a100: int, a101: int, a102: int, a103: int, a104: int,
 *         a105: int, a106: int, a107: int, a108: int, a109: int, a110: int, a111: int, a112: int,
 *         a113: int, a114: int, a115: int, a116: int, a117: int, a118: int, a119: int, a120: int,
 *         a121: int, a122: int, a123: int, a124: int, a125: int, a126: int, a127: int
 *     }>,
 *     strings: list<array{
 *         b1: string, b2: string, b3: string, b4: string, b5: string, b6: string, b7: string, b8: string,
 *         b9: string, b10: string, b11: string, b12: string, b13: string, b14: string, b15: string, b16: string,
 *         b17: string, b18: string, b19: string, b20: string, b21: string, b22: string, b23: string, b24: string,
 *         b25: string, b26: string, b27: string, b28: string, b29: string, b30: string, b31: string, b32: string,
 *         b33: string, b34: string, b35: string, b36: string, b37: string, b38: string, b39: string, b40: string,
 *         b41: string, b42: string, b43: string, b44: string, b45: string, b46: string, b47: string, b48: string,
 *         b49: string, b50: string, b51: string, b52: string, b53: string, b54: string, b55: string, b56: string,
 *         b57: string, b58: string, b59: string, b60: string, b61: string, b62: string, b63: string, b64: string,
 *         b65: string, b66: string, b67: string, b68: string, b69: string, b70: string, b71: string, b72: string,
 *         b73: string, b74: string, b75: string, b76: string, b77: string, b78: string, b79: string, b80: string,
 *         b81: string, b82: string, b83: string, b84: string, b85: string, b86: string, b87: string, b88: string,
 *         b89: string, b90: string, b91: string, b92: string, b93: string, b94: string, b95: string, b96: string,
 *         b97: string, b98: string, b99: string, b100: string, b101: string, b102: string, b103: string, b104: string,
 *         b105: string, b106: string, b107: string, b108: string, b109: string, b110: string, b111: string, b112: string,
 *         b113: string, b114: string, b115: string, b116: string, b117: string, b118: string, b119: string, b120: string,
 *         b121: string, b122: string, b123: string, b124: string, b125: string, b126: string, b127: string
 *     }>,
 * }
 */
class Foo
{

	/** @param Booking $data */
	public function mergedAfterIfElse(array $data): void
	{
		if ($data['version'] === '') {
			$version = null;
		} else {
			$version = $data['version'];
		}

		assertType("''|int", $data['version']);
		assertType('string', $data['name']);
		assertType('list<int>', array_column($data['ints'], 'a127'));
		assertType('list<string>', array_column($data['strings'], 'b1'));
	}

	/** @param Booking $data */
	public function earlyReturn(array $data): void
	{
		if ($data['version'] === '') {
			return;
		}

		assertType('int', $data['version']);
		assertType('string', $data['name']);
		assertType('list<int>', array_column($data['ints'], 'a127'));
		assertType('list<string>', array_column($data['strings'], 'b1'));
	}

}

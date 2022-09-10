<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use CurlShareHandle;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ResourceType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function constant;
use function count;
use function defined;
use function sprintf;
use function strtolower;
use const CURLOPT_SHARE;
use const CURLOPT_SSL_VERIFYHOST;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
class CurlSetOptRule implements Rule
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!($node->name instanceof Node\Name)) {
			return [];
		}

		$functionName = $this->reflectionProvider->resolveFunctionName($node->name, $scope);

		if ($functionName === null || strtolower($functionName) !== 'curl_setopt') {
			return [];
		}

		$args = $node->getArgs();
		if (count($args) !== 3) {
			return [];
		}

		$optType = $scope->getType($args[1]->value);
		if (!$optType instanceof ConstantIntegerType) {
			return [];
		}

		$expectedType = $this->getValueType($optType->getValue());
		if ($expectedType === null) {
			return [];
		}

		$valueType = $scope->getType($args[2]->value);
		if (!$expectedType->isSuperTypeOf($valueType)->yes()) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Call to curl_setopt expects parameter 3 to be %s, %s given.',
					$expectedType->describe(VerbosityLevel::precise()),
					$valueType->describe(VerbosityLevel::precise()),
				))->line($node->getLine())->build(),
			];
		}

		return [];
	}

	private function getValueType(int $curlOpt): ?Type
	{
		if (defined('CURLOPT_SHARE') && $curlOpt === CURLOPT_SHARE && $this->reflectionProvider->hasClass(CurlShareHandle::class)) {
			return new ObjectType(CurlShareHandle::class);
		}

		if (defined('CURLOPT_SSL_VERIFYHOST') && $curlOpt === CURLOPT_SSL_VERIFYHOST) {
			return new UnionType([new ConstantIntegerType(0), new ConstantIntegerType(2)]);
		}

		$boolConstants = [
			'CURLOPT_AUTOREFERER',
			'CURLOPT_COOKIESESSION',
			'CURLOPT_CERTINFO',
			'CURLOPT_CONNECT_ONLY',
			'CURLOPT_CRLF',
			'CURLOPT_DISALLOW_USERNAME_IN_URL',
			'CURLOPT_DNS_SHUFFLE_ADDRESSES',
			'CURLOPT_HAPROXYPROTOCOL',
			'CURLOPT_SSH_COMPRESSION',
			'CURLOPT_DNS_USE_GLOBAL_CACHE',
			'CURLOPT_FAILONERROR',
			'CURLOPT_SSL_FALSESTART',
			'CURLOPT_FILETIME',
			'CURLOPT_FOLLOWLOCATION',
			'CURLOPT_FORBID_REUSE',
			'CURLOPT_FRESH_CONNECT',
			'CURLOPT_FTP_USE_EPRT',
			'CURLOPT_FTP_USE_EPSV',
			'CURLOPT_FTP_CREATE_MISSING_DIRS',
			'CURLOPT_FTPAPPEND',
			'CURLOPT_TCP_NODELAY',
			'CURLOPT_FTPASCII',
			'CURLOPT_FTPLISTONLY',
			'CURLOPT_HEADER',
			'CURLOPT_HTTP09_ALLOWED',
			'CURLOPT_HTTPGET',
			'CURLOPT_HTTPPROXYTUNNEL',
			'CURLOPT_HTTP_CONTENT_DECODING',
			'CURLOPT_KEEP_SENDING_ON_ERROR',
			'CURLOPT_MUTE',
			'CURLOPT_NETRC',
			'CURLOPT_NOBODY',
			'CURLOPT_NOPROGRESS',
			'CURLOPT_NOSIGNAL',
			'CURLOPT_PATH_AS_IS',
			'CURLOPT_PIPEWAIT',
			'CURLOPT_POST',
			'CURLOPT_PUT',
			'CURLOPT_RETURNTRANSFER',
			'CURLOPT_SASL_IR',
			'CURLOPT_SSL_ENABLE_ALPN',
			'CURLOPT_SSL_ENABLE_NPN',
			'CURLOPT_SSL_VERIFYPEER',
			'CURLOPT_SSL_VERIFYSTATUS',
			'CURLOPT_PROXY_SSL_VERIFYPEER',
			'CURLOPT_SUPPRESS_CONNECT_HEADERS',
			'CURLOPT_TCP_FASTOPEN',
			'CURLOPT_TFTP_NO_OPTIONS',
			'CURLOPT_TRANSFERTEXT',
			'CURLOPT_UNRESTRICTED_AUTH',
			'CURLOPT_UPLOAD',
			'CURLOPT_VERBOSE',
		];
		foreach ($boolConstants as $constName) {
			if (defined($constName) && constant($constName) === $curlOpt) {
				return new BooleanType();
			}
		}

		$intConstants = [
			'CURLOPT_BUFFERSIZE',
			'CURLOPT_CONNECTTIMEOUT',
			'CURLOPT_CONNECTTIMEOUT_MS',
			'CURLOPT_DNS_CACHE_TIMEOUT',
			'CURLOPT_EXPECT_100_TIMEOUT_MS',
			'CURLOPT_HAPPY_EYEBALLS_TIMEOUT_MS',
			'CURLOPT_FTPSSLAUTH',
			'CURLOPT_HEADEROPT',
			'CURLOPT_HTTP_VERSION',
			'CURLOPT_HTTPAUTH',
			'CURLOPT_INFILESIZE',
			'CURLOPT_LOW_SPEED_LIMIT',
			'CURLOPT_LOW_SPEED_TIME',
			'CURLOPT_MAXCONNECTS',
			'CURLOPT_MAXREDIRS',
			'CURLOPT_PORT',
			'CURLOPT_POSTREDIR',
			'CURLOPT_PROTOCOLS',
			'CURLOPT_PROXYAUTH',
			'CURLOPT_PROXYPORT',
			'CURLOPT_PROXYTYPE',
			'CURLOPT_REDIR_PROTOCOLS',
			'CURLOPT_RESUME_FROM',
			'CURLOPT_SOCKS5_AUTH',
			'CURLOPT_SSL_OPTIONS',
			'CURLOPT_SSL_VERIFYHOST',
			'CURLOPT_SSLVERSION',
			'CURLOPT_PROXY_SSL_OPTIONS',
			'CURLOPT_PROXY_SSL_VERIFYHOST',
			'CURLOPT_PROXY_SSLVERSION',
			'CURLOPT_STREAM_WEIGHT',
			'CURLOPT_TCP_KEEPALIVE',
			'CURLOPT_TCP_KEEPIDLE',
			'CURLOPT_TCP_KEEPINTVL',
			'CURLOPT_TIMECONDITION',
			'CURLOPT_TIMEOUT',
			'CURLOPT_TIMEOUT_MS',
			'CURLOPT_TIMEVALUE',
			'CURLOPT_TIMEVALUE_LARGE',
			'CURLOPT_MAX_RECV_SPEED_LARGE',
			'CURLOPT_SSH_AUTH_TYPES',
			'CURLOPT_IPRESOLVE',
			'CURLOPT_FTP_FILEMETHOD',
		];
		foreach ($intConstants as $constName) {
			if (defined($constName) && constant($constName) === $curlOpt) {
				return new IntegerType();
			}
		}

		$stringConstants = [
			'CURLOPT_ABSTRACT_UNIX_SOCKET',
			'CURLOPT_CAINFO',
			'CURLOPT_CAPATH',
			'CURLOPT_COOKIE',
			'CURLOPT_COOKIEFILE',
			'CURLOPT_COOKIEJAR',
			'CURLOPT_COOKIELIST',
			'CURLOPT_CUSTOMREQUEST',
			'CURLOPT_DEFAULT_PROTOCOL',
			'CURLOPT_DNS_INTERFACE',
			'CURLOPT_DNS_LOCAL_IP4',
			'CURLOPT_DNS_LOCAL_IP6',
			'CURLOPT_EGDSOCKET',
			'CURLOPT_ENCODING',
			'CURLOPT_FTPPORT',
			'CURLOPT_INTERFACE',
			'CURLOPT_KEYPASSWD',
			'CURLOPT_KRB4LEVEL',
			'CURLOPT_LOGIN_OPTIONS',
			'CURLOPT_PINNEDPUBLICKEY',
			'CURLOPT_POSTFIELDS',
			'CURLOPT_PRIVATE',
			'CURLOPT_PRE_PROXY',
			'CURLOPT_PROXY',
			'CURLOPT_PROXY_SERVICE_NAME',
			'CURLOPT_PROXY_CAINFO',
			'CURLOPT_PROXY_CAPATH',
			'CURLOPT_PROXY_CRLFILE',
			'CURLOPT_PROXY_KEYPASSWD',
			'CURLOPT_PROXY_PINNEDPUBLICKEY',
			'CURLOPT_PROXY_SSLCERT',
			'CURLOPT_PROXY_SSLCERTTYPE',
			'CURLOPT_PROXY_SSL_CIPHER_LIST',
			'CURLOPT_PROXY_TLS13_CIPHERS',
			'CURLOPT_PROXY_SSLKEY',
			'CURLOPT_PROXY_SSLKEYTYPE',
			'CURLOPT_PROXY_TLSAUTH_PASSWORD',
			'CURLOPT_PROXY_TLSAUTH_TYPE',
			'CURLOPT_PROXY_TLSAUTH_USERNAME',
			'CURLOPT_PROXYUSERPWD',
			'CURLOPT_RANDOM_FILE',
			'CURLOPT_RANGE',
			'CURLOPT_REFERER',
			'CURLOPT_SERVICE_NAME',
			'CURLOPT_SSH_HOST_PUBLIC_KEY_MD5',
			'CURLOPT_SSH_PUBLIC_KEYFILE',
			'CURLOPT_SSH_PRIVATE_KEYFILE',
			'CURLOPT_SSL_CIPHER_LIST',
			'CURLOPT_SSLCERT',
			'CURLOPT_SSLCERTPASSWD',
			'CURLOPT_SSLCERTTYPE',
			'CURLOPT_SSLENGINE',
			'CURLOPT_SSLENGINE_DEFAULT',
			'CURLOPT_SSLKEY',
			'CURLOPT_SSLKEYPASSWD',
			'CURLOPT_SSLKEYTYPE',
			'CURLOPT_TLS13_CIPHERS',
			'CURLOPT_UNIX_SOCKET_PATH',
			'CURLOPT_URL',
			'CURLOPT_USERAGENT',
			'CURLOPT_USERNAME',
			'CURLOPT_PASSWORD',
			'CURLOPT_USERPWD',
			'CURLOPT_XOAUTH2_BEARER',
		];
		foreach ($stringConstants as $constName) {
			if (defined($constName) && constant($constName) === $curlOpt) {
				return TypeCombinator::intersect(
					new StringType(),
					new AccessoryNonEmptyStringType(),
				);
			}
		}

		$arrayConstants = [
			'CURLOPT_CONNECT_TO',
			'CURLOPT_HTTP200ALIASES',
			'CURLOPT_HTTPHEADER',
			'CURLOPT_POSTQUOTE',
			'CURLOPT_PROXYHEADER',
			'CURLOPT_QUOTE',
			'CURLOPT_RESOLVE',
		];
		foreach ($arrayConstants as $constName) {
			if (defined($constName) && constant($constName) === $curlOpt) {
				return new ArrayType(new MixedType(), new MixedType());
			}
		}

		$resourceConstants = [
			'CURLOPT_FILE',
			'CURLOPT_INFILE',
			'CURLOPT_STDERR',
			'CURLOPT_WRITEHEADER',
		];
		foreach ($resourceConstants as $constName) {
			if (defined($constName) && constant($constName) === $curlOpt) {
				return new ResourceType();
			}
		}

		return null;
	}

}

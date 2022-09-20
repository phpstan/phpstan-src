<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\Php\DummyParameter;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CallableType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\IntegerType;
use PHPStan\Type\LateResolvableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ResourceType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;
use function array_key_last;
use function array_slice;
use function constant;
use function count;
use function defined;
use function sprintf;
use const ARRAY_FILTER_USE_BOTH;
use const ARRAY_FILTER_USE_KEY;
use const CURLOPT_SSL_VERIFYHOST;

/** @api */
class ParametersAcceptorSelector
{

	/**
	 * @template T of ParametersAcceptor
	 * @param T[] $parametersAcceptors
	 * @return T
	 */
	public static function selectSingle(
		array $parametersAcceptors,
	): ParametersAcceptor
	{
		$count = count($parametersAcceptors);
		if ($count === 0) {
			throw new ShouldNotHappenException(
				'getVariants() must return at least one variant.',
			);
		}
		if ($count !== 1) {
			throw new ShouldNotHappenException('Multiple variants - use selectFromArgs() instead.');
		}

		return $parametersAcceptors[0];
	}

	/**
	 * @param Node\Arg[] $args
	 * @param ParametersAcceptor[] $parametersAcceptors
	 */
	public static function selectFromArgs(
		Scope $scope,
		array $args,
		array $parametersAcceptors,
	): ParametersAcceptor
	{
		$types = [];
		$unpack = false;
		if (
			count($args) > 0
			&& count($parametersAcceptors) > 0
		) {
			$arrayMapArgs = $args[0]->value->getAttribute('arrayMapArgs');
			if ($arrayMapArgs !== null) {
				$acceptor = $parametersAcceptors[0];
				$parameters = $acceptor->getParameters();
				$callbackParameters = [];
				foreach ($arrayMapArgs as $arg) {
					$callbackParameters[] = new DummyParameter('item', self::getIterableValueType($scope->getType($arg->value)), false, PassedByReference::createNo(), false, null);
				}
				$parameters[0] = new NativeParameterReflection(
					$parameters[0]->getName(),
					$parameters[0]->isOptional(),
					new UnionType([
						new CallableType($callbackParameters, new MixedType(), false),
						new NullType(),
					]),
					$parameters[0]->passedByReference(),
					$parameters[0]->isVariadic(),
					$parameters[0]->getDefaultValue(),
				);
				$parametersAcceptors = [
					new FunctionVariant(
						$acceptor->getTemplateTypeMap(),
						$acceptor->getResolvedTemplateTypeMap(),
						$parameters,
						$acceptor->isVariadic(),
						$acceptor->getReturnType(),
					),
				];
			}

			if (count($args) >= 3 && (bool) $args[0]->getAttribute('isCurlSetOptArg')) {
				$optType = $scope->getType($args[1]->value);
				if ($optType instanceof ConstantIntegerType) {
					$optValueType = self::getCurlOptValueType($optType->getValue());

					if ($optValueType !== null) {
						$acceptor = $parametersAcceptors[0];
						$parameters = $acceptor->getParameters();

						$parameters[2] = new NativeParameterReflection(
							$parameters[2]->getName(),
							$parameters[2]->isOptional(),
							$optValueType,
							$parameters[2]->passedByReference(),
							$parameters[2]->isVariadic(),
							$parameters[2]->getDefaultValue(),
						);

						$parametersAcceptors = [
							new FunctionVariant(
								$acceptor->getTemplateTypeMap(),
								$acceptor->getResolvedTemplateTypeMap(),
								$parameters,
								$acceptor->isVariadic(),
								$acceptor->getReturnType(),
							),
						];
					}
				}
			}

			if (isset($args[0]) && (bool) $args[0]->getAttribute('isArrayFilterArg')) {
				if (isset($args[2])) {
					$mode = $scope->getType($args[2]->value);
					if ($mode instanceof ConstantIntegerType) {
						if ($mode->getValue() === ARRAY_FILTER_USE_KEY) {
							$arrayFilterParameters = [
								new DummyParameter('key', self::getIterableKeyType($scope->getType($args[0]->value)), false, PassedByReference::createNo(), false, null),
							];
						} elseif ($mode->getValue() === ARRAY_FILTER_USE_BOTH) {
							$arrayFilterParameters = [
								new DummyParameter('item', self::getIterableValueType($scope->getType($args[0]->value)), false, PassedByReference::createNo(), false, null),
								new DummyParameter('key', self::getIterableKeyType($scope->getType($args[0]->value)), false, PassedByReference::createNo(), false, null),
							];
						}
					}
				}

				$acceptor = $parametersAcceptors[0];
				$parameters = $acceptor->getParameters();
				$parameters[1] = new NativeParameterReflection(
					$parameters[1]->getName(),
					$parameters[1]->isOptional(),
					new CallableType(
						$arrayFilterParameters ?? [
							new DummyParameter('item', self::getIterableValueType($scope->getType($args[0]->value)), false, PassedByReference::createNo(), false, null),
						],
						new MixedType(),
						false,
					),
					$parameters[1]->passedByReference(),
					$parameters[1]->isVariadic(),
					$parameters[1]->getDefaultValue(),
				);
				$parametersAcceptors = [
					new FunctionVariant(
						$acceptor->getTemplateTypeMap(),
						$acceptor->getResolvedTemplateTypeMap(),
						$parameters,
						$acceptor->isVariadic(),
						$acceptor->getReturnType(),
					),
				];
			}

			if (isset($args[0]) && (bool) $args[0]->getAttribute('isArrayWalkArg')) {
				$arrayWalkParameters = [
					new DummyParameter('item', self::getIterableValueType($scope->getType($args[0]->value)), false, PassedByReference::createReadsArgument(), false, null),
					new DummyParameter('key', self::getIterableKeyType($scope->getType($args[0]->value)), false, PassedByReference::createNo(), false, null),
				];
				if (isset($args[2])) {
					$arrayWalkParameters[] = new DummyParameter('arg', $scope->getType($args[2]->value), false, PassedByReference::createNo(), false, null);
				}

				$acceptor = $parametersAcceptors[0];
				$parameters = $acceptor->getParameters();
				$parameters[1] = new NativeParameterReflection(
					$parameters[1]->getName(),
					$parameters[1]->isOptional(),
					new CallableType($arrayWalkParameters, new MixedType(), false),
					$parameters[1]->passedByReference(),
					$parameters[1]->isVariadic(),
					$parameters[1]->getDefaultValue(),
				);
				$parametersAcceptors = [
					new FunctionVariant(
						$acceptor->getTemplateTypeMap(),
						$acceptor->getResolvedTemplateTypeMap(),
						$parameters,
						$acceptor->isVariadic(),
						$acceptor->getReturnType(),
					),
				];
			}
		}

		if (count($parametersAcceptors) === 1) {
			$acceptor = $parametersAcceptors[0];
			if (!self::hasAcceptorTemplateOrLateResolvableType($acceptor)) {
				return $acceptor;
			}
		}

		foreach ($args as $i => $arg) {
			$type = $scope->getType($arg->value);
			$index = $arg->name !== null ? $arg->name->toString() : $i;
			if ($arg->unpack) {
				$unpack = true;
				$types[$index] = $type->getIterableValueType();
			} else {
				$types[$index] = $type;
			}
		}

		return self::selectFromTypes($types, $parametersAcceptors, $unpack);
	}

	private static function hasAcceptorTemplateOrLateResolvableType(ParametersAcceptor $acceptor): bool
	{
		if (self::hasTemplateOrLateResolvableType($acceptor->getReturnType())) {
			return true;
		}

		foreach ($acceptor->getParameters() as $parameter) {
			if (!self::hasTemplateOrLateResolvableType($parameter->getType())) {
				continue;
			}

			return true;
		}

		return false;
	}

	private static function hasTemplateOrLateResolvableType(Type $type): bool
	{
		$has = false;
		TypeTraverser::map($type, static function (Type $type, callable $traverse) use (&$has): Type {
			if ($type instanceof TemplateType || $type instanceof LateResolvableType) {
				$has = true;
				return $type;
			}

			return $traverse($type);
		});

		return $has;
	}

	/**
	 * @param array<int|string, Type> $types
	 * @param ParametersAcceptor[] $parametersAcceptors
	 */
	public static function selectFromTypes(
		array $types,
		array $parametersAcceptors,
		bool $unpack,
	): ParametersAcceptor
	{
		if (count($parametersAcceptors) === 1) {
			return GenericParametersAcceptorResolver::resolve($types, $parametersAcceptors[0]);
		}

		if (count($parametersAcceptors) === 0) {
			throw new ShouldNotHappenException(
				'getVariants() must return at least one variant.',
			);
		}

		$typesCount = count($types);
		$acceptableAcceptors = [];

		foreach ($parametersAcceptors as $parametersAcceptor) {
			if ($unpack) {
				$acceptableAcceptors[] = $parametersAcceptor;
				continue;
			}

			$functionParametersMinCount = 0;
			$functionParametersMaxCount = 0;
			foreach ($parametersAcceptor->getParameters() as $parameter) {
				if (!$parameter->isOptional()) {
					$functionParametersMinCount++;
				}

				$functionParametersMaxCount++;
			}

			if ($typesCount < $functionParametersMinCount) {
				continue;
			}

			if (
				!$parametersAcceptor->isVariadic()
				&& $typesCount > $functionParametersMaxCount
			) {
				continue;
			}

			$acceptableAcceptors[] = $parametersAcceptor;
		}

		if (count($acceptableAcceptors) === 0) {
			return GenericParametersAcceptorResolver::resolve($types, self::combineAcceptors($parametersAcceptors));
		}

		if (count($acceptableAcceptors) === 1) {
			return GenericParametersAcceptorResolver::resolve($types, $acceptableAcceptors[0]);
		}

		$winningAcceptors = [];
		$winningCertainty = null;
		foreach ($acceptableAcceptors as $acceptableAcceptor) {
			$isSuperType = TrinaryLogic::createYes();
			$acceptableAcceptor = GenericParametersAcceptorResolver::resolve($types, $acceptableAcceptor);
			foreach ($acceptableAcceptor->getParameters() as $i => $parameter) {
				if (!isset($types[$i])) {
					if (!$unpack || count($types) <= 0) {
						break;
					}

					$type = $types[array_key_last($types)];
				} else {
					$type = $types[$i];
				}

				if ($parameter->getType() instanceof MixedType) {
					$isSuperType = $isSuperType->and(TrinaryLogic::createMaybe());
				} else {
					$isSuperType = $isSuperType->and($parameter->getType()->isSuperTypeOf($type));
				}
			}

			if ($isSuperType->no()) {
				continue;
			}

			if ($winningCertainty === null) {
				$winningAcceptors[] = $acceptableAcceptor;
				$winningCertainty = $isSuperType;
			} else {
				$comparison = $winningCertainty->compareTo($isSuperType);
				if ($comparison === $isSuperType) {
					$winningAcceptors = [$acceptableAcceptor];
					$winningCertainty = $isSuperType;
				} elseif ($comparison === null) {
					$winningAcceptors[] = $acceptableAcceptor;
				}
			}
		}

		if (count($winningAcceptors) === 0) {
			return GenericParametersAcceptorResolver::resolve($types, self::combineAcceptors($acceptableAcceptors));
		}

		return GenericParametersAcceptorResolver::resolve($types, self::combineAcceptors($winningAcceptors));
	}

	/**
	 * @param ParametersAcceptor[] $acceptors
	 */
	public static function combineAcceptors(array $acceptors): ParametersAcceptor
	{
		if (count($acceptors) === 0) {
			throw new ShouldNotHappenException(
				'getVariants() must return at least one variant.',
			);
		}
		if (count($acceptors) === 1) {
			return $acceptors[0];
		}

		$minimumNumberOfParameters = null;
		foreach ($acceptors as $acceptor) {
			$acceptorParametersMinCount = 0;
			foreach ($acceptor->getParameters() as $parameter) {
				if ($parameter->isOptional()) {
					continue;
				}

				$acceptorParametersMinCount++;
			}

			if ($minimumNumberOfParameters !== null && $minimumNumberOfParameters <= $acceptorParametersMinCount) {
				continue;
			}

			$minimumNumberOfParameters = $acceptorParametersMinCount;
		}

		$parameters = [];
		$isVariadic = false;
		$returnType = null;

		foreach ($acceptors as $acceptor) {
			if ($returnType === null) {
				$returnType = $acceptor->getReturnType();
			} else {
				$returnType = TypeCombinator::union($returnType, $acceptor->getReturnType());
			}
			$isVariadic = $isVariadic || $acceptor->isVariadic();

			foreach ($acceptor->getParameters() as $i => $parameter) {
				if (!isset($parameters[$i])) {
					$parameters[$i] = new NativeParameterReflection(
						$parameter->getName(),
						$i + 1 > $minimumNumberOfParameters,
						$parameter->getType(),
						$parameter->passedByReference(),
						$parameter->isVariadic(),
						$parameter->getDefaultValue(),
					);
					continue;
				}

				$isVariadic = $parameters[$i]->isVariadic() || $parameter->isVariadic();
				$defaultValueLeft = $parameters[$i]->getDefaultValue();
				$defaultValueRight = $parameter->getDefaultValue();
				if ($defaultValueLeft !== null && $defaultValueRight !== null) {
					$defaultValue = TypeCombinator::union($defaultValueLeft, $defaultValueRight);
				} else {
					$defaultValue = null;
				}

				$parameters[$i] = new NativeParameterReflection(
					$parameters[$i]->getName() !== $parameter->getName() ? sprintf('%s|%s', $parameters[$i]->getName(), $parameter->getName()) : $parameter->getName(),
					$i + 1 > $minimumNumberOfParameters,
					TypeCombinator::union($parameters[$i]->getType(), $parameter->getType()),
					$parameters[$i]->passedByReference()->combine($parameter->passedByReference()),
					$isVariadic,
					$defaultValue,
				);

				if ($isVariadic) {
					$parameters = array_slice($parameters, 0, $i + 1);
					break;
				}
			}
		}

		return new FunctionVariant(
			TemplateTypeMap::createEmpty(),
			null,
			$parameters,
			$isVariadic,
			$returnType,
		);
	}

	private static function getIterableValueType(Type $type): Type
	{
		if ($type instanceof UnionType) {
			$types = [];
			foreach ($type->getTypes() as $innerType) {
				$iterableValueType = $innerType->getIterableValueType();
				if ($iterableValueType instanceof ErrorType) {
					continue;
				}

				$types[] = $iterableValueType;
			}

			return TypeCombinator::union(...$types);
		}

		return $type->getIterableValueType();
	}

	private static function getIterableKeyType(Type $type): Type
	{
		if ($type instanceof UnionType) {
			$types = [];
			foreach ($type->getTypes() as $innerType) {
				$iterableKeyType = $innerType->getIterableKeyType();
				if ($iterableKeyType instanceof ErrorType) {
					continue;
				}

				$types[] = $iterableKeyType;
			}

			return TypeCombinator::union(...$types);
		}

		return $type->getIterableKeyType();
	}

	private static function getCurlOptValueType(int $curlOpt): ?Type
	{
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

		// unknown constant
		return null;
	}

}

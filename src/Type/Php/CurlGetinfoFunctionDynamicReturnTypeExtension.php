<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use const CURLINFO_APPCONNECT_TIME;
use const CURLINFO_CERTINFO;
use const CURLINFO_CONDITION_UNMET;
use const CURLINFO_CONNECT_TIME;
use const CURLINFO_CONTENT_LENGTH_DOWNLOAD;
use const CURLINFO_CONTENT_LENGTH_UPLOAD;
use const CURLINFO_CONTENT_TYPE;
use const CURLINFO_COOKIELIST;
use const CURLINFO_EFFECTIVE_URL;
use const CURLINFO_FILETIME;
use const CURLINFO_FTP_ENTRY_PATH;
use const CURLINFO_HEADER_OUT;
use const CURLINFO_HEADER_SIZE;
use const CURLINFO_HTTP_CONNECTCODE;
use const CURLINFO_HTTP_VERSION;
use const CURLINFO_HTTPAUTH_AVAIL;
use const CURLINFO_LOCAL_IP;
use const CURLINFO_LOCAL_PORT;
use const CURLINFO_NAMELOOKUP_TIME;
use const CURLINFO_NUM_CONNECTS;
use const CURLINFO_OS_ERRNO;
use const CURLINFO_PRETRANSFER_TIME;
use const CURLINFO_PRIMARY_IP;
use const CURLINFO_PRIMARY_PORT;
use const CURLINFO_PRIVATE;
use const CURLINFO_PROTOCOL;
use const CURLINFO_PROXY_SSL_VERIFYRESULT;
use const CURLINFO_PROXYAUTH_AVAIL;
use const CURLINFO_REDIRECT_COUNT;
use const CURLINFO_REDIRECT_TIME;
use const CURLINFO_REDIRECT_URL;
use const CURLINFO_REQUEST_SIZE;
use const CURLINFO_RESPONSE_CODE;
use const CURLINFO_RTSP_CLIENT_CSEQ;
use const CURLINFO_RTSP_CSEQ_RECV;
use const CURLINFO_RTSP_SERVER_CSEQ;
use const CURLINFO_RTSP_SESSION_ID;
use const CURLINFO_SCHEME;
use const CURLINFO_SIZE_DOWNLOAD;
use const CURLINFO_SIZE_UPLOAD;
use const CURLINFO_SPEED_DOWNLOAD;
use const CURLINFO_SPEED_UPLOAD;
use const CURLINFO_SSL_ENGINES;
use const CURLINFO_SSL_VERIFYRESULT;
use const CURLINFO_STARTTRANSFER_TIME;
use const CURLINFO_TOTAL_TIME;

final class CurlGetinfoFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	/** @var array<int,Type>|null */
	private ?array $componentTypesPairedConstants = null;

	/** @var array<string,Type>|null */
	private ?array $componentTypesPairedStrings = null;

	private ?Type $allComponentsTogetherType = null;

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'curl_getinfo';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		if (count($functionCall->getArgs()) < 1) {
			return ParametersAcceptorSelector::selectSingle(
				$functionReflection->getVariants(),
			)->getReturnType();
		}

		$this->cacheReturnTypes();

		if (count($functionCall->getArgs()) > 1) {
			$componentType = $scope->getType($functionCall->getArgs()[1]->value);

			if (!$componentType instanceof ConstantType) {
				return $this->createAllComponentsReturnType();
			}

			$componentType = $componentType->toInteger();
			if (!$componentType instanceof ConstantIntegerType) {
				throw new ShouldNotHappenException();
			}
		} else {
			$componentType = new ConstantIntegerType(-1);
		}

		if ($componentType->getValue() === -1) {
			return $this->createAllComponentsReturnType();
		}

		return $this->componentTypesPairedConstants[$componentType->getValue()] ?? new ConstantBooleanType(false);
	}

	/**
	 * @throws ShouldNotHappenException
	 */
	private function createAllComponentsReturnType(): Type
	{
		if ($this->allComponentsTogetherType === null) {
			$returnTypes = [
				new ConstantBooleanType(false),
			];

			$builder = ConstantArrayTypeBuilder::createEmpty();

			if ($this->componentTypesPairedStrings === null) {
				throw new ShouldNotHappenException();
			}

			foreach ($this->componentTypesPairedStrings as $componentName => $componentValueType) {
				$builder->setOffsetValueType(new ConstantStringType($componentName), $componentValueType, true);
			}

			$returnTypes[] = $builder->getArray();

			$this->allComponentsTogetherType = TypeCombinator::union(...$returnTypes);
		}

		return $this->allComponentsTogetherType;
	}

	private function cacheReturnTypes(): void
	{
		if ($this->componentTypesPairedConstants !== null) {
			return;
		}

		$string = new StringType();
		$integer = new IntegerType();
		$float = new FloatType();
		$false = new ConstantBooleanType(false);
		$null = new NullType();

		$stringOrNull = TypeCombinator::union($string, $null);
		$stringOrFalse = TypeCombinator::union($string, $false);
		$arrayOfIntegerKeyedStrings = new ArrayType($integer, $string);
		$arrayOfArrayIntegerKeyedStrings = new ArrayType($integer, $arrayOfIntegerKeyedStrings);

		$this->componentTypesPairedConstants = [
			CURLINFO_EFFECTIVE_URL => $string,
			CURLINFO_FILETIME => $integer,
			CURLINFO_TOTAL_TIME => $float,
			CURLINFO_NAMELOOKUP_TIME => $float,
			CURLINFO_CONNECT_TIME => $float,
			CURLINFO_PRETRANSFER_TIME => $float,
			CURLINFO_STARTTRANSFER_TIME => $float,
			CURLINFO_REDIRECT_COUNT => $integer,
			CURLINFO_REDIRECT_TIME => $float,
			CURLINFO_REDIRECT_URL => $string,
			CURLINFO_PRIMARY_IP => $string,
			CURLINFO_PRIMARY_PORT => $integer,
			CURLINFO_LOCAL_IP => $string,
			CURLINFO_LOCAL_PORT => $integer,
			CURLINFO_SIZE_UPLOAD => $float,
			CURLINFO_SIZE_DOWNLOAD => $float,
			CURLINFO_SPEED_DOWNLOAD => $float,
			CURLINFO_SPEED_UPLOAD => $float,
			CURLINFO_HEADER_SIZE => $float,
			CURLINFO_HEADER_OUT => $stringOrFalse,
			CURLINFO_REQUEST_SIZE => $float,
			CURLINFO_SSL_VERIFYRESULT => $integer,
			CURLINFO_CONTENT_LENGTH_DOWNLOAD => $float,
			CURLINFO_CONTENT_LENGTH_UPLOAD => $float,
			CURLINFO_CONTENT_TYPE => $string,
			CURLINFO_PRIVATE => $stringOrFalse,
			CURLINFO_RESPONSE_CODE => $integer,
			CURLINFO_HTTP_CONNECTCODE => $integer,
			CURLINFO_HTTPAUTH_AVAIL => $integer,
			CURLINFO_PROXYAUTH_AVAIL => $integer,
			CURLINFO_OS_ERRNO => $integer,
			CURLINFO_NUM_CONNECTS => $integer,
			CURLINFO_SSL_ENGINES => $arrayOfIntegerKeyedStrings,
			CURLINFO_COOKIELIST => $arrayOfIntegerKeyedStrings,
			CURLINFO_FTP_ENTRY_PATH => $stringOrFalse,
			CURLINFO_APPCONNECT_TIME => $float,
			CURLINFO_CERTINFO => $arrayOfArrayIntegerKeyedStrings,
			CURLINFO_CONDITION_UNMET => $integer,
			CURLINFO_RTSP_CLIENT_CSEQ => $integer,
			CURLINFO_RTSP_CSEQ_RECV => $integer,
			CURLINFO_RTSP_SERVER_CSEQ => $integer,
			CURLINFO_RTSP_SESSION_ID => $integer,
			CURLINFO_HTTP_VERSION => $integer,
			CURLINFO_PROTOCOL => $string,
			CURLINFO_PROXY_SSL_VERIFYRESULT => $integer,
			CURLINFO_SCHEME => $string,
		];

		$this->componentTypesPairedStrings = [
			'url' => $string,
			'content_type' => $stringOrNull,
			'http_code' => $integer,
			'header_size' => $integer,
			'request_size' => $integer,
			'filetime' => $integer,
			'ssl_verify_result' => $integer,
			'redirect_count' => $integer,
			'total_time' => $float,
			'namelookup_time' => $float,
			'connect_time' => $float,
			'pretransfer_time' => $float,
			'size_upload' => $float,
			'size_download' => $float,
			'speed_download' => $float,
			'speed_upload' => $float,
			'download_content_length' => $float,
			'upload_content_length' => $float,
			'starttransfer_time' => $float,
			'redirect_time' => $float,
			'redirect_url' => $string,
			'primary_ip' => $string,
			'certinfo' => $arrayOfIntegerKeyedStrings,
			'primary_port' => $integer,
			'local_ip' => $string,
			'local_port' => $integer,
			'http_version' => $integer,
			'protocol' => $integer,
			'ssl_verifyresult' => $integer,
			'scheme' => $string,
		];
	}

}

<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function count;

final class CurlGetinfoFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

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

		if (count($functionCall->getArgs()) <= 1) {
			return $this->createAllComponentsReturnType();
		}

		$componentType = $scope->getType($functionCall->getArgs()[1]->value);
		if (!$componentType->isNull()->no()) {
			return $this->createAllComponentsReturnType();
		}

		$componentType = $componentType->toInteger();
		if (!$componentType instanceof ConstantIntegerType) {
			return $this->createAllComponentsReturnType();
		}

		$stringType = new StringType();
		$integerType = new IntegerType();
		$floatType = new FloatType();
		$falseType = new ConstantBooleanType(false);
		$stringFalseType = TypeCombinator::union($stringType, $falseType);
		$integerStringArrayType = new ArrayType($integerType, $stringType);
		$nestedStringStringArrayType = new ArrayType($integerType, new ArrayType($stringType, $stringType));

		$componentTypesPairedConstants = [
			'CURLINFO_EFFECTIVE_URL' => $stringType,
			'CURLINFO_FILETIME' => $integerType,
			'CURLINFO_TOTAL_TIME' => $floatType,
			'CURLINFO_NAMELOOKUP_TIME' => $floatType,
			'CURLINFO_CONNECT_TIME' => $floatType,
			'CURLINFO_PRETRANSFER_TIME' => $floatType,
			'CURLINFO_STARTTRANSFER_TIME' => $floatType,
			'CURLINFO_REDIRECT_COUNT' => $integerType,
			'CURLINFO_REDIRECT_TIME' => $floatType,
			'CURLINFO_REDIRECT_URL' => $stringType,
			'CURLINFO_PRIMARY_IP' => $stringType,
			'CURLINFO_PRIMARY_PORT' => $integerType,
			'CURLINFO_LOCAL_IP' => $stringType,
			'CURLINFO_LOCAL_PORT' => $integerType,
			'CURLINFO_SIZE_UPLOAD' => $integerType,
			'CURLINFO_SIZE_DOWNLOAD' => $integerType,
			'CURLINFO_SPEED_DOWNLOAD' => $integerType,
			'CURLINFO_SPEED_UPLOAD' => $integerType,
			'CURLINFO_HEADER_SIZE' => $integerType,
			'CURLINFO_HEADER_OUT' => $stringFalseType,
			'CURLINFO_REQUEST_SIZE' => $integerType,
			'CURLINFO_SSL_VERIFYRESULT' => $integerType,
			'CURLINFO_CONTENT_LENGTH_DOWNLOAD' => $floatType,
			'CURLINFO_CONTENT_LENGTH_UPLOAD' => $floatType,
			'CURLINFO_CONTENT_TYPE' => $stringFalseType,
			'CURLINFO_PRIVATE' => $stringFalseType,
			'CURLINFO_RESPONSE_CODE' => $integerType,
			'CURLINFO_HTTP_CONNECTCODE' => $integerType,
			'CURLINFO_HTTPAUTH_AVAIL' => $integerType,
			'CURLINFO_PROXYAUTH_AVAIL' => $integerType,
			'CURLINFO_OS_ERRNO' => $integerType,
			'CURLINFO_NUM_CONNECTS' => $integerType,
			'CURLINFO_SSL_ENGINES' => $integerStringArrayType,
			'CURLINFO_COOKIELIST' => $integerStringArrayType,
			'CURLINFO_FTP_ENTRY_PATH' => $stringFalseType,
			'CURLINFO_APPCONNECT_TIME' => $floatType,
			'CURLINFO_CERTINFO' => $nestedStringStringArrayType,
			'CURLINFO_CONDITION_UNMET' => $integerType,
			'CURLINFO_RTSP_CLIENT_CSEQ' => $integerType,
			'CURLINFO_RTSP_CSEQ_RECV' => $integerType,
			'CURLINFO_RTSP_SERVER_CSEQ' => $integerType,
			'CURLINFO_RTSP_SESSION_ID' => $integerType,
			'CURLINFO_HTTP_VERSION' => $integerType,
			'CURLINFO_PROTOCOL' => $stringType,
			'CURLINFO_PROXY_SSL_VERIFYRESULT' => $integerType,
			'CURLINFO_SCHEME' => $stringType,
			'CURLINFO_CONTENT_LENGTH_DOWNLOAD_T' => $integerType,
			'CURLINFO_CONTENT_LENGTH_UPLOAD_T' => $integerType,
			'CURLINFO_SIZE_DOWNLOAD_T' => $integerType,
			'CURLINFO_SIZE_UPLOAD_T' => $integerType,
			'CURLINFO_SPEED_DOWNLOAD_T' => $integerType,
			'CURLINFO_SPEED_UPLOAD_T' => $integerType,
			'CURLINFO_APPCONNECT_TIME_T' => $integerType,
			'CURLINFO_CONNECT_TIME_T' => $integerType,
			'CURLINFO_FILETIME_T' => $integerType,
			'CURLINFO_NAMELOOKUP_TIME_T' => $integerType,
			'CURLINFO_PRETRANSFER_TIME_T' => $integerType,
			'CURLINFO_REDIRECT_TIME_T' => $integerType,
			'CURLINFO_STARTTRANSFER_TIME_T' => $integerType,
			'CURLINFO_TOTAL_TIME_T' => $integerType,
		];

		foreach ($componentTypesPairedConstants as $constantName => $type) {
			$constantNameNode = new Name($constantName);
			if ($this->reflectionProvider->hasConstant($constantNameNode, $scope) === false) {
				continue;
			}

			$valueType = $this->reflectionProvider->getConstant($constantNameNode, $scope)->getValueType();
			if ($componentType->isSuperTypeOf($valueType)->yes()) {
				 return $type;
			}
		}

		return $falseType;
	}

	private function createAllComponentsReturnType(): Type
	{
		$returnTypes = [
			new ConstantBooleanType(false),
		];

		$builder = ConstantArrayTypeBuilder::createEmpty();

		$stringType = new StringType();
		$integerType = new IntegerType();
		$floatType = new FloatType();
		$stringOrNullType = TypeCombinator::union($stringType, new NullType());
		$nestedStringStringArrayType = new ArrayType($integerType, new ArrayType($stringType, $stringType));

		$componentTypesPairedStrings = [
			'url' => $stringType,
			'content_type' => $stringOrNullType,
			'http_code' => $integerType,
			'header_size' => $integerType,
			'request_size' => $integerType,
			'filetime' => $integerType,
			'ssl_verify_result' => $integerType,
			'redirect_count' => $integerType,
			'total_time' => $floatType,
			'namelookup_time' => $floatType,
			'connect_time' => $floatType,
			'pretransfer_time' => $floatType,
			'size_upload' => $floatType,
			'size_download' => $floatType,
			'speed_download' => $floatType,
			'speed_upload' => $floatType,
			'download_content_length' => $floatType,
			'upload_content_length' => $floatType,
			'starttransfer_time' => $floatType,
			'redirect_time' => $floatType,
			'redirect_url' => $stringType,
			'primary_ip' => $stringType,
			'certinfo' => $nestedStringStringArrayType,
			'primary_port' => $integerType,
			'local_ip' => $stringType,
			'local_port' => $integerType,
			'http_version' => $integerType,
			'protocol' => $integerType,
			'ssl_verifyresult' => $integerType,
			'scheme' => $stringType,
			'appconnect_time_us' => $integerType,
			'connect_time_us' => $integerType,
			'namelookup_time_us' => $integerType,
			'pretransfer_time_us' => $integerType,
			'redirect_time_us' => $integerType,
			'starttransfer_time_us' => $integerType,
			'total_time_us' => $integerType,
		];
		foreach ($componentTypesPairedStrings as $componentName => $componentValueType) {
			$builder->setOffsetValueType(new ConstantStringType($componentName), $componentValueType);
		}

		$returnTypes[] = $builder->getArray();

		return TypeUtils::toBenevolentUnion(TypeCombinator::union(...$returnTypes));
	}

}

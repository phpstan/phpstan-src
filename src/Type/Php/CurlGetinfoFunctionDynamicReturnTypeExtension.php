<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
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
use function strtolower;

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

	/**
	 * @throws ShouldNotHappenException
	 */
	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		if (count($functionCall->getArgs()) < 1) {
			return ParametersAcceptorSelector::selectSingle(
				$functionReflection->getVariants(),
			)->getReturnType();
		}

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

		$componentTypesPairedConstants = [
			'CURLINFO_EFFECTIVE_URL' => new StringType(),
			'CURLINFO_FILETIME' => new IntegerType(),
			'CURLINFO_TOTAL_TIME' => new FloatType(),
			'CURLINFO_NAMELOOKUP_TIME' => new FloatType(),
			'CURLINFO_CONNECT_TIME' => new FloatType(),
			'CURLINFO_PRETRANSFER_TIME' => new FloatType(),
			'CURLINFO_STARTTRANSFER_TIME' => new FloatType(),
			'CURLINFO_REDIRECT_COUNT' => new IntegerType(),
			'CURLINFO_REDIRECT_TIME' => new FloatType(),
			'CURLINFO_REDIRECT_URL' => new StringType(),
			'CURLINFO_PRIMARY_IP' => new StringType(),
			'CURLINFO_PRIMARY_PORT' => new IntegerType(),
			'CURLINFO_LOCAL_IP' => new StringType(),
			'CURLINFO_LOCAL_PORT' => new IntegerType(),
			'CURLINFO_SIZE_UPLOAD' => new IntegerType(),
			'CURLINFO_SIZE_DOWNLOAD' => new IntegerType(),
			'CURLINFO_SPEED_DOWNLOAD' => new IntegerType(),
			'CURLINFO_SPEED_UPLOAD' => new IntegerType(),
			'CURLINFO_HEADER_SIZE' => new IntegerType(),
			'CURLINFO_HEADER_OUT' => TypeCombinator::union(new StringType(), new ConstantBooleanType(false)),
			'CURLINFO_REQUEST_SIZE' => new IntegerType(),
			'CURLINFO_SSL_VERIFYRESULT' => new IntegerType(),
			'CURLINFO_CONTENT_LENGTH_DOWNLOAD' => new FloatType(),
			'CURLINFO_CONTENT_LENGTH_UPLOAD' => new FloatType(),
			'CURLINFO_CONTENT_TYPE' => new StringType(),
			'CURLINFO_PRIVATE' => TypeCombinator::union(new StringType(), new ConstantBooleanType(false)),
			'CURLINFO_RESPONSE_CODE' => new IntegerType(),
			'CURLINFO_HTTP_CONNECTCODE' => new IntegerType(),
			'CURLINFO_HTTPAUTH_AVAIL' => new IntegerType(),
			'CURLINFO_PROXYAUTH_AVAIL' => new IntegerType(),
			'CURLINFO_OS_ERRNO' => new IntegerType(),
			'CURLINFO_NUM_CONNECTS' => new IntegerType(),
			'CURLINFO_SSL_ENGINES' => new ArrayType(new IntegerType(), new StringType()),
			'CURLINFO_COOKIELIST' => new ArrayType(new IntegerType(), new StringType()),
			'CURLINFO_FTP_ENTRY_PATH' => TypeCombinator::union(new StringType(), new ConstantBooleanType(false)),
			'CURLINFO_APPCONNECT_TIME' => new FloatType(),
			'CURLINFO_CERTINFO' => new ArrayType(new IntegerType(), new ArrayType(new IntegerType(), new StringType())),
			'CURLINFO_CONDITION_UNMET' => new IntegerType(),
			'CURLINFO_RTSP_CLIENT_CSEQ' => new IntegerType(),
			'CURLINFO_RTSP_CSEQ_RECV' => new IntegerType(),
			'CURLINFO_RTSP_SERVER_CSEQ' => new IntegerType(),
			'CURLINFO_RTSP_SESSION_ID' => new IntegerType(),
			'CURLINFO_HTTP_VERSION' => new IntegerType(),
			'CURLINFO_PROTOCOL' => new StringType(),
			'CURLINFO_PROXY_SSL_VERIFYRESULT' => new IntegerType(),
			'CURLINFO_SCHEME' => new StringType(),
			'CURLINFO_CONTENT_LENGTH_DOWNLOAD_T' => new IntegerType(),
			'CURLINFO_CONTENT_LENGTH_UPLOAD_T' => new IntegerType(),
			'CURLINFO_SIZE_DOWNLOAD_T' => new IntegerType(),
			'CURLINFO_SIZE_UPLOAD_T' => new IntegerType(),
			'CURLINFO_SPEED_DOWNLOAD_T' => new IntegerType(),
			'CURLINFO_SPEED_UPLOAD_T' => new IntegerType(),
			'CURLINFO_APPCONNECT_TIME_T' => new IntegerType(),
			'CURLINFO_CONNECT_TIME_T' => new IntegerType(),
			'CURLINFO_FILETIME_T' => new IntegerType(),
			'CURLINFO_NAMELOOKUP_TIME_T' => new IntegerType(),
			'CURLINFO_PRETRANSFER_TIME_T' => new IntegerType(),
			'CURLINFO_REDIRECT_TIME_T' => new IntegerType(),
			'CURLINFO_STARTTRANSFER_TIME_T' => new IntegerType(),
			'CURLINFO_TOTAL_TIME_T' => new IntegerType(),
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

		return new ConstantBooleanType(false);
	}

	private function createAllComponentsReturnType(): Type
	{
		$returnTypes = [
			new ConstantBooleanType(false),
		];

		$builder = ConstantArrayTypeBuilder::createEmpty();
		$componentTypesPairedStrings = [
			'url' => new StringType(),
			'content_type' => TypeCombinator::union(new StringType(), new NullType()),
			'http_code' => new IntegerType(),
			'header_size' => new IntegerType(),
			'request_size' => new IntegerType(),
			'filetime' => new IntegerType(),
			'ssl_verify_result' => new IntegerType(),
			'redirect_count' => new IntegerType(),
			'total_time' => new FloatType(),
			'namelookup_time' => new FloatType(),
			'connect_time' => new FloatType(),
			'pretransfer_time' => new FloatType(),
			'size_upload' => new FloatType(),
			'size_download' => new FloatType(),
			'speed_download' => new FloatType(),
			'speed_upload' => new FloatType(),
			'download_content_length' => new FloatType(),
			'upload_content_length' => new FloatType(),
			'starttransfer_time' => new FloatType(),
			'redirect_time' => new FloatType(),
			'redirect_url' => new StringType(),
			'primary_ip' => new StringType(),
			'certinfo' => new ArrayType(new IntegerType(), new StringType()),
			'primary_port' => new IntegerType(),
			'local_ip' => new StringType(),
			'local_port' => new IntegerType(),
			'http_version' => new IntegerType(),
			'protocol' => new IntegerType(),
			'ssl_verifyresult' => new IntegerType(),
			'scheme' => new StringType(),
		];
		foreach ($componentTypesPairedStrings as $componentName => $componentValueType) {
			$builder->setOffsetValueType(new ConstantStringType($componentName), $componentValueType, true);
		}

		$returnTypes[] = $builder->getArray();

		return TypeCombinator::union(...$returnTypes);
	}

}

/*
 * PHPStanTurbo\InitializerExprTypeResolver — native implementation of
 * PHPStan\Reflection\InitializerExprTypeResolver.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it (including the #[AutowiredParameter] bool).
 * The class is final, so the direct entries (pt_initializer_expr_type_resolver_*)
 * take the native body for exactly the native class and call the method by
 * name on any other receiver.
 *
 * The twin's `callable(Expr): Type $getTypeCallback` parameters are a
 * pt_ietr_get_type in C++: a function pointer plus its data, so a native
 * caller hands the resolver its operand reader without allocating a closure
 * (the twin only ever calls the callback synchronously). A PHP callable
 * passed to the registered methods becomes such a callback over the value;
 * where a PHP collaborator needs a real callable it may keep
 * (OversizedArrayBuilder, a PHP receiver of an entry), the callback's
 * toCallable() builds one that owns what it reads — the caller's NativeClosure
 * over copies of its captures, never a pointer into a stack frame. The
 * twin's own `fn (Expr $expr): Type => $this->getType($expr, $context)`
 * closures are callbacks over the resolver and the context.
 *
 * The arithmetic runs on the engine's own operator functions
 * (add_function(), mod_function(), bitwise_and_function(), zend_compare(),
 * zval_get_long(), ...) and the internal min() / max(), so int overflow into
 * float, float-to-int truncation and the mixed-type comparisons behave
 * exactly like the twin's PHP operators.
 *
 * TypeCombinator, ConstantArrayTypeBuilder, ConstantTypeHelper, TypeResult,
 * TypeUtils and the Type kernel are native (direct entries / TypeOps); the
 * collaborators that stay PHP — ConstantResolver, ReflectionProviderProvider,
 * PhpVersion (its versionId read in place), the operator extension
 * registries, OversizedArrayBuilder, InitializerExprContext,
 * ParserNodeTypeToPHPStanType, CallableAssertionsHelper, TemplateTag,
 * SimpleThrowPoint and the php-parser node classes — go through cached
 * method sites and the class map.
 */

#include "support.h"
#include "generated/InitializerExprTypeResolver.h"

#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "ParserVisitors.h"

#include <cmath>
#include <limits>
#include <vector>

zend_class_entry *pt_ce_initializer_expr_type_resolver = nullptr;

namespace slots = ptdecl::InitializerExprTypeResolver::slot;
namespace sigs = ptdecl::InitializerExprTypeResolver::sig;

namespace {

using phpstanturbo::NullableLong;
using phpstanturbo::visitors::NodeProp;

/* the twin's CALCULATE_SCALARS_LIMIT and its private IS_* codes */
constexpr zend_long PT_IETR_CALCULATE_SCALARS_LIMIT = PT_INITIALIZER_EXPR_TYPE_RESOLVER_CALCULATE_SCALARS_LIMIT;
constexpr zend_long PT_IETR_IS_SCALAR_TYPE = 1;
constexpr zend_long PT_IETR_IS_UNKNOWN = 2;

#define IETR_VAL(name, expr) \
	zv::Val name = (expr); \
	if (UNEXPECTED(name.isUndef())) return zv::Val()

#define IETR_TRI(name, expr) \
	zend_long name = (expr); \
	if (UNEXPECTED(name < 0)) return zv::Val()

/* {{{ Type method calls */

/* a Type method the resolver calls: its name as written (the engine's Error
 * for a non-object receiver names it), lowercased at compile time for the
 * by-name lookup, and its TypeOps id when it has one */
struct Method
{
	const char *name;
	char lc[48];
	uint8_t len;
	pt_type_op_id op;

	constexpr Method(const char *n, pt_type_op_id o = PT_OP_COUNT) : name(n), lc{}, len(0), op(o)
	{
		while (n[len] != '\0') {
			char c = n[len];
			lc[len] = (c >= 'A' && c <= 'Z') ? (char) (c + ('a' - 'A')) : c;
			len++;
		}
	}
};

constexpr Method mToNumber{"toNumber"};
constexpr Method mToString{"toString"};
constexpr Method mToInteger{"toInteger"};
constexpr Method mToBoolean{"toBoolean"};
constexpr Method mToFloat{"toFloat"};
constexpr Method mToArray{"toArray"};
constexpr Method mToBitwiseNotType{"toBitwiseNotType"};
constexpr Method mToBooleanType{"toBooleanType"};
constexpr Method mToClassConstantType{"toClassConstantType"};
constexpr Method mGetConstantScalarTypes{"getConstantScalarTypes"};
constexpr Method mGetConstantScalarValues{"getConstantScalarValues", PT_OP_GET_CONSTANT_SCALAR_VALUES};
constexpr Method mGetFiniteTypes{"getFiniteTypes"};
constexpr Method mGetConstantStrings{"getConstantStrings"};
constexpr Method mGetConstantArrays{"getConstantArrays", PT_OP_GET_CONSTANT_ARRAYS};
constexpr Method mIsInteger{"isInteger", PT_OP_IS_INTEGER};
constexpr Method mIsString{"isString", PT_OP_IS_STRING};
constexpr Method mIsFloat{"isFloat", PT_OP_IS_FLOAT};
constexpr Method mIsNull{"isNull", PT_OP_IS_NULL};
constexpr Method mIsArray{"isArray", PT_OP_IS_ARRAY};
constexpr Method mIsList{"isList", PT_OP_IS_LIST};
constexpr Method mIsVoid{"isVoid", PT_OP_IS_VOID};
constexpr Method mIsObject{"isObject"};
constexpr Method mIsEnum{"isEnum"};
constexpr Method mIsTrue{"isTrue"};
constexpr Method mIsFalse{"isFalse"};
constexpr Method mIsConstantArray{"isConstantArray", PT_OP_IS_CONSTANT_ARRAY};
constexpr Method mIsConstantScalarValue{"isConstantScalarValue", PT_OP_IS_CONSTANT_SCALAR_VALUE};
constexpr Method mIsClassString{"isClassString"};
constexpr Method mIsIterableAtLeastOnce{"isIterableAtLeastOnce", PT_OP_IS_ITERABLE_AT_LEAST_ONCE};
constexpr Method mIsNonEmptyString{"isNonEmptyString"};
constexpr Method mIsNonFalsyString{"isNonFalsyString"};
constexpr Method mIsLiteralString{"isLiteralString"};
constexpr Method mIsLowercaseString{"isLowercaseString"};
constexpr Method mIsUppercaseString{"isUppercaseString"};
constexpr Method mIsNumericString{"isNumericString"};
constexpr Method mIsUnsealed{"isUnsealed", PT_OP_IS_UNSEALED};
constexpr Method mGetUnsealedTypes{"getUnsealedTypes"};
constexpr Method mGetIterableKeyType{"getIterableKeyType", PT_OP_GET_ITERABLE_KEY_TYPE};
constexpr Method mGetIterableValueType{"getIterableValueType", PT_OP_GET_ITERABLE_VALUE_TYPE};
constexpr Method mIsSuperTypeOf{"isSuperTypeOf", PT_OP_IS_SUPER_TYPE_OF};
constexpr Method mEquals{"equals", PT_OP_EQUALS};
constexpr Method mGetValue{"getValue", PT_OP_GET_VALUE};
constexpr Method mGetTypes{"getTypes", PT_OP_GET_TYPES};
constexpr Method mGetKeyTypes{"getKeyTypes", PT_OP_GET_KEY_TYPES};
constexpr Method mGetValueTypes{"getValueTypes", PT_OP_GET_VALUE_TYPES};
constexpr Method mIsOptionalKey{"isOptionalKey"};
constexpr Method mHasOffsetValueType{"hasOffsetValueType", PT_OP_HAS_OFFSET_VALUE_TYPE};
constexpr Method mGetOffsetValueType{"getOffsetValueType", PT_OP_GET_OFFSET_VALUE_TYPE};
constexpr Method mAppend{"append"};
constexpr Method mExponentiate{"exponentiate"};
constexpr Method mLooseCompare{"looseCompare"};
constexpr Method mGetReasons{"getReasons"};
constexpr Method mIsSmallerThan{"isSmallerThan"};
constexpr Method mIsSmallerThanOrEqual{"isSmallerThanOrEqual"};
constexpr Method mGetValueType{"getValueType"};
constexpr Method mGetParentClass{"getParentClass", PT_OP_GET_PARENT_CLASS};
constexpr Method mGetName{"getName", PT_OP_GET_NAME};
constexpr Method mIsFinal{"isFinal", PT_OP_IS_FINAL};
constexpr Method mHasConstant{"hasConstant"};
constexpr Method mHasEnumCase{"hasEnumCase"};
constexpr Method mGetNativeReflection{"getNativeReflection"};
constexpr Method mGetReflectionConstant{"getReflectionConstant"};
constexpr Method mGetDeclaringClass{"getDeclaringClass"};
constexpr Method mGetFileName{"getFileName", PT_OP_GET_FILE_NAME};
constexpr Method mGetValueExpression{"getValueExpression"};
constexpr Method mGetType{"getType", PT_OP_GET_TYPE};
constexpr Method mGetConstantPhpDocType{"getConstantPhpDocType"};
constexpr Method mGetConstant{"getConstant"};
constexpr Method mHasPhpDocType{"hasPhpDocType"};
constexpr Method mHasNativeType{"hasNativeType"};
constexpr Method mGetValueExpr{"getValueExpr"};
constexpr Method mGetNativeType{"getNativeType"};
constexpr Method mGetVariants{"getVariants"};
constexpr Method mGetReturnType{"getReturnType"};
constexpr Method mGetNativeReturnType{"getNativeReturnType"};
constexpr Method mGetTemplateTypeMap{"getTemplateTypeMap", PT_OP_GET_TEMPLATE_TYPE_MAP};
constexpr Method mGetBound{"getBound"};
constexpr Method mGetDefault{"getDefault"};
constexpr Method mGetVariance{"getVariance"};
constexpr Method mGetThrowPoints{"getThrowPoints"};
constexpr Method mGetImpurePoints{"getImpurePoints"};
constexpr Method mAcceptsNamedArguments{"acceptsNamedArguments"};
constexpr Method mMustUseReturnValue{"mustUseReturnValue"};
constexpr Method mIsStaticClosure{"isStaticClosure"};
constexpr Method mGetThrowType{"getThrowType"};
constexpr Method mGetParameters{"getParameters"};
constexpr Method mGetAsserts{"getAsserts"};
constexpr Method mIsVariadic{"isVariadic", PT_OP_IS_VARIADIC};
constexpr Method mGetResolvedTemplateTypeMap{"getResolvedTemplateTypeMap"};
constexpr Method mGetCallSiteVarianceMap{"getCallSiteVarianceMap", PT_OP_GET_CALL_SITE_VARIANCE_MAP};
constexpr Method mHasMethod{"hasMethod", PT_OP_HAS_METHOD};
constexpr Method mGetMethod{"getMethod", PT_OP_GET_METHOD};
constexpr Method mIsStatic{"isStatic"};
constexpr Method mGetStaticObjectType{"getStaticObjectType"};
constexpr Method mGetAncestorWithClassName{"getAncestorWithClassName", PT_OP_GET_ANCESTOR_WITH_CLASS_NAME};
constexpr Method mGetClassName{"getClassName"};
constexpr Method mGetClassStringObjectType{"getClassStringObjectType"};
constexpr Method mGetObjectClassNames{"getObjectClassNames", PT_OP_GET_OBJECT_CLASS_NAMES};
constexpr Method mHasInstanceProperty{"hasInstanceProperty", PT_OP_HAS_INSTANCE_PROPERTY};
constexpr Method mGetInstanceProperty{"getInstanceProperty", PT_OP_GET_INSTANCE_PROPERTY};
constexpr Method mGetReadableType{"getReadableType"};
constexpr Method mGetExprType{"getExprType"};
constexpr Method mGeneralize{"generalize"};
constexpr Method mGetStartLine{"getStartLine"};

/* the engine's Error for a method call on a non-object */
inline void throwCallOnNonObject(const Method &m, zval *receiver)
{
	zend_throw_error(NULL, "Call to a member function %s() on %s", m.name, zend_zval_value_name(receiver));
}

/* $receiver->method(...$argv); UNDEF = pending exception */
inline zv::Val callOn(zval *receiver, const Method &m, uint32_t argc = 0, zval *argv = NULL)
{
	if (UNEXPECTED(Z_TYPE_P(receiver) != IS_OBJECT)) {
		throwCallOnNonObject(m, receiver);
		return zv::Val();
	}
	if (m.op != PT_OP_COUNT) return pt_type_op(Z_OBJ_P(receiver), m.op, argc, argv);
	return pt_type_call(Z_OBJ_P(receiver), m.lc, m.len, argc, argv);
}

/* the PT_TRI_* value of a TrinaryLogic / result object's ->yes()/->no()
 * family (a TrinaryLogic or an IsSuperTypeOfResult / AcceptsResult); -1 =
 * pending exception */
inline zend_long trinaryOf(zval *value)
{
	if (EXPECTED(Z_TYPE_P(value) == IS_OBJECT)) {
		zend_class_entry *ce = Z_OBJCE_P(value);
		if (EXPECTED(ce == pt_ce_trinary)) return pt_trinary_value(Z_OBJ_P(value));
		if (ce == pt_ce_is_super_type_of_result || ce == pt_ce_accepts_result) return pt_type_result_trinary(value);
		zv::Val yes = pt_type_call(Z_OBJ_P(value), PT_LC("yes"), 0, NULL);
		if (UNEXPECTED(yes.isUndef())) return -1;
		if (zend_is_true(yes.raw())) return PT_TRI_YES;
		zv::Val no = pt_type_call(Z_OBJ_P(value), PT_LC("no"), 0, NULL);
		if (UNEXPECTED(no.isUndef())) return -1;
		return zend_is_true(no.raw()) ? PT_TRI_NO : PT_TRI_MAYBE;
	}
	zend_throw_error(NULL, "Call to a member function yes() on %s", zend_zval_value_name(value));
	return -1;
}

/* $receiver->method(...$argv) of a method returning TrinaryLogic (or a
 * result object): the PT_TRI_* value, -1 = pending exception */
inline zend_long triOn(zval *receiver, const Method &m, uint32_t argc = 0, zval *argv = NULL)
{
	zv::Val result = callOn(receiver, m, argc, argv);
	if (UNEXPECTED(result.isUndef())) return -1;
	return trinaryOf(result.raw());
}

/* $value instanceof <native class> */
inline bool isA(zval *value, zend_class_entry *ce)
{
	return Z_TYPE_P(value) == IS_OBJECT && instanceof_function(Z_OBJCE_P(value), ce);
}

/* $value instanceof <class-map class>; -1 = pending exception */
inline int isAClass(zval *value, int classIdx)
{
	zend_class_entry *ce = pt_class(classIdx);
	if (UNEXPECTED(ce == NULL)) return -1;
	return Z_TYPE_P(value) == IS_OBJECT && instanceof_function(Z_OBJCE_P(value), ce) ? 1 : 0;
}

/* }}} */

/* {{{ Type construction */

inline zv::Val adopted(bool ok, zval &value)
{
	if (UNEXPECTED(!ok)) return zv::Val();
	return zv::Val::adopt(value);
}

zv::Val newErrorType()
{
	zval z;
	return adopted(pt_error_type_new(&z), z);
}

zv::Val newMixedType(bool isExplicitMixed = false)
{
	zval z;
	return adopted(pt_mixed_type_new(&z, isExplicitMixed), z);
}

zv::Val newNeverType()
{
	zval z;
	return adopted(pt_never_type_new(&z), z);
}

zv::Val newIntegerType()
{
	zval z;
	return adopted(pt_integer_type_new(&z), z);
}

zv::Val newFloatType()
{
	zval z;
	return adopted(pt_float_type_new(&z), z);
}

zv::Val newStringType()
{
	zval z;
	return adopted(pt_string_type_new(&z), z);
}

zv::Val newNullType()
{
	zval z;
	return adopted(pt_null_type_new(&z), z);
}

zv::Val newBooleanType()
{
	zval z;
	return adopted(pt_boolean_type_new(&z), z);
}

zv::Val newConstantBooleanType(bool value)
{
	zval z;
	return adopted(pt_constant_boolean_type_new(&z, value), z);
}

zv::Val newConstantIntegerType(zend_long value)
{
	zval z;
	return adopted(pt_constant_integer_type_new(&z, value), z);
}

zv::Val newConstantFloatType(double value)
{
	zval z;
	return adopted(pt_constant_float_type_new(&z, value), z);
}

zv::Val newConstantStringType(zend_string *value, bool isClassString = false)
{
	zval z;
	return adopted(pt_constant_string_type_new(&z, value, isClassString), z);
}

zv::Val newClassStringType()
{
	zval z;
	return adopted(pt_class_string_type_new(&z), z);
}

zv::Val newObjectType(zend_string *className)
{
	zval z;
	return adopted(pt_object_type_new(&z, className), z);
}

zv::Val newObjectTypeLiteral(const char *className, size_t len)
{
	zend_string *name = zend_string_init(className, len, 0);
	zv::Val type = newObjectType(name);
	zend_string_release(name);
	return type;
}

zv::Val newArrayType(zval *keyType, zval *itemType)
{
	zval z;
	return adopted(pt_array_type_new(&z, keyType, itemType), z);
}

zv::Val newUnionType(zv::Arr &types)
{
	zval z;
	return adopted(pt_union_type_new(&z, types.raw()), z);
}

zv::Val newBenevolentUnionType(zv::Arr &types)
{
	zval z;
	return adopted(pt_benevolent_union_type_new(&z, types.raw()), z);
}

zv::Val newIntersectionType(zv::Arr &types)
{
	zval z;
	return adopted(pt_intersection_type_new(&z, types.raw()), z);
}

/* new BenevolentUnionType([new IntegerType(), new FloatType()]) (the order
 * the twin spells: first, second) */
zv::Val newIntFloatUnion(bool benevolent, bool floatFirst = false)
{
	IETR_VAL(integerType, newIntegerType());
	IETR_VAL(floatType, newFloatType());
	zv::Arr types = zv::Arr::create(2);
	if (floatFirst) {
		types.push(std::move(floatType));
		types.push(std::move(integerType));
	} else {
		types.push(std::move(integerType));
		types.push(std::move(floatType));
	}
	return benevolent ? newBenevolentUnionType(types) : newUnionType(types);
}

/* new TypeResult($type, []) */
zv::Val newTypeResult(zv::Val type)
{
	if (UNEXPECTED(type.isUndef())) return zv::Val();
	zval reasons;
	ZVAL_EMPTY_ARRAY(&reasons);
	return pt_type_result_new(type.raw(), &reasons);
}

/* TypeCombinator::union(...$types) of a list the resolver built */
zv::Val unionOf(zv::Arr &types)
{
	HashTable *ht = types.table();
	uint32_t count = zend_hash_num_elements(ht);
	if (count == 0) return pt_type_combinator_union(0, NULL);
	if (EXPECTED(HT_IS_PACKED(ht) && HT_IS_WITHOUT_HOLES(ht))) return pt_type_combinator_union(count, ht->arPacked);
	return pt_type_combinator_call_spread(PT_LC("union"), ht);
}

/* TypeCombinator::union($a, $b) */
zv::Val union2(zval *a, zval *b)
{
	zv::Args argv{a, b};
	return pt_type_combinator_union(2, argv);
}

/* ConstantTypeHelper::getTypeFromValue($value) */
zv::Val typeFromValue(zval *value)
{
	return pt_constant_type_helper_get_type_from_value(value);
}

/* IntegerRangeType::fromInterval($min, $max) of int|null zvals; a float (the
 * twin's arithmetic overflowed) raises the TypeError of the twin's
 * strict-types call */
zv::Val fromIntervalZv(zval *min, zval *max)
{
	bool minOk = Z_TYPE_P(min) == IS_LONG || Z_TYPE_P(min) == IS_NULL;
	bool maxOk = Z_TYPE_P(max) == IS_LONG || Z_TYPE_P(max) == IS_NULL;
	if (EXPECTED(minOk && maxOk)) return pt_integer_range_from_interval(NullableLong::from(min), NullableLong::from(max), 0);
	zend_type_error("PHPStan\\Type\\IntegerRangeType::fromInterval(): Argument #%d ($%s) must be of type ?int, %s given", minOk ? 2 : 1, minOk ? "max" : "min", zend_zval_value_name(minOk ? max : min));
	return zv::Val();
}

zv::Val fromInterval(NullableLong min, NullableLong max)
{
	return pt_integer_range_from_interval(min, max, 0);
}

/* TypeUtils::toBenevolentUnion($type) */
zv::Val toBenevolentUnion(zval *type)
{
	return pt_type_call_static_ce(pt_ce_type_utils, PT_LC("tobenevolentunion"), 1, type);
}

/* }}} */

/* {{{ PHP value arithmetic (the twin's operators, on the engine's functions) */

typedef zend_result (ZEND_FASTCALL *pt_ietr_binary_op)(zval *result, zval *op1, zval *op2);

/* $a <op> $b; UNDEF = pending exception */
zv::Val phpOp(pt_ietr_binary_op fn, zval *a, zval *b)
{
	zval result;
	ZVAL_UNDEF(&result);
	if (UNEXPECTED(fn(&result, a, b) != SUCCESS || EG(exception) != NULL)) {
		zval_ptr_dtor(&result);
		return zv::Val();
	}
	return zv::Val::adopt(result);
}

/* an internal function of the engine's function table called directly
 * (the function the twin calls: min(), max(), dirname()), its lookup cached
 * in `cache`; UNDEF = pending exception */
zv::Val callInternalFunction(zend_function *&cache, const char *name, size_t len, uint32_t argc, zval *argv)
{
	if (UNEXPECTED(cache == NULL)) {
		cache = (zend_function *) zend_hash_str_find_ptr(CG(function_table), name, len);
		if (UNEXPECTED(cache == NULL)) {
			zend_throw_error(NULL, "Call to undefined function %s()", name);
			return zv::Val();
		}
	}
	zval result;
	ZVAL_UNDEF(&result);
	zend_call_known_function(cache, NULL, NULL, &result, argc, argv, NULL);
	if (UNEXPECTED(EG(exception) != NULL)) {
		zval_ptr_dtor(&result);
		return zv::Val();
	}
	return zv::Val::adopt(result);
}

/* min(...$argv) / max(...$argv) */
zv::Val phpMinMax(bool max, uint32_t argc, zval *argv)
{
	static zend_function *minFn = NULL;
	static zend_function *maxFn = NULL;
	return max ? callInternalFunction(maxFn, PT_LC("max"), argc, argv) : callInternalFunction(minFn, PT_LC("min"), argc, argv);
}

/* dirname($path) */
zv::Val phpDirname(zval *path)
{
	static zend_function *dirnameFn = NULL;
	return callInternalFunction(dirnameFn, PT_LC("dirname"), 1, path);
}

/* $value === 0 || $value === 0.0 — in_array($value, [0, 0.0], true) */
inline bool isZeroNumber(zval *value)
{
	return (Z_TYPE_P(value) == IS_LONG && Z_LVAL_P(value) == 0) || (Z_TYPE_P(value) == IS_DOUBLE && Z_DVAL_P(value) == 0.0);
}

/* $a === $b for scalars */
inline bool identical(zval *a, zval *b)
{
	return zend_is_identical(a, b);
}

/* $a > $b / $a < $b (zend_compare, as the VM's IS_SMALLER) */
inline bool greaterThan(zval *a, zval *b)
{
	return zend_compare(b, a) < 0;
}

inline bool lessThan(zval *a, zval *b)
{
	return zend_compare(a, b) < 0;
}

/* }}} */

/* {{{ php-parser nodes */

NodeProp pt_ietr_name_name = PT_NAME_PROP;
NodeProp pt_ietr_identifier_name = PT_NODE_PROP(PT_CLASS_IDENTIFIER, "name");

/* new <BinaryOp class>($left, $right) through the class map */
zv::Val newBinaryOpNode(int classIdx, zval *left, zval *right)
{
	zv::Args argv{left, right};
	return pt_type_new(classIdx, 2, argv);
}

/* }}} */

/* {{{ the PHP collaborators (one site each; switch to their direct entries
 * once they are ported) */

pt_method_site pt_ietr_call_operator_site;
pt_method_site pt_ietr_call_unary_operator_site;

/* $registry->callOperatorTypeSpecifyingExtensions($expr, $leftType, $rightType) */
zv::Val callOperatorTypeSpecifyingExtensions(zval *registry, zval *expr, zval *leftType, zval *rightType)
{
	zv::Args argv{expr, leftType, rightType};
	return pt_call_method_cached(pt_ietr_call_operator_site, Z_OBJ_P(registry), PT_LC("calloperatortypespecifyingextensions"), 3, argv);
}

/* $registry->callUnaryOperatorTypeSpecifyingExtensions($sigil, $operandType) */
zv::Val callUnaryOperatorTypeSpecifyingExtensions(zval *registry, const char *sigil, zval *operandType)
{
	zval sigilZv;
	ZVAL_CHAR(&sigilZv, sigil[0]);
	zv::Args argv{&sigilZv, operandType};
	return pt_call_method_cached(pt_ietr_call_unary_operator_site, Z_OBJ_P(registry), PT_LC("callunaryoperatortypespecifyingextensions"), 2, argv);
}

/* $phpVersion->versionId >= $minimum (the final PhpVersion's supports*()
 * one-liners), the method on anything else; -1 = pending exception */
int phpVersionAtLeast(zval *phpVersion, zend_long minimum, const char *lcname, size_t len)
{
	if (EXPECTED(Z_TYPE_P(phpVersion) == IS_OBJECT)) {
		zend_class_entry *phpVersionCe = pt_class(PT_CLASS_PHP_VERSION);
		if (UNEXPECTED(phpVersionCe == NULL)) return -1;
		if (EXPECTED(Z_OBJCE_P(phpVersion) == phpVersionCe)) {
			static int32_t offset = -2;
			static zend_class_entry *offsetFor = NULL;
			if (UNEXPECTED(offsetFor != phpVersionCe)) {
				offsetFor = phpVersionCe;
				offset = pt_instance_prop_offset(phpVersionCe, PT_LC("versionId"));
			}
			if (EXPECTED(offset >= 0)) {
				zval *versionId = OBJ_PROP(Z_OBJ_P(phpVersion), (uint32_t) offset);
				if (EXPECTED(Z_TYPE_P(versionId) == IS_LONG)) return Z_LVAL_P(versionId) >= minimum ? 1 : 0;
			}
		}
		zv::Val result = pt_type_call(Z_OBJ_P(phpVersion), lcname, len, 0, NULL);
		if (UNEXPECTED(result.isUndef())) return -1;
		return zend_is_true(result.raw()) ? 1 : 0;
	}
	zend_throw_error(NULL, "Call to a member function %s() on %s", lcname, zend_zval_value_name(phpVersion));
	return -1;
}

/* }}} */

/* {{{ more php-parser node properties */

NodeProp pt_ietr_array_items = PT_NODE_PROP(PT_CLASS_ARRAY_EXPR, "items");
NodeProp pt_ietr_array_item_value = PT_NODE_PROP(PT_CLASS_ARRAY_ITEM, "value");
NodeProp pt_ietr_array_item_key = PT_NODE_PROP(PT_CLASS_ARRAY_ITEM, "key");
NodeProp pt_ietr_array_item_unpack = PT_NODE_PROP(PT_CLASS_ARRAY_ITEM, "unpack");
NodeProp pt_ietr_cast_expr = PT_NODE_PROP(PT_CLASS_CAST_EXPR, "expr");
NodeProp pt_ietr_func_call_name = PT_NODE_PROP(PT_CLASS_FUNC_CALL, "name");
NodeProp pt_ietr_func_call_args = PT_NODE_PROP(PT_CLASS_FUNC_CALL, "args");
NodeProp pt_ietr_static_call_class = PT_NODE_PROP(PT_CLASS_STATIC_CALL, "class");
NodeProp pt_ietr_static_call_name = PT_NODE_PROP(PT_CLASS_STATIC_CALL, "name");
NodeProp pt_ietr_param_default = PT_NODE_PROP(PT_CLASS_PARAM, "default");
NodeProp pt_ietr_param_var = PT_NODE_PROP(PT_CLASS_PARAM, "var");
NodeProp pt_ietr_param_variadic = PT_NODE_PROP(PT_CLASS_PARAM, "variadic");
NodeProp pt_ietr_param_by_ref = PT_NODE_PROP(PT_CLASS_PARAM, "byRef");
NodeProp pt_ietr_param_type = PT_NODE_PROP(PT_CLASS_PARAM, "type");
NodeProp pt_ietr_const_fetch_name = PT_NODE_PROP(PT_CLASS_CONST_FETCH, "name");
NodeProp pt_ietr_variable_name = PT_NODE_PROP(PT_CLASS_VARIABLE, "name");
NodeProp pt_ietr_closure_params = PT_NODE_PROP(PT_CLASS_CLOSURE_EXPR, "params");
NodeProp pt_ietr_closure_static = PT_NODE_PROP(PT_CLASS_CLOSURE_EXPR, "static");
NodeProp pt_ietr_closure_return_type = PT_NODE_PROP(PT_CLASS_CLOSURE_EXPR, "returnType");
NodeProp pt_ietr_new_class = PT_NODE_PROP(PT_CLASS_NEW, "class");
NodeProp pt_ietr_array_dim_fetch_var = PT_NODE_PROP(PT_CLASS_ARRAY_DIM_FETCH, "var");
NodeProp pt_ietr_array_dim_fetch_dim = PT_NODE_PROP(PT_CLASS_ARRAY_DIM_FETCH, "dim");
NodeProp pt_ietr_class_const_fetch_class = PT_NODE_PROP(PT_CLASS_CLASS_CONST_FETCH, "class");
NodeProp pt_ietr_class_const_fetch_name = PT_NODE_PROP(PT_CLASS_CLASS_CONST_FETCH, "name");
NodeProp pt_ietr_unary_plus_expr = PT_NODE_PROP(PT_CLASS_UNARY_PLUS, "expr");
NodeProp pt_ietr_unary_minus_expr = PT_NODE_PROP(PT_CLASS_UNARY_MINUS, "expr");
NodeProp pt_ietr_boolean_not_expr = PT_NODE_PROP(PT_CLASS_BOOLEAN_NOT_EXPR, "expr");
NodeProp pt_ietr_bitwise_not_expr = PT_NODE_PROP(PT_CLASS_BITWISE_NOT, "expr");
NodeProp pt_ietr_binary_op_left = PT_NODE_PROP(PT_CLASS_BINARY_OP_EXPR, "left");
NodeProp pt_ietr_binary_op_right = PT_NODE_PROP(PT_CLASS_BINARY_OP_EXPR, "right");
NodeProp pt_ietr_ternary_cond = PT_NODE_PROP(PT_CLASS_TERNARY_EXPR, "cond");
NodeProp pt_ietr_ternary_if = PT_NODE_PROP(PT_CLASS_TERNARY_EXPR, "if");
NodeProp pt_ietr_ternary_else = PT_NODE_PROP(PT_CLASS_TERNARY_EXPR, "else");
NodeProp pt_ietr_arg_value = PT_NODE_PROP(PT_CLASS_ARG, "value");
NodeProp pt_ietr_scalar_string_value = PT_NODE_PROP(PT_CLASS_SCALAR_STRING, "value");
NodeProp pt_ietr_scalar_int_value = PT_NODE_PROP(PT_CLASS_SCALAR_INT, "value");
NodeProp pt_ietr_scalar_float_value = PT_NODE_PROP(PT_CLASS_SCALAR_FLOAT, "value");
NodeProp pt_ietr_property_fetch_var = PT_NODE_PROP(PT_CLASS_PROPERTY_FETCH, "var");
NodeProp pt_ietr_property_fetch_name = PT_NODE_PROP(PT_CLASS_PROPERTY_FETCH, "name");

/* $node->$name of a node the dispatch guarantees to be an instance of the
 * property's class (dereferenced); NULL with the engine's Error pending (an
 * uninitialized typed property, or the class map failing) */
zval *nodeProp(NodeProp &prop, zval *node)
{
	zval *value = prop.of(Z_OBJ_P(node));
	if (EXPECTED(value != NULL && Z_TYPE_P(value) != IS_UNDEF)) return value;
	if (EG(exception) != NULL) return NULL;
	if (value == NULL) {
		zend_throw_error(NULL, "Undefined property: %s::$%s", ZSTR_VAL(Z_OBJCE_P(node)->name), prop.name);
		return NULL;
	}
	zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(Z_OBJCE_P(node)->name), prop.name);
	return NULL;
}

/* (string) $name of a Name node: its $name string (NULL with an exception
 * pending) */
zend_string *nameString(zval *name)
{
	zval *value = nodeProp(pt_ietr_name_name, name);
	if (UNEXPECTED(value == NULL)) return NULL;
	if (UNEXPECTED(Z_TYPE_P(value) != IS_STRING)) {
		pt_throw_should_not_happen();
		return NULL;
	}
	return Z_STR_P(value);
}

/* (string) $identifier / $identifier->toString() of an Identifier node */
zend_string *identifierString(zval *identifier)
{
	zval *value = nodeProp(pt_ietr_identifier_name, identifier);
	if (UNEXPECTED(value == NULL)) return NULL;
	if (UNEXPECTED(Z_TYPE_P(value) != IS_STRING)) {
		pt_throw_should_not_happen();
		return NULL;
	}
	return Z_STR_P(value);
}

/* }}} */

/* {{{ the PHP collaborators of the reflection and constant-expression paths */

pt_method_site pt_ietr_get_reflection_provider_site;
pt_method_site pt_ietr_resolve_constant_site;
pt_method_site pt_ietr_resolve_predefined_constant_site;
pt_method_site pt_ietr_resolve_class_constant_type_site;
pt_method_site pt_ietr_oversized_build_site;
pt_method_site pt_ietr_parser_node_type_resolve_site;
pt_method_site pt_ietr_with_conditional_return_predicate_site;
pt_method_site pt_ietr_simple_throw_point_create_explicit_site;
pt_method_site pt_ietr_simple_throw_point_create_implicit_site;
pt_method_site pt_ietr_generalize_precision_more_specific_site;

/* $reflectionProviderProvider->getReflectionProvider() */
zv::Val reflectionProviderOf(zval *reflectionProviderProvider)
{
	return pt_call_method_cached(pt_ietr_get_reflection_provider_site, Z_OBJ_P(reflectionProviderProvider), PT_LC("getreflectionprovider"), 0, NULL);
}

/* $constantResolver->resolveConstant($name, $context) */
zv::Val resolveConstant(zval *constantResolver, zval *name, zval *context)
{
	zv::Args argv{name, context};
	return pt_call_method_cached(pt_ietr_resolve_constant_site, Z_OBJ_P(constantResolver), PT_LC("resolveconstant"), 2, argv);
}

/* $constantResolver->resolvePredefinedConstant($name) */
zv::Val resolvePredefinedConstant(zval *constantResolver, zval *name)
{
	return pt_call_method_cached(pt_ietr_resolve_predefined_constant_site, Z_OBJ_P(constantResolver), PT_LC("resolvepredefinedconstant"), 1, name);
}

/* $constantResolver->resolveClassConstantType($className, $constantName,
 * $constantType, $nativeType, $phpDocType) */
zv::Val resolveClassConstantType(zval *constantResolver, zval *className, zval *constantName, zval *constantType, zval *nativeType, zval *phpDocType)
{
	zv::Args argv{className, constantName, constantType, nativeType, phpDocType};
	return pt_call_method_cached(pt_ietr_resolve_class_constant_type_site, Z_OBJ_P(constantResolver), PT_LC("resolveclassconstanttype"), 5, argv);
}

/* $oversizedArrayBuilder->build($expr, $getTypeCallback) */
zv::Val oversizedArrayBuild(zval *builder, zval *expr, zval *getTypeCallback)
{
	zv::Args argv{expr, getTypeCallback};
	return pt_call_method_cached(pt_ietr_oversized_build_site, Z_OBJ_P(builder), PT_LC("build"), 2, argv);
}

/* ParserNodeTypeToPHPStanType::resolve($type, $classReflection) */
zv::Val parserNodeTypeResolve(zval *type, zval *classReflection)
{
	zv::Args argv{type, classReflection};
	return pt_call_static_cached(pt_ietr_parser_node_type_resolve_site, PT_CLASS_PARSER_NODE_TYPE_TO_PHPSTAN_TYPE, PT_LC("resolve"), 2, argv);
}

/* CallableAssertionsHelper::withConditionalReturnPredicate($assertions, $variant) */
zv::Val withConditionalReturnPredicate(zval *assertions, zval *variant)
{
	zv::Args argv{assertions, variant};
	return pt_call_static_cached(pt_ietr_with_conditional_return_predicate_site, PT_CLASS_CALLABLE_ASSERTIONS_HELPER, PT_LC("withconditionalreturnpredicate"), 2, argv);
}

/* SimpleThrowPoint::createExplicit($type, $canContainAnyThrowable) */
zv::Val simpleThrowPointCreateExplicit(zval *type, bool canContainAnyThrowable)
{
	zval flag = {};
	ZVAL_BOOL(&flag, canContainAnyThrowable);
	zv::Args argv{type, &flag};
	return pt_call_static_cached(pt_ietr_simple_throw_point_create_explicit_site, PT_CLASS_SIMPLE_THROW_POINT, PT_LC("createexplicit"), 2, argv);
}

/* SimpleThrowPoint::createImplicit() */
zv::Val simpleThrowPointCreateImplicit()
{
	return pt_call_static_cached(pt_ietr_simple_throw_point_create_implicit_site, PT_CLASS_SIMPLE_THROW_POINT, PT_LC("createimplicit"), 0, NULL);
}

/* InitializerExprContext::fromClass($className, $fileName) */
zv::Val contextFromClass(zval *className, zval *fileName)
{
	return pt_initializer_expr_context_from_class(className, fileName);
}

/* InitializerExprContext::fromClassReflection($classReflection) */
zv::Val contextFromClassReflection(zval *classReflection)
{
	return pt_initializer_expr_context_from_class_reflection(classReflection);
}

/* GeneralizePrecision::moreSpecific() */
zv::Val generalizePrecisionMoreSpecific()
{
	return pt_call_static_cached(pt_ietr_generalize_precision_more_specific_site, PT_CLASS_GENERALIZE_PRECISION, PT_LC("morespecific"), 0, NULL);
}

/* $context->getFile() / getClassName() / getNamespace() / getTraitName() /
 * getFunction() / getMethod() / getProperty() (a ?string): the slot readers
 * of the native InitializerExprContext (AnalyserValues.h) */
zv::Val contextGetter(zval *context, zval *(*reader)(zval *, zv::Val &), const char *name)
{
	if (UNEXPECTED(Z_TYPE_P(context) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", name, zend_zval_value_name(context));
		return zv::Val();
	}
	zv::Val hold;
	zval *value = reader(context, hold);
	if (UNEXPECTED(value == NULL)) return zv::Val();
	return zv::Val::copyOf(zv::Ref(value));
}

inline zv::Val contextGetFile(zval *context) { return contextGetter(context, pt_initializer_expr_context_file, "getFile"); }
inline zv::Val contextGetClassName(zval *context) { return contextGetter(context, pt_initializer_expr_context_class_name, "getClassName"); }
inline zv::Val contextGetNamespace(zval *context) { return contextGetter(context, pt_initializer_expr_context_namespace, "getNamespace"); }
inline zv::Val contextGetTraitName(zval *context) { return contextGetter(context, pt_initializer_expr_context_trait_name, "getTraitName"); }
inline zv::Val contextGetFunction(zval *context) { return contextGetter(context, pt_initializer_expr_context_function, "getFunction"); }
inline zv::Val contextGetMethod(zval *context) { return contextGetter(context, pt_initializer_expr_context_method, "getMethod"); }
inline zv::Val contextGetProperty(zval *context) { return contextGetter(context, pt_initializer_expr_context_property, "getProperty"); }

/* }}} */

/* {{{ the getTypeCallback */

/* $callback($expr) of a PHP callable handed to a registered method; the
 * callable itself (another reference to it) where it escapes */
zv::Val callableGetType(void *data, zval *expr)
{
	return pt_type_call_callable(static_cast<zval *>(data), 1, expr);
}

zv::Val callableToCallable(void *data)
{
	return zv::Val::copyOf(zv::Ref(static_cast<zval *>(data)));
}

inline pt_ietr_get_type callableCallback(zval *callable)
{
	return { &callableGetType, callable, &callableToCallable };
}

inline zv::Val getTypeOf(const pt_ietr_get_type &callback, zval *expr)
{
	return callback.fn(callback.data, expr);
}

/* the callback as a PHP callable for a collaborator that may keep it: the
 * caller's owning callable (pt_ietr_get_type::toCallable) */
inline zv::Val callbackCallable(const pt_ietr_get_type &callback)
{
	return callback.toCallable(callback.data);
}

/* }}} */

} // namespace

namespace phpstanturbo {

/* the binary operators whose type the resolver computes */
enum IetrBinaryKind : uint8_t
{
	IETR_PLUS,
	IETR_MINUS,
	IETR_MUL,
	IETR_DIV,
	IETR_MOD,
	IETR_POW,
	IETR_SHIFT_LEFT,
	IETR_SHIFT_RIGHT,
	IETR_BITWISE_AND,
	IETR_BITWISE_OR,
	IETR_BITWISE_XOR,
	IETR_OTHER,
};

inline constexpr int ietrBinaryKindClasses[IETR_OTHER] = {
	PT_CLASS_BINARY_OP_PLUS,
	PT_CLASS_BINARY_OP_MINUS,
	PT_CLASS_MUL_EXPR,
	PT_CLASS_DIV_EXPR,
	PT_CLASS_MOD_EXPR,
	PT_CLASS_POW_EXPR,
	PT_CLASS_SHIFT_LEFT_EXPR,
	PT_CLASS_SHIFT_RIGHT_EXPR,
	PT_CLASS_BITWISE_AND_EXPR,
	PT_CLASS_BITWISE_OR_EXPR,
	PT_CLASS_BITWISE_XOR_EXPR,
};

/* {{{ getType()'s instanceof chain: the arms in the twin's order */

enum IetrGetTypeArm : uint8_t
{
	ARM_TYPE_EXPR,
	ARM_INT,
	ARM_FLOAT,
	ARM_STRING,
	ARM_CONST_FETCH,
	ARM_FILE,
	ARM_DIR,
	ARM_LINE,
	ARM_NEW,
	ARM_ARRAY,
	ARM_CAST,
	ARM_CALL_LIKE,
	ARM_CLOSURE,
	ARM_ARRAY_DIM_FETCH,
	ARM_CLASS_CONST_FETCH,
	ARM_UNARY_PLUS,
	ARM_UNARY_MINUS,
	ARM_COALESCE,
	ARM_TERNARY,
	ARM_FUNC_CALL,
	ARM_BOOLEAN_NOT,
	ARM_BITWISE_NOT,
	ARM_CONCAT,
	ARM_BITWISE_AND,
	ARM_BITWISE_OR,
	ARM_BITWISE_XOR,
	ARM_SPACESHIP,
	ARM_BOOLEAN_AND,
	ARM_LOGICAL_AND,
	ARM_BOOLEAN_OR,
	ARM_LOGICAL_OR,
	ARM_DIV,
	ARM_MOD,
	ARM_PLUS,
	ARM_MINUS,
	ARM_MUL,
	ARM_POW,
	ARM_SHIFT_LEFT,
	ARM_SHIFT_RIGHT,
	ARM_IDENTICAL,
	ARM_NOT_IDENTICAL,
	ARM_EQUAL,
	ARM_NOT_EQUAL,
	ARM_SMALLER,
	ARM_SMALLER_OR_EQUAL,
	ARM_GREATER,
	ARM_GREATER_OR_EQUAL,
	ARM_LOGICAL_XOR,
	ARM_MAGIC_CLASS,
	ARM_MAGIC_NAMESPACE,
	ARM_MAGIC_METHOD,
	ARM_MAGIC_FUNCTION,
	ARM_MAGIC_TRAIT,
	ARM_MAGIC_PROPERTY,
	ARM_PROPERTY_FETCH,
	ARM_COUNT,
};

inline constexpr int ietrArmClasses[ARM_COUNT] = {
	PT_CLASS_TYPE_EXPR,
	PT_CLASS_SCALAR_INT,
	PT_CLASS_SCALAR_FLOAT,
	PT_CLASS_SCALAR_STRING,
	PT_CLASS_CONST_FETCH,
	PT_CLASS_MAGIC_CONST_FILE,
	PT_CLASS_MAGIC_CONST_DIR,
	PT_CLASS_MAGIC_CONST_LINE,
	PT_CLASS_NEW,
	PT_CLASS_ARRAY_EXPR,
	PT_CLASS_CAST_EXPR,
	PT_CLASS_CALL_LIKE,
	PT_CLASS_CLOSURE_EXPR,
	PT_CLASS_ARRAY_DIM_FETCH,
	PT_CLASS_CLASS_CONST_FETCH,
	PT_CLASS_UNARY_PLUS,
	PT_CLASS_UNARY_MINUS,
	PT_CLASS_COALESCE_EXPR,
	PT_CLASS_TERNARY_EXPR,
	PT_CLASS_FUNC_CALL,
	PT_CLASS_BOOLEAN_NOT_EXPR,
	PT_CLASS_BITWISE_NOT,
	PT_CLASS_CONCAT_EXPR,
	PT_CLASS_BITWISE_AND_EXPR,
	PT_CLASS_BITWISE_OR_EXPR,
	PT_CLASS_BITWISE_XOR_EXPR,
	PT_CLASS_SPACESHIP_EXPR,
	PT_CLASS_BOOLEAN_AND_EXPR,
	PT_CLASS_LOGICAL_AND_EXPR,
	PT_CLASS_BOOLEAN_OR_EXPR,
	PT_CLASS_LOGICAL_OR_EXPR,
	PT_CLASS_DIV_EXPR,
	PT_CLASS_MOD_EXPR,
	PT_CLASS_BINARY_OP_PLUS,
	PT_CLASS_BINARY_OP_MINUS,
	PT_CLASS_MUL_EXPR,
	PT_CLASS_POW_EXPR,
	PT_CLASS_SHIFT_LEFT_EXPR,
	PT_CLASS_SHIFT_RIGHT_EXPR,
	PT_CLASS_IDENTICAL_EXPR,
	PT_CLASS_BINARY_OP_NOT_IDENTICAL,
	PT_CLASS_EQUAL_EXPR,
	PT_CLASS_NOT_EQUAL_EXPR,
	PT_CLASS_SMALLER_EXPR,
	PT_CLASS_SMALLER_OR_EQUAL_EXPR,
	PT_CLASS_GREATER_EXPR,
	PT_CLASS_GREATER_OR_EQUAL_EXPR,
	PT_CLASS_LOGICAL_XOR_EXPR,
	PT_CLASS_MAGIC_CONST_CLASS,
	PT_CLASS_MAGIC_CONST_NAMESPACE,
	PT_CLASS_MAGIC_CONST_METHOD,
	PT_CLASS_MAGIC_CONST_FUNCTION,
	PT_CLASS_MAGIC_CONST_TRAIT,
	PT_CLASS_MAGIC_CONST_PROPERTY,
	PT_CLASS_PROPERTY_FETCH,
};

/* the class entry -> matching arms memo: direct-mapped, a collision
 * recomputes */
#define PT_IETR_ARM_CACHE_BITS_LIMIT 7

struct IetrArmSlot
{
	zend_class_entry *ce;
	uint32_t generation;
	uint64_t arms;
};

inline IetrArmSlot ietrArmCache[1u << PT_IETR_ARM_CACHE_BITS_LIMIT];

/* the arms whose class $expr's class is an instance of, bit per arm (the
 * twin tests them in order, falling through an arm whose extra condition
 * fails); false = pending exception (the class map failing) */
inline bool getTypeArmsOf(zend_class_entry *ce, uint64_t &arms)
{
	uintptr_t hash = ((uintptr_t) ce >> 4) * (uintptr_t) 0x9E3779B97F4A7C15ull;
	IetrArmSlot &slot = ietrArmCache[hash >> (sizeof(uintptr_t) * 8 - PT_IETR_ARM_CACHE_BITS_LIMIT)];
	if (EXPECTED(slot.ce == ce && slot.generation == pt_engine_generation)) {
		arms = slot.arms;
		return true;
	}
	uint64_t computed = 0;
	for (unsigned arm = 0; arm < ARM_COUNT; arm++) {
		zend_class_entry *armCe = pt_class(ietrArmClasses[arm]);
		if (UNEXPECTED(armCe == NULL)) return false;
		if (instanceof_function(ce, armCe)) computed |= (uint64_t) 1 << arm;
	}
	slot.ce = ce;
	slot.generation = pt_engine_generation;
	slot.arms = computed;
	arms = computed;
	return true;
}

/* }}} */

/* Mirrors PHPStan\Reflection\InitializerExprTypeResolver; UNDEF = pending
 * exception. */
class InitializerExprTypeResolver
{
public:
	explicit InitializerExprTypeResolver(zend_object *self) : self(self) {}

	/* {{{ getConcatType() / resolveConcatType() */

	/* Mirrors getConcatType(). */
	zv::Val getConcatType(zval *left, zval *right, const pt_ietr_get_type &getTypeCallback) const
	{
		IETR_VAL(leftType, getTypeOf(getTypeCallback, left));
		IETR_VAL(rightType, getTypeOf(getTypeCallback, right));
		return resolveConcatType(leftType.raw(), rightType.raw());
	}

	/* Mirrors resolveConcatType(). */
	zv::Val resolveConcatType(zval *left, zval *right) const
	{
		IETR_VAL(leftStringType, callOn(left, mToString));
		IETR_VAL(rightStringType, callOn(right, mToString));
		{
			IETR_VAL(union_, union2(leftStringType.raw(), rightStringType.raw()));
			if (isA(union_.raw(), pt_ce_error_type)) return newErrorType();
		}

		int leftEmpty = isEmptyConstantString(leftStringType.raw());
		if (UNEXPECTED(leftEmpty < 0)) return zv::Val();
		if (leftEmpty) return rightStringType;

		int rightEmpty = isEmptyConstantString(rightStringType.raw());
		if (UNEXPECTED(rightEmpty < 0)) return zv::Val();
		if (rightEmpty) return leftStringType;

		if (isA(leftStringType.raw(), pt_ce_constant_string_type) && isA(rightStringType.raw(), pt_ce_constant_string_type)) {
			return callOn(leftStringType.raw(), mAppend, 1, rightStringType.raw());
		}

		IETR_VAL(leftConstantStrings, callOn(leftStringType.raw(), mGetConstantStrings));
		IETR_VAL(rightConstantStrings, callOn(rightStringType.raw(), mGetConstantStrings));
		if (UNEXPECTED(!requireArray(leftConstantStrings.raw()) || !requireArray(rightConstantStrings.raw()))) return zv::Val();
		zend_long combinedConstantStringsCount = (zend_long) zend_hash_num_elements(Z_ARRVAL_P(leftConstantStrings.raw())) * (zend_long) zend_hash_num_elements(Z_ARRVAL_P(rightConstantStrings.raw()));

		// we limit the number of union-types for performance reasons
		if (combinedConstantStringsCount > 0 && combinedConstantStringsCount <= PT_IETR_CALCULATE_SCALARS_LIMIT) {
			zv::Arr strings = zv::Arr::create((uint32_t) combinedConstantStringsCount);
			for (zv::ArrayEntry leftEntry : zv::ArrRef(leftConstantStrings.raw())) {
				zval *leftConstantString = leftEntry.value().raw();
				int leftStringEmpty = constantStringValueIsEmpty(leftConstantString);
				if (UNEXPECTED(leftStringEmpty < 0)) return zv::Val();
				if (leftStringEmpty) {
					// $strings = array_merge($strings, $rightConstantStrings)
					for (zv::ArrayEntry rightEntry : zv::ArrRef(rightConstantStrings.raw())) {
						if (rightEntry.hasStringKey()) {
							strings.set(rightEntry.stringKey(), zv::Val::copyOf(rightEntry.value()));
						} else {
							strings.push(rightEntry.value());
						}
					}
					continue;
				}

				for (zv::ArrayEntry rightEntry : zv::ArrRef(rightConstantStrings.raw())) {
					zval *rightConstantString = rightEntry.value().raw();
					int rightStringEmpty = constantStringValueIsEmpty(rightConstantString);
					if (UNEXPECTED(rightStringEmpty < 0)) return zv::Val();
					if (rightStringEmpty) {
						strings.push(zv::Ref(leftConstantString));
						continue;
					}

					IETR_VAL(appended, callOn(leftConstantString, mAppend, 1, rightConstantString));
					strings.push(std::move(appended));
				}
			}

			if (zend_hash_num_elements(strings.table()) > 0) {
				return unionOf(strings);
			}
		}

		zv::Arr accessoryTypes = zv::Arr::create(6);
		{
			IETR_TRI(leftNonEmpty, triOn(leftStringType.raw(), mIsNonEmptyString));
			IETR_TRI(rightNonEmpty, triOn(rightStringType.raw(), mIsNonEmptyString));
			if (pt_trinary_and(leftNonEmpty, rightNonEmpty) == PT_TRI_YES) {
				zval accessory;
				if (UNEXPECTED(!pt_accessory_non_falsy_string_type_new(&accessory))) return zv::Val();
				accessoryTypes.push(zv::Val::adopt(accessory));
			} else {
				IETR_TRI(leftNonFalsy, triOn(leftStringType.raw(), mIsNonFalsyString));
				IETR_TRI(rightNonFalsy, triOn(rightStringType.raw(), mIsNonFalsyString));
				if (pt_trinary_or(leftNonFalsy, rightNonFalsy) == PT_TRI_YES) {
					zval accessory;
					if (UNEXPECTED(!pt_accessory_non_falsy_string_type_new(&accessory))) return zv::Val();
					accessoryTypes.push(zv::Val::adopt(accessory));
				} else {
					IETR_TRI(leftNonEmpty2, triOn(leftStringType.raw(), mIsNonEmptyString));
					IETR_TRI(rightNonEmpty2, triOn(rightStringType.raw(), mIsNonEmptyString));
					if (pt_trinary_or(leftNonEmpty2, rightNonEmpty2) == PT_TRI_YES) {
						zval accessory;
						if (UNEXPECTED(!pt_accessory_non_empty_string_type_new(&accessory))) return zv::Val();
						accessoryTypes.push(zv::Val::adopt(accessory));
					}
				}
			}
		}

		{
			IETR_TRI(leftLiteral, triOn(leftStringType.raw(), mIsLiteralString));
			IETR_TRI(rightLiteral, triOn(rightStringType.raw(), mIsLiteralString));
			if (pt_trinary_and(leftLiteral, rightLiteral) == PT_TRI_YES) {
				zval accessory;
				if (UNEXPECTED(!pt_accessory_literal_string_type_new(&accessory))) return zv::Val();
				accessoryTypes.push(zv::Val::adopt(accessory));
			}
		}

		{
			IETR_TRI(leftLowercase, triOn(leftStringType.raw(), mIsLowercaseString));
			IETR_TRI(rightLowercase, triOn(rightStringType.raw(), mIsLowercaseString));
			if (pt_trinary_and(leftLowercase, rightLowercase) == PT_TRI_YES) {
				zval accessory;
				if (UNEXPECTED(!pt_accessory_lowercase_string_type_new(&accessory))) return zv::Val();
				accessoryTypes.push(zv::Val::adopt(accessory));
			}
		}

		{
			IETR_TRI(leftUppercase, triOn(leftStringType.raw(), mIsUppercaseString));
			IETR_TRI(rightUppercase, triOn(rightStringType.raw(), mIsUppercaseString));
			if (pt_trinary_and(leftUppercase, rightUppercase) == PT_TRI_YES) {
				zval accessory;
				if (UNEXPECTED(!pt_accessory_uppercase_string_type_new(&accessory))) return zv::Val();
				accessoryTypes.push(zv::Val::adopt(accessory));
			}
		}

		{
			zval emptyString;
			ZVAL_EMPTY_STRING(&emptyString);
			IETR_VAL(emptyStringType, newConstantStringType(Z_STR(emptyString)));
			IETR_VAL(leftNumericStringNonEmpty, pt_type_combinator_remove(leftStringType.raw(), emptyStringType.raw()));
			IETR_TRI(leftNumeric, triOn(leftNumericStringNonEmpty.raw(), mIsNumericString));
			if (leftNumeric == PT_TRI_YES) {
				IETR_TRI(leftIsInteger, triOn(left, mIsInteger));
				bool integerValidation = leftIsInteger == PT_TRI_YES;

				bool allRightConstantsZeroOrMore = false;
				for (zv::ArrayEntry rightEntry : zv::ArrRef(rightConstantStrings.raw())) {
					zval *rightConstantString = rightEntry.value().raw();
					IETR_VAL(value, constantStringValue(rightConstantString));
					if (UNEXPECTED(Z_TYPE_P(value.raw()) != IS_STRING)) {
						// ConstantStringType::getValue(): string
						pt_throw_should_not_happen();
						return zv::Val();
					}
					zend_string *string = Z_STR_P(value.raw());
					if (ZSTR_LEN(string) == 0) {
						continue;
					}

					if (
						is_numeric_string(ZSTR_VAL(string), ZSTR_LEN(string), NULL, NULL, false) == 0
						|| !(integerValidation ? ZSTR_VAL(string)[0] != '-' : isDecimalDigits(string))
					) {
						allRightConstantsZeroOrMore = false;
						break;
					}

					allRightConstantsZeroOrMore = true;
				}

				bool nonNegativeRight = allRightConstantsZeroOrMore;
				if (!nonNegativeRight) {
					IETR_VAL(zeroOrMoreInteger, fromInterval(NullableLong::of(0), NullableLong::null()));
					IETR_TRI(zeroOrMoreRight, triOn(zeroOrMoreInteger.raw(), mIsSuperTypeOf, 1, right));
					nonNegativeRight = zeroOrMoreRight == PT_TRI_YES;
				}
				if (nonNegativeRight) {
					zval accessory;
					if (UNEXPECTED(!pt_accessory_numeric_string_type_new(&accessory))) return zv::Val();
					accessoryTypes.push(zv::Val::adopt(accessory));
				}
			}
		}

		if (zend_hash_num_elements(accessoryTypes.table()) > 0) {
			IETR_VAL(stringType, newStringType());
			accessoryTypes.push(std::move(stringType));
			return newIntersectionType(accessoryTypes);
		}

		return newStringType();
	}

	/* }}} */

	/* {{{ the bitwise operators */

	/* Mirrors getBitwiseAndType() / getBitwiseOrType() / getBitwiseXorType()
	 * (kind: IETR_BITWISE_AND / _OR / _XOR — the three bodies differ only in
	 * the node class, the operation and the range helper) */
	zv::Val getBitwiseType(IetrBinaryKind kind, zval *left, zval *right, const pt_ietr_get_type &getTypeCallback) const
	{
		IETR_VAL(leftTypeHold, getTypeOf(getTypeCallback, left));
		IETR_VAL(rightTypeHold, getTypeOf(getTypeCallback, right));
		zval *leftType = leftTypeHold.raw();
		zval *rightType = rightTypeHold.raw();

		{
			IETR_VAL(node, newBinaryOpNode(ietrBinaryKindClasses[kind], left, right));
			IETR_VAL(specifiedTypes, callOperatorTypeSpecifyingExtensions(prop(slots::operatorTypeSpecifyingExtensionRegistry), node.raw(), leftType, rightType));
			if (Z_TYPE_P(specifiedTypes.raw()) != IS_NULL) return specifiedTypes;
		}

		if (isA(leftType, pt_ce_never_type) || isA(rightType, pt_ce_never_type)) {
			return getNeverType(leftType, rightType);
		}

		zv::Val result;
		zend_long code = getFiniteOrConstantScalarTypes(leftType, rightType, kind, result);
		if (UNEXPECTED(code < 0)) return zv::Val();
		if (code == 0) return result;

		// computed before optimizeScalarType() below widens integer ranges to int
		IETR_VAL(leftNumberType, callOn(leftType, mToNumber));
		IETR_VAL(rightNumberType, callOn(rightType, mToNumber));

		zv::Val optimizedLeft;
		zv::Val optimizedRight;
		if (code == PT_IETR_IS_SCALAR_TYPE) {
			optimizedLeft = optimizeScalarType(leftType);
			if (UNEXPECTED(optimizedLeft.isUndef())) return zv::Val();
			leftType = optimizedLeft.raw();
			optimizedRight = optimizeScalarType(rightType);
			if (UNEXPECTED(optimizedRight.isUndef())) return zv::Val();
			rightType = optimizedRight.raw();
		}

		bool leftMixed = isA(leftType, pt_ce_mixed_type);
		bool rightMixed = isA(rightType, pt_ce_mixed_type);
		if (leftMixed && rightMixed) {
			IETR_VAL(integerType, newIntegerType());
			IETR_VAL(stringType, newStringType());
			zv::Arr types = zv::Arr::create(2);
			types.push(std::move(integerType));
			types.push(std::move(stringType));
			return newBenevolentUnionType(types);
		}

		IETR_TRI(leftIsString, triOn(leftType, mIsString));
		IETR_TRI(rightIsString, triOn(rightType, mIsString));
		if (
			(leftIsString == PT_TRI_YES || leftMixed)
			&& (rightIsString == PT_TRI_YES || rightMixed)
		) {
			return newStringType();
		}
		if (leftIsString == PT_TRI_MAYBE && rightIsString == PT_TRI_MAYBE) {
			return newErrorType();
		}

		{
			IETR_VAL(leftToNumber, callOn(leftType, mToNumber));
			if (isA(leftToNumber.raw(), pt_ce_error_type)) return newErrorType();
			IETR_VAL(rightToNumber, callOn(rightType, mToNumber));
			if (isA(rightToNumber.raw(), pt_ce_error_type)) return newErrorType();
		}

		zv::Val range = kind == IETR_BITWISE_AND
			? computeBitwiseAndRange(leftNumberType.raw(), rightNumberType.raw())
			: computeBitwiseOrXorRange(leftNumberType.raw(), rightNumberType.raw());
		if (UNEXPECTED(range.isUndef())) return zv::Val();
		if (Z_TYPE_P(range.raw()) != IS_NULL) return range;

		return newIntegerType();
	}

	/* Mirrors getFiniteOrConstantScalarTypes() with the operation of `kind`:
	 * 0 = the Type in `result`, PT_IETR_IS_SCALAR_TYPE / PT_IETR_IS_UNKNOWN,
	 * -1 = pending exception */
	zend_long getFiniteOrConstantScalarTypes(zval *leftType, zval *rightType, IetrBinaryKind kind, zv::Val &result) const
	{
		pt_ietr_binary_op operation = kind == IETR_BITWISE_AND ? bitwise_and_function : (kind == IETR_BITWISE_OR ? bitwise_or_function : bitwise_xor_function);
		return getFiniteOrConstantScalarTypesWith(leftType, rightType, operation, NULL, result);
	}

	/* the same with the operation as the engine's operator function or, for
	 * a PHP caller of the private method, its $operationCallable */
	static zend_long getFiniteOrConstantScalarTypesWith(zval *leftType, zval *rightType, pt_ietr_binary_op operation, zval *operationCallable, zv::Val &result)
	{
		zv::Val leftTypes = isA(leftType, pt_ce_integer_range_type) ? callOn(leftType, mGetFiniteTypes) : callOn(leftType, mGetConstantScalarTypes);
		if (UNEXPECTED(leftTypes.isUndef())) return -1;
		zv::Val rightTypes = isA(rightType, pt_ce_integer_range_type) ? callOn(rightType, mGetFiniteTypes) : callOn(rightType, mGetConstantScalarTypes);
		if (UNEXPECTED(rightTypes.isUndef())) return -1;
		if (UNEXPECTED(!requireArray(leftTypes.raw()) || !requireArray(rightTypes.raw()))) return -1;

		zend_long leftTypesCount = zend_hash_num_elements(Z_ARRVAL_P(leftTypes.raw()));
		zend_long rightTypesCount = zend_hash_num_elements(Z_ARRVAL_P(rightTypes.raw()));

		if (leftTypesCount == 0 || rightTypesCount == 0) {
			return PT_IETR_IS_UNKNOWN;
		}

		bool generalize = leftTypesCount * rightTypesCount > PT_IETR_CALCULATE_SCALARS_LIMIT;
		if (generalize) {
			return PT_IETR_IS_SCALAR_TYPE;
		}

		zend_class_entry *constantScalarCe = pt_class(PT_CLASS_CONSTANT_SCALAR_TYPE);
		if (UNEXPECTED(constantScalarCe == NULL)) return -1;
		zv::Arr resultTypes = zv::Arr::create((uint32_t) (leftTypesCount * rightTypesCount));
		for (zv::ArrayEntry leftEntry : zv::ArrRef(leftTypes.raw())) {
			zval *leftTypeInner = leftEntry.value().raw();
			for (zv::ArrayEntry rightEntry : zv::ArrRef(rightTypes.raw())) {
				zval *rightTypeInner = rightEntry.value().raw();
				zv::Val resultType;
				if (isA(leftTypeInner, pt_ce_constant_string_type) && isA(rightTypeInner, pt_ce_constant_string_type)) {
					zv::Val leftValue = callOn(leftTypeInner, mGetValue);
					if (UNEXPECTED(leftValue.isUndef())) return -1;
					zv::Val rightValue = callOn(rightTypeInner, mGetValue);
					if (UNEXPECTED(rightValue.isUndef())) return -1;
					zv::Val resultValue = applyOperation(operation, operationCallable, leftValue.raw(), rightValue.raw());
					if (UNEXPECTED(resultValue.isUndef())) return -1;
					resultType = typeFromValue(resultValue.raw());
				} else {
					zv::Val leftNumberType = callOn(leftTypeInner, mToNumber);
					if (UNEXPECTED(leftNumberType.isUndef())) return -1;
					zv::Val rightNumberType = callOn(rightTypeInner, mToNumber);
					if (UNEXPECTED(rightNumberType.isUndef())) return -1;

					if (isA(leftNumberType.raw(), pt_ce_error_type) || isA(rightNumberType.raw(), pt_ce_error_type)) {
						result = newErrorType();
						return result.isUndef() ? -1 : 0;
					}

					if (!isA(leftNumberType.raw(), constantScalarCe) || !isA(rightNumberType.raw(), constantScalarCe)) {
						pt_throw_should_not_happen();
						return -1;
					}

					zv::Val leftValue = callOn(leftNumberType.raw(), mGetValue);
					if (UNEXPECTED(leftValue.isUndef())) return -1;
					zv::Val rightValue = callOn(rightNumberType.raw(), mGetValue);
					if (UNEXPECTED(rightValue.isUndef())) return -1;
					zv::Val resultValue = applyOperation(operation, operationCallable, leftValue.raw(), rightValue.raw());
					if (UNEXPECTED(resultValue.isUndef())) return -1;
					resultType = typeFromValue(resultValue.raw());
				}
				if (UNEXPECTED(resultType.isUndef())) return -1;
				resultTypes.push(std::move(resultType));
			}
		}
		result = unionOf(resultTypes);
		return result.isUndef() ? -1 : 0;
	}

	/* $operationCallable($a, $b) or the operator */
	static zv::Val applyOperation(pt_ietr_binary_op operation, zval *operationCallable, zval *a, zval *b)
	{
		if (operationCallable != NULL) {
			zv::Args argv{a, b};
			return pt_type_call_callable(operationCallable, 2, argv);
		}
		return phpOp(operation, a, b);
	}

	/* }}} */

	/* {{{ getSpaceshipType() */

	/* Mirrors getSpaceshipType(). */
	zv::Val getSpaceshipType(zval *left, zval *right, const pt_ietr_get_type &getTypeCallback) const
	{
		IETR_VAL(leftTypes, getTypeOf(getTypeCallback, left));
		IETR_VAL(rightTypes, getTypeOf(getTypeCallback, right));

		if (isA(leftTypes.raw(), pt_ce_never_type) || isA(rightTypes.raw(), pt_ce_never_type)) {
			return getNeverType(leftTypes.raw(), rightTypes.raw());
		}

		IETR_VAL(leftValues, callOn(leftTypes.raw(), mGetConstantScalarValues));
		IETR_VAL(rightValues, callOn(rightTypes.raw(), mGetConstantScalarValues));
		if (UNEXPECTED(!requireArray(leftValues.raw()) || !requireArray(rightValues.raw()))) return zv::Val();

		zend_long leftValuesCount = zend_hash_num_elements(Z_ARRVAL_P(leftValues.raw()));
		zend_long rightValuesCount = zend_hash_num_elements(Z_ARRVAL_P(rightValues.raw()));
		if (leftValuesCount > 0 && rightValuesCount > 0 && leftValuesCount * rightValuesCount <= PT_IETR_CALCULATE_SCALARS_LIMIT) {
			zv::Arr resultTypes = zv::Arr::create((uint32_t) (leftValuesCount * rightValuesCount));
			for (zv::ArrayEntry leftEntry : zv::ArrRef(leftValues.raw())) {
				for (zv::ArrayEntry rightEntry : zv::ArrRef(rightValues.raw())) {
					zval comparison;
					ZVAL_LONG(&comparison, zend_compare(leftEntry.value().raw(), rightEntry.value().raw()));
					if (UNEXPECTED(EG(exception) != NULL)) return zv::Val();
					IETR_VAL(resultType, typeFromValue(&comparison));
					resultTypes.push(std::move(resultType));
				}
			}
			return unionOf(resultTypes);
		}

		return fromInterval(NullableLong::of(-1), NullableLong::of(1));
	}

	/* }}} */

	/* {{{ the arithmetic operators */

	/* the constant-scalar product of getPlusType() / getMinusType() /
	 * getMulType() / getDivTypeFromTypes() / getModType() /
	 * getShiftLeftType() / getShiftRightType(): 0 = the Type in `result`
	 * (the union, or an ErrorType the twin returns from inside the loop),
	 * 1 = generalize (the twin's optimizeScalarType() of both sides follows),
	 * 2 = not both sides constant, -1 = pending exception */
	int constantScalarArithmetic(zval *leftType, zval *rightType, IetrBinaryKind kind, zv::Val &result) const
	{
		zv::Val leftTypes = callOn(leftType, mGetConstantScalarTypes);
		if (UNEXPECTED(leftTypes.isUndef())) return -1;
		zv::Val rightTypes = callOn(rightType, mGetConstantScalarTypes);
		if (UNEXPECTED(rightTypes.isUndef())) return -1;
		if (UNEXPECTED(!requireArray(leftTypes.raw()) || !requireArray(rightTypes.raw()))) return -1;
		zend_long leftTypesCount = zend_hash_num_elements(Z_ARRVAL_P(leftTypes.raw()));
		zend_long rightTypesCount = zend_hash_num_elements(Z_ARRVAL_P(rightTypes.raw()));
		if (leftTypesCount <= 0 || rightTypesCount <= 0) return 2;

		bool generalize = leftTypesCount * rightTypesCount > PT_IETR_CALCULATE_SCALARS_LIMIT;
		if (generalize) return 1;

		zend_class_entry *constantScalarCe = pt_class(PT_CLASS_CONSTANT_SCALAR_TYPE);
		if (UNEXPECTED(constantScalarCe == NULL)) return -1;
		zv::Arr resultTypes = zv::Arr::create((uint32_t) (leftTypesCount * rightTypesCount));
		for (zv::ArrayEntry leftEntry : zv::ArrRef(leftTypes.raw())) {
			for (zv::ArrayEntry rightEntry : zv::ArrRef(rightTypes.raw())) {
				zv::Val leftNumberType = callOn(leftEntry.value().raw(), mToNumber);
				if (UNEXPECTED(leftNumberType.isUndef())) return -1;
				zv::Val rightNumberType = callOn(rightEntry.value().raw(), mToNumber);
				if (UNEXPECTED(rightNumberType.isUndef())) return -1;

				if (isA(leftNumberType.raw(), pt_ce_error_type) || isA(rightNumberType.raw(), pt_ce_error_type)) {
					result = newErrorType();
					return result.isUndef() ? -1 : 0;
				}

				if (!isA(leftNumberType.raw(), constantScalarCe) || !isA(rightNumberType.raw(), constantScalarCe)) {
					pt_throw_should_not_happen();
					return -1;
				}

				zv::Val leftValue = callOn(leftNumberType.raw(), mGetValue);
				if (UNEXPECTED(leftValue.isUndef())) return -1;
				zv::Val rightValue = callOn(rightNumberType.raw(), mGetValue);
				if (UNEXPECTED(rightValue.isUndef())) return -1;

				zv::Val resultValue;
				switch (kind) {
					case IETR_PLUS:
						resultValue = phpOp(add_function, leftValue.raw(), rightValue.raw());
						break;
					case IETR_MINUS:
						resultValue = phpOp(sub_function, leftValue.raw(), rightValue.raw());
						break;
					case IETR_MUL:
						resultValue = phpOp(mul_function, leftValue.raw(), rightValue.raw());
						break;
					case IETR_DIV:
						if (isZeroNumber(rightValue.raw())) {
							result = newErrorType();
							return result.isUndef() ? -1 : 0;
						}
						resultValue = phpOp(div_function, leftValue.raw(), rightValue.raw());
						break;
					case IETR_MOD: {
						zval rightInteger;
						ZVAL_LONG(&rightInteger, zval_get_long(rightValue.raw()));
						if (Z_LVAL(rightInteger) == 0) {
							result = newErrorType();
							return result.isUndef() ? -1 : 0;
						}
						zval leftInteger;
						ZVAL_LONG(&leftInteger, zval_get_long(leftValue.raw()));
						resultValue = phpOp(mod_function, &leftInteger, &rightInteger);
						break;
					}
					case IETR_SHIFT_LEFT:
					case IETR_SHIFT_RIGHT: {
						zval zero;
						ZVAL_LONG(&zero, 0);
						if (lessThan(rightValue.raw(), &zero)) {
							result = newErrorType();
							return result.isUndef() ? -1 : 0;
						}
						zval leftInteger, rightInteger;
						ZVAL_LONG(&leftInteger, zval_get_long(leftValue.raw()));
						ZVAL_LONG(&rightInteger, zval_get_long(rightValue.raw()));
						resultValue = phpOp(kind == IETR_SHIFT_LEFT ? shift_left_function : shift_right_function, &leftInteger, &rightInteger);
						break;
					}
					default:
						pt_throw_should_not_happen();
						return -1;
				}
				if (UNEXPECTED(resultValue.isUndef())) return -1;
				zv::Val resultType = typeFromValue(resultValue.raw());
				if (UNEXPECTED(resultType.isUndef())) return -1;
				resultTypes.push(std::move(resultType));
			}
		}

		result = unionOf(resultTypes);
		return result.isUndef() ? -1 : 0;
	}

	/* Mirrors getDivType(). */
	zv::Val getDivType(zval *left, zval *right, const pt_ietr_get_type &getTypeCallback) const
	{
		IETR_VAL(leftType, getTypeOf(getTypeCallback, left));
		IETR_VAL(rightType, getTypeOf(getTypeCallback, right));

		IETR_VAL(result, getDivTypeFromTypes(left, right, leftType.raw(), rightType.raw()));

		IETR_TRI(leftIsInteger, triOn(leftType.raw(), mIsInteger));
		if (leftIsInteger == PT_TRI_YES) {
			IETR_TRI(rightIsInteger, triOn(rightType.raw(), mIsInteger));
			if (rightIsInteger == PT_TRI_YES) {
				IETR_VAL(modNode, newBinaryOpNode(PT_CLASS_MOD_EXPR, left, right));
				IETR_VAL(modType, getTypeOf(getTypeCallback, modNode.raw()));
				IETR_TRI(modIsInteger, triOn(modType.raw(), mIsInteger));
				if (modIsInteger == PT_TRI_YES) {
					IETR_VAL(zero, newConstantIntegerType(0));
					IETR_TRI(zeroIsSuperTypeOfMod, triOn(zero.raw(), mIsSuperTypeOf, 1, modType.raw()));
					if (zeroIsSuperTypeOfMod == PT_TRI_YES) {
						IETR_VAL(floatType, newFloatType());
						IETR_VAL(withoutFloat, pt_type_combinator_remove(result.raw(), floatType.raw()));

						// PHP_INT_MIN / -1 divides without a remainder but still overflows to a float
						if (!isA(withoutFloat.raw(), pt_ce_never_type)) {
							return withoutFloat;
						}
					}
				}
			}
		}

		return result;
	}

	/* Mirrors getDivTypeFromTypes(). */
	zv::Val getDivTypeFromTypes(zval *left, zval *right, zval *leftType, zval *rightType) const
	{
		zv::Val result;
		int constant = constantScalarArithmetic(leftType, rightType, IETR_DIV, result);
		if (UNEXPECTED(constant < 0)) return zv::Val();
		if (constant == 0) return result;

		zv::Val optimizedLeft;
		zv::Val optimizedRight;
		if (constant == 1) {
			optimizedLeft = optimizeScalarType(leftType);
			if (UNEXPECTED(optimizedLeft.isUndef())) return zv::Val();
			leftType = optimizedLeft.raw();
			optimizedRight = optimizeScalarType(rightType);
			if (UNEXPECTED(optimizedRight.isUndef())) return zv::Val();
			rightType = optimizedRight.raw();
		}

		{
			IETR_VAL(rightNumberType, callOn(rightType, mToNumber));
			IETR_VAL(rightScalarValues, callOn(rightNumberType.raw(), mGetConstantScalarValues));
			if (UNEXPECTED(!requireArray(rightScalarValues.raw()))) return zv::Val();
			for (zv::ArrayEntry entry : zv::ArrRef(rightScalarValues.raw())) {
				if (isZeroNumber(entry.value().raw())) {
					return newErrorType();
				}
			}
		}

		return resolveCommonMath(IETR_DIV, left, right, NULL, leftType, rightType);
	}

	/* Mirrors getModType(). */
	zv::Val getModType(zval *left, zval *right, const pt_ietr_get_type &getTypeCallback) const
	{
		IETR_VAL(leftTypeHold, getTypeOf(getTypeCallback, left));
		IETR_VAL(rightTypeHold, getTypeOf(getTypeCallback, right));
		zval *leftType = leftTypeHold.raw();
		zval *rightType = rightTypeHold.raw();

		if (isA(leftType, pt_ce_never_type) || isA(rightType, pt_ce_never_type)) {
			return getNeverType(leftType, rightType);
		}

		{
			IETR_VAL(node, newBinaryOpNode(PT_CLASS_MOD_EXPR, left, right));
			IETR_VAL(extensionSpecified, callOperatorTypeSpecifyingExtensions(prop(slots::operatorTypeSpecifyingExtensionRegistry), node.raw(), leftType, rightType));
			if (Z_TYPE_P(extensionSpecified.raw()) != IS_NULL) return extensionSpecified;
		}

		{
			IETR_VAL(leftToNumber, callOn(leftType, mToNumber));
			if (isA(leftToNumber.raw(), pt_ce_error_type)) return newErrorType();
			IETR_VAL(rightToNumber, callOn(rightType, mToNumber));
			if (isA(rightToNumber.raw(), pt_ce_error_type)) return newErrorType();
		}

		zv::Val result;
		int constant = constantScalarArithmetic(leftType, rightType, IETR_MOD, result);
		if (UNEXPECTED(constant < 0)) return zv::Val();
		if (constant == 0) return result;

		zv::Val optimizedLeft;
		zv::Val optimizedRight;
		if (constant == 1) {
			optimizedLeft = optimizeScalarType(leftType);
			if (UNEXPECTED(optimizedLeft.isUndef())) return zv::Val();
			leftType = optimizedLeft.raw();
			optimizedRight = optimizeScalarType(rightType);
			if (UNEXPECTED(optimizedRight.isUndef())) return zv::Val();
			rightType = optimizedRight.raw();
		}

		{
			IETR_VAL(integerType, callOn(rightType, mToInteger));
			if (isA(integerType.raw(), pt_ce_constant_integer_type)) {
				zend_long value;
				if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(integerType.raw()), value))) return zv::Val();
				if (value == 1) return newConstantIntegerType(0);
			}
		}

		{
			IETR_VAL(rightNumberType, callOn(rightType, mToNumber));
			IETR_VAL(rightScalarValues, callOn(rightNumberType.raw(), mGetConstantScalarValues));
			if (UNEXPECTED(!requireArray(rightScalarValues.raw()))) return zv::Val();
			for (zv::ArrayEntry entry : zv::ArrRef(rightScalarValues.raw())) {
				if (isZeroNumber(entry.value().raw())) {
					return newErrorType();
				}
			}
		}

		NullableLong maxMagnitude = NullableLong::null();
		{
			IETR_VAL(rightInteger, callOn(rightType, mToInteger));
			bool hasDivisorBounds;
			NullableLong divisorMin, divisorMax;
			if (UNEXPECTED(!getIntegerBounds(rightInteger.raw(), hasDivisorBounds, divisorMin, divisorMax))) return zv::Val();
			if (hasDivisorBounds) {
				maxMagnitude = getMaxModuloMagnitude(divisorMin, divisorMax);
			}
		}

		bool hasLeftBounds;
		NullableLong leftMin, leftMax;
		{
			IETR_VAL(leftInteger, callOn(leftType, mToInteger));
			if (UNEXPECTED(!getIntegerBounds(leftInteger.raw(), hasLeftBounds, leftMin, leftMax))) return zv::Val();
		}
		if (!hasLeftBounds) {
			leftMin = NullableLong::null();
			leftMax = NullableLong::null();
		}
		if (leftMin.isNull) {
			IETR_VAL(nonNegative, fromInterval(NullableLong::of(0), NullableLong::null()));
			IETR_TRI(leftIsNonNegative, triOn(nonNegative.raw(), mIsSuperTypeOf, 1, leftType));
			if (leftIsNonNegative == PT_TRI_YES) leftMin = NullableLong::of(0);
		}
		if (leftMax.isNull) {
			IETR_VAL(nonPositive, fromInterval(NullableLong::null(), NullableLong::of(0)));
			IETR_TRI(leftIsNonPositive, triOn(nonPositive.raw(), mIsSuperTypeOf, 1, leftType));
			if (leftIsNonPositive == PT_TRI_YES) leftMax = NullableLong::of(0);
		}

		// The result has the sign of the dividend and is never bigger in magnitude than either
		// the dividend or the largest magnitude the divisor can have.
		NullableLong rangeMax;
		if (!leftMax.isNull && leftMax.value <= 0) {
			rangeMax = NullableLong::of(0);
		} else if (maxMagnitude.isNull) {
			rangeMax = leftMax;
		} else if (leftMax.isNull) {
			rangeMax = maxMagnitude;
		} else {
			rangeMax = NullableLong::of(std::min(leftMax.value, maxMagnitude.value));
		}

		NullableLong rangeMin;
		if (!leftMin.isNull && leftMin.value >= 0) {
			rangeMin = NullableLong::of(0);
		} else if (maxMagnitude.isNull) {
			rangeMin = leftMin;
		} else if (leftMin.isNull) {
			rangeMin = NullableLong::of(-maxMagnitude.value);
		} else {
			rangeMin = NullableLong::of(std::max(leftMin.value, -maxMagnitude.value));
		}

		return fromInterval(rangeMin, rangeMax);
	}

	/* Mirrors toIntBound(): the bound an overflowing float stands for, since
	 * the values past the int range are floats. ZEND_LONG_MAX is not
	 * representable as a double, so the first double past the int range is
	 * (double) ZEND_LONG_MAX itself. */
	static void toIntBound(double value, zval *out)
	{
		if (!std::isfinite(value)) {
			ZVAL_NULL(out);
		} else if (value >= (double) ZEND_LONG_MAX) {
			ZVAL_LONG(out, ZEND_LONG_MAX);
		} else if (value <= (double) ZEND_LONG_MIN) {
			ZVAL_LONG(out, ZEND_LONG_MIN);
		} else {
			ZVAL_LONG(out, (zend_long) value);
		}
	}

	/* private static: the highest possible absolute value of `$x % $divisor`,
	 * one less than the largest possible absolute value of the divisor; null
	 * when there is no such bound. A divisor reaching PHP_INT_MIN is reported
	 * as unbounded too: abs(PHP_INT_MIN) does not fit into an integer, and the
	 * bound it stands for is PHP_INT_MAX, which is all an unbounded result can
	 * hold anyway */
	static NullableLong getMaxModuloMagnitude(NullableLong divisorMin, NullableLong divisorMax)
	{
		if (divisorMin.isNull || divisorMax.isNull || divisorMin.value == ZEND_LONG_MIN) return NullableLong::null();
		zend_long absMin = divisorMin.value < 0 ? -divisorMin.value : divisorMin.value;
		zend_long absMax = divisorMax.value < 0 ? -divisorMax.value : divisorMax.value;
		zend_long magnitude = std::max(absMin, absMax);
		if (magnitude == 0) return NullableLong::null();
		return NullableLong::of(magnitude - 1);
	}

	/* private: the lowest and highest value the type can hold, null for an
	 * unbounded side; `has` false when the type is not built from integer
	 * ranges and constants. false = pending exception */
	[[nodiscard]] bool getIntegerBounds(zval *type, bool &has, NullableLong &outMin, NullableLong &outMax) const
	{
		has = false;
		zv::Val innerTypesHold;
		zval single;
		HashTable *innerTypes = NULL;
		if (isA(type, pt_ce_union_type)) {
			zv::Val types = callOn(type, mGetTypes);
			if (UNEXPECTED(types.isUndef() || !requireArray(types.raw()))) return false;
			innerTypesHold = std::move(types);
			innerTypes = Z_ARRVAL_P(innerTypesHold.raw());
			if (zend_hash_num_elements(innerTypes) == 0) return true;
		}

		NullableLong min = NullableLong::null();
		NullableLong max = NullableLong::null();
		bool unboundedMin = false;
		bool unboundedMax = false;
		auto visit = [&](zval *innerType, bool &known) -> bool {
			NullableLong innerMin, innerMax;
			known = true;
			if (isA(innerType, pt_ce_integer_range_type)) {
				if (UNEXPECTED(!pt_integer_range_bounds(Z_OBJ_P(innerType), innerMin, innerMax))) return false;
			} else if (isA(innerType, pt_ce_constant_integer_type)) {
				zend_long value;
				if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(innerType), value))) return false;
				innerMin = NullableLong::of(value);
				innerMax = innerMin;
			} else {
				known = false;
				return true;
			}

			if (innerMin.isNull) {
				unboundedMin = true;
			} else if (min.isNull || innerMin.value < min.value) {
				min = innerMin;
			}

			if (innerMax.isNull) {
				unboundedMax = true;
			} else if (max.isNull || innerMax.value > max.value) {
				max = innerMax;
			}
			return true;
		};

		if (innerTypes == NULL) {
			ZVAL_COPY_VALUE(&single, type);
			bool known;
			if (UNEXPECTED(!visit(&single, known))) return false;
			if (!known) return true;
		} else {
			for (zv::ArrayEntry entry : zv::TableRef(innerTypes)) {
				bool known;
				if (UNEXPECTED(!visit(entry.value().deref().raw(), known))) return false;
				if (!known) return true;
			}
		}

		has = true;
		outMin = unboundedMin ? NullableLong::null() : min;
		outMax = unboundedMax ? NullableLong::null() : max;
		return true;
	}

	/* getIntegerBounds() as the private method returns it: the [min, max]
	 * pair or null; UNDEF = pending exception */
	zv::Val getIntegerBoundsValue(zval *type) const
	{
		bool has = false;
		NullableLong min = NullableLong::null();
		NullableLong max = NullableLong::null();
		if (UNEXPECTED(!getIntegerBounds(type, has, min, max))) return zv::Val();
		if (!has) return zv::Val::null();
		zv::Arr pair = zv::Arr::create(2);
		pair.push(min.toVal());
		pair.push(max.toVal());
		return zv::Val(std::move(pair));
	}

	/* shiftLeftOverflows() for the private method's glue; false = pending
	 * exception */
	[[nodiscard]] static bool shiftLeftOverflowsValue(zend_long value, zend_long shift, bool &out)
	{
		zval shiftZv;
		ZVAL_LONG(&shiftZv, shift);
		return shiftLeftOverflows(value, &shiftZv, out);
	}

	/* Mirrors getPlusType(). */
	zv::Val getPlusType(zval *left, zval *right, const pt_ietr_get_type &getTypeCallback) const
	{
		IETR_VAL(leftTypeHold, getTypeOf(getTypeCallback, left));
		IETR_VAL(rightTypeHold, getTypeOf(getTypeCallback, right));
		zval *leftType = leftTypeHold.raw();
		zval *rightType = rightTypeHold.raw();

		if (isA(leftType, pt_ce_never_type) || isA(rightType, pt_ce_never_type)) {
			return getNeverType(leftType, rightType);
		}

		zv::Val result;
		int constant = constantScalarArithmetic(leftType, rightType, IETR_PLUS, result);
		if (UNEXPECTED(constant < 0)) return zv::Val();
		if (constant == 0) return result;

		zv::Val optimizedLeft;
		zv::Val optimizedRight;
		if (constant == 1) {
			optimizedLeft = optimizeScalarType(leftType);
			if (UNEXPECTED(optimizedLeft.isUndef())) return zv::Val();
			leftType = optimizedLeft.raw();
			optimizedRight = optimizeScalarType(rightType);
			if (UNEXPECTED(optimizedRight.isUndef())) return zv::Val();
			rightType = optimizedRight.raw();
		}

		IETR_VAL(leftConstantArrays, callOn(leftType, mGetConstantArrays));
		IETR_VAL(rightConstantArrays, callOn(rightType, mGetConstantArrays));
		if (UNEXPECTED(!requireArray(leftConstantArrays.raw()) || !requireArray(rightConstantArrays.raw()))) return zv::Val();

		zend_long leftCount = zend_hash_num_elements(Z_ARRVAL_P(leftConstantArrays.raw()));
		zend_long rightCount = zend_hash_num_elements(Z_ARRVAL_P(rightConstantArrays.raw()));
		if (leftCount > 0 && rightCount > 0
			&& (leftCount + rightCount < PT_CONSTANT_ARRAY_TYPE_BUILDER_ARRAY_COUNT_LIMIT)) {
			zv::Arr resultTypes = zv::Arr::create((uint32_t) (leftCount * rightCount));
			for (zv::ArrayEntry rightEntry : zv::ArrRef(rightConstantArrays.raw())) {
				zval *rightConstantArray = rightEntry.value().raw();
				for (zv::ArrayEntry leftEntry : zv::ArrRef(leftConstantArrays.raw())) {
					zval *leftConstantArray = leftEntry.value().raw();
					IETR_VAL(newArrayBuilder, pt_constant_array_type_builder_create_from_constant_array(rightConstantArray));
					IETR_VAL(leftKeyTypes, callOn(leftConstantArray, mGetKeyTypes));
					if (UNEXPECTED(!requireArray(leftKeyTypes.raw()))) return zv::Val();
					for (zv::ArrayEntry keyEntry : zv::ArrRef(leftKeyTypes.raw())) {
						zval *leftKeyType = keyEntry.value().raw();
						zval i;
						entryKey(keyEntry, i);
						IETR_VAL(optionalHold, callOn(leftConstantArray, mIsOptionalKey, 1, &i));
						bool optional = zend_is_true(optionalHold.raw());
						IETR_VAL(valueType, callOn(leftConstantArray, mGetOffsetValueType, 1, leftKeyType));
						zv::Val valueTypeHold = std::move(valueType);
						if (!optional) {
							IETR_TRI(rightHas, triOn(rightConstantArray, mHasOffsetValueType, 1, leftKeyType));
							if (rightHas == PT_TRI_MAYBE) {
								IETR_VAL(rightValueType, callOn(rightConstantArray, mGetOffsetValueType, 1, leftKeyType));
								IETR_VAL(merged, union2(valueTypeHold.raw(), rightValueType.raw()));
								valueTypeHold = std::move(merged);
							}
						}
						if (UNEXPECTED(!pt_constant_array_type_builder_set_offset_value_type(newArrayBuilder.raw(), leftKeyType, valueTypeHold.raw(), optional))) return zv::Val();
					}
					IETR_VAL(array, pt_constant_array_type_builder_get_array(newArrayBuilder.raw()));
					resultTypes.push(std::move(array));
				}
			}
			return unionOf(resultTypes);
		}

		// `array{...} + array<TKey, TValue>` keeps the known keyed prefix and
		// folds the right-hand side into an open `...<TKey, TValue>` tail. This
		// only applies when the left arrays' sealedness is known (bleeding edge);
		// otherwise the legacy behavior below is kept for backward compatibility.
		if (leftCount > 0 && rightCount == 0
			&& leftCount < PT_CONSTANT_ARRAY_TYPE_BUILDER_ARRAY_COUNT_LIMIT) {
			IETR_TRI(rightIsArray, triOn(rightType, mIsArray));
			if (rightIsArray == PT_TRI_YES) {
				bool allSealednessKnown = true;
				for (zv::ArrayEntry leftEntry : zv::ArrRef(leftConstantArrays.raw())) {
					IETR_TRI(unsealed, triOn(leftEntry.value().raw(), mIsUnsealed));
					if (unsealed == PT_TRI_MAYBE) {
						allSealednessKnown = false;
						break;
					}
				}

				if (allSealednessKnown) {
					IETR_VAL(rightKeyType, callOn(rightType, mGetIterableKeyType));
					IETR_VAL(rightValueType, callOn(rightType, mGetIterableValueType));
					zv::Arr resultTypes = zv::Arr::create((uint32_t) leftCount);
					for (zv::ArrayEntry leftEntry : zv::ArrRef(leftConstantArrays.raw())) {
						zval *leftConstantArray = leftEntry.value().raw();
						IETR_VAL(newArrayBuilder, pt_constant_array_type_builder_create_from_constant_array(leftConstantArray));
						IETR_VAL(existingUnsealed, callOn(leftConstantArray, mGetUnsealedTypes));
						IETR_TRI(unsealed, triOn(leftConstantArray, mIsUnsealed));
						if (unsealed == PT_TRI_YES && Z_TYPE_P(existingUnsealed.raw()) != IS_NULL) {
							zval *existingKey = arrayIndex(existingUnsealed.raw(), 0);
							if (UNEXPECTED(existingKey == NULL)) return zv::Val();
							IETR_VAL(keyUnion, union2(existingKey, rightKeyType.raw()));
							zval *existingValue = arrayIndex(existingUnsealed.raw(), 1);
							if (UNEXPECTED(existingValue == NULL)) return zv::Val();
							IETR_VAL(valueUnion, union2(existingValue, rightValueType.raw()));
							if (UNEXPECTED(!pt_constant_array_type_builder_make_unsealed(newArrayBuilder.raw(), keyUnion.raw(), valueUnion.raw()))) return zv::Val();
						} else {
							if (UNEXPECTED(!pt_constant_array_type_builder_make_unsealed(newArrayBuilder.raw(), rightKeyType.raw(), rightValueType.raw()))) return zv::Val();
						}
						IETR_VAL(array, pt_constant_array_type_builder_get_array(newArrayBuilder.raw()));
						resultTypes.push(std::move(array));
					}
					return unionOf(resultTypes);
				}
			}
		}

		IETR_TRI(leftIsArray, triOn(leftType, mIsArray));
		IETR_TRI(rightIsArray, triOn(rightType, mIsArray));
		if (leftIsArray == PT_TRI_YES && rightIsArray == PT_TRI_YES) {
			IETR_VAL(leftKeyTypeForEquals, callOn(leftType, mGetIterableKeyType));
			IETR_VAL(rightKeyTypeForEquals, callOn(rightType, mGetIterableKeyType));
			zv::Val keyType;
			{
				zv::Val equalsHold = callOn(leftKeyTypeForEquals.raw(), mEquals, 1, rightKeyTypeForEquals.raw());
				if (UNEXPECTED(equalsHold.isUndef())) return zv::Val();
				if (zend_is_true(equalsHold.raw())) {
					// to preserve BenevolentUnionType
					keyType = callOn(leftType, mGetIterableKeyType);
				} else {
					IETR_VAL(leftKey, callOn(leftType, mGetIterableKeyType));
					IETR_VAL(rightKey, callOn(rightType, mGetIterableKeyType));
					keyType = union2(leftKey.raw(), rightKey.raw());
				}
				if (UNEXPECTED(keyType.isUndef())) return zv::Val();
			}

			IETR_VAL(leftIterableValueType, callOn(leftType, mGetIterableValueType));
			IETR_VAL(rightIterableValueType, callOn(rightType, mGetIterableValueType));
			IETR_VAL(valueUnion, union2(leftIterableValueType.raw(), rightIterableValueType.raw()));
			IETR_VAL(arrayType, newArrayType(keyType.raw(), valueUnion.raw()));

			zv::Arr accessories = zv::Arr::create(4);
			if (leftCount > 0) {
				// Use the first constant array as a reference to list potential offsets.
				// We only need to check the first array because we're looking for offsets that exist in ALL arrays.
				zval *constantArray = arrayIndex(leftConstantArrays.raw(), 0);
				if (UNEXPECTED(constantArray == NULL)) return zv::Val();
				IETR_VAL(keyTypes, callOn(constantArray, mGetKeyTypes));
				if (UNEXPECTED(!requireArray(keyTypes.raw()))) return zv::Val();
				for (zv::ArrayEntry keyEntry : zv::ArrRef(keyTypes.raw())) {
					zval *offsetType = keyEntry.value().raw();
					IETR_TRI(hasOffset, triOn(leftType, mHasOffsetValueType, 1, offsetType));
					if (hasOffset != PT_TRI_YES) {
						continue;
					}

					IETR_VAL(valueType, callOn(leftType, mGetOffsetValueType, 1, offsetType));
					zval accessory;
					if (UNEXPECTED(!pt_has_offset_value_type_new(&accessory, offsetType, valueType.raw()))) return zv::Val();
					accessories.push(zv::Val::adopt(accessory));
				}
			}

			if (rightCount > 0) {
				// Use the first constant array as a reference to list potential offsets.
				// We only need to check the first array because we're looking for offsets that exist in ALL arrays.
				zval *constantArray = arrayIndex(rightConstantArrays.raw(), 0);
				if (UNEXPECTED(constantArray == NULL)) return zv::Val();
				IETR_VAL(keyTypes, callOn(constantArray, mGetKeyTypes));
				if (UNEXPECTED(!requireArray(keyTypes.raw()))) return zv::Val();
				for (zv::ArrayEntry keyEntry : zv::ArrRef(keyTypes.raw())) {
					zval *offsetType = keyEntry.value().raw();
					IETR_TRI(hasOffset, triOn(rightType, mHasOffsetValueType, 1, offsetType));
					if (hasOffset != PT_TRI_YES) {
						continue;
					}

					IETR_VAL(rightOffsetValueType, callOn(rightType, mGetOffsetValueType, 1, offsetType));
					IETR_VAL(valueType, union2(leftIterableValueType.raw(), rightOffsetValueType.raw()));
					zval accessory;
					if (UNEXPECTED(!pt_has_offset_value_type_new(&accessory, offsetType, valueType.raw()))) return zv::Val();
					accessories.push(zv::Val::adopt(accessory));
				}
			}

			IETR_TRI(leftAtLeastOnce, triOn(leftType, mIsIterableAtLeastOnce));
			bool nonEmpty = leftAtLeastOnce == PT_TRI_YES;
			if (!nonEmpty) {
				IETR_TRI(rightAtLeastOnce, triOn(rightType, mIsIterableAtLeastOnce));
				nonEmpty = rightAtLeastOnce == PT_TRI_YES;
			}
			if (nonEmpty) {
				zval accessory;
				if (UNEXPECTED(!pt_non_empty_array_type_new(&accessory))) return zv::Val();
				accessories.push(zv::Val::adopt(accessory));
			}
			IETR_TRI(leftIsList, triOn(leftType, mIsList));
			if (leftIsList == PT_TRI_YES) {
				IETR_TRI(rightIsList, triOn(rightType, mIsList));
				if (rightIsList == PT_TRI_YES) {
					zval accessory;
					if (UNEXPECTED(!pt_accessory_array_list_type_new(&accessory))) return zv::Val();
					accessories.push(zv::Val::adopt(accessory));
				}
			}

			uint32_t accessoryCount = zend_hash_num_elements(accessories.table());
			if (accessoryCount > 0) {
				zv::Arr intersectArgs = zv::Arr::create(accessoryCount + 1);
				intersectArgs.push(std::move(arrayType));
				for (zv::ArrayEntry entry : zv::ArrRef(accessories.raw())) {
					intersectArgs.push(entry.value());
				}
				return pt_type_combinator_intersect(accessoryCount + 1, intersectArgs.table()->arPacked);
			}

			return arrayType;
		}

		bool leftMixed = isA(leftType, pt_ce_mixed_type);
		bool rightMixed = isA(rightType, pt_ce_mixed_type);
		if (leftMixed && rightMixed) {
			IETR_VAL(floatType, newFloatType());
			IETR_VAL(integerType, newIntegerType());
			zv::Arr types = zv::Arr::create(3);
			types.push(std::move(floatType));
			types.push(std::move(integerType));
			if (leftIsArray == PT_TRI_NO && rightIsArray == PT_TRI_NO) {
				return newBenevolentUnionType(types);
			}
			IETR_VAL(keyMixed, newMixedType());
			IETR_VAL(valueMixed, newMixedType());
			IETR_VAL(arrayType, newArrayType(keyMixed.raw(), valueMixed.raw()));
			types.push(std::move(arrayType));
			return newBenevolentUnionType(types);
		}

		if (
			(leftIsArray == PT_TRI_YES && rightIsArray == PT_TRI_NO)
			|| (leftIsArray == PT_TRI_NO && rightIsArray == PT_TRI_YES)
		) {
			return newErrorType();
		}

		if (
			(leftIsArray == PT_TRI_YES && rightIsArray == PT_TRI_MAYBE)
			|| (leftIsArray == PT_TRI_MAYBE && rightIsArray == PT_TRI_YES)
		) {
			IETR_VAL(keyMixed, newMixedType());
			IETR_VAL(valueMixed, newMixedType());
			IETR_VAL(resultType, newArrayType(keyMixed.raw(), valueMixed.raw()));
			IETR_TRI(leftAtLeastOnce, triOn(leftType, mIsIterableAtLeastOnce));
			bool nonEmpty = leftAtLeastOnce == PT_TRI_YES;
			if (!nonEmpty) {
				IETR_TRI(rightAtLeastOnce, triOn(rightType, mIsIterableAtLeastOnce));
				nonEmpty = rightAtLeastOnce == PT_TRI_YES;
			}
			if (nonEmpty) {
				zval accessory;
				if (UNEXPECTED(!pt_non_empty_array_type_new(&accessory))) return zv::Val();
				zv::Val accessoryHold = zv::Val::adopt(accessory);
				zv::Args argv{resultType.raw(), accessoryHold.raw()};
				return pt_type_combinator_intersect(2, argv);
			}

			return resultType;
		}

		if (leftIsArray == PT_TRI_MAYBE && rightIsArray == PT_TRI_MAYBE) {
			zv::Arr plusableTypes = zv::Arr::create(5);
			{
				IETR_VAL(stringType, newStringType());
				plusableTypes.push(std::move(stringType));
				IETR_VAL(floatType, newFloatType());
				plusableTypes.push(std::move(floatType));
				IETR_VAL(integerType, newIntegerType());
				plusableTypes.push(std::move(integerType));
				IETR_VAL(keyMixed, newMixedType());
				IETR_VAL(valueMixed, newMixedType());
				IETR_VAL(arrayType, newArrayType(keyMixed.raw(), valueMixed.raw()));
				plusableTypes.push(std::move(arrayType));
				IETR_VAL(booleanType, newBooleanType());
				plusableTypes.push(std::move(booleanType));
			}
			IETR_VAL(plusable, newUnionType(plusableTypes));

			IETR_TRI(plusableSuperTypeOfLeftTri, triOn(plusable.raw(), mIsSuperTypeOf, 1, leftType));
			IETR_TRI(plusableSuperTypeOfRightTri, triOn(plusable.raw(), mIsSuperTypeOf, 1, rightType));
			bool plusableSuperTypeOfLeft = plusableSuperTypeOfLeftTri == PT_TRI_YES;
			bool plusableSuperTypeOfRight = plusableSuperTypeOfRightTri == PT_TRI_YES;
			if (plusableSuperTypeOfLeft && plusableSuperTypeOfRight) {
				return union2(leftType, rightType);
			}
			if (plusableSuperTypeOfLeft && rightMixed) {
				return zv::Val::copyOf(zv::Ref(leftType));
			}
			if (plusableSuperTypeOfRight && leftMixed) {
				return zv::Val::copyOf(zv::Ref(rightType));
			}
		}

		return resolveCommonMath(IETR_PLUS, left, right, NULL, leftType, rightType);
	}

	/* Mirrors getMinusType() / getMulType() (kind IETR_MINUS / IETR_MUL). */
	zv::Val getMinusOrMulType(IetrBinaryKind kind, zval *left, zval *right, const pt_ietr_get_type &getTypeCallback) const
	{
		IETR_VAL(leftTypeHold, getTypeOf(getTypeCallback, left));
		IETR_VAL(rightTypeHold, getTypeOf(getTypeCallback, right));
		zval *leftType = leftTypeHold.raw();
		zval *rightType = rightTypeHold.raw();

		zv::Val result;
		int constant = constantScalarArithmetic(leftType, rightType, kind, result);
		if (UNEXPECTED(constant < 0)) return zv::Val();
		if (constant == 0) return result;

		zv::Val optimizedLeft;
		zv::Val optimizedRight;
		if (constant == 1) {
			optimizedLeft = optimizeScalarType(leftType);
			if (UNEXPECTED(optimizedLeft.isUndef())) return zv::Val();
			leftType = optimizedLeft.raw();
			optimizedRight = optimizeScalarType(rightType);
			if (UNEXPECTED(optimizedRight.isUndef())) return zv::Val();
			rightType = optimizedRight.raw();
		}

		if (kind == IETR_MUL) {
			{
				IETR_VAL(leftNumberType, callOn(leftType, mToNumber));
				int zero = isConstantIntegerZero(leftNumberType.raw());
				if (UNEXPECTED(zero < 0)) return zv::Val();
				if (zero) {
					IETR_TRI(rightIsFloat, triOn(rightType, mIsFloat));
					if (rightIsFloat == PT_TRI_YES) {
						return newConstantFloatType(0.0);
					}
					return newConstantIntegerType(0);
				}
			}
			{
				IETR_VAL(rightNumberType, callOn(rightType, mToNumber));
				int zero = isConstantIntegerZero(rightNumberType.raw());
				if (UNEXPECTED(zero < 0)) return zv::Val();
				if (zero) {
					IETR_TRI(leftIsFloat, triOn(leftType, mIsFloat));
					if (leftIsFloat == PT_TRI_YES) {
						return newConstantFloatType(0.0);
					}
					return newConstantIntegerType(0);
				}
			}
		}

		return resolveCommonMath(kind, left, right, NULL, leftType, rightType);
	}

	/* Mirrors getPowType(). */
	zv::Val getPowType(zval *left, zval *right, const pt_ietr_get_type &getTypeCallback) const
	{
		IETR_VAL(leftType, getTypeOf(getTypeCallback, left));
		IETR_VAL(rightType, getTypeOf(getTypeCallback, right));

		{
			IETR_VAL(node, newBinaryOpNode(PT_CLASS_POW_EXPR, left, right));
			IETR_VAL(extensionSpecified, callOperatorTypeSpecifyingExtensions(prop(slots::operatorTypeSpecifyingExtensionRegistry), node.raw(), leftType.raw(), rightType.raw()));
			if (Z_TYPE_P(extensionSpecified.raw()) != IS_NULL) return extensionSpecified;
		}

		IETR_VAL(exponentiatedTyped, callOn(leftType.raw(), mExponentiate, 1, rightType.raw()));
		if (!isA(exponentiatedTyped.raw(), pt_ce_error_type)) {
			return exponentiatedTyped;
		}

		return newErrorType();
	}

	/* Mirrors getShiftLeftType() / getShiftRightType(). */
	zv::Val getShiftType(IetrBinaryKind kind, zval *left, zval *right, const pt_ietr_get_type &getTypeCallback) const
	{
		IETR_VAL(leftTypeHold, getTypeOf(getTypeCallback, left));
		IETR_VAL(rightTypeHold, getTypeOf(getTypeCallback, right));
		zval *leftType = leftTypeHold.raw();
		zval *rightType = rightTypeHold.raw();

		{
			IETR_VAL(node, newBinaryOpNode(ietrBinaryKindClasses[kind], left, right));
			IETR_VAL(specifiedTypes, callOperatorTypeSpecifyingExtensions(prop(slots::operatorTypeSpecifyingExtensionRegistry), node.raw(), leftType, rightType));
			if (Z_TYPE_P(specifiedTypes.raw()) != IS_NULL) return specifiedTypes;
		}

		if (isA(leftType, pt_ce_never_type) || isA(rightType, pt_ce_never_type)) {
			return getNeverType(leftType, rightType);
		}

		zv::Val result;
		int constant = constantScalarArithmetic(leftType, rightType, kind, result);
		if (UNEXPECTED(constant < 0)) return zv::Val();
		if (constant == 0) return result;

		zv::Val optimizedLeft;
		zv::Val optimizedRight;
		if (constant == 1) {
			optimizedLeft = optimizeScalarType(leftType);
			if (UNEXPECTED(optimizedLeft.isUndef())) return zv::Val();
			leftType = optimizedLeft.raw();
			optimizedRight = optimizeScalarType(rightType);
			if (UNEXPECTED(optimizedRight.isUndef())) return zv::Val();
			rightType = optimizedRight.raw();
		}

		{
			IETR_VAL(leftNumberType, callOn(leftType, mToNumber));
			IETR_VAL(rightNumberType, callOn(rightType, mToNumber));

			if (isA(leftNumberType.raw(), pt_ce_error_type) || isA(rightNumberType.raw(), pt_ce_error_type)) {
				return newErrorType();
			}
		}

		return resolveCommonMath(kind, left, right, NULL, leftType, rightType);
	}

	/* Mirrors optimizeScalarType(). */
	static zv::Val optimizeScalarType(zval *type)
	{
		zv::Arr types = zv::Arr::create(4);
		{
			IETR_TRI(isInteger, triOn(type, mIsInteger));
			if (isInteger == PT_TRI_YES) {
				IETR_VAL(integerType, newIntegerType());
				types.push(std::move(integerType));
			}
		}
		{
			IETR_TRI(isString, triOn(type, mIsString));
			if (isString == PT_TRI_YES) {
				IETR_VAL(stringType, newStringType());
				types.push(std::move(stringType));
			}
		}
		{
			IETR_TRI(isFloat, triOn(type, mIsFloat));
			if (isFloat == PT_TRI_YES) {
				IETR_VAL(floatType, newFloatType());
				types.push(std::move(floatType));
			}
		}
		{
			IETR_TRI(isNull, triOn(type, mIsNull));
			if (isNull == PT_TRI_YES) {
				IETR_VAL(nullType, newNullType());
				types.push(std::move(nullType));
			}
		}

		uint32_t count = zend_hash_num_elements(types.table());
		if (count == 0) {
			return newErrorType();
		}

		if (count == 1) {
			return zv::Val::copyOf(zv::Ref(zend_hash_index_find(types.table(), 0)));
		}

		return newUnionType(types);
	}

	/* Mirrors getNonNegativeIntegerBounds(): true fills min/max (max null
	 * for no finite upper bound), false = null; -1 = pending exception */
	static int getNonNegativeIntegerBounds(zval *type, zend_long &min, NullableLong &max)
	{
		if (isA(type, pt_ce_integer_range_type)) {
			NullableLong rangeMin, rangeMax;
			if (UNEXPECTED(!pt_integer_range_bounds(Z_OBJ_P(type), rangeMin, rangeMax))) return -1;
			if (!rangeMin.isNull && rangeMin.value >= 0) {
				min = rangeMin.value;
				max = rangeMax;
				return 1;
			}
			return 0;
		}
		if (isA(type, pt_ce_constant_integer_type)) {
			zend_long value;
			if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(type), value))) return -1;
			if (value >= 0) {
				min = value;
				max = NullableLong::of(value);
				return 1;
			}
		}
		return 0;
	}

	/* Mirrors getNonNegativeIntegerBounds() as the PHP value it returns:
	 * [min, max] or null */
	static zv::Val getNonNegativeIntegerBoundsValue(zval *type)
	{
		zend_long min = 0;
		NullableLong max = NullableLong::null();
		int bounds = getNonNegativeIntegerBounds(type, min, max);
		if (UNEXPECTED(bounds < 0)) return zv::Val();
		if (bounds == 0) return zv::Val::null();
		zv::Arr pair = zv::Arr::create(2);
		pair.push(zv::Val::integer(min));
		pair.push(max.toVal());
		return zv::Val(std::move(pair));
	}

	/* Mirrors computeBitwiseAndRange(): a Type, or PHP null */
	static zv::Val computeBitwiseAndRange(zval *leftNumberType, zval *rightNumberType)
	{
		zend_long leftMin = 0, rightMin = 0;
		NullableLong leftMax = NullableLong::null(), rightMax = NullableLong::null();
		int leftBounds = getNonNegativeIntegerBounds(leftNumberType, leftMin, leftMax);
		if (UNEXPECTED(leftBounds < 0)) return zv::Val();
		int rightBounds = getNonNegativeIntegerBounds(rightNumberType, rightMin, rightMax);
		if (UNEXPECTED(rightBounds < 0)) return zv::Val();
		if (leftBounds == 0 && rightBounds == 0) {
			return zv::Val::null();
		}

		bool anyMax = false;
		zend_long minOfMax = 0;
		if (leftBounds == 1 && !leftMax.isNull) {
			minOfMax = leftMax.value;
			anyMax = true;
		}
		if (rightBounds == 1 && !rightMax.isNull) {
			minOfMax = anyMax && minOfMax <= rightMax.value ? minOfMax : rightMax.value;
			anyMax = true;
		}

		return fromInterval(NullableLong::of(0), anyMax ? NullableLong::of(minOfMax) : NullableLong::null());
	}

	/* Mirrors computeBitwiseOrXorRange(): a Type, or PHP null */
	static zv::Val computeBitwiseOrXorRange(zval *leftNumberType, zval *rightNumberType)
	{
		zend_long leftMin = 0, rightMin = 0;
		NullableLong leftMax = NullableLong::null(), rightMax = NullableLong::null();
		int leftBounds = getNonNegativeIntegerBounds(leftNumberType, leftMin, leftMax);
		if (UNEXPECTED(leftBounds < 0)) return zv::Val();
		int rightBounds = getNonNegativeIntegerBounds(rightNumberType, rightMin, rightMax);
		if (UNEXPECTED(rightBounds < 0)) return zv::Val();
		if (leftBounds == 0 || rightBounds == 0) {
			return zv::Val::null();
		}
		if (leftMax.isNull || rightMax.isNull) {
			return fromInterval(NullableLong::of(0), NullableLong::null());
		}

		return fromInterval(NullableLong::of(0), NullableLong::of(allBitsMask(leftMax.value >= rightMax.value ? leftMax.value : rightMax.value)));
	}

	/* Mirrors allBitsMask(). */
	static zend_long allBitsMask(zend_long value)
	{
		value |= value >> 1;
		value |= value >> 2;
		value |= value >> 4;
		value |= value >> 8;
		value |= value >> 16;
		value |= value >> 32;

		return value;
	}

	/* }}} */

	/* {{{ resolveIdenticalType() / resolveEqualType() */

	/* Mirrors resolveIdenticalType(). */
	zv::Val resolveIdenticalType(zval *leftType, zval *rightType) const
	{
		if (isA(leftType, pt_ce_never_type) || isA(rightType, pt_ce_never_type)) {
			return newTypeResult(newConstantBooleanType(false));
		}

		{
			int leftConstantScalar = isAClass(leftType, PT_CLASS_CONSTANT_SCALAR_TYPE);
			if (UNEXPECTED(leftConstantScalar < 0)) return zv::Val();
			if (leftConstantScalar) {
				int rightConstantScalar = isAClass(rightType, PT_CLASS_CONSTANT_SCALAR_TYPE);
				if (UNEXPECTED(rightConstantScalar < 0)) return zv::Val();
				if (rightConstantScalar) {
					IETR_VAL(leftValue, callOn(leftType, mGetValue));
					IETR_VAL(rightValue, callOn(rightType, mGetValue));
					return newTypeResult(newConstantBooleanType(identical(leftValue.raw(), rightValue.raw())));
				}
			}
		}

		{
			IETR_VAL(leftTypeFiniteTypes, callOn(leftType, mGetFiniteTypes));
			IETR_VAL(rightTypeFiniteType, callOn(rightType, mGetFiniteTypes));
			if (UNEXPECTED(!requireCountable(leftTypeFiniteTypes.raw()) || !requireCountable(rightTypeFiniteType.raw()))) return zv::Val();
			if (zend_hash_num_elements(Z_ARRVAL_P(leftTypeFiniteTypes.raw())) == 1 && zend_hash_num_elements(Z_ARRVAL_P(rightTypeFiniteType.raw())) == 1) {
				zval *leftFinite = arrayIndexForRead(leftTypeFiniteTypes.raw(), 0);
				if (UNEXPECTED(leftFinite == NULL)) return zv::Val();
				zval *rightFinite = arrayIndexForRead(rightTypeFiniteType.raw(), 0);
				if (UNEXPECTED(rightFinite == NULL)) return zv::Val();
				IETR_VAL(equals, callOn(leftFinite, mEquals, 1, rightFinite));
				return newTypeResult(newConstantBooleanType(zend_is_true(equals.raw())));
			}
		}

		IETR_VAL(leftIsSuperTypeOfRight, callOn(leftType, mIsSuperTypeOf, 1, rightType));
		IETR_VAL(rightIsSuperTypeOfLeft, callOn(rightType, mIsSuperTypeOf, 1, leftType));
		IETR_TRI(leftNo, trinaryOf(leftIsSuperTypeOfRight.raw()));
		if (leftNo == PT_TRI_NO) {
			IETR_TRI(rightNo, trinaryOf(rightIsSuperTypeOfLeft.raw()));
			if (rightNo == PT_TRI_NO) {
				IETR_VAL(leftReasons, callOn(leftIsSuperTypeOfRight.raw(), mGetReasons));
				IETR_VAL(rightReasons, callOn(rightIsSuperTypeOfLeft.raw(), mGetReasons));
				IETR_VAL(reasons, arrayMerge(leftReasons.raw(), rightReasons.raw()));
				IETR_VAL(falseType, newConstantBooleanType(false));
				return pt_type_result_new(falseType.raw(), reasons.raw());
			}
		}

		if (isA(leftType, pt_ce_constant_array_type) && isA(rightType, pt_ce_constant_array_type)) {
			return resolveConstantArrayTypeComparison(leftType, rightType, true);
		}

		return newTypeResult(newBooleanType());
	}

	/* Mirrors resolveEqualType(). */
	zv::Val resolveEqualType(zval *leftType, zval *rightType) const
	{
		if (isA(leftType, pt_ce_never_type) || isA(rightType, pt_ce_never_type)) {
			return newTypeResult(newConstantBooleanType(false));
		}

		{
			IETR_TRI(leftIsEnum, triOn(leftType, mIsEnum));
			bool identicalComparison = false;
			if (leftIsEnum == PT_TRI_YES) {
				IETR_TRI(rightIsTrue, triOn(rightType, mIsTrue));
				identicalComparison = rightIsTrue == PT_TRI_NO;
			}
			if (!identicalComparison) {
				IETR_TRI(rightIsEnum, triOn(rightType, mIsEnum));
				if (rightIsEnum == PT_TRI_YES) {
					IETR_TRI(leftIsTrue, triOn(leftType, mIsTrue));
					identicalComparison = leftIsTrue == PT_TRI_NO;
				}
			}
			if (identicalComparison) {
				return resolveIdenticalType(leftType, rightType);
			}
		}

		if (isA(leftType, pt_ce_constant_array_type) && isA(rightType, pt_ce_constant_array_type)) {
			return resolveConstantArrayTypeComparison(leftType, rightType, false);
		}

		zv::Args argv{rightType, prop(slots::phpVersion)};
		return newTypeResult(callOn(leftType, mLooseCompare, 2, argv));
	}

	/* Mirrors resolveConstantArrayTypeComparison() with the twin's value
	 * comparison callbacks: resolveIdenticalType() (identical) or
	 * resolveEqualType() */
	zv::Val resolveConstantArrayTypeComparison(zval *leftType, zval *rightType, bool identical, zval *valueComparisonCallback = NULL) const
	{
		IETR_VAL(leftKeyTypes, callOn(leftType, mGetKeyTypes));
		IETR_VAL(rightKeyTypesHold, callOn(rightType, mGetKeyTypes));
		IETR_VAL(leftValueTypes, callOn(leftType, mGetValueTypes));
		IETR_VAL(rightValueTypes, callOn(rightType, mGetValueTypes));
		if (UNEXPECTED(!requireArray(leftKeyTypes.raw()) || !requireArray(rightKeyTypesHold.raw()) || !requireArray(leftValueTypes.raw()) || !requireArray(rightValueTypes.raw()))) return zv::Val();

		IETR_VAL(resultTypeInit, newConstantBooleanType(true));
		zv::Val resultType = std::move(resultTypeInit);

		// the twin's inner foreach unsets every key it visits, always from the
		// front of what is left: the remaining right keys are a suffix
		HashTable *rightKeyTypes = Z_ARRVAL_P(rightKeyTypesHold.raw());
		HashPosition rightPosition = 0;
		zend_hash_internal_pointer_reset_ex(rightKeyTypes, &rightPosition);
		uint32_t rightRemaining = zend_hash_num_elements(rightKeyTypes);
		zval j;
		ZVAL_UNDEF(&j);

		for (zv::ArrayEntry leftEntry : zv::ArrRef(leftKeyTypes.raw())) {
			zval *leftKeyType = leftEntry.value().raw();
			zval i;
			entryKey(leftEntry, i);
			IETR_VAL(leftOptionalHold, callOn(leftType, mIsOptionalKey, 1, &i));
			bool leftOptional = zend_is_true(leftOptionalHold.raw());
			if (leftOptional) {
				IETR_VAL(booleanType, newBooleanType());
				resultType = std::move(booleanType);
			}

			if (rightRemaining == 0) {
				if (!leftOptional) {
					return newTypeResult(newConstantBooleanType(false));
				}
				continue;
			}

			bool found = false;
			while (rightRemaining > 0) {
				zval *rightKeyType = zend_hash_get_current_data_ex(rightKeyTypes, &rightPosition);
				zend_string *stringKey = NULL;
				zend_ulong indexKey = 0;
				int keyKind = zend_hash_get_current_key_ex(rightKeyTypes, &stringKey, &indexKey, &rightPosition);
				if (keyKind == HASH_KEY_IS_STRING) {
					ZVAL_STR(&j, stringKey);
				} else {
					ZVAL_LONG(&j, (zend_long) indexKey);
				}
				zend_hash_move_forward_ex(rightKeyTypes, &rightPosition);
				rightRemaining--;

				IETR_VAL(equals, callOn(leftKeyType, mEquals, 1, rightKeyType));
				if (zend_is_true(equals.raw())) {
					found = true;
					break;
				}
				IETR_VAL(rightOptionalHold, callOn(rightType, mIsOptionalKey, 1, &j));
				if (!zend_is_true(rightOptionalHold.raw())) {
					return newTypeResult(newConstantBooleanType(false));
				}
			}

			if (!found) {
				if (!leftOptional) {
					return newTypeResult(newConstantBooleanType(false));
				}
				continue;
			}

			IETR_VAL(rightOptionalHold, callOn(rightType, mIsOptionalKey, 1, &j));
			if (zend_is_true(rightOptionalHold.raw())) {
				IETR_VAL(booleanType, newBooleanType());
				resultType = std::move(booleanType);
				if (leftOptional) {
					continue;
				}
			}

			zval *leftValueType = arrayOffsetForRead(leftValueTypes.raw(), &i);
			if (UNEXPECTED(leftValueType == NULL && EG(exception) != NULL)) return zv::Val();
			zval *rightValueType = arrayOffsetForRead(rightValueTypes.raw(), &j);
			if (UNEXPECTED(rightValueType == NULL && EG(exception) != NULL)) return zv::Val();
			zval nullValue;
			ZVAL_NULL(&nullValue);
			zval *leftValue = leftValueType != NULL ? leftValueType : &nullValue;
			zval *rightValue = rightValueType != NULL ? rightValueType : &nullValue;
			zv::Val leftIdenticalToRightResult;
			if (valueComparisonCallback != NULL) {
				zv::Args argv{leftValue, rightValue};
				leftIdenticalToRightResult = pt_type_call_callable(valueComparisonCallback, 2, argv);
			} else {
				leftIdenticalToRightResult = identical ? resolveIdenticalType(leftValue, rightValue) : resolveEqualType(leftValue, rightValue);
			}
			if (UNEXPECTED(leftIdenticalToRightResult.isUndef())) return zv::Val();
			IETR_VAL(leftIdenticalToRight, typeResultType(leftIdenticalToRightResult.raw()));
			IETR_TRI(isFalse, triOn(leftIdenticalToRight.raw(), mIsFalse));
			if (isFalse == PT_TRI_YES) {
				return leftIdenticalToRightResult;
			}
			IETR_VAL(merged, union2(resultType.raw(), leftIdenticalToRight.raw()));
			resultType = std::move(merged);
		}

		while (rightRemaining > 0) {
			zend_string *stringKey = NULL;
			zend_ulong indexKey = 0;
			int keyKind = zend_hash_get_current_key_ex(rightKeyTypes, &stringKey, &indexKey, &rightPosition);
			if (keyKind == HASH_KEY_IS_STRING) {
				ZVAL_STR(&j, stringKey);
			} else {
				ZVAL_LONG(&j, (zend_long) indexKey);
			}
			zend_hash_move_forward_ex(rightKeyTypes, &rightPosition);
			rightRemaining--;

			IETR_VAL(rightOptionalHold, callOn(rightType, mIsOptionalKey, 1, &j));
			if (!zend_is_true(rightOptionalHold.raw())) {
				return newTypeResult(newConstantBooleanType(false));
			}
			IETR_VAL(booleanType, newBooleanType());
			resultType = std::move(booleanType);
		}

		return newTypeResult(callOn(resultType.raw(), mToBoolean));
	}

	/* }}} */

	/* {{{ resolveCommonMath() / integerRangeMath() */

	/* Mirrors resolveCommonMath(); the twin's $expr is `new BinaryOp\<kind>($left,
	 * $right)` created here (or the node itself when one is given) */
	zv::Val resolveCommonMath(IetrBinaryKind kind, zval *left, zval *right, zval *givenExpr, zval *leftType, zval *rightType) const
	{
		{
			zv::Val exprHold;
			zval *expr = givenExpr;
			if (expr == NULL) {
				exprHold = newBinaryOpNode(ietrBinaryKindClasses[kind], left, right);
				if (UNEXPECTED(exprHold.isUndef())) return zv::Val();
				expr = exprHold.raw();
			}
			IETR_VAL(specifiedTypes, callOperatorTypeSpecifyingExtensions(prop(slots::operatorTypeSpecifyingExtensionRegistry), expr, leftType, rightType));
			if (Z_TYPE_P(specifiedTypes.raw()) != IS_NULL) return specifiedTypes;
		}

		IETR_VAL(types, union2(leftType, rightType));
		IETR_VAL(leftNumberType, callOn(leftType, mToNumber));
		IETR_VAL(rightNumberType, callOn(rightType, mToNumber));

		bool typesMixed = isA(types.raw(), pt_ce_mixed_type);
		if (
			!typesMixed
			&& (
				isA(rightNumberType.raw(), pt_ce_integer_range_type)
				|| isA(rightNumberType.raw(), pt_ce_constant_integer_type)
				|| isA(rightNumberType.raw(), pt_ce_union_type)
			)
		) {
			if (isA(leftNumberType.raw(), pt_ce_integer_range_type) || isA(leftNumberType.raw(), pt_ce_constant_integer_type)) {
				return integerRangeMath(leftNumberType.raw(), kind, rightNumberType.raw());
			} else if (isA(leftNumberType.raw(), pt_ce_union_type)) {
				IETR_VAL(leftTypes, callOn(leftNumberType.raw(), mGetTypes));
				if (UNEXPECTED(!requireArray(leftTypes.raw()))) return zv::Val();
				zv::Arr unionParts = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(leftTypes.raw())));

				for (zv::ArrayEntry entry : zv::ArrRef(leftTypes.raw())) {
					IETR_VAL(numberType, callOn(entry.value().raw(), mToNumber));
					if (isA(numberType.raw(), pt_ce_integer_range_type) || isA(numberType.raw(), pt_ce_constant_integer_type)) {
						IETR_VAL(part, integerRangeMath(numberType.raw(), kind, rightNumberType.raw()));
						unionParts.push(std::move(part));
					} else {
						unionParts.push(std::move(numberType));
					}
				}

				IETR_VAL(union_, unionOf(unionParts));
				if (isA(leftNumberType.raw(), pt_ce_benevolent_union_type)) {
					IETR_VAL(benevolent, toBenevolentUnion(union_.raw()));
					return callOn(benevolent.raw(), mToNumber);
				}

				return callOn(union_.raw(), mToNumber);
			}
		}

		{
			IETR_TRI(leftIsArray, triOn(leftType, mIsArray));
			if (leftIsArray == PT_TRI_YES) return newErrorType();
			IETR_TRI(rightIsArray, triOn(rightType, mIsArray));
			if (rightIsArray == PT_TRI_YES) return newErrorType();
			IETR_TRI(typesIsArray, triOn(types.raw(), mIsArray));
			if (typesIsArray == PT_TRI_YES) return newErrorType();
		}

		if (isA(leftNumberType.raw(), pt_ce_error_type) || isA(rightNumberType.raw(), pt_ce_error_type)) {
			return newErrorType();
		}
		if (isA(leftNumberType.raw(), pt_ce_never_type) || isA(rightNumberType.raw(), pt_ce_never_type)) {
			return getNeverType(leftNumberType.raw(), rightNumberType.raw());
		}

		{
			IETR_TRI(leftIsFloat, triOn(leftNumberType.raw(), mIsFloat));
			bool isFloat = leftIsFloat == PT_TRI_YES;
			if (!isFloat) {
				IETR_TRI(rightIsFloat, triOn(rightNumberType.raw(), mIsFloat));
				isFloat = rightIsFloat == PT_TRI_YES;
			}
			if (isFloat) {
				if (kind == IETR_SHIFT_LEFT || kind == IETR_SHIFT_RIGHT) {
					return newIntegerType();
				}
				return newFloatType();
			}
		}

		IETR_VAL(resultType, union2(leftNumberType.raw(), rightNumberType.raw()));
		if (kind == IETR_DIV) {
			bool benevolent = typesMixed;
			if (!benevolent) {
				IETR_TRI(resultIsInteger, triOn(resultType.raw(), mIsInteger));
				benevolent = resultIsInteger == PT_TRI_YES;
			}
			return newIntFloatUnion(benevolent);
		}

		if (typesMixed
			|| isA(leftType, pt_ce_benevolent_union_type)
			|| isA(rightType, pt_ce_benevolent_union_type)
		) {
			return toBenevolentUnion(resultType.raw());
		}

		return resultType;
	}

	/* Mirrors integerRangeMath() ($range a ConstantIntegerType or
	 * IntegerRangeType, $node's class as `kind`). */
	zv::Val integerRangeMath(zval *range, IetrBinaryKind kind, zval *operandIn) const
	{
		zval rangeMin, rangeMax;
		if (isA(range, pt_ce_integer_range_type)) {
			NullableLong min, max;
			if (UNEXPECTED(!pt_integer_range_bounds(Z_OBJ_P(range), min, max))) return zv::Val();
			setNullableLong(&rangeMin, min);
			setNullableLong(&rangeMax, max);
		} else {
			IETR_VAL(value, callOn(range, mGetValue));
			if (UNEXPECTED(Z_TYPE_P(value.raw()) != IS_LONG)) {
				// the twin's `$range->getValue()` of a ConstantIntegerType is always an int
				pt_throw_should_not_happen();
				return zv::Val();
			}
			ZVAL_LONG(&rangeMin, Z_LVAL_P(value.raw()));
			ZVAL_COPY_VALUE(&rangeMax, &rangeMin);
		}

		if (isA(operandIn, pt_ce_union_type)) {
			IETR_VAL(operandTypes, callOn(operandIn, mGetTypes));
			if (UNEXPECTED(!requireArray(operandTypes.raw()))) return zv::Val();
			zv::Arr unionParts = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(operandTypes.raw())));

			for (zv::ArrayEntry entry : zv::ArrRef(operandTypes.raw())) {
				zval *type = entry.value().raw();
				IETR_VAL(numberType, callOn(type, mToNumber));
				if (isA(numberType.raw(), pt_ce_integer_range_type) || isA(numberType.raw(), pt_ce_constant_integer_type)) {
					IETR_VAL(part, integerRangeMath(range, kind, numberType.raw()));
					unionParts.push(std::move(part));
				} else {
					IETR_VAL(part, callOn(type, mToNumber));
					unionParts.push(std::move(part));
				}
			}

			IETR_VAL(union_, unionOf(unionParts));
			if (isA(operandIn, pt_ce_benevolent_union_type)) {
				IETR_VAL(benevolent, toBenevolentUnion(union_.raw()));
				return callOn(benevolent.raw(), mToNumber);
			}

			return callOn(union_.raw(), mToNumber);
		}

		IETR_VAL(operand, callOn(operandIn, mToNumber));
		zval operandMin, operandMax;
		bool operandIsConstant;
		if (isA(operand.raw(), pt_ce_integer_range_type)) {
			NullableLong min, max;
			if (UNEXPECTED(!pt_integer_range_bounds(Z_OBJ_P(operand.raw()), min, max))) return zv::Val();
			setNullableLong(&operandMin, min);
			setNullableLong(&operandMax, max);
			operandIsConstant = false;
		} else if (isA(operand.raw(), pt_ce_constant_integer_type)) {
			zend_long value;
			if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(operand.raw()), value))) return zv::Val();
			ZVAL_LONG(&operandMin, value);
			ZVAL_LONG(&operandMax, value);
			operandIsConstant = true;
		} else {
			return operand;
		}

		// $operand->getValue() of a ConstantIntegerType operand
		zval *operandValue = &operandMin;

		// the int|float|null results; floats and the INF sentinels are plain
		// doubles, so every zval here is scalar and needs no release
		zval min, max;
		ZVAL_NULL(&min);
		ZVAL_NULL(&max);

		zval zero;
		ZVAL_LONG(&zero, 0);

		switch (kind) {
			case IETR_PLUS:
				if (operandIsConstant) {
					if (Z_TYPE(rangeMin) != IS_NULL && UNEXPECTED(!scalarOp(add_function, &rangeMin, operandValue, &min))) return zv::Val();
					if (Z_TYPE(rangeMax) != IS_NULL && UNEXPECTED(!scalarOp(add_function, &rangeMax, operandValue, &max))) return zv::Val();
				} else {
					if (Z_TYPE(rangeMin) != IS_NULL && Z_TYPE(operandMin) != IS_NULL && UNEXPECTED(!scalarOp(add_function, &rangeMin, &operandMin, &min))) return zv::Val();
					if (Z_TYPE(rangeMax) != IS_NULL && Z_TYPE(operandMax) != IS_NULL && UNEXPECTED(!scalarOp(add_function, &rangeMax, &operandMax, &max))) return zv::Val();
				}
				break;
			case IETR_MINUS:
				if (operandIsConstant) {
					if (Z_TYPE(rangeMin) != IS_NULL && UNEXPECTED(!scalarOp(sub_function, &rangeMin, operandValue, &min))) return zv::Val();
					if (Z_TYPE(rangeMax) != IS_NULL && UNEXPECTED(!scalarOp(sub_function, &rangeMax, operandValue, &max))) return zv::Val();
				} else {
					if (identical(&rangeMin, &rangeMax) && Z_TYPE(rangeMin) != IS_NULL
						&& (Z_TYPE(operandMin) == IS_NULL || Z_TYPE(operandMax) == IS_NULL)) {
						ZVAL_NULL(&min);
						ZVAL_COPY_VALUE(&max, &rangeMin);
					} else {
						if (Z_TYPE(operandMin) == IS_NULL) {
							ZVAL_NULL(&min);
						} else if (Z_TYPE(rangeMin) != IS_NULL) {
							if (Z_TYPE(operandMax) != IS_NULL) {
								if (UNEXPECTED(!scalarOp(sub_function, &rangeMin, &operandMax, &min))) return zv::Val();
							} else {
								if (UNEXPECTED(!scalarOp(sub_function, &rangeMin, &operandMin, &min))) return zv::Val();
							}
						} else {
							ZVAL_NULL(&min);
						}

						if (Z_TYPE(operandMax) == IS_NULL) {
							ZVAL_NULL(&min);
							ZVAL_NULL(&max);
						} else if (Z_TYPE(rangeMax) != IS_NULL) {
							if (Z_TYPE(rangeMin) != IS_NULL && Z_TYPE(operandMin) == IS_NULL) {
								if (UNEXPECTED(!scalarOp(sub_function, &rangeMin, &operandMax, &min))) return zv::Val();
								ZVAL_NULL(&max);
							} else if (Z_TYPE(operandMin) != IS_NULL) {
								if (UNEXPECTED(!scalarOp(sub_function, &rangeMax, &operandMin, &max))) return zv::Val();
							} else {
								ZVAL_NULL(&max);
							}
						} else {
							ZVAL_NULL(&max);
						}

						if (Z_TYPE(min) != IS_NULL && Z_TYPE(max) != IS_NULL && greaterThan(&min, &max)) {
							zval swap;
							ZVAL_COPY_VALUE(&swap, &min);
							ZVAL_COPY_VALUE(&min, &max);
							ZVAL_COPY_VALUE(&max, &swap);
						}
					}
				}
				break;
			case IETR_MUL: {
				zval minusInf, inf;
				ZVAL_DOUBLE(&minusInf, -std::numeric_limits<double>::infinity());
				ZVAL_DOUBLE(&inf, std::numeric_limits<double>::infinity());
				zval min1, min2, max1, max2;
				if (UNEXPECTED(!mulOrZero(&rangeMin, &operandMin, &minusInf, &minusInf, &min1))) return zv::Val();
				if (UNEXPECTED(!mulOrZero(&rangeMin, &operandMax, &minusInf, &inf, &min2))) return zv::Val();
				if (UNEXPECTED(!mulOrZero(&rangeMax, &operandMin, &inf, &minusInf, &max1))) return zv::Val();
				if (UNEXPECTED(!mulOrZero(&rangeMax, &operandMax, &inf, &inf, &max2))) return zv::Val();

				zv::Args extremes{&min1, &min2, &max1, &max2};
				IETR_VAL(minValue, phpMinMax(false, 4, extremes));
				IETR_VAL(maxValue, phpMinMax(true, 4, extremes));
				ZVAL_COPY_VALUE(&min, minValue.raw());
				ZVAL_COPY_VALUE(&max, maxValue.raw());

				if (!std::isfinite(zval_get_double(&min))) {
					ZVAL_NULL(&min);
				}
				if (!std::isfinite(zval_get_double(&max))) {
					ZVAL_NULL(&max);
				}
				break;
			}
			case IETR_DIV: {
				if (operandIsConstant) {
					if (Z_TYPE(rangeMin) != IS_NULL && Z_LVAL_P(operandValue) != 0) {
						if (UNEXPECTED(!scalarOp(div_function, &rangeMin, operandValue, &min))) return zv::Val();
					}
					if (Z_TYPE(rangeMax) != IS_NULL && Z_LVAL_P(operandValue) != 0) {
						if (UNEXPECTED(!scalarOp(div_function, &rangeMax, operandValue, &max))) return zv::Val();
					}
				} else {
					// Avoid division by zero when looking for the min and the max by using the closest int
					if (identical(&operandMin, &zero)) {
						ZVAL_LONG(&operandMin, 1);
					}
					if (identical(&operandMax, &zero)) {
						ZVAL_LONG(&operandMax, -1);
					}

					if (
						(lessThan(&operandMin, &zero) || Z_TYPE(operandMin) == IS_NULL)
						&& (greaterThan(&operandMax, &zero) || Z_TYPE(operandMax) == IS_NULL)
					) {
						IETR_VAL(negativeOperand, fromIntervalZv(&operandMin, &zero));
						IETR_VAL(positiveOperand, fromIntervalZv(&zero, &operandMax));
						return splitDivision(range, kind, negativeOperand.raw(), positiveOperand.raw(), true);
					}
					if (
						(lessThan(&rangeMin, &zero) || Z_TYPE(rangeMin) == IS_NULL)
						&& (greaterThan(&rangeMax, &zero) || Z_TYPE(rangeMax) == IS_NULL)
					) {
						IETR_VAL(negativeRange, fromIntervalZv(&rangeMin, &zero));
						IETR_VAL(positiveRange, fromIntervalZv(&zero, &rangeMax));
						return splitDivision(operand.raw(), kind, negativeRange.raw(), positiveRange.raw(), false);
					}

					zval minusInf, inf;
					ZVAL_DOUBLE(&minusInf, -std::numeric_limits<double>::infinity());
					ZVAL_DOUBLE(&inf, std::numeric_limits<double>::infinity());
					zval *rangeMinOrInf = Z_TYPE(rangeMin) != IS_NULL ? &rangeMin : &minusInf;
					zval *rangeMaxOrInf = Z_TYPE(rangeMax) != IS_NULL ? &rangeMax : &inf;
					zval rangeMinSign, rangeMaxSign;
					ZVAL_LONG(&rangeMinSign, zend_compare(rangeMinOrInf, &zero));
					ZVAL_LONG(&rangeMaxSign, zend_compare(rangeMaxOrInf, &zero));

					zval negativeTenth, positiveTenth;
					ZVAL_DOUBLE(&negativeTenth, -0.1);
					ZVAL_DOUBLE(&positiveTenth, 0.1);
					zval min1, min2, max1, max2;
					if (UNEXPECTED(!scalarOp(Z_TYPE(operandMin) != IS_NULL ? div_function : mul_function, Z_TYPE(operandMin) != IS_NULL ? rangeMinOrInf : &rangeMinSign, Z_TYPE(operandMin) != IS_NULL ? &operandMin : &negativeTenth, &min1))) return zv::Val();
					if (UNEXPECTED(!scalarOp(Z_TYPE(operandMax) != IS_NULL ? div_function : mul_function, Z_TYPE(operandMax) != IS_NULL ? rangeMinOrInf : &rangeMinSign, Z_TYPE(operandMax) != IS_NULL ? &operandMax : &positiveTenth, &min2))) return zv::Val();
					if (UNEXPECTED(!scalarOp(Z_TYPE(operandMin) != IS_NULL ? div_function : mul_function, Z_TYPE(operandMin) != IS_NULL ? rangeMaxOrInf : &rangeMaxSign, Z_TYPE(operandMin) != IS_NULL ? &operandMin : &negativeTenth, &max1))) return zv::Val();
					if (UNEXPECTED(!scalarOp(Z_TYPE(operandMax) != IS_NULL ? div_function : mul_function, Z_TYPE(operandMax) != IS_NULL ? rangeMaxOrInf : &rangeMaxSign, Z_TYPE(operandMax) != IS_NULL ? &operandMax : &positiveTenth, &max2))) return zv::Val();

					zv::Args extremes{&min1, &min2, &max1, &max2};
					IETR_VAL(minValue, phpMinMax(false, 4, extremes));
					IETR_VAL(maxValue, phpMinMax(true, 4, extremes));
					ZVAL_COPY_VALUE(&min, minValue.raw());
					ZVAL_COPY_VALUE(&max, maxValue.raw());

					if (Z_TYPE(min) == IS_DOUBLE && Z_DVAL(min) == -std::numeric_limits<double>::infinity()) {
						ZVAL_NULL(&min);
					}
					if (Z_TYPE(max) == IS_DOUBLE && Z_DVAL(max) == std::numeric_limits<double>::infinity()) {
						ZVAL_NULL(&max);
					}
				}

				if (Z_TYPE(min) != IS_NULL && Z_TYPE(max) != IS_NULL && greaterThan(&min, &max)) {
					zval swap;
					ZVAL_COPY_VALUE(&swap, &min);
					ZVAL_COPY_VALUE(&min, &max);
					ZVAL_COPY_VALUE(&max, &swap);
				}

				if (Z_TYPE(min) == IS_DOUBLE) {
					toIntBound(std::ceil(Z_DVAL(min)), &min);
				}
				if (Z_TYPE(max) == IS_DOUBLE) {
					toIntBound(std::floor(Z_DVAL(max)), &max);
				}

				// invert maximas on division with negative constants
				bool rangeNegativeConstant = false;
				if (isA(range, pt_ce_constant_integer_type)) {
					zend_long value;
					if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(range), value))) return zv::Val();
					rangeNegativeConstant = value < 0;
				}
				if ((rangeNegativeConstant || (operandIsConstant && Z_LVAL_P(operandValue) < 0))
					&& (Z_TYPE(min) == IS_NULL || Z_TYPE(max) == IS_NULL)) {
					zval swap;
					ZVAL_COPY_VALUE(&swap, &min);
					ZVAL_COPY_VALUE(&min, &max);
					ZVAL_COPY_VALUE(&max, &swap);
				}

				if (Z_TYPE(min) == IS_NULL && Z_TYPE(max) == IS_NULL) {
					return newIntFloatUnion(true);
				}

				IETR_VAL(rangeType, fromIntervalZv(&min, &max));
				IETR_VAL(floatType, newFloatType());
				return union2(rangeType.raw(), floatType.raw());
			}
			case IETR_SHIFT_LEFT:
			case IETR_SHIFT_RIGHT: {
				if (!operandIsConstant) {
					return newIntegerType();
				}
				if (Z_LVAL_P(operandValue) < 0) {
					return newErrorType();
				}
				if (kind == IETR_SHIFT_LEFT) {
					// an overflowing shift wraps around, which breaks the monotonicity the bounds rely on
					bool minOverflows = false;
					bool maxOverflows = false;
					if (Z_TYPE(rangeMin) != IS_NULL && UNEXPECTED(!shiftLeftOverflows(zval_get_long(&rangeMin), operandValue, minOverflows))) return zv::Val();
					if (!minOverflows && Z_TYPE(rangeMax) != IS_NULL && UNEXPECTED(!shiftLeftOverflows(zval_get_long(&rangeMax), operandValue, maxOverflows))) return zv::Val();
					if (minOverflows || maxOverflows) {
						return newIntegerType();
					}
				}
				pt_ietr_binary_op shift = kind == IETR_SHIFT_LEFT ? shift_left_function : shift_right_function;
				if (Z_TYPE(rangeMin) != IS_NULL) {
					zval rangeMinInteger;
					ZVAL_LONG(&rangeMinInteger, zval_get_long(&rangeMin));
					if (UNEXPECTED(!scalarOp(shift, &rangeMinInteger, operandValue, &min))) return zv::Val();
				}
				if (Z_TYPE(rangeMax) != IS_NULL) {
					zval rangeMaxInteger;
					ZVAL_LONG(&rangeMaxInteger, zval_get_long(&rangeMax));
					if (UNEXPECTED(!scalarOp(shift, &rangeMaxInteger, operandValue, &max))) return zv::Val();
				}
				break;
			}
			default:
				pt_throw_should_not_happen();
				return zv::Val();
		}

		if (Z_TYPE(min) == IS_DOUBLE) {
			ZVAL_NULL(&min);
		}
		if (Z_TYPE(max) == IS_DOUBLE) {
			ZVAL_NULL(&max);
		}

		return fromIntervalZv(&min, &max);
	}

	/* the Div arm's split around zero: TypeCombinator::union(
	 * integerRangeMath(fixed, negative), integerRangeMath(fixed, positive)
	 * )->toNumber() and the int|float -> benevolent rewrite; the split side
	 * is the operand (operandSplit) or the range */
	zv::Val splitDivision(zval *fixed, IetrBinaryKind kind, zval *negative, zval *positive, bool operandSplit) const
	{
		IETR_VAL(negativeResult, operandSplit ? integerRangeMath(fixed, kind, negative) : integerRangeMath(negative, kind, fixed));
		IETR_VAL(positiveResult, operandSplit ? integerRangeMath(fixed, kind, positive) : integerRangeMath(positive, kind, fixed));
		IETR_VAL(union_, union2(negativeResult.raw(), positiveResult.raw()));
		IETR_VAL(result, callOn(union_.raw(), mToNumber));

		IETR_VAL(intFloat, newIntFloatUnion(false));
		IETR_VAL(equals, callOn(result.raw(), mEquals, 1, intFloat.raw()));
		if (zend_is_true(equals.raw())) {
			return newIntFloatUnion(true);
		}

		return result;
	}

	/* }}} */

	/* {{{ the unary operators */

	/* Mirrors getUnaryPlusType(). */
	zv::Val getUnaryPlusType(zval *expr, const pt_ietr_get_type &getTypeCallback) const
	{
		IETR_VAL(type, getTypeOf(getTypeCallback, expr));

		IETR_VAL(specifiedTypes, callUnaryOperatorTypeSpecifyingExtensions(prop(slots::unaryOperatorTypeSpecifyingExtensionRegistry), "+", type.raw()));
		if (Z_TYPE_P(specifiedTypes.raw()) != IS_NULL) {
			return specifiedTypes;
		}

		return callOn(type.raw(), mToNumber);
	}

	/* Mirrors getUnaryMinusType(). */
	zv::Val getUnaryMinusType(zval *expr, const pt_ietr_get_type &getTypeCallback) const
	{
		IETR_VAL(type, getTypeOf(getTypeCallback, expr));

		IETR_VAL(specifiedTypes, callUnaryOperatorTypeSpecifyingExtensions(prop(slots::unaryOperatorTypeSpecifyingExtensionRegistry), "-", type.raw()));
		if (Z_TYPE_P(specifiedTypes.raw()) != IS_NULL) {
			return specifiedTypes;
		}

		IETR_VAL(negated, getUnaryMinusTypeFromType(expr, type.raw()));
		if (isA(negated.raw(), pt_ce_integer_range_type)) {
			zval minusOne;
			ZVAL_LONG(&minusOne, -1);
			IETR_VAL(minusOneNode, pt_type_new(PT_CLASS_SCALAR_INT, 1, &minusOne));
			IETR_VAL(mulNode, newBinaryOpNode(PT_CLASS_MUL_EXPR, expr, minusOneNode.raw()));
			return getTypeOf(getTypeCallback, mulNode.raw());
		}

		return negated;
	}

	/* Mirrors getUnaryMinusTypeFromType(). */
	static zv::Val getUnaryMinusTypeFromType(zval *expr, zval *typeIn)
	{
		(void) expr;
		IETR_VAL(type, callOn(typeIn, mToNumber));
		IETR_VAL(scalarValues, callOn(type.raw(), mGetConstantScalarValues));
		if (UNEXPECTED(!requireCountable(scalarValues.raw()))) return zv::Val();

		uint32_t count = zend_hash_num_elements(Z_ARRVAL_P(scalarValues.raw()));
		if (count > 0) {
			zv::Arr newTypes = zv::Arr::create(count);
			for (zv::ArrayEntry entry : zv::ArrRef(scalarValues.raw())) {
				zval *scalarValue = entry.value().raw();
				ZVAL_DEREF(scalarValue);
				if (Z_TYPE_P(scalarValue) == IS_LONG) {
					zval minusOne, newValue;
					ZVAL_LONG(&minusOne, -1);
					if (UNEXPECTED(!scalarOp(mul_function, scalarValue, &minusOne, &newValue))) return zv::Val();
					if (Z_TYPE(newValue) != IS_LONG) {
						// Negating the smallest integer overflows into a float.
						IETR_VAL(floatType, newConstantFloatType(zval_get_double(&newValue)));
						newTypes.push(std::move(floatType));
						continue;
					}
					IETR_VAL(integerType, newConstantIntegerType(Z_LVAL(newValue)));
					newTypes.push(std::move(integerType));
				} else if (Z_TYPE_P(scalarValue) == IS_DOUBLE) {
					zval minusOne, newValue;
					ZVAL_LONG(&minusOne, -1);
					if (UNEXPECTED(!scalarOp(mul_function, scalarValue, &minusOne, &newValue))) return zv::Val();
					IETR_VAL(floatType, newConstantFloatType(Z_DVAL(newValue)));
					newTypes.push(std::move(floatType));
				}
			}

			return unionOf(newTypes);
		}

		return type;
	}

	/* Mirrors getBitwiseNotType(). */
	zv::Val getBitwiseNotType(zval *expr, const pt_ietr_get_type &getTypeCallback) const
	{
		IETR_VAL(exprType, getTypeOf(getTypeCallback, expr));

		IETR_VAL(specifiedTypes, callUnaryOperatorTypeSpecifyingExtensions(prop(slots::unaryOperatorTypeSpecifyingExtensionRegistry), "~", exprType.raw()));
		if (Z_TYPE_P(specifiedTypes.raw()) != IS_NULL) {
			return specifiedTypes;
		}

		return getBitwiseNotTypeFromType(exprType.raw());
	}

	/* Mirrors getBitwiseNotTypeFromType(). */
	static zv::Val getBitwiseNotTypeFromType(zval *exprType)
	{
		return callOn(exprType, mToBitwiseNotType);
	}

	/* }}} */

	/* {{{ small private helpers of the twin */

	/* Mirrors getNeverType(). */
	static zv::Val getNeverType(zval *leftType, zval *rightType)
	{
		// make sure we don't lose the explicit flag in the process
		if (isA(leftType, pt_ce_never_type)) {
			bool isExplicit;
			if (UNEXPECTED(!pt_never_type_is_explicit(Z_OBJ_P(leftType), isExplicit))) return zv::Val();
			if (isExplicit) return zv::Val::copyOf(zv::Ref(leftType));
		}
		if (isA(rightType, pt_ce_never_type)) {
			bool isExplicit;
			if (UNEXPECTED(!pt_never_type_is_explicit(Z_OBJ_P(rightType), isExplicit))) return zv::Val();
			if (isExplicit) return zv::Val::copyOf(zv::Ref(rightType));
		}
		return newNeverType();
	}

	/* Mirrors getTypeFromValue(). */
	static zv::Val getTypeFromValue(zval *value)
	{
		return typeFromValue(value);
	}

	/* }}} */

	/* {{{ getType() */

	/* Mirrors getType(); $expr an Expr object */
	zv::Val getType(zval *expr, zval *context) const
	{
		uint64_t mask;
		if (UNEXPECTED(!getTypeArmsOf(Z_OBJCE_P(expr), mask))) return zv::Val();
		GetTypeFrame frame{self, context};
		pt_ietr_get_type callback{&getTypeCallbackBody, &frame, &getTypeCallbackCallable};

		for (unsigned arm = 0; mask != 0; arm++, mask >>= 1) {
			if ((mask & 1) == 0) continue;
			switch (arm) {
				case ARM_TYPE_EXPR:
					return callOn(expr, mGetExprType);
				case ARM_INT: {
					zval *value = nodeProp(pt_ietr_scalar_int_value, expr);
					if (UNEXPECTED(value == NULL)) return zv::Val();
					if (EXPECTED(Z_TYPE_P(value) == IS_LONG)) return newConstantIntegerType(Z_LVAL_P(value));
					return pt_type_new_ce(pt_ce_constant_integer_type, 1, value);
				}
				case ARM_FLOAT: {
					zval *value = nodeProp(pt_ietr_scalar_float_value, expr);
					if (UNEXPECTED(value == NULL)) return zv::Val();
					if (EXPECTED(Z_TYPE_P(value) == IS_DOUBLE)) return newConstantFloatType(Z_DVAL_P(value));
					return pt_type_new_ce(pt_ce_constant_float_type, 1, value);
				}
				case ARM_STRING: {
					zval *value = nodeProp(pt_ietr_scalar_string_value, expr);
					if (UNEXPECTED(value == NULL)) return zv::Val();
					if (EXPECTED(Z_TYPE_P(value) == IS_STRING)) return newConstantStringType(Z_STR_P(value));
					return pt_type_new_ce(pt_ce_constant_string_type, 1, value);
				}
				case ARM_CONST_FETCH: {
					zval *name = nodeProp(pt_ietr_const_fetch_name, expr);
					if (UNEXPECTED(name == NULL)) return zv::Val();
					zend_string *constName = nameString(name);
					if (UNEXPECTED(constName == NULL)) return zv::Val();
					if (zend_string_equals_literal_ci(constName, "true")) {
						return newConstantBooleanType(true);
					} else if (zend_string_equals_literal_ci(constName, "false")) {
						return newConstantBooleanType(false);
					} else if (zend_string_equals_literal_ci(constName, "null")) {
						return newNullType();
					}

					IETR_VAL(constant, resolveConstant(prop(slots::constantResolver), name, context));
					if (Z_TYPE_P(constant.raw()) != IS_NULL) {
						return constant;
					}

					return newErrorType();
				}
				case ARM_FILE:
				case ARM_DIR: {
					IETR_VAL(file, contextGetFile(context));
					if (Z_TYPE_P(file.raw()) == IS_NULL) {
						return newStringType();
					}
					if (UNEXPECTED(Z_TYPE_P(file.raw()) != IS_STRING)) {
						pt_throw_should_not_happen();
						return zv::Val();
					}
					zv::Val stringType;
					if (arm == ARM_FILE) {
						stringType = newConstantStringType(Z_STR_P(file.raw()));
					} else {
						IETR_VAL(directory, phpDirname(file.raw()));
						stringType = constantStringOf(directory.raw(), false);
					}
					if (UNEXPECTED(stringType.isUndef())) return zv::Val();
					if (zend_is_true(prop(slots::usePathConstantsAsConstantString))) {
						return stringType;
					}
					IETR_VAL(precision, generalizePrecisionMoreSpecific());
					return callOn(stringType.raw(), mGeneralize, 1, precision.raw());
				}
				case ARM_LINE: {
					IETR_VAL(line, callOn(expr, mGetStartLine));
					if (EXPECTED(Z_TYPE_P(line.raw()) == IS_LONG)) return newConstantIntegerType(Z_LVAL_P(line.raw()));
					return pt_type_new_ce(pt_ce_constant_integer_type, 1, line.raw());
				}
				case ARM_NEW: {
					zval *class_ = nodeProp(pt_ietr_new_class, expr);
					if (UNEXPECTED(class_ == NULL)) return zv::Val();
					int isName = isAClass(class_, PT_CLASS_NAME);
					if (UNEXPECTED(isName < 0)) return zv::Val();
					if (isName) {
						zend_string *className = nameString(class_);
						if (UNEXPECTED(className == NULL)) return zv::Val();
						return newObjectType(className);
					}

					zval objectType;
					return adopted(pt_object_without_class_type_new(&objectType), objectType);
				}
				case ARM_ARRAY:
					return getArrayType(expr, callback);
				case ARM_CAST:
					return getCastType(expr, callback);
				case ARM_CALL_LIKE: {
					bool firstClassCallable;
					if (UNEXPECTED(!pt_call_like_is_first_class_callable(Z_OBJ_P(expr), firstClassCallable))) return zv::Val();
					if (firstClassCallable) {
						return getFirstClassCallableType(expr, context, false);
					}
					break;
				}
				case ARM_CLOSURE: {
					zval *isStatic = nodeProp(pt_ietr_closure_static, expr);
					if (UNEXPECTED(isStatic == NULL)) return zv::Val();
					if (zend_is_true(isStatic)) {
						return getStaticClosureType(expr, context);
					}
					break;
				}
				case ARM_ARRAY_DIM_FETCH: {
					zval *dim = nodeProp(pt_ietr_array_dim_fetch_dim, expr);
					if (UNEXPECTED(dim == NULL)) return zv::Val();
					if (Z_TYPE_P(dim) != IS_NULL) {
						zval *var = nodeProp(pt_ietr_array_dim_fetch_var, expr);
						if (UNEXPECTED(var == NULL)) return zv::Val();
						IETR_VAL(varType, getTypeRecursive(var, context));
						IETR_VAL(dimType, getTypeRecursive(dim, context));
						return callOn(varType.raw(), mGetOffsetValueType, 1, dimType.raw());
					}
					break;
				}
				case ARM_CLASS_CONST_FETCH: {
					zval *name = nodeProp(pt_ietr_class_const_fetch_name, expr);
					if (UNEXPECTED(name == NULL)) return zv::Val();
					int isIdentifier = isAClass(name, PT_CLASS_IDENTIFIER);
					if (UNEXPECTED(isIdentifier < 0)) return zv::Val();
					if (isIdentifier) {
						zval *class_ = nodeProp(pt_ietr_class_const_fetch_class, expr);
						if (UNEXPECTED(class_ == NULL)) return zv::Val();
						zend_string *constantName = identifierString(name);
						if (UNEXPECTED(constantName == NULL)) return zv::Val();
						zval constantNameZv;
						ZVAL_STR(&constantNameZv, constantName);
						IETR_VAL(className, contextGetClassName(context));
						return getClassConstFetchType(class_, &constantNameZv, className.raw(), callback);
					}
					break;
				}
				case ARM_UNARY_PLUS: {
					zval *operand = nodeProp(pt_ietr_unary_plus_expr, expr);
					if (UNEXPECTED(operand == NULL)) return zv::Val();
					return getUnaryPlusType(operand, callback);
				}
				case ARM_UNARY_MINUS: {
					zval *operand = nodeProp(pt_ietr_unary_minus_expr, expr);
					if (UNEXPECTED(operand == NULL)) return zv::Val();
					return getUnaryMinusType(operand, callback);
				}
				case ARM_COALESCE: {
					IETR_VAL(leftType, getTypeRecursive(binaryLeft(expr), context));
					IETR_VAL(rightType, getTypeRecursive(binaryRight(expr), context));
					IETR_VAL(leftWithoutNull, pt_type_combinator_remove_null(leftType.raw()));
					return union2(leftWithoutNull.raw(), rightType.raw());
				}
				case ARM_TERNARY: {
					zval *else_ = nodeProp(pt_ietr_ternary_else, expr);
					if (UNEXPECTED(else_ == NULL)) return zv::Val();
					IETR_VAL(elseType, getTypeRecursive(else_, context));
					zval *if_ = nodeProp(pt_ietr_ternary_if, expr);
					if (UNEXPECTED(if_ == NULL)) return zv::Val();
					zval *truthySide = if_;
					if (Z_TYPE_P(if_) == IS_NULL) {
						truthySide = nodeProp(pt_ietr_ternary_cond, expr);
						if (UNEXPECTED(truthySide == NULL)) return zv::Val();
					}
					IETR_VAL(truthySideType, getTypeRecursive(truthySide, context));
					IETR_VAL(withoutFalsey, pt_type_combinator_call(PT_LC("removefalsey"), 1, truthySideType.raw()));
					return union2(withoutFalsey.raw(), elseType.raw());
				}
				case ARM_FUNC_CALL: {
					zval *name = nodeProp(pt_ietr_func_call_name, expr);
					if (UNEXPECTED(name == NULL)) return zv::Val();
					int isName = isAClass(name, PT_CLASS_NAME);
					if (UNEXPECTED(isName < 0)) return zv::Val();
					if (!isName) break;
					zend_string *functionName = nameString(name);
					if (UNEXPECTED(functionName == NULL)) return zv::Val();
					if (!zend_string_equals_literal_ci(functionName, "constant")) break;

					zval *args = nodeProp(pt_ietr_func_call_args, expr);
					if (UNEXPECTED(args == NULL)) return zv::Val();
					zval *firstArg = Z_TYPE_P(args) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(args), 0) : NULL;
					if (firstArg == NULL) break;
					ZVAL_DEREF(firstArg);
					int isArg = isAClass(firstArg, PT_CLASS_ARG);
					if (UNEXPECTED(isArg < 0)) return zv::Val();
					if (!isArg) break;
					zval *argValue = nodeProp(pt_ietr_arg_value, firstArg);
					if (UNEXPECTED(argValue == NULL)) return zv::Val();
					int isString = isAClass(argValue, PT_CLASS_SCALAR_STRING);
					if (UNEXPECTED(isString < 0)) return zv::Val();
					if (!isString) break;
					zval *constantName = nodeProp(pt_ietr_scalar_string_value, argValue);
					if (UNEXPECTED(constantName == NULL)) return zv::Val();
					IETR_VAL(constant, resolvePredefinedConstant(prop(slots::constantResolver), constantName));
					if (Z_TYPE_P(constant.raw()) != IS_NULL) {
						return constant;
					}
					break;
				}
				case ARM_BOOLEAN_NOT: {
					zval *operand = nodeProp(pt_ietr_boolean_not_expr, expr);
					if (UNEXPECTED(operand == NULL)) return zv::Val();
					IETR_VAL(operandType, getTypeRecursive(operand, context));
					IETR_VAL(exprBooleanType, callOn(operandType.raw(), mToBoolean));
					if (isA(exprBooleanType.raw(), pt_ce_constant_boolean_type)) {
						bool value;
						if (UNEXPECTED(!constantBooleanValue(exprBooleanType.raw(), value))) return zv::Val();
						return newConstantBooleanType(!value);
					}

					return newBooleanType();
				}
				case ARM_BITWISE_NOT: {
					zval *operand = nodeProp(pt_ietr_bitwise_not_expr, expr);
					if (UNEXPECTED(operand == NULL)) return zv::Val();
					return getBitwiseNotType(operand, callback);
				}
				case ARM_CONCAT:
					return getConcatType(binaryLeft(expr), binaryRight(expr), callback);
				case ARM_BITWISE_AND:
					return getBitwiseType(IETR_BITWISE_AND, binaryLeft(expr), binaryRight(expr), callback);
				case ARM_BITWISE_OR:
					return getBitwiseType(IETR_BITWISE_OR, binaryLeft(expr), binaryRight(expr), callback);
				case ARM_BITWISE_XOR:
					return getBitwiseType(IETR_BITWISE_XOR, binaryLeft(expr), binaryRight(expr), callback);
				case ARM_SPACESHIP:
					return getSpaceshipType(binaryLeft(expr), binaryRight(expr), callback);
				case ARM_BOOLEAN_AND:
				case ARM_LOGICAL_AND:
				case ARM_BOOLEAN_OR:
				case ARM_LOGICAL_OR:
					return newBooleanType();
				case ARM_DIV:
					return getDivType(binaryLeft(expr), binaryRight(expr), callback);
				case ARM_MOD:
					return getModType(binaryLeft(expr), binaryRight(expr), callback);
				case ARM_PLUS:
					return getPlusType(binaryLeft(expr), binaryRight(expr), callback);
				case ARM_MINUS:
					return getMinusOrMulType(IETR_MINUS, binaryLeft(expr), binaryRight(expr), callback);
				case ARM_MUL:
					return getMinusOrMulType(IETR_MUL, binaryLeft(expr), binaryRight(expr), callback);
				case ARM_POW:
					return getPowType(binaryLeft(expr), binaryRight(expr), callback);
				case ARM_SHIFT_LEFT:
					return getShiftType(IETR_SHIFT_LEFT, binaryLeft(expr), binaryRight(expr), callback);
				case ARM_SHIFT_RIGHT:
					return getShiftType(IETR_SHIFT_RIGHT, binaryLeft(expr), binaryRight(expr), callback);
				case ARM_IDENTICAL:
				case ARM_EQUAL: {
					IETR_VAL(leftType, getTypeRecursive(binaryLeft(expr), context));
					IETR_VAL(rightType, getTypeRecursive(binaryRight(expr), context));
					IETR_VAL(result, arm == ARM_IDENTICAL ? resolveIdenticalType(leftType.raw(), rightType.raw()) : resolveEqualType(leftType.raw(), rightType.raw()));
					return typeResultType(result.raw());
				}
				case ARM_NOT_IDENTICAL:
				case ARM_NOT_EQUAL: {
					zval *left = binaryLeft(expr);
					if (UNEXPECTED(left == NULL)) return zv::Val();
					zval *right = binaryRight(expr);
					if (UNEXPECTED(right == NULL)) return zv::Val();
					IETR_VAL(comparison, newBinaryOpNode(arm == ARM_NOT_IDENTICAL ? PT_CLASS_IDENTICAL_EXPR : PT_CLASS_EQUAL_EXPR, left, right));
					IETR_VAL(negation, pt_type_new(PT_CLASS_BOOLEAN_NOT_EXPR, 1, comparison.raw()));
					return getTypeRecursive(negation.raw(), context);
				}
				case ARM_SMALLER:
				case ARM_SMALLER_OR_EQUAL:
				case ARM_GREATER:
				case ARM_GREATER_OR_EQUAL: {
					bool greater = arm == ARM_GREATER || arm == ARM_GREATER_OR_EQUAL;
					zval *smaller = greater ? binaryRight(expr) : binaryLeft(expr);
					IETR_VAL(smallerType, getTypeRecursive(smaller, context));
					zval *larger = greater ? binaryLeft(expr) : binaryRight(expr);
					IETR_VAL(largerType, getTypeRecursive(larger, context));
					zv::Args argv{largerType.raw(), prop(slots::phpVersion)};
					IETR_VAL(trinary, callOn(smallerType.raw(), arm == ARM_SMALLER || arm == ARM_GREATER ? mIsSmallerThan : mIsSmallerThanOrEqual, 2, argv));
					return callOn(trinary.raw(), mToBooleanType);
				}
				case ARM_LOGICAL_XOR: {
					IETR_VAL(leftType, getTypeRecursive(binaryLeft(expr), context));
					IETR_VAL(leftBooleanType, callOn(leftType.raw(), mToBoolean));
					IETR_VAL(rightType, getTypeRecursive(binaryRight(expr), context));
					IETR_VAL(rightBooleanType, callOn(rightType.raw(), mToBoolean));

					if (
						isA(leftBooleanType.raw(), pt_ce_constant_boolean_type)
						&& isA(rightBooleanType.raw(), pt_ce_constant_boolean_type)
					) {
						bool leftValue, rightValue;
						if (UNEXPECTED(!constantBooleanValue(leftBooleanType.raw(), leftValue) || !constantBooleanValue(rightBooleanType.raw(), rightValue))) return zv::Val();
						return newConstantBooleanType(leftValue != rightValue);
					}

					return newBooleanType();
				}
				case ARM_MAGIC_CLASS: {
					IETR_VAL(traitName, contextGetTraitName(context));
					if (Z_TYPE_P(traitName.raw()) != IS_NULL) {
						zv::Arr types = zv::Arr::create(2);
						IETR_VAL(classStringType, newClassStringType());
						types.push(std::move(classStringType));
						zval literal;
						if (UNEXPECTED(!pt_accessory_literal_string_type_new(&literal))) return zv::Val();
						types.push(zv::Val::adopt(literal));
						return newIntersectionType(types);
					}

					IETR_VAL(className, contextGetClassName(context));
					if (Z_TYPE_P(className.raw()) == IS_NULL) {
						return newConstantStringType(ZSTR_EMPTY_ALLOC());
					}

					IETR_VAL(classNameAgain, contextGetClassName(context));
					return constantStringOf(classNameAgain.raw(), true);
				}
				case ARM_MAGIC_NAMESPACE: {
					IETR_VAL(traitName, contextGetTraitName(context));
					if (Z_TYPE_P(traitName.raw()) != IS_NULL) {
						zv::Arr types = zv::Arr::create(2);
						IETR_VAL(stringType, newStringType());
						types.push(std::move(stringType));
						zval literal;
						if (UNEXPECTED(!pt_accessory_literal_string_type_new(&literal))) return zv::Val();
						types.push(zv::Val::adopt(literal));
						return newIntersectionType(types);
					}

					IETR_VAL(namespace_, contextGetNamespace(context));
					return constantStringOrEmpty(namespace_.raw());
				}
				case ARM_MAGIC_METHOD: {
					IETR_VAL(method, contextGetMethod(context));
					return constantStringOrEmpty(method.raw());
				}
				case ARM_MAGIC_FUNCTION: {
					IETR_VAL(function, contextGetFunction(context));
					return constantStringOrEmpty(function.raw());
				}
				case ARM_MAGIC_TRAIT: {
					IETR_VAL(traitName, contextGetTraitName(context));
					if (Z_TYPE_P(traitName.raw()) == IS_NULL) {
						return newConstantStringType(ZSTR_EMPTY_ALLOC());
					}

					IETR_VAL(traitNameAgain, contextGetTraitName(context));
					return constantStringOf(traitNameAgain.raw(), true);
				}
				case ARM_MAGIC_PROPERTY: {
					IETR_VAL(contextProperty, contextGetProperty(context));
					if (Z_TYPE_P(contextProperty.raw()) == IS_NULL) {
						return newConstantStringType(ZSTR_EMPTY_ALLOC());
					}

					return constantStringOf(contextProperty.raw(), false);
				}
				case ARM_PROPERTY_FETCH: {
					zval *name = nodeProp(pt_ietr_property_fetch_name, expr);
					if (UNEXPECTED(name == NULL)) return zv::Val();
					int isIdentifier = isAClass(name, PT_CLASS_IDENTIFIER);
					if (UNEXPECTED(isIdentifier < 0)) return zv::Val();
					if (!isIdentifier) break;

					zval *var = nodeProp(pt_ietr_property_fetch_var, expr);
					if (UNEXPECTED(var == NULL)) return zv::Val();
					IETR_VAL(fetchedOnType, getTypeRecursive(var, context));
					zend_string *propertyName = identifierString(name);
					if (UNEXPECTED(propertyName == NULL)) return zv::Val();
					zval propertyNameZv;
					ZVAL_STR(&propertyNameZv, propertyName);
					IETR_TRI(hasProperty, triOn(fetchedOnType.raw(), mHasInstanceProperty, 1, &propertyNameZv));
					if (hasProperty != PT_TRI_YES) {
						return newErrorType();
					}

					IETR_VAL(outOfClassScope, pt_type_new(PT_CLASS_OUT_OF_CLASS_SCOPE, 0, NULL));
					zv::Args argv{&propertyNameZv, outOfClassScope.raw()};
					IETR_VAL(property, callOn(fetchedOnType.raw(), mGetInstanceProperty, 2, argv));
					return callOn(property.raw(), mGetReadableType);
				}
				default:
					break;
			}
		}

		return newMixedType();
	}

	/* the static closure arm of getType() */
	zv::Val getStaticClosureType(zval *expr, zval *context) const
	{
		zval *params = nodeProp(pt_ietr_closure_params, expr);
		if (UNEXPECTED(params == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(params) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(params));
			params = NULL;
		}

		zv::Arr parameters = zv::Arr::create(params != NULL ? zend_hash_num_elements(Z_ARRVAL_P(params)) : 0);
		bool isVariadic = false;
		zval firstOptionalParameterIndex;
		ZVAL_NULL(&firstOptionalParameterIndex);
		if (params != NULL) {
			for (zv::ArrayEntry entry : zv::ArrRef(params)) {
				zval *param = entry.value().deref().raw();
				if (UNEXPECTED(Z_TYPE_P(param) != IS_OBJECT)) {
					zend_throw_error(NULL, "Attempt to read property \"default\" on %s", zend_zval_value_name(param));
					return zv::Val();
				}
				zval *default_ = nodeProp(pt_ietr_param_default, param);
				if (UNEXPECTED(default_ == NULL)) return zv::Val();
				bool isOptionalCandidate = Z_TYPE_P(default_) != IS_NULL;
				if (!isOptionalCandidate) {
					zval *variadic = nodeProp(pt_ietr_param_variadic, param);
					if (UNEXPECTED(variadic == NULL)) return zv::Val();
					isOptionalCandidate = zend_is_true(variadic);
				}

				if (isOptionalCandidate) {
					if (Z_TYPE(firstOptionalParameterIndex) == IS_NULL) {
						entryKey(entry, firstOptionalParameterIndex);
					}
				} else {
					ZVAL_NULL(&firstOptionalParameterIndex);
				}
			}

			for (zv::ArrayEntry entry : zv::ArrRef(params)) {
				zval *param = entry.value().deref().raw();
				zval *variadic = nodeProp(pt_ietr_param_variadic, param);
				if (UNEXPECTED(variadic == NULL)) return zv::Val();
				bool paramVariadic = zend_is_true(variadic);
				if (paramVariadic) {
					isVariadic = true;
				}
				zval *var = nodeProp(pt_ietr_param_var, param);
				if (UNEXPECTED(var == NULL)) return zv::Val();
				int isVariable = isAClass(var, PT_CLASS_VARIABLE);
				if (UNEXPECTED(isVariable < 0)) return zv::Val();
				if (!isVariable) {
					pt_throw_should_not_happen();
					return zv::Val();
				}
				zval *varName = nodeProp(pt_ietr_variable_name, var);
				if (UNEXPECTED(varName == NULL)) return zv::Val();
				if (Z_TYPE_P(varName) != IS_STRING) {
					pt_throw_should_not_happen();
					return zv::Val();
				}

				zval i;
				entryKey(entry, i);
				bool optional = Z_TYPE(firstOptionalParameterIndex) != IS_NULL && zend_compare(&i, &firstOptionalParameterIndex) >= 0;
				zval *paramType = nodeProp(pt_ietr_param_type, param);
				if (UNEXPECTED(paramType == NULL)) return zv::Val();
				int nullable = isParameterValueNullable(param);
				if (UNEXPECTED(nullable < 0)) return zv::Val();
				IETR_VAL(functionType, getFunctionType(paramType, nullable == 1, false, context));
				zval *byRef = nodeProp(pt_ietr_param_by_ref, param);
				if (UNEXPECTED(byRef == NULL)) return zv::Val();
				zend_object *passedByReferenceObject = zend_is_true(byRef) ? pt_passed_by_reference_create_creates_new_variable() : pt_passed_by_reference_create_no();
				if (UNEXPECTED(passedByReferenceObject == NULL)) return zv::Val();
				zval passedByReference;
				ZVAL_OBJ_COPY(&passedByReference, passedByReferenceObject);
				zv::Val passedByReferenceHold = zv::Val::adopt(passedByReference);
				zval *default_ = nodeProp(pt_ietr_param_default, param);
				if (UNEXPECTED(default_ == NULL)) return zv::Val();
				zv::Val defaultValue = zv::Val::null();
				if (Z_TYPE_P(default_) != IS_NULL) {
					defaultValue = getTypeRecursive(default_, context);
					if (UNEXPECTED(defaultValue.isUndef())) return zv::Val();
				}

				zval optionalZv = {}, variadicZv = {};
				ZVAL_BOOL(&optionalZv, optional);
				ZVAL_BOOL(&variadicZv, paramVariadic);
				zv::Args argv{varName, &optionalZv, functionType.raw(), passedByReferenceHold.raw(), &variadicZv, defaultValue.raw()};
				IETR_VAL(parameter, pt_native_parameter_reflection_new(6, argv));
				parameters.push(std::move(parameter));
			}
		}

		IETR_VAL(returnTypeInit, newMixedType(false));
		zv::Val returnType = std::move(returnTypeInit);
		zval *exprReturnType = nodeProp(pt_ietr_closure_return_type, expr);
		if (UNEXPECTED(exprReturnType == NULL)) return zv::Val();
		if (Z_TYPE_P(exprReturnType) != IS_NULL) {
			returnType = getFunctionType(exprReturnType, false, false, context);
			if (UNEXPECTED(returnType.isUndef())) return zv::Val();
		}

		IETR_VAL(templateTypeMap, pt_type_call_static_ce(pt_ce_template_type_map, PT_LC("createempty"), 0, NULL));
		IETR_VAL(resolvedTemplateTypeMap, pt_type_call_static_ce(pt_ce_template_type_map, PT_LC("createempty"), 0, NULL));
		IETR_VAL(callSiteVarianceMap, pt_type_call_static_ce(pt_ce_template_type_variance_map, PT_LC("createempty"), 0, NULL));
		zval closureType;
		if (UNEXPECTED(!pt_closure_type_new(
			&closureType,
			parameters.raw(),
			returnType.raw(),
			isVariadic,
			templateTypeMap.raw(),
			resolvedTemplateTypeMap.raw(),
			callSiteVarianceMap.raw(),
			NULL,
			NULL,
			NULL,
			NULL,
			NULL,
			pt_trinary_singleton(PT_TRI_YES),
			NULL,
			NULL,
			pt_trinary_singleton(PT_TRI_YES)
		))) return zv::Val();
		return zv::Val::adopt(closureType);
	}

	/* $this->getType($expr, $context) of a nested node — on a fresh C stack
	 * segment when the current one runs low (the twin recursed on the VM
	 * stack); NULL expr = the property read failed (pending exception) */
	zv::Val getTypeRecursive(zval *expr, zval *context) const
	{
		if (UNEXPECTED(expr == NULL)) return zv::Val();
		if (UNEXPECTED(!requireExpr(expr, "getType", "expr"))) return zv::Val();
		zv::Val type;
		pt_engine_with_stack([&]() { type = getType(expr, context); });
		return type;
	}

	/* }}} */

	/* {{{ getArrayType() / getCastType() / getCastObjectType() */

	/* Mirrors getArrayType(). */
	zv::Val getArrayType(zval *expr, const pt_ietr_get_type &getTypeCallback) const
	{
		zval *items = nodeProp(pt_ietr_array_items, expr);
		if (UNEXPECTED(items == NULL)) return zv::Val();
		if (UNEXPECTED(!requireArray(items))) return zv::Val();
		if (zend_hash_num_elements(Z_ARRVAL_P(items)) > PT_CONSTANT_ARRAY_TYPE_BUILDER_ARRAY_COUNT_LIMIT) {
			IETR_VAL(callable, callbackCallable(getTypeCallback));
			return oversizedArrayBuild(prop(slots::oversizedArrayBuilder), expr, callable.raw());
		}

		IETR_VAL(arrayBuilder, pt_constant_array_type_builder_create_empty());
		zval isList;
		ZVAL_NULL(&isList);
		zv::Arr hasOffsetValueTypes = zv::Arr::create(0);
		for (zv::ArrayEntry itemEntry : zv::ArrRef(items)) {
			zval *arrayItem = itemEntry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(arrayItem) != IS_OBJECT)) {
				// a list() hole: the twin reads null off it with the engine's
				// warnings and hands the callback null
				zend_error(E_WARNING, "Attempt to read property \"value\" on %s", zend_zval_value_name(arrayItem));
				if (UNEXPECTED(EG(exception) != NULL)) return zv::Val();
				zval nullValue;
				ZVAL_NULL(&nullValue);
				IETR_VAL(holeValueType, getTypeOf(getTypeCallback, &nullValue));
				zend_error(E_WARNING, "Attempt to read property \"unpack\" on %s", zend_zval_value_name(arrayItem));
				if (UNEXPECTED(EG(exception) != NULL)) return zv::Val();
				zend_error(E_WARNING, "Attempt to read property \"key\" on %s", zend_zval_value_name(arrayItem));
				if (UNEXPECTED(EG(exception) != NULL)) return zv::Val();
				if (UNEXPECTED(!pt_constant_array_type_builder_set_offset_value_type(arrayBuilder.raw(), NULL, holeValueType.raw()))) return zv::Val();
				continue;
			}
			zval *itemValue = nodeProp(pt_ietr_array_item_value, arrayItem);
			if (UNEXPECTED(itemValue == NULL)) return zv::Val();
			IETR_VAL(valueType, getTypeOf(getTypeCallback, itemValue));
			zval *unpack = nodeProp(pt_ietr_array_item_unpack, arrayItem);
			if (UNEXPECTED(unpack == NULL)) return zv::Val();
			if (zend_is_true(unpack)) {
				IETR_VAL(constantArrays, callOn(valueType.raw(), mGetConstantArrays));
				if (UNEXPECTED(!requireArray(constantArrays.raw()))) return zv::Val();
				uint32_t totalArrays = zend_hash_num_elements(Z_ARRVAL_P(constantArrays.raw()));
				if (totalArrays > 0) {
					if (UNEXPECTED(!unpackConstantArrays(arrayBuilder.raw(), constantArrays.raw(), totalArrays, hasOffsetValueTypes))) return zv::Val();
				} else {
					if (UNEXPECTED(!pt_constant_array_type_builder_degrade_to_general_array(arrayBuilder.raw()))) return zv::Val();

					int keepStringKeys = phpVersionAtLeast(prop(slots::phpVersion), 80100, PT_LC("supportsarrayunpackingwithstringkeys"));
					if (UNEXPECTED(keepStringKeys < 0)) return zv::Val();
					bool stringKeys = false;
					if (keepStringKeys) {
						IETR_VAL(iterableKeyType, callOn(valueType.raw(), mGetIterableKeyType));
						IETR_TRI(keyIsString, triOn(iterableKeyType.raw(), mIsString));
						stringKeys = keyIsString != PT_TRI_NO;
					}
					zv::Val offsetType;
					if (stringKeys) {
						ZVAL_FALSE(&isList);
						offsetType = callOn(valueType.raw(), mGetIterableKeyType);
						if (UNEXPECTED(offsetType.isUndef())) return zv::Val();

						zv::Arr kept = zv::Arr::create(zend_hash_num_elements(hasOffsetValueTypes.table()));
						for (zv::ArrayEntry entry : zv::ArrRef(hasOffsetValueTypes.raw())) {
							zval *hasOffsetValueType = entry.value().raw();
							IETR_VAL(hasOffsetOffsetType, pt_has_offset_value_type_get_offset_type(Z_OBJ_P(hasOffsetValueType)));
							IETR_TRI(covers, triOn(offsetType.raw(), mIsSuperTypeOf, 1, hasOffsetOffsetType.raw()));
							if (covers != PT_TRI_YES) {
								if (entry.hasStringKey()) {
									zend_hash_add_new(kept.table(), entry.stringKey(), hasOffsetValueType);
								} else {
									zend_hash_index_add_new(kept.table(), entry.indexKey(), hasOffsetValueType);
								}
								Z_ADDREF_P(hasOffsetValueType);
							}
						}
						hasOffsetValueTypes = std::move(kept);
					} else {
						if (Z_TYPE(isList) == IS_NULL) {
							IETR_VAL(builderIsList, callOn(arrayBuilder.raw(), mIsList));
							ZVAL_BOOL(&isList, zend_is_true(builderIsList.raw()));
						}
						offsetType = newIntegerType();
						if (UNEXPECTED(offsetType.isUndef())) return zv::Val();
					}

					IETR_VAL(iterableValueType, callOn(valueType.raw(), mGetIterableValueType));
					IETR_TRI(atLeastOnce, triOn(valueType.raw(), mIsIterableAtLeastOnce));
					if (UNEXPECTED(!pt_constant_array_type_builder_set_offset_value_type(arrayBuilder.raw(), offsetType.raw(), iterableValueType.raw(), atLeastOnce != PT_TRI_YES))) return zv::Val();
				}
			} else {
				zval *key = nodeProp(pt_ietr_array_item_key, arrayItem);
				if (UNEXPECTED(key == NULL)) return zv::Val();
				zv::Val keyType;
				if (Z_TYPE_P(key) != IS_NULL) {
					keyType = getTypeOf(getTypeCallback, key);
					if (UNEXPECTED(keyType.isUndef())) return zv::Val();
				}
				if (UNEXPECTED(!pt_constant_array_type_builder_set_offset_value_type(arrayBuilder.raw(), keyType.isUndef() ? NULL : keyType.raw(), valueType.raw()))) return zv::Val();
			}
		}

		IETR_VAL(arrayTypeInit, pt_constant_array_type_builder_get_array(arrayBuilder.raw()));
		zv::Val arrayType = std::move(arrayTypeInit);
		if (Z_TYPE(isList) == IS_TRUE) {
			zval listType;
			if (UNEXPECTED(!pt_accessory_array_list_type_new(&listType))) return zv::Val();
			zv::Val listTypeHold = zv::Val::adopt(listType);
			zv::Args argv{arrayType.raw(), listTypeHold.raw()};
			IETR_VAL(intersected, pt_type_combinator_intersect(2, argv));
			arrayType = std::move(intersected);
		}

		uint32_t hasOffsetCount = zend_hash_num_elements(hasOffsetValueTypes.table());
		if (hasOffsetCount > 0) {
			IETR_TRI(isConstantArray, triOn(arrayType.raw(), mIsConstantArray));
			if (isConstantArray != PT_TRI_YES) {
				zv::Arr intersectArgs = zv::Arr::create(hasOffsetCount + 1);
				intersectArgs.push(zv::Ref(arrayType.raw()));
				for (zv::ArrayEntry entry : zv::ArrRef(hasOffsetValueTypes.raw())) {
					intersectArgs.push(entry.value());
				}
				IETR_VAL(intersected, pt_type_combinator_intersect(hasOffsetCount + 1, intersectArgs.table()->arPacked));
				arrayType = std::move(intersected);
			}
		}

		return arrayType;
	}

	/* the unpack arm of getArrayType() over the item's constant arrays:
	 * the slots merged by string key / integer position, set on the builder
	 * and remembered as HasOffsetValueTypes; false = pending exception */
	bool unpackConstantArrays(zval *arrayBuilder, zval *constantArrays, uint32_t totalArrays, zv::Arr &hasOffsetValueTypes) const
	{
		int keepStringKeys = phpVersionAtLeast(prop(slots::phpVersion), 80100, PT_LC("supportsarrayunpackingwithstringkeys"));
		if (UNEXPECTED(keepStringKeys < 0)) return false;

		// Unpacking merges string keys by name, while integer keys are always
		// renumbered, so they're merged by their position among integer keys.
		// A slot missing from some of the unpacked arrays becomes optional.
		// $slots / $slotOrder: the slots in creation order, found by their
		// string key's value or their integer position
		struct Slot
		{
			zval keyType; /* borrowed from the unpacked array's key types, UNDEF = null */
			zv::Arr valueTypes;
			zend_long presentCount;
			bool anyOptional;
		};
		std::vector<Slot> slots;
		std::vector<size_t> integerSlots;
		zv::ScratchTable stringSlots(8);
		// the key type arrays the slots borrow from, kept alive
		zv::Arr keyTypeArrays = zv::Arr::create(totalArrays);

		for (zv::ArrayEntry arrayEntry : zv::ArrRef(constantArrays)) {
			zval *constantArrayType = arrayEntry.value().raw();
			size_t nextIntegerSlot = 0;
			zv::Val keyTypes = callOn(constantArrayType, mGetKeyTypes);
			if (UNEXPECTED(keyTypes.isUndef())) return false;
			if (UNEXPECTED(!requireArray(keyTypes.raw()))) return false;
			for (zv::ArrayEntry keyEntry : zv::ArrRef(keyTypes.raw())) {
				zval *keyType = keyEntry.value().raw();
				zval i;
				entryKey(keyEntry, i);

				size_t slotIndex;
				bool stringSlot = false;
				zv::Val keyValue;
				if (keepStringKeys) {
					zend_long keyIsString = triOn(keyType, mIsString);
					if (UNEXPECTED(keyIsString < 0)) return false;
					stringSlot = keyIsString == PT_TRI_YES;
				}
				if (stringSlot) {
					keyValue = callOn(keyType, mGetValue);
					if (UNEXPECTED(keyValue.isUndef())) return false;
					zend_string *slotKey = zval_get_string(keyValue.raw());
					if (UNEXPECTED(EG(exception) != NULL)) {
						zend_string_release(slotKey);
						return false;
					}
					zval *found = zend_hash_find(stringSlots.table(), slotKey);
					if (found != NULL) {
						slotIndex = (size_t) Z_LVAL_P(found);
					} else {
						slotIndex = slots.size();
						zval indexZv;
						ZVAL_LONG(&indexZv, (zend_long) slotIndex);
						zend_hash_add_new(stringSlots.table(), slotKey, &indexZv);
						slots.push_back(Slot{});
						ZVAL_COPY_VALUE(&slots.back().keyType, keyType);
						slots.back().valueTypes = zv::Arr::create(totalArrays);
						slots.back().presentCount = 0;
						slots.back().anyOptional = false;
					}
					zend_string_release(slotKey);
				} else {
					size_t position = nextIntegerSlot++;
					if (position < integerSlots.size()) {
						slotIndex = integerSlots[position];
					} else {
						slotIndex = slots.size();
						integerSlots.push_back(slotIndex);
						slots.push_back(Slot{});
						ZVAL_UNDEF(&slots.back().keyType);
						slots.back().valueTypes = zv::Arr::create(totalArrays);
						slots.back().presentCount = 0;
						slots.back().anyOptional = false;
					}
				}

				zv::Val valueTypes = callOn(constantArrayType, mGetValueTypes);
				if (UNEXPECTED(valueTypes.isUndef())) return false;
				zval *valueType = Z_TYPE_P(valueTypes.raw()) == IS_ARRAY ? arrayOffsetForRead(valueTypes.raw(), &i) : NULL;
				if (UNEXPECTED(EG(exception) != NULL)) return false;
				Slot &slot = slots[slotIndex];
				if (valueType != NULL) {
					slot.valueTypes.push(zv::Ref(valueType));
				} else {
					slot.valueTypes.push(zv::Val::null());
				}
				slot.presentCount++;
				zv::Val optional = callOn(constantArrayType, mIsOptionalKey, 1, &i);
				if (UNEXPECTED(optional.isUndef())) return false;
				if (!zend_is_true(optional.raw())) {
					continue;
				}

				slot.anyOptional = true;
			}
			keyTypeArrays.push(std::move(keyTypes));
		}

		for (Slot &slot : slots) {
			zv::Val mergedValueType = unionOf(slot.valueTypes);
			if (UNEXPECTED(mergedValueType.isUndef())) return false;
			bool isOptional = slot.anyOptional || slot.presentCount < (zend_long) totalArrays;
			zval *slotKeyType = Z_TYPE(slot.keyType) == IS_UNDEF ? NULL : &slot.keyType;
			if (UNEXPECTED(!pt_constant_array_type_builder_set_offset_value_type(arrayBuilder, slotKeyType, mergedValueType.raw(), isOptional))) return false;

			if (slotKeyType == NULL) {
				continue;
			}

			zv::Val keyValue = callOn(slotKeyType, mGetValue);
			if (UNEXPECTED(keyValue.isUndef())) return false;
			zval *existing = arrayKeyFind(hasOffsetValueTypes.raw(), keyValue.raw());
			if (existing != NULL) {
				zv::Val newValueType;
				if (isOptional) {
					zv::Val existingValueType = pt_has_offset_value_type_get_value_type(Z_OBJ_P(existing));
					if (UNEXPECTED(existingValueType.isUndef())) return false;
					newValueType = union2(existingValueType.raw(), mergedValueType.raw());
					if (UNEXPECTED(newValueType.isUndef())) return false;
				} else {
					newValueType = zv::Val::copyOf(zv::Ref(mergedValueType.raw()));
				}
				zval hasOffsetValueType;
				if (UNEXPECTED(!pt_has_offset_value_type_new(&hasOffsetValueType, slotKeyType, newValueType.raw()))) return false;
				arrayKeyUpdate(hasOffsetValueTypes, keyValue.raw(), zv::Val::adopt(hasOffsetValueType));
			} else if (!isOptional) {
				zval hasOffsetValueType;
				if (UNEXPECTED(!pt_has_offset_value_type_new(&hasOffsetValueType, slotKeyType, mergedValueType.raw()))) return false;
				arrayKeyUpdate(hasOffsetValueTypes, keyValue.raw(), zv::Val::adopt(hasOffsetValueType));
			}
		}

		return true;
	}

	/* Mirrors getCastType(). */
	zv::Val getCastType(zval *expr, const pt_ietr_get_type &getTypeCallback) const
	{
		static constexpr int castClasses[] = {
			PT_CLASS_CAST_INT,
			PT_CLASS_CAST_BOOL,
			PT_CLASS_CAST_DOUBLE,
			PT_CLASS_CAST_STRING,
			PT_CLASS_CAST_ARRAY,
			PT_CLASS_CAST_OBJECT,
		};
		for (size_t kind = 0; kind < sizeof(castClasses) / sizeof(castClasses[0]); kind++) {
			int matches = isAClass(expr, castClasses[kind]);
			if (UNEXPECTED(matches < 0)) return zv::Val();
			if (!matches) continue;
			zval *operand = nodeProp(pt_ietr_cast_expr, expr);
			if (UNEXPECTED(operand == NULL)) return zv::Val();
			IETR_VAL(operandType, getTypeOf(getTypeCallback, operand));
			switch (kind) {
				case 0:
					return callOn(operandType.raw(), mToInteger);
				case 1:
					return callOn(operandType.raw(), mToBoolean);
				case 2:
					return callOn(operandType.raw(), mToFloat);
				case 3:
					return callOn(operandType.raw(), mToString);
				case 4:
					return callOn(operandType.raw(), mToArray);
				default:
					return getCastObjectType(operandType.raw());
			}
		}

		return newMixedType();
	}

	/* Mirrors getCastObjectType(). */
	static zv::Val getCastObjectType(zval *exprType)
	{
		if (isA(exprType, pt_ce_union_type)) {
			IETR_VAL(types, callOn(exprType, mGetTypes));
			if (UNEXPECTED(Z_TYPE_P(types.raw()) != IS_ARRAY)) {
				zend_type_error("array_map(): Argument #2 ($array) must be of type array, %s given", zend_zval_value_name(types.raw()));
				return zv::Val();
			}
			zv::Arr objects = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(types.raw())));
			for (zv::ArrayEntry entry : zv::ArrRef(types.raw())) {
				IETR_VAL(object, castToObject(entry.value().raw()));
				objects.push(std::move(object));
			}
			return unionOf(objects);
		}

		return castToObject(exprType);
	}

	/* the $castToObject closure of getCastObjectType() */
	static zv::Val castToObject(zval *type)
	{
		IETR_VAL(constantArrays, callOn(type, mGetConstantArrays));
		if (UNEXPECTED(!requireArray(constantArrays.raw()))) return zv::Val();
		if (zend_hash_num_elements(Z_ARRVAL_P(constantArrays.raw())) > 0) {
			zv::Arr objects = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(constantArrays.raw())));
			for (zv::ArrayEntry arrayEntry : zv::ArrRef(constantArrays.raw())) {
				zval *constantArray = arrayEntry.value().raw();
				zv::Arr properties = zv::Arr::create(0);
				zv::Arr optionalProperties = zv::Arr::create(0);
				IETR_VAL(keyTypes, callOn(constantArray, mGetKeyTypes));
				if (UNEXPECTED(!requireArray(keyTypes.raw()))) return zv::Val();
				for (zv::ArrayEntry keyEntry : zv::ArrRef(keyTypes.raw())) {
					zval *keyType = keyEntry.value().raw();
					zval i;
					entryKey(keyEntry, i);
					IETR_VAL(valueTypes, callOn(constantArray, mGetValueTypes));
					zval *valueType = Z_TYPE_P(valueTypes.raw()) == IS_ARRAY ? arrayOffsetForRead(valueTypes.raw(), &i) : NULL;
					if (UNEXPECTED(EG(exception) != NULL)) return zv::Val();
					IETR_VAL(optional, callOn(constantArray, mIsOptionalKey, 1, &i));
					if (zend_is_true(optional.raw())) {
						IETR_VAL(keyValue, callOn(keyType, mGetValue));
						optionalProperties.push(std::move(keyValue));
					}
					IETR_VAL(keyValue, callOn(keyType, mGetValue));
					if (UNEXPECTED(!arrayKeyUpdate(properties, keyValue.raw(), valueType != NULL ? zv::Val::copyOf(zv::Ref(valueType)) : zv::Val::null()))) return zv::Val();
				}

				zval shape;
				if (UNEXPECTED(!pt_object_shape_type_new(&shape, properties.raw(), optionalProperties.raw()))) return zv::Val();
				zv::Arr members = zv::Arr::create(2);
				members.push(zv::Val::adopt(shape));
				IETR_VAL(stdClass, newObjectTypeLiteral(PT_LC("stdClass")));
				members.push(std::move(stdClass));
				IETR_VAL(object, newIntersectionType(members));
				objects.push(std::move(object));
			}

			return unionOf(objects);
		}
		IETR_TRI(isObject, triOn(type, mIsObject));
		if (isObject == PT_TRI_YES) {
			return zv::Val::copyOf(zv::Ref(type));
		}

		return newObjectTypeLiteral(PT_LC("stdClass"));
	}

	/* }}} */

	/* {{{ getFunctionType() / the first-class callables */

	/* Mirrors getFunctionType(). */
	zv::Val getFunctionType(zval *type, bool isNullable, bool isVariadic, zval *context) const
	{
		if (isNullable) {
			IETR_VAL(inner, getFunctionType(type, false, isVariadic, context));
			return pt_type_combinator_add_null(inner.raw());
		}
		if (isVariadic) {
			int supportsNamedArguments = phpVersionAtLeast(prop(slots::phpVersion), 80000, PT_LC("supportsnamedarguments"));
			if (UNEXPECTED(supportsNamedArguments < 0)) return zv::Val();
			zval zero;
			ZVAL_LONG(&zero, 0);
			if (supportsNamedArguments) {
				zv::Arr keyTypes = zv::Arr::create(2);
				IETR_VAL(nonNegative, pt_integer_range_create_all_greater_than_or_equal_to(&zero));
				keyTypes.push(std::move(nonNegative));
				IETR_VAL(stringType, newStringType());
				keyTypes.push(std::move(stringType));
				IETR_VAL(keyType, newUnionType(keyTypes));
				IETR_VAL(itemType, getFunctionType(type, false, false, context));
				return newArrayType(keyType.raw(), itemType.raw());
			}

			IETR_VAL(nonNegative, pt_integer_range_create_all_greater_than_or_equal_to(&zero));
			IETR_VAL(itemType, getFunctionType(type, false, false, context));
			zv::Arr members = zv::Arr::create(2);
			IETR_VAL(arrayType, newArrayType(nonNegative.raw(), itemType.raw()));
			members.push(std::move(arrayType));
			zval listType;
			if (UNEXPECTED(!pt_accessory_array_list_type_new(&listType))) return zv::Val();
			members.push(zv::Val::adopt(listType));
			return newIntersectionType(members);
		}

		int isName = isAClass(type, PT_CLASS_NAME);
		if (UNEXPECTED(isName < 0)) return zv::Val();
		if (isName) {
			zend_string *className = nameString(type);
			if (UNEXPECTED(className == NULL)) return zv::Val();
			if (zend_string_equals_literal_ci(className, "parent")) {
				zv::Val classReflection = zv::Val::null();
				IETR_VAL(contextClassName, contextGetClassName(context));
				if (Z_TYPE_P(contextClassName.raw()) != IS_NULL) {
					bool hasClass;
					if (UNEXPECTED(!reflectionProviderHasClass(contextClassName.raw(), hasClass))) return zv::Val();
					if (hasClass) {
						IETR_VAL(contextClassNameAgain, contextGetClassName(context));
						classReflection = reflectionProviderGetClass(contextClassNameAgain.raw());
						if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
					}
				}
				if (Z_TYPE_P(classReflection.raw()) != IS_NULL) {
					IETR_VAL(parentClass, callOn(classReflection.raw(), mGetParentClass));
					if (Z_TYPE_P(parentClass.raw()) != IS_NULL) {
						IETR_VAL(parentClassAgain, callOn(classReflection.raw(), mGetParentClass));
						IETR_VAL(parentName, classReflectionName(parentClassAgain.raw()));
						return objectTypeOf(parentName.raw());
					}
				}

				zval nonexistent;
				return adopted(pt_nonexistent_parent_class_type_new(&nonexistent), nonexistent);
			}
		}

		zv::Val classReflection = zv::Val::null();
		IETR_VAL(contextClassName, contextGetClassName(context));
		if (Z_TYPE_P(contextClassName.raw()) != IS_NULL) {
			bool hasClass;
			if (UNEXPECTED(!reflectionProviderHasClass(contextClassName.raw(), hasClass))) return zv::Val();
			if (hasClass) {
				IETR_VAL(contextClassNameAgain, contextGetClassName(context));
				classReflection = reflectionProviderGetClass(contextClassNameAgain.raw());
				if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
			}
		}

		return parserNodeTypeResolve(type, classReflection.raw());
	}

	/* Mirrors isParameterValueNullable(); -1 = pending exception */
	static int isParameterValueNullable(zval *parameter)
	{
		zval *default_ = nodeProp(pt_ietr_param_default, parameter);
		if (UNEXPECTED(default_ == NULL)) return -1;
		int isConstFetch = isAClass(default_, PT_CLASS_CONST_FETCH);
		if (UNEXPECTED(isConstFetch < 0)) return -1;
		if (isConstFetch) {
			zval *name = nodeProp(pt_ietr_const_fetch_name, default_);
			if (UNEXPECTED(name == NULL)) return -1;
			zend_string *constName = nameString(name);
			if (UNEXPECTED(constName == NULL)) return -1;
			return zend_string_equals_literal_ci(constName, "null") ? 1 : 0;
		}

		return 0;
	}

	/* Mirrors getFirstClassCallableType(). */
	zv::Val getFirstClassCallableType(zval *expr, zval *context, bool nativeTypesPromoted) const
	{
		int isFuncCall = isAClass(expr, PT_CLASS_FUNC_CALL);
		if (UNEXPECTED(isFuncCall < 0)) return zv::Val();
		if (isFuncCall) {
			zval *name = nodeProp(pt_ietr_func_call_name, expr);
			if (UNEXPECTED(name == NULL)) return zv::Val();
			int isName = isAClass(name, PT_CLASS_NAME);
			if (UNEXPECTED(isName < 0)) return zv::Val();
			if (isName) {
				bool hasFunction;
				{
					IETR_VAL(reflectionProvider, reflectionProviderOf(prop(slots::reflectionProviderProvider)));
					if (UNEXPECTED(!pt_reflection_provider_has_function(reflectionProvider.raw(), name, context, hasFunction))) return zv::Val();
				}
				if (hasFunction) {
					IETR_VAL(reflectionProvider, reflectionProviderOf(prop(slots::reflectionProviderProvider)));
					IETR_VAL(function, pt_reflection_provider_get_function(reflectionProvider.raw(), name, context));
					IETR_VAL(variants, callOn(function.raw(), mGetVariants));
					return createFirstClassCallable(function.raw(), variants.raw(), nativeTypesPromoted);
				}

				return newObjectTypeLiteral(PT_LC("Closure"));
			}
		}

		int isStaticCall = isAClass(expr, PT_CLASS_STATIC_CALL);
		if (UNEXPECTED(isStaticCall < 0)) return zv::Val();
		if (isStaticCall) {
			zval *class_ = nodeProp(pt_ietr_static_call_class, expr);
			if (UNEXPECTED(class_ == NULL)) return zv::Val();
			int classIsName = isAClass(class_, PT_CLASS_NAME);
			if (UNEXPECTED(classIsName < 0)) return zv::Val();
			if (!classIsName) {
				return newObjectTypeLiteral(PT_LC("Closure"));
			}

			zval *name = nodeProp(pt_ietr_static_call_name, expr);
			if (UNEXPECTED(name == NULL)) return zv::Val();
			int nameIsIdentifier = isAClass(name, PT_CLASS_IDENTIFIER);
			if (UNEXPECTED(nameIsIdentifier < 0)) return zv::Val();
			if (!nameIsIdentifier) {
				return newObjectTypeLiteral(PT_LC("Closure"));
			}

			zv::Val classReflection = zv::Val::null();
			{
				IETR_VAL(contextClassName, contextGetClassName(context));
				if (Z_TYPE_P(contextClassName.raw()) != IS_NULL) {
					bool hasClass;
					if (UNEXPECTED(!reflectionProviderHasClass(contextClassName.raw(), hasClass))) return zv::Val();
					if (hasClass) {
						IETR_VAL(contextClassNameAgain, contextGetClassName(context));
						classReflection = reflectionProviderGetClass(contextClassNameAgain.raw());
						if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
					}
				}
			}

			IETR_VAL(classTypeInit, resolveTypeByName(class_, classReflection.raw()));
			zv::Val classType = std::move(classTypeInit);
			zend_string *methodNameString = identifierString(name);
			if (UNEXPECTED(methodNameString == NULL)) return zv::Val();
			zval methodName;
			ZVAL_STR(&methodName, methodNameString);
			{
				IETR_TRI(hasMethod, triOn(classType.raw(), mHasMethod, 1, &methodName));
				if (hasMethod != PT_TRI_YES) {
					return newObjectTypeLiteral(PT_LC("Closure"));
				}
			}

			zv::Val method;
			{
				IETR_VAL(outOfClassScope, pt_type_new(PT_CLASS_OUT_OF_CLASS_SCOPE, 0, NULL));
				zv::Args argv{&methodName, outOfClassScope.raw()};
				method = callOn(classType.raw(), mGetMethod, 2, argv);
				if (UNEXPECTED(method.isUndef())) return zv::Val();
			}
			IETR_VAL(lateBoundClassType, resolveTypeByNameWithLateStaticBinding(class_, classType.raw(), method.raw()));
			classType = std::move(lateBoundClassType);
			{
				IETR_TRI(hasMethod, triOn(classType.raw(), mHasMethod, 1, &methodName));
				if (hasMethod != PT_TRI_YES) {
					return newObjectTypeLiteral(PT_LC("Closure"));
				}
			}
			{
				IETR_VAL(outOfClassScope, pt_type_new(PT_CLASS_OUT_OF_CLASS_SCOPE, 0, NULL));
				zv::Args argv{&methodName, outOfClassScope.raw()};
				method = callOn(classType.raw(), mGetMethod, 2, argv);
				if (UNEXPECTED(method.isUndef())) return zv::Val();
			}

			IETR_VAL(variants, callOn(method.raw(), mGetVariants));
			return createFirstClassCallable(method.raw(), variants.raw(), nativeTypesPromoted);
		}

		int isNew = isAClass(expr, PT_CLASS_NEW);
		if (UNEXPECTED(isNew < 0)) return zv::Val();
		if (isNew) {
			return newErrorType();
		}

		pt_throw_should_not_happen();
		return zv::Val();
	}

	/* Mirrors createFirstClassCallable(); $function PHP null for null */
	static zv::Val createFirstClassCallable(zval *function, zval *variants, bool nativeTypesPromoted)
	{
		if (UNEXPECTED(Z_TYPE_P(variants) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(variants));
			if (UNEXPECTED(EG(exception) != NULL)) return zv::Val();
			return pt_type_combinator_union(0, NULL);
		}
		zend_class_entry *extendedParametersAcceptorCe = pt_class(PT_CLASS_EXTENDED_PARAMETERS_ACCEPTOR);
		if (UNEXPECTED(extendedParametersAcceptorCe == NULL)) return zv::Val();
		zend_class_entry *callableParametersAcceptorCe = pt_class(PT_CLASS_CALLABLE_PARAMETERS_ACCEPTOR);
		if (UNEXPECTED(callableParametersAcceptorCe == NULL)) return zv::Val();
		zend_class_entry *templateTypeCe = pt_class(PT_CLASS_TEMPLATE_TYPE);
		if (UNEXPECTED(templateTypeCe == NULL)) return zv::Val();
		bool hasFunction = Z_TYPE_P(function) != IS_NULL;

		zv::Arr closureTypes = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(variants)));
		for (zv::ArrayEntry variantEntry : zv::ArrRef(variants)) {
			zval *variant = variantEntry.value().deref().raw();
			IETR_VAL(returnTypeInit, callOn(variant, mGetReturnType));
			zv::Val returnType = std::move(returnTypeInit);
			bool extendedAcceptor = isA(variant, extendedParametersAcceptorCe);
			if (extendedAcceptor && nativeTypesPromoted) {
				IETR_VAL(nativeReturnType, callOn(variant, mGetNativeReturnType));
				returnType = std::move(nativeReturnType);
			}

			zv::Arr templateTags = zv::Arr::create(0);
			{
				IETR_VAL(templateTypeMap, callOn(variant, mGetTemplateTypeMap));
				IETR_VAL(templateTypes, callOn(templateTypeMap.raw(), mGetTypes));
				if (UNEXPECTED(!requireArray(templateTypes.raw()))) return zv::Val();
				for (zv::ArrayEntry entry : zv::ArrRef(templateTypes.raw())) {
					zval *templateType = entry.value().raw();
					if (!isA(templateType, templateTypeCe)) {
						continue;
					}
					IETR_VAL(tagName, callOn(templateType, mGetName));
					IETR_VAL(name, callOn(templateType, mGetName));
					IETR_VAL(bound, callOn(templateType, mGetBound));
					IETR_VAL(defaultType, callOn(templateType, mGetDefault));
					IETR_VAL(variance, callOn(templateType, mGetVariance));
					zv::Args tagArgs{name.raw(), bound.raw(), defaultType.raw(), variance.raw()};
					IETR_VAL(tag, pt_type_new(PT_CLASS_TEMPLATE_TAG, 4, tagArgs));
					if (UNEXPECTED(!arrayKeyUpdate(templateTags, tagName.raw(), std::move(tag)))) return zv::Val();
				}
			}

			zv::Val throwPoints = zv::Val(zv::Arr::empty());
			zv::Val impurePoints = zv::Val(zv::Arr::empty());
			zv::Val acceptsNamedArguments = zv::Val::copyOf(zv::Ref(pt_trinary_singleton(PT_TRI_YES)));
			zv::Val mustUseReturnValue = zv::Val::copyOf(zv::Ref(pt_trinary_singleton(PT_TRI_MAYBE)));
			zv::Val isStaticClosure = zv::Val::copyOf(zv::Ref(pt_trinary_singleton(PT_TRI_MAYBE)));
			bool callableAcceptor = isA(variant, callableParametersAcceptorCe);
			if (callableAcceptor) {
				throwPoints = callOn(variant, mGetThrowPoints);
				if (UNEXPECTED(throwPoints.isUndef())) return zv::Val();
				impurePoints = callOn(variant, mGetImpurePoints);
				if (UNEXPECTED(impurePoints.isUndef())) return zv::Val();
				acceptsNamedArguments = callOn(variant, mAcceptsNamedArguments);
				if (UNEXPECTED(acceptsNamedArguments.isUndef())) return zv::Val();
				mustUseReturnValue = callOn(variant, mMustUseReturnValue);
				if (UNEXPECTED(mustUseReturnValue.isUndef())) return zv::Val();
				isStaticClosure = callOn(variant, mIsStaticClosure);
				if (UNEXPECTED(isStaticClosure.isUndef())) return zv::Val();
			} else if (hasFunction) {
				zv::Arr throwPointList = zv::Arr::create(1);
				zv::Arr impurePointList = zv::Arr::create(1);
				IETR_VAL(returnTypeForThrow, callOn(variant, mGetReturnType));
				IETR_VAL(throwTypeInit, callOn(function, mGetThrowType));
				zv::Val throwType = std::move(throwTypeInit);
				if (Z_TYPE_P(throwType.raw()) == IS_NULL) {
					if (isA(returnTypeForThrow.raw(), pt_ce_never_type)) {
						bool isExplicit;
						if (UNEXPECTED(!pt_never_type_is_explicit(Z_OBJ_P(returnTypeForThrow.raw()), isExplicit))) return zv::Val();
						if (isExplicit) {
							IETR_VAL(throwable, newObjectTypeLiteral(PT_LC("Throwable")));
							throwType = std::move(throwable);
						}
					}
				}

				if (Z_TYPE_P(throwType.raw()) != IS_NULL) {
					IETR_TRI(isVoid, triOn(throwType.raw(), mIsVoid));
					if (isVoid != PT_TRI_YES) {
						IETR_VAL(throwPoint, simpleThrowPointCreateExplicit(throwType.raw(), true));
						throwPointList.push(std::move(throwPoint));
					}
				} else {
					IETR_VAL(throwable, newObjectTypeLiteral(PT_LC("Throwable")));
					IETR_TRI(coversThrowable, triOn(throwable.raw(), mIsSuperTypeOf, 1, returnTypeForThrow.raw()));
					if (coversThrowable != PT_TRI_YES) {
						IETR_VAL(throwPoint, simpleThrowPointCreateImplicit());
						throwPointList.push(std::move(throwPoint));
					}
				}

				{
					pt_simple_impure_point_data impurePointData;
					zval noArgs;
					ZVAL_EMPTY_ARRAY(&noArgs);
					if (UNEXPECTED(!pt_simple_impure_point_resolve(function, variant, NULL, &noArgs, impurePointData))) return zv::Val();
					if (impurePointData.exists) {
						zv::Val impurePoint = pt_simple_impure_point_new(impurePointData.identifier, impurePointData.description, impurePointData.certain);
						zend_string_release(impurePointData.description);
						if (UNEXPECTED(impurePoint.isUndef())) return zv::Val();
						impurePointList.push(std::move(impurePoint));
					}
				}

				throwPoints = zv::Val(std::move(throwPointList));
				impurePoints = zv::Val(std::move(impurePointList));
				acceptsNamedArguments = callOn(function, mAcceptsNamedArguments);
				if (UNEXPECTED(acceptsNamedArguments.isUndef())) return zv::Val();
				mustUseReturnValue = callOn(function, mMustUseReturnValue);
				if (UNEXPECTED(mustUseReturnValue.isUndef())) return zv::Val();
			}

			IETR_VAL(parameters, callOn(variant, mGetParameters));
			zv::Val assertions;
			if (hasFunction) {
				assertions = callOn(function, mGetAsserts);
			} else if (callableAcceptor) {
				assertions = callOn(variant, mGetAsserts);
			} else {
				assertions = pt_assertions_create_empty();
			}
			if (UNEXPECTED(assertions.isUndef())) return zv::Val();

			// a conditional return type referencing the function's own parameter,
			// like @return ($value is int ? true : false) on is_int(),
			// also becomes a type predicate of the resulting Closure
			IETR_VAL(predicateAssertions, withConditionalReturnPredicate(assertions.raw(), variant));
			IETR_VAL(isVariadicHold, callOn(variant, mIsVariadic));
			IETR_VAL(templateTypeMap, callOn(variant, mGetTemplateTypeMap));
			IETR_VAL(resolvedTemplateTypeMap, callOn(variant, mGetResolvedTemplateTypeMap));
			zv::Val callSiteVarianceMap = extendedAcceptor
				? callOn(variant, mGetCallSiteVarianceMap)
				: pt_type_call_static_ce(pt_ce_template_type_variance_map, PT_LC("createempty"), 0, NULL);
			if (UNEXPECTED(callSiteVarianceMap.isUndef())) return zv::Val();
			zval closureType;
			if (UNEXPECTED(!pt_closure_type_new(
				&closureType,
				parameters.raw(),
				returnType.raw(),
				zend_is_true(isVariadicHold.raw()),
				templateTypeMap.raw(),
				resolvedTemplateTypeMap.raw(),
				callSiteVarianceMap.raw(),
				templateTags.raw(),
				throwPoints.raw(),
				impurePoints.raw(),
				NULL,
				NULL,
				acceptsNamedArguments.raw(),
				mustUseReturnValue.raw(),
				predicateAssertions.raw(),
				isStaticClosure.raw()
			))) return zv::Val();
			closureTypes.push(zv::Val::adopt(closureType));
		}

		return unionOf(closureTypes);
	}

	/* Mirrors resolveName(): the name string (owned) */
	static zv::Val resolveName(zval *name, zval *classReflection)
	{
		zend_string *originalClass = nameString(name);
		if (UNEXPECTED(originalClass == NULL)) return zv::Val();
		if (Z_TYPE_P(classReflection) != IS_NULL) {
			if (zend_string_equals_literal_ci(originalClass, "self") || zend_string_equals_literal_ci(originalClass, "static")) {
				return classReflectionName(classReflection);
			} else if (zend_string_equals_literal_ci(originalClass, "parent")) {
				IETR_VAL(parentClass, callOn(classReflection, mGetParentClass));
				if (Z_TYPE_P(parentClass.raw()) != IS_NULL) {
					IETR_VAL(parentClassAgain, callOn(classReflection, mGetParentClass));
					return classReflectionName(parentClassAgain.raw());
				}
			}
		}

		return zv::Val::string(originalClass);
	}

	/* Mirrors resolveTypeByName(). */
	static zv::Val resolveTypeByName(zval *name, zval *classReflection)
	{
		zend_string *nameValue = nameString(name);
		if (UNEXPECTED(nameValue == NULL)) return zv::Val();
		if (zend_string_equals_literal_ci(nameValue, "static") && Z_TYPE_P(classReflection) != IS_NULL) {
			zval staticType;
			return adopted(pt_static_type_new(&staticType, classReflection), staticType);
		}

		IETR_VAL(originalClass, resolveName(name, classReflection));
		if (Z_TYPE_P(classReflection) != IS_NULL) {
			zval thisType;
			if (UNEXPECTED(!pt_this_type_new(&thisType, classReflection))) return zv::Val();
			zv::Val thisTypeHold = zv::Val::adopt(thisType);
			IETR_VAL(ancestor, callOn(thisTypeHold.raw(), mGetAncestorWithClassName, 1, originalClass.raw()));
			if (Z_TYPE_P(ancestor.raw()) != IS_NULL) {
				return ancestor;
			}
		}

		return objectTypeOf(originalClass.raw());
	}

	/* Mirrors resolveTypeByNameWithLateStaticBinding(). */
	static zv::Val resolveTypeByNameWithLateStaticBinding(zval *class_, zval *classType, zval *methodReflectionCandidate)
	{
		if (isA(classType, pt_ce_static_type)) {
			zend_string *className = nameString(class_);
			if (UNEXPECTED(className == NULL)) return zv::Val();
			if (
				!zend_string_equals_literal_ci(className, "self")
				&& !zend_string_equals_literal_ci(className, "static")
				&& !zend_string_equals_literal_ci(className, "parent")
			) {
				IETR_VAL(isStatic, callOn(methodReflectionCandidate, mIsStatic));
				if (zend_is_true(isStatic.raw())) {
					return callOn(classType, mGetStaticObjectType);
				}
			}
		}

		return zv::Val::copyOf(zv::Ref(classType));
	}

	/* Mirrors getReflectionProvider(). */
	zv::Val getReflectionProvider() const
	{
		return reflectionProviderOf(prop(slots::reflectionProviderProvider));
	}

	/* }}} */

	/* {{{ getClassConstFetchTypeByReflection() / getClassConstFetchType() */

	/* Mirrors getClassConstFetchTypeByReflection(); $constantName a string
	 * zval, $classReflection PHP null for null */
	zv::Val getClassConstFetchTypeByReflection(zval *class_, zval *constantName, zval *classReflection, const pt_ietr_get_type &getTypeCallback) const
	{
		zend_string *constantNameString = Z_STR_P(constantName);
		bool constantNameIsClass = zend_string_equals_literal_ci(constantNameString, "class");
		bool isObject = false;
		zv::Val constantClassType;
		int classIsName = isAClass(class_, PT_CLASS_NAME);
		if (UNEXPECTED(classIsName < 0)) return zv::Val();
		if (classIsName) {
			zend_string *constantClass = nameString(class_);
			if (UNEXPECTED(constantClass == NULL)) return zv::Val();
			constantClassType = newObjectType(constantClass);
			if (UNEXPECTED(constantClassType.isUndef())) return zv::Val();
			bool resolveStatic = false;
			if (Z_TYPE_P(classReflection) != IS_NULL) {
				bool isFinal;
				if (UNEXPECTED(!classReflectionIsFinal(classReflection, isFinal))) return zv::Val();
				if (isFinal) {
					resolveStatic = true;
				} else if (zend_string_equals_literal_ci(constantClass, "static")) {
					if (constantNameIsClass) {
						zval staticType;
						if (UNEXPECTED(!pt_static_type_new(&staticType, classReflection))) return zv::Val();
						zv::Val staticTypeHold = zv::Val::adopt(staticType);
						return pt_type_new_ce(pt_ce_generic_class_string_type, 1, staticTypeHold.raw());
					}

					resolveStatic = true;
					isObject = true;
				}
			}
			if (
				zend_string_equals_literal_ci(constantClass, "self")
				|| zend_string_equals_literal_ci(constantClass, "parent")
				|| (resolveStatic && zend_string_equals_literal_ci(constantClass, "static"))
			) {
				IETR_VAL(resolvedName, resolveName(class_, classReflection));
				if (zend_string_equals_literal_ci(Z_STR_P(resolvedName.raw()), "parent") && constantNameIsClass) {
					return newClassStringType();
				}
				constantClassType = resolveTypeByName(class_, classReflection);
				if (UNEXPECTED(constantClassType.isUndef())) return zv::Val();
			}

			if (constantNameIsClass) {
				IETR_VAL(className, callOn(constantClassType.raw(), mGetClassName));
				return constantStringOf(className.raw(), true);
			}
		} else {
			int classIsString = isAClass(class_, PT_CLASS_SCALAR_STRING);
			if (UNEXPECTED(classIsString < 0)) return zv::Val();
			if (classIsString && constantNameIsClass) {
				zval *value = nodeProp(pt_ietr_scalar_string_value, class_);
				if (UNEXPECTED(value == NULL)) return zv::Val();
				return constantStringOf(value, true);
			}

			constantClassType = getTypeOf(getTypeCallback, class_);
			if (UNEXPECTED(constantClassType.isUndef())) return zv::Val();
			isObject = true;
		}

		if (constantNameIsClass) {
			IETR_VAL(reflectionProvider, getReflectionProvider());
			return callOn(constantClassType.raw(), mToClassConstantType, 1, reflectionProvider.raw());
		}

		{
			IETR_TRI(isClassString, triOn(constantClassType.raw(), mIsClassString));
			if (isClassString == PT_TRI_YES) {
				IETR_TRI(isConstantScalarValue, triOn(constantClassType.raw(), mIsConstantScalarValue));
				if (isConstantScalarValue == PT_TRI_YES) {
					isObject = false;
				}
				IETR_VAL(objectType, callOn(constantClassType.raw(), mGetClassStringObjectType));
				constantClassType = std::move(objectType);
			}
		}

		zv::Arr types = zv::Arr::create(0);
		IETR_VAL(referencedClasses, callOn(constantClassType.raw(), mGetObjectClassNames));
		if (UNEXPECTED(!requireArray(referencedClasses.raw()))) return zv::Val();
		for (zv::ArrayEntry entry : zv::ArrRef(referencedClasses.raw())) {
			zval *referencedClass = entry.value().raw();
			{
				bool hasClass;
				if (UNEXPECTED(!reflectionProviderHasClass(referencedClass, hasClass))) return zv::Val();
				if (!hasClass) {
					continue;
				}
			}

			IETR_VAL(constantClassReflection, reflectionProviderGetClass(referencedClass));
			{
				IETR_VAL(hasConstant, callOn(constantClassReflection.raw(), mHasConstant, 1, constantName));
				if (!zend_is_true(hasConstant.raw())) {
					IETR_VAL(reflectionName, classReflectionName(constantClassReflection.raw()));
					if (Z_TYPE_P(reflectionName.raw()) == IS_STRING && zend_string_equals_literal(Z_STR_P(reflectionName.raw()), "Attribute") && zend_string_equals_literal(constantNameString, "TARGET_CONSTANT")) {
						return newConstantIntegerType(1 << 16);
					}
					continue;
				}
			}

			{
				bool isEnum;
				if (UNEXPECTED(!classReflectionIsEnum(constantClassReflection.raw(), isEnum))) return zv::Val();
				if (isEnum) {
					IETR_VAL(hasEnumCase, callOn(constantClassReflection.raw(), mHasEnumCase, 1, constantName));
					if (zend_is_true(hasEnumCase.raw())) {
						IETR_VAL(reflectionName, classReflectionName(constantClassReflection.raw()));
						if (UNEXPECTED(Z_TYPE_P(reflectionName.raw()) != IS_STRING)) {
							pt_throw_should_not_happen();
							return zv::Val();
						}
						zval enumCase;
						if (UNEXPECTED(!pt_enum_case_object_type_new(&enumCase, Z_STR_P(reflectionName.raw()), constantNameString))) return zv::Val();
						types.push(zv::Val::adopt(enumCase));
						continue;
					}
				}
			}

			IETR_VAL(reflectionName, classReflectionName(constantClassReflection.raw()));
			zend_string *reflectionNameString = zval_get_string(reflectionName.raw());
			zend_string *resolvingName = zend_string_concat3(ZSTR_VAL(reflectionNameString), ZSTR_LEN(reflectionNameString), "::", 2, ZSTR_VAL(constantNameString), ZSTR_LEN(constantNameString));
			zend_string_release(reflectionNameString);
			zv::Val resolvingNameHold = zv::Val::adoptString(resolvingName);
			if (zend_symtable_exists(Z_ARRVAL_P(prop(slots::currentlyResolvingClassConstant)), resolvingName)) {
				IETR_VAL(mixed, newMixedType());
				types.push(std::move(mixed));
				continue;
			}

			if (!isObject) {
				zval *cached = zend_symtable_find(Z_ARRVAL_P(prop(slots::classConstantValueTypeCache)), resolvingName);
				if (cached != NULL) {
					types.push(zv::Ref(cached));
					continue;
				}
			}

			zval trueValue = {};
			ZVAL_TRUE(&trueValue);
			writeArrayProperty(slots::currentlyResolvingClassConstant, resolvingName, &trueValue);

			if (!isObject) {
				IETR_VAL(nativeReflection, callOn(constantClassReflection.raw(), mGetNativeReflection));
				IETR_VAL(reflectionConstant, callOn(nativeReflection.raw(), mGetReflectionConstant, 1, constantName));
				if (Z_TYPE_P(reflectionConstant.raw()) == IS_FALSE) {
					unsetArrayProperty(slots::currentlyResolvingClassConstant, resolvingName);
					continue;
				}
				IETR_VAL(reflectionConstantDeclaringClass, callOn(reflectionConstant.raw(), mGetDeclaringClass));
				IETR_VAL(valueExpression, callOn(reflectionConstant.raw(), mGetValueExpression));
				IETR_VAL(declaringClassName, callOn(reflectionConstantDeclaringClass.raw(), mGetName));
				IETR_VAL(declaringFileNameHold, callOn(reflectionConstantDeclaringClass.raw(), mGetFileName));
				zv::Val declaringFileName = zend_is_true(declaringFileNameHold.raw()) ? std::move(declaringFileNameHold) : zv::Val::null();
				IETR_VAL(constantContext, contextFromClass(declaringClassName.raw(), declaringFileName.raw()));
				IETR_VAL(constantType, getTypeRecursive(valueExpression.raw(), constantContext.raw()));
				zv::Val nativeType = zv::Val::null();
				{
					IETR_VAL(reflectionType, callOn(reflectionConstant.raw(), mGetType));
					if (Z_TYPE_P(reflectionType.raw()) != IS_NULL) {
						IETR_VAL(reflectionTypeAgain, callOn(reflectionConstant.raw(), mGetType));
						nativeType = pt_typehint_helper_decide_type_from_reflection(reflectionTypeAgain.raw(), NULL, constantClassReflection.raw());
						if (UNEXPECTED(nativeType.isUndef())) return zv::Val();
					}
				}
				IETR_VAL(className, classReflectionName(constantClassReflection.raw()));
				IETR_VAL(phpDocType, callOn(constantClassReflection.raw(), mGetConstantPhpDocType, 1, constantName));
				IETR_VAL(resolvedType, resolveClassConstantType(prop(slots::constantResolver), className.raw(), constantName, constantType.raw(), nativeType.raw(), phpDocType.raw()));
				writeArrayProperty(slots::classConstantValueTypeCache, resolvingName, resolvedType.raw());
				types.push(zv::Ref(resolvedType.raw()));
				unsetArrayProperty(slots::currentlyResolvingClassConstant, resolvingName);
				continue;
			}

			IETR_VAL(constantReflection, callOn(constantClassReflection.raw(), mGetConstant, 1, constantName));
			{
				bool classIsFinal;
				if (UNEXPECTED(!classReflectionIsFinal(constantClassReflection.raw(), classIsFinal))) return zv::Val();
				if (!classIsFinal) {
					IETR_VAL(constantIsFinal, callOn(constantReflection.raw(), mIsFinal));
					if (!zend_is_true(constantIsFinal.raw())) {
						IETR_VAL(hasPhpDocType, callOn(constantReflection.raw(), mHasPhpDocType));
						if (!zend_is_true(hasPhpDocType.raw())) {
							IETR_VAL(hasNativeType, callOn(constantReflection.raw(), mHasNativeType));
							if (!zend_is_true(hasNativeType.raw())) {
								unsetArrayProperty(slots::currentlyResolvingClassConstant, resolvingName);
								return newMixedType();
							}
						}
					}
				}
			}

			zv::Val constantType;
			{
				bool classIsFinal;
				if (UNEXPECTED(!classReflectionIsFinal(constantClassReflection.raw(), classIsFinal))) return zv::Val();
				if (!classIsFinal) {
					constantType = callOn(constantReflection.raw(), mGetValueType);
				} else {
					IETR_VAL(valueExpr, callOn(constantReflection.raw(), mGetValueExpr));
					IETR_VAL(declaringClass, callOn(constantReflection.raw(), mGetDeclaringClass));
					IETR_VAL(constantContext, contextFromClassReflection(declaringClass.raw()));
					constantType = getTypeRecursive(valueExpr.raw(), constantContext.raw());
				}
				if (UNEXPECTED(constantType.isUndef())) return zv::Val();
			}

			IETR_VAL(nativeType, callOn(constantReflection.raw(), mGetNativeType));
			IETR_VAL(className, classReflectionName(constantClassReflection.raw()));
			IETR_VAL(phpDocType, callOn(constantClassReflection.raw(), mGetConstantPhpDocType, 1, constantName));
			IETR_VAL(resolvedConstantType, resolveClassConstantType(prop(slots::constantResolver), className.raw(), constantName, constantType.raw(), nativeType.raw(), phpDocType.raw()));
			unsetArrayProperty(slots::currentlyResolvingClassConstant, resolvingName);
			types.push(std::move(resolvedConstantType));
		}

		if (zend_hash_num_elements(types.table()) > 0) {
			return unionOf(types);
		}

		{
			IETR_TRI(hasConstant, triOn(constantClassType.raw(), mHasConstant, 1, constantName));
			if (hasConstant != PT_TRI_YES) {
				return newErrorType();
			}
		}

		IETR_VAL(constant, callOn(constantClassType.raw(), mGetConstant, 1, constantName));
		return callOn(constant.raw(), mGetValueType);
	}

	/* Mirrors getClassConstFetchType(); $className PHP null for null */
	zv::Val getClassConstFetchType(zval *class_, zval *constantName, zval *className, const pt_ietr_get_type &getTypeCallback) const
	{
		zv::Val classReflection = zv::Val::null();
		if (Z_TYPE_P(className) != IS_NULL) {
			bool hasClass;
			if (UNEXPECTED(!reflectionProviderHasClass(className, hasClass))) return zv::Val();
			if (hasClass) {
				classReflection = reflectionProviderGetClass(className);
				if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
			}
		}

		return getClassConstFetchTypeByReflection(class_, constantName, classReflection.raw(), getTypeCallback);
	}

	/* }}} */

private:
	zend_object *self;

	zval *prop(uint32_t slot) const
	{
		return OBJ_PROP_NUM(self, slot);
	}

	/* {{{ value helpers */

	/* count() / foreach over a Type method's array result: the engine's
	 * TypeError / warning for anything else; false = pending exception */
	static bool requireArray(zval *value)
	{
		if (EXPECTED(Z_TYPE_P(value) == IS_ARRAY)) return true;
		zend_type_error("count(): Argument #1 ($value) must be of type Countable|array, %s given", zend_zval_value_name(value));
		return false;
	}

	static bool requireCountable(zval *value)
	{
		return requireArray(value);
	}

	/* $array[$index] of a list the twin indexes ($leftConstantArrays[0],
	 * $existingUnsealed[0]); NULL with the engine's warning + Error... the
	 * twin reads only indexes it knows exist, so a miss is a broken
	 * collaborator: ShouldNotHappenException */
	static zval *arrayIndex(zval *array, zend_ulong index)
	{
		if (EXPECTED(Z_TYPE_P(array) == IS_ARRAY)) {
			zval *value = zend_hash_index_find(Z_ARRVAL_P(array), index);
			if (EXPECTED(value != NULL)) {
				ZVAL_DEREF(value);
				return value;
			}
		}
		pt_throw_should_not_happen();
		return NULL;
	}

	/* $array[$index] read for a value the twin then calls a method on */
	static zval *arrayIndexForRead(zval *array, zend_ulong index)
	{
		return arrayIndex(array, index);
	}

	/* $array[$key] with a key of the array's own iteration (int or string);
	 * NULL when absent (the twin's warning + null follow at the caller) */
	static zval *arrayOffsetForRead(zval *array, zval *key)
	{
		zval *value = Z_TYPE_P(key) == IS_LONG ? zend_hash_index_find(Z_ARRVAL_P(array), (zend_ulong) Z_LVAL_P(key)) : zend_symtable_find(Z_ARRVAL_P(array), Z_STR_P(key));
		if (UNEXPECTED(value == NULL)) {
			if (Z_TYPE_P(key) == IS_LONG) {
				zend_error(E_WARNING, "Undefined array key " ZEND_LONG_FMT, Z_LVAL_P(key));
			} else {
				zend_error(E_WARNING, "Undefined array key \"%s\"", ZSTR_VAL(Z_STR_P(key)));
			}
			return NULL;
		}
		ZVAL_DEREF(value);
		return value;
	}

	/* the foreach key of an entry as a PHP value (borrowed string) */
	static void entryKey(const zv::ArrayEntry &entry, zval &key)
	{
		zend_string *stringKey = entry.stringKeyOrNull();
		if (stringKey != NULL) {
			ZVAL_STR(&key, stringKey);
		} else {
			ZVAL_LONG(&key, (zend_long) entry.indexKey());
		}
	}

	/* array_merge($a, $b) of two lists of reasons */
	static zv::Val arrayMerge(zval *a, zval *b)
	{
		if (UNEXPECTED(Z_TYPE_P(a) != IS_ARRAY || Z_TYPE_P(b) != IS_ARRAY)) {
			zend_type_error("array_merge(): Argument #%d must be of type array, %s given", Z_TYPE_P(a) != IS_ARRAY ? 1 : 2, zend_zval_value_name(Z_TYPE_P(a) != IS_ARRAY ? a : b));
			return zv::Val();
		}
		zv::Arr merged = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(a)) + zend_hash_num_elements(Z_ARRVAL_P(b)));
		for (zval *source : { a, b }) {
			for (zv::ArrayEntry entry : zv::ArrRef(source)) {
				if (entry.hasStringKey()) {
					merged.set(entry.stringKey(), zv::Val::copyOf(entry.value()));
				} else {
					merged.push(entry.value());
				}
			}
		}
		return zv::Val(std::move(merged));
	}

	/* $type instanceof ConstantStringType && $type->getValue() === '';
	 * -1 = pending exception */
	static int isEmptyConstantString(zval *type)
	{
		if (!isA(type, pt_ce_constant_string_type)) return 0;
		return constantStringValueIsEmpty(type);
	}

	/* $constantString->getValue() === '' of a ConstantStringType; -1 =
	 * pending exception */
	static int constantStringValueIsEmpty(zval *constantString)
	{
		zv::Val value = constantStringValue(constantString);
		if (UNEXPECTED(value.isUndef())) return -1;
		return Z_TYPE_P(value.raw()) == IS_STRING && ZSTR_LEN(Z_STR_P(value.raw())) == 0 ? 1 : 0;
	}

	/* $constantString->getValue() */
	static zv::Val constantStringValue(zval *constantString)
	{
		if (EXPECTED(isA(constantString, pt_ce_constant_string_type))) return pt_constant_string_get_value(Z_OBJ_P(constantString));
		return callOn(constantString, mGetValue);
	}

	/* Strings::match($value, '#^\d+$#') !== null: ASCII digits, a final
	 * newline allowed before the end ($ without D) */
	static bool isDecimalDigits(zend_string *value)
	{
		size_t length = ZSTR_LEN(value);
		if (length > 0 && ZSTR_VAL(value)[length - 1] == '\n') length--;
		if (length == 0) return false;
		for (size_t i = 0; i < length; i++) {
			char c = ZSTR_VAL(value)[i];
			if (c < '0' || c > '9') return false;
		}
		return true;
	}

	/* $type instanceof ConstantIntegerType && $type->getValue() === 0; -1 =
	 * pending exception */
	static int isConstantIntegerZero(zval *type)
	{
		if (!isA(type, pt_ce_constant_integer_type)) return 0;
		zend_long value;
		if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(type), value))) return -1;
		return value == 0 ? 1 : 0;
	}

	/* $result = $a <op> $b for scalar operands (no release needed); false =
	 * pending exception */
	/* private static: a left shift that does not fit into an integer wraps
	 * around instead of turning into a float, so the shifted value no longer
	 * preserves the ordering of its operand — `($value << $shift) >> $shift
	 * !== $value` through the engine's operators; false = pending exception */
	[[nodiscard]] static bool shiftLeftOverflows(zend_long value, zval *shift, bool &out)
	{
		zval operand, shifted, restored;
		ZVAL_LONG(&operand, value);
		if (UNEXPECTED(!scalarOp(shift_left_function, &operand, shift, &shifted))) return false;
		if (UNEXPECTED(!scalarOp(shift_right_function, &shifted, shift, &restored))) return false;
		out = Z_TYPE(restored) != IS_LONG || Z_LVAL(restored) != value;
		return true;
	}

	static bool scalarOp(pt_ietr_binary_op fn, zval *a, zval *b, zval *result)
	{
		ZVAL_UNDEF(result);
		if (UNEXPECTED(fn(result, a, b) != SUCCESS || EG(exception) != NULL)) {
			zval_ptr_dtor(result);
			ZVAL_UNDEF(result);
			return false;
		}
		return true;
	}

	/* `$a === 0 || $b === 0 ? 0 : ($a ?? $aDefault) * ($b ?? $bDefault)` */
	static bool mulOrZero(zval *a, zval *b, zval *aDefault, zval *bDefault, zval *result)
	{
		if ((Z_TYPE_P(a) == IS_LONG && Z_LVAL_P(a) == 0) || (Z_TYPE_P(b) == IS_LONG && Z_LVAL_P(b) == 0)) {
			ZVAL_LONG(result, 0);
			return true;
		}
		return scalarOp(mul_function, Z_TYPE_P(a) != IS_NULL ? a : aDefault, Z_TYPE_P(b) != IS_NULL ? b : bDefault, result);
	}

	static void setNullableLong(zval *out, NullableLong value)
	{
		if (value.isNull) {
			ZVAL_NULL(out);
		} else {
			ZVAL_LONG(out, value.value);
		}
	}

	/* $typeResult->type */
	static zv::Val typeResultType(zval *result)
	{
		if (EXPECTED(Z_TYPE_P(result) == IS_OBJECT && Z_OBJCE_P(result) == pt_ce_type_result)) {
			static int32_t offset = -2;
			static zend_class_entry *offsetFor = NULL;
			if (UNEXPECTED(offsetFor != pt_ce_type_result)) {
				offsetFor = pt_ce_type_result;
				offset = pt_instance_prop_offset(pt_ce_type_result, PT_LC("type"));
			}
			if (EXPECTED(offset >= 0)) return zv::Val::copyOf(zv::Ref(OBJ_PROP(Z_OBJ_P(result), (uint32_t) offset)));
		}
		if (UNEXPECTED(Z_TYPE_P(result) != IS_OBJECT)) {
			zend_throw_error(NULL, "Attempt to read property \"type\" on %s", zend_zval_value_name(result));
			return zv::Val();
		}
		zval rv;
		ZVAL_UNDEF(&rv);
		zval *type = zend_read_property(Z_OBJCE_P(result), Z_OBJ_P(result), PT_LC("type"), 0, &rv);
		if (UNEXPECTED(EG(exception) != NULL)) {
			zval_ptr_dtor(&rv);
			return zv::Val();
		}
		zv::Val copy = zv::Val::copyOf(zv::Ref(type));
		zval_ptr_dtor(&rv);
		return copy;
	}

	/* }}} */

	/* {{{ getType()'s callback and node reads */

	/* the twin's `fn (Expr $expr): Type => $this->getType($expr, $context)` */
	struct GetTypeFrame
	{
		zend_object *self;
		zval *context;
	};

	static zv::Val getTypeCallbackBody(void *data, zval *expr)
	{
		GetTypeFrame *frame = static_cast<GetTypeFrame *>(data);
		return getTypeOfExpr(frame->self, frame->context, expr);
	}

	/* the closure as a PHP callable that outlives the call: a NativeClosure
	 * capturing $this and $context, like the twin's */
	static zv::Val getTypeCallbackCallable(void *data)
	{
		GetTypeFrame *frame = static_cast<GetTypeFrame *>(data);
		return pt_native_closure(&getTypeClosureBody, frame->self, frame->context);
	}

	/* fn (Expr $expr): Type => $this->getType($expr, $context) — captures:
	 * $this, $context */
	static void getTypeClosureBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 1)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Reflection\\InitializerExprTypeResolver::{closure}(), %u passed and exactly 1 expected", argc);
			return;
		}
		zval *expr = &argv[0];
		ZVAL_DEREF(expr);
		zv::Val type = getTypeOfExpr(Z_OBJ(captures[0]), &captures[1], expr);
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* the closure's body: the Expr parameter check, then getType() on a fresh
	 * C stack segment when the current one runs low */
	static zv::Val getTypeOfExpr(zend_object *self, zval *context, zval *expr)
	{
		zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
		if (UNEXPECTED(exprCe == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(expr) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(expr), exprCe))) {
			zend_type_error("PHPStan\\Reflection\\InitializerExprTypeResolver::{closure}(): Argument #1 ($expr) must be of type PhpParser\\Node\\Expr, %s given", zend_zval_value_name(expr));
			return zv::Val();
		}
		zv::Val type;
		pt_engine_with_stack([&]() { type = InitializerExprTypeResolver(self).getType(expr, context); });
		return type;
	}

	/* $expr instanceof Expr for a value handed to getType(); false = the
	 * engine's TypeError thrown */
	static bool requireExpr(zval *expr, const char *method, const char *parameter)
	{
		zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
		if (UNEXPECTED(exprCe == NULL)) return false;
		if (EXPECTED(Z_TYPE_P(expr) == IS_OBJECT && instanceof_function(Z_OBJCE_P(expr), exprCe))) return true;
		zend_type_error("PHPStan\\Reflection\\InitializerExprTypeResolver::%s(): Argument #1 ($%s) must be of type PhpParser\\Node\\Expr, %s given", method, parameter, zend_zval_value_name(expr));
		return false;
	}

	static zval *binaryLeft(zval *expr)
	{
		return nodeProp(pt_ietr_binary_op_left, expr);
	}

	static zval *binaryRight(zval *expr)
	{
		return nodeProp(pt_ietr_binary_op_right, expr);
	}

	/* }}} */

	/* {{{ reflection reads */

	/* $this->getReflectionProvider()->hasClass($className); false = pending
	 * exception */
	bool reflectionProviderHasClass(zval *className, bool &out) const
	{
		zv::Val reflectionProvider = getReflectionProvider();
		if (UNEXPECTED(reflectionProvider.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(reflectionProvider.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function hasClass() on %s", zend_zval_value_name(reflectionProvider.raw()));
			return false;
		}
		return pt_reflection_provider_has_class(Z_OBJ_P(reflectionProvider.raw()), className, out);
	}

	/* $this->getReflectionProvider()->getClass($className) */
	zv::Val reflectionProviderGetClass(zval *className) const
	{
		IETR_VAL(reflectionProvider, getReflectionProvider());
		if (UNEXPECTED(Z_TYPE_P(reflectionProvider.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getClass() on %s", zend_zval_value_name(reflectionProvider.raw()));
			return zv::Val();
		}
		return pt_reflection_provider_get_class(Z_OBJ_P(reflectionProvider.raw()), className);
	}

	/* $classReflection->getName() */
	static zv::Val classReflectionName(zval *classReflection)
	{
		if (EXPECTED(Z_TYPE_P(classReflection) == IS_OBJECT && Z_OBJCE_P(classReflection) == pt_ce_class_reflection)) return pt_class_reflection_get_name(Z_OBJ_P(classReflection));
		return callOn(classReflection, mGetName);
	}

	/* $classReflection->isFinal(); false = pending exception */
	static bool classReflectionIsFinal(zval *classReflection, bool &out)
	{
		if (EXPECTED(Z_TYPE_P(classReflection) == IS_OBJECT && Z_OBJCE_P(classReflection) == pt_ce_class_reflection)) return pt_class_reflection_is_final(Z_OBJ_P(classReflection), out);
		zv::Val result = callOn(classReflection, mIsFinal);
		if (UNEXPECTED(result.isUndef())) return false;
		out = zend_is_true(result.raw());
		return true;
	}

	/* $classReflection->isEnum(); false = pending exception */
	static bool classReflectionIsEnum(zval *classReflection, bool &out)
	{
		if (EXPECTED(Z_TYPE_P(classReflection) == IS_OBJECT && Z_OBJCE_P(classReflection) == pt_ce_class_reflection)) return pt_class_reflection_is_enum(Z_OBJ_P(classReflection), out);
		zv::Val result = callOn(classReflection, mIsEnum);
		if (UNEXPECTED(result.isUndef())) return false;
		out = zend_is_true(result.raw());
		return true;
	}

	/* }}} */

	/* {{{ more value helpers */

	/* $constantBooleanType->getValue(); false = pending exception */
	static bool constantBooleanValue(zval *type, bool &out)
	{
		if (EXPECTED(Z_OBJCE_P(type) == pt_ce_constant_boolean_type)) return pt_constant_boolean_type_value(Z_OBJ_P(type), out);
		zv::Val value = callOn(type, mGetValue);
		if (UNEXPECTED(value.isUndef())) return false;
		out = zend_is_true(value.raw());
		return true;
	}

	/* new ConstantStringType($value, $isClassString) of a PHP value (a
	 * non-string goes through the constructor's parameter check) */
	static zv::Val constantStringOf(zval *value, bool isClassString)
	{
		if (EXPECTED(Z_TYPE_P(value) == IS_STRING)) return newConstantStringType(Z_STR_P(value), isClassString);
		zval flag = {};
		ZVAL_BOOL(&flag, isClassString);
		zv::Args argv{value, &flag};
		return pt_type_new_ce(pt_ce_constant_string_type, 2, argv);
	}

	/* new ConstantStringType($value ?? '') */
	static zv::Val constantStringOrEmpty(zval *value)
	{
		if (Z_TYPE_P(value) == IS_NULL) return newConstantStringType(ZSTR_EMPTY_ALLOC());
		return constantStringOf(value, false);
	}

	/* new ObjectType($className) of a PHP value */
	static zv::Val objectTypeOf(zval *className)
	{
		if (EXPECTED(Z_TYPE_P(className) == IS_STRING)) return newObjectType(Z_STR_P(className));
		return pt_type_new_ce(pt_ce_object_type, 1, className);
	}

	/* $array[$key] ?? null of an int|string key (symtable semantics) */
	static zval *arrayKeyFind(zval *array, zval *key)
	{
		zval *value = NULL;
		if (Z_TYPE_P(key) == IS_LONG) {
			value = zend_hash_index_find(Z_ARRVAL_P(array), (zend_ulong) Z_LVAL_P(key));
		} else if (Z_TYPE_P(key) == IS_STRING) {
			value = zend_symtable_find(Z_ARRVAL_P(array), Z_STR_P(key));
		}
		if (value != NULL) ZVAL_DEREF(value);
		return value != NULL && Z_TYPE_P(value) != IS_NULL ? value : NULL;
	}

	/* $array[$key] = $value of an int|string key; false = the engine's
	 * TypeError for any other key */
	static bool arrayKeyUpdate(zv::Arr &array, zval *key, zv::Val value)
	{
		if (Z_TYPE_P(key) == IS_LONG) {
			array.separate();
			zval v = value.take();
			zend_hash_index_update(array.table(), (zend_ulong) Z_LVAL_P(key), &v);
			return true;
		}
		if (EXPECTED(Z_TYPE_P(key) == IS_STRING)) {
			array.set(Z_STR_P(key), std::move(value));
			return true;
		}
		zend_type_error("Cannot access offset of type %s on array", zend_zval_value_name(key));
		return false;
	}

	/* $this->$property[$key] = $value on one of the memo arrays */
	void writeArrayProperty(uint32_t slot, zend_string *key, zval *value) const
	{
		zval *property = prop(slot);
		ZVAL_DEREF(property);
		if (UNEXPECTED(Z_TYPE_P(property) != IS_ARRAY)) return;
		SEPARATE_ARRAY(property);
		zval copy;
		ZVAL_COPY(&copy, value);
		zend_symtable_update(Z_ARRVAL_P(property), key, &copy);
	}

	/* unset($this->$property[$key]) on one of the memo arrays */
	void unsetArrayProperty(uint32_t slot, zend_string *key) const
	{
		zval *property = prop(slot);
		ZVAL_DEREF(property);
		if (UNEXPECTED(Z_TYPE_P(property) != IS_ARRAY)) return;
		SEPARATE_ARRAY(property);
		zend_symtable_del(Z_ARRVAL_P(property), key);
	}

	/* }}} */
};

} // namespace phpstanturbo

using phpstanturbo::InitializerExprTypeResolver;
using phpstanturbo::IetrBinaryKind;

/* {{{ direct entries */

namespace {

inline bool isNativeResolver(zval *resolver)
{
	return Z_TYPE_P(resolver) == IS_OBJECT && Z_OBJCE_P(resolver) == pt_ce_initializer_expr_type_resolver;
}

/* $resolver->method(...$argv) of a PHP receiver (a prefixed differential's
 * twin), the lookup cached per site */
zv::Val callResolverMethod(pt_method_site &site, zval *resolver, const char *lcname, size_t len, uint32_t argc, zval *argv)
{
	if (UNEXPECTED(Z_TYPE_P(resolver) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", lcname, zend_zval_value_name(resolver));
		return zv::Val();
	}
	return pt_call_method_cached(site, Z_OBJ_P(resolver), lcname, len, argc, argv);
}

/* ->get<Operator>Type($left, $right, $callable) of a PHP receiver */
zv::Val callResolverOperatorMethod(pt_method_site &site, zval *resolver, const char *lcname, size_t len, zval *left, zval *right, const pt_ietr_get_type &getTypeCallback)
{
	IETR_VAL(callable, callbackCallable(getTypeCallback));
	zv::Args argv{left, right, callable.raw()};
	return callResolverMethod(site, resolver, lcname, len, 3, argv);
}

/* ->get<Operator>Type($expr, $callable) of a PHP receiver */
zv::Val callResolverUnaryMethod(pt_method_site &site, zval *resolver, const char *lcname, size_t len, zval *expr, const pt_ietr_get_type &getTypeCallback)
{
	IETR_VAL(callable, callbackCallable(getTypeCallback));
	zv::Args argv{expr, callable.raw()};
	return callResolverMethod(site, resolver, lcname, len, 2, argv);
}

} // namespace

zv::Val pt_initializer_expr_type_resolver_get_type(zval *resolver, zval *expr, zval *context)
{
	if (EXPECTED(isNativeResolver(resolver) && Z_TYPE_P(expr) == IS_OBJECT)) {
		return InitializerExprTypeResolver(Z_OBJ_P(resolver)).getType(expr, context);
	}
	static pt_method_site site;
	zv::Args argv{expr, context};
	return callResolverMethod(site, resolver, PT_LC("gettype"), 2, argv);
}

zv::Val pt_initializer_expr_type_resolver_get_binary_op_type(zval *resolver, pt_ietr_binary_operator op, zval *left, zval *right, const pt_ietr_get_type &getTypeCallback)
{
	if (EXPECTED(isNativeResolver(resolver))) {
		InitializerExprTypeResolver native(Z_OBJ_P(resolver));
		switch (op) {
			case PT_IETR_OP_CONCAT:
				return native.getConcatType(left, right, getTypeCallback);
			case PT_IETR_OP_BITWISE_AND:
				return native.getBitwiseType(phpstanturbo::IETR_BITWISE_AND, left, right, getTypeCallback);
			case PT_IETR_OP_BITWISE_OR:
				return native.getBitwiseType(phpstanturbo::IETR_BITWISE_OR, left, right, getTypeCallback);
			case PT_IETR_OP_BITWISE_XOR:
				return native.getBitwiseType(phpstanturbo::IETR_BITWISE_XOR, left, right, getTypeCallback);
			case PT_IETR_OP_SPACESHIP:
				return native.getSpaceshipType(left, right, getTypeCallback);
			case PT_IETR_OP_DIV:
				return native.getDivType(left, right, getTypeCallback);
			case PT_IETR_OP_MOD:
				return native.getModType(left, right, getTypeCallback);
			case PT_IETR_OP_PLUS:
				return native.getPlusType(left, right, getTypeCallback);
			case PT_IETR_OP_MINUS:
				return native.getMinusOrMulType(phpstanturbo::IETR_MINUS, left, right, getTypeCallback);
			case PT_IETR_OP_MUL:
				return native.getMinusOrMulType(phpstanturbo::IETR_MUL, left, right, getTypeCallback);
			case PT_IETR_OP_POW:
				return native.getPowType(left, right, getTypeCallback);
			case PT_IETR_OP_SHIFT_LEFT:
				return native.getShiftType(phpstanturbo::IETR_SHIFT_LEFT, left, right, getTypeCallback);
			case PT_IETR_OP_SHIFT_RIGHT:
				return native.getShiftType(phpstanturbo::IETR_SHIFT_RIGHT, left, right, getTypeCallback);
		}
		pt_throw_should_not_happen();
		return zv::Val();
	}

	static pt_method_site sites[PT_IETR_OP_SHIFT_RIGHT + 1];
	static constexpr struct
	{
		const char *lcname;
		size_t len;
	} names[PT_IETR_OP_SHIFT_RIGHT + 1] = {
		{ PT_LC("getconcattype") },
		{ PT_LC("getbitwiseandtype") },
		{ PT_LC("getbitwiseortype") },
		{ PT_LC("getbitwisexortype") },
		{ PT_LC("getspaceshiptype") },
		{ PT_LC("getdivtype") },
		{ PT_LC("getmodtype") },
		{ PT_LC("getplustype") },
		{ PT_LC("getminustype") },
		{ PT_LC("getmultype") },
		{ PT_LC("getpowtype") },
		{ PT_LC("getshiftlefttype") },
		{ PT_LC("getshiftrighttype") },
	};
	return callResolverOperatorMethod(sites[op], resolver, names[op].lcname, names[op].len, left, right, getTypeCallback);
}

zv::Val pt_initializer_expr_type_resolver_resolve_concat_type(zval *resolver, zval *left, zval *right)
{
	if (EXPECTED(isNativeResolver(resolver))) return InitializerExprTypeResolver(Z_OBJ_P(resolver)).resolveConcatType(left, right);
	static pt_method_site site;
	zv::Args argv{left, right};
	return callResolverMethod(site, resolver, PT_LC("resolveconcattype"), 2, argv);
}

zv::Val pt_initializer_expr_type_resolver_resolve_identical_type(zval *resolver, zval *leftType, zval *rightType)
{
	if (EXPECTED(isNativeResolver(resolver))) return InitializerExprTypeResolver(Z_OBJ_P(resolver)).resolveIdenticalType(leftType, rightType);
	static pt_method_site site;
	zv::Args argv{leftType, rightType};
	return callResolverMethod(site, resolver, PT_LC("resolveidenticaltype"), 2, argv);
}

zv::Val pt_initializer_expr_type_resolver_resolve_equal_type(zval *resolver, zval *leftType, zval *rightType)
{
	if (EXPECTED(isNativeResolver(resolver))) return InitializerExprTypeResolver(Z_OBJ_P(resolver)).resolveEqualType(leftType, rightType);
	static pt_method_site site;
	zv::Args argv{leftType, rightType};
	return callResolverMethod(site, resolver, PT_LC("resolveequaltype"), 2, argv);
}

zv::Val pt_initializer_expr_type_resolver_get_array_type(zval *resolver, zval *expr, const pt_ietr_get_type &getTypeCallback)
{
	if (EXPECTED(isNativeResolver(resolver))) return InitializerExprTypeResolver(Z_OBJ_P(resolver)).getArrayType(expr, getTypeCallback);
	static pt_method_site site;
	return callResolverUnaryMethod(site, resolver, PT_LC("getarraytype"), expr, getTypeCallback);
}

zv::Val pt_initializer_expr_type_resolver_get_cast_type(zval *resolver, zval *expr, const pt_ietr_get_type &getTypeCallback)
{
	if (EXPECTED(isNativeResolver(resolver))) return InitializerExprTypeResolver(Z_OBJ_P(resolver)).getCastType(expr, getTypeCallback);
	static pt_method_site site;
	return callResolverUnaryMethod(site, resolver, PT_LC("getcasttype"), expr, getTypeCallback);
}

zv::Val pt_initializer_expr_type_resolver_get_cast_object_type(zval *resolver, zval *exprType)
{
	if (EXPECTED(isNativeResolver(resolver))) return InitializerExprTypeResolver::getCastObjectType(exprType);
	static pt_method_site site;
	return callResolverMethod(site, resolver, PT_LC("getcastobjecttype"), 1, exprType);
}

zv::Val pt_initializer_expr_type_resolver_get_function_type(zval *resolver, zval *type, bool isNullable, bool isVariadic, zval *context)
{
	if (EXPECTED(isNativeResolver(resolver))) return InitializerExprTypeResolver(Z_OBJ_P(resolver)).getFunctionType(type, isNullable, isVariadic, context);
	static pt_method_site site;
	zv::Args argv{type, isNullable, isVariadic, context};
	return callResolverMethod(site, resolver, PT_LC("getfunctiontype"), 4, argv);
}

zv::Val pt_initializer_expr_type_resolver_get_first_class_callable_type(zval *resolver, zval *expr, zval *context, bool nativeTypesPromoted)
{
	if (EXPECTED(isNativeResolver(resolver))) return InitializerExprTypeResolver(Z_OBJ_P(resolver)).getFirstClassCallableType(expr, context, nativeTypesPromoted);
	static pt_method_site site;
	zv::Args argv{expr, context, nativeTypesPromoted};
	return callResolverMethod(site, resolver, PT_LC("getfirstclasscallabletype"), 3, argv);
}

zv::Val pt_initializer_expr_type_resolver_create_first_class_callable(zval *resolver, zval *function, zval *variants, bool nativeTypesPromoted)
{
	if (EXPECTED(isNativeResolver(resolver))) return InitializerExprTypeResolver::createFirstClassCallable(function, variants, nativeTypesPromoted);
	static pt_method_site site;
	zv::Args argv{function, variants, nativeTypesPromoted};
	return callResolverMethod(site, resolver, PT_LC("createfirstclasscallable"), 3, argv);
}

zv::Val pt_initializer_expr_type_resolver_get_class_const_fetch_type_by_reflection(zval *resolver, zval *class_, zval *constantName, zval *classReflection, const pt_ietr_get_type &getTypeCallback)
{
	if (EXPECTED(isNativeResolver(resolver) && Z_TYPE_P(constantName) == IS_STRING)) {
		return InitializerExprTypeResolver(Z_OBJ_P(resolver)).getClassConstFetchTypeByReflection(class_, constantName, classReflection, getTypeCallback);
	}
	static pt_method_site site;
	IETR_VAL(callable, callbackCallable(getTypeCallback));
	zv::Args argv{class_, constantName, classReflection, callable.raw()};
	return callResolverMethod(site, resolver, PT_LC("getclassconstfetchtypebyreflection"), 4, argv);
}

zv::Val pt_initializer_expr_type_resolver_get_unary_plus_type(zval *resolver, zval *expr, const pt_ietr_get_type &getTypeCallback)
{
	if (EXPECTED(isNativeResolver(resolver))) return InitializerExprTypeResolver(Z_OBJ_P(resolver)).getUnaryPlusType(expr, getTypeCallback);
	static pt_method_site site;
	return callResolverUnaryMethod(site, resolver, PT_LC("getunaryplustype"), expr, getTypeCallback);
}

zv::Val pt_initializer_expr_type_resolver_get_unary_minus_type(zval *resolver, zval *expr, const pt_ietr_get_type &getTypeCallback)
{
	if (EXPECTED(isNativeResolver(resolver))) return InitializerExprTypeResolver(Z_OBJ_P(resolver)).getUnaryMinusType(expr, getTypeCallback);
	static pt_method_site site;
	return callResolverUnaryMethod(site, resolver, PT_LC("getunaryminustype"), expr, getTypeCallback);
}

zv::Val pt_initializer_expr_type_resolver_get_bitwise_not_type(zval *resolver, zval *expr, const pt_ietr_get_type &getTypeCallback)
{
	if (EXPECTED(isNativeResolver(resolver))) return InitializerExprTypeResolver(Z_OBJ_P(resolver)).getBitwiseNotType(expr, getTypeCallback);
	static pt_method_site site;
	return callResolverUnaryMethod(site, resolver, PT_LC("getbitwisenottype"), expr, getTypeCallback);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

namespace {

#define IETR_THIS InitializerExprTypeResolver(Z_OBJ_P(ZEND_THIS))

/* the engine's TypeError of a class-typed parameter the twin declares (the
 * internal arginfo does not enforce it); false = thrown */
bool ietrArgClass(zval *value, uint32_t arg, int classIdx, const char *typeName, bool nullable = false)
{
	if (nullable && Z_TYPE_P(value) == IS_NULL) return true;
	zend_class_entry *ce = pt_class(classIdx);
	if (UNEXPECTED(ce == NULL)) return false;
	if (EXPECTED(Z_TYPE_P(value) == IS_OBJECT && instanceof_function(Z_OBJCE_P(value), ce))) return true;
	zend_argument_type_error(arg, "must be of type %s%s, %s given", nullable ? "?" : "", typeName, zend_zval_value_name(value));
	return false;
}

bool ietrArgType(zval *value, uint32_t arg)
{
	return ietrArgClass(value, arg, PT_CLASS_TYPE, "PHPStan\\Type\\Type");
}

bool ietrArgExpr(zval *value, uint32_t arg)
{
	return ietrArgClass(value, arg, PT_CLASS_EXPR, "PhpParser\\Node\\Expr");
}

bool ietrArgContext(zval *value, uint32_t arg)
{
	/* the final native class; under the prefixed activation of the
	 * differential tests the context factories hand out the PHP twin */
	if (EXPECTED(Z_TYPE_P(value) == IS_OBJECT && (Z_OBJCE_P(value) == pt_ce_initializer_expr_context || zend_string_equals_literal(Z_OBJCE_P(value)->name, "PHPStan\\Reflection\\InitializerExprContext")))) return true;
	zend_argument_type_error(arg, "must be of type PHPStan\\Reflection\\InitializerExprContext, %s given", zend_zval_value_name(value));
	return false;
}

bool ietrArgNativeClass(zval *value, uint32_t arg, zend_class_entry *ce, const char *typeName, bool nullable = false)
{
	if (nullable && Z_TYPE_P(value) == IS_NULL) return true;
	if (EXPECTED(Z_TYPE_P(value) == IS_OBJECT && instanceof_function(Z_OBJCE_P(value), ce))) return true;
	zend_argument_type_error(arg, "must be of type %s%s, %s given", nullable ? "?" : "", typeName, zend_zval_value_name(value));
	return false;
}

bool ietrArgCallable(zval *value, uint32_t arg)
{
	if (EXPECTED(zend_is_callable(value, 0, NULL))) return true;
	zend_argument_type_error(arg, "must be of type callable, %s given", zend_zval_value_name(value));
	return false;
}

/* the node class of a BinaryOp handed to the private math methods */
IetrBinaryKind binaryKindOf(zval *node, bool &ok)
{
	ok = true;
	static constexpr phpstanturbo::IetrBinaryKind kinds[] = {
		phpstanturbo::IETR_PLUS,
		phpstanturbo::IETR_MINUS,
		phpstanturbo::IETR_MUL,
		phpstanturbo::IETR_DIV,
		phpstanturbo::IETR_SHIFT_LEFT,
		phpstanturbo::IETR_SHIFT_RIGHT,
	};
	for (phpstanturbo::IetrBinaryKind kind : kinds) {
		int matches = isAClass(node, phpstanturbo::ietrBinaryKindClasses[kind]);
		if (UNEXPECTED(matches < 0)) {
			ok = false;
			return phpstanturbo::IETR_OTHER;
		}
		if (matches) return kind;
	}
	return phpstanturbo::IETR_OTHER;
}

/* the (Expr $left, Expr $right, callable $getTypeCallback) glue */
#define IETR_OPERATOR_GLUE(body) \
	[](INTERNAL_FUNCTION_PARAMETERS) { \
		zval *left, *right, *callable; \
		if (!zp::parse<zp::Obj, zp::Obj, zp::Zval>(execute_data, left, right, callable)) RETURN_THROWS(); \
		if (UNEXPECTED(!ietrArgExpr(left, 1) || !ietrArgExpr(right, 2) || !ietrArgCallable(callable, 3))) RETURN_THROWS(); \
		pt_ietr_get_type getTypeCallback = callableCallback(callable); \
		InitializerExprTypeResolver self = IETR_THIS; \
		PT_RETURN_VAL(body); \
	}

/* the (Expr $expr, callable $getTypeCallback) glue */
#define IETR_UNARY_GLUE(exprClassIdx, exprTypeName, body) \
	[](INTERNAL_FUNCTION_PARAMETERS) { \
		zval *expr, *callable; \
		if (!zp::parse<zp::Obj, zp::Zval>(execute_data, expr, callable)) RETURN_THROWS(); \
		if (UNEXPECTED(!ietrArgClass(expr, 1, exprClassIdx, exprTypeName) || !ietrArgCallable(callable, 2))) RETURN_THROWS(); \
		pt_ietr_get_type getTypeCallback = callableCallback(callable); \
		InitializerExprTypeResolver self = IETR_THIS; \
		PT_RETURN_VAL(body); \
	}

} // namespace

PT_MINIT_REGISTRATION(pt_register_initializer_expr_type_resolver)
{
	reg::Class cls("PHPStan\\Reflection\\InitializerExprTypeResolver");
	ptdecl::InitializerExprTypeResolver::declareClass(cls);
	cls.classConstantLong("CALCULATE_SCALARS_LIMIT", PT_INITIALIZER_EXPR_TYPE_RESOLVER_CALCULATE_SCALARS_LIMIT);
	cls.privateClassConstantLong("IS_SCALAR_TYPE", PT_IETR_IS_SCALAR_TYPE);
	cls.privateClassConstantLong("IS_UNKNOWN", PT_IETR_IS_UNKNOWN);
	ptdecl::InitializerExprTypeResolver::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *constantResolver, *reflectionProviderProvider, *phpVersion, *operatorTypeSpecifyingExtensionRegistry, *unaryOperatorTypeSpecifyingExtensionRegistry, *oversizedArrayBuilder;
		bool usePathConstantsAsConstantString;
		ZEND_PARSE_PARAMETERS_START(7, 7)
			Z_PARAM_OBJECT(constantResolver)
			Z_PARAM_OBJECT(reflectionProviderProvider)
			Z_PARAM_OBJECT(phpVersion)
			Z_PARAM_OBJECT(operatorTypeSpecifyingExtensionRegistry)
			Z_PARAM_OBJECT(unaryOperatorTypeSpecifyingExtensionRegistry)
			Z_PARAM_OBJECT(oversizedArrayBuilder)
			Z_PARAM_BOOL(usePathConstantsAsConstantString)
		ZEND_PARSE_PARAMETERS_END();
		zend_object *self = Z_OBJ_P(ZEND_THIS);
		pt_write_slot(self, slots::constantResolver, constantResolver);
		pt_write_slot(self, slots::reflectionProviderProvider, reflectionProviderProvider);
		pt_write_slot(self, slots::phpVersion, phpVersion);
		pt_write_slot(self, slots::operatorTypeSpecifyingExtensionRegistry, operatorTypeSpecifyingExtensionRegistry);
		pt_write_slot(self, slots::unaryOperatorTypeSpecifyingExtensionRegistry, unaryOperatorTypeSpecifyingExtensionRegistry);
		pt_write_slot(self, slots::oversizedArrayBuilder, oversizedArrayBuilder);
		zval flag = {};
		ZVAL_BOOL(&flag, usePathConstantsAsConstantString);
		pt_write_slot(self, slots::usePathConstantsAsConstantString, &flag);
	});

	cls.method(sigs::getType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *context;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, expr, context)) RETURN_THROWS();
		if (UNEXPECTED(!ietrArgExpr(expr, 1) || !ietrArgContext(context, 2))) RETURN_THROWS();
		PT_RETURN_VAL(IETR_THIS.getType(expr, context));
	});

	cls.method(sigs::getConcatType, IETR_OPERATOR_GLUE(self.getConcatType(left, right, getTypeCallback)));

	cls.method(sigs::resolveConcatType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *left, *right;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, left, right)) RETURN_THROWS();
		if (UNEXPECTED(!ietrArgType(left, 1) || !ietrArgType(right, 2))) RETURN_THROWS();
		PT_RETURN_VAL(IETR_THIS.resolveConcatType(left, right));
	});

	cls.method(sigs::getArrayType, IETR_UNARY_GLUE(PT_CLASS_ARRAY_EXPR, "PhpParser\\Node\\Expr\\Array_", self.getArrayType(expr, getTypeCallback)));
	cls.method(sigs::getCastType, IETR_UNARY_GLUE(PT_CLASS_CAST_EXPR, "PhpParser\\Node\\Expr\\Cast", self.getCastType(expr, getTypeCallback)));

	cls.method(sigs::getCastObjectType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *exprType;
		if (!zp::parse<zp::Obj>(execute_data, exprType)) RETURN_THROWS();
		if (UNEXPECTED(!ietrArgType(exprType, 1))) RETURN_THROWS();
		PT_RETURN_VAL(InitializerExprTypeResolver::getCastObjectType(exprType));
	});

	cls.method(sigs::getFunctionType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *context;
		bool isNullable, isVariadic;
		if (!zp::parse<zp::Zval, zp::Bool, zp::Bool, zp::Obj>(execute_data, type, isNullable, isVariadic, context)) RETURN_THROWS();
		if (UNEXPECTED(!ietrArgContext(context, 4))) RETURN_THROWS();
		PT_RETURN_VAL(IETR_THIS.getFunctionType(type, isNullable, isVariadic, context));
	});

	cls.method(sigs::isParameterValueNullable, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *parameter;
		if (!zp::parse<zp::Obj>(execute_data, parameter)) RETURN_THROWS();
		if (UNEXPECTED(!ietrArgClass(parameter, 1, PT_CLASS_PARAM, "PhpParser\\Node\\Param"))) RETURN_THROWS();
		int nullable = InitializerExprTypeResolver::isParameterValueNullable(parameter);
		if (UNEXPECTED(nullable < 0)) RETURN_THROWS();
		RETURN_BOOL(nullable == 1);
	});

	cls.method(sigs::getFirstClassCallableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *context;
		bool nativeTypesPromoted;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Bool>(execute_data, expr, context, nativeTypesPromoted)) RETURN_THROWS();
		if (UNEXPECTED(!ietrArgClass(expr, 1, PT_CLASS_CALL_LIKE, "PhpParser\\Node\\Expr\\CallLike") || !ietrArgContext(context, 2))) RETURN_THROWS();
		PT_RETURN_VAL(IETR_THIS.getFirstClassCallableType(expr, context, nativeTypesPromoted));
	});

	cls.method(sigs::createFirstClassCallable, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *function, *variants;
		bool nativeTypesPromoted;
		if (!zp::parse<zp::ObjOrNull, zp::Arr, zp::Bool>(execute_data, function, variants, nativeTypesPromoted)) RETURN_THROWS();
		zval nullFunction;
		if (function == NULL) {
			ZVAL_NULL(&nullFunction);
			function = &nullFunction;
		} else {
			zend_class_entry *functionReflectionCe = pt_class(PT_CLASS_FUNCTION_REFLECTION);
			zend_class_entry *methodReflectionCe = pt_class(PT_CLASS_EXTENDED_METHOD_REFLECTION);
			if (UNEXPECTED(functionReflectionCe == NULL || methodReflectionCe == NULL)) RETURN_THROWS();
			if (UNEXPECTED(!instanceof_function(Z_OBJCE_P(function), functionReflectionCe) && !instanceof_function(Z_OBJCE_P(function), methodReflectionCe))) {
				zend_argument_type_error(1, "must be of type PHPStan\\Reflection\\FunctionReflection|PHPStan\\Reflection\\ExtendedMethodReflection|null, %s given", zend_zval_value_name(function));
				RETURN_THROWS();
			}
		}
		PT_RETURN_VAL(InitializerExprTypeResolver::createFirstClassCallable(function, variants, nativeTypesPromoted));
	});

	cls.method(sigs::getBitwiseAndType, IETR_OPERATOR_GLUE(self.getBitwiseType(phpstanturbo::IETR_BITWISE_AND, left, right, getTypeCallback)));
	cls.method(sigs::getBitwiseOrType, IETR_OPERATOR_GLUE(self.getBitwiseType(phpstanturbo::IETR_BITWISE_OR, left, right, getTypeCallback)));
	cls.method(sigs::getBitwiseXorType, IETR_OPERATOR_GLUE(self.getBitwiseType(phpstanturbo::IETR_BITWISE_XOR, left, right, getTypeCallback)));

	cls.method(sigs::getFiniteOrConstantScalarTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *leftType, *rightType, *operationCallable;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Zval>(execute_data, leftType, rightType, operationCallable)) RETURN_THROWS();
		if (UNEXPECTED(!ietrArgType(leftType, 1) || !ietrArgType(rightType, 2) || !ietrArgCallable(operationCallable, 3))) RETURN_THROWS();
		zv::Val result;
		zend_long code = InitializerExprTypeResolver::getFiniteOrConstantScalarTypesWith(leftType, rightType, NULL, operationCallable, result);
		if (UNEXPECTED(code < 0)) RETURN_THROWS();
		if (code == 0) PT_RETURN_VAL(std::move(result));
		RETURN_LONG(code);
	});

	cls.method(sigs::getSpaceshipType, IETR_OPERATOR_GLUE(self.getSpaceshipType(left, right, getTypeCallback)));
	cls.method(sigs::getDivType, IETR_OPERATOR_GLUE(self.getDivType(left, right, getTypeCallback)));

	cls.method(sigs::getDivTypeFromTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *left, *right, *leftType, *rightType;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, left, right, leftType, rightType)) RETURN_THROWS();
		if (UNEXPECTED(!ietrArgExpr(left, 1) || !ietrArgExpr(right, 2) || !ietrArgType(leftType, 3) || !ietrArgType(rightType, 4))) RETURN_THROWS();
		PT_RETURN_VAL(IETR_THIS.getDivTypeFromTypes(left, right, leftType, rightType));
	});

	cls.method(sigs::getModType, IETR_OPERATOR_GLUE(self.getModType(left, right, getTypeCallback)));
	cls.method(sigs::getPlusType, IETR_OPERATOR_GLUE(self.getPlusType(left, right, getTypeCallback)));
	cls.method(sigs::getMinusType, IETR_OPERATOR_GLUE(self.getMinusOrMulType(phpstanturbo::IETR_MINUS, left, right, getTypeCallback)));
	cls.method(sigs::getMulType, IETR_OPERATOR_GLUE(self.getMinusOrMulType(phpstanturbo::IETR_MUL, left, right, getTypeCallback)));
	cls.method(sigs::getPowType, IETR_OPERATOR_GLUE(self.getPowType(left, right, getTypeCallback)));
	cls.method(sigs::getShiftLeftType, IETR_OPERATOR_GLUE(self.getShiftType(phpstanturbo::IETR_SHIFT_LEFT, left, right, getTypeCallback)));
	cls.method(sigs::getShiftRightType, IETR_OPERATOR_GLUE(self.getShiftType(phpstanturbo::IETR_SHIFT_RIGHT, left, right, getTypeCallback)));

	cls.method(sigs::optimizeScalarType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		if (UNEXPECTED(!ietrArgType(type, 1))) RETURN_THROWS();
		PT_RETURN_VAL(InitializerExprTypeResolver::optimizeScalarType(type));
	});

	cls.method(sigs::getNonNegativeIntegerBounds, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		if (UNEXPECTED(!ietrArgType(type, 1))) RETURN_THROWS();
		PT_RETURN_VAL(InitializerExprTypeResolver::getNonNegativeIntegerBoundsValue(type));
	});

	cls.method(sigs::getMaxModuloMagnitude, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_long divisorMin, divisorMax;
		bool divisorMinIsNull, divisorMaxIsNull;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_LONG_OR_NULL(divisorMin, divisorMinIsNull)
			Z_PARAM_LONG_OR_NULL(divisorMax, divisorMaxIsNull)
		ZEND_PARSE_PARAMETERS_END();
		NullableLong magnitude = InitializerExprTypeResolver::getMaxModuloMagnitude(
			divisorMinIsNull ? NullableLong::null() : NullableLong::of(divisorMin),
			divisorMaxIsNull ? NullableLong::null() : NullableLong::of(divisorMax));
		PT_RETURN_VAL(magnitude.toVal());
	});

	cls.method(sigs::getIntegerBounds, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		if (UNEXPECTED(!ietrArgType(type, 1))) RETURN_THROWS();
		PT_RETURN_VAL(IETR_THIS.getIntegerBoundsValue(type));
	});

	cls.method(sigs::shiftLeftOverflows, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_long value, shift;
		if (!zp::parse<zp::Long, zp::Long>(execute_data, value, shift)) RETURN_THROWS();
		bool out = false;
		if (UNEXPECTED(!InitializerExprTypeResolver::shiftLeftOverflowsValue(value, shift, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.method(sigs::toIntBound, [](INTERNAL_FUNCTION_PARAMETERS) {
		double value;
		if (!zp::parse<zp::Double>(execute_data, value)) RETURN_THROWS();
		InitializerExprTypeResolver::toIntBound(value, return_value);
	});

	cls.method(sigs::computeBitwiseAndRange, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *leftNumberType, *rightNumberType;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, leftNumberType, rightNumberType)) RETURN_THROWS();
		if (UNEXPECTED(!ietrArgType(leftNumberType, 1) || !ietrArgType(rightNumberType, 2))) RETURN_THROWS();
		PT_RETURN_VAL(InitializerExprTypeResolver::computeBitwiseAndRange(leftNumberType, rightNumberType));
	});

	cls.method(sigs::computeBitwiseOrXorRange, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *leftNumberType, *rightNumberType;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, leftNumberType, rightNumberType)) RETURN_THROWS();
		if (UNEXPECTED(!ietrArgType(leftNumberType, 1) || !ietrArgType(rightNumberType, 2))) RETURN_THROWS();
		PT_RETURN_VAL(InitializerExprTypeResolver::computeBitwiseOrXorRange(leftNumberType, rightNumberType));
	});

	cls.method(sigs::allBitsMask, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_long value;
		if (!zp::parse<zp::Long>(execute_data, value)) RETURN_THROWS();
		RETURN_LONG(InitializerExprTypeResolver::allBitsMask(value));
	});

	cls.method(sigs::resolveIdenticalType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *leftType, *rightType;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, leftType, rightType)) RETURN_THROWS();
		if (UNEXPECTED(!ietrArgType(leftType, 1) || !ietrArgType(rightType, 2))) RETURN_THROWS();
		PT_RETURN_VAL(IETR_THIS.resolveIdenticalType(leftType, rightType));
	});

	cls.method(sigs::resolveEqualType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *leftType, *rightType;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, leftType, rightType)) RETURN_THROWS();
		if (UNEXPECTED(!ietrArgType(leftType, 1) || !ietrArgType(rightType, 2))) RETURN_THROWS();
		PT_RETURN_VAL(IETR_THIS.resolveEqualType(leftType, rightType));
	});

	cls.method(sigs::resolveConstantArrayTypeComparison, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *leftType, *rightType, *valueComparisonCallback;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Zval>(execute_data, leftType, rightType, valueComparisonCallback)) RETURN_THROWS();
		if (UNEXPECTED(
			!ietrArgNativeClass(leftType, 1, pt_ce_constant_array_type, "PHPStan\\Type\\Constant\\ConstantArrayType")
			|| !ietrArgNativeClass(rightType, 2, pt_ce_constant_array_type, "PHPStan\\Type\\Constant\\ConstantArrayType")
			|| !ietrArgCallable(valueComparisonCallback, 3)
		)) RETURN_THROWS();
		PT_RETURN_VAL(IETR_THIS.resolveConstantArrayTypeComparison(leftType, rightType, true, valueComparisonCallback));
	});

	cls.method(sigs::resolveCommonMath, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *leftType, *rightType;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, expr, leftType, rightType)) RETURN_THROWS();
		if (UNEXPECTED(!ietrArgClass(expr, 1, PT_CLASS_BINARY_OP_EXPR, "PhpParser\\Node\\Expr\\BinaryOp") || !ietrArgType(leftType, 2) || !ietrArgType(rightType, 3))) RETURN_THROWS();
		bool ok;
		IetrBinaryKind kind = binaryKindOf(expr, ok);
		if (UNEXPECTED(!ok)) RETURN_THROWS();
		PT_RETURN_VAL(IETR_THIS.resolveCommonMath(kind, NULL, NULL, expr, leftType, rightType));
	});

	cls.method(sigs::integerRangeMath, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *range, *node, *operand;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, range, node, operand)) RETURN_THROWS();
		if (UNEXPECTED(!ietrArgType(range, 1) || !ietrArgClass(node, 2, PT_CLASS_BINARY_OP_EXPR, "PhpParser\\Node\\Expr\\BinaryOp") || !ietrArgType(operand, 3))) RETURN_THROWS();
		bool ok;
		IetrBinaryKind kind = binaryKindOf(node, ok);
		if (UNEXPECTED(!ok)) RETURN_THROWS();
		PT_RETURN_VAL(IETR_THIS.integerRangeMath(range, kind, operand));
	});

	cls.method(sigs::getClassConstFetchTypeByReflection, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *class_, *classReflection, *callable;
		zend_string *constantName;
		if (!zp::parse<zp::Obj, zp::Str, zp::ObjOrNull, zp::Zval>(execute_data, class_, constantName, classReflection, callable)) RETURN_THROWS();
		if (UNEXPECTED(!ietrArgCallable(callable, 4))) RETURN_THROWS();
		zval nullReflection;
		if (classReflection == NULL) {
			ZVAL_NULL(&nullReflection);
			classReflection = &nullReflection;
		} else if (UNEXPECTED(!ietrArgNativeClass(classReflection, 3, pt_ce_class_reflection, "PHPStan\\Reflection\\ClassReflection", true))) {
			RETURN_THROWS();
		}
		zval constantNameZv;
		ZVAL_STR(&constantNameZv, constantName);
		pt_ietr_get_type getTypeCallback = callableCallback(callable);
		PT_RETURN_VAL(IETR_THIS.getClassConstFetchTypeByReflection(class_, &constantNameZv, classReflection, getTypeCallback));
	});

	cls.method(sigs::getClassConstFetchType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *class_, *callable;
		zend_string *constantName, *className;
		if (!zp::parse<zp::Obj, zp::Str, zp::StrOrNull, zp::Zval>(execute_data, class_, constantName, className, callable)) RETURN_THROWS();
		if (UNEXPECTED(!ietrArgCallable(callable, 4))) RETURN_THROWS();
		zval constantNameZv, classNameZv;
		ZVAL_STR(&constantNameZv, constantName);
		if (className == NULL) {
			ZVAL_NULL(&classNameZv);
		} else {
			ZVAL_STR(&classNameZv, className);
		}
		pt_ietr_get_type getTypeCallback = callableCallback(callable);
		PT_RETURN_VAL(IETR_THIS.getClassConstFetchType(class_, &constantNameZv, &classNameZv, getTypeCallback));
	});

	cls.method(sigs::getUnaryPlusType, IETR_UNARY_GLUE(PT_CLASS_EXPR, "PhpParser\\Node\\Expr", self.getUnaryPlusType(expr, getTypeCallback)));
	cls.method(sigs::getUnaryMinusType, IETR_UNARY_GLUE(PT_CLASS_EXPR, "PhpParser\\Node\\Expr", self.getUnaryMinusType(expr, getTypeCallback)));

	cls.method(sigs::getUnaryMinusTypeFromType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *type;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, expr, type)) RETURN_THROWS();
		if (UNEXPECTED(!ietrArgExpr(expr, 1) || !ietrArgType(type, 2))) RETURN_THROWS();
		PT_RETURN_VAL(InitializerExprTypeResolver::getUnaryMinusTypeFromType(expr, type));
	});

	cls.method(sigs::getBitwiseNotType, IETR_UNARY_GLUE(PT_CLASS_EXPR, "PhpParser\\Node\\Expr", self.getBitwiseNotType(expr, getTypeCallback)));

	cls.method(sigs::getBitwiseNotTypeFromType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *exprType;
		if (!zp::parse<zp::Obj>(execute_data, exprType)) RETURN_THROWS();
		if (UNEXPECTED(!ietrArgType(exprType, 1))) RETURN_THROWS();
		PT_RETURN_VAL(InitializerExprTypeResolver::getBitwiseNotTypeFromType(exprType));
	});

	cls.method(sigs::resolveName, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *name, *classReflection;
		if (!zp::parse<zp::Obj, zp::ObjOrNull>(execute_data, name, classReflection)) RETURN_THROWS();
		zval nullReflection;
		if (classReflection == NULL) {
			ZVAL_NULL(&nullReflection);
			classReflection = &nullReflection;
		}
		if (UNEXPECTED(!ietrArgClass(name, 1, PT_CLASS_NAME, "PhpParser\\Node\\Name") || !ietrArgNativeClass(classReflection, 2, pt_ce_class_reflection, "PHPStan\\Reflection\\ClassReflection", true))) RETURN_THROWS();
		PT_RETURN_VAL(InitializerExprTypeResolver::resolveName(name, classReflection));
	});

	cls.method(sigs::resolveTypeByName, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *name, *classReflection;
		if (!zp::parse<zp::Obj, zp::ObjOrNull>(execute_data, name, classReflection)) RETURN_THROWS();
		zval nullReflection;
		if (classReflection == NULL) {
			ZVAL_NULL(&nullReflection);
			classReflection = &nullReflection;
		}
		if (UNEXPECTED(!ietrArgClass(name, 1, PT_CLASS_NAME, "PhpParser\\Node\\Name") || !ietrArgNativeClass(classReflection, 2, pt_ce_class_reflection, "PHPStan\\Reflection\\ClassReflection", true))) RETURN_THROWS();
		PT_RETURN_VAL(InitializerExprTypeResolver::resolveTypeByName(name, classReflection));
	});

	cls.method(sigs::resolveTypeByNameWithLateStaticBinding, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *class_, *classType, *methodReflectionCandidate;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, class_, classType, methodReflectionCandidate)) RETURN_THROWS();
		if (UNEXPECTED(!ietrArgClass(class_, 1, PT_CLASS_NAME, "PhpParser\\Node\\Name") || !ietrArgType(classType, 2) || !ietrArgClass(methodReflectionCandidate, 3, PT_CLASS_METHOD_REFLECTION, "PHPStan\\Reflection\\MethodReflection"))) RETURN_THROWS();
		PT_RETURN_VAL(InitializerExprTypeResolver::resolveTypeByNameWithLateStaticBinding(class_, classType, methodReflectionCandidate));
	});

	cls.method(sigs::getTypeFromValue, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *value;
		if (!zp::parse<zp::Zval>(execute_data, value)) RETURN_THROWS();
		PT_RETURN_VAL(InitializerExprTypeResolver::getTypeFromValue(value));
	});

	cls.method(sigs::getReflectionProvider, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(IETR_THIS.getReflectionProvider());
	});

	cls.method(sigs::getNeverType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *leftType, *rightType;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, leftType, rightType)) RETURN_THROWS();
		if (UNEXPECTED(!ietrArgType(leftType, 1) || !ietrArgType(rightType, 2))) RETURN_THROWS();
		PT_RETURN_VAL(InitializerExprTypeResolver::getNeverType(leftType, rightType));
	});

	cls.shadow(&pt_ce_initializer_expr_type_resolver);
}

#undef IETR_OPERATOR_GLUE
#undef IETR_UNARY_GLUE
#undef IETR_THIS

/* }}} */

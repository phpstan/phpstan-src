/*
 * The Type helpers shared by InitializerExprTypeResolver.cpp and
 * OperatorTypeHelper.cpp: calling Type methods, constructing the native
 * types, and the PHP value arithmetic of the twins' operators on the
 * engine's own operator functions.
 */

#ifndef PT_IETR_SUPPORT_H
#define PT_IETR_SUPPORT_H

#include "support.h"
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"

#include <cmath>
#include <limits>

namespace phpstanturbo::ietr {

/* InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT and
 * OperatorTypeHelper's private IS_* codes */
constexpr zend_long PT_IETR_CALCULATE_SCALARS_LIMIT = PT_INITIALIZER_EXPR_TYPE_RESOLVER_CALCULATE_SCALARS_LIMIT;
constexpr zend_long PT_IETR_IS_SCALAR_TYPE = 1;
constexpr zend_long PT_IETR_IS_UNKNOWN = 2;

#define IETR_VAL(name, expr) \
	zv::Val name = (expr); \
	if (UNEXPECTED(name.isUndef())) return zv::Val()

#define IETR_TRI(name, expr) \
	zend_long name = (expr); \
	if (UNEXPECTED(name < 0)) return zv::Val()

/* the binary operators whose type the resolver and the helper compute */
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

inline constexpr Method mToNumber{"toNumber"};
inline constexpr Method mToString{"toString"};
inline constexpr Method mToInteger{"toInteger"};
inline constexpr Method mToBoolean{"toBoolean"};
inline constexpr Method mToFloat{"toFloat"};
inline constexpr Method mToArray{"toArray"};
inline constexpr Method mToBitwiseNotType{"toBitwiseNotType"};
inline constexpr Method mToBooleanType{"toBooleanType"};
inline constexpr Method mToClassConstantType{"toClassConstantType"};
inline constexpr Method mGetConstantScalarTypes{"getConstantScalarTypes"};
inline constexpr Method mGetConstantScalarValues{"getConstantScalarValues", PT_OP_GET_CONSTANT_SCALAR_VALUES};
inline constexpr Method mGetFiniteTypes{"getFiniteTypes"};
inline constexpr Method mGetConstantStrings{"getConstantStrings"};
inline constexpr Method mIsDecimalIntegerString{"isDecimalIntegerString"};
inline constexpr Method mGetConstantArrays{"getConstantArrays", PT_OP_GET_CONSTANT_ARRAYS};
inline constexpr Method mIsInteger{"isInteger", PT_OP_IS_INTEGER};
inline constexpr Method mIsString{"isString", PT_OP_IS_STRING};
inline constexpr Method mIsFloat{"isFloat", PT_OP_IS_FLOAT};
inline constexpr Method mIsNull{"isNull", PT_OP_IS_NULL};
inline constexpr Method mIsArray{"isArray", PT_OP_IS_ARRAY};
inline constexpr Method mIsList{"isList", PT_OP_IS_LIST};
inline constexpr Method mIsVoid{"isVoid", PT_OP_IS_VOID};
inline constexpr Method mIsObject{"isObject"};
inline constexpr Method mIsEnum{"isEnum"};
inline constexpr Method mIsTrue{"isTrue"};
inline constexpr Method mIsFalse{"isFalse"};
inline constexpr Method mIsConstantArray{"isConstantArray", PT_OP_IS_CONSTANT_ARRAY};
inline constexpr Method mIsConstantScalarValue{"isConstantScalarValue", PT_OP_IS_CONSTANT_SCALAR_VALUE};
inline constexpr Method mIsClassString{"isClassString"};
inline constexpr Method mIsIterableAtLeastOnce{"isIterableAtLeastOnce", PT_OP_IS_ITERABLE_AT_LEAST_ONCE};
inline constexpr Method mIsNonEmptyString{"isNonEmptyString"};
inline constexpr Method mIsNonFalsyString{"isNonFalsyString"};
inline constexpr Method mIsLiteralString{"isLiteralString"};
inline constexpr Method mIsLowercaseString{"isLowercaseString"};
inline constexpr Method mIsUppercaseString{"isUppercaseString"};
inline constexpr Method mIsNumericString{"isNumericString"};
inline constexpr Method mIsUnsealed{"isUnsealed", PT_OP_IS_UNSEALED};
inline constexpr Method mGetUnsealedTypes{"getUnsealedTypes"};
inline constexpr Method mGetIterableKeyType{"getIterableKeyType", PT_OP_GET_ITERABLE_KEY_TYPE};
inline constexpr Method mGetIterableValueType{"getIterableValueType", PT_OP_GET_ITERABLE_VALUE_TYPE};
inline constexpr Method mIsSuperTypeOf{"isSuperTypeOf", PT_OP_IS_SUPER_TYPE_OF};
inline constexpr Method mEquals{"equals", PT_OP_EQUALS};
inline constexpr Method mGetValue{"getValue", PT_OP_GET_VALUE};
inline constexpr Method mGetTypes{"getTypes", PT_OP_GET_TYPES};
inline constexpr Method mGetKeyTypes{"getKeyTypes", PT_OP_GET_KEY_TYPES};
inline constexpr Method mGetValueTypes{"getValueTypes", PT_OP_GET_VALUE_TYPES};
inline constexpr Method mIsOptionalKey{"isOptionalKey"};
inline constexpr Method mHasOffsetValueType{"hasOffsetValueType", PT_OP_HAS_OFFSET_VALUE_TYPE};
inline constexpr Method mGetOffsetValueType{"getOffsetValueType", PT_OP_GET_OFFSET_VALUE_TYPE};
inline constexpr Method mAppend{"append"};
inline constexpr Method mLooseCompare{"looseCompare"};
inline constexpr Method mGetReasons{"getReasons"};
inline constexpr Method mIsSmallerThan{"isSmallerThan"};
inline constexpr Method mIsSmallerThanOrEqual{"isSmallerThanOrEqual"};
inline constexpr Method mGetValueType{"getValueType"};
inline constexpr Method mGetParentClass{"getParentClass", PT_OP_GET_PARENT_CLASS};
inline constexpr Method mGetName{"getName", PT_OP_GET_NAME};
inline constexpr Method mIsFinal{"isFinal", PT_OP_IS_FINAL};
inline constexpr Method mHasConstant{"hasConstant"};
inline constexpr Method mHasEnumCase{"hasEnumCase"};
inline constexpr Method mGetNativeReflection{"getNativeReflection"};
inline constexpr Method mGetReflectionConstant{"getReflectionConstant"};
inline constexpr Method mGetDeclaringClass{"getDeclaringClass"};
inline constexpr Method mGetFileName{"getFileName", PT_OP_GET_FILE_NAME};
inline constexpr Method mGetValueExpression{"getValueExpression"};
inline constexpr Method mGetType{"getType", PT_OP_GET_TYPE};
inline constexpr Method mGetConstantPhpDocType{"getConstantPhpDocType"};
inline constexpr Method mGetConstant{"getConstant"};
inline constexpr Method mHasPhpDocType{"hasPhpDocType"};
inline constexpr Method mHasNativeType{"hasNativeType"};
inline constexpr Method mGetValueExpr{"getValueExpr"};
inline constexpr Method mGetNativeType{"getNativeType"};
inline constexpr Method mGetVariants{"getVariants"};
inline constexpr Method mGetReturnType{"getReturnType"};
inline constexpr Method mGetNativeReturnType{"getNativeReturnType"};
inline constexpr Method mGetTemplateTypeMap{"getTemplateTypeMap", PT_OP_GET_TEMPLATE_TYPE_MAP};
inline constexpr Method mGetBound{"getBound"};
inline constexpr Method mGetDefault{"getDefault"};
inline constexpr Method mGetVariance{"getVariance"};
inline constexpr Method mGetThrowPoints{"getThrowPoints"};
inline constexpr Method mGetImpurePoints{"getImpurePoints"};
inline constexpr Method mAcceptsNamedArguments{"acceptsNamedArguments"};
inline constexpr Method mMustUseReturnValue{"mustUseReturnValue"};
inline constexpr Method mIsStaticClosure{"isStaticClosure"};
inline constexpr Method mGetThrowType{"getThrowType"};
inline constexpr Method mGetParameters{"getParameters"};
inline constexpr Method mGetAsserts{"getAsserts"};
inline constexpr Method mIsVariadic{"isVariadic", PT_OP_IS_VARIADIC};
inline constexpr Method mGetResolvedTemplateTypeMap{"getResolvedTemplateTypeMap"};
inline constexpr Method mGetCallSiteVarianceMap{"getCallSiteVarianceMap", PT_OP_GET_CALL_SITE_VARIANCE_MAP};
inline constexpr Method mHasMethod{"hasMethod", PT_OP_HAS_METHOD};
inline constexpr Method mGetMethod{"getMethod", PT_OP_GET_METHOD};
inline constexpr Method mIsStatic{"isStatic"};
inline constexpr Method mGetStaticObjectType{"getStaticObjectType"};
inline constexpr Method mChangeBaseClass{"changeBaseClass", PT_OP_CHANGE_BASE_CLASS};
inline constexpr Method mGetClassReflection{"getClassReflection", PT_OP_GET_CLASS_REFLECTION};
inline constexpr Method mGetObjectClassReflections{"getObjectClassReflections", PT_OP_GET_OBJECT_CLASS_REFLECTIONS};
inline constexpr Method mGetAncestorWithClassName{"getAncestorWithClassName", PT_OP_GET_ANCESTOR_WITH_CLASS_NAME};
inline constexpr Method mGetClassName{"getClassName"};
inline constexpr Method mGetClassStringObjectType{"getClassStringObjectType"};
inline constexpr Method mGetObjectClassNames{"getObjectClassNames", PT_OP_GET_OBJECT_CLASS_NAMES};
inline constexpr Method mHasInstanceProperty{"hasInstanceProperty", PT_OP_HAS_INSTANCE_PROPERTY};
inline constexpr Method mGetInstanceProperty{"getInstanceProperty", PT_OP_GET_INSTANCE_PROPERTY};
inline constexpr Method mGetReadableType{"getReadableType"};
inline constexpr Method mGetExprType{"getExprType"};
inline constexpr Method mGeneralize{"generalize"};
inline constexpr Method mGetStartLine{"getStartLine"};

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

inline zv::Val newErrorType()
{
	zval z;
	return adopted(pt_error_type_new(&z), z);
}

inline zv::Val newMixedType(bool isExplicitMixed = false)
{
	zval z;
	return adopted(pt_mixed_type_new(&z, isExplicitMixed), z);
}

inline zv::Val newNeverType()
{
	zval z;
	return adopted(pt_never_type_new(&z), z);
}

inline zv::Val newIntegerType()
{
	zval z;
	return adopted(pt_integer_type_new(&z), z);
}

inline zv::Val newFloatType()
{
	zval z;
	return adopted(pt_float_type_new(&z), z);
}

inline zv::Val newStringType()
{
	zval z;
	return adopted(pt_string_type_new(&z), z);
}

inline zv::Val newNullType()
{
	zval z;
	return adopted(pt_null_type_new(&z), z);
}

inline zv::Val newBooleanType()
{
	zval z;
	return adopted(pt_boolean_type_new(&z), z);
}

inline zv::Val newConstantBooleanType(bool value)
{
	zval z;
	return adopted(pt_constant_boolean_type_new(&z, value), z);
}

inline zv::Val newConstantIntegerType(zend_long value)
{
	zval z;
	return adopted(pt_constant_integer_type_new(&z, value), z);
}

inline zv::Val newConstantFloatType(double value)
{
	zval z;
	return adopted(pt_constant_float_type_new(&z, value), z);
}

inline zv::Val newConstantStringType(zend_string *value, bool isClassString = false)
{
	zval z;
	return adopted(pt_constant_string_type_new(&z, value, isClassString), z);
}

inline zv::Val newClassStringType()
{
	zval z;
	return adopted(pt_class_string_type_new(&z), z);
}

inline zv::Val newObjectType(zend_string *className)
{
	zval z;
	return adopted(pt_object_type_new(&z, className), z);
}

inline zv::Val newObjectTypeLiteral(const char *className, size_t len)
{
	zend_string *name = zend_string_init(className, len, 0);
	zv::Val type = newObjectType(name);
	zend_string_release(name);
	return type;
}

inline zv::Val newArrayType(zval *keyType, zval *itemType)
{
	zval z;
	return adopted(pt_array_type_new(&z, keyType, itemType), z);
}

inline zv::Val newUnionType(zv::Arr &types)
{
	zval z;
	return adopted(pt_union_type_new(&z, types.raw()), z);
}

inline zv::Val newBenevolentUnionType(zv::Arr &types)
{
	zval z;
	return adopted(pt_benevolent_union_type_new(&z, types.raw()), z);
}

inline zv::Val newIntersectionType(zv::Arr &types)
{
	zval z;
	return adopted(pt_intersection_type_new(&z, types.raw()), z);
}

/* new BenevolentUnionType([new IntegerType(), new FloatType()]) (the order
 * the twin spells: first, second) */
inline zv::Val newIntFloatUnion(bool benevolent, bool floatFirst = false)
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
inline zv::Val newTypeResult(zv::Val type)
{
	if (UNEXPECTED(type.isUndef())) return zv::Val();
	zval reasons;
	ZVAL_EMPTY_ARRAY(&reasons);
	return pt_type_result_new(type.raw(), &reasons);
}

/* TypeCombinator::union(...$types) of a list the resolver built */
inline zv::Val unionOf(zv::Arr &types)
{
	HashTable *ht = types.table();
	uint32_t count = zend_hash_num_elements(ht);
	if (count == 0) return pt_type_combinator_union(0, NULL);
	if (EXPECTED(HT_IS_PACKED(ht) && HT_IS_WITHOUT_HOLES(ht))) return pt_type_combinator_union(count, ht->arPacked);
	return pt_type_combinator_call_spread(PT_LC("union"), ht);
}

/* TypeCombinator::union($a, $b) */
inline zv::Val union2(zval *a, zval *b)
{
	zv::Args argv{a, b};
	return pt_type_combinator_union(2, argv);
}

/* ConstantTypeHelper::getTypeFromValue($value) */
inline zv::Val typeFromValue(zval *value)
{
	return pt_constant_type_helper_get_type_from_value(value);
}

/* IntegerRangeType::fromInterval($min, $max) of int|null zvals; a float (the
 * twin's arithmetic overflowed) raises the TypeError of the twin's
 * strict-types call */
inline zv::Val fromIntervalZv(zval *min, zval *max)
{
	bool minOk = Z_TYPE_P(min) == IS_LONG || Z_TYPE_P(min) == IS_NULL;
	bool maxOk = Z_TYPE_P(max) == IS_LONG || Z_TYPE_P(max) == IS_NULL;
	if (EXPECTED(minOk && maxOk)) return pt_integer_range_from_interval(NullableLong::from(min), NullableLong::from(max), 0);
	zend_type_error("PHPStan\\Type\\IntegerRangeType::fromInterval(): Argument #%d ($%s) must be of type ?int, %s given", minOk ? 2 : 1, minOk ? "max" : "min", zend_zval_value_name(minOk ? max : min));
	return zv::Val();
}

inline zv::Val fromInterval(NullableLong min, NullableLong max)
{
	return pt_integer_range_from_interval(min, max, 0);
}

/* TypeUtils::toBenevolentUnion($type) */
inline zv::Val toBenevolentUnion(zval *type)
{
	return pt_type_call_static_ce(pt_ce_type_utils, PT_LC("tobenevolentunion"), 1, type);
}

/* }}} */

/* {{{ PHP value arithmetic (the twin's operators, on the engine's functions) */

typedef zend_result (ZEND_FASTCALL *pt_ietr_binary_op)(zval *result, zval *op1, zval *op2);

/* $a <op> $b; UNDEF = pending exception */
inline zv::Val phpOp(pt_ietr_binary_op fn, zval *a, zval *b)
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
inline zv::Val callInternalFunction(zend_function *&cache, const char *name, size_t len, uint32_t argc, zval *argv)
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
inline zv::Val phpMinMax(bool max, uint32_t argc, zval *argv)
{
	static zend_function *minFn = NULL;
	static zend_function *maxFn = NULL;
	return max ? callInternalFunction(maxFn, PT_LC("max"), argc, argv) : callInternalFunction(minFn, PT_LC("min"), argc, argv);
}

/* dirname($path) */
inline zv::Val phpDirname(zval *path)
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

/* {{{ value helpers */

/* count() / foreach over a Type method's array result: the engine's
 * TypeError / warning for anything else; false = pending exception */
inline bool requireArray(zval *value)
{
	if (EXPECTED(Z_TYPE_P(value) == IS_ARRAY)) return true;
	zend_type_error("count(): Argument #1 ($value) must be of type Countable|array, %s given", zend_zval_value_name(value));
	return false;
}

inline bool requireCountable(zval *value)
{
	return requireArray(value);
}

/* $array[$index] of a list the twin indexes ($leftConstantArrays[0],
 * $existingUnsealed[0]); the twin reads only indexes it knows exist, so a
 * miss is a broken collaborator: ShouldNotHappenException */
inline zval *arrayIndex(zval *array, zend_ulong index)
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

/* the foreach key of an entry as a PHP value (borrowed string) */
inline void entryKey(const zv::ArrayEntry &entry, zval &key)
{
	zend_string *stringKey = entry.stringKeyOrNull();
	if (stringKey != NULL) {
		ZVAL_STR(&key, stringKey);
	} else {
		ZVAL_LONG(&key, (zend_long) entry.indexKey());
	}
}

/* $constantString->getValue() */
inline zv::Val constantStringValue(zval *constantString)
{
	if (EXPECTED(isA(constantString, pt_ce_constant_string_type))) return pt_constant_string_get_value(Z_OBJ_P(constantString));
	return callOn(constantString, mGetValue);
}

/* $constantString->getValue() === '' of a ConstantStringType; -1 =
 * pending exception */
inline int constantStringValueIsEmpty(zval *constantString)
{
	zv::Val value = constantStringValue(constantString);
	if (UNEXPECTED(value.isUndef())) return -1;
	return Z_TYPE_P(value.raw()) == IS_STRING && ZSTR_LEN(Z_STR_P(value.raw())) == 0 ? 1 : 0;
}

/* $type instanceof ConstantStringType && $type->getValue() === '';
 * -1 = pending exception */
inline int isEmptyConstantString(zval *type)
{
	if (!isA(type, pt_ce_constant_string_type)) return 0;
	return constantStringValueIsEmpty(type);
}

/* Strings::match($value, '#^\d+\z#') !== null: ASCII digits only */
inline bool isDecimalDigits(zend_string *value)
{
	size_t length = ZSTR_LEN(value);
	if (length == 0) return false;
	for (size_t i = 0; i < length; i++) {
		char c = ZSTR_VAL(value)[i];
		if (c < '0' || c > '9') return false;
	}
	return true;
}

/* Strings::match($value, '#^\d+(\.\d*)?([eE][+-]?\d+)?\z#') !== null:
 * digits, an optional fraction and an optional exponent, nothing else */
inline bool isNumberAfterInteger(zend_string *value)
{
	const char *c = ZSTR_VAL(value);
	const char *end = c + ZSTR_LEN(value);
	auto digits = [&c, end]() -> size_t {
		const char *start = c;
		while (c < end && *c >= '0' && *c <= '9') c++;
		return (size_t) (c - start);
	};
	if (digits() == 0) return false;
	if (c < end && *c == '.') {
		c++;
		digits();
	}
	if (c < end && (*c == 'e' || *c == 'E')) {
		c++;
		if (c < end && (*c == '+' || *c == '-')) c++;
		if (digits() == 0) return false;
	}
	return c == end;
}

/* $type instanceof ConstantIntegerType && $type->getValue() === 0; -1 =
 * pending exception */
inline int isConstantIntegerZero(zval *type)
{
	if (!isA(type, pt_ce_constant_integer_type)) return 0;
	zend_long value;
	if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(type), value))) return -1;
	return value == 0 ? 1 : 0;
}

/* $result = $a <op> $b for scalar operands (no release needed); false =
 * pending exception */
inline bool scalarOp(pt_ietr_binary_op fn, zval *a, zval *b, zval *result)
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
inline bool mulOrZero(zval *a, zval *b, zval *aDefault, zval *bDefault, zval *result)
{
	if ((Z_TYPE_P(a) == IS_LONG && Z_LVAL_P(a) == 0) || (Z_TYPE_P(b) == IS_LONG && Z_LVAL_P(b) == 0)) {
		ZVAL_LONG(result, 0);
		return true;
	}
	return scalarOp(mul_function, Z_TYPE_P(a) != IS_NULL ? a : aDefault, Z_TYPE_P(b) != IS_NULL ? b : bDefault, result);
}

inline void setNullableLong(zval *out, NullableLong value)
{
	if (value.isNull) {
		ZVAL_NULL(out);
	} else {
		ZVAL_LONG(out, value.value);
	}
}

/* }}} */

} // namespace phpstanturbo::ietr

#endif

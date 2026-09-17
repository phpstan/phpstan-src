/*
 * PHPStanTurbo\IdenticalNarrowingHelper — native implementation of
 * PHPStan\Analyser\ExprHandler\Helper\IdenticalNarrowingHelper.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. The public methods are exported as
 * pt_identical_narrowing_helper_* direct entries (support.h); the class is
 * final, so the entries take the native body for exactly the native class
 * entry and call the method by name on anything else.
 *
 * The twin's one closure — specifyEqual()'s identical-type callback — is a
 * native closure capturing what the arrow function captures ($this, the
 * evaluation scope, both operands and the NodeScopeResolver).
 *
 * DefaultNarrowingHelper, ExpressionResult, MutatingScope, ExprPrinter,
 * SpecifiedTypes, TypeSpecifierContext, TypeCombinator, the reflection
 * provider's memoized class lookups, CountNarrowingHelper and the Type
 * classes are called through their direct entries / ops; the collaborators
 * that stay PHP for now (RicherScopeGetTypeHelper, ClassReflection::asFinal())
 * through the cached method sites in the block below, one helper each.
 *
 * The twin evaluates `$a->unionWith($b)` receiver first, then argument; the
 * port sequences both into locals before combining them (C++ leaves the
 * order of argument evaluation unspecified, and a createTypesCallback may
 * fill memos).
 */

#include "support.h"
#include "generated/IdenticalNarrowingHelper.h"
#include "generated/TypeResult.h"

namespace slots = ptdecl::IdenticalNarrowingHelper::slot;
namespace sigs = ptdecl::IdenticalNarrowingHelper::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"

#include <cstring>

zend_class_entry *pt_ce_identical_narrowing_helper = nullptr;

namespace {

/* {{{ the PHP collaborators (one site each; switch to their direct entries
 * once they are ported) */

pt_method_site pt_inh_get_identical_result_site;
pt_method_site pt_inh_as_final_site;

/* the Error PHP raises for a method call on a non-object */
zv::Val callOnNonObject(const char *method, zval *value)
{
	zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(value));
	return zv::Val();
}

/* $countNarrowingHelper->specifyCountSize($countFuncCall, $type, $sizeType,
 * $context, $scope, $rootExpr) */
zv::Val specifyCountSize(zval *countNarrowingHelper, zval *countFuncCall, zval *type, zval *sizeType, zval *context, zval *scope, zval *rootExpr)
{
	return pt_count_narrowing_helper_specify_count_size(countNarrowingHelper, countFuncCall, type, sizeType, context, scope, rootExpr);
}

/* $richerScopeGetTypeHelper->getIdenticalResult($scope, $expr, $nodeScopeResolver) */
zv::Val getIdenticalResult(zval *richerScopeGetTypeHelper, zval *scope, zval *expr, zval *nodeScopeResolver)
{
	zv::Args argv{scope, expr, nodeScopeResolver};
	return pt_call_method_cached(pt_inh_get_identical_result_site, Z_OBJ_P(richerScopeGetTypeHelper), PT_LC("getidenticalresult"), 3, argv);
}

/* $classReflection->asFinal() */
zv::Val classReflectionAsFinal(zval *classReflection)
{
	if (UNEXPECTED(Z_TYPE_P(classReflection) != IS_OBJECT)) return callOnNonObject("asFinal", classReflection);
	return pt_call_method_cached(pt_inh_as_final_site, Z_OBJ_P(classReflection), PT_LC("asfinal"), 0, NULL);
}

/* $result->type of a TypeResult (the slot of the native class, the property
 * otherwise); UNDEF = pending exception */
zv::Val typeResultType(zval *result)
{
	if (EXPECTED(Z_TYPE_P(result) == IS_OBJECT && Z_OBJCE_P(result) == pt_ce_type_result)) return zv::Val::copyOf(zv::Ref(OBJ_PROP_NUM(Z_OBJ_P(result), ptdecl::TypeResult::slot::type)));
	if (UNEXPECTED(Z_TYPE_P(result) != IS_OBJECT)) {
		zend_throw_error(NULL, "Attempt to read property \"type\" on %s", zend_zval_value_name(result));
		return zv::Val();
	}
	zval rv;
	ZVAL_UNDEF(&rv);
	zval *type = zend_read_property(Z_OBJCE_P(result), Z_OBJ_P(result), PT_LC("type"), 0, &rv);
	if (UNEXPECTED(EG(exception))) {
		zval_ptr_dtor(&rv);
		return zv::Val();
	}
	if (type == &rv) return zv::Val::adopt(rv);
	return zv::Val::copyOf(zv::Ref(type));
}

/* }}} */

/* {{{ node shapes and properties */

pt_property_site pt_inh_expr_site;
pt_property_site pt_inh_name_site;
pt_property_site pt_inh_identifier_name_site;
pt_property_site pt_inh_class_site;
pt_property_site pt_inh_args_site;
pt_property_site pt_inh_arg_value_site;
pt_property_site pt_inh_int_value_site;
pt_property_site pt_inh_float_value_site;
pt_property_site pt_inh_string_value_site;

/* $value instanceof <class-map class> (the node classes the keys name always
 * resolve; a failure leaves its exception pending and answers false) */
inline bool isA(zval *value, int classIdx)
{
	if (Z_TYPE_P(value) != IS_OBJECT) return false;
	zend_class_entry *ce = pt_class(classIdx);
	return ce != NULL && instanceof_function(Z_OBJCE_P(value), ce);
}

/* a declared property of a node (dereferenced); an undefined zval when the
 * class declares no such property */
zval *nodeProp(pt_property_site &site, zval *node, const char *name, size_t len)
{
	static zval undefined;
	zval *slot = pt_property_cached(site, Z_OBJ_P(node), name, len);
	if (UNEXPECTED(slot == NULL)) {
		ZVAL_UNDEF(&undefined);
		return &undefined;
	}
	ZVAL_DEREF(slot);
	return slot;
}

inline zval *nameOf(zval *node) { return nodeProp(pt_inh_name_site, node, PT_LC("name")); }
inline zval *classOf(zval *node) { return nodeProp(pt_inh_class_site, node, PT_LC("class")); }

/* $x instanceof AlwaysRememberedExpr ? $x->getExpr() : $x */
zval *unwrap(zval *expr)
{
	if (!isA(expr, PT_CLASS_ALWAYS_REMEMBERED_EXPR)) return expr;
	zval *inner = nodeProp(pt_inh_expr_site, expr, PT_LC("expr"));
	return Z_TYPE_P(inner) == IS_OBJECT ? inner : expr;
}

/* the `name` string of an Identifier or Name (NULL when it is not one) */
zend_string *identifierString(zval *identifier)
{
	if (Z_TYPE_P(identifier) != IS_OBJECT) return NULL;
	zval *name = nodeProp(pt_inh_identifier_name_site, identifier, PT_LC("name"));
	return Z_TYPE_P(name) == IS_STRING ? Z_STR_P(name) : NULL;
}

/* $node->name->toLowerString() === $literal (the node's name an Identifier
 * or a Name) */
template <size_t N>
inline bool lowerNameIs(zend_string *name, const char (&literal)[N])
{
	return name != NULL && ZSTR_LEN(name) == N - 1 && zend_binary_strcasecmp(ZSTR_VAL(name), N - 1, literal, N - 1) == 0;
}

/* the ConstFetch literal a node is: 'null' / 'true' / 'false', or none */
enum ConstantLiteral { LITERAL_NONE, LITERAL_NULL, LITERAL_TRUE, LITERAL_FALSE };

ConstantLiteral constFetchLiteral(zval *expr)
{
	if (!isA(expr, PT_CLASS_CONST_FETCH)) return LITERAL_NONE;
	zend_string *name = identifierString(nameOf(expr));
	if (lowerNameIs(name, "null")) return LITERAL_NULL;
	if (lowerNameIs(name, "true")) return LITERAL_TRUE;
	if (lowerNameIs(name, "false")) return LITERAL_FALSE;
	return LITERAL_NONE;
}

/* the raw `args` of a call that is not a first-class callable (what
 * getArgs() returns once isFirstClassCallable() said no); borrowed, NULL
 * when unset */
HashTable *rawArgs(zval *call)
{
	zval *args = nodeProp(pt_inh_args_site, call, PT_LC("args"));
	return Z_TYPE_P(args) == IS_ARRAY ? Z_ARRVAL_P(args) : NULL;
}

/* $call->getArgs()[0]->value, the call known not to be a first-class
 * callable and to have that argument (borrowed) */
zval *firstArgValue(zval *call)
{
	HashTable *args = rawArgs(call);
	zval *arg = args != NULL ? zend_hash_index_find(args, 0) : NULL;
	if (arg == NULL) return &EG(uninitialized_zval);
	ZVAL_DEREF(arg);
	if (Z_TYPE_P(arg) != IS_OBJECT) return &EG(uninitialized_zval);
	return nodeProp(pt_inh_arg_value_site, arg, PT_LC("value"));
}

/* !$call->isFirstClassCallable() && isset($call->getArgs()[0]); false =
 * pending exception */
[[nodiscard]] bool hasFirstArg(zval *call, bool &out)
{
	bool firstClassCallable;
	if (UNEXPECTED(!pt_call_like_is_first_class_callable(Z_OBJ_P(call), firstClassCallable))) return false;
	if (firstClassCallable) {
		out = false;
		return true;
	}
	HashTable *args = rawArgs(call);
	zval *arg = args != NULL ? zend_hash_index_find(args, 0) : NULL;
	if (arg != NULL) {
		ZVAL_DEREF(arg);
	}
	out = arg != NULL && Z_TYPE_P(arg) != IS_NULL;
	return true;
}

/* }}} */

/* {{{ values */

inline zv::Val adoptOr(bool ok, zval &value)
{
	return ok ? zv::Val::adopt(value) : zv::Val();
}

inline zv::Val newNullType() { zval v; return adoptOr(pt_null_type_new(&v), v); }
inline zv::Val newNeverType() { zval v; return adoptOr(pt_never_type_new(&v), v); }
inline zv::Val newConstantBoolean(bool value) { zval v; return adoptOr(pt_constant_boolean_type_new(&v, value), v); }
inline zv::Val newConstantInteger(zend_long value) { zval v; return adoptOr(pt_constant_integer_type_new(&v, value), v); }
inline zv::Val newConstantFloat(double value) { zval v; return adoptOr(pt_constant_float_type_new(&v, value), v); }
inline zv::Val newConstantString(zend_string *value) { zval v; return adoptOr(pt_constant_string_type_new(&v, value), v); }
inline zv::Val newStringType() { zval v; return adoptOr(pt_string_type_new(&v), v); }
inline zv::Val newNonEmptyArrayType() { zval v; return adoptOr(pt_non_empty_array_type_new(&v), v); }
inline zv::Val newAccessoryNonEmptyString() { zval v; return adoptOr(pt_accessory_non_empty_string_type_new(&v), v); }
inline zv::Val newAccessoryNonFalsyString() { zval v; return adoptOr(pt_accessory_non_falsy_string_type_new(&v), v); }

/* new ConstantArrayType([], []) */
zv::Val newEmptyConstantArray()
{
	zval empty;
	ZVAL_EMPTY_ARRAY(&empty);
	zval v;
	return adoptOr(pt_constant_array_type_new(&v, &empty, &empty), v);
}

/* new UnionType($types) over a list built here */
zv::Val newUnion(zv::Arr &types)
{
	zval v;
	return adoptOr(pt_union_type_new(&v, types.raw()), v);
}

/* new ObjectType($className) / new ObjectType($className, classReflection: $reflection) */
zv::Val newObjectType(zend_string *className, zval *classReflection = NULL)
{
	zval v;
	return adoptOr(pt_object_type_new(&v, className, NULL, classReflection), v);
}

/* the twin's typeOnScope read: $result->getTypeOnScope($evaluationScope,
 * $evaluationScope->nativeTypesPromoted) */
zv::Val typeOnScope(zval *result, zval *evaluationScope)
{
	bool promoted;
	if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(evaluationScope), promoted))) return zv::Val();
	return pt_expression_result_get_type_on_scope(result, evaluationScope, promoted);
}

/* $type->method() of a TrinaryLogic-returning Type method: the PT_TRI_*
 * value (an op when there is one), -1 = pending exception */
inline zend_long trinaryOp(zval *type, pt_type_op_id op, const char *method)
{
	if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
		(void) callOnNonObject(method, type);
		return -1;
	}
	return pt_type_op_trinary(Z_OBJ_P(type), op, 0, NULL);
}

inline zend_long trinaryByName(zval *type, const char *method, const char *lcname, size_t len)
{
	if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
		(void) callOnNonObject(method, type);
		return -1;
	}
	return pt_type_call_trinary(Z_OBJ_P(type), lcname, len, 0, NULL);
}

#define PT_INH_IS_TRUE(type) trinaryByName(type, "isTrue", PT_LC("istrue"))
#define PT_INH_IS_FALSE(type) trinaryByName(type, "isFalse", PT_LC("isfalse"))

/* $type->isTrue()->yes() || $type->isFalse()->yes(), the isTrue() answer in
 * *isTrue; false = pending exception */
[[nodiscard]] bool isConstantBool(zval *type, bool &out, bool &isTrue)
{
	zend_long trueValue = PT_INH_IS_TRUE(type);
	if (UNEXPECTED(trueValue < 0)) return false;
	isTrue = trueValue == PT_TRI_YES;
	if (isTrue) {
		out = true;
		return true;
	}
	zend_long falseValue = PT_INH_IS_FALSE(type);
	if (UNEXPECTED(falseValue < 0)) return false;
	out = falseValue == PT_TRI_YES;
	return true;
}

/* $type->by-name call returning a value */
inline zv::Val typeCall(zval *type, const char *method, const char *lcname, size_t len)
{
	if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) return callOnNonObject(method, type);
	return pt_type_call(Z_OBJ_P(type), lcname, len, 0, NULL);
}

/* count($type->getFiniteTypes()); false = pending exception */
[[nodiscard]] bool finiteTypeCount(zval *type, zend_long &out)
{
	zv::Val finiteTypes = typeCall(type, "getFiniteTypes", PT_LC("getfinitetypes"));
	if (UNEXPECTED(finiteTypes.isUndef())) return false;
	if (UNEXPECTED(Z_TYPE_P(finiteTypes.raw()) != IS_ARRAY)) {
		zend_type_error("count(): Argument #1 ($value) must be of type Countable|array, %s given", zend_zval_value_name(finiteTypes.raw()));
		return false;
	}
	out = zend_hash_num_elements(Z_ARRVAL_P(finiteTypes.raw()));
	return true;
}

/* $type->getConstantStrings() */
inline zv::Val constantStringsOf(zval *type)
{
	zv::Val strings = typeCall(type, "getConstantStrings", PT_LC("getconstantstrings"));
	if (UNEXPECTED(!strings.isUndef() && Z_TYPE_P(strings.raw()) != IS_ARRAY)) {
		zend_type_error("count(): Argument #1 ($value) must be of type Countable|array, %s given", zend_zval_value_name(strings.raw()));
		return zv::Val();
	}
	return strings;
}

/* $constantStrings[0] of a list of ConstantStringTypes (borrowed; the
 * uninitialized zval when unset) */
zval *firstOf(zval *list)
{
	zval *first = zend_hash_index_find(Z_ARRVAL_P(list), 0);
	if (first == NULL) {
		zend_error(E_WARNING, "Undefined array key 0");
		return &EG(uninitialized_zval);
	}
	ZVAL_DEREF(first);
	return first;
}

/* $constantString->getValue() (an owned string) */
zv::Val constantStringValue(zval *constantString)
{
	if (UNEXPECTED(Z_TYPE_P(constantString) != IS_OBJECT)) return callOnNonObject("getValue", constantString);
	return pt_type_op(Z_OBJ_P(constantString), PT_OP_GET_VALUE, 0, NULL);
}

/* $a->isSuperTypeOf($b)->yes(); false = pending exception */
[[nodiscard]] bool isSuperTypeOfYes(zval *a, zval *b, bool &out)
{
	if (UNEXPECTED(Z_TYPE_P(a) != IS_OBJECT)) return !callOnNonObject("isSuperTypeOf", a).isUndef();
	zv::Val result = pt_type_op(Z_OBJ_P(a), PT_OP_IS_SUPER_TYPE_OF, 1, b);
	if (UNEXPECTED(result.isUndef())) return false;
	zend_long value = pt_type_result_trinary(result.raw());
	if (UNEXPECTED(value < 0)) return false;
	out = value == PT_TRI_YES;
	return true;
}

/* IntegerRangeType::fromInterval($min, $max)->isSuperTypeOf($type)->yes() */
[[nodiscard]] bool rangeContains(phpstanturbo::NullableLong min, phpstanturbo::NullableLong max, zval *type, bool &out)
{
	zv::Val range = pt_integer_range_from_interval(min, max, 0);
	if (UNEXPECTED(range.isUndef())) return false;
	return isSuperTypeOfYes(range.raw(), type, out);
}

/* (new ConstantIntegerType($value))->isSuperTypeOf($type)->yes() */
[[nodiscard]] bool constantIntegerContains(zend_long value, zval *type, bool &out)
{
	zv::Val constant = newConstantInteger(value);
	if (UNEXPECTED(constant.isUndef())) return false;
	return isSuperTypeOfYes(constant.raw(), type, out);
}

/* $a->unionWith($b) / ->intersectWith($b), both already evaluated */
inline zv::Val unionWith(zv::Val types, zv::Val other)
{
	if (UNEXPECTED(types.isUndef() || other.isUndef())) return zv::Val();
	return pt_specified_types_union_with(Z_OBJ_P(types.raw()), other.raw());
}

inline zv::Val intersectWith(zv::Val types, zv::Val other)
{
	if (UNEXPECTED(types.isUndef() || other.isUndef())) return zv::Val();
	return pt_specified_types_intersect_with(Z_OBJ_P(types.raw()), other.raw());
}

/* }}} */

/* {{{ TypeSpecifierContext */

[[nodiscard]] inline bool ctxNull(zval *context, bool &out) { return pt_type_specifier_context_null(Z_OBJ_P(context), out); }
[[nodiscard]] inline bool ctxTrue(zval *context, bool &out) { return pt_type_specifier_context_true(Z_OBJ_P(context), out); }
[[nodiscard]] inline bool ctxFalse(zval *context, bool &out) { return pt_type_specifier_context_false(Z_OBJ_P(context), out); }
[[nodiscard]] inline bool ctxTruthy(zval *context, bool &out) { return pt_type_specifier_context_truthy(Z_OBJ_P(context), out); }

/* a context singleton as an owned value */
inline zv::Val contextValue(zend_object *context)
{
	if (UNEXPECTED(context == NULL)) return zv::Val();
	zval value;
	ZVAL_OBJ_COPY(&value, context);
	return zv::Val::adopt(value);
}

/* $context->negate() */
inline zv::Val negate(zval *context)
{
	return pt_type_specifier_context_negate(Z_OBJ_P(context));
}

/* }}} */

/* the function families specifyFuncCallFamilies() recognizes by lowercase name */
bool nameIn(zend_string *name, std::initializer_list<const char *> names)
{
	for (const char *candidate : names) {
		size_t len = strlen(candidate);
		if (ZSTR_LEN(name) == len && zend_binary_strcasecmp(ZSTR_VAL(name), len, candidate, len) == 0) return true;
	}
	return false;
}

/* SpecifiedTypes|false|null as a zv::Val: the object, IS_FALSE, IS_NULL */
inline bool isFalseOutcome(zv::Val &value)
{
	return Z_TYPE_P(value.raw()) == IS_FALSE;
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\Helper\IdenticalNarrowingHelper;
 * UNDEF = pending exception, NULL zval arguments stand for null. */
class IdenticalNarrowingHelper
{
public:
	explicit IdenticalNarrowingHelper(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *defaultNarrowingHelper, zval *reflectionProvider, zval *countNarrowingHelper, zval *exprPrinter, zval *richerScopeGetTypeHelper)
	{
		zv::ObjRef object(self);
		object.propAtWrite(slots::defaultNarrowingHelper, zv::Val::copyOf(zv::Ref(defaultNarrowingHelper)));
		object.propAtWrite(slots::reflectionProvider, zv::Val::copyOf(zv::Ref(reflectionProvider)));
		object.propAtWrite(slots::countNarrowingHelper, zv::Val::copyOf(zv::Ref(countNarrowingHelper)));
		object.propAtWrite(slots::exprPrinter, zv::Val::copyOf(zv::Ref(exprPrinter)));
		object.propAtWrite(slots::richerScopeGetTypeHelper, zv::Val::copyOf(zv::Ref(richerScopeGetTypeHelper)));
	}

	/* Mirrors specifyIdentical(). */
	zv::Val specifyIdentical(zval *nodeScopeResolver, zval *left, zval *right, zval *leftResult, zval *rightResult, zval *context, zval *evaluationScope, zval *leftArgResult, zval *rightArgResult, zval *identicalTypeCallback) const
	{
		(void) nodeScopeResolver;
		bool isNull;
		if (UNEXPECTED(!ctxNull(context, isNull))) return zv::Val();
		if (isNull) return zv::Val::null();

		// slices 1+2 cover comparisons against a null/true/false literal;
		// everything else falls through to the scalar-literal slice below
		ConstantLiteral constantName = constFetchLiteral(left);
		zval *subject = right;
		zval *subjectResult = rightResult;
		if (constantName == LITERAL_NONE) {
			constantName = constFetchLiteral(right);
			subject = left;
			subjectResult = leftResult;
		}
		if (constantName == LITERAL_NONE) {
			if (UNEXPECTED(EG(exception))) return zv::Val();
			// a side whose TYPE is a constant bool (match (true) arms, bool
			// class constants) compares like the literal
			zval *unwrappedLeft = unwrap(left);
			zval *unwrappedRight = unwrap(right);
			zv::Val leftType = literalTypeOrResultType(unwrappedLeft, leftResult, evaluationScope);
			if (UNEXPECTED(leftType.isUndef())) return zv::Val();
			bool leftBool, leftTrue;
			if (UNEXPECTED(!isConstantBool(leftType.raw(), leftBool, leftTrue))) return zv::Val();
			if (leftBool && !isA(unwrappedRight, PT_CLASS_CONST_FETCH)) return specifyAgainstBool(right, rightResult, leftTrue, context, evaluationScope);
			zv::Val rightType = literalTypeOrResultType(unwrappedRight, rightResult, evaluationScope);
			if (UNEXPECTED(rightType.isUndef())) return zv::Val();
			bool rightBool, rightTrue;
			if (UNEXPECTED(!isConstantBool(rightType.raw(), rightBool, rightTrue))) return zv::Val();
			if (rightBool && !isA(unwrappedLeft, PT_CLASS_CONST_FETCH)) return specifyAgainstBool(left, leftResult, rightTrue, context, evaluationScope);
			if (UNEXPECTED(EG(exception))) return zv::Val();

			zv::Val types = specifyAgainstScalarLiteral(left, right, leftResult, rightResult, context, evaluationScope, leftArgResult, rightArgResult, identicalTypeCallback);
			if (UNEXPECTED(types.isUndef())) return zv::Val();
			if (!types.isNull()) return types;

			return specifyGeneral(left, right, leftResult, rightResult, context, evaluationScope, leftArgResult, rightArgResult, identicalTypeCallback);
		}

		if (constantName == LITERAL_NULL) {
			// deliberately NOT guarded by specifyDecidedComparison()
			zv::Val nullType = newNullType();
			if (UNEXPECTED(nullType.isUndef())) return zv::Val();
			return createSubjectTypes(evaluationScope, subject, subjectResult, nullType.raw(), context);
		}

		return specifyAgainstBool(subject, subjectResult, constantName == LITERAL_TRUE, context, evaluationScope);
	}

	/* Mirrors specifyEqual(). */
	zv::Val specifyEqual(zval *nodeScopeResolver, zval *left, zval *right, zval *leftResult, zval *rightResult, zval *context, zval *evaluationScope, zval *leftArgResult, zval *rightArgResult) const
	{
		bool isNull;
		if (UNEXPECTED(!ctxNull(context, isNull))) return zv::Val();
		if (isNull) return zv::Val::null();

		zv::Val identicalTypeCallback = pt_native_closure(&identicalTypeCallbackBody, self, evaluationScope, left, right, nodeScopeResolver);

		zval *unwrappedLeft = unwrap(left);
		zval *unwrappedRight = unwrap(right);
		zv::Val leftType = literalTypeOrResultType(unwrappedLeft, leftResult, evaluationScope);
		if (UNEXPECTED(leftType.isUndef())) return zv::Val();
		zv::Val rightType = literalTypeOrResultType(unwrappedRight, rightResult, evaluationScope);
		if (UNEXPECTED(rightType.isUndef())) return zv::Val();

		zv::Val leftScalarValues = pt_type_op(Z_OBJ_P(leftType.raw()), PT_OP_GET_CONSTANT_SCALAR_VALUES, 0, NULL);
		if (UNEXPECTED(leftScalarValues.isUndef())) return zv::Val();
		zv::Val rightScalarValues = pt_type_op(Z_OBJ_P(rightType.raw()), PT_OP_GET_CONSTANT_SCALAR_VALUES, 0, NULL);
		if (UNEXPECTED(rightScalarValues.isUndef())) return zv::Val();
		if (countOf(leftScalarValues.raw()) == 1 && !isA(unwrappedRight, PT_CLASS_CONST_FETCH)) {
			zv::Val constantSideTypes = specifyEqualAgainstConstantSide(nodeScopeResolver, left, right, leftResult, rightResult, right, rightResult, firstOf(leftScalarValues.raw()), leftType.raw(), rightType.raw(), context, evaluationScope, leftArgResult, rightArgResult, identicalTypeCallback.raw());
			if (UNEXPECTED(constantSideTypes.isUndef())) return zv::Val();
			if (!isFalseOutcome(constantSideTypes)) return constantSideTypes;
		} else if (countOf(rightScalarValues.raw()) == 1 && !isA(unwrappedLeft, PT_CLASS_CONST_FETCH)) {
			zv::Val constantSideTypes = specifyEqualAgainstConstantSide(nodeScopeResolver, left, right, leftResult, rightResult, left, leftResult, firstOf(rightScalarValues.raw()), rightType.raw(), leftType.raw(), context, evaluationScope, leftArgResult, rightArgResult, identicalTypeCallback.raw());
			if (UNEXPECTED(constantSideTypes.isUndef())) return zv::Val();
			if (!isFalseOutcome(constantSideTypes)) return constantSideTypes;
		}
		if (UNEXPECTED(EG(exception))) return zv::Val();

		// a side that coerces to a known bool compares the other side's
		// truthiness - the literal-bool identical narrowing composes it
		zv::Val leftBool = typeCall(leftType.raw(), "toBoolean", PT_LC("toboolean"));
		if (UNEXPECTED(leftBool.isUndef())) return zv::Val();
		bool leftIsConstantBool, leftBoolTrue;
		if (UNEXPECTED(!isConstantBool(leftBool.raw(), leftIsConstantBool, leftBoolTrue))) return zv::Val();
		if (leftIsConstantBool) {
			zend_long rightIsBoolean = trinaryOp(rightType.raw(), PT_OP_IS_BOOLEAN, "isBoolean");
			if (UNEXPECTED(rightIsBoolean < 0)) return zv::Val();
			if (rightIsBoolean == PT_TRI_YES) {
				zend_long trueAgain = PT_INH_IS_TRUE(leftBool.raw());
				if (UNEXPECTED(trueAgain < 0)) return zv::Val();
				zv::Val literal = newBoolConstFetch(trueAgain == PT_TRI_YES);
				if (UNEXPECTED(literal.isUndef())) return zv::Val();
				// the literal side of the delegation needs no result; the subject side is the right operand
				return specifyIdentical(nodeScopeResolver, literal.raw(), right, rightResult, rightResult, context, evaluationScope, leftArgResult, rightArgResult, identicalTypeCallback.raw());
			}
		}
		zv::Val rightBool = typeCall(rightType.raw(), "toBoolean", PT_LC("toboolean"));
		if (UNEXPECTED(rightBool.isUndef())) return zv::Val();
		bool rightIsConstantBool, rightBoolTrue;
		if (UNEXPECTED(!isConstantBool(rightBool.raw(), rightIsConstantBool, rightBoolTrue))) return zv::Val();
		if (rightIsConstantBool) {
			zend_long leftIsBoolean = trinaryOp(leftType.raw(), PT_OP_IS_BOOLEAN, "isBoolean");
			if (UNEXPECTED(leftIsBoolean < 0)) return zv::Val();
			if (leftIsBoolean == PT_TRI_YES) {
				zend_long trueAgain = PT_INH_IS_TRUE(rightBool.raw());
				if (UNEXPECTED(trueAgain < 0)) return zv::Val();
				zv::Val literal = newBoolConstFetch(trueAgain == PT_TRI_YES);
				if (UNEXPECTED(literal.isUndef())) return zv::Val();
				return specifyIdentical(nodeScopeResolver, left, literal.raw(), leftResult, leftResult, context, evaluationScope, leftArgResult, rightArgResult, identicalTypeCallback.raw());
			}
		}

		// an empty constant array equals only empty countables
		for (int side = 0; side < 2; side++) {
			zval *arrayType = side == 0 ? rightType.raw() : leftType.raw();
			zval *constantType = side == 0 ? leftType.raw() : rightType.raw();
			zend_long isArray = trinaryOp(arrayType, PT_OP_IS_ARRAY, "isArray");
			if (UNEXPECTED(isArray < 0)) return zv::Val();
			if (isArray != PT_TRI_YES) continue;
			zend_long isConstantArray = trinaryOp(constantType, PT_OP_IS_CONSTANT_ARRAY, "isConstantArray");
			if (UNEXPECTED(isConstantArray < 0)) return zv::Val();
			if (isConstantArray != PT_TRI_YES) continue;
			zend_long iterable = trinaryOp(constantType, PT_OP_IS_ITERABLE_AT_LEAST_ONCE, "isIterableAtLeastOnce");
			if (UNEXPECTED(iterable < 0)) return zv::Val();
			if (iterable != PT_TRI_NO) continue;

			zv::Val nonEmptyArray = newNonEmptyArrayType();
			if (UNEXPECTED(nonEmptyArray.isUndef())) return zv::Val();
			zv::Val negated = negate(context);
			if (UNEXPECTED(negated.isUndef())) return zv::Val();
			return side == 0
				? createSubjectTypes(evaluationScope, right, rightResult, nonEmptyArray.raw(), negated.raw())
				: createSubjectTypes(evaluationScope, left, leftResult, nonEmptyArray.raw(), negated.raw());
		}

		// same-type sides cannot coerce - loose equals strict
		bool sameKind;
		if (UNEXPECTED(!bothAre(leftType.raw(), rightType.raw(), sameKind))) return zv::Val();
		if (sameKind) return specifyIdentical(nodeScopeResolver, left, right, leftResult, rightResult, context, evaluationScope, leftArgResult, rightArgResult, identicalTypeCallback.raw());

		zv::Str leftExprString = print(left);
		if (UNEXPECTED(leftExprString.isNull())) return zv::Val();
		zv::Str rightExprString = print(right);
		if (UNEXPECTED(rightExprString.isNull())) return zv::Val();
		if (zend_string_equals(leftExprString.get(), rightExprString.get())) {
			if (!isA(left, PT_CLASS_VARIABLE) || !isA(right, PT_CLASS_VARIABLE)) return pt_specified_types_new();
		}

		zv::Val leftTypes = createSubjectTypes(evaluationScope, left, leftResult, leftType.raw(), context);
		if (UNEXPECTED(leftTypes.isUndef())) return zv::Val();
		zv::Val rightTypes = createSubjectTypes(evaluationScope, right, rightResult, rightType.raw(), context);
		if (UNEXPECTED(rightTypes.isUndef())) return zv::Val();

		bool isTrue;
		if (UNEXPECTED(!ctxTrue(context, isTrue))) return zv::Val();
		if (isTrue) return unionWith(std::move(leftTypes), std::move(rightTypes));
		zv::Val leftSure = toSureTypes(leftTypes.raw(), evaluationScope);
		if (UNEXPECTED(leftSure.isUndef())) return zv::Val();
		zv::Val rightSure = toSureTypes(rightTypes.raw(), evaluationScope);
		return intersectWith(std::move(leftSure), std::move(rightSure));
	}

	/* Mirrors specifyIdenticalAgainstType(). */
	zv::Val specifyIdenticalAgainstType(zval *subject, zval *subjectResult, zval *constantExpr, zval *constantType, zval *context, zval *evaluationScope, zval *subjectArgResult, zval *identicalTypeCallback) const
	{
		bool isNull;
		if (UNEXPECTED(!ctxNull(context, isNull))) return zv::Val();
		if (isNull) return zv::Val::null();

		zend_long constantIsNull = trinaryOp(constantType, PT_OP_IS_NULL, "isNull");
		if (UNEXPECTED(constantIsNull < 0)) return zv::Val();
		if (constantIsNull == PT_TRI_YES) {
			// unguarded like the null-literal slice
			zv::Val nullType = newNullType();
			if (UNEXPECTED(nullType.isUndef())) return zv::Val();
			return createSubjectTypes(evaluationScope, subject, subjectResult, nullType.raw(), context);
		}

		zval *unwrappedSubject = unwrap(subject);

		bool constantBool, constantTrue;
		if (UNEXPECTED(!isConstantBool(constantType, constantBool, constantTrue))) return zv::Val();
		if (constantBool) {
			zv::Val boolType = newConstantBoolean(constantTrue);
			if (UNEXPECTED(boolType.isUndef())) return zv::Val();
			zv::Val types = createSubjectTypes(evaluationScope, subject, subjectResult, boolType.raw(), context);
			if (UNEXPECTED(types.isUndef())) return zv::Val();
			bool isTrue;
			if (UNEXPECTED(!ctxTrue(context, isTrue))) return zv::Val();
			if (!isTrue && (isA(unwrappedSubject, PT_CLASS_NULLSAFE_METHOD_CALL) || isA(unwrappedSubject, PT_CLASS_NULLSAFE_PROPERTY_FETCH))) return types;
			if (UNEXPECTED(EG(exception))) return zv::Val();

			return unionWith(std::move(types), boolSpecifiedTypes(subjectResult, evaluationScope, constantTrue, isTrue));
		}

		if (isA(unwrappedSubject, PT_CLASS_FUNC_CALL)) {
			zv::Val familyTypes = specifyFuncCallFamilies(subject, subjectResult, unwrappedSubject, constantExpr, constantType, context, evaluationScope, subjectArgResult);
			if (UNEXPECTED(familyTypes.isUndef())) return zv::Val();
			if (familyTypes.isNull()) return familyTypes;
			if (!isFalseOutcome(familyTypes)) return familyTypes;
		} else if (isA(unwrappedSubject, PT_CLASS_CLASS_CONST_FETCH) && isA(classOf(unwrappedSubject), PT_CLASS_EXPR)) {
			return zv::Val::null();
		}
		if (UNEXPECTED(EG(exception))) return zv::Val();

		bool isFalse;
		if (UNEXPECTED(!ctxFalse(context, isFalse))) return zv::Val();
		if (isFalse) {
			zv::Val identicalType = pt_type_call_callable(identicalTypeCallback, 0, NULL);
			if (UNEXPECTED(identicalType.isUndef())) return zv::Val();
			bool decided, decidedTrue;
			if (UNEXPECTED(!isConstantBool(identicalType.raw(), decided, decidedTrue))) return zv::Val();
			if (decided) {
				zv::Val never = newNeverType();
				if (UNEXPECTED(never.isUndef())) return zv::Val();
				zv::Val contextForTypes = decidedTrue ? negate(context) : zv::Val::copyOf(zv::Ref(context));
				if (UNEXPECTED(contextForTypes.isUndef())) return zv::Val();

				zv::Val subjectTypes = createSubjectTypes(evaluationScope, subject, subjectResult, never.raw(), contextForTypes.raw());
				if (UNEXPECTED(subjectTypes.isUndef())) return zv::Val();
				return unionWith(std::move(subjectTypes), createForSubject(constantExpr, never.raw(), contextForTypes.raw(), evaluationScope));
			}
		}

		zv::Val types = createSubjectTypes(evaluationScope, subject, subjectResult, constantType, context);
		if (UNEXPECTED(types.isUndef())) return zv::Val();

		zv::Val subjectType = typeOnScope(subjectResult, evaluationScope);
		if (UNEXPECTED(subjectType.isUndef())) return zv::Val();
		zend_long finiteCount;
		if (UNEXPECTED(!finiteTypeCount(subjectType.raw(), finiteCount))) return zv::Val();
		if (finiteCount == 1) {
			types = unionWith(std::move(types), createForSubject(constantExpr, subjectType.raw(), context, evaluationScope));
		}

		return types;
	}

	/* Mirrors captureFirstArgResult(). */
	zv::Val captureFirstArgResult(zval *side, zval *storage) const
	{
		zval *unwrapped = unwrap(side);
		if (!isA(unwrapped, PT_CLASS_FUNC_CALL)) {
			if (UNEXPECTED(EG(exception))) return zv::Val();
			return zv::Val::null();
		}
		bool hasArg;
		if (UNEXPECTED(!hasFirstArg(unwrapped, hasArg))) return zv::Val();
		if (!hasArg) return zv::Val::null();

		zval *value = firstArgValue(unwrapped);
		if (UNEXPECTED(Z_TYPE_P(value) != IS_OBJECT)) {
			zend_type_error("PHPStan\\Analyser\\ExpressionResultStorage::findExpressionResult(): Argument #1 ($expr) must be of type PhpParser\\Node\\Expr, %s given", zend_zval_value_name(value));
			return zv::Val();
		}
		return pt_expression_result_storage_find(storage, value);
	}

private:
	zend_object *self;

	zval *slot(uint32_t index) const { return OBJ_PROP_NUM(self, index); }
	zval *defaultNarrowingHelper() const { return slot(slots::defaultNarrowingHelper); }

	/* {{{ DefaultNarrowingHelper (direct entries) */

	zv::Val createSubjectTypes(zval *s, zval *subject, zval *subjectResult, zval *type, zval *context) const
	{
		return pt_default_narrowing_helper_create_subject_types(defaultNarrowingHelper(), s, subject, subjectResult, type, context);
	}

	zv::Val createForSubject(zval *subject, zval *type, zval *context, zval *scope) const
	{
		return pt_default_narrowing_helper_create_for_subject(defaultNarrowingHelper(), subject, type, context, scope, NULL);
	}

	zv::Val toSureTypes(zval *types, zval *evaluationScope) const
	{
		return pt_default_narrowing_helper_to_sure_types(defaultNarrowingHelper(), types, evaluationScope);
	}

	/* }}} */

	/* $this->exprPrinter->printExpr($expr); NULL = pending exception */
	zv::Str print(zval *expr) const
	{
		if (UNEXPECTED(Z_TYPE_P(expr) != IS_OBJECT)) {
			zend_type_error("PHPStan\\Node\\Printer\\ExprPrinter::printExpr(): Argument #1 ($expr) must be of type PhpParser\\Node\\Expr, %s given", zend_zval_value_name(expr));
			return zv::Str();
		}
		return zv::Str::adopt(pt_expr_printer_print(slot(slots::exprPrinter), Z_OBJ_P(expr)));
	}

	/* $this->reflectionProvider->hasClass($className); false = pending exception */
	[[nodiscard]] bool hasClass(zval *className, bool &out) const
	{
		zval *provider = slot(slots::reflectionProvider);
		if (UNEXPECTED(Z_TYPE_P(provider) != IS_OBJECT)) return !callOnNonObject("hasClass", provider).isUndef();
		return pt_reflection_provider_has_class(Z_OBJ_P(provider), className, out);
	}

	/* new ObjectType($className, classReflection:
	 * $this->reflectionProvider->getClass($className)->asFinal()) */
	zv::Val finalObjectType(zval *className) const
	{
		zval *provider = slot(slots::reflectionProvider);
		if (UNEXPECTED(Z_TYPE_P(provider) != IS_OBJECT)) return callOnNonObject("getClass", provider);
		zv::Val classReflection = pt_reflection_provider_get_class(Z_OBJ_P(provider), className);
		if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		zv::Val finalReflection = classReflectionAsFinal(classReflection.raw());
		if (UNEXPECTED(finalReflection.isUndef())) return zv::Val();
		return newObjectType(Z_STR_P(className), finalReflection.raw());
	}

	/* count() of an array value (0 for anything else) */
	static zend_long countOf(zval *array)
	{
		return Z_TYPE_P(array) == IS_ARRAY ? zend_hash_num_elements(Z_ARRVAL_P(array)) : 0;
	}

	/* new Expr\ConstFetch(new Name($value ? 'true' : 'false')) */
	static zv::Val newBoolConstFetch(bool value)
	{
		zv::Val nameString = value ? zv::Val::string(PT_LC("true")) : zv::Val::string(PT_LC("false"));
		zv::Val name = pt_type_new(PT_CLASS_NAME, 1, nameString.raw());
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		return pt_type_new(PT_CLASS_CONST_FETCH, 1, name.raw());
	}

	/* the four same-kind conjuncts of specifyEqual(): both strings, integers,
	 * floats or enums; false = pending exception */
	[[nodiscard]] static bool bothAre(zval *leftType, zval *rightType, bool &out)
	{
		static const pt_type_op_id ops[] = { PT_OP_IS_STRING, PT_OP_IS_INTEGER, PT_OP_IS_FLOAT };
		static const char *const names[] = { "isString", "isInteger", "isFloat" };
		for (size_t i = 0; i < 3; i++) {
			zend_long leftValue = trinaryOp(leftType, ops[i], names[i]);
			if (UNEXPECTED(leftValue < 0)) return false;
			if (leftValue != PT_TRI_YES) continue;
			zend_long rightValue = trinaryOp(rightType, ops[i], names[i]);
			if (UNEXPECTED(rightValue < 0)) return false;
			if (rightValue == PT_TRI_YES) {
				out = true;
				return true;
			}
		}
		zend_long leftEnum = trinaryByName(leftType, "isEnum", PT_LC("isenum"));
		if (UNEXPECTED(leftEnum < 0)) return false;
		if (leftEnum == PT_TRI_YES) {
			zend_long rightEnum = trinaryByName(rightType, "isEnum", PT_LC("isenum"));
			if (UNEXPECTED(rightEnum < 0)) return false;
			out = rightEnum == PT_TRI_YES;
			return true;
		}
		out = false;
		return true;
	}

	/* $subjectResult->getSpecifiedTypesForScope($evaluationScope,
	 * $context->true() ? $boolContext : $boolContext->negate()) with
	 * $boolContext = $value ? createTrue() : createFalse() */
	static zv::Val boolSpecifiedTypes(zval *subjectResult, zval *evaluationScope, bool value, bool contextTrue)
	{
		zv::Val boolContext = contextValue(value ? pt_type_specifier_context_create_true() : pt_type_specifier_context_create_false());
		if (UNEXPECTED(boolContext.isUndef())) return zv::Val();
		if (!contextTrue) {
			boolContext = negate(boolContext.raw());
			if (UNEXPECTED(boolContext.isUndef())) return zv::Val();
		}
		return pt_expression_result_get_specified_types_for_scope(subjectResult, evaluationScope, boolContext.raw());
	}

	/* Mirrors specifyAgainstBool(). */
	zv::Val specifyAgainstBool(zval *subject, zval *subjectResult, bool value, zval *context, zval *evaluationScope) const
	{
		zv::Val boolType = newConstantBoolean(value);
		if (UNEXPECTED(boolType.isUndef())) return zv::Val();
		zv::Val types = createSubjectTypes(evaluationScope, subject, subjectResult, boolType.raw(), context);
		if (UNEXPECTED(types.isUndef())) return zv::Val();

		// a nullsafe chain that did not produce the constant may have
		// short-circuited instead - its own narrowing only holds when the
		// comparison succeeded
		zval *unwrappedSubject = unwrap(subject);
		bool isTrue;
		if (UNEXPECTED(!ctxTrue(context, isTrue))) return zv::Val();
		if (!isTrue && (isA(unwrappedSubject, PT_CLASS_NULLSAFE_METHOD_CALL) || isA(unwrappedSubject, PT_CLASS_NULLSAFE_PROPERTY_FETCH))) return types;
		if (UNEXPECTED(EG(exception))) return zv::Val();

		bool isTrueAgain;
		if (UNEXPECTED(!ctxTrue(context, isTrueAgain))) return zv::Val();
		return unionWith(std::move(types), boolSpecifiedTypes(subjectResult, evaluationScope, value, isTrueAgain));
	}

	/* Mirrors specifyAgainstScalarLiteral(): a SpecifiedTypes or null */
	zv::Val specifyAgainstScalarLiteral(zval *left, zval *right, zval *leftResult, zval *rightResult, zval *context, zval *evaluationScope, zval *leftArgResult, zval *rightArgResult, zval *identicalTypeCallback) const
	{
		zval *constantExpr, *constantResult, *subject, *subjectResult;
		if (isScalarLiteral(left)) {
			constantExpr = left;
			constantResult = leftResult;
			subject = right;
			subjectResult = rightResult;
		} else if (isScalarLiteral(right)) {
			constantExpr = right;
			constantResult = rightResult;
			subject = left;
			subjectResult = leftResult;
		} else {
			if (UNEXPECTED(EG(exception))) return zv::Val();
			return zv::Val::null();
		}

		zval *unwrappedSubject = unwrap(subject);
		if (isA(unwrappedSubject, PT_CLASS_FUNC_CALL)) {
			zv::Val familyConstantType = literalTypeOrResultType(constantExpr, constantResult, evaluationScope);
			if (UNEXPECTED(familyConstantType.isUndef())) return zv::Val();
			zv::Val familyTypes = specifyFuncCallFamilies(subject, subjectResult, unwrappedSubject, constantExpr, familyConstantType.raw(), context, evaluationScope, Z_OBJ_P(subject) == Z_OBJ_P(left) ? leftArgResult : rightArgResult);
			if (UNEXPECTED(familyTypes.isUndef())) return zv::Val();
			if (familyTypes.isNull()) return familyTypes;
			if (!isFalseOutcome(familyTypes)) return familyTypes;
		} else if (isA(unwrappedSubject, PT_CLASS_CLASS_CONST_FETCH) && isA(classOf(unwrappedSubject), PT_CLASS_EXPR)) {
			// only ::class composes; a constant fetched off an object falls back
			if (!isClassNameFetchName(nameOf(unwrappedSubject))) return zv::Val::null();
		} else if (!isSubjectCoveredAgainstConstant(subject)) {
			return zv::Val::null();
		}
		if (UNEXPECTED(EG(exception))) return zv::Val();

		zv::Val constantType = literalTypeOrResultType(constantExpr, constantResult, evaluationScope);
		if (UNEXPECTED(constantType.isUndef())) return zv::Val();
		zend_long finiteCount;
		if (UNEXPECTED(!finiteTypeCount(constantType.raw(), finiteCount))) return zv::Val();
		if (finiteCount != 1) {
			// a class constant does not have to be single-valued
			return zv::Val::null();
		}

		// $a::class === Foo::class narrows $a to a final Foo when true;
		// other contexts and plain-string sides only pin the fetch
		if (isA(unwrappedSubject, PT_CLASS_CLASS_CONST_FETCH) && isA(classOf(unwrappedSubject), PT_CLASS_EXPR)) {
			bool isTrue;
			if (UNEXPECTED(!ctxTrue(context, isTrue))) return zv::Val();
			if (isTrue && isA(constantExpr, PT_CLASS_CLASS_CONST_FETCH)) {
				zv::Val constantStrings = constantStringsOf(constantType.raw());
				if (UNEXPECTED(constantStrings.isUndef())) return zv::Val();
				if (countOf(constantStrings.raw()) == 1) {
					zv::Val value = constantStringValue(firstOf(constantStrings.raw()));
					if (UNEXPECTED(value.isUndef())) return zv::Val();
					if (!(Z_TYPE_P(value.raw()) == IS_STRING && ZSTR_LEN(Z_STR_P(value.raw())) == 0)) {
						bool known;
						if (UNEXPECTED(!hasClass(value.raw(), known))) return zv::Val();
						if (!known) {
							// an unknown class name narrows like instanceof - not composed yet
							return zv::Val::null();
						}

						zv::Val objectType = finalObjectType(value.raw());
						if (UNEXPECTED(objectType.isUndef())) return zv::Val();
						zv::Val classTypes = createForSubject(classOf(unwrappedSubject), objectType.raw(), context, evaluationScope);
						if (UNEXPECTED(classTypes.isUndef())) return zv::Val();
						return unionWith(std::move(classTypes), createSubjectTypes(evaluationScope, subject, subjectResult, constantType.raw(), context));
					}
				}
			}
		}
		if (UNEXPECTED(EG(exception))) return zv::Val();

		zv::Val decidedTypes = specifyDecidedComparison(left, right, leftResult, rightResult, context, evaluationScope, identicalTypeCallback);
		if (UNEXPECTED(decidedTypes.isUndef())) return zv::Val();
		if (!decidedTypes.isNull()) return decidedTypes;

		zv::Val types = createSubjectTypes(evaluationScope, subject, subjectResult, constantType.raw(), context);
		if (UNEXPECTED(types.isUndef())) return zv::Val();

		// a single-valued subject pins its value onto the literal side too
		zv::Val subjectType = typeOnScope(subjectResult, evaluationScope);
		if (UNEXPECTED(subjectType.isUndef())) return zv::Val();
		zend_long subjectFiniteCount;
		if (UNEXPECTED(!finiteTypeCount(subjectType.raw(), subjectFiniteCount))) return zv::Val();
		if (subjectFiniteCount == 1) {
			types = unionWith(std::move(types), createSubjectTypes(evaluationScope, constantExpr, constantResult, subjectType.raw(), context));
		}

		return types;
	}

	/* Mirrors specifyDecidedComparison(): a SpecifiedTypes or null */
	zv::Val specifyDecidedComparison(zval *left, zval *right, zval *leftResult, zval *rightResult, zval *context, zval *evaluationScope, zval *identicalTypeCallback) const
	{
		bool isFalse;
		if (UNEXPECTED(!ctxFalse(context, isFalse))) return zv::Val();
		if (!isFalse) return zv::Val::null();

		zv::Val identicalType = pt_type_call_callable(identicalTypeCallback, 0, NULL);
		if (UNEXPECTED(identicalType.isUndef())) return zv::Val();
		bool decided, isTrue;
		if (UNEXPECTED(!isConstantBool(identicalType.raw(), decided, isTrue))) return zv::Val();
		if (!decided) return zv::Val::null();

		zv::Val never = newNeverType();
		if (UNEXPECTED(never.isUndef())) return zv::Val();
		zv::Val contextForTypes = isTrue ? negate(context) : zv::Val::copyOf(zv::Ref(context));
		if (UNEXPECTED(contextForTypes.isUndef())) return zv::Val();

		zv::Val leftTypes = createSubjectTypes(evaluationScope, left, leftResult, never.raw(), contextForTypes.raw());
		if (UNEXPECTED(leftTypes.isUndef())) return zv::Val();
		return unionWith(std::move(leftTypes), createSubjectTypes(evaluationScope, right, rightResult, never.raw(), contextForTypes.raw()));
	}

	/* Mirrors getTypeFromGettypeStringValue(): a Type or null */
	static zv::Val getTypeFromGettypeStringValue(zval *value)
	{
		if (UNEXPECTED(Z_TYPE_P(value) != IS_STRING)) {
			zend_type_error("PHPStan\\Analyser\\ExprHandler\\Helper\\IdenticalNarrowingHelper::getTypeFromGettypeStringValue(): Argument #1 ($value) must be of type string, %s given", zend_zval_value_name(value));
			return zv::Val();
		}
		zend_string *name = Z_STR_P(value);
		zval out;
		if (zend_string_equals_literal(name, "string")) return adoptOr(pt_string_type_new(&out), out);
		if (zend_string_equals_literal(name, "array")) {
			zval keyZv, itemZv;
			if (UNEXPECTED(!pt_mixed_type_new(&keyZv))) return zv::Val();
			zv::Val key = zv::Val::adopt(keyZv);
			if (UNEXPECTED(!pt_mixed_type_new(&itemZv))) return zv::Val();
			zv::Val item = zv::Val::adopt(itemZv);
			return adoptOr(pt_array_type_new(&out, key.raw(), item.raw()), out);
		}
		if (zend_string_equals_literal(name, "boolean")) return adoptOr(pt_boolean_type_new(&out), out);
		if (zend_string_equals_literal(name, "resource") || zend_string_equals_literal(name, "resource (closed)")) return adoptOr(pt_resource_type_new(&out), out);
		if (zend_string_equals_literal(name, "integer")) return adoptOr(pt_integer_type_new(&out), out);
		if (zend_string_equals_literal(name, "double")) return adoptOr(pt_float_type_new(&out), out);
		if (zend_string_equals_literal(name, "NULL")) return adoptOr(pt_null_type_new(&out), out);
		if (zend_string_equals_literal(name, "object")) return adoptOr(pt_object_without_class_type_new(&out), out);

		return zv::Val::null();
	}

	/* Mirrors specifyGeneral(): a SpecifiedTypes or null */
	zv::Val specifyGeneral(zval *left, zval *right, zval *leftResult, zval *rightResult, zval *context, zval *evaluationScope, zval *leftArgResult, zval *rightArgResult, zval *identicalTypeCallback) const
	{
		zval *unwrappedLeft = unwrap(left);
		zval *unwrappedRight = unwrap(right);

		// a `$a::class` side falls back only where the old instanceof-style
		// blocks would fire: a true context with a single class-name string
		// on the other side; everything else narrows generically
		bool contextTrue;
		if (UNEXPECTED(!ctxTrue(context, contextTrue))) return zv::Val();
		if (contextTrue) {
			for (int i = 0; i < 2; i++) {
				zval *sideUnwrapped = i == 0 ? unwrappedLeft : unwrappedRight;
				zval *side = i == 0 ? left : right;
				zval *sideResult = i == 0 ? leftResult : rightResult;
				zval *otherResult = i == 0 ? rightResult : leftResult;
				if (!isA(sideUnwrapped, PT_CLASS_CLASS_CONST_FETCH) || !isA(classOf(sideUnwrapped), PT_CLASS_EXPR)) {
					if (UNEXPECTED(EG(exception))) return zv::Val();
					continue;
				}
				// only `$expr::class` names the fetched-on class
				if (!isClassNameFetchName(nameOf(sideUnwrapped))) continue;

				zv::Val otherType = typeOnScope(otherResult, evaluationScope);
				if (UNEXPECTED(otherType.isUndef())) return zv::Val();
				zv::Val otherStrings = constantStringsOf(otherType.raw());
				if (UNEXPECTED(otherStrings.isUndef())) return zv::Val();
				if (countOf(otherStrings.raw()) != 1) continue;
				zval *otherString = firstOf(otherStrings.raw());
				zv::Val value = constantStringValue(otherString);
				if (UNEXPECTED(value.isUndef())) return zv::Val();
				if (Z_TYPE_P(value.raw()) == IS_STRING && ZSTR_LEN(Z_STR_P(value.raw())) == 0) continue;

				bool known;
				if (UNEXPECTED(!hasClass(value.raw(), known))) return zv::Val();
				if (UNEXPECTED(Z_TYPE_P(value.raw()) != IS_STRING)) {
					zend_type_error("PHPStan\\Type\\ObjectType::__construct(): Argument #1 ($className) must be of type string, %s given", zend_zval_value_name(value.raw()));
					return zv::Val();
				}
				zv::Val objectType;
				if (!known) {
					// an unknown class narrows like instanceof: intersect the
					// fetched-on object with the named type (it cannot be pinned
					// as final without reflection)
					objectType = newObjectType(Z_STR_P(value.raw()));
				} else {
					objectType = finalObjectType(value.raw());
				}
				if (UNEXPECTED(objectType.isUndef())) return zv::Val();
				zv::Val classTypes = createForSubject(classOf(sideUnwrapped), objectType.raw(), context, evaluationScope);
				if (UNEXPECTED(classTypes.isUndef())) return zv::Val();
				return unionWith(std::move(classTypes), createSubjectTypes(evaluationScope, side, sideResult, otherString, context));
			}
		}

		zv::Val leftType = literalTypeOrResultType(unwrappedLeft, leftResult, evaluationScope);
		if (UNEXPECTED(leftType.isUndef())) return zv::Val();
		zv::Val rightType = literalTypeOrResultType(unwrappedRight, rightResult, evaluationScope);
		if (UNEXPECTED(rightType.isUndef())) return zv::Val();

		bool leftIsFuncCall = isA(unwrappedLeft, PT_CLASS_FUNC_CALL);
		bool rightIsFuncCall = isA(unwrappedRight, PT_CLASS_FUNC_CALL);
		if (UNEXPECTED(EG(exception))) return zv::Val();

		// fn1() === fn2() merges both normalized directions
		if (leftIsFuncCall && rightIsFuncCall) {
			// count($a) === count($b): a decided size flows across; otherwise
			// one non-empty side makes both non-empty
			bool countPair;
			if (UNEXPECTED(!ctxTrue(context, countPair))) return zv::Val();
			if (countPair && UNEXPECTED(!isNormalCountCall(unwrappedLeft, countPair))) return zv::Val();
			if (countPair && UNEXPECTED(!isNormalCountCall(unwrappedRight, countPair))) return zv::Val();
			if (countPair) {
				if (leftArgResult == NULL || rightArgResult == NULL) return zv::Val::null();
				zv::Val rightArgType = typeOnScope(rightArgResult, evaluationScope);
				if (UNEXPECTED(rightArgType.isUndef())) return zv::Val();
				zv::Val countTypes = specifyCountSize(slot(slots::countNarrowingHelper), unwrappedRight, rightArgType.raw(), leftType.raw(), context, evaluationScope, unwrappedRight);
				if (UNEXPECTED(countTypes.isUndef())) return zv::Val();
				if (!countTypes.isNull()) return countTypes;

				zv::Val leftArgType = typeOnScope(leftArgResult, evaluationScope);
				if (UNEXPECTED(leftArgType.isUndef())) return zv::Val();
				bool nonEmptyBoth;
				if (UNEXPECTED(!arraysMakeBothNonEmpty(leftArgType.raw(), rightArgType.raw(), rightType.raw(), nonEmptyBoth))) return zv::Val();
				if (nonEmptyBoth) {
					zv::Val nonEmptyLeft = newNonEmptyArrayType();
					if (UNEXPECTED(nonEmptyLeft.isUndef())) return zv::Val();
					zv::Val leftTypes = createForSubject(firstArgValue(unwrappedLeft), nonEmptyLeft.raw(), context, evaluationScope);
					if (UNEXPECTED(leftTypes.isUndef())) return zv::Val();
					zv::Val nonEmptyRight = newNonEmptyArrayType();
					if (UNEXPECTED(nonEmptyRight.isUndef())) return zv::Val();
					return unionWith(std::move(leftTypes), createForSubject(firstArgValue(unwrappedRight), nonEmptyRight.raw(), context, evaluationScope));
				}
			}

			zv::Val leftDirection = specifyFuncCallFamilies(left, leftResult, unwrappedLeft, right, rightType.raw(), context, evaluationScope, leftArgResult);
			if (UNEXPECTED(leftDirection.isUndef())) return zv::Val();
			zv::Val rightDirection = specifyFuncCallFamilies(right, rightResult, unwrappedRight, left, leftType.raw(), context, evaluationScope, rightArgResult);
			if (UNEXPECTED(rightDirection.isUndef())) return zv::Val();
			if (leftDirection.isNull() || rightDirection.isNull()) return zv::Val::null();
			zv::Val merged = zv::Val::null();
			if (!isFalseOutcome(leftDirection)) {
				merged = std::move(leftDirection);
			}
			if (!isFalseOutcome(rightDirection)) {
				merged = merged.isNull() ? std::move(rightDirection) : unionWith(std::move(merged), std::move(rightDirection));
				if (UNEXPECTED(merged.isUndef())) return zv::Val();
			}
			if (!merged.isNull()) return merged;

			// neither family matched - the generic tail below pins both sides
		}

		// a single call side runs the family compositions with the other
		// side's TYPE as the constant
		if (leftIsFuncCall || rightIsFuncCall) {
			zv::Val familyTypes = leftIsFuncCall
				? specifyFuncCallFamilies(left, leftResult, unwrappedLeft, right, rightType.raw(), context, evaluationScope, leftArgResult)
				: specifyFuncCallFamilies(right, rightResult, unwrappedRight, left, leftType.raw(), context, evaluationScope, rightArgResult);
			if (UNEXPECTED(familyTypes.isUndef())) return zv::Val();
			if (familyTypes.isNull()) return familyTypes;
			if (!isFalseOutcome(familyTypes)) return familyTypes;
		}

		zv::Val decidedTypes = specifyDecidedComparison(left, right, leftResult, rightResult, context, evaluationScope, identicalTypeCallback);
		if (UNEXPECTED(decidedTypes.isUndef())) return zv::Val();
		if (!decidedTypes.isNull()) return decidedTypes;

		zv::Val types = zv::Val::null();
		bool pinRight;
		if (UNEXPECTED(!pinsOther(leftType.raw(), rightType.raw(), context, pinRight))) return zv::Val();
		if (pinRight) {
			types = createSubjectTypes(evaluationScope, right, rightResult, leftType.raw(), context);
			if (UNEXPECTED(types.isUndef())) return zv::Val();
		}
		bool pinLeft;
		if (UNEXPECTED(!pinsOther(rightType.raw(), leftType.raw(), context, pinLeft))) return zv::Val();
		if (pinLeft) {
			zv::Val leftTypes = createSubjectTypes(evaluationScope, left, leftResult, rightType.raw(), context);
			if (UNEXPECTED(leftTypes.isUndef())) return zv::Val();
			types = types.isNull() ? std::move(leftTypes) : unionWith(std::move(types), std::move(leftTypes));
			if (UNEXPECTED(types.isUndef())) return zv::Val();
		}

		if (!types.isNull()) return types;

		zv::Str leftExprString = print(unwrappedLeft);
		if (UNEXPECTED(leftExprString.isNull())) return zv::Val();
		zv::Str rightExprString = print(unwrappedRight);
		if (UNEXPECTED(rightExprString.isNull())) return zv::Val();
		if (zend_string_equals(leftExprString.get(), rightExprString.get())) {
			if (!isA(unwrappedLeft, PT_CLASS_VARIABLE) || !isA(unwrappedRight, PT_CLASS_VARIABLE)) return pt_specified_types_new();
		}

		bool isTrue;
		if (UNEXPECTED(!ctxTrue(context, isTrue))) return zv::Val();
		if (isTrue) {
			zv::Val leftTypes = createSubjectTypes(evaluationScope, left, leftResult, rightType.raw(), context);
			if (UNEXPECTED(leftTypes.isUndef())) return zv::Val();
			return unionWith(std::move(leftTypes), createSubjectTypes(evaluationScope, right, rightResult, leftType.raw(), context));
		}
		bool isFalse;
		if (UNEXPECTED(!ctxFalse(context, isFalse))) return zv::Val();
		if (isFalse) {
			zv::Val leftTypes = createSubjectTypes(evaluationScope, left, leftResult, leftType.raw(), context);
			if (UNEXPECTED(leftTypes.isUndef())) return zv::Val();
			zv::Val leftSure = toSureTypes(leftTypes.raw(), evaluationScope);
			if (UNEXPECTED(leftSure.isUndef())) return zv::Val();
			zv::Val rightTypes = createSubjectTypes(evaluationScope, right, rightResult, rightType.raw(), context);
			if (UNEXPECTED(rightTypes.isUndef())) return zv::Val();
			zv::Val rightSure = toSureTypes(rightTypes.raw(), evaluationScope);
			return intersectWith(std::move(leftSure), std::move(rightSure));
		}

		return pt_specified_types_new();
	}

	/* count(A->getFiniteTypes()) === 1 || ($context->true() &&
	 * A->isConstantValue()->yes() && !B->equals(A) && B->isSuperTypeOf(A)->yes())
	 * — whether side A pins its type onto side B; false = pending exception */
	[[nodiscard]] static bool pinsOther(zval *typeA, zval *typeB, zval *context, bool &out)
	{
		zend_long finiteCount;
		if (UNEXPECTED(!finiteTypeCount(typeA, finiteCount))) return false;
		if (finiteCount == 1) {
			out = true;
			return true;
		}
		out = false;
		bool isTrue;
		if (UNEXPECTED(!ctxTrue(context, isTrue))) return false;
		if (!isTrue) return true;
		zend_long constantValue = trinaryByName(typeA, "isConstantValue", PT_LC("isconstantvalue"));
		if (UNEXPECTED(constantValue < 0)) return false;
		if (constantValue != PT_TRI_YES) return true;
		zv::Val equals = pt_type_op(Z_OBJ_P(typeB), PT_OP_EQUALS, 1, typeA);
		if (UNEXPECTED(equals.isUndef())) return false;
		if (zend_is_true(equals.raw())) return true;
		return isSuperTypeOfYes(typeB, typeA, out);
	}

	/* $leftArgType->isArray()->yes() && $rightArgType->isArray()->yes() &&
	 * !$rightType->isConstantScalarValue()->yes() &&
	 * ($leftArgType->isIterableAtLeastOnce()->yes() ||
	 * $rightArgType->isIterableAtLeastOnce()->yes()); false = pending exception */
	[[nodiscard]] static bool arraysMakeBothNonEmpty(zval *leftArgType, zval *rightArgType, zval *rightType, bool &out)
	{
		out = false;
		zend_long value = trinaryOp(leftArgType, PT_OP_IS_ARRAY, "isArray");
		if (UNEXPECTED(value < 0)) return false;
		if (value != PT_TRI_YES) return true;
		value = trinaryOp(rightArgType, PT_OP_IS_ARRAY, "isArray");
		if (UNEXPECTED(value < 0)) return false;
		if (value != PT_TRI_YES) return true;
		value = trinaryOp(rightType, PT_OP_IS_CONSTANT_SCALAR_VALUE, "isConstantScalarValue");
		if (UNEXPECTED(value < 0)) return false;
		if (value == PT_TRI_YES) return true;
		value = trinaryOp(leftArgType, PT_OP_IS_ITERABLE_AT_LEAST_ONCE, "isIterableAtLeastOnce");
		if (UNEXPECTED(value < 0)) return false;
		if (value == PT_TRI_YES) {
			out = true;
			return true;
		}
		value = trinaryOp(rightArgType, PT_OP_IS_ITERABLE_AT_LEAST_ONCE, "isIterableAtLeastOnce");
		if (UNEXPECTED(value < 0)) return false;
		out = value == PT_TRI_YES;
		return true;
	}

	/* $call->name instanceof Name && in_array($call->name->toLowerString(),
	 * ['count', 'sizeof'], true) && !$call->isFirstClassCallable() &&
	 * isset($call->getArgs()[0]); false = pending exception */
	[[nodiscard]] static bool isNormalCountCall(zval *call, bool &out)
	{
		zval *name = nameOf(call);
		if (!isA(name, PT_CLASS_NAME)) {
			out = false;
			return EG(exception) == NULL;
		}
		zend_string *lower = identifierString(name);
		if (!(lowerNameIs(lower, "count") || lowerNameIs(lower, "sizeof"))) {
			out = false;
			return true;
		}
		return hasFirstArg(call, out);
	}

	/* Mirrors specifyEqualAgainstConstantSide(): a SpecifiedTypes, false or null */
	zv::Val specifyEqualAgainstConstantSide(zval *nodeScopeResolver, zval *left, zval *right, zval *leftResult, zval *rightResult, zval *subject, zval *subjectResult, zval *value, zval *constantType, zval *otherType, zval *context, zval *evaluationScope, zval *leftArgResult, zval *rightArgResult, zval *identicalTypeCallback) const
	{
		zval *unwrappedSubject = unwrap(subject);

		if (Z_TYPE_P(value) == IS_NULL) {
			zv::Arr members = zv::Arr::create(6);
			if (UNEXPECTED(!pushFalsyScalars(members, true, true))) return zv::Val();
			zv::Val emptyArray = newEmptyConstantArray();
			if (UNEXPECTED(emptyArray.isUndef())) return zv::Val();
			members.push(std::move(emptyArray));
			zv::Val union_ = newUnion(members);
			if (UNEXPECTED(union_.isUndef())) return zv::Val();
			return createSubjectTypes(evaluationScope, subject, subjectResult, union_.raw(), context);
		}

		// a bool constant compares by the subject's truthiness
		if (Z_TYPE_P(value) == IS_FALSE || Z_TYPE_P(value) == IS_TRUE) {
			bool isTrue;
			if (UNEXPECTED(!ctxTrue(context, isTrue))) return zv::Val();
			zv::Val boolContext = contextValue(Z_TYPE_P(value) == IS_FALSE ? pt_type_specifier_context_create_falsey() : pt_type_specifier_context_create_truthy());
			if (UNEXPECTED(boolContext.isUndef())) return zv::Val();
			if (!isTrue) {
				boolContext = negate(boolContext.raw());
				if (UNEXPECTED(boolContext.isUndef())) return zv::Val();
			}
			return pt_expression_result_get_specified_types_for_scope(subjectResult, evaluationScope, boolContext.raw());
		}

		/* There is a difference between php 7.x and 8.x on the equality
		 * behavior between zero and the empty string, so to be conservative
		 * we leave it untouched regardless of the language version */
		if (Z_TYPE_P(value) == IS_LONG && Z_LVAL_P(value) == 0) {
			zend_long otherInteger = trinaryOp(otherType, PT_OP_IS_INTEGER, "isInteger");
			if (UNEXPECTED(otherInteger < 0)) return zv::Val();
			zend_long otherBoolean = otherInteger == PT_TRI_YES ? PT_TRI_YES : trinaryOp(otherType, PT_OP_IS_BOOLEAN, "isBoolean");
			if (UNEXPECTED(otherBoolean < 0)) return zv::Val();
			if (otherInteger != PT_TRI_YES && otherBoolean != PT_TRI_YES) {
				bool isTrue;
				if (UNEXPECTED(!ctxTrue(context, isTrue))) return zv::Val();
				zv::Arr members = zv::Arr::create(5);
				if (UNEXPECTED(!pushFalsyScalars(members, true, false))) return zv::Val();
				if (isTrue) {
					zv::Val stringType = newStringType();
					if (UNEXPECTED(stringType.isUndef())) return zv::Val();
					members.push(std::move(stringType));
				} else {
					zend_string *zero = zend_string_init(PT_LC("0"), 0);
					zv::Val zeroString = newConstantString(zero);
					zend_string_release(zero);
					if (UNEXPECTED(zeroString.isUndef())) return zv::Val();
					members.push(std::move(zeroString));
				}
				zv::Val union_ = newUnion(members);
				if (UNEXPECTED(union_.isUndef())) return zv::Val();
				return createSubjectTypes(evaluationScope, subject, subjectResult, union_.raw(), context);
			}
		}
		if (Z_TYPE_P(value) == IS_STRING && Z_STRLEN_P(value) == 0) {
			bool isTrue;
			if (UNEXPECTED(!ctxTrue(context, isTrue))) return zv::Val();
			zv::Arr members = zv::Arr::create(5);
			if (isTrue) {
				if (UNEXPECTED(!pushFalsyScalars(members, true, false))) return zv::Val();
			} else {
				zv::Val nullType = newNullType();
				if (UNEXPECTED(nullType.isUndef())) return zv::Val();
				members.push(std::move(nullType));
				zv::Val falseType = newConstantBoolean(false);
				if (UNEXPECTED(falseType.isUndef())) return zv::Val();
				members.push(std::move(falseType));
			}
			zv::Val emptyString = newConstantString(ZSTR_EMPTY_ALLOC());
			if (UNEXPECTED(emptyString.isUndef())) return zv::Val();
			members.push(std::move(emptyString));
			zv::Val union_ = newUnion(members);
			if (UNEXPECTED(union_.isUndef())) return zv::Val();
			return createSubjectTypes(evaluationScope, subject, subjectResult, union_.raw(), context);
		}

		// loose equals strict for these call results and class names
		if (isA(unwrappedSubject, PT_CLASS_FUNC_CALL) && isA(nameOf(unwrappedSubject), PT_CLASS_NAME)) {
			bool hasArg;
			if (UNEXPECTED(!hasFirstArg(unwrappedSubject, hasArg))) return zv::Val();
			if (hasArg) {
				zend_string *funcName = identifierString(nameOf(unwrappedSubject));
				if (funcName != NULL && nameIn(funcName, { "gettype", "get_class", "get_debug_type" })) {
					zend_long isString = trinaryOp(constantType, PT_OP_IS_STRING, "isString");
					if (UNEXPECTED(isString < 0)) return zv::Val();
					if (isString == PT_TRI_YES) return specifyIdentical(nodeScopeResolver, left, right, leftResult, rightResult, context, evaluationScope, leftArgResult, rightArgResult, identicalTypeCallback);
				}
				bool isTrue;
				if (UNEXPECTED(!ctxTrue(context, isTrue))) return zv::Val();
				if (isTrue && funcName != NULL && lowerNameIs(funcName, "preg_match")) {
					bool one;
					if (UNEXPECTED(!constantIntegerContains(1, constantType, one))) return zv::Val();
					if (one) return specifyIdentical(nodeScopeResolver, left, right, leftResult, rightResult, context, evaluationScope, leftArgResult, rightArgResult, identicalTypeCallback);
				}
			}
		}
		if (isA(unwrappedSubject, PT_CLASS_CLASS_CONST_FETCH) && isClassNameFetchName(nameOf(unwrappedSubject))) {
			zend_long isString = trinaryOp(constantType, PT_OP_IS_STRING, "isString");
			if (UNEXPECTED(isString < 0)) return zv::Val();
			if (isString == PT_TRI_YES) return specifyIdentical(nodeScopeResolver, left, right, leftResult, rightResult, context, evaluationScope, leftArgResult, rightArgResult, identicalTypeCallback);
		}
		if (UNEXPECTED(EG(exception))) return zv::Val();

		return zv::Val::boolean(false);
	}

	/* [new NullType(), new ConstantBooleanType(false), new
	 * ConstantIntegerType(0), new ConstantFloatType(0.0), (new
	 * ConstantStringType(''))] into a union's member list; false = pending
	 * exception */
	[[nodiscard]] static bool pushFalsyScalars(zv::Arr &members, bool withNumbers, bool withEmptyString)
	{
		zv::Val nullType = newNullType();
		if (UNEXPECTED(nullType.isUndef())) return false;
		members.push(std::move(nullType));
		zv::Val falseType = newConstantBoolean(false);
		if (UNEXPECTED(falseType.isUndef())) return false;
		members.push(std::move(falseType));
		if (withNumbers) {
			zv::Val zero = newConstantInteger(0);
			if (UNEXPECTED(zero.isUndef())) return false;
			members.push(std::move(zero));
			zv::Val zeroFloat = newConstantFloat(0.0);
			if (UNEXPECTED(zeroFloat.isUndef())) return false;
			members.push(std::move(zeroFloat));
		}
		if (withEmptyString) {
			zv::Val emptyString = newConstantString(ZSTR_EMPTY_ALLOC());
			if (UNEXPECTED(emptyString.isUndef())) return false;
			members.push(std::move(emptyString));
		}
		return true;
	}

	/* !($name instanceof Expr) && $name->toLowerString() === 'class' */
	static bool isClassNameFetchName(zval *name)
	{
		if (isA(name, PT_CLASS_EXPR)) return false;
		return lowerNameIs(identifierString(name), "class");
	}

	/* Mirrors specifyFuncCallFamilies(): a SpecifiedTypes, false or null */
	zv::Val specifyFuncCallFamilies(zval *subject, zval *subjectResult, zval *call, zval *constantExpr, zval *constantType, zval *context, zval *evaluationScope, zval *argResult) const
	{
		if (!isA(nameOf(call), PT_CLASS_NAME)) {
			if (UNEXPECTED(EG(exception))) return zv::Val();
			return zv::Val::boolean(false);
		}
		bool hasArg;
		if (UNEXPECTED(!hasFirstArg(call, hasArg))) return zv::Val();
		if (!hasArg) return zv::Val::boolean(false);
		zend_string *name = identifierString(nameOf(call));
		if (UNEXPECTED(name == NULL)) return zv::Val::boolean(false);

		// preg_match(...) === 1 is the call's own truthy narrowing
		if (lowerNameIs(name, "preg_match")) {
			bool isTrue;
			if (UNEXPECTED(!ctxTrue(context, isTrue))) return zv::Val();
			if (isTrue) {
				bool one;
				if (UNEXPECTED(!constantIntegerContains(1, constantType, one))) return zv::Val();
				if (one) return pt_expression_result_get_specified_types_for_scope(subjectResult, evaluationScope, context);
			}

			// other constants and contexts only pin the call below
		}

		// a trimmed string that is not '' was a non-empty string already
		if (nameIn(name, { "trim", "ltrim", "rtrim", "chop", "mb_trim", "mb_ltrim", "mb_rtrim" })) {
			bool isFalse;
			if (UNEXPECTED(!ctxFalse(context, isFalse))) return zv::Val();
			if (isFalse) {
				zv::Val constantStrings = constantStringsOf(constantType);
				if (UNEXPECTED(constantStrings.isUndef())) return zv::Val();
				if (countOf(constantStrings.raw()) == 1) {
					zv::Val value = constantStringValue(firstOf(constantStrings.raw()));
					if (UNEXPECTED(value.isUndef())) return zv::Val();
					if (Z_TYPE_P(value.raw()) == IS_STRING && ZSTR_LEN(Z_STR_P(value.raw())) == 0) {
						zval *argExpr = firstArgValue(call);
						if (argResult == NULL) return zv::Val::null();
						zv::Val argType = typeOnScope(argResult, evaluationScope);
						if (UNEXPECTED(argType.isUndef())) return zv::Val();
						zend_long isString = trinaryOp(argType.raw(), PT_OP_IS_STRING, "isString");
						if (UNEXPECTED(isString < 0)) return zv::Val();
						if (isString == PT_TRI_YES) {
							zv::Val stringType = newStringType();
							if (UNEXPECTED(stringType.isUndef())) return zv::Val();
							zv::Val nonEmpty = newAccessoryNonEmptyString();
							if (UNEXPECTED(nonEmpty.isUndef())) return zv::Val();
							zv::Arr members = zv::Arr::create(2);
							members.push(std::move(stringType));
							members.push(std::move(nonEmpty));
							zval intersectionZv;
							if (UNEXPECTED(!pt_intersection_type_new(&intersectionZv, members.raw()))) return zv::Val();
							zv::Val intersection = zv::Val::adopt(intersectionZv);
							zv::Val negated = negate(context);
							if (UNEXPECTED(negated.isUndef())) return zv::Val();
							return createForSubject(argExpr, intersection.raw(), negated.raw(), evaluationScope);
						}
					}
				}
			}

			// other constants and contexts only pin the call
		}

		// a known parent class narrows the argument to the child side of it
		if (lowerNameIs(name, "get_parent_class")) {
			bool isTrue;
			if (UNEXPECTED(!ctxTrue(context, isTrue))) return zv::Val();
			if (isTrue) {
				zv::Val constantStrings = constantStringsOf(constantType);
				if (UNEXPECTED(constantStrings.isUndef())) return zv::Val();
				if (countOf(constantStrings.raw()) == 1) {
					zv::Val value = constantStringValue(firstOf(constantStrings.raw()));
					if (UNEXPECTED(value.isUndef())) return zv::Val();
					if (!(Z_TYPE_P(value.raw()) == IS_STRING && ZSTR_LEN(Z_STR_P(value.raw())) == 0)) {
						zval *argExpr = firstArgValue(call);
						if (argResult == NULL) return zv::Val::null();
						zv::Val argType = typeOnScope(argResult, evaluationScope);
						if (UNEXPECTED(argType.isUndef())) return zv::Val();
						zv::Val className = constantStringValue(firstOf(constantStrings.raw()));
						if (UNEXPECTED(className.isUndef())) return zv::Val();
						if (UNEXPECTED(Z_TYPE_P(className.raw()) != IS_STRING)) {
							zend_type_error("PHPStan\\Type\\ObjectType::__construct(): Argument #1 ($className) must be of type string, %s given", zend_zval_value_name(className.raw()));
							return zv::Val();
						}
						zv::Val objectType = newObjectType(Z_STR_P(className.raw()));
						if (UNEXPECTED(objectType.isUndef())) return zv::Val();
						zv::Val classStringType = pt_type_new_ce(pt_ce_generic_class_string_type, 1, objectType.raw());
						if (UNEXPECTED(classStringType.isUndef())) return zv::Val();

						zv::Val narrowed;
						zend_long isString = trinaryOp(argType.raw(), PT_OP_IS_STRING, "isString");
						if (UNEXPECTED(isString < 0)) return zv::Val();
						if (isString == PT_TRI_YES) {
							narrowed = std::move(classStringType);
						} else {
							zend_long isObject = trinaryByName(argType.raw(), "isObject", PT_LC("isobject"));
							if (UNEXPECTED(isObject < 0)) return zv::Val();
							if (isObject == PT_TRI_YES) {
								narrowed = std::move(objectType);
							} else {
								zv::Args unionArgv{objectType.raw(), classStringType.raw()};
								narrowed = pt_type_combinator_union(2, unionArgv);
								if (UNEXPECTED(narrowed.isUndef())) return zv::Val();
							}
						}

						return createForSubject(argExpr, narrowed.raw(), context, evaluationScope);
					}
				}
			}

			// other contexts and non-single class names only pin the call
		}

		// a string function whose result is a non-empty literal had a
		// non-empty (non-falsy for a non-falsy literal) string argument;
		// case-mapping functions pin the case accessory on the literal side
		if (nameIn(name, {
			"substr", "strstr", "stristr", "strchr", "strrchr", "strtolower", "strtoupper", "ucfirst", "lcfirst",
			"mb_substr", "mb_strstr", "mb_stristr", "mb_strchr", "mb_strrchr", "mb_strtolower", "mb_strtoupper", "mb_ucfirst", "mb_lcfirst",
			"ucwords", "mb_convert_case", "mb_convert_kana",
		})) {
			bool truthy;
			if (UNEXPECTED(!ctxTruthy(context, truthy))) return zv::Val();
			zend_long nonEmptyConstant = truthy ? trinaryByName(constantType, "isNonEmptyString", PT_LC("isnonemptystring")) : PT_TRI_NO;
			if (UNEXPECTED(nonEmptyConstant < 0)) return zv::Val();
			if (truthy && nonEmptyConstant == PT_TRI_YES) {
				zval *argExpr = firstArgValue(call);
				if (argResult == NULL) return zv::Val::null();
				zv::Val argType = typeOnScope(argResult, evaluationScope);
				if (UNEXPECTED(argType.isUndef())) return zv::Val();

				zend_long isString = trinaryOp(argType.raw(), PT_OP_IS_STRING, "isString");
				if (UNEXPECTED(isString < 0)) return zv::Val();
				if (isString == PT_TRI_YES) {
					zv::Val types = pt_specified_types_new();
					if (UNEXPECTED(types.isUndef())) return zv::Val();
					bool lower = nameIn(name, { "strtolower", "mb_strtolower" });
					bool upper = !lower && nameIn(name, { "strtoupper", "mb_strtoupper" });
					if (lower || upper) {
						zval accessoryZv;
						if (UNEXPECTED(!(lower ? pt_accessory_lowercase_string_type_new(&accessoryZv) : pt_accessory_uppercase_string_type_new(&accessoryZv)))) return zv::Val();
						zv::Val accessory = zv::Val::adopt(accessoryZv);
						zv::Args intersectArgv{constantType, accessory.raw()};
						zv::Val intersected = pt_type_combinator_intersect(2, intersectArgv);
						if (UNEXPECTED(intersected.isUndef())) return zv::Val();
						types = createForSubject(constantExpr, intersected.raw(), context, evaluationScope);
						if (UNEXPECTED(types.isUndef())) return zv::Val();
					}

					zend_long nonFalsy = trinaryByName(constantType, "isNonFalsyString", PT_LC("isnonfalsystring"));
					if (UNEXPECTED(nonFalsy < 0)) return zv::Val();
					zv::Val accessory = nonFalsy == PT_TRI_YES ? newAccessoryNonFalsyString() : newAccessoryNonEmptyString();
					if (UNEXPECTED(accessory.isUndef())) return zv::Val();

					zv::Args intersectArgv{argType.raw(), accessory.raw()};
					zv::Val intersected = pt_type_combinator_intersect(2, intersectArgv);
					if (UNEXPECTED(intersected.isUndef())) return zv::Val();
					return unionWith(std::move(types), createForSubject(argExpr, intersected.raw(), context, evaluationScope));
				}
			}

			// a non-string argument, an empty literal or a non-truthy
			// context only pins the call
		}

		// count($x) === N reconstructs the array shape by its size
		if (nameIn(name, { "count", "sizeof" })) {
			zend_long isInteger = trinaryOp(constantType, PT_OP_IS_INTEGER, "isInteger");
			if (UNEXPECTED(isInteger < 0)) return zv::Val();
			if (isInteger != PT_TRI_YES) return zv::Val::null();

			zval *argExpr = firstArgValue(call);
			bool negative;
			if (UNEXPECTED(!rangeContains(NullableLong::null(), NullableLong::of(-1), constantType, negative))) return zv::Val();
			if (negative) {
				zv::Val never = newNeverType();
				if (UNEXPECTED(never.isUndef())) return zv::Val();
				return createForSubject(argExpr, never.raw(), context, evaluationScope);
			}

			if (argResult == NULL) return zv::Val::null();
			zv::Val argType = typeOnScope(argResult, evaluationScope);
			if (UNEXPECTED(argType.isUndef())) return zv::Val();

			bool zero;
			if (UNEXPECTED(!constantIntegerContains(0, constantType, zero))) return zv::Val();
			if (zero) {
				bool truthy;
				if (UNEXPECTED(!ctxTruthy(context, truthy))) return zv::Val();
				zend_long argIsArray = truthy ? trinaryOp(argType.raw(), PT_OP_IS_ARRAY, "isArray") : PT_TRI_YES;
				if (UNEXPECTED(argIsArray < 0)) return zv::Val();
				zv::Val newArgType;
				if (truthy && argIsArray != PT_TRI_YES) {
					zend_string *countable = zend_string_init(PT_LC("Countable"), 0);
					zv::Val countableType = newObjectType(countable);
					zend_string_release(countable);
					if (UNEXPECTED(countableType.isUndef())) return zv::Val();
					zv::Val emptyArray = newEmptyConstantArray();
					if (UNEXPECTED(emptyArray.isUndef())) return zv::Val();
					zv::Arr members = zv::Arr::create(2);
					members.push(std::move(countableType));
					members.push(std::move(emptyArray));
					newArgType = newUnion(members);
				} else {
					newArgType = newEmptyConstantArray();
				}
				if (UNEXPECTED(newArgType.isUndef())) return zv::Val();

				zv::Val subjectTypes = createSubjectTypes(evaluationScope, subject, subjectResult, constantType, context);
				if (UNEXPECTED(subjectTypes.isUndef())) return zv::Val();
				return unionWith(std::move(subjectTypes), createForSubject(argExpr, newArgType.raw(), context, evaluationScope));
			}

			zv::Val countTypes = specifyCountSize(slot(slots::countNarrowingHelper), call, argType.raw(), constantType, context, evaluationScope, call);
			if (UNEXPECTED(countTypes.isUndef())) return zv::Val();
			if (!countTypes.isNull()) {
				// the old path pinned the call only through the remembered
				// wrapper; the composed pin covers wrapper and call alike
				if (!(Z_TYPE_P(subject) == IS_OBJECT && Z_OBJ_P(subject) == Z_OBJ_P(call))) {
					return unionWith(std::move(countTypes), createSubjectTypes(evaluationScope, subject, subjectResult, constantType, context));
				}

				return countTypes;
			}

			bool truthy;
			if (UNEXPECTED(!ctxTruthy(context, truthy))) return zv::Val();
			if (truthy) {
				zend_long argIsArray = trinaryOp(argType.raw(), PT_OP_IS_ARRAY, "isArray");
				if (UNEXPECTED(argIsArray < 0)) return zv::Val();
				if (argIsArray == PT_TRI_YES) {
					zv::Val types = createSubjectTypes(evaluationScope, subject, subjectResult, constantType, context);
					if (UNEXPECTED(types.isUndef())) return zv::Val();
					bool positive;
					if (UNEXPECTED(!rangeContains(NullableLong::of(1), NullableLong::null(), constantType, positive))) return zv::Val();
					if (positive) {
						zv::Val nonEmptyArray = newNonEmptyArrayType();
						if (UNEXPECTED(nonEmptyArray.isUndef())) return zv::Val();
						return unionWith(std::move(types), createForSubject(argExpr, nonEmptyArray.raw(), context, evaluationScope));
					}

					return types;
				}
			}

			// a non-array argument in a non-truthy context only pins the call
		}

		// strlen($x) === 0 empties $x; === N >= 1 makes it non-empty in the
		// truthy direction (>= 2 non-falsy)
		if (nameIn(name, { "strlen", "mb_strlen" })) {
			HashTable *args = rawArgs(call);
			if (args == NULL || zend_hash_num_elements(args) != 1) return zv::Val::null();
			zend_long isInteger = trinaryOp(constantType, PT_OP_IS_INTEGER, "isInteger");
			if (UNEXPECTED(isInteger < 0)) return zv::Val();
			if (isInteger != PT_TRI_YES) return zv::Val::null();

			zval *argExpr = firstArgValue(call);
			bool negative;
			if (UNEXPECTED(!rangeContains(NullableLong::null(), NullableLong::of(-1), constantType, negative))) return zv::Val();
			if (negative) {
				zv::Val never = newNeverType();
				if (UNEXPECTED(never.isUndef())) return zv::Val();
				return createForSubject(argExpr, never.raw(), context, evaluationScope);
			}

			bool zero;
			if (UNEXPECTED(!constantIntegerContains(0, constantType, zero))) return zv::Val();
			if (zero) {
				zv::Val subjectTypes = createSubjectTypes(evaluationScope, subject, subjectResult, constantType, context);
				if (UNEXPECTED(subjectTypes.isUndef())) return zv::Val();
				zv::Val emptyString = newConstantString(ZSTR_EMPTY_ALLOC());
				if (UNEXPECTED(emptyString.isUndef())) return zv::Val();
				return unionWith(std::move(subjectTypes), createForSubject(argExpr, emptyString.raw(), context, evaluationScope));
			}

			bool truthy;
			if (UNEXPECTED(!ctxTruthy(context, truthy))) return zv::Val();
			bool positive = false;
			if (truthy && UNEXPECTED(!rangeContains(NullableLong::of(1), NullableLong::null(), constantType, positive))) return zv::Val();
			if (truthy && positive) {
				if (argResult == NULL) return zv::Val::null();
				zv::Val argType = typeOnScope(argResult, evaluationScope);
				if (UNEXPECTED(argType.isUndef())) return zv::Val();
				zend_long isString = trinaryOp(argType.raw(), PT_OP_IS_STRING, "isString");
				if (UNEXPECTED(isString < 0)) return zv::Val();
				if (isString == PT_TRI_YES) {
					bool atLeastTwo;
					if (UNEXPECTED(!rangeContains(NullableLong::of(2), NullableLong::null(), constantType, atLeastTwo))) return zv::Val();
					zv::Val accessory = atLeastTwo ? newAccessoryNonFalsyString() : newAccessoryNonEmptyString();
					if (UNEXPECTED(accessory.isUndef())) return zv::Val();

					zv::Val subjectTypes = createSubjectTypes(evaluationScope, subject, subjectResult, constantType, context);
					if (UNEXPECTED(subjectTypes.isUndef())) return zv::Val();
					return unionWith(std::move(subjectTypes), createForSubject(argExpr, accessory.raw(), context, evaluationScope));
				}
			}

			// a non-string argument or a falsey non-zero size only pins the call
		}

		// gettype($x) === 'string' narrows $x by the named type in either
		// direction
		if (lowerNameIs(name, "gettype")) {
			zv::Val constantStrings = constantStringsOf(constantType);
			if (UNEXPECTED(constantStrings.isUndef())) return zv::Val();
			zend_long stringCount = countOf(constantStrings.raw());
			if (stringCount > 1) {
				// a union of type names narrows by the intersection of the
				// per-name narrowings
				zv::Val intersectedTypes = zv::Val::null();
				for (zv::ArrayEntry entry : zv::ArrRef(constantStrings.raw())) {
					zval *constantString = entry.value().deref().raw();
					zv::Val value = constantStringValue(constantString);
					if (UNEXPECTED(value.isUndef())) return zv::Val();
					zv::Val mapped = getTypeFromGettypeStringValue(value.raw());
					if (UNEXPECTED(mapped.isUndef())) return zv::Val();
					if (mapped.isNull()) continue;
					zv::Val subjectTypes = createSubjectTypes(evaluationScope, subject, subjectResult, constantString, context);
					if (UNEXPECTED(subjectTypes.isUndef())) return zv::Val();
					zv::Val one = unionWith(std::move(subjectTypes), createForSubject(firstArgValue(call), mapped.raw(), context, evaluationScope));
					if (UNEXPECTED(one.isUndef())) return zv::Val();
					intersectedTypes = intersectedTypes.isNull() ? std::move(one) : intersectWith(std::move(intersectedTypes), std::move(one));
					if (UNEXPECTED(intersectedTypes.isUndef())) return zv::Val();
				}
				if (!intersectedTypes.isNull()) return intersectedTypes;

				// no known type names - only pin the call
			}
			if (stringCount == 1) {
				zv::Val value = constantStringValue(firstOf(constantStrings.raw()));
				if (UNEXPECTED(value.isUndef())) return zv::Val();
				zv::Val gettypeNarrowedType = getTypeFromGettypeStringValue(value.raw());
				if (UNEXPECTED(gettypeNarrowedType.isUndef())) return zv::Val();
				if (!gettypeNarrowedType.isNull()) {
					zv::Val subjectTypes = createSubjectTypes(evaluationScope, subject, subjectResult, constantType, context);
					if (UNEXPECTED(subjectTypes.isUndef())) return zv::Val();
					return unionWith(std::move(subjectTypes), createForSubject(firstArgValue(call), gettypeNarrowedType.raw(), context, evaluationScope));
				}
				// an unknown type-name string only pins the call itself below
			}

			// a non-constant string side only pins the call
		}

		// get_class($o) === 'Foo' pins $o to a final Foo when the comparison
		// holds; outside the true context only the call itself narrows
		if (nameIn(name, { "get_class", "get_debug_type" })) {
			bool isTrue;
			if (UNEXPECTED(!ctxTrue(context, isTrue))) return zv::Val();
			if (isTrue) {
				zv::Val narrowedObjectType = zv::Val::null();
				zv::Val constantStrings = constantStringsOf(constantType);
				if (UNEXPECTED(constantStrings.isUndef())) return zv::Val();
				bool single = countOf(constantStrings.raw()) == 1;
				bool known = false;
				if (single) {
					zv::Val value = constantStringValue(firstOf(constantStrings.raw()));
					if (UNEXPECTED(value.isUndef())) return zv::Val();
					if (UNEXPECTED(!hasClass(value.raw(), known))) return zv::Val();
				}
				if (single && known) {
					zv::Val className = constantStringValue(firstOf(constantStrings.raw()));
					if (UNEXPECTED(className.isUndef())) return zv::Val();
					if (UNEXPECTED(Z_TYPE_P(className.raw()) != IS_STRING)) {
						zend_type_error("PHPStan\\Type\\ObjectType::__construct(): Argument #1 ($className) must be of type string, %s given", zend_zval_value_name(className.raw()));
						return zv::Val();
					}
					narrowedObjectType = finalObjectType(className.raw());
					if (UNEXPECTED(narrowedObjectType.isUndef())) return zv::Val();
				} else {
					zv::Val classStringObjectType = typeCall(constantType, "getClassStringObjectType", PT_LC("getclassstringobjecttype"));
					if (UNEXPECTED(classStringObjectType.isUndef())) return zv::Val();
					zend_long isObject = trinaryByName(classStringObjectType.raw(), "isObject", PT_LC("isobject"));
					if (UNEXPECTED(isObject < 0)) return zv::Val();
					if (isObject == PT_TRI_YES) {
						narrowedObjectType = typeCall(constantType, "getClassStringObjectType", PT_LC("getclassstringobjecttype"));
						if (UNEXPECTED(narrowedObjectType.isUndef())) return zv::Val();
					}
				}

				if (!narrowedObjectType.isNull()) {
					zv::Val argTypes = createForSubject(firstArgValue(call), narrowedObjectType.raw(), context, evaluationScope);
					if (UNEXPECTED(argTypes.isUndef())) return zv::Val();
					return unionWith(std::move(argTypes), createSubjectTypes(evaluationScope, subject, subjectResult, constantType, context));
				}
			}
		}

		return zv::Val::boolean(false);
	}

	/* $this->literalType($expr) ?? $result->getTypeOnScope($evaluationScope,
	 * $evaluationScope->nativeTypesPromoted) */
	static zv::Val literalTypeOrResultType(zval *expr, zval *result, zval *evaluationScope)
	{
		zv::Val literal = literalType(expr);
		if (UNEXPECTED(literal.isUndef())) return zv::Val();
		if (!literal.isNull()) return literal;
		return typeOnScope(result, evaluationScope);
	}

	/* Mirrors literalType(): a Type or null */
	static zv::Val literalType(zval *expr)
	{
		if (isA(expr, PT_CLASS_SCALAR_INT)) {
			zval *value = nodeProp(pt_inh_int_value_site, expr, PT_LC("value"));
			return newConstantInteger(Z_TYPE_P(value) == IS_LONG ? Z_LVAL_P(value) : zval_get_long(value));
		}
		if (isA(expr, PT_CLASS_SCALAR_FLOAT)) {
			zval *value = nodeProp(pt_inh_float_value_site, expr, PT_LC("value"));
			return newConstantFloat(Z_TYPE_P(value) == IS_DOUBLE ? Z_DVAL_P(value) : zval_get_double(value));
		}
		if (isA(expr, PT_CLASS_SCALAR_STRING)) {
			zval *value = nodeProp(pt_inh_string_value_site, expr, PT_LC("value"));
			if (UNEXPECTED(Z_TYPE_P(value) != IS_STRING)) {
				zend_type_error("PHPStan\\Type\\Constant\\ConstantStringType::__construct(): Argument #1 ($value) must be of type string, %s given", zend_zval_value_name(value));
				return zv::Val();
			}
			return newConstantString(Z_STR_P(value));
		}
		switch (constFetchLiteral(expr)) {
			case LITERAL_TRUE:
				return newConstantBoolean(true);
			case LITERAL_FALSE:
				return newConstantBoolean(false);
			case LITERAL_NULL:
				return newNullType();
			default:
				break;
		}
		if (UNEXPECTED(EG(exception))) return zv::Val();

		return zv::Val::null();
	}

	/* Mirrors isScalarLiteral(). */
	static bool isScalarLiteral(zval *expr)
	{
		if (isA(expr, PT_CLASS_SCALAR_INT) || isA(expr, PT_CLASS_SCALAR_STRING) || isA(expr, PT_CLASS_SCALAR_FLOAT)) return true;

		// Foo::BAR, Suit::Hearts, Foo::class - but not $a::class
		return isA(expr, PT_CLASS_CLASS_CONST_FETCH)
			&& isA(classOf(expr), PT_CLASS_NAME)
			&& !isA(nameOf(expr), PT_CLASS_EXPR);
	}

	/* Mirrors isSubjectCoveredAgainstConstant(). */
	static bool isSubjectCoveredAgainstConstant(zval *subject)
	{
		zval *unwrapped = unwrap(subject);
		if (isA(unwrapped, PT_CLASS_FUNC_CALL)) return false;

		return !(isA(unwrapped, PT_CLASS_CLASS_CONST_FETCH) && isA(classOf(unwrapped), PT_CLASS_EXPR));
	}

	/* fn (): Type => $this->richerScopeGetTypeHelper->getIdenticalResult(
	 * $evaluationScope, new Expr\BinaryOp\Identical($left, $right),
	 * $nodeScopeResolver)->type — captures: $this, $evaluationScope, $left,
	 * $right, $nodeScopeResolver */
	static void identicalTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) argc;
		(void) argv;
		zv::Args identicalArgv{&captures[2], &captures[3]};
		zv::Val identical = pt_type_new(PT_CLASS_IDENTICAL_EXPR, 2, identicalArgv);
		if (UNEXPECTED(identical.isUndef())) return;
		zval *helper = OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::richerScopeGetTypeHelper);
		if (UNEXPECTED(Z_TYPE_P(helper) != IS_OBJECT)) {
			(void) callOnNonObject("getIdenticalResult", helper);
			return;
		}
		zv::Val result = getIdenticalResult(helper, &captures[1], identical.raw(), &captures[4]);
		if (UNEXPECTED(result.isUndef())) return;
		zv::Val type = typeResultType(result.raw());
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::IdenticalNarrowingHelper;

/* {{{ direct entries (support.h): the native body for the native class (the
 * twin is final), the method by name for anything else */

namespace {

inline bool isNativeHelper(zval *helper)
{
	return EXPECTED(Z_OBJCE_P(helper) == pt_ce_identical_narrowing_helper);
}

inline zval *nullable(zval *value)
{
	return value != NULL && Z_TYPE_P(value) != IS_NULL ? value : NULL;
}

inline zval *orNull(zval *value, zval &nullZv)
{
	if (value != NULL) return value;
	ZVAL_NULL(&nullZv);
	return &nullZv;
}

} // namespace

zv::Val pt_identical_narrowing_helper_specify_identical(zval *helper, zval *nodeScopeResolver, zval *left, zval *right, zval *leftResult, zval *rightResult, zval *context, zval *evaluationScope, zval *leftArgResult, zval *rightArgResult, zval *identicalTypeCallback)
{
	if (isNativeHelper(helper)) return IdenticalNarrowingHelper(Z_OBJ_P(helper)).specifyIdentical(nodeScopeResolver, left, right, leftResult, rightResult, context, evaluationScope, nullable(leftArgResult), nullable(rightArgResult), identicalTypeCallback);
	zval nullLeft, nullRight;
	zv::Args argv{nodeScopeResolver, left, right, leftResult, rightResult, context, evaluationScope, orNull(leftArgResult, nullLeft), orNull(rightArgResult, nullRight), identicalTypeCallback};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("specifyidentical"), 10, argv);
}

zv::Val pt_identical_narrowing_helper_specify_equal(zval *helper, zval *nodeScopeResolver, zval *left, zval *right, zval *leftResult, zval *rightResult, zval *context, zval *evaluationScope, zval *leftArgResult, zval *rightArgResult)
{
	if (isNativeHelper(helper)) return IdenticalNarrowingHelper(Z_OBJ_P(helper)).specifyEqual(nodeScopeResolver, left, right, leftResult, rightResult, context, evaluationScope, nullable(leftArgResult), nullable(rightArgResult));
	zval nullLeft, nullRight;
	zv::Args argv{nodeScopeResolver, left, right, leftResult, rightResult, context, evaluationScope, orNull(leftArgResult, nullLeft), orNull(rightArgResult, nullRight)};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("specifyequal"), 9, argv);
}

zv::Val pt_identical_narrowing_helper_specify_identical_against_type(zval *helper, zval *subject, zval *subjectResult, zval *constantExpr, zval *constantType, zval *context, zval *evaluationScope, zval *subjectArgResult, zval *identicalTypeCallback)
{
	if (isNativeHelper(helper)) return IdenticalNarrowingHelper(Z_OBJ_P(helper)).specifyIdenticalAgainstType(subject, subjectResult, constantExpr, constantType, context, evaluationScope, nullable(subjectArgResult), identicalTypeCallback);
	zval nullZv;
	zv::Args argv{subject, subjectResult, constantExpr, constantType, context, evaluationScope, orNull(subjectArgResult, nullZv), identicalTypeCallback};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("specifyidenticalagainsttype"), 8, argv);
}

zv::Val pt_identical_narrowing_helper_capture_first_arg_result(zval *helper, zval *side, zval *storage)
{
	if (isNativeHelper(helper)) return IdenticalNarrowingHelper(Z_OBJ_P(helper)).captureFirstArgResult(side, storage);
	zv::Args argv{side, storage};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("capturefirstargresult"), 2, argv);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_INH_THIS IdenticalNarrowingHelper(Z_OBJ_P(ZEND_THIS))

void pt_register_identical_narrowing_helper()
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\Helper\\IdenticalNarrowingHelper");
	ptdecl::IdenticalNarrowingHelper::declareClass(cls);
	ptdecl::IdenticalNarrowingHelper::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *defaultNarrowingHelper, *reflectionProvider, *countNarrowingHelper, *exprPrinter, *richerScopeGetTypeHelper;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, defaultNarrowingHelper, reflectionProvider, countNarrowingHelper, exprPrinter, richerScopeGetTypeHelper)) RETURN_THROWS();
		PT_INH_THIS.construct(defaultNarrowingHelper, reflectionProvider, countNarrowingHelper, exprPrinter, richerScopeGetTypeHelper);
	});

	cls.method(sigs::specifyIdentical, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *left, *right, *leftResult, *rightResult, *context, *evaluationScope, *leftArgResult, *rightArgResult, *identicalTypeCallback;
		ZEND_PARSE_PARAMETERS_START(10, 10)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(left)
			Z_PARAM_OBJECT(right)
			Z_PARAM_OBJECT(leftResult)
			Z_PARAM_OBJECT(rightResult)
			Z_PARAM_OBJECT(context)
			Z_PARAM_OBJECT(evaluationScope)
			Z_PARAM_OBJECT_OR_NULL(leftArgResult)
			Z_PARAM_OBJECT_OR_NULL(rightArgResult)
			Z_PARAM_ZVAL(identicalTypeCallback)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!zend_is_callable(identicalTypeCallback, 0, NULL))) {
			zend_argument_type_error(10, "must be of type callable, %s given", zend_zval_value_name(identicalTypeCallback));
			RETURN_THROWS();
		}
		PT_RETURN_VAL(PT_INH_THIS.specifyIdentical(nodeScopeResolver, left, right, leftResult, rightResult, context, evaluationScope, leftArgResult, rightArgResult, identicalTypeCallback));
	});

	cls.method(sigs::specifyEqual, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *left, *right, *leftResult, *rightResult, *context, *evaluationScope, *leftArgResult, *rightArgResult;
		ZEND_PARSE_PARAMETERS_START(9, 9)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(left)
			Z_PARAM_OBJECT(right)
			Z_PARAM_OBJECT(leftResult)
			Z_PARAM_OBJECT(rightResult)
			Z_PARAM_OBJECT(context)
			Z_PARAM_OBJECT(evaluationScope)
			Z_PARAM_OBJECT_OR_NULL(leftArgResult)
			Z_PARAM_OBJECT_OR_NULL(rightArgResult)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_INH_THIS.specifyEqual(nodeScopeResolver, left, right, leftResult, rightResult, context, evaluationScope, leftArgResult, rightArgResult));
	});

	cls.method(sigs::specifyIdenticalAgainstType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *subject, *subjectResult, *constantExpr, *constantType, *context, *evaluationScope, *subjectArgResult, *identicalTypeCallback;
		ZEND_PARSE_PARAMETERS_START(8, 8)
			Z_PARAM_OBJECT(subject)
			Z_PARAM_OBJECT(subjectResult)
			Z_PARAM_OBJECT(constantExpr)
			Z_PARAM_OBJECT(constantType)
			Z_PARAM_OBJECT(context)
			Z_PARAM_OBJECT(evaluationScope)
			Z_PARAM_OBJECT_OR_NULL(subjectArgResult)
			Z_PARAM_ZVAL(identicalTypeCallback)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!zend_is_callable(identicalTypeCallback, 0, NULL))) {
			zend_argument_type_error(8, "must be of type callable, %s given", zend_zval_value_name(identicalTypeCallback));
			RETURN_THROWS();
		}
		PT_RETURN_VAL(PT_INH_THIS.specifyIdenticalAgainstType(subject, subjectResult, constantExpr, constantType, context, evaluationScope, subjectArgResult, identicalTypeCallback));
	});

	cls.method(sigs::captureFirstArgResult, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *side, *storage;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, side, storage)) RETURN_THROWS();
		PT_RETURN_VAL(PT_INH_THIS.captureFirstArgResult(side, storage));
	});

	cls.shadow(&pt_ce_identical_narrowing_helper);
}

#undef PT_INH_THIS

/* }}} */

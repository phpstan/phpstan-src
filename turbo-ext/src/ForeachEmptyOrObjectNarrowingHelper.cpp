/*
 * PHPStanTurbo\ForeachEmptyOrObjectNarrowingHelper — native implementation of
 * PHPStan\Analyser\ExprHandler\Helper\ForeachEmptyOrObjectNarrowingHelper.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it, the four collaborators live in the twin's
 * property slots. narrowToEmptyOrObject() composes the truthy narrowing of
 * `$iteratee === [] || is_object($iteratee)` through
 * BooleanNarrowingHelper::specifyDisjunction() the way the twin does; the
 * twin's closures are native closures over the same captures, and the
 * collaborators are called through their direct entries.
 */

#include "support.h"
#include "generated/ForeachEmptyOrObjectNarrowingHelper.h"

namespace slots = ptdecl::ForeachEmptyOrObjectNarrowingHelper::slot;
namespace sigs = ptdecl::ForeachEmptyOrObjectNarrowingHelper::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "AnalyserValues.h"

zend_class_entry *pt_ce_foreach_empty_or_object_narrowing_helper = nullptr;

/* the permanent interned string of the twin's literal (module startup) */
static zend_string *pt_feoh_is_object = nullptr;

namespace {

/* new FullyQualified('is_object') */
zv::Val newIsObjectName()
{
	zval name;
	ZVAL_INTERNED_STR(&name, pt_feoh_is_object);
	return pt_name_node_new(PT_CLASS_FULLY_QUALIFIED, &name);
}

/* TypeSpecifierContext::createTruthy() / ::createFalsey() as a zval */
bool contextZval(zval *out, bool truthy)
{
	zend_object *context = truthy ? pt_type_specifier_context_create_truthy() : pt_type_specifier_context_create_falsey();
	if (UNEXPECTED(context == NULL)) return false;
	ZVAL_OBJ(out, context);
	return true;
}

/* self::emptyArrayComparison(): the type of `$iteratee === []` */
zv::Val emptyArrayComparison(zval *iterateeType, zval *emptyArrayType)
{
	if (UNEXPECTED(Z_TYPE_P(iterateeType) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function equals() on %s", zend_zval_value_name(iterateeType));
		return zv::Val();
	}
	zv::Val equals = pt_type_op(Z_OBJ_P(iterateeType), PT_OP_EQUALS, 1, emptyArrayType);
	if (UNEXPECTED(equals.isUndef())) return zv::Val();
	zval out;
	if (zend_is_true(equals.raw())) {
		if (UNEXPECTED(!pt_constant_boolean_type_new(&out, true))) return zv::Val();
		return zv::Val::adopt(out);
	}
	zv::Val superType = pt_type_op(Z_OBJ_P(emptyArrayType), PT_OP_IS_SUPER_TYPE_OF, 1, iterateeType);
	if (UNEXPECTED(superType.isUndef())) return zv::Val();
	if (UNEXPECTED(Z_TYPE_P(superType.raw()) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function no() on %s", zend_zval_value_name(superType.raw()));
		return zv::Val();
	}
	if (pt_result_value(Z_OBJ_P(superType.raw())) == PT_TRI_NO) {
		if (UNEXPECTED(!pt_constant_boolean_type_new(&out, false))) return zv::Val();
		return zv::Val::adopt(out);
	}
	if (UNEXPECTED(!pt_boolean_type_new(&out))) return zv::Val();
	return zv::Val::adopt(out);
}

/* self::isObjectVerdict(): the type of `is_object($iteratee)` */
zv::Val isObjectVerdict(zval *iterateeType)
{
	if (UNEXPECTED(Z_TYPE_P(iterateeType) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function isObject() on %s", zend_zval_value_name(iterateeType));
		return zv::Val();
	}
	zend_long isObject = pt_type_call_trinary(Z_OBJ_P(iterateeType), PT_LC("isobject"), 0, NULL);
	if (UNEXPECTED(isObject < 0)) return zv::Val();
	zval out;
	if (isObject == PT_TRI_YES) {
		if (UNEXPECTED(!pt_constant_boolean_type_new(&out, true))) return zv::Val();
	} else if (isObject == PT_TRI_NO) {
		if (UNEXPECTED(!pt_constant_boolean_type_new(&out, false))) return zv::Val();
	} else if (UNEXPECTED(!pt_boolean_type_new(&out))) {
		return zv::Val();
	}
	return zv::Val::adopt(out);
}

/* $iterateeResult->getType() / ->getNativeType() */
zv::Val iterateeTypeOf(zval *iterateeResult, bool nativeTypesPromoted)
{
	return nativeTypesPromoted ? pt_expression_result_get_native_type(iterateeResult) : pt_expression_result_get_type(iterateeResult);
}

/* static fn (): Type => self::emptyArrayComparison($iterateeResult->getType(), $emptyArrayType)
 * — captures: $iterateeResult, $emptyArrayType */
void identicalTypeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
{
	(void) argc;
	(void) argv;
	zv::Val iterateeType = pt_expression_result_get_type(&captures[0]);
	if (UNEXPECTED(iterateeType.isUndef())) return;
	zv::Val type = emptyArrayComparison(iterateeType.raw(), &captures[1]);
	if (UNEXPECTED(type.isUndef())) return;
	type.intoReturnValue(return_value);
}

/* $leftTypes($s, $context): $identicalNarrowingHelper->specifyIdenticalAgainstType(...)
 * — captures: $identicalNarrowingHelper, $identical->left, $iterateeResult,
 * $identical->right, $emptyArrayType, $subjectArgResult, the identical type
 * callback; null when the comparison cannot be composed */
zv::Val leftTypesOf(zval *captures, zval *s, zval *context)
{
	return pt_identical_narrowing_helper_specify_identical_against_type(&captures[0], &captures[1], &captures[2], &captures[3], &captures[4], context, s, &captures[5], &captures[6]);
}

/* static fn (MutatingScope $s, TypeSpecifierContext $context): SpecifiedTypes
 * => $leftTypes($s, $context) ?? new SpecifiedTypes() */
void leftTypesBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
{
	if (UNEXPECTED(argc < 2)) {
		zend_throw_error(NULL, "phpstan_turbo: the left narrowing callback expects a scope and a context");
		return;
	}
	zv::Val types = leftTypesOf(captures, &argv[0], &argv[1]);
	if (UNEXPECTED(types.isUndef())) return;
	if (types.isNull()) types = pt_specified_types_new();
	if (UNEXPECTED(types.isUndef())) return;
	types.intoReturnValue(return_value);
}

/* $rightTypes($s, $context): the conditional return type narrowing of
 * is_object() — captures: $defaultNarrowingHelper, $isObjectCall, its args,
 * $isObjectFunction; null when there is none */
zv::Val rightTypesOf(zval *captures, zval *s, zval *context)
{
	zval *isObjectFunction = &captures[3];
	if (UNEXPECTED(Z_TYPE_P(isObjectFunction) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function getVariants() on %s", zend_zval_value_name(isObjectFunction));
		return zv::Val();
	}
	zv::Val variants = pt_type_call(Z_OBJ_P(isObjectFunction), PT_LC("getvariants"), 0, NULL);
	if (UNEXPECTED(variants.isUndef())) return zv::Val();
	zv::Val namedArgumentsVariants = pt_type_call(Z_OBJ_P(isObjectFunction), PT_LC("getnamedargumentsvariants"), 0, NULL);
	if (UNEXPECTED(namedArgumentsVariants.isUndef())) return zv::Val();
	zv::Val acceptor = pt_parameters_acceptor_selector_select_from_args(s, &captures[2], variants.raw(), namedArgumentsVariants.raw());
	if (UNEXPECTED(acceptor.isUndef())) return zv::Val();
	return pt_default_narrowing_helper_specify_types_from_conditional_return_type(&captures[0], context, &captures[1], acceptor.raw(), s);
}

/* static fn (MutatingScope $s, TypeSpecifierContext $context): SpecifiedTypes
 * => $rightTypes($s, $context) ?? new SpecifiedTypes() */
void rightTypesBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
{
	if (UNEXPECTED(argc < 2)) {
		zend_throw_error(NULL, "phpstan_turbo: the right narrowing callback expects a scope and a context");
		return;
	}
	zv::Val types = rightTypesOf(captures, &argv[0], &argv[1]);
	if (UNEXPECTED(types.isUndef())) return;
	if (types.isNull()) types = pt_specified_types_new();
	if (UNEXPECTED(types.isUndef())) return;
	types.intoReturnValue(return_value);
}

/* static fn (bool $nativeTypesPromoted): Type => self::emptyArrayComparison(...)
 * — captures: $iterateeResult, $emptyArrayType */
void leftTypeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
{
	zv::Val iterateeType = iterateeTypeOf(&captures[0], argc > 0 && zend_is_true(&argv[0]));
	if (UNEXPECTED(iterateeType.isUndef())) return;
	zv::Val type = emptyArrayComparison(iterateeType.raw(), &captures[1]);
	if (UNEXPECTED(type.isUndef())) return;
	type.intoReturnValue(return_value);
}

/* static fn (bool $nativeTypesPromoted): Type => self::isObjectVerdict(...)
 * — captures: $iterateeResult */
void rightTypeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
{
	zv::Val iterateeType = iterateeTypeOf(&captures[0], argc > 0 && zend_is_true(&argv[0]));
	if (UNEXPECTED(iterateeType.isUndef())) return;
	zv::Val type = isObjectVerdict(iterateeType.raw());
	if (UNEXPECTED(type.isUndef())) return;
	type.intoReturnValue(return_value);
}

/* static fn (): MutatingScope => $scope — captures: $scope */
void scopeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
{
	(void) argc;
	(void) argv;
	ZVAL_COPY(return_value, &captures[0]);
}

/* $types->setRootExpr($rootExpr) applied to $scope */
zv::Val applyRooted(zval *scope, zval *types, zval *rootExpr)
{
	zv::Val rooted = pt_specified_types_set_root_expr(Z_OBJ_P(types), rootExpr);
	if (UNEXPECTED(rooted.isUndef())) return zv::Val();
	return pt_mutating_scope_apply_specified_types(Z_OBJ_P(scope), rooted.raw());
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\Helper\ForeachEmptyOrObjectNarrowingHelper. */
class ForeachEmptyOrObjectNarrowingHelper
{
public:
	explicit ForeachEmptyOrObjectNarrowingHelper(zend_object *self) : self(self) {}

	/* __construct(private ReflectionProvider $reflectionProvider, private
	 * IdenticalNarrowingHelper $identicalNarrowingHelper, private
	 * DefaultNarrowingHelper $defaultNarrowingHelper, private
	 * BooleanNarrowingHelper $booleanNarrowingHelper) */
	void construct(zval *reflectionProvider, zval *identicalNarrowingHelper, zval *defaultNarrowingHelper, zval *booleanNarrowingHelper) const
	{
		pt_write_slot(self, slots::reflectionProvider, reflectionProvider);
		pt_write_slot(self, slots::identicalNarrowingHelper, identicalNarrowingHelper);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
		pt_write_slot(self, slots::booleanNarrowingHelper, booleanNarrowingHelper);
	}

	/* Mirrors narrowToEmptyOrObject(). UNDEF = pending exception */
	zv::Val narrowToEmptyOrObject(zval *nodeScopeResolver, zval *scope, zval *iteratee, zval *iterateeResult, zval *storage) const
	{
		zval emptyItems;
		ZVAL_EMPTY_ARRAY(&emptyItems);
		zv::Val emptyArray = pt_type_new(PT_CLASS_ARRAY_EXPR, 1, &emptyItems);
		if (UNEXPECTED(emptyArray.isUndef())) return zv::Val();
		zv::Args identicalArgv{iteratee, emptyArray.raw()};
		zv::Val identical = pt_type_new(PT_CLASS_IDENTICAL_EXPR, 2, identicalArgv);
		if (UNEXPECTED(identical.isUndef())) return zv::Val();
		zv::Val name = newIsObjectName();
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		zv::Val arg = pt_type_new(PT_CLASS_ARG, 1, iteratee);
		if (UNEXPECTED(arg.isUndef())) return zv::Val();
		zv::Arr args = zv::Arr::create(1);
		args.push(std::move(arg));
		zv::Args callArgv{name.raw(), args.raw()};
		zv::Val isObjectCall = pt_type_new(PT_CLASS_FUNC_CALL, 2, callArgv);
		if (UNEXPECTED(isObjectCall.isUndef())) return zv::Val();
		zv::Args orArgv{identical.raw(), isObjectCall.raw()};
		zv::Val condition = pt_type_new(PT_CLASS_BOOLEAN_OR_EXPR, 2, orArgv);
		if (UNEXPECTED(condition.isUndef())) return zv::Val();

		zv::Val composed = composeScope(nodeScopeResolver, scope, condition.raw(), identical.raw(), iteratee, emptyArray.raw(), isObjectCall.raw(), args.raw(), iterateeResult, storage);
		if (UNEXPECTED(composed.isUndef())) return zv::Val();
		if (!composed.isNull()) return composed;
		zval truthy;
		if (UNEXPECTED(!contextZval(&truthy, true))) return zv::Val();
		return pt_node_scope_resolver_narrow_scope_with_condition(nodeScopeResolver, scope, condition.raw(), &truthy);
	}

	/* Mirrors the private composeScope(); null when a side cannot be
	 * composed, UNDEF = pending exception. The nodes' operands come in
	 * directly ($identical->left / ->right, the call's args) instead of
	 * being read back */
	zv::Val composeScope(zval *nodeScopeResolver, zval *scope, zval *condition, zval *identical, zval *identicalLeft, zval *identicalRight, zval *isObjectCall, zval *isObjectArgs, zval *iterateeResult, zval *storage) const
	{
		zval *reflectionProvider = OBJ_PROP_NUM(self, slots::reflectionProvider);
		zval *identicalNarrowingHelper = OBJ_PROP_NUM(self, slots::identicalNarrowingHelper);
		zval *defaultNarrowingHelper = OBJ_PROP_NUM(self, slots::defaultNarrowingHelper);
		zval *booleanNarrowingHelper = OBJ_PROP_NUM(self, slots::booleanNarrowingHelper);
		if (UNEXPECTED(Z_TYPE_P(booleanNarrowingHelper) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function specifyDisjunction() on %s", zend_zval_value_name(booleanNarrowingHelper));
			return zv::Val();
		}

		zv::Val isObjectName = newIsObjectName();
		if (UNEXPECTED(isObjectName.isUndef())) return zv::Val();
		bool hasFunction;
		if (UNEXPECTED(!pt_reflection_provider_has_function(reflectionProvider, isObjectName.raw(), scope, hasFunction))) return zv::Val();
		if (!hasFunction) return zv::Val::null();
		zv::Val isObjectFunction = pt_reflection_provider_get_function(reflectionProvider, isObjectName.raw(), scope);
		if (UNEXPECTED(isObjectFunction.isUndef())) return zv::Val();
		zv::Val emptyArrayType;
		{
			zval keyTypes, valueTypes, out;
			ZVAL_EMPTY_ARRAY(&keyTypes);
			ZVAL_EMPTY_ARRAY(&valueTypes);
			if (UNEXPECTED(!pt_constant_array_type_new(&out, &keyTypes, &valueTypes))) return zv::Val();
			emptyArrayType = zv::Val::adopt(out);
		}
		zv::Val subjectArgResult = pt_identical_narrowing_helper_capture_first_arg_result(identicalNarrowingHelper, identicalLeft, storage);
		if (UNEXPECTED(subjectArgResult.isUndef())) return zv::Val();

		zv::Val identicalTypeCallback = pt_native_closure(&identicalTypeBody, iterateeResult, emptyArrayType.raw());
		zval leftCaptures[7];
		ZVAL_COPY_VALUE(&leftCaptures[0], identicalNarrowingHelper);
		ZVAL_COPY_VALUE(&leftCaptures[1], identicalLeft);
		ZVAL_COPY_VALUE(&leftCaptures[2], iterateeResult);
		ZVAL_COPY_VALUE(&leftCaptures[3], identicalRight);
		ZVAL_COPY_VALUE(&leftCaptures[4], emptyArrayType.raw());
		ZVAL_COPY_VALUE(&leftCaptures[5], subjectArgResult.raw());
		ZVAL_COPY_VALUE(&leftCaptures[6], identicalTypeCallback.raw());
		zval rightCaptures[4];
		ZVAL_COPY_VALUE(&rightCaptures[0], defaultNarrowingHelper);
		ZVAL_COPY_VALUE(&rightCaptures[1], isObjectCall);
		ZVAL_COPY_VALUE(&rightCaptures[2], isObjectArgs);
		ZVAL_COPY_VALUE(&rightCaptures[3], isObjectFunction.raw());

		zval truthy, falsey;
		if (UNEXPECTED(!contextZval(&truthy, true) || !contextZval(&falsey, false))) return zv::Val();
		zv::Val leftTruthyTypes = leftTypesOf(leftCaptures, scope, &truthy);
		if (UNEXPECTED(leftTruthyTypes.isUndef())) return zv::Val();
		zv::Val leftFalseyTypes = leftTypesOf(leftCaptures, scope, &falsey);
		if (UNEXPECTED(leftFalseyTypes.isUndef())) return zv::Val();
		if (leftTruthyTypes.isNull() || leftFalseyTypes.isNull()) return zv::Val::null();
		zv::Val leftFalseyScope = applyRooted(scope, leftFalseyTypes.raw(), identical);
		if (UNEXPECTED(leftFalseyScope.isUndef())) return zv::Val();
		zv::Val rightTruthyTypes = rightTypesOf(rightCaptures, leftFalseyScope.raw(), &truthy);
		if (UNEXPECTED(rightTruthyTypes.isUndef())) return zv::Val();
		if (rightTruthyTypes.isNull()) return zv::Val::null();
		zv::Val leftTruthyScope = applyRooted(scope, leftTruthyTypes.raw(), identical);
		if (UNEXPECTED(leftTruthyScope.isUndef())) return zv::Val();
		zv::Val rightTruthyScope = applyRooted(leftFalseyScope.raw(), rightTruthyTypes.raw(), isObjectCall);
		if (UNEXPECTED(rightTruthyScope.isUndef())) return zv::Val();

		zv::Val leftTypesCallback = pt_native_closure_new(&leftTypesBody, 7, leftCaptures);
		zv::Val leftTypeCallback = pt_native_closure(&leftTypeBody, iterateeResult, emptyArrayType.raw());
		zv::Val leftTruthyScopeCallback = pt_native_closure(&scopeBody, leftTruthyScope.raw());
		zv::Val leftFalseyScopeCallback = pt_native_closure(&scopeBody, leftFalseyScope.raw());
		zv::Val rightTypesCallback = pt_native_closure_new(&rightTypesBody, 4, rightCaptures);
		zv::Val rightTypeCallback = pt_native_closure(&rightTypeBody, iterateeResult);
		zv::Val rightTruthyScopeCallback = pt_native_closure(&scopeBody, rightTruthyScope.raw());
		zv::Val types = pt_boolean_narrowing_helper_specify_disjunction(
			Z_OBJ_P(booleanNarrowingHelper),
			nodeScopeResolver,
			scope,
			&truthy,
			condition,
			identical,
			leftTypesCallback.raw(),
			leftTypeCallback.raw(),
			leftTruthyScopeCallback.raw(),
			leftFalseyScopeCallback.raw(),
			isObjectCall,
			rightTypesCallback.raw(),
			rightTypeCallback.raw(),
			rightTruthyScopeCallback.raw()
		);
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		return pt_mutating_scope_apply_specified_types(Z_OBJ_P(scope), types.raw());
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::ForeachEmptyOrObjectNarrowingHelper;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_foreach_empty_or_object_narrowing_helper)
{
	pt_feoh_is_object = zend_string_init_interned(PT_LC("is_object"), 1);

	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\Helper\\ForeachEmptyOrObjectNarrowingHelper");
	ptdecl::ForeachEmptyOrObjectNarrowingHelper::declareClass(cls);
	ptdecl::ForeachEmptyOrObjectNarrowingHelper::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *reflectionProvider, *identicalNarrowingHelper, *defaultNarrowingHelper, *booleanNarrowingHelper;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, reflectionProvider, identicalNarrowingHelper, defaultNarrowingHelper, booleanNarrowingHelper)) RETURN_THROWS();
		ForeachEmptyOrObjectNarrowingHelper(Z_OBJ_P(ZEND_THIS)).construct(reflectionProvider, identicalNarrowingHelper, defaultNarrowingHelper, booleanNarrowingHelper);
	});

	cls.method(sigs::narrowToEmptyOrObject, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *scope, *iteratee, *iterateeResult, *storage;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, nodeScopeResolver, scope, iteratee, iterateeResult, storage)) RETURN_THROWS();
		PT_RETURN_VAL(ForeachEmptyOrObjectNarrowingHelper(Z_OBJ_P(ZEND_THIS)).narrowToEmptyOrObject(nodeScopeResolver, scope, iteratee, iterateeResult, storage));
	});

	cls.method(sigs::composeScope, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *scope, *condition, *identical, *isObjectCall, *iterateeResult, *storage;
		ZEND_PARSE_PARAMETERS_START(7, 7)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(condition)
			Z_PARAM_OBJECT(identical)
			Z_PARAM_OBJECT(isObjectCall)
			Z_PARAM_OBJECT(iterateeResult)
			Z_PARAM_OBJECT(storage)
		ZEND_PARSE_PARAMETERS_END();
		/* the private method's operands read back from the nodes, as the twin does */
		zval leftRv, rightRv, argsRv;
		zval *left = zend_read_property(Z_OBJCE_P(identical), Z_OBJ_P(identical), PT_LC("left"), 0, &leftRv);
		zval *right = zend_read_property(Z_OBJCE_P(identical), Z_OBJ_P(identical), PT_LC("right"), 0, &rightRv);
		zval *args = zend_read_property(Z_OBJCE_P(isObjectCall), Z_OBJ_P(isObjectCall), PT_LC("args"), 0, &argsRv);
		if (UNEXPECTED(EG(exception))) RETURN_THROWS();
		PT_RETURN_VAL(ForeachEmptyOrObjectNarrowingHelper(Z_OBJ_P(ZEND_THIS)).composeScope(nodeScopeResolver, scope, condition, identical, left, right, isObjectCall, args, iterateeResult, storage));
	});

	cls.method(sigs::emptyArrayComparison, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *iterateeType, *emptyArrayType;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, iterateeType, emptyArrayType)) RETURN_THROWS();
		PT_RETURN_VAL(emptyArrayComparison(iterateeType, emptyArrayType));
	});

	cls.method(sigs::isObjectVerdict, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *iterateeType;
		if (!zp::parse<zp::Obj>(execute_data, iterateeType)) RETURN_THROWS();
		PT_RETURN_VAL(isObjectVerdict(iterateeType));
	});

	cls.shadow(&pt_ce_foreach_empty_or_object_narrowing_helper);
}

/* }}} */

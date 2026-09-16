/*
 * PHPStanTurbo\CoalesceCompositionHelper — native implementation of
 * PHPStan\Analyser\ExprHandler\Helper\CoalesceCompositionHelper.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. Its three public methods are exported for
 * CoalesceHandler.cpp and AssignOpHandler's `??=` as
 * pt_coalesce_composition_helper_get_falsey_specified_types(),
 * _get_right_side_scope_specified_types() and _compose_type(). The isSet()
 * verdict callbacks are native closures (they capture nothing); composeType()'s
 * $leftIsSetType closure is only called in place and is inlined.
 *
 * ExpressionResult, ExpressionResultStorage, MutatingScope, NodeScopeResolver,
 * SpecifiedTypes, TypeSpecifierContext, DefaultNarrowingHelper, TypeCombinator,
 * IssetabilityResolution and the Type kernel are called through their direct
 * entries.
 */

#include "support.h"
#include "generated/CoalesceCompositionHelper.h"

namespace slots = ptdecl::CoalesceCompositionHelper::slot;
namespace sigs = ptdecl::CoalesceCompositionHelper::sig;
#include "OperatorHandlers.h"

zend_class_entry *pt_ce_coalesce_composition_helper = nullptr;

namespace {

/* $resolution->isSet($typeCallback) (IssetabilityResolution.cpp): the ?bool
 * verdict */
zv::Val resolutionIsSet(zval *resolution, zval *typeCallback)
{
	if (UNEXPECTED(Z_TYPE_P(resolution) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function isSet() on %s", zend_zval_value_name(resolution));
		return zv::Val();
	}
	return pt_issetability_resolution_is_set(resolution, typeCallback);
}

/* a TypeSpecifierContext singleton as a zval (borrowed) */
inline zval objectZval(zend_object *object)
{
	zval z;
	ZVAL_OBJ(&z, object);
	return z;
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\Helper\CoalesceCompositionHelper;
 * UNDEF = pending exception. */
class CoalesceCompositionHelper
{
public:
	explicit CoalesceCompositionHelper(zend_object *self) : self(self) {}

	/* the constructor body: the promoted property */
	void construct(zval *defaultNarrowingHelper) const
	{
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
	}

	/* Mirrors getFalseySpecifiedTypes(). */
	zv::Val getFalseySpecifiedTypes(zval *s, zval *evaluationScope, zval *leftExpr, zval *leftResult, zval *rootExpr, zval *context) const
	{
		zv::Val resolution = pt_expression_result_get_issetability_resolution(leftResult, evaluationScope, false, false);
		if (UNEXPECTED(resolution.isUndef())) return zv::Val();
		zv::Val alwaysTrue = pt_native_closure(&alwaysTrueBody);
		zv::Val isset = resolutionIsSet(resolution.raw(), alwaysTrue.raw());
		if (UNEXPECTED(isset.isUndef())) return zv::Val();

		if (Z_TYPE_P(isset.raw()) != IS_TRUE) return pt_specified_types_new();

		zval nullType;
		if (UNEXPECTED(!pt_null_type_new(&nullType))) return zv::Val();
		zv::Val nullTypeHold = zv::Val::adopt(nullType);
		zv::Val negated = pt_type_specifier_context_negate(Z_OBJ_P(context));
		if (UNEXPECTED(negated.isUndef())) return zv::Val();
		zv::Val subjectTypes = pt_default_narrowing_helper_create_subject_types(OBJ_PROP_NUM(self, slots::defaultNarrowingHelper), s, leftExpr, leftResult, nullTypeHold.raw(), negated.raw());
		if (UNEXPECTED(subjectTypes.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(subjectTypes.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function setRootExpr() on %s", zend_zval_value_name(subjectTypes.raw()));
			return zv::Val();
		}
		return pt_specified_types_set_root_expr(Z_OBJ_P(subjectTypes.raw()), rootExpr);
	}

	/* Mirrors getRightSideScopeSpecifiedTypes(). */
	zv::Val getRightSideScopeSpecifiedTypes(zval *s, zval *leftExpr, zval *leftResult, zval *chainResults, zval *rootExpr) const
	{
		zval *defaultNarrowingHelper = OBJ_PROP_NUM(self, slots::defaultNarrowingHelper);
		zv::Val readType = pt_default_narrowing_helper_build_chain_type_reader(defaultNarrowingHelper, chainResults, s);
		if (UNEXPECTED(readType.isUndef())) return zv::Val();
		zend_object *falsey = pt_type_specifier_context_create_falsey();
		if (UNEXPECTED(falsey == NULL)) return zv::Val();
		zval falseyZval = objectZval(falsey);
		return pt_default_narrowing_helper_create_isset_single_subject_non_true_types(defaultNarrowingHelper, s, leftExpr, leftResult, readType.raw(), &falseyZval, rootExpr);
	}

	/* Mirrors composeType(). */
	zv::Val composeType(zval *nodeScopeResolver, zval *leftExpr, zval *leftResult, zval *rightResult, zval *evaluationScopeArg, zval *chainResults, zval *rootExpr, bool nativeTypesPromoted) const
	{
		zv::Val result;
		pt_engine_with_stack([&]() { result = composeTypeBody(nodeScopeResolver, leftExpr, leftResult, rightResult, evaluationScopeArg, chainResults, rootExpr, nativeTypesPromoted); });
		return result;
	}

	/* static function (Type $type): ?bool — the "set and not null" verdict:
	 * null when the type may be null, !isNull()->yes() otherwise */
	static void notNullVerdictBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) captures;
		if (UNEXPECTED(!ptoh::requireArgs(argc, 1, "PHPStan\\Analyser\\ExprHandler\\Helper\\CoalesceCompositionHelper::{closure}"))) return;
		if (UNEXPECTED(Z_TYPE(argv[0]) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function isNull() on %s", zend_zval_value_name(&argv[0]));
			return;
		}
		zend_long isNull = pt_type_op_trinary(Z_OBJ(argv[0]), PT_OP_IS_NULL, 0, NULL);
		if (UNEXPECTED(isNull < 0)) return;
		if (isNull == PT_TRI_MAYBE) return;
		ZVAL_BOOL(return_value, isNull != PT_TRI_YES);
	}

private:
	zend_object *self;

	zv::Val composeTypeBody(zval *nodeScopeResolver, zval *leftExpr, zval *leftResult, zval *rightResult, zval *evaluationScopeArg, zval *chainResults, zval *rootExpr, bool nativeTypesPromoted) const
	{
		// the whole resolution runs in the asked flavour - the native ask maps
		// the evaluation scope once and every read below follows it, so the
		// phpdoc left type never leaks into the native answer
		zv::Val evaluationScopeHold;
		zval *evaluationScope = evaluationScopeArg;
		if (nativeTypesPromoted) {
			evaluationScopeHold = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(evaluationScopeArg));
			if (UNEXPECTED(evaluationScopeHold.isUndef())) return zv::Val();
			evaluationScope = evaluationScopeHold.raw();
		}
		zv::Val resolution = pt_expression_result_get_issetability_resolution(leftResult, evaluationScope, nativeTypesPromoted, false);
		if (UNEXPECTED(resolution.isUndef())) return zv::Val();
		zv::Val notNullVerdict = pt_native_closure(&notNullVerdictBody);
		zv::Val isset = resolutionIsSet(resolution.raw(), notNullVerdict.raw());
		if (UNEXPECTED(isset.isUndef())) return zv::Val();

		if (Z_TYPE_P(isset.raw()) != IS_NULL && Z_TYPE_P(isset.raw()) != IS_FALSE) {
			return leftIsSetType(nodeScopeResolver, leftExpr, leftResult, evaluationScope, chainResults, rootExpr, nativeTypesPromoted);
		}

		// the right side was processed on the left-is-null scope, so its own
		// result is the evaluation point.
		zv::Val rightType = nativeTypesPromoted ? pt_expression_result_get_native_type(rightResult) : pt_expression_result_get_type(rightResult);
		if (UNEXPECTED(rightType.isUndef())) return zv::Val();

		if (Z_TYPE_P(isset.raw()) == IS_NULL) {
			zv::Val leftType = leftIsSetType(nodeScopeResolver, leftExpr, leftResult, evaluationScope, chainResults, rootExpr, nativeTypesPromoted);
			if (UNEXPECTED(leftType.isUndef())) return zv::Val();
			zv::Args unionArgv{leftType.raw(), rightType.raw()};
			return pt_type_combinator_union(2, unionArgv);
		}

		return rightType;
	}

	/* the $leftIsSetType closure: the left side's type when it is set - the
	 * left read on the left-is-set narrowed scope (offsets resolve against the
	 * HasOffset-narrowed parent). The narrowing is tracked by the scope
	 * (getTypeOnScope's authoritative read); only an untracked left side
	 * needs reprocessing there. */
	zv::Val leftIsSetType(zval *nodeScopeResolver, zval *leftExpr, zval *leftResult, zval *evaluationScope, zval *chainResults, zval *rootExpr, bool nativeTypesPromoted) const
	{
		zval *defaultNarrowingHelper = OBJ_PROP_NUM(self, slots::defaultNarrowingHelper);
		zv::Val readType = pt_default_narrowing_helper_build_chain_type_reader(defaultNarrowingHelper, chainResults, evaluationScope);
		if (UNEXPECTED(readType.isUndef())) return zv::Val();
		zend_object *truthy = pt_type_specifier_context_create_truthy();
		if (UNEXPECTED(truthy == NULL)) return zv::Val();
		zval truthyZval = objectZval(truthy);
		zv::Val leftIssetTypes = pt_default_narrowing_helper_create_isset_truthy_chain_types(defaultNarrowingHelper, evaluationScope, leftExpr, readType.raw(), rootExpr, &truthyZval);
		if (UNEXPECTED(leftIssetTypes.isUndef())) return zv::Val();
		zv::Val leftIsSetScope = pt_mutating_scope_apply_specified_types(Z_OBJ_P(evaluationScope), leftIssetTypes.raw());
		if (UNEXPECTED(leftIsSetScope.isUndef())) return zv::Val();
		bool answers;
		if (UNEXPECTED(!pt_expression_result_answers_on_scope(leftResult, leftIsSetScope.raw(), nativeTypesPromoted, answers))) return zv::Val();
		zv::Val leftType;
		if (answers) {
			leftType = pt_expression_result_get_type_on_scope(leftResult, leftIsSetScope.raw(), nativeTypesPromoted);
		} else {
			zv::Val onDemandStorage = pt_expression_result_storage_new();
			if (UNEXPECTED(onDemandStorage.isUndef())) return zv::Val();
			zv::Val onDemandResult = pt_node_scope_resolver_process_expr_on_demand(nodeScopeResolver, leftExpr, leftIsSetScope.raw(), onDemandStorage.raw());
			if (UNEXPECTED(onDemandResult.isUndef())) return zv::Val();
			leftType = pt_expression_result_get_type_on_scope(onDemandResult.raw(), leftIsSetScope.raw(), nativeTypesPromoted);
		}
		if (UNEXPECTED(leftType.isUndef())) return zv::Val();

		return pt_type_combinator_remove_null(leftType.raw());
	}

	/* static fn (): bool => true */
	static void alwaysTrueBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) captures;
		(void) argc;
		(void) argv;
		ZVAL_TRUE(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::CoalesceCompositionHelper;

zv::Val pt_coalesce_composition_helper_get_falsey_specified_types(zval *helper, zval *s, zval *evaluationScope, zval *leftExpr, zval *leftResult, zval *rootExpr, zval *context)
{
	if (EXPECTED(Z_OBJCE_P(helper) == pt_ce_coalesce_composition_helper)) return CoalesceCompositionHelper(Z_OBJ_P(helper)).getFalseySpecifiedTypes(s, evaluationScope, leftExpr, leftResult, rootExpr, context);
	zv::Args argv{s, evaluationScope, leftExpr, leftResult, rootExpr, context};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("getfalseyspecifiedtypes"), 6, argv);
}

/* the `array $chainResults` parameter check of the native path; false =
 * TypeError thrown */
[[nodiscard]] static bool chainResultsArgument(zval *&chainResults, const char *method, int position)
{
	ZVAL_DEREF(chainResults);
	if (EXPECTED(Z_TYPE_P(chainResults) == IS_ARRAY)) return true;
	zend_type_error("PHPStan\\Analyser\\ExprHandler\\Helper\\CoalesceCompositionHelper::%s(): Argument #%d ($chainResults) must be of type array, %s given", method, position, zend_zval_value_name(chainResults));
	return false;
}

zv::Val pt_coalesce_composition_helper_get_right_side_scope_specified_types(zval *helper, zval *s, zval *leftExpr, zval *leftResult, zval *chainResults, zval *rootExpr)
{
	if (EXPECTED(Z_OBJCE_P(helper) == pt_ce_coalesce_composition_helper)) return !chainResultsArgument(chainResults, "getRightSideScopeSpecifiedTypes", 4) ? zv::Val() : CoalesceCompositionHelper(Z_OBJ_P(helper)).getRightSideScopeSpecifiedTypes(s, leftExpr, leftResult, chainResults, rootExpr);
	zv::Args argv{s, leftExpr, leftResult, chainResults, rootExpr};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("getrightsidescopespecifiedtypes"), 5, argv);
}

zv::Val pt_coalesce_composition_helper_compose_type(zval *helper, zval *nodeScopeResolver, zval *leftExpr, zval *leftResult, zval *rightResult, zval *evaluationScope, zval *chainResults, zval *rootExpr, bool nativeTypesPromoted)
{
	if (EXPECTED(Z_OBJCE_P(helper) == pt_ce_coalesce_composition_helper)) return !chainResultsArgument(chainResults, "composeType", 6) ? zv::Val() : CoalesceCompositionHelper(Z_OBJ_P(helper)).composeType(nodeScopeResolver, leftExpr, leftResult, rightResult, evaluationScope, chainResults, rootExpr, nativeTypesPromoted);
	zv::Args argv{nodeScopeResolver, leftExpr, leftResult, rightResult, evaluationScope, chainResults, rootExpr, nativeTypesPromoted};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("composetype"), 8, argv);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_coalesce_composition_helper()
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\Helper\\CoalesceCompositionHelper");
	ptdecl::CoalesceCompositionHelper::declareClass(cls);
	ptdecl::CoalesceCompositionHelper::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *defaultNarrowingHelper;
		if (!zp::parse<zp::Obj>(execute_data, defaultNarrowingHelper)) RETURN_THROWS();
		CoalesceCompositionHelper(Z_OBJ_P(ZEND_THIS)).construct(defaultNarrowingHelper);
	});

	cls.method(sigs::getFalseySpecifiedTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *s, *evaluationScope, *leftExpr, *leftResult, *rootExpr, *context;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, s, evaluationScope, leftExpr, leftResult, rootExpr, context)) RETURN_THROWS();
		PT_RETURN_VAL(CoalesceCompositionHelper(Z_OBJ_P(ZEND_THIS)).getFalseySpecifiedTypes(s, evaluationScope, leftExpr, leftResult, rootExpr, context));
	});

	cls.method(sigs::getRightSideScopeSpecifiedTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *s, *leftExpr, *leftResult, *chainResults, *rootExpr;
		ZEND_PARSE_PARAMETERS_START(5, 5)
			Z_PARAM_OBJECT(s)
			Z_PARAM_OBJECT(leftExpr)
			Z_PARAM_OBJECT(leftResult)
			Z_PARAM_ARRAY(chainResults)
			Z_PARAM_OBJECT(rootExpr)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(CoalesceCompositionHelper(Z_OBJ_P(ZEND_THIS)).getRightSideScopeSpecifiedTypes(s, leftExpr, leftResult, chainResults, rootExpr));
	});

	cls.method(sigs::composeType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *leftExpr, *leftResult, *rightResult, *evaluationScope, *chainResults, *rootExpr;
		bool nativeTypesPromoted;
		ZEND_PARSE_PARAMETERS_START(8, 8)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(leftExpr)
			Z_PARAM_OBJECT(leftResult)
			Z_PARAM_OBJECT(rightResult)
			Z_PARAM_OBJECT(evaluationScope)
			Z_PARAM_ARRAY(chainResults)
			Z_PARAM_OBJECT(rootExpr)
			Z_PARAM_BOOL(nativeTypesPromoted)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(CoalesceCompositionHelper(Z_OBJ_P(ZEND_THIS)).composeType(nodeScopeResolver, leftExpr, leftResult, rightResult, evaluationScope, chainResults, rootExpr, nativeTypesPromoted));
	});

	cls.shadow(&pt_ce_coalesce_composition_helper);
}

/* }}} */

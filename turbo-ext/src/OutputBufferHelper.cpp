/*
 * PHPStanTurbo\OutputBufferHelper — native implementation of
 * PHPStan\Analyser\ExprHandler\Helper\OutputBufferHelper.
 *
 * A final DI service: the constructor keeps the twin's arginfo. Every
 * function call asks getLevelDelta() (FuncCallScopeEffectsHelper), so its
 * direct entry pt_output_buffer_helper_get_level_delta() compares the name
 * against the two constant lists without a call. applyLevelDelta() runs for
 * the ob_* functions only; addDelta()'s type callback is a native
 * pt_ietr_get_type (InitializerExprTypeResolver calls it synchronously),
 * NodeScopeResolver, MutatingScope and InitializerExprTypeResolver are called
 * through their direct entries, TypeExpr::getExprType() stays PHP behind a
 * cached method site.
 */

#include "support.h"
#include "generated/OutputBufferHelper.h"

namespace slots = ptdecl::OutputBufferHelper::slot;
namespace sigs = ptdecl::OutputBufferHelper::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"

zend_class_entry *pt_ce_output_buffer_helper = nullptr;

namespace {

/* {{{ the PHP collaborators (one site each) */

pt_method_site pt_obh_get_expr_type_site;

/* $this->initializerExprTypeResolver->getPlusType($left, $right, $getTypeCallback) */
zv::Val getPlusType(zval *initializerExprTypeResolver, zval *left, zval *right, const pt_ietr_get_type &getTypeCallback)
{
	if (UNEXPECTED(Z_TYPE_P(initializerExprTypeResolver) != IS_OBJECT)) {
		zend_throw_error(NULL, "Typed property PHPStan\\Analyser\\ExprHandler\\Helper\\OutputBufferHelper::$initializerExprTypeResolver must not be accessed before initialization");
		return zv::Val();
	}
	return pt_initializer_expr_type_resolver_get_binary_op_type(initializerExprTypeResolver, PT_IETR_OP_PLUS, left, right, getTypeCallback);
}

/* $typeExpr->getExprType() */
zv::Val getExprType(zval *typeExpr)
{
	return pt_call_method_cached(pt_obh_get_expr_type_site, Z_OBJ_P(typeExpr), PT_LC("getexprtype"), 0, NULL);
}

/* }}} */

/* static fn (Expr $expr): Type => $expr instanceof TypeExpr ? $expr->getExprType() : new MixedType() */
zv::Val typeExprTypeCallback(void *data, zval *expr)
{
	(void) data;
	ZVAL_DEREF(expr);
	zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
	if (UNEXPECTED(exprCe == NULL)) return zv::Val();
	if (UNEXPECTED(Z_TYPE_P(expr) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(expr), exprCe))) {
		zend_type_error("PHPStan\\Analyser\\ExprHandler\\Helper\\OutputBufferHelper::{closure}(): Argument #1 ($expr) must be of type PhpParser\\Node\\Expr, %s given", zend_zval_value_name(expr));
		return zv::Val();
	}
	zend_class_entry *typeExprCe = pt_class_loaded(PT_CLASS_TYPE_EXPR);
	if (UNEXPECTED(EG(exception))) return zv::Val();
	if (typeExprCe != NULL && instanceof_function(Z_OBJCE_P(expr), typeExprCe)) {
		return getExprType(expr);
	}
	zval mixed;
	if (UNEXPECTED(!pt_mixed_type_new(&mixed))) return zv::Val();
	return zv::Val::adopt(mixed);
}

/* the same closure called from PHP (it captures nothing) */
void typeExprTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
{
	(void) captures;
	if (UNEXPECTED(argc < 1)) {
		zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\ExprHandler\\Helper\\OutputBufferHelper::{closure}(), %u passed and exactly 1 expected", argc);
		return;
	}
	zv::Val type = typeExprTypeCallback(NULL, &argv[0]);
	if (UNEXPECTED(type.isUndef())) return;
	type.intoReturnValue(return_value);
}

/* the callback as a PHP callable that outlives the call */
zv::Val typeExprTypeCallable(void *data)
{
	(void) data;
	return pt_native_closure(&typeExprTypeCallbackBody);
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\Helper\OutputBufferHelper. */
class OutputBufferHelper
{
public:
	explicit OutputBufferHelper(zend_object *self) : self(self) {}

	void construct(zval *initializerExprTypeResolver) const
	{
		zv::ObjRef(self).propAtWrite(slots::initializerExprTypeResolver, zv::Val::copyOf(zv::Ref(initializerExprTypeResolver)));
	}

	/* Mirrors getLevelDelta(). */
	static zend_long getLevelDelta(zend_string *functionName)
	{
		if (zend_string_equals_literal(functionName, "ob_start")) return 1;
		if (zend_string_equals_literal(functionName, "ob_get_clean")
			|| zend_string_equals_literal(functionName, "ob_get_flush")
			|| zend_string_equals_literal(functionName, "ob_end_clean")
			|| zend_string_equals_literal(functionName, "ob_end_flush")) {
			return -1;
		}
		return 0;
	}

	/* Mirrors applyLevelDelta(). */
	zv::Val applyLevelDelta(zval *nodeScopeResolver, zval *scopeIn, zend_long delta) const
	{
		zv::Val literal = zv::Val::string(PT_LC("ob_get_level"));
		zv::Val name = pt_name_node_new(PT_CLASS_NAME, literal.raw());
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		zv::Val fullyQualified = pt_name_node_new(PT_CLASS_FULLY_QUALIFIED, literal.raw());
		if (UNEXPECTED(fullyQualified.isUndef())) return zv::Val();

		zv::Val scope = zv::Val::copyOf(zv::Ref(scopeIn));
		zval *names[] = { name.raw(), fullyQualified.raw() };
		for (zval *callName : names) {
			zval empty;
			ZVAL_EMPTY_ARRAY(&empty);
			zv::Args callArgs{callName, &empty};
			zv::Val obGetLevelCall = pt_type_new(PT_CLASS_FUNC_CALL, 2, callArgs);
			if (UNEXPECTED(obGetLevelCall.isUndef())) return zv::Val();

			zv::Val levelType = pt_node_scope_resolver_read_scope_state_or_synthetic_type(nodeScopeResolver, obGetLevelCall.raw(), scope.raw());
			if (UNEXPECTED(levelType.isUndef())) return zv::Val();
			zv::Val type = addDelta(levelType.raw(), delta);
			if (UNEXPECTED(type.isUndef())) return zv::Val();
			zv::Val nativeScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scope.raw()));
			if (UNEXPECTED(nativeScope.isUndef())) return zv::Val();
			zv::Val nativeLevelType = pt_node_scope_resolver_read_scope_state_or_synthetic_type(nodeScopeResolver, obGetLevelCall.raw(), nativeScope.raw());
			if (UNEXPECTED(nativeLevelType.isUndef())) return zv::Val();
			zv::Val nativeType = addDelta(nativeLevelType.raw(), delta);
			if (UNEXPECTED(nativeType.isUndef())) return zv::Val();

			zv::Val assigned = pt_mutating_scope_assign_expression(Z_OBJ_P(scope.raw()), Z_OBJ_P(obGetLevelCall.raw()), type.raw(), nativeType.raw());
			if (UNEXPECTED(assigned.isUndef())) return zv::Val();
			scope = std::move(assigned);
		}

		return scope;
	}

private:
	zend_object *self;

	/* Mirrors addDelta(). */
	zv::Val addDelta(zval *levelType, zend_long delta) const
	{
		zv::Val left = pt_type_new(PT_CLASS_TYPE_EXPR, 1, levelType);
		if (UNEXPECTED(left.isUndef())) return zv::Val();
		zval deltaType;
		if (UNEXPECTED(!pt_constant_integer_type_new(&deltaType, delta))) return zv::Val();
		zv::Val deltaTypeHold = zv::Val::adopt(deltaType);
		zv::Val right = pt_type_new(PT_CLASS_TYPE_EXPR, 1, deltaTypeHold.raw());
		if (UNEXPECTED(right.isUndef())) return zv::Val();
		pt_ietr_get_type callback{&typeExprTypeCallback, NULL, &typeExprTypeCallable};
		return getPlusType(OBJ_PROP_NUM(self, slots::initializerExprTypeResolver), left.raw(), right.raw(), callback);
	}
};

} // namespace phpstanturbo

using phpstanturbo::OutputBufferHelper;

zend_long pt_output_buffer_helper_get_level_delta(zval *helper, zend_string *functionName, bool &ok)
{
	ok = true;
	if (EXPECTED(Z_OBJCE_P(helper) == pt_ce_output_buffer_helper)) return OutputBufferHelper::getLevelDelta(functionName);
	zv::Args argv{functionName};
	zv::Val delta = pt_type_call(Z_OBJ_P(helper), PT_LC("getleveldelta"), 1, argv);
	if (UNEXPECTED(delta.isUndef())) {
		ok = false;
		return 0;
	}
	return zval_get_long(delta.raw());
}

zv::Val pt_output_buffer_helper_apply_level_delta(zval *helper, zval *nodeScopeResolver, zval *scope, zend_long delta)
{
	if (EXPECTED(Z_OBJCE_P(helper) == pt_ce_output_buffer_helper)) return OutputBufferHelper(Z_OBJ_P(helper)).applyLevelDelta(nodeScopeResolver, scope, delta);
	zv::Args argv{nodeScopeResolver, scope, delta};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("applyleveldelta"), 3, argv);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

/* the twin's private LEVEL_INCREMENTING_FUNCTIONS and
 * LEVEL_DECREMENTING_FUNCTIONS (getLevelDelta() compares the same names),
 * as persistent lists built once at module startup */
static const pt_superglobal_name pt_obh_level_incrementing_functions[] = {
	{ PT_LC("ob_start") },
};
static const pt_superglobal_name pt_obh_level_decrementing_functions[] = {
	{ PT_LC("ob_get_clean") },
	{ PT_LC("ob_get_flush") },
	{ PT_LC("ob_end_clean") },
	{ PT_LC("ob_end_flush") },
};
static HashTable *pt_obh_level_incrementing_functions_list = nullptr;
static HashTable *pt_obh_level_decrementing_functions_list = nullptr;

static void pt_obh_level_incrementing_functions_constant(zval *out)
{
	pt_persistent_list_into(out, pt_obh_level_incrementing_functions_list);
}

static void pt_obh_level_decrementing_functions_constant(zval *out)
{
	pt_persistent_list_into(out, pt_obh_level_decrementing_functions_list);
}

PT_MINIT_REGISTRATION(pt_register_output_buffer_helper)
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\Helper\\OutputBufferHelper");
	ptdecl::OutputBufferHelper::declareClass(cls);
	ptdecl::OutputBufferHelper::declareProperties(cls);
	pt_obh_level_incrementing_functions_list = pt_persistent_string_list(pt_obh_level_incrementing_functions, sizeof(pt_obh_level_incrementing_functions) / sizeof(pt_obh_level_incrementing_functions[0]));
	pt_obh_level_decrementing_functions_list = pt_persistent_string_list(pt_obh_level_decrementing_functions, sizeof(pt_obh_level_decrementing_functions) / sizeof(pt_obh_level_decrementing_functions[0]));
	cls.privateClassConstantValue("LEVEL_INCREMENTING_FUNCTIONS", pt_obh_level_incrementing_functions_constant);
	cls.privateClassConstantValue("LEVEL_DECREMENTING_FUNCTIONS", pt_obh_level_decrementing_functions_constant);

	/* the real parameter class name: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *initializerExprTypeResolver;
		if (!zp::parse<zp::Obj>(execute_data, initializerExprTypeResolver)) RETURN_THROWS();
		OutputBufferHelper(Z_OBJ_P(ZEND_THIS)).construct(initializerExprTypeResolver);
	});

	cls.method(sigs::getLevelDelta, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *functionName;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_STR(functionName)
		ZEND_PARSE_PARAMETERS_END();
		RETURN_LONG(OutputBufferHelper::getLevelDelta(functionName));
	});

	cls.method(sigs::applyLevelDelta, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *scope;
		zend_long delta;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_LONG(delta)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(OutputBufferHelper(Z_OBJ_P(ZEND_THIS)).applyLevelDelta(nodeScopeResolver, scope, delta));
	});

	cls.shadow(&pt_ce_output_buffer_helper);
}

/* }}} */

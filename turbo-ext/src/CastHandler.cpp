/*
 * PHPStanTurbo\CastHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\CastHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback ($this, $expr, $exprResult)
 * and the specifyTypesCallback ($this, $expr, $exprResult,
 * $nodeScopeResolver, $beforeScope, $subjectArgResult). The typeCallback's
 * InitializerExprTypeResolver::getCastType() (a final class's pure dispatch
 * on the cast kind) is spelled out, so its $getTypeCallback closure — only
 * ever asked about $expr->expr — is the operand read in place; the object
 * cast's getCastObjectType() stays a call.
 *
 * NodeScopeResolver, ExpressionResult, ExpressionContext, MutatingScope,
 * SpecifiedTypes, TypeSpecifierContext, IdenticalNarrowingHelper,
 * DefaultNarrowingHelper, InitializerExprTypeResolver::getCastObjectType()
 * and the Type kernel are called through their direct entries; the Type
 * conversions through the engine.
 */

#include "support.h"
#include "generated/CastHandler.h"

namespace slots = ptdecl::CastHandler::slot;
namespace sigs = ptdecl::CastHandler::sig;
#include "SimpleExprHandlers.h"

zend_class_entry *pt_ce_cast_handler = nullptr;

namespace {

using phpstanturbo::visitors::NodeProp;

NodeProp pt_ch_cast_expr = PT_NODE_PROP(PT_CLASS_CAST_EXPR, "expr");

/* $initializerExprTypeResolver->getCastObjectType($exprType) */
zv::Val getCastObjectType(zval *resolver, zval *exprType)
{
	return pt_initializer_expr_type_resolver_get_cast_object_type(resolver, exprType);
}

/* the cast classes, in the order getCastType() tests them (Unset_ first:
 * the handler's own arm) */
enum CastKind : uint8_t
{
	CAST_UNSET,
	CAST_INT,
	CAST_BOOL,
	CAST_DOUBLE,
	CAST_STRING,
	CAST_ARRAY,
	CAST_OBJECT,
	CAST_OTHER,
};

constexpr int castClasses[CAST_OTHER] = {
	PT_CLASS_CAST_UNSET,
	PT_CLASS_CAST_INT,
	PT_CLASS_CAST_BOOL,
	PT_CLASS_CAST_DOUBLE,
	PT_CLASS_CAST_STRING,
	PT_CLASS_CAST_ARRAY,
	PT_CLASS_CAST_OBJECT,
};

/* the first cast class $expr is an instance of (the php-parser classes are
 * siblings); -1 = pending exception */
int castKindOf(zval *expr)
{
	zend_class_entry *ce = Z_OBJCE_P(expr);
	for (int i = 0; i < CAST_OTHER; i++) {
		zend_class_entry *kindCe = pt_class(castClasses[i]);
		if (UNEXPECTED(kindCe == NULL)) return -1;
		if (instanceof_function(ce, kindCe)) return i;
	}
	return CAST_OTHER;
}

/* new ConstFetch(new FullyQualified('true')) / new Int_(0) / new Float_(0.0) */
zv::Val trueConstFetch()
{
	zval name;
	ZVAL_STRINGL(&name, "true", 4);
	zv::Val nameValue = zv::Val::adopt(name);
	zv::Val fullyQualified = pt_type_new(PT_CLASS_FULLY_QUALIFIED, 1, nameValue.raw());
	if (UNEXPECTED(fullyQualified.isUndef())) return zv::Val();
	return pt_type_new(PT_CLASS_CONST_FETCH, 1, fullyQualified.raw());
}

zv::Val zeroInt()
{
	zval zero;
	ZVAL_LONG(&zero, 0);
	return pt_type_new(PT_CLASS_SCALAR_INT, 1, &zero);
}

zv::Val zeroFloat()
{
	zval zero;
	ZVAL_DOUBLE(&zero, 0.0);
	return pt_type_new(PT_CLASS_SCALAR_FLOAT, 1, &zero);
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\CastHandler; UNDEF = pending
 * exception. */
class CastHandler
{
public:
	static constexpr const char *closureName = "PHPStan\\Analyser\\ExprHandler\\CastHandler::{closure}";

	explicit CastHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *initializerExprTypeResolver, zval *expressionResultFactory, zval *defaultNarrowingHelper, zval *identicalNarrowingHelper) const
	{
		pt_write_slot(self, slots::initializerExprTypeResolver, initializerExprTypeResolver);
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
		pt_write_slot(self, slots::identicalNarrowingHelper, identicalNarrowingHelper);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *expr, bool &out)
	{
		int isCast = ptoh::isInstance(expr, PT_CLASS_CAST_EXPR);
		if (UNEXPECTED(isCast < 0)) return false;
		if (!isCast) {
			out = false;
			return true;
		}
		int isString = ptoh::isInstance(expr, PT_CLASS_CAST_STRING);
		if (UNEXPECTED(isString < 0)) return false;
		out = !isString;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *beforeScope = scope;
		zval *inner = ptoh::operand(pt_ch_cast_expr, expr);
		if (UNEXPECTED(inner == NULL)) return zv::Val();
		zv::Val innerContext = pt_expression_context_enter_deep_keeping_value_flow(context);
		if (UNEXPECTED(innerContext.isUndef())) return zv::Val();
		zv::Val exprResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, inner, scope, storage, nodeCallback, innerContext.raw());
		if (UNEXPECTED(exprResult.isUndef())) return zv::Val();
		ptse::ChildResult child;
		if (UNEXPECTED(!child.read(exprResult.raw()))) return zv::Val();

		inner = ptoh::operand(pt_ch_cast_expr, expr);
		if (UNEXPECTED(inner == NULL)) return zv::Val();
		zv::Val subjectArgResult = pt_identical_narrowing_helper_capture_first_arg_result(OBJ_PROP_NUM(self, slots::identicalNarrowingHelper), inner, storage);
		if (UNEXPECTED(subjectArgResult.isUndef())) return zv::Val();

		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, self, expr, exprResult.raw());
		zv::Val specifyTypesCallback = pt_native_closure(&specifyTypesCallbackBody, self, expr, exprResult.raw(), nodeScopeResolver, beforeScope, subjectArgResult.raw());
		pt_expression_result_args args(child.scope, beforeScope, expr, child.hasYield, child.isAlwaysTerminating, child.throwPoints, child.impurePoints, typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(child.variableFlow.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return CastHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* function (bool $nativeTypesPromoted) use ($expr, $exprResult): Type —
	 * captures: $this, $expr, $exprResult */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptse::requireArgs(argc, 1, closureName))) return;
		bool nativeTypesPromoted = zend_is_true(&argv[0]);
		zv::Val type;
		pt_engine_with_stack([&]() { type = resolveType(captures, nativeTypesPromoted); });
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	static zv::Val resolveType(zval *captures, bool nativeTypesPromoted)
	{
		zval *expr = &captures[1];
		int kind = castKindOf(expr);
		if (UNEXPECTED(kind < 0)) return zv::Val();
		if (kind == CAST_UNSET) {
			zval nullType;
			if (UNEXPECTED(!pt_null_type_new(&nullType))) return zv::Val();
			return zv::Val::adopt(nullType);
		}

		// InitializerExprTypeResolver::getCastType() — its callback answers
		// $expr->expr from the operand's result
		if (kind == CAST_OTHER) return pt_type_new_mixed_type();
		zv::Val exprType = ptse::typeOf(&captures[2], nativeTypesPromoted);
		if (UNEXPECTED(exprType.isUndef())) return zv::Val();
		if (kind == CAST_OBJECT) return getCastObjectType(OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::initializerExprTypeResolver), exprType.raw());
		static constexpr struct
		{
			const char *lcname;
			size_t len;
		} conversions[] = {
			{ NULL, 0 },
			{ PT_LC("tointeger") },
			{ PT_LC("toboolean") },
			{ PT_LC("tofloat") },
			{ PT_LC("tostring") },
			{ PT_LC("toarray") },
		};
		if (UNEXPECTED(Z_TYPE_P(exprType.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function %s() on %s", conversions[kind].lcname, zend_zval_value_name(exprType.raw()));
			return zv::Val();
		}
		return pt_type_call(Z_OBJ_P(exprType.raw()), conversions[kind].lcname, conversions[kind].len, 0, NULL);
	}

	/* function (TypeSpecifierContext $context, bool $nativeTypesPromoted) use
	 * ($expr, $exprResult, $nodeScopeResolver, $beforeScope,
	 * $subjectArgResult): SpecifiedTypes — captures: $this, $expr, $exprResult,
	 * $nodeScopeResolver, $beforeScope, $subjectArgResult */
	static void specifyTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptse::requireArgs(argc, 2, closureName))) return;
		zv::Val types;
		pt_engine_with_stack([&]() { types = specifyTypes(captures, &argv[0], zend_is_true(&argv[1])); });
		if (UNEXPECTED(types.isUndef())) return;
		types.intoReturnValue(return_value);
	}

	static zv::Val specifyTypes(zval *captures, zval *context, bool nativeTypesPromoted)
	{
		zend_object *handler = Z_OBJ(captures[0]);
		zval *expr = &captures[1];
		zval *exprResult = &captures[2];
		zval *nodeScopeResolver = &captures[3];
		zval *beforeScope = &captures[4];
		zval *subjectArgResult = &captures[5];

		zv::Val evaluationScope = nativeTypesPromoted ? pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(beforeScope)) : zv::Val::copyOf(zv::Ref(beforeScope));
		if (UNEXPECTED(evaluationScope.isUndef())) return zv::Val();
		int kind = castKindOf(expr);
		if (UNEXPECTED(kind < 0)) return zv::Val();
		// a cast's truthiness is a loose comparison of the inner
		// expression - composed from its result; the fabricated
		// literal is only printed into entries, never walked
		if (kind == CAST_BOOL || kind == CAST_INT || kind == CAST_DOUBLE) {
			bool contextNull;
			if (UNEXPECTED(!pt_type_specifier_context_null(Z_OBJ_P(context), contextNull))) return zv::Val();
			if (!contextNull) {
				zv::Val literal;
				zv::Val equalContext;
				if (kind == CAST_BOOL) {
					literal = trueConstFetch();
					if (UNEXPECTED(literal.isUndef())) return zv::Val();
					equalContext = zv::Val::copyOf(zv::Ref(context));
				} else {
					literal = kind == CAST_INT ? zeroInt() : zeroFloat();
					if (UNEXPECTED(literal.isUndef())) return zv::Val();
					equalContext = pt_type_specifier_context_negate(Z_OBJ_P(context));
					if (UNEXPECTED(equalContext.isUndef())) return zv::Val();
				}

				// the literal side never reads its stand-in result
				zval *inner = ptoh::operand(pt_ch_cast_expr, expr);
				if (UNEXPECTED(inner == NULL)) return zv::Val();
				zv::Val types = pt_identical_narrowing_helper_specify_equal(OBJ_PROP_NUM(handler, slots::identicalNarrowingHelper), nodeScopeResolver, inner, literal.raw(), exprResult, exprResult, equalContext.raw(), evaluationScope.raw(), subjectArgResult, NULL);
				if (UNEXPECTED(types.isUndef())) return zv::Val();
				if (!types.isNull()) return setRootExpr(types, expr);
			}
		}

		if (kind == CAST_BOOL || kind == CAST_INT || kind == CAST_DOUBLE) {
			zval *inner = ptoh::operand(pt_ch_cast_expr, expr);
			if (UNEXPECTED(inner == NULL)) return zv::Val();
			zv::Val literal = kind == CAST_BOOL ? trueConstFetch() : (kind == CAST_INT ? zeroInt() : zeroFloat());
			if (UNEXPECTED(literal.isUndef())) return zv::Val();
			zv::Args comparisonArgs{inner, literal.raw()};
			zv::Val comparison = pt_type_new(kind == CAST_BOOL ? PT_CLASS_EQUAL_EXPR : PT_CLASS_NOT_EQUAL_EXPR, 2, comparisonArgs);
			if (UNEXPECTED(comparison.isUndef())) return zv::Val();
			zv::Val result = pt_mutating_scope_obtain_result_for_node(Z_OBJ_P(evaluationScope.raw()), Z_OBJ_P(comparison.raw()));
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(result.raw()) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function getSpecifiedTypes() on %s", zend_zval_value_name(result.raw()));
				return zv::Val();
			}
			zv::Val types = pt_expression_result_get_specified_types(result.raw(), context, nativeTypesPromoted);
			if (UNEXPECTED(types.isUndef())) return zv::Val();
			return setRootExpr(types, expr);
		}

		return pt_default_narrowing_helper_specify_default_types(OBJ_PROP_NUM(handler, slots::defaultNarrowingHelper), expr, context);
	}

	/* $types->setRootExpr($expr) */
	static zv::Val setRootExpr(zv::Val &types, zval *expr)
	{
		if (UNEXPECTED(Z_TYPE_P(types.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function setRootExpr() on %s", zend_zval_value_name(types.raw()));
			return zv::Val();
		}
		return pt_specified_types_set_root_expr(Z_OBJ_P(types.raw()), expr);
	}
};

} // namespace phpstanturbo

using phpstanturbo::CastHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_cast_handler()
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\CastHandler");
	ptdecl::CastHandler::declareClass(cls);
	ptdecl::CastHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *initializerExprTypeResolver, *expressionResultFactory, *defaultNarrowingHelper, *identicalNarrowingHelper;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, initializerExprTypeResolver, expressionResultFactory, defaultNarrowingHelper, identicalNarrowingHelper)) RETURN_THROWS();
		CastHandler(Z_OBJ_P(ZEND_THIS)).construct(initializerExprTypeResolver, expressionResultFactory, defaultNarrowingHelper, identicalNarrowingHelper);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!CastHandler::supports(expr, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.method(sigs::processExpr, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *stmt, *expr, *scope, *storage, *nodeCallback, *context;
		ZEND_PARSE_PARAMETERS_START(7, 7)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(CastHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_cast_handler);
	pt_expr_handler_entry_register(&pt_ce_cast_handler, &CastHandler::processExprEntry);
}

/* }}} */

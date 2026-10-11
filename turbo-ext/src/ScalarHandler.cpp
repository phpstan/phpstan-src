/*
 * PHPStanTurbo\ScalarHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\ScalarHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the
 * class's handler entry (Engine.h), so native callers dispatch to it
 * without an engine frame. The literal scalars (String_, Int_, Float_) get
 * their constant type directly; anything else (the magic constants) goes
 * through InitializerExprTypeResolver::getType() once, eagerly - the result
 * is built through pt_expression_result_create() with the precomputed
 * type/nativeType and no typeCallback, like the twin.
 *
 * SpecifiedTypes and InitializerExprTypeResolver are called through their
 * direct entries; InitializerExprContext, which stays PHP for now, through the
 * cached method site in the block below.
 */

#include "support.h"
#include "generated/ScalarHandler.h"

namespace slots = ptdecl::ScalarHandler::slot;
namespace sigs = ptdecl::ScalarHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "ParserVisitors.h"

zend_class_entry *pt_ce_scalar_handler = nullptr;

namespace {

using phpstanturbo::visitors::NodeProp;

/* {{{ the PHP collaborators (one site each; switch to their direct entries
 * once they are ported) */


/* InitializerExprContext::fromScope($scope) */
zv::Val initializerExprContextFromScope(zval *scope)
{
	return pt_initializer_expr_context_from_scope(scope);
}

/* $initializerExprTypeResolver->getType($expr, $context) */
zv::Val initializerExprType(zval *initializerExprTypeResolver, zval *expr, zval *context)
{
	return pt_initializer_expr_type_resolver_get_type(initializerExprTypeResolver, expr, context);
}

/* }}} */

/* {{{ the scalar nodes' value properties */

NodeProp pt_sh_scalar_string_value = PT_NODE_PROP(PT_CLASS_SCALAR_STRING, "value");
NodeProp pt_sh_scalar_int_value = PT_NODE_PROP(PT_CLASS_SCALAR_INT, "value");
NodeProp pt_sh_scalar_float_value = PT_NODE_PROP(PT_CLASS_SCALAR_FLOAT, "value");

/* $node->value (dereferenced); NULL = pending exception */
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

/* }}} */

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\ScalarHandler; UNDEF = pending
 * exception. */
class ScalarHandler
{
public:
	explicit ScalarHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *initializerExprTypeResolver, zval *expressionResultFactory)
	{
		zv::ObjRef object(self);
		object.propAtWrite(slots::initializerExprTypeResolver, zv::Val::copyOf(zv::Ref(initializerExprTypeResolver)));
		object.propAtWrite(slots::expressionResultFactory, zv::Val::copyOf(zv::Ref(expressionResultFactory)));
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *expr, bool &out) const
	{
		zend_class_entry *scalarCe = pt_class(PT_CLASS_SCALAR);
		if (UNEXPECTED(scalarCe == NULL)) return false;
		if (!instanceof_function(Z_OBJCE_P(expr), scalarCe)) {
			out = false;
			return true;
		}
		zend_class_entry *interpolatedStringCe = pt_class(PT_CLASS_INTERPOLATED_STRING);
		if (UNEXPECTED(interpolatedStringCe == NULL)) return false;
		out = !instanceof_function(Z_OBJCE_P(expr), interpolatedStringCe);
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		(void) nodeScopeResolver;
		(void) stmt;
		(void) storage;
		(void) nodeCallback;
		(void) context;
		// a literal's type is lexical - identical on every scope - so compute it
		// once, eagerly; anything but the plain scalars (the magic constants)
		// keeps going through the initializer resolver
		zv::Val type;
		if (visitors::isInstanceOf(Z_OBJ_P(expr), PT_CLASS_SCALAR_STRING)) {
			zval *value = nodeProp(pt_sh_scalar_string_value, expr);
			if (UNEXPECTED(value == NULL)) return zv::Val();
			if (EXPECTED(Z_TYPE_P(value) == IS_STRING)) {
				zval out;
				if (UNEXPECTED(!pt_constant_string_type_new(&out, Z_STR_P(value), false))) return zv::Val();
				type = zv::Val::adopt(out);
			} else {
				type = pt_type_new_ce(pt_ce_constant_string_type, 1, value);
			}
		} else if (visitors::isInstanceOf(Z_OBJ_P(expr), PT_CLASS_SCALAR_INT)) {
			zval *value = nodeProp(pt_sh_scalar_int_value, expr);
			if (UNEXPECTED(value == NULL)) return zv::Val();
			if (EXPECTED(Z_TYPE_P(value) == IS_LONG)) {
				zval out;
				if (UNEXPECTED(!pt_constant_integer_type_new(&out, Z_LVAL_P(value)))) return zv::Val();
				type = zv::Val::adopt(out);
			} else {
				type = pt_type_new_ce(pt_ce_constant_integer_type, 1, value);
			}
		} else if (visitors::isInstanceOf(Z_OBJ_P(expr), PT_CLASS_SCALAR_FLOAT)) {
			zval *value = nodeProp(pt_sh_scalar_float_value, expr);
			if (UNEXPECTED(value == NULL)) return zv::Val();
			if (EXPECTED(Z_TYPE_P(value) == IS_DOUBLE)) {
				zval out;
				if (UNEXPECTED(!pt_constant_float_type_new(&out, Z_DVAL_P(value)))) return zv::Val();
				type = zv::Val::adopt(out);
			} else {
				type = pt_type_new_ce(pt_ce_constant_float_type, 1, value);
			}
		} else {
			zv::Val initializerExprContext = initializerExprContextFromScope(scope);
			if (UNEXPECTED(initializerExprContext.isUndef())) return zv::Val();
			type = initializerExprType(OBJ_PROP_NUM(self, slots::initializerExprTypeResolver), expr, initializerExprContext.raw());
			if (UNEXPECTED(type.isUndef())) return zv::Val();
		}

		zv::Val specifyTypesCallback = pt_specified_types_empty_specify_callback();
		if (UNEXPECTED(specifyTypesCallback.isUndef())) return zv::Val();
		pt_expression_result_args args(scope, scope, expr, false, false, NULL, NULL, NULL, specifyTypesCallback.raw());
		args.withType(type.raw());
		args.withNativeType(type.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return ScalarHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::ScalarHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_scalar_handler)
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\ScalarHandler");
	ptdecl::ScalarHandler::declareClass(cls);
	ptdecl::ScalarHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *initializerExprTypeResolver, *expressionResultFactory;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, initializerExprTypeResolver, expressionResultFactory)) RETURN_THROWS();
		ScalarHandler(Z_OBJ_P(ZEND_THIS)).construct(initializerExprTypeResolver, expressionResultFactory);
	});

	cls.method<&ScalarHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(ScalarHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_scalar_handler);
	pt_expr_handler_entry_register(&pt_ce_scalar_handler, &ScalarHandler::processExprEntry);
}

/* }}} */

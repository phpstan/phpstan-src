/*
 * PHPStanTurbo\ClosureBindToVarVisitor — native twin of
 * PHPStan\Parser\ClosureBindToVarVisitor, declared under that name at
 * activation (final, extending PhpParser\NodeVisitorAbstract like the
 * original).
 *
 * Records the closure a $closure->bindTo() call is made on, on the call's
 * first argument.
 *
 * enterNode() always returns null, so the visitor is also registered with
 * pt_native_visitor_register(): the native NodeTraverser then runs it
 * directly per node instead of calling into the engine (ParserVisitors.h).
 */

#include "ParserVisitors.h"
#include "generated/ClosureBindToVarVisitor.h"

namespace sigs = ptdecl::ClosureBindToVarVisitor::sig;

static zend_class_entry *pt_ce_closure_bind_to_var_visitor = nullptr;

static const char pt_closure_bind_to_var_attribute[] = "closureBindToVar";
static zend_string *pt_closure_bind_to_var_attribute_str = nullptr;

namespace phpstanturbo {

using visitors::NodeProp;

/* Mirrors PHPStan\Parser\ClosureBindToVarVisitor. */
class ClosureBindToVarVisitor
{
public:
	/* enterNode(); the twin always returns null, false = pending exception */
	[[nodiscard]] static bool enterNode(zend_object *visitor, zend_object *node)
	{
		static NodeProp methodProp = PT_NODE_PROP(PT_CLASS_METHOD_CALL, "name");
		static NodeProp argsProp = PT_NODE_PROP(PT_CLASS_METHOD_CALL, "args");
		static NodeProp varProp = PT_NODE_PROP(PT_CLASS_METHOD_CALL, "var");
		static NodeProp identifierProp = PT_IDENTIFIER_PROP;

		if (!visitors::isInstanceOf(node, PT_CLASS_METHOD_CALL)) return !EG(exception);
		zend_object *method = methodProp.objectOf(node, PT_CLASS_IDENTIFIER);
		if (method == NULL) return !EG(exception);
		zend_string *methodName = visitors::nameString(method, identifierProp);
		if (methodName == NULL || !visitors::lowerEquals(methodName, "bindto")) return true;
		zval *args = argsProp.of(node);
		if (visitors::isFirstClassCallable(args)) return true;

		zend_object *arg = visitors::argAt(args, 0);
		zval *var = varProp.of(node);
		if (arg != NULL && var != NULL) {
			visitors::setAttribute(arg, pt_closure_bind_to_var_attribute_str, var);
		}
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::ClosureBindToVarVisitor;

/* {{{ engine ABI glue: parameter parsing + registration */

static const pt_native_visitor pt_closure_bind_to_var_entry = {
	&pt_ce_closure_bind_to_var_visitor,
	ClosureBindToVarVisitor::enterNode,
	NULL,
	NULL,
};

PT_MINIT_REGISTRATION(pt_register_closure_bind_to_var_visitor)
{
	pt_closure_bind_to_var_attribute_str = zend_string_init_interned(pt_closure_bind_to_var_attribute, sizeof(pt_closure_bind_to_var_attribute) - 1, 1);

	reg::Class cls("PHPStan\\Parser\\ClosureBindToVarVisitor");
	ptdecl::ClosureBindToVarVisitor::declareClass(cls);
	ptdecl::ClosureBindToVarVisitor::declareProperties(cls);
	cls.publicClassConstantString("ATTRIBUTE_NAME", pt_closure_bind_to_var_attribute);

	cls.method(sigs::enterNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *node;
		if (!zp::parse<zp::Obj>(execute_data, node)) RETURN_THROWS();
		if (UNEXPECTED(!ClosureBindToVarVisitor::enterNode(Z_OBJ_P(ZEND_THIS), Z_OBJ_P(node)))) RETURN_THROWS();
		RETURN_NULL();
	});

	cls.shadow(&pt_ce_closure_bind_to_var_visitor);
	pt_native_visitor_register(&pt_closure_bind_to_var_entry);
}

/* }}} */

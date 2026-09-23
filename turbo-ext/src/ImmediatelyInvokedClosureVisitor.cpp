/*
 * PHPStanTurbo\ImmediatelyInvokedClosureVisitor — native twin of
 * PHPStan\Parser\ImmediatelyInvokedClosureVisitor, declared under that name
 * at activation (final, extending PhpParser\NodeVisitorAbstract like the
 * original).
 *
 * Marks a closure/arrow function that is called right where it is written,
 * and records the call's arguments on it.
 *
 * enterNode() always returns null, so the visitor is also registered with
 * pt_native_visitor_register(): the native NodeTraverser then runs it
 * directly per node instead of calling into the engine (ParserVisitors.h).
 */

#include "ParserVisitors.h"
#include "generated/ImmediatelyInvokedClosureVisitor.h"

namespace sigs = ptdecl::ImmediatelyInvokedClosureVisitor::sig;

static zend_class_entry *pt_ce_immediately_invoked_closure_visitor = nullptr;

static const char pt_immediately_invoked_closure_attribute[] = "isImmediatelyInvokedClosure";
static const char pt_immediately_invoked_closure_args_attribute[] = "immediatelyInvokedClosureArgs";
static zend_string *pt_immediately_invoked_closure_attribute_str = nullptr;
static zend_string *pt_immediately_invoked_closure_args_attribute_str = nullptr;

namespace phpstanturbo {

using visitors::NodeProp;

/* Mirrors PHPStan\Parser\ImmediatelyInvokedClosureVisitor. */
class ImmediatelyInvokedClosureVisitor
{
public:
	/* enterNode(); the twin always returns null, false = pending exception */
	[[nodiscard]] static bool enterNode(zend_object *visitor, zend_object *node)
	{
		static NodeProp calleeProp = PT_NODE_PROP(PT_CLASS_FUNC_CALL, "name");
		static NodeProp argsProp = PT_NODE_PROP(PT_CLASS_FUNC_CALL, "args");

		if (!visitors::isInstanceOf(node, PT_CLASS_FUNC_CALL)) return !EG(exception);
		zval *callee = calleeProp.of(node);
		if (callee == NULL || Z_TYPE_P(callee) != IS_OBJECT) return true;
		zend_object *closure = Z_OBJ_P(callee);
		if (!visitors::isInstanceOf(closure, PT_CLASS_CLOSURE_EXPR) && !visitors::isInstanceOf(closure, PT_CLASS_ARROW_FUNCTION)) return !EG(exception);
		zval *args = argsProp.of(node);
		if (visitors::isFirstClassCallable(args)) return true;

		visitors::setAttributeTrue(closure, pt_immediately_invoked_closure_attribute_str);
		if (args != NULL) {
			visitors::setAttribute(closure, pt_immediately_invoked_closure_args_attribute_str, args);
		}
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::ImmediatelyInvokedClosureVisitor;

/* {{{ engine ABI glue: parameter parsing + registration */

static const pt_native_visitor pt_immediately_invoked_closure_entry = {
	&pt_ce_immediately_invoked_closure_visitor,
	ImmediatelyInvokedClosureVisitor::enterNode,
	NULL,
	NULL,
};

void pt_register_immediately_invoked_closure_visitor()
{
	pt_immediately_invoked_closure_attribute_str = zend_string_init_interned(pt_immediately_invoked_closure_attribute, sizeof(pt_immediately_invoked_closure_attribute) - 1, 1);
	pt_immediately_invoked_closure_args_attribute_str = zend_string_init_interned(pt_immediately_invoked_closure_args_attribute, sizeof(pt_immediately_invoked_closure_args_attribute) - 1, 1);

	reg::Class cls("PHPStan\\Parser\\ImmediatelyInvokedClosureVisitor");
	ptdecl::ImmediatelyInvokedClosureVisitor::declareClass(cls);
	ptdecl::ImmediatelyInvokedClosureVisitor::declareProperties(cls);
	cls.publicClassConstantString("ATTRIBUTE_NAME", pt_immediately_invoked_closure_attribute);
	cls.publicClassConstantString("ARGS_ATTRIBUTE_NAME", pt_immediately_invoked_closure_args_attribute);

	cls.method(sigs::enterNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *node;
		if (!zp::parse<zp::Obj>(execute_data, node)) RETURN_THROWS();
		if (UNEXPECTED(!ImmediatelyInvokedClosureVisitor::enterNode(Z_OBJ_P(ZEND_THIS), Z_OBJ_P(node)))) RETURN_THROWS();
		RETURN_NULL();
	});

	cls.shadow(&pt_ce_immediately_invoked_closure_visitor);
	pt_native_visitor_register(&pt_immediately_invoked_closure_entry);
}

/* }}} */

/*
 * PHPStanTurbo\ClosureArgVisitor — native twin of
 * PHPStan\Parser\ClosureArgVisitor, declared under that name at
 * activation (final, extending PhpParser\NodeVisitorAbstract like the
 * original).
 *
 * Records the arguments of a call that invokes a closure literal (directly
 * or through an assignment) on that closure.
 *
 * enterNode() always returns null, so the visitor is also registered with
 * pt_native_visitor_register(): the native NodeTraverser then runs it
 * directly per node instead of calling into the engine (ParserVisitors.h).
 */

#include "ParserVisitors.h"
#include "generated/ClosureArgVisitor.h"

namespace sigs = ptdecl::ClosureArgVisitor::sig;

static zend_class_entry *pt_ce_closure_arg_visitor = nullptr;

static const char pt_closure_arg_attribute[] = "closureCallArgs";
static zend_string *pt_closure_arg_attribute_str = nullptr;

namespace phpstanturbo {

using visitors::NodeProp;

/* Mirrors PHPStan\Parser\ClosureArgVisitor. */
class ClosureArgVisitor
{
public:
	/* enterNode(); the twin always returns null, false = pending exception */
	[[nodiscard]] static bool enterNode(zend_object *visitor, zend_object *node)
	{
		static NodeProp calleeProp = PT_NODE_PROP(PT_CLASS_FUNC_CALL, "name");
		static NodeProp argsProp = PT_NODE_PROP(PT_CLASS_FUNC_CALL, "args");
		static NodeProp assignExprProp = PT_NODE_PROP(PT_CLASS_ASSIGN_EXPR, "expr");

		if (!visitors::isInstanceOf(node, PT_CLASS_FUNC_CALL)) return !EG(exception);
		zval *args = argsProp.of(node);
		if (visitors::isFirstClassCallable(args)) return true;

		zval *callee = calleeProp.of(node);
		if (callee == NULL || Z_TYPE_P(callee) != IS_OBJECT) return true;
		zend_object *calleeObject = Z_OBJ_P(callee);
		zend_object *target;
		if (visitors::isInstanceOf(calleeObject, PT_CLASS_ASSIGN_EXPR)) {
			/* ($f = function () {})(...) */
			target = assignExprProp.objectOf(calleeObject, PT_CLASS_CLOSURE_EXPR);
		} else {
			target = visitors::isInstanceOf(calleeObject, PT_CLASS_CLOSURE_EXPR) ? calleeObject : NULL;
		}
		if (target == NULL) return !EG(exception);

		if (args != NULL && Z_TYPE_P(args) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(args)) > 0) {
			visitors::setAttribute(target, pt_closure_arg_attribute_str, args);
		}
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::ClosureArgVisitor;

/* {{{ engine ABI glue: parameter parsing + registration */

static const pt_native_visitor pt_closure_arg_entry = {
	&pt_ce_closure_arg_visitor,
	ClosureArgVisitor::enterNode,
	NULL,
	NULL,
};

PT_MINIT_REGISTRATION(pt_register_closure_arg_visitor)
{
	pt_closure_arg_attribute_str = zend_string_init_interned(pt_closure_arg_attribute, sizeof(pt_closure_arg_attribute) - 1, 1);

	reg::Class cls("PHPStan\\Parser\\ClosureArgVisitor");
	ptdecl::ClosureArgVisitor::declareClass(cls);
	ptdecl::ClosureArgVisitor::declareProperties(cls);
	cls.publicClassConstantString("ATTRIBUTE_NAME", pt_closure_arg_attribute);

	cls.method(sigs::enterNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *node;
		if (!zp::parse<zp::Obj>(execute_data, node)) RETURN_THROWS();
		if (UNEXPECTED(!ClosureArgVisitor::enterNode(Z_OBJ_P(ZEND_THIS), Z_OBJ_P(node)))) RETURN_THROWS();
		RETURN_NULL();
	});

	cls.shadow(&pt_ce_closure_arg_visitor);
	pt_native_visitor_register(&pt_closure_arg_entry);
}

/* }}} */

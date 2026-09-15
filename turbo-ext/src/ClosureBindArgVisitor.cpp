/*
 * PHPStanTurbo\ClosureBindArgVisitor — native twin of
 * PHPStan\Parser\ClosureBindArgVisitor, declared under that name at
 * activation (final, extending PhpParser\NodeVisitorAbstract like the
 * original).
 *
 * Marks the closure argument of a Closure::bind() call that also passes a
 * new $this.
 *
 * enterNode() always returns null, so the visitor is also registered with
 * pt_native_visitor_register(): the native NodeTraverser then runs it
 * directly per node instead of calling into the engine (ParserVisitors.h).
 */

#include "ParserVisitors.h"
#include "generated/ClosureBindArgVisitor.h"

static zend_class_entry *pt_ce_closure_bind_arg_visitor = nullptr;

static const char pt_closure_bind_arg_attribute[] = "closureBindArg";
static zend_string *pt_closure_bind_arg_attribute_str = nullptr;

namespace phpstanturbo {

using visitors::NodeProp;

/* Mirrors PHPStan\Parser\ClosureBindArgVisitor. */
class ClosureBindArgVisitor
{
public:
	/* enterNode(); the twin always returns null, false = pending exception */
	[[nodiscard]] static bool enterNode(zend_object *visitor, zend_object *node)
	{
		static NodeProp classProp = PT_NODE_PROP(PT_CLASS_STATIC_CALL, "class");
		static NodeProp methodProp = PT_NODE_PROP(PT_CLASS_STATIC_CALL, "name");
		static NodeProp argsProp = PT_NODE_PROP(PT_CLASS_STATIC_CALL, "args");
		static NodeProp nameProp = PT_NAME_PROP;
		static NodeProp identifierProp = PT_IDENTIFIER_PROP;

		if (!visitors::isInstanceOf(node, PT_CLASS_STATIC_CALL)) return !EG(exception);
		zend_object *className = classProp.objectOf(node, PT_CLASS_NAME);
		if (className == NULL) return !EG(exception);
		zend_string *classString = visitors::nameString(className, nameProp);
		if (classString == NULL || !visitors::lowerEquals(classString, "closure")) return true;
		zend_object *method = methodProp.objectOf(node, PT_CLASS_IDENTIFIER);
		if (method == NULL) return !EG(exception);
		zend_string *methodName = visitors::nameString(method, identifierProp);
		if (methodName == NULL || !visitors::lowerEquals(methodName, "bind")) return true;
		zval *args = argsProp.of(node);
		if (visitors::isFirstClassCallable(args)) return true;

		if (args == NULL || Z_TYPE_P(args) != IS_ARRAY || zend_hash_num_elements(Z_ARRVAL_P(args)) <= 1) return true;
		zend_object *arg = visitors::argAt(args, 0);
		if (arg != NULL) {
			visitors::setAttributeTrue(arg, pt_closure_bind_arg_attribute_str);
		}
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::ClosureBindArgVisitor;

/* {{{ engine ABI glue: parameter parsing + registration */

static const pt_native_visitor pt_closure_bind_arg_entry = {
	&pt_ce_closure_bind_arg_visitor,
	ClosureBindArgVisitor::enterNode,
	NULL,
	NULL,
};

void pt_register_closure_bind_arg_visitor()
{
	pt_closure_bind_arg_attribute_str = zend_string_init_interned(pt_closure_bind_arg_attribute, sizeof(pt_closure_bind_arg_attribute) - 1, 1);

	reg::Class cls("PHPStan\\Parser\\ClosureBindArgVisitor");
	ptdecl::ClosureBindArgVisitor::declareClass(cls);
	ptdecl::ClosureBindArgVisitor::declareProperties(cls);
	cls.publicClassConstantString("ATTRIBUTE_NAME", pt_closure_bind_arg_attribute);

	cls.method("enterNode", reg::Public, 1, { reg::obj("node", "PhpParser\\Node") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *node;
		if (!zp::parse<zp::Obj>(execute_data, node)) RETURN_THROWS();
		if (UNEXPECTED(!ClosureBindArgVisitor::enterNode(Z_OBJ_P(ZEND_THIS), Z_OBJ_P(node)))) RETURN_THROWS();
		RETURN_NULL();
	});

	cls.shadow(&pt_ce_closure_bind_arg_visitor);
	pt_native_visitor_register(&pt_closure_bind_arg_entry);
}

/* }}} */

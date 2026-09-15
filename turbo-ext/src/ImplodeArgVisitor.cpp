/*
 * PHPStanTurbo\ImplodeArgVisitor — native twin of
 * PHPStan\Parser\ImplodeArgVisitor, declared under that name at
 * activation (final, extending PhpParser\NodeVisitorAbstract like the
 * original).
 *
 * Marks the first argument of an implode()/join() call.
 *
 * enterNode() always returns null, so the visitor is also registered with
 * pt_native_visitor_register(): the native NodeTraverser then runs it
 * directly per node instead of calling into the engine (ParserVisitors.h).
 */

#include "ParserVisitors.h"
#include "generated/ImplodeArgVisitor.h"

static zend_class_entry *pt_ce_implode_arg_visitor = nullptr;

static const char pt_implode_arg_attribute[] = "isImplodeArg";
static zend_string *pt_implode_arg_attribute_str = nullptr;

namespace phpstanturbo {

using visitors::FuncCallProps;
using visitors::NodeProp;

/* Mirrors PHPStan\Parser\ImplodeArgVisitor. */
class ImplodeArgVisitor
{
public:
	/* enterNode(); the twin always returns null, false = pending exception */
	[[nodiscard]] static bool enterNode(zend_object *visitor, zend_object *node)
	{
		static FuncCallProps call = PT_FUNC_CALL_PROPS;
		static NodeProp nameProp = PT_NAME_PROP;

		zval *args = NULL;
		zend_string *functionName = visitors::plainFuncCallName(node, call, nameProp, &args);
		if (functionName == NULL) return !EG(exception);
		if (!(visitors::lowerEquals(functionName, "implode")
			|| visitors::lowerEquals(functionName, "join"))) {
			return true;
		}
		zend_object *arg = visitors::argAt(args, 0);
		if (arg != NULL) {
			visitors::setAttributeTrue(arg, pt_implode_arg_attribute_str);
		}
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::ImplodeArgVisitor;

/* {{{ engine ABI glue: parameter parsing + registration */

static const pt_native_visitor pt_implode_arg_entry = {
	&pt_ce_implode_arg_visitor,
	ImplodeArgVisitor::enterNode,
	NULL,
	NULL,
};

void pt_register_implode_arg_visitor()
{
	pt_implode_arg_attribute_str = zend_string_init_interned(pt_implode_arg_attribute, sizeof(pt_implode_arg_attribute) - 1, 1);

	reg::Class cls("PHPStan\\Parser\\ImplodeArgVisitor");
	ptdecl::ImplodeArgVisitor::declareClass(cls);
	ptdecl::ImplodeArgVisitor::declareProperties(cls);
	cls.publicClassConstantString("ATTRIBUTE_NAME", pt_implode_arg_attribute);

	cls.method("enterNode", reg::Public, 1, { reg::obj("node", "PhpParser\\Node") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *node;
		if (!zp::parse<zp::Obj>(execute_data, node)) RETURN_THROWS();
		if (UNEXPECTED(!ImplodeArgVisitor::enterNode(Z_OBJ_P(ZEND_THIS), Z_OBJ_P(node)))) RETURN_THROWS();
		RETURN_NULL();
	});

	cls.shadow(&pt_ce_implode_arg_visitor);
	pt_native_visitor_register(&pt_implode_arg_entry);
}

/* }}} */

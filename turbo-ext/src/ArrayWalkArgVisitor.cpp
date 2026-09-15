/*
 * PHPStanTurbo\ArrayWalkArgVisitor — native twin of
 * PHPStan\Parser\ArrayWalkArgVisitor, declared under that name at
 * activation (final, extending PhpParser\NodeVisitorAbstract like the
 * original).
 *
 * Marks the array argument of an array_walk() call.
 *
 * enterNode() always returns null, so the visitor is also registered with
 * pt_native_visitor_register(): the native NodeTraverser then runs it
 * directly per node instead of calling into the engine (ParserVisitors.h).
 */

#include "ParserVisitors.h"
#include "generated/ArrayWalkArgVisitor.h"

static zend_class_entry *pt_ce_array_walk_arg_visitor = nullptr;

static const char pt_array_walk_arg_attribute[] = "isArrayWalkArg";
static zend_string *pt_array_walk_arg_attribute_str = nullptr;

namespace phpstanturbo {

using visitors::FuncCallProps;
using visitors::NodeProp;

/* Mirrors PHPStan\Parser\ArrayWalkArgVisitor. */
class ArrayWalkArgVisitor
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
		if (!(visitors::lowerEquals(functionName, "array_walk"))) return true;
		zend_object *arg = visitors::argAt(args, 0);
		if (arg != NULL) {
			visitors::setAttributeTrue(arg, pt_array_walk_arg_attribute_str);
		}
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::ArrayWalkArgVisitor;

/* {{{ engine ABI glue: parameter parsing + registration */

static const pt_native_visitor pt_array_walk_arg_entry = {
	&pt_ce_array_walk_arg_visitor,
	ArrayWalkArgVisitor::enterNode,
	NULL,
	NULL,
};

void pt_register_array_walk_arg_visitor()
{
	pt_array_walk_arg_attribute_str = zend_string_init_interned(pt_array_walk_arg_attribute, sizeof(pt_array_walk_arg_attribute) - 1, 1);

	reg::Class cls("PHPStan\\Parser\\ArrayWalkArgVisitor");
	ptdecl::ArrayWalkArgVisitor::declareClass(cls);
	ptdecl::ArrayWalkArgVisitor::declareProperties(cls);
	cls.publicClassConstantString("ATTRIBUTE_NAME", pt_array_walk_arg_attribute);

	cls.method("enterNode", reg::Public, 1, { reg::obj("node", "PhpParser\\Node") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *node;
		if (!zp::parse<zp::Obj>(execute_data, node)) RETURN_THROWS();
		if (UNEXPECTED(!ArrayWalkArgVisitor::enterNode(Z_OBJ_P(ZEND_THIS), Z_OBJ_P(node)))) RETURN_THROWS();
		RETURN_NULL();
	});

	cls.shadow(&pt_ce_array_walk_arg_visitor);
	pt_native_visitor_register(&pt_array_walk_arg_entry);
}

/* }}} */

/*
 * PHPStanTurbo\NewAssignedToPropertyVisitor — native twin of
 * PHPStan\Parser\NewAssignedToPropertyVisitor, declared under that name at
 * activation (final, extending PhpParser\NodeVisitorAbstract like the
 * original).
 *
 * Records the property a `new` expression is assigned to, on that
 * expression.
 *
 * enterNode() always returns null, so the visitor is also registered with
 * pt_native_visitor_register(): the native NodeTraverser then runs it
 * directly per node instead of calling into the engine (ParserVisitors.h).
 */

#include "ParserVisitors.h"
#include "generated/NewAssignedToPropertyVisitor.h"

static zend_class_entry *pt_ce_new_assigned_to_property_visitor = nullptr;

static const char pt_new_assigned_to_property_attribute[] = "assignedToProperty";
static zend_string *pt_new_assigned_to_property_attribute_str = nullptr;

namespace phpstanturbo {

using visitors::NodeProp;

/* Mirrors PHPStan\Parser\NewAssignedToPropertyVisitor. */
class NewAssignedToPropertyVisitor
{
public:
	/* enterNode(); the twin always returns null, false = pending exception */
	[[nodiscard]] static bool enterNode(zend_object *visitor, zend_object *node)
	{
		/* Assign, AssignRef and AssignOp each declare $var/$expr themselves,
		 * so the offsets are memoized per assignment kind */
		static NodeProp varProps[3] = {
			PT_NODE_PROP(PT_CLASS_ASSIGN_EXPR, "var"),
			PT_NODE_PROP(PT_CLASS_ASSIGN_REF_EXPR, "var"),
			PT_NODE_PROP(PT_CLASS_ASSIGN_OP_EXPR, "var"),
		};
		static NodeProp exprProps[3] = {
			PT_NODE_PROP(PT_CLASS_ASSIGN_EXPR, "expr"),
			PT_NODE_PROP(PT_CLASS_ASSIGN_REF_EXPR, "expr"),
			PT_NODE_PROP(PT_CLASS_ASSIGN_OP_EXPR, "expr"),
		};

		int kind;
		if (visitors::isInstanceOf(node, PT_CLASS_ASSIGN_EXPR)) {
			kind = 0;
		} else if (visitors::isInstanceOf(node, PT_CLASS_ASSIGN_REF_EXPR)) {
			kind = 1;
		} else if (visitors::isInstanceOf(node, PT_CLASS_ASSIGN_OP_EXPR)) {
			kind = 2;
		} else {
			return !EG(exception);
		}

		zval *var = varProps[kind].of(node);
		if (var == NULL || Z_TYPE_P(var) != IS_OBJECT) return true;
		if (!visitors::isInstanceOf(Z_OBJ_P(var), PT_CLASS_PROPERTY_FETCH)
			&& !visitors::isInstanceOf(Z_OBJ_P(var), PT_CLASS_STATIC_PROPERTY_FETCH)) {
			return !EG(exception);
		}
		zend_object *newExpr = exprProps[kind].objectOf(node, PT_CLASS_NEW);
		if (newExpr == NULL) return !EG(exception);
		visitors::setAttribute(newExpr, pt_new_assigned_to_property_attribute_str, var);
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::NewAssignedToPropertyVisitor;

/* {{{ engine ABI glue: parameter parsing + registration */

static const pt_native_visitor pt_new_assigned_to_property_entry = {
	&pt_ce_new_assigned_to_property_visitor,
	NewAssignedToPropertyVisitor::enterNode,
	NULL,
	NULL,
};

void pt_register_new_assigned_to_property_visitor()
{
	pt_new_assigned_to_property_attribute_str = zend_string_init_interned(pt_new_assigned_to_property_attribute, sizeof(pt_new_assigned_to_property_attribute) - 1, 1);

	reg::Class cls("PHPStan\\Parser\\NewAssignedToPropertyVisitor");
	ptdecl::NewAssignedToPropertyVisitor::declareClass(cls);
	ptdecl::NewAssignedToPropertyVisitor::declareProperties(cls);
	cls.publicClassConstantString("ATTRIBUTE_NAME", pt_new_assigned_to_property_attribute);

	cls.method("enterNode", reg::Public, 1, { reg::obj("node", "PhpParser\\Node") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *node;
		if (!zp::parse<zp::Obj>(execute_data, node)) RETURN_THROWS();
		if (UNEXPECTED(!NewAssignedToPropertyVisitor::enterNode(Z_OBJ_P(ZEND_THIS), Z_OBJ_P(node)))) RETURN_THROWS();
		RETURN_NULL();
	});

	cls.shadow(&pt_ce_new_assigned_to_property_visitor);
	pt_native_visitor_register(&pt_new_assigned_to_property_entry);
}

/* }}} */

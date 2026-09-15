/*
 * PHPStanTurbo\TypeTraverserInstanceofVisitor — native twin of
 * PHPStan\Parser\TypeTraverserInstanceofVisitor, declared under that name at
 * activation (final, extending PhpParser\NodeVisitorAbstract like the
 * original).
 *
 * Marks the instanceof expressions written inside a TypeTraverser::map()
 * callback, tracked with a nesting depth over the traversal.
 *
 * enterNode()/leaveNode() always return null and beforeTraverse() only
 * resets the depth, so the visitor is also registered with
 * pt_native_visitor_register(): the native NodeTraverser then runs it
 * directly per node instead of calling into the engine (ParserVisitors.h).
 */

#include "ParserVisitors.h"
#include "generated/TypeTraverserInstanceofVisitor.h"

static zend_class_entry *pt_ce_type_traverser_instanceof_visitor = nullptr;

/* the class's only property, `private int $depth = 0` */
#define PT_TYPE_TRAVERSER_INSTANCEOF_PROP_DEPTH 0

static const char pt_type_traverser_instanceof_attribute[] = "insideTypeTraverserMap";
static zend_string *pt_type_traverser_instanceof_attribute_str = nullptr;

namespace phpstanturbo {

using visitors::NodeProp;

/* Mirrors PHPStan\Parser\TypeTraverserInstanceofVisitor. */
class TypeTraverserInstanceofVisitor
{
public:
	/* beforeTraverse(); the twin only resets the depth and returns null */
	static void beforeTraverse(zend_object *visitor)
	{
		zv::ObjRef(visitor).propAtWrite(PT_TYPE_TRAVERSER_INSTANCEOF_PROP_DEPTH, zv::Val::integer(0));
	}

	/* enterNode(); the twin always returns null, false = pending exception */
	[[nodiscard]] static bool enterNode(zend_object *visitor, zend_object *node)
	{
		zval *depth = depthOf(visitor);
		if (visitors::isInstanceOf(node, PT_CLASS_INSTANCEOF_EXPR) && Z_LVAL_P(depth) > 0) {
			visitors::setAttributeTrue(node, pt_type_traverser_instanceof_attribute_str);
			return true;
		}
		if (UNEXPECTED(EG(exception))) return false;
		if (isTypeTraverserMapCall(node)) {
			Z_LVAL_P(depth)++;
		}
		return !EG(exception);
	}

	/* leaveNode(); the twin always returns null, false = pending exception */
	[[nodiscard]] static bool leaveNode(zend_object *visitor, zend_object *node)
	{
		if (isTypeTraverserMapCall(node)) {
			Z_LVAL_P(depthOf(visitor))--;
		}
		return !EG(exception);
	}

private:
	/* TypeTraverser::map(...) — a static call to that exact class and method */
	static bool isTypeTraverserMapCall(zend_object *node)
	{
		static NodeProp classProp = PT_NODE_PROP(PT_CLASS_STATIC_CALL, "class");
		static NodeProp methodProp = PT_NODE_PROP(PT_CLASS_STATIC_CALL, "name");
		static NodeProp nameProp = PT_NAME_PROP;
		static NodeProp identifierProp = PT_IDENTIFIER_PROP;

		if (!visitors::isInstanceOf(node, PT_CLASS_STATIC_CALL)) return false;
		zend_object *className = classProp.objectOf(node, PT_CLASS_NAME);
		if (className == NULL) return false;
		zend_string *classString = visitors::nameString(className, nameProp);
		if (classString == NULL || !visitors::lowerEquals(classString, "phpstan\\type\\typetraverser")) return false;
		zend_object *method = methodProp.objectOf(node, PT_CLASS_IDENTIFIER);
		if (method == NULL) return false;
		zend_string *methodName = visitors::nameString(method, identifierProp);
		return methodName != NULL && visitors::lowerEquals(methodName, "map");
	}

	/* the depth slot; `private int $depth` always holds an IS_LONG */
	static zval *depthOf(zend_object *visitor)
	{
		return zv::ObjRef(visitor).propAt(PT_TYPE_TRAVERSER_INSTANCEOF_PROP_DEPTH).deref().raw();
	}
};

} // namespace phpstanturbo

using phpstanturbo::TypeTraverserInstanceofVisitor;

/* {{{ engine ABI glue: parameter parsing + registration */

static const pt_native_visitor pt_type_traverser_instanceof_entry = {
	&pt_ce_type_traverser_instanceof_visitor,
	TypeTraverserInstanceofVisitor::enterNode,
	TypeTraverserInstanceofVisitor::leaveNode,
	TypeTraverserInstanceofVisitor::beforeTraverse,
};

void pt_register_type_traverser_instanceof_visitor()
{
	pt_type_traverser_instanceof_attribute_str = zend_string_init_interned(pt_type_traverser_instanceof_attribute, sizeof(pt_type_traverser_instanceof_attribute) - 1, 1);

	reg::Class cls("PHPStan\\Parser\\TypeTraverserInstanceofVisitor");
	ptdecl::TypeTraverserInstanceofVisitor::declareClass(cls);
	/* "depth" must stay slot 0 (PT_TYPE_TRAVERSER_INSTANCEOF_PROP_DEPTH) */
	ptdecl::TypeTraverserInstanceofVisitor::declareProperties(cls);
	cls.publicClassConstantString("ATTRIBUTE_NAME", pt_type_traverser_instanceof_attribute);

	cls.method("beforeTraverse", reg::Public, 1, { reg::arrayArg("nodes") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		HashTable *nodes;
		if (!zp::parse<zp::Ht>(execute_data, nodes)) RETURN_THROWS();
		(void) nodes;
		TypeTraverserInstanceofVisitor::beforeTraverse(Z_OBJ_P(ZEND_THIS));
		RETURN_NULL();
	});

	cls.method("enterNode", reg::Public, 1, { reg::obj("node", "PhpParser\\Node") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *node;
		if (!zp::parse<zp::Obj>(execute_data, node)) RETURN_THROWS();
		if (UNEXPECTED(!TypeTraverserInstanceofVisitor::enterNode(Z_OBJ_P(ZEND_THIS), Z_OBJ_P(node)))) RETURN_THROWS();
		RETURN_NULL();
	});

	cls.method("leaveNode", reg::Public, 1, { reg::obj("node", "PhpParser\\Node") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *node;
		if (!zp::parse<zp::Obj>(execute_data, node)) RETURN_THROWS();
		if (UNEXPECTED(!TypeTraverserInstanceofVisitor::leaveNode(Z_OBJ_P(ZEND_THIS), Z_OBJ_P(node)))) RETURN_THROWS();
		RETURN_NULL();
	});

	cls.shadow(&pt_ce_type_traverser_instanceof_visitor);
	pt_native_visitor_register(&pt_type_traverser_instanceof_entry);
}

/* }}} */

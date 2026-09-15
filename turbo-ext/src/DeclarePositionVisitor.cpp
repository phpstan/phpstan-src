/*
 * PHPStanTurbo\DeclarePositionVisitor — native twin of
 * PHPStan\Parser\DeclarePositionVisitor, declared under that name at
 * activation (final, extending PhpParser\NodeVisitorAbstract like the
 * original).
 *
 * Records whether a declare() statement is the file's first statement (a
 * shebang InlineHTML does not count).
 *
 * enterNode() always returns null and beforeTraverse() only resets the
 * flag, so the visitor is also registered with pt_native_visitor_register():
 * the native NodeTraverser then runs it directly per node instead of
 * calling into the engine (ParserVisitors.h).
 */

#include "ParserVisitors.h"
#include "generated/DeclarePositionVisitor.h"

static zend_class_entry *pt_ce_declare_position_visitor = nullptr;

/* the class's only property, `private bool $isFirstStatement = true` */
#define PT_DECLARE_POSITION_PROP_IS_FIRST 0

static const char pt_declare_position_attribute[] = "isFirstStatement";
static zend_string *pt_declare_position_attribute_str = nullptr;

namespace phpstanturbo {

using visitors::NodeProp;

/* Mirrors PHPStan\Parser\DeclarePositionVisitor. */
class DeclarePositionVisitor
{
public:
	/* beforeTraverse(); the twin only resets the flag and returns null */
	static void beforeTraverse(zend_object *visitor)
	{
		zv::ObjRef(visitor).propAtWrite(PT_DECLARE_POSITION_PROP_IS_FIRST, zv::Val::boolean(true));
	}

	/* enterNode(); the twin always returns null, false = pending exception */
	[[nodiscard]] static bool enterNode(zend_object *visitor, zend_object *node)
	{
		static NodeProp inlineHtmlValueProp = PT_NODE_PROP(PT_CLASS_INLINE_HTML_STMT, "value");

		zval *isFirst = zv::ObjRef(visitor).propAt(PT_DECLARE_POSITION_PROP_IS_FIRST).deref().raw();
		bool isFirstStatement = Z_TYPE_P(isFirst) == IS_TRUE;

		/* ignore shebang */
		if (isFirstStatement && visitors::isInstanceOf(node, PT_CLASS_INLINE_HTML_STMT)) {
			zval *value = inlineHtmlValueProp.of(node);
			if (value != NULL && Z_TYPE_P(value) == IS_STRING
				&& Z_STRLEN_P(value) >= 2 && Z_STRVAL_P(value)[0] == '#' && Z_STRVAL_P(value)[1] == '!') {
				return true;
			}
		}
		if (UNEXPECTED(EG(exception))) return false;

		if (!visitors::isInstanceOf(node, PT_CLASS_STMT)) return !EG(exception);
		if (visitors::isInstanceOf(node, PT_CLASS_DECLARE_STMT)) {
			visitors::setAttributeBool(node, pt_declare_position_attribute_str, isFirstStatement);
		}
		zv::ObjRef(visitor).propAtWrite(PT_DECLARE_POSITION_PROP_IS_FIRST, zv::Val::boolean(false));
		return !EG(exception);
	}
};

} // namespace phpstanturbo

using phpstanturbo::DeclarePositionVisitor;

/* {{{ engine ABI glue: parameter parsing + registration */

static const pt_native_visitor pt_declare_position_entry = {
	&pt_ce_declare_position_visitor,
	DeclarePositionVisitor::enterNode,
	NULL,
	DeclarePositionVisitor::beforeTraverse,
};

void pt_register_declare_position_visitor()
{
	pt_declare_position_attribute_str = zend_string_init_interned(pt_declare_position_attribute, sizeof(pt_declare_position_attribute) - 1, 1);

	reg::Class cls("PHPStan\\Parser\\DeclarePositionVisitor");
	ptdecl::DeclarePositionVisitor::declareClass(cls);
	/* "isFirstStatement" must stay slot 0 (PT_DECLARE_POSITION_PROP_IS_FIRST) */
	ptdecl::DeclarePositionVisitor::declareProperties(cls);
	cls.publicClassConstantString("ATTRIBUTE_NAME", pt_declare_position_attribute);

	cls.method("beforeTraverse", reg::Public, 1, { reg::arrayArg("nodes") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		HashTable *nodes;
		if (!zp::parse<zp::Ht>(execute_data, nodes)) RETURN_THROWS();
		(void) nodes;
		DeclarePositionVisitor::beforeTraverse(Z_OBJ_P(ZEND_THIS));
		RETURN_NULL();
	});

	cls.method("enterNode", reg::Public, 1, { reg::obj("node", "PhpParser\\Node") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *node;
		if (!zp::parse<zp::Obj>(execute_data, node)) RETURN_THROWS();
		if (UNEXPECTED(!DeclarePositionVisitor::enterNode(Z_OBJ_P(ZEND_THIS), Z_OBJ_P(node)))) RETURN_THROWS();
		RETURN_NULL();
	});

	cls.shadow(&pt_ce_declare_position_visitor);
	pt_native_visitor_register(&pt_declare_position_entry);
}

/* }}} */

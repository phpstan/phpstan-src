/*
 * PHPStanTurbo\TraitCollectingVisitor — native twin of
 * PHPStan\Parser\TraitCollectingVisitor, declared under that name at
 * activation (final, extending PhpParser\NodeVisitorAbstract like the
 * original).
 *
 * Collects the trait declarations of the traversed file into $traits.
 *
 * enterNode() always returns null, so the visitor is also registered with
 * pt_native_visitor_register(): the native NodeTraverser then runs it
 * directly per node instead of calling into the engine (ParserVisitors.h).
 */

#include "ParserVisitors.h"
#include "generated/TraitCollectingVisitor.h"

static zend_class_entry *pt_ce_trait_collecting_visitor = nullptr;

/* the class's only property, `public array $traits = []` */
#define PT_TRAIT_COLLECTING_PROP_TRAITS 0

namespace phpstanturbo {

/* Mirrors PHPStan\Parser\TraitCollectingVisitor. */
class TraitCollectingVisitor
{
public:
	/* enterNode(); the twin always returns null, false = pending exception */
	[[nodiscard]] static bool enterNode(zend_object *visitor, zend_object *node)
	{
		if (!visitors::isInstanceOf(node, PT_CLASS_TRAIT_STMT)) return !EG(exception);
		zval nodeZv;
		ZVAL_OBJ(&nodeZv, node);
		visitors::pushStack(zv::ObjRef(visitor).propAt(PT_TRAIT_COLLECTING_PROP_TRAITS).deref().raw(), &nodeZv);
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::TraitCollectingVisitor;

/* {{{ engine ABI glue: parameter parsing + registration */

static const pt_native_visitor pt_trait_collecting_entry = {
	&pt_ce_trait_collecting_visitor,
	TraitCollectingVisitor::enterNode,
	NULL,
	NULL,
};

void pt_register_trait_collecting_visitor()
{
	reg::Class cls("PHPStan\\Parser\\TraitCollectingVisitor");
	ptdecl::TraitCollectingVisitor::declareClass(cls);
	/* "traits" must stay slot 0 (PT_TRAIT_COLLECTING_PROP_TRAITS) */
	ptdecl::TraitCollectingVisitor::declareProperties(cls);

	cls.method("enterNode", reg::Public, 1, { reg::obj("node", "PhpParser\\Node") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *node;
		if (!zp::parse<zp::Obj>(execute_data, node)) RETURN_THROWS();
		if (UNEXPECTED(!TraitCollectingVisitor::enterNode(Z_OBJ_P(ZEND_THIS), Z_OBJ_P(node)))) RETURN_THROWS();
		RETURN_NULL();
	});

	cls.shadow(&pt_ce_trait_collecting_visitor);
	pt_native_visitor_register(&pt_trait_collecting_entry);
}

/* }}} */

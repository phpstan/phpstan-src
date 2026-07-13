/*
 * PHPStanTurbo\NodeScanner — native node scanning helpers.
 *
 * nodeIsOrContainsYield(): recursively checks whether the given node is or
 * contains a Yield_/YieldFrom expression, using the shared findFirst walker
 * from the support layer. Ported from the proven ScopeOps C implementation.
 */

#include "support.h"
#include "zv.h"

static zend_class_entry *pt_ce_node_scanner;

namespace phpstanturbo {

/* Mirrors PHPStan\Node\NodeScanner. */
class NodeScanner
{
public:
	/* failed = pending exception */
	static bool nodeIsOrContainsYield(zv::ObjRef node, bool &failed)
	{
		pt_find_ctx ctx = {};
		zend_object *found = pt_find_first_recursive(node.raw(), isYieldNode, &ctx);
		failed = ctx.failed;
		return found != NULL;
	}

private:
	/* matcher for the shared findFirst walker */
	static bool isYieldNode(zend_object *node, void *ctx)
	{
		zend_class_entry *yieldCe = pt_class(PT_CLASS_YIELD);
		zend_class_entry *yieldFromCe = pt_class(PT_CLASS_YIELD_FROM);

		if (UNEXPECTED(yieldCe == NULL || yieldFromCe == NULL)) {
			((pt_find_ctx *) ctx)->failed = true;
			return false;
		}
		return instanceof_function(node->ce, yieldCe) || instanceof_function(node->ce, yieldFromCe);
	}
};

} // namespace phpstanturbo

using phpstanturbo::NodeScanner;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_node_scanner()
{
	reg::Class cls("PHPStanTurbo\\NodeScanner");

	cls.method("nodeIsOrContainsYield", reg::PublicStatic, 1, { reg::objectArg("node") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *node;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT(node)
		ZEND_PARSE_PARAMETERS_END();
		bool failed = false;
		bool result = NodeScanner::nodeIsOrContainsYield(zv::ObjRef(node), failed);
		if (UNEXPECTED(failed)) {
			RETURN_THROWS();
		}
		RETURN_BOOL(result);
	});

	/* not final */
	pt_ce_node_scanner = cls.register_();
}

/* }}} */

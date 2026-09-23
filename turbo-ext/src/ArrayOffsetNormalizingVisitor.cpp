/*
 * PHPStanTurbo\ArrayOffsetNormalizingVisitor — native twin of
 * PHPStan\Parser\ArrayOffsetNormalizingVisitor, declared under that name at
 * activation (final, extending PhpParser\NodeVisitorAbstract like the
 * original).
 *
 * Rewrites the *spelling* attribute of an array offset literal to a
 * canonical form, so `$a['k']` and `$a["k"]` produce the same expression
 * key (the twin's class doc explains why this lives here and not in the
 * printer).
 *
 * enterNode() always returns null, so the visitor is also registered with
 * pt_native_visitor_register(): the native NodeTraverser then runs it
 * directly per node instead of calling into the engine (ParserVisitors.h).
 */

#include "ParserVisitors.h"
#include "generated/ArrayOffsetNormalizingVisitor.h"

namespace sigs = ptdecl::ArrayOffsetNormalizingVisitor::sig;

static zend_class_entry *pt_ce_array_offset_normalizing_visitor = nullptr;

static const char pt_array_offset_kind_attribute[] = "kind";
static zend_string *pt_array_offset_kind_attribute_str = nullptr;

/* php-parser's frozen literal-spelling constants (String_::KIND_*, Int_::KIND_*) */
#define PT_STRING_KIND_SINGLE_QUOTED 1
#define PT_STRING_KIND_DOUBLE_QUOTED 2
#define PT_INT_KIND_DEC 10

namespace phpstanturbo {

using visitors::NodeProp;

/* Mirrors PHPStan\Parser\ArrayOffsetNormalizingVisitor. */
class ArrayOffsetNormalizingVisitor
{
public:
	/* enterNode(); the twin always returns null, false = pending exception */
	[[nodiscard]] static bool enterNode(zend_object *visitor, zend_object *node)
	{
		static NodeProp dimProp = PT_NODE_PROP(PT_CLASS_ARRAY_DIM_FETCH, "dim");
		static NodeProp stringValueProp = PT_NODE_PROP(PT_CLASS_SCALAR_STRING, "value");

		if (!visitors::isInstanceOf(node, PT_CLASS_ARRAY_DIM_FETCH)) return !EG(exception);
		zval *dimValue = dimProp.of(node);
		if (dimValue == NULL || Z_TYPE_P(dimValue) != IS_OBJECT) return true;
		zend_object *dim = Z_OBJ_P(dimValue);

		if (visitors::isInstanceOf(dim, PT_CLASS_SCALAR_STRING)) {
			/* Single quotes normally, double quotes when the value holds
			 * control characters — the same canonical form as
			 * ConstantStringType::export(), and the one that keeps the
			 * expression key free of newlines. */
			zval *value = stringValueProp.of(dim);
			bool hasControlChar = value != NULL && Z_TYPE_P(value) == IS_STRING && containsControlChar(Z_STR_P(value));
			visitors::setAttributeLong(dim, pt_array_offset_kind_attribute_str,
				hasControlChar ? PT_STRING_KIND_DOUBLE_QUOTED : PT_STRING_KIND_SINGLE_QUOTED);
			return true;
		}
		if (visitors::isInstanceOf(dim, PT_CLASS_INTERPOLATED_STRING)) {
			visitors::setAttributeLong(dim, pt_array_offset_kind_attribute_str, PT_STRING_KIND_DOUBLE_QUOTED);
			return true;
		}
		if (visitors::isInstanceOf(dim, PT_CLASS_SCALAR_INT)) {
			visitors::setAttributeLong(dim, pt_array_offset_kind_attribute_str, PT_INT_KIND_DEC);
			return true;
		}
		return !EG(exception);
	}

private:
	/* preg_match('/[\x00-\x1f]/', $value) === 1 */
	static bool containsControlChar(zend_string *value)
	{
		const char *p = ZSTR_VAL(value);
		for (size_t i = 0; i < ZSTR_LEN(value); i++) {
			if ((unsigned char) p[i] <= 0x1f) return true;
		}
		return false;
	}
};

} // namespace phpstanturbo

using phpstanturbo::ArrayOffsetNormalizingVisitor;

/* {{{ engine ABI glue: parameter parsing + registration */

static const pt_native_visitor pt_array_offset_normalizing_entry = {
	&pt_ce_array_offset_normalizing_visitor,
	ArrayOffsetNormalizingVisitor::enterNode,
	NULL,
	NULL,
};

void pt_register_array_offset_normalizing_visitor()
{
	pt_array_offset_kind_attribute_str = zend_string_init_interned(pt_array_offset_kind_attribute, sizeof(pt_array_offset_kind_attribute) - 1, 1);

	reg::Class cls("PHPStan\\Parser\\ArrayOffsetNormalizingVisitor");
	ptdecl::ArrayOffsetNormalizingVisitor::declareClass(cls);
	ptdecl::ArrayOffsetNormalizingVisitor::declareProperties(cls);

	cls.method(sigs::enterNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *node;
		if (!zp::parse<zp::Obj>(execute_data, node)) RETURN_THROWS();
		if (UNEXPECTED(!ArrayOffsetNormalizingVisitor::enterNode(Z_OBJ_P(ZEND_THIS), Z_OBJ_P(node)))) RETURN_THROWS();
		RETURN_NULL();
	});

	cls.shadow(&pt_ce_array_offset_normalizing_visitor);
	pt_native_visitor_register(&pt_array_offset_normalizing_entry);
}

/* }}} */

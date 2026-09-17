/*
 * Native readers of php-parser's Name and Identifier nodes.
 *
 * PhpParser\Node\Name / Name\FullyQualified / Name\Relative and
 * Identifier / VarLikeIdentifier stay PHP: __toString(), toString() and
 * toLowerString() return (the lowercase of) the `name` property,
 * isFullyQualified() is a constant answer of each Name class, and the
 * constructors write `attributes` and `name` (a non-empty string as given).
 * For exactly those five classes the readers use the slots and the factory
 * writes them in place; any other class (a subclass may override a getter)
 * calls the methods and constructs through the class map.
 *
 * Class entries are resolved through the class map without autoloading (an
 * object of an undeclared class cannot exist), again once the class table
 * grew, and the offsets once per request.
 */

#include "support.h"
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"

namespace {

enum : uint32_t
{
	PT_NAME_KIND_NAME = 0,
	PT_NAME_KIND_FULLY_QUALIFIED,
	PT_NAME_KIND_RELATIVE,
	PT_NAME_KIND_IDENTIFIER,
	PT_NAME_KIND_VAR_LIKE_IDENTIFIER,
	PT_NAME_KIND_COUNT,
};

/* Name and its subclasses share Name's slots, the identifiers Identifier's */
enum : uint32_t
{
	PT_NAME_FAMILY_NAME = 0,
	PT_NAME_FAMILY_IDENTIFIER,
	PT_NAME_FAMILY_COUNT,
};

const int pt_nna_class_indices[PT_NAME_KIND_COUNT] = {
	PT_CLASS_NAME, PT_CLASS_FULLY_QUALIFIED, PT_CLASS_RELATIVE_NAME, PT_CLASS_IDENTIFIER, PT_CLASS_VAR_LIKE_IDENTIFIER,
};

inline uint32_t familyOf(uint32_t kind) { return kind >= PT_NAME_KIND_IDENTIFIER ? PT_NAME_FAMILY_IDENTIFIER : PT_NAME_FAMILY_NAME; }

struct FamilySlots
{
	uint32_t name;       /* declared on Name / Identifier */
	uint32_t attributes; /* declared on NodeAbstract */
	bool resolved;
};

struct NameLayout
{
	uint32_t generation;
	uint32_t classCount; /* EG(class_table) size at the last lookup of a class not declared yet */
	zend_class_entry *ces[PT_NAME_KIND_COUNT];
	FamilySlots families[PT_NAME_FAMILY_COUNT];
	bool usable;
};

NameLayout pt_nna_layout = { 0, 0, {}, {}, true };

pt_method_site pt_nna_to_string_site;
pt_method_site pt_nna_to_lower_string_site;
pt_method_site pt_nna_is_fully_qualified_site;

inline uint32_t matchingKind(const NameLayout &layout, zend_class_entry *ce)
{
	for (uint32_t kind = 0; kind < PT_NAME_KIND_COUNT; kind++) {
		if (layout.ces[kind] == ce) {
			return layout.families[familyOf(kind)].resolved ? kind : PT_NAME_KIND_COUNT;
		}
	}
	return PT_NAME_KIND_COUNT;
}

/* the kind of exactly one of the five class entries, or PT_NAME_KIND_COUNT
 * for any other; the classes not declared yet are looked up again once the
 * class table grew */
zend_never_inline uint32_t resolveNameKind(zend_class_entry *ce)
{
	NameLayout &layout = pt_nna_layout;
	uint32_t classCount = zend_hash_num_elements(EG(class_table));
	if (layout.generation != pt_engine_generation) {
		layout = { pt_engine_generation, UINT32_MAX, {}, {}, true };
	}
	if (!layout.usable || ce == NULL) return PT_NAME_KIND_COUNT;
	if (layout.classCount != classCount) {
		layout.classCount = classCount;
		for (uint32_t kind = 0; kind < PT_NAME_KIND_COUNT; kind++) {
			if (layout.ces[kind] != NULL) continue;
			layout.ces[kind] = pt_class_loaded(pt_nna_class_indices[kind]);
			if (UNEXPECTED(EG(exception) != NULL)) {
				layout.usable = false;
				return PT_NAME_KIND_COUNT;
			}
		}
		static const uint32_t familyBase[PT_NAME_FAMILY_COUNT] = { PT_NAME_KIND_NAME, PT_NAME_KIND_IDENTIFIER };
		for (uint32_t family = 0; family < PT_NAME_FAMILY_COUNT; family++) {
			FamilySlots &slots = layout.families[family];
			zend_class_entry *baseCe = layout.ces[familyBase[family]];
			if (slots.resolved || baseCe == NULL) continue;
			int32_t name = pt_instance_prop_offset(baseCe, PT_LC("name"));
			int32_t attributes = pt_instance_prop_offset(baseCe, PT_LC("attributes"));
			if (UNEXPECTED(name < 0 || attributes < 0)) {
				layout.usable = false;
				return PT_NAME_KIND_COUNT;
			}
			slots = { (uint32_t) name, (uint32_t) attributes, true };
		}
	}
	/* the subclasses inherit their family's slots at the same offsets */
	return matchingKind(layout, ce);
}

inline uint32_t nameKindOf(zend_class_entry *ce)
{
	const NameLayout &layout = pt_nna_layout;
	if (EXPECTED(layout.generation == pt_engine_generation)) {
		uint32_t kind = matchingKind(layout, ce);
		if (EXPECTED(kind != PT_NAME_KIND_COUNT)) return kind;
	}
	return resolveNameKind(ce);
}

/* the initialized `name` string of exactly one of the five classes, NULL
 * otherwise */
inline zend_string *nameStringOf(zval *node)
{
	if (UNEXPECTED(Z_TYPE_P(node) != IS_OBJECT)) return NULL;
	uint32_t kind = nameKindOf(Z_OBJCE_P(node));
	if (UNEXPECTED(kind == PT_NAME_KIND_COUNT)) return NULL;
	zval *value = OBJ_PROP(Z_OBJ_P(node), pt_nna_layout.families[familyOf(kind)].name);
	return EXPECTED(Z_TYPE_P(value) == IS_STRING) ? Z_STR_P(value) : NULL;
}

} // namespace

zend_string *pt_name_node_cast_string(zval *node)
{
	/* (string) $node: __toString() returns $this->name */
	zend_string *name = nameStringOf(node);
	if (EXPECTED(name != NULL)) return zend_string_copy(name);
	return zval_try_get_string(node);
}

zv::Val pt_name_node_to_string(zval *node)
{
	zend_string *name = nameStringOf(node);
	if (EXPECTED(name != NULL)) return zv::Val::string(name);
	if (UNEXPECTED(Z_TYPE_P(node) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function toString() on %s", zend_zval_value_name(node));
		return zv::Val();
	}
	return pt_call_method_cached(pt_nna_to_string_site, Z_OBJ_P(node), PT_LC("tostring"), 0, NULL);
}

zv::Val pt_name_node_to_lower_string(zval *node)
{
	zend_string *name = nameStringOf(node);
	/* strtolower($this->name): ASCII-only since PHP 8.2, as zend_string_tolower() */
	if (EXPECTED(name != NULL)) return zv::Val::adoptString(zend_string_tolower(name));
	if (UNEXPECTED(Z_TYPE_P(node) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function toLowerString() on %s", zend_zval_value_name(node));
		return zv::Val();
	}
	return pt_call_method_cached(pt_nna_to_lower_string_site, Z_OBJ_P(node), PT_LC("tolowerstring"), 0, NULL);
}

bool pt_name_node_is_fully_qualified(zval *node, bool &out)
{
	if (UNEXPECTED(Z_TYPE_P(node) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function isFullyQualified() on %s", zend_zval_value_name(node));
		return false;
	}
	uint32_t kind = nameKindOf(Z_OBJCE_P(node));
	if (EXPECTED(kind < PT_NAME_KIND_IDENTIFIER)) {
		/* Name / Relative: false; FullyQualified: true */
		out = kind == PT_NAME_KIND_FULLY_QUALIFIED;
		return true;
	}
	zv::Val result = pt_call_method_cached(pt_nna_is_fully_qualified_site, Z_OBJ_P(node), PT_LC("isfullyqualified"), 0, NULL);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

zv::Val pt_name_node_new(int classIdx, zval *name)
{
	/* Name: final public function __construct($name, array $attributes = []) {
	 *     $this->attributes = $attributes; $this->name = self::prepareName($name); }
	 * Identifier: public function __construct(string $name, array $attributes = []) {
	 *     if ($name === '') throw ...; $this->attributes = $attributes; $this->name = $name; }
	 * — for a non-empty string both write it as is */
	if (EXPECTED(Z_TYPE_P(name) == IS_STRING && Z_STRLEN_P(name) != 0)) {
		zend_class_entry *ce = pt_class(classIdx);
		if (UNEXPECTED(ce == NULL)) return zv::Val();
		uint32_t kind = nameKindOf(ce);
		if (EXPECTED(kind != PT_NAME_KIND_COUNT)) {
			const FamilySlots &slots = pt_nna_layout.families[familyOf(kind)];
			zval object;
			if (UNEXPECTED(object_init_ex(&object, ce) != SUCCESS)) return zv::Val();
			zval attributes;
			ZVAL_EMPTY_ARRAY(&attributes);
			pt_write_slot(Z_OBJ(object), OBJ_PROP_TO_NUM(slots.attributes), &attributes);
			pt_write_slot(Z_OBJ(object), OBJ_PROP_TO_NUM(slots.name), name);
			return zv::Val::adopt(object);
		}
	}
	return pt_type_new(classIdx, 1, name);
}

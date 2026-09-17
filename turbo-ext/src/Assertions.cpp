/*
 * PHPStanTurbo\Assertions — native implementation of
 * PHPStan\Reflection\Assertions.
 *
 * The @phpstan-assert tags of a function or method: a final class over the
 * tag list with a private constructor, the empty instance held where the
 * twin keeps it (the private static $empty), the filters and mapTypes() over
 * the PHP AssertTag objects through one cached method site per tag method.
 * The twin's array_filter() / array_map() closures are native loops with the
 * closures' AssertTag parameter check. Native callers use the
 * pt_assertions_* entries (support.h) and the inline pt_assertions_all()
 * reader.
 */

#include "support.h"
#include "generated/Assertions.h"

namespace slots = ptdecl::Assertions::slot;
namespace sigs = ptdecl::Assertions::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "CallHandlerSupport.h"

#include "zend_smart_str.h"

zend_class_entry *pt_ce_assertions = NULL;

namespace {

/* {{{ the PHP collaborators (one site each) */

pt_method_site pt_as_get_if_site;
pt_method_site pt_as_get_type_site;
pt_method_site pt_as_with_type_site;
pt_method_site pt_as_negate_site;
pt_method_site pt_as_is_equality_site;
pt_method_site pt_as_is_negated_site;
pt_method_site pt_as_get_parameter_site;
pt_method_site pt_as_parameter_describe_site;
pt_method_site pt_as_get_assert_tags_site;

zv::Val tagCall(pt_method_site &site, zval *tag, const char *lcname, size_t len, uint32_t argc = 0, zval *argv = NULL)
{
	return pt_call_method_cached(site, Z_OBJ_P(tag), lcname, len, argc, argv);
}

/* the twin's closure names, for the parameter TypeError */
#define PT_AS_CLASS "PHPStan\\Reflection\\Assertions"
#if PHP_VERSION_ID >= 80400
#define PT_AS_CLOSURE(method, line) PT_AS_CLASS "::{closure:" PT_AS_CLASS "::" method "():" line "}"
#else
/* PHP 8.3 names a closure by its namespace alone */
#define PT_AS_CLOSURE(method, line) PT_AS_CLASS "::PHPStan\\Reflection\\{closure}"
#endif

/* the closure's `AssertTag $assert` parameter check; false = TypeError raised */
[[nodiscard]] bool requireAssertTag(zval *value, const char *closureName, const char *parameterName)
{
	zend_class_entry *assertTagCe = pt_class(PT_CLASS_ASSERT_TAG);
	if (UNEXPECTED(assertTagCe == NULL)) return false;
	if (EXPECTED(Z_TYPE_P(value) == IS_OBJECT && instanceof_function(Z_OBJCE_P(value), assertTagCe))) return true;
	zend_type_error("%s(): Argument #1 ($%s) must be of type PHPStan\\PhpDoc\\Tag\\AssertTag, %s given", closureName, parameterName, zend_zval_value_name(value));
	return false;
}

/* $tag->getIf() === $if (a literal); false = pending exception */
[[nodiscard]] bool tagIfIs(zval *tag, const char *expected, size_t expectedLen, bool &out)
{
	zv::Val condition = tagCall(pt_as_get_if_site, tag, PT_LC("getif"));
	if (UNEXPECTED(condition.isUndef())) return false;
	out = Z_TYPE_P(condition.raw()) == IS_STRING && zend_string_equals_cstr(Z_STR_P(condition.raw()), expected, expectedLen);
	return true;
}

/* $tag->isEquality() / ->isNegated(); false = pending exception */
[[nodiscard]] bool tagBool(pt_method_site &site, zval *tag, const char *lcname, size_t len, bool &out)
{
	zv::Val value = tagCall(site, tag, lcname, len);
	if (UNEXPECTED(value.isUndef())) return false;
	out = zend_is_true(value.raw());
	return true;
}

/* $array[$key] = $value keeping the source key (array_filter() / array_map()) */
void setKeyed(zv::Arr &array, const zv::ArrayEntry &entry, zv::Val value)
{
	zval v = value.take();
	if (entry.hasStringKey()) {
		zend_hash_update(array.table(), entry.stringKey(), &v);
	} else {
		zend_hash_index_update(array.table(), entry.indexKey(), &v);
	}
}

/* }}} */

/* the twin's `private static ?self $empty` slot (borrowed; resolved once per
 * activated class) */
zend_class_entry *pt_as_empty_ce = nullptr;
zval *pt_as_empty_slot = nullptr;

zval *emptySlot()
{
	zend_class_entry *ce = pt_ce_assertions;
	if (UNEXPECTED(pt_as_empty_ce != ce)) {
		ZEND_ASSERT(ce != NULL);
		if (CE_STATIC_MEMBERS(ce) == NULL) {
			zend_class_init_statics(ce);
		}
		zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&ce->properties_info, ZEND_STRL("empty"));
		ZEND_ASSERT(info != NULL && (info->flags & ZEND_ACC_STATIC) != 0);
		pt_as_empty_slot = CE_STATIC_MEMBERS(ce) + info->offset;
		pt_as_empty_ce = ce;
	}
	zval *slot = pt_as_empty_slot;
	ZVAL_DEREF(slot);
	return slot;
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Reflection\Assertions; UNDEF = pending exception. */
class Assertions
{
public:
	explicit Assertions(zend_object *self) : self(self) {}

	void construct(zval *asserts) const
	{
		pt_write_slot(self, slots::asserts, asserts);
	}

	zv::Val getAll() const
	{
		zval *asserts = slot();
		return asserts != NULL ? zv::Val::copyOf(zv::Ref(asserts)) : zv::Val();
	}

	/* array_filter($this->asserts, static fn (AssertTag $assert) =>
	 * $assert->getIf() === AssertTag::NULL) */
	zv::Val getAsserts() const
	{
		zval *asserts = slot();
		if (UNEXPECTED(asserts == NULL)) return zv::Val();
		return filterByIf(asserts, "", 0, false, PT_AS_CLOSURE("getAsserts", "58"));
	}

	/* array_merge(the IF_TRUE tags, the negated IF_FALSE non-equality tags) */
	zv::Val getAssertsIfTrue() const
	{
		zval *asserts = slot();
		if (UNEXPECTED(asserts == NULL)) return zv::Val();
		return mergedWithNegated(asserts, PT_LC("true"), PT_AS_CLOSURE("getAssertsIfTrue", "69"), PT_LC("false"), PT_AS_CLOSURE("getAssertsIfTrue", "72"), PT_AS_CLOSURE("getAssertsIfTrue", "71"));
	}

	/* array_merge(the IF_FALSE tags, the negated IF_TRUE non-equality tags) */
	zv::Val getAssertsIfFalse() const
	{
		zval *asserts = slot();
		if (UNEXPECTED(asserts == NULL)) return zv::Val();
		return mergedWithNegated(asserts, PT_LC("false"), PT_AS_CLOSURE("getAssertsIfFalse", "85"), PT_LC("true"), PT_AS_CLOSURE("getAssertsIfFalse", "88"), PT_AS_CLOSURE("getAssertsIfFalse", "87"));
	}

	/* self::create(array_map(static fn (AssertTag $tag): AssertTag =>
	 * $tag->withType($callable($tag->getType())), $this->asserts)) */
	zv::Val mapTypes(zval *callable) const
	{
		zval *asserts = slot();
		if (UNEXPECTED(asserts == NULL)) return zv::Val();
		HashTable *table = Z_ARRVAL_P(asserts);
		zv::Arr mapped = zv::Arr::create(zend_hash_num_elements(table));
		for (zv::ArrayEntry entry : zv::TableRef(table)) {
			zval *tag = entry.value().deref().raw();
			if (UNEXPECTED(!requireAssertTag(tag, PT_AS_CLOSURE("mapTypes", "96"), "tag"))) return zv::Val();
			zv::Val type = tagCall(pt_as_get_type_site, tag, PT_LC("gettype"));
			if (UNEXPECTED(type.isUndef())) return zv::Val();
			zv::Val mappedType = pt_type_call_callable(callable, 1, type.raw());
			if (UNEXPECTED(mappedType.isUndef())) return zv::Val();
			zv::Val withType = tagCall(pt_as_with_type_site, tag, PT_LC("withtype"), 1, mappedType.raw());
			if (UNEXPECTED(withType.isUndef())) return zv::Val();
			setKeyed(mapped, entry, std::move(withType));
		}
		zv::Val mappedValue(std::move(mapped));
		return create(mappedValue.raw());
	}

	/* $this === self::$empty ? $other : ($other === self::$empty ? $this :
	 * self::create(array_merge($this->getAll(), $other->getAll()))) */
	zv::Val union_(zend_object *other) const
	{
		zval *empty = emptySlot();
		if (Z_TYPE_P(empty) == IS_OBJECT && Z_OBJ_P(empty) == self) return objectValue(other);
		if (Z_TYPE_P(empty) == IS_OBJECT && Z_OBJ_P(empty) == other) return objectValue(self);
		zv::Val thisAll = getAll();
		if (UNEXPECTED(thisAll.isUndef())) return zv::Val();
		zv::Val otherAll = Assertions(other).getAll();
		if (UNEXPECTED(otherAll.isUndef())) return zv::Val();
		zv::Val merged = ptcall::arrayMerge(thisAll.raw(), otherAll.raw());
		return create(merged.raw());
	}

	/* Mirrors intersect(). */
	zv::Val intersect(zend_object *other) const
	{
		zval *empty = emptySlot();
		if (Z_TYPE_P(empty) == IS_OBJECT && Z_OBJ_P(empty) == self) return objectValue(other);
		if (Z_TYPE_P(empty) == IS_OBJECT && Z_OBJ_P(empty) == other) return objectValue(self);
		zv::Val otherAsserts = Assertions(other).getAll();
		if (UNEXPECTED(otherAsserts.isUndef())) return zv::Val();
		zv::Val thisAsserts = getAll();
		if (UNEXPECTED(thisAsserts.isUndef())) return zv::Val();

		zv::Arr merged = zv::Arr::create(0);
		for (zv::ArrayEntry thisEntry : zv::ArrRef(thisAsserts.raw())) {
			zval *thisAssert = thisEntry.value().deref().raw();
			zv::Val key = getAssertKey(thisAssert);
			if (UNEXPECTED(key.isUndef())) return zv::Val();
			for (zv::ArrayEntry otherEntry : zv::ArrRef(otherAsserts.raw())) {
				zval *otherAssert = otherEntry.value().deref().raw();
				zv::Val otherKey = getAssertKey(otherAssert);
				if (UNEXPECTED(otherKey.isUndef())) return zv::Val();
				if (!zend_string_equals(Z_STR_P(otherKey.raw()), Z_STR_P(key.raw()))) continue;
				/* $thisAssert->withType(TypeCombinator::union($thisAssert->getType(), $otherAssert->getType())) */
				if (UNEXPECTED(Z_TYPE_P(thisAssert) != IS_OBJECT)) {
					zend_throw_error(NULL, "Call to a member function withType() on %s", zend_zval_value_name(thisAssert));
					return zv::Val();
				}
				zv::Val thisType = tagCall(pt_as_get_type_site, thisAssert, PT_LC("gettype"));
				if (UNEXPECTED(thisType.isUndef())) return zv::Val();
				zv::Val otherType = tagCall(pt_as_get_type_site, otherAssert, PT_LC("gettype"));
				if (UNEXPECTED(otherType.isUndef())) return zv::Val();
				zv::Args unionArgs{thisType.raw(), otherType.raw()};
				zv::Val unionType = pt_type_combinator_call(PT_LC("union"), 2, unionArgs);
				if (UNEXPECTED(unionType.isUndef())) return zv::Val();
				zv::Val withType = tagCall(pt_as_with_type_site, thisAssert, PT_LC("withtype"), 1, unionType.raw());
				if (UNEXPECTED(withType.isUndef())) return zv::Val();
				merged.push(std::move(withType));
			}
		}
		zv::Val mergedValue(std::move(merged));
		return create(mergedValue.raw());
	}

	/* sprintf('%s-%s-%s', $assert->getParameter()->describe(),
	 * $assert->getIf(), $assert->isNegated() ? '1' : '0') */
	static zv::Val getAssertKey(zval *assert)
	{
		if (UNEXPECTED(!requireAssertTagArgument(assert))) return zv::Val();
		zv::Val parameter = tagCall(pt_as_get_parameter_site, assert, PT_LC("getparameter"));
		if (UNEXPECTED(parameter.isUndef())) return zv::Val();
		zv::Val description = tagCall(pt_as_parameter_describe_site, parameter.raw(), PT_LC("describe"));
		if (UNEXPECTED(description.isUndef())) return zv::Val();
		zv::Val condition = tagCall(pt_as_get_if_site, assert, PT_LC("getif"));
		if (UNEXPECTED(condition.isUndef())) return zv::Val();
		bool negated;
		if (UNEXPECTED(!tagBool(pt_as_is_negated_site, assert, PT_LC("isnegated"), negated))) return zv::Val();
		smart_str key = {NULL, 0};
		zend_string *descriptionString = zval_get_string(description.raw());
		zend_string *conditionString = zval_get_string(condition.raw());
		smart_str_append(&key, descriptionString);
		smart_str_appendc(&key, '-');
		smart_str_append(&key, conditionString);
		smart_str_appendc(&key, '-');
		smart_str_appendc(&key, negated ? '1' : '0');
		zend_string_release(descriptionString);
		zend_string_release(conditionString);
		zval value;
		ZVAL_STR(&value, smart_str_extract(&key));
		return zv::Val::adopt(value);
	}

	/* count($asserts) === 0 ? self::createEmpty() : new self($asserts) */
	static zv::Val create(zval *asserts)
	{
		if (zend_hash_num_elements(Z_ARRVAL_P(asserts)) == 0) return createEmpty();
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_assertions) != SUCCESS)) return zv::Val();
		Assertions(Z_OBJ(object)).construct(asserts);
		return zv::Val::adopt(object);
	}

	/* self::$empty ??= new self([]) */
	static zv::Val createEmpty()
	{
		zval *empty = emptySlot();
		if (EXPECTED(Z_TYPE_P(empty) == IS_OBJECT)) return zv::Val::copyOf(zv::Ref(empty));
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_assertions) != SUCCESS)) return zv::Val();
		zval asserts;
		ZVAL_EMPTY_ARRAY(&asserts);
		Assertions(Z_OBJ(object)).construct(&asserts);
		zval previous;
		ZVAL_COPY_VALUE(&previous, empty);
		ZVAL_COPY(empty, &object);
		zval_ptr_dtor(&previous);
		return zv::Val::adopt(object);
	}

	/* self::create($phpDocBlock->getAssertTags()) */
	static zv::Val createFromResolvedPhpDocBlock(zval *phpDocBlock)
	{
		zv::Val tags = pt_call_method_cached(pt_as_get_assert_tags_site, Z_OBJ_P(phpDocBlock), PT_LC("getasserttags"), 0, NULL);
		if (UNEXPECTED(tags.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(tags.raw()) != IS_ARRAY)) {
			zend_type_error(PT_AS_CLASS "::create(): Argument #1 ($asserts) must be of type array, %s given", zend_zval_value_name(tags.raw()));
			return zv::Val();
		}
		return create(tags.raw());
	}

private:
	zend_object *self;

	zval *slot() const
	{
		return pt_typed_slot(self, slots::asserts, pt_ce_assertions, "asserts");
	}

	static zv::Val objectValue(zend_object *object)
	{
		zval value;
		ZVAL_OBJ_COPY(&value, object);
		return zv::Val::adopt(value);
	}

	/* getAssertKey()'s `AssertTag $assert` parameter */
	static bool requireAssertTagArgument(zval *assert)
	{
		zend_class_entry *assertTagCe = pt_class(PT_CLASS_ASSERT_TAG);
		if (UNEXPECTED(assertTagCe == NULL)) return false;
		if (EXPECTED(Z_TYPE_P(assert) == IS_OBJECT && instanceof_function(Z_OBJCE_P(assert), assertTagCe))) return true;
		zend_type_error(PT_AS_CLASS "::getAssertKey(): Argument #1 ($assert) must be of type PHPStan\\PhpDoc\\Tag\\AssertTag, %s given", zend_zval_value_name(assert));
		return false;
	}

	/* array_filter($asserts, static fn (AssertTag $assert) => $assert->getIf()
	 * === $if [&& !$assert->isEquality()]) — the keys kept */
	static zv::Val filterByIf(zval *asserts, const char *condition, size_t conditionLen, bool nonEquality, const char *closureName)
	{
		HashTable *table = Z_ARRVAL_P(asserts);
		zv::Arr filtered = zv::Arr::create(0);
		for (zv::ArrayEntry entry : zv::TableRef(table)) {
			zval *tag = entry.value().deref().raw();
			if (UNEXPECTED(!requireAssertTag(tag, closureName, "assert"))) return zv::Val();
			bool matches;
			if (UNEXPECTED(!tagIfIs(tag, condition, conditionLen, matches))) return zv::Val();
			if (matches && nonEquality) {
				bool equality;
				if (UNEXPECTED(!tagBool(pt_as_is_equality_site, tag, PT_LC("isequality"), equality))) return zv::Val();
				matches = !equality;
			}
			if (matches) setKeyed(filtered, entry, zv::Val::copyOf(entry.value()));
		}
		return zv::Val(std::move(filtered));
	}

	/* array_merge(array_filter($asserts, fn getIf() === $own),
	 * array_map(static fn (AssertTag $assert) => $assert->negate(),
	 * array_filter($asserts, fn getIf() === $opposite && !isEquality())) */
	static zv::Val mergedWithNegated(zval *asserts, const char *own, size_t ownLen, const char *ownClosure, const char *opposite, size_t oppositeLen, const char *oppositeClosure, const char *negateClosure)
	{
		zv::Val ownTags = filterByIf(asserts, own, ownLen, false, ownClosure);
		if (UNEXPECTED(ownTags.isUndef())) return zv::Val();
		zv::Val oppositeTags = filterByIf(asserts, opposite, oppositeLen, true, oppositeClosure);
		if (UNEXPECTED(oppositeTags.isUndef())) return zv::Val();
		zv::Arr negated = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(oppositeTags.raw())));
		for (zv::ArrayEntry entry : zv::ArrRef(oppositeTags.raw())) {
			zval *tag = entry.value().deref().raw();
			if (UNEXPECTED(!requireAssertTag(tag, negateClosure, "assert"))) return zv::Val();
			zv::Val negatedTag = tagCall(pt_as_negate_site, tag, PT_LC("negate"));
			if (UNEXPECTED(negatedTag.isUndef())) return zv::Val();
			setKeyed(negated, entry, std::move(negatedTag));
		}
		zv::Val negatedValue(std::move(negated));
		return ptcall::arrayMerge(ownTags.raw(), negatedValue.raw());
	}
};

} // namespace phpstanturbo

using phpstanturbo::Assertions;

/* {{{ direct entries (support.h) */

namespace {

/* The class under the twin's real name: the native class in production;
 * under the prefixed activation of the differential tests (the native class
 * is PHPStanTurbo\Assertions) the PHP twin, which the PHP collaborators the
 * native callers hand the result to (CallableAssertionsHelper, the method
 * reflections) declare as their parameter type. Decided once per activated
 * class entry; NULL = the native class. */
zend_class_entry *realNameClass()
{
	static zend_class_entry *decidedFor = NULL;
	static bool realName = true;
	if (EXPECTED(decidedFor == pt_ce_assertions)) {
		if (EXPECTED(realName)) return NULL;
	} else {
		decidedFor = pt_ce_assertions;
		realName = pt_ce_assertions == NULL || zend_string_equals_literal(pt_ce_assertions->name, PT_AS_CLASS);
		if (realName) return NULL;
	}
	zend_string *name = zend_string_init(ZEND_STRL(PT_AS_CLASS), 0);
	zend_class_entry *twin = zend_lookup_class(name);
	zend_string_release(name);
	if (UNEXPECTED(twin == NULL) && !EG(exception)) {
		zend_throw_error(NULL, "Class \"%s\" not found", PT_AS_CLASS);
	}
	return twin;
}

} // namespace

zv::Val pt_assertions_create_empty()
{
	zend_class_entry *twin = realNameClass();
	if (EXPECTED(twin == NULL)) {
		if (UNEXPECTED(EG(exception))) return zv::Val();
		return Assertions::createEmpty();
	}
	return pt_type_call_static_ce(twin, PT_LC("createempty"), 0, NULL);
}

zv::Val pt_assertions_create_from_resolved_php_doc_block(zval *phpDocBlock)
{
	zend_class_entry *twin = realNameClass();
	if (UNEXPECTED(twin != NULL)) return pt_type_call_static_ce(twin, PT_LC("createfromresolvedphpdocblock"), 1, phpDocBlock);
	if (UNEXPECTED(EG(exception))) return zv::Val();
	if (UNEXPECTED(Z_TYPE_P(phpDocBlock) != IS_OBJECT)) {
		zend_type_error(PT_AS_CLASS "::createFromResolvedPhpDocBlock(): Argument #1 ($phpDocBlock) must be of type PHPStan\\PhpDoc\\ResolvedPhpDocBlock, %s given", zend_zval_value_name(phpDocBlock));
		return zv::Val();
	}
	return Assertions::createFromResolvedPhpDocBlock(phpDocBlock);
}

zv::Val pt_assertions_map_types(zval *assertions, zval *callable)
{
	if (EXPECTED(Z_TYPE_P(assertions) == IS_OBJECT && Z_OBJCE_P(assertions) == pt_ce_assertions)) return Assertions(Z_OBJ_P(assertions)).mapTypes(callable);
	if (UNEXPECTED(Z_TYPE_P(assertions) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function mapTypes() on %s", zend_zval_value_name(assertions));
		return zv::Val();
	}
	return pt_type_call(Z_OBJ_P(assertions), PT_LC("maptypes"), 1, callable);
}

zv::Val pt_assertions_get_asserts(zval *assertions, int which)
{
	if (EXPECTED(Z_TYPE_P(assertions) == IS_OBJECT && Z_OBJCE_P(assertions) == pt_ce_assertions)) {
		Assertions native(Z_OBJ_P(assertions));
		switch (which) {
			case PT_ASSERTIONS_IF_TRUE: return native.getAssertsIfTrue();
			case PT_ASSERTIONS_IF_FALSE: return native.getAssertsIfFalse();
			default: return native.getAsserts();
		}
	}
	static const char *const names[3] = { "getAsserts", "getAssertsIfTrue", "getAssertsIfFalse" };
	const char *name = names[which == PT_ASSERTIONS_IF_TRUE ? 1 : (which == PT_ASSERTIONS_IF_FALSE ? 2 : 0)];
	if (UNEXPECTED(Z_TYPE_P(assertions) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", name, zend_zval_value_name(assertions));
		return zv::Val();
	}
	zend_string *lcname = zend_string_tolower(zend_string_init(name, strlen(name), 0));
	zv::Val result = pt_type_call(Z_OBJ_P(assertions), ZSTR_VAL(lcname), ZSTR_LEN(lcname), 0, NULL);
	zend_string_release(lcname);
	return result;
}

zval *pt_assertions_all(zval *assertions, zv::Val &hold)
{
	if (EXPECTED(Z_TYPE_P(assertions) == IS_OBJECT && Z_OBJCE_P(assertions) == pt_ce_assertions)) {
		zval *asserts = OBJ_PROP_NUM(Z_OBJ_P(assertions), slots::asserts);
		if (EXPECTED(Z_TYPE_P(asserts) == IS_ARRAY)) return asserts;
	}
	if (UNEXPECTED(Z_TYPE_P(assertions) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function getAll() on %s", zend_zval_value_name(assertions));
		return NULL;
	}
	hold = pt_type_call(Z_OBJ_P(assertions), PT_LC("getall"), 0, NULL);
	return hold.isUndef() ? NULL : hold.raw();
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_AS_THIS Assertions(Z_OBJ_P(ZEND_THIS))

void pt_register_assertions()
{
	reg::Class cls("PHPStan\\Reflection\\Assertions");
	ptdecl::Assertions::declareClass(cls);
	ptdecl::Assertions::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *asserts;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_ARRAY(asserts)
		ZEND_PARSE_PARAMETERS_END();
		PT_AS_THIS.construct(asserts);
	});

	cls.method<&Assertions::getAll>(sigs::getAll);
	cls.method<&Assertions::getAsserts>(sigs::getAsserts);
	cls.method<&Assertions::getAssertsIfTrue>(sigs::getAssertsIfTrue);
	cls.method<&Assertions::getAssertsIfFalse>(sigs::getAssertsIfFalse);

	cls.method(sigs::mapTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *callable;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_ZVAL(callable)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!zend_is_callable(callable, 0, NULL))) {
			zend_argument_type_error(1, "must be of type callable, %s given", zend_zval_value_name(callable));
			RETURN_THROWS();
		}
		PT_RETURN_VAL(PT_AS_THIS.mapTypes(callable));
	});

	cls.method(sigs::intersectWith, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *other;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(other, pt_ce_assertions)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_AS_THIS.union_(Z_OBJ_P(other)));
	});

	cls.method(sigs::union_, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *other;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(other, pt_ce_assertions)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_AS_THIS.union_(Z_OBJ_P(other)));
	});

	cls.method(sigs::intersect, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *other;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(other, pt_ce_assertions)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_AS_THIS.intersect(Z_OBJ_P(other)));
	});

	cls.method(sigs::getAssertKey, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *assert;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(assert, pt_class(PT_CLASS_ASSERT_TAG))
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(Assertions::getAssertKey(assert));
	});

	cls.method(sigs::create, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *asserts;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_ARRAY(asserts)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(Assertions::create(asserts));
	});

	cls.method(sigs::createEmpty, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(Assertions::createEmpty());
	});

	cls.method(sigs::createFromResolvedPhpDocBlock, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *phpDocBlock;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(phpDocBlock, pt_class(PT_CLASS_RESOLVED_PHP_DOC_BLOCK))
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(Assertions::createFromResolvedPhpDocBlock(phpDocBlock));
	});

	cls.method(sigs::createFromAssertTags, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *assertTags;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_ARRAY(assertTags)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(Assertions::create(assertTags));
	});

	cls.shadow(&pt_ce_assertions);
}

/* }}} */

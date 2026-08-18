/*
 * PHPStanTurbo\FiniteTypeSet — native implementation of PHPStan\Type\FiniteTypeSet.
 *
 * Not final: a PHP stub subclass extends this class, and create() instantiates
 * the configured finiteTypeSet class so userland type hints keep working.
 *
 * The port is about create(): every union that holds a finite value builds one
 * of these, and the PHP twin pays key() + getConstantScalarValues() + kind() +
 * get_class() per member — four userland frames each, on unions that routinely
 * hold dozens of constants. The five classes that *are* a finite value are
 * recognised here by their exact class entry, so the whole loop runs without
 * crossing back into PHP; every other class takes the same
 * getEnumCaseObject()/getConstantScalarValues() path the twin takes.
 *
 * The logic lives in the FiniteTypeSet handle class below, structured to
 * mirror src/Type/FiniteTypeSet.php method for method; the lambdas at the
 * bottom are only the engine ABI glue (parameter parsing + delegation).
 */

#include "support.h"
#include "zv.h"

#include <cstring>

namespace phpstanturbo {

/* the twin's private key constants */
#define PT_FTS_NULL_KEY "null"
#define PT_FTS_INTEGER_KEY_PREFIX "i:"
#define PT_FTS_BOOLEAN_KEY_PREFIX "b:"
#define PT_FTS_STRING_KEY_PREFIX "s:"
#define PT_FTS_ENUM_CASE_KEY_PREFIX "enum:"

#define PT_FTS_LITERAL(s) s, sizeof(s) - 1

/* a . b . c . d in one allocation; trailing parts may be empty */
static zend_string *ptFtsConcat(const char *a, size_t al, const char *b, size_t bl, const char *c, size_t cl, const char *d, size_t dl)
{
	zend_string *out = zend_string_alloc(al + bl + cl + dl, 0);
	char *p = ZSTR_VAL(out);
	memcpy(p, a, al);
	p += al;
	memcpy(p, b, bl);
	p += bl;
	memcpy(p, c, cl);
	p += cl;
	memcpy(p, d, dl);
	ZSTR_VAL(out)[al + bl + cl + dl] = '\0';
	return out;
}

/* offset of an instance property declared anywhere up the hierarchy — a
 * private property of a parent class is not in the child's own table */
static int32_t ptFtsPropOffset(zend_class_entry *ce, const char *name, size_t len)
{
	for (zend_class_entry *cur = ce; cur != NULL; cur = cur->parent) {
		int32_t offset = pt_instance_prop_offset(cur, name, len);
		if (offset >= 0) {
			return offset;
		}
	}
	return -1;
}

/*
 * The class entries and property slots key()/kind() need, resolved once per
 * request rather than once per member.
 *
 * The five value classes are matched by their exact entry, deliberately not
 * with instanceof: TemplateConstantStringType extends ConstantStringType and
 * must key as null, and any subclass may override getConstantScalarValues()
 * or getEnumCaseObject(). A subclass therefore falls through to the userland
 * path, which answers for it exactly like the twin does. The three excluded
 * classes are matched with instanceof, because the twin excludes them with
 * instanceof.
 */
struct FiniteClasses
{
	zend_class_entry *nullType;
	zend_class_entry *constantInteger;
	zend_class_entry *constantString;
	zend_class_entry *constantBoolean;
	zend_class_entry *enumCase;
	zend_class_entry *templateType;
	zend_class_entry *unionType;
	zend_class_entry *intersectionType;
	int32_t constantIntegerValue;
	int32_t constantStringValue;
	int32_t constantBooleanValue;
	int32_t enumCaseName;
	int32_t enumClassName;
	bool inited;
};

static FiniteClasses ptFtsClasses;

/* false = pending exception */
static bool ptFtsResolveClasses()
{
	if (EXPECTED(ptFtsClasses.inited)) {
		return true;
	}

	FiniteClasses resolved;
	resolved.nullType = pt_class(PT_CLASS_NULL_TYPE);
	resolved.constantInteger = pt_class(PT_CLASS_CONSTANT_INTEGER_TYPE);
	resolved.constantString = pt_class(PT_CLASS_CONSTANT_STRING_TYPE);
	resolved.constantBoolean = pt_class(PT_CLASS_CONSTANT_BOOLEAN_TYPE);
	resolved.enumCase = pt_class(PT_CLASS_ENUM_CASE_OBJECT_TYPE);
	resolved.templateType = pt_class(PT_CLASS_TEMPLATE_TYPE);
	resolved.unionType = pt_class(PT_CLASS_UNION_TYPE);
	resolved.intersectionType = pt_class(PT_CLASS_INTERSECTION_TYPE);
	if (UNEXPECTED(resolved.nullType == NULL || resolved.constantInteger == NULL || resolved.constantString == NULL
		|| resolved.constantBoolean == NULL || resolved.enumCase == NULL || resolved.templateType == NULL
		|| resolved.unionType == NULL || resolved.intersectionType == NULL)) {
		return false;
	}

	resolved.constantIntegerValue = ptFtsPropOffset(resolved.constantInteger, PT_FTS_LITERAL("value"));
	resolved.constantStringValue = ptFtsPropOffset(resolved.constantString, PT_FTS_LITERAL("value"));
	resolved.constantBooleanValue = ptFtsPropOffset(resolved.constantBoolean, PT_FTS_LITERAL("value"));
	resolved.enumCaseName = ptFtsPropOffset(resolved.enumCase, PT_FTS_LITERAL("enumCaseName"));
	resolved.enumClassName = ptFtsPropOffset(resolved.enumCase, PT_FTS_LITERAL("className"));
	resolved.inited = true;

	ptFtsClasses = resolved;
	return true;
}

/* $object->method(); false = pending exception */
static bool ptFtsCall(zval *object, const char *lcname, size_t len, zval *result)
{
	zend_class_entry *ce = Z_OBJCE_P(object);
	zend_function *fn = pt_find_method(ce, lcname, len);
	if (UNEXPECTED(fn == NULL)) {
		return false;
	}
	zend_call_known_function(fn, Z_OBJ_P(object), ce, result, 0, NULL, NULL);
	return !EG(exception);
}

/* getClassName() and getEnumCaseName() of an already-known enum case object */
static zend_string *ptFtsEnumKey(const char *prefix, size_t prefixLen, zval *enumCaseObject, bool withCaseName)
{
	zval className, caseName;
	if (UNEXPECTED(!ptFtsCall(enumCaseObject, PT_FTS_LITERAL("getclassname"), &className))) {
		return NULL;
	}
	if (UNEXPECTED(Z_TYPE(className) != IS_STRING)) {
		zval_ptr_dtor(&className);
		zend_type_error("getClassName() must return a string");
		return NULL;
	}
	if (!withCaseName) {
		zend_string *out = ptFtsConcat(prefix, prefixLen, ZSTR_VAL(Z_STR(className)), Z_STRLEN(className), "", 0, "", 0);
		zval_ptr_dtor(&className);
		return out;
	}
	if (UNEXPECTED(!ptFtsCall(enumCaseObject, PT_FTS_LITERAL("getenumcasename"), &caseName))) {
		zval_ptr_dtor(&className);
		return NULL;
	}
	if (UNEXPECTED(Z_TYPE(caseName) != IS_STRING)) {
		zval_ptr_dtor(&className);
		zval_ptr_dtor(&caseName);
		zend_type_error("getEnumCaseName() must return a string");
		return NULL;
	}
	zend_string *out = ptFtsConcat(
		prefix, prefixLen,
		ZSTR_VAL(Z_STR(className)), Z_STRLEN(className),
		PT_FTS_LITERAL("::"),
		ZSTR_VAL(Z_STR(caseName)), Z_STRLEN(caseName));
	zval_ptr_dtor(&className);
	zval_ptr_dtor(&caseName);
	return out;
}

/* Mirrors PHPStan\Type\FiniteTypeSet. State lives in the PHP object's
 * hasClassStringMember/members/membersByKind/others properties. */
class FiniteTypeSet
{
public:
	explicit FiniteTypeSet(zend_object *self) : self(self) {}

	void construct(zv::Ref members, zv::Ref membersByKind, zv::Ref others)
	{
		zv::ObjRef obj(self);
		obj.propAtWrite(PT_FTS_PROP_MEMBERS, zv::Val::copyOf(members));
		obj.propAtWrite(PT_FTS_PROP_MEMBERS_BY_KIND, zv::Val::copyOf(membersByKind));
		obj.propAtWrite(PT_FTS_PROP_OTHERS, zv::Val::copyOf(others));
	}

	/* null when nothing keyed; UNDEF = pending exception */
	static zv::Val create(zv::ArrRef types)
	{
		if (UNEXPECTED(!ptFtsResolveClasses())) {
			return zv::Val();
		}

		/* the tables stay unallocated until the first keyed member: a union
		 * with no finite value at all — the common case — must not pay for
		 * three empty hashtables to answer null */
		zv::Arr members;
		zv::Arr membersByKind;
		zv::Arr others = zv::Arr::empty();

		for (auto entry : types) {
			zv::Ref type = entry.value().deref();
			if (UNEXPECTED(!type.isObject())) {
				zend_type_error("%s::create(): Argument #1 ($types) must be a list of objects", ZSTR_VAL(pt_ce_finite_type_set->name));
				return zv::Val();
			}

			zv::Str key;
			zv::Str kind;
			if (UNEXPECTED(!classify(type.raw(), KEY_AND_KIND, key, kind))) {
				return zv::Val();
			}

			if (key.isNull() || (!members.isUndef() && zend_symtable_exists(members.table(), key.get()))) {
				others.push(type);
				continue;
			}

			if (members.isUndef()) {
				members = zv::Arr::create(types.size());
				membersByKind = zv::Arr::create(KINDS_SIZE_HINT);
			}
			members.set(key.get(), zv::Val::copyOf(type));
			if (!zend_symtable_exists(membersByKind.table(), kind.get())) {
				membersByKind.set(kind.get(), zv::Val::copyOf(type));
			}
		}

		if (members.isUndef()) {
			return zv::Val::null();
		}

		zend_class_entry *ce = pt_impl_class(PT_CLASS_FINITE_TYPE_SET, pt_ce_finite_type_set);
		if (UNEXPECTED(ce == NULL)) {
			return zv::Val();
		}
		zval set;
		if (UNEXPECTED(object_init_ex(&set, ce) != SUCCESS)) {
			return zv::Val();
		}
		zv::ObjRef obj(Z_OBJ(set));
		obj.propAtWrite(PT_FTS_PROP_MEMBERS, zv::Val(std::move(members)));
		obj.propAtWrite(PT_FTS_PROP_MEMBERS_BY_KIND, zv::Val(std::move(membersByKind)));
		obj.propAtWrite(PT_FTS_PROP_OTHERS, zv::Val(std::move(others)));
		return zv::Val::adopt(set);
	}

	/* null string = not a finite value; false = pending exception */
	static bool key(zval *type, zv::Str &out)
	{
		if (UNEXPECTED(!ptFtsResolveClasses())) {
			return false;
		}
		zv::Str kind;
		return classify(type, KEY_ONLY, out, kind);
	}

	/* the twin's private kind(); false = pending exception */
	static bool kind(zval *type, zv::Str &out)
	{
		if (UNEXPECTED(!ptFtsResolveClasses())) {
			return false;
		}
		zv::Str key;
		return classify(type, KIND_ONLY, key, out);
	}

	/* UNDEF = pending exception */
	zv::Val getRepresentativesOfOtherKinds(zval *type) const
	{
		zv::Str wanted;
		if (UNEXPECTED(!kind(type, wanted))) {
			return zv::Val();
		}

		zv::Ref membersByKind = zv::ObjRef(self).propAt(PT_FTS_PROP_MEMBERS_BY_KIND);
		zv::Arr representatives = zv::Arr::empty();
		for (auto entry : zv::ArrRef(membersByKind.raw())) {
			zend_string *memberKind = entry.stringKeyOrNull();
			if (memberKind != NULL && zend_string_equals(memberKind, wanted.get())) {
				continue;
			}
			representatives.push(entry.value());
		}
		return zv::Val(std::move(representatives));
	}

	bool has(zend_string *key) const
	{
		return zv::ArrRef(zv::ObjRef(self).propAt(PT_FTS_PROP_MEMBERS).raw()).exists(key);
	}

	bool isComplete() const
	{
		return zv::ArrRef(zv::ObjRef(self).propAt(PT_FTS_PROP_OTHERS).raw()).size() == 0;
	}

	zv::Val getMembers() const { return zv::Val::copyOf(zv::ObjRef(self).propAt(PT_FTS_PROP_MEMBERS)); }

	zv::Val getOthers() const { return zv::Val::copyOf(zv::ObjRef(self).propAt(PT_FTS_PROP_OTHERS)); }

	/* the twin's array_diff_key() count, as one hash join that stops as soon
	 * as both a missing and a shared key have been seen */
	zv::Val containedIn(zend_object *other) const
	{
		HashTable *mine = Z_ARRVAL_P(zv::ObjRef(self).propAt(PT_FTS_PROP_MEMBERS).raw());
		HashTable *theirs = Z_ARRVAL_P(zv::ObjRef(other).propAt(PT_FTS_PROP_MEMBERS).raw());

		bool missing = false;
		bool shared = false;
		for (auto entry : zv::TableRef(mine)) {
			if (pt_ht_exists(theirs, entry.stringKeyOrNull(), entry.indexKey())) {
				shared = true;
			} else {
				missing = true;
			}
			if (missing && shared) {
				return trinary(PT_TRI_MAYBE);
			}
		}

		return trinary(missing ? PT_TRI_NO : PT_TRI_YES);
	}

	zv::Val containedInKey(zend_string *key) const
	{
		if (!has(key)) {
			return trinary(PT_TRI_NO);
		}
		if (zv::ArrRef(zv::ObjRef(self).propAt(PT_FTS_PROP_MEMBERS).raw()).size() == 1) {
			return trinary(PT_TRI_YES);
		}
		return trinary(PT_TRI_MAYBE);
	}

	/* false = pending exception */
	bool hasClassStringMember(bool &out) const
	{
		zv::ObjRef obj(self);
		zv::Ref memo = obj.propAt(PT_FTS_PROP_HAS_CLASS_STRING_MEMBER);
		if (memo.isBool()) {
			out = memo.isTrue();
			return true;
		}
		if (UNEXPECTED(!ptFtsResolveClasses())) {
			return false;
		}

		out = false;
		for (auto entry : zv::ArrRef(obj.propAt(PT_FTS_PROP_MEMBERS).raw())) {
			bool no;
			if (UNEXPECTED(!isClassStringNo(entry.value().deref().raw(), no))) {
				return false;
			}
			if (no) {
				continue;
			}
			out = true;
			break;
		}

		obj.propAtWrite(PT_FTS_PROP_HAS_CLASS_STRING_MEMBER, zv::Val::boolean(out));
		return true;
	}

private:
	/* KEY_AND_KIND fills the kind only for a keyed type — the only member of
	 * membersByKind create() can ever want */
	enum ClassifyMode
	{
		KEY_ONLY,
		KEY_AND_KIND,
		KIND_ONLY,
	};

	/* a union of finite values is overwhelmingly of one kind */
	static const uint32_t KINDS_SIZE_HINT = 4;

	zend_object *self;

	static zv::Val trinary(zend_long value) { return zv::Val::copyOf(zv::Ref(pt_trinary_singleton(value))); }

	/*
	 * key() and kind() in one pass: create() needs both per member, and the
	 * userland path answers both from the single getEnumCaseObject() call the
	 * twin would otherwise make twice.
	 *
	 * false = pending exception. A null key means the type is not a finite
	 * value — create() then never looks at the kind, so KEY_AND_KIND leaves it
	 * unset in that case.
	 */
	static bool classify(zval *type, ClassifyMode mode, zv::Str &key, zv::Str &kind)
	{
		const FiniteClasses &classes = ptFtsClasses;
		zend_class_entry *ce = Z_OBJCE_P(type);
		bool wantKey = mode != KIND_ONLY;
		bool wantKind = mode != KEY_ONLY;

		if (ce == classes.constantString) {
			if (wantKey) {
				zend_string *value = Z_STR_P(OBJ_PROP(Z_OBJ_P(type), (uint32_t) classes.constantStringValue));
				key = zv::Str::adopt(ptFtsConcat(PT_FTS_LITERAL(PT_FTS_STRING_KEY_PREFIX), ZSTR_VAL(value), ZSTR_LEN(value), "", 0, "", 0));
			}
			if (wantKind) {
				kind = zv::Str::copyOf(ce->name);
			}
			return true;
		}
		if (ce == classes.constantInteger) {
			if (wantKey) {
				char buf[MAX_LENGTH_OF_LONG + 1];
				char *digits = zend_print_long_to_buf(buf + sizeof(buf) - 1, Z_LVAL_P(OBJ_PROP(Z_OBJ_P(type), (uint32_t) classes.constantIntegerValue)));
				key = zv::Str::adopt(ptFtsConcat(PT_FTS_LITERAL(PT_FTS_INTEGER_KEY_PREFIX), digits, (size_t) (buf + sizeof(buf) - 1 - digits), "", 0, "", 0));
			}
			if (wantKind) {
				kind = zv::Str::copyOf(ce->name);
			}
			return true;
		}
		if (ce == classes.enumCase) {
			zend_string *className = Z_STR_P(OBJ_PROP(Z_OBJ_P(type), (uint32_t) classes.enumClassName));
			if (wantKey) {
				zend_string *caseName = Z_STR_P(OBJ_PROP(Z_OBJ_P(type), (uint32_t) classes.enumCaseName));
				key = zv::Str::adopt(ptFtsConcat(
					PT_FTS_LITERAL(PT_FTS_ENUM_CASE_KEY_PREFIX),
					ZSTR_VAL(className), ZSTR_LEN(className),
					PT_FTS_LITERAL("::"),
					ZSTR_VAL(caseName), ZSTR_LEN(caseName)));
			}
			if (wantKind) {
				kind = zv::Str::adopt(ptFtsConcat(PT_FTS_LITERAL(PT_FTS_ENUM_CASE_KEY_PREFIX), ZSTR_VAL(className), ZSTR_LEN(className), "", 0, "", 0));
			}
			return true;
		}
		if (ce == classes.nullType) {
			if (wantKey) {
				key = zv::Str::adopt(zend_string_init(PT_FTS_LITERAL(PT_FTS_NULL_KEY), 0));
			}
			if (wantKind) {
				kind = zv::Str::copyOf(ce->name);
			}
			return true;
		}
		if (ce == classes.constantBoolean) {
			if (wantKey) {
				bool value = zv::Ref(OBJ_PROP(Z_OBJ_P(type), (uint32_t) classes.constantBooleanValue)).isTrue();
				key = zv::Str::adopt(zend_string_init(value ? PT_FTS_BOOLEAN_KEY_PREFIX "1" : PT_FTS_BOOLEAN_KEY_PREFIX "0", sizeof(PT_FTS_BOOLEAN_KEY_PREFIX "1") - 1, 0));
			}
			if (wantKind) {
				kind = zv::Str::copyOf(ce->name);
			}
			return true;
		}

		return classifySlow(type, ce, mode, key, kind);
	}

	/* the twin's own path, for every class that is not one of the five */
	static bool classifySlow(zval *type, zend_class_entry *ce, ClassifyMode mode, zv::Str &key, zv::Str &kind)
	{
		const FiniteClasses &classes = ptFtsClasses;

		/* kind() keeps get_class() for a union or an intersection, key()
		 * refuses both outright — one instanceof pair answers for both */
		if (instanceof_function(ce, classes.unionType) || instanceof_function(ce, classes.intersectionType)) {
			if (mode != KEY_ONLY) {
				kind = zv::Str::copyOf(ce->name);
			}
			return true;
		}
		/* a template type has no key, and create() asks for its kind only for
		 * keyed members — so there is nothing left to compute */
		if (mode != KIND_ONLY && instanceof_function(ce, classes.templateType)) {
			return true;
		}

		zval enumCaseObject;
		if (UNEXPECTED(!ptFtsCall(type, PT_FTS_LITERAL("getenumcaseobject"), &enumCaseObject))) {
			return false;
		}
		if (Z_TYPE(enumCaseObject) == IS_OBJECT) {
			bool ok = true;
			if (mode != KIND_ONLY) {
				key = zv::Str::adopt(ptFtsEnumKey(PT_FTS_LITERAL(PT_FTS_ENUM_CASE_KEY_PREFIX), &enumCaseObject, true));
				ok = !key.isNull();
			}
			if (ok && mode != KEY_ONLY) {
				kind = zv::Str::adopt(ptFtsEnumKey(PT_FTS_LITERAL(PT_FTS_ENUM_CASE_KEY_PREFIX), &enumCaseObject, false));
				ok = !kind.isNull();
			}
			zval_ptr_dtor(&enumCaseObject);
			return ok;
		}
		zval_ptr_dtor(&enumCaseObject);

		if (mode != KEY_ONLY) {
			kind = zv::Str::copyOf(ce->name);
		}
		if (mode == KIND_ONLY) {
			return true;
		}

		zval scalarValues;
		if (UNEXPECTED(!ptFtsCall(type, PT_FTS_LITERAL("getconstantscalarvalues"), &scalarValues))) {
			return false;
		}
		if (Z_TYPE(scalarValues) != IS_ARRAY || zend_hash_num_elements(Z_ARRVAL(scalarValues)) != 1) {
			zval_ptr_dtor(&scalarValues);
			return true;
		}
		zval *value = zend_hash_index_find(Z_ARRVAL(scalarValues), 0);
		if (value != NULL) {
			ZVAL_DEREF(value);
			key = zv::Str::adopt(scalarKey(value));
		}
		zval_ptr_dtor(&scalarValues);
		return true;
	}

	/* the twin's null/int/bool/string branches over a scalar value; floats
	 * and anything else stay unkeyed */
	static zend_string *scalarKey(zval *value)
	{
		switch (Z_TYPE_P(value)) {
			case IS_NULL:
				return zend_string_init(PT_FTS_LITERAL(PT_FTS_NULL_KEY), 0);
			case IS_LONG: {
				char buf[MAX_LENGTH_OF_LONG + 1];
				char *digits = zend_print_long_to_buf(buf + sizeof(buf) - 1, Z_LVAL_P(value));
				return ptFtsConcat(PT_FTS_LITERAL(PT_FTS_INTEGER_KEY_PREFIX), digits, (size_t) (buf + sizeof(buf) - 1 - digits), "", 0, "", 0);
			}
			case IS_TRUE:
				return zend_string_init(PT_FTS_LITERAL(PT_FTS_BOOLEAN_KEY_PREFIX "1"), 0);
			case IS_FALSE:
				return zend_string_init(PT_FTS_LITERAL(PT_FTS_BOOLEAN_KEY_PREFIX "0"), 0);
			case IS_STRING:
				return ptFtsConcat(PT_FTS_LITERAL(PT_FTS_STRING_KEY_PREFIX), Z_STRVAL_P(value), Z_STRLEN_P(value), "", 0, "", 0);
			default:
				return NULL;
		}
	}

	/* $member->isClassString()->no(); false = pending exception */
	static bool isClassStringNo(zval *member, bool &out)
	{
		const FiniteClasses &classes = ptFtsClasses;
		zend_class_entry *ce = Z_OBJCE_P(member);
		/* of the five classes key() keys, only a constant string can answer
		 * anything but no — and only it pays a reflection lookup for it */
		if (ce == classes.nullType || ce == classes.constantInteger || ce == classes.constantBoolean || ce == classes.enumCase) {
			out = true;
			return true;
		}

		zval result;
		if (UNEXPECTED(!ptFtsCall(member, PT_FTS_LITERAL("isclassstring"), &result))) {
			return false;
		}
		if (UNEXPECTED(Z_TYPE(result) != IS_OBJECT)) {
			zval_ptr_dtor(&result);
			zend_type_error("isClassString() must return a %s", ZSTR_VAL(pt_ce_trinary->name));
			return false;
		}
		if (EXPECTED(instanceof_function(Z_OBJCE(result), pt_ce_trinary))) {
			out = pt_trinary_value(Z_OBJ(result)) == PT_TRI_NO;
			zval_ptr_dtor(&result);
			return true;
		}

		/* an unshadowed PHPStan\TrinaryLogic (the differential smoke test) */
		zval no;
		bool called = ptFtsCall(&result, PT_FTS_LITERAL("no"), &no);
		zval_ptr_dtor(&result);
		if (UNEXPECTED(!called)) {
			return false;
		}
		out = Z_TYPE(no) == IS_TRUE;
		zval_ptr_dtor(&no);
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::FiniteTypeSet;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define FTS_CLASS "PHPStanTurbo\\FiniteTypeSet"

void pt_finite_type_set_rinit()
{
	phpstanturbo::ptFtsClasses.inited = false;
}

void pt_register_finite_type_set()
{
	reg::Class cls("PHPStanTurbo\\FiniteTypeSet");
	/* not final: the stub subclass PHPStan\Type\FiniteTypeSet extends this
	 * class; the four properties must stay in this order (OBJ_PROP_NUM slots) */
	cls.privateNullProperty("hasClassStringMember");
	cls.privateArrayProperty("members");
	cls.privateArrayProperty("membersByKind");
	cls.privateArrayProperty("others");

	cls.method("__construct", reg::Private, 3, { reg::arrayArg("members"), reg::arrayArg("membersByKind"), reg::arrayArg("others") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *members, *membersByKind, *others;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_ARRAY(members)
			Z_PARAM_ARRAY(membersByKind)
			Z_PARAM_ARRAY(others)
		ZEND_PARSE_PARAMETERS_END();
		FiniteTypeSet(Z_OBJ_P(ZEND_THIS)).construct(zv::Ref(members), zv::Ref(membersByKind), zv::Ref(others));
	});

	cls.method("create", reg::PublicStatic, 1, { reg::arrayArg("types") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *types;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_ARRAY(types)
		ZEND_PARSE_PARAMETERS_END();
		zv::Val set = FiniteTypeSet::create(zv::ArrRef(types));
		if (UNEXPECTED(set.isUndef())) {
			RETURN_THROWS();
		}
		set.intoReturnValue(return_value);
	});

	cls.method("key", reg::PublicStatic, 1, { reg::any("type") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT(type)
		ZEND_PARSE_PARAMETERS_END();
		zv::Str key;
		if (UNEXPECTED(!FiniteTypeSet::key(type, key))) {
			RETURN_THROWS();
		}
		if (key.isNull()) {
			RETURN_NULL();
		}
		RETURN_STR(key.take());
	});

	cls.method("getRepresentativesOfOtherKinds", reg::Public, 1, { reg::any("type") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT(type)
		ZEND_PARSE_PARAMETERS_END();
		zv::Val representatives = FiniteTypeSet(Z_OBJ_P(ZEND_THIS)).getRepresentativesOfOtherKinds(type);
		if (UNEXPECTED(representatives.isUndef())) {
			RETURN_THROWS();
		}
		representatives.intoReturnValue(return_value);
	});

	cls.method("has", reg::Public, 1, { reg::stringArg("key") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *key;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_STR(key)
		ZEND_PARSE_PARAMETERS_END();
		RETURN_BOOL(FiniteTypeSet(Z_OBJ_P(ZEND_THIS)).has(key));
	});

	cls.method("isComplete", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(FiniteTypeSet(Z_OBJ_P(ZEND_THIS)).isComplete());
	});

	cls.method("getMembers", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		FiniteTypeSet(Z_OBJ_P(ZEND_THIS)).getMembers().intoReturnValue(return_value);
	});

	cls.method("getOthers", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		FiniteTypeSet(Z_OBJ_P(ZEND_THIS)).getOthers().intoReturnValue(return_value);
	});

	cls.method("containedIn", reg::Public, 1, { reg::obj("other", FTS_CLASS) }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *other;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(other, pt_ce_finite_type_set)
		ZEND_PARSE_PARAMETERS_END();
		FiniteTypeSet(Z_OBJ_P(ZEND_THIS)).containedIn(Z_OBJ_P(other)).intoReturnValue(return_value);
	});

	cls.method("containedInKey", reg::Public, 1, { reg::stringArg("key") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *key;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_STR(key)
		ZEND_PARSE_PARAMETERS_END();
		FiniteTypeSet(Z_OBJ_P(ZEND_THIS)).containedInKey(key).intoReturnValue(return_value);
	});

	cls.method("hasClassStringMember", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		bool out;
		if (UNEXPECTED(!FiniteTypeSet(Z_OBJ_P(ZEND_THIS)).hasClassStringMember(out))) {
			RETURN_THROWS();
		}
		RETURN_BOOL(out);
	});

	pt_ce_finite_type_set = cls.register_();
}

/* }}} */

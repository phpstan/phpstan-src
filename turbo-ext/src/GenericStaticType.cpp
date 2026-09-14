/*
 * PHPStanTurbo\GenericStaticType — native implementation of
 * PHPStan\Type\Generic\GenericStaticType.
 *
 * Declared as PHPStan\Type\Generic\GenericStaticType itself at activation:
 * not final, extending the native StaticType (declared first — Shadow.cpp
 * materialises a parent plan before its child). State is the twin's own
 * `private ?ObjectType $staticObjectType` memo and the promoted `private
 * ClassReflection $classReflection`, `private array $types`, `private
 * ?Type $subtractedType` and `private array $variances` — private slots of
 * its own next to the parent's (the twin redeclares two of the names), so
 * they follow StaticType's five in the object. The twin uses no traits of
 * its own; everything it does not override is inherited from StaticType,
 * and its `parent::` calls go to StaticType's native bodies directly
 * (pt_static_type_* in TypeTraits.h).
 *
 * Every `$this->method()` the twin makes goes through the object's class
 * entry — a PHP subclass may have overridden it — with the direct path when
 * the method is StaticType's own.
 */

#include "TypeTraits.h"
#include "generated/GenericStaticType.h"

namespace slots = ptdecl::GenericStaticType::slot;
namespace sigs = ptdecl::GenericStaticType::sig;

#pragma GCC diagnostic push
#pragma GCC diagnostic ignored "-Wpragmas"
#pragma GCC diagnostic ignored "-Wunknown-warning-option"
#pragma GCC diagnostic ignored "-Wunused-parameter"
#pragma GCC diagnostic ignored "-Wignored-qualifiers"
#pragma GCC diagnostic ignored "-Wdeprecated-declarations"
#pragma GCC diagnostic ignored "-Wattributes"
#pragma GCC diagnostic pop

/* the engine's read-of-an-undefined-key warning (its helpers are not
 * exported), for the `$array[$key]` reads the twin makes without a check */
static void pt_gst_undefined_key(const zv::ArrayEntry &entry)
{
	if (entry.hasStringKey()) {
		zend_error(E_WARNING, "Undefined array key \"%s\"", ZSTR_VAL(entry.stringKey()));
	} else {
		zend_error(E_WARNING, "Undefined array key " ZEND_LONG_FMT, (zend_long) entry.indexKey());
	}
}

zend_class_entry *pt_ce_generic_static_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\GenericStaticType. State lives in the PHP
 * object's own slots, after StaticType's. */
class GenericStaticType
{
public:
	explicit GenericStaticType(zend_object *self) : self(self) {}

	/* __construct(private ClassReflection $classReflection, private array
	 * $types, private ?Type $subtractedType, private array $variances): the
	 * promoted properties written first, as PHP does, then the zero-types
	 * check, then parent::__construct(); $subtractedType IS_NULL for null */
	void construct(zval *classReflection, zval *types, zval *subtractedType, zval *variances)
	{
		writeSlot(slots::classReflection, classReflection);
		writeSlot(slots::types, types);
		writeSlot(slots::subtractedType, subtractedType);
		writeSlot(slots::variances, variances);
		if (zend_hash_num_elements(Z_ARRVAL_P(types)) == 0) {
			zend_class_entry *ce = pt_class(PT_CLASS_SHOULD_NOT_HAPPEN);
			if (ce != NULL) {
				zend_throw_exception(ce, "Cannot create GenericStaticType with zero types.", 0);
			}
			return;
		}
		pt_static_type_construct(self, classReflection, Z_TYPE_P(subtractedType) == IS_NULL ? NULL : subtractedType);
	}

	/* new self($classReflection, $types, $subtractedType, $variances) —
	 * exactly the class, as the twin's `new self` sites spell it;
	 * $subtractedType IS_NULL for null; UNDEF = pending exception */
	static zv::Val create(zval *classReflection, zval *types, zval *subtractedType, zval *variances)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_generic_static_type) != SUCCESS)) return zv::Val();
		GenericStaticType(Z_OBJ(object)).construct(classReflection, types, subtractedType, variances);
		if (UNEXPECTED(EG(exception))) {
			zval_ptr_dtor(&object);
			return zv::Val();
		}
		return zv::Val::adopt(object);
	}

	/* the own slots (borrowed); NULL with an Error pending when the
	 * constructor never ran */
	[[nodiscard]] zval *classReflection() const { return slot(self, slots::classReflection, "classReflection"); }
	zval *types() const { return slot(self, slots::types, "types"); }
	zval *subtractedType() const { return slot(self, slots::subtractedType, "subtractedType"); }
	zval *variances() const { return slot(self, slots::variances, "variances"); }

	static zval *slot(zend_object *object, uint32_t index, const char *name) { return pt_typed_slot(object, index, pt_ce_generic_static_type, name); }

	/* the memoized static object type: a GenericObjectType over the own
	 * types and variances for a generic class, parent::getStaticObjectType()
	 * otherwise; UNDEF = pending exception */
	zv::Val getStaticObjectType() const
	{
		zval *memo = OBJ_PROP_NUM(self, slots::staticObjectType);
		if (Z_TYPE_P(memo) == IS_OBJECT) return zv::Val::copyOf(zv::Ref(memo));
		zval *reflection = classReflection();
		if (UNEXPECTED(reflection == NULL)) return zv::Val();
		zv::Val generic = pt_type_call(Z_OBJ_P(reflection), PT_LC("isgeneric"), 0, NULL);
		if (UNEXPECTED(generic.isUndef())) return zv::Val();
		zv::Val objectType;
		if (zend_is_true(generic.raw())) {
			zval *ownTypes = types();
			zval *subtracted = ownTypes != NULL ? subtractedType() : NULL;
			zval *ownVariances = subtracted != NULL ? variances() : NULL;
			if (UNEXPECTED(ownVariances == NULL)) return zv::Val();
			zv::Val name = pt_type_call(Z_OBJ_P(reflection), PT_LC("getname"), 0, NULL);
			if (UNEXPECTED(name.isUndef())) return zv::Val();
			/* new GenericObjectType($name, $this->types, $this->subtractedType, $this->classReflection, $this->variances) */
			zval genericRaw;
			if (UNEXPECTED(!pt_generic_object_type_new(&genericRaw, Z_STR_P(name.raw()), ownTypes, subtracted, reflection, ownVariances))) return zv::Val();
			objectType = zv::Val::adopt(genericRaw);
		} else {
			objectType = pt_static_type_get_static_object_type(self);
		}
		if (UNEXPECTED(objectType.isUndef())) return zv::Val();
		zv::ObjRef(self).propAtWrite(slots::staticObjectType, zv::Val::copyOf(zv::Ref(objectType.raw())));
		return objectType;
	}

	/* $this for the own class; a plain StaticType for a non-generic class;
	 * else the own type arguments carried over to the class's template
	 * parameters through its ancestor with the own class name (the class's
	 * bounds when there is none); UNDEF = pending exception */
	zv::Val changeBaseClass(zval *classReflection) const
	{
		zv::Val ownClassName = pt_static_type_this_class_name(self);
		if (UNEXPECTED(ownClassName.isUndef())) return zv::Val();
		zv::Val newName = pt_type_call(Z_OBJ_P(classReflection), PT_LC("getname"), 0, NULL);
		if (UNEXPECTED(newName.isUndef())) return zv::Val();
		if (zend_is_identical(newName.raw(), ownClassName.raw())) return thisValue();
		zv::Val generic = pt_type_call(Z_OBJ_P(classReflection), PT_LC("isgeneric"), 0, NULL);
		if (UNEXPECTED(generic.isUndef())) return zv::Val();
		if (!zend_is_true(generic.raw())) {
			zval result;
			if (UNEXPECTED(!pt_static_type_new(&result, classReflection))) return zv::Val();
			return zv::Val::adopt(result);
		}

		/* $templateTags = $this->getClassReflection()->getTemplateTags() */
		zv::Val ownReflection = pt_static_type_this_class_reflection(self);
		if (UNEXPECTED(ownReflection.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(ownReflection.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getClassReflection() must return an object");
			return zv::Val();
		}
		zv::Val templateTags = pt_type_call(Z_OBJ_P(ownReflection.raw()), PT_LC("gettemplatetags"), 0, NULL);
		if (UNEXPECTED(templateTags.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(templateTags.raw()).isArray())) {
			zend_type_error("phpstan_turbo: getTemplateTags() must return array");
			return zv::Val();
		}
		zval *ownTypes = types();
		zval *ownVariances = ownTypes != NULL ? variances() : NULL;
		zval *subtracted = ownVariances != NULL ? subtractedType() : NULL;
		if (UNEXPECTED(subtracted == NULL)) return zv::Val();
		zv::Arr indexedTypes = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(ownTypes)));
		zv::Arr indexedVariances = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(ownVariances)));
		zend_long i = 0;
		for (zv::ArrayEntry tag : zv::ArrRef(templateTags.raw())) {
			zval *type = zend_hash_index_find(Z_ARRVAL_P(ownTypes), (zend_ulong) i);
			if (type == NULL) break;
			zval *variance = zend_hash_index_find(Z_ARRVAL_P(ownVariances), (zend_ulong) i);
			if (variance == NULL) break;
			setByKey(indexedTypes, tag, type);
			setByKey(indexedVariances, tag, variance);
			i++;
		}

		/* $newType = new GenericObjectType($classReflection->getName(), $classReflection->typeMapToList($classReflection->getTemplateTypeMap())) */
		zv::Val templateTypeMap = pt_type_call(Z_OBJ_P(classReflection), PT_LC("gettemplatetypemap"), 0, NULL);
		if (UNEXPECTED(templateTypeMap.isUndef())) return zv::Val();
		zv::Val newTypes = pt_type_call(Z_OBJ_P(classReflection), PT_LC("typemaptolist"), 1, templateTypeMap.raw());
		if (UNEXPECTED(newTypes.isUndef())) return zv::Val();
		zval newTypeRaw;
		zv::Val newType = pt_generic_object_type_new(&newTypeRaw, Z_STR_P(newName.raw()), newTypes.raw()) ? zv::Val::adopt(newTypeRaw) : zv::Val();
		if (UNEXPECTED(newType.isUndef())) return zv::Val();
		zv::Val ancestorType = pt_type_call(Z_OBJ_P(newType.raw()), PT_LC("getancestorwithclassname"), 1, ownClassName.raw());
		if (UNEXPECTED(ancestorType.isUndef())) return zv::Val();
		if (ancestorType.isNull()) return withBounds(classReflection, subtracted);
		if (UNEXPECTED(!zv::Ref(ancestorType.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getAncestorWithClassName() must return an object or null");
			return zv::Val();
		}
		zv::Val ancestorClassReflection = pt_type_call(Z_OBJ_P(ancestorType.raw()), PT_LC("getclassreflection"), 0, NULL);
		if (UNEXPECTED(ancestorClassReflection.isUndef())) return zv::Val();
		if (ancestorClassReflection.isNull()) return withBounds(classReflection, subtracted);
		if (UNEXPECTED(!zv::Ref(ancestorClassReflection.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getClassReflection() must return an object or null");
			return zv::Val();
		}

		/* foreach ($ancestorClassReflection->getActiveTemplateTypeMap()->getTypes() as $typeName => $templateType) */
		zv::Val activeTypeMap = pt_type_call(Z_OBJ_P(ancestorClassReflection.raw()), PT_LC("getactivetemplatetypemap"), 0, NULL);
		if (UNEXPECTED(activeTypeMap.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(activeTypeMap.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getActiveTemplateTypeMap() must return an object");
			return zv::Val();
		}
		zv::Val activeTypes = pt_type_call(Z_OBJ_P(activeTypeMap.raw()), PT_LC("gettypes"), 0, NULL);
		if (UNEXPECTED(activeTypes.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(activeTypes.raw()).isArray())) {
			zend_type_error("phpstan_turbo: getTypes() must return array");
			return zv::Val();
		}
		zv::Arr newClassTypes = zv::Arr::create(zv::ArrRef(activeTypes.raw()).size());
		zv::Arr newClassVariances = zv::Arr::create(zv::ArrRef(activeTypes.raw()).size());
		for (zv::ArrayEntry entry : zv::ArrRef(activeTypes.raw())) {
			bool isTemplate;
			if (UNEXPECTED(!pt_type_instanceof(entry.value().raw(), PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();
			if (!isTemplate) continue;
			zval *indexedType = findByKey(indexedTypes, entry);
			if (indexedType == NULL) continue;
			zval *indexedVariance = findByKey(indexedVariances, entry);
			zv::Val templateName = pt_type_call(Z_OBJ_P(entry.value().raw()), PT_LC("getname"), 0, NULL);
			if (UNEXPECTED(templateName.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(templateName.raw()).isString())) {
				zend_type_error("phpstan_turbo: getName() must return string");
				return zv::Val();
			}
			newClassTypes.set(zv::Ref(templateName.raw()).asString(), zv::Val::copyOf(zv::Ref(indexedType)));
			if (indexedVariance != NULL) {
				newClassVariances.set(zv::Ref(templateName.raw()).asString(), zv::Val::copyOf(zv::Ref(indexedVariance)));
			} else {
				/* $indexedVariances[$typeName] — the twin reads an undefined
				 * key as null, with the engine's warning */
				pt_gst_undefined_key(entry);
				if (UNEXPECTED(EG(exception))) return zv::Val();
				newClassVariances.set(zv::Ref(templateName.raw()).asString(), zv::Val::null());
			}
		}

		/* new self($classReflection, $classReflection->typeMapToList(new TemplateTypeMap($newClassTypes)), $this->subtractedType, $classReflection->varianceMapToList(new TemplateTypeVarianceMap($newClassVariances))) */
		zv::Val newTypeMap = pt_type_new(PT_CLASS_TEMPLATE_TYPE_MAP, 1, newClassTypes.raw());
		if (UNEXPECTED(newTypeMap.isUndef())) return zv::Val();
		zv::Val listTypes = pt_type_call(Z_OBJ_P(classReflection), PT_LC("typemaptolist"), 1, newTypeMap.raw());
		if (UNEXPECTED(listTypes.isUndef())) return zv::Val();
		zv::Val newVarianceMap = pt_type_new(PT_CLASS_TEMPLATE_TYPE_VARIANCE_MAP, 1, newClassVariances.raw());
		if (UNEXPECTED(newVarianceMap.isUndef())) return zv::Val();
		zv::Val listVariances = pt_type_call(Z_OBJ_P(classReflection), PT_LC("variancemaptolist"), 1, newVarianceMap.raw());
		if (UNEXPECTED(listVariances.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(listTypes.raw()).isArray() || !zv::Ref(listVariances.raw()).isArray())) {
			zend_type_error("phpstan_turbo: typeMapToList()/varianceMapToList() must return array");
			return zv::Val();
		}
		return create(classReflection, listTypes.raw(), subtracted, listVariances.raw());
	}

	/* the CompoundType callback; the static object types' answer for
	 * another GenericStaticType; else parent::isSuperTypeOf() held to
	 * maybe; UNDEF = pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_call(Z_OBJ_P(type), PT_LC("issubtypeof"), 1, &selfZv);
		}
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_generic_static_type)) {
			zv::Val staticObject = pt_static_type_this_static_object_type(self);
			if (UNEXPECTED(staticObject.isUndef())) return zv::Val();
			zv::Val typeStaticObject = pt_type_call(Z_OBJ_P(type), PT_LC("getstaticobjecttype"), 0, NULL);
			if (UNEXPECTED(typeStaticObject.isUndef())) return zv::Val();
			return pt_type_call(Z_OBJ_P(staticObject.raw()), PT_LC("issupertypeof"), 1, typeStaticObject.raw());
		}
		zv::Val result = pt_static_type_is_super_type_of(self, type);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(result.raw()).isObject())) {
			zend_type_error("phpstan_turbo: isSuperTypeOf() must return %s", ZSTR_VAL(pt_ce_is_super_type_of_result->name));
			return zv::Val();
		}
		zv::Val maybe = pt_type_is_super_type_of_result(PT_TRI_MAYBE);
		if (UNEXPECTED(maybe.isUndef())) return zv::Val();
		return pt_type_call(Z_OBJ_P(result.raw()), PT_LC("and"), 1, maybe.raw());
	}

	/* new self over the mapped subtracted type and types when the callback
	 * changed any, $this otherwise; UNDEF = pending exception */
	zv::Val traverse(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zv::Val subtracted = pt_static_type_this_subtracted_type(self);
		if (UNEXPECTED(subtracted.isUndef())) return zv::Val();
		zv::Val mappedSubtracted;
		if (!subtracted.isNull()) {
			zval mapped;
			if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, subtracted.raw(), &mapped))) return zv::Val();
			mappedSubtracted = zv::Val::adopt(mapped);
		} else {
			mappedSubtracted = zv::Val::null();
		}

		zval *ownTypes = types();
		if (UNEXPECTED(ownTypes == NULL)) return zv::Val();
		bool typesChanged = false;
		zv::Arr mappedTypes = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(ownTypes)));
		for (zv::ArrayEntry entry : zv::ArrRef(ownTypes)) {
			zval newType;
			if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, entry.value().raw(), &newType))) return zv::Val();
			if (Z_TYPE(newType) != IS_OBJECT || Z_OBJ(newType) != Z_OBJ_P(entry.value().raw())) {
				typesChanged = true;
			}
			mappedTypes.push(zv::Val::adopt(newType));
		}

		zv::Val again = pt_static_type_this_subtracted_type(self);
		if (UNEXPECTED(again.isUndef())) return zv::Val();
		if (!zend_is_identical(mappedSubtracted.raw(), again.raw()) || typesChanged) {
			zval *reflection = classReflection();
			zval *ownVariances = reflection != NULL ? variances() : NULL;
			if (UNEXPECTED(ownVariances == NULL)) return zv::Val();
			return create(reflection, mappedTypes.raw(), mappedSubtracted.raw(), ownVariances);
		}
		return thisValue();
	}

	/* new self over the types mapped together with the right side's
	 * ancestor's (a GenericStaticType of the own class with as many
	 * types), without a subtracted type; $this otherwise; UNDEF = pending
	 * exception */
	zv::Val traverseSimultaneously(zval *right, zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		bool withClassName;
		if (UNEXPECTED(!pt_type_instanceof(right, PT_CLASS_TYPE_WITH_CLASS_NAME, withClassName))) return zv::Val();
		if (!withClassName) return thisValue();
		zv::Val ownClassName = pt_static_type_this_class_name(self);
		if (UNEXPECTED(ownClassName.isUndef())) return zv::Val();
		zv::Val ancestor = pt_type_call(Z_OBJ_P(right), PT_LC("getancestorwithclassname"), 1, ownClassName.raw());
		if (UNEXPECTED(ancestor.isUndef())) return zv::Val();
		if (!zv::Ref(ancestor.raw()).instanceOf(pt_ce_generic_static_type)) return thisValue();
		zval *ownTypes = types();
		if (UNEXPECTED(ownTypes == NULL)) return zv::Val();
		zval *ancestorTypes = GenericStaticType(Z_OBJ_P(ancestor.raw())).types();
		if (UNEXPECTED(ancestorTypes == NULL)) return zv::Val();
		if (zend_hash_num_elements(Z_ARRVAL_P(ownTypes)) != zend_hash_num_elements(Z_ARRVAL_P(ancestorTypes))) return thisValue();

		bool typesChanged = false;
		zv::Arr mappedTypes = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(ownTypes)));
		for (zv::ArrayEntry entry : zv::ArrRef(ownTypes)) {
			/* $rightType = $ancestor->types[$i] — an undefined key reads as
			 * null with the engine's warning, as in the twin */
			zval *rightType = entry.hasStringKey()
				? zend_hash_find(Z_ARRVAL_P(ancestorTypes), entry.stringKey())
				: zend_hash_index_find(Z_ARRVAL_P(ancestorTypes), entry.indexKey());
			zval null;
			if (rightType == NULL) {
				pt_gst_undefined_key(entry);
				if (UNEXPECTED(EG(exception))) return zv::Val();
				ZVAL_NULL(&null);
				rightType = &null;
			}
			zv::Args args{entry.value().raw(), rightType};
			zval newType;
			if (UNEXPECTED(!pt_call_fci(fci, fcc, 2, args, &newType))) return zv::Val();
			if (Z_TYPE(newType) != IS_OBJECT || Z_OBJ(newType) != Z_OBJ_P(entry.value().raw())) {
				typesChanged = true;
			}
			mappedTypes.push(zv::Val::adopt(newType));
		}

		if (typesChanged) {
			zval *reflection = classReflection();
			zval *ownVariances = reflection != NULL ? variances() : NULL;
			if (UNEXPECTED(ownVariances == NULL)) return zv::Val();
			zval null;
			ZVAL_NULL(&null);
			return create(reflection, mappedTypes.raw(), &null, ownVariances);
		}
		return thisValue();
	}

	/* new self over the own types and $subtractedType — for a class with
	 * allowed subtypes projected through the static object type: never
	 * stays never, an ObjectType's remaining subtracted type is taken over,
	 * anything else intersected with $this; UNDEF = pending exception */
	zv::Val changeSubtractedType(zval *subtractedType) const
	{
		zval *ownTypes = types();
		zval *ownVariances = ownTypes != NULL ? variances() : NULL;
		if (UNEXPECTED(ownVariances == NULL)) return zv::Val();
		if (Z_TYPE_P(subtractedType) != IS_NULL) {
			zv::Val classReflection = pt_static_type_this_class_reflection(self);
			if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
			if (UNEXPECTED(!zv::Ref(classReflection.raw()).isObject())) {
				zend_type_error("phpstan_turbo: getClassReflection() must return an object");
				return zv::Val();
			}
			zv::Val allowedSubTypes = pt_type_call(Z_OBJ_P(classReflection.raw()), PT_LC("getallowedsubtypes"), 0, NULL);
			if (UNEXPECTED(allowedSubTypes.isUndef())) return zv::Val();
			if (!allowedSubTypes.isNull()) {
				zv::Val staticObject = pt_static_type_this_static_object_type(self);
				if (UNEXPECTED(staticObject.isUndef())) return zv::Val();
				zv::Val objectType = pt_type_call(Z_OBJ_P(staticObject.raw()), PT_LC("changesubtractedtype"), 1, subtractedType);
				if (UNEXPECTED(objectType.isUndef())) return zv::Val();
				if (UNEXPECTED(!zv::Ref(objectType.raw()).isObject())) {
					zend_type_error("phpstan_turbo: changeSubtractedType() must return an object");
					return zv::Val();
				}
				if (zv::Ref(objectType.raw()).instanceOf(pt_ce_never_type)) return objectType;
				bool isObjectType = Z_TYPE_P(objectType.raw()) == IS_OBJECT && instanceof_function(Z_OBJCE_P(objectType.raw()), pt_ce_object_type);
				if (isObjectType) {
					zv::Val remaining = pt_type_call(Z_OBJ_P(objectType.raw()), PT_LC("getsubtractedtype"), 0, NULL);
					if (UNEXPECTED(remaining.isUndef())) return zv::Val();
					if (!remaining.isNull()) return create(classReflection.raw(), ownTypes, remaining.raw(), ownVariances);
				}
				zv::Args args{self, objectType.raw()};
				return pt_type_combinator_call(PT_LC("intersect"), 2, args);
			}
		}
		zval *reflection = classReflection();
		if (UNEXPECTED(reflection == NULL)) return zv::Val();
		return create(reflection, ownTypes, subtractedType, ownVariances);
	}

	/* $this->getStaticObjectType()->method($arg) — inferTemplateTypes() and
	 * getReferencedTemplateTypes(); UNDEF = pending exception */
	zv::Val delegate(const char *lcname, size_t len, zval *arg) const
	{
		zv::Val staticObject = pt_static_type_this_static_object_type(self);
		if (UNEXPECTED(staticObject.isUndef())) return zv::Val();
		return pt_type_call(Z_OBJ_P(staticObject.raw()), lcname, len, 1, arg);
	}

	/* new GenericTypeNode(parent::toPhpDocNode(), <the types' nodes>, <the
	 * variances' node variances>); UNDEF = pending exception */
	zv::Val toPhpDocNode() const
	{
		zv::Val parent = pt_static_type_to_php_doc_node();
		if (UNEXPECTED(parent.isUndef())) return zv::Val();
		zval *ownTypes = types();
		zval *ownVariances = ownTypes != NULL ? variances() : NULL;
		if (UNEXPECTED(ownVariances == NULL)) return zv::Val();
		zv::Arr typeNodes = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(ownTypes)));
		for (zv::ArrayEntry entry : zv::ArrRef(ownTypes)) {
			zv::Val node = pt_type_call(Z_OBJ_P(entry.value().raw()), PT_LC("tophpdocnode"), 0, NULL);
			if (UNEXPECTED(node.isUndef())) return zv::Val();
			setByKey(typeNodes, entry, node.raw());
		}
		zv::Arr varianceNodes = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(ownVariances)));
		for (zv::ArrayEntry entry : zv::ArrRef(ownVariances)) {
			zv::Val node = pt_type_call(Z_OBJ_P(entry.value().raw()), PT_LC("tophpdocnodevariance"), 0, NULL);
			if (UNEXPECTED(node.isUndef())) return zv::Val();
			setByKey(varianceNodes, entry, node.raw());
		}
		zv::Args args{parent.raw(), typeNodes.raw(), varianceNodes.raw()};
		return pt_type_new(PT_CLASS_GENERIC_TYPE_NODE, 3, args);
	}

	/* whether any type argument, else the subtracted type, has one; false
	 * with an exception pending */
	bool hasTemplateOrLateResolvableType(bool &out) const
	{
		zval *ownTypes = types();
		if (UNEXPECTED(ownTypes == NULL)) return false;
		for (zv::ArrayEntry entry : zv::ArrRef(ownTypes)) {
			zv::Val has = pt_type_call(Z_OBJ_P(entry.value().raw()), PT_LC("hastemplateorlateresolvabletype"), 0, NULL);
			if (UNEXPECTED(has.isUndef())) return false;
			if (zend_is_true(has.raw())) {
				out = true;
				return true;
			}
		}
		zval *subtracted = subtractedType();
		if (UNEXPECTED(subtracted == NULL)) return false;
		if (Z_TYPE_P(subtracted) == IS_NULL) {
			out = false;
			return true;
		}
		return pt_type_call_bool(Z_OBJ_P(subtracted), PT_LC("hastemplateorlateresolvabletype"), 0, NULL, out);
	}

private:
	zend_object *self;

	zv::Val thisValue() const { return pt_this_value(self); }

	void writeSlot(uint32_t index, zval *value) { pt_write_slot(self, index, value); }

	/* $array[$entry's key] = $value (array_map() keeps the keys) */
	static void setByKey(zv::Arr &array, const zv::ArrayEntry &entry, zval *value)
	{
		Z_TRY_ADDREF_P(value);
		if (entry.hasStringKey()) {
			zend_hash_update(array.table(), entry.stringKey(), value);
		} else {
			zend_hash_index_update(array.table(), entry.indexKey(), value);
		}
	}

	/* $array[$entry's key] ?? NULL */
	static zval *findByKey(zv::Arr &array, const zv::ArrayEntry &entry)
	{
		if (entry.hasStringKey()) return zend_hash_find(array.table(), entry.stringKey());
		return zend_hash_index_find(array.table(), entry.indexKey());
	}

	/* new self($classReflection, $classReflection->typeMapToList($classReflection->getTemplateTypeMap()->resolveToBounds()),
	 * $this->subtractedType, $classReflection->varianceMapToList($classReflection->getCallSiteVarianceMap()));
	 * UNDEF = pending exception */
	zv::Val withBounds(zval *classReflection, zval *subtracted) const
	{
		zv::Val templateTypeMap = pt_type_call(Z_OBJ_P(classReflection), PT_LC("gettemplatetypemap"), 0, NULL);
		if (UNEXPECTED(templateTypeMap.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(templateTypeMap.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getTemplateTypeMap() must return an object");
			return zv::Val();
		}
		zv::Val bounds = pt_type_call(Z_OBJ_P(templateTypeMap.raw()), PT_LC("resolvetobounds"), 0, NULL);
		if (UNEXPECTED(bounds.isUndef())) return zv::Val();
		zv::Val listTypes = pt_type_call(Z_OBJ_P(classReflection), PT_LC("typemaptolist"), 1, bounds.raw());
		if (UNEXPECTED(listTypes.isUndef())) return zv::Val();
		zv::Val varianceMap = pt_type_call(Z_OBJ_P(classReflection), PT_LC("getcallsitevariancemap"), 0, NULL);
		if (UNEXPECTED(varianceMap.isUndef())) return zv::Val();
		zv::Val listVariances = pt_type_call(Z_OBJ_P(classReflection), PT_LC("variancemaptolist"), 1, varianceMap.raw());
		if (UNEXPECTED(listVariances.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(listTypes.raw()).isArray() || !zv::Ref(listVariances.raw()).isArray())) {
			zend_type_error("phpstan_turbo: typeMapToList()/varianceMapToList() must return array");
			return zv::Val();
		}
		return create(classReflection, listTypes.raw(), subtracted, listVariances.raw());
	}
};

} // namespace phpstanturbo

using phpstanturbo::GenericStaticType;

bool pt_generic_static_type_new(zval *out, zval *classReflection, zval *types, zval *subtractedType, zval *variances)
{
	zval null;
	if (subtractedType == NULL) {
		ZVAL_NULL(&null);
		subtractedType = &null;
	}
	return pt_val_into(GenericStaticType::create(classReflection, types, subtractedType, variances), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS GenericStaticType(Z_OBJ_P(ZEND_THIS))

void pt_register_generic_static_type()
{

	reg::Class cls("PHPStan\\Type\\Generic\\GenericStaticType");
	ptdecl::GenericStaticType::declareClass(cls);
	/* the slots must stay in this order (PT_GST_PROP_*), after StaticType's */
	cls.privateTypedClassPropertyDefaultNull("staticObjectType", "PHPStan\\Type\\ObjectType");
	cls.privateTypedClassProperty("classReflection", "PHPStan\\Reflection\\ClassReflection", false);
	cls.privateTypedProperty("types", MAY_BE_ARRAY);
	cls.privateTypedClassProperty("subtractedType", "PHPStan\\Type\\Type", true);
	cls.privateTypedProperty("variances", MAY_BE_ARRAY);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *classReflection, *types, *subtractedType, *variances;
		if (!zp::parse<zp::Obj, zp::Arr, zp::ObjOrNull, zp::Arr>(execute_data, classReflection, types, subtractedType, variances)) RETURN_THROWS();
		zval null;
		if (subtractedType == NULL) {
			ZVAL_NULL(&null);
			subtractedType = &null;
		}
		PT_THIS.construct(classReflection, types, subtractedType, variances);
	});

	cls.method(sigs::getTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zval *types = PT_THIS.types();
		if (UNEXPECTED(types == NULL)) RETURN_THROWS();
		RETURN_COPY(types);
	});

	cls.method(sigs::getVariances, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zval *variances = PT_THIS.variances();
		if (UNEXPECTED(variances == NULL)) RETURN_THROWS();
		RETURN_COPY(variances);
	});

	cls.method<&GenericStaticType::getStaticObjectType>(sigs::getStaticObjectType);

	cls.method<&GenericStaticType::changeBaseClass, zp::Obj>(sigs::changeBaseClass);

	cls.method<&GenericStaticType::isSuperTypeOf, zp::Obj>(sigs::isSuperTypeOf);

	cls.method(sigs::traverse, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverse(&fci, &fcc));
	});

	cls.method(sigs::traverseSimultaneously, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *right;
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(right)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverseSimultaneously(right, &fci, &fcc));
	});

	cls.method(sigs::changeSubtractedType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *subtractedType;
		if (!zp::parse<zp::ObjOrNull>(execute_data, subtractedType)) RETURN_THROWS();
		zval null;
		if (subtractedType == NULL) {
			ZVAL_NULL(&null);
			subtractedType = &null;
		}
		PT_RETURN_VAL(PT_THIS.changeSubtractedType(subtractedType));
	});

	cls.method(sigs::inferTemplateTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *receivedType;
		if (!zp::parse<zp::Obj>(execute_data, receivedType)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.delegate(PT_LC("infertemplatetypes"), receivedType));
	});

	cls.method(sigs::getReferencedTemplateTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *positionVariance;
		if (!zp::parse<zp::Obj>(execute_data, positionVariance)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.delegate(PT_LC("getreferencedtemplatetypes"), positionVariance));
	});

	cls.method<&GenericStaticType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method<&GenericStaticType::hasTemplateOrLateResolvableType>(sigs::hasTemplateOrLateResolvableType);

	cls.shadow(&pt_ce_generic_static_type);
}

/* }}} */

# Entity Query custom fields

Generally speaking, querying for custom field properties is no different from 
querying for any other type of field properties, but there are scenarios where
some differences in how you query are required. One such case is when you have a
custom field [entity reference](../plugins/type/entity-reference.md) subfield type, and you want to query it for fields
on the actual entity being referenced.

Let's assume you have a custom field type named `field_custom` and an [Entity
reference](../plugins/type/entity-reference.md) subfield named `reference` which
can reference nodes. The nodes have a field called `field_number that` you want
to filter by in your query. With a normal entity reference field in an entity
query, we could do something like this to get the nested field value of the
referenced node:

```php
$query = \Drupal::entityQuery('node')
  ->condition('type', 'article')
  ->condition('field_reference.entity.field_number', 5);
```

This works because the `field_reference` entity reference field has a main
property on the `target_id` and the computed entity property along with the 
handler settings defined in the field definition can resolve to an entity and
therefore drill down to the `field_number` value.

In **custom_field** types, there is not a main property entityQuery can
work with, so we cannot derive the values in the same way.

The **custom_field** way of doing this for the same scenario would look like
this:

```php
$query = \Drupal::entityQuery('node')
  ->condition('type', 'article')
  ->condition('field_custom.reference.reference__entity:node.field_number', 5);
```

This works for the following reasons:

1. `field_custom` is used as the base table.
2. Although the main property is null, the next specifier `reference` is a real
column that takes precedence and the key is advanced.
3. Every `entity_reference` subfield has a computed property for the entity with
the syntax of `{subfield_name}__entity` which in this case results in
`reference__entity`.
4. `reference__entity:node` is used as the relationship_specifier. Since it's in
the property definitions and is type *DataReferenceDefinitionInterface*, it
joins the node table.

> [!note]
> Due to the fact that there is no main property in custom field types, the
> following query would also fail as the `->exists` defaults to the main
> property which of course is purposely set to `NULL` on our field types:
> 
> ```php
> $query = \Drupal::entityTypeManager()->getStorage('node')->getQuery();
> $query->accessCheck(FALSE);
> $query->exists('field_my_custom_field');
> ```
> The solution here is to target a specific known property rather than the field
> itself.
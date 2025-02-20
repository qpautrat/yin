## 0.11.0 - unreleased

This version will bring a completely rethinked hydration system while improving the docs, test coverage and test quality.
According to my plans, v11.0 may be the last minor release before v1.0.

ADDED:

CHANGED:

REMOVED:

FIXED:

## 0.10.7 - 2016-06-13

FIXED:

- [#25](https://github.com/woohoolabs/yin/issues/25): OffsetPagination bug, offset and limit mixup

## 0.10.6 - 2016-05-17

CHANGED:

- Updated justinrainbow/json-schema to v2.0.0

FIXED:

- [#23](https://github.com/woohoolabs/yin/issues/23): Fixed jsonApi object

## 0.10.5 - 2016-05-08

ADDED:

- Support for PHPUnit 5.0
- `Request::getFilteringParam()` method

CHANGED:

- Updated JSON API schema
- A default value can be provided to `Request::getResourceAttribute()` method when the attribute is not present
- [#20](https://github.com/woohoolabs/yin/issues/20): Expressing empty relationships in the response

FIXED:

- [#22](https://github.com/woohoolabs/yin/issues/22): Data member isn't present when fetching a relationship

## 0.10.4 - 2016-03-29

FIXED:

- [#18](https://github.com/woohoolabs/yin/issues/18): Sorting always happens on primary key in resource

## 0.10.3 - 2016-03-26

ADDED:

- Integrations section to the read me file

FIXED:

- Application errors now have status code 500 instead of 400
- [#17](https://github.com/woohoolabs/yin/pull/17): Avoid double stream reading

## 0.10.2 - 2016-02-29

ADDED:

- Missing sections to the read me file

CHANGED:

- [#8](https://github.com/woohoolabs/yin/issues/8): Pass attribute name to the attribute transformer
- [#11](https://github.com/woohoolabs/yin/pull/11): Pass relationship name to the relationship transformer
- [#10](https://github.com/woohoolabs/yin/pull/10): Pass attribute name to the attribute hydrator closure
- [#13](https://github.com/woohoolabs/yin/pull/13): Pass relationship name to the relationship hydrator
- [#14](https://github.com/woohoolabs/yin/pull/14): Expect callables instead of closures for hydrators/transformers
- [#7](https://github.com/woohoolabs/yin/issues/7): More intuitive example URL-s

FIXED:

- [#6](https://github.com/woohoolabs/yin/issues/6): Fixed examples in order not to throw fatal error
- [#16](https://github.com/woohoolabs/yin/issues/16): ResourceIdentifier does not consider "data" key

## 0.10.1 - 2016-01-21

FIXED:

- [#5](https://github.com/woohoolabs/yin/issues/5): Attributes and relationships objects are now omitted when empty instead of being serialized as empty arrays

## 0.10.0 - 2016-01-16

This version incorporates some new methods to easily retrieve the content of the request body and some important bug
fixes. It doesn't contain any breaking changes so updating to v10.0 is highly recommended.

ADDED:

- `AbstractSimpleResourceDocument` to define documents containing information about a single resource without
the need of a resource transformer
- `ClientGeneratedIdRequired` exception
- `getResourceAttributes()` method to `RequestInterface`
- `getResourceToOneRelationship()` and `getResourceToManyRelationship()` methods to `RequestInterface`

CHANGED:

- `TransformerTrait` transformations are now type hinted against `DateTimeInterface` to support `DateTimeImmutable`

FIXED:

- Parameter order in `AbstractCreateHydrator::hydrate()`
- [#3](https://github.com/woohoolabs/yin/issues/3): Fixed multi-level relationships
- Issue when include query param is an empty string

## 0.9.0 - 2015-11-26

ADDED:

- Possibility to pass additional meta information for documents when fetching the response
- [#2](https://github.com/woohoolabs/yin/issues/2): Possibility to only load relationship data when the relationship itself is included

CHANGED:

- Renamed `getDefaultRelationships()` to `getDefaultIncludedRelationships()` in transformers to better reflect its meaning
- The "data" key of relationships won't be present in the response when it is empty
- Renamed `Links::addLinks()` to `Links::setLinks()` and `Links::addLink()` to `Links::setLink()`

REMOVED:

- Most of the `Links::create*` static methods to simplify creation
- `RelativeLinks` class as it became useless

FIXED:

- `Responder::getDocumentResourceResponse()` was wrongly called statically
- PHP version constraint in composer.json

## 0.8.0 - 2015-11-16

ADDED:

- Attributes of the resource in the request body can be retrieved easier
- Even better support for relative links via the `RelativeLinks` class

CHANGED:

- ID of the hydrated resource also gets validated when it is missing
- The provided `ExceptionFactory` can be used when validating client-generated ID-s for hydration
- Renamed `RequestInterface::getBodyData*` methods to `RequestInterface::getResource*`

FIXED:

- Methods of `TransformerTrait` were intended to be non-static

## 0.7.1 - 2015-10-05

ADDED:

- `ApplicationError` and `ResourceNotFound`
- Mentioning optional Composer dependencies in the readme

## 0.7.0 - 2015-10-04

ADDED:

- A separate responder class
- `ExceptionFactoryInterface` which helps you to fully customize error messages
- `JsonApi::hydrate()` helper method to make hydration easier
- Integrated content negotiation and request/response validation from Woohoo Labs. Yin-Middleware
- Even more extensive documentation

CHANGED:

- JSON API exceptions extend `JsonApiException` thus they can be catched easier
- Documents are moved to `JsonApi\Document` namespace from `JsonApi\Transfomer`
- Refactored transformation to fix inclusion of multiple identical resource objects
- When the data member is missing from the top source, the appropriate exception is thrown

REMOVED:

- Different types of responses (e.g.: `FetchResponse`)

FIXED:

- Compound documents now can't include more than one resource object for each type and id pair
- Request body was always null
- Single resource documents didn't contain the data top-level member unless resource ID was 1

## 0.6.0 - 2015-09-22

ADDED:

- More convenient handling of inappropriate relationship types during hydration
- Much more unit tests (320+ tests, 92% coverage)
- Better and more documentation

CHANGED:

- Simplified relative links
- Included resources are now sorted by type and id
- Renamed `AbstractCompoundDocument` to `AbstractSuccessfulDocument`
- Documents now require a `ResourceTransformerInterface` instance instead of `AbstractResourceTransformer`

FIXED:

- Meta data didn't appear in error objects
- Empty version information appeared in jsonApi object
- Constructors of `ToOneRelationships` and `ToManyRelationships` were messed up
- Getters in `MediaTypeUnacceptable` and `MediaTypeUnsupported` didn't return the media type name
- Pagination objects are now correctly instantiated from query parameters
- Validation of query parameters didn't work
- Getting the list of included relationships didn't work as expected
- Status code of error responses was always "500" when the document contained multiple errors
- Content-Type header media types of responses are now correctly assembled when using extensions
- Fatal error when the hydrated resource type didn't match the acceptable type
- Various issues of pagination providers

## 0.5.0 - 2015-09-11

ADDED:

- Support for much easier generation of pagination links
- Shortcut to get the resource ID in `AbstractSingleResourceDocument`
- Support for relative URI-s

CHANGED:

- Improved transformation performance
- Included resources are now sorted by type instead of ID

REMOVED:

- `RelationshipRequest` became useless, thus it was removed

FIXED:
- Instantiation of `Request` could take significant time
- Sparse fieldsets and inclusion of relationships are now fully compliant with the spec
- Links with null value can be included in the response

## 0.4.2 - 2015-08-27

FIXED:

- Some exceptions had errorous namespaces
- `Request::with*` methods returned an instance of PSR `ServerRequestInterface`
- Validation of the `Content-Type` and the `Accept` headers is now compliant with the spec

## 0.4.0 - 2015-08-26

ADDED:

- Support for proper content negotiation
- Support for validation of query parameters
- Support for retrieving the requested extensions
- Full replacement and removal of relationships can be prohibited
- Exception can be raised when an unrecognized sorting parameter is received

CHANGED:

- `CreateHydrator` was renamed to `AbstractCreateHydrator`
- `UpdateHydrator` was renamed to `AbstractUpdateHydrator`
- `AbstractHydrator` can be used for update and create requests too
- Improved and more extensive documentation

FIXED:

- Meta responses follow the specification

## 0.3.6 - 2015-08-19

REMOVED:

- `TransformableInterface` and `SimpleTransformableInterface` as they were unnecessary

FIXED:

- Fixed issue with possible request body parsing
- The included key is not sent if it is empty
- Do not mutate the original responses
- `LinksTrait` and `MetaTrait` support retrieval of their properties
- The response body is cleared before assembling the response
- Errors now don't contain null fields
- Errors can contain links and a source
- Automatically determine the status code of an error document if it is not explicitly set

## 0.3.0 - 2015-08-16

ADDED:

- Support for creation and update of resources via `Hydrators`
- `JsonApi` class
- Response classes
- `Link::getHref()` method

CHANGED:

- `RequestInterface` extends `PSR\Http\Message\ServerRequestInterface`
- Several methods of `AbstractDocument` became public instead of protected
- Substantially refactored and improved examples

## 0.2.0 - 2015-08-01

ADDED:

- Support for proper and automatic fetching of relationships
- Convenience methods for `AbstractResourceTransformer` to support transformation
- Convenience methods for links and relationships
- Examples about relationships

CHANGED:

- Decoupled `Request` from PSR-7 `ServerRequestInterface`
- Simplified document creation and transformation
- Renamed `Criteria` to `Request` for future purposes
- Renamed `OneToManyTraversableRelationship` to `ToManyRelationship`
- Renamed `OneToOneRelationship` to `ToOneRelationship`

REMOVED:

- `CompulsoryLinks` and `PaginatedLinks`

FIXED:

- Transformation of resource relationships
- Transformation of meta element at the top level
- Transformation of null resources

## 0.1.5 - 2015-07-15

ADDED:

- Examples

FIXED:

- Processing of sparse fieldsets
- Processing of included relationships
- Transformation of JsonApi and meta objects

## 0.1.0 - 2015-07-13

- Initial release

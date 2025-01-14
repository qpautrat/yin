<?php
namespace WoohooLabs\Yin\JsonApi\Request;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use WoohooLabs\Yin\JsonApi\Exception\MediaTypeUnacceptable;
use WoohooLabs\Yin\JsonApi\Exception\MediaTypeUnsupported;
use WoohooLabs\Yin\JsonApi\Exception\QueryParamUnrecognized;
use WoohooLabs\Yin\JsonApi\Hydrator\Relationship\ToManyRelationship;
use WoohooLabs\Yin\JsonApi\Hydrator\Relationship\ToOneRelationship;
use WoohooLabs\Yin\JsonApi\Request\Pagination\CursorPagination;
use WoohooLabs\Yin\JsonApi\Request\Pagination\FixedPagePagination;
use WoohooLabs\Yin\JsonApi\Request\Pagination\OffsetPagination;
use WoohooLabs\Yin\JsonApi\Request\Pagination\PagePagination;
use WoohooLabs\Yin\JsonApi\Schema\ResourceIdentifier;

class Request implements RequestInterface
{
    /**
     * @var \Psr\Http\Message\ServerRequestInterface
     */
    protected $serverRequest;

    /**
     * @var array|null
     */
    protected $includedFields;

    /**
     * @var array|null
     */
    protected $includedRelationships;

    /**
     * @var array|null
     */
    protected $sorting;

    /**
     * @var array|null
     */
    protected $pagination;

    /**
     * @var array|null
     */
    protected $filtering;

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     */
    public function __construct(ServerRequestInterface $request)
    {
        $this->serverRequest = $request;
    }

    /**
     * @throws \WoohooLabs\Yin\JsonApi\Exception\MediaTypeUnsupported
     */
    public function validateContentTypeHeader()
    {
        $invalidHeaderMediaType = $this->getInvalidHeaderMediaType("Content-Type");
        if ($invalidHeaderMediaType !== null) {
            throw new MediaTypeUnsupported($invalidHeaderMediaType);
        }
    }

    /**
     * @throws \WoohooLabs\Yin\JsonApi\Exception\MediaTypeUnacceptable
     */
    public function validateAcceptHeader()
    {
        $invalidHeaderMediaType = $this->getInvalidHeaderMediaType("Accept");
        if ($invalidHeaderMediaType !== null) {
            throw new MediaTypeUnacceptable($invalidHeaderMediaType);
        }
    }

    /**
     * @throws \WoohooLabs\Yin\JsonApi\Exception\QueryParamUnrecognized
     */
    public function validateQueryParams()
    {
        foreach ($this->getQueryParams() as $queryParamName => $queryParamValue) {
            if (preg_match("/^([a-z]+)$/", $queryParamName) &&
                in_array($queryParamName, ["fields", "include", "sort", "page", "filter"]) === false
            ) {
                throw new QueryParamUnrecognized($queryParamName);
            }
        }
    }

    /**
     * @return array
     */
    public function getExtensions()
    {
        return $this->getHeaderExtensions("Content-Type");
    }

    /**
     * @return array
     */
    public function getRequiredExtensions()
    {
        return $this->getHeaderExtensions("Accept");
    }

    /**
     * Returns a list of extensions in a given header, found in the "ext" media type parameter.
     *
     * @param string $headerName
     * @return array
     */
    protected function getHeaderExtensions($headerName)
    {
        $extensions = [];

        $contentTypeHeader = $this->parseMediaTypeHeader($headerName);
        foreach ($contentTypeHeader as $mediaType) {
            if ($mediaType["name"] === "application/vnd.api+json" && isset($mediaType["parameters"]["ext"])) {
                foreach (explode(",", $mediaType["parameters"]["ext"]) as $extension) {
                    $extensions[$extension] = "";
                }
            }
        }

        return array_keys($extensions);
    }

    /**
     * Returns an invalid media type parameter in a given header of the request if present, or null otherwise.
     *
     * @param string $headerName
     * @return string|null
     */
    protected function getInvalidHeaderMediaType($headerName)
    {
        foreach ($this->parseMediaTypeHeader($headerName) as $mediaType) {
            if ($mediaType["name"] === "application/vnd.api+json") {
                foreach ($mediaType["parameters"] as $paramName => $paramValue) {
                    if ($paramName !== "ext" && $paramName !== "supported-ext") {
                        return $mediaType["raw"];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Returns a list of media type information, extracted from a given header in the current request.
     *
     * @param string $headerName
     * @return array
     */
    protected function parseMediaTypeHeader($headerName)
    {
        $mediaTypes = [];

        $mediaTypeNameRegex = '([A-Za-z0-9\._\-\+\/\*]+)';
        $mediaTypeParameterNameRegex = '([A-Za-z0-9_\-\.\+\/\*]+)';
        $mediaTypeParameterValueRegex = '["]{0,1}([A-Za-z0-9_\-\.\+\/\*,]+)["]{0,1}';
        $mediaTypeParameterRegex = $mediaTypeParameterNameRegex.'\s*=\s*'.$mediaTypeParameterValueRegex;
        $mediaTypeRegex = '(?:\s*'.$mediaTypeNameRegex.'\s*(?:(?:[;]|$)\s*'.$mediaTypeParameterRegex.')*?(?:[,]|$))*?';

        foreach ($this->getHeader($headerName) as $headerValue) {
            $parsedMediaType = [];
            preg_match_all('/'.$mediaTypeRegex.'/', $headerValue, $parsedMediaType, PREG_SET_ORDER);
            if (is_array($parsedMediaType) === false) {
                continue;
            }

            foreach ($parsedMediaType as $parsed) {
                $mediaType = ["name" => "", "parameters" => [], "raw" => ""];
                if (isset($parsed[1])) {
                    $mediaType["name"] = $parsed[1];
                    $mediaType["raw"] = trim($parsed[0]);

                    for ($i = 2; isset($parsed[$i]); $i += 2) {
                        $paramName = $parsed[$i];
                        $paramValue = isset($parsed[$i + 1]) ? $parsed[$i + 1] : "";

                        $mediaType["parameters"][$paramName] = $paramValue;
                    }
                    $mediaTypes[] = $mediaType;
                }
            }
        }

        return $mediaTypes;
    }

    protected function setIncludedFields()
    {
        $this->includedFields = [];
        $fields = $this->getQueryParam("fields", []);
        if (is_array($fields) === false) {
            return;
        }

        foreach ($fields as $resourceType => $resourceFields) {
            if (is_string($resourceFields)) {
                $this->includedFields[$resourceType] = array_flip(explode(",", $resourceFields));
            }
        }
    }

    /**
     * @param string $resourceType
     * @return array
     */
    public function getIncludedFields($resourceType)
    {
        if ($this->includedFields === null) {
            $this->setIncludedFields();
        }

        return isset($this->includedFields[$resourceType]) ? array_keys($this->includedFields[$resourceType]) : [];
    }

    /**
     * @param string $resourceType
     * @param string $field
     * @return bool
     */
    public function isIncludedField($resourceType, $field)
    {
        if ($this->includedFields === null) {
            $this->setIncludedFields();
        }

        if (array_key_exists($resourceType, $this->includedFields) === false) {
            return true;
        }

        if (empty($this->includedFields[$resourceType]) === true) {
            return false;
        }

        return isset($this->includedFields[$resourceType][$field]);
    }

    /**
     * @return array
     */
    protected function setIncludedRelationships()
    {
        $this->includedRelationships = [];

        $includeQueryParam = $this->getQueryParam("include", "");
        if ($includeQueryParam === "") {
            return;
        }

        $relationshipNames = explode(",", $includeQueryParam);
        foreach ($relationshipNames as $relationship) {
            $relationship = ".$relationship.";
            $length = strlen($relationship);
            $dot1 = 0;

            while ($dot1 < $length - 1) {
                $dot2 = strpos($relationship, ".", $dot1 + 1);
                $path = substr($relationship, 1, $dot1 > 0 ? $dot1 - 1 : 0);
                $name = substr($relationship, $dot1 + 1, $dot2 - $dot1 - 1);

                if (isset($this->includedRelationships[$path]) === false) {
                    $this->includedRelationships[$path] = [];
                }
                $this->includedRelationships[$path][$name] = $name;

                $dot1 = $dot2;
            };
        }
    }

    /**
     * @return bool
     */
    public function hasIncludedRelationships()
    {
        if ($this->includedRelationships === null) {
            $this->setIncludedRelationships();
        }

        return empty($this->includedRelationships) === false;
    }

    /**
     * @param string $baseRelationshipPath
     * @return array
     */
    public function getIncludedRelationships($baseRelationshipPath)
    {
        if ($this->includedRelationships === null) {
            $this->setIncludedRelationships();
        }

        if (isset($this->includedRelationships[$baseRelationshipPath])) {
            return array_values($this->includedRelationships[$baseRelationshipPath]);
        } else {
            return [];
        }
    }

    /**
     * @param string $baseRelationshipPath
     * @param string $relationshipName
     * @param array $defaultRelationships
     * @return bool
     */
    public function isIncludedRelationship($baseRelationshipPath, $relationshipName, array $defaultRelationships)
    {
        if ($this->includedRelationships === null) {
            $this->setIncludedRelationships();
        }

        if ($this->getQueryParam("include") === "") {
            return false;
        }

        if (empty($this->includedRelationships) && array_key_exists($relationshipName, $defaultRelationships)) {
            return true;
        }

        return isset($this->includedRelationships[$baseRelationshipPath][$relationshipName]);
    }

    protected function setSorting()
    {
        $sortingQueryParam = $this->getQueryParam("sort", "");
        if ($sortingQueryParam === "") {
            $this->sorting = [];
            return;
        }

        $sorting = explode(",", $sortingQueryParam);
        $this->sorting = is_array($sorting) ? $sorting : [];
    }

    /**
     * @return array
     */
    public function getSorting()
    {
        if ($this->sorting === null) {
            $this->setSorting();
        }

        return $this->sorting;
    }

    protected function setPagination()
    {
        $pagination =  $this->getQueryParam("page", null);
        $this->pagination = is_array($pagination) ? $pagination : [];
    }

    /**
     * @return array
     */
    public function getPagination()
    {
        if ($this->pagination === null) {
            $this->setPagination();
        }

        return $this->pagination;
    }

    /**
     * @param mixed $defaultPage
     * @return \WoohooLabs\Yin\JsonApi\Request\Pagination\FixedPagePagination
     */
    public function getFixedPageBasedPagination($defaultPage = null)
    {
        return FixedPagePagination::fromPaginationQueryParams($this->getPagination(), $defaultPage);
    }

    /**
     * @param mixed $defaultPage
     * @param mixed $defaultSize
     * @return \WoohooLabs\Yin\JsonApi\Request\Pagination\PagePagination
     */
    public function getPageBasedPagination($defaultPage = null, $defaultSize = null)
    {
        return PagePagination::fromPaginationQueryParams($this->getPagination(), $defaultPage, $defaultSize);
    }

    /**
     * @param mixed $defaultOffset
     * @param mixed $defaultLimit
     * @return \WoohooLabs\Yin\JsonApi\Request\Pagination\OffsetPagination
     */
    public function getOffsetBasedPagination($defaultOffset = null, $defaultLimit = null)
    {
        return OffsetPagination::fromPaginationQueryParams($this->getPagination(), $defaultOffset, $defaultLimit);
    }

    /**
     * @param mixed $defaultCursor
     * @return \WoohooLabs\Yin\JsonApi\Request\Pagination\CursorPagination
     */
    public function getCursorBasedPagination($defaultCursor = null)
    {
        return CursorPagination::fromPaginationQueryParams($this->getPagination(), $defaultCursor);
    }

    protected function setFiltering()
    {
        $filtering = $this->getQueryParam("filter", []);
        $this->filtering = is_array($filtering) ? $filtering : [];
    }

    /**
     * @return array
     */
    public function getFiltering()
    {
        if ($this->filtering === null) {
            $this->setFiltering();
        }

        return $this->filtering;
    }

    /**
     * @param string $param
     * @param mixed $default
     * @return mixed
     */
    public function getFilteringParam($param, $default = null)
    {
        $filtering = $this->getFiltering();

        return isset($filtering[$param]) ? $filtering[$param] : $default;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return array|string|null
     */
    public function getQueryParam($name, $default = null)
    {
        $queryParams = $this->serverRequest->getQueryParams();

        return isset($queryParams[$name]) ? $queryParams[$name] : $default;
    }

    /**
     * Returns a query parameter with a name of $name if it is present in the request, or the $default value otherwise.
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function withQueryParam($name, $value)
    {
        $self = clone $this;
        $queryParams = $this->serverRequest->getQueryParams();
        $queryParams[$name] = $value;
        $self->serverRequest = $this->serverRequest->withQueryParams($queryParams);
        $self->initializeParsedQueryParams();
        return $self;
    }

    protected function initializeParsedQueryParams()
    {
        $this->includedFields = null;
        $this->includedRelationships = null;
        $this->sorting = null;
        $this->pagination = null;
        $this->filtering = null;
    }

    /**
     * @return array|null
     */
    public function getResource()
    {
        $body = $this->getParsedBody();
        return isset($body["data"])? $body["data"] : null;
    }

    /**
     * @return string|null
     */
    public function getResourceType()
    {
        $data = $this->getResource();

        return isset($data["type"]) ? $data["type"] : null;
    }

    /**
     * @return string|null
     */
    public function getResourceId()
    {
        $data = $this->getResource();

        return isset($data["id"]) ? $data["id"] : null;
    }

    /**
     * @return array
     */
    public function getResourceAttributes()
    {
        $data = $this->getResource();

        return isset($data["attributes"]) ? $data["attributes"] : [];
    }

    /**
     * @param string $attribute
     * @param mixed $default
     * @return mixed|null
     */
    public function getResourceAttribute($attribute, $default = null)
    {
        $attributes = $this->getResourceAttributes();

        return isset($attributes[$attribute]) ? $attributes[$attribute] : $default;
    }

    /**
     * @param string $relationship
     * @return \WoohooLabs\Yin\JsonApi\Hydrator\Relationship\ToOneRelationship|null
     */
    public function getResourceToOneRelationship($relationship)
    {
        $data = $this->getResource();

        if (isset($data["relationships"][$relationship]["data"]) === false) {
            return null;
        }

        return new ToOneRelationship(ResourceIdentifier::fromArray($data["relationships"][$relationship]["data"]));
    }

    /**
     * @param string $relationship
     * @return \WoohooLabs\Yin\JsonApi\Hydrator\Relationship\ToManyRelationship|null
     */
    public function getResourceToManyRelationship($relationship)
    {
        $data = $this->getResource();

        if (isset($data["relationships"][$relationship]["data"]) === false) {
            return null;
        }

        $resourceIdentifiers = [];
        foreach ($data["relationships"][$relationship]["data"] as $item) {
            $resourceIdentifiers[] = ResourceIdentifier::fromArray($item);
        }

        return new ToManyRelationship($resourceIdentifiers);
    }

    /**
     * @inheritDoc
     */
    public function getProtocolVersion()
    {
        return $this->serverRequest->getProtocolVersion();
    }

    /**
     * @inheritDoc
     */
    public function withProtocolVersion($version)
    {
        $self = clone $this;
        $self->serverRequest = $this->serverRequest->withProtocolVersion($version);
        return $self;
    }

    /**
     * @inheritDoc
     */
    public function getHeaders()
    {
        return $this->serverRequest->getHeaders();
    }

    /**
     * @inheritDoc
     */
    public function hasHeader($name)
    {
        return $this->serverRequest->hasHeader($name);
    }

    /**
     * @inheritDoc
     */
    public function getHeader($name)
    {
        return $this->serverRequest->getHeader($name);
    }

    /**
     * @inheritDoc
     */
    public function getHeaderLine($name)
    {
        return $this->serverRequest->getHeaderLine($name);
    }

    /**
     * @inheritDoc
     */
    public function withHeader($name, $value)
    {
        $self = clone $this;
        $self->serverRequest = $this->serverRequest->withHeader($name, $value);
        return $self;
    }

    /**
     * @inheritDoc
     */
    public function withAddedHeader($name, $value)
    {
        $self = clone $this;
        $self->serverRequest = $this->serverRequest->withAddedHeader($name, $value);
        return $self;
    }

    /**
     * @inheritDoc
     */
    public function withoutHeader($name)
    {
        $self = clone $this;
        $self->serverRequest = $this->serverRequest->withoutHeader($name);
        return $self;
    }

    /**
     * @inheritDoc
     */
    public function getBody()
    {
        return $this->serverRequest->getBody();
    }

    /**
     * @inheritDoc
     */
    public function withBody(StreamInterface $body)
    {
        $self = clone $this;
        $self->serverRequest = $this->serverRequest->withBody($body);
        return $self;
    }

    /**
     * @inheritDoc
     */
    public function getRequestTarget()
    {
        return $this->serverRequest->getRequestTarget();
    }

    /**
     * @inheritDoc
     */
    public function withRequestTarget($requestTarget)
    {
        $self = clone $this;
        $self->serverRequest = $this->serverRequest->withRequestTarget($requestTarget);
        return $self;
    }

    /**
     * @inheritDoc
     */
    public function getMethod()
    {
        return $this->serverRequest->getMethod();
    }

    /**
     * @inheritDoc
     */
    public function withMethod($method)
    {
        $self = clone $this;
        $self->serverRequest = $this->serverRequest->withMethod($method);
        return $self;
    }

    /**
     * @inheritDoc
     */
    public function getUri()
    {
        return $this->serverRequest->getUri();
    }

    /**
     * @inheritDoc
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $self = clone $this;
        $self->serverRequest = $this->serverRequest->withUri($uri, $preserveHost);
        return $self;
    }

    /**
     * @inheritDoc
     */
    public function getServerParams()
    {
        return $this->serverRequest->getServerParams();
    }

    /**
     * @inheritDoc
     */
    public function getCookieParams()
    {
        return $this->serverRequest->getCookieParams();
    }

    /**
     * @inheritDoc
     */
    public function withCookieParams(array $cookies)
    {
        $self = clone $this;
        $self->serverRequest = $this->serverRequest->withCookieParams($cookies);
        return $self;
    }

    /**
     * @inheritDoc
     */
    public function getQueryParams()
    {
        return $this->serverRequest->getQueryParams();
    }

    /**
     * @inheritDoc
     */
    public function withQueryParams(array $query)
    {
        $self = clone $this;
        $self->serverRequest = $this->serverRequest->withQueryParams($query);
        $self->initializeParsedQueryParams();
        return $self;
    }

    /**
     * @inheritDoc
     */
    public function getUploadedFiles()
    {
        return $this->serverRequest->getUploadedFiles();
    }

    /**
     * @inheritDoc
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        $self = clone $this;
        $self->serverRequest = $this->serverRequest->withUploadedFiles($uploadedFiles);
        return $self;
    }

    /**
     * @inheritDoc
     */
    public function getParsedBody()
    {
        $content = $this->serverRequest->getBody()->getContents();
        if ($content && empty($this->serverRequest->getParsedBody())) {
            $this->serverRequest = $this->serverRequest->withParsedBody(
                json_decode($content, true)
            );
        }

        return $this->serverRequest->getParsedBody();
    }

    /**
     * @inheritDoc
     */
    public function withParsedBody($data)
    {
        $self = clone $this;
        $self->serverRequest = $this->serverRequest->withParsedBody($data);
        return $self;
    }

    /**
     * @inheritDoc
     */
    public function getAttributes()
    {
        return $this->serverRequest->getAttributes();
    }

    /**
     * @inheritDoc
     */
    public function getAttribute($name, $default = null)
    {
        return $this->serverRequest->getAttribute($name, $default);
    }

    /**
     * @inheritDoc
     */
    public function withAttribute($name, $value)
    {
        $self = clone $this;
        $self->serverRequest = $this->serverRequest->withAttribute($name, $value);
        return $self;
    }

    /**
     * @inheritDoc
     */
    public function withoutAttribute($name)
    {
        $self = clone $this;
        $self->serverRequest = $this->serverRequest->withoutAttribute($name);
        return $self;
    }
}

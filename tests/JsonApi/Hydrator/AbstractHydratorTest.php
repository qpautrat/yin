<?php
namespace WoohooLabsTest\Yin\JsonApi\Hydrator;

use PHPUnit_Framework_TestCase;
use WoohooLabs\Yin\JsonApi\Exception\ExceptionFactory;
use WoohooLabs\Yin\JsonApi\Hydrator\Relationship\ToManyRelationship;
use WoohooLabs\Yin\JsonApi\Hydrator\Relationship\ToOneRelationship;
use WoohooLabs\Yin\JsonApi\Request\Request;
use WoohooLabsTest\Yin\JsonApi\Utils\StubHydrator;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Stream;

class AbstractHydratorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @expectedException \WoohooLabs\Yin\JsonApi\Exception\ResourceTypeMissing
     */
    public function validateTypeWhenMissing()
    {
        $body = [
            "data" => []
        ];

        $hydrator = $this->createHydrator();
        $hydrator->hydrateForCreate($this->createRequest($body), new ExceptionFactory(), []);
    }

    /**
     * @test
     * @expectedException \WoohooLabs\Yin\JsonApi\Exception\ResourceTypeUnacceptable
     */
    public function validateTypeWhenUnacceptableAndOnlyOneAcceptable()
    {
        $body = [
            "data" => [
                "type" => "elephant"
            ]
        ];

        $hydrator = $this->createHydrator("fox");
        $hydrator->hydrateForCreate($this->createRequest($body), new ExceptionFactory(), []);
    }

    /**
     * @test
     * @expectedException \WoohooLabs\Yin\JsonApi\Exception\ResourceTypeUnacceptable
     */
    public function validateTypeWhenUnacceptableAndMoreAcceptable()
    {
        $body = [
            "data" => [
                "type" => "elephant"
            ]
        ];

        $hydrator = $this->createHydrator(["fox", "wolf"]);
        $hydrator->hydrateForUpdate($this->createRequest($body), new ExceptionFactory(), []);
    }

    /**
     * @test
     */
    public function hydrateAttributesWhenEmpty()
    {
        $body = [
            "data" => [
                "type" => "elephant",
                "id" => "1"
            ]
        ];

        $hydrator = $this->createHydrator("elephant");
        $domainObject = $hydrator->hydrateForUpdate($this->createRequest($body), new ExceptionFactory(), []);
        $this->assertEquals([], $domainObject);
    }

    /**
     * @test
     */
    public function hydrateAttributesWhenHydratorEmpty()
    {
        $body = [
            "data" => [
                "type" => "elephant",
                "id" => "1",
                "attributes" => [
                    "height" => 2.5
                ]
            ]
        ];
        $attributeHydrator = [
            "weight" => function (array &$elephant, $attribute) {
                $elephant["weight"] = $attribute;
            }
        ];

        $hydrator = $this->createHydrator("elephant", $attributeHydrator);
        $domainObject = $hydrator->hydrateForUpdate($this->createRequest($body), new ExceptionFactory(), []);
        $this->assertEquals([], $domainObject);
    }

    /**
     * @test
     */
    public function hydrateAttributesWhenHydratorReturnByReference()
    {
        $weight = 1000;
        $body = [
            "data" => [
                "type" => "elephant",
                "id" => "1",
                "attributes" => [
                    "weight" => $weight
                ]
            ]
        ];
        $attributeHydrator = [
            "weight" => function (array &$elephant, $attribute) {
                $elephant["weight"] = $attribute;
            }
        ];

        $hydrator = $this->createHydrator("elephant", $attributeHydrator);
        $domainObject = $hydrator->hydrateForUpdate($this->createRequest($body), new ExceptionFactory(), []);
        $this->assertEquals(["weight" => $weight], $domainObject);
    }

    /**
     * @test
     */
    public function hydrateAttributesWhenHydratorReturnByValue()
    {
        $weight = 1000;
        $body = [
            "data" => [
                "type" => "elephant",
                "id" => "1",
                "attributes" => [
                    "weight" => $weight
                ]
            ]
        ];
        $attributeHydrator = [
            "weight" => function (array $elephant, $attribute) {
                $elephant["weight"] = $attribute;
                return $elephant;
            }
        ];

        $hydrator = $this->createHydrator("elephant", $attributeHydrator);
        $domainObject = $hydrator->hydrateForUpdate($this->createRequest($body), new ExceptionFactory(), []);
        $this->assertEquals(["weight" => $weight], $domainObject);
    }

    /**
     * @test
     */
    public function hydrateRelationshipsWhenHydratorEmpty()
    {
        $body = [
            "data" => [
                "type" => "elephant",
                "id" => "1",
                "relationships" => [
                    "parents" => []
                ]
            ]
        ];
        $relationshipHydrator = [
            "children" => function (array &$elephant, ToManyRelationship $children) {
                $elephant["children"] = ["Dumbo", "Mambo"];
            }
        ];

        $hydrator = $this->createHydrator("elephant", [], $relationshipHydrator);
        $domainObject = $hydrator->hydrateForUpdate($this->createRequest($body), new ExceptionFactory(), []);
        $this->assertEquals([], $domainObject);
    }

    /**
     * @test
     * @expectedException \WoohooLabs\Yin\JsonApi\Exception\RelationshipTypeInappropriate
     */
    public function hydrateRelationshipsWhenCardinalityInappropriate()
    {
        $body = [
            "data" => [
                "type" => "elephant",
                "id" => "1",
                "relationships" => [
                    "children" => [
                        "data" => [
                            "type" => "elephant",
                            "id" => "2"
                        ]
                    ]
                ]
            ]
        ];
        $relationshipHydrator = [
            "children" => function (array &$elephant, ToManyRelationship $children) {
                $elephant["children"] = $children->getResourceIdentifiers();
            }
        ];

        $hydrator = $this->createHydrator("elephant", [], $relationshipHydrator);
        $hydrator->hydrateForUpdate($this->createRequest($body), new ExceptionFactory(), []);
    }

    /**
     * @test
     * @expectedException \WoohooLabs\Yin\JsonApi\Exception\RelationshipTypeInappropriate
     */
    public function hydrateRelationshipsWhenCardinalityInappropriate2()
    {
        $body = [
            "data" => [
                "type" => "elephant",
                "id" => "1",
                "relationships" => [
                    "children" => [
                        "data" => [
                            [
                                "type" => "elephant",
                                "id" => "2"
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $relationshipHydrator = [
            "children" => function (array &$elephant, ToOneRelationship $children) {
                $elephant["children"] = $children->getResourceIdentifier();
            }
        ];

        $hydrator = $this->createHydrator("elephant", [], $relationshipHydrator);
        $hydrator->hydrateForUpdate($this->createRequest($body), new ExceptionFactory(), []);
    }

    /**
     * @test
     */
    public function hydrateRelationshipsWhenExpectedCardinalityIsNotSet()
    {
        $body = [
            "data" => [
                "type" => "elephant",
                "id" => "1",
                "relationships" => [
                    "children" => [
                        "data" => [
                            [
                                "type" => "elephant",
                                "id" => "2"
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $relationshipHydrator = [
            "children" => function (array &$elephant, $children) {
                $elephant["children"] = "Dumbo";
            }
        ];

        $hydrator = $this->createHydrator("elephant", [], $relationshipHydrator);
        $domainObject = $hydrator->hydrateForUpdate($this->createRequest($body), new ExceptionFactory(), []);
        $this->assertEquals(["children" => "Dumbo"], $domainObject);
    }

    /**
     * @test
     */
    public function hydrateRelationships()
    {
        $body = [
            "data" => [
                "type" => "elephant",
                "id" => "1",
                "relationships" => [
                    "owner" => [
                        "data" => [
                            "type" => "person",
                            "id" => "1"
                        ]
                    ],
                    "children" => [
                        "data" => [
                            [
                                "type" => "elephant",
                                "id" => "2"
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $relationshipHydrator = [
            "owner" => function (array $elephant, ToOneRelationship $owner) {
                $elephant["owner"] = $owner->getResourceIdentifier()->getId();
                return $elephant;
            },
            "children" => function (array &$elephant, ToManyRelationship $children) {
                $elephant["children"] = $children->getResourceIdentifierIds();
            }
        ];

        $hydrator = $this->createHydrator("elephant", [], $relationshipHydrator);
        $domainObject = $hydrator->hydrateForUpdate($this->createRequest($body), new ExceptionFactory(), []);
        $this->assertEquals(["owner" => "1", "children" => ["2"]], $domainObject);
    }

    private function createRequest(array $body)
    {
        $psrRequest = new ServerRequest();
        $psrRequest = $psrRequest
            ->withParsedBody($body)
            ->withBody(new Stream("php://memory", "rw"));
        $psrRequest->getBody()->write(json_encode($body));

        $request = new Request($psrRequest);

        return $request;
    }

    /**
     * @param string|array $acceptedType
     * @param array $attributeHydrator
     * @param array $relationshipHydrator
     * @return \WoohooLabs\Yin\JsonApi\Hydrator\AbstractHydrator
     */
    private function createHydrator($acceptedType = "", array $attributeHydrator = [], array $relationshipHydrator = [])
    {
        return new StubHydrator($acceptedType, $attributeHydrator, $relationshipHydrator);
    }
}

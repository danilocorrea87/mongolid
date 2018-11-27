<?php
namespace Mongolid\Model;

use Mockery as m;
use MongoDB\BSON\ObjectId;
use Mongolid\Cursor\EmbeddedCursor;
use Mongolid\Query\Builder;
use Mongolid\TestCase;

class HasRelationsTraitTest extends TestCase
{
    /**
     * @dataProvider referenceScenarios
     */
    public function testShouldReferenceOne($fieldValue, $expectedQuery)
    {
        // Set
        $model = new UserStub();
        $model->refOne = $fieldValue;

        $builder = $this->instance(Builder::class, m::mock(Builder::class)->makePartial());
        $expectedQuery = $expectedQuery['referencesOne'];
        $expected = new RelatedStub();

        // Expectations
        $builder->expects()
            ->first(m::type(RelatedStub::class), $expectedQuery, [])
            ->andReturn($expected);

        // Actions
        $result = $model->relationReferencesOne;

        // Assertions
        $this->assertSame($expected, $result);
    }

    /**
     * @dataProvider referenceScenarios
     */
    public function testShouldReferenceMany($fieldValue, $expectedQuery)
    {
        // Set
        $model = new UserStub();
        $model->refMany = $fieldValue;

        $builder = $this->instance(Builder::class, m::mock(Builder::class)->makePartial());
        $expectedQuery = $expectedQuery['referencesMany'];
        $expected = new EmbeddedCursor([]);

        // Expectations
        $builder->expects()
            ->where(m::type(RelatedStub::class), $expectedQuery, [])
            ->andReturn($expected);

        // Actions
        $result = $model->relationReferencesMany;

        // Assertions
        $this->assertSame($expected, $result);
    }

    /**
     * @dataProvider embedsScenarios
     */
    public function testShouldEmbedsOne($fieldValue, $expectedItems)
    {
        // Set
        $model = new UserStub();
        $model->embOne = $fieldValue;

        $expectedItems = $expectedItems['embedsOne'];

        // Actions
        $result = $model->relationEmbedsOne;

        // Assertions
        $this->assertInstanceOf(RelatedStub::class, $result);
        $this->assertSame($expectedItems, $result);
    }

    /**
     * @dataProvider embedsScenarios
     */
    public function testShouldEmbedsMany($fieldValue, $expectedItems)
    {
        // Set
        $model = new UserStub();
        $model->embMany = $fieldValue;

        $expectedItems = $expectedItems['embedsMany'];

        // Actions
        $result = $model->relationEmbedsMany;

        // Assertions
        $this->assertInstanceOf(EmbeddedCursor::class, $result);
        $this->assertContainsOnlyInstancesOf(RelatedStub::class, $result->all());
        $this->assertSame($expectedItems, $result->all());
    }

    public function referenceScenarios(): array
    {
        return [
            'referenced by string id' => [
                'fieldValue' => 'abc123',
                'expectedQuery' => [
                    'referencesOne' => ['_id' => 'abc123'],
                    'referencesMany' => ['_id' => ['$in' => ['abc123']]],
                ],
            ],
            'referenced by objectId represented as string' => [
                'fieldValue' => '577afb0b4d3cec136058fa82',
                'expectedQuery' => [
                    'referencesOne' => ['_id' => new ObjectId('577afb0b4d3cec136058fa82')],
                    'referencesMany' => ['_id' => ['$in' => [new ObjectId('577afb0b4d3cec136058fa82')]]],
                ],
            ],
            'referenced by an objectId itself' => [
                'fieldValue' => new ObjectId('577afb0b4d3cec136058fa82'),
                'expectedQuery' => [
                    'referencesOne' => ['_id' => new ObjectId('577afb0b4d3cec136058fa82')],
                    'referencesMany' => ['_id' => ['$in' => [new ObjectId('577afb0b4d3cec136058fa82')]]],
                ],
            ],
            'series of objectIds' => [
                'fieldValue' => [new ObjectId('577afb0b4d3cec136058fa82'), new ObjectId('577afb7e4d3cec136258fa83')],
                'expectedQuery' => [
                    'referencesOne' => ['_id' => new ObjectId('577afb0b4d3cec136058fa82')],
                    'referencesMany' => [
                        '_id' => [
                            '$in' => [
                                new ObjectId('577afb0b4d3cec136058fa82'),
                                new ObjectId('577afb7e4d3cec136258fa83'),
                            ],
                        ],
                    ],
                ],
            ],
            'series of objectIds as strings' => [
                'fieldValue' => ['577afb0b4d3cec136058fa82', '577afb7e4d3cec136258fa83'],
                'expectedQuery' => [
                    'referencesOne' => ['_id' => new ObjectId('577afb0b4d3cec136058fa82')],
                    'referencesMany' => [
                        '_id' => [
                            '$in' => [
                                new ObjectId('577afb0b4d3cec136058fa82'),
                                new ObjectId('577afb7e4d3cec136258fa83'),
                            ],
                        ],
                    ],
                ],
            ],
            'Model referenced with null' => [
                'fieldValue' => null,
                'expectedQuery' => [
                    'referencesOne' => ['_id' => null],
                    'referencesMany' => ['_id' => ['$in' => []]],
                ],
            ],
        ];
    }

    public function embedsScenarios(): array
    {
        $model1 = new RelatedStub();
        $model1->_id = 12345;
        $model1->name = 'John';
        $model1->syncOriginalDocumentAttributes();

        $model2 = new RelatedStub();
        $model2->_id = 67890;
        $model2->name = 'Bob';
        $model2->syncOriginalDocumentAttributes();

        return [
            'A single embedded document' => [
                'fieldValue' => $model1,
                'expectedItems' => [
                    'embedsOne' => $model1,
                    'embedsMany' => [$model1],
                ],
            ],
            'Many embedded documents' => [
                'fieldValue' => [$model1, $model2],
                'expectedItems' => [
                    'embedsOne' => $model1,
                    'embedsMany' => [$model1, $model2],
                ],
            ],
        ];
    }
}

class UserStub extends AbstractModel
{
    /**
     * {@inheritdoc}
     */
    protected $collection = 'users';

    public function relationReferencesOne()
    {
        return $this->referencesOne(RelatedStub::class, 'refOne');
    }

    public function relationReferencesMany()
    {
        return $this->referencesMany(RelatedStub::class, 'refMany');
    }

    public function relationEmbedsOne()
    {
        return $this->embedsOne(RelatedStub::class, 'embOne');
    }

    public function relationEmbedsMany()
    {
        return $this->embedsMany(RelatedStub::class, 'embMany');
    }
}

class RelatedStub extends AbstractModel
{
    /**
     * {@inheritdoc}
     */
    protected $collection = 'related';
}
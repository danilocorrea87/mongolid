<?php
namespace Mongolid\DataMapper;

use MongoDB\BSON\ObjectId;
use MongoDB\Driver\BulkWrite as MongoBulkWrite;
use MongoDB\Driver\WriteConcern;
use Mongolid\Connection\Connection;
use Mongolid\Container\Ioc;
use Mongolid\Schema\AbstractSchema;
use Mongolid\Schema\HasSchemaInterface;

/**
 * This class is meant to provide a better API for handling
 * with bulk operations.
 *
 * It's an incomplete and highly opinionated abstraction
 * but yet flexible, since you are able to access the
 * driver's API and can work with both ActiveRecord and
 * DataMapper, since this class relies on the Schema and
 * both classes implements HasSchemaInterface.
 */
class BulkWrite
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var MongoBulkWrite
     */
    protected $bulkWrite;

    /**
     * @var AbstractSchema
     */
    protected $schema;

    public function __construct(HasSchemaInterface $entity)
    {
        $this->setBulkWrite(new MongoBulkWrite(['ordered' => false]));
        $this->schema = $entity->getSchema();
    }

    /**
     * Get the BulkWrite object to perform other operations
     * not covered by this class.
     *
     * @return MongoBulkWrite
     */
    public function getBulkWrite()
    {
        return $this->bulkWrite;
    }

    /**
     * Set BulkWrite object that will receive all operations
     * and later be executed.
     *
     * @return $this
     */
    public function setBulkWrite(MongoBulkWrite $bulkWrite)
    {
        $this->bulkWrite = $bulkWrite;

        return $this;
    }

    /**
     * Add an `update` operation to the Bulk, where only one record is updated, by `_id`.
     * Be aware that working with multiple levels of nesting on `$dataToSet` may have
     * an undesired behavior that could lead to data loss on a specific key.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/update/set/#set-top-level-fields
     *
     * @param ObjectId|string $id
     * @param array           $dataToSet
     * @param array           $options
     */
    public function updateOne(
        $id,
        array $dataToSet,
        array $options = ['upsert' => true],
        string $operator = '$set'
    ) {
        return $this->getBulkWrite()->update(
            ['_id' => $id],
            [$operator => $dataToSet],
            $options
        );
    }

    /**
     * Execute the BulkWrite, using connection.
     * The collection is inferred from entity's collection name.
     *
     * @param int $writeConcern
     *
     * @return \MongoDB\Driver\WriteResult
     */
    public function execute($writeConcern = 1)
    {
        $connection = Ioc::make(Connection::class);
        $manager = $connection->getRawManager();

        $namespace = $connection->defaultDatabase.'.'.$this->schema->collection;

        return $manager->executeBulkWrite(
            $namespace,
            $this->getBulkWrite(),
            ['writeConcern' => new WriteConcern($writeConcern)]
        );
    }
}

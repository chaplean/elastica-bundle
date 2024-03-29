<?php

namespace FOS\ElasticaBundle\Persister;

use Psr\Log\LoggerInterface;
use Elastica\Exception\BulkException;
use FOS\ElasticaBundle\Transformer\ModelToElasticaTransformerInterface;
use Elastica\Type;
use Elastica\Document;

/**
 * Inserts, replaces and deletes single documents in an elastica type
 * Accepts domain model objects and converts them to elastica documents.
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
class ObjectPersister implements ObjectPersisterInterface
{
    protected $type;
    protected $transformer;
    protected $objectClass;
    protected $fields;
    protected $logger;

    /**
     * @param Type                                $type
     * @param ModelToElasticaTransformerInterface $transformer
     * @param string                              $objectClass
     * @param array                               $fields
     */
    public function __construct(Type $type, ModelToElasticaTransformerInterface $transformer, $objectClass, array $fields)
    {
        $this->type            = $type;
        $this->transformer     = $transformer;
        $this->objectClass     = $objectClass;
        $this->fields          = $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function handlesObject($object)
    {
        return $object instanceof $this->objectClass;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Log exception if logger defined for persister belonging to the current listener, otherwise re-throw.
     *
     * @param BulkException $e
     *
     * @throws BulkException
     */
    private function log(BulkException $e)
    {
        if (! $this->logger) {
            throw $e;
        }

        $this->logger->error($e);
    }

    /**
     * {@inheritdoc}
     */
    public function insertOne($object)
    {
        $this->insertMany(array($object));
    }

    /**
     * {@inheritdoc}
     */
    public function replaceOne($object)
    {
        $this->replaceMany(array($object));
    }

    /**
     * {@inheritdoc}
     */
    public function deleteOne($object)
    {
        $this->deleteMany(array($object));
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($id)
    {
        $this->deleteManyByIdentifiers(array($id));
    }

    /**
     * {@inheritdoc}
     */
    public function insertMany(array $objects)
    {
        $documents = array();
        foreach ($objects as $object) {
            $documents[] = $this->transformToElasticaDocument($object);
        }
        try {
            $this->type->addDocuments($documents);
        } catch (BulkException $e) {
            $this->log($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function replaceMany(array $objects)
    {
        $documents = array();
        foreach ($objects as $object) {
            $document = $this->transformToElasticaDocument($object);
            $document->setDocAsUpsert(true);
            $documents[] = $document;
        }

        try {
            $this->type->updateDocuments($documents);
        } catch (BulkException $e) {
            $this->log($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMany(array $objects)
    {
        $documents = array();
        foreach ($objects as $object) {
            $documents[] = $this->transformToElasticaDocument($object);
        }
        try {
            $this->type->deleteDocuments($documents);
        } catch (BulkException $e) {
            $this->log($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteManyByIdentifiers(array $identifiers, $routing = false)
    {
        try {
            $this->type->getIndex()->getClient()->deleteIds($identifiers, $this->type->getIndex(), $this->type, $routing);
        } catch (BulkException $e) {
            $this->log($e);
        }
    }

    /**
     * Transforms an object to an elastica document.
     *
     * @param object $object
     *
     * @return Document the elastica document
     */
    public function transformToElasticaDocument($object)
    {
        return $this->transformer->transform($object, $this->fields);
    }
}

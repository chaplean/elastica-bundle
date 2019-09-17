<?php

namespace FOS\ElasticaBundle\Exception;

class MissingDoctrineObjectException extends \RuntimeException
{
    /**
     * @var integer[]|array
     */
    private $missingObjects;

    /**
     * @param string      $message
     * @param array|null  $missingObjects
     */
    public function __construct($message, $missingObjects = null)
    {
        parent::__construct($message);

        if ($missingObjects !== null) {
            $this->missingObjects = $missingObjects;
        }
    }

    /**
     * @return integer[]|array|null
     */
    public function getMissingObjects()
    {
        return $this->missingObjects;
    }
}

<?php

namespace Doctrine\ORM\ODMAdapter;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ORM\ODMAdapter\Exception\MappingException;
use Doctrine\ORM\ODMAdapter\Mapping\ClassMetadata;
use Doctrine\ORM\ODMAdapter\Mapping\ClassMetadataFactory;

/**
 * The ObjectAdapterManager will combine persistence operation
 * on orm and odm and provide a doctrine common interface.
 *
 * @author Maximilian Berghoff <Maximilian.Berghoff@gmx.de>
 */
class ObjectAdapterManager
{
    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * @var ClassMetadataFactory
     */
    protected $classMetdataFactory;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * Both managers needs to be injected in service definition.
     *
     * @param DocumentManager $dm
     * @param ObjectManager $em
     * @param Configuration $config
     * @param EventManager $evm
     */
    public function __construct(DocumentManager $dm, ObjectManager $em, Configuration $config = null, EventManager $evm = null)
    {
        $this->configuration = $config?: new Configuration();
        $this->eventManager = $evm ?: new EventManager();
        $this->dm = $dm;
        $this->em = $em;

        $classMetadataFactoryClass = $this->configuration->getClassMetadataFactoryName();
        $this->classMetdataFactory = new $classMetadataFactoryClass($this);
    }

    /**
     * Factory method for a Document Manager.
     *
     * @param DocumentManager $dm
     * @param ObjectManager $em
     * @param Configuration $configuration
     * @param EventManager $evm
     *
     * @return ObjectAdapterManager
     */
    public static function create(DocumentManager $dm, ObjectManager $em, Configuration $configuration = null, EventManager $evm = null)
    {
        return new self($dm, $em, $configuration, $evm);
    }

    public function bindDocument($object)
    {
        // todo implement that
    }

    public function updateBoundDocument($object)
    {
        // todo implement that
    }

    public function removeDocument($object)
    {

    }

    /**
     * @param  string        $className
     * @return ClassMetadata
     */
    public function getClassMetadata($className)
    {
        return $this->classMetdataFactory->getMetadataFor($className);
    }

    /**
     * @param Configuration $config
     */
    public function setConfiguration($config)
    {
        $this->configuration = $config;
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param EventManager $eventManager
     */
    public function setEventManager($eventManager)
    {
        $this->eventManager = $eventManager;
    }

    /**
     * @return EventManager
     */
    public function getEventManager()
    {
        return $this->eventManager;
    }

    /**
     * @return DocumentManager
     */
    public function getDocumentManager()
    {
        return $this->dm;
    }

    /**
     * @return ObjectManager
     */
    public function getObjectManager()
    {
        return $this->em;
    }

    /**
     * This method makes the decision for the right manager depending
     * on the type of mapping.
     *
     * @param object $object
     * @return ObjectManager|DocumentManager
     * @throws Exception\MappingException
     */
    public function getManager($object)
    {
        $classMetdata = $this->getClassMetadata($object);
        $referencedObjectMapping = $classMetdata->getReferencedObjects();
        if (count($referencedObjectMapping) < 1) {
            throw new MappingException('There must be one at least one referenced object.');
        }

        $reference = array_shift($referencedObjectMapping);
        if ('reference-document' !== $reference['type'] && 'reference-object' !== $reference['type']) {
            throw new MappingException(
                sprintf('Reference type %s not supported by adapter manager.', $reference['type'])
            );
        }

        return 'reference-document' == $reference['type'] ? $this->getDocumentManager() : $this->getObjectManager();
    }
}

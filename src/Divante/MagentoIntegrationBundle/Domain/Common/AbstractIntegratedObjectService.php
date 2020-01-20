<?php
/**
 * @category    pimcore5-module-magento2-integration
 * @date        20/01/2020
 * @author      Michał Bolka <mbolka@divante.co>
 * @copyright   Copyright (c) 2020 DIVANTE (https://divante.co)
 */

namespace Divante\MagentoIntegrationBundle\Domain\Common;

use Divante\MagentoIntegrationBundle\Domain\Helper\ObjectStatusHelper;
use Divante\MagentoIntegrationBundle\Model\DataObject\IntegrationConfiguration;
use Divante\MagentoIntegrationBundle\Domain\DataObject\DataObjectEventListener;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\AbstractElement;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\EventDispatcher\Debug\WrappedListener;

/**
 * Class AbstractIntegratedObjectService
 * @package Divante\MagentoIntegrationBundle\Domain\Common
 */
abstract class AbstractIntegratedObjectService implements IntegratedElementServiceInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /** @var StatusService */
    protected $statusService;

    /**
     * AbstractIntegratedObjectService constructor.
     * @param StatusService $statusService
     */
    public function __construct(StatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    /**
     * @param AbstractElement $object
     *
     * @return bool
     */
    protected function isOnlyIndexChanged(AbstractElement $object): bool
    {
        $originObject = Concrete::getById($object->getId(), true);
        return
            $originObject instanceof Concrete
            && $originObject->getIndex() !== $object->getIndex()
            && $originObject->getFullPath() === $object->getFullPath();
    }

    /**
     * @param string $listenerClassName
     */
    protected function removeIntegratorListeners(string $listenerClassName): void
    {
        $integrationListener = $this->container->get($listenerClassName);
        $this->container->get('event_dispatcher')->removeListener(
            'pimcore.dataobject.preUpdate',
            [$integrationListener, 'onPreUpdate']
        );
        $this->container->get('event_dispatcher')->removeListener(
            'pimcore.dataobject.postUpdate',
            [$integrationListener, 'onPostUpdate']
        );
    }

    protected function restoreIntegratorListeners(): void
    {
        $listeners = $this->container->get('event_dispatcher')->getListeners('pimcore.dataobject.preUpdate');
        $exists = false;
        foreach ($listeners as $listener) {
            if ($listener instanceof WrappedListener && strpos($listener->getPretty(), DataObjectEventListener::class) >= 0) {
                $exists = true;
            }
        }
        if (!$exists) {
            $integrationListener = $this->container->get(DataObjectEventListener::class);
            $this->container->get('event_dispatcher')->addListener(
                'pimcore.dataobject.preUpdate',
                [$integrationListener, 'onPreUpdate']
            );
            $this->container->get('event_dispatcher')->addListener(
                'pimcore.dataobject.postUpdate',
                [$integrationListener, 'onPostUpdate']
            );
        }
    }

    public function setSendStatus(AbstractElement $element, IntegrationConfiguration $configuration): void
    {
        $this->removeIntegratorListeners(DataObjectEventListener::class);
        $this->statusService->setStatusProperty(
            $element,
            $configuration->getKey(),
            ObjectStatusHelper::SYNC_STATUS_SENT
        );
        $element->save();
        $this->restoreIntegratorListeners();
    }

    public function setDeleteStatus(AbstractElement $element, IntegrationConfiguration $configuration): void
    {
        $this->removeIntegratorListeners(DataObjectEventListener::class);
        $this->statusService->setStatusProperty(
            $element,
            $configuration->getKey(),
            ObjectStatusHelper::SYNC_STATUS_DELETE
        );
        $element->save();
        $this->restoreIntegratorListeners();
    }
}
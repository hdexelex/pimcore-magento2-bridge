<?php

namespace Divante\MagentoIntegrationBundle\Action\Admin\Integrator;

use Divante\MagentoIntegrationBundle\Domain\Admin\CommandExcecutor;
use Divante\MagentoIntegrationBundle\Domain\Admin\Request\GetIntegrationConfiguration;
use Divante\MagentoIntegrationBundle\Domain\Admin\SendProductsService;
use Divante\MagentoIntegrationBundle\Responder\JsonResponder;
use Divante\MagentoIntegrationBundle\Responder\MappedObjectJsonResponder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class SendProductsAction
 * @package Divante\MagentoIntegrationBundle\Action\Admin\Integrator
 * @Route("/integration-configuration/send/products", methods={"POST"})
 */
class SendProductsAction
{
    /** @var CommandExcecutor */
    private $domain;

    /** @var JsonResponder */
    private $responder;

    /**
     * GetProductAction constructor.
     * @param CommandExcecutor $domain
     * @param JsonResponder $responder
     */
    public function __construct(CommandExcecutor $domain, JsonResponder $jsonResponder)
    {
        $this->domain    = $domain;
        $this->responder = $jsonResponder;
    }

    /**
     * @return JsonResponse
     */
    public function __invoke(GetIntegrationConfiguration $query)
    {
        $this->domain->excecuteCommandSendProducts($query);
        return $this->responder->createResponse([]);
    }
}

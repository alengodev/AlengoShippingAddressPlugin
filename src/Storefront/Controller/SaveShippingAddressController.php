<?php declare(strict_types=1);

namespace AlengoShippingAddress\Storefront\Controller;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class SaveShippingAddressController extends StorefrontController
{
    #[Route(
        path: '/save-shipping-address',
        name: 'frontend.alengo.save_shipping_address',
        defaults: ['XmlHttpRequest' => true],
        methods: ['GET', 'POST'],
    )]
    public function saveShippingAddress(Request $request, SalesChannelContext $context): Response
    {
        $session = $request->getSession();
        $session->set('altShippingAddress', $request->request->all());

        return new Response(json_encode(['success' => true]));
    }
}

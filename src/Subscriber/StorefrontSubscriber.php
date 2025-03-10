<?php declare(strict_types=1);

namespace AlengoShippingAddress\Subscriber;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class StorefrontSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $logger;
    private EntityRepository $orderAddressRepository;
    private RequestStack $requestStack;
    private EntityRepository $orderRepository;

    public function __construct(
        LoggerInterface $logger,
        EntityRepository $orderAddressRepository,
        EntityRepository $orderRepository,
        RequestStack $requestStack,
    )
    {
        $this->logger = $logger;
        $this->orderAddressRepository = $orderAddressRepository;
        $this->orderRepository = $orderRepository;
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents(): array
    {
        // events to listen to
        return [
            CheckoutOrderPlacedEvent::class => 'onOrderPlaced',
        ];
    }

    public function onOrderPlaced(CheckoutOrderPlacedEvent $event): void
    {
        $altShippingAddress = [];

        // Get the current request
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            // Get the session from the request
            $session = $request->getSession();
            // Access the session variable
            $altShippingAddress = $session->get('altShippingAddress', []);
        }

        // if checkbox is checked override shipping address
        if ($altShippingAddress['altShippingAddress']['active'] ?? false) {
            // get order, order address and country id
            $order = $event->getOrder();
            //$orderAddress = $order->getAddresses()->first();

            $shippingAddressId = $order->getDeliveries()->first()->getShippingOrderAddressId();
            $shippingAddress = $order->getAddresses()->get($shippingAddressId);

            // Create a new shipping address
            $newShippingAddress = [
                'id' => Uuid::randomHex(),
                'orderId' => $order->getId(),
                'firstName' => $shippingAddress->getFirstName(),
                'lastName' => $shippingAddress->getLastName(),
                'street' => $altShippingAddress['altShippingAddress']['street'] ?? $shippingAddress->getStreet(),
                'zipcode' => $altShippingAddress['altShippingAddress']['zipcode'] ?? $altShippingAddress->getZipcode(),
                'city' => $altShippingAddress['altShippingAddress']['city'] ?? $shippingAddress->getCity(),
                'countryId' => $order->getAddresses()->first()->getCountryId(),
            ];
            // Add the new shipping address to the order
            $this->orderAddressRepository->create([$newShippingAddress], $event->getContext());

            // Update the order to use the new shipping address
            $order->getDeliveries()->first()->setShippingOrderAddressId($newShippingAddress['id']);
            $this->orderRepository->update([
                [
                    'id' => $order->getId(),
                    'deliveries' => [
                        [
                            'id' => $order->getDeliveries()->first()->getId(),
                            'shippingOrderAddressId' => $newShippingAddress['id'],
                        ],
                    ],
                ],
            ], $event->getContext());
        }
    }
}

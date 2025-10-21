<?php declare(strict_types=1);

namespace AlengoShippingAddress\Subscriber;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
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
    ) {
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

        // get order, order address and country id
        $order = $event->getOrder();

        // if checkbox is checked override shipping address or if b2b plugin is active
        if ((isset($altShippingAddress['altShippingAddress']) && ($altShippingAddress['altShippingAddress']['active'] ?? false)) || ($order->getCustomFields()['vio_b2b_employee_id'] ?? false)) {
            $orderCustomerEmail = $order->getOrderCustomer()->getEmail();
            $orderCustomerFirstName = $order->getOrderCustomer()->getFirstName();
            $orderCustomerLastName = $order->getOrderCustomer()->getLastName();

            $shippingAddressId = $order->getDeliveries()->first()->getShippingOrderAddressId();
            $shippingAddress = $order->getAddresses()->get($shippingAddressId);
            $newShippingAddress = [];

            if ($order->getCustomFields()['vio_b2b_employee_id'] ?? false) {
                // Create a new shipping address
                $newShippingAddress = [
                    'id' => Uuid::randomHex(),
                    'orderId' => $order->getId(),
                    'company' => $shippingAddress->getCompany(),
                    'firstName' => $order->getCustomFields()['vio_b2b_employee_firstName'] !== '' ? $order->getCustomFields()['vio_b2b_employee_firstName'] : $shippingAddress->getFirstName(),
                    'lastName' => $order->getCustomFields()['vio_b2b_employee_lastName'] !== '' ? $order->getCustomFields()['vio_b2b_employee_lastName'] : $shippingAddress->getLastName(),
                    'street' => $shippingAddress->getStreet(),
                    'zipcode' => $shippingAddress->getZipcode(),
                    'city' => $shippingAddress->getCity(),
                    'countryId' => $order->getAddresses()->first()->getCountryId(),
                ];

                // change order email and name to employee email and name
                $orderCustomerEmail = $order->getCustomFields()['vio_b2b_employee_email'] ?? $order->getOrderCustomer()->getEmail();
                $orderCustomerFirstName = $order->getCustomFields()['vio_b2b_employee_firstName'] ?? $order->getOrderCustomer()->getFirstName();
                $orderCustomerLastName = $order->getCustomFields()['vio_b2b_employee_lastName'] ?? $order->getOrderCustomer()->getLastName();
            }

            if ($altShippingAddress['altShippingAddress']['active'] ?? false) {
                // Create a new shipping address
                $newShippingAddress = [
                    'id' => Uuid::randomHex(),
                    'orderId' => $order->getId(),
                    'company' => $altShippingAddress['altShippingAddress']['company'] !== '' ? $altShippingAddress['altShippingAddress']['company'] : $shippingAddress->getCompany(),
                    'firstName' => $altShippingAddress['altShippingAddress']['firstName'] !== '' ? $altShippingAddress['altShippingAddress']['firstName'] : $shippingAddress->getFirstName(),
                    'lastName' => $altShippingAddress['altShippingAddress']['lastName'] !== '' ? $altShippingAddress['altShippingAddress']['lastName'] : $shippingAddress->getLastName(),
                    'street' => $altShippingAddress['altShippingAddress']['street'] !== '' ? $altShippingAddress['altShippingAddress']['street'] : $shippingAddress->getStreet(),
                    'zipcode' => $altShippingAddress['altShippingAddress']['zipcode'] !== '' ? $altShippingAddress['altShippingAddress']['zipcode'] : $shippingAddress->getZipcode(),
                    'city' => $altShippingAddress['altShippingAddress']['city'] !== '' ? $altShippingAddress['altShippingAddress']['city'] : $shippingAddress->getCity(),
                    'countryId' => $order->getAddresses()->first()->getCountryId(),
                ];
            }

            // Add the new shipping address to the order
            $this->orderAddressRepository->create([$newShippingAddress], $event->getContext());

            // Update the order to use the new shipping address
            $order->getDeliveries()->first()->setShippingOrderAddressId($newShippingAddress['id']);
            $this->orderRepository->update([
                [
                    'id' => $order->getId(),
                    'orderCustomer' => [
                        'id' => $order->getOrderCustomer()->getId(),
                        'email' => $orderCustomerEmail,
                        'firstName' => $orderCustomerFirstName,
                        'lastName' => $orderCustomerLastName,
                    ],
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

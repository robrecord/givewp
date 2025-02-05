<?php

use Give\Donations\Models\Donation;
use Give\Framework\PaymentGateways\PaymentGateway;
use Give\Framework\PaymentGateways\PaymentGatewayRegister;
use Give\Framework\PaymentGateways\SubscriptionModule;
use Give\Subscriptions\Models\Subscription;

/**
 * @unreleased
 */
class HasRouteMethodTest extends WP_UnitTestCase
{
    /**
     * @unreleased
     */
    public function testRegisteredSubscriptionModuleRouteShouldExecute()
    {
        $this->registerGateway();
        $gateway = give(GatewayRouteTestGateway::class);
        $gatewayRouteMethod = 'gatewaySimpleRouteMethod';
        $routeMethod = 'handleSimpleRoute';
        $secureRouteMethod = 'handleSecureRoute';

        $this->assertEquals(
            GatewayRouteTestGateway::class . $gatewayRouteMethod,
            $gateway->callRouteMethod($gatewayRouteMethod, [])
        );

        $this->assertEquals(
            GatewayRouteTestGatewaySubscriptionModule::class . $routeMethod,
            $gateway->callRouteMethod($routeMethod, [])
        );

        $this->assertEquals(
            GatewayRouteTestGatewaySubscriptionModule::class . $secureRouteMethod,
            $gateway->callRouteMethod($secureRouteMethod, [])
        );
    }

    /**
     * @unreleased
     */
    public function testThrowExceptionOnUnRegisteredRouteMethod()
    {
        $this->registerGateway();
        $gateway = give(GatewayRouteTestGateway::class);
        $routeMethod = 'UnRegisteredRoute';

        $this->expectExceptionMessage(
            'UnRegisteredRoute route method is not supported by GatewayRouteTestGateway and GatewayRouteTestGatewaySubscriptionModule'
        );

        $this->assertEquals(
            GatewayRouteTestGatewaySubscriptionModule::class . $routeMethod,
            $gateway->callRouteMethod($routeMethod, [])
        );
    }

    private function registerGateway()
    {
        add_filter("give_gateway_GatewayRouteTestGateway_subscription_module", function () {
            return GatewayRouteTestGatewaySubscriptionModule::class;
        });

        (new PaymentGatewayRegister())->registerGateway(GatewayRouteTestGateway::class);
    }
}

class GatewayRouteTestGateway extends PaymentGateway
{
    public $routeMethods = ['gatewaySimpleRouteMethod'];

    protected function gatewaySimpleRouteMethod($queryParams): string
    {
        return __CLASS__ . __FUNCTION__;
    }

    public function getLegacyFormFieldMarkup(int $formId, array $args): string
    {
        return '';
    }

    public static function id(): string
    {
        return 'GatewayRouteTestGateway';
    }

    public function getId(): string
    {
        return self::id();
    }

    public function getName(): string
    {
        return self::id();
    }

    public function getPaymentMethodLabel(): string
    {
        return self::id();
    }

    public function createPayment(Donation $donation)
    {
    }

    public function refundDonation(Donation $donation)
    {
        // TODO: Implement refundDonation() method.
    }
}

class GatewayRouteTestGatewaySubscriptionModule extends SubscriptionModule
{
    public $routeMethods = [
        'handleSimpleRoute'
    ];

    public $secureRouteMethods = [
        'handleSecureRoute'
    ];

    public function createSubscription(
        Donation $donation,
        Subscription $subscription
    ) {
    }

    protected function handleSimpleRoute($queryParams): string
    {
        return __CLASS__ . __FUNCTION__;
    }

    protected function handleSecureRoute($queryParams): string
    {
        return __CLASS__ . __FUNCTION__;
    }

    public function cancelSubscription(Subscription $subscription)
    {
    }
}

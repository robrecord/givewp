<?php

namespace Give\PaymentGateways\Gateways\Stripe;

use Give\Donations\Models\Donation;
use Give\Framework\Exceptions\Primitives\Exception;
use Give\Framework\PaymentGateways\Commands\GatewayCommand;
use Give\Framework\PaymentGateways\Exceptions\PaymentGatewayException;
use Give\Framework\PaymentGateways\PaymentGateway;
use Give\Framework\PaymentGateways\SubscriptionModule;
use Give\Helpers\Call;
use Give\PaymentGateways\Gateways\Stripe\Traits\BECSMandateForm;
use Give\PaymentGateways\Gateways\Stripe\Traits\HandlePaymentIntentStatus;

/**
 * @since 2.19.0
 */
class BECSGateway extends PaymentGateway
{
    use BECSMandateForm;
    use HandlePaymentIntentStatus;

    /** @var array */
    protected $errorMessages = [];

    /**
     * @param SubscriptionModule|null $subscriptionModule
     */
    public function __construct(SubscriptionModule $subscriptionModule = null)
    {
        parent::__construct($subscriptionModule);

        $this->errorMessages['accountConfiguredNoSsl']    = esc_html__( 'Mandate form fields are disabled because your site is not running securely over HTTPS.', 'give' );
        $this->errorMessages['accountNotConfiguredNoSsl'] = esc_html__( 'Mandate form fields are disabled because Stripe is not connected and your site is not running securely over HTTPS.', 'give' );
        $this->errorMessages['accountNotConfigured']      = esc_html__( 'Mandate form fields are disabled. Please connect and configure your Stripe account to accept donations.', 'give' );
    }

    /**
     * @inheritDoc
     * @since 2.19.7 fix handlePaymentIntentStatus not receiving extra param
     * @since 2.19.0
     * @return GatewayCommand
     * @throws PaymentGatewayException
     */
    public function createPayment( Donation $donation ): GatewayCommand
    {
        $paymentMethod = Call::invoke( Actions\GetPaymentMethodFromRequest::class, $donation );
        $donationSummary = Call::invoke( Actions\SaveDonationSummary::class, $donation );
        $stripeCustomer = Call::invoke( Actions\GetOrCreateStripeCustomer::class, $donation );

        $createIntentAction = new Actions\CreatePaymentIntent( $this->getPaymentIntentArgs() );

        return $this->handlePaymentIntentStatus(
            $createIntentAction(
                $donation,
                $donationSummary,
                $stripeCustomer,
                $paymentMethod
            ),
            $donation
        );
    }

    /**
     * @inheritDoc
     */
    public static function id(): string
    {
        return 'stripe_becs';
    }

    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return self::id();
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return __('Stripe - BECS Direct Debit', 'give');
    }

    /**
     * @inheritDoc
     */
    public function getPaymentMethodLabel(): string
    {
        return __('Stripe - BECS Direct Debit', 'give');
    }

    /**
     * @inheritDoc
     */
    public function getLegacyFormFieldMarkup(int $formId, array $args): string
    {
        return $this->getMandateFormHTML( $formId, $args );
    }

    /**
     * @since 2.19.0
     * @return array
     */
    protected function getPaymentIntentArgs(): array
    {
        return [
            'payment_method_types' => [ 'au_becs_debit' ],
            'setup_future_usage'   => 'off_session', // @TODO Is this correct? Maybe should be `on_session` - need to confirm.
            'mandate_data'         => [
                'customer_acceptance' => [
                    'type'   => 'online',
                    'online' => [
                        'ip_address' => give_stripe_get_ip_address(),
                        'user_agent' => give_get_user_agent(),
                    ],
                ],
            ],
        ];
    }

    /**
     * @since 2.20.0
     * @inerhitDoc
     * @throws Exception
     */
    public function refundDonation(Donation $donation)
    {
        throw new Exception('Method has not been implemented yet. Please use the legacy method in the meantime.');
    }
}

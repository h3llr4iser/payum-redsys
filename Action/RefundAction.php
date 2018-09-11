<?php
/**
 * Created by PhpStorm.
 * User: sergio
 * Date: 22/06/2017
 * Time: 16:48
 */

namespace Crevillo\Payum\Redsys\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Crevillo\Payum\Redsys\Api;
use Payum\Core\Request\Refund;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Payum\Core\Reply\HttpPostRedirect;

class RefundAction implements ActionInterface, ApiAwareInterface, GenericTokenFactoryAwareInterface
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * @var GenericTokenFactoryInterface
     */
    protected $tokenFactory;

    /**
     * @param GenericTokenFactoryInterface $genericTokenFactory
     *
     * @return void
     */
    public function setGenericTokenFactory(
        GenericTokenFactoryInterface $genericTokenFactory = null
    ) {
        $this->tokenFactory = $genericTokenFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function setApi($api)
    {
        if (false == $api instanceof Api) {
            throw new UnsupportedApiException('Not supported.');
        }

        $this->api = $api;
    }

    /**
     * {@inheritDoc}
     */
    public function execute($request)
    {

        /** @var $request Refund */
        RequestNotSupportedException::assertSupports($this, $request);

        $paymentDetails = ArrayObject::ensureArrayObject($request->getModel());

        //prepare parameters form
//        if($paymentDetails->offsetExists('Ds_SignatureVersion')){
//            $paymentDetails->offsetUnset('Ds_SignatureVersion');
//        }
//        if($paymentDetails->offsetExists('Ds_MerchantParameters')){
//            $paymentDetails->offsetUnset('Ds_MerchantParameters');
//        }

        if (empty($postData['Ds_Merchant_MerchantURL']) && $request->getToken() && $this->tokenFactory) {
            $notifyToken = $this->tokenFactory->createNotifyToken(
                $request->getToken()->getGatewayName(),
                $request->getToken()->getDetails()
            );

            $postData['Ds_Merchant_MerchantURL'] = $notifyToken->getTargetUrl();
        }

        if (false == $paymentDetails['Ds_Merchant_UrlOK'] && $request->getToken()) {
            $paymentDetails['Ds_Merchant_UrlOK'] = $request->getToken()
                ->getAfterUrl();
        }
        if (false == $paymentDetails['Ds_Merchant_UrlKO'] && $request->getToken()) {
            $paymentDetails['Ds_Merchant_UrlKO'] = $request->getToken()
                ->getAfterUrl();
        }

        $paymentDetails['Ds_Merchant_TransactionType'] = Api::TRANSACTIONTYPE_REFUND;
        $refundDetails['Ds_MerchantParameters'] = $this->api->createMerchantParameters($paymentDetails->toUnsafeArray());
        $refundDetails['Ds_SignatureVersion'] = Api::SIGNATURE_VERSION;
        $refundDetails['Ds_Signature'] = $this->api->sign($paymentDetails->toUnsafeArray());


//        ArrayObject::ensureArrayObject($refundDetails);

//        $newDeatils = $this->api->doRefund($refundDetails);

        throw new HttpPostRedirect($this->api->getRedsysUrl(),$refundDetails);

    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Refund &&
            $request->getModel() instanceof \ArrayAccess
            ;
    }

}
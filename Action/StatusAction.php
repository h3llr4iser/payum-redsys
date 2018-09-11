<?php
namespace Crevillo\Payum\Redsys\Action;

use Crevillo\Payum\Redsys\Api;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetStatusInterface;

class StatusAction implements ActionInterface
{
    /**
     * {@inheritDoc}
     */
    public function execute($request)
    {
        /** @var $request GetStatusInterface */
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if (null == $model['Ds_Response']) {
            $request->markNew();

            return;
        }


        if ($model['Ds_AuthorisationCode'] && null === $model['Ds_Response']) {
            $request->markPending();

            return;
        }

        if (in_array($model['Ds_Response'], Api::$CANCELED_RS_RESPONSES)) {
            $request->markCanceled();

            return;
        }

        if (0 <= $model['Ds_Response'] && 99 >= $model['Ds_Response']) {
            $request->markCaptured();

            return;
        }

//	    if ("0900" == $model['Ds_Response'] && $model['Ds_Merchant_TransactionType'] == Api::TRANSACTIONTYPE_REFUND) {
	    if ("0900" == $model['Ds_Response']) {
            $request->markRefunded();

            return;
        }

        $request->markUnknown();
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof \ArrayAccess;
    }
}

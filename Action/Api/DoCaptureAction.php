<?php
namespace Workup\Payum\Paypal\ExpressCheckout\Nvp\Action\Api;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Workup\Payum\Paypal\ExpressCheckout\Nvp\Api;
use Workup\Payum\Paypal\ExpressCheckout\Nvp\Request\Api\DoCapture;
use Workup\Payum\Paypal\ExpressCheckout\Nvp\Request\Api\GetTransactionDetails;

class DoCaptureAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($request)
    {
        /** @var $request DoCapture */
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());
        $paymentRequestN = $request->getPaymentRequestN();

        $fields = new ArrayObject([]);
        foreach ($this->getPaymentRequestNFields() as $field) {
            $fields[$field] = $model['PAYMENTREQUEST_'.$paymentRequestN.'_'.$field];
        }
        $fields['AUTHORIZATIONID'] = $fields['TRANSACTIONID'];

        $fields->validateNotEmpty(['AMT', 'COMPLETETYPE', 'AUTHORIZATIONID']);

        $this->api->doCapture((array) $fields);

        $this->gateway->execute(new GetTransactionDetails($model, $paymentRequestN));
    }

    /**
     * @return array
     */
    protected function getPaymentRequestNFields()
    {
        return array(
            'TRANSACTIONID',
            'PARENTTRANSACTIONID',
            'RECEIPTID',
            'TRANSACTIONTYPE',
            'PAYMENTTYPE',
            'ORDERTIME',
            'AMT',
            'CURRENCYCODE',
            'FEEAMT',
            'SETTLEAMT',
            'TAXAMT',
            'EXCHANGERATE',
            'PAYMENTSTATUS',
            'PENDINGREASON',
            'REASONCODE',
            'COMPLETETYPE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        return
            $request instanceof DoCapture &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}

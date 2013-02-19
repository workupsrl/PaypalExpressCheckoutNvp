<?php
namespace Payum\Paypal\ExpressCheckout\Nvp\Action;

use Payum\Action\ActionInterface;
use Payum\Exception\RequestNotSupportedException;
use Payum\PaymentInstructionAggregateInterface;
use Payum\Request\StatusRequestInterface;
use Payum\Paypal\ExpressCheckout\Nvp\PaymentInstruction;
use Payum\Paypal\ExpressCheckout\Nvp\Api;

class StatusAction implements ActionInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute($request)
    {
        if (false == $this->supports($request)) {
            throw RequestNotSupportedException::createActionNotSupported($this, $request);
        }
        
        $instruction = $this->getPaymentInstructionFromRequest($request);

        if (in_array(Api::L_ERRORCODE_PAYMENT_NOT_AUTHORIZED, $instruction->getLErrorcoden())) {
            $request->markCanceled();
            
            return;
        }

        if (count($instruction->getLErrorcoden()) > 0) {
            $request->markFailed();

            return;
        }
        
        //treat this situation as canceled. In other case we can get into an endless cycle.
        if (
            false == $instruction->getPayerid() && 
            $instruction->getCheckoutstatus() == Api::CHECKOUTSTATUS_PAYMENT_ACTION_NOT_INITIATED
        ) {
            $request->markCanceled();

            return;
        }
        
        if (
            false == $instruction->getCheckoutstatus() || 
            Api::CHECKOUTSTATUS_PAYMENT_ACTION_NOT_INITIATED == $instruction->getCheckoutstatus()
        ) {
            $request->markNew();

            return;
        }
        if (Api::CHECKOUTSTATUS_PAYMENT_ACTION_IN_PROGRESS == $instruction->getCheckoutstatus()) {
            $request->markInProgress();

            return;
        }
        if (Api::CHECKOUTSTATUS_PAYMENT_ACTION_FAILED == $instruction->getCheckoutstatus()) {
            $request->markFailed();

            return;
        }
        
        //todo check all payment statuses.
        if (
            Api::CHECKOUTSTATUS_PAYMENT_COMPLETED == $instruction->getCheckoutstatus() ||
            Api::CHECKOUTSTATUS_PAYMENT_ACTION_COMPLETED == $instruction->getCheckoutstatus()
        ) {
            $successCounter = 0;
            foreach ($instruction->getPaymentrequestPaymentstatus() as $paymentStatus) {
                $inProgress = array(
                    Api::PAYMENTSTATUS_IN_PROGRESS,
                    Api::PAYMENTSTATUS_PENDING,
                );
                if (in_array($paymentStatus, $inProgress)) {
                    $request->markInProgress();

                    return;
                }
                
                $failedStatuses = array(
                    Api::PAYMENTSTATUS_FAILED,
                    Api::PAYMENTSTATUS_EXPIRED, 
                    Api::PAYMENTSTATUS_DENIED, 
                    Api::PAYMENTSTATUS_CANCELED_REVERSAL
                );
                if (in_array($paymentStatus, $failedStatuses)) {
                    $request->markFailed();
                
                    return;
                }

                $completedStatuses = array(
                    Api::PAYMENTSTATUS_COMPLETED, 
                    Api::PAYMENTSTATUS_PROCESSED
                );
                if (in_array($paymentStatus, $completedStatuses)) {
                    $successCounter++;
                }
            }
            
            if ($successCounter == count($instruction->getPaymentrequestPaymentstatus())) {
                $request->markSuccess();
                
                return;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        if (false == $request instanceof StatusRequestInterface) {
            return false;
        }
        
        return (bool) $this->getPaymentInstructionFromRequest($request);
    }

    /**
     * @param \Payum\Request\StatusRequestInterface $request
     *
     * @return PaymentInstruction|null
     */
    protected function getPaymentInstructionFromRequest(StatusRequestInterface $request)
    {
        if ($request->getModel() instanceof PaymentInstruction) {
            return $request->getModel();
        }

        if (
            $request->getModel() instanceof PaymentInstructionAggregateInterface &&
            $request->getModel()->getPaymentInstruction() instanceof PaymentInstruction
        ) {
            return $request->getModel()->getPaymentInstruction();
        }
    }
}
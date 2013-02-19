<?php
namespace Payum\Paypal\ExpressCheckout\Nvp\Tests\Action;

use Payum\Paypal\ExpressCheckout\Nvp\Bridge\Buzz\Response;
use Payum\Paypal\ExpressCheckout\Nvp\Action\GetTransactionDetailsAction;
use Payum\Paypal\ExpressCheckout\Nvp\Payment;
use Payum\Paypal\ExpressCheckout\Nvp\Request\GetTransactionDetailsRequest;
use Payum\Paypal\ExpressCheckout\Nvp\PaymentInstruction;
use Payum\Paypal\ExpressCheckout\Nvp\Api;

class GetTransactionDetailsActionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldBeSubClassOfActionPaymentAware()
    {
        $rc = new \ReflectionClass('Payum\Paypal\ExpressCheckout\Nvp\Action\GetTransactionDetailsAction');

        $this->assertTrue($rc->isSubclassOf('Payum\Paypal\ExpressCheckout\Nvp\Action\ActionPaymentAware'));
    }

    /**
     * @test
     */
    public function couldBeConstructedWithoutAnyArguments()   
    {
        new GetTransactionDetailsAction();
    }

    /**
     * @test
     */
    public function shouldSupportGetTransactionDetailsRequest()
    {
        $action = new GetTransactionDetailsAction();
        $action->setPayment(new Payment($this->createApiMock()));
        
        $request = new GetTransactionDetailsRequest($paymentRequestN = 5, new PaymentInstruction);
        
        $this->assertTrue($action->supports($request));
    }

    /**
     * @test
     */
    public function shouldNotSupportAnythingNotGetTransactionDetailsRequest()
    {
        $action = new GetTransactionDetailsAction();
        $action->setPayment(new Payment($this->createApiMock()));

        $this->assertFalse($action->supports(new \stdClass()));
    }

    /**
     * @test
     * 
     * @expectedException \Payum\Exception\RequestNotSupportedException
     */
    public function throwIfNotSupportedRequestGivenAsArgumentForExecute()
    {
        $action = new GetTransactionDetailsAction();
        $action->setPayment(new Payment($this->createApiMock()));

        $action->execute(new \stdClass());
    }

    /**
     * @test
     *
     * @expectedException \Payum\Exception\LogicException
     * @expectedExceptionMessage The TransactionId must be set.
     */
    public function throwIfInstructionNotHaveTokenSetInInstruction()
    {
        $action = new GetTransactionDetailsAction();
        $action->setPayment(new Payment($this->createApiMock()));

        $request = new GetTransactionDetailsRequest($paymentRequestN = 5, new PaymentInstruction);
        
        //guard
        $this->assertNull($request->getPaymentInstruction()->getPaymentrequestTransactionid($paymentRequestN));

        $action->execute($request);
    }

    /**
     * @test
     */
    public function shouldCallApiGetTransactionDetailsMethodWithExpectedRequiredArguments()
    {
        $actualRequest = null;
        
        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->once())
            ->method('getTransactionDetails')
            ->will($this->returnCallback(function($request) use (&$actualRequest){
                $actualRequest = $request;

                return new Response();
            }))
        ;
        
        $action = new GetTransactionDetailsAction();
        $action->setPayment(new Payment($apiMock));

        $request = new GetTransactionDetailsRequest($paymentRequestN = 5, new PaymentInstruction);
        $request->getPaymentInstruction()->setPaymentrequestTransactionid(
            $paymentRequestN, 
            $expectedTransactionId = 'theTransactionId'
        );

        $action->execute($request);
        
        $this->assertInstanceOf('Buzz\Message\Form\FormRequest', $actualRequest);
        
        $fields = $actualRequest->getFields();

        $this->assertArrayHasKey('TRANSACTIONID', $fields);
        $this->assertEquals($expectedTransactionId, $fields['TRANSACTIONID']);
    }

    /**
     * @test
     */
    public function shouldCallApiGetTransactionDetailsAndUpdateInstructionFromResponseOnSuccess()
    {
        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->once())
            ->method('getTransactionDetails')
            ->will($this->returnCallback(function() {
                $response = new Response;
                $response->setContent(http_build_query(array(
                    'FIRSTNAME'=> 'theFirstname',
                    'EMAIL' => 'the@example.com',
                    'PAYMENTSTATUS' => 'theStatus',
                )));
                
                return $response;
            }))
        ;

        $action = new GetTransactionDetailsAction();
        $action->setPayment(new Payment($apiMock));

        $request = new GetTransactionDetailsRequest($paymentRequestN = 5, new PaymentInstruction);
        $request->getPaymentInstruction()->setPaymentrequestTransactionid(
            $paymentRequestN,
            $expectedTransactionId = 'theTransactionId'
        );

        $action->execute($request);
        
        $this->assertEquals('theFirstname', $request->getPaymentInstruction()->getFirstname());
        $this->assertEquals('the@example.com', $request->getPaymentInstruction()->getEmail());
        $this->assertEquals(
            'theStatus', 
            $request->getPaymentInstruction()->getPaymentrequestPaymentstatus($paymentRequestN)
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Payum\Paypal\ExpressCheckout\Nvp\Api
     */
    protected function createApiMock()
    {
        return $this->getMock('Payum\Paypal\ExpressCheckout\Nvp\Api', array(), array(), '', false);
    }
}
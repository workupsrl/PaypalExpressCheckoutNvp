<?php
namespace Workup\Payum\Paypal\ExpressCheckout\Nvp\Tests\Action\Api;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayInterface;
use Workup\Payum\Paypal\ExpressCheckout\Nvp\Action\Api\DoCaptureAction;
use Workup\Payum\Paypal\ExpressCheckout\Nvp\Api;
use Workup\Payum\Paypal\ExpressCheckout\Nvp\Request\Api\DoCapture;
use Workup\Payum\Paypal\ExpressCheckout\Nvp\Request\Api\GetTransactionDetails;

class DoCaptureActionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function shouldImplementActionInterface()
    {
        $rc = new \ReflectionClass(DoCaptureAction::class);

        $this->assertTrue($rc->implementsInterface(ActionInterface::class));
    }

    /**
     * @test
     */
    public function shouldImplementApoAwareInterface()
    {
        $rc = new \ReflectionClass(DoCaptureAction::class);

        $this->assertTrue($rc->implementsInterface(ApiAwareInterface::class));
    }

    /**
     * @test
     */
    public function shouldImplementsGatewayAwareInterface()
    {
        $rc = new \ReflectionClass(DoCaptureAction::class);

        $this->assertTrue($rc->implementsInterface(GatewayAwareInterface::class));
    }

    /**
     * @test
     */
    public function shouldSupportDoCaptureRequestAndArrayAccessAsModel()
    {
        $action = new DoCaptureAction();

        $this->assertTrue($action->supports(new DoCapture(new \ArrayObject(), 0)));
    }

    /**
     * @test
     */
    public function shouldNotSupportAnythingNotDoCaptureRequest()
    {
        $action = new DoCaptureAction();

        $this->assertFalse($action->supports(new \stdClass()));
    }

    /**
     * @test
     */
    public function throwIfNotSupportedRequestGivenAsArgumentForExecute()
    {
        $this->expectException(\Payum\Core\Exception\RequestNotSupportedException::class);
        $action = new DoCaptureAction();

        $action->execute(new \stdClass());
    }

    /**
     * @test
     */
    public function throwIfTransactionIdNorAuthorizationIdNotSetInModel()
    {
        $this->expectException(\Payum\Core\Exception\LogicException::class);
        $this->expectExceptionMessage('The AMT, COMPLETETYPE, AUTHORIZATIONID fields are required.');
        $action = new DoCaptureAction();

        $action->execute(new DoCapture([], 0));
    }

    /**
     * @test
     */
    public function throwIfCompleteTypeNotSet()
    {
        $this->expectException(\Payum\Core\Exception\LogicException::class);
        $this->expectExceptionMessage('The COMPLETETYPE fields are required.');
        $action = new DoCaptureAction();

        $request = new DoCapture(array(
            'PAYMENTREQUEST_0_TRANSACTIONID' => 'aTransactionId',
            'PAYMENTREQUEST_0_AMT' => 100,
        ), 0);

        $action->execute($request);
    }

    /**
     * @test
     */
    public function throwIfAmtNotSet()
    {
        $this->expectException(\Payum\Core\Exception\LogicException::class);
        $this->expectExceptionMessage('The AMT fields are required.');
        $action = new DoCaptureAction();

        $request = new DoCapture(array(
            'PAYMENTREQUEST_0_TRANSACTIONID' => 'aReferenceId',
            'PAYMENTREQUEST_0_COMPLETETYPE' => 'Complete',
        ), 0);

        $action->execute($request);
    }

    /**
     * @test
     */
    public function shouldCallApiDoCaptureMethodWithExpectedRequiredArguments()
    {
        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->once())
            ->method('DoCapture')
            ->will($this->returnCallback(function (array $fields) {
                $this->assertArrayHasKey('TRANSACTIONID', $fields);
                $this->assertEquals('theTransactionId', $fields['TRANSACTIONID']);

                $this->assertArrayHasKey('AMT', $fields);
                $this->assertEquals('theAmt', $fields['AMT']);

                $this->assertArrayHasKey('COMPLETETYPE', $fields);
                $this->assertEquals('Complete', $fields['COMPLETETYPE']);

                return array();
            }))
        ;

        $action = new DoCaptureAction();
        $action->setApi($apiMock);
        $action->setGateway($this->createGatewayMock());

        $request = new DoCapture(array(
            'PAYMENTREQUEST_0_TRANSACTIONID' => 'theTransactionId',
            'PAYMENTREQUEST_0_COMPLETETYPE' => 'Complete',
            'PAYMENTREQUEST_0_AMT' => 'theAmt',
        ), 0);

        $action->execute($request);
    }

    /**
     * @test
     */
    public function shouldCallApiDoCaptureMethodAndUpdateModelFromResponseOnSuccess()
    {
        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->once())
            ->method('DoCapture')
            ->will($this->returnCallback(function () {
                return array(
                    'FIRSTNAME' => 'theFirstname',
                    'EMAIL' => 'the@example.com',
                );
            }))
        ;

        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(GetTransactionDetails::class))
            ->will($this->returnCallback(function (GetTransactionDetails $request) {
                $this->assertSame(0, $request->getPaymentRequestN());
                $this->assertSame(array(
                    'PAYMENTREQUEST_0_TRANSACTIONID' => 'theTransactionId',
                    'PAYMENTREQUEST_0_COMPLETETYPE' => 'Complete',
                    'PAYMENTREQUEST_0_AMT' => 'theAmt',
                ), (array) $request->getModel());


                $model = $request->getModel();
                $model['FIRSTNAME'] = 'theFirstname';
                $model['EMAIL'] = 'the@example.com';
            }))
        ;

        $action = new DoCaptureAction();
        $action->setApi($apiMock);
        $action->setGateway($gatewayMock);

        $request = new DoCapture(array(
            'PAYMENTREQUEST_0_TRANSACTIONID' => 'theTransactionId',
            'PAYMENTREQUEST_0_COMPLETETYPE' => 'Complete',
            'PAYMENTREQUEST_0_AMT' => 'theAmt',
        ), 0);

        $action->execute($request);

        $model = $request->getModel();

        $this->assertArrayHasKey('FIRSTNAME', $model);
        $this->assertEquals('theFirstname', $model['FIRSTNAME']);

        $this->assertArrayHasKey('EMAIL', $model);
        $this->assertEquals('the@example.com', $model['EMAIL']);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Api
     */
    protected function createApiMock()
    {
        return $this->createMock(Api::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|GatewayInterface
     */
    protected function createGatewayMock()
    {
        return $this->createMock(GatewayInterface::class);
    }
}

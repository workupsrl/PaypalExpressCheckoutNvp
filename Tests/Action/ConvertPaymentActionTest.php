<?php
namespace Workup\Payum\Paypal\ExpressCheckout\Nvp\Tests\Action;

use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Generic;
use Payum\Core\Request\GetCurrency;
use Payum\Core\Tests\GenericActionTest;
use Workup\Payum\Paypal\ExpressCheckout\Nvp\Action\ConvertPaymentAction;
use Payum\Core\Model\Payment;
use Payum\Core\Request\Convert;

class ConvertPaymentActionTest extends GenericActionTest
{
    protected $actionClass = 'Workup\Payum\Paypal\ExpressCheckout\Nvp\Action\ConvertPaymentAction';

    protected $requestClass = 'Payum\Core\Request\Convert';

    public function provideSupportedRequests(): \Iterator
    {
        yield array(new $this->requestClass(new Payment(), 'array'));
        yield array(new $this->requestClass($this->createMock(PaymentInterface::class), 'array'));
        yield array(new $this->requestClass(new Payment(), 'array', $this->createMock('Payum\Core\Security\TokenInterface')));
    }

    public function provideNotSupportedRequests(): \Iterator
    {
        yield array('foo');
        yield array(array('foo'));
        yield array(new \stdClass());
        yield array($this->getMockForAbstractClass(Generic::class, array(array())));
        yield array(new $this->requestClass(new \stdClass(), 'array'));
        yield array(new $this->requestClass(new Payment(), 'foobar'));
        yield array(new $this->requestClass($this->createMock(PaymentInterface::class), 'foobar'));
    }

    /**
     * @test
     */
    public function shouldCorrectlyConvertOrderToDetailsAndSetItBack()
    {
        $gatewayMock = $this->createMock('Payum\Core\GatewayInterface');
        $gatewayMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf('Payum\Core\Request\GetCurrency'))
            ->willReturnCallback(function (GetCurrency $request) {
                $request->name = 'US Dollar';
                $request->alpha3 = 'USD';
                $request->numeric = 123;
                $request->exp = 2;
                $request->country = 'US';
            })
        ;

        $payment = new Payment();
        $payment->setNumber('theNumber');
        $payment->setCurrencyCode('USD');
        $payment->setTotalAmount(123);
        $payment->setDescription('the description');
        $payment->setClientId('theClientId');
        $payment->setClientEmail('theClientEmail');

        $action = new ConvertPaymentAction();
        $action->setGateway($gatewayMock);

        $action->execute($convert = new Convert($payment, 'array'));

        $details = $convert->getResult();

        $this->assertNotEmpty($details);

        $this->assertArrayHasKey('INVNUM', $details);
        $this->assertEquals('theNumber', $details['INVNUM']);

        $this->assertArrayHasKey('PAYMENTREQUEST_0_AMT', $details);
        $this->assertEquals(1.23, $details['PAYMENTREQUEST_0_AMT']);

        $this->assertArrayHasKey('PAYMENTREQUEST_0_CURRENCYCODE', $details);
        $this->assertEquals('USD', $details['PAYMENTREQUEST_0_CURRENCYCODE']);
    }

    /**
     * @test
     */
    public function shouldNotOverwriteAlreadySetExtraDetails()
    {
        $gatewayMock = $this->createMock('Payum\Core\GatewayInterface');
        $gatewayMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf('Payum\Core\Request\GetCurrency'))
            ->willReturnCallback(function (GetCurrency $request) {
                $request->name = 'US Dollar';
                $request->alpha3 = 'USD';
                $request->numeric = 123;
                $request->exp = 2;
                $request->country = 'US';
            })
        ;

        $payment = new Payment();
        $payment->setCurrencyCode('USD');
        $payment->setTotalAmount(123);
        $payment->setDescription('the description');
        $payment->setDetails(array(
            'foo' => 'fooVal',
        ));

        $action = new ConvertPaymentAction();
        $action->setGateway($gatewayMock);

        $action->execute($convert = new Convert($payment, 'array'));

        $details = $convert->getResult();

        $this->assertNotEmpty($details);

        $this->assertArrayHasKey('foo', $details);
        $this->assertEquals('fooVal', $details['foo']);
    }
}

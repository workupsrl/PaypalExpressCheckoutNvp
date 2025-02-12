<?php
namespace Workup\Payum\Paypal\ExpressCheckout\Nvp\Tests\Action\Api;

class BaseApiAwareActionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function shouldImplementActionInterface()
    {
        $rc = new \ReflectionClass('Workup\Payum\Paypal\ExpressCheckout\Nvp\Action\Api\BaseApiAwareAction');

        $this->assertTrue($rc->isSubclassOf('Payum\Core\Action\ActionInterface'));
    }

    /**
     * @test
     */
    public function shouldImplementApiAwareInterface()
    {
        $rc = new \ReflectionClass('Workup\Payum\Paypal\ExpressCheckout\Nvp\Action\Api\BaseApiAwareAction');

        $this->assertTrue($rc->isSubclassOf('Payum\Core\ApiAwareInterface'));
    }

    /**
     * @test
     */
    public function shouldBeAbstract()
    {
        $rc = new \ReflectionClass('Workup\Payum\Paypal\ExpressCheckout\Nvp\Action\Api\BaseApiAwareAction');

        $this->assertTrue($rc->isAbstract());
    }

    /**
     * @test
     */
    public function throwIfUnsupportedApiGiven()
    {
        $this->expectException(\Payum\Core\Exception\UnsupportedApiException::class);
        $action = $this->getMockForAbstractClass('Workup\Payum\Paypal\ExpressCheckout\Nvp\Action\Api\BaseApiAwareAction');

        $action->setApi(new \stdClass());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Workup\Payum\Paypal\ExpressCheckout\Nvp\Api
     */
    protected function createApiMock()
    {
        return $this->createMock('Workup\Payum\Paypal\ExpressCheckout\Nvp\Api', array(), array(), '', false);
    }
}

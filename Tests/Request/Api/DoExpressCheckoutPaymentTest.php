<?php
namespace Workup\Payum\Paypal\ExpressCheckout\Nvp\Tests\Request\Api;

use Payum\Core\Request\Generic;

class DoExpressCheckoutPaymentTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function shouldBeSubClassOfGeneric()
    {
        $rc = new \ReflectionClass('Workup\Payum\Paypal\ExpressCheckout\Nvp\Request\Api\DoExpressCheckoutPayment');

        $this->assertTrue($rc->isSubclassOf(Generic::class));
    }
}

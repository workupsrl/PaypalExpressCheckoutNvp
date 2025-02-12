<?php
namespace Workup\Payum\Paypal\ExpressCheckout\Nvp\Tests\Request\Api;

use Payum\Core\Request\Generic;
use Workup\Payum\Paypal\ExpressCheckout\Nvp\Request\Api\DoVoid;

class DoVoidTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function shouldBeSubClassOfGeneric()
    {
        $rc = new \ReflectionClass(DoVoid::class);

        $this->assertTrue($rc->isSubclassOf(Generic::class));
    }
}

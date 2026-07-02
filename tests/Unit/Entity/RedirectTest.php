<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Redirect;
use PHPUnit\Framework\TestCase;

class RedirectTest extends TestCase
{
    public function testSourceIsNormalizedWithLeadingSlash(): void
    {
        $redirect = (new Redirect())->setSource('ancienne-page');

        $this->assertSame('/ancienne-page', $redirect->getSource());
    }

    public function testRelativeTargetIsNormalized(): void
    {
        $redirect = (new Redirect())->setTarget('nouvelle-page');

        $this->assertSame('/nouvelle-page', $redirect->getTarget());
    }

    public function testAbsoluteTargetIsKept(): void
    {
        $redirect = (new Redirect())->setTarget('https://exemple.fr/page');

        $this->assertSame('https://exemple.fr/page', $redirect->getTarget());
    }

    public function testDefaultStatusCodeIs301(): void
    {
        $this->assertSame(301, (new Redirect())->getStatusCode());
    }
}

<?php

declare(strict_types=1);

namespace Spacetab\AmphpSupport\Handler;

abstract class AbstractTrustedRequest implements TrustedRequestInterface
{
    use TrustedRequestTrait;
}

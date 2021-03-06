<?php

declare(strict_types=1);

namespace Spacetab\AmphpSupport\Handler;

use Amp\Http\Server\RequestHandler;

interface TrustedRequestInterface extends RequestHandler
{
    /**
     * Sets the trusted body contents after validation.
     *
     * @param array<mixed> $body
     * @return $this
     */
    public function setTrustedBody(array $body): self;

    /**
     * Gets the trusted body contents after validation.
     *
     * @return array<mixed>
     */
    public function getTrustedBody(): array;
}

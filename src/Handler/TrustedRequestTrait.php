<?php

declare(strict_types=1);

namespace Spacetab\AmphpSupport\Handler;

trait TrustedRequestTrait
{
    /**
     * @var array<mixed>
     */
    protected array $trustedBody;

    /**
     * Sets the trusted body contents after validation.
     *
     * @param array<mixed> $body
     * @return $this
     */
    public function setTrustedBody(array $body): self
    {
        $this->trustedBody = $body;

        return $this;
    }

    /**
     * Gets the trusted body contents after validation.
     *
     * @return array<mixed>
     */
    public function getTrustedBody(): array
    {
        return $this->trustedBody;
    }
}

<?php

namespace AlexDpy\Acl\Mask;

final class BasicMaskBuilder extends AbstractMaskBuilder
{
    const MASK_VIEW = 1;
    const MASK_EDIT = 2;
    const MASK_CREATE = 4;
    const MASK_DELETE = 8;

    /**
     * {@inheritdoc}
     */
    public function resolveMask($code)
    {
        if (is_string($code)) {
            if (!defined($name = sprintf('static::MASK_%s', strtoupper($code)))) {
                throw new \InvalidArgumentException(sprintf('The code "%s" is not supported', $code));
            }

            return constant($name);
        }

        if (!is_int($code)) {
            throw new \InvalidArgumentException('$code must be an integer.');
        }

        return $code;
    }
}

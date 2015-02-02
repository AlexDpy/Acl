<?php

namespace AlexDpy\Acl\Mask;

interface MaskBuilderInterface
{
    /**
     * Set the mask of this permission
     *
     * @param int $mask
     *
     * @return MaskBuilderInterface
     * @throws \InvalidArgumentException if $mask is not an integer
     */
    public function set($mask);

    /**
     * Returns the mask of this permission.
     *
     * @return int
     */
    public function get();

    /**
     * Adds a mask to the permission.
     *
     * @param int|string $mask
     *
     * @return MaskBuilderInterface
     */
    public function add($mask);

    /**
     * Removes a mask from the permission.
     *
     * @param int|string $mask
     *
     * @return MaskBuilderInterface
     */
    public function remove($mask);

    /**
     * Resets the PermissionBuilder.
     *
     * @return MaskBuilderInterface
     */
    public function reset();

    /**
     * Returns the mask for the passed code
     *
     * @param mixed $code
     *
     * @return int|string
     *
     * @throws \InvalidArgumentException
     */
    public function resolveMask($code);
}

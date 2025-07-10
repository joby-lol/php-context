<?php

namespace Joby\ContextInjection\Invoker;

use Attribute;

/**
 * Attribute to indicate what category a parameter should be pulled from, if it
 * needs to be from a category other than "default"
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
readonly class CategoryName
{
    /**
     * The category to pull the parameter from.
     *
     * @param string $category The category name.
     */
    public function __construct(
        public string $category,
    )
    {
    }
}

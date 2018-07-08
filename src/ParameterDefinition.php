<?php
/**
 * ParameterDefinition
 *
 * @license MIT
 * @copyright 2018 Tommy Teasdale
 */
declare(strict_types=1);

namespace Apine\DistRoute;


final class ParameterDefinition
{
    /**
     * @var string
     */
    public $name;
    
    /**
     * @var string
     */
    public $pattern;
    
    /**
     * @var bool
     */
    public $optional = false;
    
    public function __construct(string $name, string $pattern)
    {
        $this->name = $name;
        $this->pattern = $pattern;
    }
}
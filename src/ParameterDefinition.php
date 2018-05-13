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
    public $name;
    
    public $pattern;
    
    public $optional = false;
    
    public function __construct($name, $pattern)
    {
        $this->name = $name;
        $this->pattern = $pattern;
    }
}
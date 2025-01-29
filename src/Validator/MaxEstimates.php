<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class MaxEstimates extends Constraint
{
    /*
     * Any public properties become valid options for the annotation.
     * Then, use these in your validator class.
     */
    public string $message = 'L\'estimation est trop élevé {{ estimates }} contre {{ max }}.';

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}

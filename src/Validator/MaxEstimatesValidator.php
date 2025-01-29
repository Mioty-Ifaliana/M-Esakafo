<?php

namespace App\Validator;

use App\Entity\Task;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class MaxEstimatesValidator extends ConstraintValidator
{
    public function validate($task, Constraint $constraint): void
    {
        if (!$task instanceof Task) {
            return;
        }

        if ($task->getDueDate() && $task->getCreatedAt()) {
            $interval = $task->getCreatedAt()->diff($task->getDueDate());
            $hoursBetweenDates = ($interval->days * 24) + $interval->h;

            if ($task->getEstimates() > $hoursBetweenDates) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ estimates }}', $task->getEstimates())
                    ->setParameter('{{ max }}', $hoursBetweenDates)
                    ->addViolation();
            }
        }
    }
}

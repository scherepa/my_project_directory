<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class AssignAgentDTO
{
    /**
     * @Assert\NotBlank()
     * @Assert\Type("integer")
     */
    public $userId;

    /**
     * @Assert\Type(type="integer", message="Manager ID must be an integer.")
     * @Assert\Choice(callback={"App\Validator\AssignAgentValidator", "validManagerIds"}, groups={"Custom"})
     */
    public $managerId; // nullable depending on the mode
}

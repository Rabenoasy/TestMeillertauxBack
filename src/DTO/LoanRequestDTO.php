<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use App\Service\Data\LoanConstant;
// This DTO class is used to validate and transfer data for loan requests.
// It uses Symfony's validation constraints to ensure that the data meets the required criteria.

class LoanRequestDTO
{
    #[Assert\NotBlank]
    #[Assert\Choice(choices: LoanConstant::ALLOWED_AMOUNTS)]
    public ?int $amount = null;
    
    #[Assert\NotBlank]
    #[Assert\Choice(choices: LoanConstant::ALLOWED_DURATIONS)]
    public ?int $duration = null;
    
    #[Assert\NotBlank]
    #[Assert\Type('string')]
    public ?string $name = null;
    
    #[Assert\NotBlank]
    #[Assert\Email]
    public ?string $email = null;
    
    #[Assert\NotBlank]
    #[Assert\Regex('/^\+?[0-9]{7,15}$/')]
    public ?string $phone = null;
}
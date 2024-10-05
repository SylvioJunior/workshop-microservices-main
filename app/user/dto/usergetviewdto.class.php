<?php

namespace App\User\Dto;

/**
 * Class UserGetDto
 *
 * Data Transfer Object para obter dados de usuários, excluindo o campo de senha.
 */
class UserGetViewDto extends UserGetDto
{
    public function __construct(array $data = [], bool $ignoreValidation = false, ?object &$parentObject = null)
    {
        parent::__construct($data, $ignoreValidation, $parentObject);
        unset($this->password);
        unset($this->passwordHash);
        unset($this->passwordSalt);
    }
}

<?php

namespace App\User\Dto;


/**
 * Class UserDto
 *
 * Data Transfer Object para usuários.
 */
class UserUpdateDataDto extends UserUpdateDto
{

    public function __construct(array $data = [], bool $ignoreValidation = false, ?object &$parentObject = null)
    {
        parent::__construct($data, $ignoreValidation, $parentObject);
        unset($this->password);
        unset($this->passwordHash);
        unset($this->passwordSalt);
        unset($this->externalId);
        unset($this->username);
        unset($this->email);
        unset($this->accountStatus);
        unset($this->emailVerified);
        unset($this->phoneVerified);
        unset($this->mfaEnabled);
    }
}

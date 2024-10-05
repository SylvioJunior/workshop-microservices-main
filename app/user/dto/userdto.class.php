<?php

namespace App\User\Dto;

use Core\Base\Dto;

/**
 * Class UserDto
 *
 * Data Transfer Object para usuários.
 */
class UserDto extends Dto
{
    /**
     * @Validation\NotEmpty(msg="ID externo é obrigatório")
     * @Validation\String(msg="ID externo deve ser uma string")
     */
    public ?string $externalId;

    /**
     * @Validation\NotEmpty(msg="Nome de usuário é obrigatório")
     * @Validation\String(msg="Nome de usuário deve ser uma string")
     * @Validation\MinLength(value="3", msg="Nome de usuário deve ter no mínimo 3 caracteres")
     * @Validation\MaxLength(value="50", msg="Nome de usuário deve ter no máximo 50 caracteres")
     * @Sanitization\SafeString()
     */
    public ?string $username;

    /**
     * @Validation\NotEmpty(msg="E-mail é obrigatório")
     * @Validation\Email(msg="E-mail inválido")
     * @Sanitization\Lower()
     */
    public ?string $email;

    /**
     * @Validation\String(msg="Número de telefone deve ser uma string")
     * @CustomValidation\PhoneNumber()
     */
    public ?string $phoneNumber;

    /**
     * @Validation\NotEmpty(msg="Senha é obrigatória")
     * @Validation\MinLength(value="8", msg="Senha deve ter no mínimo 8 caracteres")
     * @CustomValidation\StrongPassword()
     */
    public ?string $password;
    public ?string $passwordHash;
    public ?string $passwordSalt;

    /**
     * @Validation\Enum(options="['ACTIVE','INACTIVE','SUSPENDED', 'PENDING']", msg="Status da conta inválido")
     */
    public ?string $accountStatus;

    /**
     * @Validation\Boolean(msg="E-mail verificado deve ser um booleano")
     */
    public ?bool $emailVerified;

    /**
     * @Validation\Boolean(msg="Telefone verificado deve ser um booleano")
     */
    public ?bool $phoneVerified;

    /**
     * @Validation\Boolean(msg="MFA habilitado deve ser um booleano")
     */
    public ?bool $mfaEnabled;

    /**
     * @Validation\List(msg="Atributos customizados devem ser uma lista ou JSON")
     */
    public ?array $customAttributes;

    /**
     * Validação personalizada para senha forte
     *
     * @param string $value
     * @param array $params
     * @return bool|string
     */
    public function strongPassword(?string $value, ?array $params): bool|string
    {
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $value ?? '')) {
            return "Senha deve conter pelo menos uma letra maiúscula, uma minúscula, um número e um caractere especial.";
        }
        return true;
    }

    /**
     * Validação personalizada para número de telefone
     *
     * @param string $value
     * @param array $params
     * @return bool|string
     */
    public function phoneNumber(?string $value, ?array $params): bool|string
    {
        if (!preg_match('/^\+?\d{1,3}\d{1,14}$/', $value ?? '')) {
            return "Número de telefone inválido. Use o formato E.164.";
        }
        return true;
    }
}

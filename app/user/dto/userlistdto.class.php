<?php

declare(strict_types=1);

namespace App\User\Dto;

use Core\Base\Dto;

/**
 * Class UserListDto
 *
 * Data Transfer Object para listagem de usuários
 */
class UserListDto extends Dto
{
    /**
     * @var string|null
     * @Validation\Enum(options="['full','id','compact']", msg="Formato: O campo deve ser full, id ou compact.")
     * @Sanitization\DefaultIfEmpty(value="full")
     */
    public ?string $format;

    /**
     * @var string|null
     * @Validation\String(msg="Busca: Este campo deve estar em formato de texto")
     * @Validation\MaxLength(value="50", msg="Busca: O campo deve ter no máximo 50 caracteres.")
     */
    public ?string $search;

    /**
     * @var int|null
     * @Validation\Integer(msg="Página: O campo deve ser um número inteiro.")
     * @Sanitization\Integer()
     */
    public ?int $page;

    /**
     * @var int|null
     * @Validation\Integer(msg="Linhas por página: O campo deve ser um número inteiro.")
     * @Sanitization\Integer()
     */
    public ?int $rowsPerPage;

    /**
     * @var UserFilterDto
     */
    public ?UserFilterDto $filters;
}

/**
 * Class UserFilterDto
 *
 * Data Transfer Object para filtros de usuários
 */
class UserFilterDto extends Dto
{
    /**
     * @var string|null
     * @Validation\String(msg="Nome de usuário: Este campo deve estar em formato de texto")
     */
    public ?string $username;

    /**
     * @var string|null
     * @Validation\Email(msg="E-mail: Este campo deve ser um e-mail válido")
     */
    public ?string $email;

    /**
     * @var string|null
     * @Validation\Enum(options="['ACTIVE','INACTIVE','SUSPENDED']", msg="Status da conta: O campo deve ter uma opção válida.")
     */
    public ?string $accountStatus;

    /**
     * @var bool|null
     * @Validation\Boolean(msg="E-mail verificado: Este campo deve ser um booleano")
     */
    public ?bool $emailVerified;

    /**
     * @var bool|null
     * @Validation\Boolean(msg="Telefone verificado: Este campo deve ser um booleano")
     */
    public ?bool $phoneVerified;

    /**
     * @var bool|null
     * @Validation\Boolean(msg="MFA habilitado: Este campo deve ser um booleano")
     */
    public ?bool $mfaEnabled;

    /**
     * @var string|null
     * @Validation\String(msg="ID do grupo: Este campo deve estar em formato de texto")
     */
    public ?string $groupId;

    /**
     * @var string|null
     * @Validation\String(msg="ID do papel: Este campo deve estar em formato de texto")
     */
    public ?string $roleId;

    /**
     * @var string|null
     * @Validation\String(msg="Nome do provedor externo: Este campo deve estar em formato de texto")
     * @Validation\Enum(options="['GOOGLE','FACEBOOK','TWITTER','GITHUB']", msg="Nome do provedor externo: O campo deve ter uma opção válida.")
     */
    public ?string $externalProviderName;

    /**
     * @var UserDatePeriodDto
     */
    public ?UserDatePeriodDto $createdAtPeriod;

    /**
     * @var UserDatePeriodDto
     */
    public ?UserDatePeriodDto $updatedAtPeriod;
}

/**
 * Class UserDatePeriodDto
 *
 * Data Transfer Object para período de datas
 */
class UserDatePeriodDto extends Dto
{
    /**
     * @var string|null
     * @Validation\String(msg="Data inicial: Este campo deve estar em formato de texto")
     * @Validation\Date(msg="Data inicial: Este campo deve ser uma data")
     * @CustomValidation\StartDate()
     */
    public ?string $startDate;

    /**
     * @var string|null
     * @Validation\String(msg="Data final: Este campo deve estar em formato de texto")
     * @Validation\Date(msg="Data final: Este campo deve ser uma data")
     * @CustomValidation\EndDate()
     */
    public ?string $endDate;

    /**
     * Validate start date
     *
     * @param string|null $value
     * @return bool|string
     */
    public function startDate(?string $value): bool|string
    {
        $rawData = $this->raw();

        if ($value === '' && isset($rawData['endDate']) && $rawData['endDate'] !== "") {
            return "Data inicial: Você deve especificar a data inicial";
        } elseif (
            $value !== '' && isset($rawData['endDate']) && $rawData['endDate'] !== "" &&
            strtotime($value) > strtotime($rawData['endDate'])
        ) {
            return "Data inicial: Não pode ser maior que a data final.";
        }

        return true;
    }

    /**
     * Validate end date
     *
     * @param string|null $value
     * @return bool|string
     */
    public function endDate(?string $value): bool|string
    {
        $rawData = $this->raw();

        if ($value === '' && isset($rawData['startDate']) && $rawData['startDate'] !== "") {
            return "Data final: Você deve especificar a data final";
        } elseif (
            $value !== '' && isset($rawData['startDate']) && $rawData['startDate'] !== "" &&
            strtotime($value) < strtotime($rawData['startDate'])
        ) {
            return "Data final: Não pode ser menor que a data inicial.";
        }

        return true;
    }
}

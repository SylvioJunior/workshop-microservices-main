# Padrão de Projeto DTO para Listagem de Dados

O padrão de projeto DTO (Data Transfer Object) é utilizado para transferir dados entre diferentes camadas da aplicação. Este padrão é especialmente útil em situações onde é necessário listar dados com filtros e paginação.

## Estrutura da Classe DTO

A classe base DTO se encontra no arquivo `core/base/dto.class.php`. Esta classe fornece a funcionalidade de validação e sanitização dos dados através de anotações.

### Anotações Disponíveis

#### Validações

- `@Validation\NotEmpty(msg="Mensagem de erro")`: Verifica se o campo não está vazio.
- `@Validation\String(msg="Mensagem de erro")`: Verifica se o campo é uma string.
- `@Validation\Integer(msg="Mensagem de erro")`: Verifica se o campo é um número inteiro.
- `@Validation\Float(msg="Mensagem de erro")`: Verifica se o campo é um número decimal.
- `@Validation\Boolean(msg="Mensagem de erro")`: Verifica se o campo é um booleano.
- `@Validation\Email(msg="Mensagem de erro")`: Verifica se o campo é um e-mail válido.
- `@Validation\Url(msg="Mensagem de erro")`: Verifica se o campo é uma URL válida.
- `@Validation\CpfCnpj(msg="Mensagem de erro")`: Verifica se o campo é um CPF ou CNPJ válido.
- `@Validation\Date(msg="Mensagem de erro")`: Verifica se o campo é uma data válida.
- `@Validation\Enum(options="[opções]", msg="Mensagem de erro")`: Verifica se o campo está entre as opções fornecidas.
- `@Validation\MinLength(value="valor", msg="Mensagem de erro")`: Verifica se o campo tem um comprimento mínimo.
- `@Validation\MaxLength(value="valor", msg="Mensagem de erro")`: Verifica se o campo tem um comprimento máximo.
- `@Validation\Interval(min="valor", max="valor", msg="Mensagem de erro")`: Verifica se o campo está dentro do intervalo fornecido.
- `@Validation\Currency(msg="Mensagem de erro")`: Verifica se o campo está em formato de moeda.
- `@Validation\List(msg="Mensagem de erro")`: Verifica se o campo é uma lista ou JSON.

#### Sanitizações

- `@Sanitization\SafeString()`: Sanitiza o campo para uma string segura.
- `@Sanitization\Upper()`: Converte o campo para letras maiúsculas.
- `@Sanitization\Lower()`: Converte o campo para letras minúsculas.
- `@Sanitization\AlphaNum()`: Sanitiza o campo para conter apenas letras e números.
- `@Sanitization\Truncate(length="valor":int)`: Trunca o campo para o comprimento fornecido.
- `@Sanitization\Currency()`: Sanitiza o campo para formato de moeda.
- `@Sanitization\CpfCnpj()`: Sanitiza o campo para formato de CPF ou CNPJ.

#### Validações e Sanitizações Personalizadas

- `@CustomValidation\NomeDoMetodo()`: Executa uma validação personalizada definida na classe.
- `@CustomSanitization\NomeDoMetodo()`: Executa uma sanitização personalizada definida na classe.

## Exemplo de Uso

Abaixo está um exemplo de como um DTO pode ser escrito e utilizado para filtros de listagem:

```php
// Exemplo de um DTO genérico

declare(strict_types=1);

namespace App\Transactions\Dto;

use Core\Base\Dto;

/*

- Class TransactionListDto
-
- Data Transfer Object for Transaction List
  */
  class TransactionListDto extends Dto
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
       * @var TransactionFilterDto
       */
      public ?TransactionFilterDto $filters;

  }

/*

- Class TransactionFilterDto
-
- Data Transfer Object for Transaction Filters
  */
  class TransactionFilterDto extends Dto
  {
        /**
         * @var string|null
        * @Validation\NotEmpty(msg="ID da empresa: Você deve especificar a empresa")
        * @Validation\String(msg="ID da empresa: Este campo deve estar em formato de texto")
        */
        public ?string $companyId;

      /**
       * @var TransactionDatePeriodDto
       */
      public ?TransactionDatePeriodDto $datePeriod;

      /**
       * @var string|null
       * @Validation\String(msg="Ano/Mês: Este campo deve estar em formato de texto")
       * @CustomValidation\yearMonth()
       * @CustomSanitization\calculateDatePeriod()
       */
      public ?string $yearMonth;

      /**
       * @var string|null
       * @Validation\String(msg="Tipo: Este campo deve estar em formato de texto")
       * @Validation\Enum(options="['RevenueRecognition','RevenueReceivableSettlement','RevenuePrepayment','ExpenseRecognition','ExpensePayableSettlement','ExpensePrepayment','DebtIssuance','DebtRepayment','ThirdPartyFinancingIssuance','ThirdPartyFinancingRepayment','AssetAmortization','AssetDepreciation','AssetPurchase','AssetTransfer','EquityIncrease','EquityDecrease','EquityProfitLossSettlement','EquityDividendPayment','Other']", msg="Tipo: O campo deve ter uma opção válida.")
       */
      public ?string $type;

      /**
       * @var array|null
       * @Validation\List(msg="Nome da tag: Este campo deve ser uma lista")
       * @CustomValidation\listOfString()
       */
      public ?array $tagName;

      /**
       * @var string|null
       * @Validation\String(msg="Tag primária: Este campo deve estar em formato de texto")
       */
      public ?string $primaryTag;

      /**
       * @var string|null
       * @Validation\String(msg="Tag secundária: Este campo deve estar em formato de texto")
       */
      public ?string $secondaryTag;

      /**
       * @var string|null
       * @Validation\String(msg="Descrição: Este campo deve estar em formato de texto")
       * @Sanitization\SafeString()
       * @Sanitization\Truncate(length="255")
       */
      public ?string $description;

      /**
       * @var float|null
       * @Validation\Currency(msg="Valor: O campo deve estar em formato de moeda.")
       * @Validation\Interval(min="0.01",max="99999999999.99", msg="Valor: O campo deve ter um valor entre 0,01 e 99.999.999.999,99")
       * @Sanitization\Currency()
       */
      public ?float $amount;

      /**
       * @var string|null
       * @Validation\String(msg="ID do grupo: Este campo deve estar em formato de texto")
       */
      public ?string $groupId;

      /**
       * @var string|null
       * @Validation\String(msg="Status: Este campo deve estar em formato de texto")
       * @Validation\Enum(options="['Consistent','NotConsistent','NotChecked','Checking','Error']", msg="Status: O campo deve ter uma opção válida.")
       */
      public ?string $status;

      /**
       * @var string|null
       * @Validation\String(msg="ID do usuário: Este campo deve estar em formato de texto")
       */
      public ?string $userId;

      /**
       * Validate year and month format
       *
       * @param string|null $value
       * @return bool|string
       */
      public function yearMonth(?string $value): bool|string
      {
          if ($value === null || $value === '') {
              return true;
          }

          if (preg_match('/^\d{4}-\d{2}$/', $value)) {
              [$year, $month] = explode('-', $value);
              if (checkdate((int)$month, 1, (int)$year)) {
                  return true;
              }
          }

          return "Ano/Mês: O formato deve ser AAAA-MM.";
      }

      /**
       * Calculate date period based on year and month
       *
       * @param string|null $value
       * @return string|null
       */
      public function calculateDatePeriod(?string $value): ?string
      {
          if ($value !== null && $value !== '') {
              $this->datePeriod = new TransactionDatePeriodDto([
                  'startDate' => date("Y-m-01", strtotime($value)),
                  'endDate' => date("Y-m-t", strtotime($value)),
              ]);
          }

          return $value;
      }

      /**
       * Validate list of strings
       *
       * @param array|null $list
       * @return bool|string
       */
      public function listOfString(mixed $list): bool|string
      {
          if (is_array($list)) {
              foreach ($list as $item) {
                  if (!is_string($item)) {
                      return "Nome da tag: Esta lista deve conter apenas itens de texto.";
                  }
              }
          }

          return true;
      }

  }

/*

- Class TransactionDatePeriodDto
-
- Data Transfer Object for Transaction Date Period
  */
  class TransactionDatePeriodDto extends Dto
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
```

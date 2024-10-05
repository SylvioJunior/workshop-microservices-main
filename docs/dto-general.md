# Usando DTOs

O DTO (Data Transfer Object) que serve para transferência de dados entre a aplicação de forma interna e para o cliente. Esta classe permite realizar validações e sanitizações nativas ou personalizadas, através de anotações nas variáveis da classe.

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

Abaixo está um exemplo de como um DTO pode ser escrito e utilizado:

```php
// Exemplo de um DTO genérico

use Core\Base\Dto;

/**
 * Class ExemploDto
 *
 * Data Transfer Object de exemplo.
 */
class ExemploDto extends Dto
{
    /**
     * @Validation\NotEmpty(msg="Campo obrigatório: Este campo deve ser preenchido.")
     * @Validation\String(msg="Campo obrigatório: Este campo deve ser uma string.")
     */
    public ?string $campoObrigatorio;

    /**
     * @Validation\Integer(msg="Idade: Este campo deve ser um número inteiro.")
     * @Validation\Interval(min="0":int, max="120":int, msg="Idade: Deve estar entre 0 e 120.")
     */
    public ?int $idade;

    /**
     * @Validation\Email(msg="E-mail: O campo deve ser um e-mail válido.")
     * @Sanitization\Lower()
     */
    public ?string $email;

    /**
     * @Validation\Url(msg="Site: O campo deve ser uma URL válida.")
     */
    public ?string $site;

    /**
     * @Validation\CpfCnpj(msg="CPF/CNPJ: O campo deve ser um CPF ou CNPJ válido.")
     * @Sanitization\CpfCnpj()
     */
    public ?string $cpfCnpj;

    /**
     * @Validation\Date(msg="Data de Nascimento: O campo deve ser uma data válida.")
     */
    public ?string $dataNascimento;

    /**
     * @Validation\Enum(options="['Ativo','Inativo']", msg="Status: O campo deve ser Ativo ou Inativo.")
     */
    public ?string $status;

    /**
     * @Validation\MinLength(value="8", msg="Senha: Deve ter no mínimo 8 caracteres.")
     * @CustomValidation\StrongPassword()
     */
    public ?string $senha;

    /**
     * @Sanitization\SafeString()
     * @Sanitization\Upper()
     */
    public ?string $codigo;

    /**
     * Validação personalizada para senha forte
     *
     * @param string $value
     * @param array $params
     * @return bool|string
     */
    public function strongPassword(string $value, array $params): bool|string
    {
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $value)) {
            return "Senha: Deve conter pelo menos uma letra maiúscula, uma minúscula, um número e um caractere especial.";
        }
        return true;
    }
}
```

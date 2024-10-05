# Usando Controladores

O Controller e serve para criar controladores na aplicação que recebem invocações de usuários e consumidores, para que acessem serviços internos. Alguns exemplos já implementados são estes:

- `ImportController`
- `TransactionController`

## Estrutura da Classe Controller

A classe base Controller se encontra no arquivo `core/base/controller.class.php`. Esta classe fornece a funcionalidade de manipulação de requisições e respostas, além de validações e tratamento de exceções.

### Métodos Comuns

Os controladores geralmente possuem métodos comuns para manipulação de dados, como:

- `list()`: Lista os itens com filtros e paginação opcionais.
- `get()`: Obtém um item específico.
- `create()`: Cria um novo item.
- `update()`: Atualiza um item existente.
- `delete()`: Deleta um item.

## Exemplo de Uso

Abaixo está um exemplo de como um controlador pode ser escrito e utilizado:

### ImportController

```php
use Core\Base\Controller;
use Core\Base\Request;
use Core\Exceptions\ItemNotFoundException;

/**
 * Class ExemploCrudController
 *
 * Exemplo de um controlador CRUD.
 */
class ExemploCrudController extends Controller
{
    /**
     * Lista os itens com filtros e paginação opcionais.
     *
     * @return array
     */
    public static function list(): array
    {
        Request::validateTypes([
            'search' => ['string', 'null'],
            'page' => ['integer', 'null'],
            'filters' => ['array', 'null'],
            'format' => ['string', 'null'],
            'rowsPerPage' => ['integer', 'null']
        ]);

        extract(Request::$payload ?? [], EXTR_SKIP);

        $page = intval($page ?? 1);
        $rowsPerPage = intval($rowsPerPage ?? 10);

        ExemploService::setContext(
            Request::$workspace,
            Request::$user
        );

        $list = ExemploService::list(
            $search ?? null,
            $page,
            $filters ?? [],
            $format ?? 'compact',
            $rowsPerPage
        );

        return [
            'status' => 200,
            'meta' => [
                'rows_per_page' => $rowsPerPage,
                'page' => $page ?? 1
            ],
            'data' => $list
        ];
    }

    /**
     * Obtém um item específico.
     *
     * @return array
     * @throws ItemNotFoundException
     */
    public static function get(): array
    {
        Request::validateTypes([
            'id' => ['string', 'null'],
            'format' => ['string', 'null']
        ]);

        extract(Request::$data ?? [], EXTR_SKIP);

        ExemploService::setContext(
            Request::$workspace,
            Request::$user
        );

        $filter = compact('id');

        $item = ExemploService::get($filter, $format ?? 'compact');

        if (!$item) {
            throw new ItemNotFoundException("Item não encontrado.");
        }

        return [
            'status' => 200,
            'data' => $item
        ];
    }

    /**
     * Cria um novo item.
     *
     * @return array
     */
    public static function create(): array
    {
        Request::validateTypes([]);

        ExemploService::setContext(
            Request::$workspace,
            Request::$user
        );

        $item = ExemploService::create(
            Request::$payload
        );

        return [
            'status' => 200,
            'data' => $item
        ];
    }

    /**
     * Atualiza um item existente.
     *
     * @return array
     */
    public static function update(): array
    {
        Request::validateTypes([
            'id' => ['string', 'null']
        ]);

        extract(Request::$data ?? [], EXTR_SKIP);

        ExemploService::setContext(
            Request::$workspace,
            Request::$user
        );

        $filter = compact('id');

        $item = ExemploService::update(
            $filter,
            Request::$payload,
        );

        return [
            'status' => 200,
            'data' => $item
        ];
    }

    /**
     * Deleta um item.
     *
     * @return array
     * @throws ItemNotFoundException
     */
    public static function delete(): array
    {
        Request::validateTypes([
            'id' => ['string', 'null']
        ]);

        extract(Request::$data ?? [], EXTR_SKIP);

        ExemploService::setContext(
            Request::$workspace,
            Request::$user
        );

        $filter = compact('id');

        $item = ExemploService::delete($filter);

        if (!$item) {
            throw new ItemNotFoundException("Item não encontrado.");
        }

        return [
            'status' => 200
        ];
    }
}

```

# Documentação de Rotas

A estrutura de rotas é um componente fundamental na aplicação, permitindo que os utilizadores/consumidores acessem serviços internos de forma organizada e segura. Esta documentação visa fornecer uma visão geral sobre como as rotas devem ser criadas, configuradas e utilizadas na aplicação.

## Tipos de Rotas

A aplicação suporta os seguintes tipos de rotas:

- **GET**: Utilizada para obter informações de um recurso.
- **POST**: Utilizada para criar um novo recurso.
- **PUT**: Utilizada para atualizar um recurso existente.
- **DELETE**: Utilizada para deletar um recurso.
- **CMD**: Utilizada para executar comandos específicos, geralmente relacionados a operações batch ou processos em segundo plano.
- **LAMBDA**: Utilizada para executar funções lambda, que são funções anônimas que podem ser executadas em resposta a eventos específicos.
- **INTERNAL**: Utilizada para rotas internas, que não devem ser acessadas diretamente pelo usuário/consumidor.

## Configuração de Rotas

Para configurar uma rota, é necessário seguir os seguintes passos:

1. **Definir a Rota**: Identificar a rota que deseja criar e definir seu caminho, método HTTP e ação a ser realizada.
2. **Criar o Controlador**: Criar um controlador que irá lidar com a solicitação e realizar a ação desejada.
3. **Registrar a Rota**: Utilizar a classe `Router` para registrar a rota criada, associando-a ao controlador e ao método HTTP correspondente.
4. **Configurar Middleware (Opcional)**: Se necessário, configurar middleware para autenticação, validação ou outras funções específicas.

## Exemplo de Configuração de Rota

Abaixo está um exemplo genérico de como configurar uma rota:

```php
Router::get(
    'exemplo',
    [ExemploController::class, 'get'],
    [AuthenticationController::class, 'tokenAndWorkspaceValidation']
);
```

Neste exemplo, estamos criando uma rota GET para o caminho `/exemplo`, que irá chamar o método `get` do controlador `ExemploController`. Além disso, estamos configurando o middleware `tokenAndWorkspaceValidation` do controlador `AuthenticationController` para autenticar e validar a solicitação.

## Utilização de Rotas

Para utilizar uma rota, basta enviar uma solicitação HTTP para o caminho configurado, utilizando o método HTTP correspondente. Por exemplo, para a rota criada acima, você pode enviar uma solicitação GET para `/exemplo`.

## Conclusão

A estrutura de rotas é fundamental para a organização e segurança da aplicação. Ao seguir os passos de configuração e utilizar as rotas de forma adequada, você pode garantir que os serviços internos sejam acessados de forma segura e eficiente.

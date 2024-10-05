**Organize code**
Organize, otimize o código, use padrões PSR PHP e strict_types, coloque os niveis dos namespaces começando com letra maiuscula, separe, espace e organize os use namespace por contexto e documente o código em inglês

**Criar classe de DTO basica**
Preciso criar uma classe UserDto, que implementa os campos do usuário do seguinte projeto:
@premissas-iniciais.md
@modelagem.md

A forma de se criar DTO no projeto consta na seguinte documentação
@dtos.md

A modelagem do banco está no seguinte arquivo
@schema.prisma

Crie a classe DTO com todas as validações e sanitizações que você entender serem cabíveis para este tipo de dado.

**Criar classe de DTO listagem**
Preciso criar uma classe UserListDto, que implementa uma filtragem de lista de usuários do seguinte projeto:
@premissas-iniciais.md
@modelagem.md

A forma de se criar DTO para listagem no projeto consta na seguinte documentação
@dto-listing.md

A modelagem do banco está no seguinte arquivo
@schema.prisma

Crie a classe DTO com todos os filtros, validações e sanitizações que você entender serem cabíveis para este tipo de dado.

**Criar classe de serviço**
Preciso criar uma classe Services CRUD para o cadastro de usuários do projeto @premissas-iniciais.md . A modelagem do projeto é esta aqui @modelagem.md .

A forma de se criar uma classe de serviço está nesta documentação @services.md

A modelagem do banco está em @schema.prisma .

Preciso que crie a classe de serviço com todos os métodos CRUD e listagem implementados.

**Criar classe de controlador**
Crie um controller, implementando os métodos de @userservices.class.php .

Observe a documentação em @controllers.md para se basear

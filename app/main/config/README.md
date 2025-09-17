# Configuração do Banco de Dados
## Sistema de Gestão Escolar - Merenda

### Arquivos de Configuração

#### 1. `config.php`
Contém todas as configurações do sistema, incluindo:
- Configurações do banco de dados
- Configurações da aplicação
- Configurações de segurança
- Configurações de ambiente (DEBUG_MODE)

#### 2. `Database.php`
Classe principal para gerenciar a conexão com o banco de dados:
- Implementa o padrão Singleton
- Gerencia conexões PDO
- Fornece métodos para consultas e transações
- Trata erros de forma adequada

#### 3. `init.php`
Arquivo de inicialização que:
- Carrega as configurações
- Inicializa a sessão
- Fornece funções helper úteis
- Facilita o uso da conexão em outros arquivos

### Como Usar

#### Método 1: Usando a classe Database diretamente
```php
<?php
require_once 'config/config.php';
require_once 'config/Database.php';

$db = Database::getInstance();

// Consulta SELECT
$usuarios = $db->query("SELECT * FROM usuarios WHERE ativo = ?", [true]);

// Inserção
$sucesso = $db->execute("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)", 
    ['João Silva', 'joao@email.com', password_hash('123456', PASSWORD_DEFAULT)]);

// Último ID inserido
$ultimoId = $db->lastInsertId();
?>
```

#### Método 2: Usando o arquivo init.php (Recomendado)
```php
<?php
require_once 'config/init.php';

// Consulta usando função helper
$usuarios = dbQuery("SELECT * FROM usuarios WHERE ativo = ?", [true]);

// Inserção usando função helper
$sucesso = dbExecute("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)", 
    ['Maria Santos', 'maria@email.com', hashPassword('123456')]);

// Último ID
$ultimoId = dbLastInsertId();
?>
```

### Configuração Inicial

#### 1. Configurar o Banco
1. Edite o arquivo `config.php` com suas credenciais:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'escola_merenda');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha');
```

#### 2. Criar o Banco de Dados
Execute o script SQL fornecido:
```bash
mysql -u root -p < database_setup.sql
```

Ou importe manualmente no phpMyAdmin/MySQL Workbench.

#### 3. Testar a Conexão
Acesse: `http://localhost/seu_projeto/config/test_connection.php`

### Funções Helper Disponíveis (init.php)

#### Banco de Dados
- `getDatabase()` - Obtém instância da conexão
- `dbQuery($sql, $params)` - Executa SELECT
- `dbExecute($sql, $params)` - Executa INSERT/UPDATE/DELETE
- `dbLastInsertId()` - Último ID inserido

#### Segurança
- `sanitize($data)` - Sanitiza dados de entrada
- `hashPassword($password)` - Gera hash da senha
- `verifyPassword($password, $hash)` - Verifica senha
- `isValidEmail($email)` - Valida email

#### Utilitários
- `redirect($url)` - Redireciona para URL
- `showMessage($message, $type)` - Exibe mensagens Bootstrap

### Estrutura do Banco de Dados

#### Tabelas Principais:
- **usuarios** - Administradores e funcionários
- **escolas** - Cadastro das escolas
- **alimentos** - Catálogo de alimentos
- **estoque** - Controle de estoque por escola
- **cardapios** - Cardápios planejados
- **cardapio_itens** - Itens de cada cardápio
- **movimentacoes_estoque** - Histórico de movimentações
- **relatorios_consumo** - Relatórios de consumo

### Credenciais Padrão
- **Email:** admin@escola.com
- **Senha:** password

### Configurações de Ambiente

#### Desenvolvimento (DEBUG_MODE = true)
- Exibe erros detalhados
- Logs de erro ativados
- Informações de debug visíveis

#### Produção (DEBUG_MODE = false)
- Erros genéricos para usuários
- Logs apenas no servidor
- Informações sensíveis ocultas

### Segurança

#### Medidas Implementadas:
- Prepared Statements (previne SQL Injection)
- Hash de senhas com PASSWORD_DEFAULT
- Sanitização de dados de entrada
- Validação de tipos de dados
- Controle de sessões
- Logs de erro seguros

### Troubleshooting

#### Erro de Conexão:
1. Verifique se o MySQL está rodando
2. Confirme as credenciais no config.php
3. Verifique se o banco existe
4. Confirme as permissões do usuário

#### Erro de Charset:
- Certifique-se que o banco usa utf8mb4
- Verifique a configuração de collation

#### Erro de Permissões:
- Verifique permissões do usuário MySQL
- Confirme se o usuário pode acessar o banco específico
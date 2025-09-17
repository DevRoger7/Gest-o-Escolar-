# Implementação do INNER JOIN - Sistema de Login

## 📋 Resumo da Implementação

Foi implementada uma estrutura de banco de dados normalizada com **INNER JOIN** entre as tabelas `pessoas` e `usuarios`, separando dados pessoais das credenciais de acesso.

## 🗄️ Estrutura do Banco de Dados

### Tabela `pessoas`
```sql
CREATE TABLE pessoas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(14) UNIQUE NOT NULL,        -- CPF está aqui
    email VARCHAR(100) UNIQUE NOT NULL,
    telefone VARCHAR(20),
    endereco TEXT,
    data_nascimento DATE,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Tabela `usuarios`
```sql
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pessoa_id INT NOT NULL,                 -- Chave estrangeira
    senha VARCHAR(255) NOT NULL,            -- Senha está aqui
    tipo ENUM('admin', 'funcionario', 'nutricionista') DEFAULT 'funcionario',
    ultimo_login TIMESTAMP NULL,
    tentativas_login INT DEFAULT 0,
    bloqueado BOOLEAN DEFAULT FALSE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pessoa_id) REFERENCES pessoas(id) ON DELETE CASCADE
);
```

## 🔗 Query com INNER JOIN

### Query Implementada no ModelLogin.php:
```sql
SELECT 
    u.id as usuario_id,
    u.pessoa_id,
    u.senha,
    u.tipo,
    u.bloqueado,
    u.tentativas_login,
    p.id as pessoa_id,
    p.nome,
    p.cpf,
    p.email,
    p.ativo
FROM usuarios u
INNER JOIN pessoas p ON u.pessoa_id = p.id
WHERE p.cpf = ? AND p.ativo = 1
```

### Explicação do INNER JOIN:
- **usuarios u**: Alias para tabela usuarios
- **pessoas p**: Alias para tabela pessoas  
- **ON u.pessoa_id = p.id**: Condição de junção
- **WHERE p.cpf = ?**: Busca pelo CPF na tabela pessoas
- **AND p.ativo = 1**: Apenas pessoas ativas

## 📁 Arquivos Modificados/Criados

### 1. `database_setup.sql` ✅ Atualizado
- Criada tabela `pessoas` com CPF
- Modificada tabela `usuarios` com relacionamento
- Inseridos dados de teste com relacionamento

### 2. `modelLogin.php` ✅ Completamente Reescrito
- Implementado INNER JOIN
- Adicionada validação de CPF
- Implementado controle de tentativas
- Adicionada verificação de hash de senha
- Implementado sistema de bloqueio
- Adicionado tratamento de erros

### 3. `teste_login.php` ✅ Criado
- Interface de teste do sistema
- Demonstração do funcionamento
- Usuários de exemplo
- Documentação visual

## 🔐 Melhorias de Segurança Implementadas

### 1. **Prepared Statements**
```php
$resultado = $this->db->query($sql, [$cpf]);
```
- Previne SQL Injection
- Parâmetros são escapados automaticamente

### 2. **Hash de Senhas**
```php
if (password_verify($senha, $usuario['senha'])) {
    // Login válido
}
```
- Usa `password_verify()` do PHP
- Senhas nunca são comparadas em texto plano

### 3. **Validação de CPF**
```php
private function validarCPF($cpf) {
    // Algoritmo oficial de validação
}
```
- Verifica dígitos verificadores
- Rejeita CPFs inválidos

### 4. **Controle de Tentativas**
```php
private function incrementarTentativasLogin($usuarioId) {
    $sql = "UPDATE usuarios SET 
            tentativas_login = tentativas_login + 1,
            bloqueado = CASE WHEN tentativas_login >= 4 THEN 1 ELSE 0 END
            WHERE id = ?";
}
```
- Bloqueia após 5 tentativas
- Previne ataques de força bruta

### 5. **Sanitização de Dados**
```php
private function limparCPF($cpf) {
    return preg_replace('/[^0-9]/', '', $cpf);
}
```
- Remove caracteres especiais
- Padroniza formato de entrada

## 🧪 Como Testar

### 1. **Configurar Banco de Dados**
```bash
# No MySQL/phpMyAdmin, execute:
mysql -u root -p < database_setup.sql
```

### 2. **Configurar Credenciais**
Edite `config/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'escola_merenda');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha');
```

### 3. **Testar Conexão**
Acesse: `http://localhost:8081/config/test_connection.php`

### 4. **Testar Login**
Acesse: `http://localhost:8081/teste_login.php`

## 👥 Usuários de Teste

| Nome | CPF | Email | Tipo | Senha |
|------|-----|-------|------|-------|
| Administrador do Sistema | 000.000.000-00 | admin@escola.com | admin | password |
| Maria Silva Santos | 111.222.333-44 | maria@escola.com | funcionario | password |
| João Carlos Oliveira | 555.666.777-88 | joao@escola.com | nutricionista | password |

## 📊 Vantagens da Nova Estrutura

### 1. **Normalização**
- Dados pessoais separados das credenciais
- Evita redundância de informações
- Facilita manutenção

### 2. **Segurança**
- Controle granular de acesso
- Auditoria de tentativas de login
- Bloqueio automático

### 3. **Flexibilidade**
- Fácil adição de novos campos pessoais
- Múltiplos tipos de usuário
- Histórico de acessos

### 4. **Performance**
- Índices otimizados
- Consultas eficientes
- Relacionamentos bem definidos

## 🔄 Migração de Dados Existentes

Se você já tinha dados na estrutura antiga:

```sql
-- 1. Backup dos dados existentes
CREATE TABLE usuarios_backup AS SELECT * FROM usuarios;

-- 2. Migrar dados para nova estrutura
INSERT INTO pessoas (nome, cpf, email, ativo)
SELECT nome, cpf, email, ativo FROM usuarios_backup;

INSERT INTO usuarios (pessoa_id, senha, tipo)
SELECT p.id, u.senha, u.tipo 
FROM usuarios_backup u
JOIN pessoas p ON p.cpf = u.cpf;

-- 3. Verificar migração e remover backup
-- DROP TABLE usuarios_backup;
```

## 🚀 Próximos Passos

1. **Implementar Controllers** que usem o novo ModelLogin
2. **Criar Views** de login com a nova estrutura
3. **Implementar Middleware** de autenticação
4. **Adicionar Logs** de auditoria
5. **Criar Sistema** de recuperação de senha

## 📞 Suporte

Em caso de problemas:
1. Verifique se o MySQL está rodando
2. Confirme as credenciais no config.php
3. Execute o script SQL completo
4. Teste a conexão primeiro
5. Verifique os logs de erro do PHP

---

**✅ Implementação Concluída com Sucesso!**

O sistema agora usa INNER JOIN corretamente entre as tabelas `pessoas` e `usuarios`, com o CPF na tabela `pessoas` e a senha na tabela `usuarios`, exatamente como solicitado.
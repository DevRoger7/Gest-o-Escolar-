# Scripts para Popular Banco de Dados com Dados de Teste

Este diretório contém scripts para popular o banco de dados com dados de teste para desenvolvimento e testes do sistema.

## ⚠️ ATENÇÃO

**Estes scripts devem ser executados APENAS em ambiente de desenvolvimento/teste!**

Nunca execute em produção, pois irão inserir dados fictícios no banco.

## 📋 Arquivos Disponíveis

### 1. `popular_banco_teste.sql`
Script SQL completo com todos os dados de teste.

**Conteúdo:**
- 3 Escolas
- 9 Séries (1º ao 9º ano)
- 12 Disciplinas
- 8 Turmas
- 21 Pessoas (10 alunos, 5 professores, 2 gestores, 2 funcionários, 2 responsáveis)
- 8 Usuários
- Lotações (professores, gestores, funcionários)
- Matrículas de alunos em turmas
- Vínculos de professores com turmas
- 18 Notas
- 15 Frequências
- 5 Planos de Aula
- 6 Habilidades BNCC

### 2. `popular_banco_teste.php`
Script PHP alternativo (parcial) para execução via navegador.

## 🚀 Como Executar

### Opção 1: Via phpMyAdmin (Recomendado)

1. Acesse o phpMyAdmin
2. Selecione o banco de dados `escola_merenda`
3. Vá na aba "SQL"
4. Copie e cole o conteúdo do arquivo `popular_banco_teste.sql`
5. Clique em "Executar"

### Opção 2: Via Linha de Comando

```bash
mysql -u root -p escola_merenda < app/main/database/popular_banco_teste.sql
```

### Opção 3: Via PHP (Navegador)

1. Acesse: `http://localhost/Gest-o-Escolar-/app/main/database/popular_banco_teste.php`
2. O script irá executar (parcialmente - use o SQL para dados completos)

## 🔐 Credenciais de Acesso

Após executar o script, você poderá acessar o sistema com as seguintes credenciais:

### Administrador
- **Username:** `admin`
- **Senha:** `123456`
- **Role:** ADM

### Gestor
- **Username:** `roberto.alves`
- **Senha:** `123456`
- **Role:** GESTAO

### Professor
- **Username:** `maria.silva`
- **Senha:** `123456`
- **Role:** PROFESSOR

**Outros professores:**
- `joao.santos` (senha: 123456)
- `ana.costa` (senha: 123456)
- `pedro.oliveira` (senha: 123456)
- `carla.mendes` (senha: 123456)

## 📊 Dados Inseridos

### Escolas
1. Escola Municipal João Silva
2. Escola Municipal Maria José
3. Escola Municipal Pedro Alves

### Turmas Criadas
- 1º Ano A (Matutino) - Escola João Silva
- 1º Ano B (Vespertino) - Escola João Silva
- 2º Ano A (Matutino) - Escola João Silva
- 3º Ano A (Matutino) - Escola João Silva
- 1º Ano A (Matutino) - Escola Maria José
- 2º Ano A (Matutino) - Escola Maria José
- 6º Ano A (Matutino) - Escola Pedro Alves
- 7º Ano A (Matutino) - Escola Pedro Alves

### Alunos
10 alunos distribuídos nas turmas acima, com notas e frequências já cadastradas.

### Professores
5 professores com lotações em diferentes escolas e turmas.

## 🔄 Limpar Dados (Opcional)

Se você quiser limpar os dados antes de inserir novamente, descomente as linhas DELETE no início do arquivo SQL:

```sql
DELETE FROM nota;
DELETE FROM frequencia;
DELETE FROM plano_aula;
-- ... etc
```

**CUIDADO:** Isso irá deletar TODOS os dados das tabelas, não apenas os de teste!

## 📝 Notas Importantes

1. **Senha padrão:** Todos os usuários têm a senha `123456` (hash bcrypt)
2. **Ano letivo:** Os dados estão configurados para o ano 2025
3. **Datas:** As datas de matrícula e outras são de janeiro de 2025
4. **CPFs:** Os CPFs são fictícios (11111111111, 22222222222, etc.)

## 🐛 Solução de Problemas

### Erro: "Duplicate entry"
Se você receber erros de entrada duplicada, significa que alguns dados já existem. Você pode:
1. Limpar os dados primeiro (descomente as linhas DELETE)
2. Ou ajustar os IDs no script para não conflitar

### Erro: "Foreign key constraint"
Certifique-se de que as tabelas existem e estão com a estrutura correta.

### Erro: "Table doesn't exist"
Execute primeiro os scripts de criação de tabelas em `app/main/database/`.

## 📞 Suporte

Se encontrar problemas, verifique:
1. Se todas as tabelas existem
2. Se as foreign keys estão corretas
3. Se há dados conflitantes no banco

---

**Última atualização:** Dezembro 2025




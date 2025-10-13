# Instalação do Módulo de Estoque Central

## 📋 Pré-requisitos

- Sistema SIGAE instalado e funcionando
- Acesso ao banco de dados MySQL/MariaDB
- Usuário com perfil Administrador (ADM)

## 🚀 Passo a Passo da Instalação

### 1. Criar a Tabela no Banco de Dados

Execute o arquivo SQL no seu banco de dados:

```bash
mysql -u seu_usuario -p seu_banco_de_dados < app/main/config/create_estoque_central.sql
```

Ou através do phpMyAdmin:
1. Acesse o phpMyAdmin
2. Selecione seu banco de dados
3. Vá na aba "SQL"
4. Copie e cole o conteúdo do arquivo `create_estoque_central.sql`
5. Clique em "Executar"

### 2. Verificar Permissões

Certifique-se de que o usuário do sistema tem permissões adequadas:
- O módulo é acessível apenas para usuários com `tipo = 'ADM'`
- A verificação é feita automaticamente no início do arquivo `gestao_estoque_central.php`

### 3. Acessar o Sistema

1. Faça login no SIGAE com um usuário Administrador
2. No menu lateral, clique em **"Estoque Central"**
3. Você será direcionado para a página de gestão de estoque

## ✅ Verificação da Instalação

### Teste Básico

1. Acesse a página de Estoque Central
2. Clique na aba "Novo Item"
3. Preencha os campos obrigatórios:
   - Nome: "Teste"
   - Categoria: "Material Escolar"
   - Quantidade: 1
   - Unidade de Medida: "UN"
   - Valor Unitário: 1.00
   - Estoque Mínimo: 1
   - Localização: "Teste"
   - Status: "ativo"
4. Clique em "Cadastrar Item"
5. Verifique se a mensagem de sucesso aparece
6. Verifique se o item aparece na lista

### Verificar Estatísticas

Na aba "Lista de Itens", verifique se os cards de estatísticas estão funcionando:
- Total de Itens
- Itens Ativos
- Estoque Baixo
- Valor Total

## 🗄️ Estrutura da Tabela

A tabela `estoque_central` contém os seguintes campos:

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | INT(11) | ID único do item (auto-incremento) |
| nome | VARCHAR(255) | Nome do item |
| descricao | TEXT | Descrição detalhada |
| categoria | VARCHAR(100) | Categoria do item |
| quantidade | DECIMAL(10,2) | Quantidade em estoque |
| unidade_medida | VARCHAR(20) | Unidade de medida (UN, CX, PC, etc.) |
| valor_unitario | DECIMAL(10,2) | Valor unitário do item |
| data_aquisicao | DATE | Data de aquisição |
| fornecedor | VARCHAR(255) | Nome do fornecedor |
| localizacao | VARCHAR(255) | Localização física no estoque |
| estoque_minimo | DECIMAL(10,2) | Quantidade mínima para alertas |
| status | ENUM | Status: ativo ou inativo |
| obs | TEXT | Observações adicionais |
| criado_em | TIMESTAMP | Data/hora de criação (automático) |
| atualizado_em | TIMESTAMP | Data/hora da última atualização (automático) |

## 📊 Dados de Exemplo

O arquivo SQL inclui 15 itens de exemplo para você começar:
- Materiais escolares
- Materiais de limpeza
- Materiais de escritório
- Equipamentos de informática
- Mobiliário

Você pode manter esses dados para testes ou excluí-los conforme necessário.

## 🔧 Configurações Adicionais

### Ajustar Categorias

Se desejar adicionar ou modificar categorias, edite o arquivo:
```
app/main/Views/dashboard/gestao_estoque_central.php
```

Procure pela seção "Categoria" no formulário (linhas ~1046-1057):
```html
<select name="categoria" required>
    <option value="">Selecione...</option>
    <option value="Material Escolar">Material Escolar</option>
    <!-- Adicione mais categorias aqui -->
</select>
```

### Ajustar Unidades de Medida

Para adicionar ou modificar unidades de medida, edite a mesma seção "Unidade de Medida" (linhas ~1073-1084).

## 🐛 Solução de Problemas

### Erro: "Tabela não encontrada"
- Verifique se o SQL foi executado corretamente
- Confirme que o nome do banco de dados está correto
- Verifique a conexão com o banco em `app/main/config/Database.php`

### Erro: "Acesso negado"
- Verifique se você está logado como Administrador (ADM)
- Limpe o cache do navegador
- Faça logout e login novamente

### Página não carrega
- Verifique os logs de erro do PHP
- Confirme que todos os arquivos foram copiados corretamente
- Verifique as permissões dos arquivos (devem ser legíveis pelo servidor web)

### Alertas de estoque não aparecem
- Confirme que o campo `estoque_minimo` está preenchido
- Verifique se a quantidade está realmente abaixo do mínimo

## 📁 Arquivos do Sistema

Arquivos criados/modificados nesta instalação:

### Arquivos Novos
- `app/main/Views/dashboard/gestao_estoque_central.php` - Página principal do módulo
- `app/main/config/create_estoque_central.sql` - Script de criação da tabela
- `app/main/Views/dashboard/README_ESTOQUE_CENTRAL.md` - Documentação do usuário
- `app/main/config/INSTALL_ESTOQUE_CENTRAL.md` - Este guia de instalação

### Arquivos Modificados
- `app/main/Views/dashboard/dashboard.php` - Adicionado link no menu
- `app/main/Views/dashboard/gestao_escolas.php` - Adicionado link no menu
- `app/main/Views/dashboard/gestao_usuarios.php` - Atualizado link existente

## 🔐 Segurança

O módulo implementa as seguintes medidas de segurança:

1. **Autenticação**: Verifica se o usuário está logado
2. **Autorização**: Apenas usuários ADM têm acesso
3. **Prepared Statements**: Todas as queries usam prepared statements
4. **Transações**: Operações de banco usam transações para garantir integridade
5. **Escape de HTML**: Todos os outputs usam `htmlspecialchars()`

## 📈 Próximos Passos

Após a instalação, você pode:

1. Cadastrar seus itens reais de estoque
2. Configurar os estoques mínimos adequados
3. Treinar a equipe para usar o sistema
4. Estabelecer processos de inventário regular
5. Configurar backup automático do banco de dados

## 📞 Suporte

Para dúvidas ou problemas:
1. Consulte a documentação em `README_ESTOQUE_CENTRAL.md`
2. Verifique os logs de erro do sistema
3. Entre em contato com o suporte técnico

---

**Sistema Integrado de Gestão da Administração Escolar (SIGAE)**  
Versão 1.0 - 2025

**Desenvolvido para a Secretaria de Educação**


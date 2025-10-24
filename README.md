# 🏫 Sistema de Gestão Escolar - SIGEA

## 📋 **VISÃO GERAL**

O **Sistema de Gestão Escolar (SIGEA)** é uma plataforma completa para gerenciamento de escolas, desenvolvida em PHP com arquitetura MVC. O sistema oferece funcionalidades para administração escolar, gestão de usuários, controle de estoque, cardápios, relatórios e um calendário integrado.

---

## 🚀 **FUNCIONALIDADES PRINCIPAIS**

### **👥 Gestão de Usuários**
- **Administradores (ADM)**: Acesso total ao sistema
- **Gestão**: Gestão de escolas e professores
- **Professores**: Acesso a turmas e alunos
- **Alunos**: Portal do aluno com notas e frequência
- **Nutricionistas**: Gestão de cardápios e estoque
- **ADM Merenda**: Controle de estoque central

### **🏫 Gestão Escolar**
- Cadastro e gerenciamento de escolas
- Controle de turmas e séries
- Gestão de professores e lotações
- Controle de alunos e matrículas
- Sistema de notas e avaliações
- Controle de frequência

### **🍽️ Gestão de Merenda**
- Controle de estoque por escola
- Gestão de cardápios mensais
- Controle de produtos e fornecedores
- Relatórios de consumo
- Movimentações de estoque

### **📅 Calendário Integrado**
- Calendário completo com FullCalendar.js
- Criação e edição de eventos
- Cores personalizáveis para eventos
- Feriados nacionais, estaduais e municipais
- Visualizações: mensal, semanal, diária e lista
- Sistema de temas (claro/escuro)

### **📊 Relatórios**
- Relatórios de consumo por escola
- Relatórios de estoque
- Relatórios de frequência
- Relatórios de notas
- Exportação em PDF

---

## 🛠️ **TECNOLOGIAS UTILIZADAS**

### **Backend**
- **PHP 7.4+** - Linguagem principal
- **MySQL** - Banco de dados
- **PDO** - Conexão com banco
- **Arquitetura MVC** - Padrão de desenvolvimento

### **Frontend**
- **HTML5** - Estrutura
- **CSS3** - Estilização
- **JavaScript** - Interatividade
- **Tailwind CSS** - Framework CSS
- **FullCalendar.js** - Biblioteca de calendário

### **Recursos**
- **Sistema de Temas** - Claro e escuro
- **Responsivo** - Mobile-first
- **API REST** - Endpoints para calendário
- **Sessões** - Controle de acesso
- **Segurança** - Prepared statements, hash de senhas

---

## 📁 **ESTRUTURA DO PROJETO**

```
app/
├── main/
│   ├── config/
│   │   ├── Database.php          # Classe de conexão
│   │   ├── database_setup.sql    # Estrutura do banco
│   │   ├── calendar_setup.sql    # Tabelas do calendário
│   │   └── init.php              # Inicialização
│   ├── Controllers/
│   │   ├── autenticacao/
│   │   │   └── controllerLogin.php
│   │   └── gestao/
│   │       ├── EscolaController.php
│   │       ├── GestorController.php
│   │       ├── ProfessorController.php
│   │       └── UsuarioController.php
│   ├── Models/
│   │   ├── autenticacao/
│   │   │   └── modelLogin.php
│   │   └── sessao/
│   │       └── sessions.php
│   └── Views/
│       ├── auth/
│       │   ├── login.php
│       │   └── session-expired.php
│       └── dashboard/
│           ├── dashboard.php           # Dashboard principal
│           ├── calendar.php            # Calendário
│           ├── gestao_escolas.php      # Gestão de escolas
│           ├── gestao_usuarios.php    # Gestão de usuários
│           ├── gestao_estoque_central.php
│           ├── api/
│           │   └── events.php         # API do calendário
│           ├── assets/
│           │   ├── css/
│           │   │   └── calendar.css   # Estilos do calendário
│           │   └── js/
│           │       └── calendar.js    # JavaScript do calendário
│           └── theme-manager.js       # Gerenciador de temas
```

---

## 🗄️ **ESTRUTURA DO BANCO DE DADOS**

### **Tabelas Principais**

#### **👥 Usuários e Pessoas**
- **`pessoa`** - Dados pessoais
- **`usuario`** - Credenciais e permissões
- **`aluno`** - Dados dos alunos
- **`professor`** - Dados dos professores
- **`gestor`** - Dados dos gestores

#### **🏫 Estrutura Escolar**
- **`escola`** - Cadastro das escolas
- **`turma`** - Turmas e séries
- **`disciplina`** - Disciplinas
- **`professor_lotacao`** - Lotação de professores
- **`gestor_lotacao`** - Lotação de gestores

#### **📚 Acadêmico**
- **`avaliacao`** - Avaliações e provas
- **`nota`** - Notas dos alunos
- **`frequencia`** - Controle de frequência
- **`comunicado`** - Comunicados

#### **🍽️ Merenda**
- **`produto`** - Catálogo de produtos
- **`estoque_central`** - Estoque central
- **`cardapio`** - Cardápios mensais
- **`cardapio_item`** - Itens dos cardápios
- **`movimentacao_estoque`** - Histórico de movimentações

#### **📅 Calendário**
- **`calendar_events`** - Eventos do calendário
- **`calendar_categories`** - Categorias de eventos
- **`calendar_notifications`** - Notificações
- **`calendar_settings`** - Configurações do usuário

---

## 🚀 **INSTALAÇÃO E CONFIGURAÇÃO**

### **1. Requisitos**
- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Apache/Nginx
- XAMPP/WAMP (desenvolvimento)

### **2. Configuração do Banco**
```sql
-- Criar banco de dados
CREATE DATABASE escola_merenda;

-- Executar scripts SQL
-- database_setup.sql (estrutura principal)
-- calendar_setup.sql (tabelas do calendário)
```

### **3. Configuração do Sistema**
```php
// config/config.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'escola_merenda');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha');
define('DEBUG_MODE', true); // Desenvolvimento
```

### **4. Acesso ao Sistema**
- **URL**: ``
- **Login**: `admin@escola.com`
- **Senha**: `password`

---

## 👥 **TIPOS DE USUÁRIO**

### **🔑 Administrador (ADM)**
- Acesso total ao sistema
- Gestão de usuários e escolas
- Controle de estoque central
- Relatórios completos
- Configurações do sistema

### **🏫 Gestão**
- Gestão de escolas específicas
- Controle de professores e alunos
- Relatórios da escola
- Gestão de turmas

### **👨‍🏫 Professor**
- Acesso às suas turmas
- Lançamento de notas
- Controle de frequência
- Comunicados para alunos

### **👨‍🎓 Aluno**
- Portal do aluno
- Visualização de notas
- Controle de frequência
- Comunicados recebidos

### **🍽️ Nutricionista**
- Gestão de cardápios
- Controle de estoque da escola
- Relatórios de consumo
- Planejamento nutricional

### **📦 ADM Merenda**
- Controle de estoque central
- Gestão de produtos
- Relatórios de distribuição
- Controle de fornecedores

---

## 📅 **SISTEMA DE CALENDÁRIO**

### **Funcionalidades**
- **Criação de Eventos**: Título, descrição, data, hora
- **Cores Personalizáveis**: 12 cores disponíveis
- **Tipos de Evento**: Reunião, prova, feriado, evento, etc.
- **Feriados Automáticos**: Nacionais, estaduais e municipais
- **Visualizações**: Mensal, semanal, diária, lista
- **Temas**: Claro e escuro
- **Responsivo**: Funciona em mobile

### **API do Calendário**
- **GET** `/api/events.php` - Listar eventos
- **POST** `/api/events.php` - Criar evento
- **PUT** `/api/events.php?id=X` - Atualizar evento
- **DELETE** `/api/events.php?id=X` - Excluir evento

### **Feriados Integrados**
- **Nacionais**: Brasil API
- **Estaduais**: Ceará
- **Municipais**: Maranguape
- **Cores**: Vermelho para feriados
- **Links**: Informações sobre feriados

---

## 🎨 **SISTEMA DE TEMAS**

### **Tema Claro**
- Fundo branco
- Texto escuro
- Cores suaves
- Ideal para uso diurno

### **Tema Escuro**
- Fundo escuro
- Texto claro
- Cores contrastantes
- Ideal para uso noturno

### **Persistência**
- Salvo no localStorage
- Sincronizado entre páginas
- Aplicado automaticamente
- Botão de alternância

---

## 🔒 **SEGURANÇA**

### **Medidas Implementadas**
- **Prepared Statements**: Prevenção de SQL Injection
- **Hash de Senhas**: PASSWORD_DEFAULT
- **Sanitização**: Dados de entrada
- **Validação**: Tipos de dados
- **Sessões**: Controle de acesso
- **CORS**: Headers de segurança

### **Controle de Acesso**
- Verificação de sessão
- Permissões por tipo de usuário
- Redirecionamento automático
- Logout automático

---

## 📊 **RELATÓRIOS DISPONÍVEIS**

### **📈 Relatórios de Consumo**
- Consumo por escola
- Consumo por período
- Produtos mais consumidos
- Análise de tendências

### **📦 Relatórios de Estoque**
- Estoque atual por escola
- Movimentações de estoque
- Produtos em falta
- Histórico de entradas/saídas

### **👥 Relatórios Acadêmicos**
- Frequência por turma
- Notas por disciplina
- Desempenho dos alunos
- Estatísticas de aprovação

---

## 🚀 **FUNCIONALIDADES AVANÇADAS**

### **📱 Responsividade**
- Design mobile-first
- Adaptação automática
- Touch-friendly
- Performance otimizada

### **🔄 Sincronização**
- Dados em tempo real
- Atualizações automáticas
- Cache inteligente
- Sessões persistentes

### **📈 Performance**
- Queries otimizadas
- Índices de banco
- Cache de dados
- Lazy loading

---

## 🛠️ **DESENVOLVIMENTO**

### **Padrões Utilizados**
- **MVC**: Separação de responsabilidades
- **Singleton**: Conexão com banco
- **Factory**: Criação de objetos
- **Observer**: Eventos do sistema

### **Estrutura de Código**
- **Controllers**: Lógica de negócio
- **Models**: Acesso a dados
- **Views**: Interface do usuário
- **Assets**: CSS, JS, imagens

### **Convenções**
- **Nomenclatura**: camelCase para JS, snake_case para PHP
- **Comentários**: Documentação inline
- **Indentação**: 4 espaços
- **Encoding**: UTF-8

---

## 🔧 **MANUTENÇÃO**

### **Logs do Sistema**
- Erros de aplicação
- Logs de acesso
- Logs de banco
- Logs de segurança

### **Backup**
- Backup automático do banco
- Versionamento de código
- Documentação atualizada
- Testes de integridade

### **Monitoramento**
- Performance do sistema
- Uso de recursos
- Erros em tempo real
- Estatísticas de uso

---

## 📞 **SUPORTE**

### **Documentação**
- README completo
- Comentários no código
- Exemplos de uso
- Troubleshooting

### **Contato**
- **Desenvolvedor**: Sistema SIGEA
- **Versão**: 1.0.0
- **Última Atualização**: 2025
- **Licença**: Proprietária

---

## 🎯 **ROADMAP**

### **Próximas Funcionalidades**
- [ ] Notificações push
- [ ] App mobile
- [ ] Integração com redes sociais
- [ ] Relatórios avançados
- [ ] Sistema de backup automático
- [ ] Integração com sistemas externos

### **Melhorias Planejadas**
- [ ] Performance otimizada
- [ ] Interface mais intuitiva
- [ ] Mais tipos de relatórios
- [ ] Sistema de permissões granular
- [ ] API REST completa
- [ ] Documentação interativa

---

## 🏆 **BENEFÍCIOS DO SISTEMA**

### **✅ Para Administradores**
- Controle total da escola
- Relatórios completos
- Gestão eficiente
- Tomada de decisões baseada em dados

### **✅ Para Professores**
- Facilidade no lançamento de notas
- Controle de frequência simplificado
- Comunicação com alunos
- Organização de eventos

### **✅ Para Alunos**
- Acesso às informações acadêmicas
- Transparência nas notas
- Comunicados em tempo real
- Histórico completo

### **✅ Para a Escola**
- Organização completa
- Controle de estoque
- Gestão de merenda
- Relatórios para órgãos competentes

---

## 🎉 **CONCLUSÃO**

O **Sistema de Gestão Escolar (SIGEA)** é uma solução completa e moderna para gerenciamento escolar, oferecendo todas as funcionalidades necessárias para uma gestão eficiente e transparente. Com interface intuitiva, sistema de temas, calendário integrado e relatórios avançados, o SIGEA é a escolha ideal para escolas que buscam modernização e eficiência.

**Sistema de Gestão Escolar - SIGEA** 📚✨

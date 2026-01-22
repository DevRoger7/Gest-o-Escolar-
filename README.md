# 🎓 Sistema de Gestão Escolar (SIGAE)

<div align="center">

![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-005C84?style=for-the-badge&logo=mysql&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)

**Sistema completo de gestão escolar com controle acadêmico, merenda e transporte**

[Características](#-características) • [Instalação](#-instalação) • [Uso](#-como-usar) • [Estrutura](#-estrutura-do-projeto)

</div>

---

## 📋 Índice

- [Sobre o Projeto](#-sobre-o-projeto)
- [Características](#-características)
- [Tipos de Usuários](#-tipos-de-usuários)
- [Tecnologias Utilizadas](#-tecnologias-utilizadas)
- [Requisitos](#-requisitos)
- [Instalação](#-instalação)
- [Configuração](#-configuração)
- [Estrutura do Projeto](#-estrutura-do-projeto)
- [Como Usar](#-como-usar)
- [Funcionalidades por Perfil](#-funcionalidades-por-perfil)
- [Sistema de Permissões](#-sistema-de-permissões)
- [Licença](#-licença)

---

## 🎯 Sobre o Projeto

O **Sistema de Gestão Escolar (SIGAE)** é uma solução completa desenvolvida em PHP para gerenciar todas as operações de uma instituição de ensino. O sistema oferece funcionalidades abrangentes para administração acadêmica, gestão de merenda escolar, controle de transporte, e muito mais.

### Objetivos

- ✅ Centralizar todas as informações escolares em uma única plataforma
- ✅ Facilitar a gestão acadêmica e administrativa
- ✅ Controlar a merenda escolar e estoque
- ✅ Gerenciar transporte escolar
- ✅ Fornecer relatórios detalhados e dashboards interativos
- ✅ Garantir segurança através de sistema de permissões robusto

---

## ✨ Características

### 🎓 Gestão Acadêmica
- **Matrícula de Alunos**: Sistema completo de matrícula e transferência
- **Gestão de Turmas**: Criação e organização de turmas por série e turno
- **Controle de Frequência**: Registro diário de presença/ausência
- **Lançamento de Notas**: Sistema de avaliações com cálculo automático de médias
- **Boletins Escolares**: Geração automática de boletins por bimestre
- **Planos de Aula**: Criação e registro de planos de aula pelos professores
- **Avaliações**: Sistema completo de criação e gerenciamento de avaliações

### 🍽️ Gestão de Merenda
- **Cardápios**: Criação e gerenciamento de cardápios por escola
- **Estoque**: Controle completo de entrada e saída de produtos
- **Consumo Diário**: Registro de consumo de alimentos
- **Pedidos**: Sistema de solicitação e aprovação de produtos
- **Fornecedores**: Gestão de fornecedores e entregas
- **Indicadores Nutricionais**: Acompanhamento de indicadores de alimentação

### 🚌 Transporte Escolar
- **Rotas**: Gerenciamento de rotas de transporte
- **Veículos**: Cadastro e controle de veículos
- **Motoristas**: Gestão de motoristas e lotações
- **Alunos Transportados**: Controle de alunos que utilizam transporte

### 📊 Relatórios e Dashboards
- **Dashboard Administrativo**: Visão geral do sistema com estatísticas
- **Relatórios Pedagógicos**: Relatórios acadêmicos detalhados
- **Relatórios Financeiros**: Controle financeiro e custos
- **Relatórios de Merenda**: Análise de consumo e estoque
- **Gráficos Interativos**: Visualização de dados com Chart.js

### 🔐 Segurança
- **Sistema de Autenticação**: Login seguro com CPF e senha
- **Controle de Permissões**: Sistema granular de permissões por perfil
- **Gerenciamento de Sessão**: Controle de tempo de sessão e logout seguro
- **Validação de Dados**: Validação completa de informações

---

## 👥 Tipos de Usuários

O sistema possui **7 perfis de usuários** distintos, cada um com permissões e funcionalidades específicas:

| Perfil | Descrição | Principais Funcionalidades |
|--------|-----------|---------------------------|
| 👨‍💼 **ADM** | Administrador Geral | Gestão completa do sistema, escolas, usuários, estoque central |
| 🎓 **GESTAO** | Direção/Coordenação | Gestão de turmas, alunos, professores, acompanhamento acadêmico |
| 👨‍🏫 **PROFESSOR** | Professor | Lançamento de notas, frequência, planos de aula, avaliações |
| 🥗 **NUTRICIONISTA** | Nutricionista | Criação de cardápios, pedidos de insumos, indicadores nutricionais |
| 🍽️ **ADM_MERENDA** | Administrador de Merenda | Gestão de estoque, aprovação de pedidos, distribuição |
| 🎒 **ALUNO** | Aluno | Visualização de notas, frequência, boletins, cardápios |
| 👨‍👩‍👧 **RESPONSAVEL** | Responsável | Acompanhamento do desempenho dos filhos, comunicados |

---

## 🛠️ Tecnologias Utilizadas

### Backend
- **PHP 7.4+** - Linguagem de programação principal
- **MySQL/MariaDB** - Banco de dados relacional
- **PDO** - Camada de abstração para acesso ao banco de dados
- **FPDF** - Geração de relatórios em PDF

### Frontend
- **HTML5** - Estrutura das páginas
- **CSS3** - Estilização
- **Tailwind CSS** - Framework CSS utilitário
- **JavaScript (Vanilla)** - Interatividade e validações
- **Chart.js** - Gráficos e visualizações

### Acessibilidade
- **VLibras** - Suporte para tradução em Libras

### Arquitetura
- **MVC (Model-View-Controller)** - Padrão arquitetural
- **Singleton Pattern** - Para conexão com banco de dados
- **Autoload (Composer)** - Gerenciamento de dependências

---

## 📦 Requisitos

### Servidor
- **PHP**: 7.4 ou superior
- **MySQL/MariaDB**: 5.7 ou superior
- **Apache/Nginx**: Servidor web
- **Composer**: Gerenciador de dependências PHP

### Extensões PHP
- `pdo_mysql`
- `mbstring`
- `json`
- `session`

### Recomendado
- **XAMPP/WAMP/MAMP**: Para ambiente de desenvolvimento local

---

## 🚀 Instalação

### 1. Clone o Repositório

```bash
git clone https://github.com/seu-usuario/Gest-o-Escolar-.git
cd Gest-o-Escolar-
```

### 2. Instale as Dependências

```bash
composer install
```

### 3. Configure o Banco de Dados

1. Crie um banco de dados MySQL:
```sql
CREATE DATABASE escola_merenda CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Execute os scripts SQL de migração na pasta `app/migrations/`:
```bash
# Execute os arquivos SQL na ordem:
# - add_distrito_localidades.sql
# - add_transporte_aluno.sql
# - add_transporte_escolar.sql
# - escola_merenda.sql
# - insert_usuarios_transporte.sql
```

### 4. Configure as Credenciais do Banco

Edite o arquivo `app/main/config/Database.php`:

```php
private function __construct() {
    $this->host = 'localhost';
    $this->dbname = 'escola_merenda';
    $this->username = 'seu_usuario';
    $this->password = 'sua_senha';
    $this->connect();
}
```

### 5. Configure o Servidor Web

#### Apache (.htaccess)
Certifique-se de que o mod_rewrite está habilitado e configure o DocumentRoot para a pasta `app/`.

#### Nginx
Configure o server block para apontar para a pasta `app/`.

### 6. Acesse o Sistema

Abra seu navegador e acesse:
```
http://localhost/Gest-o-Escolar-/app/
```

O sistema redirecionará automaticamente para a página de login.

---

## ⚙️ Configuração

### Configuração de Permissões

O sistema utiliza um sistema de permissões centralizado. As permissões são definidas em:
- `app/main/Models/permissions/PermissionManager.php`

### Configuração de Sessão

As configurações de sessão podem ser ajustadas em:
- `app/main/Models/sessao/sessions.php`

### Configuração de Autoload

O autoload está configurado em:
- `app/main/config/auto_include.php`

---

## 📁 Estrutura do Projeto

```
Gest-o-Escolar-/
│
├── app/
│   ├── index.php                 # Ponto de entrada principal
│   │
│   └── main/
│       ├── config/               # Configurações do sistema
│       │   ├── Database.php      # Classe de conexão com BD
│       │   ├── init.php          # Inicialização do sistema
│       │   └── auto_include.php  # Autoload de classes
│       │
│       ├── Controllers/          # Controladores (Lógica de negócio)
│       │   ├── academico/        # Controllers acadêmicos
│       │   ├── autenticacao/    # Controllers de autenticação
│       │   ├── comunicacao/     # Controllers de comunicação
│       │   ├── gestao/          # Controllers de gestão
│       │   ├── merenda/         # Controllers de merenda
│       │   └── validacao/       # Controllers de validação
│       │
│       ├── Models/              # Modelos (Acesso a dados)
│       │   ├── academico/       # Models acadêmicos
│       │   ├── autenticacao/    # Models de autenticação
│       │   ├── dashboard/       # Models de dashboard
│       │   ├── merenda/         # Models de merenda
│       │   ├── permissions/     # Sistema de permissões
│       │   └── sessao/          # Gerenciamento de sessão
│       │
│       ├── Views/               # Visualizações (Interface)
│       │   ├── auth/           # Páginas de autenticação
│       │   ├── dashboard/      # Páginas do dashboard
│       │   └── errors/         # Páginas de erro
│       │
│       ├── Middleware/          # Middlewares
│       │   └── RouteProtection.php
│       │
│       └── database/            # Scripts SQL e migrações
│           └── migrations/      # Migrações do banco
│
├── migrations/                  # Migrações adicionais
├── vendor/                      # Dependências do Composer
├── composer.json               # Configuração do Composer
└── README.md                   # Este arquivo
```

---

## 💻 Como Usar

### Primeiro Acesso

1. Acesse a página de login
2. Use suas credenciais (CPF e senha)
3. O sistema redirecionará para o dashboard apropriado ao seu perfil

### Navegação

- **Menu Lateral**: Acesse todas as funcionalidades através do menu lateral responsivo
- **Dashboard**: Visualize estatísticas e informações importantes
- **Cards de Acesso Rápido**: Use os cards no dashboard para acesso rápido às funcionalidades principais

### Funcionalidades Principais

#### Para Administradores
- Gerenciar escolas, usuários e estoque central
- Acessar todos os relatórios do sistema
- Configurar permissões e segurança

#### Para Gestão
- Gerenciar turmas e alunos
- Acompanhar desempenho acadêmico
- Visualizar relatórios pedagógicos

#### Para Professores
- Lançar frequência e notas
- Criar planos de aula e avaliações
- Enviar comunicados

#### Para Nutricionistas
- Criar e editar cardápios
- Fazer pedidos de insumos
- Visualizar indicadores nutricionais

---

## 🎯 Funcionalidades por Perfil

### 👨‍💼 Administrador Geral (ADM)

- ✅ Gestão completa de usuários (CRUD)
- ✅ Gestão de escolas (CRUD)
- ✅ Gestão de turmas, séries e disciplinas
- ✅ Controle de lotação de professores
- ✅ Gestão de estoque central
- ✅ Acesso a todos os relatórios
- ✅ Dashboard com estatísticas gerais
- ✅ Calendário de eventos

### 🎓 Gestão (GESTAO)

- ✅ Criar e organizar turmas
- ✅ Realizar matrículas
- ✅ Transpor alunos entre turmas
- ✅ Atribuir professores às turmas
- ✅ Acompanhar frequência e desempenho
- ✅ Visualizar notas e boletins
- ✅ Dashboard com estatísticas da escola
- ✅ Relatórios pedagógicos

### 👨‍🏫 Professor

- ✅ Criar e registrar planos de aula
- ✅ Criar avaliações
- ✅ Registrar frequência diária
- ✅ Lançar e editar notas
- ✅ Adicionar observações sobre alunos
- ✅ Enviar comunicados
- ✅ Visualizar cardápios
- ✅ Gerar relatórios das turmas

### 🥗 Nutricionista

- ✅ Criar e modificar cardápios
- ✅ Gerar lista de insumos
- ✅ Solicitar produtos ao administrador
- ✅ Visualizar indicadores nutricionais
- ✅ Gerenciar substituições de alimentos

### 🍽️ Administrador de Merenda (ADM_MERENDA)

- ✅ Visualizar e revisar cardápios
- ✅ Controlar estoque de produtos
- ✅ Registrar consumo diário
- ✅ Monitorar desperdício e custos
- ✅ Gerenciar fornecedores
- ✅ Aprovar/rejeitar pedidos
- ✅ Montar kits de alimentação
- ✅ Acompanhar entregas

### 🎒 Aluno

- ✅ Visualizar notas e boletins
- ✅ Consultar frequência
- ✅ Visualizar histórico escolar
- ✅ Receber comunicados
- ✅ Visualizar cardápios
- ✅ Atualizar dados pessoais
- ✅ Dashboard com estatísticas pessoais

### 👨‍👩‍👧 Responsável

- ✅ Acompanhar desempenho dos filhos
- ✅ Acompanhar frequência
- ✅ Visualizar comunicados
- ✅ Consultar cardápios
- ✅ Manter contato com coordenação e professores

---

## 🔐 Sistema de Permissões

O sistema utiliza um sistema de permissões granular e centralizado. Cada perfil de usuário possui um conjunto específico de permissões.

### Métodos Disponíveis

```php
// Verificar uma permissão específica
temPermissao($permissao)

// Verificar se tem pelo menos uma das permissões
temAlgumaPermissao($permissoes)

// Verificar se tem todas as permissões
temTodasPermissoes($permissoes)

// Obter o tipo de usuário
getTipoUsuario()

// Verificar se é de um tipo específico
eTipo($tipo)
```

### Exemplo de Uso

```php
<?php
require_once 'app/main/config/permissions_helper.php';

if (temPermissao('lancar_nota')) {
    // Permite lançar notas
}

if (eTipo('PROFESSOR')) {
    // Código específico para professores
}
?>
```

---

## 📝 Licença

Este projeto está sob a licença MIT. Veja o arquivo `LICENSE` para mais detalhes.

---

## 👨‍💻 Autor

**Kron**

- Desenvolvido em: Dezembro 2024
- Última atualização: Dezembro 2024

---

<div align="center">

**Sistema de Gestão Escolar - Solução completa para administração educacional**

Desenvolvido com as melhores práticas de desenvolvimento web

</div>


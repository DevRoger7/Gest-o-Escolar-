# Sistema de Gestão Escolar

## 📋 Descrição

Sistema completo para centralizar e automatizar os processos acadêmicos e de merenda escolar em um único ambiente moderno e eficiente.

## 🎯 Funcionalidades Principais

### 👥 Gestão de Usuários
- **ADM**: Cadastro de escolas, gestores e professores
- **Gestão**: Criação de turmas, matrícula de alunos, atribuição de professores
- **Professores**: Registro de atividades, frequência e notas
- **Alunos/Responsáveis**: Portal simplificado para consulta de informações
- **Nutricionista**: Montagem de cardápios e gestão de insumos
- **ADM Merenda**: Controle de estoque e aprovação de pedidos

### 📊 Módulos do Sistema
- **Acadêmico**: Turmas, matrículas, frequência, notas, relatórios
- **Alimentação**: Cardápios, estoque, pedidos, aprovações
- **Relatórios**: Acadêmicos, gestão de pessoal, alimentação escolar

## 🎨 Design

### Paleta de Cores
- **Verde Principal**: #2D5A27
- **Verde Secundário**: #4A7C59
- **Laranja**: #FF6B35
- **Vermelho**: #D62828
- **Verde Claro**: #A8D5BA

### Características
- Design moderno e fluido
- Ícones SVG finos e elegantes
- Animações suaves e responsivas
- Interface intuitiva e acessível

## 🚀 Tecnologias

- **Backend**: PHP 7.4+
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework CSS**: Tailwind CSS
- **Arquitetura**: MVC (Model-View-Controller)
- **Banco de Dados**: MySQL

## 📁 Estrutura do Projeto

```
Gest-o-Escolar-/
├── app/
│   └── main/
│       ├── Controllers/
│       │   ├── AuthController.php
│       │   └── HomeController.php
│       ├── Views/
│       │   ├── auth/
│       │   │   └── login.php
│       │   └── home/
│       │       └── index.php
│       └── config/
│           └── config.php
├── assets/
│   ├── img/
│   │   ├── brasao_maranguape.png
│   │   └── escolas/
│   │       ├── antonio_luiz_coelho.jpg
│   │       ├── clovis_monteiro.jpg
│   │       ├── jose_fernandes_vieira.jpg
│   │       └── nilo_pinheiro_campelo.jpg
│   ├── icons/
│   │   └── favicon.png
│   └── .htaccess
├── config/
│   └── config.php
├── index.php
├── .htaccess
└── README.md
```

## 🔧 Instalação

1. Clone o repositório
2. Configure o servidor web (Apache/Nginx)
3. Configure o banco de dados MySQL
4. Ajuste as configurações em `config/config.php`
5. Acesse o sistema via navegador

## 📱 Acesso

- **URL Principal**: `http://localhost/GitHub/Gest-o-Escolar-/`
- **Login**: `http://localhost/GitHub/Gest-o-Escolar-/login`

## 🎯 MVP (Mínimo Produto Viável)

- ✅ Cadastro de usuários, escolas e lotações
- ✅ Criação de turmas e matrícula de alunos
- ✅ Lançamento de frequência e notas
- ✅ Portal do aluno para visualização de informações
- ✅ Módulo inicial da merenda (produtos, estoque, pedidos, aprovações)

## 🔐 Segurança

- Validação de entrada de dados
- Proteção contra SQL Injection
- Sessões seguras
- Controle de acesso por perfil
- Logs de auditoria

## 📈 Benefícios

- **Centralização**: Unifica processos em um único ambiente
- **Transparência**: Acesso direto a informações acadêmicas
- **Eficiência**: Automação de processos administrativos
- **Controle**: Gestão completa de estoque e recursos
- **Histórico**: Registros detalhados de todas as operações

## 👨‍💻 Desenvolvimento

Sistema desenvolvido seguindo as melhores práticas de desenvolvimento web, com foco em usabilidade, performance e segurança.

---

**Desenvolvido com ❤️ para a educação brasileira**
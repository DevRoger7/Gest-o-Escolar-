# Guia para Atualizar Caminhos Após Reorganização

## Padrões de Atualização

### 1. Includes e Requires

**Antes:**
```php
require_once('../../Models/sessao/sessions.php');
include('components/sidebar_adm.php');
```

**Depois (dependendo da nova localização):**
```php
// Se o arquivo está em adm/gestao/alunos.php
require_once('../../../Models/sessao/sessions.php');
include('../../components/sidebar_adm.php');
```

### 2. Links HTML

**Antes:**
```html
<a href="gestao_alunos_adm.php">Alunos</a>
```

**Depois:**
```html
<a href="adm/gestao/alunos.php">Alunos</a>
```

### 3. Redirecionamentos PHP

**Antes:**
```php
header('Location: gestao_alunos_adm.php');
```

**Depois:**
```php
header('Location: adm/gestao/alunos.php');
```

### 4. Form Actions

**Antes:**
```html
<form action="gestao_alunos_adm.php" method="POST">
```

**Depois:**
```html
<form action="adm/gestao/alunos.php" method="POST">
```

## Tabela de Conversão de Caminhos Relativos

### Para arquivos em `adm/gestao/`:
- `../../Models/` → `../../../Models/`
- `../../config/` → `../../../config/`
- `components/` → `../../components/`
- `dashboard.php` → `../../shared/dashboard.php`

### Para arquivos em `professor/`:
- `../../Models/` → `../../../Models/`
- `../../config/` → `../../../config/`
- `components/` → `../../components/`
- `dashboard.php` → `../../shared/dashboard.php`

### Para arquivos em `aluno/`:
- `../../Models/` → `../../../Models/`
- `../../config/` → `../../../config/`
- `components/` → `../../components/`
- `dashboard.php` → `../../shared/dashboard.php`

### Para arquivos em `merenda/`:
- `../../Models/` → `../../../Models/`
- `../../config/` → `../../../config/`
- `components/` → `../../components/`
- `dashboard.php` → `../../shared/dashboard.php`

### Para arquivos em `nutricionista/`:
- `../../Models/` → `../../../Models/`
- `../../config/` → `../../../config/`
- `components/` → `../../components/`
- `dashboard.php` → `../../shared/dashboard.php`

## Script de Busca e Substituição

Use este padrão para buscar e substituir em todos os arquivos:

### Buscar:
- `href="gestao_alunos_adm.php"`
- `href="notas_professor.php"`
- `href="cardapios_merenda.php"`
- etc.

### Substituir pelos novos caminhos conforme a tabela acima.

## Checklist de Atualização

- [ ] Atualizar todos os `require_once` e `include`
- [ ] Atualizar todos os links `<a href="">`
- [ ] Atualizar todos os `header('Location: ...')`
- [ ] Atualizar todos os `action=""` em formulários
- [ ] Atualizar todos os `fetch()` e `XMLHttpRequest` com URLs
- [ ] Atualizar redirecionamentos JavaScript `window.location.href`
- [ ] Testar todas as funcionalidades
- [ ] Remover arquivos de redirecionamento temporários


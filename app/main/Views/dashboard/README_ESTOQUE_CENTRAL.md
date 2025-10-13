# Sistema de Gestão de Estoque Central

## 📦 Visão Geral

O módulo de Gestão de Estoque Central permite o controle completo de materiais, equipamentos e produtos armazenados no almoxarifado central da secretaria de educação.

## 🎯 Funcionalidades

### 1. **Cadastro de Itens**
- Registro completo de itens com informações detalhadas
- Campos obrigatórios e opcionais
- Categorização por tipo de material
- Controle de valores e quantidades

### 2. **Controle de Estoque**
- Monitoramento de quantidades em tempo real
- Alertas automáticos de estoque baixo
- Estoque mínimo configurável por item
- Rastreamento de localização física

### 3. **Gestão Financeira**
- Registro de valor unitário
- Cálculo automático de valor total por item
- Valor total do estoque
- Data de aquisição para controle patrimonial

### 4. **Busca e Filtros**
- Busca por nome, descrição, categoria ou fornecedor
- Listagem organizada e intuitiva
- Visualização rápida de informações essenciais

### 5. **Relatórios e Estatísticas**
- Total de itens cadastrados
- Itens ativos no estoque
- Alertas de estoque baixo
- Valor total do inventário

## 📋 Campos do Sistema

### Informações Básicas
- **Nome**: Identificação do item (obrigatório)
- **Descrição**: Detalhes adicionais sobre o item
- **Categoria**: Classificação do material (obrigatório)

### Controle de Quantidade
- **Quantidade**: Quantidade atual em estoque (obrigatório)
- **Unidade de Medida**: UN, CX, PC, KG, L, M, etc. (obrigatório)
- **Estoque Mínimo**: Limite para alertas (obrigatório)

### Informações Financeiras
- **Valor Unitário**: Preço por unidade (obrigatório)
- **Valor Total**: Calculado automaticamente (quantidade × valor unitário)
- **Data de Aquisição**: Quando o item foi adquirido

### Informações Logísticas
- **Fornecedor**: Nome do fornecedor/empresa
- **Localização**: Onde o item está armazenado (obrigatório)
- **Status**: Ativo ou Inativo (obrigatório)

### Observações
- **Obs**: Informações adicionais sobre o item

## 📊 Categorias Disponíveis

1. **Material Escolar**: Cadernos, canetas, lápis, etc.
2. **Material de Limpeza**: Detergentes, desinfetantes, etc.
3. **Material de Escritório**: Papel, grampeadores, etc.
4. **Equipamentos**: Máquinas e equipamentos diversos
5. **Mobiliário**: Cadeiras, mesas, armários, etc.
6. **Informática**: Computadores, impressoras, periféricos
7. **Alimentação**: Produtos alimentícios (se aplicável)
8. **Outros**: Itens que não se encaixam nas categorias acima

## 📏 Unidades de Medida

- **UN** - Unidade
- **CX** - Caixa
- **PC** - Pacote
- **KG** - Quilograma
- **L** - Litro
- **M** - Metro
- **M²** - Metro Quadrado
- **DZ** - Dúzia
- **RL** - Rolo

## 🚨 Sistema de Alertas

### Estoque Baixo
O sistema exibe automaticamente alertas quando:
- A quantidade atual é menor ou igual ao estoque mínimo
- Alertas aparecem na dashboard com destaque visual
- Lista detalhada dos itens que precisam de reposição

### Indicadores Visuais
- **Badge Verde**: Item ativo
- **Badge Vermelho**: Item inativo
- **Badge Laranja**: Estoque baixo
- **Fundo Laranja**: Linha da tabela para itens com estoque baixo

## 🔧 Como Usar

### Cadastrar Novo Item

1. Clique na aba **"Novo Item"**
2. Preencha todos os campos obrigatórios (marcados com *)
3. Adicione informações complementares se necessário
4. Clique em **"Cadastrar Item"**
5. Aguarde a confirmação de sucesso

### Editar Item Existente

1. Na lista de itens, localize o item desejado
2. Clique no ícone de **edição** (lápis)
3. Modifique os campos necessários
4. Clique em **"Salvar Alterações"**
5. Ou clique em **"Cancelar"** para descartar as alterações

### Excluir Item

1. Na lista de itens, localize o item desejado
2. Clique no ícone de **exclusão** (lixeira)
3. Confirme a exclusão no modal
4. **Atenção**: Esta ação não pode ser desfeita

### Buscar Itens

1. Use a barra de busca no topo da lista
2. Digite nome, descrição, categoria ou fornecedor
3. Clique em **"Buscar"** ou pressione Enter
4. Para limpar a busca, clique em **"Limpar"**

## 💡 Dicas de Uso

### Boas Práticas

1. **Localização Clara**: Use um padrão para registrar localizações
   - Exemplo: "Almoxarifado A - Prateleira 3 - Caixa 2"

2. **Estoque Mínimo**: Configure valores realistas baseados em:
   - Taxa de consumo médio
   - Tempo de reposição do fornecedor
   - Importância do item

3. **Categorização**: Categorize corretamente para facilitar relatórios e buscas

4. **Atualização Regular**: Mantenha as quantidades sempre atualizadas

5. **Fornecedores**: Registre sempre o fornecedor para facilitar recompras

6. **Observações**: Use o campo de observações para:
   - Registrar peculiaridades do item
   - Anotar condições especiais de armazenamento
   - Indicar itens em garantia

### Controle de Estoque Eficiente

- Realize inventários periódicos (mensal ou bimestral)
- Configure alertas de estoque mínimo adequadamente
- Atualize valores unitários quando houver mudanças de preço
- Inative itens que não são mais utilizados (não os exclua)
- Mantenha registro histórico para análises futuras

## 🔐 Segurança e Permissões

- Apenas usuários com perfil **Administrador (ADM)** têm acesso
- Todas as operações são registradas com timestamp
- Sistema de autenticação e sessão integrado

## 📱 Responsividade

- Interface totalmente responsiva
- Funciona em desktops, tablets e smartphones
- Menu lateral adaptativo para dispositivos móveis

## 🎨 Recursos de Acessibilidade

- **Tema Claro/Escuro**: Alternância de tema para conforto visual
- **VLibras**: Integração com tradutor de Libras
- **Cores Semânticas**: Uso de cores para indicar status e alertas
- **Fontes Legíveis**: Tipografia otimizada para leitura

## 🗄️ Banco de Dados

### Tabela: `estoque_central`

A estrutura da tabela inclui:
- Campos de identificação e descrição
- Campos de quantidade e valores
- Campos de logística (fornecedor, localização)
- Campos de controle (status, datas)
- Timestamps automáticos de criação e atualização

### Instalação

Execute o arquivo SQL disponível em:
```
app/main/config/create_estoque_central.sql
```

## 🐛 Solução de Problemas

### Erro ao Cadastrar Item
- Verifique se todos os campos obrigatórios estão preenchidos
- Confirme que valores numéricos estão no formato correto
- Verifique a conexão com o banco de dados

### Item Não Aparece na Lista
- Verifique se o item foi realmente cadastrado (mensagem de sucesso)
- Limpe os filtros de busca
- Atualize a página

### Alertas de Estoque Não Aparecem
- Confirme que o estoque mínimo está configurado
- Verifique se a quantidade está realmente abaixo do mínimo

## 📞 Suporte

Para dúvidas ou problemas:
1. Verifique esta documentação
2. Consulte o administrador do sistema
3. Entre em contato com o suporte técnico

## 🔄 Atualizações Futuras

Melhorias planejadas:
- [ ] Movimentação de estoque (entrada/saída)
- [ ] Histórico de movimentações
- [ ] Requisição de materiais pelas escolas
- [ ] Relatórios em PDF
- [ ] Exportação para Excel
- [ ] Código de barras/QR Code
- [ ] Dashboard com gráficos
- [ ] Notificações por e-mail para estoque baixo

---

**Sistema Integrado de Gestão da Administração Escolar (SIGAE)**  
Versão 1.0 - 2025


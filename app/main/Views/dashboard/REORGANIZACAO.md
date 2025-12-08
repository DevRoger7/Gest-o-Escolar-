# Reorganização da Pasta Dashboard

## Estrutura Proposta

```
dashboard/
├── components/              # Componentes reutilizáveis (já existe)
│   ├── sidebar_adm.php
│   ├── sidebar_merenda.php
│   ├── sidebar_nutricionista.php
│   ├── sidebar_professor.php
│   └── alert.php
│
├── aluno/                   # Módulo do Aluno
│   ├── notas.php           # aluno_notas.php
│   ├── frequencia.php      # aluno_frequencia.php
│   └── boletins.php        # aluno_boletins.php
│
├── professor/              # Módulo do Professor
│   ├── notas.php           # notas_professor.php
│   ├── frequencia.php      # frequencia_professor.php
│   ├── comunicados.php     # comunicados_professor.php
│   ├── observacoes.php     # observacoes_professor.php
│   └── relatorios.php      # relatorios_professor.php
│
├── adm/                    # Módulo Administrativo
│   ├── gestao/
│   │   ├── alunos.php      # gestao_alunos_adm.php
│   │   ├── professores.php # gestao_professores_adm.php
│   │   ├── funcionarios.php # gestao_funcionarios_adm.php
│   │   ├── gestores.php    # gestao_gestores_adm.php
│   │   ├── usuarios.php    # gestao_usuarios.php
│   │   ├── escolas.php     # gestao_escolas.php
│   │   ├── turmas.php      # gestao_turmas_adm.php
│   │   ├── series.php      # gestao_series_adm.php
│   │   └── disciplinas.php # gestao_disciplinas_adm.php
│   ├── supervisao/
│   │   ├── academica.php   # supervisao_academica_adm.php
│   │   └── alimentacao.php # supervisao_alimentacao_adm.php
│   ├── relatorios/
│   │   ├── pedagogicos.php # relatorios_pedagogicos_adm.php
│   │   └── financeiros.php # relatorios_financeiros_adm.php
│   ├── permissoes.php      # permissoes_adm.php
│   ├── validacao.php       # validacao_lancamentos_adm.php
│   └── configuracoes.php   # configuracoes_seguranca_adm.php
│
├── merenda/                # Módulo de Merenda
│   ├── cardapios.php       # cardapios_merenda.php
│   ├── estoque.php         # estoque_merenda.php
│   ├── consumo.php         # consumo_merenda.php
│   ├── pedidos.php         # pedidos_merenda.php
│   ├── fornecedores.php    # fornecedores_merenda.php
│   ├── entregas.php        # entregas_merenda.php
│   ├── custos.php          # custos_merenda.php
│   ├── desperdicio.php     # desperdicio_merenda.php
│   └── estoque_central.php # gestao_estoque_central.php
│
├── nutricionista/          # Módulo do Nutricionista
│   ├── cardapios.php       # cardapios_nutricionista.php
│   ├── pedidos.php         # pedidos_nutricionista.php
│   ├── estoque.php         # avaliacao_estoque_nutricionista.php
│   ├── substituicoes.php   # substituicoes_nutricionista.php
│   ├── indicadores.php     # indicadores_nutricionais.php
│   └── relatorios.php      # relatorios_nutricionais.php
│
├── gestao/                 # Módulo de Gestão Escolar
│   ├── escolar.php         # gestao_escolar.php
│   └── lotacao.php         # lotacao_professores.php
│
├── shared/                 # Arquivos compartilhados
│   ├── dashboard.php       # dashboard.php (principal)
│   ├── calendar.php        # calendar.php
│   └── dashboard_footer.php
│
├── api/                    # APIs (já existe)
│   └── events.php
│
├── assets/                 # Assets (já existe)
│   ├── css/
│   ├── js/
│   └── img/
│
├── css/                    # CSS adicional
│   └── loading.css
│
├── js/                     # JavaScript adicional
│   ├── form-validation.js
│   └── loading.js
│
├── global-theme.css
└── theme-manager.js
```

## Benefícios da Reorganização

1. **Organização por Módulo**: Cada tipo de usuário tem sua pasta
2. **Facilita Manutenção**: Mais fácil encontrar arquivos relacionados
3. **Escalabilidade**: Fácil adicionar novos módulos
4. **Clareza**: Estrutura mais intuitiva para novos desenvolvedores
5. **Separação de Responsabilidades**: Cada módulo é independente

## Plano de Migração

1. Criar as novas pastas
2. Mover os arquivos para suas respectivas pastas
3. Atualizar todos os `include` e `require` nos arquivos
4. Atualizar links e redirecionamentos
5. Testar todas as funcionalidades

## Arquivos que Precisam de Atualização de Caminhos

- Todos os arquivos que fazem `include` ou `require` de outros arquivos
- Todos os links `<a href="">` que apontam para outros dashboards
- Todos os redirecionamentos `header('Location: ...')`
- Todos os formulários que fazem `action=""` para outros arquivos


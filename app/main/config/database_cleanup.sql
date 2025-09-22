-- Script para remover as tabelas criadas
-- Sistema de Gestão Escolar - Merenda

-- Usar o banco de dados
USE escola_merenda;

-- Desativar verificação de chaves estrangeiras para permitir a exclusão
SET FOREIGN_KEY_CHECKS = 0;

-- Remover tabelas na ordem inversa de dependência
DROP TABLE IF EXISTS relatorios_consumo;
DROP TABLE IF EXISTS movimentacoes_estoque;
DROP TABLE IF EXISTS cardapio_itens;
DROP TABLE IF EXISTS cardapios;
DROP TABLE IF EXISTS estoque;
DROP TABLE IF EXISTS alimentos;
DROP TABLE IF EXISTS escolas;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS pessoas;

-- Remover tabelas adicionais que possam ter sido criadas
DROP TABLE IF EXISTS aluno;
DROP TABLE IF EXISTS aluno_turma;
DROP TABLE IF EXISTS avaliacao;
DROP TABLE IF EXISTS cardapio;
DROP TABLE IF EXISTS cardapio_item;
DROP TABLE IF EXISTS comunicado;
DROP TABLE IF EXISTS disciplina;
DROP TABLE IF EXISTS escola;
DROP TABLE IF EXISTS estoque_central;
DROP TABLE IF EXISTS frequencia;
DROP TABLE IF EXISTS gestor;
DROP TABLE IF EXISTS gestor_lotacao;
DROP TABLE IF EXISTS justificativa;
DROP TABLE IF EXISTS movimentacao_estoque;
DROP TABLE IF EXISTS nota;
DROP TABLE IF EXISTS pacote;
DROP TABLE IF EXISTS pacote_item;
DROP TABLE IF EXISTS pedido_cesta;
DROP TABLE IF EXISTS pedido_item;
DROP TABLE IF EXISTS pessoa;
DROP TABLE IF EXISTS produto;
DROP TABLE IF EXISTS professor;
DROP TABLE IF EXISTS professor_lotacao;
DROP TABLE IF EXISTS turma;
DROP TABLE IF EXISTS turma_professor;
DROP TABLE IF EXISTS usuario;

-- Reativar verificação de chaves estrangeiras
SET FOREIGN_KEY_CHECKS = 1;

-- Remover o banco de dados (opcional, comentado por segurança)
-- DROP DATABASE IF EXISTS escola_merenda;
-- ============================================================
-- Hashes de Senhas Comuns para SIGAE
-- ============================================================

-- Senha: "123456"
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

-- Senha: "password"
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

-- Senha: "aluno123"
-- Hash: $2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy

-- Senha: "senha123"
-- Hash: $2y$10$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeG6Lruj3vjPGga31lW

-- ============================================================
-- EXEMPLO DE USO:
-- ============================================================

-- Criar aluno com senha "123456"
INSERT INTO `usuario` (`pessoa_id`, `username`, `senha_hash`, `role`, `ativo`) 
VALUES (
    @pessoa_id,
    'joao.silva',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- Senha: "password"
    'ALUNO',
    1
);

-- ============================================================
-- IMPORTANTE:
-- - Execute o arquivo gerar_hash_senha.php para gerar novos hashes
-- - Ou use: https://bcrypt-generator.com/
-- ============================================================


<?php

class UserModel {
    
    // Dados de teste para o professor
    private static $testUsers = [
        'professor' => [
            'id' => 1,
            'nome' => 'João Silva Santos',
            'cpf' => '12345678901',
            'email' => 'joao.silva@maranguape.edu.br',
            'senha' => '123456', // Em produção, seria hash
            'tipo' => 'professor',
            'escolas' => [
                [
                    'id' => 1,
                    'nome' => 'Antonio Luiz Coelho',
                    'endereco' => 'Rua das Flores, 123 - Centro',
                    'telefone' => '(85) 3333-1111',
                    'codigo' => 'ALC001',
                    'logo' => 'antonio_luiz_coelho.jpg'
                ],
                [
                    'id' => 2,
                    'nome' => 'Clóvis Monteiro',
                    'endereco' => 'Av. Principal, 456 - Bairro Novo',
                    'telefone' => '(85) 3333-2222',
                    'codigo' => 'CM002',
                    'logo' => 'clovis_monteiro.jpg'
                ],
                [
                    'id' => 3,
                    'nome' => 'José Fernandes Vieira',
                    'endereco' => 'Rua da Escola, 789 - Vila Nova',
                    'telefone' => '(85) 3333-3333',
                    'codigo' => 'JFV003',
                    'logo' => 'jose_fernandes_vieira.jpg'
                ],
                [
                    'id' => 4,
                    'nome' => 'Nilo Pinheiro Campelo',
                    'endereco' => 'Rua dos Estudantes, 321 - Centro',
                    'telefone' => '(85) 3333-4444',
                    'codigo' => 'NPC004',
                    'logo' => 'nilo_pinheiro_campelo.jpg'
                ]
            ]
        ]
    ];
    
    public static function authenticate($cpf, $senha) {
        // Buscar usuário por CPF
        foreach (self::$testUsers as $key => $user) {
            if ($user['cpf'] === $cpf && $user['senha'] === $senha) {
                return $user;
            }
        }
        return false;
    }
    
    public static function getUserById($id) {
        foreach (self::$testUsers as $key => $user) {
            if ($user['id'] == $id) {
                return $user;
            }
        }
        return false;
    }
    
    public static function getUserSchools($userId) {
        $user = self::getUserById($userId);
        return $user ? $user['escolas'] : [];
    }
}

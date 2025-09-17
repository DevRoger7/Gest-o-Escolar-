<?php

class HubController
{
    public function index()
    {
        // Verificar se está logado
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        // Dados do usuário
        $userName = $_SESSION['user_name'] ?? 'Usuário';
        $userType = $_SESSION['user_type'] ?? 'professor';
        $schools = $_SESSION['user_schools'] ?? [];
        
        // Renderizar a view do hub
        $title = 'Selecionar Escola - Sistema de Gestão Escolar Maranguape';
        
        include_once __DIR__ . '/../Views/hub/index.php';
    }
    
    public function selectSchool()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $schoolId = $_POST['school_id'] ?? '';
            
            if ($schoolId) {
                // Buscar dados da escola selecionada
                $schools = $_SESSION['user_schools'] ?? [];
                $selectedSchool = null;
                
                foreach ($schools as $school) {
                    if ($school['id'] == $schoolId) {
                        $selectedSchool = $school;
                        break;
                    }
                }
                
                if ($selectedSchool) {
                    // Salvar escola selecionada na sessão
                    $_SESSION['selected_school_id'] = $selectedSchool['id'];
                    $_SESSION['selected_school_name'] = $selectedSchool['nome'];
                    $_SESSION['selected_school_code'] = $selectedSchool['codigo'];
                    
                    // Redirecionar para dashboard da escola
                    header('Location: /dashboard');
                    exit;
                }
            }
        }
        
        header('Location: /hub');
        exit;
    }
}

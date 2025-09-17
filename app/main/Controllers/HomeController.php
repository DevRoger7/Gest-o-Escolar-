<?php
/**
 * Controller Home
 * Sistema de Gestão Escolar
 */

class HomeController {
    
    public function index() {
        $title = 'Bem-vindo ao Sistema de Gestão Escolar';
        $message = 'Sistema funcionando corretamente!';
        
        include_once __DIR__ . '/../Views/home/index.php';
    }
}
?>
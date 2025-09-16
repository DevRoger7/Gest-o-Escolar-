<?php
/**
 * Controller Home
 * Sistema de Gestão Escolar
 */

require_once APP_PATH . '/Core/Controller.php';

class HomeController extends Controller {
    
    public function index() {
        $data = [
            'title' => 'Bem-vindo ao Sistema de Gestão Escolar',
            'message' => 'Sistema funcionando corretamente!'
        ];
        
        $this->view('home/index', $data);
    }
}
?>
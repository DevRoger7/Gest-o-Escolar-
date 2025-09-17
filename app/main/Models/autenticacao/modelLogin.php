<?php

Class ModelLogin {
    public function login($cpf, $senha) {
        require_once("../../config/Database.php");
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $sql = "SELECT u.*, p.* FROM usuario u 
                INNER JOIN pessoa p ON u.pessoa_id = p.id 
                WHERE p.cpf = ? AND u.senha_hash = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$cpf, $senha]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($resultado) {
            return $resultado;
        } else {
            return false;
        }
    }
}

?>

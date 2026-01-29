<?php
require_once __DIR__ . '/../../data/conector.php';
session_start();

if (!isset($_SESSION['id']) || $_SESSION['tipo_usuario'] !== 'Sindico') {
    header('Location: ../../login.php');
    exit;
}

$conexao = (new Conector())->getConexao();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $titulo     = trim($_POST['titulo']);
    $conteudo   = trim($_POST['conteudo']);
    $prioridade = $_POST['prioridade'];
    $criador    = $_SESSION['id'];

    if ($titulo === '' || $conteudo === '') {
        header("Location: ../../views/Sindico/avisos.php?erro=Preencha todos os campos");
        exit;
    }

    $stmt = $conexao->prepare("
        INSERT INTO Aviso (titulo, conteudo, prioridade, criado_por)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("sssi", $titulo, $conteudo, $prioridade, $criador);
    $stmt->execute();

    $idAviso = $stmt->insert_id;

    $usuarios = $conexao->query("
        SELECT id_usuario 
        FROM Usuario 
        WHERE tipo IN ('Morador', 'Porteiro')
    ");

    $stmtLeitura = $conexao->prepare("
        INSERT INTO Leitura_Aviso (id_aviso, id_usuario, data_leitura)
        VALUES (?, ?, NULL)
    ");

    while ($u = $usuarios->fetch_assoc()) {
        $stmtLeitura->bind_param("ii", $idAviso, $u['id_usuario']);
        $stmtLeitura->execute();
    }

    header("Location: ../../views/Sindico/avisos.php?success=Aviso publicado");
    exit;
}

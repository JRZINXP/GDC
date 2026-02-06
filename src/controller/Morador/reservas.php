<?php

require_once __DIR__ . '/../../data/conector.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $area_comum = $_POST['area_comum'] ?? null;
    $data = $_POST['data'] ?? null;
    $hora_inicio = $_POST['hora_inicio'] ?? null;
    $hora_fim = $_POST['hora_fim'] ?? null;

    if (!$area_comum || !$data || !$hora_inicio || !$hora_fim) {
        $_SESSION['mensagem'] = "Todos os campos são obrigatórios!";
        $_SESSION['tipo_mensagem'] = "erro";
        header("Location: ../../view/Morador/reservas.php");
        exit();
    }

date_default_timezone_set('Africa/Maputo');

$data_atual = date('Y-m-d');
$hora_atual = date('H:i');

if ($data < $data_atual) {
    $_SESSION['mensagem'] = "Não é possível reservar para datas passadas!";
    $_SESSION['tipo_mensagem'] = "erro";
    header("Location: ../../view/Morador/reservas.php");
    exit();
}

if ($data === $data_atual) {

    if ($hora_inicio <= $hora_atual) {
        $_SESSION['mensagem'] = "A hora de início já passou!";
        $_SESSION['tipo_mensagem'] = "erro";
        header("Location: ../../view/Morador/reservas.php");
        exit();
    }

    if ($hora_fim <= $hora_atual) {
        $_SESSION['mensagem'] = "A hora de término já passou!";
        $_SESSION['tipo_mensagem'] = "erro";
        header("Location: ../../view/Morador/reservas.php");
        exit();
    }
}

if ($hora_fim <= $hora_inicio) {
    $_SESSION['mensagem'] = "A hora de término deve ser maior que a hora de início!";
    $_SESSION['tipo_mensagem'] = "erro";
    header("Location: ../../view/Morador/reservas.php");
    exit();
}

    $conector = new Conector();
    $conexao = $conector->getConexao();

    $stmt = $conexao->prepare("SELECT id_morador FROM Morador WHERE id_usuario = ?");
    $stmt->bind_param("s", $_SESSION['id']);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {
        $_SESSION['mensagem'] = "Erro: Morador não encontrado!";
        $_SESSION['tipo_mensagem'] = "erro";
        header("Location: ../../view/Morador/reservas.php");
        exit();
    }

    $morador = $resultado->fetch_assoc();
    $id_morador = $morador['id_morador'];

    $query_conflito = "SELECT id_reserva FROM Reserva 
WHERE area_comum = ?
AND data = ?
AND hora_inicio < ?
AND hora_fim > ?
AND status = 'aprovada'";

    
    $stmt_conflito = $conexao->prepare($query_conflito);
    $stmt_conflito->bind_param(
        "ssss", $area_comum, $data, $hora_fim, $hora_inicio
    );
    $stmt_conflito->execute();
    $resultado_conflito = $stmt_conflito->get_result();

    if ($resultado_conflito->num_rows > 0) {
        $_SESSION['mensagem'] = "Esta área comum já está reservada para este horário!";
        $_SESSION['tipo_mensagem'] = "erro";
        header("Location: ../../view/Morador/reservas.php");
        exit();
    }

    try {

        $query_inserir = "INSERT INTO Reserva 
(id_morador, area_comum, data, hora_inicio, hora_fim, status) 
VALUES (?, ?, ?, ?, ?, 'pendente')";

        
        $stmt_inserir = $conexao->prepare($query_inserir);
       $stmt_inserir->bind_param("issss", $id_morador, $area_comum, $data, $hora_inicio, $hora_fim);

        if ($stmt_inserir->execute()) {
            $_SESSION['mensagem'] = "Reserva enviada para aprovação do síndico!";
            $_SESSION['tipo_mensagem'] = "sucesso";
        } else {
            throw new Exception("Erro ao criar reserva: " . $stmt_inserir->error);
        }

    } catch (Exception $e) {
        $_SESSION['mensagem'] = $e->getMessage();
        $_SESSION['tipo_mensagem'] = "erro";
    }

    header("Location: ../../view/Morador/reservas.php");
    exit();

} else {

    header("Location: ../../view/Morador/reservas.php");
    exit();
}

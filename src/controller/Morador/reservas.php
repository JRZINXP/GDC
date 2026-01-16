<?php

require_once __DIR__ . '/../../data/conector.php';

session_start();

// Verificar se o método é POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $area_comum = $_POST['area_comum'] ?? null;
    $data = $_POST['data'] ?? null;
    $hora_inicio = $_POST['hora_inicio'] ?? null;
    $hora_fim = $_POST['hora_fim'] ?? null;

    // Validações básicas
    if (!$area_comum || !$data || !$hora_inicio || !$hora_fim) {
        $_SESSION['mensagem'] = "Todos os campos são obrigatórios!";
        $_SESSION['tipo_mensagem'] = "erro";
        header("Location: ../../view/Morador/reservas.php");
        exit();
    }

    // Validar se data é futura
    $data_timestamp = strtotime($data);
    $hoje_timestamp = strtotime(date('Y-m-d'));
    
    if ($data_timestamp < $hoje_timestamp) {
        $_SESSION['mensagem'] = "Não é possível fazer reserva para datas passadas!";
        $_SESSION['tipo_mensagem'] = "erro";
        header("Location: ../../view/Morador/reservas.php");
        exit();
    }

    // Validar se hora_fim é maior que hora_inicio
    if ($hora_fim <= $hora_inicio) {
        $_SESSION['mensagem'] = "A hora de término deve ser maior que a hora de início!";
        $_SESSION['tipo_mensagem'] = "erro";
        header("Location: ../../view/Morador/reservas.php");
        exit();
    }

    // Buscar o ID do morador logado
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

    // Verificar conflito de horário - reserva no mesmo dia e horário
    $query_conflito = "SELECT id_reserva FROM Reserva 
                       WHERE id_morador = ? 
                       AND data = ? 
                       AND (
                           (hora_inicio < ? AND hora_fim > ?)
                           OR (hora_inicio < ? AND hora_fim > ?)
                           OR (hora_inicio >= ? AND hora_fim <= ?)
                       )";
    
    $stmt_conflito = $conexao->prepare($query_conflito);
    $stmt_conflito->bind_param("isssssss", $id_morador, $data, $hora_fim, $hora_inicio, $hora_fim, $hora_inicio, $hora_inicio, $hora_fim);
    $stmt_conflito->execute();
    $resultado_conflito = $stmt_conflito->get_result();

    if ($resultado_conflito->num_rows > 0) {
        $_SESSION['mensagem'] = "Você já possui uma reserva neste horário!";
        $_SESSION['tipo_mensagem'] = "erro";
        header("Location: ../../view/Morador/reservas.php");
        exit();
    }

    try {
        // Inserir a reserva
        $query_inserir = "INSERT INTO Reserva (id_morador, area_comum, data, hora_inicio, hora_fim) 
                          VALUES (?, ?, ?, ?, ?)";
        
        $stmt_inserir = $conexao->prepare($query_inserir);
        $stmt_inserir->bind_param("issss", $id_morador, $area_comum, $data, $hora_inicio, $hora_fim);

        if ($stmt_inserir->execute()) {
            $_SESSION['mensagem'] = "Reserva criada com sucesso!";
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
    // Se não for POST, redirecionar
    header("Location: ../../view/Morador/reservas.php");
    exit();
}

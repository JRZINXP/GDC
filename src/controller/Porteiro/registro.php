<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../data/conector.php';
require_once __DIR__ . '/../../utils/log.php';

if (!isset($_SESSION['id']) || $_SESSION['tipo_usuario'] !== 'Porteiro') {
    echo json_encode([
        'success' => false,
        'message' => 'Acesso não autorizado'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método inválido'
    ]);
    exit;
}

if (!isset($_POST['acao'], $_POST['id_agendamento'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Dados incompletos'
    ]);
    exit;
}

$acao = $_POST['acao'];
$idAgendamento = (int) $_POST['id_agendamento'];

$conexao = (new Conector())->getConexao();

try {

    $stmt = $conexao->prepare("
        SELECT id_agendamento
        FROM Agendamento
        WHERE id_agendamento = ?
    ");
    $stmt->bind_param("i", $idAgendamento);
    $stmt->execute();

    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception('Agendamento não encontrado');
    }

    $stmt = $conexao->prepare("
        SELECT id_registro, entrada, saida
        FROM Registro
        WHERE id_agendamento = ?
    ");
    $stmt->bind_param("i", $idAgendamento);
    $stmt->execute();
    $registro = $stmt->get_result()->fetch_assoc();

    /* ================= ENTRADA ================= */
    if ($acao === 'entrada') {

        if ($registro) {
            throw new Exception('Entrada já registrada');
        }

        $stmt = $conexao->prepare("
            INSERT INTO Registro (id_agendamento, entrada)
            VALUES (?, NOW())
        ");
        $stmt->bind_param("i", $idAgendamento);
        $stmt->execute();

        /* LOG */
        registrarLog(
            $conexao,
            $_SESSION['id'],
            $_SESSION['nome'] ?? $_SESSION['email'],
            "ENTRADA_VISITANTE",
            "Registrou entrada do agendamento ID $idAgendamento"
        );

        echo json_encode([
            'success' => true,
            'message' => 'Entrada registrada com sucesso'
        ]);
        exit;
    }

    /* ================= SAÍDA ================= */
    if ($acao === 'saida') {

        if (!$registro || !$registro['entrada']) {
            throw new Exception('Entrada ainda não registrada');
        }

        if ($registro['saida']) {
            throw new Exception('Saída já registrada');
        }

        $stmt = $conexao->prepare("
            UPDATE Registro
            SET saida = NOW()
            WHERE id_registro = ?
        ");
        $stmt->bind_param("i", $registro['id_registro']);
        $stmt->execute();

        /* LOG */
        registrarLog(
            $conexao,
            $_SESSION['id'],
            $_SESSION['nome'] ?? $_SESSION['email'],
            "SAIDA_VISITANTE",
            "Registrou saída do agendamento ID $idAgendamento"
        );

        echo json_encode([
            'success' => true,
            'message' => 'Saída registrada com sucesso'
        ]);
        exit;
    }

    throw new Exception('Ação inválida');

} catch (Exception $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}

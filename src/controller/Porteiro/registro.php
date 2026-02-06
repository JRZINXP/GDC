<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../data/conector.php';

/* PROTEÇÃO */
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

    /* VERIFICAR AGENDAMENTO */
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

    /* BUSCAR REGISTRO */
    $stmt = $conexao->prepare("
        SELECT id_registro, entrada, saida
        FROM Registro
        WHERE id_agendamento = ?
    ");
    $stmt->bind_param("i", $idAgendamento);
    $stmt->execute();
    $registro = $stmt->get_result()->fetch_assoc();

    /* ===== REGISTRAR ENTRADA ===== */
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

        echo json_encode([
            'success' => true,
            'message' => 'Entrada registrada com sucesso'
        ]);
        exit;
    }

    /* ===== REGISTRAR SAÍDA ===== */
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

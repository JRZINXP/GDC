<?php
session_start();

if (!isset($_SESSION['id']) || $_SESSION['tipo_usuario'] !== 'Porteiro') {
    header('Location: ../../login.php');
    exit();
}

require_once __DIR__ . '/../../data/conector.php';
$conexao = (new Conector())->getConexao();

/* Base URL */
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
$base_url = $protocol . $_SERVER['HTTP_HOST'] . '/GDC';

/* QUERY */
$stmt = $conexao->prepare("
    SELECT 
        a.id_agendamento,
        DATE(a.data) AS data,
        a.hora AS hora_agendada,
        r.entrada,
        r.saida,
        v.nome AS visitante_nome,
        m.nome AS morador_nome,
        u.numero AS casa_numero
    FROM Agendamento a
    JOIN Visitante v ON v.id_visitante = a.id_visitante
    JOIN Morador m ON m.id_morador = a.id_morador
    JOIN Unidade u ON u.id_unidade = m.id_unidade
    LEFT JOIN Registro r ON r.id_agendamento = a.id_agendamento
    WHERE DATE(a.data) >= CURDATE()
    ORDER BY a.data, a.hora
");
$stmt->execute();
$visitas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Registro de Visitas</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
* {
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
    margin: 0;
    padding: 2rem;
    color: #1f2937;
}

.box {
    background: #fff;
    border-radius: 14px;
    padding: 2.5rem;
    max-width: 1200px;
    margin: auto;
    box-shadow: 0 20px 35px rgba(0,0,0,.08);
}

/* BOTÃO VOLTAR */
.btn-voltar {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #e5e7eb;
    color: #374151;
    padding: 10px 16px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 1.5rem;
    transition: .3s;
}

.btn-voltar i {
    color: #4a148c;
}

.btn-voltar:hover {
    background: #d1d5db;
    transform: translateX(-3px);
}

/* TÍTULO */
h1 {
    margin-bottom: 2rem;
    display: flex;
    gap: 12px;
    align-items: center;
    font-size: 1.6rem;
}

h1 i {
    color: #4a148c;
}

/* SELECT */
.select-area {
    display: flex;
    gap: 1rem;
    margin-bottom: 2.5rem;
}

select {
    flex: 1;
    padding: 14px;
    border-radius: 10px;
    border: 1px solid #d1d5db;
    background: #f9fafb;
}

select:focus {
    outline: none;
    border-color: #4a148c;
    background: #fff;
}

.select-area button {
    padding: 14px 22px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    background: #4a148c;
    color: #fff;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: .3s;
}

.select-area button:hover {
    background: #2e0f5f;
    transform: translateY(-2px);
}

/* TABELA */
table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: #f3f4f6;
    padding: 16px;
    text-align: left;
    font-weight: 600;
}

td {
    padding: 16px;
    border-bottom: 1px solid #e5e7eb;
}

tr:hover {
    background: #fafafa;
}

/* STATUS */
.status {
    padding: 6px 16px;
    border-radius: 999px;
    font-size: .75rem;
    font-weight: 600;
}

.pendente { background:#fef3c7; color:#92400e; }
.em { background:#ede9fe; color:#4a148c; }
.done { background:#dcfce7; color:#166534; }

/* BOTÕES */
.btn {
    padding: 8px 14px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-size: .8rem;
    font-weight: 500;
    color: #fff;
}

.btn-in { background:#10b981; }
.btn-in:hover { background:#059669; }

.btn-out { background:#4a148c; }
.btn-out:hover { background:#2e0f5f; }

.btn-off {
    background:#9ca3af;
    cursor:not-allowed;
}

/* RESPONSIVO */
@media (max-width: 768px) {
    .select-area {
        flex-direction: column;
    }

    .select-area button {
        width: 100%;
        justify-content: center;
    }
}
</style>
</head>

<body>

<div class="box">

    <a href="index.php" class="btn-voltar">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>

    <h1><i class="fas fa-user-check"></i> Registro de Visitas</h1>

    <table>
        <thead>
            <tr>
                <th>Visitante</th>
                <th>Casa</th>
                <th>Entrada</th>
                <th>Saída</th>
                <th>Status</th>
                <th>Ação</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($visitas as $v):
            if (!$v['entrada']) { $status='Pendente'; $cls='pendente'; }
            elseif (!$v['saida']) { $status='Em visita'; $cls='em'; }
            else { $status='Concluído'; $cls='done'; }
        ?>
            <tr>
                <td><?= htmlspecialchars($v['visitante_nome']) ?></td>
                <td>Casa <?= $v['casa_numero'] ?></td>
                <td><?= $v['entrada'] ? date('H:i', strtotime($v['entrada'])) : '--' ?></td>
                <td><?= $v['saida'] ? date('H:i', strtotime($v['saida'])) : '--' ?></td>
                <td><span class="status <?= $cls ?>"><?= $status ?></span></td>
                <td>
                    <?php if (!$v['entrada']): ?>
                        <button class="btn btn-in" onclick="registrarEntrada(<?= $v['id_agendamento'] ?>)">Entrada</button>
                    <?php elseif (!$v['saida']): ?>
                        <button class="btn btn-out" onclick="registrarSaida(<?= $v['id_agendamento'] ?>)">Saída</button>
                    <?php else: ?>
                        <button class="btn btn-off" disabled>Finalizado</button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function registrarEntrada(id) {
    fetch('<?= $base_url ?>/src/controller/Porteiro/registro.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'acao=entrada&id_agendamento=' + id
    })
    .then(r => r.json())
    .then(d => d.success ? location.reload() : alert(d.message));
}

function registrarSaida(id) {
    fetch('<?= $base_url ?>/src/controller/Porteiro/registro.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'acao=saida&id_agendamento=' + id
    })
    .then(r => r.json())
    .then(d => d.success ? location.reload() : alert(d.message));
}

function registrarEntradaSelect() {
    const id = document.getElementById('agendamentoEntrada').value;
    if (!id) return alert('Selecione uma visita');
    registrarEntrada(id);
}
</script>

</body>
</html>

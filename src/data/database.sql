CREATE DATABASE CondominioDigital;
USE CondominioDigital;

CREATE TABLE Usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    tipo ENUM('Morador', 'Sindico', 'Porteiro') NOT NULL
);

CREATE TABLE Unidade (
    id_unidade INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(20) NOT NULL,
    rua VARCHAR(100),
    tipo_unidade NVARCHAR(100)
);

CREATE TABLE Porteiro (
    id_porteiro INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    nome VARCHAR(100) NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario)
);

CREATE TABLE Morador (
    id_morador INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    id_unidade INT,
    nome VARCHAR(100) NOT NULL,
    telefone VARCHAR(20),
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario),
    FOREIGN KEY (id_unidade) REFERENCES Unidade(id_unidade)
);

CREATE TABLE Sindico (
    id_sindico INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    nome VARCHAR(100) NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario)
);

CREATE TABLE Veiculo (
    id_veiculo INT AUTO_INCREMENT PRIMARY KEY,
    id_morador INT NULL,
    id_visitante INT NULL,
    matricula VARCHAR(20) NOT NULL,
    cor NVARCHAR(50),
    modelo NVARCHAR(100),
    FOREIGN KEY (id_morador) REFERENCES Morador(id_morador)
);

CREATE TABLE Visitante (
    id_visitante INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    documento_imagem VARCHAR(255) NOT NULL
);


CREATE TABLE Agendamento (
    id_agendamento INT AUTO_INCREMENT PRIMARY KEY,
    id_morador INT NOT NULL,
    id_visitante INT NOT NULL,
    data DATE NOT NULL,
    hora TIME NOT NULL,
    motivo VARCHAR(100) NOT NULL,

    FOREIGN KEY (id_morador) 
        REFERENCES Morador(id_morador),

    FOREIGN KEY (id_visitante) 
        REFERENCES Visitante(id_visitante)
);

CREATE TABLE Registro (
    id_registro INT AUTO_INCREMENT PRIMARY KEY,
    id_agendamento INT,
    id_porteiro INT,
    entrada DATETIME,
    saida DATETIME,
    FOREIGN KEY (id_agendamento) REFERENCES Agendamento(id_agendamento)

);

CREATE TABLE Aviso (
    id_aviso INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    conteudo TEXT NOT NULL,
    prioridade ENUM('Baixa', 'Média', 'Alta') DEFAULT 'Baixa',
    criado_por INT, 
    FOREIGN KEY (criado_por) REFERENCES Usuario(id_usuario)
);

CREATE TABLE Leitura_Aviso (
    id_aviso INT,
    id_usuario INT, 
    data_leitura DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_aviso, id_usuario),
    FOREIGN KEY (id_aviso) REFERENCES Aviso(id_aviso),
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id_usuario)
);

CREATE TABLE Reserva (
    id_reserva INT AUTO_INCREMENT PRIMARY KEY,
    id_morador INT,
    area_comum VARCHAR(100) NOT NULL,
    data DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    FOREIGN KEY (id_morador) REFERENCES Morador(id_morador)
);


CREATE TABLE Entrega (
    id_entrega INT AUTO_INCREMENT PRIMARY KEY,  
    id_morador INT,
    descricao VARCHAR(255) NOT NULL,
    data_recepcao DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_entrega DATETIME,                     
    status tinyint(1) default 0,
    FOREIGN KEY (id_morador) REFERENCES Morador(id_morador)
);

CREATE TABLE Ocorrencia (
    id_ocorrencia INT AUTO_INCREMENT PRIMARY KEY,
    id_morador INT,
    tipo ENUM('Reclamacao', 'Manutencao', 'Sugestao', 'Outro') NOT NULL,
    titulo VARCHAR(100) NOT NULL,
    descricao TEXT NOT NULL,
    status ENUM('Pendente', 'Em Analise', 'Resolvido') DEFAULT 'Pendente',
    data_abertura DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_resolucao DATETIME,
    resposta_sindico TEXT,
    FOREIGN KEY (id_morador) REFERENCES Morador(id_morador)
);
CREATE TABLE logs (
    id_log INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    acao VARCHAR(255),
    data_log DATETIME DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE Reserva 
ADD status VARCHAR(20) DEFAULT 'pendente';
ALTER TABLE Reserva 
MODIFY status VARCHAR(20) DEFAULT 'pendente';



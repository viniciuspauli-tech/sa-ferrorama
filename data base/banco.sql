CREATE DATABASE IF NOT EXISTS ferrorama
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE ferrorama;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    perfil ENUM('usuario', 'administrador') NOT NULL DEFAULT 'usuario',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS trens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identificador VARCHAR(50) NOT NULL UNIQUE,
    modelo VARCHAR(100) NOT NULL,
    status ENUM('ativo', 'inativo', 'manutencao', 'falha') NOT NULL DEFAULT 'inativo',
    velocidade_atual DECIMAL(6,2) DEFAULT 0,
    localizacao_atual VARCHAR(150) DEFAULT NULL,
    consumo_energia DECIMAL(8,2) DEFAULT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sensores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    localizacao VARCHAR(150) NOT NULL,
    tipo_dado VARCHAR(100) NOT NULL,
    trem_id INT NOT NULL,
    CONSTRAINT fk_sensores_trem
        FOREIGN KEY (trem_id) REFERENCES trens(id)
) ENGINE=InnoDB;

INSERT INTO trens
    (identificador, modelo, status, velocidade_atual, localizacao_atual, consumo_energia)
VALUES
    ('TR-001', 'Expresso 4000', 'ativo', 87.50, 'KM 12 - Trecho Norte', 320.10),
    ('TR-002', 'Regional 2200', 'manutencao', 0.00, 'Pátio Central', 0.00),
    ('TR-003', 'Cargueiro 8800', 'ativo', 45.20, 'KM 56 - Trecho Sul', 510.75),
    ('TR-004', 'Expresso 4000', 'falha', 0.00, 'KM 34 - Trecho Leste', 12.00);
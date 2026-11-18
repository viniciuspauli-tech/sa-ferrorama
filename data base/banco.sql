CREATE DATABASE IF NOT EXISTS ferrorama
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE ferrorama;

-- =========================================
-- TABELA DE USUÁRIOS
-- =========================================

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    perfil ENUM('usuario', 'administrador')
        NOT NULL DEFAULT 'usuario',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================
-- TABELA DE TRENS
-- =========================================


CREATE TABLE trens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identificador VARCHAR(50) NOT NULL UNIQUE,
    modelo VARCHAR(100) NOT NULL,
    status ENUM('ativo', 'inativo', 'manutencao', 'falha')
        NOT NULL DEFAULT 'inativo',
    velocidade_atual DECIMAL(6,2) DEFAULT 0,
    localizacao_atual VARCHAR(150) DEFAULT NULL,
    consumo_energia DECIMAL(8,2) DEFAULT NULL,

    -- Todo trem precisa de um usuário responsável
    responsavel_id INT NOT NULL,

    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_trens_responsavel (responsavel_id),

    CONSTRAINT fk_trens_responsavel
        FOREIGN KEY (responsavel_id)
        REFERENCES usuarios(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================
-- TABELA DE SENSORES
-- =========================================

CREATE TABLE sensores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    localizacao VARCHAR(150) NOT NULL,
    tipo_dado VARCHAR(100) NOT NULL,

    UNIQUE KEY uq_sensor_localizacao (nome, localizacao)
) ENGINE=InnoDB;

-- =========================================
-- TABELA DE ROTAS
-- Agora possui usuario_id
-- =========================================

CREATE TABLE rotas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT NOT NULL,
    origem VARCHAR(100) NOT NULL,
    destino VARCHAR(100) NOT NULL,
    distancia_km DECIMAL(8,2) NOT NULL,

    trem_id INT NOT NULL,
    usuario_id INT NULL,

    tempo_estimado VARCHAR(20) NOT NULL,

    status ENUM('ativa', 'inativa', 'manutencao')
        NOT NULL DEFAULT 'ativa',

    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_rotas_usuario (usuario_id),

    CONSTRAINT fk_rotas_trem
        FOREIGN KEY (trem_id)
        REFERENCES trens(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_rotas_usuario
CREATE DATABASE IF NOT EXISTS ferrorama
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE ferrorama;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    perfil ENUM('usuario', 'administrador') NOT NULL DEFAULT 'usuario',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE trens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    identificacao VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE sensores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    localizacao VARCHAR(150) NOT NULL,
    tipo_dado VARCHAR(100) NOT NULL,
    trem_id INT NOT NULL,

    FOREIGN KEY (trem_id)
        REFERENCES trens(id)
);
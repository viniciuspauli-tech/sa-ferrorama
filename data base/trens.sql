-- Tabela de trens do sistema Ferrorama
-- Ajuste os tipos/tamanhos conforme o restante do banco do projeto

CREATE TABLE IF NOT EXISTS trens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identificador VARCHAR(50) NOT NULL UNIQUE,   -- RN15: cada trem deve ter identificador único
    modelo VARCHAR(100) NOT NULL,
    status ENUM('ativo', 'inativo', 'manutencao', 'falha') NOT NULL DEFAULT 'inativo', -- RN7: calculado com base nos sensores
    velocidade_atual DECIMAL(6,2) DEFAULT 0,     -- RF14
    localizacao_atual VARCHAR(150) DEFAULT NULL, -- RF15
    consumo_energia DECIMAL(8,2) DEFAULT NULL,   -- RF16
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Dados de exemplo para testar a tela
INSERT INTO trens (identificador, modelo, status, velocidade_atual, localizacao_atual, consumo_energia) VALUES
('TR-001', 'Expresso 4000', 'ativo', 87.50, 'KM 12 - Trecho Norte', 320.10),
('TR-002', 'Regional 2200', 'manutencao', 0.00, 'Pátio Central', 0.00),
('TR-003', 'Cargueiro 8800', 'ativo', 45.20, 'KM 56 - Trecho Sul', 510.75),
('TR-004', 'Expresso 4000', 'falha', 0.00, 'KM 34 - Trecho Leste', 12.00);
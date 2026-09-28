USE ferrorama;

ALTER TABLE trens
CHANGE identificacao identificador VARCHAR(50) NOT NULL;

ALTER TABLE trens
ADD modelo VARCHAR(100) NOT NULL DEFAULT 'Não informado';

ALTER TABLE trens
ADD status ENUM('ativo', 'inativo', 'manutencao', 'falha')
NOT NULL DEFAULT 'inativo';

ALTER TABLE trens
ADD velocidade_atual DECIMAL(6,2) DEFAULT 0;

ALTER TABLE trens
ADD localizacao_atual VARCHAR(150) DEFAULT NULL;

ALTER TABLE trens
ADD consumo_energia DECIMAL(8,2) DEFAULT NULL;

ALTER TABLE trens
ADD criado_em DATETIME DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE trens
ADD atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP
ON UPDATE CURRENT_TIMESTAMP;
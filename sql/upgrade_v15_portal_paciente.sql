-- Portal do paciente: executar após v14. Idempotente, sem excluir dados existentes.
SET NAMES utf8mb4;
CREATE TABLE IF NOT EXISTS portal_solicitacoes (
 id INT NOT NULL AUTO_INCREMENT,
 empresa_id INT NOT NULL,
 paciente_id INT NOT NULL,
 profissional_id INT NOT NULL,
 especialidade VARCHAR(60) NOT NULL,
 data_inicio DATETIME NOT NULL,
 data_fim DATETIME NOT NULL,
 status ENUM('pendente','confirmada','recusada') NOT NULL DEFAULT 'pendente',
 agendamento_id INT DEFAULT NULL,
 criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY(id),
 KEY idx_portal_empresa_status (empresa_id,status),
 KEY idx_portal_paciente (empresa_id,paciente_id),
 KEY idx_portal_profissional_data (empresa_id,profissional_id,data_inicio),
 CONSTRAINT fk_portal_empresa FOREIGN KEY(empresa_id) REFERENCES empresas(id),
 CONSTRAINT fk_portal_paciente FOREIGN KEY(paciente_id) REFERENCES pacientes(id),
 CONSTRAINT fk_portal_profissional FOREIGN KEY(profissional_id) REFERENCES usuarios(id),
 CONSTRAINT fk_portal_agendamento FOREIGN KEY(agendamento_id) REFERENCES agendamentos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS portal_acesso_limites (
 chave CHAR(64) NOT NULL,
 tentativas INT NOT NULL DEFAULT 0,
 janela DATETIME NOT NULL,
 PRIMARY KEY(chave),
 KEY idx_portal_limite_janela (janela)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

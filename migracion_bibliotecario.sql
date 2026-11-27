-- Migración segura para habilitar el perfil de bibliotecario.
-- No elimina ni modifica usuarios, préstamos ni otros datos existentes.
ALTER TABLE usuarios
  MODIFY COLUMN rol ENUM('admin','bibliotecario','lector') NOT NULL DEFAULT 'lector';

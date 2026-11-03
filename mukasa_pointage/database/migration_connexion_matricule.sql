-- À exécuter UNE SEULE FOIS sur une base existante créée avec l’ancienne version.
USE mukasa_pointage;
ALTER TABLE employees ADD COLUMN role ENUM('employe') NOT NULL DEFAULT 'employe' AFTER statut;
ALTER TABLE admins ADD COLUMN matricule VARCHAR(30) NULL AFTER id, ADD COLUMN role ENUM('admin') NOT NULL DEFAULT 'admin' AFTER matricule;
UPDATE admins SET matricule='ADM001' WHERE matricule IS NULL OR matricule='';
ALTER TABLE admins MODIFY matricule VARCHAR(30) NOT NULL, ADD UNIQUE KEY uq_admin_matricule (matricule);

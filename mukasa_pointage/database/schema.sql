CREATE DATABASE IF NOT EXISTS mukasa_pointage CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mukasa_pointage;

CREATE TABLE entities (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL UNIQUE,
  nom VARCHAR(100) NOT NULL,
  type_entite ENUM('bureau','site') NOT NULL,
  actif TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE employees (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  matricule VARCHAR(30) NOT NULL UNIQUE,
  nom VARCHAR(100) NOT NULL,
  prenom VARCHAR(100) NULL,
  entity_id INT UNSIGNED NOT NULL,
  statut ENUM('actif','inactif') NOT NULL DEFAULT 'actif',
  role ENUM('employe') NOT NULL DEFAULT 'employe',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_employee_entity FOREIGN KEY(entity_id) REFERENCES entities(id)
) ENGINE=InnoDB;

CREATE TABLE attendance (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id INT UNSIGNED NOT NULL,
  work_date DATE NOT NULL,
  heure_arrivee TIME NULL,
  heure_sortie TIME NULL,
  note VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_employee_day(employee_id,work_date),
  INDEX idx_attendance_date(work_date),
  CONSTRAINT fk_attendance_employee FOREIGN KEY(employee_id) REFERENCES employees(id)
) ENGINE=InnoDB;

CREATE TABLE admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  matricule VARCHAR(30) NOT NULL UNIQUE,
  role ENUM('admin') NOT NULL DEFAULT 'admin',
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id INT UNSIGNED NOT NULL,
  attendance_id BIGINT UNSIGNED NOT NULL,
  action VARCHAR(50) NOT NULL,
  details JSON NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_attendance(attendance_id),
  CONSTRAINT fk_audit_admin FOREIGN KEY(admin_id) REFERENCES admins(id),
  CONSTRAINT fk_audit_attendance FOREIGN KEY(attendance_id) REFERENCES attendance(id)
) ENGINE=InnoDB;

INSERT INTO entities(code,nom,type_entite) VALUES
('BUREAU','Bureau','bureau'),
('SITE01','Site 1','site'),
('SITE02','Site 2','site');

-- Mot de passe de démonstration : ChangeMe123!
INSERT INTO admins(matricule,role,username,password_hash) VALUES
('ADM001','admin','admin','$2y$12$TpIGozUXd2R1X49q3JUnpe8Tfd7Jrznv575j5FjVskjj.HHw7S5Ae');

-- Données fictives de démonstration : remplacez-les par les vrais employés.
INSERT INTO employees(matricule,nom,prenom,entity_id) VALUES
('AK001','Employé 01','Bureau',(SELECT id FROM entities WHERE code='BUREAU')),
('PT002','Employé 02','Bureau',(SELECT id FROM entities WHERE code='BUREAU')),
('AM003','Employé 03','Bureau',(SELECT id FROM entities WHERE code='BUREAU')),
('CT004','Employé 04','Bureau',(SELECT id FROM entities WHERE code='BUREAU')),
('BU005','Employé 05','Bureau',(SELECT id FROM entities WHERE code='BUREAU')),
('BU006','Employé 06','Bureau',(SELECT id FROM entities WHERE code='BUREAU')),
('BU007','Employé 07','Bureau',(SELECT id FROM entities WHERE code='BUREAU')),
('BU008','Employé 08','Bureau',(SELECT id FROM entities WHERE code='BUREAU')),
('BU009','Employé 09','Bureau',(SELECT id FROM entities WHERE code='BUREAU')),
('BU010','Employé 10','Bureau',(SELECT id FROM entities WHERE code='BUREAU')),
('S101','Employé Site 1','Site',(SELECT id FROM entities WHERE code='SITE01')),
('S201','Employé Site 2','Site',(SELECT id FROM entities WHERE code='SITE02'));

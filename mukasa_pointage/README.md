# MUKASA POINTAGE — Prototype PHP/MySQL

Application web de pointage pour trois entités : Bureau, Site 1 et Site 2.
Stack : PHP 8+, MySQL/MariaDB, HTML, Bootstrap 5.3.3, JavaScript.

## Installation locale (XAMPP)
1. Décompresser `mukasa_pointage.zip` dans `C:\xampp\htdocs\mukasa_pointage`.
2. Démarrer Apache et MySQL dans XAMPP.
3. Ouvrir phpMyAdmin et importer `database/schema.sql`.
4. Vérifier les paramètres dans `config/database.php`.
5. Ouvrir `http://localhost/mukasa_pointage/`.
6. Connexion de démonstration :
   - Employé : saisir un matricule actif (par exemple `AK001`), puis cliquer sur « Se connecter ».
   - Administrateur : matricule `ADM001`, puis mot de passe `ChangeMe123!`.
7. Remplacer les identifiants de démonstration avant tout usage réel. Le mot de passe administrateur doit être changé dans la base après installation.

## Démonstration des employés
La base SQL contient des matricules de démonstration (AK001 à BU010) dans l'entité Bureau et quelques employés de démonstration sur les deux sites. Les remplacer par les vrais employés dans l'espace administrateur ou dans la base.

## Fonctionnement
- Tous les utilisateurs commencent sur une page de connexion avec un seul champ matricule.
- Si le matricule correspond à un employé actif, une session employé est ouverte et l'utilisateur est redirigé vers le pointage.
- Si le matricule correspond à un administrateur, un second écran demande le mot de passe avant l'accès au tableau de bord.
- Le pointage utilise le matricule de la session connectée : l'employé n'a pas à le ressaisir à chaque arrivée/sortie.
- L'heure est calculée côté serveur, jamais depuis le navigateur.
- Un seul pointage d'arrivée et une seule sortie par employé et par journée.
- Une sortie sans arrivée et les doublons sont refusés.
- L'administrateur peut consulter les pointages, filtrer par date et entité, gérer les employés et corriger un pointage avec motif.
- Les corrections sont consignées dans `audit_logs`.

## À compléter avant déploiement réel
- Configurer les heures officielles et les fuseaux horaires selon la direction.
- Remplacer le mot de passe administrateur de démonstration.
- Mettre le site sous HTTPS, ajouter sauvegardes automatiques et politique de conservation.
- Définir comment les employés des sites accèdent au réseau (connexion Internet sécurisée ou réseau privé).
- Le prototype ne vérifie pas la géolocalisation et ne garantit pas que la personne qui saisit un matricule est son titulaire. Pour une sécurité plus forte, ajouter PIN personnel, QR code temporaire ou authentification.
- Les absences ne sont pas automatiquement confirmées : missions, congés et autorisations doivent être pris en compte par l'administrateur.

# Agenda partagé des salles communales

Application PHP légère pour consulter les disponibilités des salles après connexion, créer des réservations avec compte utilisateur, et administrer les salles, les accès et les statuts.

## Fonctionnalités

- Agenda hebdomadaire par salle réservé aux utilisateurs connectés.
- Connexion avec e-mail et mot de passe.
- Protection anti brute force sur les tentatives de connexion.
- Mot de passe oublié avec lien temporaire de réinitialisation par e-mail.
- Auto-inscription des associations avec validation obligatoire par un administrateur.
- Page Mon compte avec changement de mot de passe par l’utilisateur connecté et envoi du nouveau mot de passe par e-mail.
- Création de comptes associations depuis l’administration, avec l’e-mail comme login.
- Page dédiée à la gestion des comptes avec recherche par nom ou e-mail.
- Page dédiée aux demandes à traiter : créations de comptes association et réservations en attente.
- Réinitialisation du mot de passe d’un compte depuis l’administration avec envoi du nouveau mot de passe à l’adresse du compte.
- Suppression des comptes association ou administrateur, avec conservation obligatoire d’au moins un administrateur actif.
- Réservation de créneaux avec raison obligatoire et contrôle des chevauchements.
- Verrou transactionnel lors de l’enregistrement, la modification ou la validation d’une réservation pour éviter deux réservations simultanées sur le même créneau.
- Réservations cliquables dans l’agenda, avec page de détail et modification autorisée selon les droits.
- Charte PDF administrable, protégée derrière la connexion, avec acceptation obligatoire avant une réservation association.
- Réservation ou blocage d’un créneau par un administrateur au nom d’une association.
- Demande de matériel communal par événement, avec choix des quantités par type de matériel.
- Inventaire administrable : tables/bancs, barnums, grilles, barrières ou autre matériel.
- Sauvegarde globale de l’inventaire matériel depuis l’administration.
- Validation administrateur des demandes de matériel avec contrôle du stock déjà validé sur le créneau.
- Suppression administrateur des demandes de matériel annulées, refusées ou passées.
- Plages non réservables par les associations, sur une salle ou sur toutes les salles.
- Administration des salles communales.
- Création et activation/désactivation des comptes associations.
- Validation, refus ou annulation des réservations.
- Export PDF imprimable des réservations sur une période, avec choix des salles et des statuts.
- Notifications e-mail configurables lors d’une réservation et d’un changement de statut.
- Envoi d’un e-mail récapitulatif avec PDF imprimable au demandeur et aux administrateurs après validation d’une demande de matériel.
- Notification e-mail lors de la création d’un compte association ou administrateur.
- Journal d’activité administrateur en heure locale, exportable en CSV et purgeable depuis l’administration.
- Page Apparence dédiée pour personnaliser le nom affiché, le logo, les couleurs et le texte de la page de connexion.
- Sauvegarde/restauration complète au format JSON.
- Sauvegarde automatique par e-mail avec tâche planifiée OVH.
- Base SQLite par défaut, avec configuration MySQL possible.

## Première installation locale ou OVH

1. Copiez `config/config.example.php` vers `config/config.php`.
2. Gardez SQLite pour une petite installation, ou passez `database.driver` à `mysql` et renseignez la base OVH.
3. Assurez-vous que le dossier `storage/` est accessible en écriture par PHP.
4. Faites pointer le domaine vers le dossier `public/`.
5. Ouvrez le site : la page `setup` demandera de créer le premier compte administrateur.

Sur un hébergement web OVH, le fichier `.ovhconfig` fourni demande PHP 8.5, l’environnement `stable64`, le mode production et le pare-feu applicatif. Si votre espace utilise le dossier `www/` comme racine, placez le contenu de `public/` dans `www/`, puis gardez `app/`, `config/` et `storage/` au même niveau que `www/`.

Le dossier `public/uploads/branding/` doit rester accessible en écriture si vous voulez téléverser un logo depuis l’administration. Le dossier `storage/charters/` est utilisé pour stocker la charte PDF hors du dossier public.

## Configuration MySQL

Exemple dans `config/config.php` :

```php
'database' => [
    'driver' => 'mysql',
    'host' => 'votre-serveur.mysql.db',
    'port' => '3306',
    'name' => 'votre_base',
    'user' => 'votre_utilisateur',
    'password' => 'votre_mot_de_passe',
],
```

## Validation administrateur

Par défaut, une réservation créée par une association est enregistrée en attente et doit être validée par un administrateur :

```php
'reservations' => [
    'require_approval' => true,
    'max_duration_hours' => 24,
],
```

Pour revenir à une validation automatique, passez `require_approval` à `false`.

## Notifications e-mail

Les notifications utilisent la fonction PHP `mail()`, adaptée à un hébergement mutualisé simple. L’adresse e-mail d’envoi, le nom d’expéditeur et les destinataires administrateurs sont configurables depuis la page d’administration.

Le fichier `config/config.php` sert de configuration par défaut :

```php
'mail' => [
    'enabled' => true,
    'from_email' => 'no-reply@votre-domaine.fr',
    'from_name' => 'Agenda des salles communales',
    'admin_recipients' => [
        'mairie@votre-domaine.fr',
    ],
],
'site' => [
    'public_url' => 'https://votre-domaine.fr/agenda/public/',
],
```

Si `admin_recipients` est vide, tous les comptes administrateurs actifs recevront les notifications de nouvelles réservations et de créations de comptes.

L’URL publique de l’agenda est configurable depuis l’administration. Elle sert notamment à construire le lien de connexion envoyé dans les e-mails.

Lorsqu’un compte est créé, l’adresse du compte reçoit son e-mail de connexion, le mot de passe provisoire saisi par l’administrateur et le lien de connexion. Les administrateurs reçoivent une notification de création sans le mot de passe.

Lorsqu’une association demande elle-même un compte, le compte est créé inactif et reste inutilisable jusqu’à validation par un administrateur. Les administrateurs reçoivent une notification et peuvent traiter la demande depuis la page `Demandes`.

Lorsqu’un mot de passe est modifié depuis Mon compte ou réinitialisé par un administrateur, le nouveau mot de passe est envoyé à l’adresse e-mail du compte concerné.

La page “Mot de passe oublié” envoie un lien temporaire par e-mail, valable 60 minutes par défaut. La durée peut être modifiée dans `config/config.php` :

```php
'security' => [
    'password_reset_minutes' => 60,
    'login_max_attempts' => 5,
    'login_window_minutes' => 15,
    'login_lock_minutes' => 15,
],
```

La connexion est protégée contre les essais répétés : après plusieurs échecs sur la même adresse e-mail ou la même adresse IP, l’accès est temporairement bloqué. Les tentatives réussies, échouées et bloquées sont enregistrées dans le journal d’activité.

Lorsqu’une demande de matériel est validée, le demandeur et les destinataires administrateurs reçoivent un e-mail récapitulatif avec un PDF en pièce jointe, prévu pour être imprimé et joint au dossier de sortie du matériel.

## Personnalisation

Depuis la page `Apparence` de l’administration, vous pouvez modifier :

- le nom affiché dans l’en-tête ;
- le logo de la commune ou de l’entité ;
- la couleur principale, la couleur secondaire et la couleur de fond ;
- le texte affiché sur la page de connexion pour les personnes sans compte ;
- la charte PDF de réservation.

Les réglages sont stockés dans la table `settings`. Les logos sont stockés dans `public/uploads/branding/`. La charte est stockée dans `storage/charters/` et téléchargée via une page qui exige une connexion.

## Sauvegarde et restauration

La page `Sauvegardes` de l’administration permet de télécharger un fichier JSON complet contenant :

- les comptes et mots de passe hachés ;
- les statuts de validation des demandes de compte ;
- les salles ;
- les réservations ;
- les acceptations de charte ;
- les plages non réservables ;
- l’inventaire matériel et les demandes de matériel ;
- le journal d’activité ;
- les paramètres d’apparence ;
- les logos et la charte téléversés.

La restauration remplace les données actuelles. Avant toute restauration, téléchargez une sauvegarde de l’état actuel.

La page `Sauvegardes` permet aussi d’activer une sauvegarde automatique par e-mail vers une ou plusieurs adresses dédiées. L’e-mail contient le fichier JSON complet en pièce jointe.

Pour automatiser l’envoi avec les tâches planifiées OVH, utilisez le script PHP côté hébergement. Aucun token n’est nécessaire dans ce mode :

- script OVH : `/chemin/agenda/cron/backup.php`
- commande CLI si demandée : `php /chemin/agenda/cron/backup.php`

L’URL protégée par clé `https://votre-domaine.fr/agenda/public/cron_backup.php?token=VOTRE_CLE` reste disponible uniquement si vous utilisez un service externe qui appelle une URL publique.

Le destinataire, la fréquence, la clé pour appel URL externe et le dernier statut d’envoi sont configurables depuis l’administration.

## Lancement avec PHP

Si PHP est installé localement :

```bash
php -S localhost:8000 -t public
```

Puis ouvrez `http://localhost:8000`.

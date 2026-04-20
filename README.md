# Système de Caisse - Application PHP

Une application web complète de gestion de caisse pour supermarché, développée en PHP avec interface moderne utilisant TailwindCSS.

## Fonctionnalités

### Gestion des utilisateurs
- **3 rôles prédéfinis** : Super Administrateur, Manager, Caissier
- **Authentification sécurisée** avec sessions PHP
- **Gestion des comptes** (ajout/suppression pour les admins)

### Gestion des produits
- **Enregistrement de produits** avec code-barres, nom, prix HT, stock
- **Recherche et liste** des produits
- **Mise à jour du stock** en temps réel

### Système de facturation
- **Scan de code-barres** (manuel pour l'instant)
- **Calcul automatique** des totaux HT, TVA (18%), TTC
- **Génération de factures** avec numéros uniques
- **Impression** des factures

### Rapports
- **Rapport journalier** : ventes, factures, produits vendus
- **Rapport mensuel** : évolution des ventes, statistiques détaillées

## Comptes de démonstration

- **Super Admin** : `admin` / `admin123`
- **Manager** : `manager` / `manager123`
- **Caissier** : `caissier` / `caissier123`

## Installation

1. **Prérequis** :
   - Serveur web (Apache/Nginx)
   - PHP 7.4+ avec extensions JSON et Session
   - Navigateur moderne

2. **Déploiement** :
   - Copier tous les fichiers dans le répertoire web
   - S'assurer que PHP peut écrire dans le dossier `data/`
   - Accéder à `index.php` dans le navigateur

3. **Configuration** (optionnel) :
   - Modifier `config/config.php` pour adapter les paramètres
   - Ajuster les taux de TVA, devise, etc.

## Structure des fichiers

```
facturation/
├── index.php                 # Point d'entrée
├── config/
│   └── config.php           # Configuration générale
├── auth/
│   ├── login.php            # Page de connexion
│   ├── logout.php           # Déconnexion
│   └── session.php          # Gestion des sessions
├── modules/
│   ├── facturation/
│   │   ├── nouvelle-facture.php    # Création de factures
│   │   ├── afficher-facture.php    # Affichage des factures
│   │   └── calcul.php              # API de calcul
│   ├── produits/
│   │   ├── enregistrer.php         # Ajout de produits
│   │   ├── liste.php               # Liste des produits
│   │   └── lire.php                # API de lecture produit
│   └── admin/
│       ├── gestion-comptes.php     # Gestion utilisateurs
│       ├── ajouter-compte.php      # Redirection
│       └── supprimer-compte.php    # Redirection
├── rapports/
│   ├── rapport-journalier.php      # Stats journalières
│   └── rapport-mensuel.php         # Stats mensuelles
├── data/
│   ├── utilisateurs.json           # Données utilisateurs
│   ├── produits.json               # Données produits
│   └── factures.json               # Données factures
├── includes/
│   ├── header.php                  # En-tête HTML
│   ├── footer.php                  # Pied de page HTML
│   ├── fonctions-auth.php          # Fonctions authentification
│   ├── fonctions-produits.php      # Fonctions produits
│   └── fonctions-factures.php      # Fonctions factures
└── assets/
    ├── css/
    │   └── style.css               # Styles personnalisés
    └── js/
        └── scanner.js              # Module scan code-barres
```

## Sécurité

- **Sessions PHP** avec régénération d'ID
- **Timeout de session** automatique (1 heure)
- **Validation des entrées** côté serveur
- **Contrôle d'accès** basé sur les rôles
- **Protection XSS** avec htmlspecialchars()

## Technologies utilisées

- **Backend** : PHP 7.4+ (sans framework)
- **Frontend** : HTML5, TailwindCSS, JavaScript vanilla
- **Icônes** : Lucide (via CDN)
- **Stockage** : Fichiers JSON (facilement remplaçable par base de données)

## Développement futur

- **Base de données** : Migration vers MySQL/PostgreSQL
- **Scan réel** : Intégration QuaggaJS pour scan caméra
- **API REST** : Exposition des fonctionnalités via API
- **Multi-magasin** : Support de plusieurs points de vente
- **Sauvegarde** : Système de sauvegarde automatique
- **Logs** : Traçabilité des actions utilisateur

## Support

Pour toute question ou problème, vérifier :
1. Les logs d'erreur PHP
2. Les permissions d'écriture sur `data/`
3. La configuration du serveur web
4. La compatibilité PHP

## Licence

Application développée pour démonstration - utilisation libre pour projets personnels ou éducatifs.
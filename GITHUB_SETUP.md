# Guide de publication sur GitHub

Ce guide vous explique comment publier ce projet sur GitHub pour le partager en open source.

## 📋 Prérequis

- Un compte GitHub (créez-en un sur [github.com](https://github.com) si vous n'en avez pas)
- Git installé sur votre machine
- Accès en ligne de commande (Terminal, PowerShell, CMD)

## 🚀 Étapes de publication

### 1. Initialiser Git (si pas déjà fait)

```bash
git init
```

### 2. Ajouter tous les fichiers

```bash
git add .
```

### 3. Créer le premier commit

```bash
git commit -m "Initial commit: Dashboard Freelance - Open Source"
```

### 4. Créer un nouveau repository sur GitHub

1. Allez sur [github.com](https://github.com)
2. Cliquez sur le bouton **"+"** en haut à droite
3. Sélectionnez **"New repository"**
4. Remplissez les informations :
   - **Repository name** : `dashboard-freelance` (ou le nom de votre choix)
   - **Description** : "Dashboard complet et open source pour gérer vos revenus, factures, clients et paiements"
   - **Visibilité** : Public (pour open source) ou Private
   - **NE COCHEZ PAS** "Initialize this repository with a README" (on a déjà un README)
5. Cliquez sur **"Create repository"**

### 5. Lier votre dépôt local à GitHub

GitHub vous donnera une URL. Utilisez-la dans cette commande :

```bash
git remote add origin https://github.com/VOTRE_USERNAME/dashboard-freelance.git
```

Remplacez `VOTRE_USERNAME` par votre nom d'utilisateur GitHub.

### 6. Pousser le code sur GitHub

```bash
git branch -M main
git push -u origin main
```

Si GitHub vous demande vos identifiants :
- **Username** : Votre nom d'utilisateur GitHub
- **Password** : Utilisez un Personal Access Token (voir ci-dessous)

## 🔑 Créer un Personal Access Token (si nécessaire)

Si Git vous demande un mot de passe :

1. Allez sur GitHub > Settings > Developer settings > Personal access tokens > Tokens (classic)
2. Cliquez sur **"Generate new token"**
3. Donnez-lui un nom (ex: "Dashboard Freelance")
4. Sélectionnez les permissions : `repo` (accès complet aux dépôts)
5. Cliquez sur **"Generate token"**
6. **COPIEZ LE TOKEN** (vous ne pourrez plus le voir après)
7. Utilisez ce token comme mot de passe lors du `git push`

## ✅ Vérification

Une fois le push terminé :

1. Rafraîchissez la page de votre repository sur GitHub
2. Vous devriez voir tous vos fichiers
3. Le README.md s'affichera automatiquement sur la page principale

## 🔄 Commandes Git utiles pour la suite

### Voir l'état des modifications
```bash
git status
```

### Ajouter des fichiers modifiés
```bash
git add .
```

### Créer un commit
```bash
git commit -m "Description de vos modifications"
```

### Envoyer les modifications sur GitHub
```bash
git push
```

### Récupérer les modifications depuis GitHub
```bash
git pull
```

## 📝 Mise à jour du lien GitHub dans le footer

Une fois votre repository créé, mettez à jour le lien dans `includes/footer.php` :

```php
<a href="https://github.com/VOTRE_USERNAME/dashboard-freelance" target="_blank" rel="noopener noreferrer">Open Source</a>
```

## 🎉 C'est fait !

Votre projet est maintenant sur GitHub et accessible à tous (si vous l'avez mis en public). Les autres développeurs peuvent :
- Voir votre code
- Le télécharger (clone)
- Proposer des améliorations (pull requests)
- Signaler des bugs (issues)

---

**Besoin d'aide ?** Consultez la [documentation GitHub](https://docs.github.com) ou ouvrez une issue sur votre repository.


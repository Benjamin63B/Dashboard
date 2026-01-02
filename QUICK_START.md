# 🚀 Démarrage rapide pour GitHub

## ⚡ Commandes rapides

### 1. Configurer Git (une seule fois)

```bash
git config --global user.name "Votre Nom"
git config --global user.email "votre.email@example.com"
```

### 2. Vérifier que tout est prêt

```bash
git status
```

### 3. Créer le commit initial

```bash
git add .
git commit -m "Initial commit: Dashboard Freelance - Open Source CMS"
```

### 4. Créer le repository sur GitHub

1. Allez sur https://github.com/new
2. Nom : `dashboard-freelance` (ou autre)
3. Description : "Dashboard complet pour freelances et créateurs"
4. **Public** pour open source
5. **Ne cochez PAS** "Initialize with README"
6. Cliquez sur **Create repository**

### 5. Connecter et pousser

```bash
git remote add origin https://github.com/VOTRE_USERNAME/dashboard-freelance.git
git branch -M main
git push -u origin main
```

**Remplacez `VOTRE_USERNAME` par votre nom d'utilisateur GitHub !**

### 6. Mettre à jour le lien dans footer.php

Ouvrez `includes/footer.php` et remplacez le lien GitHub :

```php
<a href="https://github.com/VOTRE_USERNAME/dashboard-freelance" target="_blank" rel="noopener noreferrer">Open Source</a>
```

## ✅ C'est tout !

Votre projet est maintenant sur GitHub ! 🎉

---

**Besoin d'aide ?** Consultez `GITHUB_SETUP.md` pour un guide détaillé.


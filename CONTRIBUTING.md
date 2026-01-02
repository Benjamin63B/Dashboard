# Guide de contribution

Merci de votre intérêt pour contribuer à ce projet ! 🎉

## 🚀 Comment contribuer

### Signaler un bug

1. Vérifiez que le bug n'a pas déjà été signalé dans les [Issues](https://github.com/votre-repo/issues)
2. Créez une nouvelle issue avec :
   - Un titre clair et descriptif
   - Une description détaillée du problème
   - Les étapes pour reproduire le bug
   - Votre environnement (PHP version, OS, etc.)

### Proposer une amélioration

1. Créez une issue pour discuter de votre idée
2. Attendez la validation avant de commencer à coder
3. Suivez les standards de code du projet

### Soumettre une Pull Request

1. **Fork** le repository
2. Créez une **branche** pour votre fonctionnalité :
   ```bash
   git checkout -b feature/ma-fonctionnalite
   ```
3. **Codez** votre fonctionnalité en suivant les standards
4. **Testez** votre code
5. **Commitez** vos changements :
   ```bash
   git commit -m "Ajout de ma fonctionnalité"
   ```
6. **Poussez** vers votre fork :
   ```bash
   git push origin feature/ma-fonctionnalite
   ```
7. Créez une **Pull Request** sur GitHub

## 📝 Standards de code

### PHP

- Utilisez PSR-12 pour le style de code
- Indentez avec 4 espaces
- Utilisez des noms de variables et fonctions descriptifs
- Commentez votre code si nécessaire
- Utilisez des requêtes préparées PDO pour la base de données

### Exemple

```php
<?php
/**
 * Description de la fonction
 * 
 * @param string $param Description du paramètre
 * @return bool Description de la valeur de retour
 */
function maFonction($param) {
    // Code ici
    return true;
}
```

### Fichiers de langue

- Ajoutez toutes les traductions dans les 4 fichiers de langue
- Utilisez des clés descriptives en anglais
- Gardez la même structure dans tous les fichiers

### CSS

- Utilisez les variables CSS définies dans `:root`
- Respectez la structure BEM si possible
- Commentez les sections complexes

## 🧪 Tests

Avant de soumettre votre PR :

- [ ] Testez sur PHP 7.4+
- [ ] Vérifiez que l'installation fonctionne
- [ ] Testez sur différents navigateurs
- [ ] Vérifiez qu'il n'y a pas d'erreurs PHP
- [ ] Testez les fonctionnalités modifiées

## 📚 Documentation

- Mettez à jour le README.md si nécessaire
- Ajoutez des commentaires dans le code
- Documentez les nouvelles fonctionnalités

## ✅ Checklist avant de soumettre

- [ ] Mon code suit les standards du projet
- [ ] J'ai testé mon code
- [ ] J'ai mis à jour la documentation
- [ ] J'ai ajouté les traductions dans toutes les langues
- [ ] Mon commit message est clair et descriptif
- [ ] Je n'ai pas ajouté de fichiers sensibles (config.php, etc.)

## 🎯 Types de contributions recherchées

- 🐛 Correction de bugs
- ✨ Nouvelles fonctionnalités
- 📝 Amélioration de la documentation
- 🌍 Traductions dans d'autres langues
- 🎨 Améliorations de l'interface
- ⚡ Optimisations de performance
- 🔒 Améliorations de sécurité

## 💬 Questions ?

N'hésitez pas à ouvrir une issue pour poser des questions !

---

**Merci de contribuer à ce projet open source !** 🙏


# 🥋 Chuck365 Fact Viewer (WordPress Plugin)

**Chuck365 Fact Viewer** est un plugin WordPress moderne conçu pour afficher un fait unique et hilarant de Chuck Norris chaque jour sur votre site. 

Propulsé par l'API de [Chuck365.fr](https://chuck365.fr), ce plugin allie la puissance légendaire de Chuck Norris à une architecture logicielle robuste et élégante.

## 🚀 Fonctionnalités

* **Intégration Gutenberg Native** : Un bloc dédié avec prévisualisation en temps réel dans l'éditeur.
* **Personnalisation Totale** : Modifiez les couleurs de bordure, de fond et de texte directement depuis l'inspecteur de blocs ou les réglages d'administration.
* **Performance Optimisée** : Utilisation de `Transients` WordPress pour mettre en cache le "Fact" du jour et éviter les appels API inutiles.
* **Système de Presets** : Changez le style en un clic (Original, Fire, Dark, Ocean, Forest, Gold).
* **Shortcode Inclus** : Utilisez `[chuck_fact]` pour afficher Chuck où vous voulez.

## 🛠️ Stack Technique

* **PHP 8.3+** : Utilisation de `strict_types=1` pour une stabilité maximale.
* **Sécurité renforcée** : Protection CSRF systématique et nettoyage des entrées (`clean_input`, `wp_kses_post`).
* **JavaScript (Vanilla)** : Scripts légers sans dépendances lourdes pour une vitesse de chargement optimale.
* **API REST** : Communication fluide avec le backend de Chuck365.fr via `wp_remote_get`.

## 📂 Structure du Projet

```text
chuck365-viewer/
├── block/               # Composants du bloc Gutenberg (JSON, Edit, Ajax)
├── css/                 # Styles CSS (Front & Admin)
├── images/              # Assets graphiques
├── js/                  # Logique JavaScript (ColorPicker & Tabs)
├── languages/           # Fichiers de traduction (.pot)
└── chuck365-viewer.php  # Cœur du plugin et contrôleur principal
```

## 📥 Installation

1. Téléchargez le dossier du plugin.
2. Déposez-le dans `/wp-content/plugins/`.
3. Activez **Chuck365 Fact Viewer** depuis votre tableau de bord WordPress.
4. Rendez-vous dans **Réglages > Chuck365** pour configurer votre premier style !

## 🤝 Soutenir le Projet

Le serveur et l'API de Chuck365 sont maintenus par passion. Si vous aimez ce plugin, n'hésitez pas à soutenir l'auteur via le bouton "Soutenir Chuck365" directement dans l'interface du plugin ou sur le site [Chuck365.fr](https://chuck365.fr). 

---
© **Papy 3D Factory** — *Parce que même WordPress a besoin de la protection de Chuck Norris.*

#  Capaci

## Lancement local

Depuis le dossier `project`, installez les dépendances puis lancez le serveur :

```powershell
cd project
php composer.phar install
php -S 127.0.0.1:8000 -t public
```

La connexion PostgreSQL est lue dans `project/.env` depuis `DATABASE_URL`.
Copiez `.env.example` si vous devez recréer ce fichier.

## Accès au jeu : https://notes-de-cours-kreine.fr/public/capaci

## Équipe de développement

- Thomas BOUDEELE : https://github.com/timberlek78
- Louis HAGUES : https://github.com/Louis-Hagues
- Killian REINE :  https://github.com/killianreine

##  Présentation du jeu

**Capaci** est un jeu de stratégie pour **deux joueurs (1v1)** qui combine :

* la logique de déplacement des **échecs**,
* et le système de domination du **Chi Fou Mi (Pierre–Feuille–Ciseaux)**.

---

##  Les pièces

Il existe **trois familles de pièces** :

* ✂️ **Ciseaux** → battent les **Feuilles**
* 📄 **Feuilles** → battent les **Pierres**
* 🪨 **Pierres** → battent les **Ciseaux**

### Relation de domination

```
✂️ Ciseaux ──► 📄 Feuilles
📄 Feuilles ──► 🪨 Pierres
🪨 Pierres ──► ✂️ Ciseaux
```

---

##  Composition d’une armée

Chaque joueur possède :

* 4 ✂️ Ciseaux
* 4 📄 Feuilles
* 4 🪨 Pierres

-> **12 pièces au total par joueur**

Les pièces sont distinguées par leur **couleur** (par exemple : Rouge et Noir).

---

##  Objectif du jeu

La partie se termine lorsqu’un joueur :

1. **perd toutes les pièces d’une même famille**
   *(ex. plus aucun Ciseau)*
   **OU**
2. **ne peut plus effectuer aucun déplacement valide**

Le joueur a alors perdu. Son adversaire lui remporte donc la partie.

---

##  Plateau de jeu

Le jeu se déroule sur un **plateau de 6 × 6 cases**.

### Schéma du plateau

```
+----+----+----+----+----+----+
| 📄 | ✂️ | 🪨 | 🪨 | ✂️ | 📄 |
+----+----+----+----+----+----+
| ✂️ | 🪨 | 📄 | 📄 | 🪨 | ✂️ |
+----+----+----+----+----+----+
|    |    |    |    |    |    |
+----+----+----+----+----+----+
|    |    |    |    |    |    |
+----+----+----+----+----+----+
| ✂️ | 🪨 | 📄 | 📄 | 🪨 | ✂️ |
+----+----+----+----+----+----+
| 📄 | ✂️ | 🪨 | 🪨 | ✂️ | 📄 |
+----+----+----+----+----+----+
```

---

##  Déroulement d’un tour

* Les joueurs jouent **à tour de rôle**
* À son tour, un joueur :

  * choisit **une de ses pièces**
  * effectue **un déplacement valide**
  * peut éventuellement **capturer une pièce adverse**

---

##  Déplacement des pièces

Toutes les pièces se déplacent **comme le roi aux échecs** :

* horizontalement
* verticalement
* diagonalement

### Directions possibles

```
↖  ↑  ↗
←  ●  →
↙  ↓  ↘
```

---

##  Portée du déplacement (règle spéciale)

Le **nombre de cases** qu’une pièce peut parcourir dépend :

* du **nombre de pièces (alliées ou adverses)** présentes **sur les côtés adjacents** de cette pièce.

>  Plus il y a de pièces sur les côtés, plus la portée de déplacement augmente.

*(Cette règle peut être précisée davantage si nécessaire, par exemple avec un calcul exact ou des exemples.)*

### Exemple visuel

```
[ ] [N] [ ]
[R] [●] [N]
[ ] [ ] [ ]

● = pièce active
R/N = pièces rouges ou noires adjacentes
```
-> Ici, la pièce ● a **3 pièces adjacentes**, ce qui influence sa distance de déplacement.

---

## Capture des pièces

* Une pièce peut capturer **uniquement** une pièce qu’elle domine selon la règle :

  * Pierre > Ciseaux
  * Ciseaux > Feuille
  * Feuille > Pierre
* Si une pièce tente de capturer une pièce qui la domine, **elle est éliminée** à la place.


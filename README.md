# Canalblog Importer [![Build Status](https://travis-ci.org/oncletom/wp-canalblog-importer.svg?branch=master)](https://travis-ci.org/oncletom/wp-canalblog-importer)

**Requires at least:** 5.0
**Tested up to:** 5.3-RC2
**Stable tag:** trunk

> Fatigué(e) d'avoir à gérer un blog sur Canalblog ?
Cette extension va vous permettre de TOUT récupérer en quelques clics.


## Description ##

**Ne fonctionne(ra) pas sur un hébergement Windows**

Cette extension récupère tout votre contenu public de n'importe quel blog Canalblog. Il faut juste un peu de patience pendant que tout se fait tout seul.

**Ce qui est rappatrié**

 * Les articles
 * Les catégories
 * Les mots-clefs
 * Les médias
 * Les commentaires
 * Beaucoup d'heures de votre travail

En bonus, si vous avez lié vos articles entre eux sur Canalblog, l'outil
va corriger les liens pour qu'ils pointent vers leur nouvelle adresse.
La classe !

**Ce qui n'est pas rappatrié**

 * Votre liste de liens amis
 * Le référencement
 * L'absence de réponses de Canalblog à vos questions


**Traductions intégrées**

* Anglais
* Français
* Biélorusse (par [Marcis G.](http://pc.de/))

**Remarque**

Cette extension nécessite au minimum `php@7.1`. Si vous avez un bon hébergeur,
vous n'aurez même pas besoin d'y penser.
Sinon vous verrez plein d'erreurs et rien ne fonctionnera.

## Installation ##

L'installation se fait de manière on ne peut plus classique :

1. Uploadez le plugin dans votre répertoire `wp-content/plugins` ou cherchez-le depuis l'outil d'installation de WordPress
1. Activez-le depuis votre interface d'administration
1. Rendez-vous dans la rubrique Outils > Importer

Encore quelques clics et ça sera terminé !

## Développement

```bash
brew install mariadb
mysql.server start
composer install

sh bin/install-wp-tests.sh wordpress_test root '' 127.0.0.1 5.2
```

```bash
composer run test
```

## Changelog ##

[Cf. section "Changelog" de `readme.txt`](readme.txt).

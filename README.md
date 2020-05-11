# Canalblog Importer [![Build Status](https://travis-ci.com/oncletom/wp-canalblog-importer.svg?branch=master)](https://travis-ci.com/oncletom/wp-canalblog-importer)

**Requires at least:** 5.2
**Tested up to:** 5.4
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

Cette extension nécessite au minimum `php@7.3`. Si vous avez un bon hébergeur,
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
# plugin (test) dependencies
$ composer install

# ephemeral database
$ docker run --rm -p 3306:3306 -e MYSQL_ROOT_HOST=% -e MYSQL_ALLOW_EMPTY_PASSWORD=1 -e MYSQL_ROOT_PASSWORD='' mariadb:latest

# setup a test WordPress instance
$ sh bin/install-wp-tests.sh wordpress_test root '' 127.0.0.1 5.4
```

```bash
$ composer run test
> phpunit
Installing...
Running as single site... To run multisite, use -c tests/phpunit/multisite.xml
Not running ajax tests. To execute these, use --group ajax.
Not running ms-files tests. To execute these, use --group ms-files.
Not running external-http tests. To execute these, use --group external-http.
PHPUnit 7.5.16 by Sebastian Bergmann and contributors.

........E.F.....................................E......F....      60 / 60 (100%)
```

## Changelog ##

[Cf. section "Changelog" de `readme.txt`](readme.txt).

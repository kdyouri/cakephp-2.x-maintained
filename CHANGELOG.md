# CHANGELOG — Fork cakephp-2.x-maintained

Fork privé de maintenance de CakePHP 2.x, basé sur la version officielle
2.10.24. Le nom du package Composer (`cakephp/cakephp`) est conservé ; les
versions du fork suivent le schéma `2.10.24.N`.

## 2.10.24.4 — 2026-11-02

**Correctif console sous PHP 8.x.**

- `Shell::hasMethod()` : retourne `false` pour un nom de commande non
  chaîne ou vide au lieu de construire un `ReflectionMethod($this, null)`
  (TypeError fatal sous PHP 8.x). Le flux `cake` sans commande affiche
  de nouveau l'aide en console, comme sous PHP 7.4. Testé sous PHP 7.4.33
  et 8.5.12 (ShellTest 43 tests OK des deux côtés).

## 2.10.24.3 — 2026-11-02

**Compatibilité PHP 7.4 → 8.5** (aucun changement fonctionnel).

- `composer.json` : contrainte PHP `>=5.3.0,<8.0.0` → `>=7.4.0` (l'ancienne
  borne supérieure bloquait toute résolution sous PHP 8.x alors que le code
  est testé fonctionnel).
- `Security::rijndael()` : lève une `CakeException` claire quand l'extension
  mcrypt (supprimée en PHP 7.2) est absente, au lieu d'un fatal
  `Call to undefined function`. L'ordre de validation des paramètres est
  préservé.
- `Security::cipher()` : le seed très grand est borné explicitement à
  `PHP_INT_MAX` (comportement identique à la saturation historique du cast
  sous PHP 7, sans la deprecation du cast introduite en PHP 8.1).
- `lib/Cake/Utility/String.php` : la classe `String` (nom réservé depuis
  PHP 7.0, fatal à la compilation) n'est plus déclarée ; le fichier produit
  une notice pointant vers `CakeText` si quelque chose l'autoload encore.
- `CakeObject` : attribut `#[AllowDynamicProperties]` déclaré et hérité par
  toute la hiérarchie (controllers, models, views, helpers, components,
  shells) — supprime les deprecations PHP 8.2+ des propriétés dynamiques,
  sans effet sous PHP 7.x.
- `ShellDispatcher` : `strpos((string)$params[...])` — supprime la
  deprecation `strpos(null)` en console sous PHP 8.1+.

Tests : sous PHP 7.4.33 les suites core conservent leurs résultats
(View 85 OK, CakeRequest 92 OK, Security 33 OK/1 skip, Controller 64 OK
(+7 erreurs baseline tests), Model 369 (1 err/28 skip baseline tests),
SecurityComponent 81 (7 échecs + 8 erreurs baseline tests, basés sur la
session)). Sous PHP 8.5.12 : View 85 OK, CakeRequest 92 OK, Security 33 OK
(14 skips — mcrypt absent), Controller 64 OK, Model 369 (1 err/28 skip,
identique), CakeText 25 OK ; console sans deprecation.

## 2.10.24.2 — 2026-11-02

**Backport : CVE-2015-8379 / GHSA-556q-h4vr-pgh2 (high)** — Contournement CSRF
via méthode forgée (`_method` / `X-HTTP-Method-Override`).

- `SecurityComponent::startup()` : seules les requêtes GET, HEAD et OPTIONS
  **sans données POST** sautent la validation (`validatePost` + CSRF) ;
  toute autre méthode, forgée incluse, est validée et rejetée sans jeton.
- Aligné sur le correctif officiel 3.1.5 (commit `0f818a23a8`).
- Tests : 3 nouveaux tests dans `SecurityComponentTest` (2 démontraient le
  bypass avant correctif, 1 garantit le non-rejet des GET légitimes) ;
  `testRequireSecureSucceed` ajusté (méthode de test irréaliste `Secure`
  remplacée par `GET`).

## 2.10.24.1 — 2026-11-02

**Backport : CVE-2026-48820 / GHSA-wpvj-hjcr-h3p2 (moderate)** — Path traversal
dans la résolution des fichiers de vue (`View::element()`).

- Ajout de `View::_checkFilePath()` : tout nom de template contenant `..` est
  résolu en `realpath` et validé contre l'ensemble des chemins de templates
  (application + plugin), pour les résolveurs de vues, layouts et elements.
- Aligné sur le correctif officiel 4.5.11 (commit `2af17bd02f`).
- Tests : 4 nouveaux tests dans `ViewTest` (3 démontraient l'inclusion hors
  chemin avant correctif, 1 garantit le traversal intra-cascade légitime).

## 2.10.24 — Base officielle

Tag `2.10.24` de cakephp/cakephp (commit `cf14e6546`, *Bump version*),
dernière version officielle de la branche `2.x` (supprimée du dépôt officiel
après EOL). Le fork est créé depuis ce tag avec l'historique Git officiel.

Audit initial : 4 advisories remontées par `composer audit` pour
`cakephp/cakephp 2.10.24` — 2 applicables (corrigées ci-dessus), 2 non
applicables (justifications dans SECURITY.md).
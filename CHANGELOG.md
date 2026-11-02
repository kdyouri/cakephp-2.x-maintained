# CHANGELOG — Fork cakephp-2.x-maintained

Fork privé de maintenance de CakePHP 2.x, basé sur la version officielle
2.10.24. Le nom du package Composer (`cakephp/cakephp`) est conservé ; les
versions du fork suivent le schéma `2.10.24.N`.

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
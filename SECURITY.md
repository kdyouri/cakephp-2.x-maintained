# Security — Fork cakephp-2.x-maintained

## Contexte

Ce dépôt est un fork privé de maintenance du framework CakePHP 2.x, basé sur la
dernière version officielle **CakePHP 2.10.24** (branche `2.x` supprimée du dépôt
officiel après EOL ; le fork est créé depuis le tag officiel `2.10.24`).

- Nom du package Composer conservé : `cakephp/cakephp` (non modifié).
- Branche principale : `2.x-maintained`.
- Versionnement du fork : `2.10.24.N` (N = numéro de correctif backporté).
- Historique Git officiel conservé.

## Méthodologie d'audit

1. Audit Composer : `composer validate` + `composer audit` sur un projet témoin
   exigeant `cakephp/cakephp:2.10.24` (l'audit porte sur le package framework,
   pas seulement ses dépendances).
2. Pour chaque advisory : lecture du GHSA/CVE, des plages de versions affectées
   et des commits de correctif officiels (branches 3.x, 4.x, 5.x).
3. Vérification systématique du code réel de 2.10.24 : chaque advisory n'est pas
   considérée comme applicable automatiquement ; la présence effective du motif
   vulnérable dans l'architecture 2.x est vérifiée avant tout backport.
4. Sources : GitHub Security Advisories, dépôt officiel cakephp/cakephp, NVD.

## Vue d'ensemble

| CVE / GHSA | Sévérité | Composant | Verdict | Version corrigée |
|---|---|---|---|---|
| CVE-2026-48820 / GHSA-wpvj-hjcr-h3p2 | Moderate (6.3) | View | **Applicable — corrigé** | 2.10.24.1 |
| CVE-2015-8379 / GHSA-556q-h4vr-pgh2 | High (8.8) | SecurityComponent | **Applicable (résidu) — corrigé** | 2.10.24.2 |
| CVE-2026-79752 / GHSA-vjqc-q4mp-2rvf | Critical (9.2) | Database\FunctionsBuilder | Non applicable (composant absent de 2.x) | — |
| CVE-2020-15400 / GHSA-j33j-fg2g-mcv2 | Moderate (4.3) | CsrfProtectionMiddleware | Non applicable (architecture session, pas de cookie CSRF en 2.x) | — |

Détails dans [BACKPORTS.md](BACKPORTS.md).

## Vulnérabilités corrigées

### CVE-2026-48820 — Path traversal dans View (2.10.24.1)

`View::_getElementFileName()`, `_getLayoutFileName()` et `_getViewFileName()`
résolvaient les noms de templates contenant `..` sans vérifier que le fichier
résolu reste dans les chemins de templates de l'application/plugin. Si un nom
d'element est construit à partir de données utilisateur, il est possible
d'inclure d'autres fichiers PHP du serveur (exposition de secrets / code).

Correctif : backport du mécanisme `_checkFilePath()` introduit en 4.5.11
(commit officiel `2af17bd02f`), appliqué aux trois résolveurs de fichiers, avec
comparaison du `realpath` contre l'ensemble des chemins de templates du plugin.
Le traversal légitime à l'intérieur du chemin de cascade reste autorisé
(par ex. `element('../Tests/index')` pour un plugin).

Tests de régression : 4 nouveaux tests dans `ViewTest` — 3 démontraient
l'inclusion hors chemin avant correctif (échecs RED), le 4e garantit que le
traversal intra-cascade légitime reste fonctionnel.

### CVE-2015-8379 — Contournement CSRF via `_method` (2.10.24.2)

En 2.10.24, une requête avec méthode forgée via le paramètre `_method`
(`CakeRequest::_processPost()` écrase `$_SERVER['REQUEST_METHOD']`) ne
déclenchait ni `validatePost` ni la validation CSRF de `SecurityComponent`
quand les données POST étaient vidées (cas de toute méthode forgée hors
POST/PUT/PATCH/DELETE). Un attaquant pouvait donc déclencher une action
d'état depuis un formulaire cross-site sans jeton CSRF.

Contexte historique : le correctif officiel 3.1.5 (commit `0f818a23a8`,
*« Only allow GET, HEAD, OPTIONS to not have CSRF tokens »*) a traité la
partie « données préservées » ; la lignée 2.x avait déjà partiellement durci le
comportement en janvier 2016 (vidage des données POST pour les méthodes
forgées non standard, commit `bd53ef01a6`), mais la validation restait
entièrement sautée pour ces requêtes. Ce backport comble ce résidu en suivant
la sémantique officielle : seules les requêtes GET, HEAD et OPTIONS **sans
données POST** sautent la validation ; toute autre méthode, forgée incluse
(y compris en casse mixte), est validée et rejetée (blackhole) sans jeton.

Changement de test existant : `testRequireSecureSucceed` utilisait la méthode
irréaliste `'Secure'`, qui encodait l'ancien comportement ; elle utilise
désormais `GET` (l'intention du test — vérifier le succès HTTPS — est
inchangée).

Tests de régression : 2 nouveaux tests démontraient le bypass avant correctif
(échecs RED : méthode forgée `TRACE` et `post` en casse mixte non rejetées) ;
1 nouveau test garantit que les requêtes GET légitimes sans données ne sont
pas rejetées.

## Vulnérabilités non applicables (justifiées)

### CVE-2026-79752 — SQL injection dans FunctionsBuilder

Les méthodes vulnérables (`cast`, `extract`, `datePart`, `dateAdd`) appartiennent
à `Cake\Database\FunctionsBuilder`, **introduit en CakePHP 3.0** et absent de
2.x. L'injection exige de passer des données utilisateur dans les paramètres
`$dataType` / `$part` / `$unit` construisant du SQL brut. L'équivalent 2.x
(`DboSource::calculate()` et construction d'expressions SQL) est un usage
interne avec quotation des identifiants ; aucun motif équivalent injectable
n'a été identifié dans 2.10.24. Verdict : **non applicable** (plage
`< 4.5.12` remontée uniquement par comparaison de versions).

### CVE-2020-15400 — Génération du token CSRF

Le vecteur (fix 4.0.6 / PR #14431, opt-in `verifyTokenSource` en 3.10.3)
concerne un token CSRF **stocké en cookie** : un attaquant avec XSS (ou accès
physique) peut figer un token cookie qu'il connaît pour neutraliser la
protection CSRF. En 2.x, les tokens CSRF (`SecurityComponent`) sont stockés
**côté serveur en session** (`Session::write('_Token')`) — aucun token CSRF
n'est exposé dans un cookie manipulable, le vecteur de fixation n'existe pas.
La génération 2.x (`hash('sha512', Security::randomBytes(16))`) offre 128 bits
d'entropie côté serveur. Verdict : **non applicable**.

### Correctifs adjacents examinés (sans advisory publiée)

- **#19513 — Strip CRLF dans SmtpTransport (4.5.12)** : en 2.x, `MailTransport`
  retire déjà `\r`/`\n` des headers et du sujet, et les headers envoyés en
  DATA SMTP (`from/sender/replyTo/readReceipt/to/cc/subject`) passent tous par
  `mb_encode_mimeheader()` (CRLF encodés en base64) ou par `Validation::email()`.
  L'injection SMTP par CRLF n'est pas exploitable en 2.x. Non applicable.
- **#19539 — `urldecodeSegments` dans le routage (4.5.12)** : correctif de
  cohérence 4.x sans advisory ; aucune classe de vulnérabilité équivalente
  identifiée dans le routage 2.10.24. Non applicable.

## composer audit vs état réel du fork

`composer audit` se base sur les identifiants d'advisories et les plages de
versions, **pas sur le code**. Les 4 advisories ci-dessus continueront donc
d'apparaître pour `cakephp/cakephp 2.10.24.x` tant que les plages upstream
n'incluent pas les versions du fork. Deux états distincts doivent être tenus :

1. **Résultat de `composer audit`** : 4 advisories remontées (attendu, voir
   tableau ci-dessus — 2 faussement applicables par plage de versions, 2
   applicables et corrigées dans le fork).
2. **État réel vérifié** : les 2 vulnérabilités applicables sont corrigées et
   testées dans ce fork (voir BACKPORTS.md).

Un audit sans alerte ne constitue pas une preuve de sécurité ; inversement,
la persistance des alertes ne signifie pas que le fork reste vulnérable.

## Risques résiduels

- **CSRF sur requêtes GET** : par design (2.x et versions supérieures), les
  actions d'état accessibles en GET ne sont pas couvertes par la protection
  CSRF. Recommandation : utiliser `requirePost()` (ou l'équivalent) pour toute
  action modifiant l'état, et ne jamais mettre de données d'état en GET.
- **Dépendances de développement** : le verrou de dev du framework contient
  phpunit 3.7.38, php-code-coverage 1.2.18, symfony/yaml 2.8.52 et
  squizlabs/php_codesniffer 1.5.6, visés par des advisories (dont
  CVE-2017-9841). Ce sont des `require-dev` : Composer n'installe jamais les
  dépendances de dev d'une dépendance — elles ne sont pas livrées en
  production. À ne pas utiliser telles quelles sur un serveur exposé.
- **EOL CakePHP 2.x** : plus de correctifs officiels. Surveiller les
  advisories cakephp/cakephp 4.x/5.x ; toute nouvelle vulnérabilité dont le
  motif existe en 2.x devra être évaluée pour backport (processus identique à
  celui documenté ici).
- **Compatibilité PHP 7.4** : le framework est fonctionnel sur PHP 7.4
  (`composer.json` : `>=5.3.0,<8.0.0`) ; une partie de la suite de tests
  upstream contient des incompatibilités PHP 7.4 préexistantes (documentées
  dans le rapport de release). Les échecs constatés sont identiques avant et
  après les correctifs de ce fork.

## Signalement

Pour signaler une vulnérabilité dans ce fork, contacter l'équipe de
maintenance interne. Ne pas ouvrir d'issue publique avec des détails
d'exploitation.
# BACKPORTS — Suivi des correctifs de sécurité

Fork basé sur CakePHP **2.10.24** (dernière version officielle, branche `2.x`
EOL). Ce tableau fait le lien entre chaque advisory, le correctif officiel
upstream et l'état du fork.

| CVE / GHSA | Composant | Sévérité | Version corrigée | Commit fork | Correctif officiel | Statut |
|---|---|---|---|---|---|---|
| CVE-2026-48820 / GHSA-wpvj-hjcr-h3p2 | View (`element()` et résolveurs de fichiers) | Moderate 6.3 | 2.10.24.1 | `6077ec17cf` | 4.5.11 — `2af17bd02f` | Corrigé |
| CVE-2015-8379 / GHSA-556q-h4vr-pgh2 | SecurityComponent (CSRF / `_method`) | High 8.8 | 2.10.24.2 | `2fdb53ae07` | 3.1.5 — `0f818a23a8` | Corrigé |
| CVE-2026-79752 / GHSA-vjqc-q4mp-2rvf | `Cake\Database\FunctionsBuilder` | Critical 9.2 | — | — | 4.5.12 / 5.3.7 — `3f4d13ea42` | Non applicable |
| CVE-2020-15400 / GHSA-j33j-fg2g-mcv2 | `CsrfProtectionMiddleware` (token CSRF) | Moderate 4.3 | — | — | 4.0.6 (`#14431`) / 3.10.3 (`309c8990`) | Non applicable |

## Détail des correctifs

### CVE-2026-48820 — View::element() sans check de confinement de chemin

- **Advisory** : <https://github.com/advisories/GHSA-wpvj-hjcr-h3p2>
- **Impact** : `_getElementFileName()` ne vérifiait pas que le chemin résolu
  reste dans les chemins de templates ; un nom d'element forgé à partir de
  données utilisateur permet d'inclure des fichiers PHP arbitraires (CWE-22).
- **Correctif officiel** : 4.5.11, commit `2af17bd02f` (*Fix a potential file
  handling weakness in element()*), backport de #19447 / #19459.
- **Backport 2.x** : commit `6077ec17cf` (release `2.10.24.1`).
  - `lib/Cake/View/View.php` : nouvelle méthode `_checkFilePath($file,
    $plugin)` ; contrôle appliqué à `_getViewFileName()`,
    `_getLayoutFileName()` et `_getElementFileName()` (les trois résolveurs,
    conformément à l'architecture 4.x).
  - `lib/Cake/Test/Case/View/ViewTest.php` : `testElementPathEscape`,
    `testElementPathInsideTemplatePaths`, `testLayoutPathEscape`,
    `testViewPathEscape`.
- **Tests** : `ViewTest` — 85 tests OK (baseline 81 + 4). Avant correctif, les
  3 tests d'échappement échouaient (inclusion hors template paths). Les
  suites `Routing/Dispatcher` (56 tests) et `Controller/Controller` (64
  tests) : échecs identiques à la baseline PHP 7.4 (aucune régression).

### CVE-2015-8379 — Contournement CSRF via le paramètre `_method`

- **Advisory** : <https://github.com/advisories/GHSA-556q-h4vr-pgh2>
- **Impact** : en 2.10.24, une requête à méthode forgée (paramètre `_method`
  ou header `X-HTTP-Method-Override`) avec données POST vidées sautait
  `validatePost` et la validation CSRF de `SecurityComponent` — un attaquant
  pouvait déclencher une action d'état sans jeton CSRF (CWE-352).
- **Correctif officiel** : 3.1.5, commit `0f818a23a8` (*Only allow GET, HEAD,
  OPTIONS to not have CSRF tokens*). La lignée 2.x avait déjà vidé les données
  POST des méthodes forgées non standard depuis janvier 2016 (`bd53ef01a6`,
  *Better method overriding emulation for GET*) mais la validation restait
  sautée : ce backport comble le résidu.
- **Backport 2.x** : commit `2fdb53ae07` (release `2.10.24.2`).
  - `lib/Cake/Controller/Component/SecurityComponent.php` : la gate de
    validation passe de
    `$hasData = (data || is(put/post/delete/patch))` à
    `$hasData = (data || !isSafeMethod)` avec
    `isSafeMethod = REQUEST_METHOD absent ou ∈ {GET, HEAD, OPTIONS}`.
  - `lib/Cake/Test/Case/Controller/Component/SecurityComponentTest.php` :
    `testCsrfNotSkippedForForgedMethod` (méthode `TRACE` forgée),
    `testCsrfNotSkippedForForgedMethodMixedCase` (`post` en casse mixte),
    `testCsrfSkippedForGetRequestWithoutData` (garde-fou GET) ;
    `testRequireSecureSucceed` ajusté (`'Secure'` → `'GET'`, l'ancienne
    valeur encodant l'ancien comportement).
- **Tests** : `SecurityComponentTest` — 81 tests ; 7 échecs + 8 erreurs
  strictement identiques à la baseline PHP 7.4 (aucune régression) ; les 2
  tests de bypass échouaient avant correctif et passent après. Suite
  `Controller/Controller` : échecs identiques à la baseline.

## Justification des non-applicabilités

### CVE-2026-79752 — FunctionsBuilder SQL injection

`Cake\Database\FunctionsBuilder` (méthodes `cast`, `extract`, `datePart`,
`dateAdd`) n'existe pas en CakePHP 2.x — le composant est introduit en 3.0.
L'injection requiert des données utilisateur dans les paramètres `$dataType`
/ `$part` / `$unit` servant à construire du SQL. Le mécanisme 2.x équivalent
(`DboSource::calculate()`) est alimenté par des appels internes avec quotation
des identifiants (`name()`). Aucun motif équivalent injectable n'a été trouvé
dans 2.10.24. L'advisory est remontée par Composer uniquement parce que la
plage `< 4.5.12` couvre toute version antérieure par comparaison de versions.

### CVE-2020-15400 — Génération du token CSRF

Le correctif officiel (4.0.6 / #14431 ; opt-in `verifyTokenSource` en 3.10.3)
signe les tokens CSRF par HMAC du sel applicatif pour empêcher la **fixation
d'un token stocké en cookie** par un attaquant disposant de XSS ou d'un accès
physique. En CakePHP 2.x, les tokens CSRF de `SecurityComponent` sont stockés
en session côté serveur (`Session::write('_Token')`) et ne transitent jamais
par un cookie : le vecteur de manipulation n'existe pas. La génération 2.x
(`hash('sha512', Security::randomBytes(16))`) est d'entropie suffisante.

## Éléments examinés hors advisory

| Référence | Contenu | Verdict 2.x |
|---|---|---|
| `b67b622457` (4.5.12, #19513) | Strip CRLF des headers dans SmtpTransport | Non exploitable en 2.x : `MailTransport` retire déjà `\r`/`\n`, et les headers DATA SMTP passent par `mb_encode_mimeheader()` (CRLF encodés) ou `Validation::email()` |
| `a6b66baba1` (4.5.12, #19539) | `urldecodeSegments()` dans le routage | Correctif de cohérence 4.x sans advisory ; pas de classe de vulnérabilité équivalente en 2.x |

## Vulnérabilités restant à corriger

Aucune à ce jour parmi les advisories remontées par `composer audit` pour
`cakephp/cakephp 2.10.24`. Les 4 advisories continuent d'apparaître dans
l'audit (plages de versions upstream) ; voir la section dédiée de
[SECURITY.md](SECURITY.md). Toute nouvelle advisory cakephp/cakephp 4.x/5.x
devra être évaluée pour applicabilité en 2.x.
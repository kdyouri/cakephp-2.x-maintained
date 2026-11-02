<?php
/**
 * String handling methods.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Utility
 * @since         CakePHP(tm) v 1.2.0.5551
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
App::uses('CakeText', 'Utility');

/**
 * The legacy String class cannot be declared anymore: "String" is a reserved
 * class name since PHP 7.0 and declaring it is a fatal error. Code referencing
 * String should use CakeText instead, which has been the documented
 * replacement since CakePHP 2.7.
 *
 * This file still exists so that App::uses('String', 'Utility') autoloading
 * produces a helpful notice instead of a fatal parse error.
 *
 * @package Cake.Utility
 * @deprecated 3.0.0 Deprecated since version 2.7, use CakeText class instead.
 */

trigger_error(__d('cake_dev', 'The String class cannot be declared since PHP 7.0 reserved the name, use CakeText instead.'), E_USER_NOTICE);
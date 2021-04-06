<?php
declare(strict_types=1);

/**
 * Passbolt ~ Open source password manager for teams
 * Copyright (c) Passbolt SA (https://www.passbolt.com)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Passbolt SA (https://www.passbolt.com)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.passbolt.com Passbolt(tm)
 * @since         3.3.0
 */
namespace App\Database\Type;

use Cake\Database\Driver;
use Cake\Database\Type\StringType;

class TemplateVarsType extends StringType
{
    /**
     * Decodes a JSON string
     *
     * @param mixed $value json string to decode
     * @param Driver $driver database driver
     * @return mixed|null|string|void
     */
    public function toPHP($value, Driver $driver)
    {
        if ($value === null) {
            return;
        }

        if (is_a($driver, Driver\Postgres::class)) {
            $value = str_replace("~~NULL_BYTE~~", "\0", $value);
        }

        return unserialize($value);
    }

    /**
     * Marshal - Encodes a JSON string
     *
     * @param mixed $value json string to decode
     * @return mixed|null|string
     */
    public function marshal($value): ?string
    {
        if ($value === null) {
            return null;
        }

        return serialize($value);
    }

    /**
     * Returns the JSON representation of a value for Postgres
     * Serialize per default.
     *
     * @param mixed $value string or object to encode
     * @param Driver $driver database driver
     * @return null|string
     */
    public function toDatabase($value, Driver $driver)
    {
        if ($value === null) {
            return null;
        }

        if (is_a($driver, Driver\Postgres::class)) {
            $value = str_replace("\0", "~~NULL_BYTE~~", $value);
        }

        return $value;
    }

    /**
     * Returns whether the cast to PHP is required to be invoked
     *
     * @return bool always true
     */
    public function requiresToPhpCast()
    {
        return true;
    }
}

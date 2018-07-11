<?php
/**
 * @copyright Copyright (c) 2018 Flávio Gomes da Silva Lisboa <flavio.lisboa@fgsl.eti.br>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\User_SAML\Strategies;

abstract class AbstractStrategyManager {
	/**
	 * @param string $dir
	 * @param string $onlyClassName
	 * @return object|NULL
	 */
	public static function getStrategy($onlyClassName = false)
	{
		$dir = static::getStrategyDir();
		$files = array_diff(scandir($dir), ['..', '.']);
		foreach($files as $file){
			// Only one strategy is expected, then first matched is returned
			if (substr($file,-12) === 'Strategy.php' && substr($file,0,8) !== 'Abstract'){
				$namespace = get_called_class();
				$tokens = explode('\\', $namespace);
				unset($tokens[count($tokens)-1]);
				$namespace = implode('\\', $tokens);
				$strategy = $namespace . '\\' . substr($file,0,strlen($file)-4);
				return ($onlyClassName ? $strategy : new $strategy());
			}
		}
		return null;
	}
	
	/**
	 * @return string
	 */
	public static function getStrategyDir()
	{
		throw new \NotFoundException(get_called_class() . ' must implement ' . __METHOD__);
	}
}
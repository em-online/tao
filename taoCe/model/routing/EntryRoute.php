<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoCe\model\routing;

use oat\tao\model\routing\AbstractRoute;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Route represents request to the root of tao
 *
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class EntryRoute extends AbstractRoute
{
    public function resolve(ServerRequestInterface $request)
    {
        $relativeUrl = \tao_helpers_Request::getRelativeUrl($request->getRequestTarget());
        if ($relativeUrl === '') {
            return 'oat\\taoCe\\actions\\Main@rootEntry';
        }
        return null;
    }

    /**
     * Get controller namespace prefix
     * @return string
     */
    public static function getControllerPrefix()
    {
        return '';
    }
}

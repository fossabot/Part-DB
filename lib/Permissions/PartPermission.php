<?php
/*
    Part-DB Version 0.4+ "nextgen"
    Copyright (C) 2017 Jan Böhmer
    https://github.com/jbtronics

    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
*/

namespace PartDB\Permissions;

class PartPermission extends BasePermission
{
    const CREATE = "create";
    const READ  = "read";
    const EDIT  = "edit";
    const MOVE  = "move";
    const DELETE = "delete";
    const SEARCH    = "search";
    const ALL_PARTS = "all_parts";
    const ORDER_PARTS = "order_parts";
    const NO_PRICE_PARTS = "no_price_parts";
    const OBSOLETE_PARTS = "obsolete_parts";
    const UNKNONW_INSTOCK_PARTS = "unknown_instock_parts";
    const CHANGE_FAVORITE = "change_favorite";
    const SHOW_FAVORITE_PARTS = "show_favorite_parts";

    /**
     * Returns an array of all available operations for this Permission.
     * @return array All availabel operations.
     */
    public static function listOperations()
    {
        /**
         * Dont change these definitions, because it would break compatibility with older database.
         * However you can add other definitions, the return value can get high as 30, as the DB uses a 32bit integer.
         */
        $operations = array();
        $operations[] = static::buildOperationArray(0, static::READ, _("Anzeigen"));
        $operations[] = static::buildOperationArray(2, static::EDIT, _("Bearbeiten"));
        $operations[] = static::buildOperationArray(4, static::CREATE, _("Anlegen"));
        $operations[] = static::buildOperationArray(6, static::MOVE, _("Verschieben"));
        $operations[] = static::buildOperationArray(8, static::DELETE, _("Löschen"));
        $operations[] = static::buildOperationArray(10, static::SEARCH, _("Suchen"));
        $operations[] = static::buildOperationArray(12, static::ALL_PARTS, _("Alle Teile auflisten"));
        $operations[] = static::buildOperationArray(14, static::ORDER_PARTS, _("Zu bestellende Teile auflisten"));
        $operations[] = static::buildOperationArray(16, static::NO_PRICE_PARTS, _("Teile ohne Preis auflisten"));
        $operations[] = static::buildOperationArray(18, static::OBSOLETE_PARTS, _("Obsolete Teile auflisten"));
        $operations[] = static::buildOperationArray(20, static::UNKNONW_INSTOCK_PARTS, _("Teile mit unbekanntem Lagerbestand auflisten"));
        $operations[] = static::buildOperationArray(22, static::CHANGE_FAVORITE, _("Favoritenstatus ändern"));
        $operations[] = static::buildOperationArray(24, static::SHOW_FAVORITE_PARTS, _("Favorisierte Bauteile auflisten"));

        return $operations;
    }

    protected function modifyValueBeforeSetting($operation, $new_value, $data)
    {
        //Set read permission, too, when you get edit permissions.
        if (($operation == static::EDIT
                || $operation == static::DELETE
                || $operation == static::MOVE
                || $operation == static::CREATE
                || $operation == static::SEARCH
                || $operation == static::ALL_PARTS)
            && $new_value == static::ALLOW) {
            return parent::writeBitPair($data, static::opToBitN(static::READ), static::ALLOW);
        }

        return $data;
    }
}

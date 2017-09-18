<?php
/**
 * Created by PhpStorm.
 * User: janhb
 * Date: 18.09.2017
 * Time: 15:48
 */

namespace PartDB\Permissions;


class ToolsPermission extends BasePermission
{
    const IMPORT        = "import";
    const LABELS        = "labels";
    const CALCULATOR    = "calculator";
    const FOOTPRINTS    = "footprints";
    const IC_LOGOS      = "ic_logos";
    const STATISTICS    = "statistics";

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
        $operations[] = static::buildOperationArray(0, static::IMPORT, _("Import"));
        $operations[] = static::buildOperationArray(2, static::LABELS, _("Labels"));
        $operations[] = static::buildOperationArray(4, static::CALCULATOR, _("Widerstandsrechner"));
        $operations[] = static::buildOperationArray(6, static::FOOTPRINTS, _("Footprints"));
        $operations[] = static::buildOperationArray(8, static::IC_LOGOS, _("IC-Logos"));
        $operations[] = static::buildOperationArray(10, static::STATISTICS, _("Statistik"));

        return $operations;
    }
}
<?php
        
    /*
     * we have multiple states:
     * choose file (default)
     * detect and select entries
     * push entries in database
     */
    include ("lib.php");
    partdb_init();


    /* work around for older php: bevore 5.3.0
    */
    if ( !function_exists( 'str_getcsv')) {
        function str_getcsv( $str, $delim=',', $enclose='"', $preserve=false) {
            $resArr = array();
            $n = 0;
            $expEncArr = explode( $enclose, $str);
            foreach( $expEncArr as $EncItem)
            {
                if( $n++%2)
                {
                    array_push( $resArr, array_pop( $resArr) . ( $preserve?$enclose:'') . $EncItem.( $preserve?$enclose:''));
                }
                else
                {
                    $expDelArr = explode( $delim, $EncItem);
                    array_push( $resArr, array_pop( $resArr) . array_shift( $expDelArr));
                    $resArr = array_merge( $resArr, $expDelArr);
                }
            }
            return $resArr; 
        }
    }

    // set action to default, if not exists
    $action    = ( isset( $_REQUEST["action"]) ? $_REQUEST["action"] : 'default');
    $show_file = false;

    // determine action
    // this is a kind of a state machine 
    if ( strcmp( $action, "import_file")  == 0 ) { $action="import_file"; }
    if ( isset( $_REQUEST['check']))             { $action="check_data";  }
    if ( isset( $_REQUEST['import']))            { $action="commit_data"; }


    // data processing

    // catch data arrays, if defined
    $active    = ( isset( $_REQUEST['active'])    ? $_REQUEST['active'] : array());
    $category  = ( isset( $_REQUEST['category'])  ? $_REQUEST['category'] : array());
    $name      = ( isset( $_REQUEST['name'])      ? $_REQUEST['name'] : array());
    $nr        = ( isset( $_REQUEST['nr'])        ? $_REQUEST['nr'] : array());
    $count     = ( isset( $_REQUEST['count'])     ? $_REQUEST['count'] : array());
    $footprint = ( isset( $_REQUEST['footprint']) ? $_REQUEST['footprint'] : array());
    $storeloc  = ( isset( $_REQUEST['storeloc'])  ? $_REQUEST['storeloc'] : array());
    $supplier  = ( isset( $_REQUEST['supplier'])  ? $_REQUEST['supplier'] : array());
    $sup_part  = ( isset( $_REQUEST['sup_part'])  ? $_REQUEST['sup_part'] : array());
    $comment   = ( isset( $_REQUEST['comment'])   ? $_REQUEST['comment'] : array());


    // try to catch the file 
    if ( $action == "import_file") 
    {
        if (is_uploaded_file( $_FILES['import_file']['tmp_name']))
        {
            // read file content
            $filename    = $_FILES['import_file']['name']; 
            $filestring  = file_get_contents( $_FILES['import_file']['tmp_name']);
            // added for correct handling from excel 2010 files
            $filestring  = mb_convert_encoding($filestring, 'UTF-8', mb_detect_encoding($filestring, 'UTF-8, ISO-8859-1', true));
            $content_arr = explode("\n", $filestring);
            $action      = "check_data";
            $show_file   = true;
        }
        else
        {
            $action = "error";
            $error  = "Upload fehlgeschlagen";
        }
        if (($_FILES["import_file"]["type"] != "text/plain") &&
            ($_FILES["import_file"]["type"] != "application/vnd.ms-excel"))
        {
            $action = "error";
            $error  = "falscher Dateityp: ".$_FILES["import_file"]["type"]." (statt text/plain)";
        }
        if ($_FILES["import_file"]["error"] > 0)
        {
            $action = "error";
            $error  = $_FILES["file"]["error"];
        }
       
        // fill data_arr
        $data_arr = array();
        foreach ($content_arr as $line_num => $line) 
        {
            // remove whitespaces etc.
            $line = trim( $line);

            // ignore line with comments, or empty lines
            if ( (strlen( $line) > 0) && ( $line[0] !== '#'))
            {
                // combine line nr. and stuff to an array
                $data_arr[] = array_merge( array( 0 => $line_num), str_getcsv( $line, $_REQUEST['divider']));
            }
        }
        
        // extract data from data_arr
        // fill the arrays with initial values
        foreach ($data_arr as $key => $data) 
        {
            $nr[$key]        = isset( $data[0]) ? $data[0] : '';
            $category[$key]  = isset( $data[0]) ? $data[1] : '';
            $name[$key]      = isset( $data[1]) ? $data[2] : '';
            $count[$key]     = isset( $data[2]) ? $data[3] : '';
            $footprint[$key] = isset( $data[3]) ? $data[4] : '';
            $storeloc[$key]  = isset( $data[4]) ? $data[5] : '';
            $supplier[$key]  = isset( $data[5]) ? $data[6] : '';
            $sup_part[$key]  = isset( $data[6]) ? $data[7] : '';
            $comment[$key]   = isset( $data[7]) ? $data[8] : '';
        }
        
    } // end import_file


    // interpret import file content
    if ( $action == "check_data") 
    {
        // predefines
        $ok     = "&nbsp;&#x2714;"; // check mark
        $halfok = "(&#x2714;)";     // (check mark)
        $bad    = "&#x2718;";       // X

        // empty defaults
        $add_category  = array();
        $add_footprint = array();
        $add_storeloc  = array();
        $add_supplier  = array();

        // do sanity checks
        foreach ($nr as $key => $data) 
        {
            $active[$key]            = true;
            $missing_name[$key]      = $ok;
            $missing_count[$key]     = $ok;
            $missing_category[$key]  = $ok;
            $missing_footprint[$key] = $ok;
            $missing_storeloc[$key]  = $ok;
            $missing_supplier[$key]  = $ok;
            

            // empty name?
            if ( strlen( $name[$key]) == 0)       
            { 
                $active[$key]       = false; 
                $missing_name[$key] = $bad; 
            }
            // count not numeric?
            if ( !( is_numeric( $count[$key])))
            { 
                $active[$key]        = false; 
                $missing_count[$key] = $bad; 
            }
            // missing category?
            if ( strlen( $category[$key]) == 0)
            { 
                $active[$key]            = false;
                $missing_category[$key]  = $bad;
            }
            else
            {
                // category not found in database
                if (! (check_categories( $category[$key])))
                {
                    $missing_category[$key] = $halfok;
                    $add_category[]         = $category[$key];
                }
            }
            // missing footprint?
            if ( strlen( $footprint[$key]) == 0)
            { 
                $active[$key]            = false;
                $missing_footprint[$key] = $bad;
            }
            else
            {
                if (! (check_footprint( $footprint[$key])))
                {
                    $missing_footprint[$key] = $halfok;
                    $add_footprint[]         = $footprint[$key];
                }
            }
            // missing storeloc?
            if ( strlen( $storeloc[$key]) == 0)
            { 
                $active[$key]           = false;
                $missing_storeloc[$key] = $bad;
            }
            else
            {
                if (! (check_storeloc( $storeloc[$key])))
                {
                    $missing_storeloc[$key] = $halfok;
                    $add_storeloc[]         = $storeloc[$key];
                }
            }
            // missing supplier?
            if ( strlen( $supplier[$key]) == 0)
            { 
                $active[$key]           = false;
                $missing_supplier[$key] = $bad;
            }
            else
            {
                if (! (check_supplier( $supplier[$key])))
                {
                    $missing_supplier[$key] = $halfok;
                    $add_supplier[]         = $supplier[$key];
                }
            }
        } // end foreach

        // suppress multiple occurence
        $add_category  = array_unique( $add_category);
        $add_footprint = array_unique( $add_footprint);
        $add_storeloc  = array_unique( $add_storeloc);
        $add_supplier  = array_unique( $add_supplier);
    } // end check_data


    // push all stuff into database
    if ( $action == "commit_data" )
    {
        // fetch missign category, footprint, etc.
        $open_category  = explode( ';', $_REQUEST["add_category"]);
        $open_footprint = explode( ';', $_REQUEST["add_footprint"]);
        $open_storeloc  = explode( ';', $_REQUEST["add_storeloc"]);
        $open_supplier  = explode( ';', $_REQUEST["add_supplier"]);


        // add to database
        foreach ($open_category as $entry)
        {
		    $query = "INSERT INTO categories (name, parentnode) VALUES (". smart_escape($entry) .",0 );";
		    mysql_query ($query);
            $add_category[] = $entry;
        }

        foreach ($open_footprint as $entry)
        {
		    $query = "INSERT INTO footprints (name) VALUES (". smart_escape($entry) .");";
		    mysql_query ($query);
            $add_footprint[] = $entry;
        }

        foreach ($open_storeloc as $entry)
        {
		    $query = "INSERT INTO storeloc (name) VALUES (". smart_escape($entry) .");";
		    mysql_query ($query);
            $add_storeloc[] = $entry;
        }

        foreach ($open_supplier as $entry)
        {
		    $query = "INSERT INTO suppliers (name) VALUES (". smart_escape($entry) .");";
		    mysql_query ($query);
            $add_supplier[] = $entry;
        }
        
        // add selected parts to database
        foreach ($nr as $key => $data) 
        {
            if ($active[$key] == "true")
            {
                // catch the right id's
                $category_id  = get_category_id(  $category[$key]);
                $footprint_id = get_footprint_id( $footprint[$key]);
                $storeloc_id  = get_storeloc_id(  $storeloc[$key]);
                $supplier_id  = get_supplier_id(  $supplier[$key]);

                $query = 
                    "INSERT INTO parts ".
                    "(id_category, ".
                    "name, ".
                    "instock, ".
                    "mininstock, ".
                    "comment, ".
                    "id_footprint, ".
                    "id_storeloc, ".
                    "id_supplier, ".
                    "supplierpartnr) ".
                    "VALUES (". 
                    smart_escape($category_id)    .",".
                    smart_escape($name[$key])     .",".
                    smart_escape($count[$key])    .",".
                    smart_escape("0")             .",".
                    smart_escape($comment[$key])  .",".
                    smart_escape($footprint_id)   .",".
                    smart_escape($storeloc_id)    .",".
                    smart_escape($supplier_id)    .",".
                    smart_escape($sup_part[$key]) .");";
                       
		        mysql_query ($query);
                // collect name for reporting
                $add_part[] = $name[$key];
            }
        }

    } // end commit_data 

    // start data presentation
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
          "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <title>Import</title>
    <link rel="StyleSheet" href="css/partdb.css" type="text/css">
</head>
<body class="body">

<?php
    if ($action == "default") {
?>

<table class="table">
    <tr>
        <td class="tdtop">
        Datei ausw&auml;hlen
        </td>
    <tr>
    <tr>
        <td>
            <form enctype="multipart/form-data" action="" method="post">
            <input type="hidden" name="action" value="import_file">
            Dateityp: <select name="type" disabled>
                <option selected>csv</option>
                <option >xml</option> <!-- xml not implemented jet -->
            </select>
            &nbsp;&nbsp;&nbsp;
            Trennzeichen: <input type="text" name="divider" size="1" maxlength="1" value=";">
            <br>
            <input type="file"   name="import_file" size="30">
            &nbsp;&nbsp;&nbsp;
            <input type="submit" value="Importieren">
            </form>
            <br>
        </td>
    </tr>
    <tr>
        <td class="tdtop">
        Beispiel f&uuml;r den Dateiaufbau (csv)
        </td>
    </tr>
    <tr>
        <td>
        <pre>
# Kategorie; Name; Anzahl; Footprint; Lagerort; Lieferant; Bestellnummer; Kommentar
Dioden;1N4004;10;THT;Kiste;Reichelt;1N 4004;DO41, 400V 1A
Controller;ATMega 8;1;DIP28;Kiste;Reichelt;ATMEGA 8-16 DIP
Oszillatoren;Quarzoszillator 8 MHz;1;THT;Kiste;Reichelt;OSZI 8,000000
Schaltkreise;MAX 232;1;DIP16;Kiste;Reichelt;MAX 232 EPE
        </pre>
        </td>
    </tr>
</table>

<?php
    }
    if ($show_file) {
?>
<table class="table">
    <tr>
        <td class="tdtop">
        Daten importieren (<?php print $filename ?>)
        </td>
    </tr>
    <tr>
        <td class="tdtext">
        <?php
        foreach ($content_arr as $line_num => $line) 
        {
            print "#{$line_num}: ". htmlspecialchars($line) ."<br>\n";
        }
        ?>
        </td>
    </tr>
</table>
<?php
    }
    if ($action == "check_data") {
?>

<br>

<form action="" method="post" enctype="multipart/form-data">

<input type="hidden" name="add_category"  value='<?php print implode( ';', $add_category); ?>'>
<input type="hidden" name="add_footprint" value='<?php print implode( ';', $add_footprint); ?>'>
<input type="hidden" name="add_storeloc"  value='<?php print implode( ';', $add_storeloc); ?>'>
<input type="hidden" name="add_supplier"  value='<?php print implode( ';', $add_supplier); ?>'>

<table class="table">
	<tr>
        <td colspan="10" class="tdtop">
        Daten pr&uuml;fen
</td>
    </tr>
    <tr>
        <td colspan="10">
        <?php 
            if ( sizeof($add_category) > 0 )
            {
                print "fehlende Kategorien: ". implode(', ', $add_category) ."<br>";
            }

            if ( sizeof($add_footprint) > 0 )
            {
                print "fehlende Footprints: ". implode(', ', $add_footprint) ."<br>";
            }

            if ( sizeof($add_storeloc) > 0 )
            {
                print "fehlende Lagerorte: ". implode(', ', $add_storeloc) ."<br>";
            }

            if ( sizeof($add_supplier) > 0 )
            {
                print "fehlende Lieferanten: ". implode(', ', $add_supplier) ."<br>";
            }
          ?>
        </td>
    </tr>
	<tr class="trcat">
        <td>Import</td>
        <td>#</td>
        <td>Kategorie</td>
        <td>Name</td> 
        <td>Anzahl<br>
        <td>Footprint</td>
        <td>Lagerort</td>
        <td>Lieferant</td>
        <td>Bestellnr.</td>
        <td>Kommentar</td>
    </tr>
<?php
		$rowcount = 1;
        foreach ($nr as $key => $data) 
        {
			$rowcount++;
			print "<tr class=\"trlist". (($rowcount % 2) + 1) ."\">";
            
            // active and valid checkbox
			print "<td class=\"tdrow0\"><input type=\"checkbox\" name=\"active[{$key}]\" value=\"true\"";
            if ( $active[$key] ) 
            { 
                print " checked";
            }
            else
            { 
                print " disabled";
            }
            print "></td>\n";

            // line number
			print "<td class=\"tdrow1\"><input type=\"hidden\" name=\"nr[{$key}]\" value=\"{$nr[$key]}\">{$nr[$key]}</td>\n";

            // category
			print "<td class=\"tdrow2\" style=\"text-align:left\">";
            print "<input type=\"text\" style=\"width:80%\" name=\"category[$key]\" size=\"15\" value=\"{$category[$key]}\">{$missing_category[$key]}</td>\n";

            // name
			print "<td class=\"tdrow2\" style=\"text-align:left\">";
			print "<input type=\"text\" style=\"width:80%\" name=\"name[$key]\" size=\"15\" value=\"{$name[$key]}\">{$missing_name[$key]}</td>\n";

            // count (in stock)
			print "<td class=\"tdrow2\" style=\"text-align:left\">";
			print "<input type=\"text\" style=\"width:60%\" name=\"count[$key]\" size=\"3\" value=\"{$count[$key]}\">{$missing_count[$key]}</td>\n";

            // footprint
			print "<td class=\"tdrow2\" style=\"text-align:left\">";
			print "<input type=\"text\" style=\"width:60%\" name=\"footprint[$key]\" size=\"5\" value=\"{$footprint[$key]}\">{$missing_footprint[$key]}</td>\n";

            // storeloc
			print "<td class=\"tdrow2\" style=\"text-align:left\">";
			print "<input type=\"text\" style=\"width:75%\" name=\"storeloc[$key]\" size=\"8\" value=\"{$storeloc[$key]}\">{$missing_storeloc[$key]}</td>\n";

            // supplier
			print "<td class=\"tdrow2\" style=\"text-align:left\">";
			print "<input type=\"text\" style=\"width:75%\" name=\"supplier[$key]\" size=\"8\" value=\"{$supplier[$key]}\">{$missing_supplier[$key]}</td>\n";

            // supplierpartnr 
			print "<td class=\"tdrow2\" style=\"text-align:left\">";
			print "<input type=\"text\" style=\"width:90%\" name=\"sup_part[$key]\" size=\"10\" value=\"{$sup_part[$key]}\"></td>\n";

            // comment 
			print "<td class=\"tdrow2\" style=\"text-align:left\">";
			print "<input type=\"text\" style=\"width:90%\" name=\"comment[$key]\" size=\"10\" value=\"{$comment[$key]}\"></td>\n";

            print "</tr>\n";
        }
        
    ?>
        </td>
    </tr>
    <tr>
    <td colspan="10" class="trtext" align="center">
        <input type="submit" name="check"  value="Daten pr&uuml;fen">
        <input type="submit" name="import" value="Import">
    </td>
    </tr>
</table>
</form>


<?php
    }
    if ($action == "commit_data") {

?>
<table class="table">
	<tr>
        <td class="tdtop">
        Datenbank aktualisiert
        </td>
    </tr>
    <tr>
        <td>
        <?php 
            if ( sizeof($add_category) > 0 )
            {
                print "Kategorien hinzugef&uuml;gt: ". implode(', ', $add_category) ."<br>";
            }
            if ( sizeof($add_footprint) > 0 )
            {
                print "Footprints hinzugef&uuml;gt: ". implode(', ', $add_footprint) ."<br>";
            }
            if ( sizeof($add_storeloc) > 0 )
            {
                print "Lagerorte hinzugef&uuml;gt: ". implode(', ', $add_storeloc) ."<br>";
            }
            if ( sizeof($add_supplier) > 0 )
            {
                print "Lieferanten hinzugef&uuml;gt: ". implode(', ', $add_supplier) ."<br>";
            }
            if ( sizeof($add_part) > 0 )
            {
                print "Bauteile hinzugef&uuml;gt: ". implode(', ', $add_part) ."<br>";
            }
        ?>
        </td>
    </tr>
</table>
<?php
    }
    if ($action == "error") {

?>
<br>
<table class="table">
    <tr>
        <td class="tdred">
        Fehler: <?php print $error ?>
        </td>
    </tr>
</table>

<?php
    }
?>

</body>
</html>
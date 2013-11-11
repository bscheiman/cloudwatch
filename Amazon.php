<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('memory_limit', -1);
error_reporting(-1);

include_once 'Common.php';

function amazonMonitor($db) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, "http://status.aws.amazon.com");
    $html = curl_exec($ch);

    $dom = new simple_html_dom();
    $html = $dom->load($html);

    $blocks = array("NA_block", "SA_block", "EU_block", "AP_block");
    $regions = array("North America", "South America", "Europe", "Asia Pacific");
    $allStatus = array("Service is operating normally", "Performance issues", "Service disruption", "Informational message");
    $fullStatus = array("Green", "Yellow", "Red", "Blue");
    
    foreach ($html->find('div') as $div) {
        $id = $div->id;

        if (strlen($id) != 8 || !endsWith($id, "_block"))
            continue;

        $table = $div->find('table', 0);
        $first = true;

        $regionName = $regions[array_search($id, $blocks)];

        foreach ($table->find('tr') as $tr) {
            if ($first) {
                $first = false;
                continue;
            }

            $serviceName = trim($tr->find('td', 1)->plaintext);
            $status = trim($tr->find('td', 2)->plaintext, " \t\n\r\0\x0B.");
            $status = array_search($status, $allStatus);

            /*
             * Status:
             * Service is operating normally
             * Performance issues
             * Service disruption
             * Informational message
             */

            if (preg_match("/\((.*)\)/", $serviceName, $match)) {
                $regionName = $match[1];
                $serviceName = trim(preg_replace("/(.*?) \(.*\)/", "$1", $serviceName));
            }

            echo "$regionName / $serviceName: $fullStatus[$status]\n";

            compareDb($db, $serviceName, $regionName, "Amazon", $status, $fullStatus, PUSHOVERUSER_AMAZON, "CloudWatch / Amazon");
        }
    }

    $html = null;
    $dom = null;
}

function endsWith($haystack, $needle) {
    return substr_compare($haystack, $needle, -strlen($needle)) == 0;
}
?>
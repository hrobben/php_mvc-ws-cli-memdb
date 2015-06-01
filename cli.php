<?php
if (ISCLI) {
    if (empty($argv[1])) { die("\n\n\n\nuse index.php -h to let this work\n\n\n\n");}
    if ($argv[1] == '-h' || $argv[1] == '--help') {
        echo "-h  --help         shows this text.\n\n";
        echo "    --article=#id  where \"#id\" is the id number of the acticle, also second arg --xml or --json\n\n";
        echo "    --articles     gives a list of all acticles. or --articlesxml\n\n";
        echo "    --xml          gives XML output.\n\n";
        echo "    --json         gives JSON output.\n\n";
        echo "    --websocket    starts the websocket server.\n\n";
        echo "    use lowercase chars\n\n";
        echo "Type 'yes' to show all articles in standard output: ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim($line) != 'yes') {
            echo "ABORTING!\n";
            exit;
        }
        echo "\n";
        echo "Thank you, showing complete list of articles...\n";
    }
}